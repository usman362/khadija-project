<?php

namespace App\Domain\Disputes;

/**
 * Rule R34 Phase 1 artifact 5 — the notification matrix.
 *
 * §11 asks for it "fully specified: trigger / recipient / channel / timing /
 * retry / cancellation", so every row carries all six. The last two are the
 * ones normally left out and the ones that cause the damage: a reminder that
 * keeps firing after a case closes, or an email that silently fails and is
 * never sent again.
 *
 * TIMING here means WHEN A MESSAGE IS SENT relative to something that has
 * already happened. It never means a deadline. §12 holds every filing,
 * response and expiry window for attorney review, and Virginia treats
 * deviating from your own published process as a standalone violation — so a
 * row whose timing depends on a deadline says `deadline_dependent` and stays
 * unschedulable until Operations and counsel set the number. Building it any
 * other way would have picked the deadline by accident, in a reminder.
 */
final class NotificationMatrix
{
    /** Timing that cannot be scheduled until §12 is resolved. */
    public const DEADLINE_DEPENDENT = 'deadline_dependent';

    /**
     * @return array<int, array{
     *     trigger:string, recipients:array<int,string>, channels:array<int,string>,
     *     timing:string, retry:string, cancellation:string, note?:string
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'trigger'      => 'case_filed',
                'recipients'   => ['filing party', 'responding party', 'intake_specialist'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour; in-app never retries, it persists',
                'cancellation' => 'none — the filing happened',
                'note'         => 'The responding party learns of the case from us, not from the other party. §9 forbids off-platform pressure; being told by the person who filed is how that starts.',
            ],
            [
                'trigger'      => 'case_filed_severity_4_5',
                'recipients'   => ['fraud_specialist', 'super_admin'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
                'note'         => 'Severity 4–5 skip Direct Resolution (§2), so nobody is waiting on the parties to talk. If this is missed the case sits unowned.',
            ],
            [
                'trigger'      => 'balance_paused',
                'recipients'   => ['professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate, with the case-filed notice',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
                'note'         => '§8 — money stopping is the part a professional notices first. Discovering it without being told is how a support ticket becomes a complaint.',
            ],
            [
                'trigger'      => 'response_awaited',
                'recipients'   => ['responding party'],
                'channels'     => ['in_app', 'email'],
                'timing'       => self::DEADLINE_DEPENDENT,
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancelled the moment a response is submitted, or the case leaves Direct Resolution / Awaiting Response',
                'note'         => 'Cannot be scheduled: the reminder interval would state the response window, and §12 has not set one.',
            ],
            [
                'trigger'      => 'evidence_submitted',
                'recipients'   => ['other party', 'case owner'],
                'channels'     => ['in_app'],
                'timing'       => 'batched — one summary per party per day, not one per file',
                'retry'        => 'none, in-app persists',
                'cancellation' => 'suppressed once the case is terminal',
                'note'         => 'Batched deliberately. A party uploading twelve photographs should not send the other side twelve emails.',
            ],
            [
                'trigger'      => 'evidence_requested',
                'recipients'   => ['party the request is addressed to'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancelled when the request is satisfied or withdrawn',
            ],
            [
                'trigger'      => 'settlement_proposed',
                'recipients'   => ['other party'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancelled if the proposal is retracted or the case moves to Formal Investigation',
            ],
            [
                'trigger'      => 'moved_to_formal_investigation',
                'recipients'   => ['client', 'professional', 'case owner'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
                'note'         => 'Wording is fixed by §2: platform conformance review. DecisionGuide::BANNED_WORDING lists the terms this message must never reach for.',
            ],
            [
                'trigger'      => 'case_reassigned',
                'recipients'   => ['incoming staff member', 'outgoing staff member'],
                'channels'     => ['in_app'],
                'timing'       => 'immediate',
                'retry'        => 'none',
                'cancellation' => 'none',
                'note'         => 'Staff only. The parties are told who decided their case, not each internal handoff.',
            ],
            [
                'trigger'      => 'decision_issued',
                'recipients'   => ['client', 'professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 5 attempts over 24 hours',
                'cancellation' => 'never cancelled — this is the Resolution/Outcome Notice (§2 Step 3)',
                'note'         => 'More retries than any other row. This is the record of what was decided; a party who never received it is a party who was not told.',
            ],
            [
                'trigger'      => 'decision_revised',
                'recipients'   => ['client', 'professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 5 attempts over 24 hours',
                'cancellation' => 'never cancelled',
                'note'         => '§5 — a revision is announced, not slipped in. Both the original and the revision are shown.',
            ],
            [
                'trigger'      => 'cure_period_opened',
                'recipients'   => ['professional', 'client'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
            ],
            [
                'trigger'      => 'cure_deadline_approaching',
                'recipients'   => ['professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => self::DEADLINE_DEPENDENT,
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancelled the moment the cure is recorded, or the case closes',
                'note'         => 'The cure deadline is agreed per case (§5), so this one becomes schedulable as soon as a case sets its own date — unlike the platform-wide windows in §12.',
            ],
            [
                'trigger'      => 'financial_outcome_executed',
                'recipients'   => ['client', 'professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'on confirmation from the payment provider, never on the decision alone',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
                'note'         => '§8 separates a platform decision from payment processing. Telling someone they have been paid before the processor says so is the one message here that can be false.',
            ],
            [
                'trigger'      => 'payment_provider_action',
                'recipients'   => ['client', 'professional', 'finance_reviewer', 'legal_administrator'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate on notice from the provider',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'none',
                'note'         => '§8 — a chargeback under the provider\'s own rules. Disclosed up front, not discovered after the fact.',
            ],
            [
                'trigger'      => 'outside_escalation_requested',
                'recipients'   => ['legal_administrator', 'other party'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancelled if the request is withdrawn before it is sent onward',
            ],
            [
                'trigger'      => 'case_closed',
                'recipients'   => ['client', 'professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancels every pending reminder on this case',
                'note'         => 'The row that stops the others. A closed case that keeps sending reminders is the most common way this kind of module embarrasses itself.',
            ],
            [
                'trigger'      => 'case_expired',
                'recipients'   => ['client', 'professional'],
                'channels'     => ['in_app', 'email'],
                'timing'       => self::DEADLINE_DEPENDENT,
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancels every pending reminder on this case',
                'note'         => 'The expiry window itself is §12. Nothing here decides when a case expires.',
            ],
            [
                'trigger'      => 'case_withdrawn',
                'recipients'   => ['other party', 'case owner'],
                'channels'     => ['in_app', 'email'],
                'timing'       => 'immediate',
                'retry'        => 'email: 3 attempts over 1 hour',
                'cancellation' => 'cancels every pending reminder on this case',
            ],
            [
                'trigger'      => 'repeat_pattern_flagged',
                'recipients'   => ['fraud_specialist', 'super_admin'],
                'channels'     => ['in_app'],
                'timing'       => 'immediate',
                'retry'        => 'none',
                'cancellation' => 'none',
                'note'         => 'Staff only, and never sent to the account concerned. §7 — a pattern is an input to a review, not a finding, and telling someone they have been flagged makes it one.',
            ],
        ];
    }

    /** Rows that cannot be scheduled until §12 is resolved. */
    public static function blockedOnLegalReview(): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $row) => $row['timing'] === self::DEADLINE_DEPENDENT,
        ));
    }

    /**
     * Which pending notifications a state change should cancel.
     *
     * Reading it off the matrix rather than hand-listing it per state keeps
     * the two from drifting: a row added above with "cancels every pending
     * reminder" is honoured without anyone remembering to come back here.
     */
    public static function cancelsAllPending(string $trigger): bool
    {
        foreach (self::all() as $row) {
            if ($row['trigger'] === $trigger) {
                return str_contains($row['cancellation'], 'every pending reminder');
            }
        }

        return false;
    }
}
