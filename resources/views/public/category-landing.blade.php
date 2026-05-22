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
    .cat-hero {
        position: relative;
        min-height: 360px;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, #0b0f1a 0%, #1f2547 100%);
        @if($category->cover_image)
        background-image:
            linear-gradient(135deg, rgba(11,15,26,0.55) 0%, rgba(31,37,71,0.35) 100%),
            url('{{ asset('storage/' . $category->cover_image) }}');
        background-size: cover;
        background-position: center;
        @endif
        color: #fff;
        padding: 60px 24px;
    }
    .cat-hero-inner { max-width: 1180px; margin: 0 auto; width: 100%; }
    .cat-hero-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85);
        text-transform: uppercase; letter-spacing: 1px;
        background: rgba(255,255,255,0.12); padding: 6px 14px; border-radius: 999px;
    }
    .cat-hero-title { font-size: 44px; font-weight: 800; margin: 14px 0 12px; line-height: 1.1; }
    .cat-hero-desc { font-size: 17px; opacity: 0.92; max-width: 680px; line-height: 1.55; }
    .cat-hero-meta { display: flex; flex-wrap: wrap; gap: 22px; margin-top: 22px; font-size: 14px; }
    .cat-hero-meta b { font-size: 22px; font-weight: 800; display: block; }
    .cat-hero-cta {
        display: inline-flex; align-items: center; gap: 8px; margin-top: 26px;
        background: #fff; color: #0b0f1a; font-weight: 700;
        padding: 13px 26px; border-radius: 12px;
        text-decoration: none; transition: transform 0.15s, box-shadow 0.15s;
    }
    .cat-hero-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.25); }

    .cat-section { max-width: 1180px; margin: 0 auto; padding: 48px 24px; }
    .cat-section h2 { font-size: 26px; font-weight: 800; margin: 0 0 6px; color: #0b0f1a; }
    .cat-section p.lead { color: #5a607a; margin: 0 0 28px; font-size: 15px; }

    .cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .cat-card {
        background: #fff; border: 1px solid #e7e9ee; border-radius: 14px;
        padding: 18px; transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
        display: flex; flex-direction: column; gap: 10px;
        text-decoration: none; color: inherit;
    }
    .cat-card:hover {
        border-color: #6366f1; box-shadow: 0 10px 24px rgba(99,102,241,0.12);
        transform: translateY(-2px);
    }
    .cat-card-head { display: flex; gap: 12px; align-items: center; }
    .cat-card-avatar {
        width: 52px; height: 52px; border-radius: 50%;
        object-fit: cover; background: #f0f1f5;
    }
    .cat-card-name { font-weight: 700; color: #0b0f1a; font-size: 15px; }
    .cat-card-headline { font-size: 13px; color: #5a607a; }
    .cat-card-meta { display: flex; gap: 10px; font-size: 13px; color: #5a607a; flex-wrap: wrap; }
    .cat-card-rating { color: #f59e0b; font-weight: 700; }
    .cat-card-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: auto; }
    .cat-card-tag {
        font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 999px;
        background: #eef2ff; color: #4338ca;
    }

    .cat-siblings { display: flex; flex-wrap: wrap; gap: 10px; }
    .cat-sibling {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid #e7e9ee; border-radius: 999px;
        padding: 8px 16px; font-size: 14px; font-weight: 600;
        color: #0b0f1a; text-decoration: none; transition: border-color 0.15s, background 0.15s;
    }
    .cat-sibling:hover { border-color: #6366f1; background: #eef2ff; color: #4338ca; }

    .cat-empty {
        background: #f7f8fb; border-radius: 14px; padding: 48px 24px;
        text-align: center; color: #5a607a;
    }
    .cat-empty h3 { color: #0b0f1a; margin: 0 0 8px; }
    .cat-empty a {
        display: inline-block; margin-top: 16px; background: #6366f1; color: #fff;
        padding: 11px 22px; border-radius: 10px; font-weight: 700; text-decoration: none;
    }

    @media (max-width: 640px) {
        .cat-hero-title { font-size: 32px; }
        .cat-hero { padding: 40px 18px; min-height: 280px; }
        .cat-section { padding: 32px 18px; }
    }
</style>
@endpush

@section('content')

{{-- ── Breadcrumb (also a JSON-LD signal for Google) ────────────── --}}
<nav aria-label="Breadcrumb" style="max-width:1180px;margin:14px auto 0;padding:0 24px;font-size:13px;color:#5a607a;">
    <a href="{{ route('landing') }}" style="color:#5a607a;text-decoration:none;">Home</a>
    <span> › </span>
    <a href="{{ route('events-categories') }}" style="color:#5a607a;text-decoration:none;">Categories</a>
    <span> › </span>
    <span style="color:#0b0f1a;font-weight:600;">{{ $category->name }}</span>
</nav>

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

<section class="cat-hero">
    <div class="cat-hero-inner">
        <span class="cat-hero-eyebrow">{{ $category->parent->name ?? 'Featured Category' }}</span>
        <h1 class="cat-hero-title">Hire {{ $category->name }}</h1>
        <p class="cat-hero-desc">
            {{ $category->long_description ?: $category->short_description ?: ('Browse vetted ' . strtolower($category->name) . ' for your next event. Every pro on GigResource is reference-checked, license-verified, and protected by our payment guarantee.') }}
        </p>
        <div class="cat-hero-meta">
            <div><b>{{ number_format($totalCount) }}+</b> Pros available</div>
            <div><b>4.8★</b> Avg rating</div>
            <div><b>24h</b> Avg quote time</div>
        </div>
        <a href="{{ $browseUrl }}" class="cat-hero-cta">
            Browse all {{ $category->name }}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</section>

<section class="cat-section">
    <h2>Featured {{ $category->name }}</h2>
    <p class="lead">Top-rated pros in this category, prioritised by verification and review score.</p>

    @if($featured->isNotEmpty())
        <div class="cat-grid">
            @foreach($featured as $pro)
                @php
                    $profile = $pro->profile;
                    $rating = $pro->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
                @endphp
                <a href="{{ route('public.professional.show', $pro) }}" class="cat-card">
                    <div class="cat-card-head">
                        <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="cat-card-avatar" loading="lazy">
                        <div>
                            <div class="cat-card-name">{{ $pro->name }}</div>
                            @if($profile && $profile->headline)
                                <div class="cat-card-headline">{{ \Illuminate\Support\Str::limit($profile->headline, 50) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="cat-card-meta">
                        @if($rating)
                            <span class="cat-card-rating">★ {{ $rating }}</span>
                            <span>({{ $pro->reviews_count }})</span>
                        @endif
                        @if($profile && $profile->city)
                            <span>· {{ $profile->city }}</span>
                        @endif
                    </div>
                    <div class="cat-card-tags">
                        @foreach($pro->activeBadges() as $badge)
                            <span class="cat-card-tag">{{ str_replace('_', ' ', ucfirst($badge)) }}</span>
                        @endforeach
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="cat-empty">
            <h3>No pros listed in this category yet</h3>
            <p>Be the first — or browse our other categories to find the right vendor for your event.</p>
            <a href="{{ route('public.browse') }}">Browse all professionals</a>
        </div>
    @endif
</section>

@if($siblings->isNotEmpty())
<section class="cat-section" style="background:#f7f8fb;border-radius:24px;margin-bottom:48px;">
    <h2>Related categories</h2>
    <p class="lead">Other event services you might need.</p>
    <div class="cat-siblings">
        @foreach($siblings as $sib)
            <a href="{{ route('public.category', $sib->slug) }}" class="cat-sibling">
                @if($sib->icon)<span>{{ $sib->icon }}</span>@endif
                {{ $sib->name }}
            </a>
        @endforeach
    </div>
</section>
@endif

@endsection
