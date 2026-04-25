@extends('layouts.public')

@section('title', 'Frequently Asked Questions | ' . config('app.name'))

@push('styles')
<style>
    /* ───────────────────────────────────────────────────────────
       PUBLIC FAQ PAGE
       Hero banner + searchable, categorised accordion list.
       Pulls live data from the admin-managed `faqs` table.
       ─────────────────────────────────────────────────────────── */

    /* ─── HERO BANNER ─── */
    .faq-hero {
        position: relative;
        padding: 180px 0 70px;
        overflow: hidden;
        text-align: center;
    }
    /* Photographic cover image behind the hero, dimmed + gradient
       overlaid so the eyebrow / heading / search field stay legible. */
    .faq-hero-bg {
        position: absolute; inset: 0; z-index: 0;
    }
    .faq-hero-bg img {
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0.28;
    }
    .faq-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(900px 420px at 18% 10%, rgba(59,130,246,0.22), transparent 55%),
            radial-gradient(800px 400px at 85% 0%, rgba(139,92,246,0.22), transparent 55%),
            radial-gradient(700px 300px at 50% 100%, rgba(249,115,22,0.10), transparent 60%),
            linear-gradient(180deg, rgba(11,15,26,0.55) 0%, rgba(11,15,26,0.92) 80%, var(--bg-dark) 100%);
    }
    .faq-hero .container { position: relative; z-index: 1; }
    .faq-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 16px; border-radius: 999px;
        background: rgba(139,92,246,0.14);
        border: 1px solid rgba(139,92,246,0.32);
        font-size: 11px; font-weight: 800; letter-spacing: 1.2px;
        text-transform: uppercase; color: #c4b5fd;
        margin-bottom: 22px;
    }
    .faq-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 0 8px rgba(139,92,246,0.6);
    }
    .faq-hero h1 {
        font-size: 3rem; font-weight: 900;
        letter-spacing: -0.02em; line-height: 1.1;
        margin-bottom: 16px;
    }
    .faq-hero h1 .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .faq-hero p.lede {
        max-width: 620px; margin: 0 auto 28px;
        color: var(--text-muted); font-size: 1.05rem;
        line-height: 1.65;
    }

    /* ─── SEARCH BOX ─── */
    .faq-search {
        max-width: 540px; margin: 0 auto;
        position: relative;
    }
    .faq-search input {
        width: 100%;
        padding: 16px 18px 16px 50px;
        border-radius: 14px;
        background: rgba(255,255,255,0.05);
        border: 1.5px solid rgba(255,255,255,0.10);
        color: #fff;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
        backdrop-filter: blur(8px);
    }
    .faq-search input::placeholder { color: var(--text-muted); }
    .faq-search input:focus {
        border-color: rgba(139,92,246,0.50);
        background: rgba(139,92,246,0.06);
    }
    .faq-search svg {
        position: absolute; left: 18px; top: 50%;
        transform: translateY(-50%);
        width: 20px; height: 20px;
        color: var(--text-muted);
        pointer-events: none;
    }

    /* ─── CATEGORY FILTER PILLS ─── */
    .faq-cats {
        display: flex; flex-wrap: wrap; justify-content: center;
        gap: 8px; margin-top: 22px;
    }
    .faq-cat-pill {
        padding: 7px 16px;
        border-radius: 999px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        color: var(--text-light);
        font-size: 12.5px; font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .faq-cat-pill:hover {
        background: rgba(139,92,246,0.10);
        border-color: rgba(139,92,246,0.40);
        color: #fff;
    }
    .faq-cat-pill.is-active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 6px 16px rgba(139,92,246,0.35);
    }

    /* ─── FAQ LIST ─── */
    .faq-section { padding: 30px 0 80px; }
    .faq-wrap { max-width: 820px; margin: 0 auto; }

    .faq-cat-block { margin-bottom: 36px; }
    .faq-cat-block:last-child { margin-bottom: 0; }
    .faq-cat-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 0.95rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px dashed rgba(255,255,255,0.10);
    }
    .faq-cat-title .count {
        font-size: 11px; color: var(--text-muted);
        background: rgba(255,255,255,0.05);
        padding: 2px 8px; border-radius: 999px;
    }

    .faq-item {
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        background: rgba(255,255,255,0.025);
        margin-bottom: 12px;
        overflow: hidden;
        transition: border-color 0.3s, background 0.3s;
    }
    .faq-item[open] {
        border-color: rgba(139,92,246,0.35);
        background: rgba(139,92,246,0.05);
    }
    .faq-item summary {
        padding: 18px 22px; cursor: pointer;
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; list-style: none;
        font-size: 15px; font-weight: 600; color: #fff;
    }
    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary::after {
        content: ''; width: 22px; height: 22px; flex-shrink: 0;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='%238b5cf6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
        transition: transform 0.25s;
    }
    .faq-item[open] summary::after { transform: rotate(180deg); }
    .faq-body {
        padding: 0 22px 18px;
        font-size: 14px; color: var(--text-muted); line-height: 1.7;
    }
    .faq-body p { margin: 0 0 10px; }
    .faq-body p:last-child { margin-bottom: 0; }

    /* "No results" helper shown when search filter hides everything */
    .faq-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 15px;
        background: rgba(255,255,255,0.025);
        border: 1px dashed rgba(255,255,255,0.10);
        border-radius: 14px;
        display: none;
    }
    .faq-empty.is-visible { display: block; }
    .faq-empty strong { color: #fff; display: block; margin-bottom: 6px; }

    /* ─── CONTACT CTA ─── */
    .faq-contact {
        max-width: 820px;
        margin: 30px auto 0;
        padding: 36px 32px;
        text-align: center;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(59,130,246,0.10), rgba(139,92,246,0.10));
        border: 1px solid rgba(139,92,246,0.25);
    }
    .faq-contact h3 {
        font-size: 1.5rem; font-weight: 800;
        margin-bottom: 8px;
    }
    .faq-contact p {
        color: var(--text-muted); font-size: 14.5px;
        max-width: 480px; margin: 0 auto 18px;
        line-height: 1.6;
    }
    .faq-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 24px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff; text-decoration: none;
        font-size: 14px; font-weight: 700;
        box-shadow: 0 10px 26px rgba(139,92,246,0.35);
        transition: transform 0.2s, opacity 0.2s;
    }
    .faq-btn:hover { transform: translateY(-1px); opacity: 0.95; }

    @media (max-width: 600px) {
        .faq-hero { padding: 140px 16px 50px; }
        .faq-hero h1 { font-size: 2rem; }
        .faq-hero p.lede { font-size: 0.95rem; }
        .faq-item summary { padding: 14px 16px; font-size: 14px; }
        .faq-body { padding: 0 16px 14px; }
        .faq-contact { padding: 28px 22px; }
    }
