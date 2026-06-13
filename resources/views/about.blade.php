@extends('layouts.landing')

@php
    $seoTitle       = 'About GigResource — Connecting People, Creating Unforgettable Events';
    $seoDescription = 'GigResource was built to make event planning simpler — connecting clients with trusted event professionals through a modern platform for collaboration, transparency, and successful events.';
@endphp

@section('content')

@push('styles')
<style>
    /* ════════ About page (light) — page-scoped ════════ */
    .ab-section { padding: 70px 0; }
    .ab-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; }
    .ab-eyebrow.orange { color: var(--orange-dark); }
    .ab-eyebrow.pill { background: var(--bg-soft-2); color: var(--blue); padding: 6px 14px; border-radius: 999px; letter-spacing: 1px; }
    .ab-h2 { font-size: 34px; font-weight: 800; letter-spacing: -0.6px; color: var(--ink); line-height: 1.15; }
    .ab-head { text-align: center; max-width: 640px; margin: 0 auto 50px; }
    .ab-head .ab-h2 { margin-top: 14px; }

    /* ── HERO ───────────────────────────────────────── */
    .ab-hero { padding: 50px 0 30px; position: relative; overflow: hidden; }
    .ab-hero::before { content: ''; position: absolute; top: -140px; right: -100px; width: 480px; height: 480px; background: radial-gradient(circle, rgba(37,99,235,0.09), transparent 70%); z-index: 0; }
    .ab-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 48px; align-items: center; }
    .ab-h1 { font-size: 50px; font-weight: 800; letter-spacing: -1.4px; line-height: 1.08; color: var(--ink); margin: 16px 0 0; }
    .ab-h1 .o { color: var(--orange); }
    .ab-h1 .b { color: var(--blue); }
    .ab-hero p.sub { font-size: 16.5px; color: var(--muted); line-height: 1.65; margin: 22px 0 28px; max-width: 480px; }
    .ab-hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }

    /* hero illustration */
    .ab-hero-art { position: relative; min-height: 430px; }
    .ab-art-dots { position: absolute; top: 8px; right: 0; width: 120px; height: 120px; opacity: 0.5;
        background-image: radial-gradient(var(--blue) 1.6px, transparent 1.6px); background-size: 16px 16px; z-index: 0; }
    .ab-window { position: relative; z-index: 2; background: #fff; border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow-lg); padding: 16px; max-width: 430px; margin-left: auto; }
    .ab-window-bar { display: flex; align-items: center; gap: 6px; margin-bottom: 14px; }
    .ab-window-bar i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    .ab-window-bar .l1 { background: #ff5f57; } .ab-window-bar .l2 { background: #febc2e; } .ab-window-bar .l3 { background: #28c840; }
    .ab-window-bar b { margin-left: 8px; font-family: var(--ff-head); font-size: 13px; color: var(--ink); font-weight: 800; }
    .ab-window-bar b i { font-style: normal; color: var(--blue); }
    .ab-pcards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ab-pcard { background: var(--bg-soft); border: 1px solid var(--line-soft); border-radius: 14px; padding: 14px 12px; text-align: center; position: relative; }
    .ab-pcard img { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; margin: 0 auto 9px; display: block; }
    .ab-pcard b { font-size: 12.5px; color: var(--ink); display: block; }
    .ab-pcard span { font-size: 10.5px; color: var(--muted); }
    .ab-pcard .stars { color: #f59e0b; font-size: 11px; margin-top: 5px; letter-spacing: 1px; }
    .ab-pcard .vbadge { position: absolute; top: 10px; right: 10px; width: 20px; height: 20px; border-radius: 50%; background: var(--blue); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(37,99,235,0.4); }
    .ab-pcard .vbadge svg { width: 11px; height: 11px; }
    .ab-float { position: absolute; background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-lg); z-index: 3; }
    .ab-float-msg { bottom: 26px; left: -10px; padding: 11px 14px; display: flex; align-items: center; gap: 10px; }
    .ab-float-msg img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
    .ab-float-msg b { font-size: 12px; color: var(--ink); display: block; }
    .ab-float-msg span { font-size: 10px; color: var(--muted); }
    .ab-3dcal { position: absolute; left: -18px; top: 70px; z-index: 3; width: 66px; height: 66px; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.28)); }
    .ab-3dchat { position: absolute; right: -14px; top: -10px; z-index: 3; width: 60px; height: 60px; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.25)); }
    .ab-plant { position: absolute; right: -6px; bottom: -6px; z-index: 1; width: 70px; height: 90px; filter: drop-shadow(0 10px 16px rgba(15,27,53,0.12)); }

    /* ── OUR STORY ──────────────────────────────────── */
    .ab-story { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow); padding: 44px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 44px; align-items: center; }
    .ab-story-art { position: relative; min-height: 300px; display: flex; align-items: center; justify-content: center; }
    .ab-checklist { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow-lg); padding: 20px 22px; width: 240px; position: relative; z-index: 2; }
    .ab-checklist-row { display: flex; align-items: center; gap: 11px; padding: 9px 0; }
    .ab-checklist-row .ck { width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; }
    .ab-checklist-row .ck svg { width: 13px; height: 13px; }
    .ab-checklist-row .bar { flex: 1; height: 9px; border-radius: 6px; background: var(--bg-soft-2); }
    .ab-checklist-row .bar.short { max-width: 60%; }
    .ab-story-chat { position: absolute; top: 8px; right: 4px; width: 58px; height: 58px; z-index: 3; filter: drop-shadow(0 10px 18px rgba(249,115,22,0.3)); }
    .ab-story-person { position: absolute; bottom: 6px; right: 0; z-index: 3; background: #fff; border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow-lg); padding: 9px 11px; display: flex; align-items: center; gap: 9px; }
    .ab-story-person img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
    .ab-story-person .stars { color: #f59e0b; font-size: 10px; letter-spacing: 1px; }
    .ab-story-mug { position: absolute; bottom: -4px; left: 18px; width: 56px; height: 56px; z-index: 3; filter: drop-shadow(0 8px 14px rgba(37,99,235,0.25)); }
    .ab-story-txt h2 { margin: 14px 0 18px; }
    .ab-story-txt p { font-size: 15px; color: var(--muted); line-height: 1.7; margin: 0 0 16px; }

    /* ── MISSION / VISION ───────────────────────────── */
    .ab-mv { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .ab-mv-card { border-radius: var(--radius-lg); padding: 34px; display: flex; gap: 22px; align-items: flex-start; }
    .ab-mv-card.mission { background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(37,99,235,0.02)); border: 1px solid rgba(37,99,235,0.12); }
    .ab-mv-card.vision { background: linear-gradient(135deg, rgba(249,115,22,0.08), rgba(249,115,22,0.02)); border: 1px solid rgba(249,115,22,0.14); }
    .ab-mv-art { width: 88px; height: 88px; flex-shrink: 0; }
    .ab-mv-card h3 { font-size: 22px; font-weight: 800; margin-bottom: 10px; }
    .ab-mv-card.mission h3 { color: var(--blue); }
    .ab-mv-card.vision h3 { color: var(--orange-dark); }
    .ab-mv-card p { font-size: 14.5px; color: var(--muted); line-height: 1.65; margin: 0; }

    /* ── VALUES (principles) ────────────────────────── */
    .ab-values { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; position: relative; }
    .ab-value { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 28px 22px; text-align: center; box-shadow: var(--shadow-sm); position: relative; transition: transform .2s, box-shadow .2s; }
    .ab-value:hover { transform: translateY(-4px); box-shadow: var(--shadow); }
    .ab-value-ic { width: 70px; height: 70px; margin: 0 auto 16px; }
    .ab-value h4 { font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 9px; }
    .ab-value p { font-size: 13px; color: var(--muted); line-height: 1.55; margin: 0; }
    .ab-value-dot { display: none; }
    @media (min-width: 981px) {
        .ab-value:not(:last-child)::after { content: ''; position: absolute; top: 58px; right: -10px; width: 20px; border-top: 2px dashed #cbd5e1; z-index: 1; }
    }

    /* ── WHY CHOOSE US ──────────────────────────────── */
    .ab-why { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 28px; }
    .ab-why-item { text-align: center; padding: 0 8px; }
    .ab-why-ic { width: 58px; height: 58px; margin: 0 auto 16px; border-radius: 16px; background: var(--bg-soft-2); display: flex; align-items: center; justify-content: center; }
    .ab-why-ic svg { width: 26px; height: 26px; color: var(--blue); }
    .ab-why-item h4 { font-size: 15.5px; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .ab-why-item p { font-size: 13px; color: var(--muted); line-height: 1.55; margin: 0; }

    /* ── WHO WE SERVE ───────────────────────────────── */
    .ab-serve { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
    .ab-serve-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s; }
    .ab-serve-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .ab-serve-body { padding: 20px 22px 24px; display: flex; gap: 14px; align-items: flex-start; }
    .ab-serve-card img { width: 100%; height: 170px; object-fit: cover; }
    .ab-serve-ico { width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .ab-serve-ico svg { width: 21px; height: 21px; color: #fff; }
    .ab-serve-card h4 { font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 5px; }
    .ab-serve-card p { font-size: 13px; color: var(--muted); line-height: 1.55; margin: 0; }

    /* ── CTA ────────────────────────────────────────── */
    .ab-cta-wrap { padding: 20px 0 80px; }
    .ab-cta { background: linear-gradient(120deg, #eaf1fe 0%, #f4f7ff 55%, #fff3ea 100%); border: 1px solid var(--line); border-radius: 24px; padding: 44px 48px; display: flex; align-items: center; gap: 26px; position: relative; overflow: hidden; }
    .ab-cta-env { width: 92px; height: 92px; flex-shrink: 0; filter: drop-shadow(0 14px 24px rgba(37,99,235,0.25)); }
    .ab-cta-txt { flex: 1; min-width: 0; }
    .ab-cta-txt h2 { font-size: 28px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; }
    .ab-cta-txt p { font-size: 14.5px; color: var(--muted); margin: 10px 0 0; max-width: 480px; line-height: 1.55; }
    .ab-cta-btns { display: flex; gap: 13px; flex-shrink: 0; flex-wrap: wrap; }
    .ab-cta-plane { position: absolute; right: 30px; top: 22px; width: 64px; height: 64px; opacity: 0.9; }

    @media (max-width: 980px) {
        .ab-hero-grid { grid-template-columns: 1fr; gap: 36px; }
        .ab-hero-art { display: none; }
        .ab-story { grid-template-columns: 1fr; padding: 30px; gap: 30px; }
        .ab-story-art { display: none; }
        .ab-mv { grid-template-columns: 1fr; }
        .ab-values { grid-template-columns: 1fr 1fr; }
        .ab-why { grid-template-columns: 1fr 1fr; gap: 30px; }
        .ab-serve { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
        .ab-cta { flex-direction: column; text-align: center; padding: 34px 26px; }
        .ab-cta-plane { display: none; }
        .ab-h1 { font-size: 38px; }
        .ab-h2 { font-size: 27px; }
    }
    @media (max-width: 560px) {
        .ab-values, .ab-why { grid-template-columns: 1fr; }
    }
</style>
@endpush

{{-- ════════════ HERO ════════════ --}}
<section class="ab-hero">
    <div class="lp-container ab-hero-grid">
        <div class="ab-hero-left">
            <span class="ab-eyebrow orange">About GigResource</span>
            <h1 class="ab-h1">Connecting People.<br>Creating <span class="o">Unforgettable</span> <span class="b">Events.</span></h1>
            <p class="sub">GigResource was built to make event planning simpler. We connect clients with trusted event professionals through a modern platform designed for collaboration, transparency, and successful event experiences.</p>
            <div class="ab-hero-btns">
                <a href="{{ route('public.browse') }}" class="lp-btn lp-btn-blue">Find Professionals</a>
                <a href="{{ route('register') }}" class="lp-btn lp-btn-orange">Join GigResource</a>
            </div>
        </div>

        <div class="ab-hero-art">
            <span class="ab-art-dots"></span>

            {{-- 3D chat bubble --}}
            <svg class="ab-3dchat" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="abChat" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M12 8h40a6 6 0 0 1 6 6v24a6 6 0 0 1-6 6H28l-12 10v-10h-4a6 6 0 0 1-6-6V14a6 6 0 0 1 6-6Z" fill="url(#abChat)"/>
                <circle cx="22" cy="26" r="3.4" fill="#fff"/><circle cx="32" cy="26" r="3.4" fill="#fff"/><circle cx="42" cy="26" r="3.4" fill="#fff"/>
            </svg>

            {{-- app window --}}
            <div class="ab-window">
                <div class="ab-window-bar"><i class="l1"></i><i class="l2"></i><i class="l3"></i><b>Gig<i>Resource</i></b></div>
                <div class="ab-pcards">
                    <div class="ab-pcard">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&q=80&auto=format&fit=crop" alt="">
                        <b>Event Organizer</b>
                        <div class="stars">★★★★★</div>
                    </div>
                    <div class="ab-pcard">
                        <span class="vbadge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span>
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&q=80&auto=format&fit=crop" alt="">
                        <b>Event Professional</b>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
            </div>

            {{-- 3D calendar --}}
            <svg class="ab-3dcal" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="abCal" x1="0" y1="0" x2="66" y2="66" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="8" y="14" width="50" height="46" rx="9" fill="url(#abCal)"/>
                <rect x="8" y="14" width="50" height="14" rx="9" fill="#1d4ed8"/>
                <rect x="18" y="8" width="6" height="12" rx="3" fill="#1e293b"/><rect x="42" y="8" width="6" height="12" rx="3" fill="#1e293b"/>
                <rect x="17" y="34" width="10" height="8" rx="2.5" fill="#fff" opacity="0.95"/><rect x="31" y="34" width="10" height="8" rx="2.5" fill="#fff" opacity="0.6"/><rect x="45" y="34" width="6" height="8" rx="2.5" fill="#fff" opacity="0.6"/>
                <rect x="17" y="46" width="10" height="8" rx="2.5" fill="#fff" opacity="0.6"/><rect x="31" y="46" width="10" height="8" rx="2.5" fill="#fbbf24"/>
            </svg>

            {{-- New message float --}}
            <div class="ab-float ab-float-msg">
                <img src="https://images.unsplash.com/photo-1633332755192-727a05c4013d?w=90&q=80&auto=format&fit=crop" alt="">
                <div><b>New Message</b><span>Looking forward to it! 🎉</span></div>
            </div>

            {{-- plant --}}
            <svg class="ab-plant" viewBox="0 0 70 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M35 50C30 40 22 36 16 38c2 8 10 14 19 14Z" fill="#10b981"/>
                <path d="M35 50c5-12 14-16 21-13-2 9-12 15-21 15Z" fill="#34d399"/>
                <path d="M35 52c-2-14 3-24 9-27 3 10-1 22-9 27Z" fill="#059669"/>
                <path d="M22 56h26l-4 28a4 4 0 0 1-4 3.5H30a4 4 0 0 1-4-3.5L22 56Z" fill="#f97316"/>
                <path d="M20 52h30v6H20z" fill="#fb923c"/>
            </svg>
        </div>
    </div>
</section>

{{-- ════════════ OUR STORY ════════════ --}}
<section class="ab-section">
    <div class="lp-container">
        <div class="ab-story">
            <div class="ab-story-art">
                <div class="ab-checklist">
                    <div class="ab-checklist-row"><span class="ck" style="background:linear-gradient(135deg,#60a5fa,#2563eb);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span><span class="bar"></span></div>
                    <div class="ab-checklist-row"><span class="ck" style="background:linear-gradient(135deg,#60a5fa,#2563eb);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span><span class="bar short"></span></div>
                    <div class="ab-checklist-row"><span class="ck" style="background:linear-gradient(135deg,#fb923c,#ea580c);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg></span><span class="bar"></span></div>
                </div>
                <svg class="ab-story-chat" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="abSChat" x1="0" y1="0" x2="58" y2="58" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs>
                    <path d="M10 6h38a5 5 0 0 1 5 5v22a5 5 0 0 1-5 5H26L14 47V38h-4a5 5 0 0 1-5-5V11a5 5 0 0 1 5-5Z" fill="url(#abSChat)"/>
                    <circle cx="20" cy="22" r="3" fill="#fff"/><circle cx="29" cy="22" r="3" fill="#fff"/><circle cx="38" cy="22" r="3" fill="#fff"/>
                </svg>
                <div class="ab-story-person">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&q=80&auto=format&fit=crop" alt="">
                    <div><div style="font-size:11px;font-weight:800;color:var(--ink);">Top Rated</div><div class="stars">★★★★★</div></div>
                </div>
                <svg class="ab-story-mug" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 18h30v20a10 10 0 0 1-10 10H20a10 10 0 0 1-10-10V18Z" fill="#3b82f6"/>
                    <path d="M40 22h4a7 7 0 0 1 0 14h-4" stroke="#2563eb" stroke-width="4" fill="none"/>
                    <rect x="10" y="18" width="30" height="6" fill="#60a5fa"/>
                </svg>
            </div>
            <div class="ab-story-txt">
                <span class="ab-eyebrow pill">Our Story</span>
                <h2 class="ab-h2">Built for Better<br>Event Experiences</h2>
                <p>Planning an event should be exciting—not stressful. GigResource was created to help event organizers find the right professionals, communicate easily, and manage bookings with confidence.</p>
                <p>Whether it's a corporate event, wedding, private celebration, or live experience, our goal is to simplify every step of the process.</p>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ MISSION / VISION ════════════ --}}
<section class="ab-section" style="padding-top: 0;">
    <div class="lp-container">
        <div class="ab-mv">
            <div class="ab-mv-card mission">
                <svg class="ab-mv-art" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="42" cy="46" r="34" fill="#1d4ed8"/>
                    <circle cx="42" cy="46" r="26" fill="#fff"/>
                    <circle cx="42" cy="46" r="19" fill="#3b82f6"/>
                    <circle cx="42" cy="46" r="11" fill="#fff"/>
                    <circle cx="42" cy="46" r="5" fill="#ea580c"/>
                    <path d="M42 46 76 16" stroke="#1e293b" stroke-width="4" stroke-linecap="round"/>
                    <path d="M76 16l8-8-1 11-10-1 3-2Z" fill="#f97316"/>
                    <path d="M73 19l-6 1 1-6 5 5Z" fill="#fb923c"/>
                </svg>
                <div>
                    <h3>Our Mission</h3>
                    <p>To make it easier for people to discover, connect with, and hire trusted event professionals for every type of event.</p>
                </div>
            </div>
            <div class="ab-mv-card vision">
                <svg class="ab-mv-art" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M44 10c12 6 18 20 18 34l-8 8H34l-8-8c0-14 6-28 18-34Z" fill="#e2e8f0"/>
                    <path d="M44 10c12 6 18 20 18 34l-8 8H44V10Z" fill="#f1f5f9"/>
                    <circle cx="44" cy="34" r="8" fill="#2563eb"/><circle cx="44" cy="34" r="4" fill="#bfdbfe"/>
                    <path d="M34 52l-10 8 4-18 6 10ZM54 52l10 8-4-18-6 10Z" fill="#ea580c"/>
                    <path d="M38 60h12l-3 12c-1 4-5 4-6 0l-3-12Z" fill="#fb923c"/>
                    <path d="M40 62h8l-2 8c-.6 2.4-3.4 2.4-4 0l-2-8Z" fill="#fbbf24"/>
                </svg>
                <div>
                    <h3>Our Vision</h3>
                    <p>To become the most trusted marketplace where event professionals and clients collaborate to create extraordinary experiences.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ VALUES / PRINCIPLES ════════════ --}}
<section class="ab-section lp-section-soft" style="background: var(--bg-soft);">
    <div class="lp-container">
        <div class="ab-head">
            <span class="ab-eyebrow pill">Our Values</span>
            <h2 class="ab-h2">The Principles Behind GigResource</h2>
        </div>
        <div class="ab-values">
            {{-- Trust First --}}
            <div class="ab-value">
                <svg class="ab-value-ic" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="abShield" x1="0" y1="0" x2="70" y2="70" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                    <path d="M35 8l22 8v16c0 14-9.5 23-22 28-12.5-5-22-14-22-28V16l22-8Z" fill="url(#abShield)"/>
                    <path d="M35 8l22 8v16c0 14-9.5 23-22 28V8Z" fill="#1d4ed8" opacity="0.35"/>
                    <path d="M35 22l3.7 7.6 8.3 1-6 5.9 1.5 8.3L35 47.9l-7.5 3.9 1.5-8.3-6-5.9 8.3-1L35 22Z" fill="#fbbf24"/>
                </svg>
                <h4>Trust First</h4>
                <p>Verified profiles and transparent communication help build confidence from day one.</p>
            </div>
            {{-- Simplicity --}}
            <div class="ab-value">
                <svg class="ab-value-ic" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="abPin" x1="0" y1="0" x2="70" y2="70" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs>
                    <path d="M35 8c-11 0-20 9-20 20 0 14 20 34 20 34s20-20 20-34c0-11-9-20-20-20Z" fill="url(#abPin)"/>
                    <circle cx="35" cy="28" r="14" fill="#fff"/>
                    <circle cx="30" cy="25" r="2" fill="#ea580c"/><circle cx="40" cy="25" r="2" fill="#ea580c"/>
                    <path d="M28 31a8 8 0 0 0 14 0" stroke="#ea580c" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                </svg>
                <h4>Simplicity</h4>
                <p>Finding and hiring event professionals should be straightforward and stress-free.</p>
            </div>
            {{-- Community --}}
            <div class="ab-value">
                <svg class="ab-value-ic" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="abPpl" x1="0" y1="0" x2="70" y2="70" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                    <circle cx="22" cy="26" r="9" fill="#60a5fa"/><circle cx="48" cy="26" r="9" fill="#60a5fa"/>
                    <circle cx="35" cy="22" r="11" fill="url(#abPpl)"/>
                    <path d="M10 54c0-8 6-13 12-13s12 5 12 13v4H10v-4Z" fill="#60a5fa"/>
                    <path d="M36 54c0-8 6-13 12-13s12 5 12 13v4H36v-4Z" fill="#60a5fa"/>
                    <path d="M21 56c0-9 7-15 14-15s14 6 14 15v4H21v-4Z" fill="url(#abPpl)"/>
                </svg>
                <h4>Community</h4>
                <p>We believe successful events happen when talented professionals and clients work together.</p>
            </div>
            {{-- Innovation --}}
            <div class="ab-value">
                <svg class="ab-value-ic" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="abBulb" x1="0" y1="0" x2="70" y2="70" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs>
                    <path d="M35 10c-11 0-19 8-19 19 0 7 4 12 8 16 1.5 1.5 2 3 2 5h18c0-2 .5-3.5 2-5 4-4 8-9 8-16 0-11-8-19-19-19Z" fill="url(#abBulb)"/>
                    <path d="M35 10c11 0 19 8 19 19 0 7-4 12-8 16-1.5 1.5-2 3-2 5h-9V10Z" fill="#ea580c" opacity="0.25"/>
                    <rect x="27" y="52" width="16" height="5" rx="2.5" fill="#1e293b"/><rect x="29" y="58" width="12" height="4" rx="2" fill="#334155"/>
                    <path d="M35 24v14M30 30l5 4 5-6" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                <h4>Innovation</h4>
                <p>We continuously improve the platform to make event planning more efficient and enjoyable.</p>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ WHY CHOOSE US ════════════ --}}
<section class="ab-section">
    <div class="lp-container">
        <div class="ab-head">
            <span class="ab-eyebrow pill">Why Choose Us</span>
            <h2 class="ab-h2">Designed Around Real Event Needs</h2>
        </div>
        <div class="ab-why">
            <div class="ab-why-item">
                <span class="ab-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.5 13.5L17 22l-5-3-5 3 1.5-8.5"/></svg></span>
                <h4>Verified Professionals</h4>
                <p>Helping clients connect with trusted event experts.</p>
            </div>
            <div class="ab-why-item">
                <span class="ab-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
                <h4>Secure Payments</h4>
                <p>Protected transactions for both clients and professionals.</p>
            </div>
            <div class="ab-why-item">
                <span class="ab-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                <h4>Easy Communication</h4>
                <p>Discuss requirements and manage details in one place.</p>
            </div>
            <div class="ab-why-item">
                <span class="ab-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></span>
                <h4>Dedicated Support</h4>
                <p>Guidance whenever you need assistance.</p>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ WHO WE SERVE ════════════ --}}
<section class="ab-section lp-section-soft" style="background: var(--bg-soft);">
    <div class="lp-container">
        <div class="ab-head">
            <span class="ab-eyebrow pill">Who We Serve</span>
            <h2 class="ab-h2">Bringing the Event Industry Together</h2>
        </div>
        <div class="ab-serve">
            <div class="ab-serve-card">
                <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=600&q=80&auto=format&fit=crop" alt="Event organizer" loading="lazy">
                <div class="ab-serve-body">
                    <span class="ab-serve-ico" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);box-shadow:0 8px 16px rgba(37,99,235,0.3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <div><h4>Event Organizers</h4><p>Find the right talent for your event.</p></div>
                </div>
            </div>
            <div class="ab-serve-card">
                <img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=600&q=80&auto=format&fit=crop" alt="Event professional" loading="lazy">
                <div class="ab-serve-body">
                    <span class="ab-serve-ico" style="background:linear-gradient(135deg,#fb923c,#ea580c);box-shadow:0 8px 16px rgba(234,88,12,0.3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                    <div><h4>Event Professionals</h4><p>Showcase your expertise and grow your business.</p></div>
                </div>
            </div>
            <div class="ab-serve-card">
                <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?w=600&q=80&auto=format&fit=crop" alt="Event team" loading="lazy">
                <div class="ab-serve-body">
                    <span class="ab-serve-ico" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);box-shadow:0 8px 16px rgba(37,99,235,0.3);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <div><h4>Event Teams</h4><p>Collaborate efficiently from planning to execution.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ CTA ════════════ --}}
<div class="ab-cta-wrap">
    <div class="lp-container">
        <div class="ab-cta">
            <svg class="ab-cta-env" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="abEnv" x1="0" y1="0" x2="92" y2="92" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="12" y="24" width="68" height="50" rx="9" fill="url(#abEnv)"/>
                <path d="M12 30l34 24 34-24" stroke="#fff" stroke-width="4" fill="none" stroke-linejoin="round"/>
                <path d="M12 30l34 24 34-24v-2a4 4 0 0 0-4-4H16a4 4 0 0 0-4 4v2Z" fill="#1d4ed8"/>
                <circle cx="68" cy="26" r="13" fill="#f97316"/>
                <path d="M62 26l4 4 8-8" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="ab-cta-txt">
                <h2>Ready to Build Amazing Events Together?</h2>
                <p>Whether you're looking for trusted professionals or want to showcase your services, GigResource is here to help you succeed.</p>
            </div>
            <div class="ab-cta-btns">
                <a href="{{ route('public.browse') }}" class="lp-btn lp-btn-blue">Find Professionals</a>
                <a href="{{ route('register') }}" class="lp-btn lp-btn-orange">Create Account</a>
            </div>
            <svg class="ab-cta-plane" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M58 6L6 28l20 6 6 20 26-48Z" fill="#3b82f6"/>
                <path d="M58 6L26 34l6 20 26-48Z" fill="#1d4ed8"/>
                <path d="M58 6L26 34l-20-6L58 6Z" fill="#60a5fa"/>
            </svg>
        </div>
    </div>
</div>

@endsection
