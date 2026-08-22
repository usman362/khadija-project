<?php

namespace App\Models;

use App\Domain\Geolocation\Geocoder;
use App\Domain\Geolocation\LocationPrecision;
use App\Domain\Geolocation\ZipCentroidTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * Who the request is for — asked on every request form.
     *
     * Here rather than on one controller because the Owner wants it on all of
     * them (2026-08-20, "for data collecting purposes") and four copies of the
     * same four options is four chances for them to drift apart. The column
     * this fills is `events.organization_type`.
     */
    public const ORGANIZATION_TYPES = [
        'individual' => 'Individual',
        'business'   => 'Business',
        'government' => 'Government',
        'nonprofit'  => 'Nonprofit',
    ];

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
        'location_lat',
        'location_lng',
        'location_precision',
        'location_zip',
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

            $textDirty = $event->isDirty(['location', 'venue', 'state']);
            $pointDirty = $event->isDirty(['location_lat', 'location_lng', 'location_precision']);

            if ($textDirty && ! $pointDirty) {
                $event->applyLocationGeocode();
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
            'location_lat' => 'float',
            'location_lng' => 'float',
        ];
    }

    /**
     * Place the request. Free-text location plus the structured state.
     * No coordinates unless precision is exact or zip.
     */
    public function applyLocationGeocode(): void
    {
        $text = trim(implode(', ', array_filter([
            trim((string) $this->venue) ?: null,
            trim((string) $this->location) ?: null,
        ])));

        if ($text === '' && ! ZipCentroidTable::normalize((string) $this->location_zip)) {
            $this->location_lat = null;
            $this->location_lng = null;
            $this->location_precision = LocationPrecision::UNRESOLVED;
            $this->location_zip = null;

            return;
        }

        $placed = app(Geocoder::class)->fromFreeText(
            $text !== '' ? $text : $this->location_zip,
            $this->state,
        );

        $this->location_lat = $placed->lat;
        $this->location_lng = $placed->lng;
        $this->location_precision = $placed->precision;
        $this->location_zip = $placed->zip;
    }

    public function locationPlacementFailed(): bool
    {
        $hasText = trim((string) $this->location) !== '' || trim((string) $this->venue) !== '';

        return $hasText && $this->location_precision === LocationPrecision::UNRESOLVED;
    }

    public function locationIsApproximate(): bool
    {
        return $this->location_precision === LocationPrecision::ZIP;
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

    /**
     * Events still open to bids (A10) — service-aware, not supplier-aware.
     *
     * An event is biddable while a service it asked for has no confirmed
     * booking. This replaces the old "supplier_id IS NULL" test, which could
     * not tell a half-awarded request (still open) from one fully awarded to
     * two different pros (closed, but nameable by no single supplier_id).
     */
    public function scopeOpenForBids($query)
    {
        return $query->where('is_published', true)->where(function ($e) {
            // A requested service with no confirmed/completed booking of its own.
            $e->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('category_event as ce')
                    ->whereColumn('ce.event_id', 'events.id')
                    ->whereNotExists(function ($b) {
                        $b->selectRaw('1')->from('bookings')
                          ->whereColumn('bookings.event_id', 'events.id')
                          ->whereColumn('bookings.category_id', 'ce.category_id')
                          ->whereIn('bookings.status', ['confirmed', 'completed']);
                    });
            })
            // Or a whole-event request (no named service) with nothing confirmed.
            ->orWhere(function ($whole) {
                $whole->whereNotExists(function ($c) {
                        $c->selectRaw('1')->from('category_event as ce2')
                          ->whereColumn('ce2.event_id', 'events.id');
                    })
                    ->whereNotExists(function ($b) {
                        $b->selectRaw('1')->from('bookings')
                          ->whereColumn('bookings.event_id', 'events.id')
                          ->whereIn('bookings.status', ['confirmed', 'completed']);
                    });
            });
        });
    }

    /**
     * The services this request asked for. Empty for a whole-event (SSR)
     * request that named no specific service.
     */
    public function requestedCategoryIds(): array
    {
        return $this->categories->pluck('id')->all()
            ?: $this->categories()->pluck('categories.id')->all();
    }

    /** The services already awarded — a confirmed or completed booking names them. */
    public function awardedCategoryIds(): array
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id')
            ->all();
    }

    /**
     * Is every service on this request now awarded? (B6/A10)
     *
     * A whole-event request with no named service is fully awarded the moment
     * it has any confirmed booking. A multi-service request is fully awarded
     * only when each service it asked for has one -- which is what keeps a
     * half-awarded request on the bidding board for the services still open.
     */
    public function isFullyAwarded(): bool
    {
        $requested = $this->requestedCategoryIds();

        if (empty($requested)) {
            return $this->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->exists();
        }

        return empty(array_diff($requested, $this->awardedCategoryIds()));
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
