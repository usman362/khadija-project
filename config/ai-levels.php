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
    // The three tool-package levels, shared by professionals and clients.
    // Internal keys stay manual/semi/maximum; these are what a user sees.
    //
    // Renamed twice by Peter: "Help Me Plan" and "Coordinate It For Me" became
    // Semi and Maximum, then on 2026-07-21 "Do It Myself" became Starter. The
    // old wording said a machine did the work for you, which R29 forbids —
    // every level is rules-based, and the depth is how much the templates and
    // calculators fill in, not how clever anything is.
    'labels' => [
        'none'    => 'Locked',
        'manual'  => 'Starter',
        'semi'    => 'Semi',
        'maximum' => 'Maximum',
    ],
    'descriptions' => [
        'manual'  => 'Templates and frameworks — you fill them in yourself.',
        'semi'    => 'The fields you have already answered are filled in for you; you edit the rest.',
        'maximum' => 'The full draft is prepared from your details, ready for you to review and change.',
    ],

    // ── Phase 4: free-beta usage cap ───────────────────────────────────────
    // Clients & influencers get every AI level free during the launch/beta
    // (see AiAccess::unlockedLevels). To keep beta usage — and cost — bounded,
    // each of them gets a soft monthly action cap across all AI tools. It is
    // enforced centrally in the EnsureAiLevel middleware; professionals are
    // unaffected (they have their own per-plan quotas), and admins are exempt.
    // Set the monthly limit to 0 to disable the cap entirely.
    'free_beta' => [
        'monthly_actions' => (int) env('AI_FREE_BETA_MONTHLY', 60),
        'roles'           => ['client', 'influencer'],
    ],

    // ── GigResource IQ™ credit economy (Peter's pilot metering) ────────────
    // Tools run on our own engines (no real AI cost), but each AI action spends
    // "AI Assist Credits" from the user's monthly allowance. This lets us charge
    // for AI tiers and capture real usage/demand data before switching on a real
    // model. Users only ever see credits — never tokens. Master switch is
    // AI_CREDITS_ENABLED; when off, the older per-action beta cap applies.
    'credits' => [
        'enabled' => filter_var(env('AI_CREDITS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        // Credit cost per weight class (Peter: Light 1 / Standard 2 / Advanced 4 / Visual 6).
        'weights' => ['light' => 1, 'standard' => 2, 'advanced' => 4, 'visual' => 6],

        // Per-tool weight class (anything unlisted = 'standard').
        'tool_weight' => [
            'message-assistant'      => 'light',
            'review-writer'          => 'light',
            'translator'             => 'light',
            'upsell-assistant'       => 'light',
            'proposal-writer'        => 'standard',
            'checklist-generator'    => 'standard',
            'package-builder'        => 'standard',
            'budget-allocator'       => 'standard',
            'vendor-matchmaking'     => 'standard',
            'guest-capacity'         => 'standard',
            'theme-advisor'          => 'standard',
            'pricing-assistant'      => 'standard',
            'staffing-planner'       => 'standard',
            'bid-optimizer'          => 'standard',
            'availability-optimizer' => 'standard',
            'timeline-builder'       => 'standard',
            'event-planner'          => 'advanced',
            'contract-assistant'     => 'advanced',
            'venue-analyzer'         => 'advanced',
            'portfolio-optimizer'    => 'visual',
        ],

        // Monthly credit grant. Professionals by plan slug; clients/influencers
        // get the free-role grant during beta. Manual/Core = 0 (templates only).
        'plan_grants' => [
            'starter'      => 0,    // Core
            'professional' => 100,  // Pro-Grow
            'enterprise'   => 400,  // Elite
        ],
        'professional_default_grant' => 0,
        'free_role_grant'            => (int) env('AI_FREE_ROLE_CREDITS', 100),
    ],
];
