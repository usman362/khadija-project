<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Package extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'services', 'event_types',
        'title', 'slug', 'type', 'description', 'price', 'price_unit', 'duration',
        'coverage', 'team', 'guests', 'guests_max', 'serves_regions', 'availability', 'savings_pct',
        'includes', 'images', 'is_active', 'status', 'sort_order',
        // Rule R38, Option B — a package is bookable only in its owner's own
        // state. Stamped from the professional's account on create.
        'state',
    ];

    /** Canonical package lifecycle (Q11). Only ACTIVE is publicly browsable. */
    public const STATUSES = ['draft', 'active', 'paused', 'archived'];

    protected static function booted(): void
    {
        static::creating(function (self $package) {
            if ($package->state === null && $package->user_id) {
                $package->state = \App\Support\StateMatching::stateOf(User::find($package->user_id));
            }
        });

        // The guest-count filter compares numbers; `guests` is prose. Kept in
        // step here so no form has to remember to do it.
        static::saving(function (self $package) {
            if ($package->isDirty('guests')) {
                $package->guests_max = self::parseGuests($package->guests);
            }
        });
    }

    /**
     * The capacity inside "Up to 150", "150 guests", "80–200 seated".
     *
     * The LARGEST number wins, because the column answers "how many people can
     * this package serve" — on a range, that is the top of the range.
     */
    public static function parseGuests(?string $text): ?int
    {
        if (! preg_match_all('/\d+/', (string) $text, $m) || $m[0] === []) {
            return null;
        }

        return max(array_map('intval', $m[0])) ?: null;
    }

    protected $casts = [
        'services'   => 'array',
        'event_types' => 'array',
        'team'       => 'array',
        'includes'   => 'array',
        'images'     => 'array',
        'is_active'  => 'boolean',
        'price'      => 'integer',
        'guests_max' => 'integer',
        'savings_pct' => 'integer',
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

    /** Publicly browsable = the 'active' state (kept in step with is_active). */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Human label for the status badge. */
    public function statusLabel(): string
    {
        return ucfirst($this->status ?? ($this->is_active ? 'active' : 'draft'));
    }

    /** Packages matching a browse-by-occasion label (title/category/services). */
    public function scopeForOccasion($query, string $label)
    {
        return \App\Support\Occasions::apply($query, $label);
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

    /** A category-appropriate stock hero for packages with no uploaded photos. */
    public function fallbackHeroUrl(int $w = 640): string
    {
        $name = strtolower((string) ($this->category?->name ?? ''));
        $map = [
            'photo' => 'photo-1519741497674-611481863552', 'video' => 'photo-1485846234645-a62644f84728',
            'cater' => 'photo-1555244162-803834f70033',    'food'  => 'photo-1555244162-803834f70033',
            'dj'    => 'photo-1470229722913-7c0e2dbbafd3',  'music' => 'photo-1470229722913-7c0e2dbbafd3',
            'floral'=> 'photo-1519225421980-715cb0215aed',  'decor' => 'photo-1478146896981-b80fe463b330',
            'plan'  => 'photo-1511578314322-379afb476865',  'light' => 'photo-1492684223066-81342ee5ff30',
            'venue' => 'photo-1464366400600-7168b8af9bc3',
        ];
        $id = 'photo-1519741497674-611481863552';
        foreach ($map as $kw => $pid) {
            if (str_contains($name, $kw)) { $id = $pid; break; }
        }

        return 'https://images.unsplash.com/' . $id . '?w=' . $w . '&q=70&auto=format&fit=crop';
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
