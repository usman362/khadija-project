<?php

namespace App\Domain\Agreements;

use App\Models\Conversation;
use App\Models\Finalization;
use App\Models\Message;
use App\Models\User;
use App\Notifications\AgreementStepHappened;

/**
 * Telling the other side what just happened to the agreement.
 *
 * Sir Peter, 17 October: "Each step or update of this process we need to add it
 * to the notifications, message (inbox), both exterior email addresses,
 * transactions, and wherever else too."
 *
 * So each step goes three places, and the three are deliberate rather than
 * duplication:
 *
 *   The NOTIFICATION is the bell: something needs your attention now.
 *   The INBOX copy is the record: it belongs in the thread those two people
 *     already share, beside the messages where they argued about the price, not
 *     in a separate list nobody rereads.
 *   The EMAIL is for the person who is not looking at the site, and only if
 *     they still want that category.
 *
 * Transactions are not here. A signature is not a money event, and what belongs
 * in the money record is decided by the ledger. That question is still with him.
 *
 * Only the OTHER party is notified. Telling somebody what they themselves just
 * did is noise, and it is how a notification list becomes something people
 * learn to ignore.
 */
final class AgreementNotices
{
    /** Changes were sent for review, which moves the version. */
    public static function changesSent(Finalization $f, User $by, array $changed = []): void
    {
        self::tell(
            $f,
            $by,
            AgreementStepHappened::CHANGES_SENT,
            array_values(array_map(
                fn (string $field) => EditAgreement::FIELDS[$field] ?? $field,
                array_keys($changed),
            )),
        );
    }

    public static function approved(Finalization $f, User $by): void
    {
        self::tell($f, $by, AgreementStepHappened::APPROVED);
    }

    public static function signed(Finalization $f, User $by): void
    {
        self::tell($f, $by, AgreementStepHappened::SIGNED);
    }

    public static function declined(Finalization $f, User $by): void
    {
        self::tell($f, $by, AgreementStepHappened::DECLINED);
    }

    /** The deposit landed, which closes the money to further edits (rule 6). */
    public static function funded(Finalization $f): void
    {
        $client = $f->client;

        if ($client) {
            // Both sides hear about money, because this is the step that ends
            // the negotiation rather than continuing it.
            self::tell($f, $client, AgreementStepHappened::FUNDED, [], both: true);
        }
    }

    /**
     * One step, three places.
     *
     * @param  array<int, string>  $changed  field labels, for the changes step
     * @param  bool  $both  tell the actor as well, for steps that end the negotiation
     */
    private static function tell(
        Finalization $f,
        User $by,
        string $step,
        array $changed = [],
        bool $both = false,
    ): void {
        $version = Workspace::version($f);
        $actor   = self::nameFor($f, $by);

        $notification = new AgreementStepHappened($f, $step, $actor, $version, $changed);

        foreach (self::recipients($f, $by, $both) as $person) {
            $person->notify($notification);
        }

        self::postToInbox($f, $notification->sentence());
    }

    /**
     * Who hears about it.
     *
     * @return list<User>
     */
    private static function recipients(Finalization $f, User $by, bool $both): array
    {
        $people = array_filter([$f->client, $f->supplier]);

        if (! $both) {
            $people = array_filter($people, fn (User $u) => $u->id !== $by->id);
        }

        return array_values($people);
    }

    /**
     * How the actor is named to the person reading it.
     *
     * "You" would be wrong here: only the other side is told, so the actor is
     * always somebody else from the reader's point of view.
     */
    private static function nameFor(Finalization $f, User $by): string
    {
        if ($by->id === $f->client_id) {
            return $f->client?->name ?? 'The client';
        }

        return $f->supplier?->name ?? 'The professional';
    }

    /**
     * The inbox copy, as a system message in the thread they share.
     *
     * Posted as `source: system` so it renders as an account of what happened
     * rather than as something either of them typed. If there is no thread yet
     * the step is still notified and emailed; a conversation is not invented
     * for it, because an empty thread with one system line in it reads as a
     * message nobody sent.
     */
    private static function postToInbox(Finalization $f, string $sentence): void
    {
        $conversation = self::conversationFor($f);

        if (! $conversation) {
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'event_id'        => $f->event_id,
            'sender_id'       => $f->client_id,
            'body'            => $sentence,
            'source'          => 'system',
        ]);
    }

    /** The thread these two already share about this event, if there is one. */
    private static function conversationFor(Finalization $f): ?Conversation
    {
        return Conversation::query()
            ->where('event_id', $f->event_id)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $f->client_id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $f->supplier_id))
            ->latest('id')
            ->first();
    }
}
