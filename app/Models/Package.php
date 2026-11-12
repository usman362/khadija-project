<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Package extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'services', 'event_types',
        'title', 'purpose', 'slug', 'type', 'description', 'price', 'price_unit', 'tiers', 'duration',
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
        'tiers'      => 'array',
        'is_active'  => 'boolean',
        'price'      => 'integer',
        'guests_max' => 'integer',
        'savings_pct' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * The tiers a client chooses between, cheapest first.
     *
     * Always sorted here rather than trusting the order they were typed in:
     * a list of levels that does not climb reads as a mistake, and the first
     * one is the figure the card shows.
     *
     * @return array<int, array{name: string, price: int, blurb: ?string}>
     */
    public function tierList(): array
    {
        $tiers = collect($this->tiers ?: [])
            ->filter(fn ($t) => is_array($t) && trim((string) ($t['name'] ?? '')) !== '' && (int) ($t['price'] ?? 0) > 0)
            ->map(fn ($t) => [
                'name'  => trim((string) $t['name']),
                'price' => (int) $t['price'],
                'blurb' => trim((string) ($t['blurb'] ?? '')) ?: null,
            ])
            ->sortBy('price')
            ->values()
            ->all();

        return $tiers;
    }

    /**
     * The crew this package brings.
     *
     * `team` has been written three different ways over the life of this
     * column, and all three are in the database:
     *
     *   an INTEGER, a bare count, which is what the My Packages design asks
     *   for and what its test writes;
     *   an ARRAY of role names, which the demo seeder writes and the booking
     *   page prints;
     *   a comma-separated STRING, from older rows.
     *
     * Every view that touched it worked out its own answer, and two of them
     * got it wrong in opposite directions: the booking form fixed the array
     * case inline and My Packages returned a 500 on it. Three readings of one
     * column, so the reading lives here and a view asks for what it wants.
     *
     * The named roles, which a bare count does not have.
     *
     * @return array<int, string>
     */
    public function teamList(): array
    {
        $team = $this->team;

        // A number is a count, not a name. It has no roles to list.
        if (is_int($team) || (is_string($team) && is_numeric(trim($team)))) {
            return [];
        }

        if (is_string($team)) {
            $team = explode(',', $team);
        }

        return array_values(array_filter(array_map(
            fn ($m) => trim((string) $m),
            (array) $team,
        )));
    }

    /** How many of them, whichever way the column was written. */
    public function teamSize(): int
    {
        $team = $this->team;

        if (is_int($team) || (is_string($team) && is_numeric(trim($team)))) {
            return max(0, (int) $team);
        }

        // An array holding one number is a count that has been through a
        // json cast, not a crew of one.
        $list = $this->teamList();

        if (count($list) === 1 && is_numeric($list[0])) {
            return max(0, (int) $list[0]);
        }

        return count($list);
    }

    /**
     * The line to print, for a page with room for one.
     *
     * Roles where we have them, and the count where we only have a number,
     * rather than printing a bare "2" under a heading that says Team.
     */
    public function teamLine(): ?string
    {
        $list = $this->teamList();

        if ($list !== []) {
            return implode(', ', $list);
        }

        $size = $this->teamSize();

        return $size > 0
            ? $size . ' ' . \Illuminate\Support\Str::plural('Professional', $size)
            : null;
    }

    /** Does this package offer a choice, or one price? */
    public function hasTiers(): bool
    {
        return count($this->tierList()) > 1;
    }

    /**
     * One tier by name, for a client who has picked one.
     *
     * By name and not by position, because a professional can reorder or
     * reprice their tiers between a client opening the page and submitting
     * the form, and a position would then buy them something else.
     */
    public function tierNamed(?string $name): ?array
    {
        if ($name === null || $name === '') {
            return null;
        }

        foreach ($this->tierList() as $tier) {
            if (mb_strtolower($tier['name']) === mb_strtolower(trim($name))) {
                return $tier;
            }
        }

        return null;
    }

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
