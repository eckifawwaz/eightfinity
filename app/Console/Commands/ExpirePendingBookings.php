<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ExpirePendingBookings extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Mark pending bookings older than 24 hours as expired, freeing up their date.';

    public function handle(): int
    {
        $expired = Booking::query()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('midtrans_status')
                    ->orWhereNotIn('midtrans_status', ['settlement', 'capture']);
            })
            ->where('created_at', '<=', now()->subDay())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} pending booking(s).");

        return self::SUCCESS;
    }
}
