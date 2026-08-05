<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\ToolkitTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "What does each toolkit tier unlock?" — the tab table from Rule R31.
 *
 * Replaces the pair of Semi/Maximum toggle buttons, which said which add-on
 * was active but never what it actually bought you.
 *
 * The same page serves both sides; the tools and the membership rules differ,
 * the tiers and prices do not.
 */
class ToolkitTierController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $audience = $user?->activeRole() === 'professional'
            ? ToolkitTiers::PROFESSIONAL
            : ToolkitTiers::CLIENT;

        $tiers = config('toolkit-tiers.tiers', []);
        $tab = $request->query('tier');
        $tab = array_key_exists($tab, $tiers) ? $tab : 'semi';

        return view('client.toolkit.tiers', [
            'tiers'       => $tiers,
            'tab'         => $tab,
            'audience'    => $audience,
            'rows'        => ToolkitTiers::table($tab, $audience),
            'counts'      => collect($tiers)->map(fn ($l, $t) => ToolkitTiers::countFor($t, $audience)),
            'prices'      => collect($tiers)->map(fn ($l, $t) => ToolkitTiers::price($t)),
            'purchasable' => ToolkitTiers::purchasableBy($user),
        ]);
    }
}
