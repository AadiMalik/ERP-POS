<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    /**
     * Routes a user with must_change_password=true is still allowed to hit -
     * the change-password screen itself and the endpoint that actually
     * updates it - so the redirect below never traps them in a loop.
     */
    protected $exempt_routes = [
        'force-password-change',
        'profile.password.update',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->must_change_password && !in_array($request->route()?->getName(), $this->exempt_routes, true)) {
            return redirect()->route('force-password-change');
        }

        return $next($request);
    }
}
