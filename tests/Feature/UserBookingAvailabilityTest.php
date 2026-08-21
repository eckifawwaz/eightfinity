<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_page_exposes_unavailable_active_booking_dates(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $activeDate = now()->addWeek()->toDateString();
        $cancelledDate = now()->addWeeks(2)->toDateString();

        $this->createBooking($user, $activeDate, 'confirmed');
        $this->createBooking($user, $cancelledDate, 'cancelled');

        $response = $this->actingAs($user)->get('/book');

        $response->assertOk();
        $response->assertSee($activeDate);
        $response->assertDontSee($cancelledDate);
    }

    public function test_booking_page_does_not_mark_current_reschedule_booking_date_as_unavailable(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $date = now()->addWeek()->toDateString();
        $booking = $this->createBooking($user, $date);

        $response = $this->actingAs($user)->get("/book?reschedule={$booking->id}");

        $response->assertOk();
        $response->assertDontSee($date);
    }

    private function createBooking(User $user, string $date, string $status = 'pending'): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-AVAIL-'.strtoupper(fake()->bothify('??####')),
            'package_slug' => 'unlimited',
            'package_name' => 'Unlimited Package',
            'package_option' => 2,
            'booking_date' => $date,
            'booking_time' => '10:00',
            'people' => 4,
            'amount' => 3000000,
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
            'status' => $status,
        ]);
    }
}
