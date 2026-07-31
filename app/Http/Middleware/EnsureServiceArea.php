<?php

namespace App\Http\Middleware;

use App\Support\ServiceArea;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a coming-soon account from transacting, without locking it out.
 *
 * Peter's registration rule (2026-07-30) is that everyone registers and finds
 * out afterwards whether we operate where they are. That only holds up if the
 * account is real: an out-of-area user keeps their profile, their tools and a
 * browsable marketplace — they simply cannot commit to anything in it, because
 * every commitment ends in a contract we cannot service in their state.
 *
 * So the line is drawn at writes, not at pages:
 *
 *   · anything that is not a GET is blocked, except the routes that only touch
 *     the user's own account (profile, password, notifications, avatar) — those
 *     have to keep working or the account is not editable;
 *   · a handful of GETs are blocked too, but only the front doors of the
 *     request-creation wizards. Better to say no at the door than after seven
 *     steps of typing.
 *
 * It fails closed on purpose. A new transactional route is gated the day it is
 * added, without anyone remembering to come back here.
 */
class EnsureServiceArea
{
    /**
     * Route-name prefixes a coming-soon account may still write to. Their own
     * account, and nothing that reaches another party.
     */
    private const ACCOUNT_ROUTES = [
        // Their own account.
        'client.profile.',
        'professional.profile.',
        'influencer.profile.',
        'profile.',
        'settings.',
        'notifications.',
        'password.',
        'account.deletion.',
        'logout',
        'role.switch',
        'role.enable',
        'policy.sign',
        'attachments.store',

        // The waitlist screen and its "tell me when you launch" checkbox —
        // blocking the one thing we ask them to do would be absurd.
        'register.welcome',

        // Planning tools compute locally and commit to nothing. They are the
        // reason a coming-soon account is worth keeping, so they stay open.
        'ai-tools.',

        // Support, and a legal report route that must never be gated.
        'ai-chatbot.',
        'dmca-policy.report',

        // Saving a professional for later, and a tool result saved onto an
        // event — both are private notes to self.
        'client.saved-professionals.',
        'client.ai-artifacts.',
    ];

    /**
     * GET routes blocked as well — the entry points of flows whose only
     * possible ending is a request we would have to refuse.
     */
    private const BLOCKED_ENTRY = [
        'client.bsr.',
        'client.esr.',
        'client.post-event.',
        'client.finalize.',
        'client.events.create',
        'client.direct-offers.create',
        'professional.bid.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admins are staff, not customers — where they live is not a question
        // the marketplace gets to ask, and locking one out of the admin because
        // their profile says Ohio would be a very quiet outage.
        if (ServiceArea::allows($user) || $user?->hasRole('admin')) {
            return $next($request);
        }

        $name = (string) $request->route()?->getName();

        $blocked = $request->isMethod('GET')
            ? $this->matches($name, self::BLOCKED_ENTRY)
            : ! $this->matches($name, self::ACCOUNT_ROUTES);

        if (! $blocked) {
            return $next($request);
        }

        $profile = $request->user()?->profile;
        $where   = ServiceArea::describe($profile?->city, $profile?->state, $profile?->country);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "GigResource has not opened in {$where} yet.",
            ], 403);
        }

        // A GET gets the full explanation; a blocked write bounces back to the
        // page it came from, so the user keeps whatever they had typed.
        return $request->isMethod('GET')
            ? response()->view('errors.service-area', ['where' => $where], 403)
            : back()->with('error', "GigResource has not opened in {$where} yet, so this action is not available on your account.");
    }

    private function matches(string $name, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
