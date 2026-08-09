<?php

namespace App\Domain\Disputes;

/**
 * Rule R34 Phase 1 artifact 4 — the permissions matrix, including the seven
 * internal staff roles §11 names.
 *
 * These are dispute-module roles, not the four platform admin roles R50 locks
 * (Super-Admin, Trust & Safety, Support, Finance). A person holds a platform
 * role and may additionally hold a case role; conflating the two would either
 * give every Support agent the power to decide a case or require a fifth
 * platform role, and R50 explicitly deferred adding one.
 */
final class DisputePermissions
{
    /* ── The seven staff roles from §11, plus the two parties ── */

    public const INTAKE_SPECIALIST   = 'intake_specialist';
    public const INVESTIGATOR        = 'investigator';
    public const SENIOR_REVIEWER     = 'senior_reviewer';
    public const FRAUD_SPECIALIST    = 'fraud_specialist';
    public const LEGAL_ADMINISTRATOR = 'legal_administrator';
    public const FINANCE_REVIEWER    = 'finance_reviewer';
    public const SUPER_ADMIN         = 'super_admin';

    public const STAFF_ROLES = [
        self::INTAKE_SPECIALIST   => 'Intake Specialist',
        self::INVESTIGATOR        => 'Investigator',
        self::SENIOR_REVIEWER     => 'Senior Reviewer',
        self::FRAUD_SPECIALIST    => 'Fraud Specialist',
        self::LEGAL_ADMINISTRATOR => 'Legal Administrator',
        self::FINANCE_REVIEWER    => 'Finance Reviewer',
        self::SUPER_ADMIN         => 'Super Admin',
    ];

    /**
     * What each role may do.
     *
     * `view_evidence` and `download_evidence` are separate on purpose: §10
     * logs viewing and downloading as distinct events, and a role that can
     * read a case in the queue does not automatically need copies of a
     * client's venue photographs on its own machine.
     */
    public const ABILITIES = [
        self::INTAKE_SPECIALIST => [
            'view_case', 'view_evidence', 'classify_case', 'assign_case',
            'facilitate_direct_resolution', 'close_settled',
        ],
        self::INVESTIGATOR => [
            'view_case', 'view_evidence', 'download_evidence', 'request_evidence',
            'record_decision', 'set_cure_period', 'add_internal_note',
        ],
        self::SENIOR_REVIEWER => [
            'view_case', 'view_evidence', 'download_evidence', 'record_decision',
            'revise_decision', 'close_case', 'reassign_case', 'add_internal_note',
        ],
        self::FRAUD_SPECIALIST => [
            'view_case', 'view_evidence', 'download_evidence', 'add_internal_note',
            'confirm_fraud', 'escalate_account_action',
        ],
        self::LEGAL_ADMINISTRATOR => [
            'view_case', 'view_evidence', 'download_evidence',
            'manage_outside_escalation', 'close_case', 'apply_legal_hold',
        ],
        self::FINANCE_REVIEWER => [
            'view_case', 'view_financial_outcome', 'execute_financial_outcome',
        ],
        self::SUPER_ADMIN => ['*'],

        // The two parties. Each sees their own case and nothing internal.
        'client' => [
            'view_own_case', 'file_case', 'submit_evidence', 'respond',
            'propose_settlement', 'withdraw_case', 'request_outside_escalation',
        ],
        'professional' => [
            'view_own_case', 'file_case', 'submit_evidence', 'respond',
            'propose_settlement', 'withdraw_case', 'request_outside_escalation',
        ],
    ];

    /**
     * Deciding and paying are held apart.
     *
     * The role that records an outcome cannot be the role that moves the
     * money on it. Nothing in the architecture spells this out, but §10's
     * audit requirements and §8's separation of platform decisions from
     * payment processing only mean something if one person cannot do both
     * ends unobserved. Flagged for the PM rather than assumed silently.
     */
    public const SEPARATION_OF_DUTIES = [
        'record_decision'           => ['must_not_also' => 'execute_financial_outcome'],
        'execute_financial_outcome' => ['must_not_also' => 'record_decision'],
    ];

    public static function can(string $role, string $ability): bool
    {
        $abilities = self::ABILITIES[$role] ?? [];

        return $abilities === ['*'] || in_array($ability, $abilities, true);
    }

    public static function isStaff(string $role): bool
    {
        return array_key_exists($role, self::STAFF_ROLES);
    }

    /**
     * §7 — a staff member with a personal connection to either party must be
     * reassigned. Built as a disclosure step in assignment rather than a
     * separate mechanism: it is a handoff, triggered by a disclosure.
     */
    public static function requiresConflictDisclosureOnAssignment(): bool
    {
        return true;
    }
}
