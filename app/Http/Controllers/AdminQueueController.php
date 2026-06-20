<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQueueController extends Controller
{
    public function index(): View
    {
        $queue = Booking::with('user:id,name,email')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('booking_date')
            ->orderBy('booking_time')
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
                'booking_location' => $booking->booking_location,
                'layout_name' => $booking->layout_name ?? 'Wedding Setup',
                'booth_size' => $booking->booth_size ?? '3 x 3 meter',
                'printer_position' => $booking->printer_position ?? 'inside',
                'entrance_direction' => $booking->entrance_direction ?? 'left',
                'status' => $booking->status,
            ]);

        $completedSessions = Booking::where('status', 'completed')->count();

        return view('react', [
            'adminQueue' => [
                'items' => $queue,
                'completed_sessions' => $completedSessions,
            ],
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'queue-updated');
    }
}
