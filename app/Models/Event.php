<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'budget',
        'location',
        'venue',
        'guest_count',
        'media',
        'status',
        'is_published',
        'published_at',
        'starts_at',
        'ends_at',
        'created_by',
        'client_id',
        'supplier_id',
        'source',
        'category_id',
        // BR wizard fields
        'event_type',
        'organization_type',
        'characteristic',
        'budget_min',
        'budget_max',
        'proposal_deadline',
        'sealed_proposals',
        'questions_enabled',
        // Rule R38 — the state this request belongs to. Stamped from the
        // client's account on create; see booted().
        'state',
        // Rule R60 — may the booked professional see this event's guest list?
        // Per event, the client's choice, private until they say otherwise.
        'share_attendees',
        // Rule R33 — the client's own decision to close, and the last time
        // the listing was reactivated. Expiry itself is derived, never stored.
        'closed_at',
        'reopened_at',
    ];

    /**
     * Rule R38 — every request carries the registered state of the account
     * that raised it.
     *
     * Stamped here rather than in each of the five controllers that create an
     * Event (post-event wizard, direct offer, ER, the prototype bridge, the
     * seeders). A request that reached the board with no state would be
     * invisible to everyone, since NULL matches nobody — so the one place
     * that cannot be forgotten is the model itself.
     */
    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if ($event->state !== null) {
                return;
            }

            $owner = $event->client_id ?? $event->created_by;
            $event->state = $owner
                ? \App\Support\StateMatching::stateOf(User::find($owner))
                : null;
        });

        /*
         * Rule R33 §6 and §3 — the notices.
         *
         * Here rather than in the controllers for the same reason the state
         * stamp above is: five places create or edit an Event, and a rule
         * that has to be remembered in five places is a rule that will be
         * missed in one. §6 allows exactly two notice types and forbids
         * repeating the first-publication blast on a reactivation, so the
         * decision between them belongs somewhere it cannot be re-made
         * inconsistently.
         */
        /*
         * Checklist row 89 — the publish stamp.
         *
         * "Posted" is when the request went out to professionals, and half the
         * publish paths set `published_at` while the other half only flipped
         * `is_published`. Pages then fell back to `created_at`, which is when
         * the ROW was made — a draft written a fortnight before it was posted
         * claims to have been posted a fortnight ago, and a row backfilled by
         * an import claims to have been posted after bids it already holds.
         *
         * Stamped here, in the one place every path goes through, for the same
         * reason the state stamp above is.
         */
        static::saving(function (self $event) {
            if ($event->is_published && $event->published_at === null) {
                $event->published_at = now();
            }
        });

        static::updated(function (self $event) {
            $changes = $event->getChanges();

            // First publication only — never again for this listing.
            if (($changes['is_published'] ?? null) && $event->reopened_at === null) {
                \App\Domain\Requests\EventNotifier::published($event);
            }

            // §3 — a material change is worth re-reading a proposal over. A
            // typo in the title is not, and firing at eight professionals
            // over one is how a notification channel stops being read.
            $major = \App\Domain\Requests\RequestLifecycle::majorChanges($changes);

            if ($major !== []) {
                \App\Domain\Requests\EventNotifier::changed($event, $major);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'budget' => 'decimal:2',
            'guest_count' => 'integer',
            'media' => 'array',
            'proposal_deadline' => 'datetime',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'sealed_proposals' => 'boolean',
            'questions_enabled' => 'boolean',
            'share_attendees' => 'boolean',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    /* ── Rule R33 ───────────────────────────────────────────── */

    public function extensions(): HasMany
    {
        return $this->hasMany(EventExtension::class);
    }

    /**
     * When this request went out to professionals — the one answer every
     * screen showing a "Posted" date should use.
     *
     * `created_at` is when the row was made, which for a draft is not the same
     * day and for an imported row is not the same event. Null until it is
     * actually published: an unposted request has no posted date, and saying
     * "posted 3 weeks ago" about a draft is worse than saying nothing.
     */
    public function postedAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->is_published && $this->published_at === null) {
            return null;
        }

        return $this->published_at ?? $this->created_at;
    }

    /** What a client or professional is told this listing's status is. */
    public function lifecycleStatus(): string
    {
        return \App\Domain\Requests\RequestLifecycle::statusFor($this);
    }

    public function lifecycleLabel(): string
    {
        return \App\Domain\Requests\RequestLifecycle::label($this);
    }

    /** Rule R60 — this event's guest list. Never queried across events. */
    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    /** @deprecated Use categories() instead */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * The bids placed on this request.
     *
     * Bid has always pointed at Event; the reverse was never declared, so
     * anything asking "did this gig get a bid" had to go the long way round.
     * Reporting asks exactly that, twice.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** AI-tool results the client saved onto this event ("Add to my event"). */
    public function aiArtifacts(): HasMany
    {
        return $this->hasMany(EventAiArtifact::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
