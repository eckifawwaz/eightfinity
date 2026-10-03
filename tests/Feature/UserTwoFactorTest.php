<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\UserTwoFactorCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_home_and_package_detail_but_booking_requires_login(): void
    {
        $this->get('/home')->assertOk();
        $this->get('/packages/wedding')->assertOk();
        $this->get('/book?package=wedding&option=0')->assertRedirect('/login');
    }

    public function test_two_factor_is_disabled_by_default_for_new_users(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Awi Customer',
            'email' => 'awi@example.com',
            'phone' => '+6281234567890',
            'address' => 'Surabaya',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertRedirect('/verify-email');

        $this->assertDatabaseHas('users', [
            'email' => 'awi@example.com',
            'two_factor_enabled' => false,
        ]);
    }

    public function test_login_without_two_factor_sets_security_reminder(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect('/home');
        $response->assertSessionHas('show_two_factor_reminder', true);
        $this->assertTrue((bool) session('user_two_factor_verified'));
    }

    public function test_registration_verification_sets_security_reminder_when_two_factor_is_off(): void
    {
        Mail::fake();
        $verificationCode = null;

        $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone' => '+6281234567890',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$verificationCode) {
            $verificationCode = $mail->code;

            return $mail->hasTo('new@example.com');
        });

        $response = $this->post('/verify-email', [
            'code' => $verificationCode,
        ]);

        $response->assertRedirect('/home');
        $response->assertSessionHas('show_two_factor_reminder', true);
        $this->assertFalse((bool) User::where('email', 'new@example.com')->value('two_factor_enabled'));
    }

    public function test_login_with_two_factor_enabled_requires_email_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect('/two-factor');
        $response->assertSessionMissing('show_two_factor_reminder');
        $this->assertFalse((bool) session('user_two_factor_verified'));
        Mail::assertSent(UserTwoFactorCodeMail::class, fn ($mail) => $mail->hasTo($user->email));

        $this->get('/profile')->assertRedirect('/two-factor');
    }

    public function test_user_can_enable_and_disable_two_factor_from_profile_security(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
        ]);

        $enable = $this->actingAs($user, 'web')->patch('/profile/security', [
            'two_factor_enabled' => true,
        ]);

        $enable->assertSessionHasNoErrors();
        $this->assertTrue((bool) $user->fresh()->two_factor_enabled);
        $this->assertTrue((bool) session('user_two_factor_verified'));

        $disable = $this->patch('/profile/security', [
            'two_factor_enabled' => false,
        ]);

        $disable->assertSessionHasNoErrors();
        $this->assertFalse((bool) $user->fresh()->two_factor_enabled);
    }

    public function test_valid_two_factor_code_completes_login(): void
    {
        Mail::fake();
        $code = null;
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Mail::assertSent(UserTwoFactorCodeMail::class, function ($mail) use (&$code, $user) {
            $code = $mail->code;

            return $mail->hasTo($user->email);
        });

        $response = $this->post('/two-factor', [
            'code' => $code,
        ]);

        $response->assertRedirect('/home');
        $this->assertTrue((bool) session('user_two_factor_verified'));
        $this->assertNull($user->fresh()->two_factor_code_hash);
        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
