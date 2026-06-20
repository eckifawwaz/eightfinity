<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
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
                'people',
                'customer_address',
                'booking_location',
                'amount',
                'payment_method',
                'payment_provider',
                'status',
                'created_at',
            ]);

        return view('react', [
            'profile' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'alternate_phone' => $user->alternate_phone,
                    'address' => $bookings->first()?->customer_address,
                ],
                'summary' => [
                    'total_sessions' => $bookings->count(),
                    'completed_sessions' => $bookings->where('status', 'completed')->count(),
                    'pending_sessions' => $bookings->where('status', 'pending')->count(),
                    'total_spent' => $bookings->sum('amount'),
                ],
                'bookings' => $bookings,
            ],
        ]);
    }
}
