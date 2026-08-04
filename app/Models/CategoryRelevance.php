<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How strongly one service category applies to one kind of event.
 *
 * A client picks an Event Type; that resolves to one of 13 archetypes; this
 * says which service categories to put in front of them and in what order —
 * Essential first, then Common, then Occasional.
 */
class CategoryRelevance extends Model
{
    protected $table = 'category_relevance';

    protected $fillable = ['archetype', 'category_id', 'tier', 'signature_services'];

    /** Sorting weight, so Essential lands above Common above Occasional. */
    private const ORDER = ['Essential' => 0, 'Common' => 1, 'Occasional' => 2];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeForArchetype($query, string $archetype)
    {
        return $query->where('archetype', $archetype);
    }

    /** Essential → Common → Occasional, then alphabetical inside each. */
    public function scopeRanked($query)
    {
        $cases = collect(self::ORDER)
            ->map(fn ($weight, $tier) => "when '{$tier}' then {$weight}")
            ->implode(' ');

        return $query->orderByRaw("case tier {$cases} else 9 end");
    }

    /** The handful worth showing first, as a list rather than one string. */
    public function getSignatureListAttribute(): array
    {
        if (! $this->signature_services) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            explode(',', $this->signature_services),
        )));
    }
}
