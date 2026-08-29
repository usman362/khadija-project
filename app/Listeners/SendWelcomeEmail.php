<?php

namespace App\Listeners;

use App\Mail\WelcomeToGigResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The welcome email, sent once, on Laravel's Registered event.
 *
 * Registered here by Laravel's own listener discovery, which scans app/Listeners
 * and reads the handle() type-hint. It does NOT also need an Event::listen in a
 * provider — adding one registers the listener twice, and the client gets two
 * identical welcomes.
 *
 * NOT on the app's own UserRegistered: registration fires both, but
 * UserRegistered only for clients and professionals (an influencer signup is
 * created directly). Listening to both would send clients two welcomes and
 * influencers none — Registered is the one that fires for everybody.
 */
class SendWelcomeEmail implements ShouldQueue
{
    public function handle(Registered $event): void
    {
        if (! config('emails.lifecycle.enabled')) {
            return;
        }

        $user = $event->user;

        if (! filter_var($user->email ?? '', FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeToGigResource($user));
        } catch (Throwable $e) {
            // A greeting is not worth losing the account over. Registration has
            // already succeeded by this point; if the mail host is down, the
            // person is still signed up and we find out from the log.
            Log::warning('Welcome email failed', [
                'user_id' => $user->id,
                'reason'  => $e->getMessage(),
            ]);
        }
    }
}
