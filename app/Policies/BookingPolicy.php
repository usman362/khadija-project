<?php

namespace App\Policies;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Booking;
use App\Models\User;

/**
 * Authorization for bookings.
 *
 * Visibility is role-agnostic (participants + admins see their bookings).
 * The update check is *existence only* — "is this user even allowed to
 * touch this booking at all?" The controller still has to validate WHICH
 * transition is being requested using Booking::canActorTransition(), since
 * policies can't see the incoming `status` value.
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bookings.view_any');
    }

    public function view(User $user, Booking $booking): bool
    {
        if (! $user->can('bookings.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $booking->client_id === $user->id || $booking->supplier_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('bookings.create');
    }

    /**
     * Can the user update this booking at all? This is the coarse gate —
     * the fine-grained "can client X do transition Y" check lives on the
     * Booking model (canActorTransition). Both must pass.
     */
    public function update(User $user, Booking $booking): bool
    {
        if (! $user->can('bookings.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Terminal bookings can't be updated by participants at all — only
        // admins can (e.g. reopen a mistakenly-cancelled one).
        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        $isParticipant = $booking->client_id === $user->id || $booking->supplier_id === $user->id;
        if (! $isParticipant) {
            return false;
        }

        // Role must actually match a participant slot — a client-role user
        // can't update as supplier even if somehow their id matches.
        if ($booking->client_id === $user->id   && $user->hasRole(RoleName::CLIENT->value))   return true;
        if ($booking->supplier_id === $user->id && $user->hasRole(RoleName::SUPPLIER->value)) return true;

        return false;
    }
}
