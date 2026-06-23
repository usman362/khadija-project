@extends('layouts.influencer-portal')
@section('title', 'FAQs')
@push('styles') @include('influencer.invite._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> FAQs</div>
<div class="iv-head"><h1>Frequently Asked Questions</h1><p>Everything you need to know about the GigResource influencer program.</p></div>

<div class="iv-layout">
    <div>
        @foreach([
            ['How do I earn commissions?', 'You earn a commission whenever someone signs up and books through your referral link. Your rate depends on your current tier (5% to 12.5%).'],
            ['When and how do I get paid?', 'Once your available balance reaches the minimum payout of $'.number_format(config('influencer.min_payout_threshold',50),0).', you can request a payout from the Payouts page. Payouts are sent via bank transfer or PayPal.'],
            ['How do tiers work?', 'Your tier is based on your total successful referrals. The more you refer, the higher your tier — and the higher your commission rate and perks. See Badges & Tiers for details.'],
            ['Where can I find my referral link?', 'Your unique referral link and code are always available on the Invite Tools page, ready to copy and share anywhere.'],
            ['Is there a cost to join?', 'No. Joining the influencer program is completely free — apply, get approved, and start sharing.'],
            ['How are referrals tracked?', 'When someone clicks your link, a cookie attributes them to you for '.config('influencer.cookie_days',30).' days. Signups and bookings in that window count as your referrals.'],
        ] as [$q,$a])
            <details class="iv-faq">
                <summary>{{ $q }}<svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="6 9 12 15 18 9"/></svg></summary>
                <div class="ans">{{ $a }}</div>
            </details>
        @endforeach
    </div>
    <div>
        <div class="iv-rail-card iv-rail-soft">
            <h4>Still have questions?</h4>
            <p style="font-size:12.5px; color:var(--text); line-height:1.55;">Our support team is happy to help with anything about the program.</p>
            <a href="{{ route('public.faq') }}" class="iv-rail-cta">Visit Help Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>
@endsection
