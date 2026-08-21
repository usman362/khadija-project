<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\ToolkitTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Choose Your Toolkit Power" — the three tiers from Rule R31, what each one
 * unlocks, and what it costs.
 *
 * Replaces a three-tab table that answered "what is in this tier" one tier at
 * a time. Choosing between three things means seeing three things, so the
 * tiers sit side by side and the comparison underneath shows every tool
 * against every tier at once.
 *
 * The same page serves both sides; the tools and the membership rules differ,
 * the tiers and the prices do not.
 */
class ToolkitTierController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        /*
         * One decision, not two.
         *
         * This page is reachable from both sidebars, and it used to pick the
         * audience here while the view hardcoded the client chrome. A
         * professional therefore got their own tools drawn inside the client's
         * portal — and the client sidebar has no link back to the
         * professional one, so the only way out was the browser's Back button.
         *
         * The audience and the chrome are the same question, so they are
         * answered once.
         */
        $isPro    = (bool) $user?->isProfessionalMode();
        $audience = $isPro ? ToolkitTiers::PROFESSIONAL : ToolkitTiers::CLIENT;
        $layout   = $isPro ? 'layouts.professional' : 'layouts.client';

        $tiers = config('toolkit-tiers.tiers', []);
        $total = ToolkitTiers::toolsFor($audience)->count();

        /*
         * Whether the toolkit is on sale at all.
         *
         * AI_FEATURES_FREE_FOR_ALL unlocks every tool for every account during
         * the launch period, and there is no checkout behind these prices yet.
         * So the cards state the tiers and the prices — they are decided (R31)
         * — but the button does what the app can actually do rather than
         * pretending to take a payment.
         */
        $everythingUnlocked = filter_var(env('AI_FEATURES_FREE_FOR_ALL', false), FILTER_VALIDATE_BOOLEAN)
            || (bool) $user?->isAdmin();

        $cards = collect($tiers)->map(fn ($label, $tier) => [
            'key'         => $tier,
            'label'       => $label,
            'tagline'     => match ($tier) {
                'manual'  => 'Basic Access',
                'semi'    => 'Essential Toolkit',
                default   => 'Complete Toolkit',
            },
            'price'       => ToolkitTiers::price($tier),
            'unlocked'    => ToolkitTiers::countFor($tier, $audience),
            'total'       => $total,
            'adds'        => ToolkitTiers::toolsAddedBy($tier, $audience),
            'purchasable' => in_array($tier, ToolkitTiers::purchasableBy($user), true),
        ])->values();

        return view('client.toolkit.tiers', [
            'tiers'      => $tiers,
            'audience'   => $audience,
            'layout'     => $layout,
            'cards'      => $cards,
            'suites'     => ToolkitTiers::comparison($audience),
            'total'      => $total,
            'difference' => ToolkitTiers::upgradeDifference(),
            'everythingUnlocked' => $everythingUnlocked,
            // Semi is the one recommended on the mockup, and it is the one a
            // client starting out needs.
            'recommended' => 'semi',
        ]);
    }
}
