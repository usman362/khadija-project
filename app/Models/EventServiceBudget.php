<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the client expects to spend on ONE service of a multi-service request.
 *
 * Bids have always been per service; the budget was one figure for the whole
 * event. Five professionals bidding on five different services all saw the same
 * total, which is not a budget any of them could price against.
 */
class EventServiceBudget extends Model
{
    protected $fillable = ['event_id', 'category_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
