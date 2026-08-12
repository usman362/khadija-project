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

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('forms.index', [
            'layout'  => $this->layout($user),
            'forms'   => FormRegistry::forAudience($this->audience($user)),
            'mine'    => FormSubmission::where('submitted_by', $user->id)->latest('id')->limit(20)->get(),
            'waiting' => FormSubmission::where('counterparty_id', $user->id)
                            ->where('approval_status', 'pending')->get(),
        ]);
    }

    public function create(Request $request, string $key): View
    {
        $user       = $request->user();
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
