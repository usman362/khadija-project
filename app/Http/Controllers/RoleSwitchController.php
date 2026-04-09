<?php

namespace App\Http\Controllers;

use App\Domain\ActivityLog\Services\ActivityLogger;
use App\Domain\Auth\Enums\RoleName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleSwitchController extends Controller
{
    /** Roles eligible for the dual-mode switch. */
    private const SWITCHABLE_ROLES = [
        RoleName::CLIENT->value,
        RoleName::SUPPLIER->value,
    ];

    /**
     * Switch the active mode (must already have the target role).
     */
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', self::SWITCHABLE_ROLES)],
        ]);

        $user = $request->user();
        $target = $validated['role'];

        if (!$user->hasRole($target)) {
            return back()->with('error', "You don't have access to {$target} mode. Enable it first from your profile.");
        }

        session(['active_role' => $target]);

        return redirect()->route($target === RoleName::SUPPLIER->value ? 'professional.dashboard' : 'client.dashboard')
            ->with('status', 'Switched to ' . ($target === RoleName::SUPPLIER->value ? 'Professional' : 'Client') . ' mode.');
    }

    /**
     * Enable the alternate role for a user who currently has only one.
     * After enabling, the user is switched into the new mode.
     */
    public function enable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', self::SWITCHABLE_ROLES)],
        ]);

        $user = $request->user();
        $target = $validated['role'];

        // Admins don't switch modes
        if ($user->isAdmin()) {
            return back()->with('error', 'Administrators cannot enable client or professional modes.');
        }

        if ($user->hasRole($target)) {
            // Already has it — just switch
            session(['active_role' => $target]);
            return redirect()->route($target === RoleName::SUPPLIER->value ? 'professional.dashboard' : 'client.dashboard');
        }

        $user->assignRole($target);
        session(['active_role' => $target]);

        ActivityLogger::log('role_enabled', $user, ['role' => $target]);

        Log::info('User enabled alternate role', [
            'user_id' => $user->id,
            'role'    => $target,
        ]);

        $label = $target === RoleName::SUPPLIER->value ? 'Professional' : 'Client';
        return redirect()->route($target === RoleName::SUPPLIER->value ? 'professional.dashboard' : 'client.dashboard')
            ->with('status', "{$label} mode enabled. Welcome to your new workspace!");
    }
}
