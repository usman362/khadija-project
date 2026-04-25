    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #8b5cf6;
            --bg-dark: #0b0f1a;
            --bg-section: #0f1629;
            --bg-card: #151d35;
            --bg-card-hover: #1a2440;
            --text-white: #ffffff;
            --text-light: #c8cdd8;
            --text-muted: #7a829a;
            --border-color: #1e2d4a;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
            --success: #22c55e;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }
        button { cursor: pointer; border: none; font-family: inherit; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ─── NAVBAR ──────────────────────────── */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(11, 15, 26, 0.72);
            backdrop-filter: blur(24px) saturate(1.4);
            -webkit-backdrop-filter: blur(24px) saturate(1.4);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 0;
            transition: background 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        /* Stronger shadow + deeper bg once the user scrolls past the hero. */
        .navbar.is-scrolled {
            background: rgba(8, 12, 22, 0.92);
            border-bottom-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
        }

        /* Row 1: logo + auth/CTA buttons. */
        .navbar-row-top {
            padding: 0 24px;
        }
        .navbar-row-top .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 68px;
        }

        /* Row 2: nav links (including the "All Categories" mega trigger).
           Slightly darker strip so it visually reads as a sub-bar, like
           Alibaba's category rail. A thin gradient line separates the
           two rows for a bit of luxury. */
        .navbar-row-links {
            padding: 0 24px;
            background: rgba(0, 0, 0, 0.22);
            position: relative;
        }
        .navbar-row-links::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(59, 130, 246, 0.25) 30%,
                rgba(139, 92, 246, 0.25) 70%,
                transparent 100%
            );
        }
        .navbar-row-links .container {
            display: flex;
            align-items: center;
            height: 50px;
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease, filter 0.2s ease;
        }
        .navbar-brand img {
            filter: drop-shadow(0 2px 8px rgba(59, 130, 246, 0.25));
            transition: filter 0.2s ease;
        }
        .navbar-brand:hover {
            transform: translateY(-1px);
        }
        .navbar-brand:hover img {
            filter: drop-shadow(0 4px 14px rgba(59, 130, 246, 0.45));
        }

        .navbar-brand span {
            color: var(--primary);
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }

        .navbar-links > li > a {
            position: relative;
            display: inline-flex;
            align-items: center;
            font-size: 0.88rem;
            color: var(--text-light);
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 8px;
            transition: color 0.2s ease, background 0.2s ease;
        }
        .navbar-links > li > a::after {
            content: '';
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: 2px;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            opacity: 0;
            transform: scaleX(0.4);
            transform-origin: center;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .navbar-links > li > a:hover {
            color: var(--text-white);
            background: rgba(255, 255, 255, 0.04);
        }
        .navbar-links > li > a:hover::after,
        .navbar-links > li > a.is-active::after {
            opacity: 1;
            transform: scaleX(1);
        }
        .navbar-links > li > a.is-active {
            color: var(--text-white);
        }

        /* ─── NAV MEGA MENU (Alibaba-style All Categories) ─── */
        /*
         * .nav-mega is an <li> in the nav. It uses `position: static` so
         * the big dropdown panel can anchor to the parent .navbar (which
         * is position: fixed) and span the full width below it.
         * Hover + focus + click all trigger .open; see navbar.blade.php
         * for the small JS controller.
         */
        .nav-mega {
            position: static;
            display: flex;
            align-items: center;
        }
        .nav-mega-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(59,130,246,0.14), rgba(139,92,246,0.14));
            border: 1px solid rgba(59,130,246,0.28);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.06),
                0 2px 10px rgba(59, 130, 246, 0.1);
            color: var(--text-white);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 9px 16px;
            margin-right: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .nav-mega-trigger:hover {
            background: linear-gradient(135deg, rgba(59,130,246,0.26), rgba(139,92,246,0.26));
            border-color: rgba(59,130,246,0.5);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.1),
                0 4px 16px rgba(59, 130, 246, 0.25);
            transform: translateY(-1px);
        }
        .nav-mega.open .nav-mega-trigger {
            background: linear-gradient(135deg, rgba(59,130,246,0.32), rgba(139,92,246,0.32));
            border-color: rgba(139, 92, 246, 0.55);
        }
        .nav-mega-trigger .nmt-burger { width: 16px; height: 16px; }
        .nav-mega-trigger .nmt-chev   { width: 12px; height: 12px; transition: transform 0.2s; }
        .nav-mega.open .nav-mega-trigger .nmt-chev { transform: rotate(180deg); }

        /* The big flyout panel.
           Anchors to .navbar (position: fixed → its own containing block)
           and sits flush against the bottom edge of the links row — no
           visual gap, so the cursor can transit from trigger into the
           panel without passing through dead space (which was causing
           mouseleave to fire prematurely). A transparent padding-top
           extension gives a little hover-safety buffer as well. */
        .nav-mega-panel {
            position: absolute;
            top: 100%;
            left: 24px;
            right: 24px;
            max-width: 1240px;
            margin: 0 auto;
            background: rgba(15, 20, 34, 0.98);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            display: grid;
            grid-template-columns: 260px 1fr;
            max-height: min(640px, calc(100vh - 96px));
            overflow: hidden;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }
        .nav-mega.open .nav-mega-panel {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        /* Invisible hover bridge: a transparent strip above the panel
           that covers any visual seam between the nav row and the panel.
           Keeps :hover continuous as the cursor transits down. */
        .nav-mega-panel::before {
            content: '';
            position: absolute;
            top: -10px; left: 0; right: 0;
            height: 10px;
            background: transparent;
        }

        /* LEFT rail: scrollable list of main categories. */
        .nmp-rail {
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.015);
            overflow-y: auto;
            padding: 8px 0;
        }
        .nmp-rail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border-left: 3px solid transparent;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .nmp-rail-item > svg:first-child { width: 18px; height: 18px; color: var(--primary); flex-shrink: 0; }
        .nmp-rail-item .rail-caret { width: 14px; height: 14px; margin-left: auto; opacity: 0.3; transition: opacity 0.15s; }
        .nmp-rail-item:hover,
        .nmp-rail-item.active {
            background: linear-gradient(90deg, rgba(59,130,246,0.14), transparent 80%);
            color: var(--text-white);
            border-left-color: var(--primary);
        }
        .nmp-rail-item.active .rail-caret { opacity: 0.8; }

        /* RIGHT showcase: only one .nmp-panel shows at a time. */
        .nmp-showcase {
            padding: 24px 28px;
            overflow-y: auto;
        }
        .nmp-panel { display: none; }
        .nmp-panel.active { display: block; animation: nmpFade 0.2s ease; }
        @keyframes nmpFade {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .nmp-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 18px;
            color: var(--text-white);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nmp-title span {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Circular bubble grid — this is the "Categories for you" block. */
        .nmp-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px 16px;
        }
        .nmp-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-light);
            transition: transform 0.2s, color 0.2s;
        }
        .nmp-tile:hover {
            transform: translateY(-3px);
            color: var(--text-white);
        }
        .nmp-bubble {
            position: relative;
            width: 84px; height: 84px;
            border-radius: 50%;
            overflow: hidden;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .nmp-tile:hover .nmp-bubble {
            border-color: rgba(59,130,246,0.5);
            box-shadow: 0 6px 20px rgba(59,130,246,0.25);
        }
        .nmp-bubble img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }
        .nmp-tile:hover .nmp-bubble img { transform: scale(1.08); }

        .nmp-label {
            font-size: 0.78rem;
            font-weight: 500;
            text-align: center;
            line-height: 1.3;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-outline {
            border: 1.5px solid rgba(255,255,255,0.2);
            color: var(--text-white);
            background: transparent;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: #fff;
            border: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-blue {
            background: #2563eb;
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-blue:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-red {
            background: #dc2626;
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-red:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 8px 18px;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        .btn-lg {
            padding: 14px 32px;
            font-size: 1rem;
            border-radius: 12px;
        }

        .btn-orange {
            background: #f97316;
            color: #fff;
            border: none;
            font-weight: 700;
        }
        .btn-orange:hover {
            background: #ea580c;
            transform: translateY(-1px);
        }

        /* ── Join dropdown ── Primary CTA with gradient fill + glow. */
        .join-dropdown {
            position: relative;
        }
        .join-dropdown-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.18),
                0 6px 20px rgba(59, 130, 246, 0.32);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            font-family: inherit;
            letter-spacing: 0.1px;
        }
        .join-dropdown-btn::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><path d='M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><line x1='20' y1='8' x2='20' y2='14'/><line x1='23' y1='11' x2='17' y2='11'/></svg>") center/contain no-repeat;
            flex-shrink: 0;
        }
        .join-dropdown-btn:hover {
            transform: translateY(-1px);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.22),
                0 10px 28px rgba(59, 130, 246, 0.45);
            filter: brightness(1.08);
        }
        .join-dropdown-btn svg { transition: transform 0.2s; }
        .join-dropdown.open .join-dropdown-btn svg { transform: rotate(180deg); }
        .join-dropdown.open .join-dropdown-btn {
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.22),
                0 8px 22px rgba(139, 92, 246, 0.4);
        }

        .join-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 220px;
            background: rgba(20, 26, 46, 0.96);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            z-index: 100;
            box-shadow: 0 20px 48px rgba(0,0,0,0.55);
        }
        .join-dropdown.open .join-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .join-dropdown-item {
            display: block;
            padding: 10px 14px;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-light);
            border-radius: 8px;
            transition: background 0.15s, color 0.15s, padding-left 0.15s;
        }
        .join-dropdown-item:hover {
            background: linear-gradient(90deg, rgba(59,130,246,0.12), rgba(139,92,246,0.08));
            color: var(--text-white);
            padding-left: 18px;
        }
        .navbar-login-link {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            transition: color 0.2s;
        }
        .navbar-login-link:hover { color: var(--text-white); }

        /* ── Notifications bell ── */
        .nav-icon-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-light);
            transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
        }
        .nav-icon-btn svg { width: 18px; height: 18px; }
        .nav-icon-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.18);
            color: #fff;
            transform: translateY(-1px);
        }
        .nav-icon-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.45);
            border: 2px solid rgba(11, 15, 26, 0.95);
            line-height: 1;
        }

        /* ── User avatar dropdown ── */
        .user-dropdown {
            position: relative;
        }
        .user-dropdown-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px 4px 4px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, transform 0.2s;
            font-family: inherit;
        }
        .user-dropdown-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        .user-dropdown.open .user-dropdown-btn {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(139, 92, 246, 0.4);
        }
        .user-dropdown-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }
        .user-dropdown-chev {
            transition: transform 0.2s;
            color: var(--text-light);
        }
        .user-dropdown.open .user-dropdown-chev { transform: rotate(180deg); }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 260px;
            background: rgba(20, 26, 46, 0.96);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
            z-index: 100;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.55);
        }
        .user-dropdown.open .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-dropdown-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px 14px;
            margin-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .user-dropdown-head-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .user-dropdown-head-info { min-width: 0; flex: 1; }
        .user-dropdown-head-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-dropdown-head-email {
            font-size: 0.78rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-light);
            border-radius: 8px;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s, padding-left 0.15s;
        }
        .user-dropdown-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: 0.7;
        }
        .user-dropdown-item:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.12), rgba(139, 92, 246, 0.08));
            color: #fff;
            padding-left: 16px;
        }
        .user-dropdown-item:hover svg { opacity: 1; }
        .user-dropdown-item-danger { color: #fca5a5; }
        .user-dropdown-item-danger:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
        }
        .user-dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 6px 4px;
        }
        .user-dropdown-logout-form { margin: 0; }

        /* Log in button — refined glass outline instead of plain border. */
        .navbar-actions .btn-outline.btn-sm {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            font-weight: 600;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }
        .navbar-actions .btn-outline.btn-sm:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.28);
            color: #fff;
            transform: translateY(-1px);
        }

        .mobile-menu-btn {
            display: none;
            background: transparent;
            color: #fff;
            font-size: 1.5rem;
            padding: 8px;
        }

        /* ─── HERO ────────────────────────────── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            /* Bumped from 72px so the eyebrow/headline never sits flush
               against the fixed 68px navbar even at short viewports. */
            padding-top: 110px;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg,
                rgba(11,15,26,0.3) 0%,
                rgba(11,15,26,0.6) 40%,
                rgba(11,15,26,0.95) 100%
            );
            z-index: 1;
        }

        .hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero .container {
            position: relative;
            z-index: 2;
            text-align: center;
            padding-top: 80px;
            padding-bottom: 80px;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--text-light);
            max-width: 620px;
            margin: 0 auto 32px;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 48px;
            flex-wrap: wrap;
        }

        .hero-search {
            max-width: 500px;
            margin: 0 auto 48px;
            position: relative;
        }

        .hero-search input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border-radius: 12px;
            border: 1.5px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.06);
            color: #fff;
            font-size: 0.95rem;
            backdrop-filter: blur(10px);
            outline: none;
            transition: border-color 0.2s;
        }

        .hero-search input::placeholder { color: var(--text-muted); }
        .hero-search input:focus { border-color: var(--primary); }

        .hero-search svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .trust-badges {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            max-width: 800px;
            margin: 0 auto;
        }

        .trust-badge {
            text-align: center;
        }

        .trust-badge .badge-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 10px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .trust-badge:nth-child(1) .badge-icon { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 4px 15px rgba(34,197,94,0.3); }
        .trust-badge:nth-child(2) .badge-icon { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245,158,11,0.3); }
        .trust-badge:nth-child(3) .badge-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); box-shadow: 0 4px 15px rgba(139,92,246,0.3); }
        .trust-badge:nth-child(4) .badge-icon { background: linear-gradient(135deg, #ec4899, #db2777); box-shadow: 0 4px 15px rgba(236,72,153,0.3); }

        .trust-badge:hover .badge-icon { transform: translateY(-3px) scale(1.05); }

        .trust-badge .badge-icon svg {
            width: 22px;
            height: 22px;
            color: #fff;
        }

        .trust-badge h4 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .trust-badge p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* ─── SECTION COMMON ──────────────────── */
        .section {
            padding: 100px 0;
        }

        .section-alt {
            background: var(--bg-section);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .section-header p {
            color: var(--text-light);
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ─── HOW IT WORKS ────────────────────── */
        /*
         * Journey layout: a zig-zag of four steps connected by a dashed
         * "path" that runs behind the numbered medallions. Unlike the old
         * three-card row (which collapsed into a flat stack on mobile and
         * read as "thumbnails"), each step here claims a real row on its
         * own, with the copy and the illustration sharing horizontal space.
         *
         * Desktop: zig-zag (odd rows illustration-right, even rows
         *          illustration-left) with a vertical dashed line that
         *          threads through all four numbered badges.
         * Mobile:  single column, dashed line pulled to the left, badges
         *          sitting on the line like mile markers.
         */
        .journey {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 0;
        }

        /* The dashed path. Positioned absolutely so content can flow over
           it; `top/bottom: 60px` pulls it in from the first/last medallion
           so it doesn't overshoot. */
        .journey::before {
            content: '';
            position: absolute;
            top: 60px;
            bottom: 60px;
            left: 50%;
            width: 0;
            border-left: 2px dashed rgba(139, 92, 246, 0.35);
            transform: translateX(-1px);
            pointer-events: none;
        }

        .journey-step {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 120px 1fr;
            align-items: center;
            gap: 24px;
            margin-bottom: 48px;
        }
        .journey-step:last-child { margin-bottom: 0; }

        /* Numbered medallion sits in the center column, over the dashed
           line. Color is themed per step via the --step-color var set on
           each .journey-step below. */
        .journey-num {
            grid-column: 2;
            width: 76px;
            height: 76px;
            margin: 0 auto;
            border-radius: 50%;
            background: var(--step-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 10px 30px var(--step-shadow), 0 0 0 8px var(--bg-primary);
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        .journey-step:hover .journey-num { transform: scale(1.08) rotate(-4deg); }

        /* The two side panels: copy on one side, illustration on the
           other. We swap their columns on even rows to make the zig-zag. */
        .journey-copy,
        .journey-art {
            min-width: 0;
        }
        .journey-step:nth-child(odd) .journey-copy { grid-column: 1; text-align: right; }
        .journey-step:nth-child(odd) .journey-art  { grid-column: 3; }
        .journey-step:nth-child(even) .journey-art  { grid-column: 1; text-align: right; }
        .journey-step:nth-child(even) .journey-copy { grid-column: 3; text-align: left; }

        .journey-copy h3 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 8px;
            color: var(--text-primary);
        }
        .journey-copy p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0 0 12px;
        }
        .journey-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--step-color);
            background: var(--step-tint);
            padding: 4px 12px;
            border-radius: 999px;
        }
        .journey-meta svg { width: 14px; height: 14px; }

        /* Illustration tile: a soft tinted square with a big icon and
           decorative corner dots so it doesn't read as a stock thumbnail. */
        .journey-art {
            position: relative;
            aspect-ratio: 1 / 1;
            max-width: 220px;
            border-radius: 22px;
            background: var(--step-tint);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: transform 0.35s ease;
        }
        .journey-step:nth-child(odd) .journey-art  { margin-left: auto; margin-right: 0; }
        .journey-step:nth-child(even) .journey-art { margin-right: auto; margin-left: 0; }
        .journey-step:hover .journey-art { transform: translateY(-4px) rotate(-2deg); }

        .journey-art::before,
        .journey-art::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border-radius: 50%;
            background: var(--step-color);
            opacity: 0.22;
        }
        .journey-art::before { top: 16px; left: 16px; }
        .journey-art::after  { bottom: 20px; right: 18px; width: 10px; height: 10px; }

        .journey-art svg {
            width: 68px;
            height: 68px;
            color: var(--step-color);
            stroke-width: 1.75;
            position: relative;
            z-index: 1;
        }

        /* Per-step color theming. Each row picks its own hue so the
           timeline feels like progress, not repetition. */
        .journey-step.step-1 { --step-color: #3b82f6; --step-gradient: linear-gradient(135deg, #3b82f6, #6366f1); --step-tint: rgba(59,130,246,0.10); --step-shadow: rgba(59,130,246,0.35); }
        .journey-step.step-2 { --step-color: #f97316; --step-gradient: linear-gradient(135deg, #f97316, #ef4444); --step-tint: rgba(249,115,22,0.10); --step-shadow: rgba(249,115,22,0.35); }
        .journey-step.step-3 { --step-color: #8b5cf6; --step-gradient: linear-gradient(135deg, #8b5cf6, #d946ef); --step-tint: rgba(139,92,246,0.10); --step-shadow: rgba(139,92,246,0.35); }
        .journey-step.step-4 { --step-color: #22c55e; --step-gradient: linear-gradient(135deg, #22c55e, #14b8a6); --step-tint: rgba(34,197,94,0.10); --step-shadow: rgba(34,197,94,0.35); }

        /* Mobile: the dashed line shifts to the left gutter and every
           step becomes illustration-above, copy-below — with the medallion
           sitting on the path. This looks deliberate instead of "two
           thumbnails stacked". */
        @media (max-width: 768px) {
            .journey { padding-left: 18px; }
            .journey::before { left: 38px; top: 40px; bottom: 40px; }
            .journey-step {
                grid-template-columns: 60px 1fr;
                gap: 16px;
                margin-bottom: 36px;
                text-align: left !important;
            }
            .journey-num {
                grid-column: 1 !important;
                width: 60px; height: 60px;
                font-size: 1.3rem;
                box-shadow: 0 6px 20px var(--step-shadow), 0 0 0 6px var(--bg-primary);
            }
            .journey-copy,
            .journey-step:nth-child(odd) .journey-copy,
            .journey-step:nth-child(even) .journey-copy {
                grid-column: 2 !important;
                text-align: left !important;
            }
            .journey-art,
            .journey-step:nth-child(odd) .journey-art,
            .journey-step:nth-child(even) .journey-art {
                display: none; /* keep mobile tight — the icon isn't needed next to the copy */
            }
        }

        /* ─── CTA BANNER ──────────────────────── */
        .cta-banner {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            padding: 60px 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border: 1px solid var(--border-color);
        }

        .cta-content h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .cta-content p {
            color: var(--text-light);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .cta-features {
            list-style: none;
            margin-bottom: 24px;
        }

        .cta-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .cta-features li svg {
            width: 18px;
            height: 18px;
            color: var(--success);
            flex-shrink: 0;
        }

        .cta-image {
            border-radius: 16px;
            overflow: hidden;
            height: 300px;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cta-image svg { width: 80px; height: 80px; color: var(--text-muted); opacity: 0.2; }

        /* ─── PRICING ─────────────────────────── */
        .pricing-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 48px;
        }

        .pricing-toggle .toggle-label {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .pricing-toggle .toggle-label.active {
            color: var(--text-white);
        }

        .toggle-switch {
            width: 52px;
            height: 28px;
            background: var(--bg-card);
            border-radius: 14px;
            position: relative;
            cursor: pointer;
            border: 1.5px solid var(--border-color);
            transition: background 0.3s;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--primary);
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }

        .toggle-switch.yearly::after {
            transform: translateX(24px);
        }

        .pricing-save {
            font-size: 0.75rem;
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .pricing-tabs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .pricing-tab {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            background: var(--bg-card);
            color: var(--text-muted);
            cursor: pointer;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .pricing-tab.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .pricing-card {
            background: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 16px;
            padding: 32px 28px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 0 40px rgba(59,130,246,0.15);
            transform: scale(1.02);
        }

        .pricing-card.featured:hover {
            transform: scale(1.02) translateY(-4px);
        }

        .pricing-badge {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .badge-primary { background: var(--primary); color: #fff; }
        .badge-success { background: var(--success); color: #fff; }
        .badge-warning { background: var(--warning); color: #000; }
        .badge-danger { background: #ef4444; color: #fff; }
        .badge-info { background: #06b6d4; color: #fff; }
        .badge-dark { background: #374151; color: #fff; }

        .pricing-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pricing-card-icon svg { width: 24px; height: 24px; }

        .pricing-plan-name {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .pricing-plan-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .pricing-amount {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 20px;
        }

        .pricing-currency {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .pricing-value {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .pricing-cycle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-left: 2px;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 24px;
            flex: 1;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .pricing-features li svg { width: 16px; height: 16px; flex-shrink: 0; }
        .pricing-features li .check { color: var(--success); }
        .pricing-features li .cross { color: #ef4444; }
        .pricing-features li.excluded { color: var(--text-muted); text-decoration: line-through; }

        .pricing-btn {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            transition: all 0.2s;
        }

        .pricing-btn-default {
            background: transparent;
            border: 1.5px solid var(--border-color);
            color: var(--text-white);
        }

        .pricing-btn-default:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .pricing-btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            color: #fff;
        }

        .pricing-btn-primary:hover { opacity: 0.9; }

        /* ─── TESTIMONIALS ────────────────────── */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .testimonial-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 32px;
        }

        .testimonial-stars {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
        }

        .testimonial-stars svg { width: 18px; height: 18px; color: #f59e0b; fill: #f59e0b; }

        .testimonial-card blockquote {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.7;
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .testimonial-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }

        .testimonial-author-name { font-weight: 600; font-size: 0.9rem; }
        .testimonial-author-role { font-size: 0.8rem; color: var(--text-muted); }

        /* ─── NEWSLETTER ──────────────────────── */
        .newsletter {
            text-align: center;
        }

        .newsletter h2 {
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .newsletter p {
            color: var(--text-light);
            margin-bottom: 32px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .newsletter-form {
            display: flex;
            gap: 12px;
            max-width: 480px;
            margin: 0 auto;
        }

        .newsletter-form input {
            flex: 1;
            padding: 14px 20px;
            border-radius: 10px;
            border: 1.5px solid var(--border-color);
            background: var(--bg-card);
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        .newsletter-form input:focus { border-color: var(--primary); }
        .newsletter-form input::placeholder { color: var(--text-muted); }

        /* ─── FOOTER ──────────────────────────── */
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 60px 0 32px;
            background: #060912;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        .footer-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .footer-socials {
            display: flex;
            gap: 12px;
        }

        .footer-social {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .footer-social:hover { background: var(--primary); }
        .footer-social svg { width: 16px; height: 16px; }

        .footer-col h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col li {
            margin-bottom: 10px;
        }

        .footer-col a {
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .footer-col a:hover { color: var(--text-white); }

        .footer-bottom {
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ─── ABOUT US ───────────────────────── */
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-content h3 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .about-content h2 {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .about-content p {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 32px;
        }

        .about-stat {
            text-align: center;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .about-stat:hover { transform: translateY(-3px); }

        .about-stat:nth-child(1) h4 {
            font-size: 1.75rem; font-weight: 800;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .about-stat:nth-child(1):hover { box-shadow: 0 6px 20px rgba(59,130,246,0.15); }

        .about-stat:nth-child(2) h4 {
            font-size: 1.75rem; font-weight: 800;
            background: linear-gradient(135deg, #f97316, #f59e0b);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .about-stat:nth-child(2):hover { box-shadow: 0 6px 20px rgba(249,115,22,0.15); }

        .about-stat:nth-child(3) h4 {
            font-size: 1.75rem; font-weight: 800;
            background: linear-gradient(135deg, #22c55e, #14b8a6);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .about-stat:nth-child(3):hover { box-shadow: 0 6px 20px rgba(34,197,94,0.15); }

        .about-stat p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .about-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            height: 420px;
        }

        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .about-image::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            border: 2px solid rgba(59,130,246,0.2);
        }

        /* ─── FAQ ────────────────────────────── */
        .faq-grid {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            background: var(--bg-card);
        }

        .faq-question {
            width: 100%;
            padding: 20px 24px;
            background: transparent;
            color: var(--text-white);
            font-size: 1rem;
            font-weight: 600;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            font-family: inherit;
        }

        .faq-question:hover {
            color: var(--primary);
        }

        .faq-question .faq-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            transition: transform 0.3s;
        }

        .faq-item.active .faq-question .faq-icon {
            transform: rotate(45deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-answer-inner {
            padding: 0 24px 20px;
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .faq-item.active .faq-answer {
            max-height: 300px;
        }

        /* ─── RESPONSIVE ──────────────────────── */
        @media (max-width: 1024px) {
            .cta-banner { grid-template-columns: 1fr; }
            .cta-image { display: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .navbar-links { display: none; }
            /* Second row collapses entirely on mobile — hamburger opens
               the full nav instead. */
            .navbar-row-links { display: none; }
            .navbar-actions .join-dropdown, .navbar-actions .navbar-login-link { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .trust-badges { grid-template-columns: repeat(2, 1fr); }
            .pricing-grid { grid-template-columns: 1fr; max-width: 400px; margin: 0 auto; }
            .pricing-card.featured { transform: none; }
            .pricing-card.featured:hover { transform: translateY(-4px); }
            .newsletter-form { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
            .hero h1 { font-size: 2.2rem; }
            .cta-banner { padding: 40px 24px; }
            .hero-buttons { flex-direction: column; align-items: center; }
            .about-grid { grid-template-columns: 1fr; gap: 32px; }
            .about-image { height: 280px; }
            .about-stats { grid-template-columns: repeat(3, 1fr); gap: 12px; }
        }
    </style>
