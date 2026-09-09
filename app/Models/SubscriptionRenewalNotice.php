<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One notice, sent once, about one renewal.
 *
 * Kept because Washington DC's Automatic Renewal Protections Act requires the
 * notice, and a requirement you cannot show you met is one you may as well not
 * have met.
 */
class SubscriptionRenewalNotice extends Model
{
    protected $fillable = ['user_subscription_id', 'renews_on', 'days_before', 'sent_at', 'sent_to'];

    protected function casts(): array
    {
        return ['renews_on' => 'date', 'sent_at' => 'datetime', 'days_before' => 'integer'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }
}
