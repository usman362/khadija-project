<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'long_description',
        'cover_image',
        'thumbnail',
        'icon',
        'parent_id',
        'taxonomy_version',
        'kind',
        'archetype',
        'popularity_tier',
        'cross_fit_alt',
        'insurance_requirement',
        'insurance_type',
        'insurance_tier',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** The three kinds of row in the v2 tree. */
    public const EVENT_TYPE = 'event_type';
    public const SERVICE_CATEGORY = 'service_category';
    public const SERVICE = 'service';

    /**
     * Two category trees live in this table at once — the original one and Sir
     * Peter's V2 rebuild — so that V2 can be imported and checked before
     * anything switches over.
     *
     * This is a global scope rather than a query scope on purpose. Of the
     * roughly fifty places that query categories, a third never call
     * ->active(); relying on those being updated is how the other tree would
     * leak onto a live page.
     *
     * Use Category::anyTaxonomy() to deliberately see across both — the import
     * and switch commands do, nothing else should.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('taxonomy', function ($query) {
            $query->where('categories.taxonomy_version', config('taxonomy.version', 'v1'));
        });

        static::creating(function (self $category) {
            $category->taxonomy_version ??= config('taxonomy.version', 'v1');
        });
    }

    /** Escape hatch: query both trees. */
    public function scopeAnyTaxonomy($query)
    {
        return $query->withoutGlobalScope('taxonomy');
    }

    /** Rows of one v2 kind: event types, service categories, or services. */
    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    /**
     * The things a client can actually ask a professional to do — the ONE
     * source for every service picker on every request flow.
     *
     * Checklist row 91. Three flows each had their own idea of what a service
     * was: the emergency form filtered nothing at all, so "Baby Shower",
     * "Birthday Party" and "Award Ceremony" sat in the list as though a client
     * could book one; the direct offer filtered on kind; and the broadcast
     * form filtered on parent_id, a first-taxonomy idiom. Three answers, three
     * different catalogues, and one of them let an event type be submitted as
     * the service requested.
     *
     * Written to hold under both taxonomies, because the version is a config
     * switch with a rollback: under v2 a service is a row of kind `service`,
     * and under v1 it is any row with a parent.
     */
    public function scopeBookableServices($query)
    {
        return config('taxonomy.version', 'v1') === 'v2'
            ? $query->ofKind(self::SERVICE)
            : $query->whereNotNull('parent_id');
    }

    /** The counterpart: occasions, never bookable on their own. */
    public function scopeEventTypes($query)
    {
        return config('taxonomy.version', 'v1') === 'v2'
            ? $query->ofKind(self::EVENT_TYPE)
            : $query->whereNull('parent_id');
    }

    /** How relevant each service category is to each archetype. */
    public function relevance(): HasMany
    {
        return $this->hasMany(CategoryRelevance::class);
    }

    // Parent category
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Subcategories
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    // Professional packages filed under this category
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    // Events in this category (pivot)
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    // Professionals who list this category as a service they offer.
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    // Scope: only active
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: only parents (no parent_id)
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Recursive children (unlimited depth) — alphabetical, matching the legacy admin tree.
    public function allChildren(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('allChildren')->orderBy('name');
    }

    // Get full path name (e.g. "Weddings > Photography")
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    /**
     * Build a flat list of all categories with indentation for dropdowns.
     * Returns: [['id' => 1, 'name' => 'Wedding', 'indent' => ''], ['id' => 5, 'name' => 'Photography', 'indent' => '── '], ...]
     */
    public static function getNestedDropdownList(?int $excludeId = null): array
    {
        $roots = self::whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $result = [];
        self::flattenTree($roots, $result, 0, $excludeId);
        return $result;
    }

    private static function flattenTree($categories, array &$result, int $depth, ?int $excludeId): void
    {
        $prefix = str_repeat('── ', $depth);
        foreach ($categories as $cat) {
            if ($excludeId && $cat->id === $excludeId) {
                continue;
            }
            $result[] = [
                'id' => $cat->id,
                'name' => $prefix . $cat->name,
                'raw_name' => $cat->name,
                'depth' => $depth,
            ];
            if ($cat->allChildren && $cat->allChildren->count()) {
                self::flattenTree($cat->allChildren, $result, $depth + 1, $excludeId);
            }
        }
    }

    /*
     * ── Category artwork ──────────────────────────────────────────────
     *
     * A card picture, in one place, so the event-type wall, the rail and the
     * landing page cannot disagree about what a category looks like.
     *
     * Real artwork first: 273 pictures came across from the old live site and
     * a migration carried the matching ones onto the v2 tree. Where a category
     * has none — 71 of the 106 event types are occasions the old site never
     * had a page for — a stock photograph stands in, chosen by what the
     * occasion IS.
     *
     * The stand-in is decoration and nothing else. It makes no claim about a
     * professional, a price or a place, which is the line this codebase draws
     * around stock imagery: it may decorate, it may never stand in for a fact.
     * Anything an admin uploads replaces it immediately.
     */
    private const STOCK = [
        // Weddings & the run-up
        'wedding'      => 'photo-1519741497674-611481863552',
        'vow renewal'  => 'photo-1519225421980-715cb0215aed',
        'rehearsal'    => 'photo-1464366400600-7168b8af9bc3',
        'engagement'   => 'photo-1522673607200-164d1b6ce486',
        'bridal'       => 'photo-1519225421980-715cb0215aed',
        'bachelorette' => 'photo-1530103862676-de8c9debad1d',
        'bachelor'     => 'photo-1470229722913-7c0e2dbbafd3',

        // Milestones
        'birthday'     => 'photo-1530103862676-de8c9debad1d',
        'sweet 16'     => 'photo-1530103862676-de8c9debad1d',
        'anniversary'  => 'photo-1464366400600-7168b8af9bc3',
        'baby shower'  => 'photo-1519689680058-324335c77eba',
        'graduation'   => 'photo-1541339907198-e08756dedf3f',
        'prom'         => 'photo-1519225421980-715cb0215aed',
        'retirement'   => 'photo-1511795409834-ef04bbd61622',
        'housewarming' => 'photo-1484154218962-a197022b5858',
        'mitzvah'      => 'photo-1511795409834-ef04bbd61622',
        'coming out'   => 'photo-1533174072545-7a4b6ad7a6c3',
        'funeral'      => 'photo-1508672019048-805c876b67e2',
        'memorial'     => 'photo-1508672019048-805c876b67e2',
        'religious'    => 'photo-1508672019048-805c876b67e2',

        // Corporate
        'conference'   => 'photo-1505373877841-8d25f7d46678',
        'seminar'      => 'photo-1540575467063-178a50c2df87',
        'workshop'     => 'photo-1540575467063-178a50c2df87',
        'product launch' => 'photo-1540575467063-178a50c2df87',
        'corporate'    => 'photo-1505373877841-8d25f7d46678',
        'business'     => 'photo-1505373877841-8d25f7d46678',
        'client appreciation'   => 'photo-1511795409834-ef04bbd61622',
        'customer appreciation' => 'photo-1511795409834-ef04bbd61622',
        'real estate'  => 'photo-1484154218962-a197022b5858',
        'inauguration' => 'photo-1540575467063-178a50c2df87',
        'fraternity'   => 'photo-1541339907198-e08756dedf3f',
        'university'   => 'photo-1541339907198-e08756dedf3f',
        'homecoming'   => 'photo-1541339907198-e08756dedf3f',
        'summer camp'  => 'photo-1476514525535-07fb3b4ae5f1',
        'nursing home' => 'photo-1511795409834-ef04bbd61622',

        // Food, drink & nightlife
        'wine'         => 'photo-1510812431401-41d2bd2722f3',
        'coffee'       => 'photo-1501339847302-ac426a4a7cbb',
        'paint & sip'  => 'photo-1510812431401-41d2bd2722f3',
        'karaoke'      => 'photo-1470229722913-7c0e2dbbafd3',
        'trivia'       => 'photo-1511795409834-ef04bbd61622',
        'club'         => 'photo-1470229722913-7c0e2dbbafd3',
        'night'        => 'photo-1470229722913-7c0e2dbbafd3',

        // Outdoors, sport & public
        'beach'        => 'photo-1476514525535-07fb3b4ae5f1',
        'tailgate'     => 'photo-1471295253337-3ceaaedca402',
        'sporting'     => 'photo-1471295253337-3ceaaedca402',
        'march madness'=> 'photo-1471295253337-3ceaaedca402',
        'derby'        => 'photo-1471295253337-3ceaaedca402',
        'block party'  => 'photo-1533174072545-7a4b6ad7a6c3',
        'fair'         => 'photo-1533174072545-7a4b6ad7a6c3',
        'festival'     => 'photo-1533174072545-7a4b6ad7a6c3',
        'parade'       => 'photo-1533174072545-7a4b6ad7a6c3',
        'earth day'    => 'photo-1476514525535-07fb3b4ae5f1',
        'resort'       => 'photo-1476514525535-07fb3b4ae5f1',
        'country club' => 'photo-1471295253337-3ceaaedca402',
        'farm'         => 'photo-1476514525535-07fb3b4ae5f1',

        // Holidays & cultural
        'christmas'    => 'photo-1512389142860-9c449e58a543',
        'hanukkah'     => 'photo-1512389142860-9c449e58a543',
        'kwanzaa'      => 'photo-1512389142860-9c449e58a543',
        'diwali'       => 'photo-1512389142860-9c449e58a543',
        'new year'     => 'photo-1512389142860-9c449e58a543',
        'year-end'     => 'photo-1512389142860-9c449e58a543',
        'thanksgiving' => 'photo-1511795409834-ef04bbd61622',
        'fourth of july' => 'photo-1533174072545-7a4b6ad7a6c3',
        'juneteenth'   => 'photo-1533174072545-7a4b6ad7a6c3',
        'mardi gras'   => 'photo-1533174072545-7a4b6ad7a6c3',
        'cinco de mayo'=> 'photo-1533174072545-7a4b6ad7a6c3',
        "st. patrick"  => 'photo-1533174072545-7a4b6ad7a6c3',
        'pride'        => 'photo-1533174072545-7a4b6ad7a6c3',
        'lgbtq'        => 'photo-1533174072545-7a4b6ad7a6c3',
        'heritage'     => 'photo-1533174072545-7a4b6ad7a6c3',
        'veterans'     => 'photo-1508672019048-805c876b67e2',
        'election'     => 'photo-1540575467063-178a50c2df87',
        "mother's day" => 'photo-1519225421980-715cb0215aed',
        "father's day" => 'photo-1511795409834-ef04bbd61622',
        'spring fling' => 'photo-1519225421980-715cb0215aed',

        // Giving & the rest
        'fundraiser'   => 'photo-1511795409834-ef04bbd61622',
        'silent auction' => 'photo-1511795409834-ef04bbd61622',
        'charity'      => 'photo-1511795409834-ef04bbd61622',
        'gallery'      => 'photo-1533174072545-7a4b6ad7a6c3',
        'family'       => 'photo-1511795409834-ef04bbd61622',
        'welcome'      => 'photo-1511795409834-ef04bbd61622',
        'divorce'      => 'photo-1530103862676-de8c9debad1d',
        'private'      => 'photo-1511795409834-ef04bbd61622',
    ];

    /** The last resort — a gathering, which every one of these is. */
    private const STOCK_DEFAULT = 'photo-1511795409834-ef04bbd61622';

    /** This category's card picture: its own if it has one, a stand-in if not. */
    public function imageUrl(int $width = 500): ?string
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }

        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }

        return self::stockFor($this->name, $width);
    }

    /** Does this category have artwork of its own, rather than a stand-in? */
    public function hasOwnImage(): bool
    {
        return (bool) ($this->thumbnail ?: $this->cover_image);
    }

    /**
     * The stand-in for a name.
     *
     * Longest key first, so "block party" wins over "party" and
     * "client appreciation" over the word "client" — a shorter key matching
     * first is how "Bachelorette Party" ends up with the bachelor's photograph.
     */
    public static function stockFor(?string $name, int $width = 500): string
    {
        $haystack = strtolower((string) $name);

        $keys = array_keys(self::STOCK);
        usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a));

        $id = self::STOCK_DEFAULT;

        foreach ($keys as $key) {
            if (str_contains($haystack, $key)) {
                $id = self::STOCK[$key];
                break;
            }
        }

        return 'https://images.unsplash.com/' . $id . '?w=' . $width . '&q=70&auto=format&fit=crop';
    }
}
