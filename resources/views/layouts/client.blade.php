<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' — Dashboard')</title>

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
            --sidebar-width: 260px;
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
            --bg-primary: #f1f5f9;
            --bg-secondary: #ffffff;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-card-hover: rgba(241, 245, 249, 0.9);
            --bg-sidebar: #ffffff;
            --border-color: rgba(0, 0, 0, 0.08);
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
            height: 30px;
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
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-muted);
            padding: 16px 12px 8px;
        }

        .cl-nav-item {
            list-style: none;
        }

        .cl-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            margin-bottom: 2px;
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
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .cl-nav-link.active .cl-nav-icon { opacity: 1; }

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
            max-width: 1440px;
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

        /* ═══════════════════════ LOGOUT FORM ═══════════════════════ */
        .cl-logout-form { display: none; }
    </style>
    @stack('styles')
</head>

<body>
    {{-- Sidebar --}}
    <aside class="cl-sidebar" id="sidebar">
        <a href="{{ route('client.dashboard') }}" class="cl-sidebar-brand">
            <div>
                <img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" class="brand-logo-img brand-logo-light">
                <img src="{{ asset('logos/logo-primary.png') }}" alt="GigResource" class="brand-logo-img brand-logo-dark">
                <div class="brand-sub">{{ ucfirst(auth()->user()?->roles?->first()?->name ?? 'Client') }} Account</div>
            </div>
        </a>

        <nav class="cl-sidebar-nav">
            <ul style="list-style:none; padding:0;">
                <li class="cl-nav-item">
                    <a href="{{ route('client.dashboard') }}" class="cl-nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                </li>

                <li class="cl-nav-label">Events & Bookings</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.events.index') }}" class="cl-nav-link {{ request()->routeIs('client.events.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        My Events
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.bookings.index') }}" class="cl-nav-link {{ request()->routeIs('client.bookings.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        My Bookings
                    </a>
                </li>

                <li class="cl-nav-label">Communication</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.chat.index') }}" class="cl-nav-link {{ request()->routeIs('client.chat.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Messages
                    </a>
                </li>

                <li class="cl-nav-label">Billing</li>
                <li class="cl-nav-item">
                    <a href="{{ route('app.membership-plans.index') }}" class="cl-nav-link {{ request()->routeIs('app.membership-plans.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        Membership Plans
                    </a>
                </li>
                <li class="cl-nav-item">
                    <a href="{{ route('app.payments.history') }}" class="cl-nav-link {{ request()->routeIs('app.payments.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Payments
                    </a>
                </li>

                <li class="cl-nav-label">Account</li>
                <li class="cl-nav-item">
                    <a href="{{ route('client.profile.index') }}" class="cl-nav-link {{ request()->routeIs('client.profile.*') ? 'active' : '' }}">
                        <svg class="cl-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Profile & Settings
                    </a>
                </li>
            </ul>
        </nav>

        <div class="cl-sidebar-footer">
            <div class="cl-user-card" onclick="document.getElementById('logout-form').submit();">
                <div class="cl-user-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
                <div class="cl-user-info">
                    <div class="cl-user-name">{{ auth()->user()?->name }}</div>
                    <div class="cl-user-role">{{ ucfirst(auth()->user()?->roles?->first()?->name ?? 'Client') }}</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="cl-logout-form">@csrf</form>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="cl-main">
        <header class="cl-navbar">
            <div class="cl-navbar-left">
                <button class="cl-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="cl-page-title">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="cl-navbar-right">
                <button class="cl-theme-toggle" id="theme-toggle" title="Toggle theme">
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <a href="{{ route('client.chat.index') }}" class="cl-nav-btn" title="Messages">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span class="badge-dot"></span>
                </a>
                <button class="cl-nav-btn" title="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </button>
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
</body>
</html>
