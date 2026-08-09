<?php

namespace App\Models;

use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputeStates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rule R34 — one dispute, scoped to ONE service line (§6, R12).
 *
 * A case belongs to a booking, not to an event. On a multi-service request a
 * client may have five professionals; a problem with the caterer is not a
 * problem with the photographer, and one case covering the whole event would
 * pause five people's money over one person's work.
 */
class DisputeCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'category_id', 'filed_by', 'client_id', 'professional_id',
        'severity', 'priority', 'taxonomy', 'secondary_taxonomy',
        'state', 'summary', 'duplicate_of', 'internal_tags',
        'balance_paused', 'assigned_to', 'assigned_role', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'severity'           => 'integer',
            'secondary_taxonomy' => 'array',
            'internal_tags'      => 'array',
            'balance_paused'     => 'boolean',
            'closed_at'          => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $case) {
            $case->reference ??= self::nextReference();

            // §2 — severity decides where a case opens, and only severity 4–5
            // change it. Set here rather than in the controller so a case
            // created from a console command or a test cannot open in the
            // wrong step.
            $case->state ??= DisputeStates::openingStateFor((int) $case->severity);

            // §8 — filing pauses the held balance on this service line. It is
            // a consequence of the case existing, not a separate staff action.
            $case->balance_paused ??= true;
        });
    }

    /**
     * The next public case number, DR-YYYY-NNNNNN (§6).
     *
     * One global sequence: the year is a prefix, not a reset point. A case
     * filed on 31 December and its follow-up on 1 January land in different
     * years, and two cases numbered 000001 would be indistinguishable in a
     * support conversation where nobody reads the year aloud.
     *
     * Locked for the read so two simultaneous filings cannot take the same
     * number; the unique index is the backstop if they somehow do.
     */
    public static function nextReference(): string
    {
        $last = static::query()->orderByDesc('id')->lockForUpdate()->value('reference');

        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('DR-%s-%06d', now()->year, $next);
    }

    /* ── State ── */

    public function stateLabel(): string
    {
        return DisputeStates::LABELS[$this->state] ?? $this->state;
    }

    public function isOpen(): bool
    {
        return ! DisputeStates::isTerminal($this->state);
    }

    /**
     * Move the case, recording who moved it and why (§10).
     *
     * Returns false rather than throwing on a prohibited move: the caller is
     * a controller deciding what to show a person, and a state machine that
     * throws makes "you cannot do that from here" an error page.
     */
    public function transitionTo(string $to, User $actor, string $role, ?string $reason = null): bool
    {
        if (! DisputeStates::allows($this->state, $to, $role)) {
            return false;
        }

        $from = $this->state;

        $this->forceFill([
            'state'     => $to,
            'closed_at' => DisputeStates::isTerminal($to) ? now() : $this->closed_at,
        ])->save();

        $this->log('state_changed', $actor, $role, [
            'field' => 'state', 'old' => $from, 'new' => $to, 'reason' => $reason,
        ]);

        return true;
    }

    /** §10 — one audit row per action, with the previous and new value. */
    public function log(string $action, ?User $actor, ?string $role, array $detail = []): DisputeEvent
    {
        return $this->events()->create([
            'actor_id'           => $actor?->id,
            'actor_role'         => $role,
            'action'             => $action,
            'field'              => $detail['field'] ?? null,
            'old_value'          => $detail['old'] ?? null,
            'new_value'          => $detail['new'] ?? null,
            'reason'             => $detail['reason'] ?? null,
            'visible_to_parties' => $detail['visible'] ?? true,
            'created_at'         => now(),
        ]);
    }

    /* ── Classification helpers ── */

    public function severityLabel(): string
    {
        return DisputeClassification::SEVERITIES[$this->severity] ?? 'Unclassified';
    }

    public function taxonomyLabel(): string
    {
        return DisputeClassification::TAXONOMY[$this->taxonomy] ?? $this->taxonomy;
    }

    /** The decision that stands — the latest revision, not the first ruling (§5). */
    public function currentDecision(): ?DisputeDecision
    {
        return $this->decisions()->orderByDesc('id')->first();
    }

    /** Is $user one of the two parties? Everyone else needs a staff role. */
    public function isParty(User $user): bool
    {
        return in_array($user->id, [$this->client_id, $this->professional_id], true);
    }

    /* ── Relations ── */

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(DisputeDecision::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DisputeEvent::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DisputeAssignment::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(DisputeEscalation::class);
    }

    /**
     * §6 — independent-but-connected cases, visible to staff.
     *
     * Same event, different service line. Each still resolves entirely on its
     * own merits; this is a link on the screen, not shared state.
     */
    public function relatedCases()
    {
        if ($this->booking?->event_id === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()
            ->whereKeyNot($this->getKey())
            ->whereHas('booking', fn ($q) => $q->where('event_id', $this->booking->event_id));
    }
}
