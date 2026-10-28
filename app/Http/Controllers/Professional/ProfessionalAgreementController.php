<?php

namespace App\Http\Controllers\Professional;

use App\Domain\Agreements\AgreementNotices;
use App\Domain\Agreements\EditAgreement;
use App\Domain\Agreements\Workspace;
use App\Http\Controllers\Controller;
use App\Models\Finalization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The professional's side of the agreement.
 *
 * Week of 26 October, day one: the routes and the authorisation. Until now a
 * professional had no address for an agreement at all. The client has had
 * /client/finalize since the finalization wizard was built, and the schema
 * under it was written for both sides from the start -- supplier_id,
 * supplier_approved_version, supplier_signature, supplier_signed_at,
 * supplier_signed_version are all columns that exist and are all unused --
 * and App\Domain\Agreements\Workspace already answers supplierApproved(),
 * supplierSigned() and sideOf(). What was missing was the door.
 *
 * Not the AI agreements at /app/agreements. Those are a separate model, the
 * professional could already reach their own, and they are a document rather
 * than the contract being negotiated. This is Finalization: the bid, the
 * scope, the price, the schedule, the terms and the two signatures.
 *
 * Reading only, today. Approving, declining and asking for a change are
 * Tuesday and Wednesday; signing for themselves, which replaces the
 * counter-signature the client's own signing currently stamps on their
 * behalf, is Thursday.
 */
class ProfessionalAgreementController extends Controller
{
    /** The agreements this professional is a party to. */
    public function index(Request $request): View
    {
        $rows = Finalization::where('supplier_id', $request->user()->id)
            ->with(['client:id,name', 'event:id,title,starts_at', 'category:id,name'])
            ->latest()
            ->paginate(12);

        /*
         * Which of them are waiting on this professional, counted from the
         * same source the row's own badge reads. A count worked out any other
         * way is a second opinion about the same fact.
         */
        $waiting = $rows->getCollection()
            ->filter(fn (Finalization $f) => Workspace::waitingOn($f, $request->user()))
            ->count();

        return view('professional.agreements.index', [
            'agreements' => $rows,
            'waiting'    => $waiting,
        ]);
    }

    /** One agreement, as the professional named in it sees it. */
    public function show(Request $request, Finalization $finalization): View
    {
        $this->assertTheirs($request, $finalization);

        $finalization->load(['client:id,name', 'event:id,title,starts_at', 'category:id,name', 'bid']);

        return view('professional.agreements.show', [
            'f'       => $finalization,
            'state'   => Workspace::STATES[$finalization->workspaceStatus()],
            'version' => Workspace::version($finalization),
            'waiting' => Workspace::waitingOn($finalization, $request->user()),
            // Every version, and the narrower question: what is different
            // from the version this professional said yes to.
            'history' => EditAgreement::history($finalization),
            'since'   => EditAgreement::changesSince($finalization, $request->user()),
        ]);
    }

    /**
     * Approve the version in front of them.
     *
     * The same two calls the client's own approval makes. Workspace::approve
     * works out which side the person is on and records the version, and the
     * notice goes to the other party, so neither needed a professional's
     * copy written beside it.
     */
    public function approve(Request $request, Finalization $finalization): RedirectResponse
    {
        $this->assertTheirs($request, $finalization);

        $this->refuseIfSettled($finalization);

        Workspace::approve($finalization, $request->user());

        AgreementNotices::approved($finalization->fresh(), $request->user());

        return back()->with('status', Workspace::bothApproved($finalization->fresh())
            ? 'Both of you have approved these terms. The agreement is ready to sign.'
            : 'You approved these terms. It now waits on the client.');
    }

    /**
     * End the agreement.
     *
     * Terminal, which is why the screen asks twice and says what it costs.
     * Asking for a change instead is a different action and lands Wednesday;
     * until it does, the screen points at Messages rather than letting a
     * professional end a job because a date needs moving.
     */
    public function decline(Request $request, Finalization $finalization): RedirectResponse
    {
        $this->assertTheirs($request, $finalization);

        $this->refuseIfSettled($finalization);

        Workspace::decline($finalization, $request->user());

        AgreementNotices::declined($finalization->fresh(), $request->user());

        return redirect()
            ->route('professional.agreements.show', $finalization)
            ->with('status', 'You declined this agreement. The client has been told.');
    }

