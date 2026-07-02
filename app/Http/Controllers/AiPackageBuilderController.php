<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Package Builder (professional). Builds, prices and compares tiered service
 * packages (Bronze → Platinum) with profit margins and add-ons. Representative.
 */
class AiPackageBuilderController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.package-builder', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Avg Profit Margin', '58%', 'good'], ['Market Competitiveness', '94%', 'good'],
                ['Active Packages', '4', ''], ['Bundle Revenue', '$18,650', 'good'],
            ],
            'tiers' => [
                ['Bronze', 1250, '52%', '8 hrs', '#b08d57', false,
                    ['Wedding day photography', '200 edited photos', 'Online gallery', '1 photographer'],
                    ['+ Extra hour — $150', '+ Prints — $200']],
                ['Silver', 1850, '58%', '10 hrs', '#8b95a5', true,
                    ['Everything in Bronze', 'Engagement session', '400 edited photos', '2 photographers', 'Print release'],
                    ['+ Album — $300', '+ Drone — $250']],
                ['Gold', 2550, '61%', '12 hrs', '#c9a227', false,
                    ['Everything in Silver', 'Second shooter', '600 edited photos', 'Premium album', 'Drone coverage'],
                    ['+ Videography — $800']],
                ['Platinum', 3750, '64%', '16 hrs', '#3b3f4a', false,
                    ['Everything in Gold', 'Cinematic videography', 'Unlimited edited photos', 'Luxury album', 'Same-day teaser'],
                    ['Fully bespoke']],
            ],
            'compare' => [
                ['Hours of coverage', ['8 hrs', '10 hrs', '12 hrs', '16 hrs']],
                ['Photographers', ['1', '2', '2 + 2nd shooter', '3 + video']],
                ['Edited photos', ['200', '400', '600', 'Unlimited']],
                ['Engagement session', ['—', '✓', '✓', '✓']],
                ['Album', ['—', 'Add-on', 'Premium', 'Luxury']],
                ['Videography', ['—', '—', 'Add-on', '✓']],
            ],
            'suggestions' => [
                'Your Silver tier converts best — feature it as “Most Popular”.',
                'Add a Same-Day Teaser to Gold (+$400) — high demand, low effort.',
                'Bronze margin is thin — trim 1 hour or raise to $1,350.',
            ],
        ]);
    }

    /**
     * Build three priced tiers from a service name, base price and optional
     * add-ons. Deterministic pricing and add-on distribution — no external API.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:120'],
            'base_price'   => ['required', 'numeric', 'min:1', 'max:9999999'],
            'addons'       => ['nullable', 'string', 'max:600'],
        ]);

        try {
            $service = trim($validated['service_name']);
            $base    = (float) $validated['base_price'];

            // Parse comma-separated add-ons into a clean list.
            $addons = collect(explode(',', (string) ($validated['addons'] ?? '')))
                ->map(fn ($a) => trim($a))
                ->filter()
                ->values()
                ->all();

            // Split add-ons: first half -> Signature, all -> Premium.
            $half = (int) ceil(count($addons) / 2);
            $signatureAddons = array_slice($addons, 0, $half);

            // Core deliverables always present in the entry tier.
            $core = [
                $service . ' — core service',
                'Consultation & planning call',
                'Online delivery',
            ];

            // Premium extras layered on top of every add-on.
            $premiumExtras = [
                'Priority scheduling',
                'Extended delivery & revisions',
                'Dedicated point of contact',
            ];

            $essentialPrice = round($base, 2);
            $signaturePrice = round($base * 1.6, 2);
            $premiumPrice   = round($base * 2.3, 2);

            $tiers = [
                [
                    'name'     => 'Essential',
                    'price'    => $essentialPrice,
                    'includes' => $core,
                ],
                [
                    'name'     => 'Signature',
                    'price'    => $signaturePrice,
                    'includes' => array_merge(
                        $core,
                        $signatureAddons ?: ['Enhanced ' . strtolower($service) . ' package']
                    ),
                ],
                [
                    'name'     => 'Premium',
                    'price'    => $premiumPrice,
                    'includes' => array_merge($core, $addons, $premiumExtras),
                ],
            ];

            $tips = [
                'Position Signature as your "most popular" option — a strong middle tier makes both the Essential and Premium prices feel more reasonable to clients.',
                count($addons) > 0
                    ? 'You spread ' . count($addons) . ' add-on' . (count($addons) === 1 ? '' : 's') . ' across the tiers — consider offering the most-requested one as a standalone upsell too.'
                    : 'Add a few comma-separated add-ons (e.g. "Extra hour, Second shooter, Prints") to differentiate the higher tiers more clearly.',
                'These prices are estimates based on a common 1.6× / 2.3× tier spread — adjust them to reflect your real costs and local market.',
            ];

            $result = [
                'summary' => 'Three estimated tiers for "' . $service . '": Essential at $' . number_format($essentialPrice, 0) . ', Signature at $' . number_format($signaturePrice, 0) . ', and Premium at $' . number_format($premiumPrice, 0) . '. Prices are suggestions you can fine-tune.',
                'tiers'   => $tiers,
                'tips'    => $tips,
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
}
