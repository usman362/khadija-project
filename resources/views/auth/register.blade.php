@php
    // Active role: keep the user's chosen role across a validation-error redirect
    // (old('role')) so the "I am a..." selector doesn't silently reset to Client;
    // otherwise from ?role= (e.g. "Join as a professional" link); else Client.
    $active = old('role', $role ?? 'client');
    if (! in_array($active, ['client', 'supplier', 'influencer'], true)) {
        $active = 'client';
    }

    $recaptchaSettings = app(\App\Domain\Settings\Services\SettingsService::class);
    $showRecaptcha     = $recaptchaSettings->isRecaptchaEnabledFor('register');
    $recaptchaSiteKey  = $recaptchaSettings->getRecaptchaSiteKey();
    $recaptchaVersion  = $recaptchaSettings->get('recaptcha.version', 'v2');

    // Role chips — each carries its own brand colour; the active one is filled.
    $roles = [
        'supplier'   => ['label' => 'Professional', 'c' => '#2563eb', 'cd' => '#1d4ed8', 'soft' => '#eff4ff'],
        'client'     => ['label' => 'Client',       'c' => '#f97316', 'cd' => '#ea580c', 'soft' => '#fff3ea'],
        'influencer' => ['label' => 'Influencer',   'c' => '#ec4899', 'cd' => '#db2777', 'soft' => '#fdf0f7'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Your Account — GigResource</title>
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if($showRecaptcha && $recaptchaSiteKey)
        @if($recaptchaVersion === 'v3')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
        @else
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif
    @endif
    <style>
        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:#0f1b35; --text:#334155; --muted:#64748b; --line:#e7ebf2;
            --brand:#2563eb; --brand-dark:#1d4ed8; --brand-soft:#eff4ff;
            --accent:#f97316; --accent-dark:#ea580c; --accent-soft:#fff3ea;
            --ff:'Plus Jakarta Sans', system-ui, sans-serif;
            --ff-body:'Inter', system-ui, sans-serif;
        }
        body { font-family:var(--ff-body); color:var(--text); background:#fff; min-height:100vh; }
        a { text-decoration:none; color:inherit; }

        .rg-page { display:grid; grid-template-columns:minmax(0,0.92fr) minmax(0,1.08fr); min-height:100vh; }

        /* ── Left marketing panel ── */
        .rg-left {
            position:relative; overflow:hidden; padding:44px 52px;
            background:linear-gradient(155deg,#fdf1ea 0%,#fdeef2 55%,#f6effb 100%);
            display:flex; flex-direction:column;
        }
        .rg-blob { position:absolute; border-radius:50%; filter:blur(6px); opacity:.5; pointer-events:none; }
        .rg-blob.b1 { width:180px; height:180px; left:-60px; bottom:-40px; background:radial-gradient(circle at 30% 30%, #f9731688, transparent 70%); }
        .rg-blob.b2 { width:150px; height:150px; left:60px; bottom:-70px; background:radial-gradient(circle at 30% 30%, #ec489988, transparent 70%); }
        .rg-dots { position:absolute; top:150px; left:18px; display:grid; grid-template-columns:repeat(5,6px); gap:9px; opacity:.5; }
        .rg-dots i { width:6px; height:6px; border-radius:50%; background:#c7b6e8; display:block; }
        .rg-dots-w { position:absolute; bottom:34px; left:44px; display:grid; grid-template-columns:repeat(6,5px); gap:8px; }
        .rg-dots-w i { width:5px; height:5px; border-radius:50%; background:#ffffffcc; display:block; }

        .rg-brand { display:flex; align-items:center; gap:11px; margin-bottom:44px; position:relative; z-index:2; }
        .rg-brand img { height:40px; display:block; }

        .rg-hero { position:relative; z-index:2; }
        .rg-hero h1 { font-family:var(--ff); font-size:44px; line-height:1.08; font-weight:800; color:var(--ink); letter-spacing:-.5px; }
        .rg-hero h1 .grad { background:linear-gradient(90deg,#2563eb,#7c3aed 55%,#ec4899); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
        .rg-slogan { font-family:var(--ff); font-size:17px; font-weight:800; color:var(--ink); margin:22px 0 8px; }
        .rg-sub { font-size:15px; color:#5b6b86; line-height:1.55; max-width:34ch; }
        .rg-div { width:96px; height:5px; border-radius:5px; margin:22px 0 30px; background:linear-gradient(90deg,#f97316,#ec4899 55%,#7c3aed); }

        .rg-feat { display:flex; gap:15px; align-items:flex-start; margin-bottom:24px; position:relative; z-index:2; }
        .rg-fic { width:52px; height:52px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:#fff; box-shadow:0 8px 20px -8px rgba(15,27,53,.5); }
        .rg-fic svg { width:23px; height:23px; }
        .rg-ft b { display:block; font-family:var(--ff); font-size:16px; font-weight:700; color:var(--ink); margin-bottom:3px; }
        .rg-ft span { font-size:13.5px; color:#5b6b86; line-height:1.5; max-width:38ch; display:block; }

        /* ── Right form side ── */
        .rg-right { position:relative; display:flex; flex-direction:column; padding:26px 60px 44px; overflow:hidden; }
        .rg-right::before { content:''; position:absolute; inset:0; background:linear-gradient(180deg,#f7f9fd 0%,#fff 22%); z-index:0; }
        .rg-topbar { position:relative; z-index:2; display:flex; justify-content:flex-end; font-size:14px; color:var(--muted); margin-bottom:8px; }
        .rg-topbar a { color:var(--brand); font-weight:700; margin-left:6px; }
        .rg-topbar a:hover { text-decoration:underline; }

        .rg-card { position:relative; z-index:2; width:100%; max-width:720px; margin:0 auto; }
        .rg-h2 { font-family:var(--ff); font-size:30px; font-weight:800; color:var(--ink); letter-spacing:-.4px; }
        .rg-h2-sub { font-size:15px; color:var(--muted); margin:6px 0 22px; }

        /* OAuth */
        .rg-oauth { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .rg-oauth-btn { position:relative; display:flex; align-items:center; justify-content:center; gap:10px; padding:14px; border:1.5px solid var(--line); border-radius:12px; background:#fff; font-family:var(--ff); font-size:14.5px; font-weight:600; color:var(--ink); cursor:not-allowed; }
        .rg-oauth-btn svg { width:19px; height:19px; }
        .rg-soon { position:absolute; top:-9px; right:14px; font-size:9px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; background:#fef3e6; color:#ea580c; padding:2px 8px; border-radius:20px; }
        .rg-or { display:flex; align-items:center; gap:14px; margin:20px 0; color:var(--muted); font-size:13px; }
        .rg-or::before,.rg-or::after { content:''; flex:1; height:1px; background:var(--line); }

        /* role toggle */
        .rg-iam { font-family:var(--ff); font-size:14px; font-weight:700; color:var(--ink); margin-bottom:9px; }
        .rg-roles { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:22px; }
        .rg-role { display:flex; align-items:center; justify-content:center; gap:9px; padding:15px; border-radius:12px; border:1.6px solid var(--line); background:#fff; font-family:var(--ff); font-size:15px; font-weight:700; cursor:pointer; transition:all .15s; color:var(--ink); }
        .rg-role svg { width:20px; height:20px; }
        .rg-role[aria-pressed="true"] { color:#fff !important; border-color:transparent; box-shadow:0 10px 22px -10px currentColor; }

        /* form grid */
        .rg-form { position:relative; z-index:2; }
        .rg-grid { display:grid; grid-template-columns:1fr 1fr; gap:2px 22px; }
        .rg-field { margin-bottom:16px; }
        .rg-field.full { grid-column:1 / -1; }
        .rg-label { display:block; font-family:var(--ff); font-size:14px; font-weight:700; color:var(--ink); margin-bottom:8px; }
        .rg-wrap { position:relative; }
        .rg-icl { position:absolute; left:15px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#9aa6b8; pointer-events:none; }
        .rg-input,.rg-select { width:100%; padding:14px 15px 14px 44px; border:1.5px solid var(--line); border-radius:12px; font-family:var(--ff-body); font-size:14.5px; color:var(--ink); background:#fff; transition:border-color .15s, box-shadow .15s; }
        .rg-select { padding-left:15px; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 15px center; }
        .rg-input::placeholder { color:#9aa6b8; }
        .rg-input:focus,.rg-select:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 3px color-mix(in srgb, var(--brand) 15%, transparent); }
        .rg-input.is-invalid,.rg-select.is-invalid { border-color:#ef4444; }
        .rg-eye { position:absolute; right:13px; top:50%; transform:translateY(-50%); background:none; border:none; color:#9aa6b8; cursor:pointer; padding:4px; }
        .rg-eye:hover { color:var(--ink); }
        .rg-hint { font-size:12.5px; color:var(--muted); margin-top:7px; }
        .rg-err { color:#ef4444; font-size:12.5px; margin-top:6px; }

        .rg-phone { display:grid; grid-template-columns:92px 1fr; gap:10px; }
        .rg-cc { display:flex; align-items:center; justify-content:center; gap:7px; border:1.5px solid var(--line); border-radius:12px; background:#fff; font-size:14.5px; color:var(--ink); font-weight:600; }
        .rg-phone .rg-input { padding-left:15px; }

        .rg-note { display:flex; align-items:center; gap:9px; margin-top:14px; background:var(--brand-soft); border-radius:11px; padding:12px 14px; font-size:13px; color:var(--brand-dark); grid-column:1 / -1; }
        .rg-note svg { width:16px; height:16px; flex-shrink:0; }

        /* disclaimer */
        .rg-disc { background:#0f1b35; color:#c7d2e4; border-radius:14px; padding:18px 20px; margin:20px 0 18px; font-size:13px; line-height:1.6; }
        .rg-disc b { display:block; font-family:var(--ff); font-size:12px; font-weight:800; letter-spacing:.08em; color:#fff; margin-bottom:6px; }
        .rg-disc a { color:#93b4ff; font-weight:600; }

        .rg-agree { display:flex; align-items:flex-start; gap:10px; margin-bottom:18px; font-size:14px; color:var(--text); }
        .rg-agree input { width:17px; height:17px; margin-top:2px; accent-color:var(--brand); flex-shrink:0; }
        .rg-agree a { color:var(--brand); font-weight:600; }
        .rg-agree a:hover { text-decoration:underline; }

        .rg-submit { width:100%; padding:15px; border:none; border-radius:12px; background:linear-gradient(90deg,#2563eb,#1d4ed8); color:#fff; font-family:var(--ff); font-size:15.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; transition:transform .1s, box-shadow .15s; }
        .rg-submit:hover { transform:translateY(-1px); box-shadow:0 12px 24px -10px rgba(37,99,235,.6); }
        .rg-submit svg { width:18px; height:18px; }

        .rg-foot { text-align:center; margin-top:18px; font-size:13.5px; color:var(--muted); }
        .rg-foot a { color:var(--brand); font-weight:600; }
        .rg-foot a:hover { text-decoration:underline; }

        .rg-alert { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:12px 15px; border-radius:11px; font-size:13.5px; margin-bottom:18px; }
        .rg-alert div { margin:1px 0; }

        @media (max-width: 1080px) { .rg-right { padding:26px 34px 40px; } .rg-left { padding:40px 38px; } }
        @media (max-width: 900px) {
            .rg-page { grid-template-columns:1fr; }
            .rg-left { display:none; }
            .rg-right { padding:22px 22px 40px; }
            .rg-grid { grid-template-columns:1fr; }
            .rg-oauth { grid-template-columns:1fr; }
            .rg-roles { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="rg-page">
    {{-- ── Left marketing panel ── --}}
    <aside class="rg-left">
        <span class="rg-blob b1"></span>
        <span class="rg-blob b2"></span>
        <div class="rg-dots">@for($i=0;$i<20;$i++)<i></i>@endfor</div>
        <div class="rg-dots-w">@for($i=0;$i<18;$i++)<i></i>@endfor</div>

        <div class="rg-brand"><img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource"></div>

        <div class="rg-hero">
            <h1>Join the<br>GigResource <span class="grad">Marketplace</span></h1>
            <div class="rg-slogan">Connect. Create. Celebrate.</div>
            <p class="rg-sub">Find opportunities, hire top professionals, and bring events to life.</p>
            <div class="rg-div"></div>

            <div class="rg-feat">
                <div class="rg-fic" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="rg-ft"><b>Find Amazing Events</b><span>Browse a wide range of events tailored to your interests and needs.</span></div>
            </div>
            <div class="rg-feat">
                <div class="rg-fic" style="background:linear-gradient(135deg,#fb923c,#f97316);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
                <div class="rg-ft"><b>Verified Professionals</b><span>Connect with trusted, reviewed, and highly-rated event professionals.</span></div>
            </div>
            <div class="rg-feat">
                <div class="rg-fic" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div class="rg-ft"><b>Secure &amp; Reliable</b><span>Your data and payments are protected with enterprise-grade security.</span></div>
            </div>
            <div class="rg-feat">
                <div class="rg-fic" style="background:linear-gradient(135deg,#f472b6,#ec4899);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                <div class="rg-ft"><b>Dedicated Support</b><span>Our support team is here to help you every step of the way.</span></div>
            </div>
        </div>
    </aside>

    {{-- ── Right form ── --}}
    <main class="rg-right">
        <div class="rg-topbar">Already have an account? <a href="{{ route('login') }}">Log In</a></div>

        <div class="rg-card">
            <h2 class="rg-h2">Create Your User Account</h2>
            <div class="rg-h2-sub">Fill in your details below to get started.</div>

            <div class="rg-oauth">
                <button type="button" class="rg-oauth-btn" disabled><span class="rg-soon">Soon</span>
                    <svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Continue with Google
                </button>
                <button type="button" class="rg-oauth-btn" disabled><span class="rg-soon">Soon</span>
                    <svg viewBox="0 0 24 24" fill="#000"><path d="M16.36 1.43c.05 1.05-.37 2.06-1.06 2.81-.74.79-1.93 1.4-3.05 1.31-.06-1.05.42-2.12 1.07-2.79.73-.78 2.01-1.36 3.04-1.33zM20.5 17.2c-.55 1.27-.82 1.84-1.53 2.96-.99 1.57-2.39 3.52-4.12 3.53-1.54.02-1.94-1-4.03-.99-2.09.01-2.53 1.01-4.07.99-1.73-.02-3.06-1.78-4.05-3.34C-.04 16.43-.34 11.34 1.36 8.64 2.56 6.71 4.46 5.58 6.25 5.58c1.82 0 2.97 1 4.48 1 1.46 0 2.35-1 4.46-1 1.59 0 3.28.87 4.48 2.37-3.94 2.16-3.3 7.78.83 9.25z"/></svg>
                    Continue with Apple
                </button>
            </div>

            <div class="rg-or">or</div>

            @if ($errors->any())
                <div class="rg-alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif

            <div class="rg-iam">I am a...</div>
            <div class="rg-roles">
                <button type="button" class="rg-role" data-role="supplier" data-c="#2563eb" data-soft="#eff4ff" aria-pressed="{{ $active === 'supplier' ? 'true' : 'false' }}" style="{{ $active==='supplier' ? 'background:#2563eb;color:#fff;border-color:transparent;' : 'color:#2563eb;border-color:#bfd3ff;' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Professional
                </button>
                <button type="button" class="rg-role" data-role="client" data-c="#f97316" data-soft="#fff3ea" aria-pressed="{{ $active === 'client' ? 'true' : 'false' }}" style="{{ $active==='client' ? 'background:#f97316;color:#fff;border-color:transparent;' : 'color:#f97316;border-color:#fdd3b0;' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Client
                </button>
                <button type="button" class="rg-role" data-role="influencer" data-c="#ec4899" data-soft="#fdf0f7" aria-pressed="{{ $active === 'influencer' ? 'true' : 'false' }}" style="{{ $active==='influencer' ? 'background:#ec4899;color:#fff;border-color:transparent;' : 'color:#ec4899;border-color:#f9c1de;' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                    Influencer
                </button>
            </div>

            <form method="POST" action="{{ route('register') }}" id="rgForm" class="rg-form">
                @csrf
                <input type="hidden" name="role" id="rgRole" value="{{ $active }}">

                <div class="rg-grid">
                    <div class="rg-field">
                        <label class="rg-label">Full Name</label>
                        <div class="rg-wrap">
                            <svg class="rg-icl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" name="name" class="rg-input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="rg-field">
                        <label class="rg-label">Email Address</label>
                        <div class="rg-wrap">
                            <svg class="rg-icl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
                            <input type="email" name="email" class="rg-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" placeholder="Enter your email address" required>
                        </div>
                    </div>

                    <div class="rg-field">
                        <label class="rg-label">Password</label>
                        <div class="rg-wrap">
                            <svg class="rg-icl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" name="password" id="rgPw" class="rg-input {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Create a strong password" required>
                            <button type="button" class="rg-eye" data-eye="rgPw" aria-label="Show or hide password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                        <div class="rg-hint">Minimum 8 characters with letters and numbers</div>
                    </div>

                    <div class="rg-field">
                        <label class="rg-label">Confirm Password</label>
                        <div class="rg-wrap">
                            <svg class="rg-icl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" name="password_confirmation" id="rgPw2" class="rg-input" placeholder="Confirm your password" required>
                            <button type="button" class="rg-eye" data-eye="rgPw2" aria-label="Show or hide password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>

                    <div class="rg-field">
                        <label class="rg-label">Phone Number</label>
                        <div class="rg-phone">
                            <div class="rg-cc">🇺🇸 +1</div>
                            <input type="tel" name="phone" class="rg-input {{ $errors->has('phone') ? 'is-invalid' : '' }}" value="{{ old('phone') }}" placeholder="Enter your phone number">
                        </div>
                        @error('phone') <div class="rg-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="rg-field">
                        <label class="rg-label">State</label>
                        <select name="state" class="rg-select {{ $errors->has('state') ? 'is-invalid' : '' }}">
                            <option value="" {{ old('state') ? '' : 'selected' }} disabled>Select your state</option>
                            @foreach(config('geo.allowed_states', []) as $code => $label)
                                <option value="{{ $code }}" {{ old('state') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="rg-hint">Currently serving MD, VA, DC, DE, PA, NJ &amp; NY.</div>
                        @error('state') <div class="rg-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="rg-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        We'll use this number to send you important updates and notifications.
                    </div>
                </div>

                <div class="rg-disc">
                    <b>Before You Continue</b>
                    GigResource is an AI-assisted marketplace that connects clients and professionals. We do not provide expert advice, recommendations, or manage negotiations on your behalf. Support is not available 24/7, and background checks are not guaranteed unless purchased separately. We cannot control how third-party services use information you choose to share, and no membership outcomes are guaranteed. See our <a href="{{ route('platform-disclaimer') }}" target="_blank">Platform Disclaimer</a> for details.
                </div>

                <label class="rg-agree">
                    <input type="checkbox" name="agree" value="1" {{ old('agree') ? 'checked' : '' }} required>
                    <span>I agree to the <a href="{{ route('platform-disclaimer') }}" target="_blank">Terms of Service</a> and <a href="{{ route('privacy-policy') }}" target="_blank">Privacy Policy</a></span>
                </label>

                @if($showRecaptcha && $recaptchaSiteKey)
                    @if($recaptchaVersion === 'v2')
                        <div style="margin-bottom:16px;">
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            @error('g-recaptcha-response') <div class="rg-err">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="g-recaptcha-response" id="rgRecaptcha">
                        @error('g-recaptcha-response') <div class="rg-err">{{ $message }}</div> @enderror
                    @endif
                @endif

                <button type="submit" class="rg-submit" id="rgSubmit">Create Account
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="rg-foot">By creating an account, you agree to our <a href="{{ route('platform-disclaimer') }}">Terms of Service</a> and <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.</div>
            <div class="rg-foot" style="margin-top:8px;">Are you a professional? <a href="#" id="rgProLink">Join as a professional</a></div>
        </div>
    </main>
</div>

<script>
    // Password show / hide.
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-eye]');
        if (!b) return;
        var inp = document.getElementById(b.getAttribute('data-eye'));
        if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
    });

    // Role toggle — sets hidden role + moves the filled/active chip.
    (function () {
        var chips = Array.prototype.slice.call(document.querySelectorAll('.rg-role'));
        var hidden = document.getElementById('rgRole');
        function select(role) {
            chips.forEach(function (c) {
                var on = c.getAttribute('data-role') === role;
                var color = c.getAttribute('data-c');
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
                if (on) {
                    c.style.background = color; c.style.color = '#fff'; c.style.borderColor = 'transparent';
                } else {
                    c.style.background = '#fff'; c.style.color = color;
                    c.style.borderColor = color + '55';
                }
            });
            hidden.value = role;
        }
        chips.forEach(function (c) {
            c.addEventListener('click', function () { select(c.getAttribute('data-role')); });
        });
        var proLink = document.getElementById('rgProLink');
        if (proLink) proLink.addEventListener('click', function (e) { e.preventDefault(); select('supplier'); window.scrollTo({top:0,behavior:'smooth'}); });
    })();

    @if($showRecaptcha && $recaptchaSiteKey && $recaptchaVersion === 'v3')
    document.getElementById('rgSubmit').addEventListener('click', function (e) {
        e.preventDefault();
        grecaptcha.ready(function () {
            grecaptcha.execute('{{ $recaptchaSiteKey }}', {action: 'register'}).then(function (token) {
                document.getElementById('rgRecaptcha').value = token;
                document.getElementById('rgForm').submit();
            });
        });
    });
    @endif
</script>
</body>
</html>
