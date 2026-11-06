@extends('layouts.professional')

@section('title', 'Leads CRM')
@section('page-title', 'Leads CRM')

{{-- Khadijah's 28 October Leads CRM.

     There is no leads table and there does not need to be. Every stage is
     read off requests, bids and bookings that already exist, so each row can
     be checked against the bidding board, the bid list or the gig. The only
     thing stored is what belongs to the professional alone: their private
     note, when they last spoke to a lead, and when they mean to chase it.

     Her screen also carries a priority score out of 100. It is not here: the
     stage and the follow-up date are facts, a score would be arithmetic
     nobody agreed on, and this is the page somebody uses to decide who to
     chase. --}}

@php
    $money  = fn ($v) => '$' . number_format((float) $v, 0);
    $stageIcons = [
        'new'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>',
        'proposal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        'negotiation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'booked'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'lost'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.9" y1="4.9" x2="19.1" y2="19.1"/></svg>',
    ];
    $stages = \App\Support\LeadPipeline::STAGES;
    $link   = fn (array $over = []) => route('professional.leads.index', array_merge(array_filter([
        'q'      => $filters['q'] ?: null,
        'stage'  => $filters['stage'] ?: null,
        'source' => $filters['source'] ?: null,
        'type'   => $filters['type'] ?: null,
        'needs'  => $filters['needs'] ?: null,
    ]), $over));
@endphp

