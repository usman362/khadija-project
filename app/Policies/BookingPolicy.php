<?php

namespace App\Policies;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Booking;
use App\Models\User;

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

    public function update(User $user, Booking $booking): bool
    {
        if (! $user->can('bookings.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(RoleName::SUPPLIER->value) && $booking->supplier_id === $user->id) {
            return in_array($booking->status, ['requested', 'confirmed'], true);
        }

        if ($user->hasRole(RoleName::CLIENT->value) && $booking->client_id === $user->id) {
            return in_array($booking->status, ['requested', 'confirmed'], true);
        }

        return false;
    }
}
