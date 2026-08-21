<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'timezone',
        'language',
        'password',
        'role',
        'two_factor_enabled',
        'notification_preferences',
        'password_changed_at',
        'last_login_at',
        'email_verification_code_hash',
        'email_verification_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',
        'two_factor_code_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_expires_at' => 'datetime',
        'notification_preferences' => 'array',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public const DEFAULT_ADMIN_NOTIFICATION_PREFERENCES = [
        'daily_summary' => true,
        'new_customer_alerts' => false,
        'system_updates' => true,
        'booking_requests' => true,
        'low_stock_alerts' => true,
        'message_activity' => false,
        'desktop_toasts' => true,
        'alert_sound' => 'corporate',
    ];

    public function generateEmailVerificationCode(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    public function generateTwoFactorCode(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'two_factor_code_hash' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    public function clearTwoFactorCode(): void
    {
        $this->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_expires_at' => null,
        ])->save();
    }

    public function adminNotificationPreferences(): array
    {
        return array_merge(
            self::DEFAULT_ADMIN_NOTIFICATION_PREFERENCES,
            $this->notification_preferences ?? [],
        );
    }

    public function adminNotificationEnabled(string $key): bool
    {
        return (bool) ($this->adminNotificationPreferences()[$key] ?? false);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
