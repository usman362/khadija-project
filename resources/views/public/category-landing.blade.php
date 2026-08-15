@extends('layouts.landing')

@php
    use Illuminate\Support\Str;

    $seoTitle       = 'Hire ' . $category->name . ' — Verified Pros on GigResource';
    $seoDescription = $category->short_description
        ?: ('Browse top-rated ' . strtolower($category->name) . ' on GigResource. Compare quotes, read reviews, and book the right pro with secure, protected payments.');
    $seoImage       = $category->cover_image ? asset('storage/' . $category->cover_image) : null;
    // Filter Browse by the real relation, not by a keyword guess — ?q= searched
    // the pro's free text for the whole category name and always came back empty.
    $browseUrl      = route('public.browse', ['category' => $category->slug]);
@endphp

@push('styles')
<style>
    /* ─── Hero ─────────────────────────────────────────────── */
    .cl-hero {
        position: relative;
        padding: 56px 24px 64px;
        overflow: hidden;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border-bottom: 1px solid #fed7aa;
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
        background: #ffedd5;
        border: 1px solid #fdba74;
        border-radius: 999px;
        font-size: 11.5px; font-weight: 800;
        letter-spacing: .8px; text-transform: uppercase;
        color: #9a3412;
    }
    .cl-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: #ea580c;
    }
    .cl-hero h1 {
        font-size: 3rem; font-weight: 700;
        margin: 18px 0 14px;
        color: var(--ink);
        line-height: 1.05; letter-spacing: -0.02em;
    }
    /* Was a blue-to-orange gradient across the words, so a long category name
       came out blue at one end, purple in the middle and orange at the other —
       three brands in one line. One brand colour. */
    .cl-hero h1 .grad { color: #ea580c; }
    .cl-hero p.lede {
        font-size: 1.05rem;
        color: var(--muted);
        max-width: 680px;
        line-height: 1.65;
    }
    /* Two bare numbers sat under a rule with nothing holding them, which is
       what made the page read as unfinished. */
    .cl-stats {
        display: flex; flex-wrap: wrap; gap: 12px;
        margin-top: 26px;
        font-size: 13px; color: var(--muted);
    }
    .cl-stats > div {
        background: #fff; border: 1.5px solid #fed7aa;
        border-radius: 13px; padding: 12px 20px; min-width: 132px;
    }
    .cl-stats b {
        display: block;
        font-size: 1.5rem; font-family: var(--ff); font-weight: 800;
        color: #9a3412;
        margin-bottom: 1px;
    }
    .cl-cta-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 28px; }
    .cl-cta-alt {
        display: inline-flex; align-items: center;
        padding: 13px 22px;
        background: #fff; border: 1.5px solid #fed7aa;
        color: #9a3412; font-weight: 800;
        border-radius: 12px; text-decoration: none;
    }
    .cl-cta-alt:hover { border-color: #fdba74; background: #fff7ed; color: #9a3412; }
    .cl-cta {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 13px 26px;
        background: #ea580c;
        color: #fff; font-weight: 800;
        border-radius: 12px;
        text-decoration: none;
        box-shadow: 0 10px 26px rgba(234,88,12,0.26);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .cl-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px rgba(234,88,12,0.34);
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
        border-color: #fdba74;
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
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
        text-transform: capitalize;
    }

    /* ─── Sibling pills ───────────────────────────────────── */
    .cl-siblings { display: flex; flex-wrap: wrap; gap: 10px; }

    /* What's included */
    .cl-svc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(232px, 1fr)); gap: 12px; }
    .cl-svc { display: block; padding: 15px 16px; border: 1.5px solid var(--line); border-radius: 13px;
        background: var(--bg); text-decoration: none; transition: border-color .15s, transform .15s; }
    .cl-svc:hover { border-color: #fdba74; transform: translateY(-2px); }
    .cl-svc-name { display: block; font-weight: 800; font-size: 14px; color: var(--ink); margin-bottom: 3px; }
    .cl-svc-desc { display: block; font-size: 12.5px; color: var(--muted); line-height: 1.45; }

    /* What clients said */
    .cl-rev-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
    .cl-rev { margin: 0; padding: 18px; border: 1.5px solid var(--line); border-radius: 15px; background: var(--bg); }
    .cl-rev-stars { color: #f59e0b; font-size: 14px; letter-spacing: 1px; margin-bottom: 9px; }
    .cl-rev-stars span { color: var(--line); }
    .cl-rev blockquote { margin: 0 0 11px; font-size: 14px; line-height: 1.6; color: var(--ink); }
    .cl-rev figcaption { font-size: 12px; color: var(--muted); }
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
        border-color: #fdba74;
        color: #9a3412;
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
        background: #ea580c;
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
    .cl-breadcrumb a:hover { color: #ea580c; }
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
        {{-- "4.8 Avg rating" and "24h Avg quote time" used to sit here as hard-coded
             literals — invented numbers presented as platform data, and exactly the
             unverified-metric claim the copy rules ban. The pro count is real, so it
             stays, but only once there is something to count: "0+ Pros available"
             reads as a broken page. --}}
        @if($totalCount > 0)
            <div class="cl-stats">
                <div><b>{{ number_format($totalCount) }}</b>{{ Str::plural('Pro', $totalCount) }} available</div>
                @if($subcategoryCount > 0)
                    <div><b>{{ number_format($subcategoryCount) }}</b>{{ Str::plural('Service', $subcategoryCount) }} covered</div>
                @endif
            </div>
        @elseif($subcategoryCount > 0)
            <div class="cl-stats">
                <div><b>{{ number_format($subcategoryCount) }}</b>{{ Str::plural('Service', $subcategoryCount) }} covered</div>
            </div>
        @endif
        {{-- Two ways to act, because the page had only one. Browsing suits a
             visitor who wants to look; posting suits one who already knows what
             they need and would rather be come to. --}}
        <div class="cl-cta-row">
            <a href="{{ $browseUrl }}" class="cl-cta">
                Browse all {{ $category->name }}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <a href="{{ route('client.bsr.step', 'service') }}" class="cl-cta-alt">
                Post a request instead
            </a>
        </div>
    </div>
</section>

{{-- What this category holds. The hero says "N Services covered" and this
     is that N — it used to be nowhere on the page except as unlabelled pills
     at the bottom under a heading calling them related categories. --}}
@if($services->isNotEmpty())
<section class="cl-section" style="padding-bottom:0;">
    <div class="cl-section-head">
        <h2>What's included</h2>
        <p>The {{ $services->count() }} {{ Str::plural('service', $services->count()) }} you can book under {{ $category->name }}.</p>
    </div>
    <div class="cl-svc-grid">
        @foreach($services as $svc)
            <a class="cl-svc" href="{{ route('public.browse', ['category' => $svc->slug]) }}">
                <span class="cl-svc-name">{{ $svc->name }}</span>
                @if($svc->short_description)
                    <span class="cl-svc-desc">{{ Str::limit(strip_tags((string) $svc->short_description), 62) }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif

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

{{-- Real words from real clients about people in this category. Nothing at
     all when there are none — a placeholder testimonial on a marketplace is
     the one thing a visitor will never forgive. --}}
@if($reviews->isNotEmpty())
<section class="cl-section" style="padding-top:0;">
    <div class="cl-section-head">
        <h2>What clients said</h2>
        <p>Reviews left for professionals in this category.</p>
    </div>
    <div class="cl-rev-grid">
        @foreach($reviews as $rev)
            <figure class="cl-rev">
                <div class="cl-rev-stars">{{ str_repeat('★', (int) $rev->rating) }}<span>{{ str_repeat('★', 5 - (int) $rev->rating) }}</span></div>
                <blockquote>{{ Str::limit(strip_tags((string) $rev->comment), 150) }}</blockquote>
                <figcaption>{{ $rev->reviewer?->name ?? 'A client' }} · on {{ $rev->reviewee?->name }}</figcaption>
            </figure>
        @endforeach
    </div>
</section>
@endif

{{-- Answers the question a visitor actually arrives with: is this for
     something like my event? Straight from the Category Masterlist, which
     marks this category Essential for these occasions. --}}
@if($forEvents->isNotEmpty())
<section class="cl-section" style="padding-top:0;">
    <div class="cl-section-head">
        <h2>Essential for these events</h2>
        <p>Occasions where {{ $category->name }} is usually one of the first things booked.</p>
    </div>
    <div class="cl-siblings">
        @foreach($forEvents as $ev)
            <a href="{{ route('public.category', $ev->slug) }}" class="cl-sibling">{{ $ev->name }}</a>
        @endforeach
    </div>
</section>
@endif

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
