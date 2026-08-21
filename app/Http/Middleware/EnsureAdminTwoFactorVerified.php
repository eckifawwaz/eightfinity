<?php

namespace App\Http\Middleware;

use App\Support\PortalUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (
            $admin?->two_factor_enabled
            && $request->session()->get('admin_two_factor_verified') !== true
            && ! $request->routeIs('admin.two-factor.*')
            && ! $request->routeIs('admin.logout')
        ) {
            return redirect(PortalUrl::to('admin', '/admin/two-factor'));
        }

        return $next($request);
    }
}
