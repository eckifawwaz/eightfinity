<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Providers\RouteServiceProvider;
use App\Support\PortalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailVerificationCodeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->email_verified_at) {
            return redirect(PortalUrl::to('user', RouteServiceProvider::HOME));
        }

        return view('react', [
            'verificationEmail' => $request->user()->email,
            'verificationStatus' => session('status'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();
        $expiresAt = $user->email_verification_expires_at;
        $codeHash = $user->email_verification_code_hash;

        if (! $codeHash || ! $expiresAt || now()->greaterThan($expiresAt)) {
            return back()->withErrors([
                'code' => 'Verification code has expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($validated['code'], $codeHash)) {
            return back()->withErrors([
                'code' => 'Verification code is incorrect.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        return redirect(PortalUrl::to('user', RouteServiceProvider::HOME));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->email_verified_at) {
            return redirect(PortalUrl::to('user', RouteServiceProvider::HOME));
        }

        $code = $request->user()->generateEmailVerificationCode();

        Mail::to($request->user())->send(new EmailVerificationCodeMail($request->user(), $code));

        return back()->with('status', 'verification-code-sent');
    }
}
