@extends('layouts.landing')

@php
    use Illuminate\Support\Str;

    $seoTitle       = 'Explore Event Categories | GigResource';
    $seoDescription = 'Explore every event category we cover — weddings, corporate events, birthdays, festivals, conferences and more. Browse real categories and find the right professionals for your occasion.';

    // Event types (v2) / parent categories (v1). Service categories are a
    // separate list — they are not children of event types.
    $cats        = $allCategories ?? collect();
    $serviceCats = $serviceCategories ?? collect();
    $isV2        = $isV2 ?? $serviceCats->isNotEmpty();

    // Real category image → full URL, or a neutral fallback. Two shapes of
    // asset live on a category: a square photo thumbnail and a wide promo
    // banner with the name baked in. Cards always take the thumbnail — a
    // banner shrunk into a card reads as a shouty graphic.
    $fallbackImg = 'https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80&auto=format&fit=crop';
    $thumbUrl = function ($c) use ($fallbackImg) {
        $f = $c->thumbnail ?? null;
        return $f ? asset('storage/' . $f) : $fallbackImg;
    };

    // "Top Services" = real sub-categories that have imagery. Prices are
    // REPRESENTATIVE starting points ("from $X"), not guarantees.
    $svcBadges = [['POPULAR', 'o', 'featured'], ['FEATURED', 'b', 'featured'], ['HOT', 'h', 'hot'], ['NEW', 'n', 'new']];
    $svcPrices = [450, 180, 600, 800, 350, 120, 140, 200];
    // The level-2 service groups only ever had wide banner art, so pull the
    // leaf services instead — they carry real square thumbnails and read more
    // specifically ("DJs Services" beats "Entertainment & Activities" here).
    $topServices = \App\Models\Category::query()
        ->whereNotNull('parent_id')
        ->whereNotNull('thumbnail')
        ->where('is_active', true)
        ->orderBy('sort_order')->orderBy('name')
        ->limit(24)->get()
        ->unique('name')
        ->take(8)->values()
        ->map(function ($c, $i) use ($thumbUrl, $svcBadges, $svcPrices) {
            [$badge, $badgeClass, $group] = $svcBadges[$i % count($svcBadges)];
            return [
                'name'  => $c->name,
                'image' => $thumbUrl($c),
                'badge' => $badge, 'badgeClass' => $badgeClass, 'group' => $group,
                'sub'   => Str::limit(strip_tags((string) $c->short_description), 42) ?: 'Browse specialists',
                'from'  => $svcPrices[$i % count($svcPrices)],
                'slug'  => $c->slug,
            ];
        });
@endphp

@push('styles')
<style>
    /* ════════════════ /events-categories (light) — page-scoped .ec- ════════════════ */
    .ec-wrap { background: var(--bg-soft); }

    /* ── HERO ─────────────────────────────────────────── */
    .ec-hero { position: relative; padding: 48px 0 44px; overflow: hidden;
        background:
            linear-gradient(180deg, rgba(255,255,255,0), rgba(247,249,252,.6)),
            linear-gradient(110deg, rgba(37,99,235,.10), rgba(249,115,22,.08)); }
    .ec-hero::before { content: ''; position: absolute; inset: 0;
        background-image: url('https://images.unsplash.com/photo-1519741497674-611481863552?w=1600&q=70&auto=format&fit=crop');
        background-size: cover; background-position: center; opacity: .12; z-index: 0; }
    .ec-hero > .lp-container { position: relative; z-index: 1; }
    .ec-h1 { font-size: 40px; font-weight: 800; letter-spacing: -1.1px; text-align: center; }
    .ec-h1 .b { background: linear-gradient(135deg, #8b5cf6, #ec4899 52%, #f97316); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .ec-h1 .o { color: var(--orange); }
    .ec-hero-sub { text-align: center; color: var(--text); font-size: 16px; margin: 12px auto 26px; max-width: 640px; }

    /* search bar */
    .ec-search { display: flex; align-items: stretch; gap: 0; background: #fff;
        border: 1px solid var(--line); border-radius: 999px; padding: 7px 7px 7px 8px;
        max-width: 680px; margin: 0 auto; box-shadow: 0 18px 40px -22px rgba(15,27,53,.35); flex-wrap: wrap; }
    .ec-sfield { display: flex; align-items: center; gap: 8px; padding: 8px 14px; flex: 1 1 0; min-width: 180px; }
    .ec-sfield svg { width: 16px; height: 16px; color: var(--blue); flex-shrink: 0; }
    .ec-sfield input { border: none; outline: none; background: transparent; width: 100%;
        font-size: 14px; font-weight: 600; color: var(--ink-2); font-family: inherit; }
    .ec-sfield input::placeholder { color: var(--muted); font-weight: 500; }
    .ec-find { border: none; border-radius: 999px; padding: 0 24px; margin-left: 4px;
        background: linear-gradient(135deg, var(--orange), var(--orange-dark)); color: #fff;
        font-weight: 800; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .ec-find svg { width: 16px; height: 16px; }

    /* ── FILTER BAR ────────────────────────────────────── */
    .ec-filterbar { background: #fff; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .ec-filterbar > .lp-container { display: flex; align-items: center; gap: 14px;
        flex-wrap: wrap; padding-top: 16px; padding-bottom: 16px; }
    .ec-fb-selects { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .ec-select { appearance: none; border: 1px solid var(--line); border-radius: 999px;
        padding: 9px 34px 9px 15px; font-size: 13px; font-weight: 700; color: var(--ink-2);
        background: #fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237a8699' stroke-width='2.5'><polyline points='6 9 12 15 18 9'/></svg>") no-repeat right 12px center;
        background-size: 14px; font-family: inherit; cursor: pointer; }

    /* ── SECTION HEADS ─────────────────────────────────── */
    .ec-section { padding: 34px 0; }
    .ec-shead { margin-bottom: 20px; display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .ec-shead h2 { font-size: 26px; font-weight: 800; letter-spacing: -.6px; }
    .ec-shead h2 .b { background: linear-gradient(135deg, #8b5cf6, #ec4899 52%, #f97316); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .ec-shead h2 .o { background: linear-gradient(135deg, #8b5cf6, #ec4899 52%, #f97316); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; }
    .ec-shead p { color: var(--text); font-size: 14.5px; margin-top: 6px; }

    /* tab rows (reusable) */
    .ec-tabs { display: inline-flex; background: var(--bg-soft-2, #eef2f8); border: 1px solid var(--line);
        border-radius: 999px; padding: 4px; gap: 2px; flex-wrap: wrap; }
    .ec-tab { border: none; background: transparent; border-radius: 999px; padding: 7px 15px;
        font-size: 12.5px; font-weight: 700; color: var(--ink-2); cursor: pointer; font-family: inherit; transition: all .15s; }
    .ec-tab.active { background: #fff; color: var(--blue); box-shadow: 0 4px 12px -6px rgba(15,27,53,.4); }

    /* ── BROWSE ALL CATEGORIES: tree panel + paginated grid ─────────── */
    .ec-shop { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    .ec-shop-left { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 14px;
        box-shadow: 0 12px 30px -24px rgba(15,27,53,.5); position: sticky; top: 82px; }

    .ec-side-search { position: relative; margin-bottom: 14px; }
    .ec-side-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 16px; height: 16px; color: var(--muted); pointer-events: none; }
    .ec-side-search input { width: 100%; border: 1px solid var(--line); border-radius: 11px;
        padding: 10px 12px 10px 36px; font-size: 13.5px; color: var(--ink); background: var(--bg-soft, #f7f9fc);
        outline: none; transition: border-color .15s, box-shadow .15s; }
    .ec-side-search input:focus { border-color: var(--blue); background: #fff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12); }

    .ec-side-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px;
        color: var(--muted); padding: 0 4px 8px; }
    .ec-side-title-mt { margin-top: 16px; border-top: 1px solid var(--line-soft, var(--line)); padding-top: 14px; }
    .ec-side-none { font-size: 13px; color: var(--muted); padding: 0 4px; }

    /* Event types and services are two lists now. Each scrolls on its own
       so 106 occasions don't bury Catering under a single heading. */
    .ec-tree { max-height: 460px; overflow-y: auto; padding-right: 4px; }
    .ec-shop-left:has(#ecServiceTree) #ecTree,
    #ecServiceTree { max-height: 280px; }
    .ec-tree::-webkit-scrollbar { width: 7px; }
    .ec-tree::-webkit-scrollbar-thumb { background: var(--line); border-radius: 99px; }
    .ec-tree-nested { margin-left: 13px; padding-left: 9px; border-left: 1px solid var(--line-soft, var(--line)); }
    .ec-tree-row { display: flex; align-items: center; gap: 2px; border-radius: 9px; transition: background .12s; }
    .ec-tree-row:hover { background: var(--bg-soft-2, #eef2f8); }
    .ec-tree-row.active { background: rgba(37,99,235,.10); box-shadow: inset 0 0 0 1px rgba(37,99,235,.25); }
    .ec-tree-toggle { width: 20px; height: 26px; flex-shrink: 0; display: flex; align-items: center;
        justify-content: center; background: none; border: 0; padding: 0; cursor: pointer; color: var(--muted); }
    .ec-tree-toggle svg { width: 13px; height: 13px; transition: transform .15s; }
    .ec-tree-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }
    .ec-tree-leaf { cursor: default; }
    .ec-tree-link { flex: 1; min-width: 0; display: flex; align-items: center; gap: 7px;
        padding: 5px 8px 5px 2px; text-decoration: none; }
    .ec-tree-ico { width: 14px; height: 14px; flex-shrink: 0; color: var(--orange); }
    .ec-tree-nested .ec-tree-ico { color: var(--blue-light, var(--blue)); }
    .ec-tree-nested .ec-tree-nested .ec-tree-ico { color: var(--muted); }
    .ec-tree-link span { font-size: 13px; font-weight: 600; color: var(--ink-2); line-height: 1.25;
        overflow: hidden; text-overflow: ellipsis; }
    .ec-tree-node > .ec-tree-row > .ec-tree-link span { font-weight: 700; }
    .ec-tree-nested .ec-tree-link span { font-size: 12.5px; font-weight: 600; }
    .ec-tree-row:hover .ec-tree-link span { color: var(--blue); }
    .ec-tree-row.active .ec-tree-link span { color: var(--blue); }

    .ec-stats { display: flex; flex-direction: column; gap: 8px; padding: 0 4px; }
    .ec-stat { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .ec-stat span { font-size: 12.5px; font-weight: 600; color: var(--muted); }
    .ec-stat b { font-size: 11.5px; font-weight: 800; color: #fff; border-radius: 999px;
        padding: 3px 10px; min-width: 34px; text-align: center; }
    .ec-stat b.v-b { background: var(--blue); }
    .ec-stat b.v-o { background: var(--orange); }
    .ec-stat b.v-g { background: #0f9d58; }
    .ec-stat b.v-n { background: var(--ink-2); }

    .ec-shop-right { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    .ec-fchips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .ec-fchip { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--line);
        border-radius: 999px; padding: 6px 8px 6px 13px; font-size: 12.5px; font-weight: 600; color: var(--muted); }
    .ec-fchip b { color: var(--ink); font-weight: 800; }
    .ec-fchip a { width: 19px; height: 19px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; background: var(--bg-soft-2, #eef2f8); color: var(--ink-2);
        text-decoration: none; font-size: 14px; line-height: 1; }
    .ec-fchip a:hover { background: var(--orange); color: #fff; }
    .ec-fchip-clear { font-size: 12.5px; font-weight: 700; color: var(--blue); text-decoration: none; }
    .ec-fchip-clear:hover { text-decoration: underline; }

    .ec-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .ec-card { display: flex; flex-direction: column; background: #fff; border: 1px solid var(--line);
        border-radius: 16px; overflow: hidden; text-decoration: none;
        box-shadow: 0 10px 24px -20px rgba(15,27,53,.5); transition: transform .15s, box-shadow .15s; }
    .ec-card:hover { transform: translateY(-3px); box-shadow: 0 20px 38px -22px rgba(15,27,53,.55); }
    /* 6:5 matches the service artwork exactly and trims only 8% off a square
       occasion photo — enough to fill the box, never enough to slice the
       category name that the legacy art bakes into the image. */
    .ec-card-img { position: relative; aspect-ratio: 6 / 5; overflow: hidden; background: var(--bg-soft-2, #eef2f8); }
    .ec-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
    /* Wide promo banner in a 6:5 box. Cropping to fill would slice 25% off each
       side, straight through the lettering the artwork is built around, so the
       banner stays whole and a blurred, scaled copy of itself fills the space
       around it — the card reads as full-bleed with nothing cut off. */
    .ec-card-img.is-banner::before { content: ''; position: absolute; inset: 0;
        background-image: var(--ec-bg); background-size: cover; background-position: center;
        filter: blur(20px) saturate(1.25); transform: scale(1.25); }
    .ec-card-img.is-banner img { position: relative; object-fit: contain; }
    .ec-card:hover .ec-card-img img { transform: scale(1.06); }
    .ec-card-count { position: absolute; bottom: 9px; right: 9px; font-size: 10.5px; font-weight: 800;
        color: #fff; background: rgba(15,27,53,.72); backdrop-filter: blur(4px);
        border-radius: 7px; padding: 4px 9px; }
    .ec-card-body { padding: 12px 14px 14px; display: flex; flex-direction: column; flex: 1; }
    .ec-card-parent { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;
        letter-spacing: .4px; }
    .ec-card-body h3 { font-size: 15px; font-weight: 800; color: var(--ink); margin: 3px 0 0; line-height: 1.25; }
    .ec-card-body p { font-size: 12.5px; color: var(--muted); margin: 6px 0 0; line-height: 1.5; }
    .ec-card-go { margin-top: auto; padding-top: 12px; display: inline-flex; align-items: center; gap: 6px;
        font-size: 12.5px; font-weight: 800; color: var(--blue); }
    .ec-card-go svg { width: 13px; height: 13px; transition: transform .15s; }
    .ec-card:hover .ec-card-go svg { transform: translateX(3px); }

    /* ── AJAX paging: cross-fade the grid instead of reloading the page ── */
    .ec-grid { transition: opacity .18s ease; }
    #ecResults[aria-busy="true"] .ec-grid,
    #ecResults[aria-busy="true"] .ec-empty { opacity: .35; pointer-events: none; }
    #ecResults[aria-busy="true"] { position: relative; }
    #ecResults[aria-busy="true"]::after { content: ''; position: absolute; top: 40px; left: 50%;
        width: 30px; height: 30px; margin-left: -15px; border-radius: 50%;
        border: 3px solid rgba(37,99,235,.2); border-top-color: var(--blue);
        animation: ec-spin .6s linear infinite; }
    @keyframes ec-spin { to { transform: rotate(360deg); } }
    /* Freshly swapped cards rise into place, staggered by JS. */
    .ec-card.ec-in { animation: ec-rise .34s cubic-bezier(.22,.8,.3,1) both; }
    @keyframes ec-rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .ec-card.ec-in { animation: none; }
        #ecResults[aria-busy="true"]::after { animation: none; }
    }

    /* The shared paginator is a centred column built for the dark dashboard.
       Here it reads as a toolbar under the grid — count on the left, pages on
       the right — repainted for the light theme. The partial itself is left
       alone; every rule below is scoped to .ec-pag. */
    .ec-pag { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }
    .ec-pag .grpag { margin: 0; flex-direction: row; align-items: center;
        justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .ec-pag .grpag-list { justify-content: flex-end; margin-left: auto; }
    .ec-pag .grpag-info { color: var(--muted); }
    .ec-pag .grpag-info strong { color: var(--ink); }
    .ec-pag .grpag-item > a, .ec-pag .grpag-item > span {
        border-color: var(--line); color: var(--ink-2); background: #fff; }
    .ec-pag .grpag-item > a:hover { background: var(--bg-soft-2, #eef2f8); border-color: var(--blue); color: var(--blue); }
    .ec-pag .grpag-item.active > span { background: linear-gradient(135deg, var(--blue-light, var(--blue)), var(--blue-dark, var(--blue)));
        border-color: transparent; color: #fff; box-shadow: 0 6px 16px -6px rgba(37,99,235,.7); }

    .ec-empty { background: #fff; border: 1px solid var(--line); border-radius: 16px;
        padding: 46px 20px; text-align: center; color: var(--muted); }
    .ec-empty h3 { color: var(--ink); margin-bottom: 6px; }
    .ec-empty a { color: var(--blue); font-weight: 700; text-decoration: none; }

    /* ── TOP SERVICES ──────────────────────────────────── */
    .ec-ts-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .ec-ts { position: relative; border-radius: 16px; overflow: hidden; background: #fff;
        border: 1px solid var(--line); text-decoration: none; display: flex; flex-direction: column;
        box-shadow: 0 10px 24px -20px rgba(15,27,53,.5); transition: transform .15s, box-shadow .15s; }
    .ec-ts:hover { transform: translateY(-3px); box-shadow: 0 20px 38px -22px rgba(15,27,53,.55); }
    /* Every SERVICE asset in the legacy import is a 293x244 promo graphic with the
       service name set across it — there is no photo-only variant. Cropping one
       into a wide strip slices that name in half and reads as a broken image, so
       the box takes the artwork's own 6:5 ratio and shows it whole instead. */
    .ec-ts-img { position: relative; aspect-ratio: 293 / 244; overflow: hidden; }
    .ec-ts-img img { width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform .45s; }
    .ec-ts:hover .ec-ts-img img { transform: scale(1.07); }
    .ec-ts-tag { position: absolute; top: 10px; left: 10px; font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .5px; padding: 4px 9px; border-radius: 6px;
        background: rgba(15,27,53,.7); color: #fff; backdrop-filter: blur(4px); }
    .ec-ts-tag.o { background: var(--orange); }
    .ec-ts-tag.b { background: var(--blue); }
    .ec-ts-tag.h { background: #e11d48; }
    .ec-ts-tag.n { background: #0f9d58; }
    .ec-ts-body { padding: 12px 14px 14px; display: flex; flex-direction: column; flex: 1; }
    .ec-ts-body h3 { font-size: 15px; font-weight: 800; color: var(--ink); margin: 0 0 3px; }
    .ec-ts-sub { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
    .ec-ts-price { margin-top: auto; font-size: 13px; font-weight: 800; color: var(--ink); }
    .ec-ts-price span { color: var(--muted); font-weight: 600; font-size: 11.5px; }
    .ec-ts-price b { color: var(--blue); }
    .ec-ts.ec-hide { display: none; }

    /* ── CTA ───────────────────────────────────────────── */
    .ec-cta { margin: 8px 0 0; border-radius: 20px; padding: 34px; position: relative; overflow: hidden;
        background: linear-gradient(120deg, #eaf1ff, #f4f7ff); border: 1px solid var(--line);
        display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
    .ec-cta h3 { font-size: 24px; font-weight: 800; }
    .ec-cta p { color: var(--text); font-size: 14px; margin: 8px 0 16px; max-width: 480px; }
    .ec-cta-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .ec-btn-blue { border: none; border-radius: 11px; padding: 12px 22px; font-size: 13.5px; font-weight: 800;
        color: #fff; background: linear-gradient(135deg, var(--blue-light, var(--blue)), var(--blue-dark, var(--blue))); text-decoration: none; }
    .ec-btn-ghost { border: 1px solid var(--line); background: #fff; border-radius: 11px; padding: 12px 22px;
        font-size: 13.5px; font-weight: 700; color: var(--ink-2); text-decoration: none; }
    .ec-cta-emoji { font-size: 76px; line-height: 1; }

    @media (max-width: 1080px) {
        .ec-shop { grid-template-columns: 268px 1fr; }
        .ec-grid { grid-template-columns: repeat(2, 1fr); }
        .ec-ts-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        /* Below the two-panel breakpoint the tree stops being a rail: it sits
           above the grid and collapses to its own height so it can't push the
           results off-screen. */
        .ec-shop { grid-template-columns: 1fr; }
        .ec-shop-left { position: static; }
        .ec-tree { max-height: 300px; }
    }
    @media (max-width: 720px) {
        .ec-h1 { font-size: 30px; }
        .ec-grid { grid-template-columns: 1fr; }
        .ec-ts-grid { grid-template-columns: repeat(2, 1fr); }
        .ec-search { border-radius: 18px; }
        /* Too narrow for a toolbar — stack the count over the pages rather
           than pinning them to opposite edges. */
        .ec-pag .grpag { flex-direction: column; align-items: center; gap: 10px; }
        .ec-pag .grpag-list { justify-content: center; margin-left: 0; }
    }
</style>
@endpush

@section('content')
<div class="ec-wrap">

    {{-- ══════════════ HERO ══════════════ --}}
    <section class="ec-hero">
        <div class="lp-container">
            <h1 class="ec-h1">Explore by <span class="b">Category</span> <span class="o">✨</span></h1>
            <p class="ec-hero-sub">Every kind of event, every kind of professional — browse the categories we cover and find the right people for your occasion.</p>

            {{-- Searches the category browser below. It used to post to /browse,
                 which sits behind auth — a guest typing here just landed on the
                 login screen. --}}
            <form action="{{ route('events-categories') }}" method="GET" class="ec-search" data-ec-filter>
                <div class="ec-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search categories or services...">
                </div>
                <button type="submit" class="ec-find">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Search
                </button>
            </form>
        </div>
    </section>

    {{-- ══════════════ EXPLORE BY CATEGORY — FILTER BAR ══════════════ --}}
    <section class="ec-filterbar">
        <div class="lp-container">
            {{-- Event-type dropdown + service-category dropdown (v2). On v1 the
                 second list is still every descendant, tagged with its root. --}}
            <div class="ec-fb-selects">
                <select class="ec-select" id="ecCatSelect" aria-label="{{ $isV2 ? 'Event type' : 'Category' }}">
                    <option value="">{{ $isV2 ? 'All event types' : 'All categories' }}</option>
                    @foreach($cats as $cat)
                        <option value="{{ $cat->slug }}" @selected($branch && $branch->id === $cat->id)>{{ Str::title($cat->name) }}</option>
                    @endforeach
                </select>
                <select class="ec-select" id="ecSubSelect" aria-label="{{ $isV2 ? 'Service category' : 'Subcategory' }}">
                    <option value="">{{ $isV2 ? 'All service categories' : 'All subcategories' }}</option>
                    @if($serviceCats->isNotEmpty())
                        @foreach($serviceCats as $svcCat)
                            <option value="{{ $svcCat->slug }}"
                                    @selected($branch && $branch->id === $svcCat->id)>{{ Str::title($svcCat->name) }}</option>
                        @endforeach
                    @else
                        @foreach($cats as $cat)
                            @php
                                $flatten = function ($node) use (&$flatten) {
                                    return ($node->allChildren ?? collect())
                                        ->flatMap(fn ($k) => collect([$k])->merge($flatten($k)));
                                };
                            @endphp
                            @foreach($flatten($cat) as $desc)
                                <option value="{{ $desc->slug }}" data-root="{{ $cat->slug }}"
                                        @selected($branch && $branch->id === $desc->id)>{{ Str::title($desc->name) }}</option>
                            @endforeach
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </section>

    {{-- ══════════════ BROWSE ALL CATEGORIES — TREE + PAGINATED GRID ══════════════ --}}
    <section class="ec-section" id="ec-browse">
        <div class="lp-container">
            <div class="ec-shead">
                <div>
                    <h2>Browse all <span class="b">Categories</span></h2>
                    <p>Drill into the tree on the left, or search — every card opens that category's professionals.</p>
                </div>
            </div>

            <div class="ec-shop">
                {{-- LEFT: search · full category tree · quick stats --}}
                <aside class="ec-shop-left">
                    <form class="ec-side-search" method="GET" action="{{ route('events-categories') }}#ec-browse" data-ec-filter>
                        {{-- Always present, blank when unscoped: the AJAX pager keeps this in
                             sync with the URL so a search after a drill-in stays in that branch.
                             The submit handler drops empty params. --}}
                        <input type="hidden" name="in" value="{{ $branch?->slug }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" name="q" value="{{ $search }}" placeholder="Type 3+ letters to search…"
                               aria-label="Search categories" autocomplete="off">
                    </form>

                    <div class="ec-side-title">{{ $isV2 ? 'Event Types' : 'Categories' }}</div>
                    @if($cats->isNotEmpty())
                        <div class="ec-tree" id="ecTree">
                            @include('partials._ec-tree-item', ['categories' => $cats, 'depth' => 0, 'branch' => $branch])
                        </div>
                    @else
                        <p class="ec-side-none">No categories yet.</p>
                    @endif

                    @if($serviceCats->isNotEmpty())
                        <div class="ec-side-title ec-side-title-mt">Services</div>
                        <div class="ec-tree" id="ecServiceTree">
                            @include('partials._ec-tree-item', ['categories' => $serviceCats, 'depth' => 0, 'branch' => $branch])
                        </div>
                    @endif

                    <div class="ec-side-title ec-side-title-mt">Quick Stats</div>
                    <div class="ec-stats">
                        <div class="ec-stat"><span>Total Categories</span><b class="v-b">{{ number_format($stats['total']) }}</b></div>
                        <div class="ec-stat"><span>{{ $isV2 ? 'Event Types' : 'Main Categories' }}</span><b class="v-o">{{ number_format($stats['parents']) }}</b></div>
                        <div class="ec-stat"><span>{{ $isV2 ? 'Service Categories' : 'Subcategories' }}</span><b class="v-g">{{ number_format($stats['subcategories']) }}</b></div>
                        <div class="ec-stat"><span>Showing</span><b class="v-n" id="ecShowing">{{ number_format($stats['showing']) }}</b></div>
                    </div>
                </aside>

                {{-- RIGHT: card grid (swapped in place by the AJAX pager) --}}
                <div class="ec-shop-right" id="ecResults" aria-busy="false">
                    @if($branch || $search !== '')
                        <div class="ec-fchips">
                            @if($branch)
                                <span class="ec-fchip">
                                    In <b>{{ Str::title($branch->name) }}</b>
                                    <a href="{{ route('events-categories', array_filter(['q' => $search])) }}#ec-browse" aria-label="Clear category filter">&times;</a>
                                </span>
                            @endif
                            @if($search !== '')
                                <span class="ec-fchip">
                                    Search <b>“{{ $search }}”</b>
                                    <a href="{{ route('events-categories', array_filter(['in' => $branch?->slug])) }}#ec-browse" aria-label="Clear search">&times;</a>
                                </span>
                            @endif
                            <a class="ec-fchip-clear" href="{{ route('events-categories') }}#ec-browse">Clear all</a>
                        </div>
                    @endif

                    @if($categories->count())
                        <div class="ec-grid">
                            @foreach($categories as $cat)
                                @php
                                    // 87 categories carry only the wide 1280x800 promo banner (the
                                    // reclassify pass moved those out of `thumbnail`). Cropping one
                                    // into the 6:5 card slices its lettering, so those are shown
                                    // whole — letterboxed — while square/6:5 art fills the box.
                                    $isBanner = empty($cat->thumbnail) && ! empty($cat->cover_image);
                                    $cardImg  = $isBanner ? asset('storage/' . $cat->cover_image) : $thumbUrl($cat);
                                    $kicker   = $cat->parent?->name
                                        ?? match ($cat->kind) {
                                            \App\Models\Category::SERVICE_CATEGORY => 'Service Category',
                                            \App\Models\Category::EVENT_TYPE       => 'Event Type',
                                            default                                => 'Main Category',
                                        };
                                @endphp
                                <a class="ec-card" href="{{ route('public.category', $cat->slug) }}">
                                    <div class="ec-card-img {{ $isBanner ? 'is-banner' : '' }}"
                                         @if($isBanner) style="--ec-bg: url('{{ $cardImg }}')" @endif>
                                        <img loading="lazy" src="{{ $cardImg }}" alt="{{ $cat->name }}">
                                        @php $kidCount = $descCounts[$cat->id] ?? 0; @endphp
                                        @if($kidCount > 0)
                                            <span class="ec-card-count">{{ $kidCount }} {{ Str::plural('subcategory', $kidCount) }}</span>
                                        @endif
                                    </div>
                                    <div class="ec-card-body">
                                        <span class="ec-card-parent">{{ $kicker }}</span>
                                        <h3>{{ Str::title($cat->name) }}</h3>
                                        @if($cat->short_description)
                                            <p>{{ Str::limit(strip_tags((string) $cat->short_description), 80) }}</p>
                                        @endif
                                        <span class="ec-card-go">
                                            View professionals
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                    @else
                        <div class="ec-empty">
                            <h3>No categories match that</h3>
                            <p>Try a different search, or <a href="{{ route('events-categories') }}#ec-browse">show everything</a>.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pager sits below BOTH columns so it centres on the page, not on
                 the grid — the sidebar is shorter than the results. --}}
            <div class="ec-pag" id="ecPag">{{ $categories->onEachSide(1)->links() }}</div>
        </div>
    </section>

    {{-- ══════════════ TOP SERVICES ══════════════ --}}
    <section class="ec-section">
        <div class="lp-container">
            <div class="ec-shead">
                <div>
                    <h2>Top <span class="o">Services</span></h2>
                    <p>Popular things people book for their events. Starting prices shown are representative.</p>
                </div>
                <div class="ec-tabs" id="ecTsTabs">
                    <button type="button" class="ec-tab active" data-ts="all">All</button>
                    <button type="button" class="ec-tab" data-ts="featured">Featured</button>
                    <button type="button" class="ec-tab" data-ts="new">New</button>
                    <button type="button" class="ec-tab" data-ts="hot">Hot</button>
                </div>
            </div>

            <div class="ec-ts-grid" id="ecTsGrid">
                @foreach($topServices as $svc)
                    <a class="ec-ts" data-group="{{ $svc['group'] }}" href="{{ route('public.category', $svc['slug']) }}">
                        <div class="ec-ts-img">
                            <img loading="lazy" src="{{ $svc['image'] }}" alt="{{ $svc['name'] }}">
                            <span class="ec-ts-tag {{ $svc['badgeClass'] }}">{{ $svc['badge'] }}</span>
                        </div>
                        <div class="ec-ts-body">
                            <h3>{{ $svc['name'] }}</h3>
                            <div class="ec-ts-sub">{{ $svc['sub'] }}</div>
                            <div class="ec-ts-price"><span>from</span> <b>${{ number_format($svc['from']) }}</b></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════ CTA ══════════════ --}}
    <section class="ec-section" style="padding-bottom: 60px;">
        <div class="lp-container">
            <div class="ec-cta">
                <div>
                    <h3>Not sure which category fits?</h3>
                    <p>Post your event and let verified professionals come to you. Describe your needs and receive proposals.</p>
                    <div class="ec-cta-actions">
                        @if(auth()->guest())
                            <a href="{{ route('register', ['role' => 'client']) }}" class="ec-btn-blue">Post an Event</a>
                            <a href="{{ route('register', ['role' => 'professional']) }}" class="ec-btn-ghost">Join as Professional</a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="ec-btn-blue">Go to Dashboard</a>
                            <a href="{{ route('public.browse') }}" class="ec-btn-ghost">Browse Professionals</a>
                        @endif
                    </div>
                </div>
                <div class="ec-cta-emoji">📅</div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    // ── Category tree: disclosure toggles ─────────────────────────────
    // Branches ship collapsed (`hidden` in the markup) so the panel opens at a
    // readable size; clicking the chevron reveals that level only.
    document.querySelectorAll('.ec-tree').forEach(function (tree) {
        tree.addEventListener('click', function (e) {
            var btn = e.target.closest('.ec-tree-toggle');
            if (!btn || btn.classList.contains('ec-tree-leaf')) return;
            e.preventDefault();
            var kids = btn.closest('.ec-tree-node').querySelector(':scope > .ec-tree-kids');
            if (!kids) return;
            var open = kids.hasAttribute('hidden');
            if (open) { kids.removeAttribute('hidden'); } else { kids.setAttribute('hidden', ''); }
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Reveal the drilled-into category by opening every branch above it,
        // then scroll it into view inside the panel.
        var active = tree.querySelector('.ec-tree-row.active');
        if (active) {
            var node = active.closest('.ec-tree-node');
            while (node) {
                var kids = node.parentElement && node.parentElement.closest('.ec-tree-kids');
                if (!kids) break;
                kids.removeAttribute('hidden');
                var owner = kids.parentElement.querySelector(':scope > .ec-tree-row > .ec-tree-toggle');
                if (owner) owner.setAttribute('aria-expanded', 'true');
                node = kids.closest('.ec-tree-node');
            }
            tree.scrollTop = Math.max(0, active.offsetTop - tree.clientHeight / 2);
        }
    });

    // ── AJAX browsing: paging, tree drill-in, search and chips swap the ──
    // results in place instead of reloading the page. Every entry point is a
    // real link or a GET form, so this is pure enhancement — with JS off the
    // same URLs still work as full page loads.
    var results = document.getElementById('ecResults');
    var pager   = document.getElementById('ecPag');
    var shead   = document.getElementById('ec-browse');
    var token   = 0;   // guards against a slow earlier request landing last

    function animateCards() {
        var cards = results.querySelectorAll('.ec-card');
        cards.forEach(function (card, i) {
            card.style.animationDelay = Math.min(i * 28, 280) + 'ms';
            card.classList.add('ec-in');
        });
    }

    // The hero search, the filter-bar selects and the sidebar form all live
    // OUTSIDE the swapped region, so each has to be re-pointed at the new URL
    // by hand — otherwise searching after a drill-in would post a stale (or
    // missing) branch and silently widen the results.
    function syncControls(url) {
        var params = new URL(url, location.origin).searchParams;
        var inSlug = params.get('in') || '';
        var q      = params.get('q') || '';

        document.querySelectorAll('[data-ec-filter]').forEach(function (form) {
            var hidden = form.querySelector('input[name="in"]');
            var box    = form.querySelector('input[name="q"]');
            if (hidden) hidden.value = inSlug;
            // Never rewrite the box the visitor is typing in — live search fires
            // mid-word, and a landing response would otherwise yank the caret
            // back to whatever the request was issued for.
            if (box && box !== document.activeElement) box.value = q;
        });

        // A slug can be a root or any descendant, so try both selects and let
        // the other fall back to "All".
        var catSel = document.getElementById('ecCatSelect');
        var subSel = document.getElementById('ecSubSelect');
        if (catSel && subSel) {
            var asSub  = subSel.querySelector('option[value="' + inSlug + '"]');
            var asRoot = catSel.querySelector('option[value="' + inSlug + '"]');
            catSel.value = asSub ? (asSub.getAttribute('data-root') || '') : (asRoot ? inSlug : '');
            subSel.value = asSub ? inSlug : '';
            narrowSubSelect();
        }
    }

    // "All subcategories" holds every descendant of every root. Show only the
    // ones under the chosen category so the list stays usable.
    function narrowSubSelect() {
        var catSel = document.getElementById('ecCatSelect');
        var subSel = document.getElementById('ecSubSelect');
        if (!catSel || !subSel) return;
        var root = catSel.value;
        subSel.querySelectorAll('option[data-root]').forEach(function (opt) {
            opt.hidden = root !== '' && opt.getAttribute('data-root') !== root;
        });
        // Drop a selection that the new category doesn't contain.
        var current = subSel.selectedOptions[0];
        if (current && current.hidden) subSel.value = '';
    }

    function markActiveTree(url) {
        var trees = document.querySelectorAll('.ec-tree');
        if (!trees.length) return;
        var inSlug = new URL(url, location.origin).searchParams.get('in');
        trees.forEach(function (tree) {
            tree.querySelectorAll('.ec-tree-row.active').forEach(function (r) { r.classList.remove('active'); });
        });
        if (!inSlug) return;
        var link = document.querySelector('.ec-tree .ec-tree-link[href*="in=' + inSlug + '"]');
        if (!link) return;
        var row = link.closest('.ec-tree-row');
        row.classList.add('active');
        // Open every branch above the newly active row.
        var node = row.closest('.ec-tree-node');
        while (node) {
            var kids = node.parentElement && node.parentElement.closest('.ec-tree-kids');
            if (!kids) break;
            kids.removeAttribute('hidden');
            var owner = kids.parentElement.querySelector(':scope > .ec-tree-row > .ec-tree-toggle');
            if (owner) owner.setAttribute('aria-expanded', 'true');
            node = kids.closest('.ec-tree-node');
        }
    }

    /**
     * opts.history — 'push' (default) writes a history entry, 'replace' updates
     *   the current one, false leaves history alone. Live search replaces, so
     *   typing "photography" doesn't bury the previous view under 9 entries.
     * opts.scroll  — false keeps the viewport still; live search must not yank
     *   the page while the visitor is typing in the sidebar.
     */
    function load(url, opts) {
        if (!results || !pager) return;
        opts = opts || {};
        var mode = 'history' in opts ? opts.history : 'push';
        var mine = ++token;
        results.setAttribute('aria-busy', 'true');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error(r.status);
                return r.text();
            })
            .then(function (html) {
                if (mine !== token) return;   // a newer click already won
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var freshResults = doc.getElementById('ecResults');
                var freshPager   = doc.getElementById('ecPag');
                if (!freshResults) throw new Error('unexpected response');

                results.innerHTML = freshResults.innerHTML;
                pager.innerHTML   = freshPager ? freshPager.innerHTML : '';

                var showing = doc.getElementById('ecShowing');
                var target  = document.getElementById('ecShowing');
                if (showing && target) target.textContent = showing.textContent;

                markActiveTree(url);
                syncControls(url);
                animateCards();

                if (mode === 'replace') history.replaceState({ ecUrl: url }, '', url);
                else if (mode) history.pushState({ ecUrl: url }, '', url);

                if (shead && opts.scroll !== false) {
                    var top = shead.getBoundingClientRect().top + window.scrollY - 16;
                    if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
                }
            })
            .catch(function () {
                // Network or server trouble: fall back to a normal navigation
                // rather than leaving the visitor on a dimmed grid.
                window.location.href = url;
            })
            .then(function () {
                if (mine === token) results.setAttribute('aria-busy', 'false');
            });
    }

    // One delegated handler covers the pager, the tree, and the filter chips —
    // they all point at /events-categories with different query strings.
    document.addEventListener('click', function (e) {
        if (!results) return;
        var a = e.target.closest('#ecPag a, .ec-tree .ec-tree-link, #ecResults .ec-fchip a, #ecResults .ec-fchip-clear, #ecResults .ec-empty a');
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') return;
        var href = a.getAttribute('href') || '';
        if (href.indexOf('/events-categories') === -1) return;
        e.preventDefault();
        load(a.href);
    });

    // Both search forms — the hero one and the sidebar one — submit through the
    // same path. They post ?q= (+ ?in= when scoped) to this very page.
    document.querySelectorAll('[data-ec-filter]').forEach(function (form) {
        if (!results) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var params = new URLSearchParams(new FormData(form));
            // Drop empties so a cleared box doesn't leave ?q= behind.
            [...params.keys()].forEach(function (k) { if (!params.get(k)) params.delete(k); });
            var qs = params.toString();
            load(form.action.split('#')[0] + (qs ? '?' + qs : '') + '#ec-browse');
        });
    });

    // ── Sidebar search runs live ──────────────────────────────────────
    // Enter still works; it just isn't required. One or two characters match
    // most of the 360 names, so the query only fires from three — clearing the
    // box counts as a reset and fires immediately.
    var LIVE_MIN = 3;
    var sideForm = document.querySelector('.ec-side-search');
    var sideBox  = sideForm && sideForm.querySelector('input[name="q"]');
    if (sideBox && results) {
        var liveTimer = null;
        // Seed with the URL we rendered from, so the first keystroke on an
        // unfiltered page doesn't fire a request for the state we're already in.
        var lastLive = location.href;

        var runLive = function (term) {
            var params = new URLSearchParams(new FormData(sideForm));
            params.set('q', term);
            [...params.keys()].forEach(function (k) { if (!params.get(k)) params.delete(k); });
            var qs  = params.toString();
            var url = sideForm.action.split('#')[0] + (qs ? '?' + qs : '') + '#ec-browse';
            if (url.split('#')[0] === lastLive.split('#')[0]) return;   // nothing actually changed
            lastLive = url;
            // Replace rather than push: typing "photography" would otherwise
            // bury the previous view under nine history entries. Don't scroll —
            // the visitor is mid-word in the sidebar.
            load(url, { history: 'replace', scroll: false });
        };

        sideBox.addEventListener('input', function () {
            clearTimeout(liveTimer);
            var v = sideBox.value.trim();
            // Under three characters there is no query — show everything rather
            // than leave stale results contradicting what the box says.
            var term = v.length >= LIVE_MIN ? v : '';
            liveTimer = setTimeout(function () { runLive(term); }, 300);
        });

        // Enter submits through the shared handler — drop the pending timer so
        // the same query doesn't run twice.
        sideForm.addEventListener('submit', function () {
            clearTimeout(liveTimer);
            lastLive = '';
        });
    }

    // Filter-bar selects scope the grid. Choosing a category narrows the
    // subcategory list first, then loads; clearing one widens back out.
    var catSel = document.getElementById('ecCatSelect');
    var subSel = document.getElementById('ecSubSelect');
    function scopeTo(slug) {
        var params = new URLSearchParams();
        if (slug) params.set('in', slug);
        var box = document.querySelector('[data-ec-filter] input[name="q"]');
        if (box && box.value) params.set('q', box.value);
        var qs = params.toString();
        load('{{ route('events-categories') }}' + (qs ? '?' + qs : '') + '#ec-browse');
    }
    if (catSel && results) {
        catSel.addEventListener('change', function () {
            narrowSubSelect();
            scopeTo(catSel.value);
        });
    }
    if (subSel && results) {
        subSel.addEventListener('change', function () {
            // Falling back to "All subcategories" returns to the parent category.
            scopeTo(subSel.value || (catSel ? catSel.value : ''));
        });
    }
    narrowSubSelect();

    // Back/forward through the AJAX history.
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.ecUrl) load(e.state.ecUrl, { history: false });
    });

    // Shop-by-category tabs: visual toggle only.
    document.querySelectorAll('.ec-tabs').forEach(function (group) {
        var tabs = group.querySelectorAll('.ec-tab');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                // Top-services grid filtering by data-ts group.
                var filter = tab.getAttribute('data-ts');
                if (filter) { ecFilterServices(filter); }
            });
        });
    });

    function ecFilterServices(filter) {
        document.querySelectorAll('#ecTsGrid .ec-ts').forEach(function (card) {
            var group = card.getAttribute('data-group');
            var show = (filter === 'all') || (group === filter);
            card.classList.toggle('ec-hide', !show);
        });
    }
})();
</script>
@endpush
@endsection
