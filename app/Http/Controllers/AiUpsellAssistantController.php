<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Upsell Assistant (professional). Spots add-ons & package upgrades per
 * booking and suggests them at the right moment. Representative data.
 */
class AiUpsellAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'upsell-assistant');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        return view('ai-tools.upsell-assistant', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
            'stats' => [
                ['Upsell Opportunities', '6', ''], ['Potential Extra', '+$2,150', 'good'],
                ['Acceptance Rate', '64%', 'good'], ['Avg Order Uplift', '+18%', 'good'],
            ],
            'booking' => ['client' => 'Sarah Johnson', 'event' => 'Wedding Reception', 'package' => 'Silver — $1,850', 'guests' => '150 guests · 6 hrs'],
            'addons' => [
                ['Engagement Photo Session', 450, 82, 'High fit'],
                ['Highlight Film (3–5 min)', 799, 71, 'Trending'],
                ['Extra Hour of Coverage', 300, 64, 'Common'],
                ['Premium Album Upgrade', 350, 58, ''],
            ],
            'moment' => 'Best moment to offer: right after they accept the base proposal — acceptance is 3× higher than offering later.',
        ]);
    }

    /**
     * Compute relevant upsell add-ons and a bundle from a booked service,
     * event type and package price. Deterministic rules map — no external
     * services. All figures are estimates the pro can adjust.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booked_service' => ['required', 'string', 'max:120'],
            'event_type'     => ['required', 'string', 'max:120'],
            'package_price'  => ['required', 'numeric', 'min:1', 'max:9999999'],
        ]);

        try {
            $service = trim($validated['booked_service']);
            $eventType = trim($validated['event_type']);
            $price = (float) $validated['package_price'];

            $addons = $this->upsellsFor($service, $eventType, $price);

            // Bundle: take the top 3 (or all if fewer) and apply a small discount.
            $topItems = array_slice($addons, 0, min(3, count($addons)));
            $rawSum = array_sum(array_map(static fn ($a) => $a['price'], $topItems));

            $discountPct = 12; // small bundle discount
            $bundlePrice = (int) round($rawSum * (1 - $discountPct / 100));
            $saves = (int) round($rawSum - $bundlePrice);

            $revenueUpliftPct = $price > 0 ? round(($bundlePrice / $price) * 100, 1) : 0.0;

            $itemNames = array_map(static fn ($a) => $a['name'], $topItems);
            $namesList = $this->humanList($itemNames);

            $bundle = [
                'name'  => $eventType . ' Enhancement Bundle',
                'price' => $bundlePrice,
                'saves' => $saves,
            ];

            $script = "Thanks for booking your {$service} for your {$eventType}! A few clients in your situation loved adding {$namesList}. "
                . "I can bundle them for \${$bundlePrice} (you'd save \${$saves} vs. booking separately) — want me to add it to your proposal?";

            $summary = 'Based on a ' . $service . ' booking for a ' . $eventType . ' at $' . (int) round($price) . ', here are '
                . count($addons) . ' relevant add-on estimates. Bundling the top ' . count($topItems)
                . ' could add roughly ' . $revenueUpliftPct . '% to this booking. Prices are suggestions you can tailor.';

            $result = [
                'summary'            => $summary,
                'upsells'            => array_values($addons),
                'bundle'             => $bundle,
                'revenue_uplift_pct' => $revenueUpliftPct,
                'script'             => $script,
            ];
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
        ]);
    }

    /**
     * Rules map: pick add-ons relevant to the booked service, price them as a
     * sensible fraction of the package (or a floor), and explain the fit.
     *
     * @return array<int, array{name: string, price: int, why: string}>
     */
    private function upsellsFor(string $service, string $eventType, float $price): array
    {
        $s = strtolower($service);

        // Each entry: [name, fraction of package price, minimum floor, why]
        $catalog = [
            'photo' => [
                ['Extra Hour of Coverage', 0.16, 150, 'Captures more of the day; easiest yes for most clients.'],
                ['Second Shooter', 0.30, 250, 'Two angles at key moments — popular for larger events.'],
                ['Engagement / Pre-Event Session', 0.24, 200, 'Builds rapport before the day and adds deliverables.'],
                ['Premium Album', 0.20, 180, 'A tangible keepsake with a strong perceived value.'],
                ['Drone Coverage', 0.18, 160, 'Aerial establishing shots that stand out.'],
            ],
            'video' => [
                ['Highlight Film (3–5 min)', 0.32, 300, 'The most-shared deliverable — high emotional value.'],
                ['Extra Hour of Coverage', 0.16, 150, 'More footage means a richer final edit.'],
                ['Same-Day Edit', 0.28, 260, 'A short reel to play at the event itself.'],
                ['Raw Footage Delivery', 0.14, 120, 'Low effort for you, valued by keepsake-minded clients.'],
            ],
            'dj' => [
                ['Uplighting Package', 0.22, 200, 'Transforms the room ambiance for photos and mood.'],
                ['Photo Booth', 0.30, 250, 'Interactive add-on guests love; near-passive for you.'],
                ['Extra Hour of Music', 0.16, 120, 'Keeps the party going past the base package.'],
                ['MC / Hosting Service', 0.18, 150, 'Smooth announcements and flow for the event.'],
                ['Cold Sparks / Fog Effects', 0.20, 180, 'A high-impact moment for the first dance or entrance.'],
            ],
            'cater' => [
                ['Premium Bar Package', 0.28, 300, 'Higher margin and a noticeable guest experience lift.'],
                ['Late-Night Snacks', 0.18, 180, 'Keeps guests happy late — easy operational add.'],
                ['Additional Server', 0.14, 120, 'Faster service for larger guest counts.'],
                ['Dessert Station', 0.20, 200, 'A visual centerpiece with strong perceived value.'],
            ],
            'plan' => [
                ['Day-of Coordination', 0.35, 400, 'Removes stress on the event day — high-value peace of mind.'],
                ['Rehearsal Management', 0.18, 200, 'Ensures a smooth run-through the day before.'],
                ['RSVP & Guest Handling', 0.15, 150, 'Offloads admin many clients dread.'],
                ['Vendor Coordination', 0.22, 250, 'Single point of contact across all suppliers.'],
            ],
            'floral' => [
                ['Ceremony Arch Florals', 0.30, 250, 'A photo-focal centerpiece for the ceremony.'],
                ['Additional Centerpieces', 0.20, 180, 'Scales beautifully with guest table count.'],
                ['Bridal Party Bouquets', 0.22, 200, 'Coordinated look across the party.'],
                ['Floral Installation / Backdrop', 0.28, 240, 'Statement piece for entrances and photos.'],
            ],
            'venue' => [
                ['Extended Rental Hours', 0.20, 200, 'More time for setup, event and teardown.'],
                ['Ceremony Space Add-On', 0.25, 250, 'One location for ceremony and reception.'],
                ['Premium Linens & Décor', 0.18, 160, 'Elevates the room without external vendors.'],
                ['On-Site Coordinator', 0.22, 220, 'Smooth logistics on the day.'],
            ],
        ];

        $keywordMap = [
            'photo'  => ['photo', 'photograph'],
            'video'  => ['video', 'film', 'cinema', 'videograph'],
            'dj'     => ['dj', 'music', 'band', 'entertain', 'sound'],
            'cater'  => ['cater', 'food', 'chef', 'bar'],
            'plan'   => ['plan', 'coordinat', 'organiz'],
            'floral' => ['floral', 'flower', 'florist'],
            'venue'  => ['venue', 'hall', 'space', 'location'],
        ];

        $matchedKey = null;
        foreach ($keywordMap as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($s, $kw)) {
                    $matchedKey = $key;
                    break 2;
                }
            }
        }

        if ($matchedKey !== null) {
            $items = $catalog[$matchedKey];
        } else {
            // Generic event add-ons for unknown services.
            $items = [
                ['Extended Hours', 0.18, 150, 'More time for your ' . $eventType . ' — a common ask.'],
                ['Premium Upgrade', 0.24, 200, 'A higher tier of your core service for discerning clients.'],
                ['Add-On Consultation / Planning', 0.15, 120, 'Extra guidance clients value ahead of the event.'],
                ['On-Site Assistant', 0.16, 130, 'Extra hands to keep the ' . $eventType . ' running smoothly.'],
            ];
        }

        $result = [];
        foreach ($items as [$name, $fraction, $floor, $why]) {
            $computed = (int) round($price * $fraction);
            $result[] = [
                'name'  => $name,
                'price' => max($floor, $computed),
                'why'   => $why,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, string> $items
     */
    private function humanList(array $items): string
    {
        $items = array_values(array_filter($items));
        $count = count($items);

        if ($count === 0) {
            return 'a few add-ons';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0] . ' and ' . $items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ' and ' . $last;
    }
}
