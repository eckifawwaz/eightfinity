<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'integer',
        'layout_positions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
