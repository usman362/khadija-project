<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Emergency Service Request (ESR) — a standalone "Post a Rush Request" flow
 * for time-sensitive needs within 72 hours (Peter's request-types spec: ESR
 * is its OWN flow, not an MSR link). It reuses the whole downstream engine:
 * an ESR publishes a real Event (source 'esr') that surfaces on the pro
 * Bidding Board with priority, then flows through bids → proposals → award →
 * booking → review exactly like SSR/MSR.
 *
 * Fees (per counsel): $2.99 posting at post; $8.99 ESR service fee only on a
 * finalized agreement — never on deals that fall through.
 */
class ClientEsrController extends Controller
{
    /** Urgency reasons an ESR can cite. */
    public const REASONS = [
        'professional_cancelled' => 'A professional cancelled or is unavailable',
        'no_show'                => 'A professional did not arrive as scheduled',
        'last_minute'            => 'A last-minute service became necessary',
        'equipment_failure'      => 'Equipment or service failure / breakdown',
        'other'                  => 'Other urgent circumstance',
    ];

    public function create(Request $request): View
    {
        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('client.esr.create', [
            'categories' => $categories,
            'reasons'    => self::REASONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_name'   => ['required', 'string', 'max:200'],
            'reason'       => ['required', 'in:' . implode(',', array_keys(self::REASONS))],
            'needed_by'    => ['required', 'date'],
            'location'     => ['nullable', 'string', 'max:200'],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'budget_min'   => ['nullable', 'integer', 'min:0'],
            'services'     => ['required', 'array', 'min:1'],
            'services.*'   => ['integer', 'exists:categories,id'],
        ], [
            'services.required' => 'Select at least one service you need.',
            'reason.required'   => 'Tell us why this is urgent.',
            'needed_by.required' => 'When do you need this by?',
        ]);

        $user = $request->user();

        $event = Event::create([
            'title'        => $data['event_name'],
            'description'  => $data['description'] ?? null,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => $data['needed_by'],
            'budget'       => $data['budget_min'] ?? null,
            'location'     => $data['location'] ?? null,
            'guest_count'  => $data['guest_count'] ?? null,
            'created_by'   => $user->id,
            'client_id'    => $user->id,
            'source'       => 'esr',   // marks it urgent on the Bidding Board
        ]);

        $event->categories()->sync(collect($data['services'])->unique()->all());

        // Land on the request itself; responses show up under Proposals.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', 'Rush request published. Verified professionals are being notified now — responses will appear under Proposals.');
    }
}
