<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    // Roles that are fixed to the POS operational interface and never see
    // the Admin Panel - Admins/managers keep the default $redirectTo and
    // navigate to POS themselves (sidebar link / the POS entry picker).
    protected $pos_only_roles = [
        RoleNames::ORDERTAKER,
        RoleNames::POSMANAGER,
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Order Taker / POS Manager users are redirected straight to the POS
     * screen when they hold the required pos.access permission - they have
     * no reason to land on the Admin dashboard first. Every other role falls
     * through to the default $redirectTo ('/home').
     */
    protected function authenticated($request, $user)
    {
        $this->recordLogin($request, $user, 'success');

        $user->update(['last_login_at' => now()]);

        $role = $user->getRoleNames()->first();

        if (in_array($role, $this->pos_only_roles, true) && $user->can('pos.access')) {
            return redirect()->route('pos-screen');
        }

        return null;
    }

    /**
     * AuthenticatesUsers is a trait, not a parent class - `parent::` cannot
     * reach the trait's own sendFailedLoginResponse() once it's overridden
     * here, so its ValidationException behavior is replicated directly
     * instead (matches vendor/laravel/ui/auth-backend/AuthenticatesUsers.php).
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $this->recordLogin($request, User::where('email', $request->input($this->username()))->first(), 'failed', $request->input($this->username()));

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    protected function recordLogin(Request $request, ?User $user, string $status, ?string $email = null)
    {
        LoginHistory::create([
            'login_history_id' => generateUuid(),
            'user_id'          => $user->id ?? null,
            'business_id'      => $user->business_id ?? null,
            'branch_id'        => $user->branch_id ?? null,
            'email'            => $user->email ?? $email,
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'device'           => parseUserAgentDevice($request->userAgent()),
            'status'           => $status,
            'login_at'         => now(),
        ]);
    }

    public function logout(Request $request)
    {
        $user_id = Auth::id();

        if ($user_id) {
            LoginHistory::where('user_id', $user_id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->limit(1)
                ->update(['logout_at' => now()]);
        }

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return redirect('/login');
    }

    protected function loggedOut($request)
    {
        return redirect('/login');
    }
}
