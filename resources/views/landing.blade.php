<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Khadija') }} - Host Unforgettable Events With Confidence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            width: 40px;
            height: 40px;
            margin: 0 auto 8px;
            border-radius: 10px;
            background: rgba(59, 130, 246, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trust-badge .badge-icon svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
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
        }

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
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: rgba(59,130,246,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .step-icon svg { width: 28px; height: 28px; color: var(--primary); }

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
        }

        .about-stat h4 {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

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
</head>
<body>

<!-- ─── NAVBAR ───────────────────────────────── -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 36px;"></a>

        <ul class="navbar-links">
            <li><a href="{{ route('about-us') }}">About Us</a></li>
            <li><a href="{{ route('events-categories') }}">Events & Categories</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#faq">FAQ</a></li>
        </ul>

        <div class="navbar-actions">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-blue btn-sm">Join as Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-red btn-sm">Hire a Professional</a>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                @endif
            @endauth
        </div>

        <button class="mobile-menu-btn" onclick="this.nextElementSibling.classList.toggle('show')" aria-label="Menu">&#9776;</button>
        <div class="mobile-nav" style="display:none;"></div>
    </div>
</nav>

<!-- ─── HERO ─────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg">
        <img src="https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1600&q=80" alt="Outdoor event festival with colorful lights and staging" loading="eager">
    </div>
    <div class="container">
        <h1>Find The Right<br><span class="gradient-text">Professional</span> For<br>Every Event</h1>
        <p class="hero-subtitle">
            GigResource connects event organizers with verified professionals. Book photographers, DJs, caterers,
            decorators, and more &mdash; all in one platform.
        </p>
        <div class="hero-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-blue btn-lg">Join as Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-red btn-lg">Hire Now</a>
            @endauth
        </div>

        <div class="trust-badges">
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h4>Verified Experts</h4>
                <p>Vetted professionals only</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <h4>Secure Payments</h4>
                <p>Safe & trusted transactions</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h4>Event Categories</h4>
                <p>Browse all types of events</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h4>24/7 Support</h4>
                <p>We're here to help anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- ─── ABOUT US ─────────────────────────────── -->
<section class="section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h3>About Us</h3>
                <h2>We Connect <span class="gradient-text">Talent</span> With Opportunity</h2>
                <p>
                    GigResource is a next-generation marketplace designed to bridge the gap between skilled event
                    professionals and clients who need them. Whether you're planning a wedding, corporate event,
                    or private celebration, we make it effortless to find, book, and collaborate with top-tier talent.
                </p>
                <p>
                    Our platform handles everything from discovery to secure payments, real-time messaging,
                    and professional service agreements &mdash; so you can focus on what matters: creating
                    unforgettable experiences.
                </p>
                <div class="about-stats">
                    <div class="about-stat">
                        <h4>500+</h4>
                        <p>Professionals</p>
                    </div>
                    <div class="about-stat">
                        <h4>1,200+</h4>
                        <p>Events Booked</p>
                    </div>
                    <div class="about-stat">
                        <h4>98%</h4>
                        <p>Satisfaction</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&q=80" alt="Event planning team" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ─── HOW IT WORKS ──────────────────────────── -->
<section class="section" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2>Getting Started is Easy</h2>
            <p>A simple, transparent process for planners and professionals.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <h3>Post Your Event</h3>
                <p>Describe your event, set dates, and specify the professionals you need.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Choose Professionals</h3>
                <p>Browse verified profiles, compare rates, and select the perfect match for your event.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h3>Book Safely</h3>
                <p>Confirm your booking with secure payments and real-time chat with your team.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 48px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Join as Professional</a>
                <a href="{{ route('register') }}" class="btn btn-outline btn-lg">Hire a Professional</a>
            @endauth
        </div>
    </div>
</section>

<!-- ─── CTA BANNER ────────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-content">
                <h2>Become a {{ config('app.name', 'Khadija') }} Professional</h2>
                <p>Partner with a leading platform, help others create amazing events, and earn competitive commissions for every successful referral.</p>
                <ul class="cta-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Earning Potential — Set your own rates
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Simple Tracking & Payments
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Grow your client base organically
                    </li>
                </ul>
                <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn btn-primary btn-lg">Start Today</a>
            </div>
            <div class="cta-image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
        </div>
    </div>
</section>

<!-- ─── PRICING ───────────────────────────────── -->
<section class="section section-alt" id="pricing">
    <div class="container">
        <div class="section-header">
            <h2>Flexible Pricing for Every Need</h2>
            <p>Choose the perfect plan to launch your events to the next level.</p>
        </div>

        <div class="pricing-tabs">
            <div class="pricing-tab active">For Professionals</div>
            <div class="pricing-tab">For Clients</div>
        </div>

        <div class="pricing-toggle">
            <span class="toggle-label active" id="monthlyLabel">Monthly</span>
            <div class="toggle-switch" id="billingToggle" onclick="this.classList.toggle('yearly')"></div>
            <span class="toggle-label" id="yearlyLabel">Yearly</span>
            <span class="pricing-save">Save 15%</span>
        </div>

        <div class="pricing-grid">
            @php
                $planIcons = [
                    0 => ['bg' => 'rgba(107,114,128,0.15)', 'color' => '#9ca3af'],
                    1 => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                    2 => ['bg' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6'],
                    3 => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                ];
            @endphp

            @foreach($plans as $index => $plan)
                @php
                    $icon = $planIcons[$index % 4];
                @endphp
                <div class="pricing-card {{ $plan->is_featured ? 'featured' : '' }}">
                    @if($plan->badge_text)
                        <div class="pricing-badge badge-{{ $plan->badge_color ?? 'primary' }}">{{ $plan->badge_text }}</div>
                    @endif

                    <div class="pricing-card-icon" style="background: {{ $icon['bg'] }};">
                        @if($index === 0)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        @elseif($index === 1)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        @elseif($index === 2)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/><circle cx="12" cy="12" r="3"/></svg>
                        @endif
                    </div>

                    <div class="pricing-plan-name">{{ $plan->name }}</div>
                    <div class="pricing-plan-desc">{{ $plan->description ?? 'Perfect for your needs' }}</div>

                    <div class="pricing-amount">
                        <span class="pricing-currency">$</span>
                        <span class="pricing-value">{{ intval($plan->price) }}</span>
                        @if(!$plan->isFree())
                            <span class="pricing-cycle">{{ $plan->billingLabel() }}</span>
                        @endif
                    </div>

                    <ul class="pricing-features">
                        @if($plan->max_events)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_events }} events
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited events
                            </li>
                        @endif
                        @if($plan->max_bookings)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_bookings }} bookings
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited bookings
                            </li>
                        @endif
                        @foreach($plan->features as $feature)
                            <li class="{{ !$feature->is_included ? 'excluded' : '' }}">
                                @if($feature->is_included)
                                    <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    <svg class="cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                @endif
                                {{ $feature->feature }}
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <a href="{{ route('app.membership-plans.index') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ─── TESTIMONIALS ──────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2>Trusted by Planners & Professionals</h2>
            <p>Here's what our community says about {{ config('app.name', 'Khadija') }}.</p>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"{{ config('app.name') }} revolutionized how I manage events. It's intuitive, fast, and I found the perfect photographer for a last-minute wedding."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">S</div>
                    <div>
                        <div class="testimonial-author-name">Sarah K.</div>
                        <div class="testimonial-author-role">Wedding Planner</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"The quality of professionals here is unmatched. The hiring process is as fair as it can get and it was flawless from start to finish."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">M</div>
                    <div>
                        <div class="testimonial-author-name">Mike R.</div>
                        <div class="testimonial-author-role">Corporate Event Manager</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"As a DJ, I've doubled my bookings since joining. The platform makes it easy to showcase my work and connect with clients directly."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">A</div>
                    <div>
                        <div class="testimonial-author-name">Ahmed J.</div>
                        <div class="testimonial-author-role">Professional DJ & Musician</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── FAQ ───────────────────────────────────── -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-header">
            <h2>Frequently Asked <span class="gradient-text">Questions</span></h2>
            <p>Everything you need to know about using GigResource.</p>
        </div>
        <div class="faq-grid">
            @forelse($faqs as $faq)
                <div class="faq-item {{ $loop->first ? 'active' : '' }}">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ $faq->question }}</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">{!! $faq->answer !!}</div>
                    </div>
                </div>
            @empty
                {{-- Fallback if no FAQs in database yet --}}
                <div class="faq-item active">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>How does GigResource work?</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            GigResource connects event organizers (clients) with verified service professionals (suppliers). Simply create an account, browse available professionals by category, send booking requests, discuss details through our built-in chat, and confirm your booking.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- ─── NEWSLETTER ─────────────────────────────── -->
