<?php

namespace Tests\Feature;

use App\Mail\AdminBookingNotificationMail;
use App\Mail\AdminTwoFactorCodeMail;
use App\Models\Booking;
use App\Models\User;
use App\Support\AdminNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_settings_can_be_saved(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin, 'admin')
            ->patch('/admin/profile', [
                'phone' => '+62 812 3456 7890',
                'timezone' => 'Asia/Jakarta',
                'language' => 'id',
            ])
            ->assertRedirect();

        $admin->refresh();

        $this->assertSame('+62 812 3456 7890', $admin->phone);
        $this->assertSame('Asia/Jakarta', $admin->timezone);
        $this->assertSame('id', $admin->language);
    }

    public function test_admin_password_can_be_updated(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put('/admin/profile/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ])
            ->assertRedirect();

        $admin->refresh();

        $this->assertTrue(Hash::check('NewPassword1', $admin->password));
        $this->assertNotNull($admin->password_changed_at);
    }

    public function test_admin_two_factor_requires_email_code_before_dashboard_access(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'two_factor_enabled' => true,
        ]);

        $this
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/two-factor');

        $code = null;
        Mail::assertSent(AdminTwoFactorCodeMail::class, function (AdminTwoFactorCodeMail $mail) use ($admin, &$code) {
            $code = $mail->code;

            return $mail->hasTo($admin->email);
        });

        $this->assertNotNull($code);

        $this->get('/dashboard')->assertRedirect('/admin/two-factor');

        $this
            ->post('/admin/two-factor', ['code' => $code])
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
        $this->assertNotNull($admin->refresh()->last_login_at);
    }

    public function test_admin_notification_preferences_filter_booking_emails(): void
    {
        Mail::fake();

        User::factory()->create([
            'role' => 'admin',
            'email' => 'muted@example.com',
            'notification_preferences' => [
                'booking_requests' => false,
            ],
        ]);
        $enabledAdmin = User::factory()->create([
            'role' => 'admin',
            'email' => 'enabled@example.com',
            'notification_preferences' => [
                'booking_requests' => true,
            ],
        ]);
        $customer = User::factory()->create(['role' => 'user']);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'booking_code' => 'EF-NOTIFY-001',
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0,
            'booking_date' => '2026-07-17',
            'booking_time' => '17:30',
            'people' => 4,
            'customer_address' => 'Cikarang',
            'booking_location' => 'Customer Venue',
            'amount' => 3000000,
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
            'status' => 'pending',
        ]);

        app(AdminNotifier::class)->send('booking_requests', new AdminBookingNotificationMail($booking));

        Mail::assertSent(AdminBookingNotificationMail::class, 1);
        Mail::assertSent(AdminBookingNotificationMail::class, fn (AdminBookingNotificationMail $mail) => $mail->hasTo($enabledAdmin->email));
    }
}
