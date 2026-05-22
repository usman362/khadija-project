@extends('layouts.public')

@php
    $seoTitle       = 'Hire ' . $category->name . ' — Verified Pros on GigResource';
    $seoDescription = $category->short_description
        ?: ('Browse top-rated ' . strtolower($category->name) . ' on GigResource. Compare quotes, read reviews, and book the right pro with our marketplace guarantee.');
    $seoImage       = $category->cover_image ? asset('storage/' . $category->cover_image) : null;
    $browseUrl      = route('public.browse', ['q' => $category->name]);
@endphp

@push('styles')
<style>
    /* ─── Hero ─────────────────────────────────────────────── */
    .cl-hero {
        position: relative;
        padding: 140px 24px 80px;
        overflow: hidden;
        background: linear-gradient(135deg, var(--bg-section) 0%, var(--bg-dark) 100%);
    }
    @if($category->cover_image)
    .cl-hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image: url('{{ asset('storage/' . $category->cover_image) }}');
        background-size: cover;
        background-position: center;
        opacity: 0.35;
        z-index: 0;
    }
    .cl-hero::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(11,15,26,0.55) 0%, rgba(11,15,26,0.80) 100%);
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
        background: rgba(139, 92, 246, 0.15);
        border: 1px solid rgba(139, 92, 246, 0.30);
        border-radius: 999px;
        font-size: 12px; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase;
        color: #c4b5fd;
    }
    .cl-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
    }
    .cl-hero h1 {
        font-size: 3rem; font-weight: 900;
        margin: 18px 0 14px;
        color: var(--text-white);
        line-height: 1.05; letter-spacing: -0.02em;
    }
    .cl-hero h1 .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .cl-hero p.lede {
        font-size: 1.05rem;
        color: var(--text-light);
        max-width: 680px;
        line-height: 1.65;
    }
    .cl-stats {
        display: flex; flex-wrap: wrap; gap: 28px;
        margin-top: 26px; padding-top: 22px;
        border-top: 1px solid rgba(255,255,255,0.08);
        font-size: 14px; color: var(--text-muted);
    }
    .cl-stats b {
        display: block;
        font-size: 1.6rem; font-weight: 900;
        color: var(--text-white);
        margin-bottom: 2px;
    }
    .cl-cta {
        display: inline-flex; align-items: center; gap: 8px;
        margin-top: 28px;
        padding: 13px 26px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff; font-weight: 700;
        border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 10px 30px rgba(99,102,241,0.30);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .cl-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 36px rgba(99,102,241,0.40);
        color: #fff;
    }

    /* ─── Sections ─────────────────────────────────────────── */
    .cl-section { max-width: 1180px; margin: 0 auto; padding: 56px 24px; }
    .cl-section-head { margin-bottom: 28px; }
    .cl-section-head h2 {
        font-size: 1.75rem; font-weight: 800;
        color: var(--text-white);
        margin: 0 0 6px;
    }
    .cl-section-head p { color: var(--text-muted); margin: 0; font-size: 0.98rem; }

    /* ─── Featured cards ──────────────────────────────────── */
    .cl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 18px;
    }
    .cl-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex; flex-direction: column; gap: 12px;
        text-decoration: none; color: inherit;
        transition: border-color 0.15s, transform 0.15s, box-shadow 0.15s;
    }
    .cl-card:hover {
        border-color: rgba(139,92,246,0.50);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.30);
        background: var(--bg-card-hover);
    }
    .cl-card-head { display: flex; gap: 12px; align-items: center; }
    .cl-card-avatar {
        width: 54px; height: 54px; border-radius: 50%;
        object-fit: cover;
        background: var(--bg-section);
        border: 2px solid var(--border-color);
    }
    .cl-card-name { font-weight: 700; color: var(--text-white); font-size: 15px; }
    .cl-card-headline { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
    .cl-card-meta { display: flex; gap: 12px; font-size: 13px; color: var(--text-light); flex-wrap: wrap; }
    .cl-card-rating { color: #fbbf24; font-weight: 700; }
    .cl-card-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: auto; padding-top: 6px; }
    .cl-card-tag {
        font-size: 11px; font-weight: 600;
        padding: 3px 9px; border-radius: 999px;
        background: rgba(139,92,246,0.15);
        color: #c4b5fd;
        border: 1px solid rgba(139,92,246,0.25);
        text-transform: capitalize;
    }

    /* ─── Sibling pills ───────────────────────────────────── */
    .cl-siblings { display: flex; flex-wrap: wrap; gap: 10px; }
    .cl-sibling {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 999px;
        padding: 9px 18px;
        font-size: 14px; font-weight: 600;
        color: var(--text-light);
        text-decoration: none;
        transition: all 0.15s;
    }
    .cl-sibling:hover {
        border-color: rgba(139,92,246,0.50);
        color: #fff;
        background: var(--bg-card-hover);
    }

    /* ─── Empty state ─────────────────────────────────────── */
    .cl-empty {
        background: var(--bg-card);
        border: 1px dashed var(--border-color);
        border-radius: 16px;
        padding: 56px 24px;
        text-align: center;
        color: var(--text-muted);
    }
    .cl-empty h3 {
        color: var(--text-white);
        margin: 0 0 8px;
        font-size: 1.2rem; font-weight: 700;
    }
    .cl-empty p { margin: 0 0 18px; font-size: 0.95rem; }
    .cl-empty a {
        display: inline-block;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff !important;
        padding: 11px 24px;
        border-radius: 10px;
        font-weight: 700;
        text-decoration: none;
    }

    /* ─── Breadcrumb ──────────────────────────────────────── */
    .cl-breadcrumb {
        max-width: 1180px; margin: 14px auto 0; padding: 0 24px;
        font-size: 13px; color: var(--text-muted);
        position: relative; z-index: 2;
    }
    .cl-breadcrumb a {
        color: var(--text-muted);
        text-decoration: none;
    }
    .cl-breadcrumb a:hover { color: #c4b5fd; }
    .cl-breadcrumb .current { color: var(--text-white); font-weight: 600; }

    @media (max-width: 640px) {
        .cl-hero { padding: 120px 18px 56px; }
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
    <nav class="cl-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}">Home</a>
        <span> › </span>
        <a href="{{ route('events-categories') }}">Categories</a>
        <span> › </span>
        <span class="current">{{ $category->name }}</span>
    </nav>

    <div class="cl-hero-inner">
        <span class="cl-eyebrow"><span class="dot"></span>{{ $category->parent->name ?? 'Featured Category' }}</span>
        <h1>Hire <span class="grad">{{ $category->name }}</span></h1>
        <p class="lede">
            {{ $category->long_description ?: $category->short_description ?: ('Browse vetted ' . strtolower($category->name) . ' for your next event. Every pro on GigResource is reference-checked, license-verified, and protected by our payment guarantee.') }}
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
<section class="cl-section">
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
