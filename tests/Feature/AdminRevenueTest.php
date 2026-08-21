<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $user, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $user->id,
            'booking_code' => 'EF-REV-'.random_int(1000, 9999),
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0,
            'booking_date' => now()->toDateString(),
            'booking_time' => '17:30',
            'people' => 4,
            'customer_address' => 'Cikarang',
            'booking_location' => 'Customer Venue',
            'amount' => 1000000,
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_only_confirmed_and_completed_bookings_count_as_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->makeBooking($user, ['status' => 'completed', 'amount' => 500000]);
        $this->makeBooking($user, ['status' => 'confirmed', 'amount' => 300000]);
        $this->makeBooking($user, ['status' => 'pending', 'amount' => 900000]);
        $this->makeBooking($user, ['status' => 'cancelled', 'amount' => 700000]);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get('/admin/revenue');

        $response->assertOk();
        $response->assertViewHas('adminRevenue', function ($adminRevenue) {
            return $adminRevenue['week']['amount'] === 800000
                && $adminRevenue['month']['amount'] === 800000
                && $adminRevenue['year']['amount'] === 800000;
        });
    }

    public function test_recent_transactions_include_all_statuses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->makeBooking($user, ['status' => 'pending', 'booking_code' => 'EF-REV-PEND']);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get('/admin/revenue');

        $response
            ->assertOk()
            ->assertSee('EF-REV-PEND');
    }
}
