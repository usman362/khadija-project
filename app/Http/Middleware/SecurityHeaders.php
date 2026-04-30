<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a baseline of security headers to every web response.
 *
 * Headers applied:
 *   • X-Content-Type-Options: nosniff   — stops MIME sniffing attacks
 *   • X-Frame-Options: SAMEORIGIN       — clickjacking protection
 *   • X-XSS-Protection: 0               — disable legacy XSS auditor (CSP is the modern replacement)
 *   • Referrer-Policy: strict-origin-when-cross-origin — limits info leak via Referer
 *   • Permissions-Policy: tight default — disables risky browser APIs we don't use
 *   • Strict-Transport-Security (only when HTTPS is detected) — force HTTPS for 1 year
 *   • Content-Security-Policy: scoped to the marketplace's known sources — last
 *     line of defence against any XSS that slips through Blade's auto-escape
 *
 * The CSP is intentionally permissive on `script-src` for now ('unsafe-inline'
 * is required by all the inline <script> blocks across the views). When the
 * inline scripts are migrated to external files we can tighten that to
 * 'self' + nonces.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options',  'nosniff');
        $response->headers->set('X-Frame-Options',         'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection',        '0');
        $response->headers->set('Referrer-Policy',         'strict-origin-when-cross-origin');

        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), payment=(self), usb=(), interest-cohort=()'
        );

        // HSTS — only on HTTPS so we don't lock local dev into an https-only state.
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                // Scripts: 'unsafe-inline' is needed because the Blade views
                // ship inline scripts. CDNs whitelisted: reCAPTCHA, gstatic,
                // GTM, NobleUI dashboard template, cdnjs (font-awesome/etc).
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://www.googletagmanager.com https://nobleui.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
                // Styles: inline <style> blocks live in layouts. CDNs needed
                // by the dashboard template + font services.
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://nobleui.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
                // Fonts: Google + Bunny + cdnjs (Font Awesome ships fonts).
                "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net https://cdnjs.cloudflare.com https://nobleui.com",
                // Images: allow data URIs (icons), Unsplash (banners), and our own hosts.
                "img-src 'self' data: https: blob:",
                // Media (videos): allow same origin + Unsplash for hero promos.
                "media-src 'self' https://images.unsplash.com",
                // XHR / WebSocket: restrict to same-origin + Pusher / our APIs.
                "connect-src 'self' https://api.openai.com https://www.google-analytics.com",
                // Frames: only allow YouTube nocookie (How It Works video) + reCAPTCHA.
                "frame-src 'self' https://www.youtube-nocookie.com https://www.google.com",
                // Form actions: same-origin only — prevents form-jacking.
                "form-action 'self'",
                // No legacy Flash / plugins.
                "object-src 'none'",
                // Block iframe-embedding from foreign origins (defense in depth alongside X-Frame-Options).
                "frame-ancestors 'self'",
                // Auto-upgrade any leftover http:// asset URLs to https in production.
                "upgrade-insecure-requests",
            ])
        );

        return $response;
    }
}
