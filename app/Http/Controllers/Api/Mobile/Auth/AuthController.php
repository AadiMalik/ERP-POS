<?php

namespace App\Http\Controllers\Api\Mobile\Auth;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Concrete\Api\Mobile\MobileCustomerAccountService;
use App\Services\Concrete\Auth\OtpService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ResponseAPI;

    protected $otp_service;
    protected $account_service;

    public function __construct(OtpService $otp_service, MobileCustomerAccountService $account_service)
    {
        $this->otp_service = $otp_service;
        $this->account_service = $account_service;
    }

    public function checkEmail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'business_id' => 'nullable|string|exists:businesses,business_id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        if ($request->filled('business_id')) {
            $exists = $this->account_service->emailExistsForBusiness($request->email, $request->business_id);
        } else {
            $exists = User::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])->exists();
        }

        return $this->success(Message::SUCCESS, ['exists' => $exists]);
    }

    public function sendOtp(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'business_id' => 'required|string|exists:businesses,business_id',
            'phone' => 'nullable|string|min:7|max:20',
            'name' => 'nullable|string|min:2|max:255',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $user = $this->findUser($request->email);
            $has_profile = $user
                ? $this->account_service->emailExistsForBusiness($request->email, $request->business_id)
                : false;

            if ($user && $user->status !== 'active') {
                return $this->error('This account is disabled. Please contact support.');
            }

            if (!$has_profile && $request->filled('phone')) {
                if ($this->account_service->phoneTaken($request->phone, $user?->id)) {
                    return $this->error('This phone number is already registered.');
                }
            }

            $purpose = $has_profile ? 'login' : 'onboarding';

            // Same tenant branding as the website storefront OTP emails.
            $this->otp_service->send($request->email, $purpose, $request->business_id, 'storefront');

            return $this->success(Message::SUCCESS, ['purpose' => $purpose]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }

    public function verifyOtp(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'business_id' => 'required|string|exists:businesses,business_id',
            'name' => 'nullable|string|min:2|max:255',
            'phone' => 'nullable|string|min:7|max:20',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $user = $this->findUser($request->email);
            $has_profile = $user
                ? $this->account_service->emailExistsForBusiness($request->email, $request->business_id)
                : false;
            $purpose = $has_profile ? 'login' : 'onboarding';

            $this->otp_service->verify($request->email, $request->code, $purpose);

            if ($purpose === 'onboarding') {
                if ($request->filled('phone') && $this->account_service->phoneTaken($request->phone, $user?->id)) {
                    return $this->error('This phone number is already registered.');
                }

                if (!$user) {
                    $user = User::create([
                        'name' => $request->name ?? explode('@', $request->email)[0],
                        'email' => strtolower(trim($request->email)),
                        'phone' => $request->phone,
                        'password' => null,
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]);
                    $user->assignRole(RoleNames::USER);
                } else {
                    if ($user->status !== 'active') {
                        return $this->error('This account is disabled. Please contact support.');
                    }
                    $updates = [];
                    if ($request->filled('name')) {
                        $updates['name'] = $request->name;
                    }
                    if ($request->filled('phone')) {
                        $updates['phone'] = $request->phone;
                    }
                    if (!$user->email_verified_at) {
                        $updates['email_verified_at'] = now();
                    }
                    if (!empty($updates)) {
                        $user->update($updates);
                    }
                }

                $this->account_service->ensureProfile($user, $request->business_id);
            } else {
                if ($user->status !== 'active') {
                    return $this->error('This account is disabled. Please contact support.');
                }
                $this->account_service->ensureProfile($user, $request->business_id);
                $user->update(['last_login_at' => now()]);
            }

            $token = $user->createToken('mobile-auth')->plainTextToken;

            return $this->success(Message::SUCCESS, [
                'token' => $token,
                'requires_password' => $purpose === 'onboarding' && empty($user->password),
                'user' => $this->userPayload($user->fresh(), $request->business_id),
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function setPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'password' => MobileCustomerAccountService::passwordRules(),
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $user = Auth::user();
        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(Message::SUCCESS, []);
    }

    public function changePassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => MobileCustomerAccountService::passwordRules(),
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->account_service->changePassword(
                Auth::user(),
                $request->current_password,
                $request->password
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('Password updated successfully. Please sign in again.', []);
    }

    public function loginWithPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'business_id' => 'required|string|exists:businesses,business_id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $user = $this->findUser($request->email);

        if (!$user || empty($user->password) || !Hash::check($request->password, $user->password)) {
            return $this->error('These credentials do not match our records.');
        }

        if ($user->status !== 'active') {
            return $this->error('This account is disabled. Please contact support.');
        }

        try {
            $this->account_service->ensureProfile($user, $request->business_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('mobile-auth')->plainTextToken;

        return $this->success(Message::SUCCESS, [
            'token' => $token,
            'user' => $this->userPayload($user->fresh(), $request->business_id),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'business_id' => 'required|string|exists:businesses,business_id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $user = $this->findUser($request->email);
        $existsForBusiness = $this->account_service->emailExistsForBusiness(
            $request->email,
            $request->business_id
        );

        if (!$user || !$existsForBusiness) {
            return $this->error('This email is not registered.');
        }

        if ($user->status !== 'active') {
            return $this->error('This account is disabled. Please contact support.');
        }

        try {
            $this->otp_service->send($request->email, 'password_reset', $request->business_id, 'storefront');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('A verification code has been sent to your email.', []);
    }

    public function resetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => MobileCustomerAccountService::passwordRules(),
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->otp_service->verify($request->email, $request->code, 'password_reset');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        $user = $this->findUser($request->email);
        if (!$user) {
            return $this->error('These credentials do not match our records.');
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        return $this->success(Message::SUCCESS, []);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(Message::SUCCESS, []);
    }

    private function findUser(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
    }

    private function userPayload(User $user, ?string $business_id = null): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'createdAt' => optional($user->created_at)->toIso8601String(),
        ];

        if ($business_id) {
            try {
                $payload = $this->account_service->getProfilePayload($user, $business_id);
            } catch (Exception $e) {
                // Fall back to basic identity if profile enrichment fails.
            }
        }

        return $payload;
    }
}
