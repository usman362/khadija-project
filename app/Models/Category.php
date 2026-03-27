<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

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

    // Events in this category
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
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

    // Recursive children (unlimited depth)
    public function allChildren(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('allChildren')->orderBy('sort_order')->orderBy('name');
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
}
