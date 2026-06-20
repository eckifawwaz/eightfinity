<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(): View
    {
        $customers = User::query()
            ->where('role', 'user')
            ->with(['bookings' => fn ($query) => $query->latest()])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $bookings = $user->bookings;
                $favoritePackage = $bookings
                    ->groupBy('package_name')
                    ->sortByDesc(fn ($items) => $items->count())
                    ->keys()
                    ->first();

                return [
                    'id' => $user->id,
                    'customer_code' => 'CUST-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'alternate_phone' => $user->alternate_phone,
                    'total_bookings' => $bookings->count(),
                    'total_spent' => $bookings->sum('amount'),
                    'last_visit' => $bookings->first()?->booking_date,
                    'favorite_package' => $favoritePackage ?? '-',
                    'status' => $bookings->whereIn('status', ['pending', 'confirmed'])->isNotEmpty() ? 'Active' : 'Inactive',
                    'bookings' => $bookings->map(fn ($booking) => [
                        'id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'package_name' => $booking->package_name,
                        'booking_date' => $booking->booking_date,
                        'booking_time' => $booking->booking_time,
                        'amount' => $booking->amount,
                        'status' => $booking->status,
                    ])->values(),
                ];
            });

        $newThisMonth = User::query()
            ->where('role', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('react', [
            'adminCustomers' => [
                'customers' => $customers,
                'summary' => [
                    'total_customers' => $customers->count(),
                    'active_customers' => $customers->where('status', 'Active')->count(),
                    'new_this_month' => $newThisMonth,
                ],
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.customers')->with('status', 'customer-updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 404);

        $user->delete();

        return redirect()->route('admin.customers')->with('status', 'customer-deleted');
    }
}
