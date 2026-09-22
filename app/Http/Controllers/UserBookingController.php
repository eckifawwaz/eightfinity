<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookingLifecycle;
use App\Support\MidtransBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserBookingController extends Controller
{
    public function __construct(private readonly MidtransBookingService $midtrans)
    {
    }

    public function create(Request $request): View
    {
        return view('react', [
            'bookingAvailability' => [
                'unavailable_dates' => $this->unavailableDates($request),
            ],
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $dates = $this->unavailableDates($request);
        $selectedDate = $request->query('date');

        return response()->json([
            'unavailable_dates' => $dates,
            'date' => $selectedDate,
            'available' => $selectedDate ? ! $dates->contains($selectedDate) : null,
        ]);
    }

    private function unavailableDates(Request $request): Collection
    {
        // Release dates whose unpaid 24h/provider payment window has already ended,
        // even if the scheduler has not run yet.
        $businessToday = now(BookingLifecycle::timezone())->toDateString();

        Booking::query()
            ->where('status', 'pending')
            ->whereDate('booking_date', '>=', $businessToday)
            ->get()
            ->each(fn (Booking $booking) => $this->midtrans->sync($booking));

        $rescheduleId = $request->query('reschedule');

        return Booking::query()
            ->whereDate('booking_date', '>=', $businessToday)
            ->whereNotIn('status', ['cancelled', 'expired', 'refunded'])
            ->when($rescheduleId, function ($query, string $rescheduleId) use ($request) {
                $query->where(function ($query) use ($request, $rescheduleId) {
                    $query
                        ->whereKeyNot($rescheduleId)
                        ->orWhere('user_id', '!=', $request->user()->id);
                });
            })
            ->orderBy('booking_date')
            ->pluck('booking_date')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();
    }
}
