<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('react');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('user.profile')->with('status', 'profile-updated');
    }

    /**
     * Toggle the user's two-factor authentication preference.
     */
    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $enabled = (bool) $validated['two_factor_enabled'];

        $user->forceFill([
            'two_factor_enabled' => $enabled,
            'two_factor_code_hash' => $enabled ? $user->two_factor_code_hash : null,
            'two_factor_expires_at' => $enabled ? $user->two_factor_expires_at : null,
        ])->save();

        $request->session()->put('user_two_factor_verified', true);

        return back()->with('status', $enabled ? 'user-two-factor-enabled' : 'user-two-factor-disabled');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
