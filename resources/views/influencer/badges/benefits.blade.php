@extends('layouts.influencer-portal')
@section('title', 'Tier Benefits')
@push('styles') @include('influencer.badges._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.tiers') }}">Badges &amp; Tiers</a> <span class="sep">›</span> Tier Benefits</div>
<div class="bt-head"><h1>Tier Benefits</h1><p>Everything you unlock at each tier as you grow.</p></div>

<div class="bt-tiers" style="margin-top:22px;">
    @foreach($tiers as $key => $t)
        @php $idx = array_search($key, $tierKeys, true); @endphp        <div class="bt-tier {{ $key === $currentKey ? 'current' : '' }}">
            @if($key === $currentKey)<div class="bt-tier-flag">★ Your Tier</div>@endif
            <div class="bt-tier-badge"><x-influencer.hex-badge :color="$t['color']" :icon="$t['icon']" size="64" /></div>
            <h3>{{ $t['label'] }}</h3>
            <span class="bt-tier-pill">Tier {{ $idx + 1 }}</span>
            <div style="text-align:center; font-family:var(--ff); font-size:24px; font-weight:800; color:{{ $t['color'] }}; margin:14px 0 4px;">{{ $t['rate'] }}%</div>
            <div style="text-align:center; font-size:11.5px; color:var(--muted); margin-bottom:14px;">commission rate</div>
            <div class="bt-ben-lbl">What you get</div>
            @foreach($t['benefits'] as $ben)
                <div class="bt-ben"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $ben }}</div>
            @endforeach
            <div style="margin-top:auto; padding-top:14px;">
                <div class="bt-req-lbl">Requirement</div>
                <div class="bt-req" style="margin-bottom:0;">{{ $t['min_referrals'] == 0 ? 'Start here' : $t['min_referrals'].'+ referrals' }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="bt-panel" style="text-align:center;">
    <h3>Keep Climbing 🚀</h3>
    <div class="sub" style="max-width:50ch; margin:6px auto 14px;">Every member you refer moves you closer to the next tier and a higher commission rate. Share your link and grow your earnings.</div>
    <a href="{{ route('influencer.dashboard.referrals') }}" style="display:inline-block; background:var(--orange); color:#fff; padding:11px 22px; border-radius:11px; font-family:var(--ff); font-weight:700; font-size:13.5px;">Go to Referral Center</a>
</div>
@endsection
