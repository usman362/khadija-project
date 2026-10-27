<?php

namespace App\Http\Controllers\Professional;

use App\Domain\Agreements\Workspace;
use App\Http\Controllers\Controller;
use App\Models\Finalization;
use Illuminate\Http\Request;
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
        ]);
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
