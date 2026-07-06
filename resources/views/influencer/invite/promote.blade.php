@extends('layouts.influencer-portal')
@section('title', 'Promote')
@push('styles') @include('influencer.invite._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Promote</div>
<div class="iv-head"><h1>Promote &amp; Grow</h1><p>Ready-made assets and proven tactics to put your referral link in front of the right people.</p></div>

<div class="iv-layout">
    <div>
        <div class="iv-panel">
            <h3>Marketing Assets</h3>
            <div class="sub">Grab a ready-made asset, add your link, and share.</div>
            <div class="iv-mats">
                @foreach([
                    ['Social Media Posts','Captions + graphics for feed posts','#2563eb','var(--blue-soft)'],
                    ['Stories & Reels','Vertical templates for stories/reels','#db2777','#fce7f3'],
                    ['Banners & Graphics','Web banners in multiple sizes','#7c3aed','#ede9fe'],
                    ['Email Templates','Copy-paste email invites','#16a34a','#dcfce7'],
                ] as [$t,$d,$c,$bg])
                    <div class="iv-mat"><span class="ic" style="background:{{ $bg }};color:{{ $c }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span><div><b>{{ $t }}</b><span>{{ $d }}</span></div><span class="arrow" style="font-size:11px;font-weight:700;color:var(--orange-dark);">Coming soon</span></div>
                @endforeach
            </div>
        </div>

        <div class="iv-panel">
            <h3>Where to Promote</h3>
            <div class="sub">High-converting channels for event &amp; creator audiences.</div>
            <div class="iv-steps">
                @foreach([
                    ['Your social channels','Post about events you love with your link in bio or captions.'],
                    ['Communities & groups','Share in event-planning, wedding, and local Facebook/Discord groups.'],
                    ['Email & newsletters','Add your link to your signature and newsletter footer.'],
                    ['Content & blogs','Write reviews or guides about planning events and link your referral.'],
                ] as [$t,$d])
                    <div class="iv-step"><div class="num"></div><div><b>{{ $t }}</b><p>{{ $d }}</p></div></div>
                @endforeach
            </div>
        </div>
    </div>
    <div>
        <div class="iv-rail-card iv-rail-soft">
            <h4>Your Link</h4>
            <div style="background:#fff; border:1px solid var(--line); border-radius:10px; padding:10px 12px; font-size:12.5px; color:var(--ink); word-break:break-all; margin-bottom:10px;">{{ $referralUrl }}</div>
            <a href="{{ route('influencer.invite.tools') }}" class="iv-rail-cta">Open Invite Tools <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="iv-rail-card">
            <h4>Promotion Tips</h4>
            <div class="iv-rail-list">
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Lead with value, not the link</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Be consistent &amp; authentic</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Use a clear call-to-action</div>
            </div>
        </div>
    </div>
</div>
@endsection
