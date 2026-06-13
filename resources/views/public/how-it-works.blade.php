@extends('layouts.landing')

@php
    $seoTitle       = 'How It Works — Simple Steps, Unforgettable Events | GigResource';
    $seoDescription = 'See how GigResource works: find the right event professionals, connect and discuss, book and pay securely, and bring your event to life.';
@endphp

@section('content')

@push('styles')
<style>
    /* ════════ How It Works (light) — page-scoped ════════ */
    .hw-section { padding: 64px 0; }
    .hw-head { text-align: center; max-width: 640px; margin: 0 auto 48px; }
    .hw-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; letter-spacing: 1.3px; text-transform: uppercase; }
    .hw-eyebrow.orange { color: var(--orange-dark); }
    .hw-eyebrow.pill { background: var(--bg-soft-2); color: var(--blue); padding: 6px 14px; border-radius: 999px; }
    .hw-eyebrow.pill.orange { background: rgba(249,115,22,0.1); color: var(--orange-dark); }
    .hw-h2 { font-size: 34px; font-weight: 800; letter-spacing: -0.6px; color: var(--ink); line-height: 1.15; margin-top: 14px; }
    .hw-lead { font-size: 15.5px; color: var(--muted); line-height: 1.6; margin: 14px auto 0; max-width: 520px; }

    /* ── HERO ───────────────────────────────────────── */
    .hw-hero { padding: 50px 0 44px; position: relative; overflow: hidden; }
    .hw-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, var(--bg-soft-2) 0%, transparent 100%); z-index: 0; }
    .hw-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 46px; align-items: center; }
    .hw-h1 { font-size: 52px; font-weight: 800; letter-spacing: -1.4px; line-height: 1.06; color: var(--ink); margin: 14px 0 0; }
    .hw-h1 .o { color: var(--orange); }
    .hw-h1 .b { color: var(--blue); }
    .hw-hero p.sub { font-size: 16.5px; color: var(--muted); line-height: 1.65; margin: 22px 0 0; max-width: 440px; }

    /* hero illustration */
    .hw-hero-art { position: relative; min-height: 360px; }
    .hw-art-dots { position: absolute; top: 26px; right: 0; width: 110px; height: 110px; opacity: 0.5; background-image: radial-gradient(var(--blue) 1.6px, transparent 1.6px); background-size: 16px 16px; z-index: 0; }
    .hw-window { position: relative; z-index: 2; background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow-lg); padding: 18px; max-width: 420px; margin-left: auto; }
    .hw-window-bar { display: flex; gap: 6px; margin-bottom: 16px; }
    .hw-window-bar i { width: 9px; height: 9px; border-radius: 50%; }
    .hw-window-bar .l1 { background: #ff5f57; } .hw-window-bar .l2 { background: #febc2e; } .hw-window-bar .l3 { background: #28c840; }
    .hw-profile { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
    .hw-profile img { width: 64px; height: 64px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
    .hw-profile .stars { color: #f59e0b; font-size: 15px; letter-spacing: 2px; }
    .hw-profile .ln { height: 9px; border-radius: 5px; background: var(--bg-soft-2); margin-top: 9px; }
    .hw-profile .ln.s { max-width: 65%; }
    .hw-window .check { position: absolute; right: 30px; top: 96px; width: 30px; height: 30px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 12px rgba(37,99,235,0.45); border: 3px solid #fff; }
    .hw-window .check svg { width: 15px; height: 15px; }
    .hw-hire { display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--blue-light), var(--blue-dark)); color: #fff; font-weight: 800; font-size: 14px; padding: 13px; border-radius: 11px; box-shadow: 0 10px 20px rgba(37,99,235,0.3); }
    .hw-3d { position: absolute; z-index: 3; }
    .hw-3d-chat { left: -20px; top: 30px; width: 60px; height: 60px; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.28)); }
    .hw-3d-cal { left: -24px; bottom: 30px; width: 70px; height: 70px; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.25)); }
    .hw-3d-plant { right: -8px; bottom: -8px; width: 76px; height: 96px; z-index: 1; filter: drop-shadow(0 12px 18px rgba(15,27,53,0.12)); }

    /* ── PROCESS STEPS ──────────────────────────────── */
    .hw-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; position: relative; }
    .hw-step { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px 22px 26px; text-align: center; position: relative; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s; }
    .hw-step:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
    .hw-step-num { position: absolute; top: -16px; left: 50%; transform: translateX(-50%); width: 34px; height: 34px; border-radius: 50%; color: #fff; font-size: 15px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 4px solid var(--bg); box-shadow: 0 6px 14px rgba(15,27,53,0.18); }
    .hw-step-ic { width: 76px; height: 76px; border-radius: 20px; margin: 10px auto 18px; display: flex; align-items: center; justify-content: center; }
    .hw-step-ic svg { width: 34px; height: 34px; }
    .hw-step h4 { font-size: 16.5px; font-weight: 800; color: var(--ink); margin-bottom: 10px; }
    .hw-step p { font-size: 13px; color: var(--muted); line-height: 1.6; margin: 0; }
    @media (min-width: 981px) {
        .hw-step:not(:last-child)::after { content: ''; position: absolute; top: 64px; right: -11px; width: 22px; border-top: 2px dashed #cbd5e1; z-index: 1; }
    }

    /* ── TRUST BANNER ───────────────────────────────── */
    .hw-trust-wrap { padding: 16px 0; }
    .hw-trust { background: linear-gradient(120deg, #eaf1fe, #f3f7ff); border: 1px solid rgba(37,99,235,0.12); border-radius: var(--radius-lg); padding: 30px 38px; display: flex; align-items: center; gap: 26px; }
    .hw-trust-ic { width: 84px; height: 84px; flex-shrink: 0; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.28)); }
    .hw-trust-txt { flex: 1; min-width: 0; }
    .hw-trust-txt h3 { font-size: 22px; font-weight: 800; color: var(--ink); }
    .hw-trust-txt p { font-size: 14px; color: var(--muted); margin: 8px 0 0; max-width: 560px; line-height: 1.55; }
    .hw-trust a { flex-shrink: 0; }

    /* ── WHY CHOOSE ─────────────────────────────────── */
    .hw-why { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 26px; }
    .hw-why-item { display: flex; gap: 14px; align-items: flex-start; }
    .hw-why-ic { width: 52px; height: 52px; border-radius: 14px; background: var(--bg-soft-2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .hw-why-ic svg { width: 24px; height: 24px; color: var(--blue); }
    .hw-why-item h4 { font-size: 15px; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
    .hw-why-item p { font-size: 12.5px; color: var(--muted); line-height: 1.5; margin: 0; }

    /* ── READY CTA ──────────────────────────────────── */
    .hw-cta-wrap { padding: 14px 0 80px; }
    .hw-cta { background: linear-gradient(120deg, #eaf1fe 0%, #f4f7ff 55%, #fff3ea 100%); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 34px 40px; display: flex; align-items: center; gap: 24px; position: relative; overflow: hidden; }
    .hw-cta-env { width: 86px; height: 86px; flex-shrink: 0; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.25)); }
    .hw-cta-txt { flex: 1; min-width: 0; }
    .hw-cta-txt h2 { font-size: 25px; font-weight: 800; color: var(--ink); letter-spacing: -0.4px; }
    .hw-cta-txt p { font-size: 14px; color: var(--muted); margin: 8px 0 0; max-width: 380px; line-height: 1.5; }
    .hw-cta-btns { display: flex; gap: 13px; flex-shrink: 0; flex-wrap: wrap; }
    .hw-cta-plane { position: absolute; right: 26px; bottom: 18px; width: 56px; height: 56px; opacity: 0.9; }

    @media (max-width: 980px) {
        .hw-hero-grid { grid-template-columns: 1fr; gap: 30px; }
        .hw-hero-art { display: none; }
        .hw-steps { grid-template-columns: 1fr 1fr; gap: 30px 18px; }
        .hw-trust { flex-direction: column; text-align: center; }
        .hw-why { grid-template-columns: 1fr 1fr; gap: 26px 20px; }
        .hw-cta { flex-direction: column; text-align: center; }
        .hw-cta-plane { display: none; }
        .hw-h1 { font-size: 40px; }
        .hw-h2 { font-size: 28px; }
    }
    @media (max-width: 560px) {
        .hw-steps, .hw-why { grid-template-columns: 1fr; }
    }
</style>
@endpush

{{-- ════════════ HERO ════════════ --}}
<section class="hw-hero">
    <div class="lp-container hw-hero-grid">
        <div class="hw-hero-left">
            <span class="hw-eyebrow orange">How It Works</span>
            <h1 class="hw-h1">Simple Steps.<br><span class="o">Unforgettable</span> <span class="b">Events.</span></h1>
            <p class="sub">GigResource makes it easy to connect with trusted event professionals and get things done—faster, smarter, and more efficiently.</p>
        </div>

        <div class="hw-hero-art">
            <span class="hw-art-dots"></span>
            <svg class="hw-3d hw-3d-chat" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="hwChat" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M11 7h38a5 5 0 0 1 5 5v21a5 5 0 0 1-5 5H25L14 47V38h-3a5 5 0 0 1-5-5V12a5 5 0 0 1 5-5Z" fill="url(#hwChat)"/>
                <circle cx="20" cy="22" r="3.2" fill="#fff"/><circle cx="30" cy="22" r="3.2" fill="#fff"/><circle cx="40" cy="22" r="3.2" fill="#fff"/>
            </svg>

            <div class="hw-window">
                <div class="hw-window-bar"><i class="l1"></i><i class="l2"></i><i class="l3"></i></div>
                <div class="hw-profile">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=140&q=80&auto=format&fit=crop" alt="">
                    <div style="flex:1;min-width:0;">
                        <div class="stars">★★★★★</div>
                        <div class="ln"></div>
                        <div class="ln s"></div>
                    </div>
                </div>
                <span class="check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span>
                <div class="ln" style="height:9px;border-radius:5px;background:var(--bg-soft-2);margin-bottom:8px;"></div>
                <div class="ln" style="height:9px;border-radius:5px;background:var(--bg-soft-2);max-width:80%;margin-bottom:16px;"></div>
                <div class="hw-hire">Hire Professional</div>
            </div>

            <svg class="hw-3d hw-3d-cal" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="hwCal" x1="0" y1="0" x2="70" y2="70" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="9" y="16" width="52" height="48" rx="10" fill="url(#hwCal)"/>
                <rect x="9" y="16" width="52" height="15" rx="10" fill="#1d4ed8"/>
                <rect x="20" y="9" width="6" height="13" rx="3" fill="#1e293b"/><rect x="44" y="9" width="6" height="13" rx="3" fill="#1e293b"/>
                <rect x="18" y="37" width="10" height="9" rx="2.5" fill="#fff" opacity=".95"/><rect x="32" y="37" width="10" height="9" rx="2.5" fill="#fff" opacity=".55"/><rect x="46" y="37" width="7" height="9" rx="2.5" fill="#fff" opacity=".55"/>
                <rect x="18" y="49" width="10" height="9" rx="2.5" fill="#fff" opacity=".55"/><rect x="32" y="49" width="10" height="9" rx="2.5" fill="#fbbf24"/>
            </svg>
            <svg class="hw-3d hw-3d-plant" viewBox="0 0 76 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M38 52C32 42 23 38 15 40c2 9 11 15 23 15Z" fill="#10b981"/>
                <path d="M38 52c5-13 15-18 23-15-2 10-13 17-23 17Z" fill="#34d399"/>
                <path d="M38 54c-2-15 4-26 10-29 3 11-1 24-10 29Z" fill="#059669"/>
                <path d="M23 58h30l-4 28a4 4 0 0 1-4 3.5H31a4 4 0 0 1-4-3.5L23 58Z" fill="#fff" stroke="#e2e8f0"/>
                <path d="M21 54h34v6H21z" fill="#cbd5e1"/>
            </svg>
        </div>
    </div>
</section>

{{-- ════════════ THE PROCESS ════════════ --}}
<section class="hw-section">
    <div class="lp-container">
        <div class="hw-head">
            <span class="hw-eyebrow pill">The Process</span>
            <h2 class="hw-h2">How GigResource Works</h2>
            <p class="hw-lead">From finding the right talent to getting the job done, we make the process seamless.</p>
        </div>
        <div class="hw-steps">
            @php
                $steps = [
                    ['#2563eb', 'blue',   '1', 'Find What You Need',  'Browse gigs or professionals by category, location, or specialty and find the perfect match for your event.',
                        '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'],
                    ['#ea580c', 'orange', '2', 'Connect &amp; Discuss', 'Send messages, discuss details, and get quotes. Clarify expectations and choose the best fit.',
                        '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'],
                    ['#2563eb', 'blue',   '3', 'Book &amp; Pay Securely', 'Book with confidence using our secure payment system that protects both clients and professionals.',
                        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/>'],
                    ['#ea580c', 'orange', '4', 'Get It Done',         'Collaborate, communicate, and bring your event to life with verified professionals you can trust.',
                        '<path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L12 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/>'],
                ];
            @endphp
            @foreach($steps as [$col, $tint, $num, $title, $desc, $icon])
                <div class="hw-step">
                    <span class="hw-step-num" style="background:{{ $col }};">{{ $num }}</span>
                    <span class="hw-step-ic" style="background:{{ $tint === 'blue' ? 'rgba(37,99,235,0.1)' : 'rgba(249,115,22,0.1)' }};">
                        <svg viewBox="0 0 24 24" fill="none" stroke="{{ $col }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                    </span>
                    <h4>{!! $title !!}</h4>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════ TRUST BANNER ════════════ --}}
<div class="hw-trust-wrap">
    <div class="lp-container">
        <div class="hw-trust">
            <svg class="hw-trust-ic" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="hwSh" x1="0" y1="0" x2="84" y2="84" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M42 8l26 10v19c0 16-11 27-26 33-15-6-26-17-26-33V18L42 8Z" fill="url(#hwSh)"/>
                <path d="M42 8l26 10v19c0 16-11 27-26 33V8Z" fill="#1d4ed8" opacity=".3"/>
                <path d="M42 24l4.2 8.6 9.5 1.2-7 6.7 1.7 9.4L42 55.1l-8.4 4.4 1.7-9.4-7-6.7 9.5-1.2L42 24Z" fill="#fbbf24"/>
                <circle cx="62" cy="56" r="11" fill="#10b981" stroke="#fff" stroke-width="3"/>
                <path d="M57 56l3.5 3.5 6-6.5" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="hw-trust-txt">
                <h3>Safe. Secure. Trusted.</h3>
                <p>Your safety and satisfaction are our top priorities. That's why we verify professionals, protect payments, and support you every step of the way.</p>
            </div>
            <a href="{{ route('about-us') }}" class="lp-btn lp-btn-outline">Learn More
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>

{{-- ════════════ WHY CHOOSE ════════════ --}}
<section class="hw-section">
    <div class="lp-container">
        <div class="hw-head">
            <span class="hw-eyebrow pill orange">Why Choose Us</span>
            <h2 class="hw-h2">Built for Event Success</h2>
            <p class="hw-lead">GigResource is designed to save you time, reduce stress, and help you create unforgettable events.</p>
        </div>
        <div class="hw-why">
            <div class="hw-why-item">
                <span class="hw-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.5 13.5L17 22l-5-3-5 3 1.5-8.5"/></svg></span>
                <div><h4>Verified Professionals</h4><p>Only trusted, reviewed, and verified event experts.</p></div>
            </div>
            <div class="hw-why-item">
                <span class="hw-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
                <div><h4>Secure Payments</h4><p>Safe transactions and money protection.</p></div>
            </div>
            <div class="hw-why-item">
                <span class="hw-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>
                <div><h4>Save Time</h4><p>Quick search, easy booking, and efficient communication.</p></div>
            </div>
            <div class="hw-why-item">
                <span class="hw-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></span>
                <div><h4>Dedicated Support</h4><p>Our team is here to help you succeed.</p></div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ READY CTA ════════════ --}}
<div class="hw-cta-wrap">
    <div class="lp-container">
        <div class="hw-cta">
            <svg class="hw-cta-env" viewBox="0 0 86 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="hwEnv" x1="0" y1="0" x2="86" y2="86" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="11" y="25" width="64" height="46" rx="8" fill="url(#hwEnv)"/>
                <path d="M11 31l32 22 32-22" stroke="#fff" stroke-width="3.5" fill="none" stroke-linejoin="round"/>
                <path d="M11 31l32 22 32-22v-2a4 4 0 0 0-4-4H15a4 4 0 0 0-4 4v2Z" fill="#1d4ed8"/>
                <circle cx="66" cy="28" r="12" fill="#2563eb" stroke="#fff" stroke-width="2.5"/>
                <path d="M66 22l1.7 3.5 3.8.5-2.8 2.7.7 3.8L66 30.8l-3.4 1.7.7-3.8-2.8-2.7 3.8-.5L66 22Z" fill="#fbbf24"/>
            </svg>
            <div class="hw-cta-txt">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of event professionals and clients creating amazing experiences together.</p>
            </div>
            <div class="hw-cta-btns">
                <a href="{{ route('public.browse') }}" class="lp-btn lp-btn-outline">Find Gigs</a>
                <a href="{{ route('register') }}" class="lp-btn lp-btn-orange">Sign Up Now
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <svg class="hw-cta-plane" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M51 5L5 25l17 5 5 18 24-43Z" fill="#3b82f6"/>
                <path d="M51 5L22 30l5 18 24-43Z" fill="#1d4ed8"/>
                <path d="M51 5L22 30 5 25 51 5Z" fill="#60a5fa"/>
            </svg>
        </div>
    </div>
</div>

@endsection
