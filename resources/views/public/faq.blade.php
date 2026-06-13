@extends('layouts.landing')

@php
    $seoTitle       = 'FAQs — Questions? We\'ve Got Answers | GigResource';
    $seoDescription = 'Find quick answers to the most common questions about using GigResource — getting started, clients, professionals, payments, account security, and support.';

    // Topic metadata (description + accent) for the 6 known categories.
    $topicMeta = [
        'Getting Started'    => ['desc' => 'Learn the basics of GigResource',    'accent' => 'blue'],
        'For Clients'        => ['desc' => 'Hiring and booking professionals',   'accent' => 'orange'],
        'For Professionals'  => ['desc' => 'Grow your business on GigResource',   'accent' => 'blue'],
        'Payments'           => ['desc' => 'Secure payments and transactions',    'accent' => 'orange'],
        'Account & Security' => ['desc' => 'Manage your account and privacy',     'accent' => 'blue'],
        'Support'            => ['desc' => 'Getting help and contacting us',      'accent' => 'orange'],
    ];

    // Self-contained 3D-style icons per category.
    $icons = [
        'Getting Started'    => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqGs" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><circle cx="30" cy="21" r="11" fill="url(#fqGs)"/><circle cx="30" cy="18.5" r="4.6" fill="#fff"/><path d="M22 27a8 8 0 0 1 16 0Z" fill="#fff"/><path d="M14 52c0-9 7-15 16-15s16 6 16 15v2H14v-2Z" fill="url(#fqGs)"/><path d="M30 37c9 0 16 6 16 15v2H30V37Z" fill="#1d4ed8" opacity=".3"/></svg>',
        'For Clients'        => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqCl" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs><rect x="10" y="22" width="40" height="28" rx="6" fill="url(#fqCl)"/><path d="M22 22v-4a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v4" stroke="#ea580c" stroke-width="3.4" fill="none"/><rect x="10" y="30" width="40" height="6" fill="#c2410c" opacity=".5"/><rect x="26" y="31" width="8" height="5" rx="2" fill="#fff"/></svg>',
        'For Professionals'  => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqPr" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><circle cx="30" cy="20" r="8" fill="url(#fqPr)"/><path d="M18 50c0-9 5-15 12-15s12 6 12 15v2H18v-2Z" fill="url(#fqPr)"/><path d="M14 24l6-7 4 4M46 24l-6-7-4 4" stroke="#fb923c" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
        'Payments'           => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqPa" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs><rect x="9" y="16" width="42" height="28" rx="6" fill="url(#fqPa)"/><rect x="9" y="23" width="42" height="6" fill="#c2410c" opacity=".55"/><rect x="15" y="34" width="16" height="4" rx="2" fill="#fff"/><rect x="35" y="34" width="10" height="4" rx="2" fill="#fff" opacity=".7"/></svg>',
        'Account & Security' => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqAc" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><path d="M30 8l18 7v13c0 12-8 19-18 23-10-4-18-11-18-23V15l18-7Z" fill="url(#fqAc)"/><path d="M30 8l18 7v13c0 12-8 19-18 23V8Z" fill="#1d4ed8" opacity=".3"/><path d="M22 30l5 5 11-11" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
        'Support'            => '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="fqSu" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs><path d="M14 34v-4a16 16 0 0 1 32 0v4" stroke="url(#fqSu)" stroke-width="4.5" fill="none" stroke-linecap="round"/><rect x="9" y="32" width="11" height="16" rx="5" fill="url(#fqSu)"/><rect x="40" y="32" width="11" height="16" rx="5" fill="url(#fqSu)"/><path d="M40 46v2a6 6 0 0 1-6 6h-6" stroke="#ea580c" stroke-width="3.4" fill="none" stroke-linecap="round"/></svg>',
    ];

    $order = array_keys($topicMeta);
    $cats  = collect($order)->filter(fn ($c) => $grouped->has($c))
        ->merge($grouped->keys()->reject(fn ($c) => in_array($c, $order)))
        ->values();
@endphp

@section('content')

@push('styles')
<style>
    /* ════════ FAQ page (light) — page-scoped ════════ */
    .fq-section { padding: 56px 0; }
    .fq-head { text-align: center; max-width: 640px; margin: 0 auto 40px; }
    .fq-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
    .fq-eyebrow.orange { color: var(--orange-dark); }
    .fq-eyebrow.pill { background: var(--bg-soft-2); color: var(--blue); padding: 6px 14px; border-radius: 999px; }
    .fq-eyebrow.pill.orange { background: rgba(249,115,22,0.1); color: var(--orange-dark); }
    .fq-h2 { font-size: 33px; font-weight: 800; letter-spacing: -0.6px; color: var(--ink); line-height: 1.15; margin-top: 14px; }

    /* ── HERO ───────────────────────────────────────── */
    .fq-hero { padding: 48px 0 44px; position: relative; overflow: hidden; }
    .fq-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, var(--bg-soft-2) 0%, transparent 100%); z-index: 0; }
    .fq-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 44px; align-items: center; }
    .fq-h1 { font-size: 52px; font-weight: 800; letter-spacing: -1.4px; line-height: 1.06; color: var(--ink); margin: 14px 0 0; }
    .fq-h1 .o { color: var(--orange); }
    .fq-h1 .b { color: var(--blue); }
    .fq-hero p.sub { font-size: 16px; color: var(--muted); line-height: 1.65; margin: 22px 0 26px; max-width: 430px; }
    .fq-search { position: relative; max-width: 440px; }
    .fq-search svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--faint); }
    .fq-search input { width: 100%; box-sizing: border-box; padding: 15px 18px 15px 46px; border: 1px solid var(--line); border-radius: 12px; background: #fff; font-size: 14.5px; font-family: inherit; color: var(--ink); box-shadow: var(--shadow-sm); }
    .fq-search input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }

    /* hero illustration */
    .fq-hero-art { position: relative; min-height: 360px; display: flex; align-items: center; justify-content: center; }
    .fq-art-dots { position: absolute; top: 20px; right: 30px; width: 100px; height: 100px; opacity: 0.5; background-image: radial-gradient(var(--blue) 1.6px, transparent 1.6px); background-size: 16px 16px; z-index: 0; }
    .fq-doc { position: absolute; right: 40px; top: 24px; width: 240px; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow-lg); padding: 16px; z-index: 1; }
    .fq-doc-bar { display: flex; gap: 6px; margin-bottom: 14px; }
    .fq-doc-bar i { width: 8px; height: 8px; border-radius: 50%; }
    .fq-doc-bar .l1 { background: #ff5f57; } .fq-doc-bar .l2 { background: #febc2e; } .fq-doc-bar .l3 { background: #28c840; }
    .fq-doc-row { display: flex; align-items: center; gap: 10px; margin-bottom: 11px; }
    .fq-doc-row i { width: 13px; height: 13px; border-radius: 50%; background: var(--bg-soft-2); flex-shrink: 0; }
    .fq-doc-row span { flex: 1; height: 8px; border-radius: 5px; background: var(--bg-soft-2); }
    .fq-doc-row span.s { max-width: 60%; }
    .fq-faqbubble { position: relative; z-index: 3; width: 190px; height: 130px; filter: drop-shadow(0 18px 30px rgba(37,99,235,0.35)); margin-right: 80px; margin-bottom: 30px; }
    .fq-3d-chat { position: absolute; left: 86px; bottom: 70px; width: 64px; height: 64px; z-index: 4; filter: drop-shadow(0 12px 20px rgba(249,115,22,0.35)); }
    .fq-3d-mug { position: absolute; right: 96px; bottom: -4px; width: 60px; height: 60px; z-index: 4; filter: drop-shadow(0 10px 16px rgba(37,99,235,0.25)); }
    .fq-3d-plant { position: absolute; right: 18px; bottom: -6px; width: 66px; height: 88px; z-index: 2; filter: drop-shadow(0 10px 16px rgba(15,27,53,0.12)); }

    /* ── BROWSE BY TOPIC ────────────────────────────── */
    .fq-topics { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 16px; }
    .fq-topic { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 24px 16px; text-align: center; cursor: pointer; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s, border-color .2s; }
    .fq-topic:hover { transform: translateY(-4px); box-shadow: var(--shadow); border-color: rgba(37,99,235,0.3); }
    .fq-topic-ic { width: 56px; height: 56px; margin: 0 auto 14px; }
    .fq-topic b { font-size: 14.5px; font-weight: 800; color: var(--ink); display: block; margin-bottom: 7px; }
    .fq-topic span { font-size: 12px; color: var(--muted); line-height: 1.45; }

    /* ── ACCORDION ──────────────────────────────────── */
    .fq-box { display: grid; grid-template-columns: minmax(0, 0.32fr) minmax(0, 1fr); gap: 30px; background: var(--bg-soft); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px; }
    .fq-tabs { display: flex; flex-direction: column; gap: 2px; }
    .fq-tab { text-align: left; padding: 12px 16px; border: none; background: none; font-family: inherit; font-size: 14.5px; font-weight: 700; color: var(--muted); cursor: pointer; border-radius: 10px; border-left: 3px solid transparent; transition: all .15s; }
    .fq-tab:hover { color: var(--ink); background: rgba(37,99,235,0.04); }
    .fq-tab.on { color: var(--blue); border-left-color: var(--blue); background: rgba(37,99,235,0.06); }
    .fq-panel { display: none; flex-direction: column; gap: 12px; }
    .fq-panel.on { display: flex; }
    .fq-item { background: #fff; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; transition: border-color .15s, box-shadow .15s; }
    .fq-item.open { border-color: rgba(37,99,235,0.35); box-shadow: var(--shadow-sm); }
    .fq-q { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 17px 20px; background: none; border: none; font-family: inherit; font-size: 15px; font-weight: 700; color: var(--ink); cursor: pointer; text-align: left; }
    .fq-item.open .fq-q { color: var(--blue); }
    .fq-q .chev { width: 18px; height: 18px; flex-shrink: 0; color: var(--muted); transition: transform .25s; }
    .fq-item.open .fq-q .chev { transform: rotate(180deg); color: var(--blue); }
    .fq-a { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
    .fq-item.open .fq-a { max-height: 320px; }
    .fq-a p { font-size: 14px; color: var(--muted); line-height: 1.7; margin: 0; padding: 0 20px 18px; }
    .fq-noresult { display: none; text-align: center; padding: 40px; color: var(--muted); font-size: 14px; }

    /* ── STILL NEED HELP ────────────────────────────── */
    .fq-help-wrap { padding: 14px 0; }
    .fq-help { background: var(--bg-soft); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 34px 40px; display: flex; align-items: center; gap: 26px; position: relative; overflow: hidden; }
    .fq-help-ic { width: 96px; height: 96px; flex-shrink: 0; filter: drop-shadow(0 14px 22px rgba(37,99,235,0.28)); }
    .fq-help-txt { flex: 1; min-width: 0; }
    .fq-help-txt h3 { font-size: 23px; font-weight: 800; color: var(--ink); }
    .fq-help-txt p { font-size: 14px; color: var(--muted); margin: 8px 0 16px; max-width: 360px; line-height: 1.55; }
    .fq-help-env { width: 88px; height: 88px; flex-shrink: 0; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.22)); }

    /* ── READY CTA ──────────────────────────────────── */
    .fq-ready { text-align: center; padding: 44px 0 80px; }
    .fq-ready h2 { font-size: 30px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; margin-top: 14px; }
    .fq-ready p { font-size: 15px; color: var(--muted); margin: 12px 0 26px; }
    .fq-ready-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    @media (max-width: 980px) {
        .fq-hero-grid { grid-template-columns: 1fr; gap: 28px; }
        .fq-hero-art { display: none; }
        .fq-topics { grid-template-columns: repeat(3, 1fr); }
        .fq-box { grid-template-columns: 1fr; gap: 20px; }
        .fq-tabs { flex-direction: row; flex-wrap: wrap; gap: 8px; }
        .fq-tab { border-left: none; border: 1px solid var(--line); }
        .fq-tab.on { border-color: var(--blue); }
        .fq-help { flex-direction: column; text-align: center; }
        .fq-h1 { font-size: 40px; }
    }
    @media (max-width: 560px) {
        .fq-topics { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

{{-- ════════════ HERO ════════════ --}}
<section class="fq-hero">
    <div class="lp-container fq-hero-grid">
        <div class="fq-hero-left">
            <span class="fq-eyebrow orange">FAQs</span>
            <h1 class="fq-h1">Questions?<br><span class="o">We've</span> <span class="b">Got Answers.</span></h1>
            <p class="sub">Find quick answers to the most common questions about using GigResource. If you need more help, our support team is always here for you.</p>
            <div class="fq-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="fqSearch" placeholder="Search questions...">
            </div>
        </div>

        <div class="fq-hero-art">
            <span class="fq-art-dots"></span>
            <div class="fq-doc">
                <div class="fq-doc-bar"><i class="l1"></i><i class="l2"></i><i class="l3"></i></div>
                <div class="fq-doc-row"><i></i><span></span></div>
                <div class="fq-doc-row"><i></i><span class="s"></span></div>
                <div class="fq-doc-row"><i></i><span></span></div>
                <div class="fq-doc-row"><i></i><span class="s"></span></div>
                <div class="fq-doc-row"><i></i><span></span></div>
            </div>
            <svg class="fq-faqbubble" viewBox="0 0 190 130" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="fqBub" x1="0" y1="0" x2="190" y2="130" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M20 8h150a16 16 0 0 1 16 16v60a16 16 0 0 1-16 16H70l-34 26 4-26H20A16 16 0 0 1 4 100V24A16 16 0 0 1 20 8Z" fill="url(#fqBub)"/>
                <text x="95" y="72" font-family="Plus Jakarta Sans, Inter, sans-serif" font-size="48" font-weight="800" fill="#fff" text-anchor="middle">FAQ</text>
            </svg>
            <svg class="fq-3d-chat" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="fqChat" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs>
                <path d="M12 8h40a6 6 0 0 1 6 6v22a6 6 0 0 1-6 6H28L16 52V42h-4a6 6 0 0 1-6-6V14a6 6 0 0 1 6-6Z" fill="url(#fqChat)"/>
                <circle cx="22" cy="25" r="3.4" fill="#fff"/><circle cx="32" cy="25" r="3.4" fill="#fff"/><circle cx="42" cy="25" r="3.4" fill="#fff"/>
            </svg>
            <svg class="fq-3d-mug" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 19h32v21a11 11 0 0 1-11 11H22a11 11 0 0 1-11-11V19Z" fill="#3b82f6"/>
                <path d="M43 23h4a7 7 0 0 1 0 14h-4" stroke="#2563eb" stroke-width="4" fill="none"/>
                <rect x="11" y="19" width="32" height="6" fill="#60a5fa"/>
            </svg>
            <svg class="fq-3d-plant" viewBox="0 0 66 88" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M33 48C28 39 20 35 13 37c2 8 10 14 20 14Z" fill="#10b981"/>
                <path d="M33 48c4-12 13-16 20-13-2 8-12 15-20 15Z" fill="#34d399"/>
                <path d="M33 50c-2-14 3-24 9-27 3 10-1 22-9 27Z" fill="#059669"/>
                <path d="M20 54h26l-3 26a4 4 0 0 1-4 3.5H27a4 4 0 0 1-4-3.5L20 54Z" fill="#fff" stroke="#e2e8f0"/>
                <path d="M18 50h30v6H18z" fill="#cbd5e1"/>
            </svg>
        </div>
    </div>
</section>

{{-- ════════════ BROWSE BY TOPIC ════════════ --}}
<section class="fq-section" style="padding-bottom: 10px;">
    <div class="lp-container">
        <div class="fq-head" style="margin-bottom: 30px;">
            <span class="fq-eyebrow pill">Browse by Topic</span>
        </div>
        <div class="fq-topics">
            @foreach($cats as $cat)
                <div class="fq-topic" data-cat="{{ $cat }}" role="button" tabindex="0">
                    <div class="fq-topic-ic">{!! $icons[$cat] ?? '' !!}</div>
                    <b>{{ $cat }}</b>
                    <span>{{ $topicMeta[$cat]['desc'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════ ACCORDION ════════════ --}}
<section class="fq-section" id="fqAccordion">
    <div class="lp-container">
        <div class="fq-box">
            <div class="fq-tabs">
                @foreach($cats as $i => $cat)
                    <button type="button" class="fq-tab {{ $i === 0 ? 'on' : '' }}" data-cat="{{ $cat }}">{{ $cat }}</button>
                @endforeach
            </div>
            <div class="fq-panels">
                @foreach($cats as $i => $cat)
                    <div class="fq-panel {{ $i === 0 ? 'on' : '' }}" data-cat="{{ $cat }}">
                        @foreach($grouped[$cat] as $j => $faq)
                            <div class="fq-item {{ $i === 0 && $j === 0 ? 'open' : '' }}" data-q="{{ \Illuminate\Support\Str::lower($faq->question.' '.$faq->answer) }}">
                                <button type="button" class="fq-q">
                                    {{ $faq->question }}
                                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div class="fq-a"><p>{{ $faq->answer }}</p></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <div class="fq-noresult" id="fqNoResult">No questions match your search. Try a different keyword or <a href="#" id="fqClear" style="color:var(--blue);font-weight:700;">view all</a>.</div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ STILL NEED HELP ════════════ --}}
<div class="fq-help-wrap">
    <div class="lp-container">
        <div class="fq-help">
            <svg class="fq-help-ic" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="fqHs" x1="0" y1="0" x2="96" y2="96" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M22 54v-6a26 26 0 0 1 52 0v6" stroke="url(#fqHs)" stroke-width="7" fill="none" stroke-linecap="round"/>
                <rect x="12" y="50" width="18" height="26" rx="9" fill="url(#fqHs)"/>
                <rect x="66" y="50" width="18" height="26" rx="9" fill="url(#fqHs)"/>
                <path d="M40 40h22a6 6 0 0 1 6 6v14a6 6 0 0 1-6 6H50l-9 7 1.5-7H40a6 6 0 0 1-6-6V46a6 6 0 0 1 6-6Z" fill="#fff" stroke="#e2e8f0"/>
                <circle cx="44" cy="53" r="2.4" fill="#2563eb"/><circle cx="51" cy="53" r="2.4" fill="#2563eb"/><circle cx="58" cy="53" r="2.4" fill="#2563eb"/>
            </svg>
            <div class="fq-help-txt">
                <h3>Still Need Help?</h3>
                <p>Can't find the answer you're looking for? Our support team is ready to assist you.</p>
                <a href="{{ route('about-us') }}" class="lp-btn lp-btn-blue">Contact Support
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <svg class="fq-help-env" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="fqEnv" x1="0" y1="0" x2="88" y2="88" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="12" y="26" width="64" height="46" rx="8" fill="url(#fqEnv)"/>
                <path d="M12 32l32 22 32-22" stroke="#fff" stroke-width="3.5" fill="none" stroke-linejoin="round"/>
                <path d="M12 32l32 22 32-22v-2a4 4 0 0 0-4-4H16a4 4 0 0 0-4 4v2Z" fill="#1d4ed8"/>
                <circle cx="68" cy="28" r="12" fill="#fbbf24"/>
                <path d="M68 22l1.7 3.5 3.8.5-2.8 2.7.7 3.8L68 30.8l-3.4 1.7.7-3.8-2.8-2.7 3.8-.5L68 22Z" fill="#fff"/>
                <path d="M80 60c6 4 9 2 12 8" stroke="#3b82f6" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-dasharray="1 5"/>
            </svg>
        </div>
    </div>
</div>

{{-- ════════════ READY CTA ════════════ --}}
<div class="fq-ready">
    <div class="lp-container">
        <span class="fq-eyebrow pill orange">Ready to Get Started?</span>
        <h2>Join Thousands of Event Professionals and Clients</h2>
        <p>Create your account today and start building incredible events together.</p>
        <div class="fq-ready-btns">
            <a href="{{ route('public.browse') }}" class="lp-btn lp-btn-blue">Find Professionals</a>
            <a href="{{ route('register') }}" class="lp-btn lp-btn-orange">Sign Up Now
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var tabs    = Array.prototype.slice.call(document.querySelectorAll('.fq-tab'));
    var panels  = Array.prototype.slice.call(document.querySelectorAll('.fq-panel'));
    var topics  = Array.prototype.slice.call(document.querySelectorAll('.fq-topic'));
    var items   = Array.prototype.slice.call(document.querySelectorAll('.fq-item'));
    var search  = document.getElementById('fqSearch');
    var noResult = document.getElementById('fqNoResult');

    function activate(cat) {
        tabs.forEach(function (t) { t.classList.toggle('on', t.dataset.cat === cat); });
        panels.forEach(function (p) { p.classList.toggle('on', p.dataset.cat === cat); });
    }

    tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.cat); }); });
    topics.forEach(function (t) {
        t.addEventListener('click', function () {
            activate(t.dataset.cat);
            document.getElementById('fqAccordion').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        t.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); t.click(); } });
    });

    // Accordion toggle
    items.forEach(function (item) {
        var q = item.querySelector('.fq-q');
        q.addEventListener('click', function () { item.classList.toggle('open'); });
    });

    // Global search
    function runSearch(query) {
        query = query.trim().toLowerCase();
        var box = document.querySelector('.fq-panels');
        if (!query) {
            box.classList.remove('searching');
            panels.forEach(function (p) { p.style.display = ''; });
            tabs.forEach(function (t) { t.style.display = ''; });
            items.forEach(function (it) { it.style.display = ''; it.classList.remove('open'); });
            // restore active panel via classes
            var active = tabs.find(function (t) { return t.classList.contains('on'); });
            activate(active ? active.dataset.cat : (tabs[0] && tabs[0].dataset.cat));
            if (noResult) noResult.style.display = 'none';
            return;
        }
        // search mode: show all panels, filter items
        var anyShown = 0;
        panels.forEach(function (p) { p.classList.add('on'); p.style.display = 'flex'; });
        tabs.forEach(function (t) { t.style.display = 'none'; });
        items.forEach(function (it) {
            var match = (it.dataset.q || '').indexOf(query) !== -1;
            it.style.display = match ? '' : 'none';
            it.classList.toggle('open', match);
            if (match) anyShown++;
        });
        if (noResult) noResult.style.display = anyShown ? 'none' : 'block';
    }
    if (search) search.addEventListener('input', function () { runSearch(this.value); });
    var clear = document.getElementById('fqClear');
    if (clear) clear.addEventListener('click', function (e) { e.preventDefault(); if (search) { search.value = ''; runSearch(''); } });
})();
</script>
@endpush

@endsection
