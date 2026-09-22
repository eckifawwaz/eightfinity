<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Support\BookingLifecycle;
use App\Support\MidtransBookingService;
use Illuminate\Console\Command;

class SyncBookingStatuses extends Command
{
    protected $signature = 'bookings:sync-statuses';

    protected $description = 'Expire unpaid bookings and complete confirmed bookings when their booked duration ends.';

    public function handle(MidtransBookingService $midtrans): int
    {
        $expired = 0;
        $completed = 0;

        Booking::query()
            ->where('status', 'pending')
            ->chunkById(100, function ($bookings) use ($midtrans, &$expired) {
                foreach ($bookings as $booking) {
                    $before = $booking->status;
                    $midtrans->sync($booking);
                    if ($before !== 'expired' && $booking->fresh()->status === 'expired') {
                        $expired++;
                    }
                }
            });

        Booking::query()
            ->where('status', 'confirmed')
            ->chunkById(100, function ($bookings) use (&$completed) {
                foreach ($bookings as $booking) {
                    if (BookingLifecycle::eventEnd($booking)->lte(now())) {
                        $booking->update(['status' => 'completed']);
                        $completed++;
                    }
                }
            });

        $this->info("Expired {$expired} booking(s); completed {$completed} booking(s).");

        return self::SUCCESS;
    }
}
