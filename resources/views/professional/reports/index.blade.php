@extends('layouts.professional')

@section('title', 'Reports')

@php
    /*
     * Peter, 2026-08-09 — professionals had two CSV exports and no view of
     * whether their bidding is working, which is the only question a
     * professional on a marketplace actually has.
     *
     * Money comes from App\Support\Earnings and the response figures from
     * App\Support\ResponseStats, not recomputed here: a report that disagrees
     * with the Earnings page about the same money is worse than no report.
     */
    $bid  = $report['bidding'];
    $cash = $report['money'];
    $rep  = $report['reputation'];
    $opps = $report['opportunities'];

    $n = fn ($v) => $v === null ? '—' : number_format($v);
    $m = fn ($v) => $v === null ? '—' : '$' . number_format($v, 2);
    $p = fn ($v) => $v === null ? '—' : $v . '%';

    $peak = collect($report['over_time'])->max('earned') ?: 1;
@endphp

@push('styles')
<style>
    .rp-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .rp-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); gap:13px; margin-bottom:22px; }
    .rp-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:13px; padding:15px 16px; }
    .rp-k { font-size:11.5px; color:var(--text-muted); }
    .rp-v { font-size:22px; font-weight:800; margin-top:3px; }
    .rp-note { font-size:11px; color:var(--text-muted); margin-top:3px; line-height:1.45; }
    .rp-sec { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin:0 0 10px; }
    .rp-bars { display:flex; align-items:flex-end; gap:6px; height:130px; padding-top:6px; }
    .rp-bar { flex:1; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:5px; min-width:0; }
    .rp-bar-fill { width:100%; background:var(--brand, #2563eb); border-radius:5px 5px 0 0; min-height:2px; }
    .rp-bar-label { font-size:9.5px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
    .rp-select { padding:6px 10px; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-card); color:var(--text-primary); font-size:13px; }
</style>
@endpush

@section('content')
<div class="rp-head">
    <div>
        <h1 style="font-size:22px;font-weight:800;margin:0;">Reports</h1>
        <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">
            {{ $report['from']->format('M j, Y') }} – {{ $report['to']->format('M j, Y') }}.
            Counted from your real bids, bookings and reviews.
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET">
            <select name="range" class="rp-select" onchange="this.form.submit()" aria-label="Range">
                @foreach($ranges as $value => $label)
                    <option value="{{ $value }}" @selected($range === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('professional.reports.csv', ['range' => $range]) }}" class="cl-btn cl-btn-primary">Download CSV</a>
    </div>
</div>

<p class="rp-sec">Your bidding</p>
<div class="rp-grid">
    <div class="rp-card"><div class="rp-k">Bids placed</div><div class="rp-v">{{ $n($bid['placed']) }}</div></div>
    <div class="rp-card">
        <div class="rp-k">Win rate</div>
        <div class="rp-v">{{ $p($bid['win_rate']) }}</div>
        {{-- Open bids are neither won nor lost. Counting them as losses would
             tell someone their bidding is failing when it is in progress. --}}
        <div class="rp-note">Of the {{ $bid['won'] + $bid['lost'] }} that were decided</div>
    </div>
    <div class="rp-card"><div class="rp-k">Won</div><div class="rp-v" style="color:var(--ok-text);">{{ $n($bid['won']) }}</div></div>
    <div class="rp-card"><div class="rp-k">Still open</div><div class="rp-v">{{ $n($bid['open']) }}</div></div>
    <div class="rp-card"><div class="rp-k">Average bid</div><div class="rp-v">{{ $m($bid['average_bid']) }}</div></div>
</div>

<p class="rp-sec">Your money</p>
<div class="rp-grid">
    <div class="rp-card">
        <div class="rp-k">Earned in this period</div>
        <div class="rp-v">{{ $m($cash['earned_in_range']) }}</div>
        <div class="rp-note">After your {{ rtrim(rtrim(number_format($cash['commission_pct'], 1), '0'), '.') }}% commission</div>
    </div>
    <div class="rp-card"><div class="rp-k">Bookings completed</div><div class="rp-v">{{ $n($cash['bookings_in_range']) }}</div></div>
    {{-- Balances, not range figures — you cannot have "available to withdraw,
         last 30 days", so they are labelled for what they are. --}}
    <div class="rp-card">
        <div class="rp-k">Available to withdraw</div>
        <div class="rp-v">{{ $m($cash['available']) }}</div>
        <div class="rp-note">Your balance today, not this period</div>
    </div>
    <div class="rp-card">
        <div class="rp-k">Still to come</div>
        <div class="rp-v">{{ $m($cash['pending']) }}</div>
        <div class="rp-note">From bookings not yet completed</div>
    </div>
    <div class="rp-card"><div class="rp-k">Earned all time</div><div class="rp-v">{{ $m($cash['lifetime_earned']) }}</div></div>
</div>

<p class="rp-sec">How clients see you</p>
<div class="rp-grid">
    <div class="rp-card">
        <div class="rp-k">Rating</div>
        <div class="rp-v">{{ $rep['rating'] ? number_format($rep['rating'], 1) : '—' }}</div>
        <div class="rp-note">{{ $rep['reviews'] }} {{ Str::plural('review', $rep['reviews']) }}</div>
    </div>
    <div class="rp-card">
        <div class="rp-k">You reply to</div>
        <div class="rp-v">{{ $p($rep['response_rate']) }}</div>
        <div class="rp-note">Of the messages sent to you</div>
    </div>
    <div class="rp-card">
        <div class="rp-k">Usually within</div>
        <div class="rp-v" style="font-size:18px;">{{ \App\Support\ResponseStats::describe($rep['response_hours']) }}</div>
    </div>
    <div class="rp-card">
        <div class="rp-k">Open to you now</div>
        <div class="rp-v">{{ $n($opps['in_your_services']) }}</div>
        <div class="rp-note">
            @if(! $opps['has_services'])
                <a href="{{ route('professional.profile.index') }}">Add your services</a> to see matches
            @elseif($opps['related'] > 0)
                {{ $opps['related'] }} more nearby, outside your services
            @else
                In the services you list
            @endif
        </div>
    </div>
</div>

<p class="rp-sec">Earnings by month</p>
<div class="rp-card">
    @if(collect($report['over_time'])->sum('earned') > 0)
        <div class="rp-bars">
            @foreach($report['over_time'] as $row)
                <div class="rp-bar" title="{{ $row['month'] }} — ${{ number_format($row['earned'], 2) }} from {{ $row['bookings'] }} {{ Str::plural('booking', $row['bookings']) }}">
                    <div class="rp-bar-fill" style="height: {{ max(2, (int) round($row['earned'] / $peak * 100)) }}%;"></div>
                    <span class="rp-bar-label">{{ Str::before($row['month'], ' ') }}</span>
                </div>
            @endforeach
        </div>
        {{-- Empty months are drawn as zero rather than skipped: leaving them
             out makes a quiet spring look like continuous work. --}}
        <p class="rp-note" style="margin-top:10px;">Months with no completed bookings are shown as zero.</p>
    @else
        <p style="font-size:13px;color:var(--text-muted);margin:0;">
            No completed bookings in this period yet. They appear here once an event is finished.
        </p>
    @endif
</div>
@endsection
