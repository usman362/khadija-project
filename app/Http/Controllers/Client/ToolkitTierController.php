<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\ToolkitTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Which tools does each add-on unlock?" — the tab table from Rule R31.
 *
 * Replaces the pair of Semi/Maximum toggle buttons, which only ever said
 * which add-on was active and never what it actually bought you.
 */
class ToolkitTierController extends Controller
{
    public function index(Request $request): View
    {
        $tiers = config('toolkit-tiers.tiers', []);

        $tab = $request->query('tier');
        $tab = array_key_exists($tab, $tiers) ? $tab : array_key_first($tiers);

        return view('client.toolkit.tiers', [
            'tiers'       => $tiers,
            'tab'         => $tab,
            'rows'        => ToolkitTiers::table($tab),
            'unconfirmed' => ToolkitTiers::unconfirmed(),
        ]);
    }
}
