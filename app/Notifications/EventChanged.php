<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rule R33 §3 — the client changed something material about an event this
 * professional already has a proposal on.
 *
 * The wording is the rule's: Review / Edit Proposal / Withdraw Proposal. The
 * proposal itself stays valid until the professional acts on it — never
 * auto-withdrawn, auto-rejected or auto-repriced. That is the whole reason
 * this is a notification and not a state change: only the person who quoted
 * the work can say whether their price still stands.
 */
class EventChanged extends Notification
{
    use Queueable;

    /** @param array<int, string> $changed The major fields that moved (§3). */
    public function __construct(public Event $event, public array $changed = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'event_changed',
            'event_id' => $this->event->id,
            'title'    => $this->event->title,
            'changed'  => $this->changed,
            'message'  => 'This event has changed — review your proposal.',
            'url'      => route('professional.bidding-board.index'),
        ];
    }
}
