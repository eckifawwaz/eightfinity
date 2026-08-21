<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\AdminBookingNotificationMail;
use App\Support\AdminNotifier;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification as MidtransNotification;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'in:wedding,reservation,unlimited'],
            'option' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'after:today'],
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
        $this->ensureBookingDateIsAvailable($validated['date']);

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'booking_code' => $this->generateBookingCode(),
            'package_slug' => $validated['package'],
            'package_name' => $package['name'],
            'package_option' => $option,
            'booking_date' => $validated['date'],
            'booking_time' => $validated['time'],
            'booth_size' => $validated['booth_size'],
            'people' => 0, // no longer collected from the customer; column is NOT NULL
            'customer_address' => $validated['address'],
            'booking_location' => $validated['location'],
            'amount' => $package['prices'][$option],
            'payment_method' => $validated['payment_method'],
            'payment_provider' => $validated['payment_provider'],
            'payment_proof' => $request->file('payment_proof')?->store('payment-proofs'),
            'status' => 'pending',
        ]);

        $this->notifyAdmins($booking);

        if ($this->hasMidtransKeys()) {
            return redirect()->away($this->createMidtransPaymentUrl($booking));
        }

        if ($paymentLink = $this->paymentLinkFor($booking)) {
            return redirect()->away($paymentLink);
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

    private function hasMidtransKeys(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    private function createMidtransPaymentUrl(Booking $booking): string
    {
        $this->configureMidtrans();

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
                'finish' => route('user.payment.success', $booking),
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
                    ? 'Midtrans menolak Server Key. Copy ulang Sandbox Server Key dari dashboard Midtrans, lalu jalankan php artisan config:clear.'
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

    private function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$clientKey = config('services.midtrans.client_key');
        MidtransConfig::$isProduction = filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    public function notification(Request $request): JsonResponse
    {
        $this->configureMidtrans();

        try {
            $notification = new MidtransNotification();
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
            Log::warning('Midtrans notification booking not found', [
                'order_id' => $notification->order_id,
            ]);

            return response()->json(['message' => 'Booking not found'], 404);
        }

        $status = $this->bookingStatusFromMidtrans(
            $notification->transaction_status,
            $notification->fraud_status
        );

        $booking->update([
            'status' => $status,
            'payment_method' => $notification->payment_type ?? $booking->payment_method,
            'payment_provider' => $notification->payment_type ?? $booking->payment_provider,
            'midtrans_transaction_id' => $notification->transaction_id,
            'midtrans_payment_type' => $notification->payment_type,
            'midtrans_status' => $notification->transaction_status,
        ]);

        return response()->json(['message' => 'OK']);
    }

    private function bookingStatusFromMidtrans(?string $transactionStatus, ?string $fraudStatus): string
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'challenge' ? 'pending' : 'confirmed';
        }

        return match ($transactionStatus) {
            'settlement' => 'confirmed',
            'pending' => 'pending',
            'cancel', 'deny', 'expire', 'failure' => 'cancelled',
            'refund', 'partial_refund' => 'refunded',
            default => 'pending',
        };
    }

    private function notifyAdmins(Booking $booking): void
    {
        app(AdminNotifier::class)->send('booking_requests', new AdminBookingNotificationMail($booking));
    }

    public function success(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return view('react', [
            'booking' => $this->successPayload($booking),
        ]);
    }

    public function successData(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        return response()->json($this->successPayload($booking));
    }

    private function successPayload(Booking $booking): array
    {
        $booking->loadMissing('user:id,name');

        return array_merge(
            $booking->only([
                'id',
                'booking_code',
                'package_name',
                'package_option',
                'booking_date',
                'booking_time',
                'booth_size',
                'customer_address',
                'booking_location',
                'amount',
                'status',
            ]),
            ['customer_name' => $booking->user?->name ?? '-'],
        );
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
                'status' => 'confirmed',
                'midtrans_status' => 'settlement',
            ]);
        }

        return redirect()->route('user.payment.success', $booking);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_if(in_array($booking->status, ['completed', 'cancelled'], true), 422, 'This booking cannot be cancelled.');
        abort_unless($this->isWithinModifiableWindow($booking), 422, 'Bookings can only be cancelled up to 3 days before the event.');

        $booking->update([
            'status' => 'cancelled',
        ]);

        return redirect()->route('user.profile')->with('status', 'booking-cancelled');
    }

    private function isWithinModifiableWindow(Booking $booking): bool
    {
        return Carbon::now()->addDays(3)->lte($this->eventStart($booking));
    }

    private function eventStart(Booking $booking): Carbon
    {
        return Carbon::parse($booking->booking_date->format('Y-m-d').' '.$booking->booking_time);
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'cancelled', 422, 'Only cancelled bookings can be deleted.');

        $booking->delete();

        return redirect()->route('user.profile')->with('status', 'booking-deleted');
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_if(in_array($booking->status, ['completed', 'cancelled'], true), 422, 'This booking cannot be rescheduled.');
        abort_unless($this->isWithinModifiableWindow($booking), 422, 'Bookings can only be rescheduled up to 3 days before the event.');

        $validated = $request->validate([
            'package' => ['required', 'in:wedding,reservation,unlimited'],
            'option' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'after:today'],
            'time' => ['required', 'date_format:H:i'],
            'booth_size' => ['required', 'in:'.implode(',', self::ROOM_SIZES)],
            'address' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        $package = self::PACKAGES[$validated['package']];
        $option = (int) $validated['option'];

        abort_unless(isset($package['prices'][$option]), 422, 'Invalid package option.');
        $this->ensureBookingDateIsAvailable($validated['date'], $booking);

        $booking->update([
            'package_slug' => $validated['package'],
            'package_name' => $package['name'],
            'package_option' => $option,
            'booking_date' => $validated['date'],
            'booking_time' => $validated['time'],
            'booth_size' => $validated['booth_size'],
            'customer_address' => $validated['address'],
            'booking_location' => $validated['location'],
            'amount' => $package['prices'][$option],
            'status' => 'pending',
        ]);

        return redirect()->route('user.profile')->with('status', 'booking-rescheduled');
    }

    private function ensureBookingDateIsAvailable(string $date, ?Booking $ignoredBooking = null): void
    {
        $query = Booking::query()
            ->whereDate('booking_date', $date)
            ->where('status', '!=', 'cancelled');

        if ($ignoredBooking) {
            $query->whereKeyNot($ignoredBooking->id);
        }

        if (! $query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'date' => 'Tanggal tersebut sudah penuh. Eightfinity saat ini hanya menerima satu booking per hari.',
        ]);
    }
}
