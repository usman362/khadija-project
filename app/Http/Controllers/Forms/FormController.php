<?php

namespace App\Http\Controllers\Forms;

use App\Domain\Forms\FormRegistry;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The forms audit's ten forms, rendered from their definitions.
 *
 * One controller for all ten. The rows are ten instances of one problem, and
 * the rules that must hold — a certification is never pre-ticked and stores
 * the wording it showed, a booking form only offers your own bookings, a
 * dual-approval form is a proposal until the other side accepts — hold for
 * all of them. Ten controllers would be ten chances to drop one.
 */
class FormController extends Controller
{
    private function audience(User $user): string
    {
        return match (true) {
            $user->hasRole('influencer')   => FormRegistry::INFLUENCER,
            $user->isProfessionalMode()    => FormRegistry::PROFESSIONAL,
            default                        => FormRegistry::CLIENT,
        };
    }

    private function layout(User $user): string
    {
        return $user->isProfessionalMode() ? 'layouts.professional' : 'layouts.client';
    }

    /** The tabs, and what each means in terms of the two status columns. */
    public const TABS = [
        'action'    => 'Needs Your Action',
        'review'    => 'Under Review',
        'completed' => 'Completed',
        'closed'    => 'Closed',
    ];

    /** Date ranges the header offers, in days. Null = no limit. */
    public const RANGES = [
        'all' => ['All Time', null],
        '30'  => ['Last 30 days', 30],
        '90'  => ['Last 90 days', 90],
        '365' => ['Last 12 months', 365],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $tab = array_key_exists((string) $request->query('tab'), self::TABS)
            ? (string) $request->query('tab') : 'all';
        $range = array_key_exists((string) $request->query('range'), self::RANGES)
            ? (string) $request->query('range') : 'all';
        $group = array_key_exists((string) $request->query('group'), FormRegistry::GROUPS)
            ? (string) $request->query('group') : '';
        $q = trim((string) $request->query('q', ''));

        /*
         * Everything this person is part of — what they sent, and what was sent
         * to them for a decision. Both belong on one screen: a change order you
         * proposed and one you have been asked to accept are the same kind of
         * thing, and splitting them is how "waiting on you" gets missed.
         *
         * Loaded once and sorted in PHP, because "needs your action" depends on
         * which side of the submission this person is — not a column.
         */
        $all = FormSubmission::query()
            ->where(fn ($w) => $w->where('submitted_by', $user->id)->orWhere('counterparty_id', $user->id))
            ->with(['submitter', 'counterparty', 'subject'])
            ->latest('id')
            ->get();

        $counts = [
            'all'       => $all->count(),
            'action'    => $all->filter(fn ($s) => $this->stateOf($s, $user) === 'action')->count(),
            'review'    => $all->filter(fn ($s) => $this->stateOf($s, $user) === 'review')->count(),
            // Scoped to 30 days because the tile says so.
            'completed' => $all->filter(fn ($s) => $this->stateOf($s, $user) === 'completed'
                && $s->updated_at?->gte(now()->subDays(30)))->count(),
            'closed'    => $all->filter(fn ($s) => $this->stateOf($s, $user) === 'closed')->count(),
        ];

        $shown = $tab === 'all'
            ? $all
            : $all->filter(fn ($s) => $this->stateOf($s, $user) === $tab);

        if ($group !== '') {
            $shown = $shown->filter(fn ($s) => FormRegistry::groupOf($s->form_key) === $group);
        }

        if ($q !== '') {
            $needle = \Illuminate\Support\Str::lower($q);
            $shown = $shown->filter(fn ($s) => str_contains(\Illuminate\Support\Str::lower($s->title()), $needle)
                || str_contains(\Illuminate\Support\Str::lower((string) $s->reference), $needle));
        }

        if (self::RANGES[$range][1] !== null) {
            $since = now()->subDays(self::RANGES[$range][1]);
            $shown = $shown->filter(fn ($s) => $s->created_at?->gte($since));
        }

        $shown = $shown->values();

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $submissions = new \Illuminate\Pagination\LengthAwarePaginator(
            $shown->forPage($page, $perPage)->values(),
            $shown->count(),
            $perPage,
            $page,
            ['path' => route('forms.index'), 'query' => $request->query()],
        );

        return view('forms.index', [
            'layout'      => $this->layout($user),
            'groups'      => FormRegistry::groupsForAudience($this->audience($user)),
            'submissions' => $submissions,
            'counts'      => $counts,
            'tabs'        => self::TABS,
            'ranges'      => self::RANGES,
            'filters'     => ['tab' => $tab, 'range' => $range, 'group' => $group, 'q' => $q],
            // Passed as closures so the row and the tile read one function.
            'stateOf'     => fn (FormSubmission $s) => $this->stateOf($s, $user),
            'nextStep'    => fn (FormSubmission $s) => $this->nextStep($s, $user),
        ]);
    }

