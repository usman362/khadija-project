<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerCampaign extends Model
{
    protected $fillable = [
        'influencer_id', 'name', 'status', 'channel',
        'clicks', 'conversions', 'earnings', 'started_at', 'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'clicks' => 'integer', 'conversions' => 'integer', 'earnings' => 'decimal:2',
            'started_at' => 'date', 'ended_at' => 'date',
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }

    public function conversionRate(): float
    {
        return $this->clicks > 0 ? round($this->conversions / $this->clicks * 100, 1) : 0.0;
    }
}
