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
        // BSR wizard fields
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
    ];

    /**
     * Rule R38 — every request carries the registered state of the account
     * that raised it.
     *
     * Stamped here rather than in each of the five controllers that create an
     * Event (post-event wizard, direct offer, ESR, the prototype bridge, the
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
        ];
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
