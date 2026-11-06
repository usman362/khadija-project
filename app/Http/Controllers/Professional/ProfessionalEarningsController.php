<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Commission;
use App\Support\Earnings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Professional → Payments & Payouts.
 *
 * Khadijah's 28 October screen. Four totals, a band explaining the tier that
 * produced them, and one row per transaction that opens into the arithmetic
 * behind its own figures.
 *
 * Two things on her screen are not on this one, because the platform does not
 * do them and a money screen is the last place to imply otherwise:
 *
 *   Automatic payouts  payouts here are requested, not scheduled. The button
 *                      asks for one, and the line under it says so.
 *
 *   Estimated payout   nothing schedules a next payout, so there is no date
 *   dates              to estimate. A payout that has happened has its real
 *                      date; everything else says it is not scheduled.
 */
class ProfessionalEarningsController extends Controller
{
    /** The windows the date picker offers, in days. */
    private const WINDOWS = [
        '30'  => 'Last 30 Days',
        '90'  => 'Last 90 Days',
        '365' => 'Last 12 Months',
        'all' => 'All Time',
    ];

    public function index(Request $request): View
    {
        $user  = $request->user();
        $stats = Earnings::forProfessional($user);

        [$from, $to, $window] = $this->window($request);

        /*
         * What this professional's tier costs them, in their own money.
         *
         * Every figure here is this account's own gross put through
         * App\Support\Commission, so the band and the rows below cannot
         * report different arithmetic.
         */
        $gross  = (float) ($stats['gross'] ?? 0);
        $ladder = Commission::ladderFor($gross, $user);
        $mine   = collect($ladder)->firstWhere('current', true);
        $best   = collect($ladder)->sortBy('rate')->first();

        $sub    = $user->activeSubscription();
        $period = Earnings::window($user, $from, $to);

        // The same ninety days seen from the cheapest tier, which is the
        // comparison her "Next Tier" panel makes.
        $wouldPay = $best ? round($period['gross'] * ($best['rate'] / 100), 2) : null;

        return view('professional.earnings.index', [
            // Same source as the Transactions page and the payout guard — the
            // three used to disagree with each other on the same account at
            // the same moment.
            'stats'        => $stats,
            'transactions' => $this->loadTransactions($request),
            'filters'      => $this->filters($request),
            'windows'      => self::WINDOWS,
            'window'       => $window,
            'sort'         => $this->sort($request),
            'types'        => Booking::where('supplier_id', $user->id)
                ->with('event:id,event_type')
                ->get()
                ->map(fn (Booking $b) => $b->event?->event_type)
                ->filter()->unique()->sort()->values()->all(),
            'tier'         => [
                'label'  => $mine['label'] ?? 'Starter',
                'rate'   => $mine['rate'] ?? Commission::DEFAULT_RATE,
                'since'  => $sub?->starts_at ?? $sub?->created_at,
                'active' => $sub !== null,
                'gross'  => $gross,
                'fees'   => round($gross - ($mine['net'] ?? $gross), 2),
                'net'    => $mine['net'] ?? $gross,
                'ladder' => $ladder,
                'period' => $period,
                // Already paying the least anyone pays. Separate from the
                // line below, because "no saving to show" on an account that
                // has earned nothing is not the same claim and said the
                // opposite of the truth on Starter, the dearest tier.
                'lowest' => $mine && $best && $best['slug'] === $mine['slug'],
                // Only worth a figure when there is money for it to be
                // measured on and a cheaper tier to measure against.
                'next'   => ($best && $mine && $best['slug'] !== $mine['slug'] && $period['gross'] > 0)
                    ? [
                        'label'    => $best['label'],
                        'rate'     => $best['rate'],
                        'wouldPay' => $wouldPay,
                        'save'     => round($period['fees'] - $wouldPay, 2),
                    ]
                    : null,
            ],
        ]);
    }

    /** The filters as they came in, each one defaulting to "everything". */
    private function filters(Request $request): array
    {
        return [
            'search'  => (string) $request->query('search', ''),
            'booking' => (string) $request->query('booking', ''),
            'payment' => (string) $request->query('payment', ''),
            'type'    => (string) $request->query('type', ''),
            'from'    => (string) $request->query('from', ''),
            'to'      => (string) $request->query('to', ''),
        ];
    }

