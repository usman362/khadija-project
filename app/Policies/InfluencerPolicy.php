<?php

namespace App\Policies;

use App\Models\Influencer;
use App\Models\User;

class InfluencerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('influencers.view_any');
    }

    public function view(User $user, Influencer $influencer): bool
    {
        if ($user->can('influencers.view_any')) {
            return true;
        }
        return $influencer->user_id === $user->id;
    }

    public function approve(User $user, Influencer $influencer): bool
    {
        return $user->can('influencers.approve');
    }

    public function reject(User $user, Influencer $influencer): bool
    {
        return $user->can('influencers.reject');
    }
}
