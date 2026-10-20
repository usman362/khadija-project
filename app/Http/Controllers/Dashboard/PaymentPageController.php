<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Payments\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentPageController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Initiate payment for a membership plan.
     */
    public function initiate(Request $request, MembershipPlan $plan): RedirectResponse
    {
        if (! $plan->is_active) {
            return redirect()->route('app.membership-plans.index')
                ->with('error', 'This plan is no longer available.');
        }

        /*
         * A free plan is joined, not paid for.
         *
         * This used to return an error, which was harmless while every plan
         * cost something. Starter became free on 20 October 2026 and the error
         * became a wall: the Subscribe button told people the plan needs no
         * payment and then did nothing, so nobody could join it.
         */
        if ($plan->isFree()) {
            $this->paymentService->activateFree($request->user(), $plan);

            return redirect()->route('app.membership-plans.index')
                ->with('status', "You are on the {$plan->name} plan. There is nothing to pay.");
        }

        try {
            $result = $this->paymentService->initiatePayment($request->user(), $plan);

            return redirect()->away($result['redirect_url']);
        } catch (\Exception $e) {
            return redirect()->route('app.membership-plans.index')
                ->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Payment success callback.
     */
    public function success(Request $request): View
    {
        $sessionId = $request->query('session_id');
        $payment = null;

        if ($sessionId) {
            $payment = $this->paymentService->findBySessionId($sessionId);
            $payment?->load(['subscription.plan']);
        }

        // For PayPal, find via the most recent processing payment for this user
        if (! $payment && $request->query('gateway') === 'paypal') {
            $payment = Payment::forUser($request->user()->id)
                ->whereIn('status', ['processing', 'completed'])
                ->latest()
                ->with(['subscription.plan'])
                ->first();
        }

        return view('dashboard.payments.success', [
            'payment' => $payment,
        ]);
    }

    /**
     * Payment cancel callback.
     */
    public function cancel(Request $request): View
    {
        $sessionId = $request->query('session_id');
        $payment = null;

        if ($sessionId) {
            $payment = $this->paymentService->findBySessionId($sessionId);
        }

        return view('dashboard.payments.cancel', [
            'payment' => $payment,
        ]);
    }

    /**
     * Payment history for the current user.
     */
    public function history(Request $request): View
    {
        $query = Payment::with(['subscription.plan'])
            ->latest();

        // Admin sees all, others see own
        if (! $request->user()->isAdmin()) {
            $query->forUser($request->user()->id);
        }

        return view('dashboard.payments.history', [
            'payments' => $query->paginate(15),
        ]);
    }
}
