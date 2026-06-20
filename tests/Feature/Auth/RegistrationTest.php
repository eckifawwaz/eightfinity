<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use App\Support\PortalUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+6281234567890',
            'role' => 'user',
        ]);
        $response->assertRedirect(PortalUrl::to('user', RouteServiceProvider::HOME));
    }

    public function test_registered_email_can_login_immediately(): void
    {
        $this->post('/register', [
            'first_name' => 'Maya',
            'last_name' => 'Putri',
            'email' => 'maya@example.com',
            'phone' => '+6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated('web');

        Auth::guard('web')->logout();
        $this->assertGuest('web');

        $response = $this->post('/login', [
            'email' => 'maya@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $response->assertRedirect(PortalUrl::to('user', RouteServiceProvider::HOME));
    }
}
