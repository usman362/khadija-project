<?php

namespace App\Notifications;

use App\Models\Finalization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something moved on an agreement, and the other side needs to know.
 *
 * Sir Peter, 17 October: "Each step or update of this process we need to add it
 * to the notifications, message (inbox), both exterior email addresses,
 * transactions, and wherever else too."
 *
 * This is the notification and the email. The inbox copy is posted separately,
 * as a system message in the conversation the two of them already share, so it
 * sits in the thread rather than in a parallel list of its own. Transactions
 * are deliberately not here: a signature is not a money event, and what belongs
 * in that record is settled by the ledger rather than guessed at.
 *
 * Every step carries the version it happened on. A signature here has always
 * been a signature OF A VERSION, so "they signed" without saying which terms is
 * the one thing that must never be said.
 */
class AgreementStepHappened extends Notification
{
    use Queueable;

    public const CHANGES_SENT = 'changes_sent';
    public const APPROVED     = 'approved';
    public const SIGNED       = 'signed';
    public const DECLINED     = 'declined';
    public const FUNDED       = 'funded';

    /**
     * @param  string  $step  one of the constants above
     * @param  array<int, string>  $changed  field labels that moved, for changes_sent
     */
    public function __construct(
        public Finalization $finalization,
        public string $step,
        public string $actor,
        public int $version,
        public array $changed = [],
    ) {}

    /**
     * In-app always; email only if this account still wants that category.
     *
     * An agreement is a booking, not an event notice: this is news about money
     * and obligations, so it rides the bookings preference.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'acceptsEmail') && $notifiable->acceptsEmail('bookings')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'agreement_' . $this->step,
            'finalization_id' => $this->finalization->id,
            'event_id'        => $this->finalization->event_id,
            'title'           => $this->finalization->event?->title,
            'version'         => $this->version,
            'changed'         => $this->changed,
            'message'         => $this->sentence(),
            'url'             => route('client.finalize.overview', $this->finalization),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->subject())
            ->view('emails.agreement.step', [
                'user' => $notifiable,
                'data' => $this->toArray($notifiable),
                'step' => $this->step,
            ]);
    }

    /** What happened, in a sentence somebody can act on. */
    public function sentence(): string
    {
        $what = $this->changed === []
            ? ''
            : ' Changed: ' . implode(', ', $this->changed) . '.';

        return match ($this->step) {
            self::CHANGES_SENT => "{$this->actor} sent changes for review. The agreement is now on version {$this->version}, so earlier approvals and signatures no longer stand.{$what}",
            self::APPROVED     => "{$this->actor} approved version {$this->version}.",
            self::SIGNED       => "{$this->actor} signed version {$this->version}.",
            self::DECLINED     => "{$this->actor} declined the agreement.",
            self::FUNDED       => 'The deposit has been secured. Price and payment terms can no longer be changed here.',
            default            => "{$this->actor} updated the agreement.",
        };
    }

    private function subject(): string
    {
        $title = $this->finalization->event?->title ?? 'your event';

        return match ($this->step) {
            self::CHANGES_SENT => "Changes to review: {$title}",
            self::APPROVED     => "Terms approved: {$title}",
            self::SIGNED       => "Agreement signed: {$title}",
            self::DECLINED     => "Agreement declined: {$title}",
            self::FUNDED       => "Deposit secured: {$title}",
            default            => "Agreement updated: {$title}",
        };
    }
}
