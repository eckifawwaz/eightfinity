<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->user?->name ?? 'Unknown Customer',
                'customer_email' => $booking->user?->email ?? '-',
                'package_name' => $booking->package_name,
                'package_option' => $booking->package_option,
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
                'people' => $booking->people,
                'customer_address' => $booking->customer_address,
                'booking_location' => $booking->booking_location,
                'amount' => $booking->amount,
                'payment_method' => $booking->payment_method,
                'payment_provider' => $booking->payment_provider,
                'status' => $booking->status,
            ]);

        return view('react', [
            'adminBookings' => $bookings,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'booking-updated');
    }
}
