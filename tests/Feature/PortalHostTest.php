<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalHostTest extends TestCase
{
    public function test_user_routes_redirect_to_the_user_origin(): void
    {
        config(['portals.user_url' => 'http://user.localhost:8000']);

        $this->get('http://admin.localhost:8001/register')
            ->assertRedirect('http://user.localhost:8000/register');
    }

    public function test_admin_routes_redirect_to_the_admin_origin(): void
    {
        config(['portals.admin_url' => 'http://admin.localhost:8001']);

        $this->get('http://user.localhost:8000/admin/login')
            ->assertRedirect('http://admin.localhost:8001/admin/login');
    }

    public function test_each_portal_accepts_requests_on_its_own_origin(): void
    {
        config([
            'portals.user_url' => 'http://user.localhost:8000',
            'portals.admin_url' => 'http://admin.localhost:8001',
        ]);

        $this->get('http://user.localhost:8000/register')->assertOk();
        $this->get('http://admin.localhost:8001/admin/login')->assertOk();
    }
}
