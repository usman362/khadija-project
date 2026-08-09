<?php

namespace App\Http\Controllers\Client;

use App\Domain\Requests\EventExtensionService;
use App\Domain\Requests\RequestLifecycle;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventExtension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Rule R33 — what a client does with an expired request.
 *
 * Reopen it free inside the first 24 hours, pay to extend it, close it, or
 * duplicate it. ESR takes a different path (§5) and this controller enforces
 * that rather than letting the view decide: a view that offers an option the
 * backend refuses is a view that takes the client's money and then says no.
 */
class ClientEventLifecycleController extends Controller
{
    public function __construct(private EventExtensionService $extensions) {}

    private function authorizeOwner(Request $request, Event $event): void
    {
        abort_unless($event->client_id === $request->user()->id, 403);
    }

    /** §2 — the one free reopen, inside 24 hours of the deadline. */
    public function reopen(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $data = $request->validate([
            'proposal_deadline' => ['required', 'date', 'after:now'],
        ]);

        try {
            $this->extensions->graceReopen(
                $event, $request->user(), \Illuminate\Support\Carbon::parse($data['proposal_deadline']),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['proposal_deadline' => $e->getMessage()]);
        }

        return back()->with('status', 'Your request is open for proposals again.');
    }

    /** §2 — a paid extension. Nothing moves until the money arrives. */
    public function extend(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $data = $request->validate([
            'days'    => ['required', 'integer', 'in:' . implode(',', array_keys(RequestLifecycle::TIERS))],
            'gateway' => ['required', 'in:stripe,paypal'],
        ]);

        try {
            $result = $this->extensions->initiate(
                $event, $request->user(), (int) $data['days'], $data['gateway'],
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['days' => $e->getMessage()]);
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Back from the processor.
     *
     * The redirect is NOT proof of payment — a client can reach this URL by
     * typing it. The extension is only completed when the gateway confirms
     * the session was paid, which is why this asks rather than assumes.
     */
    public function success(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $extension = EventExtension::where('event_id', $event->id)
            ->where('gateway_session_id', $request->query('session_id'))
            ->latest('id')
            ->first();

        if ($extension === null) {
            return redirect()->route('client.events.show', $event)
                ->withErrors(['extension' => 'We could not find that payment. Nothing has been charged twice — check your extensions below.']);
        }

        if ($extension->isCompleted()) {
            return redirect()->route('client.events.show', $event)
                ->with('status', 'Your request is open for proposals again.');
        }

        return redirect()->route('client.events.show', $event)->with(
            'status',
            'Payment received — we are confirming it with the payment provider. Your request reopens as soon as that clears.',
        );
    }

    public function cancel(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $extension = EventExtension::where('event_id', $event->id)
            ->where('gateway_session_id', $request->query('session_id'))
            ->latest('id')
            ->first();

        if ($extension !== null) {
            $this->extensions->fail($extension, 'Cancelled at the payment page.');
        }

        // §2 — a failed or abandoned payment grants nothing. The request is
        // still expired, and the client may try again.
        return redirect()->route('client.events.show', $event)
            ->with('status', 'No payment was taken. Your request is still expired — you can try again.');
    }

    public function close(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $this->extensions->close($event, $request->user());

        return redirect()->route('client.events.index')->with('status', 'Request closed.');
    }

    /**
     * §2 — duplicate starts a fresh count; nothing carries over.
     *
     * Which is the whole reason it exists after the third extension: a new
     * listing is a new listing, not the old one with its history laundered.
     * Proposals, bids and extensions are all left behind on purpose.
     */
    public function duplicate(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $copy = $event->replicate([
            'supplier_id', 'published_at', 'is_published',
            'closed_at', 'reopened_at', 'proposal_deadline',
        ]);

        $copy->title        = $event->title . ' (copy)';
        $copy->status       = 'pending';
        $copy->is_published = false;
        $copy->save();

        $copy->categories()->sync($event->categories()->pluck('categories.id')->all());

        return redirect()->route('client.events.show', $copy)
            ->with('status', 'Copied. Set a new deadline and publish when you are ready.');
    }
}
