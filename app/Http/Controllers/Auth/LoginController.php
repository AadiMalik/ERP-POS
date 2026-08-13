<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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
        $role = $user->getRoleNames()->first();

        if (in_array($role, $this->pos_only_roles, true) && $user->can('pos.access')) {
            return redirect()->route('pos-screen');
        }

        return null;
    }

    protected function loggedOut($request)
    {
        return redirect('/login');
    }
}
