<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\QueueGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminQueueController extends Controller
{
    private const EQUIPMENT_KEYS = ['camera', 'printer', 'lighting', 'backdrop'];

    private const PACKAGE_DURATION_HOURS = [
        'Wedding Package' => [4, 8],
        'Reservation Package' => [4, 5],
        'Unlimited Package' => [2, 3, 4],
    ];

    public function index(): View
    {
        $confirmedBookings = Booking::with('user:id,name,email')
            ->where('status', 'confirmed')
            ->get();

        [$event, $phase] = $this->selectCurrentEvent($confirmedBookings, Carbon::now());

        $guests = $event ? $event->queueGuests()->get() : collect();
        $currentGuest = $guests->firstWhere('status', 'in_session');
        $waitingGuests = $guests->where('status', 'waiting')->values();
        $completedGuests = $guests->where('status', 'completed');

        $servingNumber = $completedGuests->count() + 1;

        $avgWaitMinutes = $this->averageWaitMinutes(
            $completedGuests->merge($currentGuest ? [$currentGuest] : [])
        );

        return view('react', [
            'adminQueue' => [
                'event' => $event ? $this->present($event, $phase, $currentGuest, $servingNumber) : null,
                'queue' => $waitingGuests->map(fn (QueueGuest $guest) => $this->presentGuest($guest))->all(),
                'stats' => [
                    'in_session' => $currentGuest ? 1 : 0,
                    'in_queue' => $waitingGuests->count(),
                    'avg_wait_minutes' => $avgWaitMinutes,
                    'total_sessions' => $completedGuests->count(),
                ],
            ],
        ]);
    }

    public function storeGuest(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'checked_in_at' => ['required', 'date_format:H:i'],
        ]);

        $maxOrder = $booking->queueGuests()->max('queue_order');

        $booking->queueGuests()->create([
            'guest_name' => $validated['guest_name'],
            'checked_in_at' => $validated['checked_in_at'],
            'queue_order' => ($maxOrder ?? 0) + 1,
            'status' => 'waiting',
        ]);

        return back()->with('status', 'queue-guest-added');
    }

    public function startGuest(QueueGuest $guest): RedirectResponse
    {
        $alreadyActive = QueueGuest::where('booking_id', $guest->booking_id)
            ->where('status', 'in_session')
            ->where('id', '!=', $guest->id)
            ->exists();

        if ($alreadyActive) {
            return back()->with('status', 'queue-guest-busy');
        }

        $guest->update(['status' => 'in_session', 'session_started_at' => Carbon::now()]);

        return back()->with('status', 'queue-guest-started');
    }

    public function moveGuestNext(QueueGuest $guest): RedirectResponse
    {
        $guest->delete();

        return back()->with('status', 'queue-guest-skipped');
    }

    public function completeGuest(QueueGuest $guest): RedirectResponse
    {
        $guest->update(['status' => 'completed']);

        return back()->with('status', 'queue-guest-completed');
    }

    private function averageWaitMinutes($guests): ?int
    {
        $today = Carbon::today();

        $waits = $guests
            ->filter(fn (QueueGuest $guest) => $guest->session_started_at && $guest->checked_in_at)
            ->map(function (QueueGuest $guest) use ($today) {
                [$hours, $minutes] = array_pad(explode(':', $guest->checked_in_at), 2, 0);
                $checkedIn = (clone $today)->setTime((int) $hours, (int) $minutes);

                return max(0, $checkedIn->diffInMinutes($guest->session_started_at, false));
            });

        return $waits->isEmpty() ? null : (int) round($waits->avg());
    }

    /**
     * Pick the confirmed booking that best represents "the event happening right now",
     * based on the real booking date/time rather than just the earliest confirmed row.
     */
    private function selectCurrentEvent($bookings, Carbon $now): array
    {
        $inProgress = null;
        $nextUpcoming = null;
        $mostRecentOverdue = null;

        foreach ($bookings as $booking) {
            $start = $this->eventStart($booking);
            $end = $this->eventEnd($booking);

            if ($now->between($start, $end)) {
                $inProgress = $booking;
                break;
            }

            if ($start->isFuture() && (! $nextUpcoming || $start->lt($this->eventStart($nextUpcoming)))) {
                $nextUpcoming = $booking;
            }

            if ($end->isPast() && (! $mostRecentOverdue || $end->gt($this->eventEnd($mostRecentOverdue)))) {
                $mostRecentOverdue = $booking;
            }
        }

        if ($inProgress) {
            return [$inProgress, 'in_progress'];
        }

        if ($nextUpcoming) {
            return [$nextUpcoming, 'upcoming'];
        }

        if ($mostRecentOverdue) {
            return [$mostRecentOverdue, 'overdue'];
        }

        return [null, null];
    }

    private function eventStart(Booking $booking): Carbon
    {
        return Carbon::parse($booking->booking_date->format('Y-m-d').' '.$booking->booking_time);
    }

    private function eventEnd(Booking $booking): Carbon
    {
        return $this->eventStart($booking)->addHours($this->durationHours($booking));
    }

    private function durationHours(Booking $booking): int
    {
        return self::PACKAGE_DURATION_HOURS[$booking->package_name][$booking->package_option] ?? 4;
    }

    public function incrementPhotos(Booking $booking): RedirectResponse
    {
        $booking->increment('photos_taken');

        return back()->with('status', 'queue-photo-logged');
    }

    public function updateBooth(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'paused' => ['required', 'boolean'],
        ]);

        $booking->update(['booth_paused' => $validated['paused']]);

        return back()->with('status', $validated['paused'] ? 'queue-booth-paused' : 'queue-booth-resumed');
    }

    public function updateEquipment(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'equipment' => ['required', 'in:'.implode(',', self::EQUIPMENT_KEYS)],
            'ok' => ['required', 'boolean'],
        ]);

        $booking->update([
            'equipment_status' => array_merge(
                $booking->equipmentStatus(),
                [$validated['equipment'] => (bool) $validated['ok']],
            ),
        ]);

        return back()->with('status', 'queue-equipment-updated');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'queue-updated');
    }

    private function present(Booking $booking, ?string $phase, ?QueueGuest $currentGuest, int $servingNumber): array
    {
        $duration = $this->durationHours($booking);

        return [
            'id' => $booking->id,
            'user_id' => $booking->user_id,
            'booking_code' => $booking->booking_code,
            'customer_name' => $booking->user?->name ?? 'Unknown Customer',
            'customer_email' => $booking->user?->email ?? '-',
            'package_name' => $booking->package_name,
            'package_option' => $booking->package_option,
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
            'booking_location' => $booking->booking_location,
            'layout_name' => $booking->layout_name ?? 'Wedding Setup',
            'booth_size' => $booking->booth_size ?? '3 x 3 meter',
            'printer_position' => $booking->printer_position ?? 'inside',
            'entrance_direction' => $booking->entrance_direction ?? 'left',
            'status' => $booking->status,
            'event_phase' => $phase,
            'starts_at' => $this->eventStart($booking)->toISOString(),
            'ends_at' => $this->eventEnd($booking)->toISOString(),
            'photos_taken' => $booking->photos_taken,
            'booth_paused' => $booking->booth_paused,
            'equipment_status' => $booking->equipmentStatus(),
            'current_guest' => $currentGuest ? $this->presentGuest($currentGuest, $servingNumber) : null,
        ];
    }

    private function presentGuest(QueueGuest $guest, ?int $servingNumber = null): array
    {
        return [
            'id' => $guest->id,
            'guest_name' => $guest->guest_name,
            'checked_in_at' => $guest->checked_in_at,
            'status' => $guest->status,
            'serving_number' => $servingNumber,
            'session_started_label' => $guest->session_started_at?->format('H:i'),
            'turn_minutes' => QueueGuest::TURN_MINUTES,
            'session_progress_percent' => $this->guestProgressPercent($guest),
        ];
    }

    private function guestProgressPercent(QueueGuest $guest): int
    {
        if (! $guest->session_started_at) {
            return 0;
        }

        $start = $guest->session_started_at;
        $end = (clone $start)->addMinutes(QueueGuest::TURN_MINUTES);
        $now = Carbon::now();

        if ($now->gte($end)) {
            return 100;
        }

        $total = $start->diffInSeconds($end);
        $elapsed = $start->diffInSeconds($now);

        return $total > 0 ? (int) round(($elapsed / $total) * 100) : 0;
    }
}
