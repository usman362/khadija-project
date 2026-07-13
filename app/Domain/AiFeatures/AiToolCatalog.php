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
            ['key' => 'budget-allocator',   'name' => 'Budget Planner',     'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.budget-allocator',   'purpose' => 'Allocates the event budget across services.'],
            ['key' => 'vendor-matchmaking', 'name' => 'Smart Match',   'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.vendor-matchmaking', 'purpose' => 'Finds the best professionals by budget, location, rating & availability.'],
            ['key' => 'event-planner',      'name' => 'Guided Event Planner',        'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.event-planner', 'purpose' => 'Organises the event from planning through completion.'],
            ['key' => 'timeline-builder',   'name' => 'Timeline Builder',     'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.timeline-builder', 'purpose' => 'Creates an event timeline incl. setup, schedule and teardown.'],
            ['key' => 'venue-analyzer',     'name' => 'Venue Compatibility Check',       'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.venue-analyzer', 'purpose' => 'Reviews venue details and recommends vendors, equipment & logistics.'],
            ['key' => 'checklist-generator','name' => 'Smart Checklist',  'audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.checklist-generator', 'purpose' => 'Builds a personalised event-planning checklist with budget & vendor status.'],
            ['key' => 'guest-capacity',     'name' => 'Guest Capacity Calculator','audience' => 'client',      'status' => 'live',    'route' => 'ai-tools.guest-capacity', 'purpose' => 'Estimates staffing, seating, food, beverage and venue capacity.'],
            ['key' => 'theme-advisor',      'name' => 'Theme & Style Advisor','audience' => 'client',       'status' => 'live',    'route' => 'ai-tools.theme-advisor', 'purpose' => 'Recommends colours, décor, themes and styling.'],

            // ── PROFESSIONAL ──────────────────────────────────────────
            ['key' => 'pricing-assistant',  'name' => 'Pricing Calculator',    'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.pricing-assistant',  'purpose' => 'Calculates competitive pricing from labour, equipment, demand & market rates.'],
            ['key' => 'proposal-writer',    'name' => 'Proposal Builder',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.proposal-writer',    'purpose' => 'Generates professional proposals, cover letters and bid descriptions.'],
            ['key' => 'staffing-planner',   'name' => 'Staffing Planner',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.staffing-planner',   'purpose' => 'Determines how many people are needed and the staffing strategy.'],
            ['key' => 'bid-optimizer',      'name' => 'Bid Calculator',        'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.bid-optimizer', 'purpose' => 'Recommends the best bid amount balanced against your margin.'],
            ['key' => 'package-builder',    'name' => 'Package Builder',      'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.package-builder', 'purpose' => 'Builds, prices and compares tiered service packages.'],
            ['key' => 'portfolio-optimizer','name' => 'Portfolio Optimizer',  'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.portfolio-optimizer', 'purpose' => 'Audits your profile & portfolio and recommends high-impact fixes.'],
            ['key' => 'availability-optimizer','name' => 'Availability Scheduler','audience' => 'professional','status' => 'live','route' => 'ai-tools.availability-optimizer', 'purpose' => 'Suggests scheduling improvements via calendar optimisation.'],
            ['key' => 'upsell-assistant',   'name' => 'Upsell Assistant',     'audience' => 'professional', 'status' => 'live',    'route' => 'ai-tools.upsell-assistant', 'purpose' => 'Suggests add-on services and package upgrades to grow revenue.'],

            // ── BOTH ──────────────────────────────────────────────────
            ['key' => 'review-writer',      'name' => 'Review Builder',        'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.review-writer',      'purpose' => 'Helps clients and pros write fair, detailed reviews.'],
            ['key' => 'contract-assistant', 'name' => 'Contract Assistant',   'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.contract-assistant', 'purpose' => 'Summarises contract clauses and highlights important terms.'],
            ['key' => 'message-assistant',  'name' => 'Message Builder',    'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.message-assistant', 'purpose' => 'Writes professional messages, replies and follow-ups.'],
            ['key' => 'translator',         'name' => 'Translator',           'audience' => 'both',         'status' => 'live',    'route' => 'ai-tools.translator', 'purpose' => 'Translates conversations, proposals and documents between languages.'],
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
            'planning'    => ['name' => 'Planning Suite',    'emoji' => '🎯', 'tagline' => 'Plan your entire event before spending a dollar.'],
            'marketplace' => ['name' => 'Marketplace Suite', 'emoji' => '🤝', 'tagline' => 'Connect buyers and professionals, intelligently.'],
            'business'    => ['name' => 'Business Suite',     'emoji' => '💼', 'tagline' => 'Grow your event business.'],
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

    /* ──────────────────────────────────────────────────────────────────
     * Presentation data for the redesigned hub — per-tool feather-style icon
     * (24×24 path) + three short feature bullets. Representative copy.
     * ────────────────────────────────────────────────────────────────── */
    private const ICONS = [
        'budget-allocator'      => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'vendor-matchmaking'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'event-planner'         => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/>',
        'timeline-builder'      => '<path d="M3 12h4l3-9 4 18 3-9h4"/>',
        'venue-analyzer'        => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'checklist-generator'   => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="m9 14 2 2 4-4"/>',
        'guest-capacity'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>',
        'theme-advisor'         => '<path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/><path d="M2 22l4-1"/>',
        'pricing-assistant'     => '<path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'proposal-writer'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'staffing-planner'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
        'bid-optimizer'         => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'package-builder'       => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'portfolio-optimizer'   => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'availability-optimizer'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M12 14v3l2 1"/>',
        'upsell-assistant'      => '<circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/>',
        'review-writer'         => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'contract-assistant'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/>',
        'message-assistant'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'translator'            => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
    ];

    private const FEATURES = [
        'budget-allocator'      => ['Smart budget distribution', 'Optimize spending', 'Stay on budget'],
        'vendor-matchmaking'    => ['Smart vendor matching', 'Budget & location filters', 'Ratings & availability insights'],
        'event-planner'         => ['Event planning assistant', 'Task management', 'Real-time updates'],
        'timeline-builder'      => ['Detailed timelines', 'Milestone tracking', 'Time conflict alerts'],
        'venue-analyzer'        => ['Venue insights', 'Vendor recommendations', 'Logistics planning'],
        'checklist-generator'   => ['Custom checklists', 'Budget tracking', 'Vendor status updates'],
        'guest-capacity'        => ['Capacity calculations', 'Staffing estimates', 'Resource planning'],
        'theme-advisor'         => ['Theme suggestions', 'Color palettes', 'Style inspiration'],
        'pricing-assistant'     => ['Market-based pricing', 'Cost & margin breakdown', 'Competitive rates'],
        'proposal-writer'       => ['Professional proposals', 'Cover letters & bids', 'Ready-to-send drafts'],
        'staffing-planner'      => ['Headcount estimates', 'Role breakdown', 'Staffing strategy'],
        'bid-optimizer'         => ['Best-bid recommendation', 'Margin-aware pricing', 'Win-rate insights'],
        'package-builder'       => ['Tiered packages', 'Smart pricing', 'Add-on suggestions'],
        'portfolio-optimizer'   => ['Profile audit', 'High-impact fixes', 'Portfolio tips'],
        'availability-optimizer'=> ['Calendar optimization', 'Scheduling tips', 'Fill open slots'],
        'upsell-assistant'      => ['Add-on suggestions', 'Package upgrades', 'Revenue growth'],
        'review-writer'         => ['Write fair & honest reviews', 'Professional tone & clarity', 'Saves time & improves quality'],
        'contract-assistant'    => ['Clause summarization', 'Key terms extraction', 'Risk & compliance insights'],
        'message-assistant'     => ['Smart message suggestions', 'Professional tone & clarity', 'Faster replies & follow-ups'],
        'translator'            => ['Multi-language translation', 'Context-aware accuracy', 'Supports documents & chat'],
    ];

    /** Feather-style 24×24 icon path(s) for a tool (fallback: generic layers). */
    public static function icon(string $key): string
    {
        return self::ICONS[$key] ?? '<path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>';
    }

    /** Three short feature bullets for a tool. */
    public static function features(string $key): array
    {
        return self::FEATURES[$key] ?? ['Smart & guided', 'Fast & simple', 'Built for events'];
    }

    /** Suite icon path — for the redesigned hub's suite selector/header. */
    public static function suiteIcon(string $suiteKey): string
    {
        return [
            'planning'    => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
            'marketplace' => '<path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0L12 5.35l-.77-.77a5.4 5.4 0 0 0-7.65 7.65l.77.77L12 21l7.65-7.65.77-.77a5.4 5.4 0 0 0 0-7.65z" transform="scale(0)"/><path d="M12 21c-1 0-8-4.5-8-10a4 4 0 0 1 7-2.6A4 4 0 0 1 20 11c0 5.5-7 10-8 10z"/>',
            'business'    => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
            'operations'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
            'automation'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        ][$suiteKey] ?? '<circle cx="12" cy="12" r="10"/>';
    }
}
