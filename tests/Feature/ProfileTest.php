<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_includes_current_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Dijeda Tester',
            'email' => 'dijeda@example.com',
            'phone' => '081234567890',
            'alternate_phone' => '089876543210',
            'address' => 'Cikarang',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('window.__USER_PROFILE__', false)
            ->assertSee('Dijeda Tester')
            ->assertSee('dijeda@example.com')
            ->assertSee('081234567890')
            ->assertSee('Cikarang');
    }

    public function test_profile_data_endpoint_returns_current_user_data_and_bookings(): void
    {
        $user = User::factory()->create([
            'name' => 'Dijeda Tester',
            'email' => 'dijeda@example.com',
            'phone' => '081234567890',
            'address' => 'Cikarang',
        ]);

        Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-TEST-001',
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
            'status' => 'completed',
            'photos_taken' => 12,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/profile/data');

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'Dijeda Tester')
            ->assertJsonPath('user.email', 'dijeda@example.com')
            ->assertJsonPath('user.phone', '081234567890')
            ->assertJsonPath('user.address', 'Cikarang')
            ->assertJsonPath('summary.total_sessions', 1)
            ->assertJsonPath('summary.completed_sessions', 1)
            ->assertJsonPath('summary.total_photos', 12)
            ->assertJsonPath('bookings.0.booking_code', 'EF-TEST-001')
            ->assertJsonPath('bookings.0.photos_taken', 12);
    }

    public function test_user_can_delete_their_cancelled_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-CANCELLED-001',
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
            'status' => 'cancelled',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete("/bookings/{$booking->id}");

        $response->assertRedirect('/profile');
        $this->assertSoftDeleted('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_user_cannot_delete_active_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'EF-ACTIVE-001',
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
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete("/bookings/{$booking->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