@push('styles')
<style>
    .lc-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .lc-head h1 { font-size:24px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .lc-head p { font-size:13px; color:var(--text-muted); margin:0; }
    .lc-btn { font-size:13px; font-weight:700; padding:10px 17px; border-radius:10px; text-decoration:none;
        background:var(--accent-blue, #2563eb); color:#fff; border:0; cursor:pointer; font-family:inherit; }
    .lc-btn.ghost { background:var(--bg-card); color:var(--text-secondary, var(--text-muted));
        border:1px solid var(--border-color); }

    /* ── Pipeline head and the five stages ── */
    .lc-pipehead { display:flex; gap:12px; align-items:center; margin-bottom:15px; }
    .lc-pipehead .ic { width:38px; height:38px; border-radius:11px; flex:none;
        background:var(--accent-blue, #2563eb); color:#fff; display:flex; align-items:center; justify-content:center; }
    .lc-pipehead .ic svg { width:19px; height:19px; }
    .lc-pipehead b { display:block; font-size:15px; font-weight:800; color:var(--text-primary); }
    .lc-pipehead span { font-size:12px; color:var(--text-muted); }

    .lc-stages { display:flex; gap:9px; align-items:stretch; }
    .lc-arrow { display:flex; align-items:center; color:var(--text-muted); flex:none; }
    .lc-arrow svg { width:15px; height:15px; }
    .lc-stage { flex:1 1 0; min-width:0; display:flex; gap:10px; align-items:flex-start; text-decoration:none;
        border:1px solid color-mix(in srgb, var(--c) 35%, transparent); border-radius:12px; padding:13px 14px;
        background:color-mix(in srgb, var(--c) 9%, transparent); }
    .lc-stage.on { box-shadow:0 0 0 2px var(--c) inset; }
    .lc-stage > .ic { width:32px; height:32px; border-radius:9px; flex:none; display:flex; align-items:center;
        justify-content:center; background:color-mix(in srgb, var(--c) 20%, transparent); color:var(--c); }
    .lc-stage > .ic svg { width:16px; height:16px; }
    .lc-stage .meta { min-width:0; }
    .lc-stage .l { display:block; font-size:11.5px; font-weight:800; color:var(--c); }
    .lc-stage .n { display:block; font-size:23px; font-weight:800; color:var(--text-primary); line-height:1.1; margin:2px 0; }
    .lc-stage .v { font-size:12px; font-weight:700; color:var(--text-primary); }
    .lc-stage .w { font-size:11px; color:var(--text-muted); margin-left:4px; }

    /* ── Table details ── */
    .lc-who { display:flex; gap:10px; align-items:flex-start; }
    .lc-av { width:30px; height:30px; border-radius:50%; flex:none; color:#fff; font-size:11px; font-weight:800;
        display:flex; align-items:center; justify-content:center; }
    .lc-chiplet { display:inline-flex; gap:5px; align-items:center; font-size:11.5px; font-weight:600;
        padding:3px 9px; border-radius:20px; border:1px solid var(--border-color);
        color:var(--text-secondary, var(--text-muted)); white-space:nowrap; }
    .lc-chiplet.plain { border:0; padding:0; }
    .lc-chiplet svg { width:12px; height:12px; flex:none; }
    .lc-sub { display:block; font-size:10.5px; color:var(--text-muted); }
    .lc-acts { display:inline-flex; gap:4px; align-items:center; }
    .lc-acts a, .lc-acts button { display:inline-flex; align-items:center; justify-content:center;
        width:27px; height:27px; border-radius:7px; border:1px solid var(--border-color);
        background:var(--bg-card); color:var(--text-muted); cursor:pointer; padding:0; }
    .lc-acts a:hover, .lc-acts button:hover { border-color:var(--accent-blue, #2563eb); color:var(--accent-blue, #2563eb); }
    .lc-acts svg { width:13px; height:13px; }
    .lc-clear { font-size:12px; font-weight:700; color:var(--accent-blue, #2563eb); text-decoration:none; white-space:nowrap; }
    .lc-chip svg { width:13px; height:13px; vertical-align:-2px; margin-right:3px; }
    .lc-legend span { display:flex; flex-direction:column; }
    .lc-legend em { font-style:normal; font-size:10.5px; color:var(--text-muted); }

    .lc-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:17px 18px; margin-bottom:17px; }

    /* ── The five stages ── */
    .lc-stages { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:11px; }
    .lc-stage { display:block; text-decoration:none; border:1px solid var(--border-color);
        border-top:3px solid var(--border-color); border-radius:11px; padding:13px 14px; background:var(--bg-page, var(--bg-card)); }
    .lc-stage.on { box-shadow:0 0 0 1px currentColor inset; }
    .lc-stage .l { display:block; font-size:11.5px; font-weight:800; }
    .lc-stage .n { display:block; font-size:24px; font-weight:800; color:var(--text-primary); line-height:1.1; margin:4px 0 2px; }
    .lc-stage .v { display:block; font-size:12px; color:var(--text-primary); font-weight:700; }
    .lc-stage .w { display:block; font-size:10.5px; color:var(--text-muted); margin-top:2px; }

    /* ── Filters ── */
    .lc-filters { display:grid; grid-template-columns:minmax(0,1.6fr) 1fr 1fr auto auto auto; gap:10px; align-items:center; }
    .lc-in, .lc-sel { width:100%; font:inherit; font-size:13px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }
    .lc-chip { font-size:12px; font-weight:700; padding:9px 13px; border-radius:9px; text-decoration:none;
        border:1px solid var(--border-color); color:var(--text-secondary, var(--text-muted)); white-space:nowrap; }
    .lc-chip.on { border-color:#ef4444; color:#b91c1c; background:rgba(239,68,68,0.1); }

    /* ── The table ── */
    .lc-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .lc-table th { text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.4px;
        color:var(--text-muted); padding:0 9px 8px; border-bottom:1px solid var(--border-color); }
    .lc-table td { padding:11px 9px; border-bottom:1px solid var(--border-color); vertical-align:top; color:var(--text-primary); }
    .lc-table tr.detail td { border-bottom:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); }
    .lc-who b { display:block; font-size:13px; font-weight:700; }
    .lc-who span { display:block; font-size:11px; color:var(--text-muted); }
    .lc-tag { display:inline-block; font-size:10.5px; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
    .lc-muted { color:var(--text-muted); }
    .lc-prio { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px;
        border:1px solid currentColor; border-radius:7px; white-space:nowrap; }
    .lc-over { color:#b91c1c; font-weight:700; }
    .lc-exp { font:inherit; font-size:11.5px; font-weight:700; color:var(--accent-blue, #2563eb);
        background:none; border:0; cursor:pointer; padding:0; }

    .lc-detail { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px,1fr)); gap:18px; padding:4px 0 8px; }
    .lc-detail h4 { font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.4px;
        color:var(--text-muted); margin:0 0 7px; }
    .lc-detail p { font-size:12.5px; color:var(--text-primary); margin:0 0 5px; line-height:1.55; }
    .lc-noteform label { display:block; font-size:10.5px; font-weight:800; text-transform:uppercase;
        letter-spacing:.4px; color:var(--text-muted); margin:9px 0 4px; }
    .lc-noteform textarea, .lc-noteform input[type=date] { width:100%; font:inherit; font-size:12.5px;
        padding:8px 10px; border-radius:9px; border:1px solid var(--border-color);
        background:var(--bg-card); color:var(--text-primary); resize:vertical; }
    .lc-noteform .tick { display:flex; gap:8px; align-items:center; font-size:12px; margin-top:9px; color:var(--text-primary); }

    .lc-foot { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; margin-top:14px; }
    .lc-pages { display:flex; gap:6px; }
    .lc-pages a, .lc-pages span { font-size:12px; font-weight:700; padding:6px 11px; border-radius:8px;
        text-decoration:none; border:1px solid var(--border-color); color:var(--text-secondary, var(--text-muted)); }
    .lc-pages .on { background:var(--text-primary); color:var(--bg-card); border-color:var(--text-primary); }

    .lc-bottom { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:17px; align-items:start; }
    .lc-bar { display:flex; height:11px; border-radius:6px; overflow:hidden; margin:11px 0 10px; background:var(--border-color); }
    .lc-bar i { display:block; height:100%; }
    .lc-legend { display:grid; gap:6px; }
    .lc-legend div { display:flex; align-items:center; gap:8px; font-size:11.5px; color:var(--text-secondary, var(--text-muted)); }
    .lc-legend i { width:9px; height:9px; border-radius:3px; flex:none; }
    .lc-legend b { margin-left:auto; color:var(--text-primary); }
    .lc-big { font-size:30px; font-weight:800; color:var(--text-primary); line-height:1.1; }
    .lc-note { font-size:11.5px; color:var(--text-muted); margin:7px 0 0; line-height:1.55; }
    .lc-quick { display:grid; gap:9px; }
    .lc-quick a { display:block; font-size:12.5px; font-weight:700; padding:10px 13px; border-radius:9px;
        text-decoration:none; border:1px solid var(--border-color); color:var(--text-primary); }

    @media (max-width: 1250px) { .lc-stages { flex-wrap:wrap; } .lc-arrow { display:none; }
        .lc-stage { flex:1 1 170px; }
        .lc-filters { grid-template-columns:1fr; } .lc-bottom { grid-template-columns:1fr; } }
    @media (max-width: 860px) { .lc-table thead { display:none; }
        .lc-table td { display:block; border:0; padding:3px 0; }
        .lc-table tr { display:block; border-bottom:1px solid var(--border-color); padding:11px 0; } }
</style>
@endpush

@section('content')
<div class="lc-head">
    <div>
        <h1>Leads CRM</h1>
        <p>Every enquiry you could take or already have, and where each one has got to.</p>
    </div>
    <a class="lc-btn" href="{{ route('professional.bidding-board.index') }}">Browse open gigs</a>
</div>

{{-- The five stages. Clicking one filters the table, and the counts and the
     table are the same set of leads, so they cannot disagree. --}}
<div class="lc-card">
    <div class="lc-pipehead">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg></span>
        <div>
            <b>Lead pipeline</b>
            <span>Click a stage to filter the leads below. The counts and the table come from the same records.</span>
        </div>
    </div>

    <div class="lc-stages">
        @foreach($summary as $key => $s)
            @if(! $loop->first)
                <span class="lc-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            @endif
            <a class="lc-stage {{ $filters['stage'] === $key ? 'on' : '' }}"
               style="--c: {{ $s['colour'] }};"
               href="{{ $filters['stage'] === $key ? $link(['stage' => null]) : $link(['stage' => $key, 'page' => null]) }}">
                <span class="ic">{!! $stageIcons[$key] !!}</span>
                <span class="meta">
                    <span class="l">{{ $s['label'] }}</span>
                    <span class="n">{{ $s['count'] }}</span>
                    <span class="v">{{ $money($s['weighted']) }}</span>
                    <span class="w">({{ $s['weight'] }}%)</span>
                </span>
            </a>
        @endforeach
    </div>
</div>

<div class="lc-card">
    <form method="GET" action="{{ route('professional.leads.index') }}" class="lc-filters">
        <input type="search" class="lc-in" name="q" value="{{ $filters['q'] }}"
               placeholder="Search by client, event or place">
        <select class="lc-sel" name="stage">
            <option value="">All stages</option>
            @foreach($stages as $key => [$label, $colour, $weight])
                <option value="{{ $key }}" @selected($filters['stage'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="lc-sel" name="type">
            <option value="">All event types</option>
            @foreach($types as $type)
                <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <select class="lc-sel" name="source">
            <option value="">All sources</option>
            @foreach($sources as $source)
                <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ $source }}</option>
            @endforeach
        </select>
        <button type="submit" class="lc-btn">Search</button>
        <a class="lc-chip {{ $filters['needs'] ? 'on' : '' }}"
           href="{{ $filters['needs'] ? $link(['needs' => null]) : $link(['needs' => 1, 'page' => null]) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Overdue follow-ups ({{ $overdue }})
        </a>
        {{-- Only where there is something to clear. A Clear button on an
             unfiltered page is a control that does nothing. --}}
        @if(array_filter($filters))
            <a class="lc-clear" href="{{ route('professional.leads.index') }}">Clear</a>
        @endif
    </form>
</div>

<div class="lc-card">
    @if($leads->isEmpty())
        <p class="lc-note" style="margin:0;">
            @if($total === 0)
                No leads yet. Open requests you could take appear here as soon as clients post them.
            @else
                Nothing matches that. <a href="{{ route('professional.leads.index') }}">Clear the filters</a>.
            @endif
        </p>
    @else
        <table class="lc-table">
            <thead>
                <tr>
                    <th>Client / event</th>
                    <th>Source</th>
                    <th>Where</th>
                    <th>Budget</th>
                    <th>Stage</th>
                    <th>Priority</th>
                    <th>Last contacted</th>
                    <th>Next follow-up</th>
                    <th>Next action</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($leads as $lead)
                @php
                    // "Budget not stated" rather than a dash or a zero: no
                    // figure on record is a fact about the request, not an
                    // amount.
                    $budget = $lead['low'] === null
                        ? 'Budget not stated'
                        : ($lead['is_range'] ? $money($lead['low']) . ' to ' . $money($lead['high']) : $money($lead['low']));
                @endphp
                <tr>
                    <td>
                        <div class="lc-who">
                            <span class="lc-av" style="background: {{ $lead['stage_color'] }};">{{ $lead['initials'] }}</span>
                            <span>
                                <b>{{ $lead['title'] }}</b>
                                <span>{{ $lead['client'] ?: 'Client not named' }}@if($lead['when']) &middot; {{ $lead['when']->format('j M Y') }}@endif</span>
                                @if($lead['ageDays'] !== null)
                                    <span>{{ $lead['ageDays'] }} {{ \Illuminate\Support\Str::plural('day', $lead['ageDays']) }} old</span>
                                @endif
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="lc-chiplet">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            {{ $lead['source'] }}
                        </span>
                    </td>
                    <td>
                        <span class="lc-chiplet plain">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $lead['where'] ?: 'Not stated' }}
                        </span>
                    </td>
                    <td>
                        {{ $budget }}
                        @if($lead['fixed'])
                            {{-- One figure is a fixed price and a band is a
                                 band; naming it stops one reading as the other. --}}
                            <span class="lc-sub">Fixed</span>
                        @elseif($lead['is_range'])
                            <span class="lc-sub">Client's own range</span>
                        @endif
                    </td>
                    <td>
                        {{-- The stage and the priority are told apart by shape
                             as well as colour, and the priority names itself,
                             so it cannot be read as another stage. --}}
                        <span class="lc-tag pl-stage-chip" style="background: {{ $lead['stage_color'] }}1f; color: {{ $lead['stage_color'] }};">
                            {{ $lead['stage_label'] }}
                        </span>
                    </td>
                    <td>
                        @php $pc = ['High' => '#ef4444', 'Medium' => '#f59e0b'][$lead['priority']] ?? '#64748b'; @endphp
                        <span class="lc-prio" style="color: {{ $pc }}; border-color: {{ $pc }};">Priority: {{ $lead['priority'] }}</span>
                    </td>
                    <td class="lc-muted">{{ $lead['last_contacted'] ? $lead['last_contacted']->humanAgo() : 'Never' }}</td>
                    <td class="{{ $lead['overdue'] ? 'lc-over' : 'lc-muted' }}">
                        @if($lead['next_follow_up'])
                            {{ $lead['next_follow_up']->format('j M') }}{{ $lead['overdue'] ? ', overdue' : '' }}
                        @else
                            Not set
                        @endif
                    </td>
                    <td class="lc-muted">{{ $lead['next_action'] ?: '—' }}</td>
                    <td>
                        <span class="lc-acts">
                            <a title="Messages" href="{{ route('professional.chat.index') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </a>
                            <a title="Open the request" href="{{ route('professional.bid.step', ['event' => $lead['event_id']]) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </a>
                            <a title="Calendar" href="{{ route('professional.calendar.index') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </a>
                            <button type="button" class="lc-exp" data-lead="{{ $lead['event_id'] }}" title="Open details">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                        </span>
                    </td>
                </tr>
                <tr class="detail" id="lead-{{ $lead['event_id'] }}" hidden>
                    <td colspan="10">
                        <div class="lc-detail">
                            <div>
                                <h4>The request</h4>
                                @if($lead['type'])<p>{{ $lead['type'] }}</p>@endif
                                @if($lead['when'])<p class="lc-muted">{{ $lead['when']->format('l j F Y') }}</p>@endif
                                @if($lead['services'])<p>{{ implode(', ', $lead['services']) }}</p>@endif
                                <p class="lc-muted">{{ $lead['where'] ?: 'Location not stated' }}</p>
                                @if($lead['guests'])<p class="lc-muted">About {{ number_format($lead['guests']) }} guests</p>@endif
                                @if($lead['venue'])<p class="lc-muted">{{ $lead['venue'] }}</p>@endif
                                @if($lead['description'])<p class="lc-muted">{{ $lead['description'] }}</p>@endif
                            </div>
                            <div>
                                <h4>Client information</h4>
                                <p><b>{{ $lead['client'] ?: 'Not named' }}</b></p>
                                <p class="lc-muted">{{ $lead['repeat'] ? 'Has booked you before' : 'New to you' }}</p>
                                <p class="lc-muted">Came in through {{ $lead['source'] }}</p>
                                @if($lead['ageDays'] !== null)
                                    <p class="lc-muted">{{ $lead['ageDays'] }} {{ \Illuminate\Support\Str::plural('day', $lead['ageDays']) }} on your list</p>
                                @endif
                            </div>
                            <div>
                                <h4>Where it stands</h4>
                                <p>{{ $lead['stage_label'] }}, counted at {{ $stages[$lead['stage']][2] }}% of its budget.</p>
                                <p class="lc-muted">
                                    {{ $lead['last_contacted'] ? 'Last contact ' . $lead['last_contacted']->humanAgo() : 'You have not been in touch.' }}
                                </p>
                            </div>
                            <div>
                                {{-- Private to this professional. The client has no
                                     screen that reads it and must not get one. --}}
                                <h4>Your note, only you see this</h4>
                                <form method="POST" action="{{ route('professional.leads.note', $lead['event_id']) }}" class="lc-noteform">
                                    @csrf
                                    <textarea name="note" rows="3" maxlength="600"
                                              placeholder="What happened, what they want">{{ $lead['note'] }}</textarea>
                                    <label for="f-{{ $lead['event_id'] }}">Chase it on</label>
                                    <input type="date" id="f-{{ $lead['event_id'] }}" name="next_follow_up_on"
                                           value="{{ $lead['next_follow_up']?->format('Y-m-d') }}">
                                    <label class="tick">
                                        <input type="checkbox" name="contacted" value="1">
                                        <span>I have just been in touch</span>
                                    </label>
                                    <button type="submit" class="lc-btn" style="margin-top:10px;">Save</button>
                                </form>
                            </div>
                            <div>
                                <h4>What you can do</h4>
                                <div class="lc-quick">
                                    <a href="{{ route('professional.chat.index') }}">Open messages</a>
                                    <a href="{{ route('professional.bid.step', ['event' => $lead['event_id']]) }}">Open the request</a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="lc-foot">
            <span class="lc-note" style="margin:0;">
                Showing {{ $leads->count() }} of {{ $shownTotal }}{{ $shownTotal === $total ? '' : ' matching' }} leads
            </span>
            @if($pages > 1)
                <span class="lc-pages">
                    @for($p = 1; $p <= $pages; $p++)
                        @if($p === $page)
                            <span class="on">{{ $p }}</span>
                        @else
                            <a href="{{ $link(['page' => $p]) }}">{{ $p }}</a>
                        @endif
                    @endfor
                </span>
            @endif
        </div>
    @endif
</div>

<div class="lc-bottom">
    <div class="lc-card" style="margin:0;">
        <h4 style="font-size:13px;font-weight:800;color:var(--text-primary);margin:0;">Pipeline value</h4>
        <div class="lc-big">{{ $money($pipeline) }}</div>

        @php $pipeMax = max(0.01, collect($summary)->sum('weighted')); @endphp
        <div class="lc-bar">
            @foreach($summary as $s)
                @if($s['weighted'] > 0)
                    <i style="width: {{ round($s['weighted'] / $pipeMax * 100, 2) }}%; background: {{ $s['colour'] }};"></i>
                @endif
            @endforeach
        </div>

        <div class="lc-legend">
            @foreach($summary as $s)
                <div>
                    <i style="background: {{ $s['colour'] }};"></i>
                    {{ $s['label'] }}
                    <b>{{ $money($s['weighted']) }}</b>
                </div>
            @endforeach
        </div>

        <p class="lc-note">Based on where each lead has got to and what its budget is.</p>
    </div>

    {{-- The weights, on their own, with what each stage means.

         They are printed rather than applied quietly: a weighted pipeline
         figure nobody can see the workings of is a number somebody plans
         against without knowing what it assumed. --}}
    <div class="lc-card" style="margin:0;">
        <h4 style="font-size:13px;font-weight:800;color:var(--text-primary);margin:0 0 11px;">Stage weights</h4>
        @php
            $meaning = [
                'new'         => 'Early, least certain',
                'proposal'    => 'Your quote is with them',
                'negotiation' => 'You are talking',
                'booked'      => 'Agreed, most certain',
                'lost'        => 'Not counted in the value',
            ];
        @endphp
        <div class="lc-legend">
            @foreach($summary as $key => $s)
                <div>
                    <i style="background: {{ $s['colour'] }};"></i>
                    <span>
                        {{ $s['label'] }}
                        <em>{{ $meaning[$key] }}</em>
                    </span>
                    <b>{{ $s['weight'] }}%</b>
                </div>
            @endforeach
        </div>
    </div>

    <div class="lc-card" style="margin:0;">
        <h4 style="font-size:13px;font-weight:800;color:var(--text-primary);margin:0 0 6px;">Your conversion</h4>
        @if($conversion === null)
            <p class="lc-note" style="margin:0;">No leads yet, so there is no rate to work out.</p>
        @else
            <div class="lc-big">{{ $conversion }}%</div>
            <p class="lc-note">
                {{ $conversion }} of every 100 leads booked, from {{ $total }}
                {{ \Illuminate\Support\Str::plural('lead', $total) }} so far.
            </p>
        @endif
    </div>

    <div class="lc-card" style="margin:0;">
        <h4 style="font-size:13px;font-weight:800;color:var(--text-primary);margin:0 0 10px;">Quick actions</h4>
        <div class="lc-quick">
            <a href="{{ $link(['needs' => 1, 'page' => null]) }}">Overdue follow-ups ({{ $overdue }})</a>
            <a href="{{ $link(['stage' => 'lost', 'page' => null]) }}">What you lost ({{ $lost }})</a>
            <a href="{{ route('professional.leads.export', request()->query()) }}">Download these leads</a>
        </div>
    </div>
</div>

<script>
(function () {
    /* One row opens at a time, so the table does not become a wall. */
    Array.prototype.slice.call(document.querySelectorAll('[data-lead]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = document.getElementById('lead-' + btn.dataset.lead);
            if (!row) { return; }

            var opening = row.hidden;

            document.querySelectorAll('tr.detail').forEach(function (r) { r.hidden = true; });
            document.querySelectorAll('[data-lead]').forEach(function (b) { b.textContent = 'Open'; });

            row.hidden = !opening;
            btn.textContent = opening ? 'Close' : 'Open';
        });
    });
})();
</script>
@endsection
