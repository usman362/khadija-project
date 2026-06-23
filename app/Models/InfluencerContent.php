<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerContent extends Model
{
    protected $table = 'influencer_content';

    protected $fillable = [
        'influencer_id', 'title', 'platform', 'type',
        'views', 'clicks', 'conversions', 'engagement_rate', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'views' => 'integer', 'clicks' => 'integer', 'conversions' => 'integer',
            'engagement_rate' => 'decimal:2', 'published_at' => 'datetime',
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }
}
