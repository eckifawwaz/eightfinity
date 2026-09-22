<?php

namespace App\Support;

use App\Models\Booking;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction as MidtransTransaction;

class MidtransBookingService
{
    public function configured(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    public function configure(): void
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$clientKey = config('services.midtrans.client_key');
        MidtransConfig::$isProduction = filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    public function sync(Booking $booking): Booking
    {
        if (! $this->configured() || ! $booking->midtrans_order_id) {
            return $this->expireLocallyWhenNeeded($booking);
        }

        $this->configure();

        try {
            $status = MidtransTransaction::status($booking->midtrans_order_id);
            $this->apply($booking, $status);
        } catch (Exception $exception) {
            Log::warning('Midtrans booking sync failed', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);

            $this->expireLocallyWhenNeeded($booking);
        }

        return $booking->refresh();
    }

    public function apply(Booking $booking, object|array $payload): Booking
    {
        $value = static function (string $key) use ($payload) {
            return is_array($payload) ? ($payload[$key] ?? null) : ($payload->{$key} ?? null);
        };

        $transactionStatus = $value('transaction_status');
        $paymentType = $value('payment_type') ?: $booking->midtrans_payment_type;
        $expiry = $this->resolveExpiry($booking, $payload, $paymentType);

        $booking->fill([
            'status' => $this->bookingStatusFromMidtrans($transactionStatus, $booking->status),
            'payment_method' => $paymentType ?: $booking->payment_method,
            'payment_provider' => $paymentType ?: $booking->payment_provider,
            'midtrans_transaction_id' => $value('transaction_id') ?: $booking->midtrans_transaction_id,
            'midtrans_payment_type' => $paymentType,
            'midtrans_status' => $transactionStatus ?: $booking->midtrans_status,
            'payment_expires_at' => $expiry,
        ]);
        $booking->save();

        return $this->expireLocallyWhenNeeded($booking);
    }

    public function bookingStatusFromMidtrans(?string $transactionStatus, string $currentStatus = 'pending'): string
    {
        if (in_array($currentStatus, ['confirmed', 'completed'], true)
            && ! in_array($transactionStatus, ['refund', 'partial_refund'], true)) {
            return $currentStatus;
        }

        return match ($transactionStatus) {
            'cancel', 'deny', 'failure' => 'cancelled',
            'expire' => 'expired',
            'refund', 'partial_refund' => 'refunded',
            default => 'pending',
        };
    }

    public function expireLocallyWhenNeeded(Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            return $booking;
        }

        if (in_array($booking->midtrans_status, ['settlement', 'capture'], true)) {
            return $booking;
        }

        $expiresAt = $booking->payment_expires_at
            ?: $booking->created_at?->copy()->addDay();

        if ($expiresAt && Carbon::now()->gte($expiresAt)) {
            $booking->update(['status' => 'expired']);
        }

        return $booking;
    }

    private function resolveExpiry(Booking $booking, object|array $payload, ?string $paymentType): ?Carbon
    {
        $value = static function (string $key) use ($payload) {
            return is_array($payload) ? ($payload[$key] ?? null) : ($payload->{$key} ?? null);
        };

        foreach (['expiry_time', 'expiry_at'] as $key) {
            if ($raw = $value($key)) {
                try {
                    return Carbon::parse($raw, BookingLifecycle::timezone())->utc();
                } catch (Exception) {
                    // Fall through to provider-specific fallback below.
                }
            }
        }

        if ($paymentType) {
            if ($booking->midtrans_payment_type === $paymentType && $booking->payment_expires_at) {
                return $booking->payment_expires_at;
            }

            $base = null;
            if ($rawTransactionTime = $value('transaction_time')) {
                try {
                    $base = Carbon::parse($rawTransactionTime, BookingLifecycle::timezone())->utc();
                } catch (Exception) {
                    $base = null;
                }
            }

            $base ??= Carbon::now();

            return $base->addMinutes(BookingLifecycle::fallbackPaymentExpiryMinutes($paymentType));
        }

        return $booking->payment_expires_at
            ?: $booking->created_at?->copy()->addDay();
    }
}
