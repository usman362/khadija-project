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

        // A pair the messaging rules do not allow (a client with another
        // client) is refused here, so a typed id or a saved link cannot open
        // what the lists leave out.
        if (\App\Domain\Messaging\MessagingPairs::blocks($user, $conversation->loadMissing('participants'))) {
            return false;
        }

        return $user->isAdmin() || $conversation->hasParticipant($user);
    }

    public function create(User $user): bool
    {
        return $user->can('messages.create');
    }

    public function sendMessage(User $user, Conversation $conversation): bool|\Illuminate\Auth\Access\Response
    {
        if (! $user->can('messages.create')) {
            return false;
        }

        // Refused here, before anything is counted against their message
        // limit; BlockGuard refuses the row itself as well.
        if (\App\Domain\Messaging\Blocking::stopsMessaging($user->id, $conversation)) {
            return \Illuminate\Auth\Access\Response::deny(\App\Domain\Messaging\Blocking::MESSAGE);
        }

        if (\App\Domain\Messaging\MessagingPairs::blocks($user, $conversation->loadMissing('participants'))) {
            return \Illuminate\Auth\Access\Response::deny('You cannot message this account.');
        }

        return $user->isAdmin() || $conversation->hasParticipant($user);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
