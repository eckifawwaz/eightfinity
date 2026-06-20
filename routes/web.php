<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminLayoutController;
use App\Http\Controllers\AdminQueueController;
use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use App\Support\PortalUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

    Route::match(['get', 'post'], '/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:web')
        ->name('logout');

    Route::middleware('auth:web')->group(function () {
        Route::view('/home', 'react')->name('user.home');
        Route::get('/profile', UserProfileController::class)->name('user.profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
        Route::view('/book', 'react')->name('user.book');
        Route::view('/packages/{slug}', 'react')->name('user.package-detail');
        Route::view('/payment', 'react')->name('user.payment');
        Route::post('/payment', [BookingPaymentController::class, 'store'])->name('user.payment.store');
        Route::get('/payment/success/{booking}', [BookingPaymentController::class, 'success'])
            ->name('user.payment.success');
        Route::patch('/bookings/{booking}/cancel', [BookingPaymentController::class, 'cancel'])
            ->name('user.bookings.cancel');
        Route::patch('/bookings/{booking}/reschedule', [BookingPaymentController::class, 'reschedule'])
            ->name('user.bookings.reschedule');
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
        Route::view('/admin/profile', 'react')->name('admin.profile');
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/admin/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings');
        Route::patch('/admin/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])
            ->name('admin.bookings.status');
        Route::get('/admin/queue', [AdminQueueController::class, 'index'])->name('admin.queue');
        Route::patch('/admin/queue/{booking}', [AdminQueueController::class, 'update'])->name('admin.queue.update');
        Route::get('/admin/customers', [AdminCustomerController::class, 'index'])->name('admin.customers');
        Route::patch('/admin/customers/{user}', [AdminCustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/admin/customers/{user}', [AdminCustomerController::class, 'destroy'])->name('admin.customers.destroy');
        Route::get('/admin/layout', [AdminLayoutController::class, 'index'])->name('admin.layout');
        Route::patch('/admin/layout/{booking}', [AdminLayoutController::class, 'update'])->name('admin.layout.update');
        Route::match(['get', 'post'], '/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('admin.logout');
    });

    Route::redirect('/admin', '/dashboard');
});
