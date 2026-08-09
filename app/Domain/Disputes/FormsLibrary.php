<?php

namespace App\Domain\Disputes;

/**
 * Rule R34 Phase 1 artifact 6 — the forms library, field by field.
 *
 * §1 settles what a form is here: "every form in this module is an electronic
 * record, and every certification/checkbox is an electronic signature — both
 * valid under ESIGN and each state's UETA adoption." So a certification field
 * is not a checkbox with some text beside it. What it must do to hold up:
 *
 *   the exact wording shown is stored with the record, not looked up later
 *   from a config file that may since have changed;
 *   it is never pre-ticked (R30's lesson — the contract checkbox on this
 *   platform shipped pre-ticked and wired to nothing);
 *   who signed, when, and from where is captured alongside it.
 *
 * `certification` fields below carry that contract. Everything else is an
 * ordinary field.
 *
 * Deliberately absent from every form: any statement of a deadline. §12 holds
 * the filing window, the response window and the expiry window for attorney
 * review, and Virginia treats deviating from your own published process as a
 * standalone violation — so a helper line reading "you have 14 days" would
 * publish a policy nobody has approved.
 */
final class FormsLibrary
{
    public const CLIENT       = 'client';
    public const PROFESSIONAL = 'professional';
    public const SHARED       = 'shared';
    public const STAFF        = 'staff';