</style>
@endpush

@push('meta')
    <meta name="description" content="Frequently asked questions about GigResource — registration, bookings, payouts, memberships, referrals, and more.">
@endpush

@section('content')

<!-- ── HERO BANNER ───────────────────────────────────────────── -->
<section class="faq-hero">
    {{-- Cover banner: a wedding decor / florals scene that ties the
         FAQ page back to the platform's event-services context. --}}
    <div class="faq-hero-bg">
        <img src="https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?w=1800&q=80&auto=format&fit=crop" alt="" loading="eager">
    </div>
    <div class="container">
        <div class="faq-eyebrow">
            <span class="dot"></span> Help &amp; Support
        </div>
        <h1>Frequently asked <span class="grad">questions</span></h1>
        <p class="lede">
            Everything you need to know about GigResource — for clients booking professionals, GigProfessionals growing their business, and Influencers earning through referrals.
        </p>

        <div class="faq-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="faqSearchInput" placeholder="Search questions…" autocomplete="off">
        </div>

        @if($grouped->isNotEmpty())
            <div class="faq-cats" role="tablist" aria-label="Filter by category">
                <button type="button" class="faq-cat-pill is-active" data-cat="__all">All</button>
                @foreach($grouped->keys() as $catName)
                    <button type="button" class="faq-cat-pill" data-cat="{{ $catName }}">{{ $catName }}</button>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- ── FAQ LIST ──────────────────────────────────────────────── -->
<section class="faq-section">
    <div class="container">
        <div class="faq-wrap" id="faqWrap">
            @forelse($grouped as $catName => $items)
                <div class="faq-cat-block" data-cat="{{ $catName }}">
                    <div class="faq-cat-title">
                        <span>{{ $catName }}</span>
                        <span class="count">{{ $items->count() }}</span>
                    </div>
                    @foreach($items as $faq)
                        <details class="faq-item" data-q="{{ \Illuminate\Support\Str::lower($faq->question.' '.$faq->answer) }}">
                            <summary>{{ $faq->question }}</summary>
                            <div class="faq-body">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            @empty
                {{-- Fallback content shown when admin hasn't added any FAQs yet --}}
                <div class="faq-cat-block" data-cat="General">
                    <div class="faq-cat-title">
                        <span>General</span>
                        <span class="count">5</span>
                    </div>
                    <details class="faq-item" data-q="how do i sign up gigprofessional">
                        <summary>How do I sign up as a GigProfessional?</summary>
                        <div class="faq-body">Visit the registration page and choose &ldquo;GigProfessional&rdquo;. Complete the form with your business type, contact details, and service offerings — then build out your profile with rates, availability, and portfolio work.</div>
                    </details>
                    <details class="faq-item" data-q="how do i join influencer program">
                        <summary>How do I join the Influencer Program?</summary>
                        <div class="faq-body">Register on the platform with your email and add a payment method for receiving commissions. Once registered, you&rsquo;ll get a personalized Influencer Dashboard with your unique referral link and tracking metrics.</div>
                    </details>
                    <details class="faq-item" data-q="search filter clients">
                        <summary>How do clients find professionals?</summary>
                        <div class="faq-body">Clients use filters to search by location, service category (DJs, caterers, photographers, etc.), and availability. Each professional has a dedicated profile showcasing services, pricing, customer reviews, and contact information.</div>
                    </details>
                    <details class="faq-item" data-q="payout threshold commissions influencer">
                        <summary>What&rsquo;s the payout threshold for Influencers?</summary>
                        <div class="faq-body">Influencers must reach $150 in commissions to receive payments. Payouts are processed within 14 business days once the threshold is met.</div>
                    </details>
                    <details class="faq-item" data-q="reviews feedback after booking">
                        <summary>Can I leave a review after a booking?</summary>
                        <div class="faq-body">Yes — all users are encouraged to leave reviews and feedback after every booking. Honest reviews improve overall quality and help future clients choose the right vendor.</div>
                    </details>
                </div>
            @endforelse

            <div class="faq-empty" id="faqEmpty">
                <strong>No matching questions found</strong>
                Try a different search term, or browse all categories.
            </div>
        </div>

        <div class="faq-contact">
            <h3>Still have questions?</h3>
            <p>Can&rsquo;t find what you&rsquo;re looking for? Our support team is happy to help — usually within a few hours.</p>
            <a href="{{ route('about-us') }}" class="faq-btn">
                Contact support
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // FAQ search + category filter — purely client-side because the
    // dataset is small and admin already controls what's published.
    (function() {
        const input  = document.getElementById('faqSearchInput');
        const wrap   = document.getElementById('faqWrap');
        const empty  = document.getElementById('faqEmpty');
        const pills  = document.querySelectorAll('.faq-cat-pill');
        if (!input || !wrap) return;

        let activeCat = '__all';

        function applyFilters() {
            const q = input.value.trim().toLowerCase();
            let visibleCount = 0;

            wrap.querySelectorAll('.faq-cat-block').forEach(block => {
                const blockCat = block.dataset.cat;
                let blockVisible = 0;

                // Hide whole block if a non-matching category is selected
                const catMatches = activeCat === '__all' || activeCat === blockCat;

                block.querySelectorAll('.faq-item').forEach(item => {
                    const haystack = item.dataset.q || '';
                    const textMatches = !q || haystack.includes(q);
                    const show = catMatches && textMatches;
                    item.style.display = show ? '' : 'none';
                    if (show) { blockVisible++; }
                });

                block.style.display = blockVisible ? '' : 'none';
                visibleCount += blockVisible;
            });

            empty.classList.toggle('is-visible', visibleCount === 0);
        }

        input.addEventListener('input', applyFilters);
        pills.forEach(p => {
            p.addEventListener('click', () => {
                pills.forEach(x => x.classList.toggle('is-active', x === p));
                activeCat = p.dataset.cat;
                applyFilters();
            });
        });
    })();
</script>

{{-- ─── FAQPage JSON-LD for SEO rich snippets ─── --}}
@php
    $_ctx = '@' . 'context';
    $_typ = '@' . 'type';
    $faqSchema = [
        $_ctx => 'https://schema.org',
        $_typ => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($f) => [
            $_typ            => 'Question',
            'name'           => $f->question,
            'acceptedAnswer' => [
                $_typ => 'Answer',
                'text' => $f->answer,
            ],
        ])->values()->all(),
    ];
@endphp
@if($faqs->isNotEmpty())
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endpush
