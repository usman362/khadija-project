@extends('layouts.influencer-portal')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dx-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 20px; align-items: start; }
    @media (max-width: 1180px) { .dx-grid { grid-template-columns: 1fr; } }

    .dx-hero { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .dx-hero h1 { font-family: var(--ff); font-size: 24px; font-weight: 800; color: var(--ink); }
    .dx-hero p { font-size: 13.5px; color: var(--muted); margin-top: 3px; }
    .dx-hero-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--orange); color: #fff; padding: 11px 18px; border-radius: 11px; font-family: var(--ff); font-weight: 700; font-size: 13.5px; }
    .dx-hero-btn:hover { background: var(--orange-dark); }

    .dx-tiles { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 20px; }
    @media (max-width: 1180px) { .dx-tiles { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 640px) { .dx-tiles { grid-template-columns: repeat(2,1fr); } }
    .dx-tile { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
    .dx-tile-ic { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .dx-tile-ic svg { width: 19px; height: 19px; }
    .dx-tile-val { font-family: var(--ff); font-size: 21px; font-weight: 800; color: var(--ink); line-height: 1; }
    .dx-tile-lbl { font-size: 12px; color: var(--muted); margin-top: 5px; }

    .dx-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px; margin-bottom: 20px; }
    .dx-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .dx-panel-head h3 { font-family: var(--ff); font-size: 16px; font-weight: 700; color: var(--ink); }
    .dx-panel-head a { font-size: 12.5px; color: var(--orange-dark); font-weight: 600; }

    .dx-chart { width: 100%; height: 200px; }
    .dx-chart .grid line { stroke: var(--line); stroke-width: 1; }
    .dx-axis { font-size: 11px; fill: var(--muted); }

    .dx-donut { width: 132px; height: 132px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .dx-donut::after { content: ''; position: absolute; width: 88px; height: 88px; background: var(--card); border-radius: 50%; }
    .dx-donut-c { position: relative; z-index: 1; text-align: center; }
    .dx-donut-c b { font-family: var(--ff); font-size: 20px; font-weight: 800; color: var(--ink); display: block; }
    .dx-donut-c span { font-size: 11px; color: var(--muted); }

    .dx-bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 12.5px; }
    .dx-bar-row .nm { width: 88px; color: var(--text); }
    .dx-bar { flex: 1; height: 8px; background: var(--bg); border-radius: 6px; overflow: hidden; }
    .dx-bar span { display: block; height: 100%; border-radius: 6px; }
    .dx-bar-row .pc { width: 44px; text-align: right; color: var(--muted); font-weight: 600; }

    .dx-list-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--line); }
    .dx-list-row:last-child { border-bottom: none; }
    .dx-list-av { width: 38px; height: 38px; border-radius: 10px; background: var(--blue-soft); display: flex; align-items: center; justify-content: center; font-family: var(--ff); font-weight: 700; color: var(--blue); font-size: 13px; flex-shrink: 0; }
    .dx-list-main { flex: 1; min-width: 0; }
    .dx-list-main b { font-family: var(--ff); font-size: 13.5px; font-weight: 600; color: var(--ink); display: block; }
    .dx-list-main span { font-size: 12px; color: var(--muted); }
    .dx-pill { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
    .dx-pill-green { background: #dcfce7; color: #16a34a; } .dx-pill-amber { background: #dcfce7; color: #15803d; }
    .dx-pill-gray { background: #eef1f6; color: #7a879c; } .dx-pill-blue { background: var(--blue-soft); color: var(--blue); }

    .dx-empty { text-align: center; padding: 26px 10px; color: var(--muted); font-size: 13px; }
    .dx-empty svg { width: 34px; height: 34px; color: #cdd6e4; margin-bottom: 8px; }

    .dx-rail-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; margin-bottom: 18px; }
    .dx-earn { background: linear-gradient(135deg, var(--orange), var(--orange-dark)); color: #fff; border: none; }
    .dx-earn .v { font-family: var(--ff); font-size: 28px; font-weight: 800; margin: 6px 0 2px; }
    .dx-earn .s { font-size: 12.5px; opacity: .9; }
    .dx-earn .row { display: flex; justify-content: space-between; margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.25); font-size: 12.5px; }
    .dx-quick a { display: flex; align-items: center; gap: 11px; padding: 10px; border-radius: 11px; font-size: 13.5px; font-weight: 600; color: var(--text); }
    .dx-quick a:hover { background: #f5f7fb; color: var(--ink); }
    .dx-quick a svg { width: 18px; height: 18px; color: var(--orange); }

    .dx-cta { background: var(--orange-soft); border: 1px solid #c9ecd4; border-radius: var(--radius); padding: 20px; text-align: center; margin-bottom: 18px; }
    .dx-cta h4 { font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); }
    .dx-cta p { font-size: 12.5px; color: var(--text); margin: 6px 0 12px; }
    .dx-cta a { display: inline-block; background: var(--orange); color: #fff; padding: 9px 18px; border-radius: 10px; font-family: var(--ff); font-weight: 700; font-size: 13px; }
</style>
@endpush

@php
    $ip_url = fn (?string $name) => $name && \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';
    $fmt = fn ($n) => $n >= 1000000 ? round($n/1000000, 1).'M' : ($n >= 1000 ? round($n/1000, 1).'K' : number_format($n));
    $series = collect($earningsSeries);
    $max = max(1, $series->max());
    $n = max(1, $series->count() - 1);
    $pts = $series->values()->map(function ($v, $i) use ($n, $max) {
        $x = 30 + ($i / $n) * 520;
        $y = 170 - ($v / $max) * 140;
        return round($x, 1).','.round($y, 1);
    })->implode(' ');
    $fields = [$influencer->full_name, $influencer->email, $influencer->social_media_links, $influencer->audience_description, $influencer->monthly_reach, $influencer->followers_count];
    $filled = collect($fields)->filter()->count();
    $complete = (int) round($filled / count($fields) * 100);
    $engPct = min(100, (float) $influencer->engagement_rate * 8);
@endphp

@section('content')
<div class="ipx-breadcrumb">Dashboard</div>

<div class="dx-hero">
    <div>
        <h1>Welcome back, {{ explode(' ', $influencer->full_name)[0] }}! 👋</h1>
        <p>Here's what's happening with your influencer account.</p>
    </div>
    <a href="{{ route('public.browse') }}" class="dx-hero-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Find Brand Deals
    </a>
</div>

@if(!$influencer->isApproved())
    <div class="dx-panel" style="border-color:#c9ecd4; background:var(--orange-soft);">
        <div style="display:flex; align-items:center; gap:12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div><b style="font-family:var(--ff); color:var(--ink);">Application under review</b><div style="font-size:12.5px; color:var(--text);">You'll get your referral link and full earnings access once an admin approves your account.</div></div>
        </div>
    </div>
@endif

<div class="dx-tiles">
    <div class="dx-tile">
        <div class="dx-tile-ic" style="background:var(--blue-soft); color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="dx-tile-val">{{ $fmt($influencer->followers_count) }}</div>
        <div class="dx-tile-lbl">Total Followers</div>
    </div>
    <div class="dx-tile">
        <div class="dx-tile-ic" style="background:#fce7f3; color:#db2777;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></div>
        <div class="dx-tile-val">{{ rtrim(rtrim(number_format($influencer->engagement_rate, 2), '0'), '.') }}%</div>
        <div class="dx-tile-lbl">Engagement Rate</div>
    </div>
    <div class="dx-tile">
        <div class="dx-tile-ic" style="background:#ede9fe; color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg></div>
        <div class="dx-tile-val">{{ $influencer->profile_score }}</div>
        <div class="dx-tile-lbl">Profile Score</div>
    </div>
    <div class="dx-tile">
        <div class="dx-tile-ic" style="background:#dcfce7; color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="dx-tile-val">${{ number_format($monthlyEarnings, 0) }}</div>
        <div class="dx-tile-lbl">Earnings This Month</div>
    </div>
    <div class="dx-tile">
        <div class="dx-tile-ic" style="background:var(--orange-soft); color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l16-5v12L3 13v-2z"/><path d="M11 18.5a3 3 0 0 1-5.5-1.5"/></svg></div>
        <div class="dx-tile-val">{{ $influencer->total_referrals }}</div>
        <div class="dx-tile-lbl">Total Referrals</div>
    </div>
</div>

<div class="dx-grid">
    <div>
        <div class="dx-panel">
            <div class="dx-panel-head"><h3>Earnings Overview</h3><span style="font-size:12px;color:var(--muted);">Last 6 months</span></div>
            <svg class="dx-chart" viewBox="0 0 560 190" preserveAspectRatio="none">
                <g class="grid">
                    @for($i=0;$i<=4;$i++)<line x1="30" y1="{{ 30 + $i*35 }}" x2="550" y2="{{ 30 + $i*35 }}"/>@endfor
                </g>
                @if($series->sum() > 0)
                    <polyline fill="rgba(22,163,74,0.08)" stroke="none" points="30,170 {{ $pts }} 550,170"/>
                    <polyline fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" points="{{ $pts }}"/>
                @endif
                @foreach($series->keys() as $i => $label)
                    <text class="dx-axis" x="{{ 30 + ($i/$n)*520 }}" y="186" text-anchor="middle">{{ $label }}</text>
                @endforeach
            </svg>
            @if($series->sum() == 0)
                <div class="dx-empty">Share your referral link to start earning — your monthly earnings will chart here.</div>
            @endif
        </div>

        <div class="dx-panel">
            <div class="dx-panel-head"><h3>Audience Snapshot</h3><a href="{{ $ip_url('influencer.analytics.audience') }}">View insights →</a></div>
            <div style="display:flex; gap:28px; align-items:center; flex-wrap:wrap;">
                <div class="dx-donut" style="position:relative; background: conic-gradient(var(--orange) 0% {{ $engPct }}%, var(--blue) {{ $engPct }}% 72%, #e7ebf2 72% 100%);">
                    <div class="dx-donut-c"><b>{{ $fmt($influencer->followers_count) }}</b><span>followers</span></div>
                </div>
                <div style="flex:1; min-width:200px;">
                    <div class="dx-bar-row"><span class="nm">Monthly Reach</span><div class="dx-bar"><span style="width:{{ $influencer->monthly_reach ? 80 : 4 }}%; background:var(--orange);"></span></div><span class="pc">{{ $fmt($influencer->monthly_reach ?? 0) }}</span></div>
                    <div class="dx-bar-row"><span class="nm">Engagement</span><div class="dx-bar"><span style="width:{{ min(100,(float)$influencer->engagement_rate*10) }}%; background:#db2777;"></span></div><span class="pc">{{ rtrim(rtrim(number_format($influencer->engagement_rate,1),'0'),'.') }}%</span></div>
                    <div class="dx-bar-row"><span class="nm">Profile Score</span><div class="dx-bar"><span style="width:{{ $influencer->profile_score }}%; background:#7c3aed;"></span></div><span class="pc">{{ $influencer->profile_score }}</span></div>
                </div>
            </div>
        </div>

        <div class="dx-panel">
            <div class="dx-panel-head"><h3>Recent Referrals</h3><a href="{{ route('influencer.dashboard.referrals') }}">View all →</a></div>
            @forelse($recentReferrals as $r)
                <div class="dx-list-row">
                    <div class="dx-list-av">{{ strtoupper(substr($r->referredUser->name ?? 'U', 0, 2)) }}</div>
                    <div class="dx-list-main">
                        <b>{{ $r->referredUser->name ?? 'New referral' }}</b>
                        <span>{{ ucfirst($r->type?->value ?? 'signup') }} &middot; {{ $r->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-family:var(--ff); font-weight:700; color:var(--ink); font-size:13.5px;">${{ number_format($r->commission_amount, 2) }}</div>
                        @php $st = $r->status?->value ?? 'pending'; @endphp                        <span class="dx-pill {{ $st==='paid'?'dx-pill-green':($st==='earned'?'dx-pill-blue':($st==='cancelled'?'dx-pill-gray':'dx-pill-amber')) }}">{{ ucfirst($st) }}</span>
                    </div>
                </div>
            @empty
                <div class="dx-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    <div>No referrals yet. Share your link to start earning commissions.</div>
                </div>
            @endforelse
        </div>
    </div>

    <div>
        <div class="dx-rail-card dx-earn">
            <div class="s">Available Balance</div>
            <div class="v">${{ number_format($influencer->available_balance, 2) }}</div>
            <div class="s">Total earned: ${{ number_format($influencer->total_earnings, 2) }}</div>
            <div class="row"><span>Paid out</span><b>${{ number_format($influencer->paid_out, 2) }}</b></div>
            <a href="{{ route('influencer.dashboard.payouts') }}" style="display:block; text-align:center; margin-top:14px; background:#fff; color:var(--orange-dark); padding:9px; border-radius:10px; font-family:var(--ff); font-weight:700; font-size:13px;">Request Payout</a>
        </div>

        <div class="dx-rail-card">
            <div class="dx-panel-head" style="margin-bottom:12px;"><h3 style="font-size:15px;">Profile Completeness</h3></div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="flex:1; height:9px; background:var(--bg); border-radius:6px; overflow:hidden;"><span style="display:block; height:100%; width:{{ $complete }}%; background:var(--orange); border-radius:6px;"></span></div>
                <b style="font-family:var(--ff); color:var(--ink);">{{ $complete }}%</b>
            </div>
            <div style="font-size:12px; color:var(--muted); margin-top:8px;">A complete profile unlocks more brand deals.</div>
        </div>

        <div class="dx-cta">
            <h4>Climb the Ranks 🏆</h4>
            <p>You're on the <b>{{ $influencer->commission_tier->label() }}</b> tier ({{ $influencer->commission_tier->rate() }}% commission). Engage more to unlock higher rates.</p>
            <a href="{{ $ip_url('influencer.badges.tiers') }}">View Tiers</a>
        </div>

        <div class="dx-rail-card dx-quick">
            <div class="dx-panel-head" style="margin-bottom:8px;"><h3 style="font-size:15px;">Quick Access</h3></div>
            <a href="{{ route('influencer.dashboard.referrals') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg> Referral Center</a>
            <a href="{{ route('influencer.dashboard.payouts') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/></svg> Payouts</a>
            <a href="{{ $ip_url('influencer.badges.tiers') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/></svg> Badges &amp; Tiers</a>
        </div>
    </div>
</div>
@endsection
