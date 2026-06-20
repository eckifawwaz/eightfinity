<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_payment_proof(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'people' => 4,
            'payment_method' => 'wallet',
            'payment_provider' => 'gopay',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect(route('user.payment.success', $booking));
        $this->assertSame(3000000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Storage::disk('local')->assertExists($booking->payment_proof);
    }
}