    /**
     * @return array<string, array{
     *     title:string, audience:string, purpose:string,
     *     fields:array<int, array<string, mixed>>
     * }>
     */
    public static function all(): array
    {
        return [
            /* ── Filing ───────────────────────────────────────────── */

            'client_filing' => [
                'title'    => 'File a Dispute',
                'audience' => self::CLIENT,
                'purpose'  => 'Opens a case against one professional on one service line (§6).',
                'fields'   => [
                    ['name' => 'booking_id', 'label' => 'Which booking', 'type' => 'select', 'required' => true,
                     'note' => 'One service line only. On a multi-service event the client picks the professional they have a problem with — R12 does not allow a case against the whole event.'],
                    ['name' => 'taxonomy', 'label' => 'What went wrong', 'type' => 'select', 'required' => true,
                     'options' => 'DisputeClassification::TAXONOMY'],
                    ['name' => 'secondary_taxonomy', 'label' => 'Anything else that applies', 'type' => 'multiselect', 'required' => false],
                    ['name' => 'summary', 'label' => 'Describe what happened', 'type' => 'textarea', 'required' => true,
                     'note' => 'Asked as what happened, not what the client wants. The standard is conformance to the agreed terms (§2), and a form that opens with "what outcome do you want" invites an answer the process cannot promise.'],
                    ['name' => 'attempted_direct', 'label' => 'Have you already raised this with the professional?', 'type' => 'radio', 'required' => true,
                     'note' => 'Direct Resolution is the required first step below severity 4 (§2). The answer routes the case; it does not block filing.'],
                    ['name' => 'evidence', 'label' => 'Attach anything that supports this', 'type' => 'file[]', 'required' => false,
                     'note' => 'Runs through the R54 upload pipeline. Hashed on arrival (§4).'],
                    ['name' => 'certify_truthful', 'type' => 'certification', 'required' => true,
                     'text' => 'I certify that the information and any files I have provided are true and accurate to the best of my knowledge, and that I have not altered or fabricated any of them.',
                     'note' => '§7 lists fabricated evidence and edited screenshots as fraud. This is the signature that makes that finding possible.'],
                ],
            ],

            'professional_filing' => [
                'title'    => 'File a Dispute',
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'The same case type, filed the other way — non-payment, client no-show, scope changed on the day.',
                'fields'   => [
                    ['name' => 'booking_id', 'label' => 'Which booking', 'type' => 'select', 'required' => true],
                    ['name' => 'taxonomy', 'label' => 'What went wrong', 'type' => 'select', 'required' => true],
                    ['name' => 'summary', 'label' => 'Describe what happened', 'type' => 'textarea', 'required' => true],
                    ['name' => 'work_performed', 'label' => 'What you delivered', 'type' => 'textarea', 'required' => true,
                     'note' => 'Only on the professional\'s form. A conformance review compares delivery against the agreed scope, and the professional is the one who can describe the first half of that comparison.'],
                    ['name' => 'evidence', 'label' => 'Attach anything that supports this', 'type' => 'file[]', 'required' => false],
                    ['name' => 'certify_truthful', 'type' => 'certification', 'required' => true,
                     'text' => 'I certify that the information and any files I have provided are true and accurate to the best of my knowledge, and that I have not altered or fabricated any of them.'],
                ],
            ],

            /* ── Both parties ─────────────────────────────────────── */

            'response' => [
                'title'    => 'Respond to a Dispute',
                'audience' => self::SHARED,
                'purpose'  => 'The responding party\'s account. §4 requires evidence from both parties.',
                'fields'   => [
                    ['name' => 'position', 'label' => 'Your account of what happened', 'type' => 'textarea', 'required' => true],
                    ['name' => 'agrees_with', 'label' => 'Which parts you agree with', 'type' => 'textarea', 'required' => false,
                     'note' => 'Optional, and worth having: a case where both sides already agree on half the facts is a shorter case.'],
                    ['name' => 'evidence', 'label' => 'Attach anything that supports this', 'type' => 'file[]', 'required' => false],
                    ['name' => 'certify_truthful', 'type' => 'certification', 'required' => true,
                     'text' => 'I certify that the information and any files I have provided are true and accurate to the best of my knowledge, and that I have not altered or fabricated any of them.'],
                ],
            ],

            'evidence_submission' => [
                'title'    => 'Add Evidence',
                'audience' => self::SHARED,
                'purpose'  => 'Adds one item to an open case (§4).',
                'fields'   => [
                    ['name' => 'kind', 'label' => 'What kind of evidence', 'type' => 'select', 'required' => true,
                     'options' => 'DecisionGuide::EVIDENCE_WEIGHT',
                     'note' => 'Shown to the submitter without its weight. The Evidence Weight Guide is an aid for the investigator (§4); printing "Low unless corroborated" next to a person\'s account of their own wedding is not what it is for.'],
                    ['name' => 'description', 'label' => 'What this shows', 'type' => 'textarea', 'required' => true],
                    ['name' => 'file', 'label' => 'File', 'type' => 'file', 'required' => false],
                    ['name' => 'supersedes', 'label' => 'Does this replace something you submitted earlier?', 'type' => 'select', 'required' => false,
                     'note' => '§4 — no silent edits. Replacing is allowed; overwriting is not.'],
                    ['name' => 'certify_unaltered', 'type' => 'certification', 'required' => true,
                     'text' => 'I certify that this file is the original and that I have not edited or altered it.'],
                ],
            ],

            'settlement_agreement' => [
                'title'    => 'Settlement Agreement',
                'audience' => self::SHARED,
                'purpose'  => '§2 Step 1 — the guided format the platform can prompt during Direct Resolution.',
                'fields'   => [
                    ['name' => 'terms', 'label' => 'What both of you have agreed', 'type' => 'textarea', 'required' => true],
                    ['name' => 'amount_to_client', 'label' => 'Amount returning to the client', 'type' => 'money', 'required' => false,
                     'note' => 'Limited to the held balance. The deposit is non-refundable in every scenario including this one (§8, Cancellation Policy), so the form cannot offer it.'],
                    ['name' => 'amount_to_professional', 'label' => 'Amount releasing to the professional', 'type' => 'money', 'required' => false],
                    ['name' => 'certify_client', 'type' => 'certification', 'required' => true, 'signer' => self::CLIENT,
                     'text' => 'I agree to these terms and understand that this closes the dispute.'],
                    ['name' => 'certify_professional', 'type' => 'certification', 'required' => true, 'signer' => self::PROFESSIONAL,
                     'text' => 'I agree to these terms and understand that this closes the dispute.'],
                ],
                'note' => 'Two signatures, captured separately. Closes as Mutual Settlement (§5) — never reaches Formal Investigation.',
            ],

            'withdrawal' => [
                'title'    => 'Withdraw a Dispute',
                'audience' => self::SHARED,
                'purpose'  => 'The filing party closes their own case.',
                'fields'   => [
                    ['name' => 'reason', 'label' => 'Why you are withdrawing', 'type' => 'textarea', 'required' => true,
                     'note' => 'Required, and staff-visible. §7 tracks duplicate disputes as fraud; a pattern of file-and-withdraw is invisible without this.'],
                    ['name' => 'acknowledge_final', 'type' => 'certification', 'required' => true,
                     'text' => 'I understand that withdrawing closes this case and that the held balance will be released according to the original agreement.'],
                ],
            ],

            'outside_escalation_request' => [
                'title'    => 'Request Outside Escalation',
                'audience' => self::SHARED,
                'purpose'  => '§2 Step 4 — the single post-decision step. There is no internal appeal.',
                'fields'   => [
                    ['name' => 'grounds', 'label' => 'Why you are escalating', 'type' => 'textarea', 'required' => true],
                    ['name' => 'acknowledge_no_internal_appeal', 'type' => 'certification', 'required' => true,
                     'text' => 'I understand that GigResource has completed its review and will not review this decision again internally.'],
                ],
                'note' => 'Wording of anything binding is §12. Until counsel rules, this form takes the request and records it — it does not describe what the outside step is or what it can do.',
            ],

            /* ── Internal ─────────────────────────────────────────── */

            'staff_classification' => [
                'title'    => 'Classify Case',
                'audience' => self::STAFF,
                'purpose'  => '§3 — the three independent fields, set at intake.',
                'fields'   => [
                    ['name' => 'severity', 'label' => 'Severity', 'type' => 'select', 'required' => true,
                     'note' => 'Levels 4–5 move the case straight to Formal Investigation the moment they are set (§2).'],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'required' => true,
                     'note' => 'Set by hand, never defaulted from severity. §3 is explicit that a high-value Level 2 can outrank a Level 3.'],
                    ['name' => 'taxonomy', 'label' => 'Primary category', 'type' => 'select', 'required' => true],
                    ['name' => 'internal_tags', 'label' => 'Internal knowledge tags', 'type' => 'multiselect', 'required' => false,
                     'options' => 'Venue issue · Insurance · Repeat pattern · Training opportunity · Policy clarification',
                     'note' => '§6 — staff-only, never user-visible, internal reporting only.'],
                    ['name' => 'duplicate_of', 'label' => 'Duplicate of', 'type' => 'select', 'required' => false,
                     'note' => '§6 — same event alone is not a duplicate. Same service line + same claim + a prior closed case on that exact line.'],
                ],
            ],

            'staff_conflict_disclosure' => [
                'title'    => 'Conflict of Interest Disclosure',
                'audience' => self::STAFF,
                'purpose'  => '§7 — completed at assignment, by the person being assigned.',
                'fields'   => [
                    ['name' => 'has_connection', 'label' => 'Do you have any personal connection to either party?', 'type' => 'radio', 'required' => true],
                    ['name' => 'detail', 'label' => 'Describe it', 'type' => 'textarea', 'required' => false,
                     'note' => 'Required if the answer is yes.'],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I confirm this disclosure is complete and accurate.'],
                ],
                'note' => 'Answering yes reassigns the case. That is a handoff triggered by a disclosure (§6), not a separate mechanism.',
            ],

            'staff_evidence_request' => [
                'title'    => 'Request Evidence',
                'audience' => self::STAFF,
                'purpose'  => 'Asks one party for something specific.',
                'fields'   => [
                    ['name' => 'addressed_to', 'label' => 'Which party', 'type' => 'select', 'required' => true],
                    ['name' => 'request', 'label' => 'What you need', 'type' => 'textarea', 'required' => true],
                ],
            ],

            'staff_decision' => [
                'title'    => 'Record Decision',
                'audience' => self::STAFF,
                'purpose'  => '§2 Step 3 — becomes the Resolution/Outcome Notice sent to both parties.',
                'fields'   => [
                    ['name' => 'resolution_type', 'label' => 'Resolution type', 'type' => 'select', 'required' => true,
                     'options' => 'DisputeClassification::RESOLUTION_TYPES'],
                    ['name' => 'financial_outcome', 'label' => 'Financial outcome', 'type' => 'select', 'required' => false,
                     'options' => 'DisputeClassification::FINANCIAL_OUTCOMES',
                     'note' => 'Two separate axes (§5). Not required on an administrative closure, a duplicate, or a mutual settlement.'],
                    ['name' => 'amount_to_client', 'label' => 'Amount to the client', 'type' => 'money', 'required' => false],
                    ['name' => 'amount_to_professional', 'label' => 'Amount to the professional', 'type' => 'money', 'required' => false],
                    ['name' => 'reasoning', 'label' => 'Reasoning', 'type' => 'textarea', 'required' => true,
                     'note' => 'Required, and it goes to both parties. §10\'s audit trail is what answers an "unreasonable delay" complaint; a decision with no stated reasoning answers nothing.'],
                    ['name' => 'cure_deadline', 'label' => 'Cure deadline', 'type' => 'date', 'required' => false,
                     'note' => 'Only on a cure-redo, and agreed per case. This is the one date in the module a staff member sets, because §5 makes it a case-level agreement rather than a platform policy.'],
                ],
                'guide' => 'DecisionGuide — shown beside this form as suggestions with reasoning. It never fills the form in.',
            ],

            'staff_decision_revision' => [
                'title'    => 'Revise Decision',
                'audience' => self::STAFF,
                'purpose'  => '§5 — a revision keeps the original alongside it.',
                'fields'   => [
                    ['name' => 'revision_reason', 'label' => 'Why this is being revised', 'type' => 'textarea', 'required' => true],
                    ['name' => 'resolution_type', 'label' => 'Revised resolution type', 'type' => 'select', 'required' => true],
                    ['name' => 'financial_outcome', 'label' => 'Revised financial outcome', 'type' => 'select', 'required' => false],
                    ['name' => 'reasoning', 'label' => 'Revised reasoning', 'type' => 'textarea', 'required' => true],
                ],
                'note' => 'Writes a new decision row pointing at the old one. Both parties are told (NotificationMatrix: decision_revised).',
            ],

            'staff_closure' => [
                'title'    => 'Close Case',
                'audience' => self::STAFF,
                'purpose'  => 'Terminal. A closed case is never reopened — a new case is opened instead.',
                'fields'   => [
                    ['name' => 'closure_note', 'label' => 'Closing note', 'type' => 'textarea', 'required' => true],
                    ['name' => 'confirm_financial_executed', 'label' => 'Financial outcome has been executed', 'type' => 'checkbox', 'required' => false,
                     'note' => 'Confirmed by Finance, not by the person who decided the case — DisputePermissions::SEPARATION_OF_DUTIES.'],
                ],
            ],
        ];
    }

    /** Every certification field, i.e. every electronic signature in the module (§1). */
    public static function certifications(): array
    {
        $out = [];

        foreach (self::all() as $key => $form) {
            foreach ($form['fields'] as $field) {
                if (($field['type'] ?? null) === 'certification') {
                    $out[] = ['form' => $key, 'field' => $field['name'], 'text' => $field['text']];
                }
            }
        }

        return $out;
    }

    public static function forAudience(string $audience): array
    {
        return array_filter(
            self::all(),
            fn (array $form) => $form['audience'] === $audience || $form['audience'] === self::SHARED,
        );
    }
}
