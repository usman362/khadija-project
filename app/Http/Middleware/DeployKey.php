<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The deploy endpoints are not for the public.
 *
 * They were: /deploy/git-pull, /deploy/migrate, /deploy/seed and
 * /deploy/cache-clear had no authentication at all, so anybody who guessed the
 * path could pull code onto production, run migrations, or run a seeder.
 *
 * The seeder is the one that matters. Running CategorySeeder on a v2 site once
 * created a second copy of all 360 categories, 106 event types became 153, and
 * the client believed their uploaded pictures had been wiped. That was us,
 * deliberately. A stranger could have done it by loading a URL.
 *
 * A shared key rather than a login, because these are called by a script and by
 * curl, not by a person in a browser. Compared with hash_equals so the check
 * cannot be timed, and refused outright when no key is configured — an empty
 * secret matching an empty parameter is how this kind of guard quietly lets
 * everybody through.
 */
class DeployKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.deploy_key', '');
        $given    = (string) ($request->query('key') ?? $request->header('X-Deploy-Key', ''));

        if ($expected === '' || ! hash_equals($expected, $given)) {
            // 404 rather than 403: a "wrong key" tells whoever is knocking that
            // there is a door.
            abort(404);
        }

        return $next($request);
    }
}
