<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * One editable block of a public page. See the migration for the shape and
 * config/page-sections.php for what each section's payload holds.
 */
class PageSection extends Model
{
    protected $fillable = [
        'page', 'key', 'heading', 'subheading', 'body',
        'image_path', 'payload', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'payload'   => 'array',
            'is_active' => 'boolean',
        ];
    }

    private const CACHE_KEY = 'page_sections.all';

    /**
     * Every section, keyed "page.key". Cached because the landing page asks for
     * a dozen of them on a request that is otherwise almost query-free.
     *
     * Deliberately not named all()/find() — those are Eloquent's own and
     * overriding them would break ordinary model use in the admin.
     */
    public static function cached(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->get()
            ->keyBy(fn ($s) => $s->page . '.' . $s->key));
    }

    /** One active section by "page.key", or null. */
    public static function block(string $pageDotKey): ?self
    {
        $s = static::cached()->get($pageDotKey);

        return $s && $s->is_active ? $s : null;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    /**
     * Image as a URL. An uploaded file lives on the public disk; a value that is
     * already a URL (the stock photography the page shipped with) passes
     * through untouched.
     */
    public function imageUrl(?string $fallback = null): ?string
    {
        $path = $this->image_path;

        if (! $path) {
            return $fallback;
        }

        return str_starts_with($path, 'http') ? $path : Storage::url($path);
    }

    /** A payload key, with a fallback for when the row predates the field. */
    public function item(string $key, mixed $fallback = null): mixed
    {
        return data_get($this->payload, $key, $fallback);
    }
}
