<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Disputes\DecisionGuide;
use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputePermissions;
use App\Domain\Disputes\DisputeStates;
use App\Domain\Disputes\RepeatOffenderHistory;
use App\Http\Controllers\Controller;
use App\Models\DisputeCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rule R34 Phase 2 — the staff side: the queue, and one case.
 *
 * §6 gives the queue its order: priority first, then age. Severity is NOT the
 * sort key, and that is the architecture's point, not an oversight — a
 * high-value Level 2 quality dispute can legitimately outrank a Level 3
 * payment dispute, so sorting by severity would bury exactly the cases §3
 * separated priority out to surface.
 */
class AdminDisputeController extends Controller
{
    /**
     * Which dispute role this staff member holds ON THIS CASE.
     *
     * Read from the assignment, not from their platform role. R50's four
     * platform roles and R34's seven case roles are different things — a
     * Support agent is not an Investigator, and letting the platform role
     * stand in for the case role would hand a decision button to whoever
     * happened to open the page.
     *
     * An admin with no assignment falls back to super_admin: the break-glass
     * role, deliberately unrestricted, and the only way to work a case before
     * anyone has been assigned one.
     */
    private function roleOn(DisputeCase $case, User $user): string
    {
        $assignment = $case->assignments()
            ->where('staff_id', $user->id)
            ->whereNull('released_at')
            ->latest('id')
            ->first();

        return $assignment?->role
            ?? ($user->isAdmin() ? DisputePermissions::SUPER_ADMIN : 'client');
    }

    public function index(Request $request): View
    {
        $state    = (string) $request->query('state', 'open');
        $priority = (string) $request->query('priority', 'all');

        $query = DisputeCase::query()->with(['client', 'professional', 'booking.event', 'assignee']);

        if ($state === 'open') {
            $query->whereNotIn('state', [DisputeStates::CLOSED, DisputeStates::EXPIRED, DisputeStates::WITHDRAWN]);
        } elseif (array_key_exists($state, DisputeStates::LABELS)) {
            $query->where('state', $state);
        }

        if (array_key_exists($priority, DisputeClassification::PRIORITIES)) {
            $query->where('priority', $priority);
        }

        // Priority order, then oldest first. Written out rather than sorted in
        // SQL by a CASE expression so it reads the same on MySQL and SQLite —
        // this project has been bitten by MySQL-only SQL more than once.
        $cases = $query->get()
            ->sortBy([
                fn ($a, $b) => self::rank($a->priority) <=> self::rank($b->priority),
                fn ($a, $b) => $a->created_at <=> $b->created_at,
            ])
            ->values();

        return view('dashboard.admin.disputes.index', [
            'cases'      => $cases,
            'state'      => $state,
            'priority'   => $priority,
            'states'     => DisputeStates::LABELS,
            'priorities' => DisputeClassification::PRIORITIES,
            'counts'     => [
                'open'      => DisputeCase::whereNotIn('state', [DisputeStates::CLOSED, DisputeStates::EXPIRED, DisputeStates::WITHDRAWN])->count(),
                'unassigned'=> DisputeCase::whereNull('assigned_to')
                                ->whereNotIn('state', [DisputeStates::CLOSED, DisputeStates::EXPIRED, DisputeStates::WITHDRAWN])->count(),
                'critical'  => DisputeCase::where('priority', 'critical')
                                ->whereNotIn('state', [DisputeStates::CLOSED, DisputeStates::EXPIRED, DisputeStates::WITHDRAWN])->count(),
            ],
        ]);
    }

    /**
     * Position in DisputeClassification::PRIORITIES — critical first.
     *
     * An unrecognised priority sorts LAST, not first. `?:` would have made it
     * 0 and pushed junk data to the top of the queue, which is the wrong way
     * round for a list staff work from the top of.
     */
    private static function rank(string $priority): int
    {
        $position = array_search($priority, array_keys(DisputeClassification::PRIORITIES), true);

        return $position === false ? PHP_INT_MAX : $position;
    }

