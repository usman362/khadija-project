<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('messages.view_any');
    }

    public function view(User $user, Message $message): bool
    {
        if (! $user->can('messages.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $message->sender_id === $user->id || $message->recipient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('messages.create');
    }
}
