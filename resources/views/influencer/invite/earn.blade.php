@extends('layouts.influencer-portal')
@section('title', 'Earn')
@push('styles') @include('influencer.invite._styles') @endpush

@php
    $series = collect($earningsSeries);
    $max = max(1, $series->max());
    $n = max(1, $series->count() - 1);
    $pts = $series->values()->map(fn($v,$i) => round(30 + ($i/$n)*520,1).','.round(170 - ($v/$max)*140,1))->implode(' ');
@endphp

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Earn</div>
<div class="iv-head"><h1>Earn More Rewards</h1><p>Your efforts deserve great rewards. Invite, engage, and earn with every successful referral.</p></div>

<div class="iv-tiles">
    <div class="iv-tile"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div><div class="v">${{ number_format($influencer->total_earnings, 0) }}</div><div class="l">Total Earnings</div></div></div>
    <div class="iv-tile"><span class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><div class="v">${{ number_format($pending, 0) }}</div><div class="l">Pending Earnings</div></div></div>
    <div class="iv-tile"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg></span><div><div class="v">${{ number_format($influencer->paid_out, 0) }}</div><div class="l">Total Paid</div></div></div>
    <div class="iv-tile"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><div><div class="v">{{ $signups }}</div><div class="l">Successful Referrals</div></div></div>
</div>

<div class="iv-layout" style="margin-top:0;">
    <div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
            <div class="iv-panel" style="margin-bottom:0;">
                <h3>How You Earn</h3>
                <div class="sub">You earn commissions when your referrals complete actions on GigResource.</div>
                <div class="iv-earn-row"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span><div class="m"><b>Successful Signup</b><span>Referred user creates an account</span></div><span class="amt">${{ number_format($signupBonus, 0) }}</span></div>
                <div class="iv-earn-row"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div class="m"><b>Event Booking</b><span>Referred user books an event</span></div><span class="amt">{{ config('influencer.tiers.'.$currentKey.'.rate', 5) }}%</span></div>
                <div class="iv-earn-row"><span class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span><div class="m"><b>Service Purchase</b><span>Referred user purchases a service</span></div><span class="amt">up to {{ config('influencer.tiers.'.$currentKey.'.rate', 5) }}%</span></div>
                <div class="iv-earn-row"><span class="ic" style="background:#dcfce7;color:#15803d;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></span><div class="m"><b>Repeat Activity</b><span>Referred user stays active</span></div><span class="amt">Bonus</span></div>
            </div>
            <div class="iv-panel" style="margin-bottom:0;">
                <div style="display:flex; align-items:center; justify-content:space-between;"><h3>Earnings Overview</h3><span style="font-size:12px;color:var(--muted);">Last 6 months</span></div>
                <div style="font-family:var(--ff); font-size:26px; font-weight:800; color:#16a34a; margin:8px 0 4px;">${{ number_format($influencer->total_earnings, 2) }}</div>
                <div style="font-size:12px; color:var(--muted); margin-bottom:10px;">Total Earnings</div>
                <svg viewBox="0 0 560 190" preserveAspectRatio="none" style="width:100%; height:160px;">
                    @for($i=0;$i<=4;$i++)<line x1="30" y1="{{ 30+$i*35 }}" x2="550" y2="{{ 30+$i*35 }}" stroke="var(--line)" stroke-width="1"/>@endfor
                    @if($series->sum()>0)
                        <polyline fill="rgba(22,163,74,0.08)" stroke="none" points="30,170 {{ $pts }} 550,170"/>
                        <polyline fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $pts }}"/>
                    @endif
                    @foreach($series->keys() as $i => $label)<text x="{{ 30+($i/$n)*520 }}" y="186" text-anchor="middle" font-size="11" fill="var(--muted)">{{ $label }}</text>@endforeach
                </svg>
                @if($series->sum()==0)<div style="text-align:center; color:var(--muted); font-size:12.5px; margin-top:-90px; position:relative;">Earnings will chart here as referrals convert.</div>@endif
            </div>
        </div>

        {{-- commission tiers --}}
        <div class="iv-panel" style="margin-top:18px;">
            <h3>Commission Tiers</h3>
            <div class="sub">The more you refer, the higher your tier and rate.</div>
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px;">
                @foreach($tiers as $key => $t)
                    <div style="border:1.5px solid {{ $key===$currentKey ? 'var(--orange)' : 'var(--line)' }}; border-radius:13px; padding:14px; text-align:center; position:relative; {{ $key===$currentKey ? 'background:var(--orange-soft);' : '' }}">
                        @if($key===$currentKey)<div style="position:absolute; top:-9px; left:50%; transform:translateX(-50%); background:var(--orange); color:#fff; font-size:9.5px; font-weight:800; padding:2px 9px; border-radius:20px; text-transform:uppercase;">Current</div>@endif
                        <div style="display:flex; justify-content:center; margin-bottom:8px;"><x-influencer.hex-badge :color="$t['color']" :icon="$t['icon']" size="40" /></div>
                        <div style="font-family:var(--ff); font-weight:700; color:var(--ink); font-size:13.5px;">{{ $t['label'] }}</div>
                        <div style="font-size:11px; color:var(--muted);">{{ $t['min_referrals']==0 ? '0' : $t['min_referrals'].'+' }} referrals</div>
                        <div style="font-family:var(--ff); font-weight:800; color:{{ $t['color'] }}; font-size:16px; margin-top:6px;">{{ $t['rate'] }}%</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- right rail --}}
    <div>
        <div class="iv-rail-card">
            <h4>Balance Breakdown</h4>
            <div class="iv-rail-stat"><div class="m"><div class="l">Available now</div></div><div class="v" style="font-family:var(--ff); font-weight:800; color:#16a34a;">${{ number_format($influencer->available_balance, 2) }}</div></div>
            <div class="iv-rail-stat"><div class="m"><div class="l">Pending</div></div><div class="v" style="font-family:var(--ff); font-weight:800; color:#15803d;">${{ number_format($pending, 2) }}</div></div>
            <div class="iv-rail-stat"><div class="m"><div class="l">Paid out</div></div><div class="v" style="font-family:var(--ff); font-weight:800; color:var(--ink);">${{ number_format($influencer->paid_out, 2) }}</div></div>
            <a href="{{ route('influencer.dashboard.payouts') }}" class="iv-rail-cta">View Payouts &amp; History <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="iv-rail-card">
            <h4>Payout Information</h4>
            <div style="font-size:13px; color:var(--text); line-height:2;">
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--muted);">Minimum payout</span><b style="color:var(--ink);">${{ number_format($minPayout, 0) }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--muted);">Methods</span><b style="color:var(--ink);">Bank, PayPal</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--muted);">Schedule</span><b style="color:var(--ink);">On request</b></div>
            </div>
        </div>
        <div class="iv-rail-card iv-rail-soft">
            <h4>Tips to Earn More</h4>
            <div class="iv-rail-list">
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Share on multiple platforms</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Create engaging content</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Encourage sign-ups &amp; bookings</div>
            </div>
            <a href="{{ route('influencer.invite.promote') }}" class="iv-rail-cta">Go to Promote <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>
@endsection
