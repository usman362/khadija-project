@extends('layouts.influencer-portal')
@section('title', 'Progress')
@push('styles') @include('influencer.badges._styles') @endpush

@php $ct = $tiers[$currentKey]; @endphp
@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.tiers') }}">Badges &amp; Tiers</a> <span class="sep">›</span> Progress</div>
<div class="bt-head"><h1>Your Progress</h1><p>See how far you've come and what it takes to reach the next tier.</p></div>

{{-- progress hero --}}
<div class="bt-panel" style="margin-top:18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <x-influencer.hex-badge :color="$ct['color']" :icon="$ct['icon']" size="56" />
            <div><div style="font-size:12px; color:var(--muted);">Current Tier</div><div style="font-family:var(--ff); font-size:20px; font-weight:800; color:var(--ink);">{{ $ct['label'] }} · {{ $ct['rate'] }}%</div></div>
        </div>
        <div style="text-align:right;">
            <div style="font-family:var(--ff); font-size:24px; font-weight:800; color:var(--ink);">{{ $influencer->total_referrals }}</div>
            <div style="font-size:12px; color:var(--muted);">total referrals</div>
        </div>
    </div>
    @if($nextTier)
        <div style="display:flex; justify-content:space-between; font-size:12.5px; color:var(--muted); margin-top:18px;">
            <span>{{ $ct['label'] }}</span><span>{{ $referralsToNext }} more to <b style="color:var(--orange-dark);">{{ $nextTier['label'] }}</b></span>
        </div>
        <div class="bt-progress"><span style="width:{{ $progressPct }}%"></span></div>
    @else
        <div style="margin-top:16px; text-align:center; color:#16a34a; font-family:var(--ff); font-weight:700;">🎉 You've reached the highest tier — incredible work!</div>
    @endif
</div>

{{-- tier ladder --}}
<div class="bt-panel">
    <h3>Tier Ladder</h3>
    <div class="sub">Every tier unlocks a higher commission rate. Here's the full path.</div>
    <div style="margin-top:18px;">
        @foreach($tiers as $key => $t)
            @php $idx = array_search($key, $tierKeys, true); @endphp            @php $done = $idx <= $currentIndex; @endphp            <div style="display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:1px solid var(--line);">
                <div style="opacity:{{ $done ? 1 : .45 }};"><x-influencer.hex-badge :color="$t['color']" :icon="$t['icon']" size="44" /></div>
                <div style="flex:1; min-width:0;">
                    <div style="font-family:var(--ff); font-weight:700; color:var(--ink); font-size:14.5px;">{{ $t['label'] }} <span style="font-size:11px; color:var(--muted); font-weight:600;">· Tier {{ $idx+1 }}</span></div>
                    <div style="font-size:12.5px; color:var(--muted);">{{ $t['min_referrals'] == 0 ? 'Starting tier' : $t['min_referrals'].'+ referrals' }} · {{ $t['rate'] }}% commission</div>
                </div>
                @if($key === $currentKey)
                    <span style="font-size:11px; font-weight:800; color:var(--orange-dark); background:var(--orange-soft); padding:4px 12px; border-radius:20px;">YOU ARE HERE</span>
                @elseif($done)
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><circle cx="12" cy="12" r="10" fill="#dcfce7" stroke="none"/><polyline points="16 9 11 15 8 12"/></svg>
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#cdd6e4" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
