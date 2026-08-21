<?php

namespace App\Domain\Toolkit;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventAiArtifact;
use App\Models\ToolkitAttachment;
use App\Models\User;
use App\Support\ToolkitTiers;
use Illuminate\Support\Collection;

/**
 * R30 — Client Toolkit → Request / Agreement bridge.
 *
 * The toolkit could already push a result onto an event. It could never reach
 * an AGREEMENT, which is where the numbers actually bind somebody. That is the
 * half this builds, and it is the half with the rules.
 *
 * Two of those rules are load-bearing:
 *
 *   Not every tool is a data source. Best Match STARTS an agreement -- it is
 *   not a figure to put inside one -- and Review Builder only runs after the
 *   event is over. Offering them here would promise a placement that has
 *   nowhere to land.
 *
 *   An accepted agreement is a contract. Data cannot be slipped into one, so a
 *   binding agreement is shown with the reason rather than hidden -- a client
 *   who cannot find their agreement assumes the feature is broken, and a
 *   client told "this one is signed" learns how the product works.
 */
class ToolkitBridge
{
    /**
     * Tools that hold no data for a request or agreement.
     * Keyed by catalogue key, valued with the reason the client is shown.
     */
    public const NOT_A_SOURCE = [
        'vendor-matchmaking' => 'Best Match starts an agreement with a professional rather than adding detail to one.',
        'review-writer'      => 'Review Builder is used after the event has finished.',
    ];

    /**
     * Language translates what other tools produced. It has no result of its
     * own to place, so it supports the bridge without appearing on it.
     */
    public const SUPPORTS_ONLY = [
        'translator' => 'Language translates text from the other tools; it has no result of its own to add.',
    ];

    /** What every client holds while the toolkit is not yet on sale. */
    public const LAUNCH_TIER = 'maximum';

    /** Agreement statuses that can still receive planning data. */
    public const OPEN_AGREEMENT_STATUSES = ['draft', 'pending_review', 'client_accepted', 'supplier_accepted'];

    /**
     * The toolkit tools this client may use as a data source.
     *
     * Each row carries why it is or is not usable, because "the grid is
     * shorter than the twelve you were sold" needs an answer on the page.
     */
    public static function toolsFor(?User $user): Collection
    {
        return collect(ToolkitTiers::toolsFor(ToolkitTiers::CLIENT))
            ->map(function (array $tool) use ($user) {
                $key      = $tool['key'];
                $unlocked = self::unlocksTool($user, $tool['name']);

                $blocked = self::NOT_A_SOURCE[$key] ?? self::SUPPORTS_ONLY[$key] ?? null;

                return $tool + [
                    'is_source'  => $blocked === null,
                    'unlocked'   => $unlocked,
                    'usable'     => $blocked === null && $unlocked,
                    'reason'     => $blocked ?? ($unlocked ? null : 'Not included in your current toolkit add-on.'),
                ];
            })
            ->values();
    }

    /** Does this client's paid level include the tool? */
    public static function unlocksTool(?User $user, string $toolName): bool
    {
        return $user !== null
            && ToolkitTiers::unlocks(self::tierOf($user), $toolName, ToolkitTiers::CLIENT);
    }

    /**
     * The toolkit tier this client holds.
     *
     * There is no checkout for the toolkit and no column recording a purchase,
     * so there is nothing to look up: during launch every client holds every
     * tool. Saying that on the screen is the honest version -- the mockup's
     * Semi / Maximum buttons would let a client pick a tier they never bought
     * and then show them tools they do not have.
     *
     * When the tier is sold and stored, this is the only method that changes.
     */
    public static function tierOf(?User $user): string
    {
        return $user ? self::LAUNCH_TIER : 'manual';
    }

    public static function everythingUnlocked(?User $user): bool
    {
        return filter_var(env('AI_FEATURES_FREE_FOR_ALL', false), FILTER_VALIDATE_BOOLEAN)
            || (bool) $user?->isAdmin();
    }

    /** The saved results this client can place. */
    public static function savedResultsFor(User $user, ?string $toolKey = null): Collection
    {
        return EventAiArtifact::query()
            ->where('user_id', $user->id)
            ->when($toolKey, fn ($q) => $q->where('tool_key', $toolKey))
            ->latest()
            ->get();
    }

    /**
     * Every place this client could put something, each with whether it can
     * actually take it and why not.
     */
    public static function destinationsFor(User $user): array
    {
        return [
            'requests'   => self::openRequests($user),
            'agreements' => self::agreementsFor($user),
        ];
    }

