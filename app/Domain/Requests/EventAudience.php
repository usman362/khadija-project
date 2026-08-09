<?php

namespace App\Domain\Requests;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Event;
use App\Models\User;
use App\Support\StateMatching;
use Illuminate\Support\Collection;

/**
 * Who hears about a listing.
 *
 * One definition, used by both of Rule R33 §6's notices. If "new event" and
 * "event reopened" went to different sets, a professional could be told a
 * listing had reopened without ever having been told it existed.
 */
final class EventAudience
{
    /**
     * Professionals who can actually work this request.
     *
     * R38 first: a professional in another state cannot bid on it, so telling
     * them about it is noise they can do nothing with. Then the services —
     * matched against the event's categories, or everyone in the state if the
     * request names no category at all.
     *
     * @return Collection<int, User>
     */
    public static function for(Event $event): Collection
    {
        $categoryIds = $event->categories()->pluck('categories.id')->all();

        if ($categoryIds === [] && $event->category_id) {
            $categoryIds = [$event->category_id];
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::PROFESSIONAL->value))
            ->when($event->state, fn ($q) => $q->whereHas(
                'profile', fn ($p) => $p->where('state', $event->state),
            ))
            ->when($categoryIds !== [], fn ($q) => $q->whereHas(
                'serviceCategories', fn ($c) => $c->whereIn('categories.id', $categoryIds),
            ))
            ->where('users.id', '!=', $event->client_id)
            ->get();
    }

    /**
     * Professionals with a proposal already on this event (§3).
     *
     * A narrower set on purpose: an event changing matters to the people who
     * priced it, not to everyone who could have.
     *
     * @return Collection<int, User>
     */
    public static function withProposalsOn(Event $event): Collection
    {
        $ids = $event->bids()
            ->whereNotIn('status', ['withdrawn', 'rejected'])
            ->pluck('supplier_id')
            ->unique()
            ->all();

        return $ids === [] ? collect() : User::whereIn('id', $ids)->get();
    }

    /**
     * Whether R38 would let this professional see the request at all.
     *
     * Belongs here rather than at each call site: the audience and the
     * eligibility have to agree, or someone gets a notification about a
     * listing that then refuses their proposal.
     */
    public static function eligible(Event $event, User $professional): bool
    {
        return StateMatching::matches($event->state, StateMatching::stateOf($professional));
    }
}
