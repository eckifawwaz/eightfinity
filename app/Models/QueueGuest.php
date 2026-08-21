<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueGuest extends Model
{
    public const TURN_MINUTES = 15;

    protected $fillable = [
        'booking_id',
        'guest_name',
        'checked_in_at',
        'queue_order',
        'status',
        'session_started_at',
    ];

    protected $casts = [
        'queue_order' => 'integer',
        'session_started_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
