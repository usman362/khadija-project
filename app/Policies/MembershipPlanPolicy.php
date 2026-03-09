<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
{
    /**
     * Anyone with membership_plans.view_any can browse plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('membership_plans.view_any');
    }

    public function view(User $user, MembershipPlan $plan): bool
    {
        return $user->can('membership_plans.view_any');
    }

    public function create(User $user): bool
    {
        return $user->can('membership_plans.create');
    }

    public function update(User $user, MembershipPlan $plan): bool
    {
        return $user->can('membership_plans.update');
    }

    public function delete(User $user, MembershipPlan $plan): bool
    {
        return $user->can('membership_plans.delete');
    }

    /**
     * Anyone with membership_plans.subscribe can subscribe to a plan.
     */
    public function subscribe(User $user, MembershipPlan $plan): bool
    {
        return $user->can('membership_plans.subscribe') && $plan->is_active;
    }
}
