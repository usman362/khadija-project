@extends('layouts.influencer-portal')
@section('title', 'Current Tier')
@push('styles') @include('influencer.badges._styles') @endpush

@php
    $ct = $tiers[$currentKey];
    $earnedBadges = collect($badges)->where('earned', true);
@endphp

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.tiers') }}">Badges &amp; Tiers</a> <span class="sep">›</span> Current Tier</div>
<div class="bt-head"><h1>Current Tier</h1><p>Track your progress, see your benefits, and unlock new opportunities.</p></div>

<div class="bt-layout" style="margin-top:18px;">
    <div>
        {{-- hero --}}
        <div class="bt-hero">
            <x-influencer.hex-badge :color="$ct['color']" :icon="$ct['icon']" size="104" />
            <div class="bt-hero-main">
                <span class="bt-hero-flag">Your Current Tier</span>
                <h2>{{ $ct['label'] }}</h2>
                <p>{{ $ct['tagline'] }} You're earning <b>{{ $ct['rate'] }}%</b> commission on every successful referral.</p>
                <a href="{{ route('influencer.badges.benefits') }}" style="display:inline-flex; align-items:center; gap:6px; margin-top:12px; color:var(--orange-dark); font-family:var(--ff); font-weight:700; font-size:13px;">View all tier benefits <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>

        {{-- stats --}}
        <div class="bt-stats">
            <div class="bt-stat"><div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Total Referrals</div><div class="v">{{ $influencer->total_referrals }}</div></div>
            <div class="bt-stat"><div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg> Commission Rate</div><div class="v orange">{{ $ct['rate'] }}%</div></div>
            <div class="bt-stat"><div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg> Next Tier</div><div class="v">{{ $nextTier ? $nextTier['label'] : 'Maxed!' }}</div></div>
            <div class="bt-stat"><div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Total Earned</div><div class="v">${{ number_format($influencer->total_earnings, 0) }}</div></div>
        </div>

        {{-- progress --}}
        <div class="bt-panel">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div><h3>Your Progress</h3><div class="sub">@if($nextTier) Refer {{ $referralsToNext }} more to reach <b>{{ $nextTier['label'] }}</b>. @else You've reached the highest tier — amazing work! @endif</div></div>
                <div style="font-family:var(--ff); font-weight:800; color:var(--ink);">
                    {{ $influencer->total_referrals }} @if($nextTier) / {{ $nextTier['min_referrals'] }} @endif referrals
                </div>
            </div>
            <div class="bt-progress"><span style="width:{{ $progressPct }}%"></span></div>
            @if(!$nextTier)<div style="text-align:right; color:#16a34a; font-size:12.5px; font-weight:600; margin-top:8px;">You're well above the requirement! 🎉</div>@endif
        </div>

        {{-- recent activity + how to earn --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-top:18px;">
            <div class="bt-panel" style="margin-top:0;">
                <h3 style="font-size:15px;">Recent Activity</h3>
                <div style="margin-top:12px;">
                    @forelse($influencer->referrals()->latest()->limit(5)->get() as $r)
                        <div style="display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--line);">
                            <div style="width:30px;height:30px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#16a34a;flex-shrink:0;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
                            <div style="flex:1;min-width:0;"><div style="font-family:var(--ff);font-size:13px;font-weight:600;color:var(--ink);">{{ $r->referredUser->name ?? 'New referral' }}</div><div style="font-size:11.5px;color:var(--muted);">{{ $r->created_at->format('M j, Y') }}</div></div>
                            <div style="color:#16a34a;font-family:var(--ff);font-weight:700;font-size:13px;">+${{ number_format($r->commission_amount, 2) }}</div>
                        </div>
                    @empty
                        <div style="text-align:center;color:var(--muted);font-size:13px;padding:18px;">No activity yet — start referring to see it here.</div>
                    @endforelse
                </div>
            </div>
            <div class="bt-panel" style="margin-top:0;">
                <h3 style="font-size:15px;">How to Earn More</h3>
                <div style="margin-top:12px;">
                    @foreach([['Refer a new member','+'.$ct['rate'].'% commission','#2563eb'],['Promote an event','More reach','#7c3aed'],['Share quality content','Higher engagement','#16a34a'],['Reach the next tier','Higher rates','#f97316']] as [$t,$v,$c])
                        <div style="display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--line);">
                            <div style="width:8px;height:8px;border-radius:50%;background:{{ $c }};flex-shrink:0;"></div>
                            <div style="flex:1;font-size:13px;color:var(--ink);">{{ $t }}</div>
                            <div style="font-size:12px;color:var(--muted);font-weight:600;">{{ $v }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- right rail --}}
    <div>
        <div class="bt-rail-card">
            <div style="display:flex; align-items:center; justify-content:space-between;"><h4 style="margin:0;">Tier Status</h4><span style="font-size:10.5px;font-weight:800;color:#16a34a;background:#dcfce7;padding:3px 10px;border-radius:20px;">ACTIVE</span></div>
            <p style="margin-top:8px;">@if($nextTier) Keep referring to climb to {{ $nextTier['label'] }} and unlock a higher rate. @else You've achieved the highest tier. Enjoy all the exclusive benefits! @endif</p>
            <div style="display:flex; align-items:center; gap:11px; margin-top:14px; padding:11px; background:var(--orange-soft); border-radius:12px;">
                <x-influencer.hex-badge :color="$ct['color']" :icon="$ct['icon']" size="40" />
                <div><div style="font-family:var(--ff);font-weight:700;color:var(--ink);font-size:13.5px;">{{ $ct['label'] }}</div><div style="font-size:11.5px;color:var(--muted);">{{ $ct['rate'] }}% commission</div></div>
            </div>
        </div>
        <div class="bt-rail-card">
            <h4>Your Benefits</h4>
            <div class="bt-rail-list">
                @foreach($ct['benefits'] as $ben)
                    <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> {{ $ben }}</div>
                @endforeach
            </div>
            <a href="{{ route('influencer.badges.benefits') }}" class="bt-rail-cta">View all benefits <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="bt-rail-card bt-rail-soft">
            <h4>Badges Earned 🏅</h4>
            <p>You've unlocked <b>{{ $earnedBadges->count() }}</b> of {{ count($badges) }} achievement badges.</p>
            <a href="{{ route('influencer.badges.all') }}" class="bt-rail-cta">View all badges <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>
@endsection
