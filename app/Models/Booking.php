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

    public function latestAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany('version');
    }

    public function activeAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->where('status', '!=', 'rejected')->latestOfMany('version');
    }
}
