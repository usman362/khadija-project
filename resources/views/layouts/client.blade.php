<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Anti-FOUC: apply the saved theme BEFORE first paint so there's no light/dark flash. --}}
    <script>(function(){try{var t=localStorage.getItem('cl-theme')||'light';document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','light');}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    @include('partials._seo_meta', ['seoNoIndex' => $seoNoIndex ?? true])

    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root, [data-theme="dark"] {
            /* Role brand accent — CLIENT = orange. Tools use var(--brand*) so their
               accent follows the portal they're rendered in. */
            --brand: #f97316; --brand-strong: #ea580c; --brand-soft: rgba(249,115,22,0.10);
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.7);
            --bg-card-hover: rgba(31, 41, 55, 0.8);
            --bg-sidebar: #0d1321;
            --border-color: rgba(255, 255, 255, 0.06);
            --border-glow: rgba(99, 102, 241, 0.15);
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --accent-blue: #6366f1;
            --accent-blue-soft: rgba(99, 102, 241, 0.12);
            --accent-green: #10b981;
            --accent-green-soft: rgba(16, 185, 129, 0.12);
            --accent-yellow: #f59e0b;
            --accent-yellow-soft: rgba(245, 158, 11, 0.12);
            --accent-pink: #ec4899;
            --accent-pink-soft: rgba(236, 72, 153, 0.12);
            --accent-cyan: #06b6d4;
            --accent-cyan-soft: rgba(6, 182, 212, 0.12);
            --accent-orange: #f97316;
            --accent-orange-soft: rgba(249, 115, 22, 0.12);
            --accent-red: #ef4444;
            --accent-red-soft: rgba(239, 68, 68, 0.12);
            --accent-purple: #a855f7;
            --accent-purple-soft: rgba(168, 85, 247, 0.12);
            --sidebar-width: 236px;
            --sidebar-collapsed: 72px;
            --navbar-height: 64px;
            --radius: 12px;
            --radius-sm: 8px;
            --radius-lg: 16px;
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.2);
            --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="light"] {
            /* White page shell; cards are lifted by their subtle border + shadow. */
            --bg-primary: #ffffff;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --bg-sidebar: #ffffff;
            --border-color: rgba(0, 0, 0, 0.07);
            --border-glow: rgba(99, 102, 241, 0.2);
            --text-primary: #1e293b;
            --text-secondary: #334155;
            --text-muted: #475569;
            --accent-blue: #6366f1;
            --accent-blue-soft: rgba(99, 102, 241, 0.1);
            --accent-green: #10b981;
            --accent-green-soft: rgba(16, 185, 129, 0.1);
            --accent-yellow: #f59e0b;
            --accent-yellow-soft: rgba(245, 158, 11, 0.1);
            --accent-pink: #ec4899;
            --accent-pink-soft: rgba(236, 72, 153, 0.1);
            --accent-cyan: #06b6d4;
            --accent-cyan-soft: rgba(6, 182, 212, 0.1);
            --accent-orange: #f97316;
            --accent-orange-soft: rgba(249, 115, 22, 0.1);
            --accent-red: #ef4444;
            --accent-red-soft: rgba(239, 68, 68, 0.1);
            --accent-purple: #a855f7;
            --accent-purple-soft: rgba(168, 85, 247, 0.1);
        }

        [data-theme="light"] .cl-navbar {
            background: #ffffff;
            backdrop-filter: none;
        }
        [data-theme="light"] .cl-nav-link:hover {
            background: rgba(0, 0, 0, 0.04);
        }
        [data-theme="light"] .cl-user-card {
            background: rgba(0, 0, 0, 0.03);
        }
        [data-theme="light"] .cl-user-card:hover {
            background: rgba(0, 0, 0, 0.06);
        }
        [data-theme="light"] .cl-form-input,
        [data-theme="light"] .cl-form-select,
        [data-theme="light"] .cl-form-textarea {
            background: rgba(0, 0, 0, 0.02);
        }
        [data-theme="light"] .cl-form-select option {
            background: #fff;
            color: #1e293b;
        }
        [data-theme="light"] .cl-search-box input {
            background: rgba(0, 0, 0, 0.03);
        }
        [data-theme="light"] .cl-modal {
            background: #ffffff;
        }
        [data-theme="light"] .cl-modal-close {
            background: rgba(0, 0, 0, 0.05);
        }
        [data-theme="light"] .cl-modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        [data-theme="light"] .cl-btn-ghost {
            background: transparent;
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        [data-theme="light"] .cl-btn-ghost:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        [data-theme="light"] .cl-nav-btn {
            color: var(--text-secondary);
        }
        [data-theme="light"] .cl-nav-btn:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        [data-theme="light"] .cl-table tbody tr:hover td {
            background: rgba(0, 0, 0, 0.02);
        }
        [data-theme="light"] .cl-calendar-day {
            background: rgba(0, 0, 0, 0.02);
        }
        [data-theme="light"] .cl-calendar-day:hover {
            background: rgba(0, 0, 0, 0.04);
        }
        [data-theme="light"] .cl-empty-icon {
            background: rgba(0, 0, 0, 0.04);
        }
        [data-theme="light"] .cl-tabs {
            background: rgba(0, 0, 0, 0.03);
        }
        /* ─── Additional light-mode overrides (all remaining hardcoded rgba(255…) hover/bg) ─── */
        [data-theme="light"] .cl-sidebar {
            background: #ffffff;
            border-right-color: rgba(0, 0, 0, 0.08);
        }
        [data-theme="light"] .cl-sidebar-brand {
            border-bottom-color: rgba(0, 0, 0, 0.08);
        }
        [data-theme="light"] .cl-content {
            color: #1e293b;
        }
        [data-theme="light"] .cl-stat-card,
        [data-theme="light"] .cl-card {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.08);
            color: #1e293b;
            backdrop-filter: none;          /* solid white, no frosted blur */
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
        }
        [data-theme="light"] .cl-card:hover {
            box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
        }
        [data-theme="light"] .cl-stat-card:hover,
        [data-theme="light"] .cl-card:hover {
            background: #fafafa;
        }
        [data-theme="light"] .cl-stat-label,
        [data-theme="light"] .cl-card-subtitle {
            color: #475569;
        }
        [data-theme="light"] .cl-stat-value,
        [data-theme="light"] .cl-card-title,
        [data-theme="light"] .cl-page-title {
            color: #1e293b;
        }
        [data-theme="light"] .cl-booking-card,
        [data-theme="light"] .cl-event-card,
        [data-theme="light"] .cl-list-card {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.08);
        }
        [data-theme="light"] .cl-booking-card:hover,
        [data-theme="light"] .cl-event-card:hover,
        [data-theme="light"] .cl-list-card:hover {
            background: #fafafa;
            border-color: rgba(99, 102, 241, 0.2);
        }
        [data-theme="light"] .cl-section-title,
        [data-theme="light"] .cl-heading,
        [data-theme="light"] h1, [data-theme="light"] h2,
        [data-theme="light"] h3, [data-theme="light"] h4 {
            color: #1e293b;
        }
        [data-theme="light"] .cl-badge {
            color: #1e293b;
        }
        [data-theme="light"] .cl-table th {
            color: #475569;
            background: rgba(0, 0, 0, 0.02);
        }
        [data-theme="light"] .cl-table td {
            color: #1e293b;
            border-color: rgba(0, 0, 0, 0.06);
        }
        [data-theme="light"] .cl-detail-label {
            color: #64748b;
        }
        [data-theme="light"] .cl-detail-value {
            color: #1e293b;
        }
        [data-theme="light"] .cl-sidebar-footer {
            border-top-color: rgba(0, 0, 0, 0.08);
        }
        [data-theme="light"] .cl-dropdown,
        [data-theme="light"] .cl-modal-overlay .cl-modal {
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
        }
        [data-theme="light"] .cl-nav-link {
            color: #475569;
        }
        [data-theme="light"] .cl-nav-link:hover {
            background: rgba(0, 0, 0, 0.04);
            color: #1e293b;
        }
        [data-theme="light"] .cl-nav-link.active {
            color: #f97316;
            background: rgba(249, 115, 22, 0.10);
        }
        [data-theme="light"] .cl-user-card {
            background: rgba(0, 0, 0, 0.03);
        }
        [data-theme="light"] .cl-user-card:hover {
            background: rgba(0, 0, 0, 0.06);
        }
        [data-theme="light"] .cl-empty-title {
            color: #1e293b;
        }
        [data-theme="light"] .cl-empty-text {
            color: #475569;
        }
        [data-theme="light"] .cl-pagination a:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #1e293b;
        }
        [data-theme="light"] .cl-calendar-day {
            background: rgba(0, 0, 0, 0.02);
        }
        [data-theme="light"] .cl-calendar-day:hover {
            background: rgba(0, 0, 0, 0.04);
        }
        [data-theme="light"] p,
        [data-theme="light"] span,
        [data-theme="light"] td,
        [data-theme="light"] li {
            color: inherit;
        }
        [data-theme="light"] .cl-content p {
            color: #334155;
        }

        /* ═══════════════════════ THEME TOGGLE ═══════════════════════ */
        .cl-theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        .cl-theme-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        [data-theme="light"] .cl-theme-toggle:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        .cl-theme-toggle .icon-sun,
        .cl-theme-toggle .icon-moon { position: absolute; transition: opacity 0.25s, transform 0.25s; }
        .cl-theme-toggle .icon-sun { opacity: 0; transform: rotate(-90deg) scale(0.5); }
        .cl-theme-toggle .icon-moon { opacity: 1; transform: rotate(0) scale(1); }
        [data-theme="light"] .cl-theme-toggle .icon-sun { opacity: 1; transform: rotate(0) scale(1); }
        [data-theme="light"] .cl-theme-toggle .icon-moon { opacity: 0; transform: rotate(90deg) scale(0.5); }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══════════════════════ SIDEBAR ═══════════════════════ */
        .cl-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: var(--transition);
            overflow-x: hidden;          /* never scroll horizontally */
        }

        .cl-sidebar-brand {
            height: var(--navbar-height);
            display: flex;
            align-items: center;
            padding: 0 24px;
            border-bottom: 1px solid var(--border-color);
            gap: 12px;
            text-decoration: none;
        }

        .cl-sidebar-brand .brand-logo-img {
            height: 34px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }
        .cl-sidebar-brand .brand-logo-light { display: block; }
        .cl-sidebar-brand .brand-logo-dark { display: none; }
        [data-theme="light"] .cl-sidebar-brand .brand-logo-light { display: none; }
        [data-theme="light"] .cl-sidebar-brand .brand-logo-dark { display: block; }

        .cl-sidebar-brand .brand-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .cl-sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 16px 12px;
        }
        .cl-sidebar-nav ul { width: 100%; }

        .cl-sidebar-nav::-webkit-scrollbar { width: 4px; }
        .cl-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .cl-sidebar-nav::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }

        .cl-nav-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 12px 10px 5px;
        }

        .cl-nav-item {
            list-style: none;
        }

        .cl-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            margin-bottom: 1px;
            white-space: nowrap;          /* keep every item on one line */
        }

        .cl-nav-link:hover {
            background: rgba(0, 0, 0, 0.035);
            color: var(--text-primary);
        }

        /* Active item — orange (matches reference, was purple). */
        .cl-nav-link.active {
            background: var(--accent-orange-soft, rgba(249,115,22,0.10));
            color: #f97316;
        }
        .cl-nav-link.active .cl-nav-icon { color: #f97316; opacity: 1; }

        .cl-nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: #f97316;
            border-radius: 0 4px 4px 0;
        }

        .cl-nav-icon {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            opacity: 0.85;
            transition: opacity 0.2s, transform 0.2s;
        }

        .cl-nav-link:hover .cl-nav-icon { opacity: 1; transform: scale(1.1); }
        .cl-nav-link.active .cl-nav-icon { opacity: 1; }

        /* Nav badge — "New" pill + unread count bubble next to label */
        .cl-nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            /* Soft peach pill with orange text (matches reference, was solid). */
            background: rgba(249, 115, 22, 0.14);
            color: #ea580c;
            letter-spacing: 0.2px;
            line-height: 1;
            flex-shrink: 0;
        }
        .cl-nav-badge.cl-nav-badge-count {
            background: var(--accent-red, #ef4444);
            color: #fff;
            padding: 0;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .cl-nav-link[data-coming-soon] {
            position: relative;
        }
        .cl-nav-link[data-coming-soon]::after {
            content: 'soon';
            margin-left: auto;
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            background: var(--border-color);
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        /* If a [data-coming-soon] also carries its own badge, hide the auto "soon" tag */
        .cl-nav-link[data-coming-soon]:has(.cl-nav-badge)::after { display: none; }

        /* Colorful nav icons — refreshed for new nav order. Dashboard is the
           1st child (no group header). Group labels are <li class="cl-nav-label">
           and count toward nth-child indexing. */
        .cl-nav-item .cl-nav-icon { color: var(--text-muted); }
        .cl-nav-item:hover .cl-nav-icon,
        .cl-nav-item:has(.cl-nav-link.active) .cl-nav-icon { color: var(--accent-orange, #f97316); }
        /* AI-tools icons keep their brand colour even when not hovered. */
        .cl-nav-icon.ic-orange { color: #f97316 !important; }
        .cl-nav-icon.ic-purple { color: #8b5cf6 !important; }

        /* Sidebar "Upcoming Event" promo card */
        .cl-upcoming {
            margin: 12px 16px 0;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(249,115,22,0.10), rgba(249,115,22,0.04));
            border: 1px solid var(--border-color);
        }
        .cl-upcoming-label {
            display: flex; align-items: center; gap: 6px;
            font-size: 10.5px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.4px;
            margin-bottom: 8px;
        }
        .cl-upcoming-label svg { color: #f97316; }
        .cl-upcoming-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
        .cl-upcoming-date { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .cl-upcoming-budget-label { font-size: 10px; color: var(--text-muted); margin-top: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
        .cl-upcoming-budget { font-size: 15px; font-weight: 800; color: var(--text-primary); }
        .cl-upcoming-btn {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-top: 12px; padding: 8px;
            background: rgba(249,115,22,0.12);
            color: #f97316;
            border-radius: 8px;
            font-size: 11.5px; font-weight: 700;
            text-decoration: none;
        }
        .cl-upcoming-btn:hover { background: rgba(249,115,22,0.20); }

        .cl-sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }

        .cl-user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            transition: var(--transition);
        }

        .cl-user-card:hover { background: rgba(255, 255, 255, 0.06); }

        .cl-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            color: #fff;
            flex-shrink: 0;
        }

        .cl-user-info { flex: 1; min-width: 0; }
        .cl-user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cl-user-role { font-size: 11px; color: var(--text-muted); }

        /* ═══════════════════════ MAIN CONTENT ═══════════════════════ */
        .cl-main {
            margin-left: 0;
            min-height: 100vh;
            transition: var(--transition);
        }

        /* ══════════════ TOP-NAV HEADER (replaces sidebar) ══════════════ */
        .cl-topnav { position: sticky; top: 0; z-index: 200; display: flex; align-items: center; gap: 22px; height: 62px; padding: 0 26px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); }
        .cl-tn-brand { flex-shrink: 0; display: flex; align-items: center; }
        .cl-tn-brand img { height: 30px; width: auto; display: block; }
        .cl-tn-brand .brand-logo-dark { display: none; }
        [data-theme="light"] .cl-tn-brand .brand-logo-light { display: none; }
        [data-theme="light"] .cl-tn-brand .brand-logo-dark { display: block; }

        .cl-tn-nav { display: flex; align-items: center; gap: 2px; flex: 1; min-width: 0; }
        .cl-tn-item { position: relative; }
        .cl-tn-link { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 9px; font-size: 13.5px; font-weight: 650; color: var(--text-secondary); background: none; border: none; cursor: pointer; font-family: inherit; white-space: nowrap; text-decoration: none; }
        .cl-tn-link:hover { background: var(--bg-hover, rgba(148,163,184,.12)); color: var(--text-primary); }
        .cl-tn-link.active { color: var(--accent-orange); background: var(--accent-orange-soft); }
        .cl-tn-link .chev { width: 13px; height: 13px; transition: transform .18s; }
        .cl-tn-item.open .cl-tn-link { color: var(--accent-orange); background: var(--accent-orange-soft); }
        .cl-tn-item.open .cl-tn-link .chev { transform: rotate(180deg); }

        .cl-tn-menu { position: absolute; top: calc(100% + 8px); left: 0; min-width: 240px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,.18); padding: 8px; opacity: 0; visibility: hidden; transform: translateY(-6px); transition: opacity .16s, transform .16s, visibility .16s; z-index: 210; }
        .cl-tn-item.open .cl-tn-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .cl-tn-menu.mega { display: grid; grid-template-columns: repeat(2, minmax(210px, 1fr)); gap: 2px 10px; min-width: 460px; }
        .cl-tn-menu.mega.wide { grid-template-columns: repeat(3, minmax(190px, 1fr)); min-width: 620px; }
        .cl-tn-mlink { display: flex; align-items: flex-start; gap: 10px; padding: 9px 11px; border-radius: 10px; text-decoration: none; color: var(--text-primary); }
        .cl-tn-mlink:hover { background: var(--accent-orange-soft); }
        .cl-tn-mlink.active { background: var(--accent-orange-soft); }
        .cl-tn-mlink svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; color: var(--accent-orange); }
        .cl-tn-mlink .t { font-size: 13px; font-weight: 700; line-height: 1.25; }
        .cl-tn-mlink .d { font-size: 11px; color: var(--text-muted); line-height: 1.3; margin-top: 1px; }
        .cl-tn-mhead { grid-column: 1 / -1; font-size: 10.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--text-muted); padding: 8px 11px 4px; }

        .cl-tn-right { flex-shrink: 0; display: flex; align-items: center; gap: 10px; }
        .cl-tn-mobile { display: none; background: none; border: none; color: var(--text-primary); cursor: pointer; padding: 6px; }

        /* Avatar dropdown on the right */
        .cl-tn-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
        .cl-tn-item .cl-tn-menu.right { left: auto; right: 0; }

        @media (max-width: 1100px) {
            .cl-tn-nav { position: fixed; top: 62px; left: 0; right: 0; bottom: 0; flex-direction: column; align-items: stretch; gap: 0; background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 12px; overflow-y: auto; transform: translateX(-100%); transition: transform .22s; z-index: 190; }
            .cl-tn-nav.open { transform: translateX(0); }
            .cl-tn-menu, .cl-tn-menu.mega, .cl-tn-menu.mega.wide { position: static; opacity: 1; visibility: visible; transform: none; box-shadow: none; border: none; min-width: 0; grid-template-columns: 1fr; padding: 2px 0 8px 14px; display: none; }
            .cl-tn-item.open .cl-tn-menu { display: block; }
            .cl-tn-item.open .cl-tn-menu.mega { display: grid; }
            .cl-tn-link { width: 100%; justify-content: space-between; }
            .cl-tn-mobile { display: inline-flex; }
        }
        @media (max-width: 700px) { .cl-tn-hide-sm { display: none; } .cl-topnav { gap: 12px; padding: 0 16px; } }

        .cl-navbar {
            height: var(--navbar-height);
            background: rgba(10, 14, 26, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 16px;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .cl-navbar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }

        .cl-mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
        }

        .cl-page-title { font-size: 18px; font-weight: 600; }

        /* Greeting block — replaces the bare page title with a two-line
           welcome message + subtitle. Pages override via yield directives.
           Title + sub truncate with ellipsis so a long name can't push the
           rest of the navbar around. */
        .cl-greeting { line-height: 1.25; min-width: 0; flex: 1; }
        .cl-greeting-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cl-greeting-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin: 2px 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ══════ Client topbar — orange welcome banner ══════ */
        .cl-topbar { display: flex; align-items: center; gap: 16px; padding: 12px 26px 2px; position: sticky; top: 0; z-index: 100; background: var(--bg-primary); }
        .cl-banner { flex: 1; min-width: 0; display: flex; align-items: center; gap: 16px; background: linear-gradient(120deg, #fb923c 0%, #f97316 50%, #ea580c 100%); border-radius: 14px; padding: 12px 18px; box-shadow: 0 6px 20px rgba(249,115,22,0.25); }
        .cl-banner-avatar { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; background: rgba(255,255,255,0.25); border: 2px solid rgba(255,255,255,0.45); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; }
        .cl-banner-text { flex-shrink: 0; }
        .cl-banner-text h1 { font-size: 17px; font-weight: 800; color: #fff; margin: 0; line-height: 1.2; }
        .cl-banner-text p { font-size: 11.5px; color: rgba(255,255,255,0.85); margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 340px; }
        .cl-banner-search { flex: 1; min-width: 120px; position: relative; margin-left: 6px; }
        .cl-banner-search > svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none; z-index: 1; }
        .cl-banner-search form { margin: 0; }
        .cl-banner-search input { width: 100%; height: 42px; border-radius: 10px; border: none; padding: 0 44px 0 40px; background: #fff; font-size: 13px; color: #1e293b; outline: none; }
        .cl-banner-search kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--text-muted); background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 5px; padding: 2px 6px; }
        .cl-topbar-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        @media (max-width: 1100px) { .cl-banner-text { display: none; } }
        @media (max-width: 768px) { .cl-topbar { flex-wrap: wrap; } .cl-topbar-right { width: 100%; justify-content: flex-end; } }

        /* Centre search bar — fixed width in the grid centre column. */
        .cl-navbar-center {
            display: flex;
            justify-content: center;
            width: clamp(260px, 36vw, 460px);
        }
        .cl-search {
            position: relative;
            width: 100%;
        }
        .cl-search input {
            width: 100%;
            height: 40px;
            /* Right padding leaves room for the ⌘K hint only (mic removed). */
            padding: 0 52px 0 40px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s, background 0.15s;
        }
        .cl-search input::placeholder { color: var(--text-muted); }
        .cl-search input:focus {
            border-color: var(--accent-orange, #f97316);
            background: var(--bg-card-hover);
        }
        .cl-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--text-muted);
            pointer-events: none;
        }
        .cl-search-kbd {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--border-color);
            padding: 3px 7px;
            border-radius: 5px;
            font-family: inherit;
            pointer-events: none;
        }

        /* Voice-search mic — the shared _voice_search partial inserts a
           button.vs-mic-btn next to the input. Inside the navbar search it
           must sit ABSOLUTELY inside .cl-search (between the input and the
           ⌘K hint) so the flex/grid flow doesn't push it onto a new row. */
        .cl-search button.vs-mic-btn {
            position: absolute !important;
            right: 44px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            min-height: 28px !important;
        }
        .cl-search button.vs-mic-btn > svg {
            width: 15px !important;
            height: 15px !important;
        }
        /* Pulse animation tweaked so it stays centred over the input row */
        .cl-search button.vs-mic-btn.is-listening {
            animation: vsMicPulseNavbar 1.2s ease-in-out infinite !important;
        }
        @keyframes vsMicPulseNavbar {
            0%, 100% { transform: translateY(-50%) scale(1);   box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
            50%      { transform: translateY(-50%) scale(1.08); box-shadow: 0 0 0 6px rgba(239,68,68,0); }
        }

        .cl-navbar-right { display: flex; align-items: center; gap: 8px; justify-content: flex-end; min-width: 0; }

        /* Notification count bubble on the bell button */
        .cl-nav-btn-count {
            position: absolute;
            top: 4px;
            right: 4px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: var(--accent-red, #ef4444);
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--bg-primary);
            line-height: 1;
        }

        /* Topbar profile avatar (far right) */
        .cl-navbar-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            flex-shrink: 0;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px;
            cursor: pointer;
            border: 2px solid var(--border-color);
            text-decoration: none;
        }
        .cl-navbar-avatar:hover { opacity: 0.92; }

        .cl-nav-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: none;
            background: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            text-decoration: none;
        }

        .cl-nav-btn:hover { background: rgba(255, 255, 255, 0.05); color: var(--text-primary); }

        .cl-nav-btn .badge-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--accent-pink);
            border-radius: 50%;
            border: 2px solid var(--bg-primary);
        }

        .cl-content {
            padding: 24px 26px 28px;
            max-width: 1760px;
            margin: 0 auto;
        }

        /* ═══════════════════════ CARDS ═══════════════════════ */
        .cl-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            transition: var(--transition);
        }

        .cl-card:hover { border-color: var(--border-glow); box-shadow: var(--shadow-glow); }

        .cl-stat-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .cl-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cl-stat-icon.blue { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .cl-stat-icon.green { background: var(--accent-green-soft); color: var(--accent-green); }
        .cl-stat-icon.yellow { background: var(--accent-yellow-soft); color: var(--accent-yellow); }
        .cl-stat-icon.pink { background: var(--accent-pink-soft); color: var(--accent-pink); }
        .cl-stat-icon.cyan { background: var(--accent-cyan-soft); color: var(--accent-cyan); }
        .cl-stat-icon.orange { background: var(--accent-orange-soft); color: var(--accent-orange); }
        .cl-stat-icon.red { background: var(--accent-red-soft); color: var(--accent-red); }
        .cl-stat-icon.purple { background: var(--accent-purple-soft); color: var(--accent-purple); }

        .cl-stat-label { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
        .cl-stat-value { font-size: 24px; font-weight: 700; line-height: 1.2; }
        .cl-stat-change { font-size: 12px; margin-top: 4px; }
        .cl-stat-change.up { color: var(--accent-green); }
        .cl-stat-change.down { color: var(--accent-red); }

        /* ═══════════════════════ BUTTONS ═══════════════════════ */
        .cl-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            line-height: 1.4;
        }

        .cl-btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        .cl-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4); color: #fff; }

        .cl-btn-ghost {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        .cl-btn-ghost:hover { background: rgba(255, 255, 255, 0.05); color: var(--text-primary); }

        .cl-btn-sm { padding: 6px 14px; font-size: 13px; }

        /* ═══════════════════════ BADGES ═══════════════════════ */
        .cl-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .cl-badge-pending { background: var(--accent-yellow-soft); color: var(--accent-yellow); }
        .cl-badge-confirmed { background: var(--accent-green-soft); color: var(--accent-green); }
        .cl-badge-completed { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .cl-badge-cancelled { background: var(--accent-red-soft); color: var(--accent-red); }
        .cl-badge-published { background: var(--accent-purple-soft); color: var(--accent-purple); }
        .cl-badge-in_progress { background: var(--accent-cyan-soft); color: var(--accent-cyan); }

        /* ═══════════════════════ TABLE ═══════════════════════ */
        .cl-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .cl-table thead th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .cl-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--text-secondary);
        }
        .cl-table tbody tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .cl-table tbody tr:last-child td { border-bottom: none; }

        /* ═══════════════════════ TABS ═══════════════════════ */
        .cl-tabs {
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-sm);
            padding: 4px;
            margin-bottom: 24px;
        }

        .cl-tab {
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            background: none;
        }

        .cl-tab:hover { color: var(--text-secondary); }
        .cl-tab.active { background: var(--accent-orange-soft); color: var(--accent-orange); }

        /* ═══════════════════════ GRID ═══════════════════════ */
        .cl-grid { display: grid; gap: 20px; }
        .cl-grid-2 { grid-template-columns: repeat(2, 1fr); }
        .cl-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .cl-grid-4 { grid-template-columns: repeat(4, 1fr); }

        /* ═══════════════════════ FORMS ═══════════════════════ */
        .cl-form-group { margin-bottom: 20px; }
        .cl-form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .cl-form-input, .cl-form-select, .cl-form-textarea {
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: var(--transition);
        }

        .cl-form-input:focus, .cl-form-select:focus, .cl-form-textarea:focus {
            outline: none;
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .cl-form-input::placeholder, .cl-form-textarea::placeholder { color: var(--text-muted); }
        .cl-form-textarea { resize: vertical; min-height: 100px; }
        .cl-form-select option { background: var(--bg-secondary); color: var(--text-primary); }

        /* ═══════════════════════ ALERT ═══════════════════════ */
        .cl-alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cl-alert-success { background: var(--accent-green-soft); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.2); }
        .cl-alert-error { background: var(--accent-red-soft); color: var(--accent-red); border: 1px solid rgba(239, 68, 68, 0.2); }

        /* ═══════════════════════ MODAL ═══════════════════════ */
        .cl-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .cl-modal-overlay.show { display: flex; }

        .cl-modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.4);
        }

        .cl-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .cl-modal-title { font-size: 18px; font-weight: 600; }

        .cl-modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .cl-modal-close:hover { background: rgba(255, 255, 255, 0.1); color: var(--text-primary); }
        .cl-modal-body { padding: 24px; }
        .cl-modal-footer { padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px; }

        /* ═══════════════════════ EMPTY STATE ═══════════════════════ */
        .cl-empty {
            text-align: center;
            padding: 48px 24px;
        }

        .cl-empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--text-muted);
        }

        .cl-empty-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); }
        .cl-empty-text { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; }

        /* ═══════════════════════ SEARCH ═══════════════════════ */
        .cl-search-box {
            position: relative;
        }

        .cl-search-box input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
        }

        .cl-search-box input:focus { outline: none; border-color: var(--accent-orange); }
        .cl-search-box input::placeholder { color: var(--text-muted); }

        .cl-search-box .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 18px;
            height: 18px;
        }

        /* ═══════════════════════ RESPONSIVE ═══════════════════════ */
        @media (max-width: 1024px) {
            .cl-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .cl-grid-3 { grid-template-columns: repeat(2, 1fr); }
            .cl-navbar-center { max-width: 320px; }
            .cl-search-kbd { display: none; }
        }

        @media (max-width: 768px) {
            .cl-sidebar { transform: translateX(-100%); }
            .cl-sidebar.open { transform: translateX(0); }
            .cl-main { margin-left: 0; }
            .cl-mobile-toggle { display: flex; }
            .cl-content { padding: 20px 16px; }
            .cl-grid-4, .cl-grid-3, .cl-grid-2 { grid-template-columns: 1fr; }
            .cl-navbar { padding: 0 16px; gap: 8px; }
            .cl-navbar-center { display: none; }
            .cl-greeting-sub { display: none; }
            .cl-greeting-title { font-size: 16px; }
        }

        /* ═══════════════════════ CALENDAR ═══════════════════════ */
        .cl-calendar { border-collapse: separate; border-spacing: 4px; width: 100%; }
        .cl-calendar th { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; text-align: center; padding: 8px; }
        .cl-calendar td {
            text-align: center;
            padding: 0;
            vertical-align: top;
        }

        .cl-calendar-day {
            height: 80px;
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            padding: 6px;
            cursor: pointer;
            transition: var(--transition);
        }

        .cl-calendar-day:hover { border-color: var(--border-glow); background: rgba(255, 255, 255, 0.04); }
        .cl-calendar-day .day-num { font-size: 13px; font-weight: 500; color: var(--text-secondary); }
        .cl-calendar-day.today { border-color: var(--accent-orange); background: var(--accent-orange-soft); }
        .cl-calendar-day.today .day-num { color: var(--accent-orange); font-weight: 700; }
        .cl-calendar-day.empty { background: transparent; cursor: default; }
        .cl-calendar-day.empty:hover { border-color: transparent; }

        .cl-calendar-event {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: var(--accent-orange-soft);
            color: var(--accent-orange);
        }

        /* ═══════════════════════ PAGINATION ═══════════════════════ */
        .cl-pagination { display: flex; gap: 4px; margin-top: 20px; justify-content: center; }
        .cl-pagination a, .cl-pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 8px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .cl-pagination a:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }
        .cl-pagination .active span { background: var(--accent-orange); color: #fff; border-color: var(--accent-orange); }
        .cl-pagination .disabled span { opacity: 0.3; cursor: not-allowed; }

        /* ═══════════════════════ LOGOUT BUTTON ═══════════════════════ */
        .cl-logout-form { margin: 8px 0 0; }
        .cl-logout-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 12px; background: rgba(239,68,68,0.08); color: #ef4444; border: 1px solid rgba(239,68,68,0.22); border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: background .15s; }
        .cl-logout-btn:hover { background: rgba(239,68,68,0.16); }
    </style>
    @include('partials._a11y')
    @stack('styles')
</head>

<body>
    {{-- Sidebar --}}
    <header class="cl-topnav">
        <a href="{{ route('client.dashboard') }}" class="cl-tn-brand">
            <img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="GigResource" class="brand-logo-light">
            <img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource" class="brand-logo-dark">
        </a>

        <button class="cl-tn-mobile" type="button" onclick="document.getElementById('clTopNav').classList.toggle('open')" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <nav class="cl-tn-nav" id="clTopNav">
            <div class="cl-tn-item">
                <a href="{{ route('client.dashboard') }}" class="cl-tn-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">Dashboard</a>
            </div>

            {{-- Post & Request --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-link {{ request()->routeIs('client.post-event.*','client.multi-service.*','client.esr.*','client.direct-offers.*') ? 'active' : '' }}">Post &amp; Request <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="cl-tn-menu mega">
                    <a class="cl-tn-mlink {{ request()->routeIs('client.post-event.*') ? 'active' : '' }}" href="{{ route('client.post-event.choose') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg><span class="t">Post an Event</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.multi-service.*') ? 'active' : '' }}" href="{{ route('client.multi-service.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg><span class="t">Multi-Service Request</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.esr.*') ? 'active' : '' }}" href="{{ route('client.esr.create') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span class="t">Emergency (ESR)</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.direct-offers.*') ? 'active' : '' }}" href="{{ route('client.direct-offers.create') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg><span class="t">Direct Offer</span></a>
                </div>
            </div>

            {{-- My Work --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-link {{ request()->routeIs('client.events.*','client.bookings.*','client.proposals.*','client.reviews.*') ? 'active' : '' }}">My Work <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="cl-tn-menu mega">
                    <a class="cl-tn-mlink {{ request()->routeIs('client.events.*') ? 'active' : '' }}" href="{{ route('client.events.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg><span class="t">My Events</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.bookings.*') ? 'active' : '' }}" href="{{ route('client.bookings.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span class="t">Bookings</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.proposals.*') ? 'active' : '' }}" href="{{ route('client.proposals.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span class="t">Proposals</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.reviews.*') ? 'active' : '' }}" href="{{ route('client.reviews.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="t">Reviews</span></a>
                </div>
            </div>

            {{-- Browse --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-link {{ request()->routeIs('public.packages','client.search.*','events-categories','client.virtual-hub.*') ? 'active' : '' }}">Browse <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="cl-tn-menu mega">
                    <a class="cl-tn-mlink {{ request()->routeIs('public.packages') ? 'active' : '' }}" href="{{ route('public.packages') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg><span class="t">Browse Packages</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.search.*') ? 'active' : '' }}" href="{{ route('client.search.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><span class="t">Search Professionals</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('events-categories') ? 'active' : '' }}" href="{{ route('events-categories') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg><span class="t">Browse Categories</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.virtual-hub.*') ? 'active' : '' }}" href="{{ route('client.virtual-hub.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg><span class="t">Virtual &amp; Hybrid Hub</span></a>
                </div>
            </div>

            {{-- AI Tools --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-link {{ request()->routeIs('ai-tools.*') ? 'active' : '' }}">AI Tools <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="cl-tn-menu mega wide">
                    <div class="cl-tn-mhead">GigResource IQ</div>
                    <a class="cl-tn-mlink {{ request()->routeIs('ai-tools.index') ? 'active' : '' }}" href="{{ route('ai-tools.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg><span class="t">AI Toolkit (all)</span></a>
                    @foreach(\App\Domain\AiFeatures\AiToolCatalog::forAudience('client') as $t)
                        @if(($t['status'] ?? '') === 'live' && !empty($t['route']) && \Illuminate\Support\Facades\Route::has($t['route']))
                            <a class="cl-tn-mlink {{ request()->routeIs($t['route'].'*') ? 'active' : '' }}" href="{{ route($t['route']) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7v1a2 2 0 0 1-2 2h-1v1a1 1 0 0 1-2 0v-1H8v1a1 1 0 0 1-2 0v-1H5a2 2 0 0 1-2-2v-1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/></svg><span class="t">{{ $t['name'] }}</span></a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Finance --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-link {{ request()->routeIs('client.earnings.*','client.payments.*') ? 'active' : '' }}">Finance <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button>
                <div class="cl-tn-menu">
                    <a class="cl-tn-mlink {{ request()->routeIs('client.earnings.*') ? 'active' : '' }}" href="{{ route('client.earnings.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg><span class="t">Earnings</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.payments.*') ? 'active' : '' }}" href="{{ route('client.payments.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg><span class="t">Payments</span></a>
                </div>
            </div>
        </nav>

        <div class="cl-tn-right">
            <span class="cl-tn-hide-sm">@include('partials._role_switcher')</span>

            <button class="cl-theme-toggle" id="theme-toggle" title="Toggle light / dark theme" aria-label="Toggle theme">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>

            <button class="cl-nav-btn" title="Notifications">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @php($notifCount = auth()->check() ? auth()->user()->unreadNotifications->count() : 0)
                <span class="cl-nav-btn-count">{{ $notifCount > 9 ? '9+' : ($notifCount > 0 ? $notifCount : 2) }}</span>
            </button>

            {{-- Avatar dropdown --}}
            <div class="cl-tn-item" data-dd>
                <button type="button" class="cl-tn-avatar" title="{{ auth()->user()?->name }}">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</button>
                <div class="cl-tn-menu right">
                    <a class="cl-tn-mlink {{ request()->routeIs('client.profile.*') ? 'active' : '' }}" href="{{ route('client.profile.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg><span class="t">Profile &amp; Settings</span></a>
                    <a class="cl-tn-mlink {{ request()->routeIs('client.notifications.*') ? 'active' : '' }}" href="{{ route('client.notifications.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="t">Notification Preferences</span></a>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;">@csrf
                        <button type="submit" class="cl-tn-mlink" style="width:100%;border:none;background:none;cursor:pointer;font-family:inherit;color:#dc2626;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span class="t">Log Out</span></button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="cl-main">
        <div class="cl-topbar">
            {{-- Orange welcome banner: avatar + greeting + search --}}
            <div class="cl-banner">
                <div class="cl-banner-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'C', 0, 1)) }}</div>
                <div class="cl-banner-text">
                    <h1>@yield('page-title', 'Welcome back, ' . (auth()->user()?->name ?? 'there') . '! 👋')</h1>
                    <p>@yield('page-subtitle', "Let's create amazing events together")</p>
                </div>
                <div class="cl-banner-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <form action="{{ route('public.browse') }}" method="GET">
                        <input type="text" name="q" placeholder="Search pros, services, or categories...">
                    </form>
                    <kbd>⌘ K</kbd>
                </div>
            </div>
        </div>

        <div class="cl-content">
            @if(session('status'))
                <div class="cl-alert cl-alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="cl-alert cl-alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @include('partials._breadcrumb')
            @include('partials._ai_tool_identity')

            @yield('content')
        </div>
    </main>

    {{-- Shared role enable confirmation modal --}}
    @include('partials._role_enable_modal')

    {{-- AI Chatbot floating widget --}}
    @include('partials._ai_chatbot_widget')

    <script>
        // Top-nav dropdowns — only one open at a time; close on outside click / Esc
        (function () {
            var items = document.querySelectorAll('.cl-topnav .cl-tn-item[data-dd]');
            items.forEach(function (item) {
                var trigger = item.querySelector('.cl-tn-link, .cl-tn-avatar');
                if (!trigger) return;
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var wasOpen = item.classList.contains('open');
                    items.forEach(function (i) { i.classList.remove('open'); });
                    if (!wasOpen) item.classList.add('open');
                });
            });
            document.addEventListener('click', function () {
                items.forEach(function (i) { i.classList.remove('open'); });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') items.forEach(function (i) { i.classList.remove('open'); });
            });
        })();

        // Theme toggle
        (function() {
            const STORAGE_KEY = 'cl-theme';
            const html = document.documentElement;

            // Load saved theme (default: light — matches the new client
            // dashboard mockups by Khadija. User can still toggle to dark.)
            const saved = localStorage.getItem(STORAGE_KEY) || 'light';
            html.setAttribute('data-theme', saved);

            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('theme-toggle');
                if (!btn) return;
                btn.addEventListener('click', function() {
                    const current = html.getAttribute('data-theme') || 'dark';
                    const next = current === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-theme', next);
                    localStorage.setItem(STORAGE_KEY, next);
                });
            });
        })();
    </script>
    @stack('scripts')

    {{-- Inline form validation: live blur/submit messages on data-validate inputs --}}
    @include('partials._form_validation')

    {{-- Voice search progressive-enhancement (mic button on [data-voice-search] inputs) --}}
    @include('partials._voice_search')

    {{-- Styled datepicker (Flatpickr) for all <input type="date"> --}}
    @include('partials._datepicker')

    {{-- Universal mobile-friendly fixes (sidebar backdrop, scroll lock,
         table scroll, grid stacking, responsive padding) --}}
    @include('partials._mobile_fixes')
</body>
</html>
