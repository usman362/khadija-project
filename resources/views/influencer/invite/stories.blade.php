@extends('layouts.influencer-portal')
@section('title', 'Success Stories')
@push('styles') @include('influencer.invite._styles') @endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.tools') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Success Stories</div>
<div class="iv-head"><h1>Success Stories</h1><p>See how creators like you are growing their income with the GigResource influencer program.</p></div>

<div class="iv-stories" style="margin-top:22px;">
    @foreach([
        ['Tripled my side income in three months. The dashboard makes it easy to see exactly what\'s working.', 'Maya R.', 'Wedding Content Creator', '#f97316'],
        ['My event-planning audience loved the referrals. Climbing tiers genuinely bumped my rate.', 'Daniel K.', 'Event Blogger', '#2563eb'],
        ['Payouts are reliable and the marketing assets saved me hours. Highly recommend.', 'Sofia L.', 'Lifestyle Influencer', '#7c3aed'],
        ['Started with zero followers in events — the academy resources got me earning fast.', 'Andre P.', 'Micro-Creator', '#16a34a'],
        ['Best affiliate program I\'ve joined for the events niche. Transparent and supportive.', 'Priya S.', 'Community Manager', '#db2777'],
        ['I share one link in my bio and it keeps earning. Set-and-forget income.', 'Liam T.', 'Podcast Host', '#d97706'],
    ] as [$quote,$name,$role,$c])
        <div class="iv-story">
            <div class="stars">★★★★★</div>
            <p>"{{ $quote }}"</p>
            <div class="who">
                <div class="av" style="background:{{ $c }};">{{ strtoupper(substr($name,0,1)) }}</div>
                <div><b>{{ $name }}</b><span>{{ $role }}</span></div>
            </div>
        </div>
    @endforeach
</div>

<div class="iv-hero" style="margin-top:22px;">
    <h2>Your story starts here ✨</h2>
    <p>Join the creators already earning with GigResource. Share your link and start building your income today.</p>
    <a href="{{ route('influencer.invite.tools') }}">Get Started</a>
</div>
@endsection
