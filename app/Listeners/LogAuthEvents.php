<?php

namespace App\Listeners;

use App\Domain\ActivityLog\Services\ActivityLogger;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class LogAuthEvents
{
    public function handleLogin(Login $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        ActivityLogger::log(ActivityLogger::ACTION_LOGIN, $user);
    }

    public function handleLogout(Logout $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        ActivityLogger::log(ActivityLogger::ACTION_LOGOUT, $user);
    }

    public function handleFailed(Failed $event): void
    {
        $identifier = $event->credentials['email'] ?? null;

        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        ActivityLogger::log(
            ActivityLogger::ACTION_LOGIN_FAILED,
            $user,
            metadata: ['attempted_email' => $identifier],
            subjectIdentifier: $identifier,
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        ActivityLogger::log(ActivityLogger::ACTION_PASSWORD_RESET, $user);
    }

    public function subscribe($events): array
    {
        return [
            Login::class         => 'handleLogin',
            Logout::class        => 'handleLogout',
            Failed::class        => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
