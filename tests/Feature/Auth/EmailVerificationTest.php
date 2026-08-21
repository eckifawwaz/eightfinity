<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $code = $user->generateEmailVerificationCode();

        $response = $this->actingAs($user)->post('/verify-email', [
            'code' => $code,
        ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $user->generateEmailVerificationCode();

        $response = $this->actingAs($user)->post('/verify-email', [
            'code' => '000000',
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertSessionHasErrors('code');
    }
}
