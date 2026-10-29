<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that one attendance reminder has gone out.
 *
 * Written before the notice is counted as sent and unique on the booking and
 * the stage, so a command that runs several times a day cannot chase somebody
 * four times for the same thing.
 */
class AttendanceReminder extends Model
{
    protected $fillable = ['booking_id', 'stage', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
