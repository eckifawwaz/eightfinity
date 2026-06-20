<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Support\PortalUrl;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('react');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->email)),
            'first_name' => trim((string) $request->first_name),
            'last_name' => trim((string) $request->last_name),
            'name' => trim((string) $request->name),
            'phone' => trim((string) $request->phone),
            'alternate_phone' => trim((string) $request->alternate_phone),
            'password_confirmation' => $request->password_confirmation ?: $request->password,
        ]);

        $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $name = trim($request->name ?: $request->first_name.' '.$request->last_name);
        $name = $name !== '' ? $name : Str::before($request->email, '@');

        $user = User::create([
            'name' => $name,
            'email' => $request->email,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        event(new Registered($user));

        $code = $user->generateEmailVerificationCode();
        Mail::to($user)->send(new EmailVerificationCodeMail($user, $code));

        Auth::login($user);

        return redirect(PortalUrl::to('user', '/verify-email'))->with('status', 'verification-code-sent');
    }
}
