<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The verification link, in the app's own template rather than Laravel's
 * unstyled default — a first email that looks nothing like the site is the one
 * people report as phishing.
 */
class VerifyEmailAddress extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Confirm your email address')
            ->view('emails.auth.verify', [
                'user' => $notifiable,
                'url'  => $this->verificationUrl($notifiable),
                // Said plainly, because it decides whether this email is
                // urgent or something they can come back to.
                'blocking' => (bool) config('emails.verification.required'),
            ]);
    }
}
