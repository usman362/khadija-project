<?php

namespace App\Domain\Forms;

/**
 * The forms audit's missing field-level specs — checklist rows 183, 184, 185,
 * 205, 233, 237, 239, 243, 245, 246.
 *
 * Every one of those rows says the same thing: the workflow document names a
 * form, the rules exist, and nobody ever wrote down the fields. So this file
 * IS the deliverable as much as the screens are — one definition per form,
 * field by field, and the renderer builds the page from it.
 *
 * One registry rather than ten hand-built forms because the rows are ten
 * instances of one problem. Ten separate builds would be ten places to forget
 * that a certification is never pre-ticked, and ten more the next time the
 * audit finds another gap.
 *
 * Three forms the audit lists are deliberately NOT here — see BLOCKED below.
 */
final class FormRegistry
{
    /* Audiences. A form is offered to the people who can actually file it. */
    public const CLIENT       = 'client';
    public const PROFESSIONAL = 'professional';
    public const INFLUENCER   = 'influencer';
    public const ANYONE       = 'anyone';

    /**
     * Forms named by the audit that must NOT be built yet, and why.
     *
     * Each is blocked on a decision nobody has made. Building them would mean
     * inventing the answer — which consent wording is lawful, which trades
     * need insurance, whether tax details even live on this platform. R1–R3
     * say flag it rather than guess, so they are flagged here, in code, where
     * the next person to open this file will see them.
     */
    public const BLOCKED = [
        'data_privacy_consent' => [
            'title'  => 'Data Privacy / Consent',
            'row'    => 217,
            'reason' => 'Consent wording for ID-document and PII collection is a legal question. Held for the same attorney pass as the Dispute Resolution items.',
        ],
        'insurance_coi' => [
            'title'  => 'Insurance / COI Verification',
            'row'    => 218,
            'reason' => 'The list of regulated categories that would need it — alcohol, catering, security, pyrotechnics — has not been confirmed by the Owner. Gating the wrong trades stops people working.',
        ],
        'w9_tax' => [
            'title'  => 'W-9 / Tax Compliance',
            'row'    => 219,
            'reason' => 'Undecided whether tax details live on GigResource at all or entirely inside Stripe Connect. Collecting a taxpayer ID we did not need would be the expensive way to find out.',
        ],
    ];

