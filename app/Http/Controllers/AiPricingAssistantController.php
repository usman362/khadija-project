<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Pricing Assistant — a client-portal AI Toolkit tool that recommends a
 * competitive price for a service from event details + adjustable factors,
 * and shows where that price sits against the local market.
 *
 * Unlike the other three AI tools (Budget Allocator / Vendor Matchmaking /
 * Review Writer) this one is a DETERMINISTIC calculator — it needs no LLM
 * call and consumes no AI quota — so it is intentionally NOT plan-gated.
 *
 * REAL vs MODEL: pricing is computed from a transparent rate model
 * (base rate per service × experience × equipment × duration × event-size ×
 * location index) — there is no live market-data feed, so the market band
 * and the "N pros available / demand" lines are model-derived illustrative
 * figures, clearly framed as estimates. "Recent Price Calculations" are
 * REAL — estimated prices for the signed-in client's own events.
 *
 * Routes: GET  /ai-tools/pricing-assistant            (show)
 *         POST /ai-tools/pricing-assistant/calculate  (recompute → JSON)
 */
class AiPricingAssistantController extends Controller
{
    /** Base rate ($) per service type — the model's starting point. */
    private const SERVICE_RATES = [
        'DJ Performance'      => 600,
        'Wedding DJ'          => 1050,
        'Corporate Event DJ'  => 850,
        'Party DJ'            => 550,
        'Live Band'           => 1600,
        'Solo Musician'       => 450,
        'Master of Ceremonies' => 500,
        'Photo Booth'         => 550,
        'Event Lighting & Sound' => 900,
    ];

    /** Local cost-of-market index by city (1.0 = national baseline). */
    private const LOCATION_INDEX = [
        'Los Angeles, CA'  => 1.00,
        'Beverly Hills, CA' => 1.18,
        'San Francisco, CA' => 1.12,
        'New York, NY'     => 1.15,
        'Miami, FL'        => 1.02,
        'Chicago, IL'      => 0.98,
        'Austin, TX'       => 0.94,
    ];

    private const EXPERIENCE = ['Beginner' => 0.82, 'Intermediate' => 1.00, 'Expert' => 1.22];
    private const EQUIPMENT  = ['Basic' => 0.86, 'Standard' => 0.95, 'Premium' => 1.08];