    /**
     * Which of the four states a submission is in FOR THIS PERSON.
     *
     * "Needs your action" is the one worth being careful about: it means a
     * dual-approval form is waiting on a decision and this person is the one
     * who has to make it. The person who proposed the change is not waiting on
     * themselves, and a form with no approval step is not waiting on either
     * party — it is with our team.
     */
    private function stateOf(FormSubmission $submission, User $user): string
    {
        if ($submission->status === 'withdrawn' || $submission->approval_status === 'declined') {
            return 'closed';
        }

        if ($submission->approval_status === 'accepted' || $submission->status === 'actioned') {
            return 'completed';
        }

        if ($submission->needsApproval()
            && $submission->approval_status === 'pending'
            && $submission->counterparty_id === $user->id) {
            return 'action';
        }

        return 'review';
    }

    /** What happens next, in the reader's own terms. Never a deadline. */
    private function nextStep(FormSubmission $submission, User $user): string
    {
        return match ($this->stateOf($submission, $user)) {
            'action'    => 'Open it and accept or decline',
            'completed' => 'Nothing — this is finished',
            'closed'    => 'Nothing — this is closed',
            default     => $submission->needsApproval()
                ? 'Waiting on ' . ($submission->counterparty?->name ?? 'the other party')
                : 'With our team — no action needed',
        };
    }

    public function create(Request $request, string $key): View
    {
        $user       = $request->user();
        /*
         * The URL carries the form's NAME now ("share-your-story"), not its
         * internal key. keyFor() accepts either, so links already sent to
         * somebody keep working.
         */
        $key        = FormRegistry::keyFor($key) ?? $key;
        $definition = FormRegistry::get($key);

        abort_if($definition === null, 404);

        // A form is offered to the people who can file it. Anything else
        // produces submissions nobody can action.
        abort_unless(
            $definition['audience'] === FormRegistry::ANYONE
                || $definition['audience'] === $this->audience($user),
            403,
        );

        return view('forms.create', [
            'layout'     => $this->layout($user),
            'key'        => $key,
            'definition' => $definition,
            'bookings'   => $this->bookingsFor($user, $definition),
        ]);
    }

