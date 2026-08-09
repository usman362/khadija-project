<?php

namespace App\Support\Reports;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\UploadedFile;
use App\Models\User;
use App\Support\Commission;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What an admin needs to know about the marketplace, over a date range.
 *
 * Built after Peter's 2026-08-09 note that reporting was "not even close":
 * influencers had six analytics pages, professionals had two CSV exports, and
 * admins and clients had nothing at all.
 *
 * Every figure comes from a record the platform already holds — bookings,
 * bids, events, users, uploads. Nothing is modelled, projected or filled in.
 * Where the data cannot answer a question the answer is null and the page
 * prints a dash, which is the same discipline the professional portfolio's
 * response figures needed: an invented number on a report is worse than a
 * missing one, because a report is what gets acted on.
 */
final class AdminReport
{
    public function __construct(
        private CarbonInterface $from,
        private CarbonInterface $to,
    ) {
    }

    /** The headline row: money, and what produced it. */
    public function money(): array
    {
        $completed = Booking::whereBetween('bookings.created_at', [$this->from, $this->to])
            ->where('status', 'completed')
            ->with('supplier.subscriptions.plan')
            ->get(['id', 'supplier_id', 'price']);

        $gross = (float) $completed->sum('price');

        // Commission is per-professional (Starter 5 / Pro 3 / Elite 1.5), so
        // it is summed per booking rather than taken as one rate off the
        // total — a single blended rate would be a different number and a
        // wrong one.
        $commission = $completed->sum(fn (Booking $b) => Commission::on((float) $b->price, $b->supplier));

        return [
            'gross'          => $gross,
            'commission'     => round($commission, 2),
            'bookings'       => $completed->count(),
            'average_value'  => $completed->count() ? round($gross / $completed->count(), 2) : null,
        ];
    }

    /** Does work posted here actually get taken? */
    public function marketplace(): array
    {
        $posted = Event::whereBetween('created_at', [$this->from, $this->to])
            ->where('is_published', true);

        $postedCount = (clone $posted)->count();
        $withBid     = (clone $posted)->whereHas('bids')->count();
        $awarded     = (clone $posted)->whereNotNull('supplier_id')->count();

        $bids = Bid::whereBetween('created_at', [$this->from, $this->to])->count();

        return [
            'posted'       => $postedCount,
            'bids'         => $bids,
            // The two questions a marketplace lives or dies on: did anyone
            // bid, and did the client end up hiring.
            'bid_rate'     => $postedCount ? (int) round($withBid / $postedCount * 100) : null,
            'award_rate'   => $postedCount ? (int) round($awarded / $postedCount * 100) : null,
            'bids_per_gig' => $withBid ? round($bids / $withBid, 1) : null,
            'time_to_first_bid_hours' => $this->timeToFirstBid(),
        ];
    }

    /**
     * How long a client waits before anyone bids.
     *
     * Measured only on gigs that actually got a bid. Counting the ones that
     * never did as some large number would turn a fill-rate problem into a
     * response-time problem and hide both.
     */
    private function timeToFirstBid(): ?float
    {
        $rows = Event::query()
            ->whereBetween('events.created_at', [$this->from, $this->to])
            ->join('bids', 'bids.event_id', '=', 'events.id')
            ->groupBy('events.id', 'events.created_at')
            ->selectRaw('events.created_at as posted, MIN(bids.created_at) as first_bid')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $hours = $rows->map(fn ($r) => \Illuminate\Support\Carbon::parse($r->posted)
            ->floatDiffInHours(\Illuminate\Support\Carbon::parse($r->first_bid)));

        return round($hours->avg(), 1);
    }

    /** Who is here, and who is actually using it. */
    public function people(): array
    {
        $signups = User::whereBetween('created_at', [$this->from, $this->to])->count();

        // "Active" means did something, not logged in — there is no
        // last-seen column, and counting accounts that merely exist is how a
        // marketplace convinces itself it is busy.
        $activePros = Bid::whereBetween('created_at', [$this->from, $this->to])
            ->distinct()->count('supplier_id');

        $activeClients = Event::whereBetween('created_at', [$this->from, $this->to])
            ->distinct()->count('client_id');

        return [
            'signups'        => $signups,
            'active_pros'    => $activePros,
            'active_clients' => $activeClients,
        ];
    }

