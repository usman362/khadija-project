<?php

namespace App\Http\Controllers\Client;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Send a Direct Offer: a targeted, NON-bidding request to one specific
     * professional. Modelled as an Event assigned to that pro and NOT
     * published to the open Bidding Board — the pro accepts / declines / replies.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'event_name'      => ['nullable', 'string', 'max:200'],
            'event_date'      => ['nullable', 'date'],
            'guests'          => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'venue'           => ['nullable', 'string', 'max:200'],
            'services'        => ['nullable', 'array'],
            'services.*'      => ['integer', 'exists:categories,id'],
            'service_single'  => ['nullable', 'string', 'max:120'],
            'budget_min'      => ['nullable', 'integer', 'min:0'],
            'request_type'    => ['nullable', 'in:SSR,MSR,ESR'],
        ]);

        $user = $request->user();
        $pro  = User::findOrFail($data['professional_id']);

        $event = Event::create([
            'title'        => $data['event_name'] ?: ('Direct Offer to ' . $pro->name),
            'status'       => 'pending',
            'is_published' => false,               // targeted — never hits the open board
            'starts_at'    => $data['event_date'] ?? null,
            'budget'       => $data['budget_min'] ?? null,
            'location'     => $data['venue'] ?? null,
            'guest_count'  => $data['guests'] ?? null,
            'created_by'   => $user->id,
            'client_id'    => $user->id,
            'supplier_id'  => $pro->id,            // the invited professional
            'source'       => 'direct_offer',
        ]);

        // Attach requested services as categories.
        $categoryIds = collect($data['services'] ?? []);
        if ($categoryIds->isEmpty() && ! empty($data['service_single'])) {
            $categoryIds = Category::active()->where('name', $data['service_single'])
                ->limit(1)->pluck('id');
        }
        if ($categoryIds->isNotEmpty()) {
            $event->categories()->sync($categoryIds->all());
        }

        // Land on the offer itself, same as the other post flows.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', 'Direct offer sent to ' . $pro->name
                . '. Once they accept, the confirmed booking appears under Bookings.');
    }
}
