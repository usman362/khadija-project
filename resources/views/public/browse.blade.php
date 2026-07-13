@extends('layouts.landing')

@php
    use Illuminate\Support\Str;

    $seoTitle       = 'Browse Event Professionals | GigResource';
    $seoDescription = 'Find verified event professionals — DJs, photographers, caterers, planners, venues. Filter by category, city, rating and budget. Read real reviews and book with confidence.';

    $f       = $filters ?? [];
    $kw      = $f['q'] ?? '';
    $cityF   = $f['city'] ?? '';
    $ratingF = (float) ($f['rating_min'] ?? 0);
    $verF    = !empty($f['verified']);
    $sortF   = $f['sort'] ?? 'top';
    $total   = method_exists($pros, 'total') ? $pros->total() : $pros->count();

    // Trending "vibe" presets — each pre-populates the keyword search.
    $vibes = [
        ['Luxury Weddings',  'Fine dining, string quartets, drone photo',   'photo-1519741497674-611481863552', 'wedding'],
        ['Corporate Tech',   'Conference A/V, livestreaming, planners',     'photo-1505373877841-8d25f7d46678', 'corporate'],
        ['Neon Birthdays',   'Party DJs, photo booths, balloon backdrops',  'photo-1530103862676-de8c9debad1d', 'birthday'],
        ['Boho Baby Showers','Themed decor, pastry chefs, lifestyle photo', 'photo-1515488042361-ee00e0ddd4e4', 'baby shower'],
        ['Destination Events','Travel planners, local vendors, decor',      'photo-1469371670807-013ccf25f16a', 'destination'],
        ['Holiday Parties',  'Catering, DJs, lighting & entertainment',     'photo-1511578314322-379afb476865', 'holiday'],
    ];

    // Representative gallery fallbacks (used when a pro has no portfolio images).
    $stockGallery = [
        'photo-1519741497674-611481863552','photo-1465495976277-4387d4b0b4c6',
        'photo-1511578314322-379afb476865','photo-1511578314322-379afb476865',
    ];
@endphp

@section('content')

