@extends('layouts.client')

@section('title', 'AI Vendor Matchmaking')
@section('page-title', 'AI Vendor Matchmaking')
@section('page-subtitle', 'We find the perfect vendors for your event based on your theme, date, and budget.')

{{-- AI Vendor Matchmaking — deterministic, dynamic matcher (no LLM). Ranks a
     vendor catalogue against the event theme/budget and the refine controls
     re-filter/re-rank live. Page-scoped — the shared layout is untouched. --}}

@push('styles')
<style>
    .vm { --vm: #8b5cf6; --vm-strong: #7c3aed; --vm-soft: rgba(139,92,246,0.09); padding-top: 22px; }
    .vm-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px 24px; }
    .vm-mb { margin-bottom: 20px; }

    /* header */
    .vm-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .vm-head-l { display: flex; align-items: center; gap: 16px; }
    .vm-head-ico { width: 58px; height: 58px; flex-shrink: 0; filter: drop-shadow(0 6px 12px rgba(124,58,237,0.35)); }
    .vm-head-ico svg { width: 100%; height: 100%; }
    .vm-head-txt h1 { font-size: 28px; font-weight: 800; color: var(--vm-strong); margin: 0; }
    .vm-head-txt p { font-size: 13.5px; color: var(--text-muted); margin: 3px 0 0; max-width: 560px; }
    .vm-back { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--vm-strong); text-decoration: none; background: var(--bg-card); white-space: nowrap; }
    .vm-back svg { width: 15px; height: 15px; }

    /* main grid */
    .vm-main { display: grid; grid-template-columns: minmax(0,1.75fr) minmax(0,1fr); gap: 20px; align-items: start; }
    .vm-col { display: flex; flex-direction: column; gap: 20px; }

    /* event details */
    .vm-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 16px; }
    .vm-sec-h .ic { width: 30px; height: 30px; border-radius: 9px; background: var(--vm-soft); display: flex; align-items: center; justify-content: center; color: var(--vm); flex-shrink: 0; }
    .vm-sec-h .ic svg { width: 16px; height: 16px; }
    .vm-sec-h b { font-size: 16px; font-weight: 800; color: var(--vm-strong); }
    .vm-ev { display: flex; align-items: flex-start; gap: 34px; flex-wrap: wrap; }
    .vm-ev-item .k { font-size: 12.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 3px; }
    .vm-ev-item .v { font-size: 14.5px; color: var(--text-primary); font-weight: 700; }
    .vm-ev-edit { margin-left: auto; display: inline-flex; align-items: center; gap: 7px; padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--vm-strong); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .vm-ev-edit svg { width: 13px; height: 13px; }
    .vm-edit-panel { display: none; gap: 14px; flex-wrap: wrap; align-items: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color); }
    .vm-edit-panel.open { display: flex; }
    .vm-edit-panel .fld { flex: 1; min-width: 150px; }
    .vm-edit-panel label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .vm-edit-panel input { width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .vm-edit-panel input:focus { outline: none; border-color: var(--vm); }

    /* top matches */
    .vm-tm-h { display: flex; align-items: center; gap: 9px; margin: 6px 0 4px; }
    .vm-tm-h svg { width: 18px; height: 18px; color: var(--vm); }
    .vm-tm-h b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .vm-match { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; margin-top: 14px; }
    .vm-match-top { display: flex; align-items: flex-start; gap: 14px; }
    .vm-avatar { width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 19px; font-weight: 800; }
    .vm-match-main { flex: 1; min-width: 0; }
    .vm-match-name { font-size: 17px; font-weight: 800; color: var(--text-primary); word-break: break-word; overflow-wrap: break-word; }
    .vm-stars { display: flex; align-items: center; gap: 2px; margin: 5px 0 9px; }
    .vm-stars svg { width: 15px; height: 15px; }
    .vm-reviews { font-size: 12.5px; color: var(--text-muted); margin-left: 5px; }
    .vm-tags { display: flex; flex-wrap: wrap; gap: 7px; }
    .vm-tag { font-size: 11.5px; font-weight: 700; padding: 4px 11px; border-radius: 999px; background: var(--bg-card-hover); color: var(--text-secondary); }
    .vm-tag-avail { background: rgba(16,185,129,0.12); color: #059669; }
    .vm-match-right { text-align: right; flex-shrink: 0; }
    .vm-match-pct { display: inline-block; font-size: 12.5px; font-weight: 800; color: var(--vm); border: 1px solid rgba(139,92,246,0.4); border-radius: 999px; padding: 4px 12px; }
    .vm-match-price { display: block; font-size: 20px; font-weight: 800; color: var(--text-primary); margin-top: 10px; }
    .vm-why { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; background: var(--vm-soft); border-radius: 10px; padding: 11px 14px; margin-top: 14px; overflow-wrap: break-word; word-break: break-word; }
    .vm-why b { color: var(--vm-strong); }
    .vm-empty { padding: 28px 12px; text-align: center; color: var(--text-muted); font-size: 13px; }
    .vm-more { display: block; text-align: center; padding: 16px; margin-top: 8px; font-size: 14px; font-weight: 800; color: var(--vm-strong); text-decoration: none; cursor: pointer; }
    .vm-more svg { width: 15px; height: 15px; vertical-align: -3px; margin-left: 4px; }

    /* sidebar cards */
    .vm-side-h { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }
    .vm-side-h svg { width: 17px; height: 17px; color: var(--vm); }
    .vm-side-h b { font-size: 15px; font-weight: 800; color: var(--vm-strong); }
    .vm-ins { display: flex; gap: 12px; padding: 9px 0; }
    .vm-ins-ico { width: 32px; height: 32px; border-radius: 9px; background: var(--vm-soft); display: flex; align-items: center; justify-content: center; color: var(--vm); flex-shrink: 0; }
    .vm-ins-ico svg { width: 16px; height: 16px; }
    .vm-ins b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; }
    .vm-ins p { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.45; }
    .vm-fld { margin-bottom: 14px; }
    .vm-fld label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 7px; }
    .vm-select-wrap { position: relative; }
    .vm-select { width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; appearance: none; -webkit-appearance: none; cursor: pointer; }
    .vm-select:focus { outline: none; border-color: var(--vm); }
    .vm-select-wrap .chev { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .vm-slider-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .vm-slider-top .k { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .vm-slider-top .v { font-size: 12px; font-weight: 800; color: var(--vm-strong); }
    .vm-range { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 4px; background: var(--border-color); outline: none; }
    .vm-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 17px; height: 17px; border-radius: 50%; background: #8b5cf6; cursor: pointer; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
    .vm-range::-moz-range-thumb { width: 17px; height: 17px; border-radius: 50%; background: #8b5cf6; cursor: pointer; border: 2px solid #fff; }
    .vm-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: none; border-radius: 10px; background: linear-gradient(135deg, #a78bfa, #7c3aed); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; margin-top: 6px; }
    .vm-btn svg { width: 15px; height: 15px; }
    .vm-help p { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px; }
    .vm-help-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--vm-strong); font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; text-decoration: none; }
    .vm-help-btn svg { width: 14px; height: 14px; }

    /* how it works */
    .vm-hiw-h { font-size: 18px; font-weight: 800; color: var(--vm-strong); text-align: center; margin: 0 0 20px; }
    .vm-steps { display: flex; align-items: flex-start; justify-content: space-between; gap: 2px; }
    .vm-step { text-align: center; flex: 1; min-width: 0; }
    .vm-step-ico { width: 56px; height: 56px; border-radius: 50%; border: 2px solid rgba(139,92,246,0.25); display: flex; align-items: center; justify-content: center; color: var(--vm); margin: 0 auto 10px; }
    .vm-step-ico svg { width: 26px; height: 26px; }
    .vm-step b { font-size: 12px; font-weight: 800; color: var(--vm); display: block; line-height: 1.3; }
    .vm-step p { font-size: 11px; color: var(--text-muted); margin: 6px 0 0; line-height: 1.45; }
    .vm-step-arr { display: flex; align-items: center; padding-top: 20px; color: var(--vm); flex-shrink: 0; }
    .vm-step-arr svg { width: 16px; height: 16px; }

    /* banner */
    .vm-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(139,92,246,0.09), rgba(124,58,237,0.05)); border: 1px solid rgba(139,92,246,0.2); border-radius: var(--radius-lg); padding: 18px 22px; }
    .vm-banner-ico { width: 46px; height: 46px; border-radius: 12px; background: rgba(139,92,246,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .vm-banner-ico svg { width: 24px; height: 24px; color: var(--vm); }
    .vm-banner-txt { flex: 1; min-width: 230px; }
    .vm-banner-txt b { font-size: 16px; color: var(--vm-strong); }
    .vm-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .vm-banner a { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, #a78bfa, #7c3aed); color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; text-decoration: none; white-space: nowrap; }

    @media (max-width: 1100px) { .vm-main { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .vm-steps { flex-wrap: wrap; } .vm-step { flex-basis: 45%; } .vm-step-arr { display: none; } .vm-match-top { flex-wrap: wrap; } }
</style>
@endpush

@section('content')
<div class="vm" data-match-url="{{ route('ai-tools.vendor-matchmaking.match') }}" data-budget="{{ $event['budget'] }}">

    @include('partials._ai_quota_badge', ['status' => $status, 'tool' => 'AI Vendor Matchmaking'])

    {{-- header --}}
    <div class="vm-head">
        <div class="vm-head-l">
            <span class="vm-head-ico">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="vmClover" x1="10" y1="8" x2="38" y2="40"><stop stop-color="#a78bfa"/><stop offset="1" stop-color="#6d28d9"/></linearGradient></defs>
                    {{-- depth (offset darker clover) --}}
                    <g fill="#5b21b6" opacity="0.55">
                        <circle cx="25.5" cy="15.5" r="9"/><circle cx="25.5" cy="33.5" r="9"/><circle cx="16.5" cy="24.5" r="9"/><circle cx="34.5" cy="24.5" r="9"/>
                    </g>
                    {{-- main clover --}}
                    <g fill="url(#vmClover)">
                        <circle cx="24" cy="14" r="9"/><circle cx="24" cy="32" r="9"/><circle cx="15" cy="23" r="9"/><circle cx="33" cy="23" r="9"/>
                    </g>
                    <circle cx="24" cy="23" r="6" fill="#7c3aed"/>
                    {{-- shine highlights --}}
                    <circle cx="21" cy="11" r="2.4" fill="#fff" opacity="0.45"/>
                    <circle cx="12.5" cy="20.5" r="2" fill="#fff" opacity="0.35"/>
                </svg>
            </span>
            <div class="vm-head-txt"><h1>AI Vendor Matchmaking</h1><p>We find the perfect vendors for your event based on your theme, date, and budget.</p></div>
        </div>
        <a href="{{ route('ai-tools.budget-allocator') }}" class="vm-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Back to AI Toolkit</a>
    </div>

    <div class="vm-main">
        {{-- LEFT --}}
        <div class="vm-col">
            <div class="vm-card">
                <div class="vm-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>Event Details</b></div>
                <div class="vm-ev">
                    <div class="vm-ev-item"><div class="k">Theme:</div><div class="v" id="vm-ev-theme">{{ $event['theme'] }}</div></div>
                    <div class="vm-ev-item"><div class="k">Date:</div><div class="v">{{ $event['date'] }}</div></div>
                    <div class="vm-ev-item"><div class="k">Budget:</div><div class="v" id="vm-ev-budget">${{ number_format($event['budget']) }}</div></div>
                    <button type="button" class="vm-ev-edit" id="vm-edit-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Edit</button>
                </div>
                <div class="vm-edit-panel" id="vm-edit-panel">
                    <div class="fld"><label>Event Theme</label><input type="text" id="vm-in-theme" value="{{ $event['theme'] }}" maxlength="120"></div>
                    <div class="fld" style="flex:0 0 auto;"><button type="button" class="vm-btn" style="width:auto;padding:10px 18px;" id="vm-apply-theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>Apply</button></div>
                </div>
            </div>

            <div class="vm-card">
                <div class="vm-tm-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6 4.5 2.3 7.1-6.3-4.6L5.7 21l2.3-7.1-6-4.5h7.6z"/></svg><b>Top Matches For You</b></div>
                <div id="vm-matches">
                    @include('client.ai-tools._vendor_matches', ['matches' => $matches])
                </div>
                <a class="vm-more" id="vm-more">View <span id="vm-more-count">{{ $moreCount }}</span> more matches <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="vm-col">
            <div class="vm-card">
                <div class="vm-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg><b>Match Insights</b></div>
                <div class="vm-ins"><span class="vm-ins-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span><div><b>We analyzed <span id="vm-ins-analyzed">{{ $analyzed }}</span>+ vendors</b><p>To find the best matches for your event.</p></div></div>
                <div class="vm-ins"><span class="vm-ins-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>Availability Confirmed</b><p>All matched vendors are available on {{ $event['date'] }}.</p></div></div>
                <div class="vm-ins"><span class="vm-ins-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span><div><b>Budget Friendly</b><p>All matches fit within your ${{ number_format($event['budget']) }} budget.</p></div></div>
            </div>

            <div class="vm-card">
                <div class="vm-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg><b>Refine Your Match</b></div>
                <div class="vm-fld">
                    <label>Category</label>
                    <div class="vm-select-wrap"><select class="vm-select" id="vm-category">@foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
                </div>
                <div class="vm-fld">
                    <label>Max Budget</label>
                    <div class="vm-select-wrap"><select class="vm-select" id="vm-budget">@foreach($budgetOptions as $k => $v)<option value="{{ $k }}" @selected($k === 1000)>{{ $v }}</option>@endforeach</select><svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
                </div>
                <div class="vm-fld">
                    <div class="vm-slider-top"><span class="k">Match Percentage</span><span class="v"><span id="vm-min-lbl">80</span>% and above</span></div>
                    <input type="range" class="vm-range" id="vm-min" min="60" max="100" step="5" value="80">
                </div>
                <button type="button" class="vm-btn" id="vm-refine"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Refine Matches</button>
            </div>

            <div class="vm-card vm-help">
                <div class="vm-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><b>Need More Help?</b></div>
                <p>Chat with our event experts and get personalized vendor recommendations.</p>
                <a href="{{ route('client.chat.index') }}" class="vm-help-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Chat with Expert</a>
            </div>

            <div class="vm-card vm-help">
                <div class="vm-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><b>Save This Search</b></div>
                <p>We'll notify you if new vendors match your event.</p>
                <button type="button" class="vm-help-btn" id="vm-save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>Save Search</button>
            </div>
        </div>
    </div>

    {{-- how it works --}}
    <div class="vm-card vm-mb" style="margin-top:20px;">
        <div class="vm-hiw-h">How It Works (For Clients)</div>
        <div class="vm-steps">
            <div class="vm-step"><span class="vm-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>1. Add Event Details</b><p>Tell us your theme, date, location, and budget.</p></div>
            <span class="vm-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="vm-step"><span class="vm-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v6M8 11h6"/></svg></span><b>2. AI Finds Matches</b><p>Our AI scans hundreds of vendors to find the best fit.</p></div>
            <span class="vm-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="vm-step"><span class="vm-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><b>3. Review Top Matches</b><p>See top vendors with match scores, reviews &amp; pricing.</p></div>
            <span class="vm-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="vm-step"><span class="vm-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b>4. Connect &amp; Chat</b><p>Message vendors directly and ask questions.</p></div>
            <span class="vm-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="vm-step"><span class="vm-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M8 12l3 3 5-6"/></svg></span><b>5. Book with Confidence</b><p>Choose your favorite and book securely.</p></div>
        </div>
    </div>

    {{-- banner --}}
    <div class="vm-banner">
        <span class="vm-banner-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
        <div class="vm-banner-txt"><b>Trusted. Verified. Reviewed.</b><p>All vendors are verified and reviewed by real customers like you for a safe booking experience.</p></div>
        <a href="{{ route('client.search.index') }}">Learn More About Safety</a>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.vm');
    if (!root) return;
    const url = root.dataset.matchUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    const esc = (s) => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    const money = (n) => '$' + Number(n).toLocaleString('en-US');

    function stars(rating) {
        const full = Math.floor(rating), half = (rating - full) >= 0.5;
        let h = '<svg width="0" height="0" style="position:absolute"><defs><linearGradient id="vmHalfJs"><stop offset="50%" stop-color="#f59e0b"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs></svg>';
        for (let i = 1; i <= 5; i++) {
            const fill = i <= full ? '#f59e0b' : (i === full + 1 && half ? 'url(#vmHalfJs)' : '#d1d5db');
            h += '<svg viewBox="0 0 24 24" fill="' + fill + '"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
        return h;
    }
    function renderMatches(matches) {
        if (!matches.length) { $('vm-matches').innerHTML = '<div class="vm-empty">No vendors match these filters. Try widening your budget or lowering the match threshold.</div>'; return; }
        $('vm-matches').innerHTML = matches.map((m) => {
            const tags = m.tags.map((t) => '<span class="vm-tag">' + esc(t) + '</span>').join('') + (m.available ? '<span class="vm-tag vm-tag-avail">Available</span>' : '');
            return '<div class="vm-match"><div class="vm-match-top">'
                + '<span class="vm-avatar" style="background:linear-gradient(135deg, ' + m.grad + ');">' + esc(m.initials) + '</span>'
                + '<div class="vm-match-main"><div class="vm-match-name">' + esc(m.name) + '</div>'
                + '<div class="vm-stars">' + stars(m.rating) + '<span class="vm-reviews">(' + m.reviews + ')</span></div>'
                + '<div class="vm-tags">' + tags + '</div></div>'
                + '<div class="vm-match-right"><span class="vm-match-pct">' + m.match + '% Match</span><span class="vm-match-price">' + money(m.price) + '</span></div>'
                + '</div><div class="vm-why"><b>Why matched?</b> ' + esc(m.why) + '</div></div>';
        }).join('');
    }

    async function refine(btn) {
        const o = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ theme: $('vm-in-theme').value, category: $('vm-category').value, max_budget: parseInt($('vm-budget').value, 10), min_match: parseInt($('vm-min').value, 10) }),
            });
            if (res.ok) {
                const d = await res.json();
                renderMatches(d.matches);
                $('vm-more-count').textContent = d.moreCount;
                $('vm-more').style.display = d.moreCount > 0 ? '' : 'none';
                $('vm-ev-theme').textContent = $('vm-in-theme').value;
                $('vm-ins-analyzed').textContent = d.analyzed;
            }
        } catch (e) { /* keep last matches */ }
        finally { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = o; } }
    }

    $('vm-min').addEventListener('input', function () { $('vm-min-lbl').textContent = this.value; });
    $('vm-refine').addEventListener('click', function () { refine(this); });
    $('vm-edit-btn').addEventListener('click', () => $('vm-edit-panel').classList.toggle('open'));
    $('vm-apply-theme').addEventListener('click', function () { refine(this); });
    $('vm-more').addEventListener('click', function (e) { e.preventDefault(); window.location.href = "{{ route('client.search.index') }}"; });
    $('vm-save').addEventListener('click', function () { const s = this.querySelector('svg').outerHTML; this.innerHTML = s + 'Search Saved!'; });
})();
</script>
@endsection
