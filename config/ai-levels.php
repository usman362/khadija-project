<?php

/**
 * GigResource IQ™ — AI capability levels (Manual / Semi / Maximum).
 *
 * This is the single source of truth for Layer 2 (membership → which AI levels
 * a user unlocks) and Layer 3 (which levels each tool can offer). It sits on top
 * of Layer 1 (admin master switch, see App\Models\AiToolSetting / AiToolCatalog).
 *
 * Level order:  manual (1)  <  semi (2)  <  maximum (3).  'none' = locked.
 *
 * Business rules (Peter's zero-users launch strategy):
 *  - PROFESSIONALS are tier-gated by their membership plan (below).
 *  - CLIENTS & INFLUENCERS get every level FREE at launch (usage-capped by the
 *    existing AiFeatureGate quotas, not by tier).
 */

return [

    // Levels each PROFESSIONAL membership plan unlocks (cumulative), by plan slug.
    //   starter      = Tier 1 "Core"        → Manual only
    //   professional = Tier 2 "Pro-Grow"    → Manual + Semi
    //   enterprise   = Tier 3 "Elite"       → Manual + Semi + Maximum
    'plan_levels' => [
        'starter'      => ['manual'],
        'professional' => ['manual', 'semi'],
        'enterprise'   => ['manual', 'semi', 'maximum'],
    ],

    // A professional with no active plan falls back to the free Core base (Manual).
    'professional_default' => ['manual'],

    // Roles that get every AI level free during the launch phase.
    'free_roles' => ['client', 'influencer'],

    // Highest level each tool can offer, keyed by AiToolCatalog key.
    // Tools that can genuinely auto-generate get 'maximum'; lighter advisory
    // tools cap at 'semi'. Anything not listed defaults to all three.
    'tool_modes' => [
        // ── Client tools ──
        'budget-allocator'       => ['manual', 'semi', 'maximum'],
        'vendor-matchmaking'     => ['manual', 'semi', 'maximum'],
        'event-planner'          => ['manual', 'semi', 'maximum'],
        'timeline-builder'       => ['manual', 'semi', 'maximum'],
        'venue-analyzer'         => ['manual', 'semi'],
        'checklist-generator'    => ['manual', 'semi', 'maximum'],
        'guest-capacity'         => ['manual', 'semi'],
        'theme-advisor'          => ['manual', 'semi'],
        // ── Professional tools ──
        'pricing-assistant'      => ['manual', 'semi', 'maximum'],
        'proposal-writer'        => ['manual', 'semi', 'maximum'],
        'staffing-planner'       => ['manual', 'semi', 'maximum'],
        'bid-optimizer'          => ['manual', 'semi', 'maximum'],
        'package-builder'        => ['manual', 'semi', 'maximum'],
        'portfolio-optimizer'    => ['manual', 'semi'],
        'availability-optimizer' => ['manual', 'semi'],
        'upsell-assistant'       => ['manual', 'semi'],
        // ── Shared (both) tools ──
        'review-writer'          => ['manual', 'semi', 'maximum'],
        'contract-assistant'     => ['manual', 'semi'],
        'message-assistant'      => ['manual', 'semi', 'maximum'],
        'translator'             => ['manual', 'semi', 'maximum'],
    ],

    // Human labels + short descriptions per level (for badges / tooltips / pricing UI).
    // Client-facing level names (Peter's "DIY" wording). Internal keys stay
    // manual/semi/maximum; only the display labels change.
    'labels' => [
        'none'    => 'Locked',
        'manual'  => 'Do It Myself',
        'semi'    => 'Help Me Plan',
        'maximum' => 'Coordinate It For Me',
    ],
    'descriptions' => [
        'manual'  => 'Do it yourself — templates & frameworks, no AI.',
        'semi'    => 'AI helps you plan — suggestions & rewrites you approve.',
        'maximum' => 'AI coordinates it for you — auto-generates the whole thing.',
    ],
];
