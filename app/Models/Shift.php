<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A work shift owned by a professional. staff_id null = an OPEN shift that
 * still needs to be filled.
 */
class Shift extends Model
{
    protected $fillable = [
        'supplier_id', 'event_id', 'staff_id', 'role', 'location',
        'starts_at', 'ends_at', 'slots', 'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'slots'     => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_id');
    }

    /** Length of the shift in hours. */
    public function hours(): float
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return 0;
        }

        return round($this->starts_at->floatDiffInHours($this->ends_at), 2);
    }
}
