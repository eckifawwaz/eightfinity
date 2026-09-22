<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\QueueGuest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(User $user, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $user->id,
            'booking_code' => 'EF-QUEUE-'.strtoupper(fake()->bothify('??####')),
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0,
            'booking_date' => now()->addDay()->toDateString(),
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
        $user = User::factory()->create(['role' => 'user', 'name' => 'Queue Customer']);

        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertViewHas('adminQueue', fn ($data) => $data['event']['id'] === $booking->id);
    }

    public function test_pending_booking_does_not_appear_as_the_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'pending']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/queue')
            ->assertOk()
            ->assertDontSee($booking->booking_code);
    }

    public function test_completed_queue_session_automatically_updates_photo_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);
        $guest = $booking->queueGuests()->create([
            'guest_name' => 'Guest A',
            'checked_in_at' => '18:00',
            'queue_order' => 1,
            'status' => 'in_session',
            'session_started_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/queue/guests/{$guest->id}/complete")
            ->assertRedirect();

        $this->assertSame('completed', $guest->fresh()->status);
        $this->assertSame(1, $booking->fresh()->photos_taken);
    }

    public function test_admin_can_delete_queue_guest(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);
        $guest = $booking->queueGuests()->create([
            'guest_name' => 'Guest To Delete',
            'checked_in_at' => '18:00',
            'queue_order' => 1,
            'status' => 'waiting',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/queue/guests/{$guest->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('queue_guests', ['id' => $guest->id]);
    }

    public function test_admin_can_pause_and_resume_the_booth(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/booth", ['paused' => '1'])
            ->assertRedirect();
        $this->assertTrue($booking->fresh()->booth_paused);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/booth", ['paused' => '0'])
            ->assertRedirect();
        $this->assertFalse($booking->fresh()->booth_paused);
    }

    public function test_current_event_takes_priority_and_overdue_booking_is_auto_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $overdue = $this->makeBooking($user, [
            'booking_code' => 'EF-OVERDUE',
            'status' => 'confirmed',
            'booking_date' => now()->subDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $current = $this->makeBooking($user, [
            'booking_code' => 'EF-IN-PROGRESS',
            'status' => 'confirmed',
            'booking_date' => now()->toDateString(),
            'booking_time' => '11:00',
        ]);

        $upcoming = $this->makeBooking($user, [
            'booking_code' => 'EF-UPCOMING',
            'status' => 'confirmed',
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response
            ->assertOk()
            ->assertSee($current->booking_code)
            ->assertDontSee($overdue->booking_code)
            ->assertDontSee($upcoming->booking_code)
            ->assertViewHas('adminQueue', fn ($data) => $data['event']['event_phase'] === 'in_progress');

        $this->assertSame('completed', $overdue->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_soonest_upcoming_event_is_selected_when_none_are_in_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $later = $this->makeBooking($user, [
            'booking_code' => 'EF-LATER',
            'status' => 'confirmed',
            'booking_date' => now()->addDays(2)->toDateString(),
            'booking_time' => '10:00',
        ]);

        $soonest = $this->makeBooking($user, [
            'booking_code' => 'EF-SOONEST',
            'status' => 'confirmed',
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/queue');

        $response->assertOk()->assertSee($soonest->booking_code)->assertDontSee($later->booking_code);
        $response->assertViewHas('adminQueue', fn ($data) => $data['event']['event_phase'] === 'upcoming');
    }

    public function test_admin_cannot_add_queue_guest_outside_booking_hours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, [
            'status' => 'confirmed',
            'booking_time' => '17:30',
            'package_option' => 0, // Wedding 4 hours => ends 21:30.
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/queue/{$booking->id}/guests", [
                'guest_name' => 'Outside Guest',
                'checked_in_at' => '16:00',
            ]);

        $response->assertSessionHasErrors('checked_in_at');
        $this->assertSame(0, $booking->queueGuests()->count());
    }

    public function test_guest_name_is_required_when_adding_queue_guest(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/queue/{$booking->id}/guests", [
                'guest_name' => '',
                'checked_in_at' => '18:00',
            ]);

        $response->assertSessionHasErrors('guest_name');
        $this->assertSame(0, $booking->queueGuests()->count());
    }

    public function test_duplicate_queue_guest_submission_is_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);
        $payload = ['guest_name' => 'Spam Guard', 'checked_in_at' => '18:00'];

        $this->actingAs($admin, 'admin')->post("/admin/queue/{$booking->id}/guests", $payload)->assertRedirect();
        $this->actingAs($admin, 'admin')->post("/admin/queue/{$booking->id}/guests", $payload)->assertRedirect();

        $this->assertSame(1, QueueGuest::where('booking_id', $booking->id)->where('guest_name', 'Spam Guard')->count());
    }


    public function test_admin_cannot_start_queue_session_outside_booking_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'Asia/Jakarta'));

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, [
            'status' => 'confirmed',
            'booking_date' => '2026-09-22',
            'booking_time' => '17:30',
            'package_option' => 0,
        ]);
        $guest = $booking->queueGuests()->create([
            'guest_name' => 'Too Early',
            'checked_in_at' => '17:30',
            'queue_order' => 1,
            'status' => 'waiting',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/queue/guests/{$guest->id}/start")
            ->assertStatus(422);

        $this->assertSame('waiting', $guest->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_admin_can_flag_equipment_as_needing_attention(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->makeBooking($user, ['status' => 'confirmed']);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/queue/{$booking->id}/equipment", ['equipment' => 'backdrop', 'ok' => '0'])
            ->assertRedirect();

        $this->assertFalse($booking->fresh()->equipmentStatus()['backdrop']);
    }
}
