<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $user, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $user->id,
            'booking_code' => 'EF-QUEUE-001',
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0,
            'booking_date' => '2026-07-17',
            'booking_time' => '17:30',
            'people' => 4,
            'customer_address' => 'Cikarang',
            'booking_location' => 'Customer Venue',
            'amount' => 3000000,
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_confirmed_booking_appears_as_the_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Queue Customer',
        ]);

        $this->makeBooking($user, ['status' => 'confirmed']);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get('/admin/queue');

        $response
            ->assertOk()
            ->assertSee((string) $user->id, false)
            ->assertSee('EF-QUEUE-001');
    }

    public function test_pending_booking_does_not_appear_as_the_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->makeBooking($user, ['status' => 'pending']);

        $response = $this
            ->actingAs($admin, 'admin')
            ->get('/admin/queue');

        $response
            ->assertOk()
            ->assertDontSee('EF-QUEUE-001');
    }

    public function test_admin_can_log_a_photo_for_the_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $this
            ->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/photos")
            ->assertRedirect();

        $this->assertSame(1, $booking->fresh()->photos_taken);
    }

    public function test_admin_can_pause_and_resume_the_booth(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $this
            ->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/booth", ['paused' => '1'])
            ->assertRedirect();

        $this->assertTrue($booking->fresh()->booth_paused);

        $this
            ->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/booth", ['paused' => '0'])
            ->assertRedirect();

        $this->assertFalse($booking->fresh()->booth_paused);
    }

    public function test_the_event_currently_in_progress_takes_priority_over_upcoming_and_overdue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        // Overdue: started well before now and already finished (4-hour Wedding Package).
        $this->makeBooking($user, [
            'booking_code' => 'EF-OVERDUE',
            'status' => 'confirmed',
            'booking_date' => now()->subDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        // In progress right now.
        $this->makeBooking($user, [
            'booking_code' => 'EF-IN-PROGRESS',
            'status' => 'confirmed',
            'booking_date' => now()->toDateString(),
            'booking_time' => now()->subHour()->format('H:i'),
        ]);

        // Upcoming: scheduled well after now.
        $this->makeBooking($user, [
            'booking_code' => 'EF-UPCOMING',
            'status' => 'confirmed',
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response
            ->assertOk()
            ->assertSee('EF-IN-PROGRESS')
            ->assertDontSee('EF-OVERDUE')
            ->assertDontSee('EF-UPCOMING');

        $response->assertViewHas('adminQueue', fn ($data) => $data['event']['event_phase'] === 'in_progress');
    }

    public function test_the_soonest_upcoming_event_is_selected_when_none_are_in_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->makeBooking($user, [
            'booking_code' => 'EF-LATER',
            'status' => 'confirmed',
            'booking_date' => now()->addDays(2)->toDateString(),
            'booking_time' => '10:00',
        ]);

        $this->makeBooking($user, [
            'booking_code' => 'EF-SOONEST',
            'status' => 'confirmed',
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response->assertOk()->assertSee('EF-SOONEST')->assertDontSee('EF-LATER');
        $response->assertViewHas('adminQueue', fn ($data) => $data['event']['event_phase'] === 'upcoming');
    }

    public function test_an_overdue_event_is_shown_when_nothing_is_in_progress_or_upcoming(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->makeBooking($user, [
            'booking_code' => 'EF-OVERDUE-ONLY',
            'status' => 'confirmed',
            'booking_date' => now()->subDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response->assertOk()->assertSee('EF-OVERDUE-ONLY');
        $response->assertViewHas('adminQueue', fn ($data) => $data['event']['event_phase'] === 'overdue');
    }

    public function test_admin_can_flag_equipment_as_needing_attention(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $this
            ->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/equipment", ['equipment' => 'backdrop', 'ok' => '0'])
            ->assertRedirect();

        $this->assertSame(false, $booking->fresh()->equipmentStatus()['backdrop']);
    }
}
