@extends($aiLayout ?? 'layouts.client')

@section('title', 'Pricing Calculator')
@section('page-title', 'Pricing Calculator')
@section('page-subtitle', 'Get the right price. Win more gigs. Maximize your value.')

{{-- Pricing Calculator — a deterministic pricing calculator (no LLM / no
     quota). Recommended price + market band are computed from a transparent
     rate model server-side (and live via POST on Calculate/Recalculate);
     "Recent Price Calculations" are REAL (the client's own events). Market
     supply/demand lines are model-derived illustrative estimates. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

@push('styles')
<style>
    .apa { --apa: #d97706; --apa-strong: #b45309; padding-top: 22px; }
    .apa-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px 24px; }

    /* header */
    .apa-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .apa-head-l { display: flex; align-items: center; gap: 16px; }
    .apa-head-ico { width: 62px; height: 62px; border-radius: 18px; background: linear-gradient(135deg, #fbbf24, #d97706); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 8px 18px rgba(217,119,6,0.38), inset 0 1.5px 0 rgba(255,255,255,0.45); }
    .apa-head-ico svg { width: 42px; height: 42px; }
    .apa-head h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .apa-head p { font-size: 13.5px; color: var(--text-muted); margin: 3px 0 0; }
    .apa-back { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--apa-strong); text-decoration: none; background: var(--bg-card); white-space: nowrap; }
    .apa-back svg { width: 15px; height: 15px; }

    /* main two-col */
    .apa-main { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 20px; margin-bottom: 20px; align-items: start; }
    .apa-sec-num { font-size: 16px; font-weight: 800; color: var(--apa); margin: 0 0 6px; }
    .apa-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 18px; }
    .apa-field { margin-bottom: 16px; }
    .apa-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 7px; }
    .apa-input { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .apa-input:focus { outline: none; border-color: var(--apa); }
    .apa-input-wrap { position: relative; }
    .apa-input-wrap > svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none; }
    .apa-input-wrap .apa-input { padding-left: 38px; }
    select.apa-input { appearance: none; -webkit-appearance: none; cursor: pointer; }
    .apa-select-wrap { position: relative; }
    .apa-select-wrap > .chev { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none; }
    .apa-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 9px; padding: 14px; border: none; border-radius: 11px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-size: 14.5px; font-weight: 800; cursor: pointer; font-family: inherit; margin-top: 4px; }
    .apa-btn:hover { filter: brightness(1.05); }
    .apa-btn svg { width: 17px; height: 17px; }

    /* recommended price card */
    .apa-rec { background: linear-gradient(135deg, rgba(245,158,11,0.07), rgba(217,119,6,0.04)); border: 1px solid rgba(217,119,6,0.22); border-radius: var(--radius-lg); padding: 22px 24px; }
    .apa-rec h3 { font-size: 16px; font-weight: 800; color: var(--apa-strong); margin: 0 0 10px; }
    .apa-rec-top { display: flex; align-items: center; gap: 14px; }
    .apa-rec-price { font-size: 48px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .apa-badge { font-size: 13px; font-weight: 800; padding: 6px 14px; border-radius: 999px; }
    .apa-rec hr { border: none; border-top: 1px solid rgba(217,119,6,0.18); margin: 18px 0; }
    .apa-rec-rk { font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .apa-rec-rv { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .apa-mi-title { font-size: 14px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .apa-mi { display: flex; align-items: center; gap: 11px; padding: 6px 0; font-size: 13px; color: var(--text-secondary); }
    .apa-mi svg { width: 16px; height: 16px; flex-shrink: 0; }
    .apa-tip { display: flex; gap: 10px; background: rgba(217,119,6,0.08); border-radius: 10px; padding: 12px 14px; margin-top: 16px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; }
    .apa-tip svg { width: 16px; height: 16px; color: var(--apa); flex-shrink: 0; margin-top: 1px; }
    .apa-tip b { color: var(--apa-strong); }

    /* why use */
    .apa-why-h { font-size: 18px; font-weight: 800; color: var(--apa-strong); text-align: center; margin-bottom: 20px; }
    .apa-why { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 18px; }
    .apa-why-card { display: flex; gap: 12px; }
    .apa-why-ico { width: 42px; height: 42px; border-radius: 50%; border: 2px solid rgba(217,119,6,0.25); display: flex; align-items: center; justify-content: center; color: var(--apa); flex-shrink: 0; }
    .apa-why-ico svg { width: 20px; height: 20px; }
    .apa-why-card b { font-size: 13px; font-weight: 800; color: var(--apa-strong); display: block; }
    .apa-why-card p { font-size: 11.5px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.45; }

    /* market comparison */
    .apa-mc-h { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 22px; flex-wrap: wrap; }
    .apa-mc-h b { font-size: 17px; font-weight: 800; color: var(--apa-strong); display: inline-flex; align-items: center; gap: 7px; }
    .apa-mc-h b svg { width: 15px; height: 15px; color: var(--text-muted); }
    .apa-mc-body { display: grid; grid-template-columns: minmax(0,1fr) 240px; gap: 24px; align-items: center; }
    .apa-mc-left { overflow-x: clip; }
    .apa-mc-scale { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .apa-mc-scale .c { text-align: center; }
    .apa-mc-scale .c:first-child { text-align: left; } .apa-mc-scale .c:last-child { text-align: right; }
    .apa-mc-scale .k { font-size: 12px; color: var(--text-muted); }
    .apa-mc-scale .v { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .apa-mc-track { position: relative; height: 14px; border-radius: 8px; background: linear-gradient(90deg, #fde68a, #f59e0b, #d97706); margin: 22px 0 8px; }
    .apa-mc-marker { position: absolute; top: 50%; transform: translate(-50%,-50%); width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid #d97706; box-shadow: 0 1px 4px rgba(0,0,0,0.2); }
    .apa-mc-flag { position: absolute; top: 26px; transform: translateX(-50%); background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border-radius: 10px; padding: 8px 14px; text-align: center; white-space: nowrap; }
    .apa-mc-flag::before { content: ''; position: absolute; top: -6px; left: 50%; transform: translateX(-50%); border-left: 7px solid transparent; border-right: 7px solid transparent; border-bottom: 7px solid #f59e0b; }
    .apa-mc-flag .k { font-size: 10px; opacity: 0.9; }
    .apa-mc-flag .v { font-size: 15px; font-weight: 800; }
    .apa-mc-means { background: rgba(217,119,6,0.06); border: 1px solid rgba(217,119,6,0.18); border-radius: 12px; padding: 16px 18px; }
    .apa-mc-means b { font-size: 13.5px; font-weight: 800; color: var(--apa-strong); }
    .apa-mc-means p { font-size: 12.5px; color: var(--text-secondary); margin: 8px 0 0; line-height: 1.5; }

    /* how it works + adjust */
    .apa-row2 { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(0,0.85fr); gap: 20px; margin-bottom: 20px; align-items: start; }
    .apa-card-h { font-size: 16px; font-weight: 800; color: var(--apa-strong); margin: 0 0 18px; }
    .apa-steps { display: flex; align-items: flex-start; justify-content: space-between; gap: 4px; }
    .apa-step { text-align: center; flex: 1; }
    .apa-step-ico { width: 50px; height: 50px; border-radius: 50%; border: 2px solid rgba(217,119,6,0.22); display: flex; align-items: center; justify-content: center; color: var(--apa); margin: 0 auto 9px; }
    .apa-step-ico svg { width: 22px; height: 22px; }
    .apa-step b { font-size: 11.5px; font-weight: 800; color: var(--apa-strong); display: block; line-height: 1.3; }
    .apa-step p { font-size: 10.5px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.4; }
    .apa-step-arr { display: flex; align-items: center; padding-top: 18px; color: var(--apa); flex-shrink: 0; }
    .apa-step-arr svg { width: 16px; height: 16px; }
    .apa-slider { margin-bottom: 16px; }
    .apa-slider-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .apa-slider-top .k { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .apa-slider-top .v { font-size: 12.5px; font-weight: 800; color: var(--apa-strong); }
    .apa-range { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 4px; background: var(--border-color); outline: none; }
    .apa-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #d97706; cursor: pointer; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
    .apa-range::-moz-range-thumb { width: 18px; height: 18px; border-radius: 50%; background: #d97706; cursor: pointer; border: 2px solid #fff; }
    .apa-recalc { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: none; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; margin-top: 6px; }
    .apa-recalc svg { width: 15px; height: 15px; }

    /* recent + tips */
    .apa-row3 { display: grid; grid-template-columns: minmax(0,1.1fr) minmax(0,0.9fr); gap: 20px; margin-bottom: 20px; align-items: start; }
    .apa-tbl { width: 100%; border-collapse: collapse; }
    .apa-tbl th { text-align: left; font-size: 11.5px; font-weight: 700; color: var(--text-muted); padding: 0 8px 12px; border-bottom: 1px solid var(--border-color); }
    .apa-tbl th:last-child, .apa-tbl td:last-child { text-align: right; }
    .apa-tbl td { font-size: 12.5px; color: var(--text-secondary); padding: 13px 8px; border-bottom: 1px solid var(--border-color); word-break: break-word; }
    .apa-tbl td.svc { font-weight: 700; color: var(--text-primary); }
    .apa-tbl td.price { font-weight: 800; color: #16a34a; }
    .apa-viewall { display: block; text-align: center; padding: 14px; margin-top: 4px; font-size: 13px; font-weight: 800; color: var(--apa-strong); text-decoration: none; }
    .apa-viewall svg { width: 14px; height: 14px; vertical-align: -2px; }
    .apa-tips-wrap { position: relative; }
    .apa-tip-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.45; }
    .apa-tip-row svg { width: 16px; height: 16px; color: var(--apa); flex-shrink: 0; margin-top: 1px; }
    .apa-bulb { position: absolute; right: 6px; bottom: 0; width: 120px; height: 110px; opacity: 0.85; pointer-events: none; }

    /* bottom banner */
    .apa-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(245,158,11,0.09), rgba(217,119,6,0.05)); border: 1px solid rgba(217,119,6,0.2); border-radius: var(--radius-lg); padding: 18px 22px; }
    .apa-banner-ico { width: 46px; height: 46px; border-radius: 12px; background: rgba(217,119,6,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .apa-banner-ico svg { width: 24px; height: 24px; color: var(--apa); }
    .apa-banner-txt { flex: 1; min-width: 220px; }
    .apa-banner-txt b { font-size: 16px; color: var(--apa-strong); }
    .apa-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; }
    .apa-banner a { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; text-decoration: none; white-space: nowrap; }
    .apa-banner a svg { width: 16px; height: 16px; }

    @media (max-width: 1100px) { .apa-main, .apa-row2, .apa-row3, .apa-mc-body { grid-template-columns: minmax(0,1fr); } .apa-why { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 640px) { .apa-why { grid-template-columns: minmax(0,1fr); } .apa-steps { flex-wrap: wrap; } .apa-step { flex-basis: 40%; } .apa-step-arr { display: none; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Work out your own price — a manual rate worksheet, no AI.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'AI suggests a competitive price and market context — you set the final number.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'the tool analyses the market and sets your optimal price automatically.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="apa" data-level="{{ $level }}"
     data-calc-url="{{ route('ai-tools.pricing-assistant.calculate') }}"
     data-init='@json($result)'>

    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card,var(--bg-secondary));border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:18px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--apa,#d97706);text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    {{-- header --}}
    <div class="apa-head">
        <div class="apa-head-l">
            <span class="apa-head-ico">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="apaCalcBody" x1="12" y1="6" x2="34" y2="42"><stop stop-color="#ffffff"/><stop offset="1" stop-color="#e6ebf2"/></linearGradient>
                        <linearGradient id="apaCalcScr" x1="14" y1="10" x2="32" y2="18"><stop stop-color="#334155"/><stop offset="1" stop-color="#0f172a"/></linearGradient>
                    </defs>
                    {{-- ground shadow + 3D depth edge --}}
                    <ellipse cx="24" cy="43" rx="13" ry="2.4" fill="#7c2d12" opacity="0.30"/>
                    <rect x="12.6" y="8.4" width="23" height="32" rx="5.5" fill="#b45309"/>
                    {{-- white body + top shine --}}
                    <rect x="11" y="6.5" width="23" height="32" rx="5.5" fill="url(#apaCalcBody)"/>
                    <rect x="11" y="6.5" width="23" height="10" rx="5.5" fill="#ffffff" opacity="0.5"/>
                    {{-- screen --}}
                    <rect x="14" y="10" width="17" height="7" rx="2" fill="url(#apaCalcScr)"/>
                    <rect x="22" y="11.8" width="7" height="1.6" rx="0.8" fill="#fbbf24"/>
                    <rect x="25.5" y="14.2" width="3.5" height="1.3" rx="0.65" fill="#64748b"/>
                    {{-- buttons (3×3, amber operator column) --}}
                    <rect x="14" y="21" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="20.2" y="21" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="26.4" y="21" width="4.6" height="4" rx="1.2" fill="#f59e0b"/>
                    <rect x="14" y="26.6" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="20.2" y="26.6" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="26.4" y="26.6" width="4.6" height="4" rx="1.2" fill="#f59e0b"/>
                    <rect x="14" y="32.2" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="20.2" y="32.2" width="4.6" height="4" rx="1.2" fill="#cbd5e1"/>
                    <rect x="26.4" y="32.2" width="4.6" height="4" rx="1.2" fill="#ea580c"/>
                </svg>
            </span>
            <div class="apa-head-txt"><h1>Pricing Calculator</h1><p>Get the right price. Win more gigs. Maximize your value.</p></div>
        </div>
        <a href="{{ route('ai-tools.budget-allocator') }}" class="apa-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Back to AI Toolkit</a>
    </div>

    @if($isManual)
    {{-- Do It Myself — manual rate worksheet, no AI --}}
    <div class="apa-card" style="margin-bottom:20px; max-width:520px;">
        <div class="apa-sec-num">Your Price Worksheet</div>
        <p class="apa-sec-sub">Work out your own quote — enter your rate, hours and any extras.</p>
        <div class="apa-field"><label>Your Rate ($ / hour)</label><input type="number" class="apa-input" id="pm-rate" min="0" step="1" placeholder="e.g. 150"></div>
        <div class="apa-field"><label>Hours</label><input type="number" class="apa-input" id="pm-hours" min="0" step="0.5" placeholder="e.g. 4"></div>
        <div class="apa-field"><label>Add-ons / extras ($)</label><input type="number" class="apa-input" id="pm-addons" min="0" step="1" placeholder="e.g. 200"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--border-color);">
            <span style="font-size:14px;font-weight:700;color:var(--text-secondary);">Your Price</span>
            <span style="font-size:28px;font-weight:800;color:var(--apa-strong,#d97706);" id="pm-total">$0</span>
        </div>
    </div>
    @else
    {{-- ════════ main: form + recommended ════════ --}}
    <div class="apa-main">
        {{-- form --}}
        <div class="apa-card">
            <div class="apa-sec-num">1. Enter Your Gig Details</div>
            <p class="apa-sec-sub">Tell us about your service so we can calculate the best price.</p>
            <div class="apa-field">
                <label>Service Type</label>
                <div class="apa-select-wrap">
                    <select class="apa-input" id="apa-service">
                        @foreach($serviceTypes as $st)<option value="{{ $st }}" @selected($st === $result['service'])>{{ $st }}</option>@endforeach
                    </select>
                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
            <div class="apa-field">
                <label>Event Date</label>
                <div class="apa-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <input type="date" class="apa-input" id="apa-date" value="{{ now()->addDays(16)->format('Y-m-d') }}">
                </div>
            </div>
            <div class="apa-field">
                <label>Location</label>
                <div class="apa-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <select class="apa-input" id="apa-location" style="padding-left:38px;">
                        @foreach($locations as $loc)<option value="{{ $loc }}" @selected($loc === $result['location'])>{{ $loc }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button type="button" class="apa-btn" id="apa-calc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8" y2="10"/><line x1="12" y1="10" x2="12" y2="10"/><line x1="16" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="8" y1="18" x2="16" y2="18"/></svg>Calculate Price</button>
        </div>

        {{-- recommended --}}
        <div class="apa-rec">
            <h3>{{ $isMax ? 'AI-Set Price' : 'Suggested Price' }}</h3>
            <div class="apa-rec-top">
                <span class="apa-rec-price" id="apa-price">{{ $money($result['price']) }}</span>
                <span class="apa-badge" id="apa-badge" style="background:{{ $result['badgeColor'] }}1f;color:{{ $result['badgeColor'] }};">{{ $result['badge'] }}</span>
            </div>
            <hr>
            <div class="apa-rec-rk">Price Range in <span id="apa-rk-city">{{ $result['city'] }}</span></div>
            <div class="apa-rec-rv" id="apa-range">{{ $money($result['low']) }} – {{ $money($result['high']) }}</div>
            <hr>
            <div class="apa-mi-title">Market Insights</div>
            <div id="apa-insights">
                @include('client.ai-tools._pricing_insights', ['insights' => $result['insights']])
            </div>
            <div class="apa-tip"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21h6v-1H9v1zm3-19A7 7 0 0 0 5 9c0 2.4 1.2 4.5 3 5.7V17h8v-2.3c1.8-1.2 3-3.3 3-5.7a7 7 0 0 0-7-7z"/></svg><span><b>Tip:</b> <span id="apa-tip-text">{{ $result['tip'] }}</span></span></div>
        </div>
    </div>

    {{-- ════════ why use ════════ --}}
    <div class="apa-card" style="margin-bottom:20px;">
        <div class="apa-why-h">Why Use Pricing Calculator?</div>
        <div class="apa-why">
            <div class="apa-why-card"><span class="apa-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span><div><b>Price with Confidence</b><p>Get data-backed pricing based on real market trends.</p></div></div>
            <div class="apa-why-card"><span class="apa-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><div><b>Win More Bookings</b><p>Competitive pricing helps you stand out and get hired.</p></div></div>
            <div class="apa-why-card"><span class="apa-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div><b>Maximize Your Earnings</b><p>Avoid underpricing or overpricing your services.</p></div></div>
            <div class="apa-why-card"><span class="apa-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>Save Time</b><p>No more guessing—get the right price in seconds.</p></div></div>
        </div>
    </div>

    {{-- ════════ market comparison ════════ --}}
    <div class="apa-card" style="margin-bottom:20px;">
        <div class="apa-mc-h">
            <b>Market Comparison in <span id="apa-mc-city">{{ $result['city'] }}</span> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></b>
            <span class="apa-badge" style="background:rgba(217,119,6,0.1);color:var(--apa-strong);" id="apa-mc-svc">{{ $result['service'] }}</span>
        </div>
        <div class="apa-mc-body">
            <div class="apa-mc-left">
                <div class="apa-mc-scale">
                    <div class="c"><div class="k">Low End</div><div class="v" id="apa-mc-low">{{ $money($result['marketLow']) }}</div></div>
                    <div class="c"><div class="k">Average</div><div class="v" id="apa-mc-avg">{{ $money($result['marketAvg']) }}</div></div>
                    <div class="c"><div class="k">High End</div><div class="v" id="apa-mc-high">{{ $money($result['marketHigh']) }}</div></div>
                </div>
                <div class="apa-mc-track">
                    <span class="apa-mc-marker" id="apa-mc-marker" style="left:{{ $result['pos'] }}%;"></span>
                    <span class="apa-mc-flag" id="apa-mc-flag" style="left:{{ $result['pos'] }}%;"><span class="k">Your Recommended Price</span><div class="v" id="apa-mc-flag-price">{{ $money($result['price']) }}</div></span>
                </div>
                <div style="height:42px;"></div>
            </div>
            <div class="apa-mc-means">
                <b>What This Means</b>
                <p id="apa-mc-means">{{ $result['means'] }}</p>
            </div>
        </div>
    </div>

    {{-- ════════ how it works + adjust ════════ --}}
    <div class="apa-row2">
        <div class="apa-card">
            <div class="apa-card-h">How It Works (For Clients)</div>
            <div class="apa-steps">
                <div class="apa-step"><span class="apa-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span><b>1. Enter Details</b><p>Add your service type, date, and location.</p></div>
                <span class="apa-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="apa-step"><span class="apa-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg></span><b>2. Smart Analysis</b><p>We scan local market data.</p></div>
                <span class="apa-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="apa-step"><span class="apa-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><b>3. Get Recommendation</b><p>Receive the best price and market insights.</p></div>
                <span class="apa-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <div class="apa-step"><span class="apa-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></span><b>4. Price with Confidence</b><p>Use the data to set your price and win more gigs.</p></div>
            </div>
        </div>
        <div class="apa-card">
            <div class="apa-card-h">Adjust &amp; Recalculate</div>
            <div class="apa-slider">
                <div class="apa-slider-top"><span class="k">Experience Level</span><span class="v" id="apa-exp-lbl">Intermediate</span></div>
                <input type="range" class="apa-range" id="apa-exp" min="0" max="2" step="1" value="1">
            </div>
            <div class="apa-slider">
                <div class="apa-slider-top"><span class="k">Set Duration</span><span class="v" id="apa-dur-lbl">4 Hours</span></div>
                <input type="range" class="apa-range" id="apa-dur" min="1" max="12" step="1" value="4">
            </div>
            <div class="apa-slider">
                <div class="apa-slider-top"><span class="k">Equipment Quality</span><span class="v" id="apa-equip-lbl">Premium</span></div>
                <input type="range" class="apa-range" id="apa-equip" min="0" max="2" step="1" value="2">
            </div>
            <div class="apa-slider">
                <div class="apa-slider-top"><span class="k">Event Size</span><span class="v" id="apa-size-lbl">100 Guests</span></div>
                <input type="range" class="apa-range" id="apa-size" min="10" max="500" step="10" value="100">
            </div>
            <button type="button" class="apa-recalc" id="apa-recalc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Recalculate Price</button>
        </div>
    </div>

    {{-- ════════ recent + tips ════════ --}}
    <div class="apa-row3">
        <div class="apa-card">
            <div class="apa-card-h">Recent Price Calculations</div>
            <table class="apa-tbl">
                <thead><tr><th>Service Type</th><th>Date</th><th>Location</th><th>Recommended Price</th></tr></thead>
                <tbody>
                    @forelse($recent as $r)
                        <tr>
                            <td class="svc">{{ $r['service'] }}</td>
                            <td>{{ $r['date'] ? $r['date']->format('M d, Y') : '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r['location'], 18) }}</td>
                            <td class="price">{{ $money($r['price']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:24px;">No calculations yet. Create an event to see price estimates here.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(count($recent))<a href="{{ route('client.events.index') }}" class="apa-viewall">View All History <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>@endif
        </div>
        <div class="apa-card apa-tips-wrap">
            <div class="apa-card-h">Pro Tips for Better Pricing</div>
            @foreach([
                'Keep your profile and portfolio updated.',
                'Offer package options (Basic, Standard, Premium).',
                'Adjust prices for peak seasons and weekends.',
                'Highlight what makes your service unique.',
                'Use Pricing Calculator before every new gig!',
            ] as $tip)
                <div class="apa-tip-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span>{{ $tip }}</span></div>
            @endforeach
            <svg class="apa-bulb" viewBox="0 0 120 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="62" cy="46" r="26" fill="#fef3c7"/>
                <path d="M62 24a18 18 0 0 0-10 33v7h20v-7a18 18 0 0 0-10-33z" fill="#fde68a" stroke="#f59e0b" stroke-width="2"/>
                <rect x="54" y="66" width="16" height="9" rx="2" fill="#f59e0b"/>
                <rect x="56" y="76" width="12" height="5" rx="2" fill="#d97706"/>
                <text x="62" y="52" font-size="18" font-weight="800" fill="#d97706" text-anchor="middle">$</text>
                <path d="M30 30l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#fbbf24"/>
                <path d="M98 60l1.5 4 4 1.5-4 1.5-1.5 4-1.5-4-4-1.5 4-1.5z" fill="#fcd34d"/>
            </svg>
        </div>
    </div>

    {{-- ════════ bottom banner ════════ --}}
    <div class="apa-banner">
        <span class="apa-banner-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <div class="apa-banner-txt"><b>Smart Pricing. More Bookings. Higher Earnings.</b><p>Let AI do the math so you can focus on creating unforgettable events.</p></div>
    </div>
    @endif
</div>

<script>
(function () {
    const root = document.querySelector('.apa');
    if (!root) return;
    if (!document.getElementById('apa-calc')) return; // Do It Myself (manual) — no AI form
    const url = root.dataset.calcUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    const money = (n) => '$' + Number(n).toLocaleString('en-US');

    const EXP = ['Beginner', 'Intermediate', 'Expert'];
    const EQUIP = ['Basic', 'Standard', 'Premium'];

    function syncLabels() {
        $('apa-exp-lbl').textContent = EXP[+$('apa-exp').value];
        $('apa-dur-lbl').textContent = $('apa-dur').value + ' Hours';
        $('apa-equip-lbl').textContent = EQUIP[+$('apa-equip').value];
        $('apa-size-lbl').textContent = $('apa-size').value + ' Guests';
    }
    ['apa-exp', 'apa-dur', 'apa-equip', 'apa-size'].forEach((id) => $(id).addEventListener('input', syncLabels));

    function insightSvg(icon, color) {
        if (icon === 'fire') return '<svg viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.07-2.14-.22-4.05 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.15.43-2.29 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
        if (icon === 'badge') return '<svg viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
    }

    function render(d) {
        $('apa-price').textContent = money(d.price);
        const badge = $('apa-badge');
        badge.textContent = d.badge;
        badge.style.background = d.badgeColor + '1f';
        badge.style.color = d.badgeColor;
        $('apa-rk-city').textContent = d.city;
        $('apa-range').textContent = money(d.low) + ' – ' + money(d.high);
        $('apa-tip-text').textContent = d.tip;
        $('apa-insights').innerHTML = d.insights.map((i) =>
            '<div class="apa-mi">' + insightSvg(i.icon, i.color) + '<span>' + i.text + '</span></div>'
        ).join('');
        // market comparison
        $('apa-mc-city').textContent = d.city;
        $('apa-rk-city').textContent = d.city;
        $('apa-mc-svc').textContent = d.service;
        $('apa-mc-low').textContent = money(d.marketLow);
        $('apa-mc-avg').textContent = money(d.marketAvg);
        $('apa-mc-high').textContent = money(d.marketHigh);
        $('apa-mc-marker').style.left = d.pos + '%';
        $('apa-mc-flag').style.left = d.pos + '%';
        $('apa-mc-flag-price').textContent = money(d.price);
        $('apa-mc-means').textContent = d.means;
    }

    async function calculate(btn) {
        const original = btn.innerHTML;
        btn.disabled = true; btn.style.opacity = '0.7';
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    service_type: $('apa-service').value,
                    location: $('apa-location').value,
                    event_date: $('apa-date').value,
                    experience: EXP[+$('apa-exp').value],
                    duration: +$('apa-dur').value,
                    equipment: EQUIP[+$('apa-equip').value],
                    event_size: +$('apa-size').value,
                }),
            });
            if (res.ok) render(await res.json());
        } catch (e) { /* keep last result on failure */ }
        finally { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = original; }
    }

    $('apa-calc').addEventListener('click', function () { calculate(this); });
    $('apa-recalc').addEventListener('click', function () { calculate(this); });
    $('apa-try')?.addEventListener('click', function (e) { e.preventDefault(); $('apa-calc').click(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
    syncLabels();
})();

// Do It Myself — manual price worksheet (no AI)
(function () {
    const rate = document.getElementById('pm-rate');
    if (!rate) return;
    const hours = document.getElementById('pm-hours');
    const addons = document.getElementById('pm-addons');
    const out = document.getElementById('pm-total');
    function calc() {
        const t = (parseFloat(rate.value) || 0) * (parseFloat(hours.value) || 0) + (parseFloat(addons.value) || 0);
        out.textContent = '$' + Number(t).toLocaleString(undefined, { maximumFractionDigits: 0 });
    }
    [rate, hours, addons].forEach(el => el.addEventListener('input', calc));
    calc();
})();
</script>
@endsection
