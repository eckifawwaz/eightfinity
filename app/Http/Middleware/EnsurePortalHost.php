<?php

namespace App\Http\Middleware;

use App\Support\PortalUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalHost
{
    /**
     * Keep user and admin traffic on their configured origins.
     */
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        if (! PortalUrl::matches($request, $portal)) {
            return redirect()->away(PortalUrl::to($portal, $request->getRequestUri()));
        }

        return $next($request);
    }
}
