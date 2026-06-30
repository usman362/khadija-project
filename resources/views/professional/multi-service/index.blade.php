@extends('layouts.professional')

@section('title', 'Multi-Service Requests')

{{-- ════════════════════════════════════════════════════════════════
     Professional Multi-Service Requests — browse & bid on event
     postings that need multiple services. Wired to REAL data
     (multi-service events = published, unassigned, 2+ categories) via
     ProfessionalMultiServiceController. Per-service budget split + bid
     counts are derived (no service-line table yet). Explainer content
     is static.
═══════════════════════════════════════════════════════════════════ --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $svcColors = ['#2563eb','#8b5cf6','#f97316','#06b6d4','#ec4899','#10b981','#6366f1','#f59e0b'];
@endphp

@push('styles')
<style>
    .pm { --pm-blue: #2563eb; }
    .pm-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px 20px; }

    /* Header */
    .pm-head { display: grid; grid-template-columns: minmax(0,1.3fr) minmax(0,1fr); gap: 24px; align-items: center; margin-bottom: 22px; }
    .pm-title { display: flex; align-items: center; gap: 10px; }
    .pm-title h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pm-new { font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: rgba(37,99,235,0.14); color: #2563eb; letter-spacing: 0.5px; }
    .pm-lead { font-size: 15px; font-weight: 700; color: var(--text-primary); margin: 10px 0 14px; }
    .pm-head p { font-size: 13px; color: var(--text-muted); line-height: 1.6; margin: 0 0 10px; }
    /* diagram */
    .pm-diagram { display: flex; align-items: center; justify-content: center; gap: 10px; }
    .pm-diag-col { display: flex; flex-direction: column; gap: 22px; }
    .pm-diag-svc { display: flex; align-items: center; gap: 8px; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); white-space: nowrap; }
    .pm-diag-svc.r { flex-direction: row-reverse; }
    .pm-diag-ico { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pm-diag-ico svg { width: 17px; height: 17px; }
    .pm-brief { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.06); width: 150px; }
    .pm-brief-h { font-size: 11px; font-weight: 800; color: var(--text-primary); text-align: center; margin-bottom: 9px; letter-spacing: 0.5px; }
    .pm-brief-row { display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: var(--text-secondary); margin: 5px 0; }
    .pm-brief-row svg { width: 12px; height: 12px; color: #10b981; }
    .pm-brief-add { font-size: 10.5px; font-weight: 700; color: #2563eb; margin-top: 5px; }
    .pm-diag-tag { grid-column: 1/-1; text-align: center; margin-top: 12px; }
    .pm-diag-tag span { font-size: 11px; font-weight: 800; color: #fff; background: linear-gradient(135deg,#8b5cf6,#6d28d9); padding: 6px 16px; border-radius: 8px; letter-spacing: 0.5px; }

    /* How it works */
    .pm-sec-title { font-size: 18px; font-weight: 800; color: var(--text-primary); margin: 0 0 14px; }
    .pm-steps { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; align-items: stretch; margin-bottom: 18px; }
    .pm-step { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 12px; text-align: center; flex: 1; }
    .pm-step-ico { width: 48px; height: 48px; border-radius: 12px; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
    .pm-step-ico svg { width: 24px; height: 24px; }
    .pm-step h4 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 5px; }
    .pm-step p { font-size: 10.5px; color: var(--text-muted); line-height: 1.4; margin: 0; }
    .pm-steps-wrap { display: flex; align-items: stretch; gap: 6px; margin-bottom: 18px; }
    .pm-step-arrow { display: flex; align-items: center; color: var(--text-muted); }
    .pm-step-arrow svg { width: 18px; height: 18px; }

    .pm-banner { display: flex; align-items: center; gap: 12px; background: rgba(37,99,235,0.05); border: 1px solid rgba(37,99,235,0.18); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .pm-banner .ic { width: 34px; height: 34px; border-radius: 9px; background: rgba(37,99,235,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pm-banner .ic svg { width: 18px; height: 18px; }
    .pm-banner p { font-size: 12.5px; color: var(--text-secondary); margin: 0; line-height: 1.5; }
    .pm-banner.green { background: rgba(16,185,129,0.07); border-color: rgba(16,185,129,0.22); }
    .pm-banner.green .ic { background: rgba(16,185,129,0.14); color: #10b981; }

    /* Example + sidebar grid */
    .pm-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 16px; align-items: start; margin-bottom: 20px; }
    .pm-ex-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
    .pm-ex-head h3 { font-size: 18px; font-weight: 800; margin: 0; }
    .pm-ex-head p { font-size: 12px; color: var(--text-muted); margin: 2px 0 0; }
    .pm-btn-ghost { font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); text-decoration: none; white-space: nowrap; }
    .pm-event-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .pm-event-h b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pm-event-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .pm-live { font-size: 9.5px; font-weight: 800; color: #10b981; display: inline-flex; align-items: center; gap: 5px; }
    .pm-live .dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; }
    .pm-svcs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .pm-svc { border: 1px solid var(--border-color); border-radius: 11px; padding: 13px; text-align: center; }
    .pm-svc-ico { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; color: #fff; }
    .pm-svc-ico svg { width: 20px; height: 20px; }
    .pm-svc-name { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pm-svc-desc { font-size: 10px; color: var(--text-muted); margin: 3px 0 8px; line-height: 1.35; min-height: 26px; }
    .pm-svc-budget-k { font-size: 9px; color: var(--text-muted); }
    .pm-svc-budget { font-size: 12px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
    .pm-svc-bids { font-size: 10px; color: var(--text-muted); margin-bottom: 8px; }
    .pm-svc-bid { display: block; font-size: 11px; font-weight: 800; color: #2563eb; text-decoration: none; padding: 6px; border-radius: 8px; background: rgba(37,99,235,0.08); }
    .pm-ex-note { display: flex; align-items: center; gap: 8px; margin-top: 12px; padding: 10px 12px; background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.2); border-radius: 9px; font-size: 11.5px; color: var(--text-secondary); }
    .pm-ex-note svg { width: 15px; height: 15px; color: #10b981; flex-shrink: 0; }

    /* sidebar cards */
    .pm-side { display: flex; flex-direction: column; gap: 16px; }
    .pm-side-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; }
    .pm-side-card h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; display: flex; align-items: center; gap: 7px; }
    .pm-side-card .sub { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px; }
    .pm-check { display: flex; align-items: center; gap: 9px; font-size: 12px; color: var(--text-secondary); padding: 5px 0; }
    .pm-check svg { width: 16px; height: 16px; color: #2563eb; flex-shrink: 0; }
    .pm-help-link { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--text-primary); font-size: 12px; font-weight: 700; text-decoration: none; margin-bottom: 8px; }
    .pm-help-link svg { width: 15px; height: 15px; color: #2563eb; }

    /* Why pros love */
    .pm-why { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px; }
    .pm-why-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; text-align: center; }
    .pm-why-ico { width: 38px; height: 38px; border-radius: 10px; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 9px; }
    .pm-why-ico svg { width: 18px; height: 18px; }
    .pm-why-card h5 { font-size: 12px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pm-why-card p { font-size: 10px; color: var(--text-muted); line-height: 1.4; margin: 0; }

    /* Recent table */
    .pm-rt-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .pm-rt-head h3 { font-size: 16px; font-weight: 800; margin: 0; }
    .pm-rt-head a { font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none; }
    .pm-tbl-wrap { width: 100%; overflow-x: auto; }
    .pm-tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
    .pm-tbl th { text-align: left; padding: 9px 8px; font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .pm-tbl td { padding: 11px 8px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .pm-rt-ev { display: flex; align-items: center; gap: 9px; }
    .pm-rt-thumb { width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; background: linear-gradient(135deg,#3b82f6,#1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; }
    .pm-rt-thumb svg { width: 14px; height: 14px; }
    .pm-rt-name { font-weight: 700; color: var(--text-primary); white-space: nowrap; }
    .pm-rt-client { font-size: 10px; color: var(--text-muted); white-space: nowrap; }
    .pm-svc-pills { display: inline-flex; align-items: center; gap: 4px; }
    .pm-svc-pill { width: 24px; height: 24px; border-radius: 6px; background: rgba(37,99,235,0.1); color: #2563eb; display: inline-flex; align-items: center; justify-content: center; }
    .pm-svc-pill svg { width: 12px; height: 12px; }
    .pm-svc-more { font-size: 10px; font-weight: 700; color: var(--text-muted); }
    .pm-rt-bid { font-size: 11px; font-weight: 800; color: #2563eb; text-decoration: none; padding: 5px 12px; border-radius: 7px; background: rgba(37,99,235,0.08); white-space: nowrap; }

    /* Bottom CTA */
    .pm-cta { display: flex; align-items: center; gap: 14px; background: linear-gradient(120deg,#1e3a8a,#2563eb); border-radius: 14px; padding: 18px 22px; margin-top: 20px; }
    .pm-cta .ic { width: 40px; height: 40px; border-radius: 11px; background: rgba(255,255,255,0.18); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pm-cta .ic svg { width: 20px; height: 20px; }
    .pm-cta-txt { flex: 1; color: #fff; }
    .pm-cta-txt b { font-size: 14px; font-weight: 800; }
    .pm-cta-txt p { font-size: 12px; color: rgba(255,255,255,0.9); margin: 2px 0 0; }
    .pm-cta-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #1e40af; font-size: 13px; font-weight: 800; padding: 11px 20px; border-radius: 10px; text-decoration: none; white-space: nowrap; }
    .pm-cta-btn svg { width: 15px; height: 15px; }

    @media (max-width: 1200px) { .pm-head { grid-template-columns: 1fr; } .pm-grid { grid-template-columns: 1fr; } .pm-steps, .pm-why { grid-template-columns: repeat(2, 1fr); } .pm-step-arrow { display: none; } .pm-steps-wrap { flex-wrap: wrap; } }
    @media (max-width: 760px) { .pm-svcs { grid-template-columns: 1fr 1fr; } .pm-steps, .pm-why { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pm">

    {{-- ════════ Header + diagram ════════ --}}
    <div class="pm-head">
        <div>
            <div class="pm-title"><h1>Multi-Service Requests</h1><span class="pm-new">NEW</span></div>
            <div class="pm-lead">One event. Multiple services. More opportunities.</div>
            <p>Clients create a single event posting and add one or more services they need.</p>
            <p>You can bid on one service or multiple services within the same event.</p>
            <p>Our system keeps every gig completely separate, organized, and fair for all professionals.</p>
        </div>
        <div class="pm-diagram">
            <div class="pm-diag-col">
                <span class="pm-diag-svc"><span class="pm-diag-ico" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2M5 2v20M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg></span>Catering</span>
                <span class="pm-diag-svc"><span class="pm-diag-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg></span>Audio Visual</span>
                <span class="pm-diag-svc"><span class="pm-diag-ico" style="background:rgba(236,72,153,0.12);color:#ec4899;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h6l2-9 4 18 2-9h6"/></svg></span>Decor &amp; Design</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                <div class="pm-brief">
                    <div class="pm-brief-h">EVENT BRIEF</div>
                    <div class="pm-brief-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Date &amp; Time</div>
                    <div class="pm-brief-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Location</div>
                    <div class="pm-brief-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Guest Count</div>
                    <div class="pm-brief-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Budget Range</div>
                    <div class="pm-brief-add">+ Multiple Services</div>
                </div>
                <div class="pm-diag-tag"><span>ONE EVENT. MULTIPLE SERVICES.</span></div>
            </div>
            <div class="pm-diag-col">
                <span class="pm-diag-svc r"><span class="pm-diag-ico" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>Entertainment</span>
                <span class="pm-diag-svc r"><span class="pm-diag-ico" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>Photography</span>
                <span class="pm-diag-svc r"><span class="pm-diag-ico" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span>Staffing</span>
            </div>
        </div>
    </div>

    {{-- ════════ How It Works ════════ --}}
    <h3 class="pm-sec-title">How It Works for Professionals</h3>
    <div class="pm-steps-wrap">
        @php
            $hiw = [
                ['bell', '1. Get Notified', "You'll be notified when a client posts a Multi-Service Request that matches your services."],
                ['target', '2. Choose What to Bid', 'View all services in the event. Bid on one, some, or all services you provide.'],
                ['clip', '3. Submit Your Bid(s)', 'Each service is a separate gig. Submit individual bids with pricing and details.'],
                ['shield', '4. Client Reviews', 'The client reviews and awards each service separately to the best fit.'],
                ['trophy', '5. You Win & Deliver', 'Manage each awarded gig independently through your dashboard.'],
            ];
        @endphp
        @foreach($hiw as $i => $s)
            <div class="pm-step">
                <div class="pm-step-ico">
                    @switch($s[0])
                        @case('bell') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> @break
                        @case('target') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg> @break
                        @case('clip') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg> @break
                        @case('shield') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg> @break
                        @default <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                    @endswitch
                </div>
                <h4>{{ $s[1] }}</h4>
                <p>{{ $s[2] }}</p>
            </div>
            @if($i < count($hiw) - 1)<div class="pm-step-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>@endif
        @endforeach
    </div>

    <div class="pm-banner">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></span>
        <p><b>Every service is a separate gig</b> — different professionals can win different services in the same event. No overlapping. No confusion. Just a smarter, more organized way to do more business.</p>
    </div>

    {{-- ════════ Example + sidebar ════════ --}}
    <div class="pm-grid">
        <div class="pm-card">
            <div class="pm-ex-head">
                <div><h3>Example: One Event with Multiple Services</h3><p>Client posts one event with multiple service needs.</p></div>
                <a href="{{ route('professional.gigs.index') }}" class="pm-btn-ghost">View Live Example</a>
            </div>
            @if($featured)
                <div class="pm-event-h">
                    <div>
                        <b>{{ $featured->title }}</b>
                        <div class="pm-event-meta">{{ $featured->starts_at?->format('M d, Y') }} · {{ $featured->location ?? 'TBD' }} · {{ $featured->client?->name ?? 'Client' }} · Budget: {{ $featured->budget ? $money($featured->budget) : 'Open' }}</div>
                    </div>
                    <span class="pm-live"><span class="dot"></span> LIVE</span>
                </div>
                @php
                    $svcCount = max($featured->categories->count(), 1);
                    $perSvc   = $featured->budget ? (float) $featured->budget / $svcCount : 0;
                @endphp
                <div class="pm-svcs">
                    @foreach($featured->categories as $i => $cat)
                        <div class="pm-svc">
                            <div class="pm-svc-ico" style="background:{{ $svcColors[$i % count($svcColors)] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
                            <div class="pm-svc-name">{{ $cat->name }}</div>
                            <div class="pm-svc-desc">{{ $cat->name }} service for this event</div>
                            <div class="pm-svc-budget-k">Budget</div>
                            <div class="pm-svc-budget">{{ $perSvc ? $money($perSvc * 0.8) . ' - ' . $money($perSvc * 1.2) : 'Open' }}</div>
                            <div class="pm-svc-bids">{{ $featured->bookings_count ?? 0 }} bids</div>
                            <a href="{{ route('professional.gigs.show', $featured) }}" class="pm-svc-bid">View &amp; Bid</a>
                        </div>
                    @endforeach
                </div>
                <div class="pm-ex-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Each service is awarded separately. You only compete for the services you choose to bid on.</div>
            @else
                <div style="padding:30px 12px;text-align:center;color:var(--text-muted);font-size:13px;">
                    No live multi-service requests right now.<br>When a client posts an event needing multiple services, it'll appear here for you to bid on.
                </div>
            @endif
        </div>

        <div class="pm-side">
            <div class="pm-side-card">
                <h4>Multi-Service Requests <span class="pm-new">NEW</span></h4>
                <p class="sub">A smarter way for clients to plan. A bigger opportunity for pros like you.</p>
                @foreach(['One event posting','Multiple services','Multiple professionals','Each gig separate','Maximum efficiency','Maximum wins'] as $chk)
                    <div class="pm-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg>{{ $chk }}</div>
                @endforeach
            </div>
            <div class="pm-side-card">
                <h4>Need Help?</h4>
                <p class="sub">Learn how to get the most out of Multi-Service Requests.</p>
                <a href="{{ route('professional.chat.index') }}" class="pm-help-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Contact Support</a>
            </div>
        </div>
    </div>

    <div class="pm-banner green">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
        <p><b>Each service is awarded separately.</b> You only compete for the services you choose to bid on.</p>
    </div>

    {{-- ════════ Why Professionals Love It ════════ --}}
    <h3 class="pm-sec-title">Why Professionals Love Multi-Service Requests</h3>
    <div class="pm-why">
        @php
            $why = [
                ['star','More Opportunities','One event can mean multiple gigs for you.'],
                ['zap','Bid Your Strengths','Choose only the services you specialize in.'],
                ['grid','Better Organization','Each gig is tracked, managed, and paid separately.'],
                ['scale','Fair & Transparent','No overlapping bids. No system confusion.'],
                ['trend','Grow Your Business','Win more gigs across more events.'],
            ];
        @endphp
        @foreach($why as $w)
            <div class="pm-why-card">
                <div class="pm-why-ico">
                    @switch($w[0])
                        @case('zap') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> @break
                        @case('grid') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> @break
                        @case('scale') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 7h18M6 7l-3 6h6zM18 7l-3 6h6z"/></svg> @break
                        @case('trend') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg> @break
                        @default <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endswitch
                </div>
                <h5>{{ $w[1] }}</h5>
                <p>{{ $w[2] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ════════ Recent Multi-Service Requests ════════ --}}
    <div class="pm-card">
        <div class="pm-rt-head">
            <h3>Recent Multi-Service Requests</h3>
            <a href="{{ route('professional.gigs.index') }}">View All ({{ $liveCount }}) →</a>
        </div>
        <div class="pm-tbl-wrap">
        <table class="pm-tbl">
            <thead><tr><th>Event / Client</th><th>Date</th><th>Location</th><th>Services Requested</th><th>Total Budget</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($recent as $ev)
                    <tr>
                        <td>
                            <div class="pm-rt-ev">
                                <span class="pm-rt-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                                <div style="min-width:0;"><div class="pm-rt-name">{{ \Illuminate\Support\Str::limit($ev->title, 22) }}</div><div class="pm-rt-client">by {{ $ev->client?->name ?? 'Client' }}</div></div>
                            </div>
                        </td>
                        <td style="white-space:nowrap;">{{ $ev->starts_at?->format('M d, Y') ?? '—' }}</td>
                        <td style="white-space:nowrap;">{{ $ev->location ? \Illuminate\Support\Str::limit($ev->location, 14) : '—' }}</td>
                        <td>
                            <span class="pm-svc-pills">
                                @foreach($ev->categories->take(2) as $cat)
                                    <span class="pm-svc-pill" title="{{ $cat->name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/></svg></span>
                                @endforeach
                                @if($ev->categories_count > 2)<span class="pm-svc-more">+{{ $ev->categories_count - 2 }} more</span>@endif
                            </span>
                        </td>
                        <td style="white-space:nowrap;font-weight:700;">{{ $ev->budget ? $money($ev->budget) : 'Open' }}</td>
                        <td><a href="{{ route('professional.gigs.show', $ev) }}" class="pm-rt-bid">View &amp; Bid</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div style="padding:24px 12px;text-align:center;color:var(--text-muted);font-size:13px;">No multi-service requests yet. When clients post events needing multiple services, they'll show up here.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($recent->count())<div style="font-size:11px;color:#10b981;font-weight:700;margin-top:10px;">● Live feed updates in real-time</div>@endif
    </div>

    {{-- ════════ Bottom CTA ════════ --}}
    <div class="pm-cta">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
        <div class="pm-cta-txt"><b>Our system ensures every service stays completely separate.</b><p>You bid. You win. You deliver. All organized — all in one place.</p></div>
        <a href="{{ route('professional.gigs.index') }}" class="pm-cta-btn">Start Bidding Now <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
