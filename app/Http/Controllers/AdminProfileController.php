<?php

namespace App\Http\Controllers;

use App\Support\AdminNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminProfileController extends Controller
{
    public function show(Request $request): View
    {
        $admin = $request->user('admin');

        return view('react', [
            'adminProfile' => [
                'user' => [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'timezone' => $admin->timezone ?: 'Asia/Jakarta',
                    'language' => $admin->language ?: 'en',
                    'two_factor_enabled' => $admin->two_factor_enabled,
                    'password_changed_at' => $admin->password_changed_at?->toISOString(),
                    'last_login_at' => $admin->last_login_at?->toISOString(),
                ],
                'notification_preferences' => $admin->adminNotificationPreferences(),
                'status' => session('status'),
            ],
        ]);
    }

    public function update(Request $request, AdminNotifier $notifier): RedirectResponse
    {
        $admin = $request->user('admin');

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', 'string', 'max:80'],
            'language' => ['required', 'in:en,id'],
        ]);

        $admin->update($validated);

        $notifier->system(
            'system_updates',
            'Eightfinity admin profile updated',
            "{$admin->name} updated admin account settings.",
        );

        return back()->with('status', 'admin-profile-updated');
    }

    public function updatePassword(Request $request, AdminNotifier $notifier): RedirectResponse
    {
        $validated = $request->validateWithBag('adminPassword', [
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $admin = $request->user('admin');
        $admin->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ])->save();

        $request->session()->put('admin_two_factor_verified', true);

        $notifier->system(
            'system_updates',
            'Eightfinity admin password changed',
            "{$admin->name} changed the admin account password.",
        );

        return back()->with('status', 'admin-password-updated');
    }

    public function updateSecurity(Request $request, AdminNotifier $notifier): RedirectResponse
    {
        $validated = $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        $admin = $request->user('admin');
        $enabled = (bool) $validated['two_factor_enabled'];

        $admin->forceFill([
            'two_factor_enabled' => $enabled,
            'two_factor_code_hash' => $enabled ? $admin->two_factor_code_hash : null,
            'two_factor_expires_at' => $enabled ? $admin->two_factor_expires_at : null,
        ])->save();

        $request->session()->put('admin_two_factor_verified', true);

        $notifier->system(
            'system_updates',
            $enabled ? 'Eightfinity admin 2FA enabled' : 'Eightfinity admin 2FA disabled',
            "{$admin->name} ".($enabled ? 'enabled' : 'disabled').' two-factor authentication.',
        );

        return back()->with('status', $enabled ? 'admin-two-factor-enabled' : 'admin-two-factor-disabled');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alert_sound' => ['required', 'in:corporate,soft,none'],
        ]);

        $request->user('admin')->update([
            'notification_preferences' => [
                'daily_summary' => $request->boolean('daily_summary'),
                'new_customer_alerts' => $request->boolean('new_customer_alerts'),
                'system_updates' => $request->boolean('system_updates'),
                'booking_requests' => $request->boolean('booking_requests'),
                'low_stock_alerts' => $request->boolean('low_stock_alerts'),
                'message_activity' => $request->boolean('message_activity'),
                'desktop_toasts' => $request->boolean('desktop_toasts'),
                'alert_sound' => $validated['alert_sound'],
            ],
        ]);

        return back()->with('status', 'admin-notifications-updated');
    }

    public function downloadLog(): BinaryFileResponse
    {
        $path = storage_path('logs/laravel.log');

        abort_unless(is_file($path), 404);

        return response()->download($path, 'eightfinity-admin.log');
    }
}
