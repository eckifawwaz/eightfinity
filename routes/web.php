<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AdminTwoFactorController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminLayoutController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminQueueController;
use App\Http\Controllers\AdminRevenueController;
use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserBookingController;
use App\Http\Controllers\UserProfileController;
use App\Support\PortalUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/notification', [BookingPaymentController::class, 'notification'])
    ->name('midtrans.notification');

Route::get('/', function (Request $request) {
    if (PortalUrl::matches($request, 'admin')) {
        return redirect(Auth::guard('admin')->check() ? '/dashboard' : '/admin/login');
    }

    return redirect()->route('register');
});

Route::middleware('portal:user')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::view('/login', 'react')->name('user.login');
        Route::view('/register', 'react')->name('register');
    });

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:web')
        ->name('login');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest:web')
        ->name('register.process');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('guest:web')
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('guest:web')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->middleware('guest:web')
        ->name('password.reset');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('guest:web')
        ->name('password.store');

    Route::match(['get', 'post'], '/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:web')
        ->name('logout');

    Route::middleware('auth:web')->group(function () {
        Route::get('/verify-email', [EmailVerificationCodeController::class, 'show'])
            ->name('verification.notice');
        Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');
        Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
        Route::put('/password', [PasswordController::class, 'update'])
            ->name('password.update');
        Route::post('/verify-email', [EmailVerificationCodeController::class, 'verify'])
            ->name('verification.code.verify');
        Route::post('/verify-email/resend', [EmailVerificationCodeController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.code.resend');

        Route::middleware('email.code.verified')->group(function () {
            Route::view('/home', 'react')->name('user.home');
            Route::get('/profile/data', [UserProfileController::class, 'data'])->name('user.profile.data');
            Route::get('/profile', UserProfileController::class)->name('user.profile');
            Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update'])->name('user.profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('user.profile.destroy');
            Route::get('/book', [UserBookingController::class, 'create'])->name('user.book');
            Route::get('/book/availability', [UserBookingController::class, 'availability'])->name('user.book.availability');
            Route::view('/packages/{slug}', 'react')->name('user.package-detail');
            Route::view('/payment', 'react')->name('user.payment');
            Route::post('/payment', [BookingPaymentController::class, 'store'])->name('user.payment.store');
            Route::get('/payment/return', [BookingPaymentController::class, 'paymentReturn'])->name('user.payment.return');
            Route::get('/payment/finish/{booking}', [BookingPaymentController::class, 'paymentFinish'])
                ->name('user.payment.finish');
            Route::post('/bookings/{booking}/continue-payment', [BookingPaymentController::class, 'continuePayment'])
                ->name('user.bookings.continue-payment');
            Route::get('/payment/success/{booking}', [BookingPaymentController::class, 'success'])
                ->name('user.payment.success');
            Route::get('/payment/success/{booking}/data', [BookingPaymentController::class, 'successData'])
                ->name('user.payment.success.data');
            Route::get('/bookings/{booking}/receipt.pdf', [BookingPaymentController::class, 'receiptPdf'])
                ->name('user.bookings.receipt.pdf');
            Route::patch('/bookings/{booking}/cancel', [BookingPaymentController::class, 'cancel'])
                ->name('user.bookings.cancel');
            Route::delete('/bookings/{booking}', [BookingPaymentController::class, 'destroy'])
                ->name('user.bookings.destroy');
            Route::patch('/bookings/{booking}/reschedule', [BookingPaymentController::class, 'reschedule'])
                ->name('user.bookings.reschedule');
        });
    });
});

Route::middleware('portal:admin')->group(function () {
    Route::view('/admin/login', 'react')
        ->middleware('guest:admin')
        ->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:admin')
        ->name('admin.login.process');

    Route::middleware(['auth:admin', 'admin'])->group(function () {
        Route::get('/admin/two-factor', [AdminTwoFactorController::class, 'show'])->name('admin.two-factor.show');
        Route::post('/admin/two-factor', [AdminTwoFactorController::class, 'verify'])->name('admin.two-factor.verify');
        Route::post('/admin/two-factor/resend', [AdminTwoFactorController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('admin.two-factor.resend');
    });

    Route::middleware(['auth:admin', 'admin', 'admin.two_factor'])->group(function () {
        Route::get('/admin/profile', [AdminProfileController::class, 'show'])->name('admin.profile');
        Route::patch('/admin/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');
        Route::put('/admin/profile/password', [AdminProfileController::class, 'updatePassword'])->name('admin.profile.password');
        Route::patch('/admin/profile/security', [AdminProfileController::class, 'updateSecurity'])->name('admin.profile.security');
        Route::patch('/admin/profile/notifications', [AdminProfileController::class, 'updateNotifications'])->name('admin.profile.notifications');
        Route::get('/admin/profile/log', [AdminProfileController::class, 'downloadLog'])->name('admin.profile.log');
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/admin/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings');
        Route::patch('/admin/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])
            ->name('admin.bookings.status');
        Route::delete('/admin/bookings/{booking}', [AdminBookingController::class, 'destroy'])
            ->name('admin.bookings.destroy');
        Route::get('/admin/queue', [AdminQueueController::class, 'index'])->name('admin.queue');
        Route::patch('/admin/queue/{booking}', [AdminQueueController::class, 'update'])->name('admin.queue.update');
        Route::patch('/admin/queue/{booking}/booth', [AdminQueueController::class, 'updateBooth'])->name('admin.queue.booth');
        Route::patch('/admin/queue/{booking}/equipment', [AdminQueueController::class, 'updateEquipment'])->name('admin.queue.equipment');
        Route::post('/admin/queue/{booking}/guests', [AdminQueueController::class, 'storeGuest'])->name('admin.queue.guests.store');
        Route::patch('/admin/queue/guests/{guest}/start', [AdminQueueController::class, 'startGuest'])->name('admin.queue.guests.start');
        Route::delete('/admin/queue/guests/{guest}', [AdminQueueController::class, 'deleteGuest'])->name('admin.queue.guests.destroy');
        Route::patch('/admin/queue/guests/{guest}/complete', [AdminQueueController::class, 'completeGuest'])->name('admin.queue.guests.complete');
        Route::get('/admin/customers', [AdminCustomerController::class, 'index'])->name('admin.customers');
        Route::patch('/admin/customers/{user}', [AdminCustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/admin/customers/{user}', [AdminCustomerController::class, 'destroy'])->name('admin.customers.destroy');
        Route::get('/admin/layout', [AdminLayoutController::class, 'index'])->name('admin.layout');
        Route::patch('/admin/layout/{booking}', [AdminLayoutController::class, 'update'])->name('admin.layout.update');
        Route::get('/admin/revenue', [AdminRevenueController::class, 'index'])->name('admin.revenue');
        Route::match(['get', 'post'], '/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('admin.logout');
    });

    Route::redirect('/admin', '/dashboard');
});
