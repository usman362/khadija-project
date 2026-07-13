<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Package extends Model
{
    protected $fillable = [
        'user_id', 'coop_partner_id', 'category_id', 'services', 'title', 'slug',
        'type', 'description', 'price', 'price_unit', 'duration', 'coverage',
        'team', 'guests', 'serves_regions', 'availability', 'savings_pct',
        'includes', 'images', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'services'   => 'array',
        'team'       => 'array',
        'includes'   => 'array',
        'images'     => 'array',
        'is_active'  => 'boolean',
        'price'      => 'integer',
        'savings_pct' => 'integer',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The second professional on a co-op package (null for solo). */
    public function coopPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coop_partner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** True when the package bundles two or more services. */
    public function isMultiService(): bool
    {
        return count($this->services ?? []) >= 2;
    }

    /** Whether this is a co-op partnership (two+ pros) vs solo multi-pro. */
    public function isCoop(): bool
    {
        return $this->type === 'co-op';
    }

    /** Number of gallery photos (for the "N Photos" badge). */
    public function photosCount(): int
    {
        return count($this->images ?? []);
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
