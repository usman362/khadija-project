@extends('layouts.landing')

@section('title', 'Shop Packages — Complete Event Solutions')
@section('meta_description', 'Browse ready-to-book professional packages on GigResource — photography, catering, décor, planning and more. Filter by category and price.')

@php
    $f = $filters;
    $sortLabels = ['trending' => 'Trending', 'newest' => 'Newest', 'price_low' => 'Price: Low to High', 'price_high' => 'Price: High to Low'];
    $activeSort = $sortLabels[$f['sort']] ?? 'Trending';
@endphp

@push('styles')
<style>
    .shp-wrap { background: var(--bg-soft); min-height: 70vh; }
    .shp-hero { background: linear-gradient(135deg, #0f1b35, #1e3a8a); color: #fff; padding: 46px 0 40px; }
    .shp-hero .lp-container { max-width: 1180px; }
    .shp-eyebrow { font-size: 12.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: #93c5fd; }
    .shp-hero .shp-h1 { color: #fff !important; font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 900; line-height: 1.1; letter-spacing: -.02em; margin: 8px 0 10px; }
    .shp-sub { font-size: 1.05rem; color: #cbd5e1; max-width: 560px; }
    .shp-search { margin-top: 22px; display: flex; gap: 10px; max-width: 540px; }
    .shp-search input { flex: 1; border: none; border-radius: 12px; padding: 13px 16px; font-size: 15px; font-family: inherit; }
    .shp-search button { border: none; border-radius: 12px; padding: 0 22px; font-size: 15px; font-weight: 800; background: var(--orange); color: #fff; cursor: pointer; }

    .shp-container { max-width: 1180px; margin: 0 auto; padding: 24px; }
    .shp-bar { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 22px; }
    .shp-count { font-size: 14px; font-weight: 700; color: var(--ink); }
    .shp-count b { color: var(--blue); }
    .shp-sort { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--muted); }
    .shp-sort select { border: 1px solid var(--line); border-radius: 10px; padding: 8px 11px; font-size: 13.5px; font-weight: 700; color: var(--ink); background: #fff; font-family: inherit; cursor: pointer; }

    .shp-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 22px; }
    .shp-chip { display: inline-flex; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 7px 15px; font-size: 13px; font-weight: 700; color: var(--ink-2); text-decoration: none; white-space: nowrap; }
    .shp-chip:hover { border-color: var(--blue); color: var(--blue); }
    .shp-chip.on { background: var(--blue); border-color: var(--blue); color: #fff; }

    .shp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 20px; }
    .shp-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; text-decoration: none; display: flex; flex-direction: column; transition: transform .15s, box-shadow .15s; }
    .shp-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px -20px rgba(15,27,53,.45); }
    .shp-media { position: relative; aspect-ratio: 3/2; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .shp-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .shp-badge { position: absolute; top: 10px; left: 10px; background: rgba(255,255,255,.94); color: #0f1b35; font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 999px; }
    .shp-type { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 800; letter-spacing: .3px; padding: 4px 9px; border-radius: 6px; color: #fff; }
    .shp-type.solo { background: #2563eb; } .shp-type.coop { background: #7c3aed; }
    .shp-body { padding: 15px 16px 17px; display: flex; flex-direction: column; flex: 1; }
    .shp-title { font-size: 15.5px; font-weight: 800; color: var(--ink); line-height: 1.3; margin-bottom: 7px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .shp-pro { font-size: 12.5px; color: var(--muted); margin-bottom: 4px; }
    .shp-rating { font-size: 12.5px; color: var(--ink-2); font-weight: 600; }
    .shp-star { color: #f59e0b; }
    .shp-foot { margin-top: auto; padding-top: 13px; display: flex; align-items: center; justify-content: space-between; }
    .shp-price { font-size: 16px; font-weight: 900; color: var(--ink); }
    .shp-view { font-size: 12.5px; font-weight: 800; color: var(--blue); }

    .shp-empty { text-align: center; padding: 60px 20px; background: #fff; border: 1px dashed var(--line); border-radius: 18px; }
    .shp-empty h3 { font-size: 19px; font-weight: 800; color: var(--ink); margin: 12px 0 6px; }
    .shp-empty p { color: var(--muted); margin: 0 0 18px; }
    .shp-empty a { display: inline-flex; background: var(--blue); color: #fff; border-radius: 11px; padding: 11px 22px; font-weight: 800; text-decoration: none; }
    .shp-pager { margin-top: 30px; display: flex; justify-content: center; }
</style>
@endpush

@section('content')
<div class="shp-wrap">
    {{-- Hero --}}
    <div class="shp-hero">
        <div class="lp-container">
            <div class="shp-eyebrow">🛍️ Shop Packages</div>
            <h1 class="shp-h1">Complete event solutions, ready to book</h1>
            <p class="shp-sub">Browse fixed-scope packages from professionals — photography, catering, décor, planning and more. Pick one, message the pro, and lock it in.</p>
            <form class="shp-search" method="GET" action="{{ route('public.packages') }}">
                @if($f['catSlug'])<input type="hidden" name="category" value="{{ $f['catSlug'] }}">@endif
                @if($f['sort'] !== 'trending')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif
                <input type="text" name="q" value="{{ $f['q'] }}" placeholder="Search packages — e.g. wedding photography">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="shp-container">
        {{-- Category chips --}}
        <div class="shp-chips">
            <a href="{{ route('public.packages', array_filter(['sort' => $f['sort'] !== 'trending' ? $f['sort'] : null, 'q' => $f['q'] ?: null])) }}"
               class="shp-chip {{ $f['catSlug'] === '' ? 'on' : '' }}">All Packages</a>
            @foreach($categories as $cat)
                <a href="{{ route('public.packages', array_filter(['category' => $cat->slug, 'sort' => $f['sort'] !== 'trending' ? $f['sort'] : null, 'q' => $f['q'] ?: null])) }}"
                   class="shp-chip {{ $f['catSlug'] === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </div>

        {{-- Count + sort --}}
        <div class="shp-bar">
            <span class="shp-count"><b>{{ $total }}</b> {{ $total === 1 ? 'package' : 'packages' }}{{ $f['q'] ? ' for "' . $f['q'] . '"' : '' }}</span>
            <form class="shp-sort" method="GET" action="{{ route('public.packages') }}">
                @if($f['catSlug'])<input type="hidden" name="category" value="{{ $f['catSlug'] }}">@endif
                @if($f['q'])<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                <label for="shp-sort-sel">Sort by:</label>
                <select id="shp-sort-sel" name="sort" onchange="this.form.submit()">
                    <option value="trending" @selected($f['sort']==='trending')>Trending</option>
                    <option value="newest" @selected($f['sort']==='newest')>Newest</option>
                    <option value="price_low" @selected($f['sort']==='price_low')>Price: Low to High</option>
                    <option value="price_high" @selected($f['sort']==='price_high')>Price: High to Low</option>
                </select>
            </form>
        </div>

        {{-- Grid --}}
        @if($packages->count())
            <div class="shp-grid">
                @foreach($packages as $pkg)
                    @php
                        $stock = ['photo-1519741497674-611481863552','photo-1511795409834-ef04bbd61622','photo-1530103862676-de8c9debad1d','photo-1492684223066-81342ee5ff30','photo-1464366400600-7168b8af9bc3','photo-1519225421980-715cb0215aed'];
                        $hero = $pkg->heroUrls(1)[0]
                            ?? 'https://images.unsplash.com/' . $stock[$pkg->id % count($stock)] . '?w=480&q=70&auto=format&fit=crop';
                        $pro  = $pkg->user;
                        $rating = $pro?->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
                        $isCoop = $pkg->type === 'co-op';
                    @endphp
                    <a class="shp-card" href="{{ route('public.package', $pkg->slug) }}">
                        <div class="shp-media">
                            @if($hero)
                                <img src="{{ $hero }}" alt="{{ $pkg->title }}" loading="lazy">
                            @endif
                            @if($pkg->category)<span class="shp-badge">{{ $pkg->category->name }}</span>@endif
                            <span class="shp-type {{ $isCoop ? 'coop' : 'solo' }}">{{ $isCoop ? 'CO-OP' : 'SOLO' }}</span>
                        </div>
                        <div class="shp-body">
                            <div class="shp-title">{{ $pkg->title }}</div>
                            <div class="shp-pro">by {{ $pro?->name ?? 'Verified Professional' }}{{ $pro?->profile?->city ? ' · ' . $pro->profile->city : '' }}</div>
                            <div class="shp-rating">
                                @if($rating)
                                    <span class="shp-star">★</span> {{ $rating }}
                                    <span style="color:var(--muted);font-weight:500;">({{ $pro->reviews_count }})</span>
                                @else
                                    <span style="color:var(--muted);font-weight:600;">New on GigResource</span>
                                @endif
                            </div>
                            <div class="shp-foot">
                                <span class="shp-price">{{ $pkg->priceLabel() }}</span>
                                <span class="shp-view">View Package →</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="shp-pager">{{ $packages->links() }}</div>
        @else
            <div class="shp-empty">
                <div style="font-size:40px;">🛍️</div>
                <h3>No packages here yet</h3>
                <p>{{ $f['q'] || $f['catSlug'] ? 'Nothing matches your filters — try clearing them.' : 'Professionals are still publishing their packages. Check back soon.' }}</p>
                @if($f['q'] || $f['catSlug'])
                    <a href="{{ route('public.packages') }}">Clear filters</a>
                @else
                    <a href="{{ route('public.browse') }}">Browse Professionals</a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
