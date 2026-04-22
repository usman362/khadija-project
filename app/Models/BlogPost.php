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

        // Nice-looking Unsplash fallbacks rotated deterministically by post id,
        // so each post consistently shows the same default image.
        $fallbacks = [
            'photo-1492684223066-81342ee5ff30', // celebration
            'photo-1519741497674-611481863552', // wedding
            'photo-1511578314322-379afb476865', // corporate
            'photo-1429962714451-bb934ecdc4ec', // concert
            'photo-1464366400600-7168b8af9bc3', // graduation
            'photo-1511795409834-ef04bbd61622', // floral
            'photo-1540575467063-178a50c2df87', // corporate handshake
            'photo-1530103862676-de8c9debad1d', // party
            'photo-1540317580384-e5d43616b9aa', // catering
            'photo-1519225421980-715cb0215aed', // dance floor
        ];

        $index = ($this->id ?? crc32($this->title ?? 'post')) % count($fallbacks);
        $photo = $fallbacks[$index];

        return "https://images.unsplash.com/{$photo}?w=800&q=80&auto=format&fit=crop";
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
