@extends('layouts.influencer-portal')
@section('title', 'Performance')
@push('styles') @include('influencer.analytics._styles') @endpush

@php
    $fmt = fn ($n) => $n >= 1000000 ? round($n/1000000,1).'M' : ($n >= 1000 ? round($n/1000,1).'K' : number_format($n));
    // line chart for clicks
    $vals = $daily->pluck('clicks'); $max = max(1, $vals->max()); $n = max(1, $vals->count()-1);
    $pts = $vals->values()->map(fn($v,$i) => round(30+($i/$n)*520,1).','.round(170-($v/$max)*140,1))->implode(' ');
    // channel donut
    $chanColors = ['#16a34a','#2563eb','#16a34a','#4ade80','#7c3aed'];
    $acc = 0; $chanStops = []; $ci = 0;
    foreach($channels as $name => $pct){ $chanStops[] = $chanColors[$ci%5].' '.$acc.'% '.($acc+$pct).'%'; $acc += $pct; $ci++; }
    if($acc < 100) $chanStops[] = '#e7ebf2 '.$acc.'% 100%';
    // device donut
    $devColors = ['mobile'=>'#16a34a','desktop'=>'#2563eb','tablet'=>'#16a34a'];
    $acc2 = 0; $devStops = [];
    foreach($devices as $k => $v){ $devStops[] = ($devColors[$k] ?? '#999').' '.$acc2.'% '.($acc2+$v).'%'; $acc2 += $v; }
    // clicks vs conversions bars (last 6 points)
    $barData = $daily->slice(-6)->values();
    $barMax = max(1, $barData->max('clicks'));
@endphp

@section('content')
<div class="an-head">
    <div><h1>Performance</h1><p>Track your overall performance and key metrics across your campaigns and content.</p></div>
    <div class="an-actions">
        <a href="{{ route('influencer.analytics.export') }}" class="an-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Report</a>
        <span class="an-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg> Last 30 days</span>
    </div>
</div>

<div class="an-tiles">
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11a3 3 0 1 0 6 0 3 3 0 0 0-6 0z"/><path d="M17.7 17.7 22 22"/><path d="M2 12a10 10 0 1 0 10-10"/></svg></span><span class="lbl">Total Clicks</span></div><div class="v">{{ $fmt($totals['clicks']) }}</div><div class="tr">↑ Last 30 days</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span><span class="lbl">Conversions</span></div><div class="v">{{ $fmt($totals['conversions']) }}</div><div class="tr">↑ Last 30 days</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><span class="lbl">Total Earnings</span></div><div class="v">${{ $fmt($totals['earnings']) }}</div><div class="tr">↑ Last 30 days</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#15803d;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><span class="lbl">Conversion Rate</span></div><div class="v">{{ $totals['conversion_rate'] }}%</div><div class="tr">↑ Last 30 days</div></div>
</div>

<div class="an-grid-2">
    <div class="an-panel">
        <div class="an-panel-head"><h3>Performance Overview</h3><span class="tag">Clicks · Last 30 days</span></div>
        <svg class="an-chart" viewBox="0 0 560 190" preserveAspectRatio="none">
            @for($i=0;$i<=4;$i++)<line x1="30" y1="{{ 30+$i*35 }}" x2="550" y2="{{ 30+$i*35 }}" stroke="var(--line)" stroke-width="1"/>@endfor
            <polyline fill="rgba(22,163,74,0.08)" stroke="none" points="30,170 {{ $pts }} 550,170"/>
            <polyline fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $pts }}"/>
            @php $dv = $daily->values(); $axisIdx = collect([0, intdiv($n,3), intdiv(2*$n,3), $n])->unique()->filter(fn($i) => isset($dv[$i]) && $dv[$i]->date)->values(); @endphp
            @foreach($axisIdx as $i)<text class="an-axis" x="{{ 30+($i/$n)*520 }}" y="186" text-anchor="middle">{{ $dv[$i]->date->format('M j') }}</text>@endforeach
        </svg>
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>By Channel</h3></div>
        <div class="an-donut-wrap">
            <div class="an-donut" style="background: conic-gradient({{ implode(',', $chanStops) }});"><div class="an-donut-c"><b>{{ $fmt($totals['clicks']) }}</b><span>clicks</span></div></div>
            <div class="an-legend">
                @php $ci2=0; @endphp                @foreach($channels as $name => $pct)
                    <div class="row"><span class="dot" style="background:{{ $chanColors[$ci2%5] }};"></span><span class="nm">{{ $name }}</span><span class="pc">{{ $pct }}%</span></div>
                    @php $ci2++; @endphp                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="an-grid-3">
    <div class="an-panel">
        <div class="an-panel-head"><h3>Top Campaigns</h3><a href="{{ route('influencer.analytics.campaigns') }}">View all →</a></div>
        <table class="an-table">
            <thead><tr><th>Campaign</th><th style="text-align:right;">Clicks</th><th style="text-align:right;">Earn</th></tr></thead>
            <tbody>
                @foreach($campaigns->take(5) as $c)
                    <tr><td class="name">{{ $c->name }}</td><td class="num">{{ $fmt($c->clicks) }}</td><td class="amt">${{ $fmt($c->earnings) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>Clicks vs Conversions</h3></div>
        <div class="an-bars">
            @foreach($barData as $d)
                <div class="col"><div class="bar"><i style="height:{{ max(3,(int)($d->conversions/$barMax*120)) }}px; background:#4ade80;"></i><i style="height:{{ max(3,(int)($d->clicks/$barMax*120)) }}px; background:#16a34a;"></i></div><span class="x">{{ $d->date->format('M j') }}</span></div>
            @endforeach
        </div>
        <div style="display:flex; gap:16px; margin-top:10px; font-size:11.5px; color:var(--muted);"><span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:#16a34a;"></span> Clicks</span><span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:#4ade80;"></span> Conversions</span></div>
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>Device Breakdown</h3></div>
        <div class="an-donut-wrap">
            <div class="an-donut" style="background: conic-gradient({{ implode(',', $devStops) }});"><div class="an-donut-c"><b>{{ $devices['mobile'] ?? 0 }}%</b><span>mobile</span></div></div>
            <div class="an-legend">
                @foreach($devices as $k => $v)<div class="row"><span class="dot" style="background:{{ $devColors[$k] ?? '#999' }};"></span><span class="nm" style="text-transform:capitalize;">{{ $k }}</span><span class="pc">{{ $v }}%</span></div>@endforeach
            </div>
        </div>
    </div>
</div>

<div class="an-summary">
    <span class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.5 12.5 17 22l-5-3-5 3 1.5-9.5"/></svg></span>
    <div class="m"><b>You're performing great! 🎉</b><p>{{ $fmt($totals['clicks']) }} clicks and {{ $fmt($totals['conversions']) }} conversions over the last 30 days, at a {{ $totals['conversion_rate'] }}% conversion rate.</p></div>
    <a href="{{ route('influencer.analytics.reports') }}">View Full Report →</a>
</div>
@endsection
