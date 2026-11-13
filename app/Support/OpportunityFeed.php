<?php

namespace App\Support;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The professional's Opportunity Feed — Rule R61, Option B.
 *
 * Peter chose Option B on 2026-08-07: the professional's own services first,
 * then related work below, clearly separated, with a "My services only"
 * filter. The half that needed deciding was what "related" means, and R61
 * settles it: same Sub category, a DIFFERENT Sub-Sub service, read off the
 * R45 taxonomy. Structural, not a Fit Score threshold.
 *
 * That distinction is the whole design, and the memo's arithmetic is why. An
 * off-category gig cannot score above 60 for an established professional and
 * about 40 for a new one, so any threshold picked above 40 would have shown
 * related work to established professionals and not to new ones — inverting
 * the purpose of the feature on the exact population it exists to help.
 * Structure cannot be defeated by a badly-chosen number.
 *
 * A DJ therefore sees a live-band request, because both sit under DJs, Live
 * Bands & Musicians. A photographer never sees a catering request, because
 * Catering & Food Services is a different Sub entirely — no tuning required
 * to prevent it.
 *
 * The related block is deliberately NON-ACTIONABLE. R61 keeps Bid and Respond
 * gated to actually-listed services, and R38's ratified finding 7 says the
 * same in the other direction: search hides what is ineligible, the feed shows
 * related-but-non-actionable.
 */
final class OpportunityFeed
{
    /** How many of each block the dashboard card shows. */
    public const LIMIT = 6;

    /**
     * @return array{listed: Collection, related: Collection, hasServices: bool}
     */
    public static function for(User $pro, bool $myServicesOnly = false): array
    {
        $mine = FitScore::servicesOf($pro);

        // A professional who has listed nothing has no services to sort by
        // and no categories to relate to. They get the open board rather than
        // an empty page, and the card tells them why.
        if ($mine->isEmpty()) {
            return [
                'listed'      => collect(),
                'related'     => self::rank(self::openRows(self::openToThem($pro), self::LIMIT), $pro),
                'hasServices' => false,
            ];
        }

        $listed = self::openRows(
            self::openToThem($pro)->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $mine)),
            self::LIMIT
        );

        if ($myServicesOnly) {
            return ['listed' => self::rank($listed, $pro), 'related' => collect(), 'hasServices' => true];
        }

        // Same parent, different service — and not already in the first block.
        $siblings = FitScore::parentsOf($mine);

        $related = $siblings->isEmpty() ? collect() : self::openRows(
            self::openToThem($pro)
                ->whereDoesntHave('categories', fn ($q) => $q->whereIn('categories.id', $mine))
                ->whereHas('categories', fn ($q) => $q->whereIn('parent_id', $siblings)),
            self::LIMIT
        );

        return [
            'listed'      => self::rank($listed, $pro),
            'related'     => self::rank($related, $pro),
            'hasServices' => true,
        ];
    }

    /**
     * How many rows to read before the lifecycle filter, so six survive it.
     *
     * Whether a request is still taking proposals is partly a question about
     * other records — whether a client has opened a finalization with
     * somebody, whether every service on it has been awarded — which no WHERE
     * clause can answer. The cheap half is done in SQL and the rest in PHP,
     * over a window rather than the whole board.
     */
    private const SCAN = 40;

    /** Open, unassigned, and in this professional's state (R38). */
    private static function openToThem(User $pro)
    {
        $query = Event::query()
            ->where('is_published', true)
            ->whereNull('supplier_id')
            ->whereIn('status', ['pending', 'published'])
            /*
             * Still taking proposals, as §1 of the lifecycle defines it.
             *
             * This asked only whether a request was published and unassigned,
             * so a request whose bidding window shut in August was still an
             * "opportunity" in November. The card is capped at six rows, so
             * six long-dead requests filled it and every live one was pushed
             * out of a feed whose entire job is work the professional can
             * still take. Every row then had to be printed "Closed to bids",
             * which is the card saying it has nothing to offer in six
             * different ways.
             */
            ->whereNull('closed_at')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('proposal_deadline')->orWhere('proposal_deadline', '>', now()))
            ->with('categories:id,name,parent_id')
            ->orderByRaw('starts_at is null, starts_at asc');

        StateMatching::scopeForViewer($query, $pro);

        return $query;
    }

    /**
     * The rest of the lifecycle test, which SQL cannot ask.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private static function openRows($query, int $limit): Collection
    {
        return $query->limit(self::SCAN)->get()
            ->filter(fn (Event $event) => \App\Domain\Requests\RequestLifecycle::acceptsProposals($event))
            ->take($limit)
            ->values();
    }

    /**
     * Order within a block by Fit Score.
     *
     * The score does what a score is good at — putting these in order —
     * instead of deciding which of them exist. That decision is the taxonomy's.
     */
    private static function rank(Collection $events, User $pro): Collection
    {
        return $events
            ->map(fn (Event $event) => [
                'event' => $event,
                'fit'   => FitScore::for($event, $pro),
            ])
            ->sortByDesc('fit')
            ->values();
    }
}
