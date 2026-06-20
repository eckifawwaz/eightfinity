<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLayoutController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with('user:id,name,email')
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
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
                'booking_location' => $booking->booking_location,
                'layout_name' => $booking->layout_name ?? 'Wedding Setup',
                'booth_size' => $booking->booth_size ?? '3 x 3 meter',
                'printer_position' => $booking->printer_position ?? 'inside',
                'entrance_direction' => $booking->entrance_direction ?? 'left',
                'layout_positions' => $booking->layout_positions,
                'status' => $booking->status,
            ]);

        return view('react', [
            'adminLayout' => [
                'bookings' => $bookings,
                'selected_booking_id' => $request->integer('booking') ?: $bookings->first()['id'] ?? null,
            ],
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'layout_name' => ['required', 'in:Wedding Setup,Corporate Event,Birthday Party,Graduation Setup,Custom Client Setup'],
            'booth_size' => ['required', 'in:3 x 3 meter,4 x 4 meter,5 x 5 meter'],
            'printer_position' => ['required', 'in:inside,outside'],
            'entrance_direction' => ['required', 'in:left,right'],
            'layout_positions' => ['nullable', 'json'],
        ]);

        if (! empty($validated['layout_positions'])) {
            $validated['layout_positions'] = json_decode($validated['layout_positions'], true);
        }

        $booking->update($validated);

        return redirect('/admin/layout?booking='.$booking->id)->with('status', 'layout-updated');
    }
}
