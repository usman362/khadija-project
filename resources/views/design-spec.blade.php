{{--
    DESIGN SPECIFICATION — full page inventory for the designer.
    Print-friendly HTML. Open in browser → Cmd+P (Ctrl+P) → "Save as PDF".

    Each section lists every page the designer needs to mock up, with:
      • Title
      • URL
      • Audience / Access level
      • Short purpose blurb
      • Key elements / components to design

    Sections in print-order:
      1. Cover page
      2. Public website
      3. Authentication
      4. Client dashboard
      5. Professional dashboard
      6. Influencer dashboard
      7. Admin dashboard
      8. Error pages
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GigResource — Design Specification</title>
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

        /* ─── Cover page ─── */
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
        .cover-logo {
            font-size: 52px; font-weight: 900;
            letter-spacing: -2px;
            margin-bottom: 24px;
        }
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
            backdrop-filter: blur(6px);
        }
        .cover-meta strong { display: block; font-size: 14px; margin-bottom: 2px; }

        /* ─── Table of contents ─── */
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
            color: #1a202c;
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
        .toc li .toc-title {
            flex: 1; font-weight: 600; color: #1a202c;
        }
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
            margin-bottom: 4px;
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
        .section-title { font-size: 22px; font-weight: 800; color: #1a202c; }
        .section-desc {
            font-size: 13px; color: #4a5568;
            margin: 8px 0 18px;
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
        .page-card.is-client      { border-left-color: #8b5cf6; }
        .page-card.is-pro         { border-left-color: #ec4899; }
        .page-card.is-influencer  { border-left-color: #06b6d4; }
        .page-card.is-admin       { border-left-color: #ef4444; }
        .page-card.is-error       { border-left-color: #64748b; }

        .page-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 12px;
            margin-bottom: 6px;
        }
        .page-title {
            font-size: 14px; font-weight: 700;
            color: #1a202c;
        }
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
        .page-elements em {
            font-style: normal;
            color: #6b7280;
            font-size: 10.5px;
        }

        /* ─── Footer ─── */
        .doc-foot {
            text-align: center;
            font-size: 11px;
            color: #718096;
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
        }

        /* ─── Print overrides ─── */
        @media print {
            html, body { background: #fff; }
            .doc-wrap { padding: 0; max-width: 100%; }
            .section, .toc, .cover { box-shadow: none; }
            .page-card { box-shadow: none; }
            .no-print { display: none; }
        }

        /* Helper print bar */
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
    /*
     * Full page inventory grouped by section. Each entry:
     *   id:        unique short code (e.g. WEB-01)
     *   title:     designer-facing page name
     *   url:       path relative to dashboard.gigresource.com
     *   purpose:   one-line designer brief
     *   elements:  bullet list of key components to design
     *   access:    who sees it
     */
    $BASE = 'https://dashboard.gigresource.com';

    $sections = [
        // ─── 1. PUBLIC WEBSITE ─────────────────────────────────────
        [
            'key' => 'web',
            'title' => 'Public Website',
            'desc' => 'Pages anyone can visit without logging in. Marketing surface for the marketplace.',
            'pages' => [
                ['id' => 'WEB-01', 'title' => 'Landing / Home',           'url' => '/',                       'purpose' => 'Main marketing page. Hero banner + value props + featured categories + how it works + testimonials + CTA.',
                    'elements' => 'Full-bleed wedding hero, search bar with category chips, trust pill, featured pro cards row, A-Z category browser, moments gallery, FAQ accordion, newsletter, dual CTAs (Start Planning · List Your Services).'],
                ['id' => 'WEB-02', 'title' => 'Browse Professionals',     'url' => '/browse',                 'purpose' => 'Searchable / filterable marketplace grid of all professionals.',
                    'elements' => 'Hero with mega search (keyword + city + button), 8-trigger advanced filter bar with dropdowns, active filter chips, category chip rail, grid/list view toggle, sort dropdown, pro cards (cover · avatar · badges · skills · rating · price · save heart · CTA), pagination.'],
                ['id' => 'WEB-03', 'title' => 'Professional Detail',      'url' => '/pro/{user}',             'purpose' => 'Public profile of a single professional — the conversion page.',
                    'elements' => 'Cover banner, avatar overlap, name + verified check + headline, breadcrumb, sticky right sidebar (Top Rated seal · Satisfaction · Verified Credentials), About, Skills, Portfolio gallery, Reviews list, Similar pros row, mobile sticky "Request Quote" bar.'],
                ['id' => 'WEB-04', 'title' => 'Events & Categories',      'url' => '/events-categories',      'purpose' => 'Alibaba-style mega category browser. Audience toggle + advanced filters + sub-tile mega panel + top services grid.',
                    'elements' => 'Hero + search, advanced filter (5 categories per audience with dropdown popovers), active chips, 9-category left rail with right showcase, sub-tile filter tabs (Popular/Top Rated/Newest/Trending), Top Services 4-col tiles, Event types section, CTA banner.'],
                ['id' => 'WEB-05', 'title' => 'How It Works',             'url' => '/how-it-works',           'purpose' => 'Step-by-step walkthrough of platform for all 3 audiences.',
                    'elements' => 'Hero, sticky 8-step pill navigator, 8 numbered step panels (audience-color-coded), each with detail cards, stat tiles for commission section, final summary CTA.'],
                ['id' => 'WEB-06', 'title' => 'About Us',                 'url' => '/about-us',               'purpose' => 'Brand story, stats, team.',
                    'elements' => 'Wedding hero with stats bar overlay, mission story, values grid, team cards, journey timeline, big-number stat band, contact CTA.'],
                ['id' => 'WEB-07', 'title' => 'Blog — List',              'url' => '/blog',                   'purpose' => 'Article index with search + category filter + featured strip + grid.',
                    'elements' => 'Wedding decor hero with eyebrow + search bar, category pills, featured 3-col strip, blog grid (image · category · title · excerpt · meta · read more), pagination.'],
                ['id' => 'WEB-08', 'title' => 'Blog — Post Detail',       'url' => '/blog/{slug}',            'purpose' => 'Single article reading view.',
                    'elements' => 'Magazine hero with featured image as banner, category pill, title + meta (author · date · read time · views), full article body styled, share bar, related posts row.'],
                ['id' => 'WEB-09', 'title' => 'FAQ',                      'url' => '/faq',                    'purpose' => 'Searchable, category-filtered FAQ list.',
                    'elements' => 'Wedding reception hero, eyebrow + heading + search input, category pill filter, accordion FAQ items grouped by category, "Still have questions?" CTA card.'],
                ['id' => 'WEB-10', 'title' => 'Join as Influencer',       'url' => '/join-as-influencer',     'purpose' => 'Recruit influencers for the referral program.',
                    'elements' => 'Couple-at-sunset hero with avatars cluster + trust stat, 4-step "How it works" cards, commission tier table, payout policy, sign-up CTA.'],
                ['id' => 'WEB-11', 'title' => 'Privacy Policy',           'url' => '/privacy-policy',         'purpose' => 'Legal — privacy.',
                    'elements' => 'Bridal-bouquet hero with eyebrow + icon + last-updated pill, long-form rich text content, optional auth-only e-signature box at bottom.'],
                ['id' => 'WEB-12', 'title' => 'AI Usage Agreement',       'url' => '/ai-agreement',           'purpose' => 'Legal — AI features.',
                    'elements' => 'Same hero shell as Privacy, content varies, e-signature box.'],
                ['id' => 'WEB-13', 'title' => 'Payment Policy',           'url' => '/payment-policy',         'purpose' => 'Legal — payments.',
                    'elements' => 'Same hero shell, content varies.'],
                ['id' => 'WEB-14', 'title' => 'Cancellation & Refund',    'url' => '/cancellation-policy',    'purpose' => 'Legal — cancellation rules.',
                    'elements' => 'Same hero shell, content varies. References $4.99 cancellation fee.'],
                ['id' => 'WEB-15', 'title' => 'Influencer Referral Land', 'url' => '/ref/{code}',             'purpose' => 'Landing page hit when someone clicks an influencer referral link.',
                    'elements' => 'Captures cookie + shows welcome + redirects to sign-up funnel.'],
            ],
        ],

        // ─── 2. AUTHENTICATION ─────────────────────────────────────
        [
            'key' => 'auth',
            'title' => 'Authentication',
            'desc' => 'Sign in, sign up, and password reset flows.',
            'pages' => [
                ['id' => 'AUTH-01', 'title' => 'Login',                   'url' => '/login',                  'purpose' => 'Email + password sign-in. Split layout with testimonial carousel side.',
                    'elements' => 'Left form column (logo · welcome · email · password · show/hide toggle · remember-me · forgot · login button · reCAPTCHA · social proof footer), right testimonial carousel side with trust stats.'],
                ['id' => 'AUTH-02', 'title' => 'Register — Role Picker',  'url' => '/register',               'purpose' => 'Choose role tab (Client or Professional) before filling form.',
                    'elements' => 'Step indicator (Choose role → Create account → Get started), trust strip pill, role tabs, info card per role, form (name · email · password · confirm), reCAPTCHA, sign-up button, switch-to-login link.'],
                ['id' => 'AUTH-03', 'title' => 'Register — Client',       'url' => '/register?role=client',   'purpose' => 'Same as above but client role pre-selected.',
                    'elements' => 'Identical to AUTH-02 with client tab active.'],
                ['id' => 'AUTH-04', 'title' => 'Register — Professional', 'url' => '/register?role=supplier', 'purpose' => 'Same with supplier role pre-selected.',
                    'elements' => 'Identical to AUTH-02 with professional tab active.'],
                ['id' => 'AUTH-05', 'title' => 'Forgot Password',         'url' => '/password/reset',         'purpose' => 'Email input → send reset link.',
                    'elements' => 'Centered card with email input + send button + back-to-login link.'],
                ['id' => 'AUTH-06', 'title' => 'Reset Password',          'url' => '/password/reset/{token}', 'purpose' => 'New password + confirm.',
                    'elements' => 'Centered card with token-protected new password + confirm + submit.'],
                ['id' => 'AUTH-07', 'title' => 'Confirm Password',        'url' => '/password/confirm',       'purpose' => 'Re-confirm password before sensitive action (e.g. account deletion).',
                    'elements' => 'Modal-style card with single password input + confirm button.'],
                ['id' => 'AUTH-08', 'title' => 'Account Restore',         'url' => '/account/deletion/restore', 'purpose' => 'Restore a soft-deleted account within grace period.',
                    'elements' => 'Centered card with restore CTA + explanation + cancel link.'],
                ['id' => 'AUTH-09', 'title' => 'Reactivation Success',    'url' => '/account/reactivation/success', 'purpose' => 'Success confirmation after reactivation.',
                    'elements' => 'Big checkmark, success message, return-to-dashboard CTA.'],
                ['id' => 'AUTH-10', 'title' => 'Reactivation Cancel',     'url' => '/account/reactivation/cancel',  'purpose' => 'Cancellation confirmation.',
                    'elements' => 'Cancel-state messaging, "go to login" CTA.'],
            ],
        ],

        // ─── 3. CLIENT DASHBOARD ───────────────────────────────────
        [
            'key' => 'client',
            'title' => 'Client Dashboard',
            'desc' => 'Pages a logged-in event-planner / client uses to find and book professionals.',
            'pages' => [
                ['id' => 'CLI-01', 'title' => 'Dashboard Home',           'url' => '/client/dashboard',       'purpose' => 'Overview of upcoming events, recent bookings, messages, suggested pros.',
                    'elements' => 'Top navbar (logo · search · notifications · theme toggle · role switcher · avatar), left sidebar with role-coloured icons, welcome banner, 4 stat cards, upcoming events list, recent bookings, recommended pros carousel, AI tools quick-access cards.'],
                ['id' => 'CLI-02', 'title' => 'My Events — List',         'url' => '/client/events',          'purpose' => 'List of all events created by this client.',
                    'elements' => 'Page header, "Create Event" button, status filter pills, event cards (title · date · category · status · actions menu), empty state.'],
                ['id' => 'CLI-03', 'title' => 'Event Detail',             'url' => '/client/events/{id}',     'purpose' => 'Single event with bookings + messages tab.',
                    'elements' => 'Breadcrumb, event header (title · date · location · budget · status pill), tab strip (Overview · Bookings · Quotes · Documents), tab content panels.'],
                ['id' => 'CLI-04', 'title' => 'My Bookings',              'url' => '/client/bookings',        'purpose' => 'All confirmed bookings list.',
                    'elements' => 'Filter strip (status · date range), booking cards with pro avatar + service + date + amount + actions, review-leaving modal trigger.'],
                ['id' => 'CLI-05', 'title' => 'Messages — Inbox',         'url' => '/client/messages',        'purpose' => 'Conversation list with pros.',
                    'elements' => 'Two-pane: conversation list left + thread right, unread badges, attachment chips, send composer with emoji + file upload.'],
                ['id' => 'CLI-06', 'title' => 'Message Thread',           'url' => '/client/messages/{id}',   'purpose' => 'Single conversation view.',
                    'elements' => 'Same as inbox but thread-focused, with pro card sidebar.'],
                ['id' => 'CLI-07', 'title' => 'AI Budget Allocator',      'url' => '/ai-tools/budget-allocator', 'purpose' => 'Enter event details → AI breaks down budget by category.',
                    'elements' => 'Hero banner card, quota badge, form (event type · budget · guest count · currency · location · date · priorities), generate button, result panel with category bars, summary, allocation list, upgrade prompt if quota exhausted.'],
                ['id' => 'CLI-08', 'title' => 'AI Vendor Matchmaking',    'url' => '/ai-tools/vendor-matchmaking', 'purpose' => 'AI suggests matching pros based on requirements.',
                    'elements' => 'Hero card, form (event type · budget · guest count · location · date · requirements textarea), find matches button, match cards with reasoning and score.'],
                ['id' => 'CLI-09', 'title' => 'AI Review Writer',         'url' => '/ai-tools/review-writer',  'purpose' => 'AI drafts review text based on inputs.',
                    'elements' => 'Hero, form (professional name · service type · event · tone pills · star rating · highlights textarea), compose button, review preview box, edit/copy actions.'],
                ['id' => 'CLI-10', 'title' => 'Profile Settings',         'url' => '/client/profile',         'purpose' => 'Personal info, password, notifications.',
                    'elements' => 'Sidebar tab nav (Basic · Address · Company · Social · Notifications · Security · Account), avatar card with upload, form grid per tab, danger zone (account deletion).'],
                ['id' => 'CLI-11', 'title' => 'Membership Plans',         'url' => '/app/membership-plans',   'purpose' => 'Browse / upgrade subscription tier.',
                    'elements' => 'Three-tier comparison cards (Starter · Professional · Elite), feature checklist, contract length tabs (6 / 12 / 18 months), 15% off badge on 18-month.'],
                ['id' => 'CLI-12', 'title' => 'Membership History',       'url' => '/app/membership-plans/history', 'purpose' => 'Past subscription timeline.',
                    'elements' => 'Table list: plan · started · expired · status · invoice link · payment method.'],
                ['id' => 'CLI-13', 'title' => 'Payment History',          'url' => '/app/payments/history',   'purpose' => 'All transactions (memberships, posts, AI agreements).',
                    'elements' => 'Filter strip (type · date), transactions table with download invoice action.'],
                ['id' => 'CLI-14', 'title' => 'Payment Success',          'url' => '/app/payments/success',   'purpose' => 'Post-Stripe-checkout success page.',
                    'elements' => 'Big checkmark, transaction summary, receipt download, return-to-dashboard CTA.'],
                ['id' => 'CLI-15', 'title' => 'Payment Cancelled',        'url' => '/app/payments/cancel',    'purpose' => 'Stripe checkout cancelled.',
                    'elements' => 'X icon, "no charge made" message, retry CTA, back link.'],
                ['id' => 'CLI-16', 'title' => 'Agreement Detail',         'url' => '/app/agreements/{id}',    'purpose' => 'View AI-generated contract with e-signature.',
                    'elements' => 'Agreement title + meta, full content, signature blocks (both parties), download PDF, cancellation request button.'],
            ],
        ],

        // ─── 4. PROFESSIONAL DASHBOARD ─────────────────────────────
        [
            'key' => 'pro',
            'title' => 'Professional Dashboard',
            'desc' => 'Pages used by supplier / vendor / gig-pro to manage bookings, gigs and earnings.',
            'pages' => [
                ['id' => 'PRO-01', 'title' => 'Dashboard Home',           'url' => '/professional/dashboard', 'purpose' => 'Earnings overview, active proposals, recent gigs, messages, reviews.',
                    'elements' => 'Same navbar / sidebar shell as client (coral-themed icons), revenue stat cards, upcoming gigs strip, lead status counts (won/lost/pending), recent messages, ratings widget, AI Review Writer quick link.'],
                ['id' => 'PRO-02', 'title' => 'Gigs / Job Feed',          'url' => '/professional/gigs',      'purpose' => 'Available gigs (jobs) the pro can bid on.',
                    'elements' => 'Filter pills (category · budget · location · date · urgency), gig cards with client avatar + event details + budget + bids count + "Submit Proposal" button, sticky filters bar.'],
                ['id' => 'PRO-03', 'title' => 'Gig Detail',               'url' => '/professional/gigs/{id}', 'purpose' => 'Single gig page with proposal-submit form.',
                    'elements' => 'Gig header (client · event type · location · date · budget · guest count), full description, requirements list, proposal form (price · message · timeline), existing proposals count.'],
                ['id' => 'PRO-04', 'title' => 'My Proposals',             'url' => '/professional/proposals', 'purpose' => 'All proposals submitted by this pro with status.',
                    'elements' => 'Status tab strip (Pending · Accepted · Declined · Expired), proposal cards with gig info + submitted price + status badge + actions, "follow up" button.'],
                ['id' => 'PRO-05', 'title' => 'Messages — Inbox',         'url' => '/professional/messages',  'purpose' => 'Conversations with clients.',
                    'elements' => 'Same two-pane chat UI as client side.'],
                ['id' => 'PRO-06', 'title' => 'Message Thread',           'url' => '/professional/messages/{id}', 'purpose' => 'Single conversation.',
                    'elements' => 'Same as client side, with client info sidebar.'],
                ['id' => 'PRO-07', 'title' => 'Reviews',                  'url' => '/professional/reviews',   'purpose' => 'All received reviews with response option (Elite flag).',
                    'elements' => 'Average rating + breakdown chart, filter pills, review cards (client avatar · star rating · text · date · respond button · flag for admin button — Elite only).'],
                ['id' => 'PRO-08', 'title' => 'Earnings Overview',        'url' => '/professional/earnings',  'purpose' => 'Money dashboard — totals + chart + payouts.',
                    'elements' => 'Big revenue number, time-range picker, line chart of earnings, payout schedule, pending balance card, withdrawal CTA.'],
                ['id' => 'PRO-09', 'title' => 'Transactions',             'url' => '/professional/transactions', 'purpose' => 'Full ledger of bookings/payouts/fees.',
                    'elements' => 'Date-range + type filter, table (date · type · client · amount · platform fee · net · status), export CSV / PDF buttons.'],
                ['id' => 'PRO-10', 'title' => 'Profile Settings',         'url' => '/professional/profile',   'purpose' => 'Public profile editor — what clients see.',
                    'elements' => 'Cover banner upload + avatar (Freelancer.com style), tabs (Basic · Services · Pricing · Portfolio · Certifications · Verification · Notifications · Security · Account), service categories multi-select, hourly rate, video introduction (Pro/Elite tiers).'],
            ],
        ],

        // ─── 5. INFLUENCER DASHBOARD ───────────────────────────────
        [
            'key' => 'influencer',
            'title' => 'Influencer Dashboard',
            'desc' => 'Pages for influencer-program members tracking referrals + commissions.',
            'pages' => [
                ['id' => 'INF-01', 'title' => 'Dashboard Home',           'url' => '/influencer/dashboard',   'purpose' => 'Performance overview.',
                    'elements' => 'Top stats (total earned · this month · pending payout · click count), unique referral link with copy button, recent referrals strip, tier-progress widget, marketing assets download.'],
                ['id' => 'INF-02', 'title' => 'Referrals',                'url' => '/influencer/referrals',   'purpose' => 'Full list of referrals with status + commission.',
                    'elements' => 'Filter (status · date), referrals table (referred user · sign-up date · plan purchased · commission · status pill).'],
                ['id' => 'INF-03', 'title' => 'Payouts',                  'url' => '/influencer/payouts',     'purpose' => 'Payout history + bank/payment method settings.',
                    'elements' => 'Pending balance card, payout-method form, payout history table (date · amount · method · status).'],
            ],
        ],

        // ─── 6. ADMIN DASHBOARD ────────────────────────────────────
        [
            'key' => 'admin',
            'title' => 'Admin Dashboard',
            'desc' => 'Administrative pages for platform owners. Uses NobleUI Bootstrap template with custom branding.',
            'pages' => [
                ['id' => 'ADM-01', 'title' => 'Dashboard Home',           'url' => '/dashboard',              'purpose' => 'Platform-wide stats and recent activity.',
                    'elements' => 'NobleUI top bar + collapsible sidebar (role-coloured icons), KPI cards (users · revenue · gigs · bookings), revenue chart, recent users, latest bookings, system alerts.'],
                ['id' => 'ADM-02', 'title' => 'Events Mgmt',              'url' => '/app/admin/events',       'purpose' => 'View / moderate all events.',
                    'elements' => 'Search + filter, datatable (title · client · date · status · actions), bulk-action toolbar, status modals.'],
                ['id' => 'ADM-03', 'title' => 'Bookings Mgmt',            'url' => '/app/bookings',           'purpose' => 'All platform bookings.',
                    'elements' => 'Datatable with filters, status pills, refund / dispute actions.'],
                ['id' => 'ADM-04', 'title' => 'Categories',               'url' => '/app/admin/categories',   'purpose' => 'Manage event categories used across the platform.',
                    'elements' => 'Card list with category image · name · slug · active toggle · sort order · edit/delete, "Create Category" button.'],
                ['id' => 'ADM-05', 'title' => 'New Category',             'url' => '/app/admin/categories/create', 'purpose' => 'Create category form.',
                    'elements' => 'Form (name · slug · icon emoji · color · description · image upload · active toggle · sort order).'],
                ['id' => 'ADM-06', 'title' => 'Edit Category',            'url' => '/app/admin/categories/{id}/edit', 'purpose' => 'Edit existing category.',
                    'elements' => 'Same as Create with current values pre-filled.'],
                ['id' => 'ADM-07', 'title' => 'AI Agreements',            'url' => '/app/agreements',         'purpose' => 'All generated AI agreements.',
                    'elements' => 'Datatable (parties · status · created · cancellation · actions), filter strip.'],
                ['id' => 'ADM-08', 'title' => 'Agreement Log',            'url' => '/app/agreement-log',      'purpose' => 'Audit log of agreement actions.',
                    'elements' => 'Datatable (timestamp · agreement · actor · action · IP).'],
                ['id' => 'ADM-09', 'title' => 'Membership Plans Admin',   'url' => '/app/admin/membership-plans', 'purpose' => 'Create / edit Starter / Pro / Elite tiers.',
                    'elements' => 'Plan cards with edit · feature checklist editor · pricing matrix · activation toggle.'],
                ['id' => 'ADM-10', 'title' => 'Users',                    'url' => '/app/users',              'purpose' => 'All platform users.',
                    'elements' => 'Datatable (avatar · name · email · roles · status · last login · actions), bulk role assignment, ban / activate.'],
                ['id' => 'ADM-11', 'title' => 'Roles',                    'url' => '/app/roles',              'purpose' => 'Role management.',
                    'elements' => 'Role cards with name · users count · permissions count · edit, create-role modal.'],
                ['id' => 'ADM-12', 'title' => 'Permissions',              'url' => '/app/permissions',        'purpose' => 'Permission catalog.',
                    'elements' => 'Permission table (name · group · description · used-by-roles count).'],
                ['id' => 'ADM-13', 'title' => 'Deletion Requests',        'url' => '/app/admin/deletion-requests', 'purpose' => 'Approve / cancel account deletions.',
                    'elements' => 'Card list with user info · reason · requested date · grace-period countdown · approve / cancel buttons.'],
                ['id' => 'ADM-14', 'title' => 'Activity Logs',            'url' => '/app/admin/activity-logs', 'purpose' => 'Audit trail across the platform.',
                    'elements' => 'Datatable (timestamp · actor · action · model · IP · user-agent), date-range + actor filter.'],
                ['id' => 'ADM-15', 'title' => 'AI Chatbot Logs',          'url' => '/app/admin/chatbot-logs', 'purpose' => 'Inspect chatbot conversations.',
                    'elements' => 'Conversation list with user · last message · message count · timestamp, click to open thread view.'],
                ['id' => 'ADM-16', 'title' => 'Chatbot Log Thread',       'url' => '/app/admin/chatbot-logs/{id}', 'purpose' => 'Single chatbot conversation transcript.',
                    'elements' => 'User → AI bubbles, token usage, response time, flag inappropriate.'],
                ['id' => 'ADM-17', 'title' => 'Influencers Admin',        'url' => '/app/influencers',        'purpose' => 'All influencer-program members.',
                    'elements' => 'Datatable (avatar · name · referrals · earnings · tier · status), tier badges.'],
                ['id' => 'ADM-18', 'title' => 'Influencer Payouts',       'url' => '/app/influencers/payouts', 'purpose' => 'Approve / process payouts.',
                    'elements' => 'Pending queue cards, batch-approve, payout history.'],
                ['id' => 'ADM-19', 'title' => 'Professional Verifications', 'url' => '/app/admin/verifications', 'purpose' => 'Review pro verification docs.',
                    'elements' => 'Queue cards with pro info + 3 badge sections (trade license · liability · workers comp) · uploaded doc previews · approve / reject buttons.'],
                ['id' => 'ADM-20', 'title' => 'FAQ Management',           'url' => '/app/admin/faqs',         'purpose' => 'Add / edit / sort FAQ entries.',
                    'elements' => 'List with drag-handles for sort order, inline edit modal, category dropdown, active toggle.'],
                ['id' => 'ADM-21', 'title' => 'Policy Pages',             'url' => '/app/admin/policies',     'purpose' => 'Edit policy page content.',
                    'elements' => 'Card list (Privacy · AI Agreement · Payment · Cancellation), each with last-updated · edit button.'],
                ['id' => 'ADM-22', 'title' => 'Edit Policy',              'url' => '/app/admin/policies/{id}/edit', 'purpose' => 'Rich-text editor for policy content.',
                    'elements' => 'WYSIWYG editor (TinyMCE or similar), publish button, version note input.'],
                ['id' => 'ADM-23', 'title' => 'Blog Posts Admin',         'url' => '/app/admin/blog/posts',   'purpose' => 'Manage blog content.',
                    'elements' => 'Datatable (image · title · category · author · status · views · actions), filter + create-post button.'],
                ['id' => 'ADM-24', 'title' => 'New Blog Post',            'url' => '/app/admin/blog/posts/create', 'purpose' => 'Create blog post form.',
                    'elements' => 'Title, slug auto-fill, category dropdown, featured image upload, rich-text body editor, meta title / description, publish / draft toggle.'],
                ['id' => 'ADM-25', 'title' => 'Edit Blog Post',           'url' => '/app/admin/blog/posts/{id}/edit', 'purpose' => 'Edit existing post.',
                    'elements' => 'Same as Create with prefill.'],
                ['id' => 'ADM-26', 'title' => 'Blog Categories Admin',    'url' => '/app/admin/blog/categories', 'purpose' => 'Manage blog categories.',
                    'elements' => 'Inline-edit card list (name · slug · post count · active toggle), create-category form.'],
                ['id' => 'ADM-27', 'title' => 'Admin Profile',            'url' => '/app/admin/profile',      'purpose' => 'Admin user own profile editor.',
                    'elements' => 'Same tabbed profile layout as client/pro, admin-only badge.'],
                ['id' => 'ADM-28', 'title' => 'Settings — Payments',      'url' => '/app/admin/settings/payments', 'purpose' => 'Stripe / payment gateway config.',
                    'elements' => 'Form with Stripe publishable + secret keys, test/live mode toggle, webhook URL display.'],
                ['id' => 'ADM-29', 'title' => 'Settings — OpenAI',        'url' => '/app/admin/settings/openai', 'purpose' => 'OpenAI API config for AI tools.',
                    'elements' => 'API key input (masked), model dropdown, max tokens, temperature, "configured" badge, test connection button.'],
                ['id' => 'ADM-30', 'title' => 'Settings — reCAPTCHA',     'url' => '/app/admin/settings/recaptcha', 'purpose' => 'reCAPTCHA site / secret keys.',
                    'elements' => 'Version dropdown (v2 / v3), site key + secret key inputs, enable toggle.'],
                ['id' => 'ADM-31', 'title' => 'Settings — Chatbot',       'url' => '/app/admin/settings/chatbot', 'purpose' => 'AI chatbot widget toggle + system prompt.',
                    'elements' => 'Enable toggle, system prompt textarea, message limit input, allowed roles.'],
                ['id' => 'ADM-32', 'title' => 'Settings — Account Del',   'url' => '/app/admin/settings/account-deletion', 'purpose' => 'Account-deletion grace period config.',
                    'elements' => 'Grace period days input, soft-delete vs hard-delete radio, email template editor.'],
                ['id' => 'ADM-33', 'title' => 'Conversations (Admin)',    'url' => '/app/chat',               'purpose' => 'View all platform conversations.',
                    'elements' => 'Same two-pane chat UI, with admin overlay actions (warn · suspend · export).'],
            ],
        ],

        // ─── 7. ERROR PAGES ────────────────────────────────────────
        [
            'key' => 'error',
            'title' => 'Error Pages',
            'desc' => 'Custom branded pages shown when something goes wrong. Designer should mock up all variants.',
            'pages' => [
                ['id' => 'ERR-01', 'title' => '404 — Page Not Found',     'url' => '/(any-bad-url)',          'purpose' => 'Show when URL doesn\'t exist.',
                    'elements' => '🧭 emoji, eyebrow pill "404", title "Missing in action", tagline, 6 helpful nav links (Browse · Categories · How It Works · FAQ · Blog · About), main "Back to Home" CTA.'],
                ['id' => 'ERR-02', 'title' => '500 — Server Error',       'url' => '/(server-error-state)',   'purpose' => 'Generic server failure.',
                    'elements' => '🛠️ emoji, reassuring message, back-home + browse-pros CTAs.'],
                ['id' => 'ERR-03', 'title' => '403 — Forbidden',          'url' => '/(forbidden-route)',      'purpose' => 'User authenticated but lacks permission.',
                    'elements' => '🔒 emoji, "no access" message, auth-aware CTAs (Dashboard if logged in, Log in otherwise).'],
                ['id' => 'ERR-04', 'title' => '401 — Unauthorized',       'url' => '/(auth-required)',        'purpose' => 'Requires login to access.',
                    'elements' => '🔐 emoji, login + register CTAs.'],
                ['id' => 'ERR-05', 'title' => '419 — Session Expired',    'url' => '/(csrf-token-expired)',   'purpose' => 'CSRF token aged out (common Laravel state).',
                    'elements' => '⏱️ emoji, "session expired" message, refresh + log-in-again CTAs.'],
                ['id' => 'ERR-06', 'title' => '429 — Too Many Requests',  'url' => '/(rate-limited)',         'purpose' => 'Rate-limit triggered.',
                    'elements' => '🛑 emoji, "slow down" message, try-again + back-home CTAs.'],
                ['id' => 'ERR-07', 'title' => '503 — Maintenance',        'url' => '/(maintenance-mode)',     'purpose' => 'Site temporarily down for maintenance.',
                    'elements' => '🚧 emoji, "we\'re tuning up" message, retry button.'],
            ],
        ],
    ];
@endphp

<div class="doc-wrap">

    {{-- ─── COVER ─── --}}
    <div class="cover">
        <div class="cover-logo">GIG RESOURCE</div>
        <h1>Complete Page Design Specification</h1>
        <p class="subtitle">
            A comprehensive inventory of every screen the designer needs to mock up — organized section-wise across the website, authentication, client / professional / influencer / admin dashboards, and error states.
        </p>
        <div class="cover-meta">
            <div><strong>{{ collect($sections)->sum(fn ($s) => count($s['pages'])) }}</strong>Pages total</div>
            <div><strong>{{ count($sections) }}</strong>Sections</div>
            <div><strong>v1.0</strong>Last updated {{ now()->format('M j, Y') }}</div>
        </div>
    </div>

    {{-- ─── TOC ─── --}}
    <div class="toc">
        <h2>Table of Contents</h2>
        <ol>
            @foreach($sections as $i => $section)
                <li>
                    <span class="toc-title">{{ $section['title'] }}</span>
                    <span class="toc-count">{{ count($section['pages']) }} pages</span>
                </li>
            @endforeach
            <li>
                <span class="toc-title">Appendix — design tokens &amp; brand colours</span>
                <span class="toc-count">reference</span>
            </li>
        </ol>
    </div>

    {{-- ─── SECTIONS ─── --}}
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
                    <code class="page-url">{{ $BASE }}{{ $page['url'] }}</code>
                    <p class="page-purpose">{{ $page['purpose'] }}</p>
                    <div class="page-elements"><strong>Elements: </strong>{!! $page['elements'] !!}</div>
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- ─── APPENDIX ─── --}}
    <div class="section">
        <div class="section-head">
            <div class="section-num">{{ count($sections) + 1 }}</div>
            <div class="section-title">Appendix — Brand & Tokens</div>
        </div>
        <p class="section-desc">Reference values to keep designs aligned with the existing live styling.</p>

        <div class="page-card">
            <div class="page-title">Brand Colours</div>
            <p class="page-elements">
                <strong>Primary blue:</strong> <code>#3b82f6</code> &nbsp;·&nbsp;
                <strong>Accent purple:</strong> <code>#8b5cf6</code> &nbsp;·&nbsp;
                <strong>Warm coral:</strong> <code>#ff7a59</code> &nbsp;·&nbsp;
                <strong>Success green:</strong> <code>#22c55e</code> &nbsp;·&nbsp;
                <strong>Danger red:</strong> <code>#ef4444</code><br>
                <strong>Backgrounds:</strong> bg-dark <code>#0b0f1a</code> · bg-card <code>#151d35</code> · bg-section <code>#0f1629</code>
            </p>
        </div>
        <div class="page-card">
            <div class="page-title">Typography</div>
            <p class="page-elements"><strong>Body font:</strong> Inter (weights 400/600/700/800)<br>
                <strong>Hero h1:</strong> 3rem clamp(2rem, 5vw, 4rem), letter-spacing -0.02em, font-weight 900<br>
                <strong>Section h2:</strong> 2rem, font-weight 800<br>
                <strong>Card h3:</strong> 1.1rem, font-weight 700</p>
        </div>
        <div class="page-card">
            <div class="page-title">Audience Colour Coding (used on filter chips, badges, tier accents)</div>
            <p class="page-elements">
                <strong>Clients:</strong> blue → purple gradient<br>
                <strong>Professionals / Suppliers:</strong> coral → amber gradient<br>
                <strong>Influencers:</strong> purple → indigo gradient<br>
                <strong>All Users:</strong> green → teal gradient
            </p>
        </div>
        <div class="page-card">
            <div class="page-title">Membership Tier Visual Tokens</div>
            <p class="page-elements">
                <strong>Starter:</strong> green accent · "Digital Business Card" feel<br>
                <strong>Professional:</strong> blue/purple accent · "Active Bidder"<br>
                <strong>Elite:</strong> gold gradient · "Featured Partner" with crown-style badge
            </p>
        </div>
        <div class="page-card">
            <div class="page-title">Imagery Direction</div>
            <p class="page-elements">All banner / cover / category-tile imagery should be <strong>wedding / elegant-event</strong> focused (couples, ceremonies, receptions, florals, rings, bouquets, reception table setups). <strong>Avoid:</strong> club scenes, dance parties, theatre stages, concerts. Real candid moments preferred over staged stock.</p>
        </div>
    </div>

    <div class="doc-foot">
        GigResource Design Specification &middot; Generated {{ now()->format('F j, Y') }} &middot; Base URL: {{ $BASE }}
    </div>
</div>

</body>
</html>
