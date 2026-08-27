<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    /**
     * Token-link reset is no longer used. Admin forgot-password is OTP-based
     * (see ForgotPasswordController). Any leftover /password/reset/{token}
     * bookmark is sent back to the request form.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm()
    {
        return redirect()->route('password.request');
    }

    public function reset()
    {
        return redirect()->route('password.request');
    }
}
