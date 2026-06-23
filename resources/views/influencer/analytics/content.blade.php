@extends('layouts.influencer-portal')
@section('title', 'Content Metrics')
@push('styles') @include('influencer.analytics._styles') @endpush

@php
    $fmt = fn ($n) => $n >= 1000000 ? round($n/1000000,1).'M' : ($n >= 1000 ? round($n/1000,1).'K' : number_format($n));
    $totalViews = $content->sum('views'); $totalClicks = $content->sum('clicks');
    $avgEng = $content->count() ? round($content->avg('engagement_rate'),1) : 0;
    $platColors = ['instagram'=>'#db2777','youtube'=>'#ef4444','tiktok'=>'#0f172a','blog'=>'#2563eb','facebook'=>'#1877F2'];
@endphp

@section('content')
<div class="an-head"><div><h1>Content Metrics</h1><p>See which content drives the most views, clicks, and conversions.</p></div></div>

<div class="an-tiles">
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span><span class="lbl">Total Views</span></div><div class="v">{{ $fmt($totalViews) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11a3 3 0 1 0 6 0 3 3 0 0 0-6 0z"/><path d="M17.7 17.7 22 22"/></svg></span><span class="lbl">Total Clicks</span></div><div class="v">{{ $fmt($totalClicks) }}</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#fce7f3;color:#db2777;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l7.8-8.4a5.5 5.5 0 0 0 0-7.8z"/></svg></span><span class="lbl">Avg Engagement</span></div><div class="v">{{ $avgEng }}%</div></div>
    <div class="an-tile"><div class="top"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="lbl">Content Pieces</span></div><div class="v">{{ $content->count() }}</div></div>
</div>

<div class="an-panel">
    <div class="an-panel-head"><h3>Top Content</h3></div>
    <table class="an-table">
        <thead><tr><th>Title</th><th>Platform</th><th>Type</th><th style="text-align:right;">Views</th><th style="text-align:right;">Clicks</th><th style="text-align:right;">Engagement</th></tr></thead>
        <tbody>
            @foreach($content as $c)
                <tr>
                    <td class="name" style="max-width:280px;">{{ $c->title }}</td>
                    <td><span style="display:inline-flex; align-items:center; gap:6px; text-transform:capitalize; color:var(--muted);"><span style="width:8px;height:8px;border-radius:50%;background:{{ $platColors[$c->platform] ?? '#999' }};"></span>{{ $c->platform }}</span></td>
                    <td style="text-transform:capitalize; color:var(--muted);">{{ $c->type }}</td>
                    <td class="num">{{ $fmt($c->views) }}</td>
                    <td class="num">{{ $fmt($c->clicks) }}</td>
                    <td class="num" style="color:#db2777; font-weight:600;">{{ rtrim(rtrim(number_format($c->engagement_rate,1),'0'),'.') }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
