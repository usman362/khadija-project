<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Enums\RoleName;
use App\Domain\Payments\Services\AccountReactivationService;
use App\Models\AccountReactivationPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AccountDeletionController extends Controller
{
    public function __construct(
        private AccountReactivationService $reactivation,
    ) {}

    /** Number of days the account is retained before hard purge. */
    public const GRACE_DAYS = 60;

    /**
     * User submits a deletion request from their profile page.
     * Admins cannot self-delete.
     */
    public function request(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole(RoleName::ADMIN->value)) {
            return back()->with('error', 'Administrator accounts cannot be deleted through this form. Contact another administrator.');
        }

        if ($user->hasPendingDeletion()) {
            return back()->with('error', 'An account deletion request is already pending.');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_text'     => ['required', 'string', 'in:DELETE'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ], [
            'confirm_text.in' => 'Please type DELETE in capital letters to confirm.',
        ]);

        $user->update([
            'deletion_requested_at' => now(),
            'deletion_scheduled_at' => now()->addDays(self::GRACE_DAYS),
            'deletion_reason'       => $validated['reason'] ?? null,
        ]);

        Log::info('Account deletion requested', [
            'user_id'      => $user->id,
            'email'        => $user->email,
            'scheduled_at' => $user->deletion_scheduled_at->toIso8601String(),
        ]);

        // User will be caught by the middleware on next request
        return redirect()->route('account.deletion.restore.show')
            ->with('status', 'Your account has been scheduled for deletion. You have ' . self::GRACE_DAYS . ' days to change your mind.');
    }

    /**
     * Show the restore-or-wait screen while the account is in the grace period.
     */
    public function showRestore(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->hasPendingDeletion()) {
            return redirect('/dashboard');
        }

        return view('account.deletion.restore', [
            'user'             => $user,
            'reactivationFee'  => $this->reactivation->getFee(),
            'feeEnabled'       => $this->reactivation->isEnabled(),
            'currency'         => $this->reactivation->getCurrency(),
        ]);
    }

    /**
     * User cancels the pending deletion — either free (if fee disabled)
     * or kicks off the payment flow (if fee enabled).
     */
    public function restore(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->hasPendingDeletion()) {
            return redirect('/dashboard');
        }

        // Free restore path — fee disabled
        if (!$this->reactivation->isEnabled()) {
            $user->update([
                'deletion_requested_at' => null,
                'deletion_scheduled_at' => null,
                'deletion_reason'       => null,
            ]);

            Log::info('Account deletion cancelled by user (free)', [
                'user_id' => $user->id,
            ]);

            return redirect('/dashboard')->with('status', 'Welcome back! Your account has been restored.');
        }

        // Paid restore path
        $validated = $request->validate([
            'gateway' => ['required', 'in:stripe,paypal'],
        ]);

        try {
            $result = $this->reactivation->initiate($user, $validated['gateway']);
        } catch (Throwable $e) {
            Log::error('Reactivation payment initiation failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Payment success callback — handles both Stripe and PayPal returns.
     * Stripe: relies on webhook for actual completion, shows "processing".
     * PayPal: captures the order synchronously and restores the account.
     */
    public function reactivationSuccess(Request $request): RedirectResponse
    {
        $user = $request->user();

        // PayPal path (capture synchronously)
        if ($request->query('gateway') === 'paypal') {
            $paymentId = $request->query('reactivation_payment_id');
            $payment   = AccountReactivationPayment::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->first();

            if (!$payment) {
                return redirect()->route('account.deletion.restore.show')->with('error', 'Payment record not found.');
            }

            $ok = $this->reactivation->capturePayPalOrder($payment);

            if (!$ok) {
                return redirect()->route('account.deletion.restore.show')
                    ->with('error', 'Payment could not be captured. Please try again.');
            }

            return redirect('/dashboard')->with('status', 'Payment successful! Your account has been reactivated.');
        }

        // Stripe path — check payment status via session_id
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('account.deletion.restore.show');
        }

        $payment = $this->reactivation->findBySessionId($sessionId);

        if (!$payment || $payment->user_id !== $user->id) {
            return redirect()->route('account.deletion.restore.show')->with('error', 'Invalid payment session.');
        }

        // Webhook may have already completed it
        if ($payment->isCompleted()) {
            return redirect('/dashboard')->with('status', 'Payment successful! Your account has been reactivated.');
        }

        // Double-check with Stripe API (covers the case where webhook is delayed)
        try {
            $secretKey = app(\App\Domain\Settings\Services\SettingsService::class)->get('payment.stripe_secret_key');
            $stripe    = new \Stripe\StripeClient($secretKey);
            $session   = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $this->reactivation->complete($payment, $session->payment_intent);
                return redirect('/dashboard')->with('status', 'Payment successful! Your account has been reactivated.');
            }
        } catch (Throwable $e) {
            Log::warning('Stripe session verification failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('account.deletion.restore.show')
            ->with('status', 'Payment is being processed. Your account will be reactivated shortly.');
    }

    /**
     * Payment cancelled — redirect back to restore page.
     */
    public function reactivationCancel(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        $paymentId = $request->query('reactivation_payment_id');

        if ($sessionId) {
            $payment = $this->reactivation->findBySessionId($sessionId);
            if ($payment) { $this->reactivation->cancel($payment); }
        } elseif ($paymentId) {
            $payment = AccountReactivationPayment::find($paymentId);
            if ($payment) { $this->reactivation->cancel($payment); }
        }

        return redirect()->route('account.deletion.restore.show')
            ->with('error', 'Payment was cancelled. You can try again anytime.');
    }
}
