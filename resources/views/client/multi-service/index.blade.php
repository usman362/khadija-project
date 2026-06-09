@extends('layouts.client')

@section('title', 'Multi-Service Request')
@section('page-title', 'Multi-Service Request for Proposal')
@section('page-subtitle', 'One event. Multiple services. Better matches — get competitive bids in one brief.')

@push('styles')
<style>
    /* ═══════════════════ Multi-Service RFP wizard ═══════════════════
       Matches Khadija's "Multi-Service Request for Proposal" mockup.
       UI scaffold — the 4-step RFP brief form. Persistence + bidding
       backend is a follow-up (RfpBrief / RfpServiceLine / RfpBid models). */
    .ms-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .ms-main { min-width: 0; }
    .ms-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .ms-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 18px 20px; margin-bottom: 16px; }

    /* Hero */
    .ms-hero { display: flex; gap: 18px; align-items: center; background: linear-gradient(135deg, rgba(249,115,22,0.06), rgba(139,92,246,0.06)); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 20px; margin-bottom: 16px; }
    .ms-hero-body { flex: 1; }
    .ms-hero h2 { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0 0 6px; }
    .ms-hero h2 span { color: #f97316; }
    .ms-hero p { font-size: 13px; color: var(--text-muted); margin: 0 0 14px; }
    .ms-hero-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #f97316; color: #fff; border: none; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .ms-hero-art { width: 90px; height: 90px; border-radius: 14px; background: rgba(249,115,22,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ms-hero-art svg { width: 44px; height: 44px; color: #f97316; }

    /* 4-step process */
    .ms-process-title { font-size: 14px; font-weight: 800; color: var(--text-primary); text-align: center; margin-bottom: 14px; }
    .ms-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .ms-step { border-radius: 12px; padding: 16px 14px; color: #fff; position: relative; }
    .ms-step.s1 { background: linear-gradient(135deg, #10b981, #059669); }
    .ms-step.s2 { background: linear-gradient(135deg, #f97316, #ea580c); }
    .ms-step.s3 { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .ms-step.s4 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .ms-step-num { width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; margin-bottom: 10px; }
    .ms-step-name { font-size: 13px; font-weight: 800; margin-bottom: 4px; }
    .ms-step-desc { font-size: 11px; opacity: 0.92; line-height: 1.4; }

    /* Wizard step indicator */
    .ms-wizbar { display: flex; align-items: center; gap: 6px; margin-bottom: 18px; flex-wrap: wrap; }
    .ms-wizstep { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .ms-wizstep .n { width: 22px; height: 22px; border-radius: 50%; background: var(--border-color); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
    .ms-wizstep.active { color: #f97316; }
    .ms-wizstep.active .n { background: #f97316; color: #fff; }
    .ms-wizstep.done .n { background: #10b981; color: #fff; }
    .ms-wizline { flex: 1; height: 1px; background: var(--border-color); min-width: 14px; }

    /* Form */
    .ms-section-label { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ms-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .ms-grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .ms-field label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 5px; }
    .ms-field label .req { color: #ef4444; }
    .ms-input, .ms-select, .ms-textarea {
        width: 100%; height: 42px; padding: 0 12px;
        border-radius: 9px; border: 1px solid var(--border-color);
        background: var(--bg-card-hover); color: var(--text-primary);
        font-size: 13px; font-family: inherit; outline: none;
    }
    .ms-input:focus, .ms-select:focus, .ms-textarea:focus { border-color: #f97316; }
    .ms-textarea { height: auto; padding: 10px 12px; resize: vertical; min-height: 76px; }
    .ms-input::placeholder, .ms-textarea::placeholder { color: var(--text-muted); }

    /* Service selection grid */
    .ms-services { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
    .ms-service { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--bg-card-hover); cursor: pointer; transition: border-color 0.15s; }
    .ms-service:has(input:checked) { border-color: #10b981; background: rgba(16,185,129,0.06); }
    .ms-service input { accent-color: #10b981; width: 16px; height: 16px; }
    .ms-service-body { min-width: 0; }
    .ms-service-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .ms-service-sub { font-size: 10.5px; color: var(--text-muted); }

    /* Service details preview table */
    .ms-detail-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 8px; }
    .ms-detail-table th { text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .ms-detail-table td { padding: 9px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .ms-detail-table .yes { color: #10b981; font-weight: 700; }
    .ms-edit-link { color: #f97316; font-weight: 600; text-decoration: none; font-size: 11px; }

    .ms-form-actions { display: flex; justify-content: space-between; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
    .ms-btn { padding: 10px 18px; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); display: inline-flex; align-items: center; gap: 7px; text-decoration: none; }
    .ms-btn svg { width: 14px; height: 14px; }
    .ms-btn.green { background: #10b981; color: #fff; border-color: #10b981; }
    .ms-btn.coral { background: #f97316; color: #fff; border-color: #f97316; }

    /* Right rail */
    .ms-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .ms-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
    .ms-hiw-row { display: flex; gap: 10px; padding: 8px 0; }
    .ms-hiw-num { width: 22px; height: 22px; border-radius: 50%; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ms-hiw-row:nth-child(2) .ms-hiw-num { background: #10b981; }
    .ms-hiw-row:nth-child(3) .ms-hiw-num { background: #f97316; }
    .ms-hiw-row:nth-child(4) .ms-hiw-num { background: #ef4444; }
    .ms-hiw-row:nth-child(5) .ms-hiw-num { background: #8b5cf6; }
    .ms-hiw-body { font-size: 11.5px; color: var(--text-secondary); line-height: 1.4; }
    .ms-hiw-body b { color: var(--text-primary); display: block; }
    .ms-sum-row { display: flex; justify-content: space-between; font-size: 12px; padding: 6px 0; border-bottom: 1px dashed var(--border-color); }
    .ms-sum-row:last-child { border-bottom: 0; }
    .ms-sum-row .lbl { color: var(--text-muted); }
    .ms-sum-row .val { color: var(--text-primary); font-weight: 600; text-align: right; }
    .ms-readiness-ring { width: 90px; height: 90px; margin: 0 auto 8px; }
    .ms-readiness-label { text-align: center; font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
    .ms-check-row { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--text-secondary); padding: 4px 0; }
    .ms-check-row svg { width: 13px; height: 13px; color: #10b981; }
    .ms-ai-row { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: #6366f1; padding: 6px 0; cursor: pointer; }
    .ms-ai-row svg { width: 13px; height: 13px; }
    .ms-risk-row { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--text-secondary); padding: 6px 0; border-bottom: 1px dashed var(--border-color); }
    .ms-risk-row:last-child { border-bottom: 0; }
    .ms-risk-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

    @media (max-width: 1200px) { .ms-layout { grid-template-columns: 1fr; } .ms-rail { position: static; } }
    @media (max-width: 800px) { .ms-steps { grid-template-columns: repeat(2, 1fr); } .ms-services { grid-template-columns: repeat(2, 1fr); } .ms-grid3, .ms-grid2 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ms-layout">
<div class="ms-main">

    {{-- Hero --}}
    <div class="ms-hero">
        <div class="ms-hero-body">
            <h2>Multi-Service <span>Request</span> for Proposal</h2>
            <p>One event. Multiple services. Better matches. Get competitive bids from the right professionals for each part of your event.</p>
            <a href="#ms-brief" class="ms-hero-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create New Event Brief</a>
        </div>
        <div class="ms-hero-art"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 2h6a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1V4a2 2 0 0 1 2-2z"/><path d="M9 12l2 2 4-4"/></svg></div>
    </div>

    {{-- 4-step process --}}
    <div class="ms-process-title">The 4-Step RFP Process</div>
    <div class="ms-steps">
        <div class="ms-step s1"><div class="ms-step-num">1</div><div class="ms-step-name">Master Form</div><div class="ms-step-desc">Fill out one event brief to start your event.</div></div>
        <div class="ms-step s2"><div class="ms-step-num">2</div><div class="ms-step-name">Select Services</div><div class="ms-step-desc">Choose the services you need from categories.</div></div>
        <div class="ms-step s3"><div class="ms-step-num">3</div><div class="ms-step-name">Service Details</div><div class="ms-step-desc">Provide questions and specific measurements.</div></div>
        <div class="ms-step s4"><div class="ms-step-num">4</div><div class="ms-step-name">Match &amp; Bidding</div><div class="ms-step-desc">We verify and match pros and you receive bids.</div></div>
    </div>

    {{-- Wizard form --}}
    <div class="ms-card" id="ms-brief">
        <div class="ms-section-label">Create New Master Event Brief</div>
        <div class="ms-wizbar">
            <div class="ms-wizstep active"><span class="n">1</span>Event Overview</div>
            <span class="ms-wizline"></span>
            <div class="ms-wizstep"><span class="n">2</span>Services Needed</div>
            <span class="ms-wizline"></span>
            <div class="ms-wizstep"><span class="n">3</span>Service Details</div>
            <span class="ms-wizline"></span>
            <div class="ms-wizstep"><span class="n">4</span>Match &amp; Submit</div>
        </div>

        {{-- Step 1: Event Overview --}}
        <div class="ms-grid2">
            <div class="ms-field"><label>Event Name <span class="req">*</span></label><input class="ms-input" placeholder="e.g. Annual Gala Dinner" value="{{ $activeEvent->title ?? '' }}"></div>
            <div class="ms-field"><label>Event Type <span class="req">*</span></label><select class="ms-select"><option>Select event type</option><option>Wedding</option><option>Corporate</option><option>Birthday</option><option>Conference</option><option>Concert</option></select></div>
        </div>
        <div class="ms-grid3">
            <div class="ms-field"><label>Event Date <span class="req">*</span></label><input type="date" class="ms-input" value="{{ $activeEvent->starts_at?->format('Y-m-d') ?? '' }}"></div>
            <div class="ms-field"><label>Start Time</label><input type="time" class="ms-input"></div>
            <div class="ms-field"><label>End Time</label><input type="time" class="ms-input"></div>
        </div>
        <div class="ms-grid2">
            <div class="ms-field"><label>Event Location <span class="req">*</span></label><input class="ms-input" placeholder="Venue or address" value="{{ $activeEvent->location ?? '' }}"></div>
            <div class="ms-field"><label>Estimated Guest Count <span class="req">*</span></label><input type="number" class="ms-input" placeholder="e.g. 300"></div>
        </div>
        <div class="ms-field" style="margin-bottom:14px;"><label>Event Description / Goals</label><textarea class="ms-textarea" placeholder="Tell us about your event, goals, theme, and any important details."></textarea></div>
        <div class="ms-grid2">
            <div class="ms-field"><label>Budget Range (Total)</label><select class="ms-select"><option>Select budget range</option><option>$5,000 – $10,000</option><option>$10,000 – $25,000</option><option>$25,000 – $50,000</option><option>$50,000+</option></select></div>
            <div class="ms-field"><label>Planning Stage</label><select class="ms-select"><option>Select planning stage</option><option>Just exploring</option><option>Actively planning</option><option>Ready to book</option></select></div>
        </div>

        {{-- Step 2: Services --}}
        <div class="ms-section-label" style="margin-top:18px;">Select the Services You Need</div>
        <div class="ms-services">
            @php
                $defaultServices = [
                    ['Catering', 'Food & beverage'], ['Audio Visual (AV)', 'Sound & lighting'], ['Decor & Design', 'Theme & styling'],
                    ['Photography', 'Event photography'], ['Videography', 'Event videography'], ['Venue', 'Spaces & logistics'],
                    ['Entertainment', 'Live band, DJ'], ['Staffing', 'Bartenders, security'], ['Transportation', 'Shuttles & cars'],
                ];
                $svcList = $categories->count() ? $categories->map(fn($c) => [$c->name, ''])->toArray() : $defaultServices;
            @endphp
            @foreach($svcList as $i => [$svc, $sub])
                <label class="ms-service">
                    <input type="checkbox" name="services[]" value="{{ $svc }}" {{ $i < 4 ? 'checked' : '' }}>
                    <div class="ms-service-body">
                        <div class="ms-service-name">{{ $svc }}</div>
                        @if($sub)<div class="ms-service-sub">{{ $sub }}</div>@endif
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Step 3: Service Details Preview --}}
        <div class="ms-section-label" style="margin-top:18px;">Service Details <span style="font-weight:500;color:var(--text-muted);font-size:11px;">(Preview)</span></div>
        <table class="ms-detail-table">
            <thead><tr><th>Service</th><th>Key Details</th><th>Budget Range</th><th>Needed</th><th></th></tr></thead>
            <tbody>
                <tr><td><b style="color:var(--text-primary);">Catering</b></td><td>Plated, dietary needs</td><td>$8,000 – $12,000</td><td class="yes">Yes</td><td><a href="#" class="ms-edit-link">Edit</a></td></tr>
                <tr><td><b style="color:var(--text-primary);">Audio Visual (AV)</b></td><td>Lighting, sound system, screens</td><td>$3,000 – $6,000</td><td class="yes">Yes</td><td><a href="#" class="ms-edit-link">Edit</a></td></tr>
                <tr><td><b style="color:var(--text-primary);">Decor &amp; Design</b></td><td>Theme: Elegant Black &amp; Gold</td><td>$4,000 – $8,000</td><td class="yes">Yes</td><td><a href="#" class="ms-edit-link">Edit</a></td></tr>
                <tr><td><b style="color:var(--text-primary);">Photography</b></td><td>Full coverage, group photos</td><td>$2,000 – $3,000</td><td class="yes">Yes</td><td><a href="#" class="ms-edit-link">Edit</a></td></tr>
            </tbody>
        </table>

        <div class="ms-form-actions">
            <button class="ms-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>Back</button>
            <div style="display:flex;gap:8px;">
                <button class="ms-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Save Draft</button>
                <button class="ms-btn coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>Next: Review &amp; Submit</button>
            </div>
        </div>
    </div>
</div>{{-- /.ms-main --}}

{{-- Right rail --}}
<aside class="ms-rail">
    <div class="ms-rail-card">
        <div class="ms-rail-title">How It Works</div>
        <div class="ms-hiw-row"><span class="ms-hiw-num" style="background:#6366f1;">1</span><div class="ms-hiw-body"><b>Create a Master Event Brief</b>Fill out your event details with one high-level form.</div></div>
        <div class="ms-hiw-row"><span class="ms-hiw-num">2</span><div class="ms-hiw-body"><b>Select Needed Services</b>Choose all the services you need from categories.</div></div>
        <div class="ms-hiw-row"><span class="ms-hiw-num">3</span><div class="ms-hiw-body"><b>Provide Service Details</b>Answer specific questions for selected services.</div></div>
        <div class="ms-hiw-row"><span class="ms-hiw-num">4</span><div class="ms-hiw-body"><b>Smart Matching &amp; Bidding</b>We match the right pros who bid on your event.</div></div>
    </div>

    <div class="ms-rail-card">
        <div class="ms-rail-title">Your Event Summary <a href="#" class="ms-edit-link">Edit</a></div>
        <div class="ms-sum-row"><span class="lbl">Event Title</span><span class="val">{{ \Illuminate\Support\Str::limit($activeEvent->title ?? 'Untitled', 18) }}</span></div>
        <div class="ms-sum-row"><span class="lbl">Date</span><span class="val">{{ $activeEvent->starts_at?->format('M d, Y') ?? '—' }}</span></div>
        <div class="ms-sum-row"><span class="lbl">Location</span><span class="val">{{ \Illuminate\Support\Str::limit($activeEvent->location ?? '—', 16) }}</span></div>
        <div class="ms-sum-row"><span class="lbl">Guest Count</span><span class="val">300</span></div>
        <div class="ms-sum-row"><span class="lbl">Budget Range</span><span class="val">${{ number_format($activeEvent->budget ?? 20000, 0) }}+</span></div>
        <a href="#" class="ms-edit-link" style="display:inline-block;margin-top:10px;">View Full Summary →</a>
    </div>

    <div class="ms-rail-card">
        <div class="ms-rail-title">Event Readiness</div>
        @php $readiness = 82; @endphp
        <svg class="ms-readiness-ring" viewBox="0 0 36 36">
            <path d="M18 4a14 14 0 1 1 0 28 14 14 0 0 1 0-28" fill="none" stroke="var(--border-color)" stroke-width="3"/>
            <path d="M18 4a14 14 0 1 1 0 28 14 14 0 0 1 0-28" fill="none" stroke="#10b981" stroke-width="3" stroke-dasharray="{{ $readiness }}, 100" stroke-linecap="round"/>
            <text x="18" y="17" text-anchor="middle" font-size="9" font-weight="800" fill="#10b981">{{ $readiness }}%</text>
            <text x="18" y="24" text-anchor="middle" font-size="4" fill="var(--text-muted)">Very Good</text>
        </svg>
        <div class="ms-check-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>All critical services added</div>
        <div class="ms-check-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Timeline looks good</div>
        <div class="ms-check-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Budget within range</div>
        <a href="#" class="ms-edit-link" style="display:inline-block;margin-top:8px;">View Recommendations →</a>
    </div>

    <div class="ms-rail-card">
        <div class="ms-rail-title">AI Event Assistant</div>
        <input class="ms-input" placeholder="Ask me anything..." style="height:38px;margin-bottom:10px;">
        <div class="ms-ai-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>Suggest services for my event</div>
        <div class="ms-ai-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Estimate my total budget</div>
        <div class="ms-ai-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>Improve my event description</div>
        <div class="ms-ai-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Find best matching vendors</div>
    </div>

    <div class="ms-rail-card">
        <div class="ms-rail-title">Risk Monitor <span style="font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(245,158,11,0.18);color:#d97706;">Medium Risk</span></div>
        <div class="ms-risk-row"><span class="ms-risk-dot" style="background:#f59e0b;"></span>Weather risk on event day · 40% chance of rain</div>
        <div class="ms-risk-row"><span class="ms-risk-dot" style="background:#10b981;"></span>Backup generator not added · Recommended</div>
        <div class="ms-risk-row"><span class="ms-risk-dot" style="background:#ef4444;"></span>Vendor availability tight · Book soon</div>
        <a href="#" class="ms-edit-link" style="display:inline-block;margin-top:8px;">View All Risks →</a>
    </div>
</aside>
</div>{{-- /.ms-layout --}}
@endsection
