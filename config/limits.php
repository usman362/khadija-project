<?php

/*
|--------------------------------------------------------------------------
| What one account may do, and how often
|--------------------------------------------------------------------------
|
| Khadijah's revised sheet, 29 Aug 2026. Her framing, in her words:
| GigResource is a seasonal, event-based marketplace, so these exist to stop
| spam, abuse, fraud and technical overload — NOT to restrain somebody doing
| ordinary work. A caterer in June is not a spammer.
|
| The numbers live here rather than in the code because she asked for exactly
| that: "monitor actual usage after launch and adjust limits using platform
| data rather than copying limits from everyday marketplaces." Changing one is
| an edit to this file, not a code change.
|
| Each entry is [max attempts, window in minutes].
|
| NOT here on purpose:
|
|   Login attempts   already enforced by App\Support\LoginLockout, and her
|                    sheet says to leave security limits where they are.
|   File size        already enforced per upload by validation.
|   5 responses per lead   a cap on the REQUEST rather than on a person —
|                    once five professionals answer, the request stops taking
|                    more. That changes how the bidding board behaves, so it
|                    is not a counter and is not built until she confirms it.
|
*/

return [

    'enabled' => env('USER_LIMITS_ENABLED', true),

    /*
     | Rule names carry no dots. config() reads a dot as nesting, so a key
     | like 'client.postings' resolves to rules -> client -> postings and
     | comes back NULL — which UserLimit reads as "no such rule" and lets
     | everything through. A limit that silently does not exist is worse than
     | no limit, because the sheet says it is there.
     */
    'rules' => [

        // ── Everyone ────────────────────────────────────────────────
        'messages-day'  => ['max' => 25, 'minutes' => 60 * 24,
            'message' => 'You have sent a lot of messages today. Please try again tomorrow.'],
        'messages-hour' => ['max' => 10, 'minutes' => 60,
            'message' => 'You are sending messages very quickly. Please wait a little and try again.'],

        'password-reset' => ['max' => 3, 'minutes' => 60 * 24,
            'message' => 'You have asked to reset your password a few times today. Please try again tomorrow.'],

        'email-resend' => ['max' => 3, 'minutes' => 60 * 24,
            'message' => 'We have already sent that email a few times today. Please check your inbox and spam folder.'],

        'reports' => ['max' => 5, 'minutes' => 60 * 24,
            'message' => 'You have reported a few things today. Please try again tomorrow.'],

        // ── Clients ─────────────────────────────────────────────────
        'client-postings' => ['max' => 10, 'minutes' => 60 * 24,
            'message' => 'You have posted 10 requests today. Please try again tomorrow.'],

        'client-invitations' => ['max' => 30, 'minutes' => 0,   // per event, not per window
            'message' => 'You can invite up to 30 people to one event.'],

        'client-images' => ['max' => 25, 'minutes' => 60 * 24,
            'message' => 'You have uploaded 25 images today. Please try again tomorrow.'],

        // ── Professionals ───────────────────────────────────────────
        'pro-responses' => ['max' => 30, 'minutes' => 60 * 24,
            'message' => 'You have responded to 30 requests today. Please try again tomorrow.'],

        'pro-images' => ['max' => 50, 'minutes' => 60 * 24,
            'message' => 'You have uploaded 50 images today. Please try again tomorrow.'],
    ],
];
