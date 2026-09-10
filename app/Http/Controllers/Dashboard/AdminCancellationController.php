<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The queue nobody had.
 *
 * Raising a cancellation told the client "our team will follow up", and there
 * was no screen for the team to follow up on: `status` only ever moved between
 * submitted and withdrawn, and `actioned_by`, `actioned_at` and
 * `resolution_note` were columns nothing ever wrote.
 *
 * Sir Peter / Ali, 2026-09-09: cancelling an event goes through an
 * administrator. This is where that happens — and booking cancellations, which
 * were already being raised into the same silence, land here too.
 */
class AdminCancellationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), array_keys(CancellationRequest::STATUS_LABELS), true)
            ? $request->query('status')
            : CancellationRequest::SUBMITTED;

        $requests = CancellationRequest::query()
            ->where('status', $status)
            ->with(['event:id,title,status,starts_at,client_id', 'booking:id,event_id,supplier_id', 'raiser:id,name,email'])
            ->orderBy('created_at')   // oldest first: a queue, not a feed
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.admin.cancellations.index', [
            'requests' => $requests,
            'status'   => $status,
            // Counted per status so the tabs say how much is actually waiting.
            'counts'   => CancellationRequest::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    /**
     * Approve — and only here does anything actually get cancelled.
     *
     * The event is left alone until this moment on purpose: a request that
     * took itself down when it was asked for would make the approval a
     * formality after the fact.
     */
    public function approve(Request $request, CancellationRequest $cancellation): RedirectResponse
    {
        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($cancellation->isPending(), 403, 'This one has already been actioned.');

        DB::transaction(function () use ($cancellation, $data, $request) {
            $cancellation->update([
                'status'          => CancellationRequest::APPROVED,
                'resolution_note' => $data['resolution_note'] ?? null,
                'actioned_by'     => $request->user()->id,
                'actioned_at'     => now(),
            ]);

            if ($cancellation->isEventCancellation() && $cancellation->event) {
                /*
                 * status AND is_published, because Event::stage() reads status
                 * first but the two columns disagreeing is a fault the app
                 * already has a query for. A cancelled event that still says
                 * published would show as open on every list that reads the
                 * flag.
                 */
                $cancellation->event->update([
                    'status'       => 'cancelled',
                    'is_published' => false,
                ]);
            }
        });

        return back()->with('status', $cancellation->reference . ' approved.');
    }

    /** Declined — the event carries on exactly as it was. */
    public function decline(Request $request, CancellationRequest $cancellation): RedirectResponse
    {
        $data = $request->validate([
            // Required here and not on approve: being told no without being
            // told why is the thing that becomes a support ticket.
            'resolution_note' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'resolution_note.required' => 'Say why this is being declined: the client sees it.',
        ]);

        abort_unless($cancellation->isPending(), 403, 'This one has already been actioned.');

        $cancellation->update([
            'status'          => CancellationRequest::DECLINED,
            'resolution_note' => $data['resolution_note'],
            'actioned_by'     => $request->user()->id,
            'actioned_at'     => now(),
        ]);

        return back()->with('status', $cancellation->reference . ' declined.');
    }
}
