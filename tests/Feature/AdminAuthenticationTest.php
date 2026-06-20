<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_access_all_pages_and_logout(): void
    {
        User::factory()->create([
            'email' => 'admin@eightfinity.com',
            'password' => 'Admin@12345',
            'role' => 'admin',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@eightfinity.com',
            'password' => 'Admin@12345',
        ])->assertRedirect('/dashboard');

        $this->assertTrue(Auth::guard('admin')->check());

        foreach ([
            '/dashboard',
            '/admin/bookings',
            '/admin/queue',
            '/admin/customers',
            '/admin/layout',
            '/admin/profile',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertFalse(Auth::guard('admin')->check());
    }
}
