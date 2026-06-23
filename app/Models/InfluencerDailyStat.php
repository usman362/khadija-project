<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerDailyStat extends Model
{
    protected $fillable = [
        'influencer_id', 'date', 'clicks', 'conversions', 'views', 'engagements', 'earnings',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clicks' => 'integer', 'conversions' => 'integer',
            'views' => 'integer', 'engagements' => 'integer', 'earnings' => 'decimal:2',
        ];
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class);
    }
}
