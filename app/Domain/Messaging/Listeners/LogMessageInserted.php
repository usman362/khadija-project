<?php

namespace App\Domain\Messaging\Listeners;

use App\Domain\Messaging\Events\MessageInserted;
use Illuminate\Support\Facades\Log;

class LogMessageInserted
{
    public function handle(MessageInserted $event): void
    {
        Log::info('Message inserted', [
            'message_id' => $event->message->id,
            'sender_id' => $event->message->sender_id,
            'recipient_id' => $event->message->recipient_id,
            'event_id' => $event->message->event_id,
            'booking_id' => $event->message->booking_id,
        ]);
    }
}
