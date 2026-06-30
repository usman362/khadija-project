@extends('layouts.client')

@section('title', 'Direct Offer / Request')
@section('page-title', 'Send a Direct Offer / Request')
@section('page-subtitle', 'Request services directly from a professional')

{{-- Client → Professional Direct Offer builder. The request type (SSR / MSR /
     ESR) reshapes the form: SSR = one service, no team; MSR = multiple services
     + team collaboration; ESR = full event scope + team. Type switching is
     pure-JS via data attributes. Sections are colour-coded: amber = your input,
     green = AI-generated. Representative submit (no backend yet). --}}

@php
    $types = [
        ['SSR', 'Single Service Request', 'One specific service from this pro — simplest request.'],
        ['MSR', 'Multiple Service Request', 'Several services + let the pro bring a vetted team.'],
        ['ESR', 'Event-wide Service Request', 'Hand off the full event scope to one lead professional.'],
    ];
@endphp

@push('styles')
<style>
    .do { --do: #f97316; --do-strong: #ea580c; --ai: #16a34a; max-width: 920px; margin: 0 auto; }

    /* request type selector */
    .do-types { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 22px; }
    .do-type { border: 2px solid var(--border-color); border-radius: 14px; padding: 15px; cursor: pointer; transition: all .15s; background: var(--bg-card); }
    .do-type:hover { border-color: var(--do); }
    .do-type.sel { border-color: var(--do); background: rgba(249,115,22,.07); }
    .do-type-code { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--do-strong); background: rgba(249,115,22,.12); padding: 3px 10px; border-radius: 999px; }
    .do-type h5 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 9px 0 5px; }
    .do-type p { font-size: 12px; color: var(--text-muted); line-height: 1.45; }

    .do-sec { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); margin-bottom: 14px; overflow: hidden; }
    .do-sec-hd { display: flex; align-items: center; gap: 9px; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    .do-sec.req .do-sec-hd { background: rgba(249,115,22,.06); }
    .do-sec.ai .do-sec-hd { background: rgba(22,163,74,.07); }
    .do-sec-hd h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .do-tag { margin-left: auto; font-size: 9.5px; font-weight: 800; letter-spacing: .3px; padding: 3px 9px; border-radius: 999px; color: #fff; }
    .do-sec.req .do-tag { background: var(--do); }
    .do-sec.ai .do-tag { background: var(--ai); }
    .do-sec-bd { padding: 15px 16px; }

    .do-field { margin-bottom: 13px; }
    .do-field:last-child { margin-bottom: 0; }
    .do-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .do-input { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .do-input:focus { outline: none; border-color: var(--do); }
    textarea.do-input { resize: vertical; min-height: 70px; }
    .do-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .do-row > div { flex: 1; min-width: 150px; }

    .do-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .do-chip { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 9px 13px; font-size: 13px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; }
    .do-chip.sel { border-color: var(--do); background: rgba(249,115,22,.1); color: var(--do-strong); }
    .do-chip .tick { display: none; } .do-chip.sel .tick { display: inline; }

    /* pro card */
    .do-pro { display: flex; align-items: center; gap: 12px; }
    .do-pro-av { width: 46px; height: 46px; border-radius: 12px; object-fit: cover; }
    .do-pro-main h5 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .do-pro-main p { font-size: 12px; color: var(--text-muted); }

    .do-ai-row { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--text-secondary); padding: 5px 0; }
    .do-ai-row .ck { color: var(--ai); font-weight: 800; flex-shrink: 0; }

    .do-hint { font-size: 11.5px; color: var(--do-strong); background: rgba(249,115,22,.08); border-radius: 8px; padding: 7px 11px; margin-top: 10px; }

    .do-foot { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 18px; flex-wrap: wrap; }
    .do-foot p { font-size: 12.5px; color: var(--text-muted); }
    .do-btn { border: none; border-radius: 12px; padding: 13px 26px; font-size: 14.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--do), var(--do-strong)); cursor: pointer; display: inline-flex; align-items: center; gap: 9px; }
    .do-btn svg { width: 17px; height: 17px; }

    /* hide sections not valid for the current type */
    .do[data-type="SSR"] [data-types]:not([data-types~="SSR"]),
    .do[data-type="MSR"] [data-types]:not([data-types~="MSR"]),
    .do[data-type="ESR"] [data-types]:not([data-types~="ESR"]) { display: none; }
    .do[data-type="SSR"] .do-svc-multi { display: none; }
    .do:not([data-type="SSR"]) .do-svc-single { display: none; }

    @media (max-width: 760px) { .do-types { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="do" data-type="{{ $type }}" id="doRoot">

    {{-- Request type --}}
    <div class="do-types">
        @foreach($types as [$code, $name, $desc])
            <div class="do-type {{ $type === $code ? 'sel' : '' }}" data-type="{{ $code }}">
                <span class="do-type-code">{{ $code }}</span>
                <h5>{{ $name }}</h5>
                <p>{{ $desc }}</p>
            </div>
        @endforeach
    </div>

    <form method="POST" action="#" onsubmit="alert('Demo: direct offer sent to professional');return false;">
        @csrf
        <input type="hidden" name="request_type" id="doType" value="{{ $type }}">

        {{-- Choose Professional --}}
        <div class="do-sec req">
            <div class="do-sec-hd"><h4>Choose Professional</h4><span class="do-tag">YOUR INPUT</span></div>
            <div class="do-sec-bd">
                @if($selectedPro)
                    <div class="do-pro" style="margin-bottom:12px;">
                        <img class="do-pro-av" src="{{ $selectedPro->avatar_url }}" alt="">
                        <div class="do-pro-main">
                            <h5>{{ $selectedPro->name }}</h5>
                            <p>{{ $selectedPro->profile->headline ?? 'Event Professional' }}@if($selectedPro->reviews_avg) · ★ {{ number_format($selectedPro->reviews_avg,1) }}@endif</p>
                        </div>
                    </div>
                @endif
                <div class="do-field">
                    <label>Send to</label>
                    <select name="professional_id" class="do-input">
                        @foreach($pros as $p)
                            <option value="{{ $p->id }}" @selected($selectedPro && $selectedPro->id === $p->id)>{{ $p->name }} — {{ $p->profile->headline ?? 'Professional' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Event Details --}}
        <div class="do-sec req">
            <div class="do-sec-hd"><h4>Event Details</h4><span class="do-tag">YOUR INPUT</span></div>
            <div class="do-sec-bd">
                <div class="do-field"><label>Event Name</label><input class="do-input" name="event_name" placeholder="e.g. Luxury Wedding Reception"></div>
                <div class="do-row">
                    <div class="do-field"><label>Event Date</label><input type="date" class="do-input" name="event_date"></div>
                    <div class="do-field"><label>Guest Count</label><input type="number" class="do-input" name="guests" placeholder="150"></div>
                </div>
                <div class="do-field"><label>Venue / Location</label><input class="do-input" name="venue" placeholder="The Grand Garden Estate, Chicago, IL"></div>
            </div>
        </div>

        {{-- Service Needs (adapts by type) --}}
        <div class="do-sec req">
            <div class="do-sec-hd">
                <h4>Service Needs</h4>
                <span class="do-tag">YOUR INPUT</span>
            </div>
            <div class="do-sec-bd">
                {{-- SSR: single service --}}
                <div class="do-svc-single">
                    <div class="do-field">
                        <label>Service requested</label>
                        <select class="do-input" name="service_single">
                            @foreach($categories as $cat)<option>{{ $cat->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="do-hint">SSR — a single, specific service. No team collaboration needed.</div>
                </div>
                {{-- MSR / ESR: multiple services --}}
                <div class="do-svc-multi">
                    <div class="do-field">
                        <label>Services requested (pick all that apply)</label>
                        <div class="do-chips">
                            @foreach($categories as $cat)
                                <label class="do-chip"><input type="checkbox" name="services[]" value="{{ $cat->id }}" hidden><span>{{ $cat->name }}</span><span class="tick">✓</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="do-hint" data-types="ESR">ESR — the lead professional coordinates the entire event scope across all selected services.</div>
                </div>
            </div>
        </div>

        {{-- Budget & Payment --}}
        <div class="do-sec req">
            <div class="do-sec-hd"><h4>Budget &amp; Payment</h4><span class="do-tag">YOUR INPUT</span></div>
            <div class="do-sec-bd">
                <div class="do-row">
                    <div class="do-field"><label>Budget Range (min)</label><input type="number" class="do-input" name="budget_min" placeholder="7000"></div>
                    <div class="do-field"><label>Budget Range (max)</label><input type="number" class="do-input" name="budget_max" placeholder="8500"></div>
                </div>
                <div class="do-field"><label>Preferred Payment</label>
                    <select class="do-input" name="payment"><option>Deposit + balance before event</option><option>Milestone payments</option><option>Full on completion</option></select>
                </div>
            </div>
        </div>

        {{-- Team Collaboration (MSR / ESR only) --}}
        <div class="do-sec req" data-types="MSR ESR">
            <div class="do-sec-hd"><h4>Team Collaboration</h4><span class="do-tag">MSR / ESR</span></div>
            <div class="do-sec-bd">
                <div class="do-field"><label>Allow the pro to bring a vetted team?</label>
                    <select class="do-input" name="allow_team"><option>Yes — they may subcontract trusted pros</option><option>No — only this professional</option></select>
                </div>
                <div class="do-field"><label>Max additional professionals</label>
                    <select class="do-input" name="max_pros"><option>Up to 3</option><option>Up to 5</option><option>No limit</option></select>
                </div>
            </div>
        </div>

        {{-- AI Summary (green) --}}
        <div class="do-sec ai">
            <div class="do-sec-hd"><h4>AI Request Summary</h4><span class="do-tag">AI GENERATED</span></div>
            <div class="do-sec-bd">
                <div class="do-ai-row"><span class="ck">✓</span> AI drafts a clear, structured request from your inputs so the pro understands scope instantly.</div>
                <div class="do-ai-row"><span class="ck">✓</span> Suggests a fair budget band based on your services, location and guest count.</div>
                <div class="do-ai-row" data-types="MSR ESR"><span class="ck">✓</span> Recommends which roles the lead pro should staff for this <b id="doTypeLbl">{{ $type }}</b>.</div>
            </div>
        </div>

        <div class="do-foot">
            <p>The professional will receive this as a <b id="doTypeLbl2">{{ $type }}</b> and can accept, counter, or ask questions.</p>
            <button type="submit" class="do-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Direct Offer
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('doRoot');
    var hidden = document.getElementById('doType');
    document.querySelectorAll('.do-type').forEach(function (card) {
        card.addEventListener('click', function () {
            var t = card.getAttribute('data-type');
            document.querySelectorAll('.do-type').forEach(function (c) { c.classList.toggle('sel', c === card); });
            root.setAttribute('data-type', t);
            hidden.value = t;
            document.querySelectorAll('#doTypeLbl, #doTypeLbl2').forEach(function (el) { el.textContent = t; });
        });
    });
    // chip toggles
    document.querySelectorAll('.do-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var cb = chip.querySelector('input'); cb.checked = !cb.checked; chip.classList.toggle('sel', cb.checked);
        });
    });
})();
</script>
@endpush
