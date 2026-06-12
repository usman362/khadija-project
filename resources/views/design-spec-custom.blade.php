{{--
    DESIGN SPECIFICATION — CUSTOM DESIGN PAGES ONLY (28 pages).
    Same format as the full design-spec.pdf but filtered to only
    the pages that require bespoke UI/UX work from the designer.

    Print-friendly. Open in browser → Cmd+P → "Save as PDF" — or
    generate via Chrome headless (see project root).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GigResource — Custom Design Pages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            color: #1a202c;
            background: #f7fafc;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doc-wrap { max-width: 900px; margin: 0 auto; padding: 40px 24px; }

        /* ─── Cover ─── */
        .cover {
            min-height: calc(100vh - 80px);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            text-align: center;
            page-break-after: always;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
            color: #fff;
            padding: 60px 40px;
            border-radius: 16px;
            margin-bottom: 40px;
        }
        .cover-logo { font-size: 52px; font-weight: 900; letter-spacing: -2px; margin-bottom: 24px; }
        .cover h1 {
            font-size: 38px; font-weight: 800;
            letter-spacing: -1px; line-height: 1.15;
            margin-bottom: 18px;
            max-width: 720px;
        }
        .cover p.subtitle {
            font-size: 16px;
            opacity: 0.92;
            max-width: 580px;
            line-height: 1.6;
            margin-bottom: 36px;
        }
        .cover-meta {
            display: inline-flex; gap: 28px; flex-wrap: wrap;
            justify-content: center;
            font-size: 12px;
            background: rgba(0,0,0,0.18);
            padding: 14px 24px;
            border-radius: 12px;
        }
        .cover-meta strong { display: block; font-size: 14px; margin-bottom: 2px; }

        /* ─── TOC ─── */
        .toc {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 32px;
            page-break-after: always;
        }
        .toc h2 {
            font-size: 24px; font-weight: 800;
            margin-bottom: 18px;
        }
        .toc ol { list-style: none; counter-reset: section; padding: 0; }
        .toc li {
            counter-increment: section;
            display: flex; justify-content: space-between;
            align-items: baseline;
            padding: 10px 0;
            border-bottom: 1px dashed #cbd5e0;
            font-size: 14px;
        }
        .toc li::before {
            content: counter(section, decimal-leading-zero);
            font-weight: 700;
            color: #8b5cf6;
            margin-right: 14px;
            font-size: 13px;
        }
        .toc li .toc-title { flex: 1; font-weight: 600; }
        .toc li .toc-count {
            font-size: 12px; font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            padding: 3px 10px; border-radius: 999px;
            margin-left: 16px;
        }

        /* ─── Section ─── */
        .section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 28px;
            page-break-inside: avoid;
        }
        .section-head {
            display: flex; align-items: center; gap: 12px;
            padding-bottom: 14px;
            border-bottom: 2px solid #f7fafc;
        }
        .section-num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff;
            font-size: 14px; font-weight: 800;
        }
        .section-title { font-size: 22px; font-weight: 800; }
        .section-desc {
            font-size: 13px; color: #4a5568;
            margin: 12px 0 18px;
            line-height: 1.6;
        }
        .section-stats {
            display: inline-flex; align-items: center; gap: 6px;
            margin-left: auto;
            font-size: 11px; font-weight: 700;
            color: #fff;
            background: #4a5568;
            padding: 4px 10px;
            border-radius: 999px;
        }

        /* ─── Page card ─── */
        .page-card {
            border: 1px solid #e2e8f0;
            border-left: 4px solid #8b5cf6;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 12px;
            background: #fafbff;
            page-break-inside: avoid;
        }
        .page-card.is-public      { border-left-color: #3b82f6; }
        .page-card.is-auth        { border-left-color: #f59e0b; }
        .page-card.is-dash        { border-left-color: #8b5cf6; }
        .page-card.is-reuse       { border-left-color: #10b981; }

        .page-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 12px;
            margin-bottom: 6px;
        }
        .page-title { font-size: 14px; font-weight: 700; }
        .page-id {
            font-size: 10px; font-weight: 800;
            color: #fff;
            background: #94a3b8;
            padding: 2px 7px;
            border-radius: 4px;
        }
        .page-url {
            display: inline-block;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            font-size: 11.5px;
            color: #6b46c1;
            background: #ede9fe;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
            word-break: break-all;
        }
        .page-purpose {
            font-size: 12px;
            color: #4a5568;
            line-height: 1.55;
            margin-bottom: 6px;
        }
        .page-elements {
            font-size: 11px;
            color: #2d3748;
        }
        .page-elements strong { color: #1a202c; }

        .doc-foot {
            text-align: center;
            font-size: 11px;
            color: #718096;
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
        }

        @media print {
            html, body { background: #fff; }
            .doc-wrap { padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }

        .print-bar {
            position: fixed; top: 20px; right: 20px;
            background: #1a202c; color: #fff;
            padding: 10px 18px; border-radius: 10px;
            font-size: 13px; font-weight: 700;
            cursor: pointer; user-select: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            z-index: 1000;
        }
        .print-bar:hover { background: #2d3748; }
    </style>
</head>
<body>
<div class="print-bar no-print" onclick="window.print()">📄  Save as PDF (Cmd+P)</div>

@php
    $BASE = 'https://gigresource.com';

    $sections = [
        [
            'key' => 'public',
            'title' => 'Public Website',
            'desc' => 'Conversion-critical pages — every visitor sees these first.',
            'pages' => [
                ['id' => 'D-01', 'title' => 'Landing / Home',           'url' => '/',
                    'purpose' => 'Main marketing page. Hero + value props + featured categories + how it works + testimonials + CTA.',
                    'elements' => 'Full-bleed wedding hero, search bar with category chips, trust pill, featured pro cards row, A-Z category browser, moments gallery, FAQ accordion, newsletter, dual CTAs (Start Planning · List Your Services).'],
                ['id' => 'D-02', 'title' => 'Browse Professionals',     'url' => '/browse',
                    'purpose' => 'Searchable / filterable marketplace grid of all professionals.',
                    'elements' => 'Hero with mega search (keyword + city + button), advanced filter bar with dropdowns, active filter chips, category chip rail, grid/list view toggle, sort dropdown, pro cards (cover · avatar · badges · skills · rating · price · save heart · CTA), pagination.'],
                ['id' => 'D-03', 'title' => 'Professional Profile',     'url' => '/pro/{user}',
                    'purpose' => 'Public profile of a single professional — the booking-decision page.',
                    'elements' => 'Cover banner, avatar overlap, name + verified check + headline, breadcrumb, sticky right sidebar (Top Rated seal · Satisfaction · Verified Credentials), About, Skills, Portfolio gallery, Reviews list, Similar pros row, mobile sticky "Request Quote" bar.'],
                ['id' => 'D-04', 'title' => 'Events & Categories',      'url' => '/events-categories',
                    'purpose' => 'Alibaba-style mega category browser with audience toggle and advanced filters.',
                    'elements' => 'Hero + search, advanced filter (5 categories per audience with dropdown popovers), active chips, 9-category left rail with right showcase, sub-tile filter tabs (Popular/Top Rated/Newest/Trending), Top Services 4-col tiles, Event types section, CTA banner.'],
                ['id' => 'D-05', 'title' => 'How It Works',             'url' => '/how-it-works',
                    'purpose' => 'Step-by-step walkthrough of platform for all 3 audiences.',
                    'elements' => 'Hero, sticky 8-step pill navigator, 8 numbered step panels (audience-color-coded), each with detail cards, stat tiles for commission section, final summary CTA.'],
                ['id' => 'D-06', 'title' => 'About Us',                 'url' => '/about-us',
                    'purpose' => 'Brand story, stats, team.',
                    'elements' => 'Wedding hero with stats bar overlay, mission story, values grid, team cards, journey timeline, big-number stat band, contact CTA.'],
                ['id' => 'D-07', 'title' => 'Blog — List',              'url' => '/blog',
                    'purpose' => 'Article index with search + category filter + featured strip + grid.',
                    'elements' => 'Wedding decor hero with eyebrow + search bar, category pills, featured 3-col strip, blog grid (image · category · title · excerpt · meta · read more), pagination.'],
                ['id' => 'D-08', 'title' => 'Blog — Post Detail',       'url' => '/blog/{slug}',
                    'purpose' => 'Single article reading view.',
                    'elements' => 'Magazine hero with featured image as banner, category pill, title + meta (author · date · read time · views), full article body styled, share bar, related posts row.'],
                ['id' => 'D-09', 'title' => 'FAQ',                      'url' => '/faq',
                    'purpose' => 'Searchable, category-filtered FAQ list.',
                    'elements' => 'Wedding reception hero, eyebrow + heading + search input, category pill filter, accordion FAQ items grouped by category, "Still have questions?" CTA card.'],
                ['id' => 'D-10', 'title' => 'Join as Influencer',       'url' => '/join-as-influencer',
                    'purpose' => 'Recruit influencers for the referral program.',
                    'elements' => 'Couple-at-sunset hero with avatars cluster + trust stat, 4-step "How it works" cards, commission tier table, payout policy, sign-up CTA.'],
                ['id' => 'D-11', 'title' => 'Policy Page (1 design — 4 uses)', 'url' => '/privacy-policy · /ai-agreement · /payment-policy · /cancellation-policy',
                    'purpose' => 'Legal pages — single design template, content varies per policy.',
                    'elements' => 'Bridal-bouquet hero with eyebrow + icon + last-updated pill, long-form rich text body, optional auth-only e-signature box at bottom.'],
                ['id' => 'D-12', 'title' => 'Pricing / Membership Plans', 'url' => '/app/membership-plans',
                    'purpose' => 'Conversion-critical: 3-tier comparison + contract length picker.',
                    'elements' => 'Three-tier comparison cards (Starter · Professional · Elite), feature checklist per tier, contract length tabs (6 / 12 / 18 months), 15% off badge on 18-month, "Most Popular" highlight, Stripe checkout CTA.'],
            ],
        ],

        [
            'key' => 'auth',
            'title' => 'Authentication',
            'desc' => 'First-impression branded moments.',
            'pages' => [
                ['id' => 'D-13', 'title' => 'Login',                    'url' => '/login',
                    'purpose' => 'Email + password sign-in. Split layout with testimonial carousel side.',
                    'elements' => 'Left form column (logo · welcome · email · password · show/hide toggle · remember-me · forgot · login button · reCAPTCHA · social proof footer), right testimonial carousel side with trust stats.'],
                ['id' => 'D-14', 'title' => 'Register',                 'url' => '/register',
                    'purpose' => 'Choose role tab (Client or Professional) then fill onboarding form.',
                    'elements' => 'Step indicator (Choose role → Create account → Get started), trust strip pill, role tabs, info card per role, form (name · email · password · confirm), reCAPTCHA, sign-up button, switch-to-login link.'],
                ['id' => 'D-15', 'title' => 'Forgot / Reset Password (1 design)', 'url' => '/password/reset',
                    'purpose' => 'Email-input or new-password reset flow — same shell reused.',
                    'elements' => 'Centered card with input + primary button + back-to-login link. Two states: request reset / set new password (token-protected).'],
            ],
        ],

        [
            'key' => 'dash',
            'title' => 'Dashboard Home Pages',
            'desc' => 'Welcome layout — one design per role.',
            'pages' => [
                ['id' => 'D-16', 'title' => 'Client Dashboard Home',    'url' => '/client/dashboard',
                    'purpose' => 'Overview of upcoming events, recent bookings, messages, suggested pros.',
                    'elements' => 'Top navbar (logo · search · notifications · theme toggle · role switcher · avatar), left sidebar with role-coloured icons, welcome banner, 4 stat cards, upcoming events list, recent bookings, recommended pros carousel, AI tools quick-access cards.'],
                ['id' => 'D-17', 'title' => 'Professional Dashboard Home', 'url' => '/professional/dashboard',
                    'purpose' => 'Earnings overview, active proposals, recent gigs, messages, reviews.',
                    'elements' => 'Same navbar / sidebar shell as client (coral-themed icons), revenue stat cards, upcoming gigs strip, lead status counts (won/lost/pending), recent messages, ratings widget, AI Review Writer quick link.'],
                ['id' => 'D-18', 'title' => 'Influencer Dashboard Home', 'url' => '/influencer/dashboard',
                    'purpose' => 'Performance overview for referral-program members.',
                    'elements' => 'Top stats (total earned · this month · pending payout · click count), unique referral link with copy button, recent referrals strip, tier-progress widget, marketing assets download.'],
                ['id' => 'D-19', 'title' => 'Admin Dashboard Home',     'url' => '/dashboard',
                    'purpose' => 'Platform-wide stats and recent activity.',
                    'elements' => 'NobleUI top bar + collapsible sidebar (role-coloured icons), KPI cards (users · revenue · gigs · bookings), revenue chart, recent users, latest bookings, system alerts.'],
            ],
        ],

        [
            'key' => 'reuse',
            'title' => 'Reusable Design Systems',
            'desc' => 'One design serves many pages — high leverage deliverables.',
            'pages' => [
                ['id' => 'D-20', 'title' => 'Profile Editor (1 design — 3 roles)', 'url' => '/client/profile · /professional/profile · /app/admin/profile',
                    'purpose' => 'Personal info, password, notifications. Reused by client / professional / admin.',
                    'elements' => 'Sidebar tab nav (Basic · Address · Company · Social · Notifications · Security · Account), avatar card with upload, form grid per tab, danger zone (account deletion), cover banner for pro variant.'],
                ['id' => 'D-21', 'title' => 'Messages / Chat (1 design — 2 roles)', 'url' => '/client/messages · /professional/messages',
                    'purpose' => 'Conversation list + thread view. Same UI for client and professional inbox.',
                    'elements' => 'Two-pane: conversation list left + thread right, unread badges, attachment chips, send composer with emoji + file upload, online status dots, search inbox.'],
                ['id' => 'D-22', 'title' => 'AI Tools Shell (1 design — 3 tools)', 'url' => '/ai-tools/budget-allocator · /ai-tools/vendor-matchmaking · /ai-tools/review-writer',
                    'purpose' => 'Form + AI-result panel pattern reused across Budget Allocator, Vendor Matchmaking, and Review Writer.',
                    'elements' => 'Hero banner card, quota badge, form grid (event details / requirements), generate button, loading state, result panel with summary + breakdown, edit/copy/export actions, upgrade prompt if quota exhausted.'],
                ['id' => 'D-23', 'title' => 'Professional Card (component)', 'url' => 'used in /browse · home · search results',
                    'purpose' => 'Tile pattern reused everywhere a pro appears in a list.',
                    'elements' => 'Cover image, badges (Top rated · Verified · New), save-heart button, avatar with online dot, name + verified check, headline, skill chips, rating + count, location, hourly price, View Profile CTA.'],
                ['id' => 'D-24', 'title' => 'Booking Card (component)',  'url' => 'used in /client/bookings · /professional/dashboard · /app/bookings',
                    'purpose' => 'Reused across all dashboards wherever a booking appears.',
                    'elements' => 'Pro / client avatar, service name, event date + time, location, amount, status pill, primary action button, three-dot menu (cancel · message · review).'],
                ['id' => 'D-25', 'title' => 'Membership Tier Card',     'url' => 'used in /app/membership-plans · landing pricing section',
                    'purpose' => 'Pricing-page tile for the 3-tier comparison.',
                    'elements' => 'Tier name + tagline, big monthly-equivalent price, contract toggle, feature checklist with check icons, "Most Popular" badge for middle tier, "Choose Plan" CTA, Elite-tier gold accent.'],
                ['id' => 'D-26', 'title' => 'Empty State (1 design — reused)', 'url' => 'used in browse · bookings · messages · etc.',
                    'purpose' => 'Friendly no-results pattern for empty lists.',
                    'elements' => 'Centered icon in gradient pill, "Nothing here yet" headline, helpful body copy, 1-2 action CTAs (reset filters · explore categories).'],
                ['id' => 'D-27', 'title' => 'Error Page Shell',         'url' => '/(404 · 500 · 403 · 419 · 429 · 401 · 503)',
                    'purpose' => 'Already built — just polish if desired. 7 variants share one shell.',
                    'elements' => 'Big emoji, eyebrow pill with error code, gradient title, tagline, primary + secondary CTAs (Back to Home · Browse). Per-code emoji + messaging variations.'],
                ['id' => 'D-28', 'title' => 'Email Templates (1 master)', 'url' => 'transactional emails',
                    'purpose' => 'Master email shell for all transactional notifications.',
                    'elements' => 'Header with logo, hero block, body copy area, primary CTA button, footer with unsubscribe + legal links. Variants: welcome · booking confirmed · message received · payment receipt · review request · cancellation notice.'],
            ],
        ],
    ];
@endphp

<div class="doc-wrap">

    {{-- Cover --}}
    <div class="cover">
        <div class="cover-logo">GIG RESOURCE</div>
        <h1>Custom Design Specification</h1>
        <p class="subtitle">
            The pages that require bespoke UI/UX design work. Every visitor-facing surface, every conversion moment, and every reusable design system the platform needs.
        </p>
        <div class="cover-meta">
            <div><strong>{{ collect($sections)->sum(fn ($s) => count($s['pages'])) }}</strong>Pages</div>
            <div><strong>{{ count($sections) }}</strong>Sections</div>
            <div><strong>v1.0</strong>Updated {{ now()->format('M j, Y') }}</div>
        </div>
    </div>

    {{-- TOC --}}
    <div class="toc">
        <h2>Table of Contents</h2>
        <ol>
            @foreach($sections as $section)
                <li>
                    <span class="toc-title">{{ $section['title'] }}</span>
                    <span class="toc-count">{{ count($section['pages']) }} pages</span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Sections --}}
    @foreach($sections as $i => $section)
        <div class="section">
            <div class="section-head">
                <div class="section-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="section-title">{{ $section['title'] }}</div>
                <span class="section-stats">{{ count($section['pages']) }} pages</span>
            </div>
            <p class="section-desc">{{ $section['desc'] }}</p>

            @foreach($section['pages'] as $page)
                <div class="page-card is-{{ $section['key'] }}">
                    <div class="page-head">
                        <div class="page-title">{{ $page['title'] }}</div>
                        <span class="page-id">{{ $page['id'] }}</span>
                    </div>
                    <code class="page-url">{{ str_contains($page['url'], '/') && !str_starts_with($page['url'], 'used') ? $BASE . str_replace(' · ', '  ·  ' . $BASE, $page['url']) : $page['url'] }}</code>
                    <p class="page-purpose">{{ $page['purpose'] }}</p>
                    <div class="page-elements"><strong>Elements: </strong>{!! $page['elements'] !!}</div>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="doc-foot">
        GigResource Custom Design Specification &middot; Generated {{ now()->format('F j, Y') }} &middot; Base URL: {{ $BASE }}
    </div>
</div>

</body>
</html>
