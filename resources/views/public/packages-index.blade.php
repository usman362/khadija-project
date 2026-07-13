@extends('layouts.landing')

@section('title', 'Find the Perfect Package — GigResource')
@section('meta_description', 'Search ready-made service bundles from event professionals who handle multiple parts of your event — one contract, one payment, better value.')

@php
    $f = $filters;
    // Build a query array with one service removed (for the removable top chips).
    $withoutService = function ($svc) use ($f) {
        $rest = array_values(array_diff($f['selected'], [$svc]));
        return array_filter(['services' => $rest ?: null, 'provider' => $f['provider'] !== 'all' ? $f['provider'] : null, 'q' => $f['q'] ?: null, 'sort' => $f['sort'] !== 'relevant' ? $f['sort'] : null, 'view' => $f['view'] !== 'list' ? $f['view'] : null]);
    };
    $baseQuery = array_filter(['services' => $f['selected'] ?: null, 'provider' => $f['provider'] !== 'all' ? $f['provider'] : null, 'q' => $f['q'] ?: null]);
    $stock = ['photo-1519741497674-611481863552','photo-1511795409834-ef04bbd61622','photo-1530103862676-de8c9debad1d','photo-1492684223066-81342ee5ff30','photo-1464366400600-7168b8af9bc3','photo-1519225421980-715cb0215aed'];
@endphp