@push('styles')
<style>
    /* ════════════════════ /browse (light) — page-scoped ════════════════════ */
    .br-wrap { background: var(--bg-soft); }

    /* ── HERO ─────────────────────────────────────────── */
    .br-hero { position: relative; padding: 46px 0 54px; overflow: hidden;
        background:
            linear-gradient(180deg, rgba(255,255,255,.0), rgba(247,249,252,.6)),
            linear-gradient(110deg, rgba(37,99,235,.10), rgba(249,115,22,.08)); }
    .br-hero::before { content:''; position:absolute; inset:0;
        background-image: url('https://images.unsplash.com/photo-1519741497674-611481863552?w=1600&q=70&auto=format&fit=crop');
        background-size: cover; background-position: center; opacity: .12; z-index: 0; }
    .br-hero > .lp-container { position: relative; z-index: 1; }
    .br-h1 { font-size: 40px; font-weight: 800; letter-spacing: -1.1px; text-align: center; }
    .br-h1 .b { color: var(--blue); }
    .br-h1 .o { color: var(--orange); }
    .br-hero-sub { text-align: center; color: var(--text); font-size: 16px; margin: 12px 0 26px; }

    /* search bar */
    .br-search { display: flex; align-items: stretch; gap: 0; background: #fff;
        border: 1px solid var(--line); border-radius: 999px; padding: 7px 7px 7px 8px;
        max-width: 940px; margin: 0 auto; box-shadow: 0 18px 40px -22px rgba(15,27,53,.35); flex-wrap: wrap; }
    .br-sfield { display: flex; align-items: center; gap: 8px; padding: 8px 14px; flex: 1 1 0; min-width: 150px; position: relative; }
    .br-sfield + .br-sfield { border-left: 1px solid var(--line-soft); }
    .br-sfield svg { width: 16px; height: 16px; color: var(--blue); flex-shrink: 0; }
    .br-sfield select, .br-sfield input { border: none; outline: none; background: transparent; width: 100%;
        font-size: 14px; font-weight: 600; color: var(--ink-2); font-family: inherit; cursor: pointer; }
    .br-sfield input::placeholder { color: var(--muted); font-weight: 500; }
    .br-find { border: none; border-radius: 999px; padding: 0 24px; margin-left: 4px;
        background: linear-gradient(135deg, var(--orange), var(--orange-dark)); color: #fff;
        font-weight: 800; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .br-find svg { width: 16px; height: 16px; }

    /* trust badges row */
    .br-trustrow { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 22px auto 0; }
    .br-tb { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.7);
        border: 1px solid var(--line); border-radius: 999px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; color: var(--ink-2); }
    .br-tb svg { width: 14px; height: 14px; color: var(--blue); }

    /* ── VIBES carousel ───────────────────────────────── */
    .br-vibes { padding: 30px 0 8px; }
    .br-vibes-cap { text-align: center; color: var(--text); font-size: 14px; margin-bottom: 16px; }
    .br-vibes-cap a { color: var(--blue); font-weight: 700; }
    .br-vibe-scroll { display: grid; grid-auto-flow: column; grid-auto-columns: 200px; gap: 14px;
        overflow-x: auto; padding: 4px 2px 14px; scroll-snap-type: x mandatory; }
    .br-vibe-scroll::-webkit-scrollbar { height: 6px; }
    .br-vibe-scroll::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
    .br-vibe { position: relative; height: 132px; border-radius: 16px; overflow: hidden; scroll-snap-align: start;
        text-decoration: none; display: block; box-shadow: 0 10px 24px -16px rgba(15,27,53,.4); }
    .br-vibe img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .br-vibe:hover img { transform: scale(1.07); }
    .br-vibe-ov { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,27,53,0) 35%, rgba(15,27,53,.4) 62%, rgba(15,27,53,.86) 100%);
        display: flex; flex-direction: column; justify-content: flex-end; padding: 12px; }
    .br-vibe-ov h4 { color: #fff; font-size: 14px; font-weight: 800; }
    .br-vibe-ov span { color: rgba(255,255,255,.82); font-size: 10.5px; line-height: 1.35; margin-top: 3px; }

    /* ── MAIN 3-COLUMN ────────────────────────────────── */
    .br-main { display: grid; grid-template-columns: 260px minmax(0,1fr) 280px; gap: 22px; padding: 18px 0 60px; align-items: start; }
    .br-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; }

    /* filters sidebar */
    .br-filters { position: sticky; top: 84px; }
    .br-filters .br-fhead { display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 12px; border-bottom: 1px solid var(--line-soft); }
    .br-filters .br-fhead h3 { font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .br-filters .br-fhead h3 svg { width: 16px; height: 16px; color: var(--blue); }
    .br-clear { font-size: 12px; font-weight: 700; color: var(--blue); text-decoration: none; }
    .br-fgroup { padding: 14px 16px; border-bottom: 1px solid var(--line-soft); }
    .br-fgroup:last-child { border-bottom: none; }
    .br-fgroup > label.br-flabel { display: block; font-size: 12.5px; font-weight: 800; color: var(--ink); margin-bottom: 10px; letter-spacing: .2px; }
    .br-opt { display: flex; align-items: center; gap: 9px; padding: 5px 0; font-size: 13px; color: var(--text); cursor: pointer; }
    .br-opt input { accent-color: var(--blue); width: 15px; height: 15px; }
    .br-range { width: 100%; accent-color: var(--blue); }
    .br-range-vals { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--muted); margin-top: 4px; }
    .br-input { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 8px 10px; font-size: 13px; color: var(--ink-2); font-family: inherit; }
    .br-switch { width: 38px; height: 21px; border-radius: 999px; background: var(--line); position: relative; cursor: pointer; transition: background .15s; flex-shrink: 0; }
    .br-apply { display: block; border: none; border-radius: 11px; padding: 11px; margin: 14px 16px 16px; width: calc(100% - 32px);
        background: linear-gradient(135deg, var(--blue), var(--blue-dark)); color: #fff; font-weight: 800; font-size: 13.5px; cursor: pointer; }

    /* results */
    .br-results-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
    .br-found { font-size: 14px; color: var(--text); }
    .br-found b { color: var(--blue); font-weight: 800; }
    .br-results-tools { display: flex; align-items: center; gap: 8px; }
    .br-sort { border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: var(--ink-2); background: #fff; font-family: inherit; cursor: pointer; }
    .br-viewtoggle { display: inline-flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .br-viewtoggle button { border: none; background: #fff; padding: 8px 11px; font-size: 12.5px; font-weight: 700; color: var(--muted); display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
    .br-viewtoggle button.on { background: var(--bg-soft-2); color: var(--blue); }
    .br-viewtoggle svg { width: 14px; height: 14px; }

    /* provider card */
    .br-pro { display: grid; grid-template-columns: 280px minmax(0,1fr); gap: 0; overflow: hidden; margin-bottom: 16px; }
    .br-pro-media { position: relative; height: 230px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); overflow: hidden; }
    .br-pro-hero { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity .55s ease; }
    .br-pro-hero.on { opacity: 1; }
    .br-pro-tag { position: absolute; left: 10px; top: 10px; z-index: 2; background: rgba(255,255,255,.94); color: #0f1b35; font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 10px rgba(15,27,53,.18); text-transform: capitalize; }
    .br-pro-tag svg { width: 13px; height: 13px; color: #f97316; }
    .br-pro-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); z-index: 2; display: flex; gap: 5px; }
    .br-pro-dots i { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.55); transition: all .2s; }
    .br-pro-dots i.on { background: #fff; width: 16px; border-radius: 3px; }
    .br-pro-body { padding: 16px 18px; display: flex; flex-direction: column; }
    .br-pro-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .br-pro-name { font-size: 17px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
    .br-pro-name .vchk { width: 16px; height: 16px; color: var(--blue); }
    .br-pro-role { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .br-pro-loc { font-size: 12.5px; color: var(--text); margin-top: 6px; display: inline-flex; align-items: center; gap: 5px; }
    .br-pro-loc svg { width: 13px; height: 13px; color: var(--orange); }
    .br-fav { border: 1px solid var(--line); background: #fff; width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
    .br-fav svg { width: 16px; height: 16px; color: var(--muted); }
    .br-chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0; }
    .br-chip { font-size: 10.5px; font-weight: 800; padding: 4px 9px; border-radius: 6px; letter-spacing: .2px; }
    .br-chip.verif { background: #fef3e8; color: #c2590a; }
    .br-chip.top { background: #e8f0fe; color: var(--blue-dark); }
    .br-chip.quick { background: #e9f9f1; color: #0f9d58; }
    .br-pro-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12.5px; color: var(--text); margin: 4px 0 12px; }
    .br-pro-meta .star { color: #f5a623; font-weight: 800; }
    .br-pro-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--line-soft); flex-wrap: wrap; }
    .br-price { font-size: 13px; color: var(--muted); }
    .br-price b { font-size: 18px; color: var(--ink); font-weight: 800; }
    .br-pro-actions { display: flex; gap: 8px; }
    .br-btn-ghost { border: 1px solid var(--line); background: #fff; border-radius: 10px; padding: 9px 14px; font-size: 12.5px; font-weight: 700; color: var(--ink-2); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .br-btn-msg { border: none; border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--orange), var(--orange-dark)); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }

    .br-empty { padding: 60px 20px; text-align: center; color: var(--muted); }
    .br-empty svg { width: 46px; height: 46px; color: var(--line); margin-bottom: 14px; }
    .br-empty h3 { color: var(--ink); }

    /* pagination */
    .br-pager { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
    .br-pager a, .br-pager span { min-width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px;
        font-size: 13px; font-weight: 700; color: var(--ink-2); border: 1px solid var(--line); background: #fff; text-decoration: none; }
    .br-pager .cur { background: var(--blue); border-color: var(--blue); color: #fff; }
    .br-pager .dis { color: var(--line); cursor: default; }

    /* right rail */
    .br-rail { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 16px; }
    .br-rail-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
    .br-rail-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--line-soft); font-size: 13px; font-weight: 800; color: var(--ink); }
    .br-rail-head svg { width: 15px; height: 15px; color: var(--blue); }
    .br-rail-head a { font-size: 11.5px; font-weight: 700; color: var(--blue); text-decoration: none; }
    .br-map { position: relative; height: 170px; background: #eef3fb; }
    .br-map-fallback { position: absolute; inset: 0; background:
        repeating-linear-gradient(0deg, var(--line-soft) 0 1px, transparent 1px 28px),
        repeating-linear-gradient(90deg, var(--line-soft) 0 1px, transparent 1px 28px); }
    .br-map-pin { position: absolute; width: 22px; height: 22px; border-radius: 50% 50% 50% 0; background: var(--blue); transform: rotate(-45deg); box-shadow: 0 4px 10px rgba(37,99,235,.4); }
    .br-map-cluster { position: absolute; left: 50%; top: 48%; transform: translate(-50%,-50%); background: var(--orange); color: #fff; font-weight: 800; font-size: 13px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; box-shadow: 0 6px 16px rgba(249,115,22,.5); z-index: 2; }
    .br-mini { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--line-soft); }
    .br-mini:last-of-type { border-bottom: none; }
    .br-mini-av { width: 34px; height: 34px; border-radius: 9px; object-fit: cover; flex-shrink: 0; }
    .br-mini-main { min-width: 0; flex: 1; }
    .br-mini-main h5 { font-size: 12.5px; font-weight: 800; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .br-mini-main span { font-size: 11px; color: var(--muted); }
    .br-mini-rate { font-size: 11.5px; font-weight: 800; color: var(--blue); flex-shrink: 0; }
    .br-rail-btn { display: block; margin: 10px 14px 14px; text-align: center; border-radius: 10px; padding: 9px; font-size: 12.5px; font-weight: 800; text-decoration: none; }
    .br-rail-btn.blue { background: linear-gradient(135deg, var(--blue), var(--blue-dark)); color: #fff; }
    .br-recent { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; padding: 12px 14px; }
    .br-recent a { display: block; text-decoration: none; }
    .br-recent img { width: 100%; height: 50px; border-radius: 8px; object-fit: cover; }
    .br-recent span { display: block; font-size: 10px; color: var(--text); font-weight: 700; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* CTA + trust strip */
    .br-cta { margin: 8px 0 0; border-radius: 20px; padding: 30px 34px; position: relative; overflow: hidden;
        background: linear-gradient(120deg, #eaf1ff, #f4f7ff); border: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
    .br-cta h3 { font-size: 24px; font-weight: 800; }
    .br-cta p { color: var(--text); font-size: 14px; margin: 8px 0 16px; max-width: 460px; }
    .br-cta-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .br-cta-emoji { font-size: 78px; line-height: 1; }
    .br-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; padding: 28px 0 0; }
    .br-strip-item { display: flex; align-items: flex-start; gap: 11px; }
    .br-strip-item svg { width: 26px; height: 26px; color: var(--blue); flex-shrink: 0; }
    .br-strip-item h4 { font-size: 13.5px; font-weight: 800; }
    .br-strip-item p { font-size: 12px; color: var(--muted); margin-top: 3px; line-height: 1.4; }

    @media (max-width: 1080px) {
        .br-main { grid-template-columns: minmax(0,1fr); }
        .br-filters, .br-rail { position: static; }
        .br-rail { display: grid; grid-template-columns: 1fr 1fr; align-items: start; }
    }
    @media (max-width: 720px) {
        .br-h1 { font-size: 30px; }
        .br-pro { grid-template-columns: minmax(0,1fr); }
        .br-pro-hero { min-height: 180px; }
        .br-rail { grid-template-columns: 1fr; }
        .br-strip { grid-template-columns: 1fr 1fr; }
        .br-search { border-radius: 18px; }
        .br-sfield + .br-sfield { border-left: none; border-top: 1px solid var(--line-soft); }
    }
</style>
@endpush

<div class="br-wrap">

    {{-- ══════════════ HERO ══════════════ --}}
    <section class="br-hero">
        <div class="lp-container">
            <h1 class="br-h1">Find Your <span class="b">Vibe</span>. Book the <span class="o">Pro</span>. ✨</h1>
            <p class="br-hero-sub">Every verified event professional, right at your fingertips.</p>

            <form action="{{ route('public.browse') }}" method="GET" class="br-search">
                <div class="br-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                    <select name="q" aria-label="Category">
                        <option value="">All Services</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->name }}" @selected($kw === $cat->name)>{{ Str::title($cat->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="br-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input type="text" name="city" value="{{ $cityF }}" placeholder="City or location" list="br-cities" autocomplete="off">
                    <datalist id="br-cities">@foreach($cities as $c)<option value="{{ $c }}">@endforeach</datalist>
                </div>
                <div class="br-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <input type="text" name="date" placeholder="Any date" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'">
                </div>
                <div class="br-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                    <select name="distance" aria-label="Distance">
                        <option>Within 25 miles</option><option>Within 50 miles</option><option>Within 100 miles</option><option>Anywhere</option>
                    </select>
                </div>
                <button type="submit" class="br-find">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Find Talent
                </button>
            </form>

            {{-- Trust badges — compliance-safe (no "24/7", no reply-time metric) --}}
            <div class="br-trustrow">
                <span class="br-tb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> License-Verified Pros</span>
                <span class="br-tb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Escrow-Protected Booking</span>
                <span class="br-tb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Quick Reply Times</span>
                <span class="br-tb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg> Dedicated Support</span>
            </div>
        </div>
    </section>

    {{-- ══════════════ TRENDING VIBES ══════════════ --}}
    <section class="br-vibes">
        <div class="lp-container">
            <p class="br-vibes-cap">Stuck on planning? Tap a <a href="#">trending vibe</a> to auto-populate your search filters.</p>
            <div class="br-vibe-scroll">
                @foreach($vibes as [$vName, $vSub, $vImg, $vQuery])
                    <a class="br-vibe" href="{{ route('public.browse', ['q' => $vQuery]) }}">
                        <img src="https://images.unsplash.com/{{ $vImg }}?w=420&q=70&auto=format&fit=crop" alt="{{ $vName }}" loading="lazy">
                        <span class="br-vibe-ov"><h4>{{ $vName }}</h4><span>{{ $vSub }}</span></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════ MAIN ══════════════ --}}
    <div class="lp-container">
        <div class="br-main">

            {{-- ── LEFT: POWER FILTERS ── --}}
            <aside class="br-filters">
                <form action="{{ route('public.browse') }}" method="GET" class="br-card">
                    @if($kw)<input type="hidden" name="q" value="{{ $kw }}">@endif
                    @if($cityF)<input type="hidden" name="city" value="{{ $cityF }}">@endif
                    <div class="br-fhead">
                        <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg> Power Filters</h3>
                        <a href="{{ route('public.browse') }}" class="br-clear">Clear All</a>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Price Range</label>
                        <input type="range" class="br-range" min="0" max="5000" value="2500" disabled>
                        <div class="br-range-vals"><span>$0</span><span>$5,000+</span></div>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Availability</label>
                        <input type="text" class="br-input" placeholder="Select date" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'">
                        <label class="br-opt" style="margin-top:8px;"><input type="checkbox"> Only show available pros</label>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Distance Radius</label>
                        <select class="br-input"><option>Within 25 miles</option><option>Within 50 miles</option><option>Within 100 miles</option><option>Anywhere</option></select>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Rating &amp; Reviews</label>
                        <label class="br-opt"><input type="radio" name="rating_min" value="5" @checked($ratingF == 5)> 5.0 ★ Perfectionists Only</label>
                        <label class="br-opt"><input type="radio" name="rating_min" value="4.5" @checked($ratingF == 4.5)> 4.5 &amp; Up (Top Rated)</label>
                        <label class="br-opt"><input type="radio" name="rating_min" value="0" @checked($ratingF == 0)> Any Rating</label>
                        <label class="br-opt"><input type="checkbox"> Reviews with Photos</label>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Response Time</label>
                        <label class="br-opt"><input type="radio" name="resp" disabled> Within 1 Hour</label>
                        <label class="br-opt"><input type="radio" name="resp" disabled checked> Within a Few Hours</label>
                        <label class="br-opt"><input type="radio" name="resp" disabled> Anytime</label>
                    </div>

                    <div class="br-fgroup">
                        <label class="br-flabel">Other Filters</label>
                        <label class="br-opt"><input type="checkbox" name="verified" value="1" @checked($verF)> Verified Pro Badge</label>
                        <label class="br-opt"><input type="checkbox" disabled> Insurance Coverage</label>
                        <label class="br-opt"><input type="checkbox" disabled> Available for Travel</label>
                        <label class="br-opt"><input type="checkbox" disabled> Eco-Friendly Vendors</label>
                    </div>

                    <input type="hidden" name="sort" value="{{ $sortF }}">
                    <button type="submit" class="br-apply">Apply Filters</button>
                </form>
            </aside>

            {{-- ── CENTER: RESULTS ── --}}
            <main class="br-results">
                <div class="br-results-head">
                    <div class="br-found">Found: <b>{{ $total }} {{ Str::plural('Pro', $total) }}</b>{{ $cityF ? ' near '.$cityF : '' }}{{ $kw ? ' for “'.Str::title($kw).'”' : '' }}</div>
                    <div class="br-results-tools">
                        <form action="{{ route('public.browse') }}" method="GET" id="brSortForm">
                            @if($kw)<input type="hidden" name="q" value="{{ $kw }}">@endif
                            @if($cityF)<input type="hidden" name="city" value="{{ $cityF }}">@endif
                            @if($ratingF)<input type="hidden" name="rating_min" value="{{ $ratingF }}">@endif
                            @if($verF)<input type="hidden" name="verified" value="1">@endif
                            <select name="sort" class="br-sort" onchange="document.getElementById('brSortForm').submit()">
                                <option value="top" @selected($sortF==='top')>Sort by: Top-Rated</option>
                                <option value="rating" @selected($sortF==='rating')>Sort by: Highest Rating</option>
                                <option value="newest" @selected($sortF==='newest')>Sort by: Newest</option>
                            </select>
                        </form>
                        <div class="br-viewtoggle">
                            <button type="button" class="on"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Grid</button>
                            <button type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> List</button>
                        </div>
                    </div>
                </div>

                @forelse($pros as $i => $pro)
                    @php
                        $p = $pro->profile;
                        $avg = round((float) ($pro->reviews_avg ?? 0), 1);
                        $cnt = (int) ($pro->reviews_count ?? 0);
                        $isVerified = $p && $p->trade_license_verified_at && $p->liability_insurance_verified_at && $p->workers_comp_verified_at;
                        $isTop = $avg >= 4.5 && $cnt > 0;
                        $gallery = collect($p ? $p->portfolioHeroUrls(4) : []);
                        $rate = $p?->hourly_rate;
                        $heroImg = $gallery->first() ?: 'https://images.unsplash.com/'.$stockGallery[$i % count($stockGallery)].'?w=560&q=72&auto=format&fit=crop';
                    @endphp
                    @php
                        $bg = $gallery->isNotEmpty()
                            ? $gallery
                            : collect($stockGallery)->map(fn ($id) => 'https://images.unsplash.com/'.$id.'?w=560&q=72&auto=format&fit=crop');
                        $bg = $bg->take(4)->values();
                        $skillsB = is_array($p?->skills) ? array_values(array_filter($p->skills)) : [];
                        $catB = $skillsB[0] ?? ($p?->industry ?: 'Event Pro');
                    @endphp
                    <article class="br-card br-pro">
                        <div class="br-pro-media">
                            @foreach($bg as $gi => $img)
                                <img class="br-pro-hero {{ $gi === 0 ? 'on' : '' }}" src="{{ $img }}" alt="{{ $pro->name }}" loading="lazy">
                            @endforeach
                            <span class="br-pro-tag">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                {{ \Illuminate\Support\Str::limit($catB, 18) }}
                            </span>
                            @if($bg->count() > 1)
                                <div class="br-pro-dots">@foreach($bg as $gi => $x)<i class="{{ $gi === 0 ? 'on' : '' }}"></i>@endforeach</div>
                            @endif
                        </div>
                        <div class="br-pro-body">
                            <div class="br-pro-top">
                                <div>
                                    <div class="br-pro-name">{{ $pro->name }}@if($isVerified)<svg class="vchk" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>@endif</div>
                                    <div class="br-pro-role">{{ $p?->headline ?? $p?->company_name ?? 'Event Professional' }}</div>
                                    <div class="br-pro-loc">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $p?->city ?? 'Location on request' }}
                                    </div>
                                </div>
                                <button type="button" class="br-fav" aria-label="Save to favorites"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
                            </div>

                            <div class="br-chips">
                                @if($isVerified)<span class="br-chip verif">VERIFIED PRO</span>@endif
                                @if($isTop)<span class="br-chip top">TOP RATED</span>@endif
                                <span class="br-chip quick">QUICK RESPONDER</span>
                            </div>

                            <div class="br-pro-meta">
                                @if($cnt > 0)
                                    <span><span class="star">★ {{ number_format($avg, 1) }}</span> ({{ $cnt }} {{ Str::plural('review', $cnt) }})</span>
                                @else
                                    <span class="star">★ New on GigResource</span>
                                @endif
                                <span>Recommended by past clients</span>
                            </div>

                            <div class="br-pro-foot">
                                <div class="br-price">
                                    @if($rate)
                                        Starting at <b>${{ number_format($rate) }}</b> / hr
                                    @else
                                        <b>Request a quote</b>
                                    @endif
                                </div>
                                <div class="br-pro-actions">
                                    <a href="{{ route('public.professional.show', $pro) }}" class="br-btn-ghost">View Portfolio</a>
                                    <a href="{{ route('public.professional.show', $pro) }}" class="br-btn-msg">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Message
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="br-card br-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <h3>No professionals match your filters yet</h3>
                        <p>Try widening your search — or post your event and let pros come to you.</p>
                        <a href="{{ route('public.browse') }}" class="br-btn-ghost" style="margin-top:14px;">Reset filters</a>
                    </div>
                @endforelse

                @if($pros->hasPages())
                    <nav class="br-pager">
                        @if($pros->onFirstPage())
                            <span class="dis">‹</span>
                        @else
                            <a href="{{ $pros->previousPageUrl() }}">‹</a>
                        @endif
                        @foreach($pros->getUrlRange(1, $pros->lastPage()) as $page => $url)
                            @if($page == $pros->currentPage())
                                <span class="cur">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if($pros->hasMorePages())
                            <a href="{{ $pros->nextPageUrl() }}">Next ›</a>
                        @else
                            <span class="dis">Next ›</span>
                        @endif
                    </nav>
                @endif
            </main>

            {{-- ── RIGHT: RAIL ── --}}
            <aside class="br-rail">
                <div class="br-rail-card">
                    <div class="br-rail-head"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Venue / Location Map</span></div>
                    <div class="br-map">
                        <div class="br-map-fallback"></div>
                        <span class="br-map-pin" style="left:24%;top:30%;"></span>
                        <span class="br-map-pin" style="left:70%;top:38%;"></span>
                        <span class="br-map-pin" style="left:38%;top:66%;"></span>
                        <span class="br-map-pin" style="left:78%;top:70%;"></span>
                        <span class="br-map-cluster">{{ min($total, 99) }}</span>
                    </div>
                </div>

                <div class="br-rail-card">
                    <div class="br-rail-head"><span>Compare Pros</span><a href="#">Clear All</a></div>
                    @foreach($pros->take(2) as $cp)
                        <div class="br-mini">
                            <img class="br-mini-av" src="{{ $cp->avatar_url }}" alt="">
                            <div class="br-mini-main"><h5>{{ $cp->name }}</h5><span>{{ $cp->profile?->city ?? '—' }}</span></div>
                            <span class="br-mini-rate">{{ $cp->profile?->hourly_rate ? '$'.number_format($cp->profile->hourly_rate) : '—' }}</span>
                        </div>
                    @endforeach
                    <a href="#" class="br-rail-btn blue">Compare Now</a>
                </div>

                <div class="br-rail-card">
                    <div class="br-rail-head"><span>Recently Viewed</span></div>
                    <div class="br-recent">
                        @foreach($pros->take(3) as $rv)
                            @php $rvImg = collect(is_array($rv->profile?->portfolio) ? $rv->profile->portfolio : [])->first(); @endphp
                            <a href="{{ route('public.professional.show', $rv) }}">
                                <img src="{{ $rvImg ?: 'https://images.unsplash.com/'.$stockGallery[$loop->index % count($stockGallery)].'?w=200&q=60&auto=format&fit=crop' }}" alt="">
                                <span>{{ $rv->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>

        {{-- ══════════════ CTA ══════════════ --}}
        <section class="br-cta">
            <div>
                <h3>Can't find what you're looking for?</h3>
                <p>Post your event and let verified professionals come to you. Describe your needs and receive proposals from top-rated experts.</p>
                <div class="br-cta-actions">
                    <a href="{{ route('register') }}" class="br-btn-msg" style="padding:11px 20px;">Post an Event</a>
                    <a href="{{ route('register') }}" class="br-btn-ghost" style="padding:11px 20px;">Join as Professional</a>
                </div>
            </div>
            <div class="br-cta-emoji">📅</div>
        </section>

        {{-- ══════════════ TRUST STRIP ══════════════ --}}
        <section class="br-strip">
            <div class="br-strip-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div><h4>Secure Bookings</h4><p>Escrow-protected payments for your peace of mind.</p></div>
            </div>
            <div class="br-strip-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                <div><h4>Verified Professionals</h4><p>Background &amp; license checks before pros go live.</p></div>
            </div>
            <div class="br-strip-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9 17 14 19 21 12 17 5 21 7 14 2 9 9 9 12 2"/></svg>
                <div><h4>Real Reviews</h4><p>Honest reviews from real, verified customers.</p></div>
            </div>
            <div class="br-strip-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/></svg>
                <div><h4>Dedicated Support</h4><p>A real team, here to help when you need it.</p></div>
            </div>
        </section>
    </div>
</div>

<script>
    // Hover carousel for pro cards — cycle portfolio images on hover.
    (function () {
        document.querySelectorAll('.br-pro-media').forEach(function (media) {
            var imgs = media.querySelectorAll('.br-pro-hero');
            var dots = media.querySelectorAll('.br-pro-dots i');
            if (imgs.length < 2) return;
            var i = 0, t = null;
            function show(n) {
                imgs[i].classList.remove('on'); if (dots[i]) dots[i].classList.remove('on');
                i = (n + imgs.length) % imgs.length;
                imgs[i].classList.add('on'); if (dots[i]) dots[i].classList.add('on');
            }
            var card = media.closest('.br-pro');
            card.addEventListener('mouseenter', function () { t = setInterval(function () { show(i + 1); }, 1400); });
            card.addEventListener('mouseleave', function () { clearInterval(t); t = null; show(0); });
        });
    })();
</script>
@endsection
