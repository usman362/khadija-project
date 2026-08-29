<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The first email an account ever receives.
 *
 * It says what this account can do next, in the words of whoever holds it — a
 * client is looking for professionals, a professional is looking for work.
 */
class WelcomeToGigResource extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name'),
            replyTo: array_filter([config('emails.reply_to')]),
        );
    }

    public function content(): Content
    {
        $role = $this->user->activeRole();

        return new Content(
            view: 'emails.auth.welcome',
            with: [
                'user'    => $this->user,
                'isPro'   => $role === 'professional',
                // Where they should go first. A welcome email whose button
                // lands on a page that means nothing to this account is worse
                // than a welcome email with no button.
                'ctaUrl'   => $role === 'professional' ? url('/professional/dashboard') : url('/client/post-event/choose'),
                'ctaLabel' => $role === 'professional' ? 'Go to my dashboard' : 'Post my first event',
            ],
        );
    }
}
