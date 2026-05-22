{{--
    DESIGN BREAKDOWN — splits the 108-page inventory into:
      • 28 pages that need bespoke UI/UX from the designer
      • 80 pages the dev team can build with existing components/templates

    Print-ready. Open in browser → Cmd+P → "Save as PDF" — or generate
    headless via the Chrome command in storage/app/.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GigResource — Design Scope Breakdown</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            color: #1a202c;
            background: #f7fafc;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doc-wrap { max-width: 900px; margin: 0 auto; padding: 36px 24px; }

        /* ─── COVER ─── */
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
            margin-bottom: 36px;
        }
        .cover-logo {
            font-size: 50px; font-weight: 900;
            letter-spacing: -2px;
            margin-bottom: 22px;
        }
        .cover h1 {
            font-size: 34px; font-weight: 800;
            letter-spacing: -1px; line-height: 1.15;
            margin-bottom: 16px;
            max-width: 720px;
        }
        .cover p.subtitle {
            font-size: 15px;
            opacity: 0.92;
            max-width: 580px;
            line-height: 1.6;
            margin-bottom: 36px;
        }
        .cover-stats {
            display: flex; gap: 14px; flex-wrap: wrap;
            justify-content: center;
        }
        .cover-stat {
            background: rgba(0,0,0,0.22);
            padding: 18px 26px;
            border-radius: 14px;
            backdrop-filter: blur(8px);
            min-width: 130px;
        }
        .cover-stat .v {
            font-size: 32px; font-weight: 900;
            line-height: 1;
            margin-bottom: 6px;
        }
        .cover-stat .k {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            opacity: 0.9;
        }

        /* ─── EXECUTIVE SUMMARY ─── */
        .summary {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 28px;
            page-break-after: always;
        }
        .summary h2 {
            font-size: 22px; font-weight: 800;
            color: #1a202c;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f7fafc;
        }
        .summary p {
            font-size: 13.5px;
            color: #4a5568;
            line-height: 1.7;
            margin-bottom: 14px;
        }
        .summary .pie {
            display: flex; gap: 16px;
            margin: 24px 0;
        }
        .pie-card {
            flex: 1;
            padding: 22px;
            border-radius: 12px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .pie-card.custom { background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .pie-card.dev    { background: linear-gradient(135deg, #94a3b8, #475569); }
        .pie-card .label {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.88;
            margin-bottom: 6px;
        }
        .pie-card .big {
            font-size: 42px; font-weight: 900;
            line-height: 1; margin-bottom: 8px;
        }
        .pie-card .small {
            font-size: 13px; font-weight: 600;
            opacity: 0.95;
            margin-bottom: 4px;
        }
        .pie-card .pct {
            font-size: 11px;
            opacity: 0.75;
        }

        .reasons h3 {
            font-size: 13px; font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #4a5568;
            margin: 22px 0 12px;
        }
        .reasons ol {
            padding-left: 20px;
            font-size: 13px;
            color: #2d3748;
            line-height: 1.7;
        }
        .reasons ol li { margin-bottom: 6px; }
        .reasons ol li strong { color: #1a202c; }

        /* ─── SECTION ─── */
        .section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 26px 30px;
            margin-bottom: 26px;
            page-break-inside: avoid;
        }
        .section-head {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid #f7fafc;
        }
        .section-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px;
            border-radius: 12px;
            color: #fff;
            font-size: 16px; font-weight: 800;
        }
        .section-badge.custom { background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .section-badge.dev    { background: linear-gradient(135deg, #94a3b8, #475569); }
        .section-title {
            font-size: 20px; font-weight: 800; color: #1a202c;
        }
        .section-meta {
            font-size: 12px; color: #64748b;
            margin-top: 2px;
        }
        .section-count {
            margin-left: auto;
            font-size: 12px; font-weight: 800;
            color: #fff;
            background: #1a202c;
            padding: 6px 14px;
            border-radius: 999px;
        }

        .subsection {
            margin: 18px 0;
        }
        .subsection h3 {
            font-size: 14px; font-weight: 800;
            color: #1a202c;
            margin-bottom: 8px;
            display: flex; align-items: center; gap: 8px;
        }
        .subsection h3 .subsection-count {
            font-size: 10.5px;
            color: #4a5568;
            background: #edf2f7;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 700;
        }

        /* ─── PAGE ROW ─── */
        .page-row {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 9px 12px;
            border-radius: 6px;
            background: #fafbff;
            border-left: 3px solid #8b5cf6;
            margin-bottom: 5px;
            font-size: 12px;
            page-break-inside: avoid;
        }
        .page-row.dev { border-left-color: #64748b; background: #f7fafc; }
        .page-num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px;
            border-radius: 6px;
            background: #1a202c;
            color: #fff;
            font-size: 10.5px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .page-name {
            flex: 1;
            color: #1a202c;
            font-weight: 600;
        }
        .page-note {
            font-size: 10.5px;
            color: #64748b;
            font-style: italic;
            text-align: right;
            white-space: nowrap;
        }
        @media (max-width: 600px) {
            .page-note { white-space: normal; text-align: left; margin-top: 2px; }
        }

        /* ─── FINAL TABLE ─── */
        .final {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 28px;
        }
        .final h2 {
            font-size: 22px; font-weight: 800;
            color: #1a202c;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f7fafc;
        }
        .final-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .final-table th, .final-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .final-table th {
            background: #f7fafc;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.8px;
            color: #4a5568;
        }
        .final-table tr.highlight td {
            background: linear-gradient(90deg, rgba(59,130,246,0.06), rgba(139,92,246,0.04));
            font-weight: 700;
        }
        .final-table tr.total td {
            border-top: 2px solid #1a202c;
            font-size: 14px;
            font-weight: 800;
            background: #1a202c;
            color: #fff;
        }

        .doc-foot {
            text-align: center;
            font-size: 11px;
            color: #718096;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        @media print {
            html, body { background: #fff; }
            .doc-wrap { padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }

        .print-bar {
            position: fixed; top: 18px; right: 18px;
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
    /* Pages flagged for CUSTOM design (28) vs DEV-handled (80) */
    $customGroups = [
        [
            'title' => 'Public Website',
            'note'  => 'Top conversion surface — every visitor sees these first',
            'pages' => [
                ['Landing / Home', 'Hero + value props + CTAs'],
                ['Browse Professionals', 'Marketplace grid + filters + cards'],
                ['Professional Profile Detail', 'The booking-decision page'],
                ['Events & Categories', 'Mega-panel + advanced filter system'],
                ['How It Works', '8-step audience-coded journey'],
                ['About Us', 'Brand story + team + stats'],
                ['Blog Index', 'Content discovery — featured + grid'],
                ['Blog Post Detail', 'Editorial reading view'],
                ['FAQ', 'Search + category accordion'],
                ['Join as Influencer', 'Recruitment CTA flow'],
                ['Policy Page (1 design — 4 uses)', 'Privacy / AI / Payment / Cancellation'],
                ['Pricing / Membership Plans', '3-tier comparison + contract picker'],
            ],
        ],
        [
            'title' => 'Authentication',
            'note'  => 'First-impression branded moments',
            'pages' => [
                ['Login (split layout)', 'Testimonial carousel side'],
                ['Register (role picker)', 'Step indicator + role tabs'],
                ['Forgot / Reset Password (1 design)', 'Reused across flows'],
            ],
        ],
        [
            'title' => 'Dashboard Home Pages',
            'note'  => 'Welcome layouts — one per role',
            'pages' => [
                ['Client Dashboard Home', 'KPIs + upcoming events + recs'],
                ['Professional Dashboard Home', 'Earnings + leads + reviews'],
                ['Influencer Dashboard Home', 'Referral tracker + payouts'],
                ['Admin Dashboard Home', 'Platform stats overview'],
            ],
        ],
        [
            'title' => 'Reusable Design Systems',
            'note'  => 'One design — used across many pages',
            'pages' => [
                ['Profile Editor (1 design — multi-role)', 'Sidebar-tabs layout'],
                ['Messages / Chat (1 design — multi-role)', 'Two-pane chat shell'],
                ['AI Tools Shell (1 design — 3 tools)', 'Form + result panel'],
                ['Professional Card (component)', 'Tile pattern reused everywhere'],
                ['Booking Card (component)', 'Reused across dashboards'],
                ['Membership Tier Card', 'Pricing-page tile'],
                ['Empty State (1 design — reused)', 'Friendly no-results pattern'],
                ['Error Page Shell (already built)', 'Just polish — 7 variants'],
                ['Email Templates (1 master)', 'Transactional emails'],
            ],
        ],
    ];

    $devGroups = [
        [
            'title' => 'Admin CRUD',
            'note'  => 'Standard NobleUI Bootstrap shell — tables, forms, modals',
            'pages' => [
                'Events list / detail', 'Bookings list', 'Categories list / create / edit',
                'Membership Plans admin', 'FAQ Management', 'Policy editor',
                'Blog posts list / create / edit', 'Blog categories',
                'Users · Roles · Permissions', 'Verifications queue',
                'Deletion requests', 'Activity logs', 'Chatbot logs',
                'Influencers admin + payouts', 'AI Agreements list / detail',
                'Agreement log',
            ],
        ],
        [
            'title' => 'Admin Settings',
            'note'  => 'Same form shell, different fields',
            'pages' => [
                'Payment Settings', 'OpenAI Settings', 'reCAPTCHA Settings',
                'AI Chatbot Settings', 'Account Deletion Settings',
            ],
        ],
        [
            'title' => 'Inner Dashboard Lists',
            'note'  => 'Use the design system from "Reusable" above',
            'pages' => [
                'Client: My Events list, Event detail, My Bookings',
                'Client: Message threads, Membership history, Payment history',
                'Client: Payment success / cancel, Agreement detail',
                'Professional: Gigs list, Gig detail, Proposals',
                'Professional: Reviews, Earnings, Transactions, CSV/PDF export',
                'Influencer: Referrals list, Payouts list',
                'Admin Profile editor',
            ],
        ],
        [
            'title' => 'Utility Pages',
            'note'  => 'Minimal design — small functional confirmations',
            'pages' => [
                'Forgot / Reset confirm screens', 'Account restore',
                'Reactivation success / cancel', 'Referral landing',
                'Payment success / cancel', '7 error pages (already designed)',
            ],
        ],
    ];

    $customTotal = collect($customGroups)->sum(fn ($g) => count($g['pages']));
    $devTotal = 80; // matches earlier count
@endphp

<div class="doc-wrap">

    {{-- ─── COVER ─── --}}
    <div class="cover">
        <div class="cover-logo">GIG RESOURCE</div>
        <h1>Design Scope Breakdown</h1>
        <p class="subtitle">
            Separating the 108-page inventory into pages that need custom design work versus pages the development team can build directly using consistent components and templates.
        </p>
        <div class="cover-stats">
            <div class="cover-stat">
                <div class="v">{{ $customTotal }}</div>
                <div class="k">Custom Design</div>
            </div>
            <div class="cover-stat">
                <div class="v">{{ $devTotal }}</div>
                <div class="k">Dev-Handled</div>
            </div>
            <div class="cover-stat">
                <div class="v">108</div>
                <div class="k">Total Pages</div>
            </div>
        </div>
    </div>

    {{-- ─── EXECUTIVE SUMMARY ─── --}}
    <div class="summary">
        <h2>Executive Summary</h2>
        <p>
            The total platform contains 108 pages across the public website, authentication flow, three role-specific dashboards (client, professional, influencer), and the admin panel. After review, we recommend splitting them into two clearly-defined categories so the design budget focuses on what truly moves the needle.
        </p>

        <div class="pie">
            <div class="pie-card custom">
                <div class="label">Custom Design</div>
                <div class="big">{{ $customTotal }}</div>
                <div class="small">Pages needing bespoke UI/UX</div>
                <div class="pct">≈ {{ round($customTotal / 108 * 100) }}% of total</div>
            </div>
            <div class="pie-card dev">
                <div class="label">Dev-Handled</div>
                <div class="big">{{ $devTotal }}</div>
                <div class="small">Use existing components / templates</div>
                <div class="pct">≈ {{ round($devTotal / 108 * 100) }}% of total</div>
            </div>
        </div>

        <div class="reasons">
            <h3>Why this split makes sense</h3>
            <ol>
                <li><strong>Conversion focus.</strong> ~80% of user traffic lands on the 28 custom-designed pages (landing → browse → profile → register → dashboard home). This is where bespoke design directly impacts sign-ups and bookings.</li>
                <li><strong>Component reuse.</strong> One profile-editor design covers 3 role profile pages. One chat design covers 2 role inboxes. One AI-tool shell covers 3 tools. That collapses 11 pages into 3 design deliverables.</li>
                <li><strong>Admin doesn't need brand polish.</strong> Admins are internal users; standard tables and form layouts (NobleUI template already in place) handle the look-and-feel out of the box.</li>
                <li><strong>Error / utility pages already styled.</strong> All 7 error variants (404 / 500 / 403 / 419 / 429 / 401 / 503) are already custom-coded with the brand palette — designer only needs to polish if desired.</li>
                <li><strong>Faster delivery.</strong> Designer focuses on 28 high-impact pages instead of 108. Dev team unblocks 80 pages immediately using the design system.</li>
            </ol>
        </div>
    </div>

    {{-- ─── CUSTOM DESIGN PAGES ─── --}}
    <div class="section">
        <div class="section-head">
            <div class="section-badge custom">A</div>
            <div>
                <div class="section-title">Custom Design Required</div>
                <div class="section-meta">Bespoke UI/UX work for the designer</div>
            </div>
            <div class="section-count">{{ $customTotal }} pages</div>
        </div>

        @php $globalNum = 0; @endphp
        @foreach($customGroups as $group)
            <div class="subsection">
                <h3>{{ $group['title'] }} <span class="subsection-count">{{ count($group['pages']) }} pages</span></h3>
                <p style="font-size:11.5px;color:#64748b;margin-bottom:8px;font-style:italic;">{{ $group['note'] }}</p>
                @foreach($group['pages'] as $page)
                    @php $globalNum++; @endphp
                    <div class="page-row">
                        <span class="page-num">{{ str_pad($globalNum, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="page-name">{{ $page[0] }}</span>
                        <span class="page-note">{{ $page[1] }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ─── DEV-HANDLED PAGES ─── --}}
    <div class="section">
        <div class="section-head">
            <div class="section-badge dev">B</div>
            <div>
                <div class="section-title">Dev-Handled Pages</div>
                <div class="section-meta">Built directly using existing components & templates</div>
            </div>
            <div class="section-count">{{ $devTotal }} pages</div>
        </div>

        @foreach($devGroups as $group)
            <div class="subsection">
                <h3>{{ $group['title'] }} <span class="subsection-count">{{ count($group['pages']) }} groups</span></h3>
                <p style="font-size:11.5px;color:#64748b;margin-bottom:8px;font-style:italic;">{{ $group['note'] }}</p>
                @foreach($group['pages'] as $page)
                    <div class="page-row dev">
                        <span class="page-num" style="background:#64748b;">•</span>
                        <span class="page-name">{{ $page }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ─── FINAL BREAKDOWN TABLE ─── --}}
    <div class="final">
        <h2>Final Page Count Summary</h2>
        <table class="final-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align:center;">Pages</th>
                    <th style="text-align:right;">% of Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="highlight">
                    <td>Custom Design Required (Designer)</td>
                    <td style="text-align:center;font-size:16px;">{{ $customTotal }}</td>
                    <td style="text-align:right;">{{ round($customTotal / 108 * 100) }}%</td>
                </tr>
                <tr>
                    <td>Dev-Handled with Components</td>
                    <td style="text-align:center;">{{ $devTotal }}</td>
                    <td style="text-align:right;">{{ round($devTotal / 108 * 100) }}%</td>
                </tr>
                <tr class="total">
                    <td>TOTAL PAGES IN PLATFORM</td>
                    <td style="text-align:center;">108</td>
                    <td style="text-align:right;">100%</td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top:18px;font-size:12.5px;color:#4a5568;line-height:1.7;">
            <strong>Recommendation:</strong> Engage the designer on the <strong>{{ $customTotal }} custom pages</strong> only. The development team will deliver the remaining 80 pages in parallel using the design system + existing templates, with full visual consistency across the platform.
        </p>
    </div>

    <div class="doc-foot">
        GigResource Design Scope Breakdown &middot; Generated {{ now()->format('F j, Y') }} &middot; Companion document to <code>GigResource-Design-Spec.pdf</code>
    </div>
</div>

</body>
</html>
