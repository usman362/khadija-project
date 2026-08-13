@extends('layouts.landing')

@php
    $seoTitle       = $client->name . ' — Client Portfolio';
    $seoDescription = 'Event history, reviews from professionals, and working record for '
                      . $client->name . ' on GigResource.';
    $seoType        = 'profile';

    $openRequests = \App\Models\Event::where('client_id', $client->id)
        ->where('is_published', true)->whereNull('supplier_id')
        ->whereIn('status', ['pending', 'published'])->count();
@endphp

@push('styles')
<style>
/* ============================================================
   Client Portfolio — Rule R53
   ------------------------------------------------------------
   The client's counterpart to /pro/{id}. Deliberately the same
   shape as that page: hero, two columns, white cards on the
   light landing layout. A professional reading both should not
   have to learn a second layout.

   Two columns, not three — Peter, 2026-08-07: where three
   columns would squeeze the content, use two and let the page
   run longer.
   ============================================================ */

.cp-page { max-width: 1180px; margin: 0 auto; padding: 28px 24px 90px; color: var(--ink, #0f1b35); }
@media (max-width: 720px) { .cp-page { padding: 18px 16px 80px; } }

.cp-breadcrumb { display:flex; align-items:center; gap:8px; margin-bottom:16px; font-size:13px; color: var(--muted, #64748b); }
.cp-breadcrumb a { color: var(--muted, #64748b); text-decoration:none; }
.cp-breadcrumb a:hover { color: var(--brand, #f97316); }

/* ── Hero ─────────────────────────────────────────────────── */
.cp-hero { background:#fff; border:1px solid var(--line, #e2e8f0); border-radius:16px; overflow:hidden; margin-bottom:20px; }
.cp-cover { height:132px; background:linear-gradient(120deg, #f97316, #fb923c 60%, #fdba74); }
.cp-hero-body { padding:0 26px 24px; }
@media (max-width: 720px) { .cp-hero-body { padding:0 16px 20px; } }

.cp-avatar { width:96px; height:96px; border-radius:50%; border:4px solid #fff; margin-top:-48px; background:#0f172a; color:#fff;
             display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:800; object-fit:cover; }

.cp-name-row { display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px; margin-top:14px; }
.cp-name { font-size:26px; font-weight:800; display:flex; align-items:center; gap:8px; margin:0; }
.cp-verified { color:#2563eb; flex-shrink:0; }

.cp-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.cp-chip { font-size:12.5px; font-weight:600; padding:5px 11px; border-radius:999px; background:var(--soft, #f1f5f9); color:var(--muted, #64748b); }
.cp-chip.is-verified { background:rgba(22,163,74,.1); color:#15803d; }

.cp-rating { display:flex; align-items:center; gap:7px; margin-top:9px; font-size:14px; }
.cp-stars { color:#f59e0b; letter-spacing:1px; }
.cp-rating small { color:var(--muted, #64748b); }

.cp-actions { display:flex; gap:10px; flex-wrap:wrap; }
.cp-btn { font-size:14px; font-weight:700; padding:10px 18px; border-radius:10px; text-decoration:none; display:inline-flex; align-items:center; gap:7px; border:1px solid var(--line, #e2e8f0); color:var(--ink, #0f1b35); background:#fff; }
.cp-btn.is-primary { background:var(--brand, #f97316); border-color:var(--brand, #f97316); color:#fff; }

/* ── Figures (sections 2 + 7, one component) ──────────────── */
.cp-figures { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px; }
.cp-figure { border:1px solid var(--line, #e2e8f0); border-radius:12px; padding:13px 15px; background:#fff; }
.cp-figure-v { font-size:20px; font-weight:800; }
.cp-figure-k { font-size:12px; color:var(--muted, #64748b); margin-top:2px; }
.cp-hero .cp-figures { margin-top:20px; padding-top:20px; border-top:1px solid var(--line, #e2e8f0); }
.cp-hero .cp-figure { border:0; padding:0; }

/* ── Two-column body ──────────────────────────────────────── */
.cp-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
@media (max-width: 980px) { .cp-grid { grid-template-columns:1fr; } }

.cp-card { background:#fff; border:1px solid var(--line, #e2e8f0); border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.cp-card h2 { font-size:16px; font-weight:800; margin:0 0 14px; }
.cp-card p { margin:0; line-height:1.65; color:var(--ink, #0f1b35); }

.cp-empty { font-size:13.5px; color:var(--muted, #64748b); line-height:1.6; }

/* ── Reviews ──────────────────────────────────────────────── */
.cp-review { padding:15px 0; border-top:1px solid var(--line, #e2e8f0); }
.cp-review:first-of-type { border-top:0; padding-top:0; }
.cp-review-top { display:flex; align-items:center; gap:10px; margin-bottom:7px; flex-wrap:wrap; }
.cp-review-who { font-weight:700; font-size:14px; }
.cp-review-when { font-size:12.5px; color:var(--muted, #64748b); margin-left:auto; }
.cp-tag { font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; background:rgba(22,163,74,.1); color:#15803d; }
.cp-review p { font-size:14px; color:#334155; }

/* ── Event types ──────────────────────────────────────────── */
.cp-types { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:11px; }
.cp-type { border:1px solid var(--line, #e2e8f0); border-radius:11px; padding:13px 15px; }
.cp-type b { display:block; font-size:14px; }
.cp-type span { font-size:12.5px; color:var(--muted, #64748b); }

/* ── Event history ────────────────────────────────────────── */
.cp-event { display:flex; gap:12px; justify-content:space-between; padding:12px 0; border-top:1px solid var(--line, #e2e8f0); font-size:14px; }
.cp-event:first-of-type { border-top:0; }
.cp-event-meta { color:var(--muted, #64748b); font-size:12.5px; white-space:nowrap; }

/* ── Sidebar ──────────────────────────────────────────────── */
.cp-side .cp-card { position:sticky; top:20px; }
.cp-kv { display:flex; justify-content:space-between; gap:12px; padding:8px 0; font-size:13.5px; border-top:1px solid var(--line, #e2e8f0); }
.cp-kv:first-of-type { border-top:0; }
.cp-kv span:first-child { color:var(--muted, #64748b); }
.cp-kv span:last-child { font-weight:700; text-align:right; }

.cp-disclaimer { margin-top:8px; font-size:12.5px; color:var(--muted, #64748b); line-height:1.6; text-align:center; }
</style>
@endpush

@section('content')
<div class="cp-page">

    {{-- Section 12 — breadcrumb.
         "Portfolio", not "Profile": the private Profile & Settings page still
         owns that word, and this page is the public one. The mockup's "Browse
         Professionals" crumb was a copy-paste from the professional template —
         a client's page does not sit under it. --}}
    <nav class="cp-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a> <span>›</span>
        <span>Client Portfolio</span>
    </nav>

    {{-- ── Section 1 — Header / identity ───────────────────── --}}
    <div class="cp-hero">
        <div class="cp-cover"></div>
        <div class="cp-hero-body">
            @if($client->avatar_url)
                <img src="{{ $client->avatar_url }}" alt="" class="cp-avatar">
            @else
                <div class="cp-avatar">{{ strtoupper(mb_substr($client->name, 0, 1)) }}</div>
            @endif

            <div class="cp-name-row">
                <div>
                    <h1 class="cp-name">
                        {{ $client->name }}
                        @if($client->email_verified_at)
                            <svg class="cp-verified" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" role="img" aria-label="Verified account">
                                <circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/>
                            </svg>
                        @endif
                    </h1>

                    @if($stats['rating'])
                        <div class="cp-rating">
                            <span class="cp-stars" aria-hidden="true">{{ str_repeat('★', (int) round($stats['rating'])) }}</span>
                            <b>{{ number_format($stats['rating'], 1) }}</b>
                            <small>({{ $stats['reviews_count'] }} {{ Str::plural('review', $stats['reviews_count']) }} from professionals)</small>
                        </div>
                    @endif

                    <div class="cp-chips">
                        @if($client->email_verified_at)
                            <span class="cp-chip is-verified">Verified Client</span>
                        @endif
                        @if($stats['member_since'])
                            <span class="cp-chip">Member since {{ $stats['member_since']->format('M Y') }}</span>
                        @endif
                        @if($profile->city || $profile->state)
                            <span class="cp-chip">{{ collect([$profile->city, $profile->state])->filter()->implode(', ') }}</span>
                        @endif
                        {{-- The loyalty-tier chip ("Level Gold Client") is not
                             drawn: nobody has defined what earns each level,
                             and it has to stay distinct from the two locked
                             ladders (Manual/Semi/Maximum, Starter/Pro/Elite).
                             Open question 3 on the spec. --}}
                    </div>
                </div>

                <div class="cp-actions">
                    <a class="cp-btn" href="{{ route('professional.chat.index') }}">Message Client</a>
                    {{-- Not a "Submit Proposal" button: a proposal goes to a
                         REQUEST, not to a person, and the spec says not to
                         invent a second proposal flow for this page. So it
                         points at the requests they actually have open, and
                         disappears when they have none. --}}
                    @if($openRequests > 0)
                        <a class="cp-btn is-primary" href="{{ route('professional.bidding-board.index', ['q' => $client->name]) }}">
                            View {{ $openRequests }} open {{ Str::plural('request', $openRequests) }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Section 2 — Quick stats --}}
            @include('public.client._figures', ['only' => [
                'response_rate', 'response_hours', 'completed_events', 'cancellation_rate', 'last_active',
            ]])
        </div>
    </div>

    <div class="cp-grid">
        <div class="cp-main">

            {{-- ── Section 3 — About ───────────────────────── --}}
            <div class="cp-card">
                <h2>About</h2>
                @if($profile->bio)
                    <p>{{ $profile->bio }}</p>
                @else
                    <p class="cp-empty">{{ $client->name }} hasn’t added an introduction yet.</p>
                @endif

                @if($profile->languages)
                    <div class="cp-chips" style="margin-top:14px;">
                        @foreach((array) $profile->languages as $language)
                            <span class="cp-chip">{{ $language }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ── Section 4 — Client Badges ───────────────── --}}
            @if($sections['badges'])
                <div class="cp-card">
                    <h2>Badges</h2>
                    <p class="cp-empty">Badge criteria are set in the badge rulebook.</p>
                </div>
            @endif

            {{-- ── Section 5 — Event History ───────────────── --}}
            @if($sections['event_history'])
                <div class="cp-card">
                    <h2>Event History</h2>
                    @forelse($eventHistory as $event)
                        <div class="cp-event">
                            <div>
                                <b>{{ $event['title'] }}</b>
                                @if($event['services'])
                                    <div class="cp-event-meta">{{ implode(' · ', $event['services']) }}</div>
                                @endif
                            </div>
                            <div class="cp-event-meta">
                                {{ collect([$event['when'], $event['where']])->filter()->implode(' · ') }}
                            </div>
                        </div>
                    @empty
                        <p class="cp-empty">No completed events yet.</p>
                    @endforelse
                </div>
            @endif

            {{-- ── Section 8 — Reviews from professionals ──── --}}
            <div class="cp-card">
                <h2>Reviews from Professionals</h2>
                @forelse($reviews as $review)
                    <div class="cp-review">
                        <div class="cp-review-top">
                            <span class="cp-review-who">{{ $review->reviewer?->name ?? 'A professional' }}</span>
                            <span class="cp-stars" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', (int) $review->rating) }}</span>
                            {{-- A fact, not a label: every review shown here
                                 is tied to a booking record, because the query
                                 requires one. --}}
                            <span class="cp-tag">Verified Booking</span>
                            <span class="cp-review-when">{{ $review->created_at->humanAgo() }}</span>
                        </div>
                        @if($review->comment)<p>{{ $review->comment }}</p>@endif
                    </div>
                @empty
                    <p class="cp-empty">
                        No reviews yet. Professionals can review a client once an event is completed.
                    </p>
                @endforelse
            </div>

            {{-- ── Section 9 — Favourite event types ───────── --}}
            @if($eventTypes->isNotEmpty())
                <div class="cp-card">
                    <h2>Event Types</h2>
                    <div class="cp-types">
                        @foreach($eventTypes->take(8) as $name => $count)
                            <div class="cp-type">
                                <b>{{ $name }}</b>
                                <span>{{ $count }} {{ Str::plural('event', $count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Section 10 — Favourite professionals ────── --}}
            @if($sections['favourite_professionals'] && $favourites->isNotEmpty())
                <div class="cp-card">
                    <h2>Professionals They Work With</h2>
                    <div class="cp-types">
                        @foreach($favourites as $pro)
                            <a class="cp-type" href="{{ route('public.professional.show', $pro) }}" style="text-decoration:none; color:inherit;">
                                <b>{{ $pro->name }}</b>
                                <span>{{ $pro->profile?->headline ?? 'Event professional' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Section 11 — How it works with … ────────── --}}
            @if($sections['working_style'])
                <div class="cp-card">
                    <h2>How It Works With {{ Str::before($client->name, ' ') }}</h2>
                    <p class="cp-empty">Working-style summary.</p>
                </div>
            @endif
        </div>

        {{-- ── Sidebar: sections 6 + 7 ─────────────────────── --}}
        <aside class="cp-side">
            <div class="cp-card">
                <h2>Trust &amp; Reputation</h2>

                @if($stats['rating'])
                    <div style="display:flex; align-items:baseline; gap:9px; margin-bottom:6px;">
                        <span style="font-size:30px; font-weight:800;">{{ number_format($stats['rating'], 1) }}</span>
                        <span class="cp-stars">{{ str_repeat('★', (int) round($stats['rating'])) }}</span>
                    </div>
                    <p class="cp-empty" style="margin-bottom:14px;">
                        Based on {{ $stats['reviews_count'] }} {{ Str::plural('review', $stats['reviews_count']) }}
                        from professionals who completed an event with {{ Str::before($client->name, ' ') }}.
                    </p>
                @else
                    <p class="cp-empty" style="margin-bottom:14px;">
                        No reviews yet.
                    </p>
                @endif

                {{-- The mockup breaks the rating into five bars — Overall
                     Experience, Communication, Organization, Payment
                     Reliability, Would Work Again. A review stores ONE rating,
                     so five bars would be one number drawn five times. That
                     needs per-category scores on the review itself; until then
                     the honest version is the aggregate above. --}}

                {{-- Section 7 — the same figures as the hero, by design. --}}
                @include('public.client._figures', ['only' => [
                    'completed_events', 'repeat_professionals', 'rating', 'member_since',
                ]])
            </div>
        </aside>
    </div>

    {{-- Section 12 — footer disclaimer. "Portfolio", not "profile". --}}
    <p class="cp-disclaimer">
        This client portfolio is visible to professionals on GigResource.
        Reviews are collected from completed events only.
    </p>
</div>
@endsection
