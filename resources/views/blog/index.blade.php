@extends('layouts.landing')

@php
    $seoTitle       = 'Blog & Insights — GigResource';
    $seoDescription = 'Expert tips, industry trends, and in-depth guides to help event professionals plan, manage, and deliver unforgettable experiences.';
    $showFeatured   = $featured && $posts->onFirstPage() && !$activeCategory && $search === '';
    $blItems        = $posts->getCollection();
    $sidebar        = $showFeatured ? $blItems->take(3) : collect();
    $grid           = $showFeatured ? $blItems->slice(3) : $blItems;
@endphp

@section('content')

@push('styles')
<style>
    /* ════════ Blog index (light) — page-scoped ════════ */
    .bl-section { padding: 56px 0; }

    /* ── HERO ───────────────────────────────────────── */
    .bl-hero { padding: 46px 0 40px; position: relative; overflow: hidden; }
    .bl-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, var(--bg-soft-2) 0%, transparent 100%); z-index: 0; }
    .bl-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 44px; align-items: center; }
    .bl-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: var(--orange-dark); }
    .bl-h1 { font-size: 48px; font-weight: 800; letter-spacing: -1.3px; line-height: 1.1; color: var(--ink); margin: 14px 0 0; }
    .bl-h1 .o { color: var(--orange); }
    .bl-h1 .b { color: var(--blue); }
    .bl-hero p.sub { font-size: 16px; color: var(--muted); line-height: 1.65; margin: 20px 0 26px; max-width: 460px; }
    .bl-search { position: relative; max-width: 440px; }
    .bl-search svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--faint); }
    .bl-search input { width: 100%; box-sizing: border-box; padding: 15px 18px 15px 46px; border: 1px solid var(--line); border-radius: 12px; background: #fff; font-size: 14.5px; font-family: inherit; color: var(--ink); box-shadow: var(--shadow-sm); }
    .bl-search input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }

    /* hero illustration */
    .bl-hero-art { position: relative; min-height: 380px; }
    .bl-art-dots { position: absolute; top: 10px; right: 0; width: 110px; height: 110px; opacity: 0.5; background-image: radial-gradient(var(--blue) 1.6px, transparent 1.6px); background-size: 16px 16px; z-index: 0; }
    .bl-window { position: relative; z-index: 2; background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow-lg); padding: 16px; max-width: 360px; margin: 0 auto; }
    .bl-window-bar { display: flex; align-items: center; gap: 6px; margin-bottom: 14px; }
    .bl-window-bar i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    .bl-window-bar .l1 { background: #ff5f57; } .bl-window-bar .l2 { background: #febc2e; } .bl-window-bar .l3 { background: #28c840; }
    .bl-window-bar b { margin-left: 8px; font-family: var(--ff-head); font-size: 14px; color: var(--ink); font-weight: 800; letter-spacing: 1px; }
    .bl-window-img { height: 100px; border-radius: 11px; background: linear-gradient(135deg, #bfdbfe, #93c5fd); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .bl-window-img svg { width: 40px; height: 40px; color: #fff; opacity: 0.85; }
    .bl-window-line { height: 9px; border-radius: 6px; background: var(--bg-soft-2); margin-bottom: 9px; }
    .bl-window-line.s { max-width: 70%; }
    .bl-3d { position: absolute; z-index: 3; }
    .bl-3d-chat { left: -6px; top: 96px; width: 56px; height: 56px; filter: drop-shadow(0 12px 18px rgba(37,99,235,0.25)); }
    .bl-3d-bulb { right: -2px; top: 78px; width: 56px; height: 56px; filter: drop-shadow(0 12px 18px rgba(249,115,22,0.3)); }
    .bl-3d-mug { left: 20px; bottom: -2px; width: 56px; height: 56px; filter: drop-shadow(0 10px 14px rgba(37,99,235,0.25)); }
    .bl-3d-plant { right: 8px; bottom: -6px; width: 60px; height: 80px; filter: drop-shadow(0 10px 14px rgba(15,27,53,0.12)); }
    .bl-inspire { position: absolute; right: -8px; bottom: 30px; z-index: 4; background: #fff; border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow-lg); padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start; max-width: 200px; }
    .bl-inspire .sp { width: 26px; height: 26px; border-radius: 8px; background: linear-gradient(135deg,#fb923c,#ea580c); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bl-inspire .sp svg { width: 14px; height: 14px; color: #fff; }
    .bl-inspire b { font-size: 12.5px; color: var(--ink); display: block; }
    .bl-inspire span { font-size: 11px; color: var(--muted); line-height: 1.4; }

    /* ── FILTER BAR ─────────────────────────────────── */
    .bl-filter { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 30px; }
    .bl-pills { display: flex; gap: 9px; flex-wrap: wrap; }
    .bl-pill { padding: 9px 16px; border-radius: 10px; border: 1px solid var(--line); background: #fff; font-size: 13.5px; font-weight: 700; color: var(--ink-2); cursor: pointer; transition: all .15s; white-space: nowrap; }
    .bl-pill:hover { border-color: var(--blue); color: var(--blue); }
    .bl-pill.on { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 6px 14px rgba(37,99,235,0.25); }
    .bl-sort { position: relative; }
    .bl-sort select { appearance: none; padding: 10px 38px 10px 16px; border: 1px solid var(--line); border-radius: 10px; background: #fff; font-size: 13.5px; font-weight: 700; color: var(--ink-2); font-family: inherit; cursor: pointer; }
    .bl-sort svg { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--muted); pointer-events: none; }

    /* ── FEATURED LAYOUT ────────────────────────────── */
    .bl-main { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 26px; margin-bottom: 30px; }
    .bl-feat { position: relative; border-radius: 18px; overflow: hidden; min-height: 460px; display: flex; flex-direction: column; justify-content: flex-end; box-shadow: var(--shadow-lg); }
    .bl-feat img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .bl-feat::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,16,32,0.92) 0%, rgba(10,16,32,0.55) 45%, rgba(10,16,32,0.25) 100%); }
    .bl-feat-body { position: relative; z-index: 2; padding: 34px; }
    .bl-badge { display: inline-block; background: var(--blue); color: #fff; font-size: 11px; font-weight: 800; letter-spacing: .5px; padding: 6px 13px; border-radius: 8px; }
    .bl-feat-date { color: rgba(255,255,255,0.8); font-size: 13px; margin: 18px 0 10px; }
    .bl-feat h2 { color: #fff; font-size: 28px; font-weight: 800; line-height: 1.2; letter-spacing: -0.5px; max-width: 420px; }
    .bl-feat p { color: rgba(255,255,255,0.85); font-size: 14px; line-height: 1.6; margin: 12px 0 22px; max-width: 400px; }
    .bl-feat .bl-readbtn { display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--ink); font-weight: 800; font-size: 13.5px; padding: 11px 20px; border-radius: 10px; }
    .bl-feat .bl-readbtn svg { width: 15px; height: 15px; color: var(--blue); }

    .bl-side { display: flex; flex-direction: column; gap: 18px; }
    .bl-side-card { display: flex; gap: 15px; align-items: flex-start; }
    .bl-side-card img { width: 110px; height: 86px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
    .bl-side-card .date { font-size: 11.5px; color: var(--muted); }
    .bl-side-card h3 { font-size: 16px; font-weight: 800; color: var(--ink); line-height: 1.25; margin: 4px 0 6px; }
    .bl-side-card p { font-size: 12.5px; color: var(--muted); line-height: 1.5; margin: 0 0 7px; }
    .bl-readmore { display: inline-flex; align-items: center; gap: 6px; color: var(--blue); font-weight: 800; font-size: 12.5px; }
    .bl-readmore svg { width: 13px; height: 13px; }

    /* ── GRID ───────────────────────────────────────── */
    .bl-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
    .bl-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s; display: flex; flex-direction: column; }
    .bl-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .bl-card-img { height: 150px; overflow: hidden; }
    .bl-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
    .bl-card:hover .bl-card-img img { transform: scale(1.06); }
    .bl-card-body { padding: 20px 22px 22px; display: flex; flex-direction: column; flex: 1; }
    .bl-card-body .date { font-size: 11.5px; color: var(--muted); }
    .bl-card-body h3 { font-size: 17px; font-weight: 800; color: var(--ink); line-height: 1.25; margin: 7px 0 9px; }
    .bl-card-body p { font-size: 13px; color: var(--muted); line-height: 1.55; margin: 0 0 16px; }
    .bl-card-body .bl-readmore { margin-top: auto; }
    .bl-empty { text-align: center; padding: 60px 20px; color: var(--muted); }
    .bl-results-head { font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 22px; }

    /* pagination */
    .bl-pager { display: flex; justify-content: center; gap: 8px; margin-top: 40px; }
    .bl-pager a, .bl-pager span { min-width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 12px; border: 1px solid var(--line); border-radius: 9px; background: #fff; color: var(--ink-2); font-size: 13.5px; font-weight: 700; }
    .bl-pager a:hover { border-color: var(--blue); color: var(--blue); }
    .bl-pager .active span { background: var(--blue); border-color: var(--blue); color: #fff; }
    .bl-pager .disabled span { opacity: 0.4; }

    /* ── NEWSLETTER ─────────────────────────────────── */
    .bl-news-wrap { padding: 10px 0 80px; }
    .bl-news { background: #fff; border: 1px solid var(--line); border-radius: 22px; box-shadow: var(--shadow); padding: 34px 40px; display: flex; align-items: center; gap: 24px; position: relative; overflow: hidden; }
    .bl-news-env { width: 84px; height: 84px; flex-shrink: 0; filter: drop-shadow(0 12px 20px rgba(37,99,235,0.25)); }
    .bl-news-txt { flex: 1; min-width: 0; }
    .bl-news-txt h2 { font-size: 25px; font-weight: 800; color: var(--ink); letter-spacing: -0.4px; }
    .bl-news-txt p { font-size: 14px; color: var(--muted); margin: 8px 0 0; max-width: 380px; line-height: 1.5; }
    .bl-news-form { display: flex; gap: 12px; flex-shrink: 0; }
    .bl-news-form input { width: 220px; max-width: 46vw; padding: 13px 16px; border: 1px solid var(--line); border-radius: 11px; font-size: 14px; font-family: inherit; color: var(--ink); background: var(--bg-soft); }
    .bl-news-form input:focus { outline: none; border-color: var(--blue); }
    .bl-news-form button { background: linear-gradient(135deg, #fb923c, var(--orange-dark)); color: #fff; border: none; border-radius: 11px; padding: 0 24px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .bl-news-ok { font-size: 12.5px; color: #059669; font-weight: 700; margin: 10px 0 0; display: none; }
    .bl-news-plane { position: absolute; right: 26px; bottom: 18px; width: 58px; height: 58px; opacity: 0.9; }

    @media (max-width: 980px) {
        .bl-hero-grid { grid-template-columns: 1fr; gap: 30px; }
        .bl-hero-art { display: none; }
        .bl-main { grid-template-columns: 1fr; }
        .bl-grid { grid-template-columns: 1fr 1fr; }
        .bl-news { flex-direction: column; text-align: center; padding: 28px 22px; }
        .bl-news-plane { display: none; }
        .bl-h1 { font-size: 36px; }
    }
    @media (max-width: 560px) {
        .bl-grid { grid-template-columns: 1fr; }
        .bl-news-form { flex-direction: column; width: 100%; }
        .bl-news-form input { width: 100%; max-width: none; }
    }
</style>
@endpush

{{-- ════════════ HERO ════════════ --}}
<section class="bl-hero">
    <div class="lp-container bl-hero-grid">
        <div class="bl-hero-left">
            <span class="bl-eyebrow">Blog &amp; Insights</span>
            <h1 class="bl-h1">Insights that Inspire.<br><span class="o">Knowledge</span> that Powers<br><span class="b">Every Event.</span></h1>
            <p class="sub">Expert tips, industry trends, and in-depth guides to help event professionals plan, manage, and deliver unforgettable experiences.</p>
            <form class="bl-search" action="{{ route('blog.index') }}" method="GET">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search blog articles...">
            </form>
        </div>

        <div class="bl-hero-art">
            <span class="bl-art-dots"></span>
            <svg class="bl-3d bl-3d-chat" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="blChat" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                <path d="M10 6h36a5 5 0 0 1 5 5v20a5 5 0 0 1-5 5H24L13 45V36h-3a5 5 0 0 1-5-5V11a5 5 0 0 1 5-5Z" fill="url(#blChat)"/>
                <circle cx="19" cy="21" r="3" fill="#fff"/><circle cx="28" cy="21" r="3" fill="#fff"/><circle cx="37" cy="21" r="3" fill="#fff"/>
            </svg>
            <svg class="bl-3d bl-3d-bulb" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="blBulb" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs>
                <circle cx="28" cy="28" r="22" fill="url(#blBulb)"/>
                <path d="M28 14c-6 0-10 4-10 10 0 4 2 6 4 8 .8.8 1 1.6 1 2.6h10c0-1 .2-1.8 1-2.6 2-2 4-4 4-8 0-6-4-10-10-10Z" fill="#fff"/>
                <rect x="24" y="35" width="8" height="3" rx="1.5" fill="#ea580c"/>
            </svg>

            <div class="bl-window">
                <div class="bl-window-bar"><i class="l1"></i><i class="l2"></i><i class="l3"></i><b>BLOG</b></div>
                <div class="bl-window-img"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
                <div class="bl-window-line"></div>
                <div class="bl-window-line s"></div>
                <div class="bl-window-line"></div>
            </div>

            <div class="bl-inspire">
                <span class="sp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg></span>
                <div><b>Stay Inspired</b><span>Fresh ideas and practical strategies for every event.</span></div>
            </div>

            <svg class="bl-3d bl-3d-mug" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 18h30v20a10 10 0 0 1-10 10H20a10 10 0 0 1-10-10V18Z" fill="#3b82f6"/>
                <path d="M40 22h4a7 7 0 0 1 0 14h-4" stroke="#2563eb" stroke-width="4" fill="none"/>
                <rect x="10" y="18" width="30" height="6" fill="#60a5fa"/>
            </svg>
            <svg class="bl-3d bl-3d-plant" viewBox="0 0 60 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M30 44C25 35 18 31 12 33c2 7 9 13 18 13Z" fill="#10b981"/>
                <path d="M30 44c4-11 12-15 19-12-2 8-11 14-19 14Z" fill="#34d399"/>
                <path d="M30 46c-2-13 3-22 8-25 3 9-1 20-8 25Z" fill="#059669"/>
                <path d="M18 50h24l-3 24a4 4 0 0 1-4 3.5H25a4 4 0 0 1-4-3.5L18 50Z" fill="#6366f1"/>
                <path d="M16 46h28v6H16z" fill="#818cf8"/>
            </svg>
        </div>
    </div>
</section>

{{-- ════════════ ARTICLES ════════════ --}}
<section class="bl-section" style="padding-top: 20px;">
    <div class="lp-container">

        {{-- Filter + sort --}}
        <div class="bl-filter">
            <div class="bl-pills">
                <a href="{{ route('blog.index') }}" class="bl-pill {{ !$activeCategory ? 'on' : '' }}">All Articles</a>
                @foreach($categories as $cat)
                    <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" class="bl-pill {{ $activeCategory === $cat->slug ? 'on' : '' }}">{{ $cat->name }}</a>
                @endforeach
            </div>
            <div class="bl-sort">
                <select onchange="window.location.href=this.value">
                    @php
                        $sortBase = array_filter(['category' => $activeCategory, 'q' => $search ?: null]);
                    @endphp
                    <option value="{{ route('blog.index', $sortBase + ['sort' => 'latest']) }}" {{ $sort === 'latest' ? 'selected' : '' }}>Latest First</option>
                    <option value="{{ route('blog.index', $sortBase + ['sort' => 'oldest']) }}" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="{{ route('blog.index', $sortBase + ['sort' => 'popular']) }}" {{ $sort === 'popular' ? 'selected' : '' }}>Most Popular</option>
                </select>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        @if($search !== '' || $activeCategory)
            <div class="bl-results-head">
                {{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}
                @if($activeCategory) in “{{ optional($categories->firstWhere('slug', $activeCategory))->name ?? $activeCategory }}” @endif
                @if($search !== '') for “{{ $search }}” @endif
            </div>
        @endif

        {{-- Featured + sidebar (default first page only) --}}
        @if($showFeatured)
            <div class="bl-main">
                <a href="{{ route('blog.show', $featured) }}" class="bl-feat">
                    <img src="{{ $featured->featuredImageUrl() }}" alt="{{ $featured->title }}">
                    <div class="bl-feat-body">
                        <span class="bl-badge">FEATURED</span>
                        <div class="bl-feat-date">{{ optional($featured->published_at)->format('M d, Y') }}</div>
                        <h2>{{ $featured->title }}</h2>
                        <p>{{ \Illuminate\Support\Str::limit($featured->excerpt, 120) }}</p>
                        <span class="bl-readbtn">Read More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                    </div>
                </a>
                <div class="bl-side">
                    @foreach($sidebar as $post)
                        <a href="{{ route('blog.show', $post) }}" class="bl-side-card">
                            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}">
                            <div>
                                <div class="date">{{ optional($post->published_at)->format('M d, Y') }}</div>
                                <h3>{{ $post->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($post->excerpt, 70) }}</p>
                                <span class="bl-readmore">Read More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Grid --}}
        @if($grid->count())
            <div class="bl-grid">
                @foreach($grid as $post)
                    <a href="{{ route('blog.show', $post) }}" class="bl-card">
                        <div class="bl-card-img"><img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" loading="lazy"></div>
                        <div class="bl-card-body">
                            <div class="date">{{ optional($post->published_at)->format('M d, Y') }}</div>
                            <h3>{{ $post->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($post->excerpt, 95) }}</p>
                            <span class="bl-readmore">Read More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                        </div>
                    </a>
                @endforeach
            </div>
        @elseif(!$showFeatured)
            <div class="bl-empty">
                <p style="font-size:16px;font-weight:700;color:var(--ink);">No articles found.</p>
                <p>Try a different category or search term.</p>
                <a href="{{ route('blog.index') }}" class="lp-btn lp-btn-blue" style="margin-top:12px;">View all articles</a>
            </div>
        @endif

        @if($posts->hasPages())
            <div class="bl-pager">{{ $posts->onEachSide(1)->links('pagination::simple-default') }}</div>
        @endif
    </div>
</section>

{{-- ════════════ NEWSLETTER ════════════ --}}
<div class="bl-news-wrap">
    <div class="lp-container">
        <div class="bl-news">
            <svg class="bl-news-env" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="blEnv" x1="0" y1="0" x2="84" y2="84" gradientUnits="userSpaceOnUse"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                <rect x="10" y="24" width="64" height="46" rx="8" fill="url(#blEnv)"/>
                <path d="M10 30l32 22 32-22v-2a4 4 0 0 0-4-4H14a4 4 0 0 0-4 4v2Z" fill="#1d4ed8"/>
                <path d="M10 30l32 22 32-22" stroke="#fff" stroke-width="3.5" fill="none" stroke-linejoin="round"/>
                <circle cx="42" cy="34" r="13" fill="#fbbf24"/>
                <path d="M42 28l1.8 3.7 4 .6-2.9 2.8.7 4-3.6-1.9-3.6 1.9.7-4-2.9-2.8 4-.6L42 28Z" fill="#fff"/>
            </svg>
            <div class="bl-news-txt">
                <h2>Stay Updated</h2>
                <p>Subscribe to our newsletter and never miss the latest tips, trends, and updates.</p>
                <p class="bl-news-ok" id="blNewsOk">Thanks — you're subscribed! 🎉</p>
            </div>
            <form class="bl-news-form" id="blNews" onsubmit="return false;">
                <input type="email" placeholder="Enter your email address" aria-label="Email address" required>
                <button type="submit">Subscribe</button>
            </form>
            <svg class="bl-news-plane" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M58 6L6 28l20 6 6 20 26-48Z" fill="#3b82f6"/>
                <path d="M58 6L26 34l6 20 26-48Z" fill="#1d4ed8"/>
                <path d="M58 6L26 34l-20-6L58 6Z" fill="#60a5fa"/>
            </svg>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var f = document.getElementById('blNews'), ok = document.getElementById('blNewsOk');
        if (f && ok) f.addEventListener('submit', function () { ok.style.display = 'block'; f.reset(); });
    })();
</script>
@endpush

@endsection