    public function show(Request $request, DisputeCase $case): View
    {
        $case->load(['booking.event', 'client', 'professional', 'filedBy', 'decisions.decider', 'assignments.staff', 'escalations']);

        $role = $this->roleOn($case, $request->user());

        return view('dashboard.admin.disputes.show', [
            'case'     => $case,
            'role'     => $role,
            'evidence' => $case->evidence()->with('submitter')->orderBy('id')->get(),

            // Staff see everything, including the rows the parties do not.
            'timeline' => $case->events()->with('actor')->orderBy('id')->get(),
            'decision' => $case->currentDecision(),
            'guide'    => DecisionGuide::all(),
            'related'  => $case->relatedCases()->with('professional')->get(),

            // §7 — history per account, and only confirmed findings count.
            'history'  => [
                'client' => [
                    'findings' => RepeatOffenderHistory::findingsAgainst($case->client),
                    'cases'    => RepeatOffenderHistory::totalCases($case->client),
                    'step'     => RepeatOffenderHistory::stepLabel(RepeatOffenderHistory::suggestedStep($case->client)),
                ],
                'professional' => [
                    'findings' => RepeatOffenderHistory::findingsAgainst($case->professional),
                    'cases'    => RepeatOffenderHistory::totalCases($case->professional),
                    'step'     => RepeatOffenderHistory::stepLabel(RepeatOffenderHistory::suggestedStep($case->professional)),
                ],
            ],

            'severities'      => DisputeClassification::SEVERITIES,
            'priorities'      => DisputeClassification::PRIORITIES,
            'taxonomy'        => DisputeClassification::TAXONOMY,
            'outcomes'        => DisputeClassification::FINANCIAL_OUTCOMES,
            'resolutionTypes' => DisputeClassification::RESOLUTION_TYPES,
            'staffRoles'      => DisputePermissions::STAFF_ROLES,

            // Who a case can be handed to. Typing a user ID into a box is how
            // a case ends up assigned to a client with an adjacent id.
            'assignable'      => User::query()
                                    ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
                                    ->orderBy('name')
                                    ->get(['id', 'name', 'email']),
            'canDecide'       => DisputePermissions::can($role, 'record_decision')
                                    || DisputePermissions::can($role, 'revise_decision'),
        ]);
    }

    /** §3 — the three independent fields, set by a person at intake. */
    public function classify(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $role = $this->roleOn($case, $user);

        abort_unless(DisputePermissions::can($role, 'classify_case'), 403);

        $data = $request->validate([
            'severity'      => ['required', 'integer', 'min:1', 'max:5'],
            'priority'      => ['required', 'in:' . implode(',', array_keys(DisputeClassification::PRIORITIES))],
            'taxonomy'      => ['required', 'in:' . implode(',', array_keys(DisputeClassification::TAXONOMY))],
            'internal_tags' => ['nullable', 'array'],
        ]);

        foreach (['severity', 'priority', 'taxonomy'] as $field) {
            if ((string) $case->$field !== (string) $data[$field]) {
                $case->log('reclassified', $user, $role, [
                    'field' => $field, 'old' => (string) $case->$field, 'new' => (string) $data[$field],
                    'visible' => false,
                ]);
            }
        }

        $case->update($data);

        // §2 — raising a case to fraud or safety takes it out of Direct
        // Resolution immediately. Waiting for someone to also press a "move
        // to review" button is how a safety case sits in a negotiation.
        if (DisputeClassification::bypassesDirectResolution((int) $data['severity'])
            && in_array($case->state, [DisputeStates::DIRECT_RESOLUTION, DisputeStates::AWAITING_RESPONSE], true)) {
            $moved = $case->transitionTo(
                DisputeStates::FORMAL_INVESTIGATION, $user, $role,
                'Severity ' . $data['severity'] . ' bypasses direct resolution.',
            );

            // Say so rather than swallowing it. A refused move here means the
            // classification saved and the case stayed in a negotiation it is
            // no longer suitable for — silence is how that goes unnoticed.
            if (! $moved) {
                return back()->with('status', 'Classification saved, but your role cannot move this case to review. Ask a senior reviewer.');
            }
        }

        return back()->with('status', 'Classification saved.');
    }

    /** §7 — assignment carries the conflict-of-interest disclosure with it. */
    public function assign(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $role = $this->roleOn($case, $user);

        abort_unless(
            DisputePermissions::can($role, 'assign_case') || DisputePermissions::can($role, 'reassign_case'),
            403,
        );

        $data = $request->validate([
            'staff_id'        => ['required', 'integer', 'exists:users,id'],
            'role'            => ['required', 'in:' . implode(',', array_keys(DisputePermissions::STAFF_ROLES))],
            'has_connection'  => ['required', 'in:yes,no'],
            'conflict_detail' => ['nullable', 'string', 'max:500'],
        ]);

        // A disclosed connection is a reason to assign someone else, so the
        // form cannot be used to assign them anyway.
        if ($data['has_connection'] === 'yes') {
            $case->log('conflict_disclosed', $user, $role, [
                'reason' => $data['conflict_detail'] ?: 'Personal connection disclosed.', 'visible' => false,
            ]);

            return back()->with('status', 'A connection was disclosed — this case needs a different owner.');
        }

        // §6 — exactly one role owns a case at a time. The previous owner is
        // released rather than left holding it alongside the new one.
        $case->assignments()->whereNull('released_at')->update(['released_at' => now()]);

        $case->assignments()->create([
            'staff_id'           => $data['staff_id'],
            'role'               => $data['role'],
            'conflict_disclosed' => false,
        ]);

        $case->update(['assigned_to' => $data['staff_id'], 'assigned_role' => $data['role']]);

        $case->log('assigned', $user, $role, [
            'field' => 'assigned_to', 'new' => (string) $data['staff_id'], 'visible' => false,
        ]);

        return back()->with('status', 'Assigned.');
    }

