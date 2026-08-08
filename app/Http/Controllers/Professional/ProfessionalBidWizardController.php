<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Event;
use App\Support\Commission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Professional — Submit Your Bid.
 *
 * The board's inline bid form takes an amount and a note. That is enough to
 * place a number, but the client's Compare screen asks them to weigh "the full
 * scope, terms and qualifications, not only price" — so this collects the rest:
 * an itemised price, availability, the delivery plan, and terms.
 *
 * State lives in the session per opportunity, so a half-written bid never
 * reaches the client. "Save draft" writes a real Bid with status `draft` and
 * no `submitted_at`; only the last step submits it.
 */
class ProfessionalBidWizardController extends Controller
{
    public const STEPS = [
        'price'        => 'Price',
        'availability' => 'Availability',
        'plan'         => 'Service Plan',
        'terms'        => 'Timeline & Terms',
        'files'        => 'Files',
        'review'       => 'Review & Submit',
    ];

    private function key(Event $event): string
    {
        return 'bid_wizard.' . $event->id;
    }

    public function show(Request $request, Event $event, string $step = 'price'): View|RedirectResponse
    {
        if (! array_key_exists($step, self::STEPS)) {
            return redirect()->route('professional.bid.step', [$event, 'price']);
        }

        $user = $request->user();
        $this->assertBiddable($request, $event);

        $data  = (array) Session::get($this->key($event), []);
        $index = array_search($step, array_keys(self::STEPS), true);

        // An existing bid seeds the wizard — editing a bid before the deadline
        // is normal, and re-typing everything would be the wrong default.
        $existing = Bid::where('event_id', $event->id)->where('supplier_id', $user->id)->first();
        if ($existing && $data === []) {
            $data = [
                'bid_id'              => $existing->id,
                'amount'              => $existing->amount,
                'breakdown'           => $existing->breakdown ?: [],
                'above_budget_reason' => $existing->above_budget_reason,
                'available_confirmed' => (bool) $existing->available_confirmed,
                'availability_note'   => $existing->availability_note,
                'plan'                => $existing->plan,
                'terms'               => $existing->terms,
                'note'                => $existing->note,
                'is_public'           => (bool) $existing->is_public,
            ];
            Session::put($this->key($event), $data);
        }

        $furthest = $this->furthestAllowed($data, $event);
        if ($index > $furthest) {
            return redirect()->route('professional.bid.step', [$event, array_keys(self::STEPS)[$furthest]])
                ->withErrors(['step' => 'Finish this step first.']);
        }

        $amount = (int) ($data['amount'] ?? 0);
        $rate   = Commission::rateFor($user);

        return view('professional.bid.wizard', [
            'event'     => $event,
            'step'      => $step,
            'stepIndex' => $index,
            'steps'     => self::STEPS,
            'data'      => $data,
            'existing'  => $existing,
            'type'      => match ($event->source) { 'esr' => 'ER', 'direct_offer' => 'DR', default => 'BR' },
            'scope'     => $event->categories->count() >= 2 ? 'MSR' : 'SSR',
            'rate'      => $rate,
            'net'       => $amount > 0 ? Commission::netOf($amount, $user) : null,
            'eligibility' => $this->eligibility($request, $event),
        ]);
    }

    public function save(Request $request, Event $event, string $step): RedirectResponse
    {
        abort_unless(array_key_exists($step, self::STEPS), 404);
        $this->assertBiddable($request, $event);

        $data = (array) Session::get($this->key($event), []);

        $validated = $request->validate($this->rulesFor($step, $request, $event, $data), [
            'amount.required'   => 'Enter your bid amount.',
            'above_budget_reason.required' => 'Your bid is above the client’s range — explain the added value or cost.',
            'available_confirmed.accepted' => 'Confirm you are available on the event date.',
            'plan.required'     => 'Describe how you will deliver this.',
            'plan.min'          => 'A little more detail — this is what the client compares against price.',
            'sealed_ack.accepted' => 'Acknowledge that your bid is sealed before continuing.',
            'confirm.accepted'  => 'Confirm your proposal before submitting.',
        ]);

        // Line items arrive as parallel arrays; keep only complete rows.
        if ($step === 'price') {
            $labels = (array) $request->input('item_label', []);
            $costs  = (array) $request->input('item_cost', []);
            $items  = [];
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $cost  = (int) ($costs[$i] ?? 0);
                if ($label !== '' && $cost > 0) {
                    $items[] = ['label' => $label, 'cost' => $cost];
                }
            }
            $validated['breakdown'] = $items;
        }

        Session::put($this->key($event), array_merge($data, $validated));
        $data = (array) Session::get($this->key($event));

        if ($request->input('action') === 'draft') {
            $bid = $this->persist($request, $event, $data, submit: false);
            Session::put($this->key($event), array_merge($data, ['bid_id' => $bid->id]));

            return redirect()->route('professional.bid.step', [$event, $step])
                ->with('status', 'Draft saved. It stays private until you submit it.');
        }

        if ($step === 'review') {
            $bid = $this->persist($request, $event, $data, submit: true);
            Session::forget($this->key($event));

            return redirect()->route('professional.bidding-board.my-bids')->with('status',
                'Proposal submitted for "' . $event->title . '". It is sealed — only the client can see it.');
        }

