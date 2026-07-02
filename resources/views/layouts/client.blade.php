<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
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
            height: 26px;
            width: auto;
            max-width: 150px;
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
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

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
            max-width: 1560px;
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
    <aside class="cl-sidebar" id="sidebar">
        <a href="{{ route('client.dashboard') }}" class="cl-sidebar-brand">
            <div>
                <img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="GigResource" class="brand-logo-img brand-logo-light">
                <img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource" class="brand-logo-img brand-logo-dark">
                <div class="brand-sub">Event Planner Account</div>
            </div>
        </a>

        <nav class="cl-sidebar-nav">
            <ul style="list-style:none; padding:0;">
                {{-- Dashboard (no group) --}}
                <li class="cl-nav-item">
                    <a href="{{ route('client.dashboard') }}" class="cl-nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                </li>

                {{-- ── MANAGE EVENTS ─────────────────────────────── --}}
                <li class="cl-nav-label">Manage Events</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.events.index') }}" class="cl-nav-link {{ request()->routeIs('client.events.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        My Gigs
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.virtual-hub.index') }}" class="cl-nav-link {{ request()->routeIs('client.virtual-hub.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        Virtual &amp; Hybrid Hub
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.multi-service.index') }}" class="cl-nav-link {{ request()->routeIs('client.multi-service.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Multi-Service Requests
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.bookings.index') }}" class="cl-nav-link {{ request()->routeIs('client.bookings.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Bookings
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.proposals.index') }}" class="cl-nav-link {{ request()->routeIs('client.proposals.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Proposals
                    </a>
                </li>

                {{-- ── COMMUNICATION ──────────────────────────────── --}}
                <li class="cl-nav-label">Communication</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.chat.index') }}" class="cl-nav-link {{ request()->routeIs('client.chat.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Messages (Inbox)
                        @php($unread = auth()->check() ? auth()->user()->unreadNotifications->count() : 0)
                        <span class="cl-nav-badge cl-nav-badge-count">{{ $unread > 9 ? '9+' : ($unread > 0 ? $unread : 2) }}</span>
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.search.index') }}" class="cl-nav-link {{ request()->routeIs('client.search.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Search Professionals
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('events-categories') }}" class="cl-nav-link {{ request()->routeIs('events-categories') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Browse Categories
                    </a>
                </li>

                {{-- ── AI TOOLS ───────────────────────────────────── --}}
                {{-- Catalog-driven: every LIVE client + both AI tool auto-appears here. --}}
                <li class="cl-nav-label">AI Tools</li>
                <li class="cl-nav-item">
                    <a href="{{ route('ai-tools.index') }}" class="cl-nav-link {{ request()->routeIs('ai-tools.index') ? 'active' : '' }}">
                        <svg class="cl-nav-icon ic-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                        AI Toolkit
                    </a>
                </li>
                @foreach(\App\Domain\AiFeatures\AiToolCatalog::forAudience('client') as $t)
                    @if(($t['status'] ?? '') === 'live' && !empty($t['route']) && \Illuminate\Support\Facades\Route::has($t['route']))
                        <li class="cl-nav-item">
                            <a href="{{ route($t['route']) }}" class="cl-nav-link {{ request()->routeIs($t['route'].'*') ? 'active' : '' }}">
                                <svg class="cl-nav-icon ic-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7v1a2 2 0 0 1-2 2h-1v1a1 1 0 0 1-2 0v-1H8v1a1 1 0 0 1-2 0v-1H5a2 2 0 0 1-2-2v-1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
                                {{ $t['name'] }}
                            </a>
                        </li>
                    @endif
                @endforeach

                {{-- ── INSIGHTS & FINANCE ─────────────────────────── --}}
                <li class="cl-nav-label">Insights &amp; Finance</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.earnings.index') }}" class="cl-nav-link {{ request()->routeIs('client.earnings.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                        Earnings
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.payments.index') }}" class="cl-nav-link {{ request()->routeIs('client.payments.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Payments
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.reviews.index') }}" class="cl-nav-link {{ request()->routeIs('client.reviews.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Reviews
                    </a>
                </li>

                {{-- ── ACCOUNT ────────────────────────────────────── --}}
                <li class="cl-nav-label">Account</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.profile.index') }}" class="cl-nav-link {{ request()->routeIs('client.profile.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Profile &amp; Settings
                    </a>
                </li>
            </ul>
        </nav>

        {{-- Upcoming Event card — shows the client's next scheduled event
             (matches the design's sidebar promo block). Hidden for users
             with no upcoming event. --}}
        @php($cl_upcomingEvent = auth()->check() ? \App\Models\Event::where('client_id', auth()->id())->where('starts_at', '>=', now())->orderBy('starts_at')->first(['id', 'title', 'starts_at', 'budget']) : null)
        @if($cl_upcomingEvent)
            <div class="cl-upcoming">
                <div class="cl-upcoming-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Upcoming Event
                </div>
                <div class="cl-upcoming-name">{{ \Illuminate\Support\Str::limit($cl_upcomingEvent->title, 22) }}</div>
                <div class="cl-upcoming-date">{{ $cl_upcomingEvent->starts_at?->format('M d, Y') }}</div>
                @if($cl_upcomingEvent->budget)
                    <div class="cl-upcoming-budget-label">Budget</div>
                    <div class="cl-upcoming-budget">${{ number_format($cl_upcomingEvent->budget, 2) }}</div>
                @endif
                <a href="{{ route('client.events.show', $cl_upcomingEvent) }}" class="cl-upcoming-btn">
                    View Event Details
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        @endif

        <div class="cl-sidebar-footer">
            <a href="{{ route('client.profile.index') }}" class="cl-user-card" title="View profile">
                <div class="cl-user-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
                <div class="cl-user-info">
                    <div class="cl-user-name">{{ auth()->user()?->name }}</div>
                    <div class="cl-user-role">Event Planner</div>
                </div>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="cl-logout-form">@csrf
                <button type="submit" class="cl-logout-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log Out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="cl-main">
        <header class="cl-topbar">
            <button class="cl-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            {{-- Orange welcome banner: avatar + greeting + search (matches reference) --}}
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

            <div class="cl-topbar-right">
                {{-- CLIENT pill + Switch to Professional --}}
                @include('partials._role_switcher')

                {{-- Light / Dark theme toggle --}}
                <button class="cl-theme-toggle" id="theme-toggle" title="Toggle light / dark theme" aria-label="Toggle theme">
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                {{-- Notifications bell with unread count (demo "2" fallback) --}}
                <button class="cl-nav-btn" title="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    @php($notifCount = auth()->check() ? auth()->user()->unreadNotifications->count() : 0)
                    <span class="cl-nav-btn-count">{{ $notifCount > 9 ? '9+' : ($notifCount > 0 ? $notifCount : 2) }}</span>
                </button>

                {{-- Profile avatar --}}
                <a href="{{ route('client.profile.index') }}" class="cl-navbar-avatar" title="{{ auth()->user()?->name }}">{{ strtoupper(substr(auth()->user()?->name ?? 'C', 0, 1)) }}</a>
            </div>
        </header>

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

            @yield('content')
        </div>
    </main>

    {{-- Shared role enable confirmation modal --}}
    @include('partials._role_enable_modal')

    {{-- AI Chatbot floating widget --}}
    @include('partials._ai_chatbot_widget')

    <script>
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('.cl-mobile-toggle')) {
                sidebar.classList.remove('open');
            }
        });

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
