<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\UserTwoFactorCodeMail;
use App\Support\PortalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserTwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->two_factor_enabled || $request->session()->get('user_two_factor_verified') === true) {
            return redirect(PortalUrl::to('user', '/home'));
        }

        return view('react', [
            'userTwoFactor' => [
                'email' => $user->email,
                'status' => session('status'),
            ],
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $expiresAt = $user->two_factor_expires_at;
        $codeHash = $user->two_factor_code_hash;

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

        $user->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
            'last_login_at' => now(),
        ])->save();

        $request->session()->put('user_two_factor_verified', true);

        return redirect()->intended(PortalUrl::to('user', '/home'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->two_factor_enabled, 404);

        $code = $user->generateTwoFactorCode();

        Mail::to($user)->send(new UserTwoFactorCodeMail($user, $code));

        return back()->with('status', 'two-factor-code-sent');
    }
}
