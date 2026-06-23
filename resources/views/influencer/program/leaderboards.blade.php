@extends('layouts.influencer-portal')
@section('title', 'Leaderboards & Challenges')
@push('styles') @include('influencer.program._styles') @endpush

@php
    $avatarColors = ['#f97316','#2563eb','#7c3aed','#16a34a','#db2777','#0891b2','#ca8a04'];
    $chalIcons = [
        'bolt'   => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'trophy' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        'cash'   => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
    ];
@endphp

@section('content')
<div class="pg-head"><h1>Leaderboards &amp; Challenges</h1><p>See where you rank and take on challenges to climb higher.</p></div>

@if($myRank)
<div class="pg-tiles" style="grid-template-columns:repeat(3,minmax(0,1fr));">
    <div class="pg-tile"><div class="ic" style="background:#fef3c7;color:#b45309;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/><path d="M4 22h16"/></svg></div><div class="v">#{{ $myRank }}</div><div class="l">Your Rank</div></div>
    <div class="pg-tile"><div class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="v">{{ $influencer->total_referrals }}</div><div class="l">Your Referrals</div></div>
    <div class="pg-tile"><div class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></div><div class="v" style="text-transform:capitalize;">{{ $influencer->commission_tier->label() }}</div><div class="l">Your Tier{{ $nextTier ? ' · '.$toNextTier.' to '.$nextTier['label'] : '' }}</div></div>
</div>
@endif

<div class="pg-grid two">
    <div class="pg-panel">
        <h3>Top Referrers</h3>
        <p class="sub">Ranked by total referrals across the program.</p>
        @foreach($board as $i => $member)
            @php $rank = $i + 1; @endphp            <div class="pg-rank-row {{ $member->id === $influencer->id ? 'me' : '' }}">
                <span class="pg-rank-no {{ $rank===1?'gold':($rank===2?'silver':($rank===3?'bronze':'')) }}">{{ $rank }}</span>
                <span class="pg-rank-av" style="background:{{ $avatarColors[$i % count($avatarColors)] }};">{{ strtoupper(\Illuminate\Support\Str::substr($member->full_name, 0, 1)) }}</span>
                <div class="nm">
                    <b>{{ $member->id === $influencer->id ? 'You' : $member->full_name }}</b>
                    <span style="text-transform:capitalize;">{{ $member->commission_tier->label() }} tier</span>
                </div>
                <div class="val"><b>{{ $member->total_referrals }}</b><span>referrals</span></div>
            </div>
        @endforeach
        @if($board->count() <= 1)
            <p style="font-size:12px; color:var(--muted); text-align:center; margin-top:8px;">More creators will appear here as the program grows.</p>
        @endif
    </div>

    <div class="pg-panel">
        <h3>Active Challenges</h3>
        <p class="sub">Complete these to boost your progress and earnings.</p>
        @foreach($challenges as $c)
            @php $pct = $c['target'] > 0 ? min(100, round($c['current'] / $c['target'] * 100)) : 0; @endphp            @php $done = $pct >= 100; @endphp            <div class="pg-chal" style="margin-bottom:12px;">
                <div class="top">
                    <span class="ic" style="background:{{ $c['color'] }}1a; color:{{ $c['color'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $chalIcons[$c['icon']] !!}</svg></span>
                    <div><b>{{ $c['title'] }}</b><small>{{ $c['desc'] }}</small></div>
                    @if($done)<span class="pg-pill paid" style="margin-left:auto;">Complete</span>@endif
                </div>
                <div class="prog-meta">
                    <span>{{ $c['unit']==='$' ? '$'.number_format($c['current'],0) : (int)$c['current'] }} / {{ $c['unit']==='$' ? '$'.number_format($c['target'],0) : (int)$c['target'] }}</span>
                    <span>{{ $pct }}%</span>
                </div>
                <div class="pg-bar"><span style="width:{{ $pct }}%; background:{{ $c['color'] }};"></span></div>
            </div>
        @endforeach
    </div>
</div>
@endsection
