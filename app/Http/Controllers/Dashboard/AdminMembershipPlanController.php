<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminMembershipPlanController extends Controller
{
    /**
     * Admin management page — list all plans with CRUD.
     */
    public function index(): View
    {
        $plans = MembershipPlan::query()
            ->ordered()
            ->withCount(['subscriptions as active_subscribers_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->with('features')
            ->get();

        return view('dashboard.membership-plans.admin', compact('plans'));
    }

    /**
     * Create a new membership plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MembershipPlan::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly,one_time'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'max_events' => ['nullable', 'integer', 'min:1'],
            'max_bookings' => ['nullable', 'integer', 'min:1'],
            'has_chat' => ['sometimes', 'boolean'],
            'has_priority_support' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', 'max:20'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ]);

        $plan = MembershipPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'billing_cycle' => $validated['billing_cycle'],
            'duration_days' => $validated['duration_days'] ?? null,
            'max_events' => $validated['max_events'] ?? null,
            'max_bookings' => $validated['max_bookings'] ?? null,
            'has_chat' => $request->boolean('has_chat', true),
            'has_priority_support' => $request->boolean('has_priority_support'),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'badge_text' => $validated['badge_text'] ?? null,
            'badge_color' => $validated['badge_color'] ?? null,
        ]);

        // Save features
        if (!empty($validated['features'])) {
            foreach ($validated['features'] as $index => $feature) {
                if (trim($feature) !== '') {
                    $plan->features()->create([
                        'feature' => trim($feature),
                        'is_included' => true,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return back()->with('status', 'Membership plan created successfully.');
    }

    /**
     * Update an existing membership plan.
     */
    public function update(Request $request, MembershipPlan $membership_plan): RedirectResponse
    {
        $this->authorize('update', $membership_plan);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly,one_time'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'max_events' => ['nullable', 'integer', 'min:1'],
            'max_bookings' => ['nullable', 'integer', 'min:1'],
            'has_chat' => ['sometimes', 'boolean'],
            'has_priority_support' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', 'max:20'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ]);

        $membership_plan->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'billing_cycle' => $validated['billing_cycle'],
            'duration_days' => $validated['duration_days'] ?? null,
            'max_events' => $validated['max_events'] ?? null,
            'max_bookings' => $validated['max_bookings'] ?? null,
            'has_chat' => $request->boolean('has_chat', true),
            'has_priority_support' => $request->boolean('has_priority_support'),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'badge_text' => $validated['badge_text'] ?? null,
            'badge_color' => $validated['badge_color'] ?? null,
        ]);

        // Replace features
        $membership_plan->features()->delete();
        if (!empty($validated['features'])) {
            foreach ($validated['features'] as $index => $feature) {
                if (trim($feature) !== '') {
                    $membership_plan->features()->create([
                        'feature' => trim($feature),
                        'is_included' => true,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return back()->with('status', 'Membership plan updated successfully.');
    }

    /**
     * Delete a membership plan.
     */
    public function destroy(MembershipPlan $membership_plan): RedirectResponse
    {
        $this->authorize('delete', $membership_plan);

        // Guard: don't delete plans with active subscribers
        if ($membership_plan->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete a plan that has active subscribers. Deactivate it instead.');
        }

        $membership_plan->delete();

        return back()->with('status', 'Membership plan deleted.');
    }
}