    /** Which way the Date column is sorted right now. */
    private function sort(Request $request): string
    {
        return $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
    }

    /**
     * The window the tier band reports over.
     *
     * An explicit pair of dates wins; otherwise one of the named spans, with
     * ninety days the default because that is the span her screen names.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function window(Request $request): array
    {
        $filters = $this->filters($request);

        if ($filters['from'] !== '' || $filters['to'] !== '') {
            return [
                $filters['from'] !== '' ? Carbon::parse($filters['from'])->startOfDay() : Carbon::create(2000),
                $filters['to'] !== '' ? Carbon::parse($filters['to'])->endOfDay() : now(),
                'custom',
            ];
        }

        $window = (string) $request->query('window', '90');
        $window = array_key_exists($window, self::WINDOWS) ? $window : '90';

        return [
            $window === 'all' ? Carbon::create(2000) : now()->subDays((int) $window),
            now(),
            $window,
        ];
    }

    /** This professional's bookings, each as one line of money. */
    private function loadTransactions(Request $request): LengthAwarePaginator
    {
        return $this->query($request)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $b) => Earnings::transaction($b, $request->user()));
    }

    /** The filtered set, shared by the table and the export. */
    private function query(Request $request)
    {
        $user    = $request->user();
        $filters = $this->filters($request);

        $query = Booking::query()
            ->where('supplier_id', $user->id)
            // The row names the event type and the service beside it, so the
            // services come along rather than one query per row.
            ->with(['event:id,title,event_type', 'event.categories:id,name',
                    'client:id,name', 'ledgerEntries']);

        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('event', fn ($e) => $e->where('title', 'like', "%{$term}%"))
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                  ->orWhere('bookings.status', 'like', "%{$term}%");
            });
        }

        if ($filters['booking'] !== '') {
            $query->where('bookings.status', $filters['booking']);
        }

        if ($filters['type'] !== '') {
            $query->whereHas('event', fn ($e) => $e->where('event_type', $filters['type']));
        }

        /*
         * Payment status is worked out per booking rather than stored, so it
         * cannot be filtered in SQL. The three groups each map onto something
         * the query can ask for, which keeps the filter honest without
         * loading every booking to sort them.
         */
        match ($filters['payment']) {
            'paid'       => $query->whereHas('ledgerEntries', fn ($l) => $l
                ->whereIn('entry_type', ['payout', 'milestone_release'])->where('state', 'released')),
            'pending'    => $query->whereIn('bookings.status', ['completed', 'confirmed', 'in_progress', 'requested'])
                ->whereDoesntHave('ledgerEntries', fn ($l) => $l
                    ->whereIn('entry_type', ['payout', 'milestone_release'])->where('state', 'released')),
            'not_earned' => $query->whereNotIn('bookings.status', ['completed']),
            default      => null,
        };

        if ($filters['from'] !== '') {
            $query->where('bookings.created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if ($filters['to'] !== '') {
            $query->where('bookings.created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        // Her Date heading carries sort arrows, so they have to sort.
        return $query->orderBy('bookings.created_at',
            $request->query('sort') === 'oldest' ? 'asc' : 'desc');
    }

    /** The transactions as they are filtered on screen, as a file. */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows, $user) {
            $out = fopen('php://output', 'wb');

            fputcsv($out, ['Date', 'Event', 'Client', 'Booking status', 'Payment status',
                           'Client paid', 'Cancellation adjustment', 'Amount fee charged on',
                           'Platform fee', 'Rate %', 'Net earnings', 'Paid out on']);

            foreach ($rows as $booking) {
                $t = Earnings::transaction($booking, $user);

                fputcsv($out, [
                    $t['date']?->format('Y-m-d'),
                    $booking->event?->title,
                    $booking->client?->name,
                    $booking->status,
                    $t['state'],
                    $t['price'],
                    $t['adjust'],
                    $t['basis'],
                    $t['fee'],
                    $t['rate'],
                    $t['net'],
                    $t['paid_at']?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, 'payments-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
