<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Concrete\Auth\OtpService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected OtpService $otp_service;

    public function __construct(OtpService $otp_service)
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only(['sendResetLinkEmail', 'resendOtp', 'resetWithOtp']);
        $this->otp_service = $otp_service;
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a password-reset OTP only when the email belongs to an active user.
     * Unregistered addresses stay on this form with a clear error.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));
        $user = $this->findActiveUser($email);

        if (!$user) {
            return back()->withInput()->withErrors([
                'email' => 'This email is not registered.',
            ]);
        }

        try {
            $this->otp_service->send($email, 'password_reset', $user->business_id);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        $request->session()->put('password_reset_email', $email);

        return redirect()->route('password.otp')
            ->with('status', 'A verification code has been sent to your email.');
    }

    public function showOtpForm(Request $request)
    {
        $email = $request->session()->get('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.passwords.reset', compact('email'));
    }

    public function resendOtp(Request $request)
    {
        $email = $request->session()->get('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        $user = $this->findActiveUser($email);
        if (!$user) {
            return back()->withErrors(['code' => 'This email is not registered.']);
        }

        try {
            $this->otp_service->send($email, 'password_reset', $user->business_id);
        } catch (Exception $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'A verification code has been sent to your email.');
    }

    public function resetWithOtp(Request $request)
    {
        $email = $request->session()->get('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->otp_service->verify($email, $request->code, 'password_reset');
        } catch (Exception $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $user = $this->findActiveUser($email);
        if (!$user) {
            return back()->withErrors(['code' => 'This code is invalid or has expired. Please request a new one.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('password_reset_email');

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. Please sign in with your new password.');
    }

    protected function findActiveUser(string $email): ?User
    {
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user || $user->status !== 'active' || $user->is_deleted) {
            return null;
        }

        return $user;
    }
}
