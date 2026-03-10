<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;

class AgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agreements.view_any');
    }

    public function view(User $user, Agreement $agreement): bool
    {
        if (! $user->can('agreements.view_any')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $booking = $agreement->booking;

        return $booking->client_id === $user->id || $booking->supplier_id === $user->id;
    }

    public function generate(User $user): bool
    {
        return $user->can('agreements.generate');
    }

    public function accept(User $user, Agreement $agreement): bool
    {
        if (! $user->can('agreements.accept')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $booking = $agreement->booking;

        return $booking->client_id === $user->id || $booking->supplier_id === $user->id;
    }
}
