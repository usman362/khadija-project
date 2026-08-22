<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'category_id',
        'client_id',
        'supplier_id',
        'created_by',
        'status',
        'notes',
        'price',
        'currency',
        'booked_at',
        'source',
    ];

    // ── Status state machine ──────────────────────────────────
    // Defines the *only* allowed transitions between statuses. Bookings
    // move forward through a small graph; skipping states (e.g. jumping
    // `requested` → `completed`) is an integrity bug, so we reject it.
    //
    //    requested  ──▶ confirmed ──▶ completed
    //        │              │
    //        └──────────────┴──▶ cancelled    (terminal)
    public const TRANSITIONS = [
        'requested' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],   // terminal
        'cancelled' => [],   // terminal
    ];

    // Which actor is allowed to drive each individual transition.
    // Key format: "from->to". Actors: 'client' | 'supplier' | 'admin'.
    // Admin always bypasses (checked separately in canActorTransition).
    public const TRANSITION_ACTORS = [
        'requested->confirmed' => ['client'],              // only the client accepts the proposal
        'requested->cancelled' => ['client', 'supplier'],  // either side can walk away pre-accept
        'confirmed->completed' => ['supplier'],            // only the pro marks work delivered
        'confirmed->cancelled' => ['client', 'supplier'],  // either side can cancel mid-flight
    ];

    /**
     * A confirmed booking is what makes a professional the one doing the job,
     * so the event records it too.
     *
     * The event carries its own supplier_id, and three separate places used to
     * be responsible for keeping it in step — accepting a bid, sending a
     * proposal, finalizing. Accepting a bid never did, which is how the same
     * professional showed 3 jobs on Contracts and Gig Operations Hub but 1 on
     * My Gigs: those pages count bookings, My Gigs counts events.
     *
     * The count was the visible half. The other half was worse — the bidding
     * board decides what is still open by looking for events with no supplier,
     * so an awarded job stayed on the board and other professionals kept
     * bidding on work that was already taken.
     *
     * Only confirmed and completed bookings claim the event. A `requested`
     * booking is a proposal nobody has accepted; if that took the job off the
     * board, sending a proposal would lock everyone else out.
     */
    protected static function booted(): void
    {
        static::saved(function (self $booking) {
            if (! in_array($booking->status, ['confirmed', 'completed'], true)) {
                return;
            }

            $event = $booking->event;

            if ($event === null || $event->supplier_id !== null) {
                return;
            }

            // Stamp the event only once it is FULLY awarded (B6/A10). A
            // multi-service request keeps every service on the board until the
            // last one is taken -- stamping on the first award is exactly what
            // used to hide a still-open service, now that awards are per
            // service. A whole-event request is full on its first award, so
            // single-service behaviour is unchanged.
            if (! $event->isFullyAwarded()) {
                return;
            }

            // supplier_id is one column and cannot name two winners. When
            // different professionals took different services, it stays null --
            // the event is off the board because it is fully awarded, tracked
            // by its bookings, not by a single supplier. It is stamped only in
            // the common case where every service went to the same pro, which
            // is what My Gigs still counts by.
            $suppliers = $event->bookings()
                ->whereIn('status', ['confirmed', 'completed'])
                ->distinct()
                ->pluck('supplier_id');

            if ($suppliers->count() === 1) {
                $event->forceFill(['supplier_id' => $suppliers->first()])->saveQuietly();
            }
        });
    }

    /** Is moving from the current status to $to allowed by the graph? */
    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Is $actor authorised to drive the transition current → $to on this
     * booking? Returns false on any of: invalid transition, non-participant,
     * or wrong role for this particular edge. Admins bypass the whole check.
     */
    public function canActorTransition(User $actor, string $to): bool
    {
        if ($actor->isAdmin()) {
            return $this->canTransitionTo($to);
        }

        if (! $this->canTransitionTo($to)) {
            return false;
        }

        $key     = "{$this->status}->{$to}";
        $allowed = self::TRANSITION_ACTORS[$key] ?? [];

        if ($actor->id === $this->client_id   && in_array('client', $allowed, true))   return true;
        if ($actor->id === $this->supplier_id && in_array('supplier', $allowed, true)) return true;

        return false;
    }

    /** The statuses $actor is currently allowed to move this booking into. */
    public function allowedTransitionsFor(User $actor): array
    {
        return array_values(array_filter(
            self::TRANSITIONS[$this->status] ?? [],
            fn ($to) => $this->canActorTransition($actor, $to),
        ));
    }

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The service this award is for (B6). Null on a whole-event (SSR) award,
     * which has no single service -- the same convention the bid uses.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->where('type', 'booking');
    }

    public function agreementLogs(): HasMany
    {
        return $this->hasMany(AgreementLog::class, 'subject_id')->where('subject_type', 'booking');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    /**
     * The signed money terms — agreed price, deposit percent and amount.
     *
     * The cancellation policy computes its refund on the held balance, which
     * is the agreed price less the deposit, so this is where those two
     * figures have to come from. Reading the booking's own `price` instead
     * would quote against a number nobody signed.
     */
    public function finalizations(): HasMany
    {
        return $this->hasMany(Finalization::class);
    }

    public function latestFinalization(): HasOne
    {
        return $this->hasOne(Finalization::class)->latestOfMany('id');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class);
    }

    public function latestAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany('version');
    }

    public function activeAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->where('status', '!=', 'rejected')->latestOfMany('version');
    }
}
