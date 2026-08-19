<?php

namespace App\Http\Controllers\Disputes;

use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputeStates;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Rule R34 Phase 2 — the screens both parties use.
 *
 * One controller for clients and professionals rather than two, because §1
 * settles it: "one internal workflow operates identically" — and the two
 * parties genuinely do the same things here. They file, they respond, they
 * submit evidence, they settle, they withdraw. Only the layout differs, and
 * the layout is one line.
 *
 * Two controllers would have been two places to forget a rule, and the rules
 * in this module are the kind that cost money when forgotten.
 */
class DisputeController extends Controller
{
    /** Which portal chrome to draw this in. */
    private function layout(User $user): string
    {
        return $user->isProfessionalMode() ? 'layouts.professional' : 'layouts.client';
    }

    /** The party's role for the state machine — never their platform role. */
    private function role(User $user, DisputeCase $case): string
    {
        return $user->id === $case->professional_id ? 'professional' : 'client';
    }

    /** §7 — a case belongs to its two parties. Nobody else opens it here. */
    private function authorizeParty(User $user, DisputeCase $case): void
    {
        abort_unless($case->isParty($user), 403);
    }

    /** The tabs, and what each one means in terms of the state machine. */
    public const TABS = [
        'all'      => 'All Cases',
        'action'   => 'Needs Your Action',
        'review'   => 'Under Review',
        'resolved' => 'Resolved',
        'closed'   => 'Closed',
    ];

    /** The date ranges the header offers, in days. Null = no limit. */
    public const RANGES = [
        'all'  => ['All Time', null],
        '30'   => ['Last 30 days', 30],
        '90'   => ['Last 90 days', 90],
        '365'  => ['Last 12 months', 365],
    ];

    /**
     * The six situations the "Common Issues" row offers, each a real taxonomy
     * key so the tile lands on the filing form with the classification already
     * chosen. "Other" opens the form with all twelve to pick from.
     */
    public const COMMON_ISSUES = [
        ['payment_dispute',    'Payment Dispute',    'Issues with payment, refunds, or extra charges.'],
        ['cancellation',       'Cancellation',       'Cancellation fees or last-minute changes.'],
        ['no_show',            'No-Show',            'Client or professional did not show up.'],
        ['incomplete_service', 'Incomplete Service',  'Service did not match the agreed scope.'],
        ['damage_claim',       'Damaged Property',   'Damage to equipment or venue.'],
        [null,                 'Other Issue',        'Something else not listed here.'],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $tab = array_key_exists((string) $request->query('tab'), self::TABS)
            ? (string) $request->query('tab') : 'all';
        $range = array_key_exists((string) $request->query('range'), self::RANGES)
            ? (string) $request->query('range') : 'all';
        $taxonomy = array_key_exists((string) $request->query('taxonomy'), DisputeClassification::TAXONOMY)
            ? (string) $request->query('taxonomy') : '';

        $mine = fn () => DisputeCase::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('professional_id', $user->id));

        /*
         * The tiles count states, and one of them — "Waiting on You" — depends
         * on which side of the case this person is, which is not a column. So
         * the party's own cases are loaded once and counted in PHP rather than
         * asked for four times in a way the database cannot answer.
         *
         * A person's own disputes are few by construction; this is not a feed.
         */
        $all = $mine()->with(['booking.event', 'client', 'professional'])->latest('id')->get();

        $counts = [
            'open'     => $all->filter->isOpen()->count(),
            'action'   => $all->filter(fn ($c) => $this->needsActionFrom($c, $user))->count(),
            'review'   => $all->whereIn('state', [DisputeStates::FORMAL_INVESTIGATION, DisputeStates::OUTSIDE_ESCALATION])->count(),
            // "Resolved" on the tile is explicitly scoped to the last 30 days,
            // so it counts that and not every decision ever made.
            'resolved' => $all->filter(fn ($c) => in_array($c->state, [DisputeStates::DECIDED, DisputeStates::CURE_PERIOD], true)
                && $c->updated_at?->gte(now()->subDays(30)))->count(),
        ];

        $shown = match ($tab) {
            'action'   => $all->filter(fn ($c) => $this->needsActionFrom($c, $user)),
            'review'   => $all->whereIn('state', [DisputeStates::FORMAL_INVESTIGATION, DisputeStates::OUTSIDE_ESCALATION]),
            'resolved' => $all->whereIn('state', [DisputeStates::DECIDED, DisputeStates::CURE_PERIOD]),
            'closed'   => $all->whereIn('state', [DisputeStates::CLOSED, DisputeStates::WITHDRAWN, DisputeStates::EXPIRED]),
            default    => $all,
        };

        if ($taxonomy !== '') {
            $shown = $shown->where('taxonomy', $taxonomy);
        }

