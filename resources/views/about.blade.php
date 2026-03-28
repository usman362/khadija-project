<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - {{ config('app.name', 'GigResource') }}</title>
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

        /* ─── NAVBAR (same as landing) ──────────────────────────── */
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

        .mobile-menu-btn {
            display: none;
            background: transparent;
            color: #fff;
            font-size: 1.5rem;
            padding: 8px;
        }

        /* ─── ABOUT PAGE CONTENT ──────────────────────────── */
        .about-hero {
            padding: 120px 0 48px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .about-hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .about-hero h1 span {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-hero p {
            color: var(--text-muted);
            font-size: 1.125rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .about-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        .intro-text {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 40px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border-color);
            line-height: 1.7;
        }

        .step-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            transition: border-color 0.3s;
        }

        .step-card:hover {
            border-color: rgba(59, 130, 246, 0.3);
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
        }

        .step-card h2 {
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-white);
        }

        .step-detail {
            margin-bottom: 16px;
        }

        .step-detail:last-child {
            margin-bottom: 0;
        }

        .step-detail strong {
            color: var(--primary);
        }

        .step-detail p {
            color: var(--text-light);
            margin: 0;
            line-height: 1.7;
        }

        .closing-section {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 12px;
            padding: 32px;
            margin-top: 40px;
            text-align: center;
        }

        .closing-section p {
            color: var(--text-light);
            font-size: 1.05rem;
            margin: 0;
            line-height: 1.7;
        }

        /* ─── FOOTER (same as landing) ──────────────────────────── */
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 60px 0 32px;
            background: #060912;
            margin-top: 0;
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
            color: var(--text-light);
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

        /* ─── RESPONSIVE ──────────────────────────── */
        @media (max-width: 1024px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .navbar-links { display: none; }
            .navbar-actions .btn-blue, .navbar-actions .btn-red { display: none; }
            .mobile-menu-btn { display: block; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
            .about-hero h1 { font-size: 2rem; }
            .about-hero { padding: 100px 0 32px; }
            .step-card { padding: 24px; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ───────────────────────────────── -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="GigResource" style="height: 36px;"></a>

        <ul class="navbar-links">
            <li><a href="{{ route('about-us') }}" style="color: var(--text-white);">About Us</a></li>
            <li><a href="/#features">Features</a></li>
            <li><a href="/#how-it-works">How It Works</a></li>
            <li><a href="/#pricing">Pricing</a></li>
            <li><a href="/#faq">FAQ</a></li>
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

<!-- ─── HERO ───────────────────────────────── -->
<section class="about-hero">
    <div class="container">
        <h1>About <span>GigResource</span></h1>
        <p>Creating seamless interactions between clients, professionals, and influencers in the event planning industry.</p>
    </div>
</section>

<!-- ─── CONTENT ───────────────────────────────── -->
<div class="about-main">
    <p class="intro-text">
        The GigResource platform is designed to create seamless interactions between clients, professionals, and GigResource Influencers. Here's a step-by-step breakdown of how the system works:
    </p>

    <div class="step-card">
        <div class="step-number">1</div>
        <h2>Client Discovery</h2>
        <div class="step-detail">
            <p><strong>Exploration:</strong> Clients visit the GigResource platform to explore an extensive range of professionals, including DJs, caterers, photographers, makeup artists, and event planners.</p>
        </div>
        <div class="step-detail">
            <p><strong>Comparison:</strong> The platform allows clients to compare different vendors based on services offered, pricing, reviews, and availability, ensuring they make well-informed decisions.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">2</div>
        <h2>Professional Registration</h2>
        <div class="step-detail">
            <p><strong>Sign-Up Process:</strong> Professionals sign up on the GigResource platform, creating comprehensive profiles showcasing their services, experience, and portfolios.</p>
        </div>
        <div class="step-detail">
            <p><strong>Verification:</strong> Each professional undergoes a verification process to ensure quality and reliability, building trust with potential clients.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">3</div>
        <h2>Influencer Promotion</h2>
        <div class="step-detail">
            <p><strong>Referral Links:</strong> GigResource Influencers receive unique referral links that track their promotional activities. They share these links through various channels such as social media, email campaigns, and personal networks to attract new clients and professionals to the platform.</p>
        </div>
        <div class="step-detail">
            <p><strong>Earnings:</strong> Influencers earn commissions for each successful sign-up (both clients and professionals) made through their referral links, creating an incentive for them to actively promote the GigResource platform.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">4</div>
        <h2>Matching and Booking</h2>
        <div class="step-detail">
            <p><strong>Client Sign-Up:</strong> After discovering a suitable vendor, clients can sign up for GigResource, accessing the dashboard where they can book vendors directly.</p>
        </div>
        <div class="step-detail">
            <p><strong>Direct Communication:</strong> Clients can communicate with professionals through the platform to discuss event details, ask questions, and finalize agreements.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">5</div>
        <h2>Event Execution</h2>
        <div class="step-detail">
            <p><strong>Collaboration:</strong> Once a booking is confirmed, professionals work directly with clients to plan and execute the event as per the client's specifications, ensuring all needs and expectations are met.</p>
        </div>
        <div class="step-detail">
            <p><strong>Support System:</strong> Throughout the planning process, GigResource provides support to both clients and professionals to address any questions or concerns, facilitating a smooth event experience.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">6</div>
        <h2>Feedback and Growth</h2>
        <div class="step-detail">
            <p><strong>Review System:</strong> After the event, clients are encouraged to leave reviews and ratings for the professionals they hired. This feedback helps future clients make informed decisions and assists professionals in building their reputations.</p>
        </div>
        <div class="step-detail">
            <p><strong>Continuous Improvement:</strong> GigResource utilises feedback from both clients and professionals to continuously enhance the platform, making it more user-friendly and effective for all participants.</p>
        </div>
    </div>

    <div class="step-card">
        <div class="step-number">7</div>
        <h2>Earnings for Influencers</h2>
        <div class="step-detail">
            <p><strong>Commission Payout:</strong> Influencers receive payments for their earned commissions once the referred clients or professionals meet the eligibility criteria, substantiating the connections made through their promotional efforts.</p>
        </div>
        <div class="step-detail">
            <p><strong>Tracking Performance:</strong> Influencers can log into their dashboard at any time to monitor their performance stats, earnings, and the status of their referral activities, empowering them to optimize their marketing strategies.</p>
        </div>
    </div>

    <div class="closing-section">
        <p>Through this comprehensive system, GigResource effectively connects clients with top-tier service providers while enabling Influencers to earn income as they contribute to the growth and success of the event planning community.</p>
    </div>
</div>

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
                    <li><a href="/#features">Features</a></li>
                    <li><a href="/#how-it-works">How It Works</a></li>
                    <li><a href="/#pricing">Pricing</a></li>
                    <li><a href="/#faq">FAQ</a></li>
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

</body>
</html>
