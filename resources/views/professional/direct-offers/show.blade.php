@extends('layouts.professional')

@section('title', 'Direct Request Details')
@section('page-title', 'Direct Request Details')

{{-- Khadijah's 28 October screen.

     Her response panel says "Send a Quick Bid". There is no bidding on a
     Direct Request on this platform and there is not meant to be: it goes to
     one professional and is accepted or declined, which is what keeps it
     different from a board post. The panel keeps her name and her shape; the
     buttons do what the platform does.

     Five things on her screen have nothing behind them here and are not
     invented: parking, indoor/outdoor, a venue TYPE rather than a venue name,
     a per-service budget breakdown, and per-service quantities. Each says so
     rather than carrying a figure nobody gave. --}}

@push('styles')
<style>
    .dr-top { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
    .dr-back { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700;
        padding:8px 14px; border-radius:9px; text-decoration:none; background:var(--bg-card);
        color:var(--text-primary); border:1px solid var(--border-color); white-space:nowrap; }
    .dr-back svg { width:15px; height:15px; flex:none; }
    .dr-top-acts { display:flex; gap:9px; flex-wrap:wrap; }

    .dr-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; font-size:13px; font-weight:700;
        padding:9px 15px; border-radius:9px; text-decoration:none; background:var(--accent-blue, #2563eb);
        color:#fff; border:1px solid var(--accent-blue, #2563eb); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .dr-btn svg { width:15px; height:15px; }
    .dr-ghost { background:var(--bg-card); color:var(--text-primary); border-color:var(--border-color); }
    .dr-ghost.blue { color:var(--accent-blue, #2563eb); }
    .dr-ghost.bad { color:var(--bad-text, #dc2626); }

    .dr-title { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .dr-title h1 { font-size:26px; font-weight:800; color:var(--text-primary); margin:0 0 4px; line-height:1.2; }
    .dr-title p { font-size:13.5px; color:var(--text-muted); margin:0; }
    .dr-pill { display:inline-block; font-size:11.5px; font-weight:700; padding:4px 13px; border-radius:20px; white-space:nowrap; }
    .dr-pill.warn { background:rgba(217,119,6,.14); color:var(--warn-text, #b45309); }
    .dr-pill.ok   { background:rgba(16,163,74,.14); color:var(--ok-text, #047857); }
    .dr-pill.bad  { background:rgba(220,38,38,.13); color:var(--bad-text, #dc2626); }

    .dr-meta { display:flex; align-items:center; justify-content:space-between; gap:18px;
        flex-wrap:wrap; margin:12px 0 18px; }
    .dr-facts { display:flex; gap:22px; flex-wrap:wrap; }
    .dr-facts span { display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--text-muted); }
    .dr-facts svg { width:14px; height:14px; flex:none; }
    .dr-due { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700; color:var(--bad-text, #dc2626); }
    .dr-due svg { width:15px; height:15px; }

    .dr-grid { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:18px; align-items:start; }
    .dr-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px;
        padding:16px 18px; margin-bottom:16px; }
    .dr-card h3 { display:flex; align-items:center; gap:9px; font-size:15px; font-weight:800;
        color:var(--text-primary); margin:0 0 12px; }
    .dr-card h3 .ic { width:30px; height:30px; border-radius:8px; flex:none; display:flex;
        align-items:center; justify-content:center; color:var(--accent-blue, #2563eb);
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 12%, transparent); }
    .dr-card h3 .ic svg { width:16px; height:16px; }
    .dr-card h3 small { font-weight:600; font-size:12px; color:var(--text-muted); }

    /* The client strip: who they are on the left, what they are asking on the right. */
    .dr-who { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) minmax(0,1fr); gap:0; align-items:center; }
    .dr-who > div { padding:0 18px; border-left:1px solid var(--border-color); }
    .dr-who > div:first-child { border-left:0; padding-left:0; }
    .dr-ava { width:54px; height:54px; border-radius:50%; flex:none; display:flex; align-items:center;
        justify-content:center; background:var(--accent-blue, #2563eb); color:#fff; font-size:18px; font-weight:800; }
    .dr-whoin { display:flex; gap:13px; align-items:center; }
    .dr-whoin b { display:flex; align-items:center; gap:8px; font-size:16px; font-weight:800; color:var(--text-primary); }
    .dr-whoin .line { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:var(--text-muted); margin-top:4px; }
    .dr-star { color:#d97706; font-weight:800; }
    .dr-k { display:block; font-size:11.5px; color:var(--text-muted); }
    .dr-v { display:block; font-size:17px; font-weight:800; color:var(--text-primary); line-height:1.3; margin:2px 0 2px; }
    .dr-s { display:block; font-size:11.5px; color:var(--text-muted); }
    .dr-s.blue { color:var(--accent-blue, #2563eb); font-weight:600; }

    .dr-tabs { display:flex; gap:4px; border-bottom:1px solid var(--border-color); margin-bottom:16px; overflow-x:auto; }
    .dr-tab { padding:11px 16px; font-size:13px; font-weight:700; white-space:nowrap; cursor:pointer;
        color:var(--text-muted); background:none; border:0; border-bottom:2.5px solid transparent; font-family:inherit; }
    .dr-tab.on { color:var(--accent-blue, #2563eb); border-bottom-color:var(--accent-blue, #2563eb); }
    .dr-pane[hidden] { display:none; }

    .dr-sum { font-size:13.5px; color:var(--text-secondary, var(--text-muted)); line-height:1.7; margin:0; white-space:pre-line; }
    .dr-chips { display:flex; gap:7px; flex-wrap:wrap; margin-top:14px; }
    .dr-chip { font-size:11.5px; font-weight:600; padding:5px 13px; border-radius:20px;
        background:var(--bg-page, rgba(128,128,128,.08)); color:var(--text-secondary, var(--text-muted));
        border:1px solid var(--border-color); }
    .dr-chip.first { background:rgba(236,72,153,.12); color:#be185d; border-color:transparent; }

    /* Her details block: a grid with hairlines between every cell. */
    .dr-dl { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); border:1px solid var(--border-color); border-radius:11px; overflow:hidden; }
    .dr-dl > div { padding:13px 15px; border-left:1px solid var(--border-color); border-top:1px solid var(--border-color); }
    .dr-dl > div:nth-child(4n+1) { border-left:0; }
    .dr-dl > div:nth-child(-n+4) { border-top:0; }
    .dr-dl b { display:block; font-size:11.5px; font-weight:700; color:var(--text-muted); margin-bottom:4px; }
    .dr-dl span { display:block; font-size:13px; color:var(--text-primary); line-height:1.45; }
    .dr-dl span.none { color:var(--text-muted); }
    .dr-dl a { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700;
        color:var(--accent-blue, #2563eb); text-decoration:none; margin-top:5px; }
    .dr-dl a svg { width:12px; height:12px; }

    .dr-table { width:100%; border-collapse:collapse; border:1px solid var(--border-color); border-radius:11px; overflow:hidden; }
    .dr-table th { text-align:left; font-size:11.5px; font-weight:700; color:var(--text-muted);
        padding:10px 14px; background:var(--bg-page, rgba(128,128,128,.05)); border-bottom:1px solid var(--border-color); }
    .dr-table td { padding:12px 14px; font-size:13px; border-bottom:1px solid var(--border-color);
        border-left:1px solid var(--border-color); color:var(--text-primary); vertical-align:middle; }
    .dr-table td:first-child { border-left:0; }
    .dr-table tr:last-child td { border-bottom:0; }
    .dr-svc { display:flex; align-items:center; gap:10px; }
    .dr-svc .tile { width:28px; height:28px; border-radius:7px; flex:none; display:flex; align-items:center;
        justify-content:center; color:#fff; font-size:12px; font-weight:800; }
    .dr-none { font-size:13px; color:var(--text-muted); }

    .dr-files { display:flex; flex-direction:column; gap:9px; }
    .dr-file { display:flex; align-items:center; gap:11px; padding:11px 13px; border:1px solid var(--border-color);
        border-radius:10px; text-decoration:none; }
    .dr-file svg { width:17px; height:17px; flex:none; color:var(--accent-blue, #2563eb); }
    .dr-file b { display:block; font-size:13px; font-weight:700; color:var(--text-primary); }
    .dr-file span { display:block; font-size:11.5px; color:var(--text-muted); }

    /* Right rail */
    .dr-rail { display:flex; flex-direction:column; gap:16px; position:sticky; top:20px; }
    .dr-rail .dr-card { margin:0; }
    .dr-rail h4 { display:flex; align-items:center; justify-content:space-between; gap:9px;
        font-size:14px; font-weight:800; color:var(--text-primary); margin:0 0 10px; }
    .dr-rail h4 .t { display:flex; align-items:center; gap:8px; }
    .dr-rail h4 svg { width:16px; height:16px; flex:none; color:var(--accent-blue, #2563eb); }
    .dr-rail p { font-size:12.5px; color:var(--text-muted); line-height:1.6; margin:0 0 12px; }
    .dr-rail .stack { display:flex; flex-direction:column; gap:9px; }
    .dr-deadline { margin-top:13px; border-radius:11px; padding:12px 13px;
        background:rgba(220,38,38,.07); border:1px solid rgba(220,38,38,.2); }
    .dr-deadline b { display:flex; align-items:center; gap:7px; font-size:12.5px; font-weight:800;
        color:var(--bad-text, #dc2626); }
    .dr-deadline b svg { width:14px; height:14px; }
    .dr-deadline .big { display:block; font-size:16px; font-weight:800; color:var(--bad-text, #dc2626); margin:5px 0 2px; }
    .dr-deadline span { display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--text-muted); }
    .dr-deadline span svg { width:12px; height:12px; }
    .dr-note { font-size:12.5px; color:var(--text-secondary, var(--text-muted)); line-height:1.65; margin:0; }
    .dr-row { display:flex; justify-content:space-between; gap:12px; font-size:12.5px;
        color:var(--text-secondary, var(--text-muted)); padding:7px 0; border-bottom:1px solid var(--border-color); }
    .dr-row:last-of-type { border-bottom:0; }
    .dr-row b { font-weight:700; color:var(--text-primary); white-space:nowrap; }
    .dr-total { display:flex; justify-content:space-between; gap:12px; align-items:center;
        margin-top:9px; padding:10px 12px; border-radius:9px;
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 9%, transparent); }
    .dr-total span { font-size:12.5px; font-weight:800; color:var(--text-primary); }
    .dr-total b { font-size:14.5px; font-weight:800; color:var(--text-primary); white-space:nowrap; }

    .dr-empty { background:var(--bg-card); border:1px dashed var(--border-color); border-radius:16px;
        padding:52px 24px; text-align:center; }
    .dr-empty h2 { font-size:18px; font-weight:800; color:var(--text-primary); margin:0 0 8px; }
    .dr-empty p { font-size:13.5px; color:var(--text-muted); margin:0 0 16px; }
    .dr-foot { font-size:11px; color:var(--text-muted); line-height:1.6; margin:4px 2px 0; }

    @media (max-width: 1150px) { .dr-grid { grid-template-columns:1fr; } .dr-rail { position:static; } }
    @media (max-width: 900px) {
        .dr-who { grid-template-columns:1fr; row-gap:14px; }
        .dr-who > div { border-left:0; padding-left:0; }
        .dr-dl { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .dr-dl > div:nth-child(4n+1) { border-left:1px solid var(--border-color); }
        .dr-dl > div:nth-child(odd) { border-left:0; }
        .dr-dl > div:nth-child(-n+2) { border-top:0; }
        .dr-dl > div:nth-child(3), .dr-dl > div:nth-child(4) { border-top:1px solid var(--border-color); }
    }
</style>
@endpush

@section('content')
@php
    $tz   = config('app.display_timezone');
    $on   = fn ($d) => $d?->timezone($tz)->format('M j, Y');
    $onAt = fn ($d) => $d?->timezone($tz)->format('M j, Y \a\t g:i A');
    $money = fn ($v) => '$' . number_format((float) $v, 0);

    $paths = [
        'back'  => '<path d="m15 18-6-6 6-6"/>',
        'share' => '<path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="m16 6-4-4-4 4"/><path d="M12 2v14"/>',
        'save'  => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
        'more'  => '<path d="m6 9 6 6 6-6"/>',
        'pin'   => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'cal'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'team'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'doc'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'list'  => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'gem'   => '<path d="m6 3 6 18 6-18z"/><path d="M2 9h20"/><path d="M6 3h12l4 6H2z"/>',
        'chat'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'user'  => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'tick'  => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'x'     => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
        'cash'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'tag'   => '<path d="M20.6 13.4 12 22l-9-9V3h10z"/><path d="M7 7h.01"/>',
        'info'  => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'bolt'  => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'map'   => '<path d="m1 6 7-3 8 3 7-3v15l-7 3-8-3-7 3z"/><path d="M8 3v15M16 6v15"/>',
    ];
    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';
@endphp

@if(! $offer)
    {{-- Nothing waiting. This page used to invent a request when there was
         none, complete with a client, a budget and a deadline, so a
         professional with an empty inbox was shown an offer that did not
         exist and could act on it. --}}
    <div class="dr-empty">
        <h2>No direct requests right now</h2>
        <p>A direct request appears here when a client sends one straight to you, rather than posting it to the board.</p>
        <a class="dr-btn" href="{{ route('professional.bidding-board.index') }}">Browse the Bidding Board</a>
    </div>
@else
@php
    $c = $offer['client'];
    $e = $offer['event'];
@endphp

<div class="dr-top">
    <a class="dr-back" href="{{ route('professional.proposals.index') }}">{!! $svg('back') !!} Back to Direct Requests</a>
    <div class="dr-top-acts">
        <a class="dr-btn dr-ghost" href="{{ route('professional.chat.index') }}">{!! $svg('share') !!} Share Request</a>
        <a class="dr-btn dr-ghost" href="{{ route('professional.gig-hub.index') }}">{!! $svg('save') !!} Save</a>
        <span class="dr-btn dr-ghost" style="cursor:default;">More {!! $svg('more') !!}</span>
    </div>
</div>

@if(session('status'))
    <div class="dr-card" style="border-color:#10b981;background:rgba(16,185,129,.07);font-size:13px;font-weight:700;">
        {{ session('status') }}
    </div>
@endif

<div class="dr-title">
    <div>
        <h1>{{ $offer['title'] }}</h1>
        <p>{{ $offer['subtitle'] }}</p>
    </div>
    <span class="dr-pill {{ $offer['status_tone'] }}">{{ $offer['status'] }}</span>
</div>

<div class="dr-meta">
    <div class="dr-facts">
        @if($offer['where'])<span>{!! $svg('pin') !!} {{ $offer['where'] }}</span>@endif
        @if($offer['when'])<span>{!! $svg('cal') !!} {{ $offer['when']->timezone($tz)->format('D, M j, Y') }}</span>@endif
        @if($offer['guests'])<span>{!! $svg('team') !!} {{ number_format($offer['guests']) }} guests</span>@endif
        <span>{!! $svg('clock') !!} Received: {{ $onAt($offer['received_at']) }}</span>
    </div>

    @if($offer['deadline'])
        <span class="dr-due">
            {!! $svg('clock') !!}
            Respond by {{ $on($offer['deadline']) }}
            ({{ $offer['days_left'] > 0 ? $offer['days_left'] . ' ' . \Illuminate\Support\Str::plural('day', $offer['days_left']) . ' left' : 'the date has passed' }})
        </span>
    @else
        {{-- The client set no deadline, so there is none to count down to.
             A date worked out from the event would put this professional
             under a clock nobody set. --}}
        <span class="dr-facts"><span>{!! $svg('clock') !!} No response deadline was set</span></span>
    @endif
</div>

<div class="dr-grid">
    <div>
        <div class="dr-card">
            <div class="dr-who">
                <div>
                    <div class="dr-whoin">
                        <span class="dr-ava">{{ $c['initials'] }}</span>
                        <span>
                            <b>
                                {{ $c['name'] }}
                                @if($c['verified'])
                                    <span class="dr-pill ok" style="font-size:10.5px;">Verified Client</span>
                                @endif
                            </b>
                            <span class="line">
                                @if($c['since'])<span>Client since {{ $c['since']->format('Y') }}</span>@endif
                                <span>{{ $c['past'] }} past {{ \Illuminate\Support\Str::plural('event', $c['past']) }}</span>
                                {{-- No rating until somebody has left one: a
                                     client with no reviews is not a 0.0. --}}
                                @if($c['rating'])
                                    <span><span class="dr-star">{{ $c['rating'] }} &starf;</span> ({{ $c['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $c['reviews']) }})</span>
                                @else
                                    <span>No reviews yet</span>
                                @endif
                            </span>
                        </span>
                    </div>
                </div>

                <div>
                    <span class="dr-k">Event Budget (total)</span>
                    @if($offer['budget_min'] || $offer['budget_max'])
                        <span class="dr-v">
                            {{ $offer['budget_min'] && $offer['budget_max'] && $offer['budget_min'] != $offer['budget_max']
                                ? $money($offer['budget_min']) . ' – ' . $money($offer['budget_max'])
                                : $money($offer['budget_max'] ?: $offer['budget_min']) }}
                        </span>
                        <span class="dr-s">for all services</span>
                    @else
                        <span class="dr-v" style="font-size:14px;color:var(--text-muted);">Not stated</span>
                        <span class="dr-s">The client gave no budget</span>
                    @endif
                </div>

                <div>
                    <span class="dr-k">Desired Date</span>
                    @if($offer['when'])
                        <span class="dr-v">{{ $on($offer['when']) }}</span>
                        <span class="dr-s blue">{{ $offer['when']->timezone($tz)->format('l') }}@if($e->timeLabel()) &bull; {{ $e->timeLabel() }}@endif</span>
                    @else
                        <span class="dr-v" style="font-size:14px;color:var(--text-muted);">Not set</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dr-tabs" role="tablist">
            <button type="button" class="dr-tab on" data-tab="overview">Request Overview</button>
            <button type="button" class="dr-tab" data-tab="details">Event Details</button>
            <button type="button" class="dr-tab" data-tab="services">Services Requested</button>
            <button type="button" class="dr-tab" data-tab="files">Attachments &amp; Files ({{ $offer['attachments']->count() }})</button>
            <button type="button" class="dr-tab" data-tab="notes">Additional Notes</button>
        </div>

        {{-- Every panel is on the page; the tabs scroll to one and mark it.
             A professional reading a request should not have to click four
             times to see what they are being asked for. --}}
        <div class="dr-pane" id="pane-overview">
            <div class="dr-card">
                <h3><span class="ic">{!! $svg('doc') !!}</span> Request Summary</h3>
                @if($offer['summary'])
                    <p class="dr-sum">{{ $offer['summary'] }}</p>
                @else
                    <p class="dr-none">The client did not write a summary.</p>
                @endif

                @if($offer['chips'])
                    <div class="dr-chips">
                        @foreach($offer['chips'] as $i => $chip)
                            <span class="dr-chip {{ $i === 0 ? 'first' : '' }}">{{ $chip }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="dr-pane" id="pane-details">
            <div class="dr-card">
                <h3><span class="ic">{!! $svg('list') !!}</span> Event Details</h3>
                <div class="dr-dl">
                    @foreach([
                        'Event Name' => $e->title,
                        'Event Type' => $offer['details']['Event Type']
                            . ($offer['details']['Format'] ? ' (' . $offer['details']['Format'] . ')' : ''),
                        'Event Date' => $offer['details']['Event Date'],
                        'Event Time' => $offer['details']['Event Time'],
                    ] as $k => $v)
                        <div>
                            <b>{{ $k }}</b>
                            <span class="{{ $v ? '' : 'none' }}">{{ $v ?: 'Not stated' }}</span>
                        </div>
                    @endforeach

                    <div>
                        <b>Guest Count</b>
                        <span class="{{ $offer['details']['Guest Count'] ? '' : 'none' }}">{{ $offer['details']['Guest Count'] ?: 'Not stated' }}</span>
                    </div>

                    <div>
                        <b>Location</b>
                        <span class="{{ $offer['details']['Location'] ? '' : 'none' }}">{{ $offer['details']['Location'] ?: 'Not stated' }}</span>
                        @if($offer['details']['Location'])
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($offer['details']['Location']) }}"
                               target="_blank" rel="noopener">{!! $svg('map') !!} View on Map</a>
                        @endif
                    </div>

                    <div>
                        <b>Venue</b>
                        <span class="{{ $offer['details']['Venue'] ? '' : 'none' }}">{{ $offer['details']['Venue'] ?: 'Not stated' }}</span>
                    </div>

                    {{-- Her screen has Indoor/Outdoor and Parking here. Neither
                         is recorded on an event, so neither is guessed. The
                         one format that IS recorded, virtual, rides along
                         with the event type above. --}}
                    <div>
                        <b>Parking</b>
                        <span class="none">Not recorded on this platform</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dr-pane" id="pane-services">
            <div class="dr-card">
                <h3>
                    <span class="ic">{!! $svg('tag') !!}</span> Services Requested
                    <small>The client is looking for the following services:</small>
                </h3>

                @if($offer['services'])
                    <table class="dr-table">
                        <thead>
                            <tr><th>Service</th><th>Group</th><th>Quantity / Duration</th></tr>
                        </thead>
                        <tbody>
                        @foreach($offer['services'] as $svc)
                            @php $tint = ['#2563eb', '#7c3aed', '#db2777', '#d97706', '#059669'][crc32($svc['name']) % 5]; @endphp
                            <tr>
                                <td>
                                    <span class="dr-svc">
                                        <span class="tile" style="background: {{ $tint }};">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($svc['name'], 0, 1)) }}</span>
                                        {{ $svc['name'] }}
                                    </span>
                                </td>
                                <td>{{ $svc['group'] ?: '—' }}</td>
                                {{-- Her table carries a brief and an hour count
                                     per service. A request records which
                                     services are wanted, not how much of each,
                                     so the cell says that rather than inventing
                                     "6 hours". --}}
                                <td class="dr-none">Not broken out per service</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="dr-none">No services were listed on this request.</p>
                @endif
            </div>
        </div>

        <div class="dr-pane" id="pane-files">
            <div class="dr-card">
                <h3><span class="ic">{!! $svg('doc') !!}</span> Attachments &amp; Files</h3>
                @if($offer['attachments']->count())
                    <div class="dr-files">
                        @foreach($offer['attachments'] as $file)
                            <span class="dr-file">
                                {!! $svg('doc') !!}
                                <span>
                                    <b>{{ $file->file_name }}</b>
                                    <span>{{ $file->file_size ? number_format($file->file_size / 1024, 0) . ' KB' : 'Size not recorded' }}</span>
                                </span>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="dr-none">The client attached nothing to this request.</p>
                @endif
            </div>
        </div>

        <div class="dr-pane" id="pane-notes">
            <div class="dr-card">
                <h3><span class="ic">{!! $svg('info') !!}</span> Additional Notes</h3>
                @if($offer['client_note'])
                    <p class="dr-sum">{{ $offer['client_note'] }}</p>
                @else
                    <p class="dr-none">The client left no extra notes.</p>
                @endif
            </div>
        </div>
    </div>

    <aside class="dr-rail">
        <div class="dr-card">
            <h4><span class="t">{!! $svg('gem') !!} Your Response Center</span></h4>
            {{-- Her panel offers a Quick Bid. A direct request is not bidding
                 on this platform: it comes to one professional and is accepted
                 or declined. Saying so here is the difference between the two
                 request types a client chose between. --}}
            <p>This request came straight to you, so there is no bidding. Accept it to turn it into a
               confirmed booking, or decline it and the client will be told.</p>

            <div class="stack">
                @if($offer['is_open'])
                    <form method="POST" action="{{ route('professional.direct-offers.accept', $offer['event_id']) }}">
                        @csrf
                        <button type="submit" class="dr-btn" style="width:100%;">{!! $svg('tick') !!} Accept This Request</button>
                    </form>
                    <form method="POST" action="{{ route('professional.direct-offers.decline', $offer['event_id']) }}"
                          onsubmit="return confirm('Decline this request? The client will be told.');">
                        @csrf
                        <button type="submit" class="dr-btn dr-ghost bad" style="width:100%;">{!! $svg('x') !!} Decline</button>
                    </form>
                @endif

                <a class="dr-btn dr-ghost blue" href="{{ route('professional.chat.index') }}">{!! $svg('chat') !!} View Messaging</a>
                <a class="dr-btn dr-ghost blue" href="{{ route('professional.clients.index') }}">{!! $svg('user') !!} View Client Profile</a>
            </div>

            @if($offer['deadline'])
                <div class="dr-deadline">
                    <b>{!! $svg('clock') !!} Response Deadline</b>
                    <span class="big">{{ $offer['days_left'] > 0
                        ? $offer['days_left'] . ' ' . \Illuminate\Support\Str::plural('day', $offer['days_left']) . ' left'
                        : 'The date has passed' }}</span>
                    <span>{!! $svg('cal') !!} {{ $onAt($offer['deadline']) }}</span>
                </div>
            @endif
        </div>

        <div class="dr-card">
            <h4><span class="t">{!! $svg('info') !!} Request Status</span>
                <span class="dr-pill {{ $offer['status_tone'] }}">{{ $offer['status'] }}</span></h4>
            <p style="margin:0;">{{ $offer['status_note'] }}</p>
        </div>

        <div class="dr-card">
            <h4><span class="t">{!! $svg('doc') !!} Client Notes</span></h4>
            @if($offer['client_note'])
                <p class="dr-note">{{ $offer['client_note'] }}</p>
            @else
                <p class="dr-note" style="color:var(--text-muted);">The client left no notes on this request.</p>
            @endif
        </div>

        <div class="dr-card">
            <h4><span class="t">{!! $svg('cash') !!} Budget (Client Provided)</span></h4>
            {{-- Her panel breaks the budget down per service. A client gives
                 one figure for the whole request; there is no per-service
                 budget to show, and splitting the total across the services
                 would invent three numbers out of one. --}}
            @if($offer['budget_min'] || $offer['budget_max'])
                @foreach($offer['services'] as $svc)
                    <div class="dr-row"><span>{{ $svc['name'] }}</span><b class="dr-none">Not split out</b></div>
                @endforeach
                <div class="dr-total">
                    <span>Total Budget</span>
                    <b>{{ $offer['budget_min'] && $offer['budget_max'] && $offer['budget_min'] != $offer['budget_max']
                        ? $money($offer['budget_min']) . ' – ' . $money($offer['budget_max'])
                        : $money($offer['budget_max'] ?: $offer['budget_min']) }}</b>
                </div>
                <p class="dr-foot">The client gave one figure for all services, not a figure per service.</p>
            @else
                <p class="dr-note" style="color:var(--text-muted);">The client gave no budget for this request.</p>
            @endif
        </div>

        @if($ladder)
            <div class="dr-card">
                <h4><span class="t">{!! $svg('bolt') !!} Your payout on this offer</span></h4>
                {{-- Rule R61: every tier receives direct requests. What a tier
                     is worth is what it keeps, so this is the budget's own top
                     figure at each published rate, not an estimate. --}}
                @foreach($ladder as $tier)
                    <div class="dr-row">
                        <span>
                            {{ $tier['label'] }}{{ $tier['current'] ? ' (your plan)' : '' }}
                            <small style="display:block;font-size:11px;">after {{ rtrim(rtrim(number_format($tier['rate'], 2), '0'), '.') }}% commission</small>
                        </span>
                        <b>{{ $money($tier['net']) }}</b>
                    </div>
                @endforeach
                <p class="dr-foot">On the top of the client's budget, before anything is agreed.</p>
            </div>
        @endif
    </aside>
</div>

<script>
(function () {
    /* The tabs scroll to their section and mark themselves. Everything stays
       on the page, so nothing is hidden behind a click. */
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.dr-tab'));

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('on'); });
            tab.classList.add('on');

            var pane = document.getElementById('pane-' + tab.dataset.tab);
            if (pane) { pane.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
})();
</script>
@endif
@endsection
