<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\PortalUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+6281234567890',
            'address' => 'Jl. Eightfinity No. 8',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+6281234567890',
            'address' => 'Jl. Eightfinity No. 8',
            'role' => 'user',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code_hash);
        $this->assertNotNull($user->email_verification_expires_at);

        Mail::assertSent(EmailVerificationCodeMail::class, fn ($mail) => $mail->hasTo('test@example.com'));
        $response->assertRedirect(PortalUrl::to('user', '/verify-email'));
    }

    public function test_registered_email_can_verify_and_login(): void
    {
        Mail::fake();
        $code = null;

        $this->post('/register', [
            'first_name' => 'Maya',
            'last_name' => 'Putri',
            'email' => 'maya@example.com',
            'phone' => '+6281234567890',
            'address' => 'Jl. Maya No. 1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated('web');
        Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('maya@example.com');
        });

        $response = $this->post('/verify-email', [
            'code' => $code,
        ]);

        $response->assertRedirect(PortalUrl::to('user', RouteServiceProvider::HOME));
        $this->assertNotNull(User::where('email', 'maya@example.com')->first()->email_verified_at);

        Auth::guard('web')->logout();
        $this->assertGuest('web');

        $loginResponse = $this->post('/login', [
            'email' => 'maya@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $loginResponse->assertRedirect(PortalUrl::to('user', RouteServiceProvider::HOME));
    }
}