    /** Requests still open enough to change. */
    public static function openRequests(User $user): Collection
    {
        return Event::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('created_by', $user->id))
            ->whereNull('closed_at')
            ->where('status', '!=', 'completed')
            ->latest()
            ->get()
            ->map(fn (Event $e) => [
                'id'       => $e->id,
                'label'    => $e->title,
                'meta'     => $e->starts_at?->format('M j, Y'),
                'eligible' => true,
                'reason'   => null,
                'model'    => $e,
            ]);
    }

    /**
     * One row per professional agreement -- never one row for "the event".
     *
     * On a multi-service request the client has an agreement with each
     * professional, and putting the guest count into all of them because it
     * was true of one is how a caterer ends up bound to the DJ's numbers.
     */
    public static function agreementsFor(User $user): Collection
    {
        return Agreement::query()
            ->whereHas('booking', fn ($q) => $q->where('client_id', $user->id))
            ->whereNotIn('status', ['rejected', 'expired'])
            ->with(['booking.supplier'])
            ->latest()
            ->get()
            ->map(function (Agreement $a) {
                $open = in_array($a->status, self::OPEN_AGREEMENT_STATUSES, true);

                return [
                    'id'       => $a->id,
                    'label'    => $a->title ?: 'Agreement #' . $a->id,
                    'meta'     => $a->booking?->supplier?->name,
                    'eligible' => $open,
                    'reason'   => $open ? null
                        : 'Both sides have accepted this agreement. Changing it now goes through the agreement change-and-approval process, not this screen.',
                    'model'    => $a,
                ];
            });
    }

    /**
     * Place a saved result. Returns the attachment, or null if that exact
     * result is already sitting on that destination.
     */
    public static function attach(
        User $user,
        EventAiArtifact $artifact,
        Event|Agreement $destination,
        string $linkMode = ToolkitAttachment::COPY,
    ): ?ToolkitAttachment {
        $already = ToolkitAttachment::query()
            ->where('attachable_type', $destination::class)
            ->where('attachable_id', $destination->id)
            ->where('source_artifact_id', $artifact->id)
            ->exists();

        if ($already) {
            return null;
        }

        return ToolkitAttachment::create([
            'attachable_type'    => $destination::class,
            'attachable_id'      => $destination->id,
            'source_artifact_id' => $artifact->id,
            'added_by'           => $user->id,
            'tool_key'           => $artifact->tool_key,
            'tool_name'          => $artifact->tool_name,
            'title'              => $artifact->title,
            'payload'            => $artifact->payload,
            'link_mode'          => $linkMode === ToolkitAttachment::LINKED
                ? ToolkitAttachment::LINKED
                : ToolkitAttachment::COPY,
            'source_fingerprint' => ToolkitAttachment::fingerprint($artifact->payload),
            'needs_review'       => false,
        ]);
    }

    /** What is currently placed on a request or agreement. */
    public static function attachmentsOn(Event|Agreement $destination): Collection
    {
        return ToolkitAttachment::query()
            ->where('attachable_type', $destination::class)
            ->where('attachable_id', $destination->id)
            ->with('source')
            ->latest()
            ->get();
    }

    /**
     * Flag linked placements whose source has since changed.
     *
     * Flagged, not updated. A linked figure can be sitting inside an agreement
     * somebody is reading, and rewriting it underneath them would be the worst
     * kind of helpful.
     */
    public static function markMovedSources(User $user): int
    {
        $linked = ToolkitAttachment::query()
            ->where('added_by', $user->id)
            ->where('link_mode', ToolkitAttachment::LINKED)
            ->where('needs_review', false)
            ->with('source')
            ->get();

        $flagged = 0;

        foreach ($linked as $attachment) {
            if ($attachment->sourceHasMoved()) {
                $attachment->update(['needs_review' => true]);
                $flagged++;
            }
        }

        return $flagged;
    }

    /**
     * Can this placement still be changed where it sits?
     *
     * The gap this closes: data linked into a DRAFT agreement, which both
     * sides then accept, and whose source later moves. Without this check the
     * review prompt would happily rewrite a figure inside a signed contract --
     * the exact thing blocking accepted agreements as destinations prevents at
     * the front door.
     */
    public static function destinationStillOpen(ToolkitAttachment $attachment): bool
    {
        $destination = $attachment->attachable;

        if ($destination instanceof Agreement) {
            return in_array($destination->status, self::OPEN_AGREEMENT_STATUSES, true);
        }

        if ($destination instanceof Event) {
            return $destination->closed_at === null && $destination->status !== 'completed';
        }

        // The destination is gone. Nothing to write into.
        return false;
    }

    /**
     * Accept the source's current version into a linked placement.
     *
     * Returns false when the place it sits has since closed -- the client
     * keeps what is there and is told which door to use instead.
     */
    public static function applyUpdate(ToolkitAttachment $attachment): bool
    {
        if (! $attachment->isLinked() || ! $attachment->source) {
            return false;
        }

        if (! self::destinationStillOpen($attachment)) {
            return false;
        }

        $attachment->update([
            'payload'            => $attachment->source->payload,
            'title'              => $attachment->source->title,
            'source_fingerprint' => ToolkitAttachment::fingerprint($attachment->source->payload),
            'needs_review'       => false,
        ]);

        return true;
    }

    /** Keep what is placed and stop following the source. */
    public static function keepCurrent(ToolkitAttachment $attachment): void
    {
        $attachment->update([
            'link_mode'    => ToolkitAttachment::COPY,
            'needs_review' => false,
        ]);
    }
}
