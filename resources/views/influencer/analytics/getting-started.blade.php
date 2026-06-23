@extends('layouts.influencer-portal')
@section('title', 'Analytics — Getting Started')
@push('styles') @include('influencer.analytics._styles') @endpush

@section('content')
<div class="an-head"><div><h1>Analytics — Getting Started</h1><p>A quick tour of everything you can track in your influencer analytics.</p></div></div>

<div class="an-grid-2" style="margin-top:18px;">
    <div class="an-panel">
        <div class="an-panel-head"><h3>What you can track</h3></div>
        @foreach([
            ['Performance','Clicks, conversions, earnings & conversion rate over time.','performance','#f97316','var(--orange-soft)'],
            ['Campaign Performance','See which campaigns convert and earn the most.','campaigns','#2563eb','var(--blue-soft)'],
            ['Audience Insights','Demographics, locations, interests & devices.','audience','#7c3aed','#ede9fe'],
            ['Content Metrics','Top content by views, clicks & engagement.','content','#16a34a','#dcfce7'],
        ] as [$t,$d,$route,$c,$bg])
            <a href="{{ route('influencer.analytics.'.$route) }}" style="display:flex; align-items:center; gap:13px; padding:14px 0; border-bottom:1px solid var(--line);">
                <span style="width:42px;height:42px;border-radius:12px;background:{{ $bg }};color:{{ $c }};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
                <div style="flex:1;"><div style="font-family:var(--ff); font-weight:700; color:var(--ink); font-size:14.5px;">{{ $t }}</div><div style="font-size:12.5px; color:var(--muted);">{{ $d }}</div></div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2.4"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @endforeach
    </div>
    <div>
        <div class="an-panel" style="margin-bottom:18px;">
            <div class="an-panel-head"><h3>How it works</h3></div>
            <div style="font-size:13.5px; color:var(--text); line-height:1.7;">
                <p style="margin-bottom:10px;">Every click on your referral link, every signup, and every booking is tracked automatically and rolled up into these dashboards.</p>
                <p>Data refreshes continuously, so the numbers you see reflect your latest activity.</p>
            </div>
        </div>
        <div class="an-panel" style="background:var(--orange-soft); border-color:#ffe2cd; text-align:center;">
            <h3 style="margin-bottom:6px;">Ready to dive in?</h3>
            <p style="font-size:13px; color:var(--text); margin-bottom:14px;">Start with your performance overview.</p>
            <a href="{{ route('influencer.analytics.performance') }}" style="display:inline-block; background:var(--orange); color:#fff; padding:11px 22px; border-radius:11px; font-family:var(--ff); font-weight:700; font-size:13.5px;">View Performance</a>
        </div>
    </div>
</div>
@endsection
