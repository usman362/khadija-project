<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('messages.view_any');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if (! $user->can('messages.view')) {
            return false;
        }

        return $user->isAdmin() || $conversation->hasParticipant($user);
    }

    public function create(User $user): bool
    {
        return $user->can('messages.create');
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $user->can('messages.create')) {
            return false;
        }

        return $user->isAdmin() || $conversation->hasParticipant($user);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
