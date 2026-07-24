@extends($aiLayout ?? 'layouts.client')

@section('title', 'Staffing Planner')
@section('page-title', 'Staffing Planner')
@section('page-subtitle', 'Plan the perfect team. At the right time. Every time.')

{{-- Staffing Planner — deterministic, dynamic staffing planner (no LLM).
     Builds a positioned role timeline + coverage stats from event type +
     guests + start time, and regenerates live. Export/Share are client-side.
     Fully page-scoped — the shared layout is untouched. --}}

@push('styles')
<style>
    .sp { --sp: var(--brand, #2563eb); --sp-strong: var(--brand-strong, #1d4ed8); --sp-soft: var(--brand-soft, rgba(37,99,235,0.08)); padding-top: 22px; }
    .sp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px 24px; }
    .sp-mb { margin-bottom: 20px; }

    /* header */
    .sp-head { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .sp-back { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 13px; font-weight: 700; color: var(--sp); text-decoration: none; background: var(--bg-card); }
    .sp-back svg { width: 15px; height: 15px; }
    .sp-head-ico { width: 60px; height: 60px; border-radius: 17px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 8px 18px rgba(37,99,235,0.36), inset 0 1.5px 0 rgba(255,255,255,0.45); }
    .sp-head-ico svg { width: 40px; height: 40px; }
    .sp-head-txt h1 { font-size: 27px; font-weight: 800; color: var(--sp-strong); margin: 0; }
    .sp-head-txt p { font-size: 13.5px; color: var(--text-muted); margin: 2px 0 0; }

    /* event bar */
    .sp-event { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 40px; }
    .sp-ev-item { display: flex; align-items: center; gap: 10px; font-size: 14px; }
    .sp-ev-item svg { width: 18px; height: 18px; color: var(--sp); flex-shrink: 0; }
    .sp-ev-item .k { color: var(--text-muted); font-weight: 600; }
    .sp-ev-item .v { color: var(--text-primary); font-weight: 700; }

    /* timeline card */
    .sp-tl-h { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .sp-tl-h b { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .sp-toggle { display: inline-flex; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
    .sp-toggle button { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border: none; background: var(--bg-card); color: var(--text-secondary); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .sp-toggle button.on { background: var(--sp-soft); color: var(--sp); }
    .sp-toggle button svg { width: 14px; height: 14px; }

    .sp-tl-wrap { overflow-x: auto; }
    .sp-tl { min-width: 720px; }
    .sp-axis { display: grid; grid-template-columns: 188px minmax(0,1fr); margin-bottom: 6px; }
    .sp-axis-track { position: relative; height: 18px; }
    .sp-axis-track span { position: absolute; transform: translateX(-50%); font-size: 11.5px; color: var(--text-muted); font-weight: 600; white-space: nowrap; }
    .sp-axis-track span:first-child { transform: none; } .sp-axis-track span:last-child { transform: translateX(-100%); }
    .sp-row { display: grid; grid-template-columns: 188px minmax(0,1fr); align-items: center; padding: 9px 0; border-top: 1px solid var(--border-color); }
    .sp-role { display: flex; align-items: center; gap: 11px; padding-right: 12px; }
    .sp-avatar { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 800; }
    .sp-role-nm { font-size: 13.5px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .sp-role-ct { font-size: 11.5px; color: var(--text-muted); }
    .sp-track { position: relative; height: 38px; display: flex; align-items: center; }
    .sp-track::before, .sp-track::after { content: ''; position: absolute; top: -4px; bottom: -4px; width: 1px; background: var(--border-color); }
    .sp-track::before { left: 20%; } .sp-track::after { left: 60%; }
    .sp-bar { position: absolute; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; white-space: nowrap; overflow: hidden; padding: 0 8px; box-sizing: border-box; }
    .sp-menu { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; display: flex; }
    .sp-menu svg { width: 16px; height: 16px; }
    .sp-row-end { display: flex; justify-content: flex-end; }

    /* list view */
    .sp-list { width: 100%; border-collapse: collapse; }
    .sp-list th { text-align: left; font-size: 11.5px; font-weight: 700; color: var(--text-muted); padding: 0 10px 12px; border-bottom: 1px solid var(--border-color); }
    .sp-list td { font-size: 13px; color: var(--text-secondary); padding: 12px 10px; border-bottom: 1px solid var(--border-color); }
    .sp-list .role { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--text-primary); }
    .sp-list .dot { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }

    .sp-allset { display: flex; align-items: center; gap: 11px; background: var(--sp-soft); border-radius: 12px; padding: 14px 18px; margin-top: 16px; font-size: 14px; font-weight: 700; color: var(--sp); }
    .sp-allset svg { width: 18px; height: 18px; }

    /* adjust panel */
    .sp-adjust { display: none; gap: 14px; flex-wrap: wrap; align-items: flex-end; background: var(--sp-soft); border: 1px solid rgba(37,99,235,0.18); border-radius: 12px; padding: 16px 18px; margin-top: 16px; }
    .sp-adjust.open { display: flex; }
    .sp-adjust .fld { flex: 1; min-width: 130px; }
    .sp-adjust label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .sp-adjust select, .sp-adjust input { width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .sp-adjust select:focus, .sp-adjust input:focus { outline: none; border-color: var(--sp); }

    /* stats */
    .sp-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 16px; }
    .sp-stat { display: flex; gap: 12px; align-items: flex-start; }
    .sp-stat-ico { width: 40px; height: 40px; border-radius: 11px; background: var(--sp-soft); display: flex; align-items: center; justify-content: center; color: var(--sp); flex-shrink: 0; }
    .sp-stat-ico svg { width: 20px; height: 20px; }
    .sp-stat .lbl { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .sp-stat .val { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .sp-stat .val small { font-size: 12px; font-weight: 700; color: var(--text-muted); }
    .sp-stat .sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    /* how it works + quick actions */
    .sp-row2 { display: grid; grid-template-columns: minmax(0,1.7fr) minmax(0,1fr); gap: 20px; align-items: start; }
    .sp-sec-h { font-size: 17px; font-weight: 800; color: var(--sp-strong); margin: 0 0 18px; }
    .sp-steps { display: flex; align-items: flex-start; justify-content: space-between; gap: 2px; }
    .sp-step { text-align: center; flex: 1; min-width: 0; }
    .sp-step-ico { width: 52px; height: 52px; border-radius: 50%; background: var(--sp-soft); display: flex; align-items: center; justify-content: center; color: var(--sp); margin: 0 auto 9px; }
    .sp-step-ico svg { width: 24px; height: 24px; }
    .sp-step b { font-size: 11.5px; font-weight: 800; color: var(--sp); display: block; line-height: 1.3; }
    .sp-step p { font-size: 10.5px; color: var(--text-muted); margin: 6px 0 0; line-height: 1.45; }
    .sp-step-arr { display: flex; align-items: center; padding-top: 18px; color: rgba(37,99,235,0.45); flex-shrink: 0; }
    .sp-step-arr svg { width: 15px; height: 15px; }
    .sp-qa { display: flex; gap: 12px; padding: 11px 0; border-top: 1px solid var(--border-color); align-items: flex-start; width: 100%; background: none; border-left: none; border-right: none; border-bottom: none; cursor: pointer; text-align: left; font-family: inherit; color: inherit; text-decoration: none; }
    .sp-qa:first-of-type { border-top: none; }
    .sp-qa-ico { width: 34px; height: 34px; border-radius: 10px; background: var(--sp-soft); display: flex; align-items: center; justify-content: center; color: var(--sp); flex-shrink: 0; }
    .sp-qa-ico svg { width: 17px; height: 17px; }
    .sp-qa b { font-size: 13px; font-weight: 800; color: var(--text-primary); display: block; }
    .sp-qa span { font-size: 11.5px; color: var(--text-muted); }

    /* features */
    .sp-feats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 18px; }
    .sp-feat-ico { width: 38px; height: 38px; border-radius: 11px; background: var(--sp-soft); display: flex; align-items: center; justify-content: center; color: var(--sp); margin-bottom: 8px; }
    .sp-feat-ico svg { width: 19px; height: 19px; }
    .sp-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; }
    .sp-feat p { font-size: 11px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.45; }

    /* banner */
    .sp-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.08), rgba(59,130,246,0.05)); border: 1px solid rgba(37,99,235,0.2); border-radius: var(--radius-lg); padding: 18px 22px; }
    .sp-banner-ico { width: 48px; height: 48px; border-radius: 13px; background: rgba(37,99,235,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sp-banner-ico svg { width: 24px; height: 24px; color: var(--sp); }
    .sp-banner-txt { flex: 1; min-width: 230px; }
    .sp-banner-txt b { font-size: 16px; color: var(--sp-strong); }
    .sp-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .sp-banner button { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; border: none; cursor: pointer; font-family: inherit; white-space: nowrap; }
    .sp-banner button svg { width: 16px; height: 16px; }

    @media (max-width: 1100px) { .sp-row2 { grid-template-columns: 1fr; } .sp-stats { grid-template-columns: repeat(3, minmax(0,1fr)); } .sp-feats { grid-template-columns: repeat(3, minmax(0,1fr)); } .sp-event { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .sp-steps { flex-wrap: wrap; } .sp-step { flex-basis: 45%; } .sp-step-arr { display: none; } .sp-stats, .sp-feats { grid-template-columns: repeat(2, minmax(0,1fr)); } .sp-adjust { flex-direction: column; align-items: stretch; } .sp-adjust .fld { width: 100%; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Build your own staff roster by hand — add each role yourself, no AI plan.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'AI suggests a roster — adjust the event and staff counts, then regenerate.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your event and AI builds the whole staffing plan for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="sp" data-gen-url="{{ route('ai-tools.staffing-planner.generate') }}" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--sp,#2563eb);text-decoration:none;">Upgrade for more →</a>@endunless
    </div>

    {{-- header --}}
    <div class="sp-head">
        <a href="{{ route('ai-tools.budget-allocator') }}" class="sp-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Back to Toolkit</a>
        <span class="sp-head-ico">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="spPerson" x1="16" y1="9" x2="32" y2="40"><stop stop-color="#93c5fd"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
                {{-- ground shadow --}}
                <ellipse cx="24" cy="42" rx="17" ry="3" fill="#1e3a8a" opacity="0.22"/>
                {{-- back-left + back-right figures (smaller, darker = depth) --}}
                <circle cx="12" cy="17" r="5.5" fill="#1d4ed8"/>
                <path d="M4 35a8 8 0 0 1 16 0z" fill="#1d4ed8"/>
                <circle cx="36" cy="17" r="5.5" fill="#1d4ed8"/>
                <path d="M28 35a8 8 0 0 1 16 0z" fill="#1d4ed8"/>
                {{-- front figure (bigger, lighter, glossy = pops forward) --}}
                <circle cx="24" cy="17" r="8" fill="url(#spPerson)"/>
                <circle cx="21.2" cy="14.2" r="2.6" fill="#ffffff" opacity="0.4"/>
                <path d="M11 41a13 12 0 0 1 26 0z" fill="url(#spPerson)"/>
                <path d="M11 41a13 12 0 0 1 26 0z" fill="#1e40af" opacity="0.12"/>
            </svg>
        </span>
        <div class="sp-head-txt"><h1>Staffing Planner</h1><p>Plan the perfect team. At the right time. Every time.</p></div>
    </div>

    {{-- event bar --}}
    <div class="sp-card sp-mb">
        <div class="sp-event">
            <div class="sp-ev-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span class="k">Event:</span><span class="v" id="sp-ev-name">{{ $event['name'] }}</span></div>
            <div class="sp-ev-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg><span class="k">Guests:</span><span class="v" id="sp-ev-guests">{{ $event['guests'] }}</span></div>
            <div class="sp-ev-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span class="k">Date:</span><span class="v" id="sp-ev-date">{{ $event['date'] }}</span></div>
            <div class="sp-ev-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span class="k">Location:</span><span class="v" id="sp-ev-loc">{{ $event['location'] }}</span></div>
        </div>
    </div>

    @if($isManual)
    {{-- Do It Myself — build your own roster by hand, no AI --}}
    <div class="sp-card sp-mb">
        <div class="sp-tl-h"><b>🛠 Build My Staff Roster</b><span style="font-size:12.5px;color:var(--text-muted);">Total staff: <b id="spm-total" style="color:var(--sp);">0</b></span></div>
        <div id="spmRows" style="display:flex;flex-direction:column;gap:10px;"></div>
        <button type="button" id="spmAdd" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--sp);background:var(--sp-soft);border:1px solid rgba(37,99,235,.28);border-radius:10px;padding:9px 15px;cursor:pointer;font-family:inherit;">+ Add role</button>
        <div style="margin-top:14px;font-size:12px;color:var(--text-muted);">Want us to build this roster + timeline for you automatically? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:var(--sp);font-weight:700;text-decoration:none;">Upgrade →</a></div>
    </div>
    @else
    {{-- timeline --}}
    <div class="sp-card sp-mb">
        <div class="sp-tl-h">
            <b>Staff Schedule &amp; Timeline</b>
            <div class="sp-toggle">
                <button type="button" class="on" id="sp-view-timeline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>Timeline View</button>
                <button type="button" id="sp-view-list"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/></svg>List View</button>
            </div>
        </div>

        {{-- TIMELINE VIEW --}}
        <div id="sp-timeline-view">
            <div class="sp-tl-wrap">
                <div class="sp-tl">
                    <div class="sp-axis">
                        <div></div>
                        <div class="sp-axis-track" id="sp-axis">
                            @foreach($axis as $a)<span style="left:{{ $a['left'] }}%;">{{ $a['label'] }}</span>@endforeach
                        </div>
                    </div>
                    <div id="sp-rows">
                        @include('client.ai-tools._staffing_rows', ['roles' => $roles])
                    </div>
                </div>
            </div>
        </div>

        {{-- LIST VIEW --}}
        <div id="sp-list-view" style="display:none;">
            <table class="sp-list">
                <thead><tr><th>Role</th><th>Staff</th><th>Start</th><th>End</th></tr></thead>
                <tbody id="sp-list-body">
                    @include('client.ai-tools._staffing_list', ['roles' => $roles])
                </tbody>
            </table>
        </div>

        <div class="sp-allset"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg><span id="sp-allset-txt">All set! Your team, perfectly timed.</span></div>

        {{-- adjust panel (Help Me Plan only — the assistive tune-and-regenerate) --}}
        @if($isSemi)
        <div class="sp-adjust open" id="sp-adjust">
            <div class="fld"><label>Event Type</label><select id="sp-in-type">@foreach($eventTypes as $k => $v)<option value="{{ $k }}" @selected($k === $event['type'])>{{ $v }}</option>@endforeach</select></div>
            <div class="fld"><label>Guest Count</label><input type="number" id="sp-in-guests" min="10" max="2000" value="{{ $event['guests'] }}"></div>
            <div class="fld"><label>Start Time</label><select id="sp-in-start">@for($h = 6; $h <= 20; $h++)<option value="{{ $h }}" @selected($h === 10)>{{ $h <= 12 ? $h : $h - 12 }}:00 {{ $h < 12 ? 'AM' : 'PM' }}</option>@endfor</select></div>
            <div class="fld" style="flex:0 0 auto;"><button type="button" class="sp-banner" style="all:unset;display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-size:13px;font-weight:800;padding:10px 18px;border-radius:9px;cursor:pointer;" id="sp-regen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Regenerate Plan</button></div>
        </div>
        @endif
    </div>

    {{-- stats --}}
    <div class="sp-card sp-mb">
        <div class="sp-stats">
            <div class="sp-stat"><span class="sp-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><div><div class="lbl">Total Staff</div><div class="val" id="sp-stat-total">{{ $stats['total_staff'] }}</div><div class="sub">Team Members</div></div></div>
            <div class="sp-stat"><span class="sp-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><div class="lbl">Total Coverage</div><div class="val"><span id="sp-stat-hrs">{{ $stats['coverage_hrs'] }}</span> <small>hrs</small></div><div class="sub">Event Duration</div></div></div>
            <div class="sp-stat"><span class="sp-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span><div><div class="lbl">Full Coverage</div><div class="val" id="sp-stat-cov">{{ $stats['coverage_pct'] }}%</div><div class="sub" id="sp-stat-gaps">{{ $stats['gaps'] }}</div></div></div>
            <div class="sp-stat"><span class="sp-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 11 12 14 16 9"/></svg></span><div><div class="lbl">On-Time Start</div><div class="val" id="sp-stat-ontime">{{ $stats['on_time'] }}</div><div class="sub">All Roles On Time</div></div></div>
            <div class="sp-stat"><span class="sp-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span><div><div class="lbl">Staff Efficiency</div><div class="val" id="sp-stat-eff">{{ $stats['efficiency'] }}</div><div class="sub">Well Optimized</div></div></div>
        </div>
    </div>

    {{-- how it works + quick actions --}}
    <div class="sp-row2 sp-mb">
        <div class="sp-card">
            <div class="sp-sec-h">How It Works (For Clients)</div>
            <div class="sp-steps">
                <div class="sp-step"><span class="sp-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b>1. Add Event Details</b><p>Enter event info like date, time, location, and guest count.</p></div>
                <span class="sp-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="sp-step"><span class="sp-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><b>2. Suggests Staff</b><p>We recommend the right roles and team size.</p></div>
                <span class="sp-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="sp-step"><span class="sp-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>3. Build Schedule</b><p>AI creates the perfect timeline for every team member.</p></div>
                <span class="sp-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="sp-step"><span class="sp-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span><b>4. Review &amp; Adjust</b><p>Make changes easily with drag-and-drop scheduling.</p></div>
                <span class="sp-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="sp-step"><span class="sp-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></span><b>5. Publish Plan</b><p>Share the plan with your team instantly.</p></div>
            </div>
        </div>
        <div class="sp-card">
            <div class="sp-sec-h">Quick Actions</div>
            <button type="button" class="sp-qa" data-action="adjust"><span class="sp-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span><div><b>Add / Remove Staff</b><span>Customize your team</span></div></button>
            <button type="button" class="sp-qa" data-action="adjust"><span class="sp-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14l2 2 4-4"/></svg></span><div><b>Adjust Schedule</b><span>Drag &amp; drop to reschedule</span></div></button>
            <button type="button" class="sp-qa" data-action="duplicate"><span class="sp-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></span><div><b>Duplicate Plan</b><span>Use for another event</span></div></button>
            <button type="button" class="sp-qa" data-action="export"><span class="sp-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span><div><b>Export Schedule</b><span>Download as PDF / Excel</span></div></button>
            <button type="button" class="sp-qa" data-action="share"><span class="sp-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></span><div><b>Share with Team</b><span>Notify your staff</span></div></button>
        </div>
    </div>

    {{-- powerful features --}}
    <div class="sp-card sp-mb">
        <div class="sp-sec-h">Powerful Features Built for Stress-Free Events</div>
        <div class="sp-feats">
            <div><span class="sp-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b>Team Recommendations</b><p>Get smart role suggestions based on event type, guests, and needs.</p></div>
            <div><span class="sp-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><b>Perfect Timing</b><p>AI ensures no overlaps or gaps in your event schedule.</p></div>
            <div><span class="sp-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><b>Role-Based Planning</b><p>Assign tasks and responsibilities to every team member.</p></div>
            <div><span class="sp-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span><b>Real-Time Updates</b><p>Make changes and notify your team instantly in one click.</p></div>
            <div><span class="sp-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg></span><b>Mobile Friendly</b><p>Access your schedule anytime, anywhere on any device.</p></div>
        </div>
    </div>

    @endif

    {{-- bottom banner --}}
    <div class="sp-banner">
        <span class="sp-banner-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <div class="sp-banner-txt"><b>Right People. Right Time. Flawless Events.</b><p>Staffing Planner takes the guesswork out of scheduling so you can focus on creating unforgettable moments.</p></div>
        <button type="button" id="sp-create"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>Create My Staffing Plan</button>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.sp');
    if (!root) return;
    const LEVEL = root.dataset.level || 'maximum';
    const url = root.dataset.genUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    let current = { roles: @json($roles), axis: @json($axis), event: @json($event), stats: @json($stats) };

    function initials(name) { const w = name.trim().split(/\s+/); return ((w[0]?.[0] || '') + (w.length > 1 ? w[w.length - 1][0] : '')).toUpperCase(); }
    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function renderRows(roles) {
        $('sp-rows').innerHTML = roles.map((r) => `
            <div class="sp-row">
                <div class="sp-role">
                    <span class="sp-avatar" style="background:linear-gradient(135deg, ${r.color}, ${r.color}cc);">${esc(initials(r.name))}</span>
                    <div><div class="sp-role-nm">${esc(r.name)}</div>${r.count > 1 ? `<div class="sp-role-ct">(${r.count} People)</div>` : (r.is_you ? '<div class="sp-role-ct">(You)</div>' : '')}</div>
                </div>
                <div class="sp-track">
                    <div class="sp-bar" style="left:${r.left}%;width:${r.width}%;background:${r.color}26;color:${r.color};">${esc(r.start_label)} – ${esc(r.end_label)}</div>
                </div>
            </div>`).join('');
    }
    function renderList(roles) {
        $('sp-list-body').innerHTML = roles.map((r) => {
            const dur = Math.round((r.end - r.start) * 10) / 10;
            return `<tr><td><div class="role"><span class="dot" style="background:${r.color};"></span>${esc(r.name)}${r.count > 1 ? ` (${r.count})` : ''}</div></td><td>${r.count}</td><td>${esc(r.start_label)}</td><td>${esc(r.end_label)} <span style="color:var(--text-muted);">· ${dur}h</span></td></tr>`;
        }).join('');
    }
    function renderAxis(axis) {
        $('sp-axis').innerHTML = axis.map((a) => `<span style="left:${a.left}%;">${esc(a.label)}</span>`).join('');
    }
    function renderStats(s) {
        $('sp-stat-total').textContent = s.total_staff;
        $('sp-stat-hrs').textContent = s.coverage_hrs;
        $('sp-stat-cov').textContent = s.coverage_pct + '%';
        $('sp-stat-gaps').textContent = s.gaps;
        $('sp-stat-ontime').textContent = s.on_time;
        $('sp-stat-eff').textContent = s.efficiency;
    }
    function renderEvent(e) {
        $('sp-ev-name').textContent = e.name; $('sp-ev-guests').textContent = e.guests;
        $('sp-ev-date').textContent = e.date; $('sp-ev-loc').textContent = e.location;
    }
    function renderAll() { renderRows(current.roles); renderList(current.roles); renderAxis(current.axis); renderStats(current.stats); renderEvent(current.event); }

    async function regenerate(btn) {
        const o = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ event_type: $('sp-in-type').value, guests: parseInt($('sp-in-guests').value || '150', 10), start_hour: parseInt($('sp-in-start').value || '10', 10) }),
            });
            if (res.ok) { const d = await res.json(); current = { roles: d.roles, axis: d.axis, event: d.event, stats: d.stats }; renderAll(); }
        } catch (e) { /* keep last plan */ }
        finally { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = o; } }
    }

    // View toggle.
    $('sp-view-timeline')?.addEventListener('click', function () { $('sp-timeline-view').style.display = ''; $('sp-list-view').style.display = 'none'; this.classList.add('on'); $('sp-view-list').classList.remove('on'); });
    $('sp-view-list')?.addEventListener('click', function () { $('sp-timeline-view').style.display = 'none'; $('sp-list-view').style.display = ''; this.classList.add('on'); $('sp-view-timeline').classList.remove('on'); });

    // Regenerate (Help Me Plan only).
    $('sp-regen')?.addEventListener('click', function () { regenerate(this); });

    // Quick Actions. Adjust panel only exists at the Help Me Plan level.
    function openAdjust() { const el = $('sp-adjust'); if (!el) return; el.classList.add('open'); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    function csv() {
        const rows = [['Role', 'Staff', 'Start', 'End']].concat(current.roles.map((r) => [r.name, r.count, r.start_label, r.end_label]));
        const blob = new Blob([rows.map((r) => r.join(',')).join('\n')], { type: 'text/csv' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'staffing-plan.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(a.href);
    }
    document.querySelectorAll('.sp-qa').forEach((b) => b.addEventListener('click', function () {
        const act = this.dataset.action;
        if (act === 'adjust') openAdjust();
        else if (act === 'export') csv();
        else if (act === 'share') { navigator.clipboard?.writeText(window.location.href); flash(this, 'Link copied!'); }
        else if (act === 'duplicate') { navigator.clipboard?.writeText(current.roles.map((r) => `${r.name} (${r.count}): ${r.start_label} – ${r.end_label}`).join('\n')); flash(this, 'Plan copied!'); }
    }));
    function flash(el, msg) { const s = el.querySelector('b').textContent; el.querySelector('b').textContent = msg; setTimeout(() => { el.querySelector('b').textContent = s; }, 1500); }

    $('sp-create')?.addEventListener('click', openAdjust);
})();

// Do It Myself — hand-built roster (no AI, no server call).
(function () {
    const rows = document.getElementById('spmRows');
    const add = document.getElementById('spmAdd');
    const totalEl = document.getElementById('spm-total');
    if (!rows || !add) return;
    function recompute() {
        let t = 0;
        rows.querySelectorAll('.spm-count').forEach(i => t += (parseInt(i.value, 10) || 0));
        if (totalEl) totalEl.textContent = t;
    }
    function hourOpts(sel) {
        let h = '';
        for (let x = 6; x <= 26; x++) { const d = x % 24; const p = d < 12 ? 'AM' : 'PM'; const dd = d % 12 === 0 ? 12 : d % 12; h += '<option value="' + x + '"' + (x === sel ? ' selected' : '') + '>' + dd + ':00 ' + p + '</option>'; }
        return h;
    }
    function addRow(name = '', count = 1, start = 10, end = 18) {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;';
        div.innerHTML =
            '<input type="text" placeholder="Role (e.g. Server Team)" class="spm-inp" style="flex:2;min-width:150px;padding:9px 12px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' +
            '<input type="number" min="1" max="99" value="' + count + '" title="How many" class="spm-inp spm-count" style="width:70px;padding:9px 10px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' +
            '<select class="spm-inp" style="width:auto;padding:9px 10px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' + hourOpts(start) + '</select>' +
            '<span style="color:var(--text-muted);font-size:12px;">to</span>' +
            '<select class="spm-inp" style="width:auto;padding:9px 10px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' + hourOpts(end) + '</select>' +
            '<button type="button" title="Remove" style="border:none;background:rgba(220,38,38,.1);color:#dc2626;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:16px;flex:0 0 auto;">&times;</button>';
        div.querySelector('input[type="text"]').value = name;
        div.querySelector('.spm-count').addEventListener('input', recompute);
        div.querySelector('button').addEventListener('click', () => { div.remove(); recompute(); });
        rows.appendChild(div);
    }
    // Seed common event-day roles the user can rename or remove.
    [['Event Manager', 1, 10, 25], ['Server Team', 4, 16, 25], ['Setup Crew', 2, 8, 14], ['DJ', 1, 18, 24], ['Cleanup Crew', 2, 24, 28]]
        .forEach(([n, c, s, e]) => addRow(n, c, s, e));
    add.addEventListener('click', () => { addRow(); recompute(); });
    recompute();
})();
</script>
@endsection
