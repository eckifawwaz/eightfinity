<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookingLifecycle;
use App\Support\MidtransBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function index(MidtransBookingService $midtrans): View
    {
        // Keep operational statuses fresh even if the scheduler was delayed.
        Booking::query()->whereIn('status', ['pending', 'confirmed'])->get()->each(function (Booking $booking) use ($midtrans) {
            if ($booking->status === 'confirmed' && BookingLifecycle::eventEnd($booking)->lte(now())) {
                $booking->update(['status' => 'completed']);

                return;
            }

            if ($booking->status === 'pending') {
                $midtrans->sync($booking);
            }
        });

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
                'booth_size' => $booking->booth_size ?? '3 x 3 meter',
                'customer_address' => $booking->customer_address,
                'booking_location' => $booking->booking_location,
                'amount' => $booking->amount,
                'payment_method' => $booking->payment_method,
                'payment_provider' => $booking->payment_provider,
                'status' => $booking->status,
                'midtrans_status' => $booking->midtrans_status,
            ]);

        return view('react', [
            'adminBookings' => $bookings,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled,expired'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'booking-updated');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings')->with('status', 'booking-deleted');
    }
}
