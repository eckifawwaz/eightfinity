<?php

namespace App\Http\Controllers;

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
                'photos_taken',
                'created_at',
            ]);

        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'alternate_phone' => $user->alternate_phone,
                'address' => $user->address ?: $bookings->first()?->customer_address,
            ],
            'summary' => [
                'total_sessions' => $bookings->count(),
                'completed_sessions' => $bookings->where('status', 'completed')->count(),
                'pending_sessions' => $bookings->where('status', 'pending')->count(),
                'total_spent' => $bookings->sum('amount'),
                'total_photos' => $bookings->sum('photos_taken'),
            ],
            'bookings' => $bookings,
        ];
    }
}
