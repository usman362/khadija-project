<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A public review tied to a completed booking. Rating is 1..5.
 *
 * Conventions:
 *  - reviewer_id → user who wrote the review
 *  - reviewee_id → user being reviewed
 *  - booking_id  → the completed booking this review is about
 *  - response    → optional public reply from reviewee (Yelp-style)
 *  - is_hidden   → moderation flag; visible scope below filters these out
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'booking_id',
        'rating',
        'title',
        'comment',
        'response',
        'response_at',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'rating'      => 'integer',
            'response_at' => 'datetime',
            'is_hidden'   => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ── Scopes ─────────────────────────────────────────────────
    /** Only reviews that should be shown publicly. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_hidden', false);
    }

    /** Reviews about a given user. */
    public function scopeAbout(Builder $q, int $userId): Builder
    {
        return $q->where('reviewee_id', $userId);
    }
}
