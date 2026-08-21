<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_booking_code_uses_eightfinity_date_random_format(): void
    {
        $this->travelTo('2026-07-06 10:00:00');
        Mail::fake();
        config([
            'portals.user_url' => null,
            'services.midtrans.server_key' => null,
            'services.midtrans.payment_links.wedding.0' => 'https://app.sandbox.midtrans.com/payment-links/test-wedding',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $this->actingAs($user)->post('/payment', [
            'package' => 'wedding',
            'option' => 0,
            'date' => '2026-07-17',
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $this->assertMatchesRegularExpression('/^EF-20260706-[A-Z0-9]{6}$/', $booking->booking_code);
    }
}
