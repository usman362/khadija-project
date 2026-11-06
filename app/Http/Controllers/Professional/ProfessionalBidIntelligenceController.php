<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bid Intelligence — a performance dashboard for the professional's bids:
 * how many they were invited to, submitted, got viewed, won, and lost, plus
 * win-rate, average bid value, response timers and an explainer of how bids
 * move through the pipeline.
 *
 * REAL data mapping (booking lifecycle requested → confirmed → completed |
 * cancelled; no separate bid table — buckets are derived and mutually
 * exclusive so the donut sums cleanly):
 *
 *   Invited   → open marketplace opportunities (published events, no
 *               supplier yet) — clients who could ask this pro to quote.
 *   Submitted → `requested` bookings with NO conversation (quote sent,
 *               awaiting first contact).
 *   Viewed    → `requested` bookings WITH an active conversation (the
 *               client engaged / opened the offer).
 *   Won       → `confirmed` + `completed` bookings.
 *   Lost      → `cancelled` bookings.
 *
 * Win-rate = Won / (Won + Lost). Average bid = mean of booking prices (or
 * event budgets). Response timers are derived from booking timestamps where
 * data exists, with sensible fallbacks. The AI insight, follow-up template
 * and market-benchmark band are explainer UI.
 *
 * Route: GET /professional/bid-intelligence
 */
