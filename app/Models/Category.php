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

    /**
     * The four kinds of row in the v2 tree.
     *
     * Level 1 event type → level 2 service category → level 3 service → level 4
     * SERVICE SPECIALTY. A specialty is a narrower way of doing the level 3
     * service — level 3 "DJ" carries "Wedding DJ", "Party DJ", "Corporate DJ",
     * "Karaoke DJ". Only where the specialization is meaningful: a level 3
     * service is not required to have any (Peter, 2026-08-29).
     *
     * These were briefly called "components", and before that "keywords". The
     * keyword name is the one that caused trouble, because it read as the paid
     * Search Visibility feature and invited a second list of the same terms.
     * There is ONE list. A specialty is stored here once; paid Search
     * Visibility REFERENCES eligible specialties, it does not copy them.
     *
     *   Level 4 Service Specialty  = taxonomy and matching
     *   Search Visibility Keywords = the paid ranking feature, which points at
     *                                eligible specialty rows
     *
     * Nothing carries SERVICE_SPECIALTY yet: level 3 is being finalised first,
     * then the specialties are built underneath it. The importer accepts them
     * the moment the sheet has them.
     */
    public const EVENT_TYPE = 'event_type';
    public const SERVICE_CATEGORY = 'service_category';
    public const SERVICE = 'service';
    public const SERVICE_SPECIALTY = 'service_specialty';

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
    /**
     * A URL-safe slug for a category name, unique within its taxonomy version.
     *
     * Deliberately NOT random. The admin screen used to build every slug as
     * "name-" . Str::random(4) -- on create AND on update -- so a category's
     * public URL was gibberish from birth and changed again every time anyone
     * edited it. That silently broke /category/{slug} for every link, bookmark
     * and search result pointing at it.
     *
     * A suffix is added only on a real collision, and then it counts (-2, -3)
     * so the URL still reads as the thing it names.
     */
    public static function makeSlug(string $name, ?int $ignoreId = null, ?string $version = null): string
    {
        $version = $version ?: config('taxonomy.version', 'v1');
        $base    = \Illuminate\Support\Str::slug($name) ?: 'category';
        $slug    = $base;
        $n       = 1;

        while (static::withoutGlobalScopes()
            ->where('taxonomy_version', $version)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$n);
        }

        return $slug;
    }

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
     * One method decides a category's picture, so the event-type wall, the
     * rail and the landing page cannot disagree about what a category looks
     * like. It reads the two columns that hold artwork; the controller used to
     * read only `thumbnail` inline and miss `cover_image`.
     *
     * It returns NULL when a category has none, and that is deliberate. There
     * was a stock stand-in here briefly on 2026-08-20; the Owner is uploading
     * the real pictures himself, and a stock photograph in the meantime is a
     * placeholder somebody has to remember to take out — the kind that stops
     * looking like a placeholder and starts looking like a decision. The card
     * draws its tinted tile instead, which reads as deliberate and fills in on
     * its own the moment a picture is uploaded.
     */
    public function imageUrl(): ?string
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }

        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }

        return null;
    }

    /** Does this category have artwork? */
    public function hasOwnImage(): bool
    {
        return (bool) ($this->thumbnail ?: $this->cover_image);
    }
}
