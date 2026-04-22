@extends('layouts.public')

@section('title', $pro->name . ' — Professional Profile')

@push('styles')
<style>
/* ============================================================
   Public Professional Profile
   ------------------------------------------------------------
   Layout is a single column on mobile, two columns on desktop:
   [ hero + about + reviews feed ]   [ satisfaction | verified ]

   The two signature cards (.pp-card-satisfaction, .pp-card-verified)
   mirror the magazine-ad the client showed as reference. Everything
   else is a standard pro store-front (hero, bio, portfolio, reviews).
   ============================================================ */

.pp-page {
    max-width: 1180px;
    margin: 0 auto;
    padding: 40px 24px 80px;
    color: var(--text-primary, #1f2937);
}
@media (max-width: 720px) { .pp-page { padding: 24px 16px 48px; } }

.pp-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 28px;
    align-items: start;
}
@media (max-width: 900px) { .pp-grid { grid-template-columns: 1fr; } }

/* ── Hero card ─────────────────────────────────────────────── */
.pp-hero {
    position: relative;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(15,23,42,0.06);
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
}
.pp-hero-cover {
    height: 160px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    background-size: cover; background-position: center;
}
.pp-hero-body {
    padding: 0 28px 24px;
    display: flex;
    gap: 20px;
    align-items: flex-end;
    margin-top: -56px;
    flex-wrap: wrap;
}
.pp-hero-avatar {
    width: 128px; height: 128px;
    border-radius: 50%;
    border: 5px solid #fff;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    object-fit: cover;
    flex-shrink: 0;
}
.pp-hero-meta { flex: 1; min-width: 220px; padding-bottom: 8px; }
.pp-hero-name {
    font-size: 26px; font-weight: 800;
    color: #0f172a;
    margin: 8px 0 4px;
    line-height: 1.1;
}
.pp-hero-headline {
    font-size: 15px; color: #475569; font-weight: 500;
    margin-bottom: 10px;
}
.pp-hero-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.pp-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px;
    background: #f1f5f9; color: #475569;
    border-radius: 999px;
    font-size: 12px; font-weight: 500;
}
.pp-tag.featured { background: #fef3c7; color: #92400e; }
.pp-tag.top { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
.pp-hero-cta {
    padding-bottom: 8px;
    display: flex; gap: 8px; flex-wrap: wrap;
}
.pp-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 14px; font-weight: 600;
    text-decoration: none;
    transition: all 0.15s;
    border: 1px solid transparent;
    cursor: pointer;
}
.pp-btn-primary { background: #2563eb; color: #fff; }
.pp-btn-primary:hover { background: #1d4ed8; }
.pp-btn-outline { background: #fff; color: #334155; border-color: #cbd5e1; }
.pp-btn-outline:hover { border-color: #2563eb; color: #2563eb; }

/* ── Generic content card ──────────────────────────────────── */
.pp-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(15,23,42,0.04);
}
.pp-card-title {
    font-size: 16px; font-weight: 700;
    color: #0f172a;
    margin: 0 0 14px;
    display: flex; align-items: center; gap: 8px;
}
.pp-card-title svg { color: #64748b; }
.pp-card-body { font-size: 14px; line-height: 1.6; color: #334155; }

/* ── Satisfaction Results (magazine-ad style) ──────────────── */
.pp-card-satisfaction { padding: 22px; }
.pp-sat-head {
    font-size: 15px; font-weight: 700; color: #0f172a;
    margin-bottom: 14px; text-align: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.pp-sat-bars {
    display: flex; flex-direction: column; gap: 7px;
    margin-bottom: 16px;
}
.pp-sat-row {
    display: grid;
    grid-template-columns: 58px 1fr 48px;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}
.pp-sat-label {
    color: #f59e0b;
    letter-spacing: 1px;
    font-size: 11px;
    white-space: nowrap;
}
.pp-sat-bar-track {
    height: 10px;
    background: #f1f5f9;
    border-radius: 5px;
    overflow: hidden;
    position: relative;
}
.pp-sat-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 5px;
    transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}
.pp-sat-count {
    text-align: right;
    color: #64748b;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}
.pp-sat-overall {
    text-align: center;
    padding: 12px 0 2px;
    border-top: 1px dashed #e2e8f0;
}
.pp-sat-score {
    font-size: 14px; font-weight: 700; color: #0f172a;
    display: inline-flex; align-items: baseline; gap: 4px;
}
.pp-sat-score .big { font-size: 26px; }
.pp-sat-score .unit { font-size: 13px; color: #64748b; font-weight: 500; }
.pp-sat-count-total {
    font-size: 12px; color: #64748b;
    margin-top: 2px;
}

/* ── Verified Credentials (checklist) ──────────────────────── */
.pp-card-verified { padding: 22px; }
.pp-ver-head {
    font-size: 15px; font-weight: 700; color: #0f172a;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
}
.pp-ver-list { display: flex; flex-direction: column; gap: 10px; }
.pp-ver-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 8px 2px;
}
.pp-ver-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #10b981; color: #fff;
    font-size: 13px; font-weight: 700;
    margin-top: 1px;
}
.pp-ver-item.none .pp-ver-check { background: #e2e8f0; color: #94a3b8; }
.pp-ver-item.pending .pp-ver-check { background: #f59e0b; color: #fff; }
.pp-ver-body { flex: 1; min-width: 0; }
.pp-ver-label {
    font-size: 13.5px; font-weight: 600; color: #0f172a;
    line-height: 1.3;
}
.pp-ver-item.none .pp-ver-label { color: #94a3b8; text-decoration: line-through; }
.pp-ver-num {
    font-size: 11px; color: #64748b;
    margin-top: 2px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    letter-spacing: 0.5px;
}
.pp-ver-status {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #10b981;
}
.pp-ver-item.pending .pp-ver-status { color: #f59e0b; }
.pp-ver-item.none .pp-ver-status { color: #94a3b8; }

/* ── Reviews feed ──────────────────────────────────────────── */
.pp-review {
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
    display: flex; gap: 14px;
}
.pp-review:last-child { border-bottom: none; }
.pp-review-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    flex-shrink: 0;
    background: #e2e8f0;
    object-fit: cover;
}
.pp-review-body { flex: 1; min-width: 0; }
.pp-review-head {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.pp-review-name { font-size: 14px; font-weight: 600; color: #0f172a; }
.pp-review-stars { color: #f59e0b; font-size: 13px; letter-spacing: 1px; }
.pp-review-date { font-size: 11px; color: #94a3b8; margin-left: auto; }
.pp-review-title { font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 2px; }
.pp-review-text { font-size: 13px; color: #475569; line-height: 1.55; }
.pp-review-response {
    margin-top: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border-left: 3px solid #2563eb;
    border-radius: 4px;
    font-size: 12.5px;
    color: #475569;
}
.pp-review-response strong { color: #0f172a; }
.pp-empty-reviews {
    text-align: center; padding: 32px 12px;
    color: #94a3b8; font-size: 13.5px;
    font-style: italic;
}

/* ── Skill chips + key/value list ──────────────────────────── */
.pp-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.pp-chip {
    padding: 4px 11px;
    background: #eff6ff; color: #1d4ed8;
    border-radius: 16px;
    font-size: 12px; font-weight: 500;
}
.pp-kv { display: grid; grid-template-columns: 130px 1fr; row-gap: 10px; column-gap: 14px; font-size: 13.5px; }
.pp-kv dt { color: #64748b; font-weight: 500; }
.pp-kv dd { color: #0f172a; margin: 0; }

/* ── Top-rated seal ────────────────────────────────────────── */
.pp-seal {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #fcd34d;
    border-radius: 12px;
    margin-bottom: 20px;
}
.pp-seal-icon {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pp-seal-text { font-size: 12.5px; color: #92400e; line-height: 1.35; }
.pp-seal-text strong { display: block; font-size: 13.5px; color: #78350f; margin-bottom: 1px; }
</style>
@endpush

@section('content')
@php
    $topRated = $pro->isTopRated();
@endphp

<div class="pp-page">
    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="pp-hero">
        <div class="pp-hero-cover"
             @if($pro->cover_image_url) style="background-image: url('{{ $pro->cover_image_url }}');" @endif></div>
        <div class="pp-hero-body">
            <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="pp-hero-avatar">
            <div class="pp-hero-meta">
                <div class="pp-hero-name">{{ $pro->name }}</div>
                @if($profile->headline)
                    <div class="pp-hero-headline">{{ $profile->headline }}</div>
                @endif
                <div class="pp-hero-tags">
                    @if($profile->city || $profile->country)
                        <span class="pp-tag">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ trim(collect([$profile->city, $profile->country])->filter()->implode(', ')) }}
                        </span>
                    @endif
                    @if($profile->hourly_rate)
                        <span class="pp-tag">${{ number_format($profile->hourly_rate, 0) }}/hr</span>
                    @endif
                    @if($profile->experience_years)
                        <span class="pp-tag">{{ $profile->experience_years }}+ yrs experience</span>
                    @endif
                    @if($topRated)
                        <span class="pp-tag top">★ Top Rated Pro</span>
                    @endif
                </div>
            </div>
            <div class="pp-hero-cta">
                @auth
                    <a href="#" class="pp-btn pp-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Message
                    </a>
                @else
                    <a href="{{ route('login') }}" class="pp-btn pp-btn-primary">Sign in to contact</a>
                @endauth
                <a href="#reviews" class="pp-btn pp-btn-outline">See reviews</a>
            </div>
        </div>
    </div>

    {{-- ── Main grid ────────────────────────────────────────── --}}
    <div class="pp-grid">
        {{-- Left column: About, Skills, Portfolio, Reviews --}}
        <div>
            @if($profile->bio)
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        About
                    </h3>
                    <div class="pp-card-body" style="white-space: pre-line;">{{ $profile->bio }}</div>
                </div>
            @endif

            @if(!empty($profile->skills))
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Skills
                    </h3>
                    <div class="pp-chips">
                        @foreach((array) $profile->skills as $skill)
                            <span class="pp-chip">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($profile->portfolio))
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        Portfolio
                    </h3>
                    <dl class="pp-kv">
                        @foreach((array) $profile->portfolio as $item)
                            <dt>{{ $item['title'] ?? '—' }}</dt>
                            <dd>
                                @if(!empty($item['url']))
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" style="color:#2563eb;">{{ $item['url'] }}</a>
                                @endif
                                @if(!empty($item['description']))
                                    <div style="color:#64748b;margin-top:2px;">{{ $item['description'] }}</div>
                                @endif
                            </dd>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Reviews feed --}}
            <div class="pp-card" id="reviews">
                <h3 class="pp-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Recent Reviews
                    <span style="margin-left:auto;font-size:12px;color:#64748b;font-weight:500;">
                        {{ $stats['count'] }} total
                    </span>
                </h3>
                @forelse($reviews as $r)
                    <div class="pp-review">
                        <img src="{{ $r->reviewer?->avatar_url ?? 'https://ui-avatars.com/api/?name=?&size=88&background=cbd5e1&color=64748b' }}"
                             alt="{{ $r->reviewer?->name ?? 'Former client' }}"
                             class="pp-review-avatar">
                        <div class="pp-review-body">
                            <div class="pp-review-head">
                                <span class="pp-review-name">{{ $r->reviewer?->name ?? 'Former client' }}</span>
                                <span class="pp-review-stars" aria-label="{{ $r->rating }} out of 5">
                                    {{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}
                                </span>
                                <span class="pp-review-date">{{ $r->created_at->diffForHumans() }}</span>
                            </div>
                            @if($r->title)
                                <div class="pp-review-title">{{ $r->title }}</div>
                            @endif
                            <div class="pp-review-text">{{ $r->comment }}</div>
                            @if($r->response)
                                <div class="pp-review-response">
                                    <strong>{{ $pro->name }} responded:</strong>
                                    {{ $r->response }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pp-empty-reviews">No reviews yet — be the first to work with {{ $pro->name }}.</div>
                @endforelse
            </div>
        </div>

        {{-- Right column: The two signature cards ─────────────── --}}
        <div>
            @if($topRated)
                <div class="pp-seal">
                    <div class="pp-seal-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="pp-seal-text">
                        <strong>Top Rated Pro</strong>
                        Verified credentials &amp; 4.5+ rating
                    </div>
                </div>
            @endif

            {{-- Homeowner Satisfaction Results — histogram card --}}
            <div class="pp-card pp-card-satisfaction">
                <div class="pp-sat-head">Customer Satisfaction Results</div>

                @php
                    // Max count drives the bar widths (relative). Falls back to 1
                    // to avoid a divide-by-zero when there are no reviews at all.
                    $maxCount = max(1, max($stats['histogram']));
                @endphp

                <div class="pp-sat-bars">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        @php
                            $count = $stats['histogram'][$star] ?? 0;
                            $pct = ($count / $maxCount) * 100;
                        @endphp
                        <div class="pp-sat-row">
                            <div class="pp-sat-label">{{ str_repeat('★', $star) }}</div>
                            <div class="pp-sat-bar-track">
                                <div class="pp-sat-bar-fill" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="pp-sat-count">{{ number_format($count) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="pp-sat-overall">
                    <div class="pp-sat-score">
                        Rating:
                        <span class="big">{{ number_format($stats['average'], 1) }}</span>
                        <span class="unit">out of 5</span>
                    </div>
                    <div class="pp-sat-count-total">
                        Based on {{ number_format($stats['count']) }} verified {{ \Illuminate\Support\Str::plural('review', $stats['count']) }}
                    </div>
                </div>
            </div>

            {{-- Verified Credentials — checklist card --}}
            <div class="pp-card pp-card-verified">
                <div class="pp-ver-head">Verified Credentials</div>
                <div class="pp-ver-list">
                    @foreach($badges as $key => $label)
                        @php
                            $status    = $profile->badgeStatus($key);      // 'verified' | 'pending' | 'none'
                            $number    = $profile->{$key . '_number'};
                            $statusTxt = ['verified' => 'Verified', 'pending' => 'Pending review', 'none' => 'Not submitted'][$status];
                        @endphp
                        <div class="pp-ver-item {{ $status }}">
                            <div class="pp-ver-check">
                                @if($status === 'verified') ✓
                                @elseif($status === 'pending') …
                                @else ✕
                                @endif
                            </div>
                            <div class="pp-ver-body">
                                <div class="pp-ver-label">{{ $label }}</div>
                                @if($number && $status === 'verified')
                                    <div class="pp-ver-num">#{{ $number }}</div>
                                @endif
                                <div class="pp-ver-status">{{ $statusTxt }}</div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Platform-awarded "Best Pick" equivalent ─────────── --}}
                    <div class="pp-ver-item {{ $topRated ? '' : 'none' }}">
                        <div class="pp-ver-check">{!! $topRated ? '✓' : '✕' !!}</div>
                        <div class="pp-ver-body">
                            <div class="pp-ver-label">GigResource Top Rated</div>
                            <div class="pp-ver-status">
                                {{ $topRated ? 'Awarded' : 'Needs 5+ reviews at 4.5★ and full verification' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
