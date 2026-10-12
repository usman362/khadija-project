@extends('layouts.client')

@section('title', 'Check Availability')

@push('styles')
<style>
    .ca-banner { display: flex; align-items: center; gap: 16px; padding: 18px 22px; border-radius: 16px;
        background: linear-gradient(90deg, rgba(249,115,22,.16), rgba(249,115,22,.05)); margin-bottom: 14px; }
    [data-theme="dark"] .ca-banner { background: linear-gradient(90deg, rgba(249,115,22,.18), rgba(249,115,22,.04)); }
    .ca-banner > svg { width: 42px; height: 42px; color: var(--accent-orange, #ea580c); flex: none; }
    .ca-banner h1 { font-size: 27px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.15; }
    .ca-banner p { font-size: 13.5px; color: var(--text-secondary); margin: 3px 0 0; }

    .ca-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }

    /* The banner. Loud on purpose: everything under it is invented. */
    .ca-warn { display: flex; gap: 12px; align-items: flex-start; border: 1px solid #fca5a5;
        background: #fef2f2; border-radius: 13px; padding: 13px 16px; margin-bottom: 14px; }
    [data-theme="dark"] .ca-warn { background: rgba(220,38,38,.1); border-color: rgba(248,113,113,.4); }
    .ca-warn svg { width: 19px; height: 19px; color: #dc2626; flex: none; margin-top: 1px; }
    .ca-warn b { display: block; font-size: 13.5px; color: #991b1b; margin-bottom: 2px; }
    [data-theme="dark"] .ca-warn b { color: #fca5a5; }
    .ca-warn p { font-size: 12.5px; color: var(--text-secondary); margin: 0; line-height: 1.55; }
    .ca-warn a { font-weight: 700; color: inherit; }

    .ca-note { border: 1px solid var(--border-color); background: var(--bg-card-hover, #f8fafc);
        border-radius: 13px; padding: 13px 16px; margin-bottom: 14px; }
    .ca-note b { display: block; font-size: 13.5px; color: var(--text-primary); margin-bottom: 2px; }
    .ca-note p { font-size: 12.5px; color: var(--text-muted); margin: 0; line-height: 1.55; }

    /* Steps */
    .ca-steps { display: grid; grid-template-columns: minmax(0,1.1fr) minmax(0,1.4fr) minmax(0,1.1fr); gap: 14px; margin-bottom: 14px; }
    .ca-step { padding: 14px 16px; }
    .ca-step-h { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); margin-bottom: 9px; }
    .ca-step select, .ca-step input { width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 9px 11px; font: inherit; font-size: 13px; color: var(--text-primary); background: var(--bg-card); }
    .ca-step select:focus, .ca-step input:focus { outline: none; border-color: var(--accent-orange, #ea580c); }
    .ca-ev-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 7px; }

    .ca-dates { display: flex; gap: 9px; flex-wrap: wrap; }
    .ca-date { border: 1px solid var(--border-color); border-radius: 11px; padding: 9px 12px; min-width: 112px; }
    .ca-date.is-primary { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.06); }
    .ca-date-l { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .3px; color: var(--text-muted); }
    .ca-date-l svg { width: 12px; height: 12px; flex: none; }
    .ca-date.is-blank { opacity: .55; border-style: dashed; }
    .ca-date-v { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .ca-date-t { font-size: 11px; color: var(--text-muted); }

    /* Results */
    .ca-filters { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; margin-top: 8px; }
    .ca-filters select { width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 8px 9px; font: inherit; font-size: 12.5px; color: var(--text-primary); background: var(--bg-card); }

    .ca-bar { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 12px; }
    .ca-sort { display: flex; align-items: center; gap: 9px; margin-left: auto; font-size: 12px; color: var(--text-muted); }
    .ca-sort-opts { display: flex; gap: 4px; }
    .ca-sort-opts a { font-size: 12px; font-weight: 700; padding: 6px 11px; border-radius: 999px;
        color: var(--text-secondary); text-decoration: none; border: 1px solid transparent; }
    .ca-sort-opts a.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.1); color: var(--brand-text, #c2410c); }
    .ca-clear { font-size: 12px; font-weight: 700; color: var(--accent-orange, #ea580c); text-decoration: none; }
    @media (max-width: 760px) { .ca-sort { margin-left: 0; } .ca-filters { grid-template-columns: 1fr; } }

    .ca-tabs { display: flex; gap: 7px; }
    .ca-tab { font-size: 12.5px; font-weight: 700; padding: 8px 14px; border-radius: 999px;
        border: 1px solid var(--border-color); color: var(--text-secondary); text-decoration: none; }
    .ca-tab.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.1); color: var(--brand-text, #c2410c); }

    .ca-table { width: 100%; border-collapse: collapse; }
    .ca-th-sub { display: block; font-weight: 600; text-transform: none; letter-spacing: 0;
        font-size: 10.5px; color: var(--text-muted); margin-top: 2px; }
    .ca-table th { text-align: left; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px;
        color: var(--text-muted); padding: 11px 14px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .ca-table td { padding: 11px 14px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-primary); vertical-align: middle; }
    .ca-table tr:last-child td { border-bottom: 0; }

    .ca-who { display: flex; align-items: center; gap: 10px; min-width: 190px; }
    .ca-who img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex: none; }
    .ca-who-n { font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .ca-who-m { font-size: 11.5px; color: var(--text-muted); }

    .ca-kind { font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
    .ca-kind.pro { background: rgba(29,78,216,.1); color: #1d4ed8; }
    .ca-kind.inf { background: rgba(109,40,217,.1); color: #6d28d9; }

    .ca-state { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; white-space: nowrap; }
    .ca-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
    .ca-dot.available   { background: #10b981; }
    .ca-dot.unavailable { background: #ef4444; }
    .ca-dot.unanswered  { background: #9ca3af; }
    .ca-state.unanswered { color: var(--text-muted); }

    .ca-acts { display: flex; flex-direction: column; gap: 6px; }
    .ca-btn { display: inline-flex; align-items: center; justify-content: center; padding: 7px 13px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary);
        font-size: 12px; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .ca-btn.go { background: var(--accent-orange, #ea580c); border-color: transparent; color: #fff; }

    .ca-legend { display: flex; gap: 18px; flex-wrap: wrap; padding: 13px 16px; border-top: 1px solid var(--border-color); }
    .ca-legend span { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; color: var(--text-muted); }

    .ca-none { padding: 38px 24px; text-align: center; }
    .ca-none p { font-size: 13.5px; color: var(--text-muted); margin: 0 0 4px; line-height: 1.6; }
    .ca-none b { color: var(--text-primary); }

    @media (max-width: 1000px) { .ca-steps { grid-template-columns: 1fr; } .ca-wrap { overflow-x: auto; } }
</style>
@endpush

@section('content')
@php
    $link = fn (array $over = []) => route('client.check-availability.index', array_filter(array_merge([
        'event'  => $event?->id,
        'who'    => $kind === 'influencer' ? 'influencers' : null,
        'q'       => $q ?: null,
        'service' => $serviceId,
        'place'   => $place ?: null,
        'sort'    => $sort !== 'match' ? $sort : null,
        'sample'  => $sample ? 1 : null,
    ], $over), fn ($v) => $v !== null && $v !== ''));
@endphp

<div class="ca-banner">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
    <div>
        <h1>Check Availability &ndash; {{ $kind === 'influencer' ? 'Influencers' : 'Professionals' }}</h1>
        <p>See if {{ $kind === 'influencer' ? 'Influencers' : 'Professionals' }} are available for your event dates before you hire them.</p>
    </div>
</div>

@if($sample)
    <div class="ca-warn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <b>Sample answers. Do not book from this screen.</b>
            <p>
                The people and the dates below are real; <b style="display:inline;">the green and red marks are invented</b> so the
                finished screen can be reviewed. Nobody has told us when they are free.
                <a href="{{ $link(['sample' => null]) }}">Show what we actually know</a>.
            </p>
        </div>
    </div>
@else
    <div class="ca-note">
        <b>We can tell you who is busy, not who is free.</b>
        <p>
            A red mark is a real booking. Everything else reads as Not Answered, because saying "I am free that day" is
            something {{ $kind === 'influencer' ? 'influencers' : 'professionals' }} record about themselves, and their side cannot record it yet.
            <a href="{{ $link(['sample' => 1]) }}" style="font-weight:700;">See the screen with sample answers</a>.
        </p>
    </div>
@endif

{{-- ── 1, 2, 3 ─────────────────────────────────────── --}}
<form method="GET" class="ca-steps">
    @if($kind === 'influencer')<input type="hidden" name="who" value="influencers">@endif
    @if($sample)<input type="hidden" name="sample" value="1">@endif

    <div class="ca-card ca-step">
        <div class="ca-step-h">1. Select Event</div>
        @if($events->isEmpty())
            <p style="font-size:13px;color:var(--text-muted);margin:0;">You have no events with a date yet.</p>
        @else
            <select name="event" onchange="this.form.submit()" aria-label="Which event">
                @foreach($events as $e)
                    <option value="{{ $e->id }}" @selected($event?->id === $e->id)>{{ $e->title }}</option>
                @endforeach
            </select>
            @if($event)
                <div class="ca-ev-meta">
                    {{ $event->starts_at?->format('D, M j, Y · g:i A') }}@if($event->city) · {{ $event->city }}@if($event->state), {{ $event->state }}@endif @endif
                </div>
            @endif
        @endif
    </div>

    <div class="ca-card ca-step">
        <div class="ca-step-h">2. Dates to Check</div>
        @if($dates->isEmpty())
            <p style="font-size:13px;color:var(--text-muted);margin:0;">Pick an event with a date.</p>
        @else
            <div class="ca-dates">
                @foreach($dates as $d)
                    <div class="ca-date {{ $d['key'] === 'primary' ? 'is-primary' : '' }}">
                        <div class="ca-date-l">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            {{ $d['label'] }}
                        </div>
                        <div class="ca-date-v">{{ $d['at']->format('M j, Y') }}</div>
                        <div class="ca-date-t">{{ $d['at']->format('D, g:i A') }}</div>
                    </div>
                @endforeach

                {{-- The slots his screen has, shown empty rather than filled
                     in: a backup is a date the client chose, and inventing one
                     would put a date on the page nobody picked. --}}
                @for($i = $dates->count(); $i < 3; $i++)
                    <div class="ca-date is-blank">
                        <div class="ca-date-l">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Backup Date {{ $i }}
                        </div>
                        <div class="ca-date-v">Not set</div>
                        <div class="ca-date-t">Add one when you post</div>
                    </div>
                @endfor
            </div>
        @endif
    </div>

    <div class="ca-card ca-step">
        <div class="ca-step-h">3. Find {{ $kind === 'influencer' ? 'Influencers' : 'Professionals' }}</div>
        <input type="text" name="q" value="{{ $q }}" placeholder="Search by name…" autocomplete="off">
        {{-- Both lists are built from who is actually on the site, so neither
             can offer a service nobody does or a town nobody is in. --}}
        <div class="ca-filters">
            <select name="service" onchange="this.form.submit()" aria-label="Service type">
                <option value="">All Service Types</option>
                @foreach($services as $svc)
                    <option value="{{ $svc->id }}" @selected($serviceId === $svc->id)>{{ $svc->name }}</option>
                @endforeach
            </select>
            <select name="place" onchange="this.form.submit()" aria-label="Location">
                <option value="">All Locations</option>
                @foreach($places as $pl)
                    <option value="{{ $pl }}" @selected($place === $pl)>{{ $pl }}</option>
                @endforeach
            </select>
        </div>
        <div class="ca-ev-meta">{{ $rows->count() }} {{ Str::plural($kind, $rows->count()) }} listed</div>
    </div>
</form>

<div class="ca-bar">
    <div class="ca-tabs">
        <a href="{{ $link(['who' => null]) }}" class="ca-tab {{ $kind === 'professional' ? 'is-on' : '' }}">Professionals</a>
        <a href="{{ $link(['who' => 'influencers']) }}" class="ca-tab {{ $kind === 'influencer' ? 'is-on' : '' }}">Influencers</a>
    </div>
    <div class="ca-sort">
        <span>Sort by</span>
        <div class="ca-sort-opts">
            {{-- Best match is free on the date you asked about first, then
                 best reviewed. Sorting by name would bury the answer. --}}
            <a href="{{ $link(['sort' => null]) }}" class="{{ $sort === 'match' ? 'is-on' : '' }}">Best match</a>
            <a href="{{ $link(['sort' => 'rating']) }}" class="{{ $sort === 'rating' ? 'is-on' : '' }}">Rating</a>
            <a href="{{ $link(['sort' => 'name']) }}" class="{{ $sort === 'name' ? 'is-on' : '' }}">Name</a>
        </div>
        @if($filtered)
            <a class="ca-clear" href="{{ $link(['q' => null, 'service' => null, 'place' => null, 'sort' => null]) }}">Clear all</a>
        @endif
    </div>
</div>

{{-- ── Results ─────────────────────────────────────── --}}
<div class="ca-card ca-wrap">
    @if($rows->isEmpty())
        <div class="ca-none">
            @if($dates->isEmpty())
                <p><b>Pick an event first.</b></p>
                <p>Choose one with a date and the people you could hire for it appear here.</p>
            @elseif($kind === 'influencer')
                <p><b>No influencers on the site yet.</b></p>
                <p>When the influencer side opens, they will be listed here beside the professionals.</p>
            @else
                <p><b>Nobody matches that search.</b></p>
            @endif
        </div>
    @else
        <table class="ca-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Service</th>
                    @foreach($dates as $d)
                        <th>
                            {{ $d['label'] }}
                            <span class="ca-th-sub">{{ $d['at']->format('M j, Y') }}</span>
                            <span class="ca-th-sub">{{ $d['at']->format('g:i A') }}@if($event?->ends_at) &ndash; {{ $event->ends_at->format('g:i A') }}@endif</span>
                        </th>
                    @endforeach
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>
                            <div class="ca-who">
                                <img src="{{ $r['avatar'] }}" alt="">
                                <div>
                                    <div class="ca-who-n">{{ $r['name'] }}</div>
                                    <div class="ca-who-m">
                                        @if($r['rating'])&#9733; {{ $r['rating'] }} ({{ $r['reviews'] }})@endif
                                        @if($r['where'])@if($r['rating']) · @endif{{ $r['where'] }}@endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="ca-kind {{ $r['noun'] === 'influencer' ? 'inf' : 'pro' }}">{{ ucfirst($r['noun']) }}</span></td>
                        <td style="max-width:220px;">{{ $r['services'] ?? '—' }}</td>
                        @foreach($dates as $d)
                            @php $s = $r['states'][$d['key']] ?? \App\Domain\CheckAvailability::UNKNOWN; @endphp
                            <td>
                                <span class="ca-state {{ $s }}">
                                    <span class="ca-dot {{ $s }}"></span>
                                    {{ \App\Domain\CheckAvailability::LABELS[$s] }}
                                </span>
                            </td>
                        @endforeach
                        <td>
                            <div class="ca-acts">
                                @if($r['profile'])
                                    <a class="ca-btn go" href="{{ $r['profile'] }}">View Profile</a>
                                @endif
                                <a class="ca-btn" href="{{ route('client.chat.index') }}">Send Message</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ca-legend">
            <span><span class="ca-dot available"></span> Available</span>
            <span><span class="ca-dot unavailable"></span> Unavailable</span>
            <span><span class="ca-dot unanswered"></span> Not Answered</span>
        </div>
    @endif
</div>
@endsection
