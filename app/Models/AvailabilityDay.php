<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's answer about one day.
 *
 * A day with no row is unknown, not free. See App\Domain\Calendar\Availability
 * for everything that reasons about these; this only stores them.
 */
class AvailabilityDay extends Model
{
    public const AVAILABLE   = 'available';
    public const UNAVAILABLE = 'unavailable';

    protected $fillable = ['user_id', 'day', 'state', 'note'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
