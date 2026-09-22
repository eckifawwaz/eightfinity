<?php

namespace App\Http\Controllers;

use App\Mail\AdminBookingNotificationMail;
use App\Models\Booking;
use App\Support\AdminNotifier;
use App\Support\BookingLifecycle;
use App\Support\MidtransBookingService;
use App\Support\SimpleReceiptPdf;
use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Midtrans\Snap;

class BookingPaymentController extends Controller
{
    private const PACKAGES = [
        'wedding' => [
            'name' => 'Wedding Package',
            'prices' => [3000000, 4500000, 5800000],
        ],
        'reservation' => [
            'name' => 'Reservation Package',
            'prices' => [799000, 899000, 999000],
        ],
        'unlimited' => [
            'name' => 'Unlimited Package',
            'prices' => [2000000, 2500000, 3000000],
        ],
    ];

    private const ROOM_SIZES = ['3 x 3 meter', '4 x 4 meter', '5 x 5 meter'];

    public function store(Request $request, MidtransBookingService $midtrans): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'in:wedding,reservation,unlimited'],
            'option' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'after:'.now(BookingLifecycle::timezone())->toDateString()],
            'time' => ['required', 'date_format:H:i'],
            'booth_size' => ['required', 'in:'.implode(',', self::ROOM_SIZES)],
            'address' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'in:qris,wallet,bank'],
            'payment_provider' => ['required', 'in:qris,gopay,ovo,dana,bca'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $package = self::PACKAGES[$validated['package']];
        $option = (int) $validated['option'];
        abort_unless(isset($package['prices'][$option]), 422, 'Invalid package option.');

        $lock = Cache::lock('booking-date:'.$validated['date'], 10);

        try {
            $booking = $lock->block(5, function () use ($request, $validated, $package, $option, $midtrans) {
                Booking::query()
                    ->whereDate('booking_date', $validated['date'])
                    ->where('status', 'pending')
                    ->get()
                    ->each(fn (Booking $booking) => $midtrans->sync($booking));

                $this->ensureBookingDateIsAvailable($validated['date']);

                return Booking::create([
                    'user_id' => $request->user()->id,
                    'booking_code' => $this->generateBookingCode(),
                    'package_slug' => $validated['package'],
                    'package_name' => $package['name'],
                    'package_option' => $option,
                    'booking_date' => $validated['date'],
                    'booking_time' => $validated['time'],
                    'booth_size' => $validated['booth_size'],
                    'people' => 0,
                    'customer_address' => $validated['address'],
                    'booking_location' => $validated['location'],
                    'amount' => $package['prices'][$option],
                    'payment_method' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_provider'],
                    'payment_proof' => $request->file('payment_proof')?->store('payment-proofs'),
                    'payment_expires_at' => now()->addDay(),
                    'status' => 'pending',
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'date' => 'Tanggal sedang diproses oleh pengguna lain. Silakan coba lagi.',
            ]);
        }

        $this->notifyAdmins($booking);

        if ($midtrans->configured()) {
            return redirect()->away($this->createMidtransPaymentUrl($booking, $midtrans));
        }

        if ($paymentLink = $this->paymentLinkFor($booking)) {
            return redirect()->away($paymentLink);
        }

        // Legacy/manual proof flow remains usable when Midtrans is intentionally disabled.
        if ($booking->payment_proof) {
            return redirect()->route('user.payment.success', $booking);
        }

        throw ValidationException::withMessages([
            'payment' => 'Midtrans belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY di .env atau siapkan Payment Link.',
        ]);
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'EF-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Booking::query()->where('booking_code', $code)->exists());

        return $code;
    }

    private function paymentLinkFor(Booking $booking): ?string
    {
        return config("services.midtrans.payment_links.{$booking->package_slug}.{$booking->package_option}");
    }

    private function createMidtransPaymentUrl(Booking $booking, MidtransBookingService $midtrans): string
    {
        $midtrans->configure();

        $orderId = $booking->booking_code;
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $booking->amount,
            ],
            'item_details' => [[
                'id' => "{$booking->package_slug}-{$booking->package_option}",
                'price' => $booking->amount,
                'quantity' => 1,
                'name' => $booking->package_name,
            ]],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone' => $booking->user->phone ?? null,
            ],
            'callbacks' => [
                'finish' => route('user.payment.finish', $booking),
            ],
        ];

        try {
            $transaction = Snap::createTransaction($params);
        } catch (Exception $exception) {
            Log::error('Midtrans Snap transaction failed', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'payment' => str_contains($exception->getMessage(), '401')
                    ? 'Midtrans menolak Server Key. Periksa kembali Server Key lalu jalankan php artisan config:clear.'
                    : 'Gagal membuat transaksi Midtrans. Cek konfigurasi Server Key dan coba lagi.',
            ]);
        }

        $booking->update([
            'midtrans_order_id' => $orderId,
            'midtrans_snap_token' => $transaction->token ?? null,
            'midtrans_redirect_url' => $transaction->redirect_url ?? null,
            'midtrans_status' => 'pending',
        ]);

        return $transaction->redirect_url;
    }

    public function notification(Request $request, MidtransBookingService $midtrans): JsonResponse
    {
        $midtrans->configure();

        try {
            $notification = new \Midtrans\Notification();
        } catch (Exception $exception) {
            Log::error('Midtrans notification verification failed', [
                'message' => $exception->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['message' => 'Notification verification failed'], 500);
        }

        $booking = Booking::query()
            ->where('midtrans_order_id', $notification->order_id)
            ->orWhere('booking_code', $notification->order_id)
            ->first();

        if (! $booking) {
            Log::warning('Midtrans notification booking not found', ['order_id' => $notification->order_id]);

            return response()->json(['message' => 'Booking not found'], 404);
        }

        $midtrans->apply($booking, $notification);

        return response()->json(['message' => 'OK']);
    }

    private function notifyAdmins(Booking $booking): void
    {
        app(AdminNotifier::class)->send('booking_requests', new AdminBookingNotificationMail($booking));
    }

    public function success(Request $request, Booking $booking): View
    {
        $this->authorizeOwner($request, $booking);

        return view('react', ['booking' => $this->successPayload($booking)]);
    }

    public function successData(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwner($request, $booking);

        return response()->json($this->successPayload($booking));
    }

    public function receiptPdf(Request $request, Booking $booking): Response
    {
        $this->authorizeOwner($request, $booking);
        $filename = 'eightfinity-receipt-'.$booking->booking_code.'.pdf';

        return response(SimpleReceiptPdf::make($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function successPayload(Booking $booking): array
    {
        $booking->loadMissing('user:id,name');

        return array_merge(
            $booking->only([
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
                'status',
                'midtrans_status',
                'midtrans_payment_type',
            ]),
            [
                'customer_name' => $booking->user?->name ?? '-',
                'duration_hours' => BookingLifecycle::durationHours($booking),
                'team_arrival_time' => BookingLifecycle::teamArrivalTime($booking),
                'reschedule_available' => BookingLifecycle::canReschedule($booking),
            ],
        );
    }

    public function paymentFinish(Request $request, Booking $booking, MidtransBookingService $midtrans): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);

        if ($booking->status === 'pending' && $booking->midtrans_order_id) {
            $booking = $midtrans->sync($booking);
        }

        $requestStatus = $request->query('transaction_status');
        $paid = in_array($booking->midtrans_status, ['settlement', 'capture'], true)
            || in_array($requestStatus, ['settlement', 'capture'], true)
            || in_array($booking->status, ['confirmed', 'completed'], true);

        if ($paid) {
            return redirect()->route('user.payment.success', $booking);
        }

        return redirect()->route('user.profile');
    }

    public function continuePayment(Request $request, Booking $booking, MidtransBookingService $midtrans): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        $booking = $midtrans->sync($booking);

        abort_unless($booking->status === 'pending', 422, 'This booking is no longer awaiting payment.');
        abort_if(
            in_array($booking->midtrans_status, ['settlement', 'capture'], true),
            422,
            'This booking has already been paid and is awaiting admin confirmation.'
        );

        if ($booking->midtrans_redirect_url) {
            return redirect()->away($booking->midtrans_redirect_url);
        }

        if ($paymentLink = $this->paymentLinkFor($booking)) {
            return redirect()->away($paymentLink);
        }

        abort(422, 'Payment link is not available for this booking.');
    }

    public function paymentReturn(Request $request): RedirectResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        abort_unless($booking, 404);

        if (! filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN)) {
            $booking->update([
                'status' => 'pending',
                'midtrans_status' => 'settlement',
            ]);
        }

        return redirect()->route('user.payment.success', $booking);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort(422, 'Bookings can no longer be self-cancelled. Please contact admin.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_unless(
            in_array($booking->status, ['cancelled', 'completed', 'expired', 'refunded'], true),
            422,
            'Only cancelled, completed, expired, or refunded bookings can be deleted.'
        );

        $booking->delete();

        return redirect()->route('user.profile')->with('status', 'booking-deleted');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeOwner($request, $booking);
        abort_if(
            in_array($booking->status, ['completed', 'cancelled', 'expired', 'refunded'], true),
            422,
            'This booking cannot be rescheduled.'
        );
        abort_unless(
            BookingLifecycle::canReschedule($booking),
            422,
            'Reschedule hanya tersedia sebelum memasuki H-3 dari hari booking.'
        );

        $validated = $request->validate([
            'date' => ['required', 'date', 'after:'.now(BookingLifecycle::timezone())->toDateString()],
            'time' => ['required', 'date_format:H:i'],
            'booth_size' => ['required', 'in:'.implode(',', self::ROOM_SIZES)],
            'address' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        $lock = Cache::lock('booking-date:'.$validated['date'], 10);

        try {
            $lock->block(5, function () use ($validated, $booking) {
                Booking::query()
                    ->whereDate('booking_date', $validated['date'])
                    ->where('status', 'pending')
                    ->whereKeyNot($booking->id)
                    ->get()
                    ->each(fn (Booking $candidate) => app(MidtransBookingService::class)->sync($candidate));

                $this->ensureBookingDateIsAvailable($validated['date'], $booking);

                $booking->update([
                    'booking_date' => $validated['date'],
                    'booking_time' => $validated['time'],
                    'booth_size' => $validated['booth_size'],
                    'customer_address' => $validated['address'],
                    'booking_location' => $validated['location'],
                    // Paid bookings are not charged again. A previously confirmed booking
                    // returns to pending only so admin can acknowledge the new schedule.
                    'status' => $booking->status === 'confirmed' ? 'pending' : $booking->status,
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'date' => 'Tanggal sedang diproses oleh pengguna lain. Silakan coba lagi.',
            ]);
        }

        return redirect()->route('user.profile')->with('status', 'booking-rescheduled');
    }

    private function ensureBookingDateIsAvailable(string $date, ?Booking $ignoredBooking = null): void
    {
        $query = Booking::query()
            ->whereDate('booking_date', $date)
            ->whereNotIn('status', ['cancelled', 'expired', 'refunded']);

        if ($ignoredBooking) {
            $query->whereKeyNot($ignoredBooking->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date' => 'Tanggal tersebut sudah penuh. Silakan pilih tanggal lain.',
            ]);
        }
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }
}
