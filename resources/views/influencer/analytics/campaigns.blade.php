@extends('layouts.influencer-portal')
@section('title', 'Campaign Performance')
@push('styles') @include('influencer.analytics._styles') @endpush

@php
    $fmt = fn ($n) => $n >= 1000 ? round($n/1000,1).'K' : number_format($n);
    $active = $campaigns->where('status','active')->count();
    $totalClicks = $campaigns->sum('clicks'); $totalConv = $campaigns->sum('conversions'); $totalEarn = $campaigns->sum('earnings');
@endphp

@section('content')
<div class="an-head"><div><h1>Campaign Performance</h1><p>Track how each of your campaigns is converting and earning.</p></div></div>

<div class="an-tiles">
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l16-5v12L3 13v-2z"/><path d="M11 18.5a3 3 0 0 1-5.5-1.5"/></svg></span><span class="lbl">Active Campaigns</span></div><div class="v">{{ $active }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg></span><span class="lbl">Total Clicks</span></div><div class="v">{{ $fmt($totalClicks) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span><span class="lbl">Conversions</span></div><div class="v">{{ $fmt($totalConv) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#15803d;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><span class="lbl">Campaign Earnings</span></div><div class="v">${{ $fmt($totalEarn) }}</div></div>
</div>

<div class="an-panel">
    <div class="an-panel-head"><h3>All Campaigns</h3></div>
    <table class="an-table">
        <thead><tr><th>Campaign</th><th>Channel</th><th>Status</th><th style="text-align:right;">Clicks</th><th style="text-align:right;">Conv.</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Earnings</th></tr></thead>
        <tbody>
            @foreach($campaigns as $c)
                <tr>
                    <td class="name">{{ $c->name }}</td>
                    <td style="text-transform:capitalize; color:var(--muted);">{{ $c->channel }}</td>
                    <td><span class="an-pill an-pill-{{ $c->status }}">{{ $c->status }}</span></td>
                    <td class="num">{{ $fmt($c->clicks) }}</td>
                    <td class="num">{{ $fmt($c->conversions) }}</td>
                    <td class="num">{{ $c->conversionRate() }}%</td>
                    <td class="amt">${{ $fmt($c->earnings) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
