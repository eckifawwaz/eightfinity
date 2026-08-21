<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminTwoFactorCodeMail;
use App\Support\PortalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $admin = $request->user('admin');

        if (! $admin?->two_factor_enabled || $request->session()->get('admin_two_factor_verified') === true) {
            return redirect(PortalUrl::to('admin', '/dashboard'));
        }

        return view('react', [
            'adminTwoFactor' => [
                'email' => $admin->email,
                'status' => session('status'),
            ],
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $admin = $request->user('admin');
        $expiresAt = $admin->two_factor_expires_at;
        $codeHash = $admin->two_factor_code_hash;

        if (! $codeHash || ! $expiresAt || now()->greaterThan($expiresAt)) {
            return back()->withErrors([
                'code' => 'Two-factor code has expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['code'], $codeHash)) {
            return back()->withErrors([
                'code' => 'Two-factor code is incorrect.',
            ]);
        }

        $admin->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
            'last_login_at' => now(),
        ])->save();

        $request->session()->put('admin_two_factor_verified', true);

        return redirect()->intended(PortalUrl::to('admin', '/dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $admin = $request->user('admin');

        abort_unless($admin?->two_factor_enabled, 404);

        $code = $admin->generateTwoFactorCode();

        Mail::to($admin)->send(new AdminTwoFactorCodeMail($admin, $code));

        return back()->with('status', 'two-factor-code-sent');
    }
}
