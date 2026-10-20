<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Somebody wrote to you.
 *
 * Sir Peter, 18 October: notifications must be "fully functional and getting
 * every notification from whatever the clients do ... to alerts, inbox messages
 * to etc".
 *
 * A message already broadcast, which updates the thread live for anybody with
 * it open. Nothing told the person who was not looking: no bell, no email, and
 * the unread count only appeared once they happened to visit Messages. A
 * message sat unanswered because nobody knew it was there, which on a
 * marketplace is a booking lost rather than a feature missing.
 *
 * The preview is deliberately short and the body is never put in the email. A
 * notification says somebody wrote; reading it happens on the site, where the
 * thread, the attachments and the reply box are.
 */
class NewMessageReceived extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * In-app always; email only if this account still wants messages mail.
     *
     * The `messages` preference already existed for exactly this and had
     * nothing using it.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'acceptsEmail') && $notifiable->acceptsEmail('messages')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'new_message',
            'conversation_id' => $this->message->conversation_id,
            'message_id'      => $this->message->id,
            'sender'          => $this->message->sender?->name ?? 'Someone',
            'preview'         => $this->preview(),
            'message'         => ($this->message->sender?->name ?? 'Someone') . ' sent you a message.',
            'url'             => route('conversations.index', ['c' => $this->message->conversation_id]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject('New message from ' . $data['sender'])
            ->view('emails.messages.received', [
                'user' => $notifiable,
                'data' => $data,
            ]);
    }

    /**
     * Enough to tell one thread from another, and no more.
     *
     * An attachment with no words is still a message worth knowing about, so
     * it says so rather than showing an empty line.
     */
    private function preview(): string
    {
        $body = trim((string) $this->message->body);

        if ($body === '') {
            return 'Sent an attachment.';
        }

        return Str::limit(Str::of($body)->squish()->value(), 90);
    }
}
