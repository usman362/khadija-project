@extends('layouts.professional')

@section('title', 'Bid Intelligence')

{{-- Bid Intelligence — explainer + a live snapshot of the pro's bid pipeline
     performance. REAL data: donut + legend = mutually-exclusive bid buckets
     (invited opportunities + requested/won/lost bookings); win-rate, average
     bid and response timers derived. AI insight / follow-up / market band are
     explainer UI. Professional blue accent (#2563eb) per portal theme. --}}

@php
    $segMap = collect($segments)->keyBy('key');
    $nameOf = fn ($k) => $segMap[$k]['name'] ?? ucfirst($k);
    $money  = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

@push('styles')
<style>
    .bi { --bi-blue: #2563eb; }
    .bi-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }
    .bi-crumb { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-muted); margin-bottom: 14px; }
    .bi-crumb a { color: var(--bi-blue); text-decoration: none; font-weight: 600; }
    .bi-crumb svg { width: 13px; height: 13px; }

    /* ── Hero ── */
    .bi-hero { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 22px; align-items: stretch; background: linear-gradient(135deg, rgba(37,99,235,0.04), rgba(139,92,246,0.03)); border: 1px solid var(--border-color); border-radius: 18px; padding: 24px 26px; margin-bottom: 20px; }
    .bi-hero-l { display: grid; grid-template-columns: min(190px, 100%) minmax(0,1fr); gap: 20px; align-items: start; }
    .bi-art { display: flex; align-items: flex-start; justify-content: center; padding-top: 6px; }
    .bi-art svg { width: 100%; max-width: 185px; height: auto; }
    .bi-hero-l h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .bi-hero-l .sub { font-size: 15px; color: var(--text-secondary); margin: 6px 0 16px; line-height: 1.45; }
    .bi-feat { display: flex; gap: 12px; padding: 9px 0; }
    .bi-feat-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bi-feat-ico svg { width: 18px; height: 18px; }
    .bi-feat b { font-size: 13px; font-weight: 800; color: var(--text-primary); display: block; }
    .bi-feat p { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.45; }

    /* donut card */
    .bi-donut-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 22px; display: flex; flex-direction: column; }
    .bi-donut-wrap { display: grid; grid-template-columns: min(190px, 100%) minmax(0,1fr); gap: 24px; align-items: center; flex: 1; }
    .bi-donut { width: 190px; height: 190px; border-radius: 50%; position: relative; flex-shrink: 0; margin: 0 auto; }
    .bi-donut::before { content: ''; position: absolute; inset: 30px; border-radius: 50%; background: var(--bg-card); }
    .bi-donut-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .bi-donut-c b { font-size: 44px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .bi-donut-c span { font-size: 14px; color: var(--text-secondary); margin-top: 4px; }
    .bi-leg { display: flex; flex-direction: column; gap: 12px; }
    .bi-leg-row { display: flex; align-items: center; gap: 11px; }
    .bi-leg-dot { width: 13px; height: 13px; border-radius: 50%; flex-shrink: 0; }
    .bi-leg-nm { font-size: 15px; font-weight: 700; color: var(--text-primary); flex: 1; }
    .bi-leg-ct { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .bi-pipe-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 15px; margin-top: 20px; border: 1px solid var(--border-color); border-radius: 12px; font-size: 15px; font-weight: 800; color: var(--bi-blue); text-decoration: none; }
    .bi-pipe-btn svg { width: 17px; height: 17px; }

    /* ── Section heading ── */
    .bi-sec-h { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .bi-sec-h .ic { width: 30px; height: 30px; border-radius: 9px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bi-sec-h .ic svg { width: 17px; height: 17px; color: #2563eb; }
    .bi-sec-h b { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .bi-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; padding-left: 40px; }

    /* ── Detailed Section Breakdown (3 cols) ── */
    .bi-bd { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; }
    .bi-bd-col { border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; }
    .bi-bd-h { font-size: 14.5px; font-weight: 800; color: var(--bi-blue); margin-bottom: 12px; line-height: 1.3; }
    .bi-bd-note { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 12px; }
    .bi-bd-note b { color: var(--text-primary); }
    .bi-bd-sep { height: 1px; background: var(--border-color); margin: 12px 0; }
    .bi-bd-leg { display: flex; gap: 11px; padding: 8px 0; align-items: flex-start; }
    .bi-bd-leg .dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; margin-top: 3px; }
    .bi-bd-leg b { font-size: 12.5px; color: var(--text-primary); }
    .bi-bd-leg p { font-size: 11px; color: var(--text-muted); margin: 1px 0 0; line-height: 1.4; }
    .bi-navmock { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; display: flex; gap: 14px; align-items: center; margin: 12px 0; }
    .bi-navmock .lines { flex: 1; }
    .bi-navmock .lrow { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; }
    .bi-navmock .lrow:last-child { margin-bottom: 0; }
    .bi-navmock .chk { width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bi-navmock .chk svg { width: 11px; height: 11px; color: #fff; }
    .bi-navmock .ln { flex: 1; height: 7px; border-radius: 4px; background: var(--border-color); }
    .bi-navmock .cal { width: 56px; height: 56px; border-radius: 12px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bi-navmock .cal svg { width: 28px; height: 28px; color: #2563eb; }
    .bi-bd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: rgba(37,99,235,0.06); border-radius: 11px; font-size: 13.5px; font-weight: 800; color: var(--bi-blue); text-decoration: none; }
    .bi-bd-cta svg { width: 15px; height: 15px; }

    /* ── What happens (4 cols) ── */
    .bi-cc { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .bi-cc-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; background: var(--bg-card); }
    .bi-cc-h { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; }
    .bi-cc-h .ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bi-cc-h .ic svg { width: 18px; height: 18px; }
    .bi-cc-h b { font-size: 13px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .bi-cc-h p { font-size: 11px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.4; }
    .bi-cc-prev { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; }
    /* win/loss */
    .bi-ai-box { border-radius: 9px; padding: 10px 12px; margin-bottom: 10px; }
    .bi-ai-box .k { font-size: 11px; font-weight: 800; margin-bottom: 4px; }
    .bi-ai-box p { font-size: 11px; color: var(--text-secondary); margin: 0; line-height: 1.45; }
    /* response timers */
    .bi-rt-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; }
    .bi-rt-row .dot { width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0; }
    .bi-rt-row .nm { font-size: 12px; color: var(--text-secondary); flex: 1; }
    .bi-rt-row .v { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .bi-rt-title { font-size: 12px; font-weight: 800; color: var(--text-primary); text-align: center; margin-bottom: 6px; }
    /* follow-up */
    .bi-fu-note { font-size: 11.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
    .bi-fu-msg { font-size: 11px; color: var(--text-secondary); line-height: 1.5; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 9px 11px; margin-bottom: 11px; }
    .bi-fu-btn { display: flex; align-items: center; justify-content: center; gap: 7px; padding: 10px; border-radius: 9px; background: #8b5cf6; color: #fff; font-size: 11.5px; font-weight: 800; text-decoration: none; }
    .bi-fu-btn svg { width: 13px; height: 13px; }
    /* benchmark */
    .bi-bm-amt { font-size: 26px; font-weight: 800; color: var(--text-primary); text-align: center; }
    .bi-bm-k { font-size: 11px; color: var(--text-muted); text-align: center; margin-bottom: 14px; }
    .bi-bm-bar { height: 8px; border-radius: 5px; background: linear-gradient(90deg, #fca5a5, #fcd34d, #86efac); position: relative; margin-bottom: 8px; }
    .bi-bm-bar .knob { position: absolute; top: 50%; width: 14px; height: 14px; border-radius: 50%; background: #fff; border: 3px solid #10b981; transform: translate(-50%,-50%); }
    .bi-bm-scale { display: flex; justify-content: space-between; }
    .bi-bm-scale div { text-align: center; }
    .bi-bm-scale .a { font-size: 11px; font-weight: 800; color: var(--text-primary); }
    .bi-bm-scale .b { font-size: 9.5px; color: var(--text-muted); }

    /* ── Bottom banner ── */
    .bi-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(139,92,246,0.05)); border: 1px solid rgba(37,99,235,0.18); border-radius: 16px; padding: 18px 22px; margin-top: 20px; }
    .bi-banner .badge { width: 48px; height: 48px; flex-shrink: 0; }
    .bi-banner .badge svg { width: 100%; height: 100%; }
    .bi-banner-txt { flex: 1; min-width: 200px; }
    .bi-banner-txt b { font-size: 17px; color: var(--text-primary); }
    .bi-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .bi-banner a { display: inline-flex; align-items: center; gap: 9px; background: #2563eb; color: #fff; font-size: 15px; font-weight: 800; padding: 14px 24px; border-radius: 12px; text-decoration: none; white-space: nowrap; }
    .bi-banner a svg { width: 17px; height: 17px; }

    @media (max-width: 1200px) { .bi-hero, .bi-hero-l { grid-template-columns: 1fr; } .bi-bd { grid-template-columns: 1fr; } .bi-cc { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .bi-cc { grid-template-columns: 1fr; } .bi-donut-wrap { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="bi">

    {{-- breadcrumb --}}
    <div class="bi-crumb"><a href="{{ route('professional.dashboard') }}">Dashboard</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg><span>Bid Intelligence</span></div>

    {{-- ════════ Hero ════════ --}}
    <div class="bi-hero">
        <div class="bi-hero-l">
            <div class="bi-art">
                <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="biBase" x1="40" y1="150" x2="160" y2="190"><stop stop-color="#93c5fd"/><stop offset="1" stop-color="#3b82f6"/></linearGradient></defs>
                    {{-- sparkles --}}
                    <path d="M40 50l1.6 4.4 4.4 1.6-4.4 1.6L40 62l-1.6-4.4L34 56l4.4-1.6z" fill="#60a5fa"/>
                    <path d="M170 90l1.2 3.4 3.4 1.2-3.4 1.2L170 99l-1.2-3.4-3.4-1.2 3.4-1.2z" fill="#93c5fd"/>
                    {{-- stand --}}
                    <ellipse cx="100" cy="172" rx="58" ry="14" fill="url(#biBase)" opacity="0.6"/>
                    <rect x="84" y="120" width="32" height="48" rx="6" fill="#3b82f6"/>
                    <rect x="84" y="120" width="32" height="48" rx="6" fill="#1d4ed8" opacity="0.3"/>
                    {{-- target rings --}}
                    <circle cx="96" cy="86" r="62" fill="#1d4ed8"/>
                    <circle cx="96" cy="86" r="62" fill="#2563eb"/>
                    <circle cx="96" cy="86" r="48" fill="#eff6ff"/>
                    <circle cx="96" cy="86" r="36" fill="#3b82f6"/>
                    <circle cx="96" cy="86" r="24" fill="#eff6ff"/>
                    <circle cx="96" cy="86" r="12" fill="#1d4ed8"/>
                    {{-- arrow --}}
                    <line x1="96" y1="86" x2="158" y2="36" stroke="#1e293b" stroke-width="5" stroke-linecap="round"/>
                    <path d="M96 86l16-5-5 16z" fill="#1e293b"/>
                    <path d="M158 36l8-10 4 8-12 2z" fill="#ef4444"/>
                    <path d="M150 30l10 4-4 10-10-4z" fill="#f59e0b"/>
                </svg>
            </div>
            <div>
                <h1>Bid Intelligence</h1>
                <div class="sub">Track performance and improve your win rate.</div>
                <div class="bi-feat"><span class="bi-feat-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><div><b>Stops Guesswork</b><p>See exactly which clients are engaging with your bids.</p></div></div>
                <div class="bi-feat"><span class="bi-feat-ico" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg></span><div><b>Boosts Wins</b><p>Learn what works so you can win more high-paying jobs.</p></div></div>
                <div class="bi-feat"><span class="bi-feat-ico" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg></span><div><b>Organizes Follow-Ups</b><p>Know who to call or email and when.</p></div></div>
                <div class="bi-feat"><span class="bi-feat-ico" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span><div><b>Saves Energy</b><p>Focus your time on the bids that have the best chance.</p></div></div>
            </div>
        </div>

        {{-- donut card --}}
        <div class="bi-donut-card">
            <div class="bi-donut-wrap">
                <div class="bi-donut" style="background: conic-gradient({{ $donutGradient }});">
                    <div class="bi-donut-c"><b>{{ $stats['total'] }}</b><span>Total Bids</span></div>
                </div>
                <div class="bi-leg">
                    @foreach($segments as $seg)
                        <div class="bi-leg-row"><span class="bi-leg-dot" style="background:{{ $seg['color'] }};"></span><span class="bi-leg-nm">{{ $seg['name'] }}</span><span class="bi-leg-ct">{{ $seg['count'] }}</span></div>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('professional.proposals.index') }}" class="bi-pipe-btn">View Full Pipeline <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>

    {{-- ════════ Detailed Section Breakdown ════════ --}}
    <div class="bi-card" style="margin-bottom:20px;">
        <div class="bi-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><b>Detailed Section Breakdown</b></div>
        <p class="bi-sec-sub">Understand how your bids move through the pipeline.</p>
        <div class="bi-bd">
            {{-- 1. Target Header --}}
            <div class="bi-bd-col">
                <div class="bi-bd-h">1. The Target Header <span style="color:var(--text-muted);font-weight:600;">(Top Section)</span></div>
                <p class="bi-bd-note"><b style="color:#2563eb;">The Goal:</b> The target and arrow icon shows that this tool helps you aim for and hit your sales goals.</p>
                <div class="bi-bd-sep"></div>
                <p class="bi-bd-note"><b style="color:#8b5cf6;">The Mission:</b> The text explains that tracking your past performance is the secret to winning more future jobs.</p>
            </div>
            {{-- 2. Donut Chart & Legend --}}
            <div class="bi-bd-col">
                <div class="bi-bd-h">2. The Donut Chart &amp; Legend <span style="color:var(--text-muted);font-weight:600;">(Middle Section)</span></div>
                <p class="bi-bd-note">The colorful circle and list show your <b>{{ $stats['total'] }} Total Bids</b> divided into progress steps.</p>
                @foreach($segments as $seg)
                    <div class="bi-bd-leg"><span class="dot" style="background:{{ $seg['color'] }};"></span><div><b>{{ $seg['name'] }} ({{ $seg['count'] }})</b><p>{{ $seg['desc'] }}</p></div></div>
                @endforeach
            </div>
            {{-- 3. Navigation Gate --}}
            <div class="bi-bd-col">
                <div class="bi-bd-h">3. The Navigation Gate <span style="color:var(--text-muted);font-weight:600;">(Bottom Section)</span></div>
                <p class="bi-bd-note">The <b>"View Full Pipeline"</b> button takes you to the complete sales calendar and master tracking spreadsheet.</p>
                <div class="bi-navmock">
                    <div class="lines">
                        <div class="lrow"><span class="chk" style="background:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span class="ln"></span></div>
                        <div class="lrow"><span class="chk" style="background:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span class="ln" style="width:80%;"></span></div>
                        <div class="lrow"><span class="chk" style="background:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span class="ln" style="width:65%;"></span></div>
                    </div>
                    <span class="cal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                </div>
                <a href="{{ route('professional.proposals.index') }}" class="bi-bd-cta">View Full Pipeline <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>
    </div>

    {{-- ════════ What happens if you click ════════ --}}
    <div class="bi-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg></span><b>What Happens if You Click?</b></div>
    <p class="bi-sec-sub">Unlock deep-dive features to help you win more jobs.</p>
    <div class="bi-cc">
        {{-- Win/Loss Reports --}}
        <div class="bi-cc-card">
            <div class="bi-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><div><b>Win/Loss Reports</b><p>Smart AI insights that explain why you won or lost a job.</p></div></div>
            <div class="bi-cc-prev">
                <div class="bi-ai-box" style="background:rgba(37,99,235,0.07);">
                    <div class="k" style="color:#2563eb;">AI Insight</div>
                    <p>Your win rate is <b>{{ $stats['win_rate'] }}%</b>. Bids viewed by clients convert far more often than un-opened ones.</p>
                </div>
                <div class="bi-ai-box" style="background:rgba(16,185,129,0.07);">
                    <div class="k" style="color:#10b981;">Recommendation</div>
                    <p>Consider adjusting pricing or highlighting more value on bids that stall after being viewed.</p>
                </div>
            </div>
        </div>
        {{-- Client Response Timers --}}
        <div class="bi-cc-card">
            <div class="bi-cc-h"><span class="ic" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>Client Response Timers</b><p>See exactly how long clients spend reviewing your proposal.</p></div></div>
            <div class="bi-cc-prev">
                <div class="bi-rt-title">Average Response Time</div>
                @foreach($responseTimes as $rt)
                    <div class="bi-rt-row"><span class="dot" style="background:{{ $rt['color'] }};"></span><span class="nm">{{ $nameOf($rt['key']) }}</span><span class="v">{{ $rt['days'] }} days</span></div>
                @endforeach
            </div>
        </div>
        {{-- Follow-Up Automation --}}
        <div class="bi-cc-card">
            <div class="bi-cc-h"><span class="ic" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg></span><div><b>Follow-Up Automation</b><p>Send one-click follow-up emails to clients who viewed your bid.</p></div></div>
            <div class="bi-cc-prev">
                <div class="bi-fu-note">Quick Follow-Up</div>
                <div class="bi-fu-msg">Hi! Just following up on the proposal I sent over. Let me know if you have any questions!</div>
                <a href="{{ route('professional.chat.index') }}" class="bi-fu-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send Follow-Up Email</a>
            </div>
        </div>
        {{-- Competitor Benchmarks --}}
        <div class="bi-cc-card">
            <div class="bi-cc-h"><span class="ic" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg></span><div><b>Competitor Benchmarks</b><p>See anonymous data on how your pricing compares locally.</p></div></div>
            <div class="bi-cc-prev">
                <div style="font-size:11.5px;font-weight:800;color:var(--text-primary);text-align:center;margin-bottom:8px;">Your Pricing vs Market</div>
                <div class="bi-bm-amt">{{ $money($pricing['avg']) }}</div>
                <div class="bi-bm-k">Your Average Bid</div>
                <div class="bi-bm-bar"><span class="knob" style="left:{{ $pricing['pos'] }}%;"></span></div>
                <div class="bi-bm-scale">
                    <div><div class="a">{{ $money($pricing['low']) }}</div><div class="b">Low</div></div>
                    <div><div class="a">{{ $money($pricing['mid']) }}</div><div class="b">Market Avg</div></div>
                    <div><div class="a">{{ $money($pricing['high']) }}</div><div class="b">High</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="bi-banner">
        <span class="badge">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="22" cy="24" r="16" fill="#2563eb"/><circle cx="22" cy="24" r="11" fill="#eff6ff"/><circle cx="22" cy="24" r="6" fill="#2563eb"/><circle cx="22" cy="24" r="2" fill="#fff"/>
                <line x1="22" y1="24" x2="40" y2="10" stroke="#1e293b" stroke-width="2.5" stroke-linecap="round"/><path d="M40 10l4-4 1 5-5-1z" fill="#f59e0b"/>
            </svg>
        </span>
        <div class="bi-banner-txt"><b>Track. Analyze. Win More.</b><p>Use Bid Intelligence to aim smarter, follow up faster, and close more deals.</p></div>
        <a href="{{ route('professional.leads.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Go to Full Pipeline <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
