<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\QueueGuest;
use App\Support\BookingLifecycle;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminQueueController extends Controller
{
    private const EQUIPMENT_KEYS = ['camera', 'printer', 'lighting', 'backdrop'];

    public function index(): View
    {
        // Opportunistic sync keeps the page correct even if cron was delayed.
        Booking::query()->where('status', 'confirmed')->get()->each(function (Booking $booking) {
            if (BookingLifecycle::eventEnd($booking)->lte(now())) {
                $booking->update(['status' => 'completed']);
            }
        });

        $confirmedBookings = Booking::with('user:id,name,email')
            ->where('status', 'confirmed')
            ->get();

        [$event, $phase] = $this->selectCurrentEvent($confirmedBookings, Carbon::now(BookingLifecycle::timezone()));

        $guests = $event ? $event->queueGuests()->get() : collect();
        $currentGuest = $guests->firstWhere('status', 'in_session');
        $waitingGuests = $guests->where('status', 'waiting')->values();
        $completedGuests = $guests->where('status', 'completed');

        if ($event && $event->photos_taken !== $completedGuests->count()) {
            $event->update(['photos_taken' => $completedGuests->count()]);
        }

        $servingNumber = $completedGuests->count() + 1;
        $avgWaitMinutes = $this->averageWaitMinutes(
            $completedGuests->merge($currentGuest ? [$currentGuest] : [])
        );

        return view('react', [
            'adminQueue' => [
                'event' => $event ? $this->present($event->fresh(), $phase, $currentGuest, $servingNumber) : null,
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
        abort_unless($booking->status === 'confirmed', 422, 'Queue hanya tersedia untuk booking yang sudah confirmed.');

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'checked_in_at' => ['required', 'date_format:H:i'],
        ]);

        $validated['guest_name'] = trim($validated['guest_name']);
        $this->ensureCheckInWithinRentalWindow($booking, $validated['checked_in_at']);

        $fingerprint = sha1(mb_strtolower($validated['guest_name']).'|'.$validated['checked_in_at']);
        $lock = Cache::lock("queue-add:{$booking->id}:{$fingerprint}", 8);

        try {
            $lock->block(3, function () use ($booking, $validated) {
                $duplicate = $booking->queueGuests()
                    ->where('guest_name', $validated['guest_name'])
                    ->where('checked_in_at', $validated['checked_in_at'])
                    ->where('created_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($duplicate) {
                    return;
                }

                $maxOrder = $booking->queueGuests()->max('queue_order');
                $booking->queueGuests()->create([
                    'guest_name' => $validated['guest_name'],
                    'checked_in_at' => $validated['checked_in_at'],
                    'queue_order' => ($maxOrder ?? 0) + 1,
                    'status' => 'waiting',
                ]);
            });
        } catch (LockTimeoutException) {
            return back()->with('status', 'queue-guest-duplicate');
        }

        return back()->with('status', 'queue-guest-added');
    }

    public function startGuest(QueueGuest $guest): RedirectResponse
    {
        if ($guest->status !== 'waiting') {
            return back();
        }

        $booking = $guest->booking;
        abort_unless($booking->status === 'confirmed', 422, 'Session hanya dapat dimulai untuk booking confirmed.');
        abort_unless(
            Carbon::now(BookingLifecycle::timezone())->between(BookingLifecycle::eventStart($booking), BookingLifecycle::eventEnd($booking)),
            422,
            'Session hanya dapat dimulai saat jam sewa sedang berlangsung.'
        );

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

    public function deleteGuest(QueueGuest $guest): RedirectResponse
    {
        $booking = $guest->booking;
        $guest->delete();
        $this->syncPhotoCount($booking);

        return back()->with('status', 'queue-guest-deleted');
    }

    public function completeGuest(QueueGuest $guest): RedirectResponse
    {
        if ($guest->status !== 'completed') {
            $guest->update(['status' => 'completed']);
        }

        $this->syncPhotoCount($guest->booking);

        return back()->with('status', 'queue-guest-completed');
    }

    private function syncPhotoCount(Booking $booking): void
    {
        $booking->update([
            'photos_taken' => $booking->queueGuests()->where('status', 'completed')->count(),
        ]);
    }

    private function ensureCheckInWithinRentalWindow(Booking $booking, string $checkedInAt): void
    {
        $start = BookingLifecycle::eventStart($booking);
        $end = BookingLifecycle::eventEnd($booking);
        $candidate = Carbon::parse($start->toDateString().' '.$checkedInAt, BookingLifecycle::timezone());

        if (! $end->isSameDay($start) && $candidate->lt($start)) {
            $candidate->addDay();
        }

        if ($candidate->lt($start) || $candidate->gt($end)) {
            throw ValidationException::withMessages([
                'checked_in_at' => sprintf(
                    'Jam check-in harus berada dalam jam sewa %s - %s.',
                    $start->format('H:i'),
                    $end->format('H:i')
                ),
            ]);
        }
    }

    private function averageWaitMinutes($guests): ?int
    {
        $waits = $guests
            ->filter(fn (QueueGuest $guest) => $guest->session_started_at && $guest->checked_in_at)
            ->map(function (QueueGuest $guest) {
                $booking = $guest->booking;
                $start = BookingLifecycle::eventStart($booking);
                $checkedIn = Carbon::parse($start->toDateString().' '.$guest->checked_in_at, BookingLifecycle::timezone());
                if (! BookingLifecycle::eventEnd($booking)->isSameDay($start) && $checkedIn->lt($start)) {
                    $checkedIn->addDay();
                }

                return max(0, $checkedIn->diffInMinutes($guest->session_started_at, false));
            });

        return $waits->isEmpty() ? null : (int) round($waits->avg());
    }

    private function selectCurrentEvent($bookings, Carbon $now): array
    {
        $inProgress = null;
        $nextUpcoming = null;

        foreach ($bookings as $booking) {
            $start = BookingLifecycle::eventStart($booking);
            $end = BookingLifecycle::eventEnd($booking);

            if ($now->between($start, $end)) {
                $inProgress = $booking;
                break;
            }

            if ($start->isFuture() && (! $nextUpcoming || $start->lt(BookingLifecycle::eventStart($nextUpcoming)))) {
                $nextUpcoming = $booking;
            }
        }

        if ($inProgress) {
            return [$inProgress, 'in_progress'];
        }

        if ($nextUpcoming) {
            return [$nextUpcoming, 'upcoming'];
        }

        return [null, null];
    }

    public function updateBooth(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate(['paused' => ['required', 'boolean']]);
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

        $booking->update(['status' => $validated['status']]);

        return back()->with('status', 'queue-updated');
    }

    private function present(Booking $booking, ?string $phase, ?QueueGuest $currentGuest, int $servingNumber): array
    {
        $start = BookingLifecycle::eventStart($booking);
        $end = BookingLifecycle::eventEnd($booking);
        $now = Carbon::now(BookingLifecycle::timezone());
        $defaultCheckIn = $now->between($start, $end)
            ? $now
            : ($now->lt($start) ? $start : $end);

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
            'starts_at' => $start->toISOString(),
            'ends_at' => $end->toISOString(),
            'duration_hours' => BookingLifecycle::durationHours($booking),
            'queue_default_check_in' => $defaultCheckIn->format('H:i'),
            'queue_window_start' => $start->format('H:i'),
            'queue_window_end' => $end->format('H:i'),
            'queue_crosses_midnight' => ! $start->isSameDay($end),
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
            'session_started_label' => $guest->session_started_at?->copy()->setTimezone(BookingLifecycle::timezone())->format('H:i'),
            'turn_minutes' => QueueGuest::TURN_MINUTES,
        ];
    }
}
