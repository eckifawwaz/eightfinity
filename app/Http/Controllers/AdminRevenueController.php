<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminRevenueController extends Controller
{
    private const REVENUE_STATUSES = ['confirmed', 'completed'];

    public function index(): View
    {
        $now = Carbon::now();

        $thisWeek = $this->sumBetween($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        $lastWeek = $this->sumBetween(
            $now->copy()->subWeek()->startOfWeek(),
            $now->copy()->subWeek()->endOfWeek(),
        );

        $thisMonth = $this->sumBetween($now->copy()->startOfMonth(), $now->copy()->endOfMonth());
        $lastMonth = $this->sumBetween(
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        );

        $thisYear = $this->sumBetween($now->copy()->startOfYear(), $now->copy()->endOfYear());
        $lastYear = $this->sumBetween($now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear());

        return view('react', [
            'adminRevenue' => [
                'week' => ['amount' => $thisWeek, 'change' => $this->percentChange($lastWeek, $thisWeek)],
                'month' => ['amount' => $thisMonth, 'change' => $this->percentChange($lastMonth, $thisMonth)],
                'year' => ['amount' => $thisYear, 'change' => $this->percentChange($lastYear, $thisYear)],
                'monthly_trend' => $this->monthlyTrend($now->year),
                'weekly_trend' => $this->weeklyTrend($now),
                'transactions' => $this->recentTransactions(),
            ],
        ]);
    }

    private function sumBetween(Carbon $start, Carbon $end): int
    {
        return (int) Booking::query()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    private function percentChange(int $previous, int $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function monthlyTrend(int $year): array
    {
        $bookings = Booking::query()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereYear('created_at', $year)
            ->get(['created_at', 'amount']);

        $sums = array_fill(1, 12, 0);
        foreach ($bookings as $booking) {
            $sums[(int) $booking->created_at->format('n')] += (int) $booking->amount;
        }

        return collect($sums)
            ->map(fn ($amount, $month) => [
                'label' => Carbon::create()->month($month)->format('M'),
                'amount' => $amount,
            ])
            ->values()
            ->all();
    }

    private function weeklyTrend(Carbon $now): array
    {
        $weeks = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = $now->copy()->subWeeks($i)->startOfWeek();
            $end = $now->copy()->subWeeks($i)->endOfWeek();

            $weeks[] = [
                'label' => $start->format('d/m'),
                'amount' => $this->sumBetween($start, $end),
            ];
        }

        return $weeks;
    }

    private function recentTransactions(): array
    {
        return Booking::with('user:id,name')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->user?->name ?? 'Unknown Customer',
                'created_at' => $booking->created_at?->toISOString(),
                'status' => $booking->status,
                'amount' => $booking->amount,
            ])
            ->all();
    }
}
