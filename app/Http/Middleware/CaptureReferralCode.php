<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferralCode
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query('ref');
        /** @var Response $response */
        $response = $next($request);

        if ($code) {
            $cookieName = (string) config('influencer.cookie_name', 'khadija_ref');
            $days = (int) config('influencer.cookie_days', 30);
            $response->headers->setCookie(
                Cookie::create($cookieName, $code, time() + ($days * 86400), '/', null, false, true)
            );
        }

        return $response;
    }
}
