<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A filter set a client asked us to keep. See the migration for why the row
 * holds the values rather than a URL.
 */
class SavedSearch extends Model
{
    protected $fillable = ['user_id', 'surface', 'label', 'params'];

    protected $casts = ['params' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSurface($query, string $surface)
    {
        return $query->where('surface', $surface);
    }

    /**
     * The query string that re-runs this search. Empty values are dropped so a
     * saved search of "Photography only" does not carry five blank filters.
     */
    public function queryParams(): array
    {
        return array_filter($this->params ?? [], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
