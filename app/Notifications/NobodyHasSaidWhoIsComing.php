<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * The professional said they would decide nearer the time, and has not.
 *
 * Sir Peter, 29 October, point 8: require confirmation at least 24 hours
 * before the event, send reminders, and flag a missed deadline.
 *
 * It says what to do, not only that something is wrong. A reminder that names
 * no action is a reminder somebody puts off, and the action here is one
 * choice on one screen, including the choice of themselves.
 */
class NobodyHasSaidWhoIsComing extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        /** 'first', 'final' or 'missed'. */
        public string $stage,
        public ?\Illuminate\Support\Carbon $confirmBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'attendance_unconfirmed',
            'booking_id' => $this->booking->id,
            'event_id'   => $this->booking->event_id,
            // Khadijah's wording, 29 October, for the dashboard alert.
            'title'      => $this->headline(),
            'event'      => $this->booking->event?->title,
            'stage'      => $this->stage,
            'confirm_by' => $this->confirmBy?->toIso8601String(),
            'message'    => $this->sentence(),
            'cta'        => 'Assign Contact Now',
            'url'        => $this->booking->event_id
                ? route('professional.gigs.show', $this->booking->event_id)
                : route('professional.gigs.index'),
        ];
    }

    /** The alert's own line, as Khadijah wrote it. */
    public function headline(): string
    {
        return $this->stage === 'missed'
            ? 'Overdue: Assign On-Site Contact'
            : 'Action Required: Assign On-Site Contact';
    }

    public function sentence(): string
    {
        $event = $this->booking->event?->title ?? 'your event';

        if ($this->stage === 'missed') {
            return 'You have not said who is attending ' . $event . ', and the deadline has passed. '
                . 'Please assign your primary on-site contact now so the client knows who will be '
                . 'arriving. Choosing yourself counts.';
        }

        $by = $this->confirmBy?->format('M j, g:i A');

        return 'Your event ' . $event . ' starts soon. Please assign your primary on-site contact so '
            . 'the client knows who will be arriving'
            . ($by ? ', by ' . $by : '')
            . '. Choosing yourself counts.';
    }
}
