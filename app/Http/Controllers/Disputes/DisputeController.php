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

    public function index(Request $request): View
    {
        $user = $request->user();

        $cases = DisputeCase::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('professional_id', $user->id))
            ->with(['booking.event', 'client', 'professional'])
            ->latest('id')
            ->paginate(15);

        return view('disputes.index', [
            'layout' => $this->layout($user),
            'cases'  => $cases,
        ]);
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
