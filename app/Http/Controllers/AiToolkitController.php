<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiToolCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Toolkit hub — the single screen that shows a user every AI tool meant for
 * them (their own + the shared "both" tools), driven entirely by AiToolCatalog.
 * Live tools link to their pages; planned tools show their purpose + "Coming
 * soon". This makes Peter's per-user AI-tools matrix visible and usable now,
 * while the planned tools are built out one by one.
 */
class AiToolkitController extends Controller
{
    public function index(Request $request): View
    {
        $isPro    = (bool) $request->user()?->hasRole('supplier');
        $audience = $isPro ? 'professional' : 'client';

        $tools  = AiToolCatalog::forAudience($audience);
        $suites = AiToolCatalog::groupedForAudience($audience);

        return view('ai-tools.index', [
            'suites'    => $suites,
            'aiLayout'  => $isPro ? 'layouts.professional' : 'layouts.client',
            'isPro'     => $isPro,
            'toolCount' => count($tools),
            'liveCount' => count(AiToolCatalog::live($tools)),
        ]);
    }
}
