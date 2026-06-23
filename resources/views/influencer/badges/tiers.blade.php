@extends('layouts.influencer-portal')
@section('title', 'Main Tiers')
@push('styles') @include('influencer.badges._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.current') }}">Badges &amp; Tiers</a> <span class="sep">›</span> Main Tiers</div>

<div class="bt-head">
    <h1>Main Tiers</h1>
    <p>Advance through our tiers by referring members, staying active, and making an impact.</p>
</div>

<div class="bt-info">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    Your tier reflects your activity. The more members you refer, the higher you climb — and the more you earn.
</div>

<div class="bt-layout">
    <div>
        <div class="bt-tiers">
            @foreach($tiers as $key => $t)
                @php $idx = array_search($key, $tierKeys, true); @endphp                <div class="bt-tier {{ $key === $currentKey ? 'current' : '' }}">
                    @if($key === $currentKey)<div class="bt-tier-flag">★ Your Tier</div>
                    @elseif($key === 'pro')<div class="bt-tier-flag" style="background:#7c3aed;">Most Popular</div>@endif
                    <div class="bt-tier-badge"><x-influencer.hex-badge :color="$t['color']" :icon="$t['icon']" size="74" /></div>
                    <h3>{{ $t['label'] }}</h3>
                    <span class="bt-tier-pill">Tier {{ $idx + 1 }} · {{ $t['rate'] }}%</span>
                    <div class="bt-tier-tag">{{ $t['tagline'] }}</div>
                    <div class="bt-req-lbl">Requirement</div>
                    <div class="bt-req">{{ $t['min_referrals'] == 0 ? 'Start here' : $t['min_referrals'].'+ referrals' }}</div>
                    <div class="bt-ben-lbl">Benefits</div>
                    @foreach($t['benefits'] as $ben)
                        <div class="bt-ben"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $ben }}</div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="bt-panel">
            <h3>How to Level Up</h3>
            <div class="sub">Refer members and stay active to unlock the next tier and a higher commission rate.</div>
            <div class="bt-levelup">
                <div>
                    <div class="bt-lu-ic" style="background:var(--blue-soft); color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg></div>
                    <b>Share Your Link</b><span>Send your referral link to your audience.</span>
                </div>
                <div>
                    <div class="bt-lu-ic" style="background:#dcfce7; color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
                    <b>Refer Members</b><span>Earn when they sign up and book.</span>
                </div>
                <div>
                    <div class="bt-lu-ic" style="background:#ede9fe; color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <b>Create Content</b><span>Promote events to grow your reach.</span>
                </div>
                <div>
                    <div class="bt-lu-ic" style="background:var(--orange-soft); color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l16-5v12L3 13v-2z"/><path d="M11 18.5a3 3 0 0 1-5.5-1.5"/></svg></div>
                    <b>Stay Active</b><span>Consistency keeps you climbing.</span>
                </div>
                <div>
                    <div class="bt-lu-ic" style="background:#fef3c7; color:#d97706;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg></div>
                    <b>Earn Badges</b><span>Hit milestones for special badges.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- right rail --}}
    <div>
        <div class="bt-rail-card bt-rail-soft">
            <h4>Climb the Ranks 🏆</h4>
            <p>The more you engage, the more benefits you unlock. Keep going and reach the top!</p>
            <a href="{{ route('influencer.badges.progress') }}" class="bt-rail-cta">View My Progress <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="bt-rail-card">
            <h4>Tier Benefits Increase</h4>
            <div class="bt-rail-list">
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Higher commission rates</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> More visibility on the platform</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Priority support from our team</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Exclusive resources &amp; perks</div>
            </div>
            <a href="{{ route('influencer.badges.benefits') }}" class="bt-rail-cta">View All Benefits <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="bt-rail-card" style="background:#f5f3ff; border-color:#ddd6fe;">
            <h4>Have Questions?</h4>
            <p>Learn more about how tiers work and how you can level up faster.</p>
            <a href="{{ route('public.faq') }}" class="bt-rail-cta" style="border-color:#7c3aed; color:#7c3aed;">Visit Help Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>
@endsection