        if (self::RANGES[$range][1] !== null) {
            $since = now()->subDays(self::RANGES[$range][1]);
            $shown = $shown->filter(fn ($c) => $c->created_at?->gte($since));
        }

        $shown = $shown->values();

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $cases = new \Illuminate\Pagination\LengthAwarePaginator(
            $shown->forPage($page, $perPage)->values(),
            $shown->count(),
            $perPage,
            $page,
            ['path' => route('disputes.index'), 'query' => $request->query()],
        );

        return view('disputes.index', [
            'layout'   => $this->layout($user),
            'cases'    => $cases,
            'counts'   => $counts,
            'tabs'     => self::TABS,
            'ranges'   => self::RANGES,
            'issues'   => self::COMMON_ISSUES,
            'taxonomy' => DisputeClassification::TAXONOMY,
            'filters'  => ['tab' => $tab, 'range' => $range, 'taxonomy' => $taxonomy],
            'viewer'   => $user->isProfessionalMode() ? 'professional' : 'client',
            'needsAction' => fn (DisputeCase $c) => $this->needsActionFrom($c, $user),
        ]);
    }

    /**
     * Is this case waiting on THIS person?
     *
     * Two situations, both of them the state machine saying so rather than a
     * guess: the case is awaiting a response and they are not the one who filed
     * it, or a decision put the case into a cure period and they are the
     * professional who has to do the curing. Everything else is waiting on the
     * other side or on the platform, and telling someone to act when there is
     * nothing to act on is worse than saying nothing.
     */
    private function needsActionFrom(DisputeCase $case, User $user): bool
    {
        if ($case->state === DisputeStates::AWAITING_RESPONSE) {
            return $case->filed_by !== $user->id;
        }

        if ($case->state === DisputeStates::CURE_PERIOD) {
            return $case->professional_id === $user->id;
        }

        return false;
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        // §6 — a case is filed against one service line. So the first thing
        // the form asks for is which booking, and the only bookings offered
        // are ones this person is actually part of.
        $bookings = Booking::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id))
            ->whereIn('status', ['confirmed', 'completed'])
            ->with(['event', 'client', 'supplier'])
            ->latest('id')
            ->get();

        return view('disputes.create', [
            'layout'   => $this->layout($user),
            'bookings' => $bookings,
            'taxonomy' => DisputeClassification::TAXONOMY,
            'filing'   => $user->isProfessionalMode() ? 'professional' : 'client',
            // Arrived from a "Common Issues" tile, which already asked what the
            // problem is — so the form does not ask again.
            'chosen'   => array_key_exists((string) $request->query('taxonomy'), DisputeClassification::TAXONOMY)
                ? (string) $request->query('taxonomy')
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'booking_id'       => ['required', 'integer', 'exists:bookings,id'],
            'taxonomy'         => ['required', 'string', 'in:' . implode(',', array_keys(DisputeClassification::TAXONOMY))],
            'summary'          => ['required', 'string', 'min:20', 'max:5000'],
            'work_performed'   => ['nullable', 'string', 'max:5000'],
            'attempted_direct' => ['required', 'in:yes,no'],
            'certify_truthful' => ['accepted'],
        ]);

        $booking = Booking::with('event')->findOrFail($data['booking_id']);

        abort_unless(
            in_array($user->id, [$booking->client_id, $booking->supplier_id], true),
            403,
        );

        // One open case per service line. A second one on the same booking is
        // not a duplicate to be classified later (§6 sets a higher bar for
        // that) — it is the same person filing twice on the same screen.
        $existing = DisputeCase::where('booking_id', $booking->id)
            ->whereNotIn('state', [DisputeStates::CLOSED, DisputeStates::EXPIRED, DisputeStates::WITHDRAWN])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'booking_id' => "There is already an open case on this booking ({$existing->reference}).",
            ]);
        }

        $case = DisputeCase::create([
            'booking_id'      => $booking->id,
            'category_id'     => $booking->event?->category_id,
            'filed_by'        => $user->id,
            'client_id'       => $booking->client_id,
            'professional_id' => $booking->supplier_id,

            // Intake classifies (§3). The filing party picks the subject, and
            // severity opens at the middle of the scale until a staff member
            // sets it — a form that let the filer choose "Fraud" would let
            // them route their own case past Direct Resolution.
            'severity'        => DisputeClassification::SEVERITY_QUALITY,
            'priority'        => 'normal',
            'taxonomy'        => $data['taxonomy'],
            'summary'         => $data['summary'],
        ]);

        $case->log('case_filed', $user, $this->role($user, $case), [
            'field' => 'state', 'new' => $case->state,
            'reason' => $data['attempted_direct'] === 'yes'
                ? 'Raised with the other party before filing.'
                : 'Not yet raised with the other party.',
        ]);

        if (! empty($data['work_performed'])) {
            $case->evidence()->create([
                'submitted_by' => $user->id,
                'kind'         => 'platform_timeline',
                'description'  => "What was delivered:\n" . $data['work_performed'],
            ]);
        }

        return redirect()
            ->route('disputes.show', $case)
            ->with('status', "Case {$case->reference} has been opened.");
    }

    public function show(Request $request, DisputeCase $case): View
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        $case->load(['booking.event', 'client', 'professional', 'decisions.decider', 'escalations']);

        return view('disputes.show', [
            'layout'    => $this->layout($user),
            'case'      => $case,
            'role'      => $this->role($user, $case),
            'evidence'  => $case->evidence()->with('submitter')->orderBy('id')->get(),

            // §7 — the parties see their case's history, not staff
            // deliberation. Internal notes never reach this page.
            'timeline'  => $case->events()->visibleToParties()->with('actor')->orderBy('id')->get(),
            'decision'  => $case->currentDecision(),
        ]);
    }

    public function addEvidence(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        abort_unless($case->isOpen(), 403, 'This case is closed.');

        $data = $request->validate([
            'kind'              => ['required', 'string', 'max:32'],
            'description'       => ['required', 'string', 'min:5', 'max:5000'],
            'supersedes'        => ['nullable', 'integer'],
            'certify_unaltered' => ['accepted'],
        ]);

        // Only their own earlier item may be replaced — §4 keeps a version
        // history of what a party submitted, not a way to edit the other side.
        if (! empty($data['supersedes'])) {
            abort_unless(
                $case->evidence()->where('id', $data['supersedes'])->where('submitted_by', $user->id)->exists(),
                403,
            );
        }

        $case->evidence()->create([
            'submitted_by' => $user->id,
            'kind'         => $data['kind'],
            'description'  => $data['description'],
            'supersedes'   => $data['supersedes'] ?? null,
        ]);

        $case->log('evidence_submitted', $user, $this->role($user, $case));

        return back()->with('status', 'Added.');
    }

    public function respond(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        abort_unless($case->isOpen(), 403, 'This case is closed.');

        $data = $request->validate([
            'position'         => ['required', 'string', 'min:20', 'max:5000'],
            'certify_truthful' => ['accepted'],
        ]);

        $role = $this->role($user, $case);

        $case->evidence()->create([
            'submitted_by' => $user->id,
            'kind'         => 'platform_timeline',
            'description'  => $data['position'],
        ]);

        $case->log('response_submitted', $user, $role, ['reason' => 'Response recorded.']);

        // A response moves Awaiting Response back into the conversation. From
        // anywhere else it is just a response — the state is not the point.
        if ($case->state === DisputeStates::AWAITING_RESPONSE) {
            $case->transitionTo(DisputeStates::DIRECT_RESOLUTION, $user, $role, 'Responded.');
        }

        return back()->with('status', 'Your response has been recorded.');
    }

    /** §2 Step 1 — either party asks for the platform review instead. */
    public function escalateToReview(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        $moved = $case->transitionTo(
            DisputeStates::FORMAL_INVESTIGATION,
            $user,
            $this->role($user, $case),
            $request->string('reason')->toString() ?: 'Direct resolution did not settle it.',
        );

        return back()->with(
            'status',
            $moved ? 'This case has gone to platform review.' : 'This case cannot move to review from where it is.',
        );
    }

    public function withdraw(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        // Only the party who filed may withdraw. The other side agreeing to
        // drop it is a settlement, and a settlement has two signatures.
        abort_unless($user->id === $case->filed_by, 403);

        $data = $request->validate([
            'reason'            => ['required', 'string', 'min:5', 'max:1000'],
            'acknowledge_final' => ['accepted'],
        ]);

        $moved = $case->transitionTo(
            DisputeStates::WITHDRAWN, $user, $this->role($user, $case), $data['reason'],
        );

        return $moved
            ? redirect()->route('disputes.index')->with('status', "Case {$case->reference} has been withdrawn.")
            : back()->with('status', 'This case can no longer be withdrawn.');
    }

    /** §2 Step 4 — the single post-decision step. */
    public function requestOutsideEscalation(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeParty($user, $case);

        $data = $request->validate([
            'grounds' => ['required', 'string', 'min:20', 'max:5000'],
            'acknowledge_no_internal_appeal' => ['accepted'],
        ]);

        $role = $this->role($user, $case);

        if (! $case->transitionTo(DisputeStates::OUTSIDE_ESCALATION, $user, $role, $data['grounds'])) {
            return back()->with('status', 'Outside escalation is only available once a decision has been issued.');
        }

        $case->escalations()->create([
            'requested_by' => $user->id,
            'outcome_summary' => null,
        ]);

        return back()->with('status', 'Your request has been recorded and passed to our legal administrator.');
    }
}
