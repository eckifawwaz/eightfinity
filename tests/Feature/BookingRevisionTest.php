<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Support\MidtransBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_finish_redirects_to_receipt_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $booking = $this->booking($user, [
            'status' => 'pending',
            'midtrans_status' => 'settlement',
        ]);

        $response = $this->actingAs($user)
            ->get("/payment/finish/{$booking->id}?transaction_status=settlement");

        $response->assertRedirect(route('user.payment.success', $booking));
    }

    public function test_receipt_download_is_a_real_pdf(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'Receipt User',
        ]);
        $booking = $this->booking($user, [
            'status' => 'confirmed',
            'booking_date' => now()->addWeek()->toDateString(),
            'booking_time' => '14:00',
        ]);

        $response = $this->actingAs($user)->get("/bookings/{$booking->id}/receipt.pdf");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('PAYMENT RECEIPT', $response->getContent());
        $this->assertStringContainsString('DETAIL BOOKING', $response->getContent());
        $this->assertStringContainsString('TOTAL PEMBAYARAN', $response->getContent());
    }

    public function test_receipt_payload_uses_dynamic_team_arrival_time(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $booking = $this->booking($user, [
            'booking_date' => now()->addWeek()->toDateString(),
            'booking_time' => '14:00',
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->getJson("/payment/success/{$booking->id}/data")
            ->assertOk()
            ->assertJsonPath('team_arrival_time', '13:00');
    }

    public function test_reschedule_is_closed_as_soon_as_h_minus_three_begins(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 00:00:00', 'Asia/Jakarta'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $booking = $this->booking($user, [
            'status' => 'confirmed',
            'booking_date' => '2026-09-22',
            'booking_time' => '14:00',
        ]);

        $response = $this->actingAs($user)->patch("/bookings/{$booking->id}/reschedule", [
            'date' => '2026-09-23',
            'time' => '15:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Test',
            'location' => 'Jakarta',
        ]);

        $response->assertStatus(422);
        $this->assertSame('2026-09-22', $booking->fresh()->booking_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_reschedule_is_still_available_before_h_minus_three(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-18 23:59:00', 'Asia/Jakarta'));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $booking = $this->booking($user, [
            'status' => 'confirmed',
            'booking_date' => '2026-09-22',
            'booking_time' => '14:00',
        ]);

        $response = $this->actingAs($user)->patch("/bookings/{$booking->id}/reschedule", [
            'date' => '2026-09-23',
            'time' => '15:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Test Baru',
            'location' => 'Jakarta',
        ]);

        $response->assertRedirect('/profile');
        $booking->refresh();
        $this->assertSame('2026-09-23', $booking->booking_date->toDateString());
        $this->assertSame('pending', $booking->status);

        Carbon::setTestNow();
    }

    public function test_confirmed_booking_is_completed_automatically_after_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 15:00:01', 'Asia/Jakarta'));

        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->booking($user, [
            'package_slug' => 'wedding',
            'package_name' => 'Wedding Package',
            'package_option' => 0, // 4 hours
            'booking_date' => '2026-09-22',
            'booking_time' => '11:00',
            'status' => 'confirmed',
        ]);

        $this->artisan('bookings:sync-statuses')->assertExitCode(0);

        $this->assertSame('completed', $booking->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_qris_payment_type_sets_five_minute_countdown_fallback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'Asia/Jakarta'));

        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->booking($user, [
            'status' => 'pending',
            'payment_expires_at' => now()->addDay(),
        ]);

        app(MidtransBookingService::class)->apply($booking, [
            'transaction_status' => 'pending',
            'payment_type' => 'qris',
            'transaction_time' => '2026-09-22 12:00:00',
        ]);

        $this->assertSame('qris', $booking->fresh()->midtrans_payment_type);
        $this->assertSame(
            '2026-09-22 12:05:00',
            $booking->fresh()->payment_expires_at->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
        Carbon::setTestNow();
    }

    public function test_late_midtrans_expire_callback_does_not_downgrade_confirmed_booking(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->booking($user, [
            'status' => 'confirmed',
            'midtrans_status' => 'settlement',
        ]);

        app(MidtransBookingService::class)->apply($booking, [
            'transaction_status' => 'expire',
            'payment_type' => 'qris',
        ]);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('expire', $booking->fresh()->midtrans_status);
    }

    public function test_pending_booking_becomes_expired_when_payment_timer_has_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'Asia/Jakarta'));

        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->booking($user, [
            'status' => 'pending',
            'payment_expires_at' => now()->subSecond(),
            'midtrans_status' => 'pending',
        ]);

        $this->artisan('bookings:sync-statuses')->assertExitCode(0);

        $this->assertSame('expired', $booking->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_expired_booking_remains_visible_in_profile_history(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $booking = $this->booking($user, [
            'status' => 'expired',
            'midtrans_status' => 'expire',
        ]);

        $this->actingAs($user)
            ->getJson('/profile/data')
            ->assertOk()
            ->assertJsonPath('bookings.0.id', $booking->id)
            ->assertJsonPath('bookings.0.status', 'expired');
    }

    public function test_expired_pending_booking_releases_date_in_availability_endpoint(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00', 'Asia/Jakarta'));

        $owner = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $visitor = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $date = now()->addWeek()->toDateString();
        $booking = $this->booking($owner, [
            'booking_date' => $date,
            'status' => 'pending',
            'payment_expires_at' => now()->subSecond(),
            'midtrans_status' => 'pending',
        ]);

        $this->actingAs($visitor)
            ->getJson('/book/availability?date='.$date)
            ->assertOk()
            ->assertJsonPath('available', true);

        $this->assertSame('expired', $booking->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_availability_endpoint_marks_an_active_date_as_full(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $visitor = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $date = now()->addWeek()->toDateString();
        $this->booking($owner, ['booking_date' => $date, 'status' => 'confirmed']);

        $this->actingAs($visitor)
            ->getJson('/book/availability?date='.$date)
            ->assertOk()
            ->assertJsonPath('available', false);
    }

    private function booking(User $user, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $user->id,
            'booking_code' => 'EF-REV-'.strtoupper(fake()->bothify('??####')),
            'package_slug' => 'unlimited',
            'package_name' => 'Unlimited Package',
            'package_option' => 2,
            'booking_date' => now()->addWeek()->toDateString(),
            'booking_time' => '10:00',
            'booth_size' => '3 x 3 meter',
            'people' => 4,
            'customer_address' => 'Jl. Test',
            'booking_location' => 'Jakarta',
            'amount' => 3000000,
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
            'status' => 'pending',
        ], $overrides));
    }
}
