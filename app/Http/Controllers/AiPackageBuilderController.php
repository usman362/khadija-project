<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Package Builder (professional). Layer-3 aware: the SAME tool behaves
 * differently by the user's AI level (AiAccess):
 *   Manual   → plain builder form, no AI.
 *   Semi     → form + "✨ suggest" helpers the user approves.
 *   Maximum  → one click auto-generates the full tiered package.
 * Pricing/engine is deterministic (representative, no external API).
 */
class AiPackageBuilderController extends Controller
{
    public function show(Request $request): View
    {
        $user     = $request->user();
        $aiLayout = $user?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        $level = AiAccess::level($user, 'package-builder');

        // Admin demo override — ?preview=manual|semi|maximum lets you present all
        // three experiences without switching accounts. Admins only.
        if ($user?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        return view('ai-tools.package-builder', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
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
     * Level-aware compute. `action`:
     *   full        → Maximum: build the whole tiered package (needs Maximum).
     *   price       → Semi: suggest a competitive base price (needs Semi).
     *   description → Semi: rewrite / improve the description (needs Semi).
     *   addons      → Semi: suggest add-ons (needs Semi).
     */
    public function compute(Request $request): JsonResponse
    {
        $action = (string) $request->input('action', 'full');
        $user   = $request->user();

        // Layer 2/3 gating: Semi assists need Semi+, the full auto-build needs Maximum.
        $needed = $action === 'full' ? 'maximum' : 'semi';
        if (! AiAccess::can($user, 'package-builder', $needed)) {
            return response()->json([
                'success' => false,
                'message' => 'This AI action is not included in your current membership. Please upgrade to unlock it.',
            ], 403);
        }

        try {
            $result = match ($action) {
                'price'       => $this->suggestPrice($request),
                'description' => $this->improveDescription($request),
                'addons'      => $this->suggestAddons($request),
                default       => $this->buildFull($request),
            };
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'action' => $action, 'result' => $result]);
    }

    /** Maximum — build three priced tiers from name, base price and add-ons. */
    private function buildFull(Request $request): array
    {
        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:120'],
            'base_price'   => ['required', 'numeric', 'min:1', 'max:9999999'],
            'addons'       => ['nullable', 'string', 'max:600'],
        ]);

        $service = trim($validated['service_name']);
        $base    = (float) $validated['base_price'];

        $addons = collect(explode(',', (string) ($validated['addons'] ?? '')))
            ->map(fn ($a) => trim($a))->filter()->values()->all();

        $half = (int) ceil(count($addons) / 2);
        $signatureAddons = array_slice($addons, 0, $half);

        $core = [$service . ' — core service', 'Consultation & planning call', 'Online delivery'];
        $premiumExtras = ['Priority scheduling', 'Extended delivery & revisions', 'Dedicated point of contact'];

        $essentialPrice = round($base, 2);
        $signaturePrice = round($base * 1.6, 2);
        $premiumPrice   = round($base * 2.3, 2);

        $tiers = [
            ['name' => 'Essential', 'price' => $essentialPrice, 'includes' => $core],
            ['name' => 'Signature', 'price' => $signaturePrice, 'includes' => array_merge($core, $signatureAddons ?: ['Enhanced ' . strtolower($service) . ' package'])],
            ['name' => 'Premium',   'price' => $premiumPrice,   'includes' => array_merge($core, $addons, $premiumExtras)],
        ];

        $tips = [
            'Position Signature as your "most popular" option — a strong middle tier makes both the Essential and Premium prices feel more reasonable to clients.',
            count($addons) > 0
                ? 'You spread ' . count($addons) . ' add-on' . (count($addons) === 1 ? '' : 's') . ' across the tiers — consider offering the most-requested one as a standalone upsell too.'
                : 'Add a few comma-separated add-ons (e.g. "Extra hour, Second shooter, Prints") to differentiate the higher tiers more clearly.',
            'These prices are estimates based on a common 1.6× / 2.3× tier spread — adjust them to reflect your real costs and local market.',
        ];

        return [
            'summary' => 'Three estimated tiers for "' . $service . '": Essential at $' . number_format($essentialPrice, 0) . ', Signature at $' . number_format($signaturePrice, 0) . ', and Premium at $' . number_format($premiumPrice, 0) . '. Prices are suggestions you can fine-tune.',
            'tiers'   => $tiers,
            'tips'    => $tips,
        ];
    }

