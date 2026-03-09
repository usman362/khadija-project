<?php

namespace App\Policies;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('events.view_any');
    }

    public function view(User $user, Event $event): bool
    {
        if (! $user->can('events.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $event->client_id === $user->id || $event->supplier_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('events.create');
    }

    public function update(User $user, Event $event): bool
    {
        if (! $user->can('events.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(RoleName::CLIENT->value) && $event->client_id === $user->id) {
            return in_array($event->status, ['pending', 'confirmed', 'published'], true);
        }

        if ($user->hasRole(RoleName::SUPPLIER->value) && $event->supplier_id === $user->id) {
            return in_array($event->status, ['confirmed', 'in_progress', 'published'], true);
        }

        return false;
    }

    public function publish(User $user, Event $event): bool
    {
        if (! $user->can('events.publish')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(RoleName::CLIENT->value) && $event->client_id === $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        if (! $user->can('events.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(RoleName::CLIENT->value)
            && $event->client_id === $user->id
            && in_array($event->status, ['pending', 'published'], true);
    }
}
