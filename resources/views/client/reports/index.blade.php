@extends('layouts.client')

@section('title', 'Reports')

@php
    /*
     * Peter, 2026-08-09 — clients had no reporting at all.
     *
     * A client's question is not the professional's. They are not competing
     * for work; they are spending money and want to know where it went, who
     * they keep hiring, and whether posting a request produces anyone.
     *
     * The shared figures come from App\Support\ClientStats, so this, the
     * public Portfolio and the Dashboard cannot disagree about one account.
     */
    $spend = $report['spend'];
    $reqs  = $report['requests'];
    $me    = $report['standing'];

    $n = fn ($v) => $v === null ? '—' : number_format($v);
    $m = fn ($v) => $v === null ? '—' : '$' . number_format($v, 2);
    $p = fn ($v) => $v === null ? '—' : $v . '%';
@endphp

@push('styles')
<style>
    .cr-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .cr-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(165px,1fr)); gap:13px; margin-bottom:22px; }
    .cr-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:13px; padding:15px 16px; }
    .cr-k { font-size:11.5px; color:var(--text-muted); }
    .cr-v { font-size:22px; font-weight:800; margin-top:3px; }
    .cr-note { font-size:11px; color:var(--text-muted); margin-top:3px; line-height:1.45; }
    .cr-sec { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin:0 0 10px; }
    .cr-row { display:flex; justify-content:space-between; gap:12px; padding:9px 0; border-top:1px solid var(--border-color); font-size:13.5px; }
    .cr-row:first-of-type { border-top:0; }
    .cr-select { padding:6px 10px; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-card); color:var(--text-primary); font-size:13px; }
    .cr-two { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:16px; }
</style>
@endpush

@section('content')
<div class="cr-head">
    <div>
        <h1 style="font-size:22px;font-weight:800;margin:0;">Reports</h1>
        <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">
            {{ $report['from']->format('M j, Y') }} – {{ $report['to']->format('M j, Y') }}.
            Counted from your real events, bookings and reviews.
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET">
            <select name="range" class="cr-select" onchange="this.form.submit()" aria-label="Range">
                @foreach($ranges as $value => $label)
                    <option value="{{ $value }}" @selected($range === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('client.reports.csv', ['range' => $range]) }}" class="cl-btn cl-btn-primary">Download CSV</a>
    </div>
</div>

<p class="cr-sec">What you spent</p>
<div class="cr-grid">
    <div class="cr-card">
        <div class="cr-k">Spent in this period</div>
        <div class="cr-v">{{ $m($spend['spent']) }}</div>
        {{-- The client pays the professional's price. Commission comes out of
             the professional at payout, so showing it here would misstate
             what an event actually cost. --}}
        <div class="cr-note">What you paid your professionals</div>
    </div>
    <div class="cr-card"><div class="cr-k">Events completed</div><div class="cr-v">{{ $n($spend['bookings']) }}</div></div>
    <div class="cr-card"><div class="cr-k">Average event</div><div class="cr-v">{{ $m($spend['average']) }}</div></div>
    <div class="cr-card">
        <div class="cr-k">Committed</div>
        <div class="cr-v">{{ $m($spend['committed']) }}</div>
        <div class="cr-note">Booked but not finished yet</div>
    </div>
</div>

<p class="cr-sec">Your requests</p>
<div class="cr-grid">
    <div class="cr-card"><div class="cr-k">Requests posted</div><div class="cr-v">{{ $n($reqs['posted']) }}</div></div>
    <div class="cr-card">
        <div class="cr-k">Got at least one bid</div>
        <div class="cr-v">{{ $p($reqs['got_a_bid']) }}</div>
    </div>
    <div class="cr-card"><div class="cr-k">Bids received</div><div class="cr-v">{{ $n($reqs['bids_received']) }}</div></div>
    <div class="cr-card">
        <div class="cr-k">Bids per request</div>
        <div class="cr-v">{{ $reqs['bids_per_request'] ?? '—' }}</div>
        <div class="cr-note">Counted on requests that got one</div>
    </div>
    <div class="cr-card"><div class="cr-k">Professionals hired</div><div class="cr-v">{{ $n($reqs['hired']) }}</div></div>
</div>

<p class="cr-sec">How professionals see you</p>
<div class="cr-grid">
    <div class="cr-card">
        <div class="cr-k">Your rating</div>
        <div class="cr-v">{{ $me['rating'] ? number_format($me['rating'], 1) : '—' }}</div>
        <div class="cr-note">{{ $me['reviews'] }} {{ Str::plural('review', $me['reviews']) }} from professionals</div>
    </div>
    <div class="cr-card">
        <div class="cr-k">You reply to</div>
        <div class="cr-v">{{ $p($me['response_rate']) }}</div>
        <div class="cr-note">Of the messages sent to you</div>
    </div>
    <div class="cr-card">
        <div class="cr-k">Usually within</div>
        <div class="cr-v" style="font-size:18px;">{{ \App\Support\ResponseStats::describe($me['response_hours']) }}</div>
    </div>
    <div class="cr-card">
        <div class="cr-k">Cancellation rate</div>
        <div class="cr-v">{{ $p($me['cancellation_rate']) }}</div>
        <div class="cr-note">Of the bookings that reached an outcome</div>
    </div>
    <div class="cr-card">
        <div class="cr-k">Booked more than once</div>
        <div class="cr-v">{{ $n($me['repeat_pros']) }}</div>
        <div class="cr-note">Professionals you went back to</div>
    </div>
</div>

<div class="cr-two">
    <div>
        <p class="cr-sec">Who you hire</p>
        <div class="cr-card">
            @forelse($report['professionals'] as $row)
                <div class="cr-row">
                    <span>
                        <b>{{ $row['name'] }}</b>
                        <span style="color:var(--text-muted);font-size:12px;">
                            · {{ $row['bookings'] }} {{ Str::plural('booking', $row['bookings']) }}
                        </span>
                    </span>
                    <span>{{ $row['spent'] ? '$' . number_format($row['spent']) : '—' }}</span>
                </div>
            @empty
                <p style="font-size:13px;color:var(--text-muted);margin:0;">
                    Nobody yet. Professionals appear here once you book one.
                </p>
            @endforelse
        </div>
    </div>

    <div>
        <p class="cr-sec">What you run</p>
        <div class="cr-card">
            @forelse($report['event_types'] as $name => $count)
                <div class="cr-row">
                    <span>{{ $name }}</span>
                    <span>{{ $count }} {{ Str::plural('event', $count) }}</span>
                </div>
            @empty
                <p style="font-size:13px;color:var(--text-muted);margin:0;">
                    Your completed events are grouped here by service.
                </p>
            @endforelse
        </div>
    </div>
</div>
@endsection
