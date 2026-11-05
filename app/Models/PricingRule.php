<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One professional's pricing rules: their floor, their surcharges, what they
 * charge to travel, and whether Quick Bid may answer for them.
 *
 * Every field is nullable on purpose. A rule nobody has set is not a rule of
 * zero, and the difference matters: a minimum booking of null means they have
 * not named one, while a minimum of 0 would mean they will work for nothing.
 */
class PricingRule extends Model
{
    protected $table = 'professional_pricing_rules';

    protected $fillable = [
        'user_id',
        'minimum_booking',
        'weekend_surcharge_pct',
        'rush_surcharge_pct',
        'travel_free_miles',
        'travel_rate_per_mile',
        'travel_max_miles',
        'quick_bid_enabled',
        'quick_bid_terms',
        'monthly_revenue_goal',
    ];

    protected function casts(): array
    {
        return [
            'minimum_booking'       => 'decimal:2',
            'travel_rate_per_mile'  => 'decimal:2',
            'quick_bid_enabled'     => 'boolean',
            'monthly_revenue_goal'  => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Has this professional actually set anything? */
    public function isSet(): bool
    {
        return $this->minimum_booking !== null
            || $this->weekend_surcharge_pct !== null
            || $this->rush_surcharge_pct !== null
            || $this->travel_free_miles !== null
            || $this->travel_rate_per_mile !== null
            || $this->quick_bid_enabled;
    }
}
