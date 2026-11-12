<?php

namespace App\Support;

use App\Domain\Requests\RequestLifecycle;
use App\Models\Event;
use App\Models\User;

/**
 * May this professional open the bid wizard on this request?
 *
 * The wizard has always asked this, because its URL is reachable directly and
 * the rules are server-side authoritative. The pages that link to the wizard
 * were not asking, and the Leads CRM proved what that costs: on 13 November
 * every one of the seven "Open the request" links on that page answered 403
 * or 410. Six of them were requests outside the professional's listed
 * services or already closed to proposals, and the page offered them anyway.
 *
 * So the question lives here, is asked once, and both sides use the answer:
 * the wizard aborts with it, a page linking to the wizard reads it and offers
 * something that works instead.
 *
 * Returns null when the wizard is open, otherwise the status and the sentence
 * the wizard would have aborted with.
 *
 * @see \App\Http\Controllers\Professional\ProfessionalBidWizardController
 */
final class BidAccess
{
    /**
     * @param  \Illuminate\Support\Collection|null  $listed  The professional's
     *         listed service ids, when the caller is asking about many events
     *         in a row and has already read them. A loop that does not pass
     *         them reads the pivot once per event.
     * @return array{code:int, message:string}|null
     */
    public static function denial(User $user, Event $event, ?\Illuminate\Support\Collection $listed = null): ?array
    {
        // DSRs are targeted: only the named professional may respond.
        if ($event->source === 'direct_offer') {
            if ((int) $event->supplier_id !== (int) $user->id) {
                return ['code' => 403, 'message' => 'This request was sent to another professional.'];
            }
        } elseif (! $event->is_published) {
            return ['code' => 404, 'message' => 'This request is not published.'];
        }

        if (in_array($event->status, ['completed', 'cancelled'], true)) {
            return ['code' => 410, 'message' => 'This request is closed.'];
        }

        // Rule R38 — same state only.
        if (! StateMatching::matches($event->state, StateMatching::stateOf($user))
            && StateMatching::appliesTo($user)) {
            return ['code' => 403, 'message' => 'This request is in another state.'];
        }

        // Rule R61 — bidding is gated to the services actually listed. A
        // professional who has listed nothing has not opted out of anything.
        $mine = $listed ?? FitScore::servicesOf($user);

        if ($mine->isNotEmpty() && $event->source !== 'direct_offer'
            && $event->categories->pluck('id')->intersect($mine)->isEmpty()) {
            return ['code' => 403, 'message' => 'Add this service to your profile before bidding on it.'];
        }

        // Rule R33 §7 — no new proposal while the listing is not open.
        if (! RequestLifecycle::acceptsProposals($event)) {
            return ['code' => 410, 'message' => 'This request is not accepting proposals right now.'];
        }

        return null;
    }

    public static function allows(User $user, Event $event, ?\Illuminate\Support\Collection $listed = null): bool
    {
        return self::denial($user, $event, $listed) === null;
    }
}
