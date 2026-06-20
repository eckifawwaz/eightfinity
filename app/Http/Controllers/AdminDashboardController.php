<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();

        $bookingsToday = Booking::whereDate('booking_date', $today)->count();
        $activeSessions = Booking::where('status', 'confirmed')->count();
        $queueLength = Booking::where('status', 'pending')->count();
        $revenueToday = Booking::whereDate('booking_date', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('amount');

        $recentBookings = Booking::with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->user?->name ?? 'Unknown Customer',
                'package_name' => $booking->package_name,
                'booking_time' => $booking->booking_time,
                'status' => $booking->status,
                'amount' => $booking->amount,
            ]);

        $maxPackageBookings = max(Booking::query()->selectRaw('COUNT(*) as total')->groupBy('package_name')->pluck('total')->max() ?? 1, 1);
        $packageStats = Booking::query()
            ->select('package_name', DB::raw('COUNT(*) as total_bookings'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('package_name')
            ->orderByDesc('total_bookings')
            ->get()
            ->map(fn ($package) => [
                'name' => $package->package_name,
                'count' => (int) $package->total_bookings,
                'revenue' => (int) $package->revenue,
                'width' => max(8, round(((int) $package->total_bookings / $maxPackageBookings) * 100)),
            ]);

        return view('react', [
            'adminDashboard' => [
                'metrics' => [
                    'bookings_today' => $bookingsToday,
                    'active_sessions' => $activeSessions,
                    'queue_length' => $queueLength,
                    'revenue_today' => $revenueToday,
                ],
                'recent_bookings' => $recentBookings,
                'package_stats' => $packageStats,
            ],
        ]);
    }
}
