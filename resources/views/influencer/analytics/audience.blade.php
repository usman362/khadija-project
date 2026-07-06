@extends('layouts.influencer-portal')
@section('title', 'Audience Insights')
@push('styles') @include('influencer.analytics._styles') @endpush

@php
    $fmt = fn ($n) => $n >= 1000000 ? round($n/1000000,1).'M' : ($n >= 1000 ? round($n/1000,1).'K' : number_format($n));
    $female = $gender['female'] ?? 50;
    $devColors = ['mobile'=>'#16a34a','desktop'=>'#2563eb','tablet'=>'#16a34a'];
    $acc=0; $devStops=[]; foreach($devices as $k=>$v){ $devStops[]=($devColors[$k]??'#999').' '.$acc.'% '.($acc+$v).'%'; $acc+=$v; }
    // growth chart (views)
    $vals = $daily->pluck('views'); $max = max(1,$vals->max()); $n = max(1,$vals->count()-1);
    $pts = $vals->values()->map(fn($v,$i)=>round(30+($i/$n)*520,1).','.round(170-($v/$max)*140,1))->implode(' ');
@endphp

@section('content')
<div class="an-head">
    <div><h1>Audience Insights</h1><p>Understand your audience better and create content that resonates.</p></div>
    <div class="an-actions"><a href="{{ route('influencer.analytics.export') }}" class="an-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export</a></div>
</div>

<div class="an-tiles five">
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><span class="lbl">Total Audience</span></div><div class="v">{{ $fmt($influencer->followers_count) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span><span class="lbl">New Followers</span></div><div class="v">{{ $fmt($newFollowers) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l7.8-8.4a5.5 5.5 0 0 0 0-7.8z"/></svg></span><span class="lbl">Engaged Audience</span></div><div class="v">{{ $fmt($totals['engagements']) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span><span class="lbl">Engagement Rate</span></div><div class="v">{{ rtrim(rtrim(number_format($influencer->engagement_rate,2),'0'),'.') }}%</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><span class="lbl">Earnings</span></div><div class="v">${{ $fmt($totals['earnings']) }}</div></div>
</div>

<div class="an-grid-3">
    <div class="an-panel">
        <div class="an-panel-head"><h3>Audience Growth</h3><span class="tag">Views · 30 days</span></div>
        <svg class="an-chart" viewBox="0 0 560 190" preserveAspectRatio="none" style="height:180px;">
            @for($i=0;$i<=4;$i++)<line x1="30" y1="{{ 30+$i*35 }}" x2="550" y2="{{ 30+$i*35 }}" stroke="var(--line)" stroke-width="1"/>@endfor
            <polyline fill="rgba(22,163,74,0.08)" stroke="none" points="30,170 {{ $pts }} 550,170"/>
            <polyline fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linejoin="round" points="{{ $pts }}"/>
        </svg>
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>Demographics</h3></div>
        <div style="display:flex; align-items:center; gap:18px; margin-bottom:14px;">
            <div class="an-donut" style="width:120px;height:120px; background: conic-gradient(#7c3aed 0% {{ $female }}%, #16a34a {{ $female }}% 100%);"><div class="an-donut-c"><b>{{ $female }}%</b><span>Female</span></div></div>
            <div style="font-size:13px;">
                <div style="display:flex; align-items:center; gap:7px; margin-bottom:8px;"><span style="width:9px;height:9px;border-radius:50%;background:#7c3aed;"></span> Female {{ $female }}%</div>
                <div style="display:flex; align-items:center; gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:#16a34a;"></span> Male {{ $gender['male'] ?? (100-$female) }}%</div>
            </div>
        </div>
        @foreach($age as $band => $pct)
            <div class="an-bar-row"><span class="nm">{{ $band }}</span><div class="an-bar"><span style="width:{{ min(100,$pct*2.5) }}%"></span></div><span class="pc">{{ $pct }}%</span></div>
        @endforeach
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>Top Locations</h3></div>
        @foreach($locations as $loc)
            <div class="an-bar-row"><span class="nm">{{ $loc['name'] }}</span><div class="an-bar"><span style="width:{{ min(100,$loc['pct']*2.4) }}%; background:var(--blue);"></span></div><span class="pc">{{ $loc['pct'] }}%</span></div>
        @endforeach
    </div>
</div>

<div class="an-grid-2" style="margin-top:18px;">
    <div class="an-panel">
        <div class="an-panel-head"><h3>Interests</h3><span class="tag">Based on audience activity</span></div>
        @foreach($interests as $int)
            <div class="an-bar-row"><span class="nm">{{ $int['name'] }}</span><div class="an-bar"><span style="width:{{ $int['pct'] }}%; background:#db2777;"></span></div><span class="pc">{{ $int['pct'] }}%</span></div>
        @endforeach
    </div>
    <div class="an-panel">
        <div class="an-panel-head"><h3>Device Usage</h3></div>
        <div class="an-donut-wrap">
            <div class="an-donut" style="background: conic-gradient({{ implode(',', $devStops) }});"><div class="an-donut-c"><b>{{ $devices['mobile'] ?? 0 }}%</b><span>mobile</span></div></div>
            <div class="an-legend">@foreach($devices as $k=>$v)<div class="row"><span class="dot" style="background:{{ $devColors[$k]??'#999' }};"></span><span class="nm" style="text-transform:capitalize;">{{ $k }}</span><span class="pc">{{ $v }}%</span></div>@endforeach</div>
        </div>
    </div>
</div>

<div class="an-summary">
    <span class="ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg></span>
    <div class="m"><b>Audience Insights Summary</b><p>Your audience is mostly {{ $female }}% female, with the largest group aged 25–34. Most engage on mobile from the United States.</p></div>
    <a href="{{ route('influencer.analytics.content') }}">Content Metrics →</a>
</div>
@endsection