    /**
     * Counter-propose: change the terms and send them back.
     *
     * Wednesday's item, "responding to an Edit Agreement request from the
     * client". A professional who can only say "please change something"
     * without saying what is not responding, they are asking the client to
     * guess, so this is the same edit the client makes and goes through the
     * same EditAgreement::submit. That raises the version, which takes every
     * approval and signature on the old terms out of play, including their
     * own: they are proposing terms nobody has agreed to yet.
     */
    public function requestChanges(Request $request, Finalization $finalization): RedirectResponse
    {
        $this->assertTheirs($request, $finalization);

        $this->refuseIfSettled($finalization);

        $data = $request->validate([
            'terms'                 => ['required', 'array'],
            'terms.scope'           => ['nullable', 'string', 'max:5000'],
            'terms.agreed_price'    => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'terms.service_start'   => ['nullable', 'date'],
            'terms.service_end'     => ['nullable', 'date', 'after_or_equal:terms.service_start'],
            'terms.schedule_notes'  => ['nullable', 'string', 'max:2000'],
            'terms.deposit_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'terms.balance_due_on'  => ['nullable', 'date'],
            'terms.payment_terms'   => ['nullable', 'string', 'max:2000'],
            'summary'               => ['required', 'string', 'max:2000'],
        ], [
            'terms.service_end.after_or_equal' => 'The service cannot end before it starts.',
            'summary.required' => 'Say what you are asking for and why. The client sees this.',
        ]);

        $changed = EditAgreement::submit($finalization, $request->user(), $data['terms'], $data['summary']);

        if ($changed === []) {
            return back()->with('status', 'Nothing was different, so the agreement is unchanged.');
        }

        AgreementNotices::changesSent($finalization->fresh(), $request->user(), $changed);

        return redirect()
            ->route('professional.agreements.show', $finalization)
            ->with('status', 'Your changes were sent. The client has to approve them before anyone signs.');
    }

    /**
     * Sign, as themselves.
     *
     * Thursday's item. Until now the client's own signing stamped a
     * counter-signature in the professional's name, because the professional
     * had no way to sign; that stand-in is gone and this is what replaces it.
     *
     * Two guards that are not formalities. Signing opens only once both sides
     * approved the SAME version, because before that there is nothing settled
     * to sign. And the version signed is recorded, so a price changed
     * afterwards cannot leave a signature sitting under terms nobody saw.
     */
    public function sign(Request $request, Finalization $finalization): RedirectResponse
    {
        $this->assertTheirs($request, $finalization);

        $this->refuseIfSettled($finalization);

        abort_unless(
            Workspace::signingOpen($finalization),
            409,
            'This agreement is not ready to sign: you both have to approve the same version first.',
        );

        $data = $request->validate([
            'supplier_signature' => ['required', 'string', 'max:120'],
            'agree'              => ['accepted'],
        ], [
            'supplier_signature.required' => 'Type your full name to sign.',
            'agree.accepted'              => 'You need to accept the agreement to sign it.',
        ]);

        $finalization->forceFill([
            'supplier_signature'      => $data['supplier_signature'],
            'supplier_signed_at'      => now(),
            'supplier_signed_version' => Workspace::version($finalization),
        ])->save();

        AgreementNotices::signed($finalization->fresh(), $request->user());

        return redirect()
            ->route('professional.agreements.show', $finalization)
            ->with('status', Workspace::bothSigned($finalization->fresh())
                ? 'Signed. Both signatures are in, and the client pays the deposit next.'
                : 'Signed. It now waits on the client to sign.');
    }

    /**
     * The agreement as a document, at whatever stage it has reached.
     *
     * The client has had this since Sir Peter asked for it beside their
     * workspace; the professional signing the same terms had no copy of them
     * to keep. It is the same view, so neither side can be handed a document
     * that says something the other's does not.
     *
     * Not the signed contract: app.agreements.download refuses anything both
     * sides have not accepted, and that file is the record of a booking. This
     * one says "draft" on every page until both signatures are on it, so a
     * professional reading it away from the screen cannot mistake where the
     * terms have reached.
     */
    public function download(Request $request, Finalization $finalization): Response
    {
        $this->assertTheirs($request, $finalization);

        $finalization->load(['event', 'supplier', 'bid.category', 'client']);

        $pdf = Pdf::loadView('client.finalize.draft-pdf', [
            'fin'         => $finalization,
            'event'       => $finalization->event,
            'pro'         => $finalization->supplier,
            // The client named in the agreement, not whoever is reading it.
            // The client's own copy passes the signed-in user, which is the
            // same person there and would be the wrong one here.
            'client'      => $finalization->client,
            'service'     => $finalization->bid?->category?->name,
            'generatedAt' => now(),
        ])->setPaper('a4');

        // Named from the client's side of the booking, because that is what
        // tells one of these apart in a professional's folder.
        $name = trim(($finalization->event?->reference() ?? 'agreement') . '-'
            . Str::slug($finalization->client?->name ?? 'client') . '-draft') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    /**
     * Neither action applies to an agreement that is already over.
     *
     * Workspace quietly does nothing in both cases, which is right for the
     * domain and wrong for a person: a button that reports success and
     * changes nothing is worse than one that says why not.
     */
    private function refuseIfSettled(Finalization $finalization): void
    {
        abort_if((bool) $finalization->declined_at, 409, 'This agreement was already ended.');
        abort_if((bool) $finalization->funded_at, 409, 'This agreement is funded and can no longer be changed here.');
    }

    /**
     * Only the professional this agreement names.
     *
     * Asked through Workspace::sideOf rather than by comparing the column
     * here, so that this and every screen that draws the agreement agree
     * about whose it is. A client reaching a professional's address for their
     * own agreement is still refused: the client has their own screen for it,
     * and two doors onto one record is how the two drift apart.
     */
    private function assertTheirs(Request $request, Finalization $finalization): void
    {
        abort_unless(
            Workspace::sideOf($finalization, $request->user()) === 'supplier',
            403,
            'Not your agreement',
        );
    }
}
