<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\User;
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

    public function __construct(OtpService $otp_service)
    {
        $this->otp_service = $otp_service;
    }

    public function checkEmail(Request $request)
    {
        $validate = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $exists = User::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])->exists();

        return $this->success(Message::SUCCESS, ['exists' => $exists]);
    }

    public function sendOtp(Request $request)
    {
        $validate = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $user = $this->findUser($request->email);

            if ($user && $user->status !== 'active') {
                return $this->error('This account is disabled. Please contact support.');
            }

            $purpose = $user ? 'login' : 'onboarding';

            $this->otp_service->send($request->email, $purpose);

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
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $user = $this->findUser($request->email);
            $purpose = $user ? 'login' : 'onboarding';

            $this->otp_service->verify($request->email, $request->code, $purpose);

            if ($purpose === 'onboarding') {
                $user = User::create([
                    'name' => $request->name ?? explode('@', $request->email)[0],
                    'email' => strtolower(trim($request->email)),
                    'password' => null,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
                $user->assignRole(RoleNames::USER);
            } else {
                if ($user->status !== 'active') {
                    return $this->error('This account is disabled. Please contact support.');
                }
                $user->update(['last_login_at' => now()]);
            }

            $token = $user->createToken('auth')->plainTextToken;

            return $this->success(Message::SUCCESS, [
                'token' => $token,
                'requires_password' => $purpose === 'onboarding',
                'user' => $this->userPayload($user),
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function setPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $user = Auth::user();
        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(Message::SUCCESS, []);
    }

    public function loginWithPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
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

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('auth')->plainTextToken;

        return $this->success(Message::SUCCESS, [
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validate = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $user = $this->findUser($request->email);

        // Always respond the same way whether or not the account exists,
        // so this endpoint can't be used to enumerate registered emails.
        if ($user && $user->status === 'active') {
            try {
                $this->otp_service->send($request->email, 'password_reset');
            } catch (Exception $e) {
                return $this->error($e->getMessage());
            }
        }

        return $this->success('If this email is registered, a reset code has been sent.', []);
    }

    public function resetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
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

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
        ];
    }
}