    public function store(Request $request, string $key): RedirectResponse
    {
        $user       = $request->user();
        /*
         * The URL carries the form's NAME now ("share-your-story"), not its
         * internal key. keyFor() accepts either, so links already sent to
         * somebody keep working.
         */
        $key        = FormRegistry::keyFor($key) ?? $key;
        $definition = FormRegistry::get($key);

        abort_if($definition === null, 404);

        $rules   = [];
        $payload = [];

        foreach ($definition['fields'] as $field) {
            $name = $field['name'];

            // A certification is validated as `accepted` so an untouched
            // checkbox fails. It is never pre-ticked and never optional when
            // the form declares one.
            if (($field['type'] ?? null) === 'certification') {
                $rules[$name] = ['accepted'];
                continue;
            }

            $rules[$name] = array_filter([
                ($field['required'] ?? false) ? 'required' : 'nullable',
                match ($field['type'] ?? 'text') {
                    'number', 'booking' => 'integer',
                    'money'             => 'numeric',
                    'date', 'datetime'  => 'date',
                    'checkbox'          => 'boolean',
                    'textarea', 'text', 'select' => 'string',
                    default             => 'string',
                },
                in_array($field['type'] ?? '', ['text', 'textarea', 'select'], true) ? 'max:5000' : null,
            ]);
        }

        $data = $request->validate($rules);

        foreach ($definition['fields'] as $field) {
            if (($field['type'] ?? null) === 'certification') {
                continue;
            }
            $payload[$field['name']] = $data[$field['name']] ?? null;
        }

        $subject       = null;
        $counterparty  = null;

        // Forms about a booking are scoped to bookings this person is on.
        if (($definition['subject'] ?? null) === 'booking' && ! empty($data['booking_id'])) {
            $subject = Booking::findOrFail($data['booking_id']);

            abort_unless(
                in_array($user->id, [$subject->client_id, $subject->supplier_id], true),
                403,
            );

            $counterparty = $user->id === $subject->client_id ? $subject->supplier_id : $subject->client_id;
        }

        /*
         * Five reports a day — Khadijah's sheet, 29 Aug. Only the report form:
         * a support request or a change order is not a report and is not
         * capped by this. Counted here, after the form has passed validation
         * and the subject has been checked, so a rejected form costs nothing.
         */
        if ($key === 'content_report') {
            \App\Support\UserLimit::hit('reports', $user, null, 'detail');
        }

        $certification = collect($definition['fields'])
            ->firstWhere('type', 'certification')['text'] ?? null;

        $submission = FormSubmission::create([
            'form_key'        => $key,
            'submitted_by'    => $user->id,
            'submitted_role'  => $this->audience($user),
            'subject_type'    => $subject ? $subject::class : null,
            'subject_id'      => $subject?->id,
            'payload'         => $payload,
            'counterparty_id' => ($definition['dual_approval'] ?? false) ? $counterparty : null,
            'approval_status' => ($definition['dual_approval'] ?? false) ? 'pending' : null,
            'certification_text' => $certification,
        ]);

        return redirect()
            ->route('forms.show', $submission)
            ->with('status', "Sent. Your reference is {$submission->reference}.");
    }

    public function show(Request $request, FormSubmission $submission): View
    {
        $this->authorizeView($request->user(), $submission);

        return view('forms.show', [
            'layout'     => $this->layout($request->user()),
            'submission' => $submission->load(['submitter', 'counterparty']),
        ]);
    }

    /**
     * The Change Order's dual approval (row 183).
     *
     * Only the other party decides, and only once. A change to a signed
     * agreement is not a change until the person it affects says so — that is
     * what makes it an order rather than an announcement.
     */
    public function respond(Request $request, FormSubmission $submission): RedirectResponse
    {
        abort_unless($submission->needsApproval(), 404);
        abort_unless($submission->counterparty_id === $request->user()->id, 403);
        abort_unless($submission->approval_status === 'pending', 403);

        $data = $request->validate([
            'decision' => ['required', 'in:accepted,declined'],
            'note'     => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->update([
            'approval_status' => $data['decision'],
            'approval_note'   => $data['note'] ?? null,
            'approved_at'     => now(),
        ]);

        return back()->with('status', $data['decision'] === 'accepted'
            ? 'Accepted. The change now stands.'
            : 'Declined. The original agreement is unchanged.');
    }

    public function withdraw(Request $request, FormSubmission $submission): RedirectResponse
    {
        abort_unless($submission->submitted_by === $request->user()->id, 403);
        abort_unless($submission->status === 'submitted', 403);

        $submission->update(['status' => 'withdrawn']);

        return back()->with('status', 'Withdrawn.');
    }

    private function authorizeView(User $user, FormSubmission $submission): void
    {
        abort_unless(
            $submission->submitted_by === $user->id
                || $submission->counterparty_id === $user->id
                || $user->isAdmin(),
            403,
        );
    }

    /** Only this person's own live bookings — never a list of everyone's. */
    private function bookingsFor(User $user, array $definition)
    {
        if (($definition['subject'] ?? null) !== 'booking') {
            return collect();
        }

        return Booking::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id))
            ->whereIn('status', ['requested', 'confirmed', 'completed'])
            ->with(['event', 'client', 'supplier'])
            ->latest('id')
            ->get();
    }
}