    /** Semi — suggest a competitive base price from the service keyword. */
    private function suggestPrice(Request $request): array
    {
        $validated = $request->validate(['service_name' => ['required', 'string', 'max:120']]);
        $name = strtolower(trim($validated['service_name']));

        // Representative base rates by keyword; deterministic fallback otherwise.
        $rates = [
            'photograph' => 1250, 'video' => 1500, 'cinema' => 1800, 'caterer' => 45,
            'catering' => 45, 'dj' => 900, 'music' => 900, 'floral' => 800, 'decor' => 800,
            'planner' => 2500, 'planning' => 2500, 'venue' => 3500, 'light' => 1200, 'makeup' => 350,
        ];
        $price = 1200;
        foreach ($rates as $kw => $val) {
            if (str_contains($name, $kw)) { $price = $val; break; }
        }
        // small deterministic nudge so it doesn't look hardcoded
        $price += (strlen($name) % 5) * 25;

        return [
            'price' => $price,
            'note'  => 'Suggested base price ≈ $' . number_format($price) . ' from typical market rates for "' . trim($validated['service_name']) . '". Adjust to your real costs.',
        ];
    }

    /** Semi — improve / expand the package description. */
    private function improveDescription(Request $request): array
    {
        $validated = $request->validate([
            'service_name' => ['nullable', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:2000'],
        ]);
        $service = trim((string) ($validated['service_name'] ?? 'your service')) ?: 'your service';
        $draft   = trim((string) ($validated['description'] ?? ''));

        $opener = $draft !== ''
            ? rtrim($draft, '. ') . '. '
            : 'Professional ' . strtolower($service) . ' tailored to your event. ';

        $improved = $opener
            . 'Every booking includes a planning consultation, clear deliverables and on-time delivery, '
            . 'so you know exactly what to expect. Choose the tier that fits your event size and budget — '
            . 'each one is designed to give you standout results without surprises.';

        return [
            'description' => $improved,
            'note'        => 'Rewritten for clarity and appeal — review and edit before saving.',
        ];
    }

    /** Semi — suggest add-ons for the service. */
    private function suggestAddons(Request $request): array
    {
        $validated = $request->validate(['service_name' => ['required', 'string', 'max:120']]);
        $name = strtolower(trim($validated['service_name']));

        $map = [
            'photograph' => ['Extra hour', 'Second shooter', 'Engagement session', 'Prints', 'Album', 'Drone coverage'],
            'video'      => ['Highlight reel', 'Drone coverage', 'Same-day teaser', 'Extra hour', 'Raw footage'],
            'cater'      => ['Extra course', 'Dessert station', 'Bar service', 'Waitstaff', 'Dietary menu'],
            'dj'         => ['Extra hour', 'Uplighting', 'MC service', 'Fog machine', 'Ceremony sound'],
            'floral'     => ['Ceremony arch', 'Centerpieces', 'Bridal bouquet', 'Setup & teardown'],
            'planner'    => ['Day-of coordination', 'Vendor sourcing', 'Timeline build', 'Rehearsal management'],
        ];
        $addons = ['Extra hour', 'Premium add-on', 'Priority scheduling', 'Extended revisions'];
        foreach ($map as $kw => $list) {
            if (str_contains($name, $kw)) { $addons = $list; break; }
        }

        return [
            'addons' => implode(', ', $addons),
            'list'   => $addons,
            'note'   => 'Common add-ons for "' . trim($validated['service_name']) . '" — keep the ones that fit.',
        ];
    }
}