class ProfessionalBidIntelligenceController extends Controller
{
    /** Legend colours, matching the reference design. */
    private const COLORS = [
        'invited'   => '#2563eb',
        'submitted' => '#8b5cf6',
        'viewed'    => '#10b981',
        'won'       => '#86efac',
        'lost'      => '#ef4444',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $base = fn () => Booking::where('supplier_id', $user->id);

        // requested split by conversation presence.
        $requestedIds = $base()->where('status', 'requested')->pluck('id');
        $viewedIds = collect();
        if ($requestedIds->isNotEmpty()) {
            $viewedIds = Conversation::whereIn('booking_id', $requestedIds)
                ->has('messages')->pluck('booking_id')->unique();
        }

        $invited   = Event::where('is_published', true)->whereNull('supplier_id')
                        ->whereIn('status', ['pending', 'published'])->count();
        $submitted = $requestedIds->reject(fn ($id) => $viewedIds->contains($id))->count();
        $viewed    = $viewedIds->count();
        $won       = $base()->whereIn('status', ['confirmed', 'completed'])->count();
        $lost      = $base()->where('status', 'cancelled')->count();

        $counts = compact('invited', 'submitted', 'viewed', 'won', 'lost');
        $total  = array_sum($counts);

        // ── Donut segments + cumulative conic-gradient ─────────────
        $labels = [
            'invited'   => ['Invited',   'Clients who asked you to send a quote.'],
            'submitted' => ['Submitted', 'Quotes you finished and sent.'],
            'viewed'    => ['Viewed',    'Proof the client opened your offer.'],
            'won'       => ['Won',       'The client accepted your offer.'],
            'lost'      => ['Lost',      'The client chose someone else.'],
        ];

        $segments = [];
        $gradientStops = [];
        $cursor = 0.0;
        foreach ($labels as $key => [$name, $desc]) {
            $count = $counts[$key];
            $pct   = $total > 0 ? $count / $total * 100 : 0;
            $color = self::COLORS[$key];
            $segments[] = [
                'key' => $key, 'name' => $name, 'desc' => $desc,
                'count' => $count, 'pct' => round($pct), 'color' => $color,
            ];
            if ($total > 0 && $count > 0) {
                $gradientStops[] = sprintf('%s %.2f%% %.2f%%', $color, $cursor, $cursor + $pct);
                $cursor += $pct;
            }
        }
        // Fallback: empty pipeline shows a neutral ring.
        $donutGradient = empty($gradientStops)
            ? 'var(--border-color) 0% 100%'
            : implode(', ', $gradientStops);

        $winRate = ($won + $lost) > 0 ? (int) round($won / ($won + $lost) * 100) : 0;

        // ── Average bid value (real) ───────────────────────────────
        $avgBid = (float) $base()->whereNotNull('price')->avg('price');
        if ($avgBid <= 0) {
            $avgBid = (float) Event::where('is_published', true)->whereNotNull('budget')->avg('budget');
        }
        // Null rather than 3200. A professional with no bids was shown an
        // average bid of $3,200, which is a figure about their own pricing
        // that nobody had worked out.
        $avgBid = $avgBid > 0 ? round($avgBid) : null;

        // ── Response timers (real where data exists, else fallback) ─
        $avgDays = function ($query) {
            $rows = $query->get(['created_at', 'updated_at']);
            if ($rows->isEmpty()) {
                return null;
            }
            $sum = $rows->sum(fn ($r) => $r->created_at->floatDiffInDays($r->updated_at));
            return round($sum / $rows->count(), 1);
        };
        /*
         * Three stages are measured. "Invited" and "viewed" were 2.1 and 3.2
         * days, typed in and never worked out from anything, sitting in the
         * same chart as the three that are real. A bar nobody measured does
         * not belong beside bars that were, so they are gone rather than
         * given a number of their own.
         */
        $responseTimes = array_values(array_filter([
            ['key' => 'submitted', 'color' => self::COLORS['submitted'], 'days' => $avgDays($base()->where('status', 'requested'))],
            ['key' => 'won',       'color' => self::COLORS['won'],       'days' => $avgDays($base()->whereIn('status', ['confirmed', 'completed']))],
            ['key' => 'lost',      'color' => self::COLORS['lost'],      'days' => $avgDays($base()->where('status', 'cancelled'))],
        ], fn ($row) => $row['days'] !== null));

        // Only the professional's own average is real, so only that is
        // shown. The low/mid/high band that used to sit beside it was this
        // same number times 0.69, 0.94 and 1.19, labelled as the market.
        $pricing = ['avg' => $avgBid];

        $stats = array_merge($counts, [
            'total'    => $total,
            'win_rate' => $winRate,
            'avg_bid'  => $avgBid,
        ]);

        /*
         * The rest of Khadijah's 28 October screen, read off the bids
         * themselves rather than off bookings.
         *
         * Her screen also carries an AI win rate predictor, a market
         * benchmark and a smart recommendation. None of the three is here:
         * the platform has no model, no competitor data and no record of why
         * a client chose somebody else, so each would be a number invented on
         * the screen a professional uses to decide what to charge.
         */
        $bids = \App\Support\BidPerformance::bids($user);

        /*
         * Her filter bar. Each one narrows the same set of bids, and each
         * list is built from what this professional's own bids actually
         * carry, so no filter can empty the page on its own.
         */
        $filters = [
            'period'  => (string) $request->query('period', '90'),
            'service' => (string) $request->query('service', ''),
            'type'    => (string) $request->query('type', ''),
            'place'   => (string) $request->query('place', ''),
            'band'    => (string) $request->query('band', ''),
        ];

        $options = [
            'services' => $bids->map(fn ($b) => $b->category?->name)->filter()->unique()->sort()->values()->all(),
            'types'    => $bids->map(fn ($b) => $b->event?->event_type)->filter()->unique()->sort()->values()->all(),
            'places'   => $bids->map(fn ($b) => $b->event?->state)->filter()->unique()->sort()->values()->all(),
            'bands'    => ['Under $1,000', '$1,000 to $2,500', '$2,500 to $5,000', 'Over $5,000'],
            'periods'  => ['30' => 'Last 30 Days', '90' => 'Last 90 Days', '365' => 'Last 12 Months', 'all' => 'All Time'],
        ];

        // Nothing to filter means no filter bar: an account that has never
        // bid should not be shown price bands and a clock.
        $hasAnyBids = $bids->isNotEmpty();

        $since = $filters['period'] === 'all' ? null : now()->subDays((int) $filters['period'] ?: 90);

        $bids = $bids->filter(function ($b) use ($filters, $since) {
            $at = $b->submitted_at ?? $b->created_at;

            return (! $since || ($at && $at->gte($since)))
                && (! $filters['service'] || $b->category?->name === $filters['service'])
                && (! $filters['type']    || $b->event?->event_type === $filters['type'])
                && (! $filters['place']   || $b->event?->state === $filters['place'])
                && (! $filters['band']    || self::band((float) $b->amount) === $filters['band']);
        })->values();

        /*
         * The same window again, ending where this one starts.
         *
         * Her tiles each carry an arrow and a percentage "vs. previous 90
         * days". This is the only honest version of that: this professional
         * against themselves over the span before. Null where there was
         * nothing to compare against, because everything is not an infinite
         * improvement on nothing.
         */
        $days  = $filters['period'] === 'all' ? null : ((int) $filters['period'] ?: 90);
        $prior = collect();

        if ($days) {
            $from = now()->subDays($days * 2);
            $to   = now()->subDays($days);

            $prior = \App\Support\BidPerformance::bids($user)->filter(function ($b) use ($from, $to) {
                $at = $b->submitted_at ?? $b->created_at;

                return $at && $at->gte($from) && $at->lt($to);
            });
        }

        $change = function (int $now, int $was): ?int {
            if ($was === 0) {
                return null;
            }

            return (int) round(($now - $was) / $was * 100);
        };

        $priorRecord = \App\Support\BidPerformance::record($prior);

        $trend = [
            'submitted' => $days ? $change($bids->count(), $priorRecord['placed']) : null,
            'accepted'  => $days ? $change(
                $bids->whereIn('status', \App\Support\BidPerformance::WON)->count(),
                $priorRecord['won'],
            ) : null,
            'open'      => $days ? $change(
                $bids->count()
                    - $bids->whereIn('status', \App\Support\BidPerformance::WON)->count()
                    - $bids->whereIn('status', \App\Support\BidPerformance::LOST)->count(),
                $priorRecord['open'],
            ) : null,
            'span'      => $days ? 'vs. previous ' . $days . ' days' : null,
        ];

        $performance = [
            'record'  => \App\Support\BidPerformance::record($bids),
            'byEvent' => \App\Support\BidPerformance::splitBy(
                $bids, fn ($b) => $b->event?->event_type
            ),
            'byPlace' => \App\Support\BidPerformance::splitBy(
                $bids, fn ($b) => $b->event?->state
            ),
            'byService' => \App\Support\BidPerformance::splitBy(
                $bids, fn ($b) => $b->category?->name
            ),
            'byPrice' => \App\Support\BidPerformance::byPrice($bids),
            'lost'    => \App\Support\BidPerformance::lost($bids),
            'season'  => \App\Support\BidPerformance::season($bids),
            'funnel'  => \App\Support\BidPerformance::funnel($user, $bids),
            'timing'  => \App\Support\BidPerformance::timing($bids),
            'trend'   => \App\Support\BidPerformance::trend($bids),
            'quality' => \App\Support\BidPerformance::quality($bids),
        ];

        /*
         * Open requests this professional has not bid on, matched to the
         * services they sell. Her screen calls these Smart Recommendations
         * and scores each one; there is no model behind a score, so these are
         * simply the open requests they could still answer.
         */
        $mine = $user->serviceCategories()->pluck('categories.id');

        $open = Event::where('is_published', true)
            ->whereIn('status', ['pending', 'published'])
            ->whereNull('supplier_id')
            ->whereDoesntHave('bids', fn ($q) => $q->where('supplier_id', $user->id))
            ->when($mine->isNotEmpty(), fn ($q) => $q
                ->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $mine)))
            ->with('categories:id,name')
            ->latest('published_at')
            ->take(4)
            ->get();

        // Everything still waiting on this professional, from the one list
        // the whole portal reads, so this card and Priority Actions cannot
        // disagree about what is outstanding.
        $waiting = collect(\App\Support\PriorityActions::forProfessional($user))->take(4);

        return view('professional.bid-intelligence.index', compact(
            'stats', 'segments', 'donutGradient', 'responseTimes', 'pricing', 'performance',
            'filters', 'options', 'open', 'waiting', 'hasAnyBids', 'trend'
        ));
    }

    /** Which price band an amount falls in, named as byPrice names them. */
    private static function band(float $amount): string
    {
        return match (true) {
            $amount < 1000 => 'Under $1,000',
            $amount < 2500 => '$1,000 to $2,500',
            $amount < 5000 => '$2,500 to $5,000',
            default        => 'Over $5,000',
        };
    }
}
