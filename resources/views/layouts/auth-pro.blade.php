@php
    // Role drives the theme + left-panel content. Set in each page via
    // @section('apRole', 'client'|'supplier'|'influencer'|'login').
    $apRole = trim($__env->yieldContent('apRole')) ?: 'client';

    $apThemes = [
        'client' => [
            'accent' => '#f97316', 'accentDark' => '#ea580c', 'soft' => '#fff3ea',
            'pageBg' => 'linear-gradient(180deg,#fdf6f0 0%,#fbf0e7 100%)',
            'panelBg' => '#fdeee2',
            'label'  => 'Create Your Client Account',
            'tagline'=> 'Discover amazing events, connect with top professionals, and bring your vision to life.',
            'benefits' => [
                ['cal',   'Find Amazing Events',     'Browse a wide range of events tailored to your interests and needs.'],
                ['star',  'Verified Professionals',  'Connect with trusted, reviewed, and highly-rated event professionals.'],
                ['shield','Secure & Reliable',       'Your data and payments are protected with enterprise-grade security.'],
                ['head',  'Dedicated Support',       'Our support team is here to help you every step of the way.'],
            ],
        ],
        'supplier' => [
            'accent' => '#2563eb', 'accentDark' => '#1d4ed8', 'soft' => '#eaf1ff',
            'pageBg' => 'linear-gradient(180deg,#f3f7ff 0%,#eaf1fe 100%)',
            'panelBg' => '#eef4ff',
            'label'  => 'Create Your Professional Account',
            'tagline'=> 'Connect with event planners, showcase your services, and grow your business.',
            'benefits' => [
                ['rocket','Find More Opportunities', 'Get discovered by clients looking for professionals like you.'],
                ['wallet','Grow Your Business',      'Receive leads, pitch for jobs, and expand your network.'],
                ['shield','Trusted & Secure',        'Your information is safe with us. We protect your data.'],
                ['head',  'Dedicated Support',       'Our team is here to help you succeed every step of the way.'],
            ],
        ],
        'influencer' => [
            'accent' => '#f97316', 'accentDark' => '#ea580c', 'soft' => '#fff3ea',
            'pageBg' => 'linear-gradient(180deg,#fdf6f0 0%,#fbf0e7 100%)',
            'panelBg' => '#fdeee2',
            'label'  => 'Create Your Influencer (Affiliate) Account',
            'tagline'=> 'Monetize your influence, promote amazing events, and earn commissions doing what you love.',
            'benefits' => [
                ['mega',  'Promote Amazing Events',     'Choose from a wide variety of events that align with your audience.'],
                ['chart', 'Earn Attractive Commissions','Earn competitive commissions for every successful referral.'],
                ['link',  'Easy Tracking & Payouts',    'Track your clicks, referrals, and earnings in real-time with easy payouts.'],
                ['head',  'Dedicated Affiliate Support','Our affiliate team is here to help you grow and succeed.'],
            ],
        ],
        'login' => [
            'accent' => '#f97316', 'accentDark' => '#ea580c', 'soft' => '#fff3ea',
            'pageBg' => 'linear-gradient(180deg,#fdf6f0 0%,#fbf0e7 100%)',
            'panelBg' => '#fdeee2',
            'label'  => 'Welcome Back',
            'tagline'=> 'Sign in to manage your events, bookings, and connections — all in one place.',
            'benefits' => [
                ['cal',   'Pick Up Where You Left Off', 'Jump straight back into your events, bookings and messages.'],
                ['star',  'Trusted Marketplace',        'Thousands of verified clients and professionals in one place.'],
                ['shield','Secure & Reliable',          'Your data and payments are protected with enterprise-grade security.'],
                ['head',  'Dedicated Support',          'Our support team is here to help you every step of the way.'],
            ],
        ],
    ];
    $ap = $apThemes[$apRole] ?? $apThemes['client'];

    // Inline icon set used by the benefit rows + trust band.
    $apIcon = function ($k) {
        return [
            'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            'star'   => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'head'   => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
            'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
            'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
            'mega'   => '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
            'chart'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'link'   => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        ][$k] ?? '';
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="--ap-accent: {{ $ap['accent'] }}; --ap-accent-dark: {{ $ap['accentDark'] }}; --ap-soft: {{ $ap['soft'] }};">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GigResource') — GigResource</title>
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink: #0f1b35; --text: #334155; --muted: #64748b; --line: #e7ebf2;
            --ff: 'Plus Jakarta Sans', system-ui, sans-serif;
            --ff-body: 'Inter', system-ui, sans-serif;
        }
        body {
            font-family: var(--ff-body); color: var(--text);
            background: {{ $ap['pageBg'] }}; min-height: 100vh;
            display: flex; flex-direction: column;
        }
        a { text-decoration: none; color: inherit; }

        /* ── Top bar ── */
        .apx-top {
            display: flex; align-items: center; justify-content: space-between;
            max-width: 1280px; width: 100%; margin: 0 auto; padding: 22px 32px;
        }
        .apx-logo img { height: 38px; display: block; }
        .apx-top-right { font-size: 14px; color: var(--muted); }
        .apx-top-right a { color: var(--ap-accent); font-weight: 700; margin-left: 6px; }
        .apx-top-right a:hover { text-decoration: underline; }

        /* ── Layout ── */
        .apx-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 8px 24px 48px; }
        .apx-grid {
            display: grid; grid-template-columns: minmax(0,1.05fr) minmax(0,1fr);
            gap: 28px; max-width: 1180px; width: 100%; align-items: stretch;
        }
        @media (max-width: 940px) {
            .apx-grid { grid-template-columns: 1fr; max-width: 560px; }
            .apx-left { display: none; }
        }

        /* ── Left panel ── */
        .apx-left {
            background: {{ $ap['panelBg'] }};
            border: 1px solid var(--line); border-radius: 24px;
            padding: 40px 38px 28px; display: flex; flex-direction: column;
        }
        .apx-left h1 { font-family: var(--ff); font-size: 30px; font-weight: 800; color: var(--ink); line-height: 1.15; }
        .apx-tagline { font-size: 15px; color: var(--muted); margin: 12px 0 26px; max-width: 30ch; line-height: 1.55; }
        .apx-benefit { display: flex; gap: 14px; margin-bottom: 18px; align-items: flex-start; }
        .apx-bicon {
            width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
            background: var(--ap-soft); display: flex; align-items: center; justify-content: center;
            color: var(--ap-accent);
        }
        .apx-bicon svg { width: 22px; height: 22px; }
        .apx-btext b { display: block; font-family: var(--ff); font-size: 15.5px; font-weight: 700; color: var(--ink); margin-bottom: 2px; }
        .apx-btext span { font-size: 13px; color: var(--muted); line-height: 1.5; }

        /* illustration band */
        .apx-illo { margin-top: auto; padding-top: 18px; }
        .apx-illo svg { width: 100%; height: auto; display: block; }

        /* trust band */
        .apx-trust {
            margin-top: 18px; background: #fff; border: 1px solid var(--line);
            border-radius: 18px; padding: 18px 14px;
            display: grid; grid-template-columns: repeat(4,1fr); gap: 8px;
        }
        .apx-trust-item { text-align: center; padding: 0 4px; }
        .apx-trust-ic {
            width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .apx-trust-ic svg { width: 20px; height: 20px; }
        .apx-trust-item b { display: block; font-family: var(--ff); font-size: 12.5px; font-weight: 700; color: var(--ink); line-height: 1.25; }
        .apx-trust-item span { display: block; font-size: 10.5px; color: var(--muted); line-height: 1.35; margin-top: 4px; }

        /* ── Right (form) card ── */
        .apx-card {
            background: #fff; border: 1px solid var(--line); border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15,27,53,.06);
            padding: 38px 40px; display: flex; flex-direction: column; justify-content: center;
        }
        .apx-card h2 { font-family: var(--ff); font-size: 24px; font-weight: 800; color: var(--ink); }
        .apx-card-sub { font-size: 14px; color: var(--muted); margin: 4px 0 24px; }

        /* OAuth */
        .apx-oauth { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .apx-oauth-btn {
            display: flex; align-items: center; justify-content: center; gap: 9px;
            padding: 12px; border: 1.5px solid var(--line); border-radius: 12px;
            background: #fff; font-family: var(--ff); font-size: 14px; font-weight: 600; color: var(--ink);
            cursor: pointer; position: relative; transition: border-color .15s, background .15s;
        }
        .apx-oauth-btn:hover { border-color: #cbd5e1; background: #f8fafc; }
        .apx-oauth-btn[disabled] { cursor: not-allowed; opacity: .9; }
        .apx-oauth-btn svg { width: 18px; height: 18px; }
        .apx-soon {
            position: absolute; top: -8px; right: 8px; font-size: 9px; font-weight: 700;
            background: var(--ap-soft); color: var(--ap-accent-dark); padding: 2px 7px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: .04em;
        }
        @media (max-width: 480px) { .apx-oauth { grid-template-columns: 1fr; } }

        .apx-or { display: flex; align-items: center; gap: 14px; margin: 20px 0; color: var(--muted); font-size: 12px; font-weight: 600; }
        .apx-or::before, .apx-or::after { content: ''; flex: 1; height: 1px; background: var(--line); }

        /* form fields */
        .apx-field { margin-bottom: 16px; }
        .apx-label { display: block; font-family: var(--ff); font-size: 13.5px; font-weight: 700; color: var(--ink); margin-bottom: 7px; }
        .apx-input-wrap { position: relative; }
        .apx-ic-left {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: var(--muted); pointer-events: none;
        }
        .apx-input, .apx-select, .apx-textarea {
            width: 100%; padding: 13px 14px 13px 42px; border: 1.5px solid var(--line);
            border-radius: 12px; font-family: var(--ff-body); font-size: 14px; color: var(--ink);
            background: #fff; transition: border-color .15s, box-shadow .15s;
        }
        .apx-input.no-ic { padding-left: 14px; }
        .apx-textarea { padding-left: 14px; resize: vertical; min-height: 92px; }
        .apx-input::placeholder, .apx-textarea::placeholder { color: #9aa6b8; }
        .apx-input:focus, .apx-select:focus, .apx-textarea:focus {
            outline: none; border-color: var(--ap-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ap-accent) 16%, transparent);
        }
        .apx-input.is-invalid, .apx-select.is-invalid { border-color: #ef4444; }
        .apx-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted); cursor: pointer; padding: 4px;
        }
        .apx-eye:hover { color: var(--ink); }
        .apx-hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .apx-err { color: #ef4444; font-size: 12.5px; margin-top: 6px; }

        /* phone row */
        .apx-phone { display: grid; grid-template-columns: 88px 1fr; gap: 10px; }
        .apx-cc {
            display: flex; align-items: center; gap: 6px; padding: 13px 10px;
            border: 1.5px solid var(--line); border-radius: 12px; background: #fff; font-size: 14px; color: var(--ink);
        }
        .apx-note {
            display: flex; align-items: center; gap: 8px; margin-top: 8px;
            background: var(--ap-soft); border-radius: 10px; padding: 9px 12px;
            font-size: 12.5px; color: var(--ap-accent-dark);
        }
        .apx-note svg { width: 15px; height: 15px; flex-shrink: 0; }

        /* checkbox */
        .apx-agree { display: flex; align-items: flex-start; gap: 9px; margin: 6px 0 18px; font-size: 13px; color: var(--text); }
        .apx-agree input { width: 16px; height: 16px; margin-top: 2px; accent-color: var(--ap-accent); flex-shrink: 0; }
        .apx-agree a { color: var(--ap-accent); font-weight: 600; }
        .apx-agree a:hover { text-decoration: underline; }

        /* submit */
        .apx-submit {
            width: 100%; padding: 14px; border: none; border-radius: 12px;
            background: var(--ap-accent); color: #fff; font-family: var(--ff);
            font-size: 15px; font-weight: 700; cursor: pointer; transition: background .15s, transform .1s;
        }
        .apx-submit:hover { background: var(--ap-accent-dark); transform: translateY(-1px); }

        .apx-foot { text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); }
        .apx-foot a { color: var(--ap-accent); font-weight: 600; }
        .apx-foot a:hover { text-decoration: underline; }

        .apx-row-between { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }

        /* alerts */
        .apx-alert { padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 18px; }
        .apx-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .apx-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }

        @media (max-width: 560px) { .apx-card { padding: 28px 22px; } .apx-top { padding: 18px 20px; } }
    </style>
    @stack('head')
</head>
<body>
    <div class="apx-top">
        <a href="{{ url('/') }}" class="apx-logo"><img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource"></a>
        <div class="apx-top-right">
            @yield('top-right')
        </div>
    </div>

    <div class="apx-wrap">
        <div class="apx-grid">
            {{-- Left role panel --}}
            <div class="apx-left">
                <h1>Join Event Pro Marketplace</h1>
                <p class="apx-tagline">{{ $ap['tagline'] }}</p>

                @foreach($ap['benefits'] as [$ic, $title, $desc])
                    <div class="apx-benefit">
                        <div class="apx-bicon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $apIcon($ic) !!}</svg></div>
                        <div class="apx-btext"><b>{{ $title }}</b><span>{{ $desc }}</span></div>
                    </div>
                @endforeach

                {{-- trust band --}}
                <div class="apx-trust">
                    <div class="apx-trust-item">
                        <div class="apx-trust-ic" style="background:#fef3c7;color:#d97706;"><svg viewBox="0 0 24 24" fill="currentColor">{!! $apIcon('star') !!}</svg></div>
                        <b>Trusted by Global Brands</b>
                    </div>
                    <div class="apx-trust-item">
                        <div class="apx-trust-ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $apIcon('rocket') !!}</svg></div>
                        <b>Fast Campaign Launches</b>
                    </div>
                    <div class="apx-trust-item">
                        <div class="apx-trust-ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="currentColor">{!! $apIcon('shield') !!}</svg></div>
                        <b>Secure Payments</b>
                    </div>
                    <div class="apx-trust-item">
                        <div class="apx-trust-ic" style="background:#dbeafe;color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">{!! $apIcon('chart') !!}</svg></div>
                        <b>Real-Time Analytics</b>
                    </div>
                </div>
            </div>

            {{-- Right form card --}}
            <div class="apx-card">
                @yield('auth_form')
            </div>
        </div>
    </div>

    <script>
        // Generic password show/hide for any [data-eye] toggle.
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-eye]');
            if (!b) return;
            var inp = document.getElementById(b.getAttribute('data-eye'));
            if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
        });
    </script>
    @stack('scripts')
    @include('partials._form_validation')
</body>
</html>
