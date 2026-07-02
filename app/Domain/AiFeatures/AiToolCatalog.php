<?php

namespace App\Domain\AiFeatures;

/**
 * Canonical catalogue of every AI tool and WHO it is for — the single source of
 * truth for Peter's "AI tools per user (client / professional / both)" matrix.
 *
 * Each entry:
 *   key       slug
 *   name      display name
 *   audience  'client' | 'professional' | 'both'
 *   purpose   one-line description
 *   status    'live'    — built and routable (route set)
 *             'planned' — on the roadmap, not built yet
 *   route     named route when live, else null
 *
 * Sidebars, the tools index and gating all read from here so the lineup can
 * never drift between client and professional again.
 */
final class AiToolCatalog
{
    public static function all(): array
    {
        return [
            // ── CLIENT ────────────────────────────────────────────────
            ['key' => 'budget-allocator',   'name' => 'AI Budget Allocator',     'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.budget-allocator',   'purpose' => 'Allocates the event budget across services.'],
            ['key' => 'vendor-matchmaking', 'name' => 'AI Vendor Matchmaking',   'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.vendor-matchmaking', 'purpose' => 'Finds the best professionals by budget, location, rating & availability.'],
            ['key' => 'event-planner',      'name' => 'AI Event Planner',        'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.event-planner', 'purpose' => 'Organises the event from planning through completion.'],
            ['key' => 'timeline-builder',   'name' => 'AI Timeline Builder',     'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.timeline-builder', 'purpose' => 'Creates an event timeline incl. setup, schedule and teardown.'],
            ['key' => 'venue-analyzer',     'name' => 'AI Venue Analyzer',       'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.venue-analyzer', 'purpose' => 'Reviews venue details and recommends vendors, equipment & logistics.'],
            ['key' => 'checklist-generator','name' => 'AI Checklist Generator',  'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.checklist-generator', 'purpose' => 'Builds a personalised event-planning checklist with budget & vendor status.'],
            ['key' => 'guest-capacity',     'name' => 'AI Guest Capacity Planner','audience' => 'client',      'status' => 'live',    'route' => 'ai-tools.guest-capacity', 'purpose' => 'Estimates staffing, seating, food, beverage and venue capacity.'],
            ['key' => 'theme-advisor',      'name' => 'AI Theme & Style Advisor','audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.theme-advisor', 'purpose' => 'Recommends colours, décor, themes and styling.'],

            // ── PROFESSIONAL ──────────────────────────────────────────
            ['key' => 'pricing-assistant',  'name' => 'AI Pricing Assistant',    'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.pricing-assistant',  'purpose' => 'Calculates competitive pricing from labour, equipment, demand & market rates.'],
            ['key' => 'proposal-writer',    'name' => 'AI Proposal Builder',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.proposal-writer',    'purpose' => 'Generates professional proposals, cover letters and bid descriptions.'],
            ['key' => 'staffing-planner',   'name' => 'AI Staffing Planner',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.staffing-planner',   'purpose' => 'Determines how many people are needed and the staffing strategy.'],
            ['key' => 'bid-optimizer',      'name' => 'AI Bid Optimizer',        'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.bid-optimizer', 'purpose' => 'Recommends the best bid amount balanced against your margin.'],
            ['key' => 'package-builder',    'name' => 'AI Package Builder',      'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.package-builder', 'purpose' => 'Builds, prices and compares tiered service packages.'],
            ['key' => 'portfolio-optimizer','name' => 'AI Portfolio Optimizer',  'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.portfolio-optimizer', 'purpose' => 'Audits your profile & portfolio and recommends high-impact fixes.'],
            ['key' => 'availability-optimizer','name' => 'AI Availability Optimizer','audience' => 'professional','status' => 'live','route' => 'ai-tools.availability-optimizer', 'purpose' => 'Suggests scheduling improvements via calendar optimisation.'],
            ['key' => 'upsell-assistant',   'name' => 'AI Upsell Assistant',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.upsell-assistant', 'purpose' => 'Suggests add-on services and package upgrades to grow revenue.'],

            // ── BOTH ──────────────────────────────────────────────────
            ['key' => 'review-writer',      'name' => 'AI Review Writer',        'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.review-writer',      'purpose' => 'Helps clients and pros write fair, detailed reviews.'],
            ['key' => 'contract-assistant', 'name' => 'AI Contract Assistant',   'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.contract-assistant', 'purpose' => 'Summarises contract clauses and highlights important terms.'],
            ['key' => 'message-assistant',  'name' => 'AI Message Assistant',    'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.message-assistant', 'purpose' => 'Writes professional messages, replies and follow-ups.'],
            ['key' => 'translator',         'name' => 'AI Translator',           'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.translator', 'purpose' => 'Translates conversations, proposals and documents between languages.'],
        ];
    }

    /**
     * Keys of tools an admin has disabled (ai_tool_settings row with enabled=false).
     * Memoized so this runs a single query per request; guarded so nothing breaks
     * if the table does not exist yet (pre-migration).
     */
    private static ?array $disabledKeys = null;

    public static function disabledKeys(): array
    {
        if (self::$disabledKeys !== null) {
            return self::$disabledKeys;
        }

        try {
            self::$disabledKeys = \App\Models\AiToolSetting::query()
                ->where('enabled', false)
                ->pluck('tool_key')
                ->all();
        } catch (\Throwable $e) {
            self::$disabledKeys = [];
        }

        return self::$disabledKeys;
    }

    /** Tools a given audience should see: their own + the shared ('both') ones, minus admin-disabled. */
    public static function forAudience(string $audience): array
    {
        $disabled = self::disabledKeys();

        return array_values(array_filter(
            self::all(),
            fn ($t) => ($t['audience'] === $audience || $t['audience'] === 'both')
                && ! in_array($t['key'], $disabled, true)
        ));
    }

    public static function client(): array       { return self::forAudience('client'); }
    public static function professional(): array { return self::forAudience('professional'); }

    public static function live(array $tools): array
    {
        return array_values(array_filter($tools, fn ($t) => $t['status'] === 'live'));
    }

    public static function counts(): array
    {
        $all = self::all();
        return [
            'total'   => count($all),
            'live'    => count(array_filter($all, fn ($t) => $t['status'] === 'live')),
            'planned' => count(array_filter($all, fn ($t) => $t['status'] === 'planned')),
            'client'  => count(self::client()),
            'professional' => count(self::professional()),
        ];
    }

    /* ──────────────────────────────────────────────────────────────────
     * GigResource IQ™ Suites — the 5 branded "Centers" the 20 tools roll up
     * into (Peter / ChatGPT). Grouping is by goal, so a suite can mix
     * audiences; the hub still only shows a user the tools for their role.
     * ────────────────────────────────────────────────────────────────── */
    private const SUITE_OF = [
        'budget-allocator' => 'planning', 'event-planner' => 'planning', 'timeline-builder' => 'planning',
        'venue-analyzer' => 'planning', 'checklist-generator' => 'planning', 'guest-capacity' => 'planning', 'theme-advisor' => 'planning',
        'vendor-matchmaking' => 'marketplace', 'staffing-planner' => 'marketplace', 'review-writer' => 'marketplace',
        'pricing-assistant' => 'business', 'package-builder' => 'business', 'portfolio-optimizer' => 'business',
        'availability-optimizer' => 'business', 'upsell-assistant' => 'business', 'bid-optimizer' => 'business',
        'proposal-writer' => 'operations', 'contract-assistant' => 'operations', 'message-assistant' => 'operations', 'translator' => 'operations',
    ];

    public static function suites(): array
    {
        return [
            'planning'    => ['name' => 'Planning Suite',    'emoji' => '🎯', 'tagline' => 'Plan your entire event with AI before spending a dollar.'],
            'marketplace' => ['name' => 'Marketplace Suite', 'emoji' => '🤝', 'tagline' => 'Connect buyers and professionals, intelligently.'],
            'business'    => ['name' => 'Business Suite',     'emoji' => '💼', 'tagline' => 'Grow your event business with AI.'],
            'operations'  => ['name' => 'Operations Suite',  'emoji' => '📄', 'tagline' => 'Communicate faster. Close more business.'],
            'automation'  => ['name' => 'Automation Suite',  'emoji' => '🚀', 'tagline' => 'Automation, analytics & insights — coming soon.'],
        ];
    }

    public static function suiteOf(string $key): string
    {
        return self::SUITE_OF[$key] ?? 'automation';
    }

    /**
     * The user's tools grouped under their GigResource IQ™ suites, in suite
     * order: [suiteKey => name, emoji, tagline, tools[]]. Empty suites dropped.
     */
    public static function groupedForAudience(string $audience): array
    {
        $tools = self::forAudience($audience);
        $out = [];
        foreach (self::suites() as $sk => $meta) {
            $inSuite = array_values(array_filter($tools, fn ($t) => self::suiteOf($t['key']) === $sk));
            if ($inSuite) {
                $out[$sk] = $meta + ['tools' => $inSuite];
            }
        }
        return $out;
    }
}
