<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingLifecycle
{
    public static function timezone(): string
    {
        return (string) config('app.booking_timezone', 'Asia/Jakarta');
    }

    private const PACKAGE_DURATION_HOURS = [
        'wedding' => [4, 6, 8],
        'reservation' => [3, 4, 5],
        'unlimited' => [2, 3, 4],
    ];

    public static function durationHours(Booking $booking): int
    {
        return self::PACKAGE_DURATION_HOURS[$booking->package_slug][$booking->package_option]
            ?? match ($booking->package_name) {
                'Wedding Package' => [4, 6, 8][$booking->package_option] ?? 4,
                'Reservation Package' => [3, 4, 5][$booking->package_option] ?? 3,
                'Unlimited Package' => [2, 3, 4][$booking->package_option] ?? 2,
                default => 4,
            };
    }

    public static function eventStart(Booking $booking): Carbon
    {
        $date = $booking->booking_date instanceof Carbon
            ? $booking->booking_date->toDateString()
            : Carbon::parse($booking->booking_date)->toDateString();

        return Carbon::parse($date.' '.$booking->booking_time, self::timezone());
    }

    public static function eventEnd(Booking $booking): Carbon
    {
        return self::eventStart($booking)->addHours(self::durationHours($booking));
    }

    /**
     * Reschedule closes as soon as the calendar reaches H-3.
     * Example: booking 22 Sep => last available moment is 18 Sep 23:59:59.
     */
    public static function canReschedule(Booking $booking, ?Carbon $now = null): bool
    {
        $now = $now
            ? $now->copy()->setTimezone(self::timezone())
            : Carbon::now(self::timezone());
        $cutoff = self::eventStart($booking)->startOfDay()->subDays(3);

        return $now->lt($cutoff);
    }

    public static function teamArrivalTime(Booking $booking): string
    {
        return self::eventStart($booking)->subHour()->format('H:i');
    }

    public static function fallbackPaymentExpiryMinutes(?string $paymentType): int
    {
        return match (strtolower((string) $paymentType)) {
            'qris' => 5,
            'gopay', 'gopay_tokenization', 'ovo', 'dana', 'shopeepay' => 15,
            'credit_card' => 15,
            'bank_transfer', 'echannel', 'bca_klikpay', 'bca_klikbca', 'cstore' => 24 * 60,
            default => 24 * 60,
        };
    }
}
