<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'booking_code',
        'package_slug',
        'package_name',
        'package_option',
        'booking_date',
        'booking_time',
        'people',
        'customer_address',
        'booking_location',
        'layout_name',
        'booth_size',
        'printer_position',
        'entrance_direction',
        'layout_positions',
        'amount',
        'payment_method',
        'payment_provider',
        'payment_proof',
        'status',
        'midtrans_order_id',
        'midtrans_snap_token',
        'midtrans_redirect_url',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'midtrans_status',
        'payment_expires_at',
        'photos_taken',
        'booth_paused',
        'equipment_status',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'integer',
        'layout_positions' => 'array',
        'photos_taken' => 'integer',
        'booth_paused' => 'boolean',
        'equipment_status' => 'array',
        'payment_expires_at' => 'datetime',
    ];

    public const DEFAULT_EQUIPMENT_STATUS = [
        'camera' => true,
        'printer' => true,
        'lighting' => true,
        'backdrop' => true,
    ];

    public function equipmentStatus(): array
    {
        return array_merge(self::DEFAULT_EQUIPMENT_STATUS, $this->equipment_status ?? []);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function queueGuests(): HasMany
    {
        return $this->hasMany(QueueGuest::class)->orderBy('queue_order');
    }
}
