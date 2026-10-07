<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\ShowsTimesWhereTheReaderIs;

class BidReply extends Model
{
    use ShowsTimesWhereTheReaderIs;

    protected $fillable = [
        'bid_id', 'user_id', 'counter_amount', 'note',
    ];

    protected $casts = [
        'counter_amount' => 'integer',
    ];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
