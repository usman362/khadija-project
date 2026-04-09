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
            background: rgba(11, 15, 26, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 0 24px;
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #fff;
            text-decoration: none;
        }

        .navbar-brand span {
            color: var(--primary);
        }

        .navbar-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .navbar-links a {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
            transition: color 0.2s;
        }

        .navbar-links a:hover { color: var(--text-white); }

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

        /* ── Join dropdown ── */
        .join-dropdown {
            position: relative;
        }
        .join-dropdown-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1.5px solid var(--primary);
            color: var(--text-white);
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .join-dropdown-btn:hover { background: rgba(59,130,246,0.1); }
        .join-dropdown-btn svg { transition: transform 0.2s; }
        .join-dropdown.open .join-dropdown-btn svg { transform: rotate(180deg); }

        .join-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 200px;
            background: #1a2440;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 8px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s;
            z-index: 100;
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }
        .join-dropdown.open .join-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .join-dropdown-item {
            display: block;
            padding: 10px 18px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.15s;
        }
        .join-dropdown-item:hover {
            background: rgba(255,255,255,0.06);
            color: var(--text-white);
        }
        .navbar-login-link {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
            transition: color 0.2s;
        }
        .navbar-login-link:hover { color: var(--text-white); }

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
            padding-top: 72px;
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
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .step-card {
            text-align: center;
            padding: 40px 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            position: relative;
            transition: border-color 0.3s, box-shadow 0.3s, transform 0.3s;
        }

        .step-card:nth-child(1):hover { border-color: rgba(59,130,246,0.4); box-shadow: 0 8px 30px rgba(59,130,246,0.1); transform: translateY(-4px); }
        .step-card:nth-child(2):hover { border-color: rgba(249,115,22,0.4); box-shadow: 0 8px 30px rgba(249,115,22,0.1); transform: translateY(-4px); }
        .step-card:nth-child(3):hover { border-color: rgba(34,197,94,0.4); box-shadow: 0 8px 30px rgba(34,197,94,0.1); transform: translateY(-4px); }

        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 auto 20px;
        }

        .step-icon {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .step-card:nth-child(1) .step-icon { background: linear-gradient(135deg, #3b82f6, #6366f1); box-shadow: 0 6px 20px rgba(59,130,246,0.35); }
        .step-card:nth-child(2) .step-icon { background: linear-gradient(135deg, #f97316, #ef4444); box-shadow: 0 6px 20px rgba(249,115,22,0.35); }
        .step-card:nth-child(3) .step-icon { background: linear-gradient(135deg, #22c55e, #14b8a6); box-shadow: 0 6px 20px rgba(34,197,94,0.35); }

        .step-card:hover .step-icon { transform: translateY(-4px) scale(1.08); }

        .step-icon svg { width: 30px; height: 30px; color: #fff; }

        .step-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .step-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
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
            .navbar-actions .join-dropdown, .navbar-actions .navbar-login-link { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .trust-badges { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: 1fr; }
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