@push('styles')
<style>
    .pk { --pk: var(--orange, #f97316); --pk-dark: #ea580c; --pk-soft: #fff4ec; }
    .pk-wrap { background: var(--bg-soft); }
    .pk-shell { max-width: 1360px; margin: 0 auto; padding: 20px 22px 60px; }

    /* Hero */
    .pk-hero { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 22px 0 20px; }
    .pk-hero h1 { font-size: clamp(1.7rem, 3.4vw, 2.5rem); font-weight: 900; color: var(--ink); letter-spacing: -.02em; margin: 0 0 6px; }
    .pk-hero h1 span { color: var(--pk); }
    .pk-hero p { color: var(--muted); font-size: 15px; max-width: 420px; margin: 0; }
    .pk-props { display: grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap: 12px; }
    .pk-prop { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
    .pk-prop svg { width: 22px; height: 22px; color: var(--pk); flex-shrink: 0; }
    .pk-prop b { display: block; font-size: 13px; font-weight: 800; color: var(--ink); line-height: 1.2; }
    .pk-prop span { font-size: 11.5px; color: var(--muted); }

    /* Toolbar */
    .pk-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 12px 16px; margin-bottom: 18px; }
    .pk-sel-lbl { font-size: 13px; font-weight: 800; color: var(--ink); display: inline-flex; align-items: center; gap: 6px; }
    .pk-sel-lbl .i { width: 16px; height: 16px; border-radius: 50%; background: var(--line); color: var(--muted); font-size: 11px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
    .pk-chip { display: inline-flex; align-items: center; gap: 7px; background: var(--pk-soft); color: var(--pk-dark); border: 1px solid #fed7aa; border-radius: 999px; padding: 6px 12px; font-size: 12.5px; font-weight: 700; text-decoration: none; }
    .pk-chip a { color: var(--pk-dark); text-decoration: none; font-weight: 800; line-height: 1; }
    .pk-addsvc { display: inline-flex; align-items: center; gap: 6px; border: 1px dashed var(--line); background: #fff; border-radius: 999px; padding: 6px 13px; font-size: 12.5px; font-weight: 700; color: var(--ink-2); cursor: pointer; }
    .pk-count { font-size: 13.5px; font-weight: 700; color: var(--ink); }
    .pk-count b { color: var(--pk); }
    .pk-tools-right { margin-left: auto; display: inline-flex; align-items: center; gap: 14px; }
    .pk-sortsel { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 700; color: var(--muted); }
    .pk-sortsel select { border: 1px solid var(--line); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--ink); background: #fff; font-family: inherit; cursor: pointer; }
    .pk-viewtog { display: inline-flex; gap: 4px; font-size: 12.5px; font-weight: 700; color: var(--muted); align-items: center; }
    .pk-viewtog a { display: inline-flex; align-items: center; gap: 5px; padding: 6px 9px; border-radius: 8px; text-decoration: none; color: var(--muted); border: 1px solid transparent; }
    .pk-viewtog a.on { color: var(--pk); border-color: var(--line); background: var(--pk-soft); }

    /* Layout */
    .pk-grid { display: grid; grid-template-columns: 250px minmax(0,1fr) 288px; gap: 20px; align-items: start; }

    /* Left rail */
    .pk-rail { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px; }
    .pk-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .pk-rail-head h3 { font-size: 15px; font-weight: 800; color: var(--ink); margin: 0; }
    .pk-clear { font-size: 12px; font-weight: 700; color: var(--pk); text-decoration: none; }
    .pk-rail-sec { font-size: 13px; font-weight: 800; color: var(--ink); margin: 16px 0 4px; }
    .pk-rail-hint { font-size: 11.5px; color: var(--muted); margin: 0 0 10px; }
    .pk-svcsearch { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 8px 11px; font-size: 12.5px; font-family: inherit; margin-bottom: 10px; }
    .pk-check { display: flex; align-items: center; gap: 9px; padding: 6px 0; font-size: 13px; color: var(--ink-2); cursor: pointer; }
    .pk-check input { width: 15px; height: 15px; accent-color: var(--pk); }
    .pk-check .cnt { margin-left: auto; font-size: 11px; color: var(--muted); font-weight: 700; }
    .pk-check.hidden { display: none; }
    .pk-showmore { font-size: 12.5px; font-weight: 700; color: var(--pk); background: none; border: none; cursor: pointer; padding: 6px 0; }
    .pk-radio { display: flex; align-items: flex-start; gap: 9px; padding: 7px 0; font-size: 13px; color: var(--ink-2); cursor: pointer; }
    .pk-radio input { margin-top: 2px; accent-color: var(--pk); }
    .pk-radio b { display: block; font-weight: 700; color: var(--ink); }
    .pk-radio span { font-size: 11.5px; color: var(--muted); }
    .pk-apply { width: 100%; margin-top: 16px; border: none; background: var(--pk); color: #fff; border-radius: 11px; padding: 11px; font-size: 14px; font-weight: 800; cursor: pointer; }
    .pk-apply:hover { background: var(--pk-dark); }
    .pk-divider { height: 1px; background: var(--line); margin: 16px 0; }

    /* Cards (list) */
    .pk-cards { display: flex; flex-direction: column; gap: 16px; }
    .pk-card { display: grid; grid-template-columns: 240px minmax(0,1fr) 190px; background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
    .pk-media { position: relative; min-height: 210px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .pk-media img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .pk-typebadge { position: absolute; top: 10px; left: 10px; font-size: 10.5px; font-weight: 800; letter-spacing: .3px; color: #fff; padding: 5px 11px; border-radius: 7px; }
    .pk-typebadge.solo { background: var(--pk); } .pk-typebadge.coop { background: #7c3aed; }
    .pk-photos { position: absolute; bottom: 10px; left: 10px; font-size: 11px; font-weight: 700; color: #fff; background: rgba(0,0,0,.55); padding: 4px 9px; border-radius: 7px; display: inline-flex; align-items: center; gap: 5px; }
    .pk-heart { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,.92); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .pk-heart svg { width: 17px; height: 17px; color: #64748b; }
    .pk-main { padding: 16px 18px; min-width: 0; }
    .pk-title { font-size: 17px; font-weight: 800; color: var(--ink); margin: 0 0 5px; }
    .pk-pro { font-size: 13px; color: var(--ink-2); font-weight: 600; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pk-verif { color: var(--pk); }
    .pk-x { color: var(--muted); }
    .pk-msp { display: inline-block; font-size: 10px; font-weight: 800; letter-spacing: .3px; color: var(--pk-dark); background: var(--pk-soft); border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 6px; margin: 8px 0 0; }
    .pk-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0; }
    .pk-tag { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: var(--ink-2); background: var(--bg-soft); border: 1px solid var(--line); border-radius: 7px; padding: 3px 9px; }
    .pk-desc { font-size: 13px; color: var(--muted); line-height: 1.5; margin: 0 0 12px; }
    .pk-facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: var(--pk-soft); border-radius: 10px; padding: 11px 13px; margin-bottom: 12px; }
    .pk-fact span { display: block; font-size: 10px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; color: var(--pk-dark); margin-bottom: 3px; }
    .pk-fact b { font-size: 12px; font-weight: 700; color: var(--ink); display: block; line-height: 1.4; }
    .pk-fact .row { font-size: 12px; color: var(--ink-2); line-height: 1.5; }
    .pk-cardfoot { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; font-size: 12px; color: var(--muted); font-weight: 600; }
    .pk-cardfoot .star { color: #f59e0b; font-weight: 800; }
    .pk-pricebox { border-left: 1px solid var(--line); padding: 18px 16px; display: flex; flex-direction: column; align-items: center; text-align: center; justify-content: center; }
    .pk-pricebox .lbl { font-size: 11px; color: var(--muted); font-weight: 700; }
    .pk-pricebox .amt { font-size: 24px; font-weight: 900; color: var(--ink); line-height: 1.1; margin: 2px 0; }
    .pk-pricebox .tp { font-size: 11px; color: var(--muted); margin-bottom: 12px; }
    .pk-btn { display: block; width: 100%; text-align: center; border-radius: 10px; padding: 10px; font-size: 13.5px; font-weight: 800; text-decoration: none; cursor: pointer; }
    .pk-btn-primary { background: var(--pk); color: #fff; border: none; }
    .pk-btn-primary:hover { background: var(--pk-dark); }
    .pk-btn-ghost { background: #fff; color: var(--pk); border: 1px solid var(--pk); margin-top: 8px; }
    .pk-save { font-size: 11.5px; font-weight: 700; color: #16a34a; margin-top: 12px; line-height: 1.4; }

    /* Grid view */
    .pk-cards.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 18px; }
    .pk-cards.grid .pk-card { grid-template-columns: 1fr; }
    .pk-cards.grid .pk-media { min-height: 168px; }
    .pk-cards.grid .pk-pricebox { border-left: none; border-top: 1px solid var(--line); }
    .pk-cards.grid .pk-facts { grid-template-columns: 1fr; }

    /* Right rail */
    .pk-side { display: flex; flex-direction: column; gap: 18px; position: sticky; top: 84px; }
    .pk-scard { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 16px; }
    .pk-scard h4 { font-size: 14px; font-weight: 800; color: var(--ink); margin: 0 0 12px; display: flex; align-items: center; justify-content: space-between; }
    .pk-map { position: relative; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg,#dbeafe,#eff6ff); height: 150px; margin-bottom: 12px; }
    .pk-map svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .pk-avail { display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; color: var(--ink-2); padding: 4px 0; }
    .pk-avail b { color: var(--ink); }
    .pk-legend { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; font-size: 11.5px; color: var(--muted); }
    .pk-legend span::before { content: "●"; margin-right: 6px; }
    .pk-legend .solo::before { color: var(--pk); } .pk-legend .coop::before { color: #7c3aed; }
    .pk-why { display: flex; gap: 10px; padding: 9px 0; }
    .pk-why svg { width: 18px; height: 18px; color: var(--pk); flex-shrink: 0; margin-top: 1px; }
    .pk-why b { display: block; font-size: 12.5px; font-weight: 800; color: var(--ink); }
    .pk-why span { font-size: 11.5px; color: var(--muted); }
    .pk-howbtn { display: block; text-align: center; margin-top: 8px; border: 1px solid var(--pk); color: var(--pk); border-radius: 10px; padding: 9px; font-size: 12.5px; font-weight: 800; text-decoration: none; }
    .pk-recent { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .pk-recent a { text-decoration: none; }
    .pk-recent img, .pk-recent .ph { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 9px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); display: block; }
    .pk-recent .cap { font-size: 10px; color: var(--ink-2); font-weight: 600; margin-top: 4px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pk-viewall { display: block; text-align: center; font-size: 12px; font-weight: 800; color: var(--pk); margin-top: 12px; text-decoration: none; }

    /* Empty */
    .pk-empty { background: #fff; border: 1px dashed var(--line); border-radius: 18px; padding: 54px 20px; text-align: center; }
    .pk-empty h3 { font-size: 18px; font-weight: 800; color: var(--ink); margin: 10px 0 6px; }
    .pk-empty p { color: var(--muted); margin: 0 0 16px; }
    .pk-empty a { display: inline-flex; background: var(--pk); color: #fff; border-radius: 11px; padding: 11px 22px; font-weight: 800; text-decoration: none; }

    @media (max-width: 1140px) { .pk-grid { grid-template-columns: 230px minmax(0,1fr); } .pk-side { display: none; } }
    @media (max-width: 820px) {
        .pk-grid { grid-template-columns: 1fr; }
        .pk-rail { position: static; }
        .pk-card { grid-template-columns: 1fr; }
        .pk-media { min-height: 180px; }
        .pk-pricebox { border-left: none; border-top: 1px solid var(--line); }
        .pk-props { grid-template-columns: repeat(2,1fr); }
    }
</style>
@endpush

@section('content')
<div class="pk pk-wrap">
    <div class="pk-shell">
        {{-- Hero + value props --}}
        <div class="pk-hero">
            <div>
                <h1>Find the Perfect <span>Package</span></h1>
                <p>Search ready-made service bundles from professionals who can handle multiple parts of your event.</p>
            </div>
            <div class="pk-props">
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8V5a2 2 0 0 1 2-2h1M12 8V5a2 2 0 0 0-2-2H9"/><line x1="12" y1="8" x2="12" y2="21"/></svg><div><b>One Contract</b><span>One Payment</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9"/></svg><div><b>Professionally</b><span>Coordinated</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div><b>Better Value</b><span>Bundle Pricing</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg><div><b>Customizable</b><span>to Your Needs</span></div></div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="pk-toolbar">
            <span class="pk-sel-lbl">Selected Services (AND Match) <span class="i">i</span></span>
            @forelse($f['selected'] as $svc)
                <span class="pk-chip">{{ $svc }} <a href="{{ route('public.packages', $withoutService($svc)) }}" title="Remove">✕</a></span>
            @empty
                <span style="font-size:12.5px;color:var(--muted);">None — pick services in the matcher →</span>
            @endforelse
            <button type="button" class="pk-addsvc" onclick="document.getElementById('pkSvcSearch')?.focus()">+ Add Another Service</button>

            <div class="pk-tools-right">
                <span class="pk-count">Showing <b>{{ $total }}</b> Package{{ $total === 1 ? '' : 's' }}</span>
                <form class="pk-sortsel" method="GET" action="{{ route('public.packages') }}">
                    @foreach($f['selected'] as $s)<input type="hidden" name="services[]" value="{{ $s }}">@endforeach
                    @if($f['provider'] !== 'all')<input type="hidden" name="provider" value="{{ $f['provider'] }}">@endif
                    @if($f['q'])<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                    @if($f['view'] !== 'list')<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
                    <label for="pk-sort">Sort by:</label>
                    <select id="pk-sort" name="sort" onchange="this.form.submit()">
                        <option value="relevant" @selected($f['sort']==='relevant')>Most Relevant</option>
                        <option value="savings" @selected($f['sort']==='savings')>Best Savings</option>
                        <option value="price_low" @selected($f['sort']==='price_low')>Price: Low to High</option>
                        <option value="price_high" @selected($f['sort']==='price_high')>Price: High to Low</option>
                        <option value="newest" @selected($f['sort']==='newest')>Newest</option>
                    </select>
                </form>
                <span class="pk-viewtog">View:
                    <a href="{{ route('public.packages', array_merge($baseQuery, ['sort' => $f['sort'], 'view' => 'grid'])) }}" class="{{ $f['view']==='grid' ? 'on' : '' }}">▦ Grid</a>
                    <a href="{{ route('public.packages', array_merge($baseQuery, ['sort' => $f['sort'], 'view' => 'list'])) }}" class="{{ $f['view']==='list' ? 'on' : '' }}">☰ List</a>
                </span>
            </div>
        </div>

        <div class="pk-grid">
            {{-- Left rail --}}
            <form class="pk-rail" method="GET" action="{{ route('public.packages') }}">
                @if($f['q'])<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                @if($f['sort'] !== 'relevant')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif
                @if($f['view'] !== 'list')<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
                <div class="pk-rail-head">
                    <h3>Refine Package Search</h3>
                    <a class="pk-clear" href="{{ route('public.packages') }}">Clear All</a>
                </div>

                <div class="pk-rail-sec">1. Service Mix Matcher</div>
                <p class="pk-rail-hint">Select all services included in the package</p>
                <input type="text" id="pkSvcSearch" class="pk-svcsearch" placeholder="Search services…" oninput="pkFilterSvc(this.value)">
                <div id="pkSvcList">
                    @foreach($services as $i => $svc)
                        <label class="pk-check {{ $i >= 6 && ! in_array($svc, $f['selected']) ? 'more hidden' : '' }}" data-svc="{{ Str::lower($svc) }}">
                            <input type="checkbox" name="services[]" value="{{ $svc }}" @checked(in_array($svc, $f['selected']))>
                            {{ $svc }}
                            <span class="cnt">{{ $serviceCounts[$svc] ?? 0 }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="button" class="pk-showmore" id="pkShowMore" onclick="pkToggleMore()">Show More ▾</button>

                <div class="pk-divider"></div>

                <div class="pk-rail-sec">2. Provider Type</div>
                <label class="pk-radio"><input type="radio" name="provider" value="all" @checked($f['provider']==='all')><span><b>All Packages ({{ $providerCounts['all'] }})</b></span></label>
                <label class="pk-radio"><input type="radio" name="provider" value="solo" @checked($f['provider']==='solo')><span><b>Solo Multi-Pros Only ({{ $providerCounts['solo'] }})</b><span>One professional does multiple services</span></span></label>
                <label class="pk-radio"><input type="radio" name="provider" value="coop" @checked($f['provider']==='coop')><span><b>Co-Op Partnerships Only ({{ $providerCounts['coop'] }})</b><span>Two or more professionals collaborate</span></span></label>

                <button type="submit" class="pk-apply">Apply Filters</button>
            </form>

            {{-- Center: cards --}}
            <div>
                @if($packages->count())
                    <div class="pk-cards {{ $f['view']==='grid' ? 'grid' : '' }}">
                        @foreach($packages as $pkg)
                            @php
                                $pro = $pkg->user;
                                $hero = $pkg->heroUrls(1)[0] ?? 'https://images.unsplash.com/' . $stock[$pkg->id % count($stock)] . '?w=520&q=70&auto=format&fit=crop';
                                $rating = $pro?->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
                                $svcTags = $pkg->services ?: ($pkg->category ? [$pkg->category->name] : []);
                                $isCoop = $pkg->type === 'co-op';
                                $total_ = '$' . number_format($pkg->price);
                            @endphp
                            <article class="pk-card">
                                <div class="pk-media">
                                    <img src="{{ $hero }}" alt="{{ $pkg->title }}" loading="lazy">
                                    <span class="pk-typebadge {{ $isCoop ? 'coop' : 'solo' }}">{{ $isCoop ? 'CO-OP PARTNERSHIP' : 'SOLO PACKAGE' }}</span>
                                    <button type="button" class="pk-heart" aria-label="Save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg></button>
                                    @if($pkg->photosCount())<span class="pk-photos">📷 {{ $pkg->photosCount() }} Photos</span>@endif
                                </div>
                                <div class="pk-main">
                                    <h3 class="pk-title">{{ $pkg->title }}</h3>
                                    <div class="pk-pro">
                                        {{ $pro?->name ?? 'Verified Professional' }} <span class="pk-verif">✔</span>
                                        @if($isCoop && $pkg->coopPartner)
                                            <span class="pk-x">×</span> {{ $pkg->coopPartner->name }} <span class="pk-verif">✔</span>
                                        @endif
                                    </div>
                                    @if($pkg->isMultiService())<span class="pk-msp">MULTI-SERVICE PRO</span>@endif
                                    <div class="pk-tags">
                                        @foreach(array_slice($svcTags, 0, 4) as $t)<span class="pk-tag">{{ $t }}</span>@endforeach
                                    </div>
                                    <p class="pk-desc">{{ \Illuminate\Support\Str::limit($pkg->description, 120) }}</p>
                                    <div class="pk-facts">
                                        <div class="pk-fact"><span>Event Coverage</span><b>{{ $pkg->coverage ?: $pkg->duration ?: '—' }}</b></div>
                                        <div class="pk-fact"><span>Team on Event Day</span>
                                            @forelse(array_slice($pkg->team ?? [], 0, 4) as $member)<div class="row">{{ $member }}</div>@empty<b>{{ $isCoop ? 'Partner team' : 'Solo pro' }}</b>@endforelse
                                        </div>
                                        <div class="pk-fact"><span>Guests Served</span><b>{{ $pkg->guests ?: '—' }}</b></div>
                                    </div>
                                    <div class="pk-cardfoot">
                                        @if($pro?->profile?->city)<span>📍 {{ $pro->profile->city }}{{ $pro->profile->state ? ', ' . $pro->profile->state : '' }}</span>@endif
                                        @if($pkg->serves_regions)<span>🌐 Serves {{ $pkg->serves_regions }}</span>@endif
                                        @if($pkg->availability)<span>🗓 {{ $pkg->availability }}</span>@endif
                                        @if($rating)<span><span class="star">★</span> {{ $rating }} ({{ $pro->reviews_count }} reviews)</span>@else<span>New on GigResource</span>@endif
                                    </div>
                                </div>
                                <div class="pk-pricebox">
                                    <span class="lbl">Starting at</span>
                                    <span class="amt">{{ $total_ }}</span>
                                    <span class="tp">Total Package</span>
                                    <a class="pk-btn pk-btn-primary" href="{{ route('public.package', $pkg->slug) }}">View Package</a>
                                    <a class="pk-btn pk-btn-ghost" href="{{ route('public.package', $pkg->slug) }}">Customize Package</a>
                                    @if($pkg->savings_pct)<div class="pk-save">Save up to {{ $pkg->savings_pct }}%<br>vs. booking separately</div>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div style="margin-top:22px; display:flex; justify-content:center;">{{ $packages->links() }}</div>
                @else
                    <div class="pk-empty">
                        <div style="font-size:40px;">🎁</div>
                        <h3>No packages match your mix</h3>
                        <p>Try removing a service or switching provider type — packages need every selected service to appear.</p>
                        <a href="{{ route('public.packages') }}">Clear filters</a>
                    </div>
                @endif
            </div>

            {{-- Right rail --}}
            <aside class="pk-side">
                <div class="pk-scard">
                    <h4>Where Packages Are Available <span style="color:var(--muted);font-weight:600;">ⓘ</span></h4>
                    <div class="pk-map">
                        <svg viewBox="0 0 200 150" preserveAspectRatio="none"><rect width="200" height="150" fill="#dbeafe"/><path d="M0 90 Q50 70 100 85 T200 80 V150 H0 Z" fill="#bfdbfe"/><path d="M120 0 L140 40 L110 60 L130 90 L160 70 L200 90 V0 Z" fill="#e0f2fe"/></svg>
                    </div>
                    @foreach($availability as $city => $count)
                        <div class="pk-avail"><span>{{ $city }}</span><b>{{ $count }}</b></div>
                    @endforeach
                    <div class="pk-legend">
                        <span class="solo">Solo Multi-Pros Packages</span>
                        <span class="coop">Co-Op Partnership Packages</span>
                    </div>
                </div>

                <div class="pk-scard">
                    <h4>Why Package Bundles?</h4>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div><b>Better Value</b><span>Save more with bundle pricing</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg><div><b>Seamless Experience</b><span>Professionals coordinate for you</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><div><b>One Contract</b><span>One payment, one point of contact</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg><div><b>Customizable</b><span>Adjust services to fit your needs</span></div></div>
                    <a class="pk-howbtn" href="#">How Packages Work</a>
                </div>

                @if($recent->count())
                    <div class="pk-scard">
                        <h4>Recently Viewed Packages</h4>
                        <div class="pk-recent">
                            @foreach($recent->take(3) as $r)
                                @php $rhero = $r->heroUrls(1)[0] ?? 'https://images.unsplash.com/' . $stock[$r->id % count($stock)] . '?w=160&q=60&auto=format&fit=crop'; @endphp
                                <a href="{{ route('public.package', $r->slug) }}">
                                    <img src="{{ $rhero }}" alt="{{ $r->title }}" loading="lazy">
                                    <div class="cap">{{ \Illuminate\Support\Str::limit($r->title, 32) }}</div>
                                </a>
                            @endforeach
                        </div>
                        <a class="pk-viewall" href="{{ route('public.packages') }}">View All Recently Viewed →</a>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>

<script>
    function pkFilterSvc(v) {
        v = (v || '').toLowerCase();
        document.querySelectorAll('#pkSvcList .pk-check').forEach(function (el) {
            var match = el.getAttribute('data-svc').indexOf(v) !== -1;
            el.style.display = match ? 'flex' : 'none';
        });
    }
    function pkToggleMore() {
        var btn = document.getElementById('pkShowMore');
        var hidden = document.querySelectorAll('#pkSvcList .pk-check.more');
        var expand = btn.textContent.indexOf('More') !== -1;
        hidden.forEach(function (el) { el.classList.toggle('hidden', !expand); });
        btn.textContent = expand ? 'Show Less ▴' : 'Show More ▾';
    }
</script>
@endsection
