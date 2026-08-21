<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserBookingController extends Controller
{
    public function create(Request $request): View
    {
        $rescheduleId = $request->query('reschedule');

        $unavailableDates = Booking::query()
            ->whereDate('booking_date', '>=', today())
            ->where('status', '!=', 'cancelled')
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

        return view('react', [
            'bookingAvailability' => [
                'unavailable_dates' => $unavailableDates,
            ],
        ]);
    }
}
