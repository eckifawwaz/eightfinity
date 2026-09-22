<?php

namespace Tests\Feature;

use App\Mail\AdminBookingNotificationMail;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_payment_proof(): void
    {
        Storage::fake('local');
        Mail::fake();
        config(['services.midtrans.payment_links.unlimited.2' => null]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'wallet',
            'payment_provider' => 'gopay',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect(route('user.payment.success', $booking));
        $this->assertSame(3000000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Storage::disk('local')->assertExists($booking->payment_proof);
        Mail::assertSent(AdminBookingNotificationMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_customer_cannot_book_a_same_day_session(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 2,
            'date' => now(BookingLifecycle::timezone())->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'wallet',
            'payment_provider' => 'gopay',
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertSame(0, $user->bookings()->count());
    }

    public function test_customer_can_book_starting_tomorrow(): void
    {
        Mail::fake();
        config(['services.midtrans.payment_links.unlimited.2' => null]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 2,
            'date' => now(BookingLifecycle::timezone())->addDay()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'wallet',
            'payment_provider' => 'gopay',
        ]);

        $response->assertSessionDoesntHaveErrors('date');
        $this->assertSame(1, $user->bookings()->count());
    }

    public function test_wedding_package_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
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

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'wedding',
            'option' => 0,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-wedding');
        $this->assertSame(3000000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_wedding_eight_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.wedding.2' => 'https://app.sandbox.midtrans.com/payment-links/test-wedding-8-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'wedding',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-wedding-8-hours');
        $this->assertSame(5800000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_reservation_four_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.reservation.1' => 'https://app.sandbox.midtrans.com/payment-links/test-reservation-4-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'reservation',
            'option' => 1,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-reservation-4-hours');
        $this->assertSame(899000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_reservation_four_plus_one_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.reservation.2' => 'https://app.sandbox.midtrans.com/payment-links/test-reservation-4-plus-1-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'reservation',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-reservation-4-plus-1-hours');
        $this->assertSame(999000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_unlimited_two_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.unlimited.0' => 'https://app.sandbox.midtrans.com/payment-links/test-unlimited-2-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 0,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-unlimited-2-hours');
        $this->assertSame(2000000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_unlimited_three_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.unlimited.1' => 'https://app.sandbox.midtrans.com/payment-links/test-unlimited-3-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 1,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-unlimited-3-hours');
        $this->assertSame(2500000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_unlimited_four_hours_redirects_to_midtrans_payment_link(): void
    {
        Storage::fake('local');
        Mail::fake();

        config([
            'services.midtrans.payment_links.unlimited.2' => 'https://app.sandbox.midtrans.com/payment-links/test-unlimited-4-hours',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@eightfinity.test',
        ]);

        $response = $this->actingAs($user)->post('/payment', [
            'package' => 'unlimited',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ]);

        $booking = $user->bookings()->firstOrFail();

        $response->assertRedirect('https://app.sandbox.midtrans.com/payment-links/test-unlimited-4-hours');
        $this->assertSame(3000000, $booking->amount);
        $this->assertSame('pending', $booking->status);
        Mail::assertSent(AdminBookingNotificationMail::class);
    }

    public function test_customer_cannot_book_a_date_that_already_has_an_active_booking(): void
    {
        Mail::fake();

        $date = now()->addWeek()->toDateString();
        $existingCustomer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->createBooking($existingCustomer, $date, 'confirmed');

        $response = $this->actingAs($customer)->post('/payment', $this->paymentPayload([
            'date' => $date,
        ]));

        $response->assertSessionHasErrors('date');
        $this->assertSame(1, Booking::whereDate('booking_date', $date)->count());
        Mail::assertNothingSent();
    }

    public function test_customer_can_book_a_date_after_previous_booking_was_cancelled(): void
    {
        Storage::fake('local');
        Mail::fake();
        config(['services.midtrans.payment_links.unlimited.2' => null]);

        $date = now()->addWeek()->toDateString();
        $existingCustomer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $customer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->createBooking($existingCustomer, $date, 'cancelled');

        $response = $this->actingAs($customer)->post('/payment', $this->paymentPayload([
            'date' => $date,
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
        ]));

        $booking = $customer->bookings()->firstOrFail();

        $response->assertRedirect(route('user.payment.success', $booking));
        $this->assertSame(2, Booking::whereDate('booking_date', $date)->count());
    }

    public function test_customer_cannot_reschedule_to_a_date_that_already_has_an_active_booking(): void
    {
        Mail::fake();

        $originalDate = now()->addWeek()->toDateString();
        $unavailableDate = now()->addWeeks(2)->toDateString();
        $customer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        $otherCustomer = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $booking = $this->createBooking($customer, $originalDate);
        $this->createBooking($otherCustomer, $unavailableDate, 'confirmed');

        $response = $this->actingAs($customer)->patch("/bookings/{$booking->id}/reschedule", [
            'package' => 'unlimited',
            'option' => 2,
            'date' => $unavailableDate,
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertSame($originalDate, $booking->fresh()->booking_date->toDateString());
    }

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'package' => 'unlimited',
            'option' => 2,
            'date' => now()->addWeek()->toDateString(),
            'time' => '14:00',
            'booth_size' => '3 x 3 meter',
            'address' => 'Jl. Sudirman No. 10',
            'location' => 'Jakarta',
            'payment_method' => 'qris',
            'payment_provider' => 'qris',
        ], $overrides);
    }

    public function test_owner_can_fetch_their_booking_receipt_data(): void
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Receipt Owner']);
        $booking = $this->createBooking($user, now()->addWeek()->toDateString(), 'completed');

        $response = $this
            ->actingAs($user)
            ->getJson("/payment/success/{$booking->id}/data");

        $response
            ->assertOk()
            ->assertJsonPath('id', $booking->id)
            ->assertJsonPath('booking_code', $booking->booking_code)
            ->assertJsonPath('customer_name', 'Receipt Owner');
    }

    public function test_another_user_cannot_fetch_someone_elses_receipt_data(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $intruder = User::factory()->create(['role' => 'user']);
        $booking = $this->createBooking($owner, now()->addWeek()->toDateString(), 'completed');

        $this
            ->actingAs($intruder)
            ->getJson("/payment/success/{$booking->id}/data")
            ->assertForbidden();
    }

    private function createBooking(User $user, string $date, string $status = 'pending'): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-TEST-'.strtoupper(fake()->bothify('??####')),
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
