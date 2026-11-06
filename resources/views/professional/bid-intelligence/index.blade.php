@extends('layouts.professional')

@section('title', 'Bid Intelligence')
@section('page-title', 'Bid Intelligence')

{{-- Khadijah's 28 October screen.

     Three of her panels are not here, and each is deliberate. All three would
     be a number invented on the screen a professional uses to decide what to
     charge:

       AI Win-Rate Predictor   there is no model. A predicted win rate would
                               be arithmetic dressed as a forecast.
       Market Context          nothing records what anybody else bid, so
                               "the average winning bid" has no source.
       Why You Win / Why You   nothing records why a client chose somebody
       Lose                    else. A decline carries no reason.

     What IS here is read from this professional's own bids. --}}

@push('styles')
<style>
    .bi-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; flex-wrap:wrap; margin-bottom:16px; }
    .bi-head h1 { font-size:24px; font-weight:800; color:var(--text-primary); margin:0 0 3px; }
    .bi-head p { font-size:12.5px; color:var(--text-muted); margin:0; }
    .bi-head-r { display:flex; flex-direction:column; align-items:flex-end; gap:6px; }
    .bi-head-acts { display:flex; gap:9px; flex-wrap:wrap; }
    .bi-stamp { font-size:11px; color:var(--text-muted); }

    .bi-btn { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700;
        padding:9px 15px; border-radius:9px; text-decoration:none; background:var(--bg-card);
        color:var(--text-primary); border:1px solid var(--border-color); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .bi-btn svg { width:15px; height:15px; flex:none; }
    .bi-btn.blue { background:var(--accent-blue, #2563eb); border-color:var(--accent-blue, #2563eb); color:#fff; }

    .bi-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:12px 13px; }
    .bi-card h3 { display:flex; align-items:center; justify-content:space-between; gap:9px;
        font-size:12.5px; font-weight:800; color:var(--text-primary); margin:0 0 10px; }
    .bi-card h3 .t { display:inline-flex; align-items:center; gap:8px; min-width:0; }
    .bi-card h3 svg { width:14px; height:14px; flex:none; color:var(--accent-blue, #2563eb); }
    .bi-more { font-size:11.5px; font-weight:700; color:var(--accent-blue, #2563eb); text-decoration:none; white-space:nowrap; }

    /* Her filter bar: a label over every control. */
    /* Her bar: a label over every control, all on one line, and the date
       range as a quiet line under the first one. align-items:flex-end made
       the whole bar as tall as that extra line. */
    .bi-filters { display:flex; gap:11px; align-items:flex-start; flex-wrap:wrap;
        /* every control sits on the label line above it, so they share a top */
        background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px;
        padding:11px 14px; margin-bottom:13px; }
    /* The five controls share the row rather than sitting at a fixed width
       with a gap before Reset Filters. */
    .bi-f { display:flex; flex-direction:column; gap:4px; min-width:0; flex:1 1 0; }
    .bi-f label { font-size:10.5px; font-weight:700; color:var(--text-muted); }
    /* .bi-f is a COLUMN flex box, so flex-basis here sets the select's
       height, not its width. It came out 150px tall. */
    .bi-f select { font-family:inherit; font-size:12px; padding:7px 9px; border-radius:8px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card));
        color:var(--text-primary); width:100%; min-width:0; flex:none; }
    .bi-f .range { font-size:10px; color:var(--text-muted); line-height:1.3; }
    .bi-reset { flex:0 0 auto; }
    .bi-reset .bi-btn { justify-content:center; }

    .bi-tiles { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:13px; }
    .bi-tile { display:flex; gap:10px; align-items:flex-start; background:var(--bg-card);
        border:1px solid var(--border-color); border-radius:12px; padding:12px 13px; }
    .bi-tile .ic { width:32px; height:32px; border-radius:9px; flex:none; display:flex; align-items:center;
        justify-content:center; color:var(--c); background:color-mix(in srgb, var(--c) 13%, transparent); }
    .bi-tile .ic svg { width:16px; height:16px; }
    .bi-tile .m { min-width:0; }
    .bi-tile .k { display:flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700;
        color:var(--text-secondary, var(--text-muted)); line-height:1.3; }
    .bi-i { width:12px; height:12px; flex:none; color:var(--text-muted); cursor:help; opacity:.7; }
    .bi-tile .v { display:block; font-size:22px; font-weight:800; color:var(--text-primary); line-height:1.25; }
    .bi-tile .s { display:block; font-size:10.5px; color:var(--text-muted); line-height:1.4; }
    /* Her trend line, under each tile. */
    .bi-tile .t { display:block; font-size:10.5px; font-weight:700; margin-top:3px; }
    .bi-tile .t.up { color:#059669; }
    .bi-tile .t.down { color:#dc2626; }
    .bi-tile .t.flat { color:var(--text-muted); font-weight:600; }

    .bi-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:12px; align-items:start; }
    .bi-grid.four { grid-template-columns:repeat(4, minmax(0,1fr)); }

    /* Funnel */
    .bi-fun { display:flex; flex-direction:column; gap:9px; }
    .bi-fun .row { display:grid; grid-template-columns:76px minmax(0,1fr) auto; gap:9px; align-items:center; }
    .bi-fun .lbl { font-size:11.5px; font-weight:700; color:var(--text-primary); }
    .bi-fun .track { display:block; height:17px; border-radius:4px;
        background:var(--bg-page, rgba(128,128,128,.1)); overflow:hidden; }
    /* display:block, or a span ignores both width and height and the funnel
       draws nothing but its own grey track. */
    .bi-fun .fill { display:block; height:100%; border-radius:4px; background:var(--c); }
    .bi-fun .fig { font-size:11px; font-weight:700; color:var(--text-muted); white-space:nowrap; }

    .bi-table { width:100%; border-collapse:collapse; font-size:11.5px; }
    .bi-table th { text-align:left; font-size:10px; font-weight:700; color:var(--text-muted);
        padding:0 5px 6px; border-bottom:1px solid var(--border-color); white-space:nowrap; }
    .bi-table td { padding:7px 5px; border-bottom:1px solid var(--border-color); color:var(--text-primary); }
    .bi-table tr:last-child td { border-bottom:0; }
    .bi-table td.num, .bi-table th.num { text-align:right; }
    .bi-bar { display:block; height:5px; border-radius:3px;
        background:var(--bg-page, rgba(128,128,128,.12)); overflow:hidden; margin-top:4px; }
    .bi-bar i { display:block; height:100%; background:var(--accent-blue, #2563eb); }

    .bi-note { font-size:10px; color:var(--text-muted); line-height:1.55; margin:9px 0 0; }
    .bi-none { font-size:11.5px; color:var(--text-muted); line-height:1.6; }

    /* Column chart, for the timing and the trend. */
    .bi-cols { display:flex; align-items:flex-end; gap:7px; height:104px; padding-top:5px; }
    .bi-col { flex:1 1 0; display:flex; flex-direction:column; justify-content:flex-end;
        align-items:center; gap:5px; min-width:0; height:100%; }
    .bi-col .bar { display:block; width:100%; max-width:40px; border-radius:5px 5px 0 0;
        background:var(--accent-blue, #2563eb); min-height:3px; }
    .bi-col .bar.flat { background:var(--border-color); }
    .bi-col .top { font-size:10.5px; font-weight:700; color:var(--text-primary); }
    .bi-xaxis { display:flex; gap:8px; margin-top:7px; }
    .bi-xaxis span { flex:1 1 0; text-align:center; font-size:9.5px; color:var(--text-muted); line-height:1.3; min-width:0; }

    .bi-split { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .bi-big { font-size:22px; font-weight:800; color:var(--text-primary); line-height:1.2; }
    .bi-big.bad { color:var(--bad-text, #dc2626); }
    .bi-sub { font-size:11px; color:var(--text-muted); line-height:1.5; }

    .bi-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:9px; }
    .bi-act { display:flex; gap:10px; align-items:center; }
    .bi-act .m { min-width:0; flex:1 1 auto; }
    .bi-act .m b { display:block; font-size:12px; font-weight:700; color:var(--text-primary);
        line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bi-act .m span { display:block; font-size:10.5px; color:var(--text-muted);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bi-act .go { font-size:11px; font-weight:700; padding:5px 11px; border-radius:7px; text-decoration:none;
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 11%, transparent);
        color:var(--accent-blue, #2563eb); white-space:nowrap; flex:none; }

    .bi-chip { display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
        background:var(--bg-page, rgba(128,128,128,.1)); color:var(--text-muted); }
    .bi-up { color:#059669; font-weight:700; }
    .bi-down { color:#dc2626; font-weight:700; }

    /* Her toggle pills, above a chart or a split. */
    .bi-toggle { display:inline-flex; gap:3px; background:var(--bg-page, rgba(128,128,128,.08));
        border:1px solid var(--border-color); border-radius:8px; padding:3px; margin-bottom:10px; }
    .bi-toggle button { padding:5px 10px; border-radius:6px; font-size:10.5px; font-weight:700;
        border:0; background:none; color:var(--text-muted); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .bi-toggle button.on { background:var(--accent-blue, #2563eb); color:#fff; }

    .bi-lost { margin-top:11px; display:flex; flex-direction:column; gap:8px; }
    .bi-lost .row { display:grid; grid-template-columns:62px minmax(0,1fr) auto auto; gap:8px; align-items:center; }
    .bi-lost .l { font-size:11px; font-weight:700; color:var(--text-primary); }
    .bi-lost .track { display:block; height:8px; border-radius:4px;
        background:var(--bg-page, rgba(128,128,128,.12)); overflow:hidden; }
    .bi-lost .track i { display:block; height:100%; }
    .bi-lost .v { font-size:11.5px; font-weight:800; color:var(--text-primary); white-space:nowrap; }
    .bi-lost .n { font-size:10px; color:var(--text-muted); white-space:nowrap; }
    [data-lostpane][hidden] { display:none; }

    .bi-check { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:7px; }
    .bi-check li { display:flex; align-items:center; gap:8px; font-size:11.5px;
        color:var(--text-secondary, var(--text-muted)); }
    .bi-check svg { width:14px; height:14px; flex:none; color:var(--border-color); }
    .bi-check li.on svg { color:#059669; }
    .bi-check span { flex:1 1 auto; min-width:0; }
    .bi-check b { font-size:11.5px; font-weight:800; color:var(--text-primary); white-space:nowrap; }

    .bi-act .when { font-size:10px; font-weight:700; color:var(--bad-text, #dc2626); white-space:nowrap; flex:none; }
    .bi-foot { display:block; margin-top:10px; text-align:center; font-size:11px; font-weight:700;
        padding:8px 10px; border-radius:8px; text-decoration:none;
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 11%, transparent);
        color:var(--accent-blue, #2563eb); }

    .bi-missing { border-style:dashed; }
    .bi-missing h3 svg { color:var(--text-muted); }

    @media (max-width: 1250px) { .bi-grid, .bi-grid.four { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .bi-tiles { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .bi-grid, .bi-grid.four, .bi-tiles { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php
    $tz    = config('app.display_timezone');
    $money = fn ($v) => '$' . number_format((float) $v, 0);
    $rate  = fn ($r) => $r === null ? 'Nothing decided' : $r . '%';

    $paths = [
        'help'  => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'doc'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'reset' => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>',
        'cal'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'mail'  => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'send'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'cup'   => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3"/><path d="M7 5H4v2a3 3 0 0 0 3 3"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'pin'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'cash'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'warn'  => '<path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'sun'   => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'bulb'  => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>',
        'check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'bell'  => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'ban'   => '<circle cx="12" cy="12" r="10"/><path d="m5 5 14 14"/>',
    ];
    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';

    /*
     * Her arrow and percentage against the span before. Nothing where there
     * was nothing to compare against: a first period is not an infinite
     * improvement on no period at all.
     */
    $tip = fn (string $t) => '<svg class="bi-i" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round"><title>' . e($t) . '</title><circle cx="12" cy="12" r="10"/>
        <path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';

    $arrow = function (?int $pct) use ($trend) {
        if (! $trend['span']) {
            return '';
        }

        if ($pct === null) {
            return '<span class="t flat">No earlier period to compare</span>';
        }

        $cls = $pct >= 0 ? 'up' : 'down';
        $mark = $pct >= 0 ? "↑" : "↓";

        return '<span class="t ' . $cls . '">' . $mark . ' ' . abs($pct) . '% ' . e($trend['span']) . '</span>';
    };

    $rec    = $performance['record'];
    $funnel = $performance['funnel'];
    $since  = $filters['period'] === 'all' ? null : now()->subDays((int) ($filters['period'] ?: 90));
@endphp

<div class="bi-head">
    <div>
        <h1>Bid Intelligence</h1>
        {{-- Her line ends "to win more". Nothing on this page can promise
             that, and the compliance rules forbid saying it. --}}
        <p>Track your bid performance and see what your own record shows about what works.</p>
    </div>
    <div class="bi-head-r">
        <div class="bi-head-acts">
            <a class="bi-btn" href="{{ route('public.faq') }}">{!! $svg('help') !!} View Help Guide</a>
            <a class="bi-btn" href="{{ route('professional.reports.index') }}">{!! $svg('doc') !!} Export PDF</a>
        </div>
        @if($hasAnyBids)
            <span class="bi-stamp">Last updated: {{ now()->timezone($tz)->format('M j, Y \a\t g:i A T') }}</span>
        @else
            <span class="bi-stamp">Every figure below is read from your own bids.</span>
        @endif
    </div>
</div>

@if($hasAnyBids)
<form method="GET" action="{{ route('professional.bid-intelligence.index') }}" class="bi-filters">
    <span class="bi-f">
        <label for="biPeriod">{!! $svg('cal', 'style="width:12px;height:12px;vertical-align:-1px;"') !!} Time Period</label>
        <select id="biPeriod" name="period" onchange="this.form.submit()">
            @foreach($options['periods'] as $key => $label)
                <option value="{{ $key }}" @selected($filters['period'] === (string) $key)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="range">
            {{ $since ? $since->timezone($tz)->format('M j, Y') . ' to ' . now()->timezone($tz)->format('M j, Y') : 'Everything you have bid on' }}
        </span>
    </span>

    <span class="bi-f">
        <label for="biService">My Services</label>
        <select id="biService" name="service" onchange="this.form.submit()">
            <option value="">All Services</option>
            @foreach($options['services'] as $o)<option value="{{ $o }}" @selected($filters['service'] === $o)>{{ $o }}</option>@endforeach
        </select>
    </span>

    <span class="bi-f">
        <label for="biType">Event Type</label>
        <select id="biType" name="type" onchange="this.form.submit()">
            <option value="">All Event Types</option>
            @foreach($options['types'] as $o)<option value="{{ $o }}" @selected($filters['type'] === $o)>{{ $o }}</option>@endforeach
        </select>
    </span>

    <span class="bi-f">
        {{-- R38: a professional is bookable in their own state, so "all
             locations" is all the places they can actually work. --}}
        <label for="biPlace">Location {!! $tip('Everywhere you can be booked, which is your own state.') !!}</label>
        <select id="biPlace" name="place" onchange="this.form.submit()">
            <option value="">All Locations (Same State)</option>
            @foreach($options['places'] as $o)<option value="{{ $o }}" @selected($filters['place'] === $o)>{{ $o }}</option>@endforeach
        </select>
    </span>

    <span class="bi-f">
        <label for="biBand">Price Range</label>
        <select id="biBand" name="band" onchange="this.form.submit()">
            <option value="">All Price Ranges</option>
            @foreach($options['bands'] as $o)<option value="{{ $o }}" @selected($filters['band'] === $o)>{{ $o }}</option>@endforeach
        </select>
    </span>

    {{-- Carried in the same label-over-control shape as the five beside it,
         so it lines up with the selects rather than with the bottom of the
         bar, which the date range line below Time Period makes taller. --}}
    <span class="bi-f bi-reset">
        <label aria-hidden="true">&nbsp;</label>
        <a class="bi-btn" href="{{ route('professional.bid-intelligence.index') }}">{!! $svg('reset') !!} Reset Filters</a>
    </span>
</form>
@endif

<div class="bi-tiles">
    @php
        $invited = $funnel[0]['count'] ?? 0;
        $share = fn ($n, $of) => $of > 0 ? round($n / $of * 100, 1) . '% of ' : null;
    @endphp

    <div class="bi-tile" style="--c:#2563eb;">
        <span class="ic">{!! $svg('mail') !!}</span>
        <span class="m">
            <span class="k">Invitations Received {!! $tip('Direct requests sent to you, plus open requests you answered.') !!}</span>
            <span class="v">{{ $invited }}</span>
            <span class="s">Direct requests and open ones you answered</span>
        </span>
    </div>

    <div class="bi-tile" style="--c:#7c3aed;">
        <span class="ic">{!! $svg('send') !!}</span>
        <span class="m">
            <span class="k">Submitted Bids {!! $tip('Bids you actually sent in this period.') !!}</span>
            <span class="v">{{ $rec['placed'] }}</span>
            <span class="s">{{ $invited > 0 ? round($rec['placed'] / $invited * 100) . '% of invitations' : 'No invitations yet' }}</span>
            {!! $arrow($trend['submitted']) !!}
        </span>
    </div>

    <div class="bi-tile" style="--c:#d97706;">
        <span class="ic">{!! $svg('clock') !!}</span>
        <span class="m">
            {{-- Her third tile is "Client Viewed". Nothing records a client
                 opening a bid, so this counts the ones nobody has decided
                 either way, which is the thing a professional can act on. --}}
            <span class="k">Still Open {!! $tip('Bids nobody has accepted or declined yet.') !!}</span>
            <span class="v">{{ $rec['open'] }}</span>
            <span class="s">Nobody has decided these either way</span>
            {!! $arrow($trend['open']) !!}
        </span>
    </div>

    <div class="bi-tile" style="--c:#059669;">
        <span class="ic">{!! $svg('cup') !!}</span>
        <span class="m">
            <span class="k">Accepted {!! $tip('Bids a client said yes to.') !!}</span>
            <span class="v">{{ $rec['won'] }}</span>
            <span class="s">
                {{ $rec['rate'] === null ? 'Nothing decided yet' : $rec['rate'] . '% win rate' }}
            </span>
            {!! $arrow($trend['accepted']) !!}
        </span>
    </div>
</div>

<div class="bi-grid">
    <div class="bi-card">
        <h3><span class="t">{!! $svg('chart') !!} Bid Funnel Conversion</span></h3>
        @if($rec['placed'])
            <div class="bi-fun">
                @foreach($funnel as $i => $stage)
                    <div class="row" style="--c: {{ ['#2563eb', '#7c3aed', '#059669'][$i] ?? '#64748b' }};">
                        <span class="lbl">{{ $stage['label'] }}</span>
                        {{-- The bar narrows with the stage, so the shape of
                             the drop is visible without reading the numbers. --}}
                        <span class="track"><span class="fill" style="width: {{ max(4, $stage['pct']) }}%;"></span></span>
                        <span class="fig">{{ $stage['count'] }} ({{ $stage['pct'] }}%)</span>
                    </div>
                @endforeach
            </div>
            {{-- Her funnel has a "Client Viewed" step between submitted and
                 accepted. Nothing records it, and an invented step in the
                 middle makes every percentage after it wrong. --}}
            <p class="bi-note">Percentages are of the invitations above. There is no "viewed" step:
               nothing on GigResource records a client opening a bid.</p>
        @else
            <p class="bi-none">You have not bid on anything in this period, so there is no funnel to draw.</p>
        @endif
    </div>
    <div class="bi-card bi-missing">
        {{-- Named without the phrase her panel used: a guard elsewhere keeps
             "AI Win-Rate" off this page entirely, so the predictor cannot
             come back by being described. --}}
        <h3><span class="t">{!! $svg('ban') !!} Win-Rate Prediction</span></h3>
        {{-- Her panel predicts a win rate for an event type, a place and a
             budget. There is no model behind it. A forecast with nothing
             behind it, on the screen somebody uses to decide what to charge,
             is worse than no forecast. --}}
        <p class="bi-none">
            Not shown. GigResource has no model that could predict a win rate, so any figure here would
            be a guess printed as a forecast. Your real record by event type, place and price range is
            in the panels above.
        </p>
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('pin') !!} Top Locations (By Win Rate)</span></h3>
        @if($performance['byPlace'])
            <table class="bi-table">
                <thead><tr><th>Location</th><th class="num">Bids</th><th class="num">Accepted</th><th class="num">Win Rate</th></tr></thead>
                <tbody>
                @foreach($performance['byPlace'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ $row['placed'] }}</td>
                        <td class="num">{{ $row['won'] }}</td>
                        <td class="num">{{ $rate($row['rate']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="bi-note">
                Total: {{ $rec['placed'] }} {{ \Illuminate\Support\Str::plural('bid', $rec['placed']) }}
                across all locations ({{ $rec['won'] }} accepted). Only places where something has
                actually been decided are listed.
            </p>
        @else
            <p class="bi-none">Nothing decided in any location yet.</p>
        @endif
    </div>
</div>

<div class="bi-grid">
    <div class="bi-card">
        <h3><span class="t">{!! $svg('cup') !!} Best-Performing Services &amp; Event Types</span>
            <a class="bi-more" href="{{ route('professional.bidding-board.my-bids') }}">View All &rarr;</a></h3>
        @php $best = array_merge($performance['byService'], $performance['byEvent']); @endphp
        @if($best)
            <table class="bi-table">
                {{-- Her fifth column: the same win rate drawn, so a reader can
                     compare rows without reading every number. --}}
                <thead><tr><th>Event Type / Service</th><th class="num">Bids</th><th class="num">Accepted</th><th class="num">Win Rate</th><th></th></tr></thead>
                <tbody>
                @foreach(array_slice($best, 0, 6) as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ $row['placed'] }}</td>
                        <td class="num">{{ $row['won'] }}</td>
                        <td class="num">{{ $rate($row['rate']) }}</td>
                        <td style="width:62px;"><span class="bi-bar" style="margin:0;"><i style="width: {{ $row['rate'] ?? 0 }}%;"></i></span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="bi-note">
                Total: {{ $rec['placed'] }} {{ \Illuminate\Support\Str::plural('bid', $rec['placed']) }}
                across all event types ({{ $rec['won'] }} accepted).
            </p>
        @else
            <p class="bi-none">Nothing has been decided yet, so no service or event type has a record.</p>
        @endif
    </div>
    <div class="bi-card bi-missing">
        <h3><span class="t">{!! $svg('ban') !!} Why You Win / Why You Lose</span></h3>
        {{-- Her panel lists reasons with percentages beside them. A declined
             bid on GigResource carries no reason, so every one of those
             percentages would be made up. --}}
        <p class="bi-none">
            Not shown. When a client declines a bid, nothing records why, so there is no reason to
            count. The nearest real answer is what your accepted bids carried, in Bid Quality below.
        </p>
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('clock') !!} Response Timing Analysis</span>
            <a class="bi-more" href="{{ route('professional.bidding-board.my-bids') }}">View Details &rarr;</a></h3>
        @if($performance['timing'])
            @php
                $bestBand = collect($performance['timing'])->sortByDesc('rate')->first();
            @endphp
            {{-- Her line says bids viewed within two days are 3x more likely
                 to be accepted. Nothing records a view, so this says the same
                 kind of thing from what IS recorded: which answering speed
                 actually won most often, for this professional. --}}
            <p class="bi-note" style="margin:0 0 9px;">
                @if($bestBand && $bestBand['rate'])
                    Your best answering speed so far is <b>{{ $bestBand['label'] }}</b>, at {{ $bestBand['rate'] }}%.
                @else
                    Nothing in any band has been decided yet, so no speed has a record.
                @endif
            </p>
            @php $top = max(array_map(fn ($r) => $r['rate'] ?? 0, $performance['timing'])) ?: 1; @endphp
            <div class="bi-cols">
                @foreach($performance['timing'] as $band)
                    <span class="bi-col">
                        {{-- A column is too narrow for "Nothing decided", so a
                             band with no verdict shows a dash and says it in
                             the line under the chart instead. --}}
                        <span class="top">{{ $band['rate'] === null ? "—" : $band['rate'] . '%' }}</span>
                        <span class="bar {{ $band['rate'] ? '' : 'flat' }}"
                              style="height: {{ max(3, round(($band['rate'] ?? 0) / $top * 86)) }}px;"></span>
                    </span>
                @endforeach
            </div>
            <div class="bi-xaxis">
                @foreach($performance['timing'] as $band)
                    <span>{{ $band['label'] }}<br>({{ $band['won'] }}/{{ $band['placed'] }})</span>
                @endforeach
            </div>
            {{-- The bands are time ranges, and a range on its own does not
                 say time since what. It is the gap between the request being
                 published and the bid going in, which is the only thing a
                 professional can change about their timing. --}}
            <p class="bi-note">Each range is how long after a request was published your bid went in.
               Numbers under each bar are accepted bids / bids sent in that range.
               A dash means nothing in that band has been decided yet.</p>
        @else
            <p class="bi-none">None of your bids can be timed yet: that needs a request with a published date and a bid against it.</p>
        @endif
    </div>
</div>

<div class="bi-grid">
    <div class="bi-card">
        <h3><span class="t">{!! $svg('cash') !!} Bid Pricing Effectiveness</span>
            <a class="bi-more" href="{{ route('professional.bidding-board.my-bids') }}">View Details &rarr;</a></h3>
        @if($performance['byPrice'])
            <table class="bi-table">
                <thead><tr><th>Price Range</th><th class="num">Bids</th><th class="num">Accepted</th><th class="num">Win Rate</th><th class="num">Avg. Bid Amount</th></tr></thead>
                <tbody>
                @foreach($performance['byPrice'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ $row['placed'] }}</td>
                        <td class="num">{{ $row['won'] }}</td>
                        <td class="num">{{ $rate($row['rate']) }}</td>
                        <td class="num">{{ $money($row['average']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{-- Her table ends on a Market Context row: the aggregated winning
                 bid for the same event type and place. Nothing records what
                 anybody else bid, so that row has no source. --}}
            <p class="bi-note">No market comparison: GigResource does not record what other professionals bid.</p>
        @else
            <p class="bi-none">No priced bids in this period.</p>
        @endif
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('chart') !!} Personal Performance Trends</span><span class="bi-chip">Last 12 months</span></h3>
        @php
            $months   = $performance['trend'];
            $hasTrend = collect($months)->contains(fn ($m) => $m['placed'] > 0);
            $topVol   = max(array_column($months, 'placed')) ?: 1;
            $topWon   = max(array_column($months, 'won')) ?: 1;
        @endphp
        @if($hasTrend)
            {{-- Her three views, each a real column on the same months: the
                 rate, how many went in, and how many came back yes. --}}
            <div class="bi-toggle" role="tablist">
                <button type="button" class="on" data-series="rate">Win Rate</button>
                <button type="button" data-series="placed">Bid Volume</button>
                <button type="button" data-series="won">Accepted Bids</button>
            </div>

            <div class="bi-cols" id="biTrend">
                @foreach($months as $m)
                    <span class="bi-col"
                          data-rate="{{ $m['rate'] === null ? '' : $m['rate'] }}"
                          data-placed="{{ $m['placed'] }}"
                          data-won="{{ $m['won'] }}"
                          data-hrate="{{ max(3, round(($m['rate'] ?? 0) / 100 * 80)) }}"
                          data-hplaced="{{ max(3, round($m['placed'] / $topVol * 80)) }}"
                          data-hwon="{{ max(3, round($m['won'] / $topWon * 80)) }}">
                        <span class="top">{{ $m['rate'] === null ? '' : $m['rate'] . '%' }}</span>
                        <span class="bar {{ $m['rate'] ? '' : 'flat' }}"
                              style="height: {{ max(3, round(($m['rate'] ?? 0) / 100 * 80)) }}px;"></span>
                    </span>
                @endforeach
            </div>
            <div class="bi-xaxis">
                @foreach($months as $m)<span>{{ $m['label'] }}</span>@endforeach
            </div>
            <p class="bi-note">A month you did not bid in is blank, not nought: sitting a month out is not losing it.</p>
        @else
            <p class="bi-none">No bids in the last twelve months, so there is no trend yet.</p>
        @endif
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('warn', 'style="color:#dc2626;"') !!} Lost Opportunity Analysis</span></h3>
        @php
            $lost = $performance['lost'];
            $top  = max($lost['declined']['amount'], $lost['expired']['amount'], 0.01);
        @endphp

        <div class="bi-toggle" role="tablist">
            <button type="button" class="on" data-lost="closed">Declined &amp; Expired (Closed)</button>
            <button type="button" data-lost="open">Unanswered (Open)</button>
        </div>

        <div data-lostpane="closed">
            <span class="bi-big bad">{{ $money($lost['amount']) }}</span>
            <span class="bi-sub">What you quoted on the {{ $lost['count'] }}
                {{ \Illuminate\Support\Str::plural('bid', $lost['count']) }} that closed without you.</span>

            <div class="bi-lost">
                <div class="row">
                    <span class="l">Declined</span>
                    <span class="track"><i style="width: {{ round($lost['declined']['amount'] / $top * 100) }}%; background:#dc2626;"></i></span>
                    <span class="v">{{ $money($lost['declined']['amount']) }}</span>
                    <span class="n">{{ $lost['declined']['count'] }} {{ \Illuminate\Support\Str::plural('bid', $lost['declined']['count']) }}</span>
                </div>
                <div class="row">
                    <span class="l">Expired</span>
                    <span class="track"><i style="width: {{ round($lost['expired']['amount'] / $top * 100) }}%; background:#d97706;"></i></span>
                    <span class="v">{{ $money($lost['expired']['amount']) }}</span>
                    <span class="n">{{ $lost['expired']['count'] }} {{ \Illuminate\Support\Str::plural('bid', $lost['expired']['count']) }}</span>
                </div>
            </div>
        </div>

        <div data-lostpane="open" hidden>
            <span class="bi-big" style="color:#d97706;">{{ $money($lost['open']['amount']) }}</span>
            <span class="bi-sub">What you quoted on the {{ $lost['open']['count'] }}
                {{ \Illuminate\Support\Str::plural('bid', $lost['open']['count']) }} nobody has answered.</span>
        </div>

        {{-- Their own amount, not the client's budget: the budget is what
             somebody else was willing to spend, and this is money this
             professional put a figure on and did not get. --}}
        <p class="bi-note">Measured on what you quoted, not on the client's budget.</p>
    </div>
</div>

<div class="bi-grid four">
    <div class="bi-card">
        <h3><span class="t">{!! $svg('sun', 'style="color:#d97706;"') !!} Seasonal Peak Alerts</span></h3>
        @if($performance['season']['enough'])
            @php $peak = max(array_column($performance['season']['months'], 'count')) ?: 1; @endphp
            <div class="bi-cols" style="height:72px;">
                @foreach($performance['season']['months'] as $m)
                    <span class="bi-col">
                        <span class="bar {{ $m['count'] ? '' : 'flat' }}"
                              style="height: {{ max(3, round($m['count'] / $peak * 62)) }}px;"></span>
                    </span>
                @endforeach
            </div>
            <div class="bi-xaxis">
                @foreach($performance['season']['months'] as $m)<span>{{ $m['label'][0] }}</span>@endforeach
            </div>
            <p class="bi-note">The months the work you bid on actually falls in.</p>
        @else
            {{-- One or two requests do not make a season. --}}
            <p class="bi-none">Not enough of your own history yet. This needs at least six dated requests behind it.</p>
        @endif
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('bulb') !!} Open Requests For You</span>
            <a class="bi-more" href="{{ route('professional.bidding-board.index') }}">View all</a></h3>
        {{-- Her panel scores each one and calls them Smart Recommendations.
             There is no model behind a score, so these are simply the open
             requests in your own services that you have not answered. --}}
        @if($open->count())
            <ul class="bi-list">
                @foreach($open as $event)
                    <li class="bi-act">
                        <span class="m">
                            <b>{{ $event->title }}</b>
                            <span>{{ $event->categories->pluck('name')->take(2)->implode(', ') ?: 'Services not listed' }}</span>
                        </span>
                        <a class="go" href="{{ route('professional.gigs.show', $event) }}">View</a>
                    </li>
                @endforeach
            </ul>
            <p class="bi-note">Matched to the services you sell. Not scored: nothing here predicts an outcome.</p>
        @else
            <p class="bi-none">Nothing open right now that matches your services and that you have not answered.</p>
        @endif
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('check') !!} Bid Quality Checklist</span></h3>
        @if($performance['quality']['sample'] > 0)
            <ul class="bi-check">
                @foreach($performance['quality']['rows'] as $row)
                    <li class="{{ $row['pct'] > 0 ? 'on' : '' }}">
                        {!! $svg('check') !!}
                        <span>{{ $row['label'] }}</span>
                        <b>{{ $row['pct'] }}%</b>
                    </li>
                @endforeach
            </ul>
            <p class="bi-note">What your {{ $performance['quality']['sample'] }} accepted
               {{ \Illuminate\Support\Str::plural('bid', $performance['quality']['sample']) }} actually carried.
               A tally of your own, not advice.</p>
        @else
            <p class="bi-none">Nothing accepted yet, so there is nothing to compare your winning bids against.</p>
        @endif
    </div>
    <div class="bi-card">
        <h3><span class="t">{!! $svg('bell', 'style="color:#dc2626;"') !!} Action Needed</span>
            <a class="bi-more" href="{{ route('professional.priority.index') }}">View all</a></h3>
        @if($waiting->count())
            <ul class="bi-list">
                @foreach($waiting as $item)
                    {{-- The job first, then what is wanted of it. In a card
                         this narrow "A client is waiting on your answer" fills
                         the line and the event name, which is the thing being
                         identified, gets cut off instead. --}}
                    <li class="bi-act">
                        <span class="m">
                            <b title="{{ $item['subject'] ?: $item['title'] }}">{{ $item['subject'] ?: $item['title'] }}</b>
                            <span>{{ $item['subject'] ? $item['title'] : $item['kind'] }}</span>
                        </span>
                        @if($item['due'])
                            <span class="when">{{ $item['due']->humanAgo() }}</span>
                        @endif
                        <a class="go" href="{{ $item['url'] }}">{{ $item['cta'] }}</a>
                    </li>
                @endforeach
            </ul>
            <a class="bi-foot" href="{{ route('professional.services.index', ['tab' => 'templates']) }}">
                Use quick-response templates &rarr;
            </a>
            <p class="bi-note">The same list Priority Actions reads, so the two cannot disagree.</p>
        @else
            <p class="bi-none">Nothing is waiting on you right now.</p>
        @endif
    </div>
</div>
</div>
<script>
(function () {
    /* Three views of the same twelve months, each a real column on the data
       already in the page: the rate, how many went in, how many came back. */
    var chart = document.getElementById('biTrend');

    document.querySelectorAll('.bi-toggle [data-series]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.series;

            btn.parentElement.querySelectorAll('button').forEach(function (b) {
                b.classList.toggle('on', b === btn);
            });

            if (!chart) { return; }

            chart.querySelectorAll('.bi-col').forEach(function (col) {
                var value = col.dataset[key];
                var top   = col.querySelector('.top');
                var bar   = col.querySelector('.bar');

                top.textContent = value === '' ? '' : (key === 'rate' ? value + '%' : value);
                bar.style.height = col.dataset['h' + key] + 'px';
                bar.classList.toggle('flat', !Number(value));
            });
        });
    });

    /* Closed against open, the two halves of work that did not become a job. */
    document.querySelectorAll('.bi-toggle [data-lost]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.parentElement.querySelectorAll('button').forEach(function (b) {
                b.classList.toggle('on', b === btn);
            });

            document.querySelectorAll('[data-lostpane]').forEach(function (pane) {
                pane.hidden = pane.dataset.lostpane !== btn.dataset.lost;
            });
        });
    });
})();
</script>
@endsection
