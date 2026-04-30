<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects all requests to the canonical hostname configured via
 * CANONICAL_HOST in the .env file. Designed for the gigresource.com
 * setup where `dashboard.gigresource.com` should land users on
 * `gigresource.com` instead.
 *
 * .env keys:
 *   CANONICAL_HOST=gigresource.com           (required to enable)
 *   CANONICAL_REDIRECT_HTTPS=true            (force https — default true)
 *   CANONICAL_REDIRECT_STATUS=301            (default 301 permanent)
 *
 * Behavior:
 *   • If CANONICAL_HOST is empty, the middleware is a no-op (safe in
 *     local dev where you don't want surprise redirects).
 *   • If the incoming Host header doesn't match CANONICAL_HOST, the
 *     full URL — path + query — is rebuilt against the canonical host
 *     and returned as a 301.
 *   • Health check ('/up') is excluded so uptime monitors never get
 *     a 301 instead of a 200.
 */
class CanonicalDomainRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = trim((string) env('CANONICAL_HOST', ''));

        // Off in local/dev unless explicitly set
        if ($canonical === '') {
            return $next($request);
        }

        // Don't 301 the health-check endpoint
        if ($request->path() === 'up') {
            return $next($request);
        }

        $forceHttps  = filter_var(env('CANONICAL_REDIRECT_HTTPS', true), FILTER_VALIDATE_BOOLEAN);
        $statusCode  = (int) env('CANONICAL_REDIRECT_STATUS', 301);
        $currentHost = strtolower($request->getHost());
        $needsScheme = $forceHttps && ! $request->isSecure();
        $needsHost   = $currentHost !== strtolower($canonical);

        if (! $needsHost && ! $needsScheme) {
            return $next($request);
        }

        $scheme = $forceHttps ? 'https' : $request->getScheme();
        $target = $scheme . '://' . $canonical . $request->getRequestUri();

        return redirect()->away($target, $statusCode);
    }
}
