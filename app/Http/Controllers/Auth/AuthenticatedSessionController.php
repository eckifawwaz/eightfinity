<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Support\PortalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('react');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $guard = $request->is('admin/login') ? 'admin' : 'web';
        $redirectTo = $guard === 'admin'
            ? PortalUrl::to('admin', '/dashboard')
            : PortalUrl::to('user', RouteServiceProvider::HOME);

        if (! Auth::guard($guard)->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard($guard)->user();

        if ($guard === 'admin' && $user?->role !== 'admin') {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account does not have admin access.',
            ]);
        }

        if ($guard === 'web' && $user?->role === 'admin') {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Admin accounts must sign in at /admin/login.',
            ]);
        }

        return redirect()->intended($redirectTo);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $guard = $request->routeIs('admin.logout') || $request->is('admin/*') || $request->is('dashboard')
            ? 'admin'
            : 'web';

        Auth::guard($guard)->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(
            $guard === 'admin'
                ? PortalUrl::to('admin', '/admin/login')
                : PortalUrl::to('user', '/login')
        );
    }
}
