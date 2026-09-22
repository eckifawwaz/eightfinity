<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookingLifecycle;
use App\Support\MidtransBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('react', [
            'profile' => $this->profileData($request),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->profileData($request));
    }

    private function profileData(Request $request): array
    {
        $user = $request->user();
        $this->syncUserBookings($user->bookings()->get(), app(MidtransBookingService::class));

        $bookings = $user->bookings()
            ->latest()
            ->get([
                'id',
                'booking_code',
                'package_slug',
                'package_name',
                'package_option',
                'booking_date',
                'booking_time',
                'booth_size',
                'customer_address',
                'booking_location',
                'amount',
                'payment_method',
                'payment_provider',
                'status',
                'midtrans_status',
                'midtrans_payment_type',
                'payment_expires_at',
                'photos_taken',
                'created_at',
            ])
            ->map(function (Booking $booking) {
                $fallbackExpiry = $booking->created_at?->copy()->addDay();

                return array_merge($booking->toArray(), [
                    'duration_hours' => BookingLifecycle::durationHours($booking),
                    'reschedule_available' => ! in_array($booking->status, ['completed', 'cancelled', 'expired', 'refunded'], true)
                        && BookingLifecycle::canReschedule($booking),
                    'effective_payment_expires_at' => ($booking->payment_expires_at ?: $fallbackExpiry)?->toISOString(),
                ]);
            });

        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'alternate_phone' => $user->alternate_phone,
                'address' => $user->address ?: ($bookings->first()['customer_address'] ?? null),
            ],
            'summary' => [
                'total_sessions' => $bookings->count(),
                'completed_sessions' => $bookings->where('status', 'completed')->count(),
                'pending_sessions' => $bookings->where('status', 'pending')->count(),
                'total_spent' => $bookings->sum('amount'),
                'total_photos' => $bookings->sum('photos_taken'),
            ],
            'bookings' => $bookings->values(),
        ];
    }

    private function syncUserBookings($bookings, MidtransBookingService $midtrans): void
    {
        foreach ($bookings as $booking) {
            if ($booking->status === 'confirmed' && BookingLifecycle::eventEnd($booking)->lte(now())) {
                $booking->update(['status' => 'completed']);
                continue;
            }

            if ($booking->status === 'pending') {
                if ($booking->midtrans_order_id) {
                    $midtrans->sync($booking);
                } else {
                    $midtrans->expireLocallyWhenNeeded($booking);
                }
            }
        }
    }
}
