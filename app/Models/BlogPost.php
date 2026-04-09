<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    protected $fillable = [
        'blog_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count'  => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post) {
            if (empty($post->slug)) {
                $post->slug = static::uniqueSlug($post->title, $post->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i    = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    // ── Relationships ──────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    // ── Scopes ─────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('blog_category_id', $categoryId);
    }

    // ── Helpers ────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_ARCHIVED  => 'Archived',
            default                => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_DRAFT     => 'secondary',
            self::STATUS_ARCHIVED  => 'dark',
            default                => 'light',
        };
    }

    public function featuredImageUrl(): string
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        return 'https://placehold.co/800x450/111827/6366f1?text=' . urlencode($this->title);
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return max(1, (int) ceil($words / 220));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
