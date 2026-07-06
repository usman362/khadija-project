@extends('layouts.influencer-portal')
@section('title', 'Become an Influencer')
@push('styles') @include('influencer.invite._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Become an Influencer</div>

<div class="iv-hero">
    <h2>The GigResource Influencer Program 🚀</h2>
    <p>Monetize your influence, promote amazing events, and earn commissions for every successful referral — doing what you love.</p>
    <a href="{{ route('influencer.invite.tools') }}">Get Your Referral Link</a>
</div>

<div class="iv-panel">
    <h3>Why join?</h3>
    <div class="sub">Built for creators, planners, and connectors in the events space.</div>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:10px;">
        @foreach([
            ['Earn Commissions','#16a34a','var(--orange-soft)','Up to 12.5% on every successful referral, paid reliably.'],
            ['Easy Tracking','#2563eb','var(--blue-soft)','Real-time clicks, signups, and earnings in your portal.'],
            ['Climb Tiers','#7c3aed','#ede9fe','Refer more to unlock higher rates and exclusive perks.'],
        ] as [$t,$c,$bg,$d])
            <div style="border:1px solid var(--line); border-radius:14px; padding:18px;">
                <div style="width:44px;height:44px;border-radius:12px;background:{{ $bg }};color:{{ $c }};display:flex;align-items:center;justify-content:center;margin-bottom:12px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg></div>
                <b style="font-family:var(--ff);font-size:15px;color:var(--ink);">{{ $t }}</b>
                <p style="font-size:13px;color:var(--muted);margin-top:5px;line-height:1.55;">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</div>

<div class="iv-panel">
    <h3>How it works</h3>
    <div class="iv-steps">
        <div class="iv-step"><div class="num"></div><div><b>Get your link</b><p>Your unique referral link is ready in Invite Tools.</p></div></div>
        <div class="iv-step"><div class="num"></div><div><b>Share it</b><p>Promote events and share your link with your audience.</p></div></div>
        <div class="iv-step"><div class="num"></div><div><b>Earn</b><p>Get paid when your referrals sign up and book on GigResource.</p></div></div>
    </div>
    <div style="text-align:center; margin-top:8px;"><a href="{{ route('influencer.invite.onboarding') }}" style="display:inline-block; background:var(--orange); color:#fff; padding:11px 22px; border-radius:11px; font-family:var(--ff); font-weight:700; font-size:13.5px;">Start Onboarding</a></div>
</div>
@endsection
