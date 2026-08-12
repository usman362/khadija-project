@extends('layouts.dashboard')

@section('title', 'Reports')

@php
    /*
     * Every figure here is a count or a sum of records the platform already
     * holds. Nothing is modelled or projected, and a value the data cannot
     * support prints a dash rather than a plausible number — a report is what
     * gets acted on, so an invented figure here is worse than a missing one.
     */
    $money  = $report['money'];
    $market = $report['marketplace'];
    $people = $report['people'];
    $queue  = $report['needs_attention'];

    $n = fn ($v) => $v === null ? '—' : number_format($v);
    $m = fn ($v) => $v === null ? '—' : '$' . number_format($v, 2);
    $p = fn ($v) => $v === null ? '—' : $v . '%';
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="bar-chart-3" class="me-2" style="width:24px;height:24px;"></i> Reports</h4>
        <p class="text-secondary mb-0">
            {{ $report['from']->format('M j, Y') }} – {{ $report['to']->format('M j, Y') }}.
            Everything below is counted from real bookings, bids and events.
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-2">
            <select name="range" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Range">
                @foreach($ranges as $value => $label)
                    <option value="{{ $value }}" @selected($range === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('app.admin.reports.csv', ['range' => $range]) }}" class="btn btn-sm btn-primary">
            <i data-lucide="download" style="width:15px;height:15px;"></i> CSV
        </a>
    </div>
</div>

{{-- ── Money ───────────────────────────────────────────────── --}}
<h6 class="text-secondary text-uppercase small mb-2">Money</h6>
<div class="row g-3 mb-4">
    @foreach([
        ['Booked value', $m($money['gross']), 'Completed bookings in this period'],
        ['Commission earned', $m($money['commission']), 'Per professional’s own rate, not a blended one'],
        ['Completed bookings', $n($money['bookings']), null],
        ['Average booking', $m($money['average_value']), null],
    ] as [$label, $value, $note])
        <div class="col-md-3 col-sm-6">
            <div class="card h-100"><div class="card-body">
                <p class="text-secondary mb-1 small">{{ $label }}</p>
                <h3 class="mb-0">{{ $value }}</h3>
                @if($note)<p class="text-secondary mb-0" style="font-size:11.5px;">{{ $note }}</p>@endif
            </div></div>
        </div>
    @endforeach
</div>

{{-- ── Marketplace ─────────────────────────────────────────── --}}
<h6 class="text-secondary text-uppercase small mb-2">Marketplace</h6>
<div class="row g-3 mb-4">
    @foreach([
        ['Gigs posted', $n($market['posted']), null],
        ['Got at least one bid', $p($market['bid_rate']), 'The number that decides whether this works'],
        ['Ended in a hire', $p($market['award_rate']), null],
        ['Bids placed', $n($market['bids']), null],
        ['Bids per gig', $market['bids_per_gig'] ?? '—', 'Counted on gigs that got one'],
        ['Time to first bid', $market['time_to_first_bid_hours'] === null ? '—' : \App\Support\ResponseStats::describe($market['time_to_first_bid_hours']), 'How long a client waits'],
    ] as [$label, $value, $note])
        <div class="col-md-2 col-sm-6">
            <div class="card h-100"><div class="card-body">
                <p class="text-secondary mb-1 small">{{ $label }}</p>
                <h4 class="mb-0">{{ $value }}</h4>
                @if($note)<p class="text-secondary mb-0" style="font-size:11px;">{{ $note }}</p>@endif
            </div></div>
        </div>
    @endforeach
</div>

{{-- ── People ──────────────────────────────────────────────── --}}
<h6 class="text-secondary text-uppercase small mb-2">People</h6>
<div class="row g-3 mb-4">
    @foreach([
        ['New accounts', $n($people['signups'])],
        ['Professionals who bid', $n($people['active_pros'])],
        ['Clients who posted', $n($people['active_clients'])],
    ] as [$label, $value])
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <p class="text-secondary mb-1 small">{{ $label }}</p>
            <h3 class="mb-0">{{ $value }}</h3>
        </div></div></div>
    @endforeach
</div>
<p class="text-secondary small mb-4" style="margin-top:-8px;">
    “Active” means someone did something — placed a bid, posted a gig. There is no last-seen record,
    and counting accounts that merely exist is how a marketplace convinces itself it is busy.
</p>

{{-- ── Needs attention ─────────────────────────────────────── --}}
<h6 class="text-secondary text-uppercase small mb-2">Needs attention</h6>
<div class="card mb-4"><div class="card-body">
    <p class="text-secondary small mb-3">
        Not limited to the dates above — a document waiting since June is exactly what you need to see today.
    </p>
    <div class="row g-3">
        @foreach([
            ['Files held for review', $queue['uploads_held'], route('app.admin.uploads.index')],
            ['Verifications pending', $queue['verification_pending'], route('app.admin.verifications.index')],
            ['Open gigs with no bids', $queue['open_gigs_no_bids'], null],
            ['Waiting on their state', $queue['out_of_area_waitlist'], route('app.admin.waitlist.index')],
        ] as [$label, $count, $link])
            <div class="col-md-3 col-sm-6">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">{{ $label }}</span>
                    @if($link && $count > 0)
                        <a href="{{ $link }}" class="badge bg-warning text-dark text-decoration-none">{{ number_format($count) }}</a>
                    @else
                        <span class="badge bg-secondary">{{ number_format($count) }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div></div>

{{-- ── By state ────────────────────────────────────────────── --}}
<h6 class="text-secondary text-uppercase small mb-2">By state</h6>
<div class="card"><div class="card-body">
    <p class="text-secondary small mb-3">
        Since R38, this is seven separate same-state marketplaces rather than one pooled market —
        so a total that hides which state produced it is not much use for deciding where to put effort.
    </p>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th>State</th><th class="text-end">Gigs posted</th>
                <th class="text-end">Professionals</th><th class="text-end">Booked value</th>
            </tr></thead>
            <tbody>
                @foreach($report['by_state'] as $row)
                    <tr>
                        <td><b>{{ $row['state'] }}</b></td>
                        <td class="text-end">{{ number_format($row['gigs']) }}</td>
                        <td class="text-end">{{ number_format($row['professionals']) }}</td>
                        <td class="text-end">{{ $row['revenue'] ? '$' . number_format($row['revenue'], 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div></div>
@endsection
