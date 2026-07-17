<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bid extends Model
{
    protected $fillable = [
        'event_id', 'category_id', 'supplier_id', 'amount', 'note', 'is_public', 'status',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'amount'    => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** The specific service this bid targets (null = whole-event / SSR bid). */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /** Negotiation thread (client ↔ pro replies / counter-offers). */
    public function replies(): HasMany
    {
        return $this->hasMany(BidReply::class)->oldest();
    }

    /**
     * Whether $viewer may see this bid's amount.
     *  - the bidder always can;
     *  - the event owner (client) always can;
     *  - everyone else only if the bidder opted the bid public.
     */
    public function amountVisibleTo(?User $viewer, ?int $eventOwnerId = null): bool
    {
        if ($this->is_public) {
            return true;
        }
        if (! $viewer) {
            return false;
        }
        if ($viewer->id === $this->supplier_id) {
            return true;
        }

        return $eventOwnerId !== null && $viewer->id === $eventOwnerId;
    }
}