    /**
     * The queue — work waiting on a person, not on the date range.
     *
     * Deliberately not filtered by the range: a verification document that
     * has been waiting since June is exactly the thing an admin needs to see
     * when they open a report for August.
     */
    public function needsAttention(): array
    {
        return [
            'uploads_held' => UploadedFile::awaitingReview()->count(),

            'verification_pending' => DB::table('user_profiles')
                ->where(fn ($q) => $q->whereNotNull('trade_license_doc')->whereNull('trade_license_verified_at'))
                ->orWhere(fn ($q) => $q->whereNotNull('liability_insurance_doc')->whereNull('liability_insurance_verified_at'))
                ->orWhere(fn ($q) => $q->whereNotNull('workers_comp_doc')->whereNull('workers_comp_verified_at'))
                ->count(),

            'out_of_area_waitlist' => DB::table('user_profiles')
                ->where('service_area_status', 'coming_soon')->count(),

            'open_gigs_no_bids' => Event::where('is_published', true)
                ->whereNull('supplier_id')
                ->whereIn('status', ['pending', 'published'])
                ->whereDoesntHave('bids')->count(),
        ];
    }

    /**
     * The same money and activity, per state.
     *
     * R38 made this the shape the business actually has: seven separate
     * same-state marketplaces rather than one pooled market, so a total that
     * hides which state produced it is not much use for deciding where to
     * put effort.
     */
    public function byState(): Collection
    {
        $gigs = Event::whereBetween('created_at', [$this->from, $this->to])
            ->whereNotNull('state')
            ->selectRaw('state, count(*) as n')->groupBy('state')->pluck('n', 'state');

        $pros = DB::table('user_profiles')
            ->join('model_has_roles', fn ($j) => $j->on('model_has_roles.model_id', '=', 'user_profiles.user_id'))
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'professional')
            ->whereNotNull('user_profiles.state')
            ->selectRaw('user_profiles.state as state, count(*) as n')
            ->groupBy('user_profiles.state')->pluck('n', 'state');

        $revenue = Booking::whereBetween('bookings.created_at', [$this->from, $this->to])
            ->where('bookings.status', 'completed')
            ->join('events', 'events.id', '=', 'bookings.event_id')
            ->whereNotNull('events.state')
            ->selectRaw('events.state as state, sum(bookings.price) as total')
            ->groupBy('events.state')->pluck('total', 'state');

        $rows = collect(array_keys(config('geo.allowed_states', [])))
            ->map(fn (string $state) => [
                'state'         => $state,
                'gigs'          => (int) ($gigs[$state] ?? 0),
                'professionals' => (int) ($pros[$state] ?? 0),
                'revenue'       => (float) ($revenue[$state] ?? 0),
            ])
            ->sortByDesc('revenue')
            ->values();

        /*
         * Whatever this table cannot place, shown rather than dropped.
         *
         * The state column arrived with R38, so anything raised before it —
         * or by an account that had no state at the time — has none. Without
         * this row the table quietly showed $4,000 under a headline of
         * $55,950 and looked like a bug in the report rather than a gap in
         * the data. A breakdown that does not add up to its own total is not
         * worth reading.
         */
        $unplaced = $this->money()['gross'] - $rows->sum('revenue');
        $statelessGigs = Event::whereBetween('created_at', [$this->from, $this->to])
            ->where('is_published', true)->whereNull('state')->count();

        if (round($unplaced, 2) > 0 || $statelessGigs > 0) {
            $rows->push([
                'state'         => 'Not recorded',
                'gigs'          => $statelessGigs,
                'professionals' => (int) DB::table('user_profiles')
                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'user_profiles.user_id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', 'professional')
                    ->where(fn ($q) => $q->whereNull('user_profiles.state')->orWhere('user_profiles.state', ''))
                    ->count(),
                'revenue'       => round(max(0, $unplaced), 2),
            ]);
        }

        return $rows;
    }

    /** Everything, for the page and for the CSV. */
    public function all(): array
    {
        return [
            'from'            => $this->from,
            'to'              => $this->to,
            'money'           => $this->money(),
            'marketplace'     => $this->marketplace(),
            'people'          => $this->people(),
            'needs_attention' => $this->needsAttention(),
            'by_state'        => $this->byState(),
        ];
    }
}
