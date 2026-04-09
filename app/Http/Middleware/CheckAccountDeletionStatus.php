<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the authenticated user has a pending deletion request, force them to
 * the "restore account" screen. They can either restore or wait for purge.
 */
class CheckAccountDeletionStatus
{
    /**
     * Routes that must remain accessible even while the user is in the
     * pending-deletion grace period, so they can cancel / logout.
     */
    private const ALLOWED_ROUTES = [
        'account.deletion.restore.show',
        'account.deletion.restore',
        'account.reactivation.success',
        'account.reactivation.cancel',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasPendingDeletion() && !in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return redirect()->route('account.deletion.restore.show');
        }

        return $next($request);
    }
}
