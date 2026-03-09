<?php

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

class LogUserRegistered
{
    public function handle(UserRegistered $event): void
    {
        Log::info('User registered', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);
    }
}
