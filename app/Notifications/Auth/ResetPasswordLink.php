<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Password reset, in the app's template. Laravel's default is plain and
 * unbranded, which is the worst look for the one email people are most
 * suspicious of.
 */
class ResetPasswordLink extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Reset your password')
            ->view('emails.auth.reset-password', [
                'user'    => $notifiable,
                'url'     => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)),
                'minutes' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
            ]);
    }
}
