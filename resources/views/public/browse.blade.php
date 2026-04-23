@extends('layouts.public')

@section('title', 'Browse Professionals | ' . config('app.name'))

@push('styles')
<style>
    /* ─── BROWSE PAGE — dark marketplace grid ─── */
    .browse-hero {
        padding: 120px 0 40px;
        position: relative;
        background: linear-gradient(180deg, rgba(59,130,246,0.06) 0%, transparent 100%);
    }
    .browse-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 20% 30%, rgba(139,92,246,0.08), transparent 40%),
            radial-gradient(circle at 80% 70%, rgba(59,130,246,0.06), transparent 40%);
        pointer-events: none;
    }
    .browse-hero .container { position: relative; z-index: 1; }
    .browse-hero h1 {
        font-size: 2.4rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 10px;
    }
    .browse-hero h1 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .browse-hero p {
        color: var(--text-muted);
        font-size: 1.05rem;
        max-width: 640px;
    }

    /* ── Toolbar: search + sort + result count ── */
    .browse-toolbar {
        margin-top: 28px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
    }
    .browse-search {
        flex: 1 1 340px;
        position: relative;
        max-width: 520px;
    }
    .browse-search input {
        width: 100%;
        padding: 14px 18px 14px 46px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #fff;
        font-family: inherit;
        font-size: 0.95rem;
        transition: background 0.2s, border-color 0.2s;
    }
    .browse-search input::placeholder { color: var(--text-muted); }
    .browse-search input:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(139, 92, 246, 0.45);
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
    }
    .browse-search svg {
        position: absolute;
        top: 50%; left: 16px;
        transform: translateY(-50%);
        width: 18px; height: 18px;
        color: var(--text-muted);
        pointer-events: none;
    }
    .browse-toolbar-right {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-left: auto;
        flex-wrap: wrap;
    }
    .browse-count {
        font-size: 0.88rem;
        color: var(--text-muted);
    }
    .browse-count strong { color: #fff; font-weight: 700; }
    .browse-sort {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--text-muted);
    }
    .browse-sort select {
        padding: 10px 38px 10px 14px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04)
            url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23c8cdd8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>")
            right 12px center/14px 14px no-repeat;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        font-family: inherit;
        font-size: 0.88rem;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }
    .browse-sort select:focus { outline: none; border-color: rgba(139,92,246,0.45); }

    /* ── Layout: sidebar + grid ── */
    .browse-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 32px;
        padding: 40px 0 100px;
    }
    @media (max-width: 960px) { .browse-layout { grid-template-columns: 1fr; } }

    /* ── Sidebar filters ── */
    .browse-filters {
        position: sticky;
        top: 140px;
        align-self: start;
        background: var(--bg-card);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 22px;
        max-height: calc(100vh - 160px);
        overflow-y: auto;
    }
    @media (max-width: 960px) { .browse-filters { position: static; max-height: none; } }
    .browse-filters h3 {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.4px;
        color: var(--text-muted);
        margin-bottom: 14px;
    }
    .browse-filters .filter-group + .filter-group {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
    .browse-filters label,
    .browse-filters .filter-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        font-size: 0.9rem;
        color: var(--text-light);
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .browse-filters label:hover,
    .browse-filters .filter-option:hover {
        background: rgba(255, 255, 255, 0.04);
        color: #fff;
    }
    .browse-filters input[type="radio"],
    .browse-filters input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 16px; height: 16px;
        border-radius: 50%;
        border: 1.5px solid rgba(255, 255, 255, 0.25);
        background: transparent;
        cursor: pointer;
        flex-shrink: 0;
        position: relative;
        transition: border-color 0.15s;
    }
    .browse-filters input[type="checkbox"] { border-radius: 4px; }
    .browse-filters input[type="radio"]:checked,
    .browse-filters input[type="checkbox"]:checked {
        border-color: var(--primary);
        background: var(--primary);
    }
    .browse-filters input[type="radio"]:checked::after {
        content: '';
        position: absolute;
        inset: 3px;
        background: #fff;
        border-radius: 50%;
    }
    .browse-filters input[type="checkbox"]:checked::after {
        content: '✓';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 11px;
        font-weight: 900;
    }
    .browse-filters select,
    .browse-filters input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff;
        font-family: inherit;
        font-size: 0.88rem;
    }
    .browse-filters select:focus,
    .browse-filters input[type="text"]:focus {
        outline: none;
        border-color: rgba(139, 92, 246, 0.45);
    }
    .browse-filters .filter-actions {
        display: flex;
        gap: 8px;
        margin-top: 22px;
    }
    .browse-filters .btn-apply {
        flex: 1;
        padding: 10px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: filter 0.2s, transform 0.2s;
    }
    .browse-filters .btn-apply:hover { filter: brightness(1.08); transform: translateY(-1px); }
    .browse-filters .btn-clear {
        padding: 10px 14px;
        background: transparent;
        color: var(--text-muted);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: color 0.15s, border-color 0.15s;
    }
    .browse-filters .btn-clear:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

    /* ── Category chips at top of filter ── */
    .filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .filter-chips a {
        font-size: 0.8rem;
        color: var(--text-light);
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 6px 12px;
        border-radius: 999px;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .filter-chips a:hover {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.35);
        color: #fff;
    }

    /* ── Professional grid ── */
    .pro-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    .pro-card {
        position: relative;
        display: flex;
        flex-direction: column;
        background: var(--bg-card);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        overflow: hidden;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        text-decoration: none;
        color: inherit;
    }
    .pro-card:hover {
        transform: translateY(-6px);
        border-color: rgba(139, 92, 246, 0.35);
        box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35);
    }
    .pro-card-cover {
        position: relative;
        height: 120px;
        background: linear-gradient(135deg, rgba(59,130,246,0.35), rgba(139,92,246,0.35));
        background-size: cover;
        background-position: center;
    }
    .pro-card-cover::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 40%, rgba(21, 29, 53, 0.85) 100%);
    }
    .pro-card-badges {
        position: absolute;
        top: 10px;
        left: 10px;
        display: flex;
        gap: 6px;
        z-index: 2;
    }
    .pro-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        background: rgba(8, 12, 22, 0.75);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        color: #fff;
    }
    .pro-badge.top {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.9), rgba(239, 68, 68, 0.9));
        border-color: rgba(255, 255, 255, 0.25);
    }
    .pro-badge.verified {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.9), rgba(16, 185, 129, 0.9));
        border-color: rgba(255, 255, 255, 0.2);
    }
    .pro-badge svg { width: 11px; height: 11px; }

    .pro-card-body {
        position: relative;
        padding: 0 20px 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
        margin-top: -32px;
    }
    .pro-card-avatar {
        position: relative;
        z-index: 2;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--bg-card);
        background: var(--bg-card);
        margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    .pro-card-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pro-card-name .check {
        width: 15px; height: 15px;
        color: var(--primary);
        flex-shrink: 0;
    }
    .pro-card-headline {
        font-size: 0.86rem;
        color: var(--text-light);
        line-height: 1.4;
        margin-bottom: 10px;
        min-height: 2.4em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pro-card-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 12px;
    }
    .pro-card-meta .sep { opacity: 0.4; }
    .pro-card-meta .rating {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #ffb648;
        font-weight: 700;
    }
    .pro-card-meta .rating svg { width: 13px; height: 13px; fill: currentColor; stroke: none; }
    .pro-card-meta .rating .count { color: var(--text-muted); font-weight: 500; margin-left: 2px; }
    .pro-card-meta .loc {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .pro-card-meta .loc svg { width: 12px; height: 12px; }

    .pro-card-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 14px;
        border-top: 1px dashed rgba(255, 255, 255, 0.08);
        margin-top: auto;
    }
    .pro-card-price {
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .pro-card-price strong {
        color: #fff;
        font-size: 0.95rem;
        font-weight: 800;
    }
    .pro-card-cta {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: gap 0.2s, color 0.2s;
    }
    .pro-card:hover .pro-card-cta { gap: 8px; color: var(--accent); }

    /* ── Empty state ── */
    .browse-empty {
        text-align: center;
        padding: 80px 20px;
        background: var(--bg-card);
        border: 1px dashed rgba(255, 255, 255, 0.1);
        border-radius: 18px;
    }
    .browse-empty .icon {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.6;
    }
    .browse-empty h3 { font-size: 1.3rem; margin-bottom: 8px; color: #fff; }
    .browse-empty p { color: var(--text-muted); margin-bottom: 20px; }

    /* ── Pagination ── */
    .browse-pagination {
        margin-top: 32px;
        display: flex;
        justify-content: center;
    }
    .browse-pagination nav > div { display: flex !important; gap: 6px; }
    .browse-pagination span,
    .browse-pagination a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        color: var(--text-light);
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .browse-pagination a:hover {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.35);
        color: #fff;
    }
    .browse-pagination .page-item.active > span,
    .browse-pagination span[aria-current] {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-color: transparent;
    }
    .browse-pagination .disabled > span { opacity: 0.4; cursor: not-allowed; }
</style>
@endpush

@section('content')

{{-- ── HERO + toolbar ─────────────────────────────── --}}
<section class="browse-hero">
    <div class="container">
        <h1>Find the <span class="gradient-text">right pro</span> for your event</h1>
        <p>Photographers, caterers, DJs, planners — every verified professional in one place, filterable by rating and location.</p>

        <form method="GET" action="{{ route('public.browse') }}" class="browse-toolbar" id="browseForm">
            {{-- Search --}}
            <div class="browse-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Try 'wedding photographer' or 'DJ in Austin'…" autocomplete="off">
            </div>

            <div class="browse-toolbar-right">
                <div class="browse-count">
                    <strong>{{ $pros->total() }}</strong> {{ Str::plural('pro', $pros->total()) }} found
                </div>
                <label class="browse-sort">
                    <span>Sort:</span>
                    <select name="sort" onchange="document.getElementById('browseForm').submit()">
                        <option value="top"    {{ $filters['sort'] === 'top'    ? 'selected' : '' }}>Top-rated</option>
                        <option value="rating" {{ $filters['sort'] === 'rating' ? 'selected' : '' }}>Highest rating</option>
                        <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>Newest</option>
                    </select>
                </label>
            </div>

            {{-- Preserve other filter values across toolbar form submits --}}
            <input type="hidden" name="city" value="{{ $filters['city'] }}">
            <input type="hidden" name="rating_min" value="{{ $filters['rating_min'] }}">
            @if($filters['verified'])<input type="hidden" name="verified" value="1">@endif
        </form>
    </div>
</section>

{{-- ── Sidebar + Grid ─────────────────────────────── --}}
<div class="container">
    <div class="browse-layout">

        {{-- SIDEBAR FILTERS --}}
        <aside>
            <form method="GET" action="{{ route('public.browse') }}" class="browse-filters">
                {{-- Preserve search keyword from toolbar --}}
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

                {{-- Category quick chips (navigate via keyword for now) --}}
                @if($categories->isNotEmpty())
                    <div class="filter-group">
                        <h3>Popular Categories</h3>
                        <div class="filter-chips">
                            @foreach($categories->take(10) as $cat)
                                <a href="{{ route('public.browse', ['q' => $cat->name]) }}">
                                    @if($cat->icon){{ $cat->icon }} @endif{{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- City filter --}}
                <div class="filter-group">
                    <h3>Location</h3>
                    <select name="city">
                        <option value="">Any city</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" {{ $filters['city'] === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rating filter --}}
                <div class="filter-group">
                    <h3>Minimum Rating</h3>
                    @foreach([0 => 'Any rating', 4 => '4.0 & up', 4.5 => '4.5 & up', 5 => '5.0 only'] as $val => $label)
                        <label>
                            <input type="radio" name="rating_min" value="{{ $val }}" {{ (float) $filters['rating_min'] === (float) $val ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Verified filter --}}
                <div class="filter-group">
                    <h3>Trust</h3>
                    <label>
                        <input type="checkbox" name="verified" value="1" {{ $filters['verified'] ? 'checked' : '' }}>
                        <span>Verified pros only</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="filter-actions">
                    <button type="submit" class="btn-apply">Apply filters</button>
                    <a href="{{ route('public.browse') }}" class="btn-clear">Clear</a>
                </div>
            </form>
        </aside>

        {{-- RESULTS GRID --}}
        <div>
            @if($pros->isEmpty())
                <div class="browse-empty">
                    <div class="icon">🔍</div>
                    <h3>No professionals match your filters</h3>
                    <p>Try loosening the rating or removing the city filter.</p>
                    <a href="{{ route('public.browse') }}" class="btn btn-primary btn-sm">Reset filters</a>
                </div>
            @else
                <div class="pro-grid">
                    @foreach($pros as $pro)
                        @php
                            $p = $pro->profile;
                            $isVerified = $p
                                && $p->trade_license_verified_at
                                && $p->liability_insurance_verified_at
                                && $p->workers_comp_verified_at;
                            $isTop = (float) ($pro->reviews_avg ?? 0) >= 4.5 && (int) ($pro->reviews_count ?? 0) >= 5;
                            $loc  = $p ? collect([$p->city, $p->state])->filter()->implode(', ') : null;
                        @endphp
                        <a href="{{ route('public.professional.show', $pro) }}" class="pro-card">
                            {{-- Cover --}}
                            <div class="pro-card-cover" @if($pro->cover_image_url) style="background-image: url('{{ $pro->cover_image_url }}');" @endif>
                                <div class="pro-card-badges">
                                    @if($isTop)
                                        <span class="pro-badge top">
                                            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            Top rated
                                        </span>
                                    @endif
                                    @if($isVerified)
                                        <span class="pro-badge verified">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Verified
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Body --}}
                            <div class="pro-card-body">
                                <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="pro-card-avatar" loading="lazy">

                                <div class="pro-card-name">
                                    {{ $pro->name }}
                                    @if($isVerified)
                                        <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.39 2.39a3 3 0 0 0 2.12.88l3.39 0a3 3 0 0 1 3 3l0 3.39a3 3 0 0 0 .88 2.12L22 16.39l-2.22 2.22a3 3 0 0 0-.88 2.12l0 3.39-3.39 0a3 3 0 0 0-2.12.88L12 27.39l-2.39-2.39a3 3 0 0 0-2.12-.88l-3.39 0 0-3.39a3 3 0 0 0-.88-2.12L2 16.39l2.22-2.22a3 3 0 0 0 .88-2.12l0-3.39 3.39 0a3 3 0 0 0 2.12-.88L12 5.39 12 2z" transform="scale(0.58) translate(6 4)"/><polyline points="9 12 11 14 15 10"/></svg>
                                    @endif
                                </div>

                                <p class="pro-card-headline">
                                    {{ $p->headline ?? $p->bio ?? 'Professional event service provider.' }}
                                </p>

                                <div class="pro-card-meta">
                                    @if($pro->reviews_count > 0)
                                        <span class="rating">
                                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            {{ number_format($pro->reviews_avg, 1) }}
                                            <span class="count">({{ $pro->reviews_count }})</span>
                                        </span>
                                    @else
                                        <span class="rating" style="color: var(--text-muted);">
                                            <svg viewBox="0 0 24 24" style="fill: currentColor;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            New
                                        </span>
                                    @endif

                                    @if($loc)
                                        <span class="sep">•</span>
                                        <span class="loc">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            {{ $loc }}
                                        </span>
                                    @endif
                                </div>

                                <div class="pro-card-foot">
                                    <div class="pro-card-price">
                                        @if($p && $p->hourly_rate)
                                            From <strong>${{ number_format((float) $p->hourly_rate, 0) }}</strong>/hr
                                        @else
                                            <span style="color: var(--text-muted);">Contact for pricing</span>
                                        @endif
                                    </div>
                                    <span class="pro-card-cta">
                                        View profile
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($pros->hasPages())
                    <div class="browse-pagination">
                        {{ $pros->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection
