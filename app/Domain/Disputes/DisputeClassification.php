<?php

namespace App\Domain\Disputes;

/**
 * Rule R34 §3 — the three classification fields, and §5's two outcome axes.
 *
 * The architecture is emphatic that severity, priority and taxonomy are
 * INDEPENDENT: "none substitutes for another, and a case record carries all
 * three simultaneously". A high-value quality dispute can outrank a payment
 * dispute in the queue, so priority cannot be derived from severity — and
 * anything that derived it would quietly re-couple them.
 *
 * Severity is the only one of the three that changes routing, and only at
 * levels 4 and 5 (§2).
 */
final class DisputeClassification
{
    /* ── §3 Severity — what kind of dispute, and does it change routing ── */

    public const SEVERITY_COMMUNICATION = 1;
    public const SEVERITY_QUALITY       = 2;
    public const SEVERITY_PAYMENT       = 3;
    public const SEVERITY_FRAUD         = 4;
    public const SEVERITY_SAFETY        = 5;

    public const SEVERITIES = [
        self::SEVERITY_COMMUNICATION => 'Minor communication issue',
        self::SEVERITY_QUALITY       => 'Quality dispute',
        self::SEVERITY_PAYMENT       => 'Payment dispute',
        self::SEVERITY_FRAUD         => 'Fraud',
        self::SEVERITY_SAFETY        => 'Safety or criminal concern',
    ];

    /* ── §3 Priority — how urgently staff work it, independent of severity ── */

    public const PRIORITIES = ['critical' => 'Critical', 'high' => 'High', 'normal' => 'Normal', 'low' => 'Low'];

    /* ── §3 Taxonomy — one required primary, optional secondaries ── */

    public const TAXONOMY = [
        'no_show'                 => 'No Show',
        'late_arrival'            => 'Late Arrival',
        'incomplete_service'      => 'Incomplete Service',
        'poor_workmanship'        => 'Poor Workmanship',
        'scope_disagreement'      => 'Scope Disagreement',
        'damage_claim'            => 'Damage Claim',
        'payment_dispute'         => 'Payment Dispute',
        'contract_interpretation' => 'Contract Interpretation',
        'fraud'                   => 'Fraud',
        'safety_concern'          => 'Safety Concern',
        'communication_issue'     => 'Communication Issue',
        'cancellation'            => 'Cancellation',
    ];

    /* ── §5 Financial outcome — what happens to the held balance ── */

    public const RELEASE_IN_FULL   = 'release_in_full';
    public const PARTIAL_PRORATED  = 'partial_prorated';
    public const CURE_REDO         = 'cure_redo';
    public const REFUND_NON_CONFORMING = 'refund_non_conforming';

    public const FINANCIAL_OUTCOMES = [
        self::RELEASE_IN_FULL   => 'Release in full',
        self::PARTIAL_PRORATED  => 'Partial (pro-rated)',
        self::CURE_REDO         => 'Cure-redo',
        self::REFUND_NON_CONFORMING => 'Refund non-conforming portion',
    ];

    /* ── §5 Resolution type — the reporting label, a separate axis ── */

    public const RESOLUTION_TYPES = [
        'no_action'             => 'No Action',
        'professional_prevails' => 'Professional Prevails',
        'client_prevails'       => 'Client Prevails',
        'partial_refund'        => 'Partial Refund',
        'partial_payment'       => 'Partial Payment',
        'service_redo'          => 'Service Redo',
        'mutual_settlement'     => 'Mutual Settlement',
        'administrative_closure' => 'Administrative Closure',
        'duplicate_case'        => 'Duplicate Case',
        'fraud_confirmed'       => 'Fraud Confirmed',
        'outside_provider_decision' => 'Outside Provider Decision',
        'court_order'           => 'Court Order',
    ];

    /**
     * §7's safeguard, and the reason the two axes are separate at all.
     *
     * Only a CONFIRMED outcome may reach internal trust or risk systems.
     * Filing a dispute never does — a public score that drops on an unproven
     * allegation punishes someone for being accused, and the architecture
     * names it as UDAP exposure in several of the seven states besides.
     */
    public const MAY_INFLUENCE_TRUST = [
        'professional_prevails',
        'client_prevails',
        'fraud_confirmed',
    ];

    /** Housekeeping closures carry no financial outcome (§5). */
    public const NO_FINANCIAL_OUTCOME = [
        'administrative_closure',
        'duplicate_case',
        'mutual_settlement',
    ];

    public static function bypassesDirectResolution(int $severity): bool
    {
        return $severity >= self::SEVERITY_FRAUD;
    }

    public static function mayInfluenceTrust(?string $resolutionType): bool
    {
        return $resolutionType !== null
            && in_array($resolutionType, self::MAY_INFLUENCE_TRUST, true);
    }

    public static function needsFinancialOutcome(string $resolutionType): bool
    {
        return ! in_array($resolutionType, self::NO_FINANCIAL_OUTCOME, true);
    }
}
