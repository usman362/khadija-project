@extends('layouts.influencer-portal')
@section('title', 'Referral Center')
@push('styles') @include('influencer.program._styles') @endpush

@php
    $srcColors = ['social'=>'#2563eb','email'=>'#16a34a','website'=>'#7c3aed','direct'=>'#16a34a'];
    $srcTotal = $bySource->sum();
@endphp

@section('content')
<div class="pg-head"><h1>Referral Center</h1><p>Share your link, track every referral, and watch your earnings grow.</p></div>

<div class="pg-tiles">
    <div class="pg-tile"><div class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div><div class="v">{{ $totals['count'] }}</div><div class="l">Total Referrals</div></div>
    <div class="pg-tile"><div class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="v">{{ $totals['converted'] }}</div><div class="l">Converted</div></div>
    <div class="pg-tile"><div class="ic" style="background:#dcfce7;color:#b45309;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="v">{{ $totals['pending_count'] }}</div><div class="l">Pending</div></div>
    <div class="pg-tile"><div class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="v">${{ number_format($totals['total'], 0) }}</div><div class="l">Commission Earned</div></div>
</div>

<div class="pg-panel" style="margin-bottom:18px;">
    <h3>Your Referral Link</h3>
    <p class="sub">Share this link anywhere — every signup is tracked to your account automatically.</p>
    <div class="pg-linkbox">
        <code id="refUrl">{{ $referralUrl }}</code>
        <button class="pg-copy" data-copy="#refUrl">Copy Link</button>
    </div>
    <div style="display:flex; align-items:center; gap:14px; margin-top:12px; font-size:12.5px; color:var(--muted);">
        <span>Referral code: <b style="color:var(--ink); font-family:var(--ff);">{{ $referralCode }}</b></span>
        <span>· This month: <b style="color:var(--ink);">{{ $thisMonth }}</b> referrals</span>
    </div>
</div>

<div class="pg-grid two">
    <div class="pg-panel">
        <h3>Recent Referrals</h3>
        <p class="sub">Your latest tracked referrals and their status.</p>
        @if($recent->isEmpty())
            <div style="text-align:center; color:var(--muted); padding:24px;">No referrals yet — share your link to get started.</div>
        @else
            <table class="pg-table">
                <thead><tr><th>Type</th><th>Source</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($recent as $r)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::headline($r->type->value) }}</td>
                            <td style="text-transform:capitalize;">{{ $r->source ?? '—' }}</td>
                            <td><b>${{ number_format($r->commission_amount, 2) }}</b></td>
                            <td><span class="pg-pill {{ $r->status->value }}">{{ ucfirst($r->status->value) }}</span></td>
                            <td style="color:var(--muted);">{{ $r->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="pg-panel">
        <h3>Referrals by Source</h3>
        <p class="sub">Where your referrals are coming from.</p>
        @forelse($bySource->sortDesc() as $src => $count)
            @php $pct = $srcTotal ? round($count / $srcTotal * 100) : 0; @endphp            <div style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:6px;"><span style="text-transform:capitalize; color:var(--text); font-weight:600;">{{ $src }}</span><span style="color:var(--muted);">{{ $count }} · {{ $pct }}%</span></div>
                <div class="pg-bar"><span style="width:{{ $pct }}%; background:{{ $srcColors[$src] ?? '#94a3b8' }};"></span></div>
            </div>
        @empty
            <div style="text-align:center; color:var(--muted); padding:24px;">No source data yet.</div>
        @endforelse

        <a href="{{ route('influencer.invite.tools') }}" style="display:block; text-align:center; margin-top:16px; padding:10px; background:var(--orange-soft); color:var(--orange-dark); border-radius:10px; font-family:var(--ff); font-weight:700; font-size:12.5px;">Get Sharing Tools →</a>
    </div>
</div>

@include('influencer.program._copy_js')
@endsection
