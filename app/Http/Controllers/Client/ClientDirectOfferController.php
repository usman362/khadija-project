<?php

namespace App\Http\Controllers\Client;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\StateMatching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Domain\Budget\ServiceBudgetWriter;

/**
 * Client → Professional Direct Offer / Request builder.
 *
 * The client sends a direct request to a chosen professional. The request type
 * reshapes the form (Peter's "minor changes to the documents per SSR/MSR"):
 *   • SSR — Single Service Request   (one service, no team)
 *   • MSR — Multiple Service Request (several services + team collaboration)
 *
 * The professional-side receiving view already exists
 * (ProfessionalDirectOfferController). This is the sending side.
 */
class ClientDirectOfferController extends Controller
{
    /**
     * Checklist row 193 — service first, then the professional.
     *
     * The form used to open with a dropdown of every professional in the
     * state, so a client could send a photography brief to a florist and only
     * find out when the florist declined it. Picking the service first means
     * the list can only contain people who do that work.
     *
     * Two ways in, both supported:
     *   ?service=<id>  the normal route — choose the work, then who does it.
     *   ?pro=<id>      "Hire This Professional" from a profile — that person
     *                  is fixed, and the services offered are only THEIRS.
     *
     * R6 still holds: a Direct Offer caps at one professional per SERVICE, not
     * one service per offer. A client may send one professional several
     * services in a single offer, which is why services stays an array.
     */
    public function create(Request $request): View
    {
        $user      = $request->user();
        $serviceId = (int) $request->query('service', 0);
        $proId     = (int) $request->query('pro', 0);

        $pros = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->with(['profile', 'serviceCategories:id,name'])
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->tap(fn ($q) => StateMatching::scopeUsersForViewer($q, $user))
            // Only people who actually offer the chosen service. This is the
            // whole fix — the filter, not a warning after the fact.
            ->when($serviceId > 0, fn ($q) => $q->whereHas(
                'serviceCategories', fn ($c) => $c->where('categories.id', $serviceId),
            ))
            ->limit(20)->get();

        /*
         * Only a professional the client actually named.
         *
         * This used to fall back to `$pros->first()` — pick a service and the
         * form quietly addressed itself to whoever came back first. Two things
         * followed from that. The client could send a request to someone they
         * never chose, and because the Service section is hidden whenever a
         * professional is set, choosing a service made the service picker
         * disappear: there was no way back to change it.
         *
         * A professional arriving in the URL (from their profile page) is a
         * real choice and still fixes the form.
         */
        $selectedPro = $proId > 0
            ? User::with('serviceCategories:id,name')->find($proId)
            : null;

        // R38 and the service filter apply to a professional named in the URL
        // too — a link is not a bypass.
        if ($selectedPro && ! $pros->contains('id', $selectedPro->id)) {
            $stillValid = User::query()
                ->whereKey($selectedPro->id)
                ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
                ->tap(fn ($q) => StateMatching::scopeUsersForViewer($q, $user))
                ->exists();

            if (! $stillValid) {
                $selectedPro = null;
            }
        }

        // Arriving from a profile page fixes the professional, so the service
        // list narrows to what that person actually does. Offering them a
        // service they do not provide is the same bug from the other end.
        $categories = $selectedPro
            ? $selectedPro->serviceCategories()->select('categories.id', 'categories.name')->get()
            : Category::active()->bookableServices()
                ->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $type = in_array($request->query('type'), ['SSR', 'MSR'], true) ? $request->query('type') : 'SSR';

        $orgTypes = \App\Models\Event::ORGANIZATION_TYPES;

        return view('client.direct-offers.create', compact(
            'orgTypes',
            'pros', 'categories', 'selectedPro', 'type', 'serviceId',
        ));
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
            // Asked on every request form now (Peter, 2026-08-20).
            'organization_type' => ['required', 'in:' . implode(',', array_keys(\App\Models\Event::ORGANIZATION_TYPES))],
            'event_date'      => ['nullable', 'date'],
            'guests'          => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'venue'           => ['nullable', 'string', 'max:200'],
            'services'        => ['nullable', 'array'],
            'services.*'      => ['integer', 'exists:categories,id', new \App\Rules\BookableService],
            'service_single'  => ['nullable', 'string', 'max:120'],
            'budget_min'      => ['nullable', 'integer', 'min:0'],
            'request_type'    => ['nullable', 'in:SSR,MSR'],
        ], [
            'professional_id.required' => 'Choose which professional this request goes to.',
            'organization_type.required' => 'Tell us who the request is for.',
        ]);

        $user = $request->user();
        $pro  = User::findOrFail($data['professional_id']);

        // The dropdown no longer offers you to yourself, but the id arrives in
        // the request and an offer to yourself would create a booking where one
        // account is both parties — a contract with itself, and commission
        // taken on money that never moved.
        abort_if($pro->id === $user->id, 422, 'You cannot send a Direct Request to yourself.');

        /*
         * Row 193's actual bug, guarded at the point it matters.
         *
         * The filtered list is a courtesy; the ids arrive in the request and
         * a stale tab or a typed URL bypasses the form entirely. A florist
         * receiving a photography brief is the thing the row is about, so it
         * is refused here rather than left for them to decline.
         */
        $asked = collect($request->input('services', []))->map(fn ($id) => (int) $id)->filter();

        if ($asked->isNotEmpty()) {
            $offered = $pro->serviceCategories()->pluck('categories.id');
            $unknown = $asked->diff($offered);

            abort_unless(
                $unknown->isEmpty(),
                422,
                'That professional does not offer one of the services you selected.',
            );
        }

        // Rule R38 — same-state only, re-checked here because the id arrives
        // in the request and the filtered dropdown is only a courtesy. A
        // Direct Offer is the one route with no board in front of it, so this
        // is the sole gate between the two accounts.
        abort_unless(
            StateMatching::allows($user, $pro),
            422,
            'That professional works in a different state.'
        );

        // Ten requests a day — Khadijah's sheet, 29 Aug. A Direct Request
        // reaches one professional rather than the board, but it is still a
        // request being sent, and the sheet counts postings not broadcasts.
        \App\Support\UserLimit::hit('client-postings', $user, null, 'professional_id');

        $event = Event::create([
            'title'        => $data['event_name'] ?: ('Direct Request to ' . $pro->name),
            'organization_type' => $data['organization_type'],
            'status'       => 'pending',
            'is_published' => false,               // targeted — never hits the open board
            'starts_at'    => $data['event_date'] ?? null,
            'budget'       => $data['budget_min'] ?? null,
            'location'     => $data['venue'] ?? null,
            'guest_count'  => $data['guests'] ?? null,
            'created_by'   => $user->id,
            'client_id'    => $user->id,
            'supplier_id'  => $pro->id,            // the invited professional
            // R38 / R71 — a Direct Offer needs no state question: the client
            // chose this professional, and the gate above has already refused
            // any pair across a state line. So the work is where that person
            // is, and taking it from them keeps the offer and the rule
            // agreeing by construction rather than by coincidence.
            'state'        => StateMatching::stateOf($pro),
            'source'       => 'direct_offer',
        ]);

        // Attach requested services as categories.
        $categoryIds = collect($data['services'] ?? []);
        if ($categoryIds->isEmpty() && ! empty($data['service_single'])) {
            // bookableServices(): the name arrives as free text, and an event
            // type answering to it would be filed as the service requested.
            $categoryIds = Category::active()->bookableServices()
                ->where('name', $data['service_single'])
                ->limit(1)->pluck('id');
        }
        if ($categoryIds->isNotEmpty()) {
            $event->categories()->sync($categoryIds->all());
        }

        // Sir Peter, 2026-09-02: a DR naming several services says what each
        // one is worth, the same as a BR. The professional prices one service,
        // not the request, so a single total is the wrong figure to show them.
        ServiceBudgetWriter::save(
            $event,
            (array) $request->input('service_budgets', []),
            $categoryIds->all(),
        );

        // Land on the offer itself, same as the other post flows.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', 'Direct Request sent to ' . $pro->name
                . '. Once they accept, the confirmed booking appears under Bookings.');
    }
}