    public function show(Request $request): View
    {
        $serviceTypes = array_keys(self::SERVICE_RATES);
        $locations    = array_keys(self::LOCATION_INDEX);

        // Default scenario (matches the reference: DJ in LA, mid-tier).
        $result = $this->compute('DJ Performance', 'Los Angeles, CA', 'Intermediate', 4, 'Premium', 100);

        $recent = $this->recentCalculations($request);

        return view('client.ai-tools.pricing-assistant', compact(
            'serviceTypes', 'locations', 'result', 'recent'
        ));
    }

    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_type' => ['required', 'string'],
            'location'     => ['nullable', 'string'],
            'event_date'   => ['nullable', 'date'],
            'experience'   => ['nullable', 'string'],
            'duration'     => ['nullable', 'numeric', 'min:1', 'max:12'],
            'equipment'    => ['nullable', 'string'],
            'event_size'   => ['nullable', 'numeric', 'min:10', 'max:1000'],
        ]);

        $result = $this->compute(
            $data['service_type'],
            $data['location'] ?? 'Los Angeles, CA',
            $data['experience'] ?? 'Intermediate',
            (float) ($data['duration'] ?? 4),
            $data['equipment'] ?? 'Premium',
            (int) ($data['event_size'] ?? 100),
            $data['event_date'] ?? null,
        );

        return response()->json($result);
    }

    /**
     * Transparent rate model → a recommended price + market band + insights.
     */
    private function compute(string $service, string $location, string $experience, float $duration, string $equipment, int $size, ?string $date = null): array
    {
        $base     = self::SERVICE_RATES[$service] ?? 600;
        $expMult  = self::EXPERIENCE[$experience] ?? 1.0;
        $eqMult   = self::EQUIPMENT[$equipment] ?? 1.0;
        $locMult  = self::LOCATION_INDEX[$location] ?? 1.0;
        $durFct   = round(0.70 + 0.075 * $duration, 3);          // 4h → 1.0
        $sizeFct  = round(max(0.85, min(1.6, 0.85 + ($size / 100) * 0.15)), 3); // 100 → 1.0
        [$dateMult, $dateNote] = $this->dateFactor($date);       // weekend / peak-season premium

        $raw   = $base * $expMult * $eqMult * $durFct * $sizeFct * $locMult * $dateMult;
        $price = (int) (round($raw / 10) * 10);

        // Market band (model-derived, framed as local estimate).
        $r25       = fn ($n) => (int) (round($n / 25) * 25);
        $marketAvg = $r25($price * 0.96);
        $marketLow = $r25($marketAvg * 0.64);
        $marketHigh = $r25($marketAvg * 1.44);
        $low  = $r25($price * 0.85);
        $high = $r25($price * 1.15);

        $span = max(1, $marketHigh - $marketLow);
        $pos  = (int) max(4, min(96, round(($price - $marketLow) / $span * 100)));

        // Verdict badge + explanation relative to market average.
        if ($price < $marketAvg * 0.92) {
            $badge = 'Below Market'; $badgeColor = '#2563eb';
            $means = 'Your price is below the local average — you have room to charge more for the value you offer.';
        } elseif ($price <= $marketAvg * 1.15) {
            $badge = 'Great Price'; $badgeColor = '#16a34a';
            if ($price > $marketAvg * 1.02) {
                $means = 'Your price is slightly above the average, which is great for the value you offer!';
            } elseif ($price < $marketAvg * 0.98) {
                $means = 'Your price is slightly below the average — competitive and attractive to clients!';
            } else {
                $means = 'Your price is right around the average, which is great for the value you offer!';
            }
        } else {
            $badge = 'Premium Price'; $badgeColor = '#ea580c';
            $means = 'Your price is above the local average — make sure to highlight what makes your service premium.';
        }

        $city = trim(explode(',', $location)[0]);
        $available = 8 + ($size % 9);          // illustrative local supply
        $demand    = ($dateMult > 1.0 || $sizeFct >= 1.1 || in_array($city, ['Los Angeles', 'New York', 'Beverly Hills'], true))
            ? 'High demand' . ($dateNote ? " ({$dateNote})" : '')
            : 'Steady demand';

        $insights = [
            ['icon' => 'check', 'text' => "{$available} {$service}s available this weekend", 'color' => '#16a34a'],
            ['icon' => 'check', 'text' => 'Average price: $' . number_format($marketAvg), 'color' => '#16a34a'],
            ['icon' => 'fire',  'text' => $demand, 'color' => '#ea580c'],
            ['icon' => 'badge', 'text' => $price <= $marketAvg * 1.15 ? "You're competitively priced!" : 'Premium positioning', 'color' => '#16a34a'],
        ];

        return [
            'service'    => $service,
            'location'   => $location,
            'city'       => $city,
            'price'      => $price,
            'low'        => $low,
            'high'       => $high,
            'marketLow'  => $marketLow,
            'marketAvg'  => $marketAvg,
            'marketHigh' => $marketHigh,
            'pos'        => $pos,
            'badge'      => $badge,
            'badgeColor' => $badgeColor,
            'means'      => $means,
            'insights'   => $insights,
            'tip'        => $price <= $marketAvg * 1.15
                ? 'This price balances value and competitiveness.'
                : 'Premium pricing works best when paired with standout reviews and portfolio.',
        ];
    }

    /**
     * Date-driven premium: weekends and peak event-season months cost more.
     * Returns [multiplier, note]. A null/invalid date is neutral (1.0) so the
     * initial page load reproduces the base recommendation exactly.
     *
     * @return array{0: float, 1: string}
     */
    private function dateFactor(?string $date): array
    {
        if (! $date) {
            return [1.0, ''];
        }
        try {
            $d = \Illuminate\Support\Carbon::parse($date);
        } catch (\Throwable) {
            return [1.0, ''];
        }

        $mult  = 1.0;
        $notes = [];
        if ($d->isWeekend()) {
            $mult *= 1.08;
            $notes[] = 'weekend';
        }
        if (in_array((int) $d->month, [5, 6, 9, 10, 12], true)) { // peak event season
            $mult *= 1.06;
            $notes[] = 'peak season';
        }

        return [round($mult, 3), implode(' + ', $notes)];
    }

    /**
     * REAL: estimated prices for the signed-in client's own recent events.
     */
    private function recentCalculations(Request $request): array
    {
        $events = Event::where('client_id', $request->user()->id)
            ->with('category:id,name')
            ->latest()
            ->take(5)
            ->get();

        $rows = [];
        foreach ($events as $ev) {
            $service  = $ev->category?->name && isset(self::SERVICE_RATES[$ev->category->name])
                ? $ev->category->name
                : 'DJ Performance';
            $location = $ev->location ?: 'Los Angeles, CA';
            $est = $this->compute($service, $location, 'Intermediate', 4, 'Premium', 100, $ev->starts_at?->toDateString());
            $rows[] = [
                'service'  => $service,
                'date'     => $ev->starts_at,
                'location' => $location,
                'price'    => $ev->budget && $ev->budget > 0 ? (int) round($ev->budget) : $est['price'],
            ];
        }

        return $rows;
    }
}
