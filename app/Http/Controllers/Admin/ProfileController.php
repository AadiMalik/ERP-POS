<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Services\Concrete\Admin\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    protected $user_service;

    public function __construct(UserService $user_service)
    {
        $this->middleware('auth');
        $this->user_service = $user_service;
    }

    public function edit()
    {
        $user = Auth::user();
        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $rules = [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        // Scoped to the authenticated user's own id only - the request never
        // supplies (or is allowed to override) which user gets updated, and
        // email is intentionally excluded since it drives session/login auth.
        $this->user_service->save([
            'id'    => Auth::id(),
            'name'  => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect()->route('profile.edit')->with('success', Message::UPDATE);
    }

    public function updatePassword(Request $request)
    {
        $rules = [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed', 'different:current_password'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $user_id = Auth::id();

        $this->user_service->changePassword([
            'id'       => $user_id,
            'password' => $request->password,
        ]);

        // Same pattern as LoginController::logout() - close the open login
        // history row, then invalidate the session so the changed password
        // takes effect immediately and forces a fresh login.
        LoginHistory::where('user_id', $user_id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->limit(1)
            ->update(['logout_at' => now()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Your password has been changed successfully. Please log in again with your new password.');
    }
}
