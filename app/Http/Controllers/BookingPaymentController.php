<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    private const PACKAGES = [
        'wedding' => [
            'name' => 'Wedding Package',
            'prices' => [5800000],
        ],
        'reservation' => [
            'name' => 'Reservation Package',
            'prices' => [899000, 899000],
        ],
        'unlimited' => [
            'name' => 'Unlimited Package',
            'prices' => [2000000, 2500000, 3000000],
        ],
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'in:wedding,reservation,unlimited'],
            'option' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'people' => ['required', 'integer', 'min:1', 'max:1000'],
            'address' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'in:qris,wallet,bank'],
            'payment_provider' => ['required', 'in:qris,gopay,ovo,dana,bca'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $package = self::PACKAGES[$validated['package']];
        $option = (int) $validated['option'];

        abort_unless(isset($package['prices'][$option]), 422, 'Invalid package option.');

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'booking_code' => 'EF-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'package_slug' => $validated['package'],
            'package_name' => $package['name'],
            'package_option' => $option,
            'booking_date' => $validated['date'],
            'booking_time' => $validated['time'],
            'people' => $validated['people'],
            'customer_address' => $validated['address'],
            'booking_location' => $validated['location'],
            'amount' => $package['prices'][$option],
            'payment_method' => $validated['payment_method'],
            'payment_provider' => $validated['payment_provider'],
            'payment_proof' => $request->file('payment_proof')?->store('payment-proofs'),
            'status' => 'pending',
        ]);

        return redirect()->route('user.payment.success', $booking);
    }

    public function success(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return view('react', [
            'booking' => $booking->only([
                'booking_code',
                'package_name',
                'package_option',
                'booking_date',
                'booking_time',
                'people',
                'customer_address',
                'booking_location',
                'amount',
                'status',
            ]),
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['completed', 'cancelled'], true)) {
            $booking->update([
                'status' => 'cancelled',
            ]);
        }

        return redirect()->route('user.profile')->with('status', 'booking-cancelled');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_if(in_array($booking->status, ['completed', 'cancelled'], true), 422, 'This booking cannot be rescheduled.');

        $validated = $request->validate([
            'package' => ['required', 'in:wedding,reservation,unlimited'],
            'option' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'people' => ['required', 'integer', 'min:1', 'max:1000'],
            'address' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        $package = self::PACKAGES[$validated['package']];
        $option = (int) $validated['option'];

        abort_unless(isset($package['prices'][$option]), 422, 'Invalid package option.');

        $booking->update([
            'package_slug' => $validated['package'],
            'package_name' => $package['name'],
            'package_option' => $option,
            'booking_date' => $validated['date'],
            'booking_time' => $validated['time'],
            'people' => $validated['people'],
            'customer_address' => $validated['address'],
            'booking_location' => $validated['location'],
            'amount' => $package['prices'][$option],
            'status' => 'pending',
        ]);

        return redirect()->route('user.profile')->with('status', 'booking-rescheduled');
    }
}
