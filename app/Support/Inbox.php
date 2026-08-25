<?php

namespace App\Support;

use App\Models\User;

/**
 * Where "go to my messages" actually goes.
 *
 * There are two inbox PAGES — one per portal — and one route named
 * `messages.index`, which is neither: it is `MessageController@index`, a JSON
 * API returning a paginated list of message rows.
 *
 * Three views linked to it. Clicking "Resolve Without Filing" on the Disputes
 * page dropped the client onto a screen of raw JSON. It was reported from
 * production, and the same link sits on the dispute rows and on the package
 * page.
 *
 * The name is the trap: `route('messages.index')` reads exactly like the page
 * anyone would want. So the choice lives here instead, once, and a view asks
 * for the inbox rather than guessing at a route name.
 */
final class Inbox
{
    /** The inbox page for whoever is looking, by the portal they are in. */
    public static function urlFor(?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return route('login');
        }

        return $user->isProfessionalMode()
            ? route('professional.chat.index')
            : route('client.chat.index');
    }

    /** The same, addressed to one conversation when we know which. */
    public static function conversationUrl(int $conversationId, ?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return route('login');
        }

        return $user->isProfessionalMode()
            ? route('professional.chat.show', $conversationId)
            : route('client.chat.show', $conversationId);
    }
}