<section class="section section-alt newsletter">
    <div class="container">
        <h2>Get Eventful Updates!</h2>
        <p>Subscribe to our newsletter for the latest industry news, planning tips, and exclusive offers.</p>
        <div class="newsletter-form">
            <input type="email" placeholder="Enter your email address">
            <button class="btn btn-primary">Subscribe</button>
        </div>
    </div>
</section>

<!-- ─── FOOTER ─────────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 32px;"></div>
                <p class="footer-desc">
                    Connecting Professionals & Clients for Perfect Events.
                    Create unforgettable experiences with our curated network of verified experts.
                </p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/gigresource/" target="_blank" class="footer-social" title="Facebook">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/gigresource2025/" target="_blank" class="footer-social" title="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="https://www.tiktok.com/@gigresource123/" target="_blank" class="footer-social" title="TikTok">
                        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.71a8.21 8.21 0 0 0 4.76 1.52V6.69h-1z"/></svg>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <ul>
                    <li><a href="{{ route('about-us') }}">About Us</a></li>
                    <li><a href="{{ route('events-categories') }}">Events & Categories</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Get Started</h4>
                <ul>
                    @guest
                        <li><a href="{{ route('register') }}">Join as Professional</a></li>
                        <li><a href="{{ route('register') }}">Hire Talent</a></li>
                        <li><a href="{{ route('login') }}">Log In</a></li>
                    @else
                        <li><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    @endguest
                </ul>
            </div>
            <div class="footer-col">
                <h4>Policies</h4>
                <ul>
                    <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('payment-policy') }}">Payment Policy</a></li>
                    <li><a href="{{ route('cancellation-policy') }}">Cancellation & Refund</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} GigResource. All rights reserved.</span>
            <span>
                <a href="{{ route('privacy-policy') }}" style="color: var(--text-muted);">Privacy</a> &middot;
                <a href="{{ route('payment-policy') }}" style="color: var(--text-muted);">Payment</a> &middot;
                <a href="{{ route('cancellation-policy') }}" style="color: var(--text-muted);">Cancellation</a>
            </span>
        </div>
    </div>
</footer>

<script>
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Pricing tab switching (visual only for now)
    document.querySelectorAll('.pricing-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.pricing-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // FAQ accordion
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const isActive = item.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        if (!isActive) item.classList.add('active');
    }

    // Close join dropdown on outside click
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('joinDropdown');
        if (dd && !dd.contains(e.target)) dd.classList.remove('open');
    });
</script>

</body>
</html>
