@extends('layouts.landing')

@php
    $seoTitle       = 'Hire ' . $category->name . ' — Verified Pros on GigResource';
    $seoDescription = $category->short_description
        ?: ('Browse top-rated ' . strtolower($category->name) . ' on GigResource. Compare quotes, read reviews, and book the right pro with secure, protected payments.');
    $seoImage       = $category->cover_image ? asset('storage/' . $category->cover_image) : null;
    $browseUrl      = route('public.browse', ['q' => $category->name]);
@endphp

@push('styles')
<style>
    /* ─── Hero ─────────────────────────────────────────────── */
    .cl-hero {
        position: relative;
        padding: 56px 24px 64px;
        overflow: hidden;
        background: linear-gradient(135deg, var(--bg-soft) 0%, var(--bg-soft-2) 100%);
        border-bottom: 1px solid var(--line);
    }
    @if($category->cover_image)
    .cl-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: url('{{ asset('storage/' . $category->cover_image) }}');
        background-size: cover;
        background-position: center;
        opacity: 0.14;
        z-index: 0;
    }
    .cl-hero::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.55) 0%, rgba(247,249,252,0.90) 100%);
        z-index: 0;
    }
    @endif
    .cl-hero-inner {
        max-width: 1180px; margin: 0 auto; width: 100%;
        position: relative; z-index: 1;
    }
    .cl-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px;
        background: rgba(37, 99, 235, 0.08);
        border: 1px solid rgba(37, 99, 235, 0.20);
        border-radius: 999px;
        font-size: 12px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase;
        color: var(--blue-dark);
    }
    .cl-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--blue), var(--orange));
    }
    .cl-hero h1 {
        font-size: 3rem; font-weight: 900;
        margin: 18px 0 14px;
        color: var(--ink);
        line-height: 1.05; letter-spacing: -0.02em;
    }
    .cl-hero h1 .grad {
        background: linear-gradient(135deg, var(--blue), var(--orange));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .cl-hero p.lede {
        font-size: 1.05rem;
        color: var(--muted);
        max-width: 680px;
        line-height: 1.65;
    }
    .cl-stats {
        display: flex; flex-wrap: wrap; gap: 28px;
        margin-top: 26px; padding-top: 22px;
        border-top: 1px solid var(--line);
        font-size: 14px; color: var(--muted);
    }
    .cl-stats b {
        display: block;
        font-size: 1.6rem; font-weight: 900;
        color: var(--ink);
        margin-bottom: 2px;
    }
    .cl-cta {
        display: inline-flex; align-items: center; gap: 8px;
        margin-top: 28px;
        padding: 13px 26px;
        background: linear-gradient(135deg, var(--blue), var(--blue-dark));
        color: #fff; font-weight: 700;
        border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 10px 30px rgba(37,99,235,0.22);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .cl-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 36px rgba(37,99,235,0.30);
        color: #fff;
    }

    /* ─── Sections ─────────────────────────────────────────── */
    .cl-section { max-width: 1180px; margin: 0 auto; padding: 56px 24px; }
    .cl-section-head { margin-bottom: 28px; }
    .cl-section-head h2 {
        font-size: 1.75rem; font-weight: 800;
        color: var(--ink);
        margin: 0 0 6px;
    }
    .cl-section-head p { color: var(--muted); margin: 0; font-size: 0.98rem; }

    /* ─── Featured cards ──────────────────────────────────── */
    .cl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 18px;
    }
    .cl-card {
        background: var(--bg);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 20px;
        display: flex; flex-direction: column; gap: 12px;
        text-decoration: none; color: inherit;
        box-shadow: var(--shadow-sm);
        transition: border-color 0.15s, transform 0.15s, box-shadow 0.15s;
    }
    .cl-card:hover {
        border-color: rgba(37,99,235,0.40);
        transform: translateY(-3px);
        box-shadow: var(--shadow);
    }
    .cl-card-head { display: flex; gap: 12px; align-items: center; }
    .cl-card-avatar {
        width: 54px; height: 54px; border-radius: 50%;
        object-fit: cover;
        background: var(--bg-soft);
        border: 2px solid var(--line);
    }
    .cl-card-name { font-weight: 700; color: var(--ink); font-size: 15px; }
    .cl-card-headline { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .cl-card-meta { display: flex; gap: 12px; font-size: 13px; color: var(--text); flex-wrap: wrap; }
    .cl-card-rating { color: #f59e0b; font-weight: 700; }
    .cl-card-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: auto; padding-top: 6px; }
    .cl-card-tag {
        font-size: 11px; font-weight: 600;
        padding: 3px 9px; border-radius: 999px;
        background: rgba(37,99,235,0.08);
        color: var(--blue-dark);
        border: 1px solid rgba(37,99,235,0.18);
        text-transform: capitalize;
    }

    /* ─── Sibling pills ───────────────────────────────────── */
    .cl-siblings { display: flex; flex-wrap: wrap; gap: 10px; }
    .cl-sibling {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--bg);
        border: 1px solid var(--line);
        border-radius: 999px;
        padding: 9px 18px;
        font-size: 14px; font-weight: 600;
        color: var(--text);
        text-decoration: none;
        box-shadow: var(--shadow-sm);
        transition: all 0.15s;
    }
    .cl-sibling:hover {
        border-color: rgba(37,99,235,0.40);
        color: var(--blue-dark);
        background: var(--bg-soft);
    }

    /* ─── Empty state ─────────────────────────────────────── */
    .cl-empty {
        background: var(--bg-soft);
        border: 1px dashed var(--line);
        border-radius: 16px;
        padding: 56px 24px;
        text-align: center;
        color: var(--muted);
    }
    .cl-empty h3 {
        color: var(--ink);
        margin: 0 0 8px;
        font-size: 1.2rem; font-weight: 700;
    }
    .cl-empty p { margin: 0 0 18px; font-size: 0.95rem; }
    .cl-empty a {
        display: inline-block;
        background: linear-gradient(135deg, var(--blue), var(--blue-dark));
        color: #fff !important;
        padding: 11px 24px;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
    }

    /* ─── Breadcrumb ──────────────────────────────────────── */
    .cl-breadcrumb {
        max-width: 1180px; margin: 0 auto 14px; padding: 0;
        font-size: 13px; color: var(--muted);
        position: relative; z-index: 2;
    }
    .cl-breadcrumb a {
        color: var(--muted);
        text-decoration: none;
    }
    .cl-breadcrumb a:hover { color: var(--blue-dark); }
    .cl-breadcrumb .current { color: var(--ink); font-weight: 600; }

    @media (max-width: 640px) {
        .cl-hero { padding: 40px 18px 48px; }
        .cl-hero h1 { font-size: 2.1rem; }
        .cl-section { padding: 40px 18px; }
    }
