@extends('layouts.client')

@section('title', 'Virtual & Hybrid Event Brief')
@section('page-title', 'Virtual & Hybrid Event Brief')
@section('page-subtitle', 'Post your event details and technical requirements. Qualified professionals will submit their bids.')

@push('styles')
<style>
    /* ═══════════ Virtual & Hybrid Event Brief — posting form ═══════════
       Matches the client's "Virtual & Hybrid Event Brief" mockup:
       a 4-section gig brief (Event Details · Technical Environment ·
       Production & Staffing · Budget & Bidding) with a 3-step header.
       UI scaffold — persistence + bidding backend is a follow-up. */

    /* Step header */
    .vhb-stepper { display: flex; align-items: center; gap: 0; margin-bottom: 22px; padding: 4px 2px; }
    .vhb-step { display: inline-flex; align-items: center; gap: 11px; flex-shrink: 0; }
    .vhb-step-num {
        width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; flex-shrink: 0;
        background: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-muted);
    }
    .vhb-step-label { font-size: 13px; font-weight: 700; color: var(--text-muted); white-space: nowrap; }
    .vhb-step.is-active .vhb-step-num { background: #f97316; border-color: #f97316; color: #fff; box-shadow: 0 4px 10px rgba(249,115,22,0.35); }
    .vhb-step.is-active .vhb-step-label { color: #f97316; }
    .vhb-step.is-done .vhb-step-num { background: #f97316; border-color: #f97316; color: #fff; }
    .vhb-step-line { flex: 1; height: 2px; background: var(--border-color); margin: 0 16px; border-radius: 2px; min-width: 30px; }
    .vhb-step-line.is-active { background: #f97316; }

    /* 4-column card grid */
    .vhb-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; align-items: start; }
    .vhb-card {
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius); padding: 20px 18px; min-width: 0;
    }
    .vhb-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .vhb-card-ico {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: rgba(249,115,22,0.12); color: #f97316;
        display: flex; align-items: center; justify-content: center;
    }
    .vhb-card-ico svg { width: 20px; height: 20px; }
    .vhb-card-title { font-size: 12.5px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.3px; line-height: 1.3; }
    .vhb-card-sub { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; margin: 0 0 16px; }

    /* Fields */
    .vhb-field { margin-bottom: 14px; }
    .vhb-label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .vhb-label .req { color: #f97316; font-weight: 800; }
    .vhb-input, .vhb-select {
        width: 100%; height: 40px; padding: 0 12px;
        border-radius: 9px; border: 1px solid var(--border-color);
        background: var(--bg-card-hover); color: var(--text-primary);
        font-size: 12.5px; font-family: inherit; outline: none;
        text-overflow: ellipsis;
    }
    .vhb-input:focus, .vhb-select:focus { border-color: #f97316; }
    .vhb-input::placeholder { color: var(--text-muted); }
    .vhb-select { -webkit-appearance: none; appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%2394a3b8' stroke-width='2.5' viewBox='0 0 24 24'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; padding-right: 30px; }
    [data-theme="dark"] .vhb-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23cbd5e1' stroke-width='2.5' viewBox='0 0 24 24'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); }

    /* Input with icon(s) */
    .vhb-iwrap { position: relative; }
    .vhb-iwrap .ico-l, .vhb-iwrap .ico-r { position: absolute; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .vhb-iwrap .ico-l { left: 12px; }
    .vhb-iwrap .ico-r { right: 12px; }
    .vhb-iwrap.has-l .vhb-input { padding-left: 34px; }
    .vhb-iwrap.has-r .vhb-input { padding-right: 34px; }

    /* Sub-section heading inside a card */
    .vhb-subhead { font-size: 12px; font-weight: 700; color: var(--text-primary); margin: 2px 0 11px; line-height: 1.4; }
    .vhb-subhead .req { color: #f97316; font-weight: 800; }
    .vhb-subhead .hint { font-weight: 500; color: var(--text-muted); font-size: 11px; }

    /* Option lists (radios + checkboxes) */
    .vhb-opts { display: flex; flex-direction: column; gap: 11px; margin-bottom: 12px; }
    .vhb-opt { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; }
    .vhb-opt-text { font-size: 12px; color: var(--text-primary); line-height: 1.4; }
    .vhb-opt-text small { display: block; font-size: 11px; color: var(--text-muted); font-weight: 400; margin-top: 2px; }
    .vhb-opt-text b { font-weight: 700; }

    /* Custom orange radio + checkbox */
    .vhb-opt input[type=radio], .vhb-opt input[type=checkbox] {
        appearance: none; -webkit-appearance: none;
        width: 17px; height: 17px; flex-shrink: 0; margin: 1px 0 0;
        border: 1.5px solid var(--border-color); background: #fff;
        cursor: pointer; position: relative; transition: border-color 0.15s, background 0.15s;
    }
    .vhb-opt input[type=radio] { border-radius: 50%; }
    .vhb-opt input[type=checkbox] { border-radius: 5px; }
    .vhb-opt input[type=radio]:checked { border-color: #f97316; background: #f97316; }
    .vhb-opt input[type=radio]:checked::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 5px; height: 5px; border-radius: 50%; background: #fff; }
    .vhb-opt input[type=checkbox]:checked { border-color: #f97316; background: #f97316; }
    .vhb-opt input[type=checkbox]:checked::after { content: ''; position: absolute; left: 5px; top: 1.5px; width: 4px; height: 8px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
    .vhb-opt input:focus-visible { outline: 2px solid rgba(249,115,22,0.4); outline-offset: 1px; }
    [data-theme="dark"] .vhb-opt input[type=radio], [data-theme="dark"] .vhb-opt input[type=checkbox] { background: var(--bg-card-hover); }
    [data-theme="dark"] .vhb-opt input:checked { background: #f97316; }

    /* "Other — please specify" inline field */
    .vhb-other { margin: 2px 0 4px; }

    /* Tip / boost callout (peach) */
    .vhb-callout { display: flex; gap: 10px; background: rgba(249,115,22,0.07); border: 1px solid rgba(249,115,22,0.20); border-radius: 10px; padding: 12px 13px; }
    .vhb-callout svg { width: 16px; height: 16px; color: #f97316; flex-shrink: 0; margin-top: 1px; }
    .vhb-callout-body { font-size: 11px; color: var(--text-secondary); line-height: 1.5; }
    .vhb-callout-body b { color: var(--text-primary); font-weight: 700; }
    .vhb-callout-title { display: block; font-weight: 800; color: var(--text-primary); margin-bottom: 2px; }

    /* Divider between sub-sections in a card */
    .vhb-divider { height: 1px; background: var(--border-color); margin: 16px 0; }

    /* Budget row */
    .vhb-budget-row { display: flex; align-items: center; gap: 8px; }
    .vhb-budget-row .vhb-select { width: 84px; flex-shrink: 0; padding-left: 10px; }
    .vhb-budget-row .vhb-input { text-align: left; }
    .vhb-budget-row .to { font-size: 12px; color: var(--text-muted); flex-shrink: 0; }

    /* Dual-thumb budget slider (visual mock) */
    .vhb-range { position: relative; height: 18px; margin: 14px 2px 0; }
    .vhb-range-track { position: absolute; top: 50%; left: 0; right: 0; height: 4px; transform: translateY(-50%); background: var(--border-color); border-radius: 999px; }
    .vhb-range-fill { position: absolute; top: 50%; height: 4px; transform: translateY(-50%); background: #f97316; border-radius: 999px; }
    .vhb-range-thumb { position: absolute; top: 50%; width: 16px; height: 16px; transform: translate(-50%,-50%); background: #f97316; border: 3px solid var(--bg-card); border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.20); }
    .vhb-range-marks { display: flex; justify-content: space-between; font-size: 10.5px; color: var(--text-muted); margin-top: 6px; padding: 0 2px; }

    /* Footer actions */
    .vhb-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 22px; }
    .vhb-btn { padding: 11px 22px; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: 1px solid var(--border-color); }
    .vhb-btn svg { width: 15px; height: 15px; }
    .vhb-btn.ghost { background: var(--bg-card); color: var(--text-primary); }
    .vhb-btn.ghost:hover { background: var(--bg-card-hover); }
    .vhb-btn.primary { background: #f97316; color: #fff; border-color: #f97316; }
    .vhb-btn.primary:hover { background: #ea580c; }

    @media (max-width: 1280px) { .vhb-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 720px)  { .vhb-grid { grid-template-columns: 1fr; } .vhb-stepper { flex-wrap: wrap; gap: 10px 0; } .vhb-step-line { min-width: 16px; margin: 0 8px; } }
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('client.virtual-hub.store') }}">
    @csrf
    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:11px 15px;margin-bottom:16px;font-size:13.5px;font-weight:600;">{{ $errors->first() }}</div>
    @endif

    {{-- ════════ Step header ════════ --}}
    <div class="vhb-stepper">
        <div class="vhb-step is-active">
            <span class="vhb-step-num">1</span>
            <span class="vhb-step-label">1. Event Details</span>
        </div>
        <span class="vhb-step-line is-active"></span>
        <div class="vhb-step">
            <span class="vhb-step-num">2</span>
            <span class="vhb-step-label">2. Production Reqs</span>
        </div>
        <span class="vhb-step-line"></span>
        <div class="vhb-step">
            <span class="vhb-step-num">3</span>
            <span class="vhb-step-label">3. Budget &amp; Bidding</span>
        </div>
    </div>

    {{-- ════════ 4 section cards ════════ --}}
    <div class="vhb-grid">

        {{-- ─── 1. EVENT DETAILS ─── --}}
        <div class="vhb-card">
            <div class="vhb-card-head">
                <span class="vhb-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span class="vhb-card-title">1. Event Details</span>
            </div>
            <p class="vhb-card-sub">Tell us about your virtual or hybrid event.</p>

            <div class="vhb-field">
                <label class="vhb-label">Project Title <span class="req">*</span></label>
                <input type="text" name="title" class="vhb-input" value="{{ old('title', 'Technical Director for 3-Day Hybrid Crypto Summit') }}" required>
            </div>
            <div class="vhb-field">
                <label class="vhb-label">Event Type <span class="req">*</span></label>
                <select name="event_type" class="vhb-select">
                    <option>Hybrid Event (Physical + Virtual)</option>
                    <option>Fully Virtual Event</option>
                    <option>Livestream / Broadcast</option>
                    <option>Webinar / Conference</option>
                </select>
            </div>
            <div class="vhb-field">
                <label class="vhb-label">Event Date <span class="req">*</span></label>
                <div class="vhb-iwrap has-l has-r">
                    <svg class="ico-l" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <input type="text" name="event_date" class="vhb-input" value="{{ old('event_date', 'Oct 25, 2024') }}">
                    <svg class="ico-r" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="vhb-field">
                <label class="vhb-label">Start Time <span class="req">*</span></label>
                <div class="vhb-iwrap has-r">
                    <input type="text" class="vhb-input" value="09:00 AM">
                    <svg class="ico-r" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="vhb-field">
                <label class="vhb-label">End Time <span class="req">*</span></label>
                <div class="vhb-iwrap has-r">
                    <input type="text" class="vhb-input" value="06:00 PM">
                    <svg class="ico-r" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="vhb-field">
                <label class="vhb-label">Time Zone <span class="req">*</span></label>
                <select class="vhb-select">
                    <option selected>(GMT-05:00) Eastern Time (US &amp; Canada)</option>
                    <option>(GMT-06:00) Central Time (US &amp; Canada)</option>
                    <option>(GMT-07:00) Mountain Time (US &amp; Canada)</option>
                    <option>(GMT-08:00) Pacific Time (US &amp; Canada)</option>
                    <option>(GMT+00:00) UTC</option>
                </select>
            </div>

            <div class="vhb-callout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg>
                <div class="vhb-callout-body">
                    <span class="vhb-callout-title">Tip</span>
                    Accurate date, time, and timezone help vendors plan and monitor your live event successfully.
                </div>
            </div>
        </div>

        {{-- ─── 2. TECHNICAL ENVIRONMENT & SOFTWARE ─── --}}
        <div class="vhb-card">
            <div class="vhb-card-head">
                <span class="vhb-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span>
                <span class="vhb-card-title">2. Technical Environment &amp; Software</span>
            </div>
            <p class="vhb-card-sub">Select the platforms and interactive features you need.</p>

            <div class="vhb-subhead">Primary Virtual Platform <span class="req">*</span></div>
            <div class="vhb-opts">
                <label class="vhb-opt"><input type="radio" name="platform" checked><span class="vhb-opt-text">Zoom Events / Webinar</span></label>
                <label class="vhb-opt"><input type="radio" name="platform"><span class="vhb-opt-text">Microsoft Teams / Webex</span></label>
                <label class="vhb-opt"><input type="radio" name="platform"><span class="vhb-opt-text">Custom 3D Virtual Environment (Web3/Metaverse)</span></label>
                <label class="vhb-opt"><input type="radio" name="platform"><span class="vhb-opt-text">Hopin / RingCentral Events</span></label>
                <label class="vhb-opt"><input type="radio" name="platform"><span class="vhb-opt-text">Other (Please specify)</span></label>
            </div>
            <div class="vhb-other"><input type="text" class="vhb-input" placeholder=""></div>

            <div class="vhb-divider"></div>

            <div class="vhb-subhead">Interactive Features Needed <span class="hint">(Select all that apply)</span></div>
            <div class="vhb-opts">
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Live Chat &amp; Q&amp;A Moderation</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Networking Roulette / Breakout Rooms</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Virtual Expo Booths</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Gamification &amp; Live Polling</span></label>
                <label class="vhb-opt"><input type="checkbox"><span class="vhb-opt-text">Other (Please specify)</span></label>
            </div>
            <div class="vhb-other"><input type="text" class="vhb-input" placeholder=""></div>
        </div>

        {{-- ─── 3. PRODUCTION & STAFFING REQUIREMENTS ─── --}}
        <div class="vhb-card">
            <div class="vhb-card-head">
                <span class="vhb-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                <span class="vhb-card-title">3. Production &amp; Staffing Requirements</span>
            </div>
            <p class="vhb-card-sub">Choose the professional services and production support you require.</p>

            <div class="vhb-subhead">Professional Services Needed <span class="req">*</span> <span class="hint">(Select all that apply)</span></div>
            <div class="vhb-opts">
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Livestream Technical Director</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Virtual Stage / 3D Environment Architect</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Live Language Interpreter</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Hybrid AV Integrator (Physical Venue Setup)</span></label>
                <label class="vhb-opt"><input type="checkbox" checked><span class="vhb-opt-text">Chat / Engagement Moderator</span></label>
                <label class="vhb-opt"><input type="checkbox"><span class="vhb-opt-text">Video Editor / Replay Producer</span></label>
                <label class="vhb-opt"><input type="checkbox"><span class="vhb-opt-text">Other (Please specify)</span></label>
            </div>
            <div class="vhb-other"><input type="text" class="vhb-input" placeholder=""></div>

            <div class="vhb-divider"></div>

            <div class="vhb-subhead">Physical Venue Details <span class="hint">(Only for Hybrid Events)</span></div>
            <div class="vhb-field">
                <label class="vhb-label">Venue Name &amp; Location</label>
                <input type="text" name="location" class="vhb-input" value="{{ old('location') }}" placeholder="e.g., TechCenter, San Francisco, CA">
            </div>
            <div class="vhb-field">
                <label class="vhb-label">On-Site Internet Speed <span class="hint">(if known)</span></label>
                <select class="vhb-select">
                    <option selected disabled value="">Select speed type</option>
                    <option>Standard (up to 100 Mbps)</option>
                    <option>High-Speed (100–500 Mbps)</option>
                    <option>Enterprise / Dedicated Fiber (1 Gbps+)</option>
                </select>
            </div>
        </div>

        {{-- ─── 4. BUDGET & BIDDING PREFERENCES ─── --}}
        <div class="vhb-card">
            <div class="vhb-card-head">
                <span class="vhb-card-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="vhb-card-title">4. Budget &amp; Bidding Preferences</span>
            </div>
            <p class="vhb-card-sub">Set your budget range and bidding preferences.</p>

            <div class="vhb-field">
                <label class="vhb-label">Estimated Production Budget <span class="req">*</span></label>
                <div class="vhb-budget-row">
                    <select class="vhb-select"><option selected>USD $</option><option>EUR €</option><option>GBP £</option></select>
                    <input type="text" name="budget_min" class="vhb-input" value="{{ old('budget_min', '$5,000') }}">
                    <span class="to">to</span>
                    <input type="text" name="budget_max" class="vhb-input" value="{{ old('budget_max', '$15,000') }}">
                </div>
                <div class="vhb-range">
                    <div class="vhb-range-track"></div>
                    <div class="vhb-range-fill" style="left:0%; right:0%;"></div>
                    <div class="vhb-range-thumb" style="left:0%;"></div>
                    <div class="vhb-range-thumb" style="left:100%;"></div>
                </div>
                <div class="vhb-range-marks"><span>$5,000</span><span>$10,000</span><span>$15,000</span></div>
            </div>

            <div class="vhb-divider"></div>

            <div class="vhb-subhead">Bidding Model <span class="req">*</span></div>
            <div class="vhb-opts">
                <label class="vhb-opt"><input type="radio" name="bidding" checked><span class="vhb-opt-text"><b>Open Bidding</b><small>All professionals can see competitor bids to encourage competitive rates.</small></span></label>
                <label class="vhb-opt"><input type="radio" name="bidding"><span class="vhb-opt-text"><b>Blind Bidding</b><small>Vendor bids stay private; only the planner can see them.</small></span></label>
            </div>

            <div class="vhb-divider"></div>

            <div class="vhb-field">
                <label class="vhb-label">Gig Expiration Date <span class="req">*</span></label>
                <div class="vhb-iwrap has-l has-r">
                    <svg class="ico-l" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <input type="text" class="vhb-input" value="Oct 31, 2024">
                    <svg class="ico-r" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>

            <div class="vhb-subhead" style="margin-top:6px;">AI Gig Boost</div>
            <div class="vhb-callout">
                <label class="vhb-opt" style="gap:10px; align-items:flex-start;">
                    <input type="checkbox" checked>
                    <span class="vhb-callout-body">
                        <b>Enable Smart Match:</b> Instantly notify the top 5 rated virtual professionals matching these requirements to submit a bid.
                        <span style="display:block; color:var(--text-muted); margin-top:3px;">(Boosts engagement instantly)</span>
                    </span>
                </label>
            </div>
        </div>

    </div>

    {{-- ════════ Footer actions ════════ --}}
    <div class="vhb-actions">
        <a href="{{ route('client.virtual-hub.index') }}" class="vhb-btn ghost">Save Draft</a>
        <button type="submit" class="vhb-btn primary">
            Review &amp; Post Gig
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
    </div>

</form>
@endsection
