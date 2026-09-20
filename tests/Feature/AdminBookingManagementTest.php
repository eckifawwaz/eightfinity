<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->createBooking();

        $response = $this
            ->actingAs($admin, 'admin')
            ->patch("/admin/bookings/{$booking->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_delete_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->createBooking('cancelled');

        $response = $this
            ->actingAs($admin, 'admin')
            ->delete("/admin/bookings/{$booking->id}");

        $response->assertRedirect('/admin/bookings');
        $this->assertSoftDeleted('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_non_admin_cannot_delete_booking_from_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->createBooking('cancelled', $user);

        $response = $this
            ->actingAs($user, 'admin')
            ->delete("/admin/bookings/{$booking->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
        ]);
    }

    private function createBooking(string $status = 'pending', ?User $user = null): Booking
    {
        $user ??= User::factory()->create(['role' => 'user']);

        return Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-ADMIN-'.strtoupper(fake()->bothify('??###')),
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0,
            'booking_date' => '2026-07-17',
            'booking_time' => '17:30',
            'people' => 100,
            'customer_address' => 'Cikarang',
            'booking_location' => 'Customer Venue',
            'amount' => 2500000,
            'payment_method' => 'qris',
            'payment_provider' => 'midtrans',
            'status' => $status,
        ]);
    }
}
