<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Anti-FOUC: apply the saved theme BEFORE first paint so there's no light/dark flash. --}}
    <script>(function(){try{var t=localStorage.getItem('cl-theme')||'dark';document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
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
            /* Role brand accent — PROFESSIONAL = blue. Tools use var(--brand*) so
               their accent follows the portal they're rendered in. */
            --brand: #2563eb; --brand-strong: #1d4ed8; --brand-soft: rgba(37,99,235,0.10);
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
            --accent-blue: #2563eb;
            --accent-blue-soft: rgba(37, 99, 235, 0.12);
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
            --bg-primary: #ffffff;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --bg-sidebar: #ffffff;
            --border-color: rgba(0, 0, 0, 0.08);
            --border-glow: rgba(99, 102, 241, 0.2);
            --text-primary: #1e293b;
            --text-secondary: #334155;
            --text-muted: #475569;
            --accent-blue: #2563eb;
            --accent-blue-soft: rgba(37, 99, 235, 0.1);
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
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.06);
            --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.1);
        }

        [data-theme="light"] .cl-navbar {
            background: rgba(255, 255, 255, 0.85);
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
            color: var(--accent-blue);
            background: rgba(99, 102, 241, 0.08);
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
            padding: 16px 12px;
        }

        .cl-sidebar-nav::-webkit-scrollbar { width: 4px; }
        .cl-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .cl-sidebar-nav::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }

        .cl-nav-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 12px 11px 5px;
        }

        .cl-nav-item {
            list-style: none;
        }

        .cl-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 11px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            margin-bottom: 1px;
            white-space: nowrap;
        }

        .cl-nav-link:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
        }

        .cl-nav-link.active {
            background: var(--accent-blue-soft);
            color: var(--accent-blue);
        }

        .cl-nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: var(--accent-blue);
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

        /* Uniform muted nav icons; active item turns blue */
        .cl-nav-icon { color: var(--text-muted); }
        .cl-nav-link.active .cl-nav-icon { color: var(--accent-blue); }
        /* nav count badge + urgent pill */
        .pro-nav-badge { margin-left: auto; background: var(--accent-blue); color: #fff; font-size: 9.5px; font-weight: 800; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pro-nav-urgent { margin-left: auto; background: rgba(239,68,68,0.14); color: #ef4444; font-size: 8px; font-weight: 800; padding: 2px 6px; border-radius: 5px; letter-spacing: 0.5px; flex-shrink: 0; }

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
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .cl-navbar-left { display: flex; align-items: center; gap: 16px; }

        /* ══════ Professional topbar — blue welcome banner ══════ */
        .pro-topbar { display: flex; align-items: flex-start; gap: 16px; padding: 14px 26px 4px; position: sticky; top: 0; z-index: 100; background: var(--bg-primary); }
        .pro-banner { flex: 1; min-width: 0; display: flex; align-items: center; gap: 16px; background: linear-gradient(120deg, #3b82f6 0%, #2563eb 100%); border-radius: 14px; padding: 13px 18px; box-shadow: 0 6px 18px rgba(37,99,235,0.20); }
        .pro-banner-avatar { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; }
        .pro-banner-text { flex-shrink: 0; }
        .pro-banner-text h1 { font-size: 17px; font-weight: 800; color: #fff; margin: 0; }
        .pro-banner-text p { font-size: 11.5px; color: rgba(255,255,255,0.82); margin: 2px 0 0; }
        .pro-banner-search { flex: 1; min-width: 120px; position: relative; margin-left: 6px; }
        .pro-banner-search > svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); }
        .pro-banner-search input { width: 100%; height: 42px; border-radius: 10px; border: none; padding: 0 44px 0 40px; background: #fff; font-size: 13px; color: #1e293b; outline: none; }
        .pro-banner-search kbd { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 10px; color: var(--text-muted); background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 5px; padding: 2px 6px; }

        .pro-topbar-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
        .pro-topbar-controls { display: flex; align-items: center; gap: 12px; }
        .pro-avail { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 800; color: #16a34a; letter-spacing: 0.5px; cursor: pointer; }
        .pro-avail .dot { width: 7px; height: 7px; border-radius: 50%; background: #16a34a; }
        .pro-toggle { position: relative; width: 34px; height: 18px; display: inline-block; }
        .pro-toggle input { display: none; }
        .pro-toggle .track { position: absolute; inset: 0; background: #16a34a; border-radius: 999px; transition: 0.2s; }
        .pro-toggle .track::after { content: ''; position: absolute; top: 2px; left: 18px; width: 14px; height: 14px; border-radius: 50%; background: #fff; transition: 0.2s; }
        /* Role switcher recoloured for the professional (blue) portal — the
           shared partial ships an orange "Switch" button; override it here. */
        .pro-topbar-right .rs-btn { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .pro-topbar-right .rs-btn:hover { box-shadow: 0 4px 12px rgba(37,99,235,0.35); }
        .pro-topbar-right .rs-btn-enable { background: transparent; }
        .pro-icon-btn { position: relative; width: 38px; height: 38px; border-radius: 10px; border: none; background: transparent; color: var(--text-secondary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; }
        .pro-icon-btn:hover { background: var(--bg-card-hover); }
        .pro-icon-btn svg { width: 18px; height: 18px; }
        .pro-icon-badge { position: absolute; top: -5px; right: -5px; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 8px; background: #2563eb; color: #fff; font-size: 9.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
        .pro-icon-badge.red { background: #ef4444; }
        .pro-avatar-chip { display: inline-flex; align-items: center; gap: 8px; padding: 3px 8px 3px 3px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); cursor: pointer; }
        .pro-avatar-img { width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; }
        .pro-avatar-meta { display: flex; flex-direction: column; line-height: 1.2; }
        .pro-avatar-meta b { font-size: 12px; color: var(--text-primary); font-weight: 700; white-space: nowrap; }
        .pro-avatar-meta span { font-size: 9px; font-weight: 800; color: #2563eb; letter-spacing: 0.5px; }
        .pro-avatar-chip > svg { width: 13px; height: 13px; color: var(--text-muted); }

        .pro-topbar-actions { display: flex; gap: 10px; }
        .pro-btn-ghost, .pro-btn-primary { display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 16px; border-radius: 10px; font-size: 12.5px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .pro-btn-ghost svg, .pro-btn-primary svg { width: 15px; height: 15px; }
        .pro-btn-ghost { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary); }
        .pro-btn-primary { background: #2563eb; border: none; color: #fff; }
        .pro-btn-primary:hover { background: #1d4ed8; }
        @media (max-width: 1200px) { .pro-banner-text { display: none; } }
        @media (max-width: 860px) { .pro-topbar { flex-direction: column; } .pro-topbar-right { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; flex-wrap: wrap; } .pro-avatar-meta { display: none; } }

        .cl-mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
        }

        .cl-page-title { font-size: 18px; font-weight: 600; }

        .cl-navbar-right { display: flex; align-items: center; gap: 8px; }

        .cl-nav-btn {
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
            padding: 28px 32px;
            max-width: 1760px;
            margin-left: auto;
            margin-right: auto;
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
            background: linear-gradient(135deg, var(--accent-blue), #8b5cf6);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .cl-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4); color: #fff; }

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
        .cl-tab.active { background: var(--accent-blue-soft); color: var(--accent-blue); }

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
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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

        .cl-search-box input:focus { outline: none; border-color: var(--accent-blue); }
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
        }

        @media (max-width: 768px) {
            .cl-sidebar { transform: translateX(-100%); }
            .cl-sidebar.open { transform: translateX(0); }
            .cl-main { margin-left: 0; }
            .cl-mobile-toggle { display: flex; }
            .cl-content { padding: 20px 16px; }
            .cl-grid-4, .cl-grid-3, .cl-grid-2 { grid-template-columns: 1fr; }
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
        .cl-calendar-day.today { border-color: var(--accent-blue); background: var(--accent-blue-soft); }
        .cl-calendar-day.today .day-num { color: var(--accent-blue); font-weight: 700; }
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
            background: var(--accent-blue-soft);
            color: var(--accent-blue);
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
        .cl-pagination .active span { background: var(--accent-blue); color: #fff; border-color: var(--accent-blue); }
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
        <a href="{{ route('professional.dashboard') }}" class="cl-sidebar-brand">
            <div>
                <img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="GigResource" class="brand-logo-img brand-logo-light">
                <img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource" class="brand-logo-img brand-logo-dark">
            </div>
        </a>

        <nav class="cl-sidebar-nav">
            <ul style="list-style:none; padding:0;">
                {{-- ── MAIN ── --}}
                <li class="cl-nav-label">Main</li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.dashboard') }}" class="cl-nav-link {{ request()->routeIs('professional.dashboard') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.priority.index') }}" class="cl-nav-link {{ request()->routeIs('professional.priority.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Priority Actions
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.bid-intelligence.index') }}" class="cl-nav-link {{ request()->routeIs('professional.bid-intelligence.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Bid Intelligence
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.bidding-board.index') }}" class="cl-nav-link {{ request()->routeIs('professional.bidding-board.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Bidding Board
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.multi-service.index') }}" class="cl-nav-link {{ request()->routeIs('professional.multi-service.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                        Multi-Service Requests
                    </a>
                </li>

                {{-- ── OPERATIONS ── --}}
                <li class="cl-nav-label">Operations</li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.contracts.index') }}" class="cl-nav-link {{ request()->routeIs('professional.contracts.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                        Contracts
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.packages.create') }}" class="cl-nav-link {{ request()->routeIs('professional.packages.create') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.89 1.45l8 4A2 2 0 0 1 22 7.24v9.53a2 2 0 0 1-1.11 1.79l-8 4a2 2 0 0 1-1.79 0l-8-4a2 2 0 0 1-1.1-1.8V7.24a2 2 0 0 1 1.11-1.79l8-4a2 2 0 0 1 1.78 0z"/><polyline points="2.32 6.16 12 11 21.68 6.16"/><line x1="12" y1="22.76" x2="12" y2="11"/></svg>
                        Create a Package
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.packages.index') }}" class="cl-nav-link {{ request()->routeIs('professional.packages.index') || request()->routeIs('professional.packages.edit') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
                        My Packages
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.gigs.index') }}" class="cl-nav-link {{ request()->routeIs('professional.gigs.index') || request()->routeIs('professional.gigs.show') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        My Gigs
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.gig-hub.index') }}" class="cl-nav-link {{ request()->routeIs('professional.gig-hub.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><line x1="2" y1="13" x2="22" y2="13"/></svg>
                        Gig Operations Hub
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.calendar.index') }}" class="cl-nav-link {{ request()->routeIs('professional.calendar.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Calendar
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.team.index') }}" class="cl-nav-link {{ request()->routeIs('professional.team.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Team & Workforce
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.chat.index') }}" class="cl-nav-link {{ request()->routeIs('professional.chat.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Messages
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.threads.index') }}" class="cl-nav-link {{ request()->routeIs('professional.threads.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 6.1H3"/><path d="M21 12.1H3"/><path d="M15.1 18H3"/></svg>
                        Threads
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.earnings.index') }}" class="cl-nav-link {{ request()->routeIs('professional.earnings.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Payments & Payouts
                    </a>
                </li>

                {{-- ── SALES ── --}}
                <li class="cl-nav-label">Sales</li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.leads.index') }}" class="cl-nav-link {{ request()->routeIs('professional.leads.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                        Leads CRM
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.proposals.index') }}" class="cl-nav-link {{ request()->routeIs('professional.proposals.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                        Proposals / Bids
                    </a>
                </li>

                {{-- ── AI TOOLS ── --}}
                {{-- Catalog-driven: every LIVE professional + both AI tool auto-appears here. --}}
                <li class="cl-nav-label">GigResource IQ</li>
                <li class="cl-nav-item">
                    <a href="{{ route('ai-tools.index') }}" class="cl-nav-link {{ request()->routeIs('ai-tools.index') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                        AI Toolkit
                    </a>
                </li>
                @foreach(\App\Domain\AiFeatures\AiToolCatalog::forAudience('professional') as $t)
                    @if(($t['status'] ?? '') === 'live' && !empty($t['route']) && \Illuminate\Support\Facades\Route::has($t['route']))
                        <li class="cl-nav-item">
                            <a href="{{ route($t['route']) }}" class="cl-nav-link {{ request()->routeIs($t['route'].'*') ? 'active' : '' }}">
                                <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7v1a2 2 0 0 1-2 2h-1v1a1 1 0 0 1-2 0v-1H8v1a1 1 0 0 1-2 0v-1H5a2 2 0 0 1-2-2v-1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
                                {{ $t['name'] }}
                            </a>
                        </li>
                    @endif
                @endforeach

                {{-- ── BUSINESS ── --}}
                <li class="cl-nav-label">Business</li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.reviews.index') }}" class="cl-nav-link {{ request()->routeIs('professional.reviews.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Reviews & Reputation
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.transactions.index') }}" class="cl-nav-link {{ request()->routeIs('professional.transactions.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="14" y2="12"/></svg>
                        Invoices
                    </a>
                </li>

                {{-- ── ACCOUNT ── --}}
                <li class="cl-nav-label">Account</li>
                <li class="cl-nav-item">
                    <a href="{{ route('professional.profile.index') }}" class="cl-nav-link {{ request()->routeIs('professional.profile.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Profile & Settings
                    </a>
                </li>
            </ul>
        </nav>

        <div class="cl-sidebar-footer">
            <a href="{{ route('professional.profile.index') }}" class="cl-user-card" title="View profile">
                <div class="cl-user-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
                <div class="cl-user-info">
                    <div class="cl-user-name">{{ auth()->user()?->name }}</div>
                    <div class="cl-user-role">Professional</div>
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
        <header class="pro-topbar">
            <button class="cl-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="pro-banner">
                <div class="pro-banner-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'P', 0, 1)) }}</div>
                <div class="pro-banner-text">
                    <h1>Welcome back, {{ auth()->user()?->name ?? 'Professional User' }}! 👋</h1>
                    <p>Here's your business overview for today, {{ now()->format('M d, Y') }}.</p>
                </div>
                <div class="pro-banner-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" placeholder="Search gigs, events, services, or professionals...">
                    <kbd>⌘K</kbd>
                </div>
            </div>

            <div class="pro-topbar-right">
                <div class="pro-topbar-controls">
                    @include('partials._role_switcher')
                    <button class="pro-icon-btn" title="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span class="pro-icon-badge">3</span>
                    </button>
                    <a href="{{ route('professional.chat.index') }}" class="pro-icon-btn" title="Messages">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span class="pro-icon-badge red">5</span>
                    </a>
                    <button class="cl-theme-toggle" id="theme-toggle" title="Toggle light / dark theme" aria-label="Toggle theme">
                        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </button>
                    <div class="pro-avatar-chip" onclick="window.location.href='{{ route('professional.profile.index') }}'" title="Account">
                        <div class="pro-avatar-img">{{ strtoupper(substr(auth()->user()?->name ?? 'P', 0, 1)) }}</div>
                        <div class="pro-avatar-meta"><b>{{ auth()->user()?->name ?? 'Professional User' }}</b><span>PRO</span></div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
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

            // Load saved theme (default: dark)
            const saved = localStorage.getItem(STORAGE_KEY) || 'dark';
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
