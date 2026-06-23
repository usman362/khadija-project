@extends('layouts.influencer-portal')
@section('title', 'Reports')
@push('styles') @include('influencer.analytics._styles') @endpush

@php $fmt = fn ($n) => $n >= 1000000 ? round($n/1000000,1).'M' : ($n >= 1000 ? round($n/1000,1).'K' : number_format($n)); @endphp
@section('content')
<div class="an-head"><div><h1>Reports</h1><p>Summaries of your performance, ready to review or export.</p></div></div>

<div class="an-tiles" style="margin-top:18px;">
    <div class="an-tile"><div class="lbl">Clicks (30d)</div><div class="v">{{ $fmt($totals['clicks']) }}</div></div>
    <div class="an-tile"><div class="lbl">Conversions (30d)</div><div class="v">{{ $fmt($totals['conversions']) }}</div></div>
    <div class="an-tile"><div class="lbl">Earnings (30d)</div><div class="v">${{ $fmt($totals['earnings']) }}</div></div>
    <div class="an-tile"><div class="lbl">Conversion Rate</div><div class="v">{{ $totals['conversion_rate'] }}%</div></div>
</div>

<div class="an-grid-2">
    <div class="an-panel">
        <div class="an-panel-head"><h3>Available Reports</h3></div>
        @foreach([
            ['Performance Summary','Clicks, conversions & earnings over time','performance'],
            ['Campaign Report','How each campaign is converting','campaigns'],
            ['Audience Report','Demographics, locations & interests','audience'],
            ['Content Report','Top content by views & engagement','content'],
        ] as [$t,$d,$route])
            <a href="{{ route('influencer.analytics.'.$route) }}" style="display:flex; align-items:center; gap:13px; padding:13px 0; border-bottom:1px solid var(--line);">
                <span style="width:38px;height:38px;border-radius:11px;background:var(--orange-soft);color:var(--orange);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                <div style="flex:1;"><div style="font-family:var(--ff); font-weight:600; color:var(--ink); font-size:14px;">{{ $t }}</div><div style="font-size:12.5px; color:var(--muted);">{{ $d }}</div></div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @endforeach
    </div>
    <div class="an-panel" style="height:fit-content;">
        <div class="an-panel-head"><h3>Export Data</h3></div>
        <p style="font-size:13px; color:var(--muted); line-height:1.55; margin-bottom:14px;">Download your raw daily analytics as a CSV file for your own records or further analysis.</p>
        <a href="{{ route('influencer.analytics.export') }}" style="display:inline-flex; align-items:center; gap:8px; background:var(--orange); color:#fff; padding:11px 20px; border-radius:11px; font-family:var(--ff); font-weight:700; font-size:13.5px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download CSV
        </a>
    </div>
</div>
@endsection
