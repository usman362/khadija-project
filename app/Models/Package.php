<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Package extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'type', 'description',
        'price', 'price_unit', 'duration', 'includes', 'images',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'includes'  => 'array',
        'images'    => 'array',
        'is_active' => 'boolean',
        'price'     => 'integer',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Hero-size image URLs (for cards + carousel), or empty. */
    public function heroUrls(int $limit = 4): array
    {
        return collect(is_array($this->images) ? $this->images : [])
            ->map(fn ($i) => Storage::url($i['hero'] ?? $i['square'] ?? ''))
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    /** Human price label, e.g. "from $1,400" / "$2,125" / "$150/hr". */
    public function priceLabel(): string
    {
        $amount = '$' . number_format($this->price);

        return match ($this->price_unit) {
            'from'   => 'from ' . $amount,
            'hourly' => $amount . '/hr',
            default  => $amount,
        };
    }
}
