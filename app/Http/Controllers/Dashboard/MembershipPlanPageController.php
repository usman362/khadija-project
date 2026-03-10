<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanPageController extends Controller
{
    /**
     * Display plans as cards for clients/suppliers to browse and subscribe.
     */
    public function index(Request $request): View
    {
        $plans = MembershipPlan::query()
            ->active()
            ->ordered()
            ->with('features')
            ->get();

        $activeSubscription = $request->user()->activeSubscription();

        return view('dashboard.membership-plans.index', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    /**
     * Subscribe the current user to a plan.
     * Free plans are activated instantly; paid plans redirect to the payment flow.
     */
    public function subscribe(Request $request, MembershipPlan $membership_plan): RedirectResponse
    {
        $this->authorize('subscribe', $membership_plan);

        // Paid plans → redirect to payment gateway
        if ($membership_plan->price > 0) {
            return redirect()->route('app.payments.initiate', $membership_plan);
        }

        $user = $request->user();

        // Cancel any existing active subscription
        $existing = $user->activeSubscription();
        if ($existing) {
            $existing->cancel('Switched to plan: ' . $membership_plan->name);
        }

        // Calculate expiry
        $startsAt = now();
        $expiresAt = $membership_plan->duration_days
            ? $startsAt->copy()->addDays($membership_plan->duration_days)
            : null;

        UserSubscription::create([
            'user_id' => $user->id,
            'membership_plan_id' => $membership_plan->id,
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'amount_paid' => 0,
        ]);

        return back()->with('status', 'Successfully subscribed to ' . $membership_plan->name . ' plan!');
    }

    /**
     * Cancel the current user's subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->activeSubscription();

        if (!$subscription) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        $reason = $request->input('reason', 'Cancelled by user');
        $subscription->cancel($reason);

        return back()->with('status', 'Your subscription has been cancelled.');
    }

    /**
     * Show subscription history for the user.
     */
    public function history(Request $request): View
    {
        $subscriptions = UserSubscription::query()
            ->forUser($request->user())
            ->with('plan')
            ->latest()
            ->paginate(10);

        return view('dashboard.membership-plans.history', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
