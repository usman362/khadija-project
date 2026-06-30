<?php

namespace App\Http\Controllers\Client;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client → Professional Direct Offer / Request builder.
 *
 * The client sends a direct request to a chosen professional. The request type
 * reshapes the form (Peter's "minor changes to the documents per SSR/MSR/ESR"):
 *   • SSR — Single Service Request   (one service, no team)
 *   • MSR — Multiple Service Request (several services + team collaboration)
 *   • ESR — Event-wide Service Request (full event scope + full team)
 *
 * The professional-side receiving view already exists
 * (ProfessionalDirectOfferController). This is the sending side.
 */
class ClientDirectOfferController extends Controller
{
    public function create(Request $request): View
    {
        $pros = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->with(['profile'])
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->limit(20)->get();

        $categories  = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $selectedPro = $request->query('pro') ? $pros->firstWhere('id', (int) $request->query('pro')) : $pros->first();
        $type        = in_array($request->query('type'), ['SSR', 'MSR', 'ESR'], true) ? $request->query('type') : 'MSR';

        return view('client.direct-offers.create', compact('pros', 'categories', 'selectedPro', 'type'));
    }
}