</style>
@endpush

@section('content')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {"@@type":"ListItem","position":1,"name":"Home","item":"{{ route('landing') }}"},
        {"@@type":"ListItem","position":2,"name":"Categories","item":"{{ route('events-categories') }}"},
        {"@@type":"ListItem","position":3,"name":"{{ $category->name }}","item":"{{ url()->current() }}"}
    ]
}
</script>

<section class="cl-hero">
    <div class="cl-hero-inner">
        <nav class="cl-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a>
            <span> › </span>
            <a href="{{ route('events-categories') }}">Categories</a>
            <span> › </span>
            <span class="current">{{ $category->name }}</span>
        </nav>

        <span class="cl-eyebrow"><span class="dot"></span>{{ $category->parent->name ?? 'Featured Category' }}</span>
        <h1>Hire <span class="grad">{{ $category->name }}</span></h1>
        <p class="lede">
            {{ $category->long_description ?: $category->short_description ?: ('Browse ' . strtolower($category->name) . ' for your next event. Compare profiles, reviews, and quotes — with secure, protected payments on every booking.') }}
        </p>
        <div class="cl-stats">
            <div><b>{{ number_format($totalCount) }}+</b>Pros available</div>
            <div><b>4.8★</b>Avg rating</div>
            <div><b>24h</b>Avg quote time</div>
        </div>
        <a href="{{ $browseUrl }}" class="cl-cta">
            Browse all {{ $category->name }}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</section>

<section class="cl-section">
    <div class="cl-section-head">
        <h2>Featured {{ $category->name }}</h2>
        <p>Top-rated pros in this category, prioritised by verification and review score.</p>
    </div>

    @if($featured->isNotEmpty())
        <div class="cl-grid">
            @foreach($featured as $pro)
                @php
                    $profile = $pro->profile;
                    $rating  = $pro->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
                @endphp
                <a href="{{ route('public.professional.show', $pro) }}" class="cl-card">
                    <div class="cl-card-head">
                        <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="cl-card-avatar" loading="lazy">
                        <div>
                            <div class="cl-card-name">{{ $pro->name }}</div>
                            @if($profile && $profile->headline)
                                <div class="cl-card-headline">{{ \Illuminate\Support\Str::limit($profile->headline, 50) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="cl-card-meta">
                        @if($rating)
                            <span class="cl-card-rating">★ {{ $rating }}</span>
                            <span>({{ $pro->reviews_count }})</span>
                        @endif
                        @if($profile && $profile->city)
                            <span>· {{ $profile->city }}</span>
                        @endif
                    </div>
                    @if(count($pro->activeBadges()))
                        <div class="cl-card-tags">
                            @foreach($pro->activeBadges() as $badge)
                                <span class="cl-card-tag">{{ str_replace('_', ' ', $badge) }}</span>
                            @endforeach
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div class="cl-empty">
            <h3>No pros listed in this category yet</h3>
            <p>Be the first — or browse our other categories to find the right vendor for your event.</p>
            <a href="{{ route('public.browse') }}">Browse all professionals</a>
        </div>
    @endif
</section>

@if($siblings->isNotEmpty())
<section class="cl-section" style="padding-top:0;">
    <div class="cl-section-head">
        <h2>Related categories</h2>
        <p>Other event services you might need.</p>
    </div>
    <div class="cl-siblings">
        @foreach($siblings as $sib)
            <a href="{{ route('public.category', $sib->slug) }}" class="cl-sibling">
                @if($sib->icon)<span>{{ $sib->icon }}</span>@endif
                {{ $sib->name }}
            </a>
        @endforeach
    </div>
</section>
@endif

@endsection
