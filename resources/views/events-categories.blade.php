@extends('layouts.landing')

@php
    use Illuminate\Support\Str;

    $seoTitle       = 'Explore Event Categories | GigResource';
    $seoDescription = 'Explore every event category we cover — weddings, corporate events, birthdays, festivals, conferences and more. Browse real categories and find the right professionals for your occasion.';

    // Real, active parent categories passed from the route (with full subtree).
    $cats = $allCategories ?? collect();

    // Total descendant count across ALL sub-levels (the event roots are thin
    // chains: root → group → sub-group → services), so direct-child count is
    // always 1 — we count every descendant to show a meaningful number.
    $descCount = function ($cat) use (&$descCount) {
        $kids = $cat->allChildren ?? collect();
        return $kids->reduce(fn ($carry, $k) => $carry + 1 + $descCount($k), 0);
    };

    // Real category image (cover/thumbnail) → full URL, or a neutral fallback.
    $fallbackImg = 'https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80&auto=format&fit=crop';
    $imgUrl = function ($c) use ($fallbackImg) {
        $f = ($c->cover_image ?? null) ?: ($c->thumbnail ?? null);
        return $f ? asset('storage/' . $f) : $fallbackImg;
    };

    // "Top Services" = real sub-categories that have imagery. Prices are
    // REPRESENTATIVE starting points ("from $X"), not guarantees.
    $svcBadges = [['POPULAR', 'o', 'featured'], ['FEATURED', 'b', 'featured'], ['HOT', 'h', 'hot'], ['NEW', 'n', 'new']];
    $svcPrices = [450, 180, 600, 800, 350, 120, 140, 200];
    $topServices = $cats->flatMap(fn ($c) => $c->allChildren ?? collect())
        ->filter(fn ($c) => ($c->thumbnail || $c->cover_image))
        ->unique('name')
        ->take(8)->values()
        ->map(function ($c, $i) use ($imgUrl, $svcBadges, $svcPrices) {
            [$badge, $badgeClass, $group] = $svcBadges[$i % count($svcBadges)];
            return [
                'name'  => $c->name,
                'image' => $imgUrl($c),
                'badge' => $badge, 'badgeClass' => $badgeClass, 'group' => $group,
                'sub'   => Str::limit(strip_tags((string) $c->short_description), 42) ?: 'Browse specialists',
                'from'  => $svcPrices[$i % count($svcPrices)],
                'slug'  => $c->slug,
            ];
        });

    // "Popular Event Types" = real top-level categories with imagery.
    $eventTypes = $cats->filter(fn ($c) => ($c->thumbnail || $c->cover_image))
        ->take(6)->values()
        ->map(fn ($c) => ['name' => $c->name, 'image' => $imgUrl($c), 'slug' => $c->slug]);
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
    .ec-fb-chips { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
    .ec-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line);
        background: #fff; border-radius: 999px; padding: 8px 14px; font-size: 12.5px; font-weight: 700;
        color: var(--ink-2); cursor: pointer; transition: all .15s; }
    .ec-chip svg { width: 14px; height: 14px; color: var(--blue); }
    .ec-chip:hover { border-color: var(--blue); color: var(--blue); }
    .ec-chip.active { background: linear-gradient(135deg, var(--blue-light, var(--blue)), var(--blue-dark, var(--blue)));
        border-color: transparent; color: #fff; box-shadow: 0 10px 22px -14px rgba(37,99,235,.8); }
    .ec-chip.active svg { color: #fff; }

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

    /* ── SHOP BY CATEGORY: two-panel ───────────────────── */
    .ec-shop { display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start; }
    .ec-shop-left { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 10px;
        box-shadow: 0 12px 30px -24px rgba(15,27,53,.5); }
    .ec-catlist { display: flex; flex-direction: column; gap: 2px; max-height: 560px; overflow-y: auto; }
    .ec-catrow { display: flex; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 12px;
        text-decoration: none; transition: background .15s; }
    .ec-catrow:hover { background: var(--bg-soft-2, #eef2f8); }
    .ec-catrow.active { background: linear-gradient(135deg, rgba(37,99,235,.10), rgba(37,99,235,.04));
        box-shadow: inset 0 0 0 1px rgba(37,99,235,.25); }
    .ec-catrow-ic { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, rgba(37,99,235,.10), rgba(249,115,22,.10));
        display: flex; align-items: center; justify-content: center; }
    .ec-catrow-ic svg { width: 19px; height: 19px; color: var(--blue); }
    .ec-catrow.active .ec-catrow-ic { background: linear-gradient(135deg, var(--blue), var(--blue-dark, var(--blue))); }
    .ec-catrow.active .ec-catrow-ic svg { color: #fff; }
    .ec-catrow-name { font-size: 14px; font-weight: 700; color: var(--ink); flex: 1; line-height: 1.2; }
    .ec-catrow.active .ec-catrow-name { color: var(--blue); }
    .ec-catrow-badge { font-size: 11.5px; font-weight: 800; color: var(--ink-2);
        background: var(--bg-soft-2, #eef2f8); border-radius: 999px; padding: 3px 9px; min-width: 26px; text-align: center; }
    .ec-catrow.active .ec-catrow-badge { background: var(--blue); color: #fff; }

    .ec-shop-right { display: flex; flex-direction: column; gap: 16px; }
    .ec-shop-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .ec-fcard { grid-column: span 2; grid-row: span 2; position: relative; min-height: 300px;
        border-radius: 18px; overflow: hidden; text-decoration: none; display: flex; flex-direction: column;
        justify-content: flex-end; padding: 24px; color: #fff; box-shadow: 0 18px 40px -24px rgba(15,27,53,.6); }
    .ec-fcard::before { content: ''; position: absolute; inset: 0; z-index: 0;
        background-size: cover; background-position: center; transition: transform .5s; }
    .ec-fcard:hover::before { transform: scale(1.06); }
    .ec-fcard::after { content: ''; position: absolute; inset: 0; z-index: 1;
        background: linear-gradient(180deg, rgba(15,27,53,.05) 20%, rgba(15,27,53,.86) 100%); }
    .ec-fcard > * { position: relative; z-index: 2; }
    .ec-fcard-badge { align-self: flex-start; font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .5px; padding: 5px 11px; border-radius: 999px; background: var(--orange); margin-bottom: 10px; }
    .ec-fcard h3 { font-size: 24px; font-weight: 800; margin: 0 0 6px; letter-spacing: -.4px;
        color: #fff !important; text-shadow: 0 1px 12px rgba(15,27,53,.55); }
    .ec-fcard p { font-size: 13.5px; color: rgba(255,255,255,.92); margin: 0 0 16px; max-width: 360px;
        text-shadow: 0 1px 10px rgba(15,27,53,.5); }
    .ec-fcard-btn { align-self: flex-start; display: inline-flex; align-items: center; gap: 8px;
        background: #fff; color: var(--blue-dark, var(--blue)); border-radius: 999px; padding: 10px 18px;
        font-size: 13px; font-weight: 800; }
    .ec-fcard-btn svg { width: 15px; height: 15px; transition: transform .15s; }
    .ec-fcard:hover .ec-fcard-btn svg { transform: translateX(3px); }

    .ec-scard { position: relative; border-radius: 16px; overflow: hidden; text-decoration: none;
        background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column;
        min-height: 143px; box-shadow: 0 10px 22px -20px rgba(15,27,53,.5); transition: transform .15s, box-shadow .15s; }
    .ec-scard:hover { transform: translateY(-3px); box-shadow: 0 18px 34px -22px rgba(15,27,53,.55); }
    .ec-scard-img { height: 80px; background-size: cover; background-position: center; }
    .ec-scard-body { padding: 10px 12px 12px; }
    .ec-scard-name { font-size: 13.5px; font-weight: 800; color: var(--ink); line-height: 1.2; }
    .ec-scard-meta { font-size: 11.5px; font-weight: 600; color: var(--muted); margin-top: 3px; }
    .ec-scard-meta .b { color: var(--blue); }

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
    .ec-ts-img { position: relative; height: 150px; overflow: hidden; }
    .ec-ts-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
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

    /* ── POPULAR EVENT TYPES ───────────────────────────── */
    .ec-et-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .ec-et { position: relative; height: 200px; border-radius: 18px; overflow: hidden;
        text-decoration: none; display: block; box-shadow: 0 12px 28px -20px rgba(15,27,53,.5); }
    .ec-et img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
    .ec-et:hover img { transform: scale(1.06); }
    .ec-et-ov { position: absolute; inset: 0; padding: 22px; display: flex; align-items: flex-end;
        justify-content: space-between; gap: 12px;
        background: linear-gradient(180deg, rgba(15,27,53,0) 32%, rgba(15,27,53,.85) 100%); }
    .ec-et-ov h3 { color: #fff; font-size: 18px; font-weight: 800; }
    .ec-et-ov span { color: rgba(255,255,255,.85); font-size: 12.5px; }
    .ec-et-arrow { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--blue-light, var(--blue)), var(--blue-dark, var(--blue)));
        display: flex; align-items: center; justify-content: center; }
    .ec-et-arrow svg { width: 16px; height: 16px; color: #fff; }

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
        .ec-shop { grid-template-columns: 260px 1fr; }
        .ec-ts-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .ec-shop { grid-template-columns: 1fr; }
        .ec-catlist { flex-direction: row; overflow-x: auto; max-height: none; }
        .ec-catrow { flex: 0 0 auto; }
        .ec-shop-cards { grid-template-columns: repeat(2, 1fr); }
        .ec-fcard { grid-column: span 2; grid-row: auto; min-height: 220px; }
        .ec-et-grid { grid-template-columns: repeat(2, 1fr); }
        .ec-fb-chips { margin-left: 0; }
    }
    @media (max-width: 720px) {
        .ec-h1 { font-size: 30px; }
        .ec-ts-grid { grid-template-columns: repeat(2, 1fr); }
        .ec-et-grid { grid-template-columns: 1fr; }
        .ec-search { border-radius: 18px; }
        .ec-shop-cards { grid-template-columns: 1fr; }
        .ec-fcard { grid-column: auto; }
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

            <form action="{{ route('public.browse') }}" method="GET" class="ec-search">
                <div class="ec-sfield">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" placeholder="Search categories or services...">
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
            <div class="ec-fb-selects">
                <select class="ec-select" id="ecCatSelect" aria-label="Category" onchange="ecCategoryJump(this)">
                    <option value="">All categories</option>
                    @foreach($cats as $cat)
                        <option value="{{ $cat->name }}">{{ Str::title($cat->name) }}</option>
                    @endforeach
                </select>
                <select class="ec-select" id="ecSubSelect" aria-label="Subcategory" onchange="ecCategoryJump(this)">
                    <option value="">All subcategories</option>
                    @foreach($cats as $cat)
                        @foreach(($cat->allChildren ?? collect()) as $child)
                            <option value="{{ $child->name }}">{{ Str::title($child->name) }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="ec-fb-chips" id="ecChips">
                <button type="button" class="ec-chip active" data-sort="top">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 8.5 22 9.3 17 14 18.3 21 12 17.5 5.7 21 7 14 2 9.3 9 8.5 12 2"/></svg>
                    Popular
                </button>
                <button type="button" class="ec-chip" data-sort="rating">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.3L22 9.2l-5 5 1.2 7L12 17.8 5.8 21.2 7 14.2l-5-5 7.1-.9L12 2z"/></svg>
                    Top Rated
                </button>
                <button type="button" class="ec-chip" data-sort="newest">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Arrivals
                </button>
            </div>
        </div>
    </section>

    {{-- ══════════════ SHOP BY CATEGORY — TWO PANEL ══════════════ --}}
    <section class="ec-section">
        <div class="lp-container">
            <div class="ec-shead">
                <div>
                    <h2>Shop by <span class="b">Category</span></h2>
                    <p>Pick a category on the left, then jump straight into matching professionals.</p>
                </div>
            </div>

            @if($cats->isNotEmpty())
                <div class="ec-shop">
                    {{-- LEFT: real category list with subcategory-count badges --}}
                    <aside class="ec-shop-left">
                        <div class="ec-catlist">
                            @foreach($cats as $i => $cat)
                                @php $count = $descCount($cat); @endphp
                                <a class="ec-catrow {{ $i === 0 ? 'active' : '' }}" href="{{ route('public.category', $cat->slug) }}">
                                    <span class="ec-catrow-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                    </span>
                                    <span class="ec-catrow-name">{{ Str::title($cat->name) }}</span>
                                    @if($count > 0)
                                        <span class="ec-catrow-badge">{{ $count }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </aside>

                    {{-- RIGHT: featured card + smaller category cards --}}
                    <div class="ec-shop-right">

                        <div class="ec-shop-cards">
                            @php
                                $first = $cats->first();
                                $firstChildren = $first->children ?? collect();
                                $rest = $cats->slice(1)->take(5);
                            @endphp

                            {{-- BIG feature card = first real category --}}
                            <a class="ec-fcard" href="{{ route('public.category', $first->slug) }}"
                               style="--x:0" data-bg="{{ $imgUrl($first) }}">
                                <span class="ec-fcard-badge">Featured</span>
                                <h3>{{ Str::title($first->name) }}</h3>
                                @php $firstCount = $descCount($first); @endphp
                                <p>Explore vetted professionals for {{ Str::lower($first->name) }}{{ $firstCount > 0 ? ' — ' . $firstCount . ' ' . Str::plural('subcategory', $firstCount) . ' to browse' : '' }}.</p>
                                <span class="ec-fcard-btn">
                                    Explore top professionals
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </span>
                            </a>

                            {{-- smaller cards = next real categories --}}
                            @foreach($rest as $j => $cat)
                                @php $scount = $descCount($cat); @endphp
                                <a class="ec-scard" href="{{ route('public.category', $cat->slug) }}">
                                    <div class="ec-scard-img" style="background-image:url('{{ $imgUrl($cat) }}')"></div>
                                    <div class="ec-scard-body">
                                        <div class="ec-scard-name">{{ Str::title($cat->name) }}</div>
                                        <div class="ec-scard-meta">
                                            @if($scount > 0)
                                                <span class="b">{{ $scount }}</span> {{ Str::plural('subcategory', $scount) }}
                                            @else
                                                Browse professionals
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="ec-empty">
                    <h3>Categories are on their way</h3>
                    <p>Browse all professionals in the meantime — <a href="{{ route('public.browse') }}">open Browse</a>.</p>
                </div>
            @endif
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

    {{-- ══════════════ POPULAR EVENT TYPES ══════════════ --}}
    <section class="ec-section">
        <div class="lp-container">
            <div class="ec-shead">
                <div>
                    <h2>Popular <span class="b">Event Types</span></h2>
                    <p>Find professionals for every kind of occasion.</p>
                </div>
            </div>

            <div class="ec-et-grid">
                @foreach($eventTypes as $et)
                    <a class="ec-et" href="{{ route('public.category', $et['slug']) }}">
                        <img loading="lazy" src="{{ $et['image'] }}" alt="{{ $et['name'] }}">
                        <div class="ec-et-ov">
                            <div>
                                <h3>{{ $et['name'] }}</h3>
                                <span>Browse specialists</span>
                            </div>
                            <span class="ec-et-arrow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </span>
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
                            <a href="{{ route('register', ['role' => 'supplier']) }}" class="ec-btn-ghost">Join as Professional</a>
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
    var browseBase = @json(route('public.browse'));

    // Paint featured-card backgrounds from data-bg (::before needs a stylesheet rule,
    // and this avoids inline url() quoting issues).
    var styleEl = document.createElement('style');
    var css = '';
    document.querySelectorAll('.ec-fcard[data-bg]').forEach(function (el, idx) {
        el.classList.add('ec-fcard-' + idx);
        el.style.backgroundColor = '#1b2a4a';
        css += '.ec-fcard-' + idx + '::before{background-image:url(' + el.getAttribute('data-bg') + ');}';
    });
    styleEl.textContent = css;
    document.head.appendChild(styleEl);

    // Filter chips → jump to Browse Professionals sorted accordingly.
    var chips = document.querySelectorAll('#ecChips .ec-chip');
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            var sort = chip.getAttribute('data-sort') || 'top';
            var q = document.getElementById('ecCatSelect');
            var qv = q && q.value ? '&q=' + encodeURIComponent(q.value) : '';
            window.location.href = browseBase + '?sort=' + encodeURIComponent(sort) + qv;
        });
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

    // Expose category-jump used by the filter-bar selects.
    window.ecCategoryJump = function (sel) {
        var val = sel.value;
        if (!val) { return; }
        var url = browseBase + '?q=' + encodeURIComponent(val);
        window.location.href = url;
    };
})();
</script>
@endpush
@endsection
