@extends('layouts.influencer-portal')
@section('title', 'Main Tiers')
@push('styles') @include('influencer.badges._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.badges.current') }}">Badges &amp; Tiers</a> <span class="sep">›</span> Main Tiers</div>

<div class="bt-head">
    {{-- Row 114 — "Tier" alone reads as a membership plan, which is a different
         thing a user pays for. Named here and on every pill below. --}}
    <h1>Influencer Tiers</h1>
    <p>Your influencer tier is earned by referring members — it is not a membership plan, and there is nothing to buy.</p>
</div>

<div class="bt-info">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    Your tier reflects your activity. The more members you refer, the higher you climb — and the more you earn.
</div>

{{-- Row 144 — a percentage nobody can read without knowing what it is a
     percentage OF. Both figures below are the ones the code actually uses:
     the rate is applied to the booking price, and the signup bonus is a flat
     amount per referral. --}}
<div class="bt-panel" style="margin-top:14px;">
    <h3>What your commission applies to</h3>
    <div class="sub">Two separate things, paid on different events.</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:12px;">
        <div style="border:1px solid var(--line);border-radius:12px;padding:13px 15px;">
            <div style="font-size:12px;font-weight:800;color:var(--ink);margin-bottom:4px;">Booking commission</div>
            <p style="font-size:12.5px;color:var(--muted);line-height:1.55;margin:0;">
                Your tier rate ({{ collect($tiers)->pluck('rate')->implode('% / ') }}%) applied to the
                <b>agreed price of each booking</b> made by someone you referred. Not the professional's
                own earnings, and not the platform's fee.
            </p>
        </div>
        <div style="border:1px solid var(--line);border-radius:12px;padding:13px 15px;">
            <div style="font-size:12px;font-weight:800;color:var(--ink);margin-bottom:4px;">Signup bonus</div>
            <p style="font-size:12.5px;color:var(--muted);line-height:1.55;margin:0;">
                A flat ${{ number_format((float) config('influencer.signup_bonus', 5), 2) }} each time
                someone registers through your link. Paid once per person, whether or not they ever book.
            </p>
        </div>
    </div>
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
                    <span class="bt-tier-pill">Influencer Tier {{ $idx + 1 }} · {{ $t['rate'] }}%</span>
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

        {{-- Row 115 — the Elite data benefit, placed on the tiers screen where
             the tier it belongs to is being read. Elite sees the figure; every
             other tier sees what unlocking it gets them, which is the point of
             putting it here at all. --}}
        <div class="bt-panel">
            <h3>Elite: see how your referred professionals are doing</h3>
            <div class="sub">Elite is the only tier that can see which professionals you referred have been booked and paid.</div>
            @if($isElite)
                <div style="display:flex;align-items:center;gap:14px;margin-top:12px;padding:14px 16px;border:1px solid var(--line);border-radius:12px;">
                    <div style="font-family:var(--ff);font-size:30px;font-weight:800;color:var(--ink);line-height:1;">{{ $paidProfessionals }}</div>
                    <div style="font-size:12.5px;color:var(--muted);line-height:1.5;">
                        {{ $paidProfessionals === 1 ? 'professional you referred has' : 'professionals you referred have' }}
                        been booked and paid on the platform.
                        <a href="{{ route('influencer.dashboard.referrals') }}" style="color:var(--blue);font-weight:700;text-decoration:none;">See your referrals →</a>
                    </div>
                </div>
            @else
                <div style="display:flex;align-items:center;gap:12px;margin-top:12px;padding:14px 16px;border:1px dashed var(--line);border-radius:12px;color:var(--muted);font-size:12.5px;line-height:1.5;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Reach Elite ({{ $tiers['elite']['min_referrals'] }}+ referrals) to unlock this.</span>
                </div>
            @endif
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
                    <div class="bt-lu-ic" style="background:#dcfce7; color:#15803d;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg></div>
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