    /** §2 Step 3 — the decision, and the Resolution/Outcome Notice behind it. */
    public function decide(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $role = $this->roleOn($case, $user);

        abort_unless(DisputePermissions::can($role, 'record_decision'), 403);

        $data = $request->validate([
            'resolution_type'        => ['required', 'in:' . implode(',', array_keys(DisputeClassification::RESOLUTION_TYPES))],
            'financial_outcome'      => ['nullable', 'in:' . implode(',', array_keys(DisputeClassification::FINANCIAL_OUTCOMES))],
            'amount_to_client'       => ['nullable', 'numeric', 'min:0'],
            'amount_to_professional' => ['nullable', 'numeric', 'min:0'],
            'finding_against'        => ['nullable', 'integer'],
            'reasoning'              => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        // §5 — the two axes are independent, but not unrelated: an outcome
        // that moves money needs to say what happens to it.
        if (DisputeClassification::needsFinancialOutcome($data['resolution_type']) && empty($data['financial_outcome'])) {
            return back()->withInput()->withErrors([
                'financial_outcome' => 'This resolution type needs a financial outcome.',
            ]);
        }

        // §7 — a fraud finding names the party it is against, because the
        // party who filed can be the one who fabricated the evidence.
        if ($data['resolution_type'] === 'fraud_confirmed') {
            if (! in_array((int) ($data['finding_against'] ?? 0), [$case->client_id, $case->professional_id], true)) {
                return back()->withInput()->withErrors([
                    'finding_against' => 'A fraud finding has to name which party it is against.',
                ]);
            }
        } else {
            $data['finding_against'] = null;
        }

        $previous = $case->currentDecision();

        $case->decisions()->create($data + [
            'decided_by'   => $user->id,
            'decided_role' => $role,
            'revises'      => $previous?->id,
            'revision_reason' => $previous ? $request->string('revision_reason')->toString() : null,
        ]);

        $case->log($previous ? 'decision_revised' : 'decision_issued', $user, $role, [
            'field' => 'resolution_type',
            'old'   => $previous?->resolution_type,
            'new'   => $data['resolution_type'],
            'reason' => $data['reasoning'],
        ]);

        if ($case->state === DisputeStates::FORMAL_INVESTIGATION) {
            $case->transitionTo(DisputeStates::DECIDED, $user, $role, 'Decision issued.');
        }

        // A cure-redo keeps the balance paused until the cure or the deadline
        // (§5), so the case does not close here — it waits.
        if (($data['financial_outcome'] ?? null) === DisputeClassification::CURE_REDO) {
            $case->transitionTo(DisputeStates::CURE_PERIOD, $user, $role, 'Cure period opened.');
        }

        return back()->with('status', 'Decision recorded. Both parties have been notified.');
    }

    public function close(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $role = $this->roleOn($case, $user);

        abort_unless(DisputePermissions::can($role, 'close_case'), 403);

        $data = $request->validate(['closure_note' => ['required', 'string', 'min:5', 'max:2000']]);

        $moved = $case->transitionTo(DisputeStates::CLOSED, $user, $role, $data['closure_note']);

        if ($moved) {
            // §8 — the pause ends when the case does. Leaving it set would
            // hold a professional's money on a case nobody is working.
            $case->update(['balance_paused' => false]);
        }

        return $moved
            ? redirect()->route('app.admin.disputes.index')->with('status', "Case {$case->reference} is closed.")
            : back()->with('status', 'This case cannot be closed from where it is.');
    }

    /** §7 — staff-only note. Never reaches either party. */
    public function note(Request $request, DisputeCase $case): RedirectResponse
    {
        $user = $request->user();
        $role = $this->roleOn($case, $user);

        abort_unless(DisputePermissions::can($role, 'add_internal_note'), 403);

        $data = $request->validate(['note' => ['required', 'string', 'min:2', 'max:2000']]);

        $case->log('internal_note', $user, $role, ['reason' => $data['note'], 'visible' => false]);

        return back()->with('status', 'Note added.');
    }
}
