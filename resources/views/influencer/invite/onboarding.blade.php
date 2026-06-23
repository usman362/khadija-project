@extends('layouts.influencer-portal')
@section('title', 'Onboarding')
@push('styles') @include('influencer.invite._styles') @endpush

@php
    $checks = [
        ['Profile created', (bool) $influencer->full_name, route('influencer.dashboard')],
        ['Application approved', $influencer->isApproved(), route('influencer.dashboard')],
        ['Got your referral link', (bool) $influencer->referral_code, route('influencer.invite.tools')],
        ['Made your first referral', $influencer->total_referrals > 0, route('influencer.invite.tools')],
        ['Earned your first commission', (float) $influencer->total_earnings > 0, route('influencer.invite.earn')],
    ];
    $done = collect($checks)->filter(fn($c)=>$c[1])->count();
    $pct = (int) round($done / count($checks) * 100);
@endphp

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Onboarding</div>
<div class="iv-head"><h1>Get Started 👋</h1><p>Complete these steps to start earning as a GigResource influencer.</p></div>

<div class="iv-panel" style="margin-top:18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;"><h3 style="margin:0;">Your Setup</h3><b style="font-family:var(--ff); color:var(--orange-dark);">{{ $done }}/{{ count($checks) }} done</b></div>
    <div style="height:10px; background:var(--bg); border-radius:6px; overflow:hidden;"><span style="display:block; height:100%; width:{{ $pct }}%; background:linear-gradient(90deg,var(--orange),var(--orange-dark)); border-radius:6px;"></span></div>
    <div style="margin-top:16px;">
        @foreach($checks as [$label, $ok, $href])
            <a href="{{ $href }}" style="display:flex; align-items:center; gap:13px; padding:13px 0; border-bottom:1px solid var(--line);">
                @if($ok)
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><circle cx="12" cy="12" r="10" fill="#dcfce7" stroke="none"/><polyline points="16 9 11 15 8 12"/></svg>
                @else
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#cdd6e4" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                @endif
                <span style="flex:1; font-family:var(--ff); font-weight:600; font-size:14px; color:{{ $ok ? 'var(--muted)' : 'var(--ink)' }}; {{ $ok ? 'text-decoration:line-through;' : '' }}">{{ $label }}</span>
                @unless($ok)<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2.4"><polyline points="9 18 15 12 9 6"/></svg>@endunless
            </a>
        @endforeach
    </div>
</div>

<div class="iv-panel" style="text-align:center;">
    <h3>Ready to earn?</h3>
    <div class="sub" style="max-width:46ch; margin:6px auto 14px;">Grab your link and start sharing — your first commission could be a referral away.</div>
    <a href="{{ route('influencer.invite.tools') }}" style="display:inline-block; background:var(--orange); color:#fff; padding:11px 22px; border-radius:11px; font-family:var(--ff); font-weight:700; font-size:13.5px;">Go to Invite Tools</a>
</div>
@endsection
