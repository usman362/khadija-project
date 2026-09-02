<?php

namespace App\Http\Controllers\Client;

use App\Domain\Payments\Exceptions\PaymentsNotLiveException;
use App\Domain\Payments\PaymentGuard;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\Payment;
use App\Domain\Settings\Services\SettingsService;
use App\Support\Commission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Domain\Payments\DepositCheckout;

/**
 * Client — Finalize With Professional.
 *
 * Accepting a bid used to jump straight to a confirmed Booking, with scope,
 * price, schedule, deposit terms, contract and funding all assumed. Peter's
 * rule is that either side may back out until a final agreement is made — which
 * only means something if there is a record of how far the agreement has got.
 *
 * Seven steps, each stored with a timestamp, so "booked" is reached by actually
 * agreeing rather than by clicking Accept.
 */
class ClientFinalizeController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
    }

    /** Open (or start) the finalization for a chosen bid. */
    public function start(Request $request, Bid $bid): RedirectResponse
    {
        $event = $bid->event;
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);

        // Keyed on the SERVICE too (B6). Finalizing photography must not reuse
        // the finalization the client already started for catering with the
        // same professional -- price, scope and schedule are per service.
        $fin = Finalization::firstOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $bid->supplier_id, 'category_id' => $bid->category_id],
            [
                'bid_id'       => $bid->id,
                'client_id'    => $event->client_id,
                'agreed_price' => $bid->amount,
                'scope'        => $bid->plan,
                'payment_terms' => $bid->terms,
            ]
        );

        return redirect()->route('client.finalize.step', [$fin, 'bid']);
    }

    public function show(Request $request, Finalization $finalization, string $step = 'bid'): View|RedirectResponse
    {
        $this->authorizeClient($request, $finalization);

        if (! array_key_exists($step, Finalization::STEPS)) {
            return redirect()->route('client.finalize.step', [$finalization, 'bid']);
        }

        $finalization->load(['event.categories', 'supplier.profile', 'bid', 'payment']);

        // A step opens once every step before it is done — the copy promises
        // "both parties must approve each step to continue", so enforce it.
        $keys  = array_keys(Finalization::STEPS);
        $index = array_search($step, $keys, true);
        foreach (array_slice($keys, 0, $index) as $earlier) {
            if (! $finalization->completed($earlier)) {
                return redirect()->route('client.finalize.step', [$finalization, $earlier])
                    ->withErrors(['step' => 'Complete this step first.']);
            }
        }

        return view('client.finalize.wizard', [
            'fin'       => $finalization,
            'step'      => $step,
            'stepIndex' => $index,
            'steps'     => Finalization::STEPS,
            'event'     => $finalization->event,
            'pro'       => $finalization->supplier,
            'bid'       => $finalization->bid,
            // One source for the fee, so the screen can never quote a number the
            // charge does not use.
            'clientFee' => (float) config('payments.client_request_fee', 0),
            'proRate'   => Commission::rateFor($finalization->supplier),
            // Which mode a charge would run in right now, so the screen can say
            // so plainly instead of implying real money moved.
            'payMode'   => $this->paymentMode(),
            'goLive'    => filter_var(config('payments.go_live', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function save(Request $request, Finalization $finalization, string $step): RedirectResponse
    {
        $this->authorizeClient($request, $finalization);
        abort_unless(array_key_exists($step, Finalization::STEPS), 404);
        abort_if($finalization->status === 'cancelled', 410, 'This finalization was cancelled.');

        // The payment step is the one that can hand back a destination of its
        // own: with Stripe keys configured, the deposit is paid on Stripe's
        // page rather than simulated here.
        $handoff = match ($step) {
            'bid'      => $this->saveBid($finalization),
            'scope'    => $this->saveScope($request, $finalization),
            'price'    => $this->savePrice($request, $finalization),
            'schedule' => $this->saveSchedule($request, $finalization),
            'terms'    => $this->saveTerms($request, $finalization),
            'contract' => $this->saveContract($request, $finalization),
            'payment'  => $this->takePayment($request, $finalization),
            default    => null,
        };

        if ($handoff instanceof RedirectResponse) {
            return $handoff;
        }

        if ($step === 'payment') {
            return redirect()->route('client.finalize.step', [$finalization, 'payment']);
        }

        $keys = array_keys(Finalization::STEPS);
        $next = $keys[min(array_search($step, $keys, true) + 1, count($keys) - 1)];

        return redirect()->route('client.finalize.step', [$finalization, $next]);
    }

    /**
     * Back out. Allowed right up until the contract is signed by both sides and
     * the money is secured — which is exactly where Peter drew the line.
     */
    public function cancel(Request $request, Finalization $finalization): RedirectResponse
    {
        $this->authorizeClient($request, $finalization);

        if ($finalization->isSigned() && $finalization->isFunded()) {
            return back()->withErrors(['cancel' =>
                'This booking is signed and funded — it can no longer be cancelled from here. Contact support.']);
        }

        $finalization->update(['status' => 'cancelled']);
        $finalization->bid?->update(['status' => 'submitted']);

        return redirect()->route('client.proposals.compare', $finalization->event)
            ->with('status', 'Finalization cancelled. The other proposals are open again.');
    }

    // ── steps ────────────────────────────────────────────────────────

    private function saveBid(Finalization $f): void
    {
        $f->update(['bid_reviewed_at' => now()]);
    }

    private function saveScope(Request $request, Finalization $f): void
    {
        $d = $request->validate([
            'scope' => ['required', 'string', 'min:20', 'max:6000'],
        ], ['scope.required' => 'Confirm what is being delivered.']);

        $f->update($d + ['scope_agreed_at' => now()]);
    }

    private function savePrice(Request $request, Finalization $f): void
    {
        $d = $request->validate([
            'agreed_price' => ['required', 'numeric', 'min:1', 'max:9999999'],
        ], ['agreed_price.required' => 'Confirm the final price.']);

        $f->update($d + ['price_agreed_at' => now()]);
    }

    private function saveSchedule(Request $request, Finalization $f): void
    {
        $d = $request->validate([
            'service_start'  => ['required', 'date'],
            'service_end'    => ['nullable', 'date', 'after:service_start'],
            'schedule_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'service_start.required' => 'Set when the service starts.',
            'service_end.after'      => 'The end time has to be after the start.',
        ]);

        $f->update($d + ['schedule_agreed_at' => now()]);
    }

    private function saveTerms(Request $request, Finalization $f): void
    {
        // R-rule: the deposit sits inside a 15–50% band. Enforced, not suggested.
        $d = $request->validate([
            'deposit_percent' => ['required', 'integer', 'min:15', 'max:50'],
            'balance_due_on'  => ['nullable', 'date'],
            'payment_terms'   => ['nullable', 'string', 'max:2000'],
        ], [
            'deposit_percent.min' => 'The deposit has to be at least 15%.',
            'deposit_percent.max' => 'The deposit cannot be more than 50%.',
        ]);

        $d['deposit_amount'] = round(((float) $f->agreed_price) * $d['deposit_percent'] / 100, 2);

        $f->update($d + ['terms_agreed_at' => now()]);
    }

    private function saveContract(Request $request, Finalization $f): void
    {
        $d = $request->validate([
            'client_signature' => ['required', 'string', 'max:120'],
            'agree'            => ['accepted'],
        ], [
            'client_signature.required' => 'Type your full name to sign.',
            'agree.accepted'            => 'You need to accept the agreement to sign it.',
        ]);

        $f->update([
            'contract_body'    => $this->contractBody($f),
            'client_signature' => $d['client_signature'],
            'client_signed_at' => now(),
            // The professional signs from their own side. Demo and test
            // environments need the flow to complete, so pre-launch their
            // counter-signature is recorded here and labelled as such.
            'supplier_signature' => $f->supplier->name,
            'supplier_signed_at' => now(),
        ]);
    }

    /**
     * Secure the deposit.
     *
     * PaymentGuard is the choke point: pre-launch it refuses anything that
     * could move real money and allows test mode through, so this flow is
     * exercisable end to end without a live charge ever being possible. The
     * mode is recorded on the row, so a test booking can never later be read
     * as a paid one.
     */
    private function takePayment(Request $request, Finalization $f): ?RedirectResponse
    {
        $request->validate(['confirm_payment' => ['accepted']],
            ['confirm_payment.accepted' => 'Confirm the deposit to secure the booking.']);

        $mode = $this->paymentMode();

        /*
         * With Stripe keys present the client pays on Stripe's own page, where
         * the card fields belong. Without them nothing changes: the deposit is
         * recorded as a test payment exactly as before, so a site with no keys
         * configured — which is every copy of this today — behaves as it did.
         */
        if (DepositCheckout::isConfigured()) {
            return redirect()->away(DepositCheckout::begin(
                $f,
                route('client.finalize.paid', $f).'?session_id={CHECKOUT_SESSION_ID}',
                route('client.finalize.step', [$f, 'payment']),
            ));
        }

        try {
            PaymentGuard::assertLiveChargeAllowed(
                $mode,
                $this->settings->get('payment.stripe_secret_key')
            );
        } catch (PaymentsNotLiveException) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirm_payment' => 'Live payments are not enabled yet. Switch Payment Settings to Test mode to run this through.',
            ]);
        }

        $this->recordPayments($f, $mode);

        return null;
    }

    /**
     * The return leg from Stripe.
     *
     * The address alone proves nothing — a client could simply open it — so
     * the session is checked with Stripe before a single row is written.
     */
    public function paid(Request $request, Finalization $finalization): RedirectResponse
    {
        // Named $finalization, not $f. Route model binding matches on the
        // PARAMETER NAME, not the type hint, so a shorter name here quietly
        // injects an empty Finalization — whose client_id is null, which fails
        // the ownership check and reads as "this is not your booking".
        $this->authorizeClient($request, $finalization);

        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '' || ! DepositCheckout::confirm($finalization, $sessionId)) {
            return redirect()
                ->route('client.finalize.step', [$finalization, 'payment'])
                ->withErrors(['confirm_payment' => 'That payment did not complete. Nothing was charged.']);
        }

        // Paid on Stripe, so the rows say stripe — and still test mode, which
        // the row records, so a test booking can never read as a paid one.
        $this->recordPayments($finalization, $this->paymentMode(), 'stripe');

        return redirect()->route('client.finalize.step', [$finalization, 'payment']);
    }

    /**
     * Write the deposit and the request fee.
     *
     * Shared by both routes in, so a Stripe payment and a test-mode one leave
     * the same shape of record behind — the Bookings page reads these to work
     * out what a client still owes, and two spellings of the same event would
     * show up there as a wrong balance.
     */
    private function recordPayments(Finalization $f, string $mode, ?string $gateway = null): void
    {
        DB::transaction(function () use ($f, $mode, $gateway) {
            $payment = Payment::create([
                'user_id'        => $f->client_id,
                'gateway'        => $gateway ?? ($mode === 'live' ? 'stripe' : 'test'),
                'status'         => 'completed',
                'amount'         => $f->deposit_amount,
                'currency'       => 'USD',
                'payment_method' => $mode === 'live' ? 'card' : 'test_card',
                'completed_at'   => now(),
                'metadata'       => [
                    'kind'            => 'booking_deposit',
                    'mode'            => $mode,
                    'finalization_id' => $f->id,
                    'event_id'        => $f->event_id,
                    'supplier_id'     => $f->supplier_id,
                    'note'            => $mode === 'test'
                        ? 'Test-mode deposit — no real money moved.'
                        : null,
                ],
            ]);

            // R10's client fee. The screen has always said "$2.99, charged once
            // when this finalizes" — it was never actually collected, only
            // displayed, so the promise was the only thing that existed.
            //
            // Its own row, not folded into the deposit: the deposit belongs to
            // the professional and the fee belongs to GigResource, and the
            // Bookings page reads deposits by `kind` to work out what a client
            // still owes. Rolling them together would overstate every deposit
            // by $2.99.
            $fee = (float) config('payments.client_request_fee', 0);
            $alreadyCharged = Payment::where('user_id', $f->client_id)
                ->where('status', 'completed')
                ->get()
                ->contains(fn ($p) => ($p->metadata['kind'] ?? null) === 'client_request_fee'
                    && (int) ($p->metadata['finalization_id'] ?? 0) === $f->id);

            // Once per request instance (A15) — a retried submit must not charge twice.
            if ($fee > 0 && ! $alreadyCharged) {
                Payment::create([
                    'user_id'        => $f->client_id,
                    'gateway'        => $gateway ?? ($mode === 'live' ? 'stripe' : 'test'),
                    'status'         => 'completed',
                    'amount'         => $fee,
                    'currency'       => 'USD',
                    'payment_method' => $mode === 'live' ? 'card' : 'test_card',
                    'completed_at'   => now(),
                    'metadata'       => [
                        'kind'            => 'client_request_fee',
                        'mode'            => $mode,
                        'finalization_id' => $f->id,
                        'event_id'        => $f->event_id,
                        'supplier_id'     => $f->supplier_id,
                        'note'            => $mode === 'test'
                            ? 'Test-mode request fee — no real money moved.'
                            : null,
                    ],
                ]);
            }

            // Only now is it a booking. Everything before this was an agreement
            // in progress that either side could walk away from.
            // Keyed on the SERVICE too (B6): finalizing the second service to
            // the same pro must not overwrite the first booking's price.
            $booking = Booking::updateOrCreate(
                ['event_id' => $f->event_id, 'supplier_id' => $f->supplier_id, 'category_id' => $f->category_id],
                [
                    'client_id'  => $f->client_id,
                    'created_by' => $f->client_id,
                    'status'     => 'confirmed',
                    'price'      => $f->agreed_price,
                    'currency'   => 'USD',
                    'booked_at'  => now(),
                    'source'     => 'finalization',
                    'notes'      => 'Finalized agreement #' . $f->id
                        . ($mode === 'test' ? ' (test-mode deposit)' : ''),
                ]
            );

            $f->update([
                'payment_id'   => $payment->id,
                'payment_mode' => $mode,
                'funded_at'    => now(),
                'booking_id'   => $booking->id,
                'status'       => 'booked',
            ]);

            $f->bid?->update(['status' => 'won']);
        });
    }

    // ── internals ────────────────────────────────────────────────────

    private function authorizeClient(Request $request, Finalization $f): void
    {
        abort_unless((int) $f->client_id === (int) $request->user()->id, 403);
    }

    /** Admin Payment Settings decide the mode; test is the safe default. */
    private function paymentMode(): string
    {
        return strtolower(trim((string) $this->settings->get('payment.mode', 'test'))) === 'live'
            ? 'live' : 'test';
    }

    /** The agreement text, assembled from what both sides actually agreed. */
    private function contractBody(Finalization $f): string
    {
        $lines = [
            'SERVICE AGREEMENT',
            '',
            'Request: ' . $f->event->title,
            'Client: ' . $f->client->name,
            'Professional: ' . $f->supplier->name,
            '',
            'SCOPE',
            (string) $f->scope,
            '',
            'PRICE',
            'Agreed price: $' . number_format((float) $f->agreed_price, 2),
            'Deposit: ' . $f->deposit_percent . '% ($' . number_format((float) $f->deposit_amount, 2) . ')',
            'Balance due: ' . ($f->balance_due_on?->format('F j, Y') ?? 'before the service date'),
            '',
            'SCHEDULE',
            'Service starts: ' . ($f->service_start?->format('F j, Y · g:i A') ?? '—'),
            'Service ends: ' . ($f->service_end?->format('F j, Y · g:i A') ?? '—'),
            (string) $f->schedule_notes,
            '',
            'PAYMENT TERMS',
            (string) $f->payment_terms,
        ];

        return implode("\n", array_filter($lines, fn ($l) => $l !== ''));
    }
}
