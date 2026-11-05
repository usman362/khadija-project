<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Earnings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ProfessionalEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = Earnings::forProfessional($user);

        /*
         * What this professional's tier costs them, in their own money.
         *
         * Khadijah's 28 October Payments & Payouts screen carries this band
         * and the page had nothing like it: four totals and no mention of the
         * rate that produced them. Every figure here is this account's own
         * gross put through App\Support\Commission, so the band and the rows
         * below cannot report different arithmetic.
         */
        $gross  = (float) ($stats['gross'] ?? 0);
        $ladder = \App\Support\Commission::ladderFor($gross, $user);
        $mine   = collect($ladder)->firstWhere('current', true);
        $best   = collect($ladder)->sortBy('rate')->first();

        return view('professional.earnings.index', [
            // Same source as the Transactions page — the two used to disagree
            // with each other on the same account at the same moment.
            'stats'    => $stats,
            'earnings' => $this->loadEarnings($request),
            'search'   => (string) $request->query('search', ''),
            'tier'     => [
                'label'  => $mine['label'] ?? 'Starter',
                'rate'   => $mine['rate'] ?? \App\Support\Commission::DEFAULT_RATE,
                'gross'  => $gross,
                'fees'   => round($gross - ($mine['net'] ?? $gross), 2),
                'net'    => $mine['net'] ?? $gross,
                'ladder' => $ladder,
                // Already paying the least anyone pays. Separate from the
                // line below, because "no saving to show" on an account that
                // has earned nothing is not the same claim and said the
                // opposite of the truth on Starter, the dearest tier.
                'lowest' => $mine && $best && $best['slug'] === $mine['slug'],
                // Only worth a figure when there is money for it to be
                // measured on and a cheaper tier to measure against.
                'saving' => ($best && $mine && $best['slug'] !== $mine['slug'] && $gross > 0)
                    ? ['label' => $best['label'], 'amount' => round($best['net'] - $mine['net'], 2)]
                    : null,
            ],
        ]);
    }

    /**
     * The earning behind each booking. Shows the net figure, matching the
     * totals above — the gross is what the client paid, not what arrives.
     */
    private function loadEarnings(Request $request): LengthAwarePaginator
    {
        $user  = $request->user();
        $query = Booking::query()
            ->where('supplier_id', $user->id)
            ->with(['event:id,title', 'client:id,name']);

        if (($term = trim((string) $request->query('search', ''))) !== '') {
            $query->where(function ($q) use ($term) {
                $q->whereHas('event', fn ($e) => $e->where('title', 'like', "%{$term}%"))
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                  ->orWhere('status', 'like', "%{$term}%");
            });
        }

        return $query->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'date'        => ($b->booked_at ?? $b->created_at)?->format('M d, Y') ?? '—',
                'description' => trim(($b->event?->title ?? 'Booking')
                    . ($b->client?->name ? ' · ' . $b->client->name : '')),
                'gross'       => (float) $b->price,
                'net'         => \App\Support\Commission::netOf((float) $b->price, $user),
                'status'      => ucfirst((string) $b->status),
            ]);
    }
}
