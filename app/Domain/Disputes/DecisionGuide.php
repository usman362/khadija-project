<?php

namespace App\Domain\Disputes;

/**
 * Rule R34 §5's Decision Matrix — a CONSISTENCY GUIDE for a human
 * investigator, and deliberately not a decision engine.
 *
 * The architecture says so twice, and R29 says it platform-wide. So this
 * class suggests and explains; it never decides, never writes to a case, and
 * has no method that produces an outcome on its own. An investigator reads
 * what comparable findings usually led to and then makes the call.
 *
 * The distinction is not decoration. §2 fixes the decider as "platform
 * conformance review", and §12 flags that describing this step as neutral,
 * impartial or algorithmic carries real exposure in DC in particular. A class
 * that returned an outcome would make the label untrue however it was worded.
 */
final class DecisionGuide
{
    /**
     * What comparable findings have usually led to.
     *
     * Each entry is a suggestion plus the reasoning, because a guide that
     * gives an answer without the reasoning is an engine with extra steps —
     * the investigator has to be able to disagree with it on the merits.
     *
     * @return array<int, array{finding:string, suggests:string, because:string}>
     */
    public static function all(): array
    {
        $c = DisputeClassification::class;

        return [
            [
                'finding'  => 'No evidence of non-conformance',
                'suggests' => $c::RELEASE_IN_FULL,
                'because'  => 'The standard is conformance to the agreed terms, not the client’s satisfaction. Nothing found means nothing to withhold.',
            ],
            [
                'finding'  => 'Service delivered but part of the agreed scope was not met',
                'suggests' => $c::PARTIAL_PRORATED,
                'because'  => 'The held balance splits to the unmet portion of that service line — not to the whole booking.',
            ],
            [
                'finding'  => 'Non-conformance that can still be corrected before the client needs it',
                'suggests' => $c::CURE_REDO,
                'because'  => 'A correctable gap with time left is usually worth more to the client than a refund. The balance stays paused until the cure or the deadline.',
            ],
            [
                'finding'  => 'Part of the service was not delivered and cannot be corrected',
                'suggests' => $c::REFUND_NON_CONFORMING,
                'because'  => 'The non-conforming portion refunds; the conforming portion still releases. The deposit is not part of this either way.',
            ],
            [
                'finding'  => 'Professional did not attend at all',
                'suggests' => $c::REFUND_NON_CONFORMING,
                'because'  => 'Nothing of that service line conformed, so nothing of its held balance releases.',
            ],
            [
                'finding'  => 'The disagreement is about what the contract said, not about what happened',
                'suggests' => $c::PARTIAL_PRORATED,
                'because'  => 'Read the agreed scope as written. Where it is genuinely ambiguous, the split follows what was actually delivered against it.',
            ],
            [
                'finding'  => 'Evidence submitted by a party was fabricated or altered',
                'suggests' => $c::REFUND_NON_CONFORMING,
                'because'  => 'Fraud is a case-level finding as well as a financial one — see the resolution type, the repeat-offender ladder in §7, and account action.',
            ],
        ];
    }

    /**
     * The wording rules §12 flags, kept where a developer will trip over them.
     *
     * Step 2 is a platform conformance review. Calling it neutral, impartial,
     * unbiased or fair is a claim about independence the process does not
     * make — GigResource is a party to the contract it is reviewing. "Neutral
     * third party" belongs only to an actual outside party at Step 4.
     */
    public const BANNED_WORDING = ['neutral', 'impartial', 'unbiased', 'fair', 'algorithmic', 'automated review'];

    /**
     * §4's Evidence Weight Guide — also a consistency aid, also not scoring.
     *
     * There are no numbers here on purpose. A weight of 0.8 against 0.3 is a
     * scoring system whatever the surrounding text calls it, and the moment
     * two weights are added together an investigator has been replaced.
     */
    public const EVIDENCE_WEIGHT = [
        'platform_contract'   => ['label' => 'Platform contract or messages', 'weight' => 'Very high'],
        'platform_timeline'   => ['label' => 'Platform timeline or activity record', 'weight' => 'Very high'],
        'timestamped_upload'  => ['label' => 'Timestamped upload or venue documentation', 'weight' => 'High'],
        'third_party_invoice' => ['label' => 'Third-party invoice or photographs', 'weight' => 'Medium'],
        'witness_statement'   => ['label' => 'Witness statement', 'weight' => 'Depends on context'],
        'verbal_allegation'   => ['label' => 'Verbal allegation', 'weight' => 'Low unless corroborated'],
    ];
}
