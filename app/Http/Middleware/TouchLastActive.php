<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a signed-in person was last here, to the nearest five minutes.
 *
 * Written after the response so it never slows a page, and at most every five
 * minutes so a busy session does not write on every click. Straight to the
 * table: the users row's updated_at is not "the profile changed" because
 * someone opened a page.
 */
class TouchLastActive
{
    private const EVERY_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user) {
            $last = $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
            if (! $last || $last->lt(now()->subMinutes(self::EVERY_MINUTES))) {
                DB::table('users')->where('id', $user->id)->update(['last_active_at' => now()]);
            }
        }

        return $response;
    }
}