    /**
     * @return array<string, array{
     *     title:string, row:int, audience:string, purpose:string,
     *     subject?:string, dual_approval?:bool,
     *     fields:array<int, array<string, mixed>>
     * }>
     */
    public static function all(): array
    {
        return [
            /* ── Row 183 — Change Order ───────────────────────── */
            'change_order' => [
                'title'    => 'Change Order',
                'row'      => 183,
                'audience' => self::ANYONE,
                'subject'  => 'booking',
                'purpose'  => 'A change to an agreed service, proposed to the other party.',

                // The Change-Order Policy's dual approval. A change to a
                // signed agreement is not a change until both sides say so —
                // that is what makes it an order rather than an announcement.
                'dual_approval' => true,

                'fields' => [
                    ['name' => 'booking_id', 'label' => 'Which booking', 'type' => 'booking', 'required' => true,
                     'note' => 'One service line (R12). Changing the caterer does not touch the photographer.'],
                    ['name' => 'change_type', 'label' => 'What is changing', 'type' => 'select', 'required' => true,
                     'options' => ['scope' => 'The scope of work', 'date' => 'The date or time', 'price' => 'The price', 'other' => 'Something else']],
                    ['name' => 'current', 'label' => 'What was agreed', 'type' => 'textarea', 'required' => true],
                    ['name' => 'proposed', 'label' => 'What you are proposing instead', 'type' => 'textarea', 'required' => true],
                    ['name' => 'price_change', 'label' => 'Change to the price', 'type' => 'money', 'required' => false,
                     'note' => 'Leave blank if the price is not changing. A change order never moves money on its own.'],
                    ['name' => 'reason', 'label' => 'Why', 'type' => 'textarea', 'required' => true],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I understand this is a proposal, and that nothing changes until the other party accepts it.'],
                ],
            ],

            /* ── Row 184 — Content / User Report ──────────────── */
            'content_report' => [
                'title'    => 'Report Content',
                'row'      => 184,
                'audience' => self::ANYONE,
                'purpose'  => 'Flag a listing, profile, review or message as breaking the rules.',
                'fields'   => [
                    ['name' => 'target', 'label' => 'What are you reporting', 'type' => 'text', 'required' => true,
                     'note' => 'Pre-filled when you arrive from the page you are reporting.'],
                    ['name' => 'category', 'label' => 'What is wrong with it', 'type' => 'select', 'required' => true,
                     'options' => [
                         'harassment'   => 'Harassment or abuse',
                         'off_platform' => 'Trying to move the job off GigResource',
                         'misleading'   => 'Misleading or false information',
                         'adult'        => 'Adult or graphic content',
                         'impersonation'=> 'Pretending to be someone else',
                         'spam'         => 'Spam or advertising',
                         'copyright'    => 'Uses my work without permission',
                         'safety'       => 'A safety concern',
                         'other'        => 'Something else',
                     ]],
                    ['name' => 'detail', 'label' => 'Tell us more', 'type' => 'textarea', 'required' => true],
                    // No certification. A report is an allegation, not a
                    // statement of fact under signature — asking someone to
                    // certify before reporting harassment is a reason not to.
                ],
            ],

            /* ── Row 185 — Correction Request ─────────────────── */
            'correction_request' => [
                'title'    => 'Request a Correction',
                'row'      => 185,
                'audience' => self::CLIENT,
                'subject'  => 'booking',
                'purpose'  => 'Ask for something to be put right before the final payment is released.',
                'fields'   => [
                    ['name' => 'booking_id', 'label' => 'Which booking', 'type' => 'booking', 'required' => true],
                    ['name' => 'deliverable', 'label' => 'What is not right', 'type' => 'text', 'required' => true],
                    ['name' => 'expected', 'label' => 'What was agreed', 'type' => 'textarea', 'required' => true,
                     'note' => 'The comparison is against the contract, not against preference — quoting the agreed wording helps most.'],
                    ['name' => 'requested', 'label' => 'What you would like done', 'type' => 'textarea', 'required' => true],
                    ['name' => 'needed_by', 'label' => 'When you need it by', 'type' => 'date', 'required' => false],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I understand this holds the final payment on this service until it is resolved, and that the deposit is not affected.'],
                ],
            ],

            /* ── Row 205 — Payout Details Confirmation ────────── */
            'payout_details' => [
                'title'    => 'Confirm Your Payout Details',
                'row'      => 205,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Confirm where your money should go before any payout is released.',
                'fields'   => [
                    ['name' => 'legal_name', 'label' => 'Name on the account', 'type' => 'text', 'required' => true,
                     'note' => 'Must match the account exactly, or the bank returns the payment.'],
                    ['name' => 'business_name', 'label' => 'Business name, if different', 'type' => 'text', 'required' => false],
                    ['name' => 'payout_state', 'label' => 'State', 'type' => 'text', 'required' => true],
                    ['name' => 'confirmed_with_processor', 'label' => 'I have completed the payment provider’s own setup', 'type' => 'checkbox', 'required' => true,
                     'note' => 'GigResource never asks for your bank or card numbers. Those go to the licensed payment provider directly, on their own screens.'],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I confirm these details are mine and are correct, and I understand payouts cannot be released until the payment provider has verified them.'],
                ],
                // The point of this form, and why it holds no numbers: bank
                // details belong to the processor. A marketplace that collects
                // account numbers into its own database has taken on a
                // liability it gains nothing from.
                'collects_no_bank_details' => true,
            ],

            /* ── Row 233 — Elite Professional Verification ────── */
            'elite_verification' => [
                'title'    => 'Elite Professional Verification',
                'row'      => 233,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Confirm your business details so a referral to you can qualify (R22).',
                'fields'   => [
                    ['name' => 'business_name', 'label' => 'Registered business name', 'type' => 'text', 'required' => true],
                    ['name' => 'business_state', 'label' => 'State it is registered in', 'type' => 'text', 'required' => true,
                     'note' => 'Must be the state this account works in (R47).'],
                    ['name' => 'trade_licence', 'label' => 'Trade licence number', 'type' => 'text', 'required' => true],
                    ['name' => 'years_trading', 'label' => 'Years trading', 'type' => 'number', 'required' => false],
                    ['name' => 'services', 'label' => 'What you do', 'type' => 'textarea', 'required' => true],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I confirm these business details are accurate and that I am authorised to give them.'],
                ],
            ],

            /* ── Row 237 — Influencer Program Application ─────── */
            'influencer_application' => [
                'title'    => 'Apply to the Influencer Program',
                'row'      => 237,
                'audience' => self::ANYONE,
                'purpose'  => 'Ask to join the program.',
                'fields'   => [
                    ['name' => 'audience_where', 'label' => 'Where your audience is', 'type' => 'textarea', 'required' => true,
                     'note' => 'The platforms you post on, and roughly how many people follow you there.'],
                    ['name' => 'audience_who', 'label' => 'Who they are', 'type' => 'textarea', 'required' => true],
                    ['name' => 'why', 'label' => 'Why GigResource fits them', 'type' => 'textarea', 'required' => true],
                    ['name' => 'links', 'label' => 'Links to your work', 'type' => 'textarea', 'required' => false],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I confirm this is my own audience and that I have not bought followers or engagement.'],
                ],
            ],

            /* ── Row 239 — Package Purchase ───────────────────── */
            'package_purchase' => [
                'title'    => 'Book a Package',
                'row'      => 239,
                'audience' => self::CLIENT,
                'purpose'  => 'Book a professional’s ready-made package for your event.',
                'fields'   => [
                    ['name' => 'package_id', 'label' => 'Which package', 'type' => 'text', 'required' => true],
                    ['name' => 'event_date', 'label' => 'Your event date', 'type' => 'date', 'required' => true],
                    ['name' => 'location', 'label' => 'Where', 'type' => 'text', 'required' => true],
                    ['name' => 'guests', 'label' => 'How many guests', 'type' => 'number', 'required' => false],
                    ['name' => 'notes', 'label' => 'Anything the professional should know', 'type' => 'textarea', 'required' => false],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I understand a deposit is taken on booking and is not refundable.'],
                ],
            ],

            /* ── Row 243 — Crew & Staffing, the five forms ────── */
            'crew_record' => [
                'title'    => 'Add or Edit a Crew Member',
                'row'      => 243,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Someone who works on your jobs.',
                'fields'   => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'role', 'label' => 'Their role', 'type' => 'text', 'required' => true],
                    ['name' => 'contact', 'label' => 'How to reach them', 'type' => 'text', 'required' => false],
                    ['name' => 'rate', 'label' => 'Their rate', 'type' => 'money', 'required' => false],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'required' => false],
                ],
            ],
            'shift_request' => [
                'title'    => 'Request a Shift',
                'row'      => 243,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Ask crew to cover a job.',
                'fields'   => [
                    ['name' => 'event', 'label' => 'Which job', 'type' => 'text', 'required' => true],
                    ['name' => 'role', 'label' => 'Role needed', 'type' => 'text', 'required' => true],
                    ['name' => 'starts_at', 'label' => 'Starts', 'type' => 'datetime', 'required' => true],
                    ['name' => 'ends_at', 'label' => 'Ends', 'type' => 'datetime', 'required' => true],
                    ['name' => 'slots', 'label' => 'How many people', 'type' => 'number', 'required' => true],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'required' => false],
                ],
            ],
            'shift_confirmation' => [
                'title'    => 'Confirm You Are On Shift',
                'row'      => 243,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Check in when you arrive.',
                'fields'   => [
                    ['name' => 'shift', 'label' => 'Which shift', 'type' => 'text', 'required' => true],
                    ['name' => 'arrived_at', 'label' => 'When you arrived', 'type' => 'datetime', 'required' => true],
                    ['name' => 'condition', 'label' => 'Anything wrong on arrival', 'type' => 'textarea', 'required' => false,
                     'note' => 'Access, parking, equipment, the venue not being ready — worth recording while you are standing there.'],
                ],
            ],
            'crew_assignment' => [
                'title'    => 'Assign Crew to a Job',
                'row'      => 243,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'Put named people on a job.',
                'fields'   => [
                    ['name' => 'event', 'label' => 'Which job', 'type' => 'text', 'required' => true],
                    ['name' => 'crew', 'label' => 'Who is on it', 'type' => 'textarea', 'required' => true],
                    ['name' => 'lead', 'label' => 'Who is leading', 'type' => 'text', 'required' => false],
                    ['name' => 'briefing', 'label' => 'Briefing', 'type' => 'textarea', 'required' => false],
                ],
            ],
            'menu_inventory' => [
                'title'    => 'Menu & Inventory List',
                'row'      => 243,
                'audience' => self::PROFESSIONAL,
                'purpose'  => 'What is going to the job.',
                'fields'   => [
                    ['name' => 'event', 'label' => 'Which job', 'type' => 'text', 'required' => true],
                    ['name' => 'items', 'label' => 'Items', 'type' => 'textarea', 'required' => true,
                     'note' => 'One per line.'],
                    ['name' => 'allergens', 'label' => 'Allergens or hazards', 'type' => 'textarea', 'required' => false,
                     'note' => 'Food, pyrotechnics, heavy rigging — anything the venue or the client needs to know about.'],
                    ['name' => 'loaded', 'label' => 'Everything is loaded and checked', 'type' => 'checkbox', 'required' => false],
                ],
            ],

            /* ── Row 245 — Campaign Plan ──────────────────────── */
            'campaign_plan' => [
                'title'    => 'Plan a Campaign',
                'row'      => 245,
                'audience' => self::INFLUENCER,
                'purpose'  => 'What you are going to promote, and where.',
                'fields'   => [
                    ['name' => 'name', 'label' => 'Campaign name', 'type' => 'text', 'required' => true],
                    ['name' => 'promoting', 'label' => 'What you are promoting', 'type' => 'textarea', 'required' => true],
                    ['name' => 'channels', 'label' => 'Where you will post', 'type' => 'text', 'required' => true],
                    ['name' => 'starts_on', 'label' => 'Starts', 'type' => 'date', 'required' => true],
                    ['name' => 'ends_on', 'label' => 'Ends', 'type' => 'date', 'required' => false],
                    ['name' => 'certify', 'type' => 'certification', 'required' => true,
                     'text' => 'I will disclose that my posts are paid promotion, as the law requires.'],
                ],
            ],

            /* ── Row 246 — Testimonial ────────────────────────── */
            'testimonial' => [
                'title'    => 'Share Your Story',
                'row'      => 246,
                'audience' => self::ANYONE,
                'purpose'  => 'Tell us how GigResource worked for you.',
                'fields'   => [
                    ['name' => 'headline', 'label' => 'In one line', 'type' => 'text', 'required' => true],
                    ['name' => 'story', 'label' => 'Your story', 'type' => 'textarea', 'required' => true],
                    ['name' => 'may_publish', 'label' => 'You may publish this with my name', 'type' => 'checkbox', 'required' => false,
                     'note' => 'Leave it unticked and we keep it internal. Nothing goes public without this.'],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Forms this person may file. */
    public static function forAudience(string $audience): array
    {
        return array_filter(
            self::all(),
            fn ($form) => $form['audience'] === $audience || $form['audience'] === self::ANYONE,
        );
    }

    /** Every certification across every form — the signatures this app takes. */
    public static function certifications(): array
    {
        $out = [];

        foreach (self::all() as $key => $form) {
            foreach ($form['fields'] as $field) {
                if (($field['type'] ?? null) === 'certification') {
                    $out[] = ['form' => $key, 'text' => $field['text']];
                }
            }
        }

        return $out;
    }
}
