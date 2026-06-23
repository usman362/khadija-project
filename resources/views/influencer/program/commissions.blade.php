@extends('layouts.influencer-portal')
@section('title', 'Commissions')
@push('styles') @include('influencer.program._styles') @endpush

@section('content')
<div class="pg-head"><h1>Commissions</h1><p>Track what you've earned and see how much more each tier unlocks.</p></div>

<div class="pg-tiles">
    <div class="pg-tile"><div class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="v">${{ number_format($totals['total'], 2) }}</div><div class="l">Total Earned</div></div>
    <div class="pg-tile"><div class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></div><div class="v">${{ number_format($totals['earned'], 2) }}</div><div class="l">Available</div></div>
    <div class="pg-tile"><div class="ic" style="background:#fef3c7;color:#b45309;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="v">${{ number_format($totals['pending'], 2) }}</div><div class="l">Pending</div></div>
    <div class="pg-tile"><div class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="v">${{ number_format($totals['paid'], 2) }}</div><div class="l">Paid Out</div></div>
</div>

<div class="pg-grid two">
    <div class="pg-panel">
        <h3>Commission Tiers</h3>
        <p class="sub">Your rate increases as you refer more members. You're earning <b style="color:var(--orange-dark);">{{ rtrim(rtrim(number_format($currentRate,1),'0'),'.') }}%</b> right now.</p>
        @foreach($tiers as $key => $tier)
            <div class="pg-tier-row {{ $key === $currentKey ? 'current' : '' }}" style="{{ $key === $currentKey ? 'border-color:'.$tier['color'].';' : '' }}">
                <span class="pg-tier-dot" style="background:{{ $tier['color'] }};"></span>
                <div class="ti">
                    <b>{{ $tier['label'] }} @if($key === $currentKey)<span class="pg-badge-now" style="margin-left:6px;">You</span>@endif</b>
                    <span>{{ $tier['min_referrals'] }}+ referrals</span>
                </div>
                <span class="pg-tier-rate" style="color:{{ $tier['color'] }};">{{ rtrim(rtrim(number_format($tier['rate'],1),'0'),'.') }}%</span>
            </div>
        @endforeach
        <div class="pg-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Commission is earned on completed referrals. Earned balance becomes withdrawable once it reaches the ${{ number_format($minPayout, 0) }} payout minimum.</span>
        </div>
    </div>

    <div class="pg-panel">
        <h3>Commission History</h3>
        <p class="sub">Your most recent commission activity.</p>
        @if($history->isEmpty())
            <div style="text-align:center; color:var(--muted); padding:24px;">No commissions yet.</div>
        @else
            <table class="pg-table">
                <thead><tr><th>Source</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($history as $r)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::headline($r->type->value) }}</td>
                            <td><b>${{ number_format($r->commission_amount, 2) }}</b></td>
                            <td><span class="pg-pill {{ $r->status->value }}">{{ ucfirst($r->status->value) }}</span></td>
                            <td style="color:var(--muted);">{{ $r->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <a href="{{ route('influencer.dashboard.payouts') }}" style="display:block; text-align:center; margin-top:16px; padding:11px; background:var(--orange); color:#fff; border-radius:10px; font-family:var(--ff); font-weight:700; font-size:13px;">Request a Payout →</a>
    </div>
</div>
@endsection
