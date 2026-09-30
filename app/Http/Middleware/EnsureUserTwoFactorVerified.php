<?php

namespace App\Http\Middleware;

use App\Support\PortalUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (
            $user?->two_factor_enabled
            && $request->session()->get('user_two_factor_verified') !== true
            && ! $request->routeIs('user.two-factor.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect(PortalUrl::to('user', '/two-factor'));
        }

        return $next($request);
    }
}