        $keys = array_keys(self::STEPS);
        $next = $keys[min(array_search($step, $keys, true) + 1, count($keys) - 1)];

        return redirect()->route('professional.bid.step', [$event, $next]);
    }

    // ── internals ────────────────────────────────────────────────────

    /**
     * A professional may only bid on something actually open to them. This is
     * the same gate the board applies, enforced again here because a wizard URL
     * can be reached directly.
     */
    private function assertBiddable(Request $request, Event $event): void
    {
        $user = $request->user();

        // DSRs are targeted: only the named professional may respond.
        if ($event->source === 'direct_offer') {
            abort_unless((int) $event->supplier_id === (int) $user->id, 403);
        } else {
            abort_unless((bool) $event->is_published, 404);
        }

        abort_if(in_array($event->status, ['completed', 'cancelled'], true), 410,
            'This request is closed.');

        // Rule R38 — same-state only, and the ratification is explicit that
        // enforcement is server-side authoritative. The board already filters
        // the list, but a filtered list is a courtesy: this URL is reachable
        // directly, and this is where the rule actually holds.
        abort_unless(
            \App\Support\StateMatching::matches($event->state, \App\Support\StateMatching::stateOf($user))
                || ! \App\Support\StateMatching::appliesTo($user),
            403,
            'This request is in another state.'
        );

        // Past the deadline the request no longer accepts proposals.
        if ($event->proposal_deadline && $event->proposal_deadline->isPast()) {
            abort(410, 'The proposal deadline for this request has passed.');
        }
    }

    /** The rail's checks, all read from real data. */
    private function eligibility(Request $request, Event $event): array
    {
        $user = $request->user();
        $p    = $user->profile;

        $myCatIds = $user->serviceCategories->pluck('id');
        $wanted   = $event->categories->pluck('id');

        return [
            ['Service match', $myCatIds->intersect($wanted)->isNotEmpty() ? 'Eligible' : 'Not listed', $myCatIds->intersect($wanted)->isNotEmpty()],
            ['Service area', $p?->state ?: ($p?->city ?: 'Not set'), (bool) ($p?->state || $p?->city)],
            ['Verification', $p?->trade_license_verified_at ? 'Verified' : 'Not verified', (bool) $p?->trade_license_verified_at],
            ['Insurance / COI', \App\Support\InsuranceRequirement::isCovered($p) ? 'Verified' : (\App\Support\InsuranceRequirement::hasLapsed($p) ? 'Expired' : 'Not on file'), \App\Support\InsuranceRequirement::isCovered($p)],
        ];
    }

    private function rulesFor(string $step, Request $request, Event $event, array $data): array
    {
        $ceiling = $event->budget_max ?: $event->budget;

        return match ($step) {
            'price' => array_filter([
                'amount'     => ['required', 'integer', 'min:1', 'max:10000000'],
                'sealed_ack' => ['accepted'],
                // Only demanded when the bid actually exceeds the client's range.
                'above_budget_reason' => $ceiling && (int) $request->input('amount') > (float) $ceiling
                    ? ['required', 'string', 'min:10', 'max:1000']
                    : ['nullable', 'string', 'max:1000'],
            ]),
            'availability' => [
                'available_confirmed' => ['accepted'],
                'availability_note'   => ['nullable', 'string', 'max:600'],
            ],
            'plan'  => ['plan'  => ['required', 'string', 'min:20', 'max:4000']],
            'terms' => ['terms' => ['nullable', 'string', 'max:4000']],
            'files' => [],
            'review' => [
                'note'      => ['nullable', 'string', 'max:1000'],
                'is_public' => ['nullable', 'boolean'],
                'confirm'   => ['accepted'],
            ],
            default => [],
        };
    }

    private function furthestAllowed(array $d, Event $event): int
    {
        $ok = [
            ! empty($d['amount']),
            ! empty($d['available_confirmed']),
            ! empty($d['plan']),
            true,   // terms optional
            true,   // files optional
        ];

        foreach ($ok as $i => $passed) {
            if (! $passed) {
                return $i;
            }
        }

        return count($ok);
    }

    private function persist(Request $request, Event $event, array $d, bool $submit): Bid
    {
        $user = $request->user();

        $attrs = [
            'amount'              => (int) ($d['amount'] ?? 0),
            'breakdown'           => $d['breakdown'] ?? [],
            'above_budget_reason' => $d['above_budget_reason'] ?? null,
            'available_confirmed' => (bool) ($d['available_confirmed'] ?? false),
            'availability_note'   => $d['availability_note'] ?? null,
            'plan'                => $d['plan'] ?? null,
            'terms'               => $d['terms'] ?? null,
            'note'                => $d['note'] ?? null,
            // R8: making a bid public is one-way. Once true it stays true, no
            // matter what a later submission says.
            'is_public'           => (bool) ($d['is_public'] ?? false)
                                      || (bool) Bid::where('event_id', $event->id)
                                            ->where('supplier_id', $user->id)->value('is_public'),
            'status'              => $submit ? 'submitted' : 'draft',
            'submitted_at'        => $submit ? now() : null,
        ];

        // One bid per professional per request — the reminder on the board says
        // so, and this is what makes it true.
        return Bid::updateOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $user->id],
            $attrs + ['category_id' => $event->categories->first()?->id]
        );
    }
}
