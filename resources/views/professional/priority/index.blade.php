@extends('layouts.professional')

@section('title', 'Priority Actions')
@section('page-title', 'Priority Actions')

{{-- Khadijah's 28 October screen: one list of the things actually waiting on
     this professional, with tabs across the top and a right hand column for
     today, the deadlines coming, and the last thirty days.

     What this replaced was four counters. "3 contracts" tells somebody three
     contracts need signing without telling them which three, so every row here
     names its own record and carries the button that opens it.

     Her screen ends on a "Want to grow faster?" card. That is a promise about
     somebody's business, and the compliance rules forbid making one, so it is
     not here. --}}

@php
    $tones = [
        'urgent' => ['Urgent',   '#dc2626'],
        'soon'   => ['Due Soon', '#d97706'],
        'new'    => ['New',      '#2563eb'],
        'open'   => ['Open',     '#64748b'],
    ];

    $kindIcon = [
        'Agreement'  => 'doc',
        'Request'    => 'chat',
        'Bid'        => 'clock',
        'Staffing'   => 'team',
        'Package'    => 'box',
        'Scheduling' => 'cal',
        'Payment'    => 'cash',
    ];

    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $tz    = config('app.display_timezone');

    /*
     * The word on the badge. Her rows show the urgency where there is one and
     * the kind of thing where there is not, which is how "PAYMENT" and
     * "PACKAGE" sit in the same column as "URGENT".
     */
    $badge = fn (array $i) => in_array($i['tone'], ['urgent', 'soon', 'new'], true)
        ? ['urgent' => 'Urgent', 'soon' => 'Due Soon', 'new' => 'New'][$i['tone']]
        : $i['kind'];

    // The tile takes the colour of the kind once the urgency has passed, so
    // nine grey rows do not all look the same.
    $kindTint = [
        'Agreement'  => '#7c3aed',
        'Request'    => '#2563eb',
        'Bid'        => '#d97706',
        'Staffing'   => '#0891b2',
        'Package'    => '#7c3aed',
        'Scheduling' => '#2563eb',
        'Payment'    => '#059669',
    ];
@endphp

@push('styles')
<style>
    .pa-layout { display: grid; grid-template-columns: minmax(0,1fr) 330px; gap: 18px; align-items: start; }

    .pa-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; }
    .pa-head { display: flex; align-items: flex-start; gap: 14px; }
    .pa-head .ic { width: 60px; height: 60px; border-radius: 18px; flex: none;
        background: var(--accent-blue, #2563eb); color: #fff; display: flex; align-items: center; justify-content: center; }
    .pa-head .ic svg { width: 29px; height: 29px; }
    .pa-head h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pa-head p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.55; }

    /* Her date card, top right. */
    .pa-date { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px;
        padding: 13px 16px; min-width: 248px; }
    .pa-date .d { display: flex; align-items: center; gap: 9px; font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .pa-date .d svg { width: 17px; height: 17px; flex: none; color: var(--accent-blue, #2563eb); }
    .pa-date .n { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 4px; }
    .pa-date select { width: 100%; margin-top: 10px; font-family: inherit; font-size: 12.5px;
        padding: 7px 9px; border-radius: 8px; border: 1px solid var(--border-color);
        background: var(--bg-page, var(--bg-card)); color: var(--text-primary);
        /* centred, as hers reads */
        text-align: center; text-align-last: center; }

    .pa-tabs { display: flex; gap: 9px; flex-wrap: wrap; margin-bottom: 16px; }
    /* Rounded rectangles, not capsules: her tabs have straight sides with a
       10px corner, and they are a size up on what was here. */
    .pa-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; border-radius: 10px;
        font-size: 13px; font-weight: 700; text-decoration: none;
        color: var(--text-primary); border: 1px solid var(--border-color);
        background: var(--bg-card); white-space: nowrap; }
    .pa-tab svg { width: 16px; height: 16px; flex: none; color: var(--c, var(--text-muted)); }
    .pa-tab.on { border-color: var(--accent-blue, #2563eb); color: var(--accent-blue, #2563eb);
        background: var(--bg-card); box-shadow: 0 0 0 1px var(--accent-blue, #2563eb) inset; }

    /* A row: tick, icon and badge, what it is, when it is due, the button. */
    /* Her row: tick, a tile, the badge, what it is, when, the button, the
       menu. Tight, so nine of them fit on one screen the way hers do. */
    .pa-item { display: grid; grid-template-columns: 16px 38px auto minmax(0,1fr) auto auto 22px;
        gap: 10px; align-items: center;
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 11px; padding: 12px 14px; margin-bottom: 10px; }
    .pa-item .tile { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
        justify-content: center; color: var(--t); background: color-mix(in srgb, var(--t) 13%, transparent); }
    .pa-item .tile svg { width: 19px; height: 19px; }
    .pa-badge { display: inline-block; font-size: 9px; font-weight: 800; letter-spacing: .3px;
        text-transform: uppercase; padding: 3px 7px; border-radius: 5px; color: var(--t);
        background: color-mix(in srgb, var(--t) 13%, transparent); white-space: nowrap; }
    .pa-item b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .pa-subject { font-size: 12px; color: var(--text-secondary, var(--text-muted)); margin-top: 1px; }
    .pa-detail { font-size: 11px; color: var(--text-muted); margin-top: 1px; line-height: 1.45; }
    .pa-when { font-size: 10.5px; color: var(--text-muted); line-height: 1.5; text-align: right;
        white-space: nowrap; }
    .pa-when .due { display: block; color: var(--bad-text, #dc2626); font-weight: 700; }
    .pa-go { font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 8px; text-decoration: none;
        background: var(--accent-blue, #2563eb); color: #fff; white-space: nowrap; }
    .pa-kebab { width: 24px; height: 26px; border-radius: 6px; border: 0;
        background: none; color: var(--text-muted); cursor: pointer; font-size: 15px; line-height: 1; padding: 0; }
    .pa-kebab:hover { background: var(--bg-card-hover, rgba(128,128,128,.1)); }

    .pa-empty { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;
        padding: 30px 20px; text-align: center; }
    .pa-empty b { display: block; font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .pa-empty span { font-size: 13px; color: var(--text-muted); }

    .pa-rail { display: flex; flex-direction: column; gap: 15px; position: sticky; top: 20px; }
    .pa-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px; padding: 15px 16px; }
    .pa-card h4 { display: flex; align-items: center; justify-content: space-between; gap: 10px;
        font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; }
    .pa-card h4 .t { display: inline-flex; align-items: center; gap: 8px; }
    .pa-card h4 svg { width: 16px; height: 16px; flex: none; color: var(--accent-blue, #2563eb); }
    .pa-card h4 a { font-size: 11.5px; font-weight: 700; color: var(--accent-blue, #2563eb);
        text-decoration: none; white-space: nowrap; }

    /* Today, as a timeline with a dot per entry. */
    .pa-line { position: relative; padding-left: 15px; }
    .pa-line::before { content: ""; position: absolute; left: 4px; top: 6px; bottom: 6px;
        width: 2px; background: var(--border-color); }
    .pa-row { position: relative; display: flex; gap: 11px; align-items: baseline; padding: 7px 0; }
    .pa-row::before { content: ""; position: absolute; left: -15px; top: 11px; width: 10px; height: 10px;
        border-radius: 50%; background: var(--accent-blue, #2563eb); box-shadow: 0 0 0 3px var(--bg-card); }
    .pa-row .t { font-size: 11.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; flex: none; }
    .pa-row .m { min-width: 0; }
    .pa-row .m b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .pa-row .m span { font-size: 11px; color: var(--text-muted); }
    .pa-none { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; }

    .pa-due { display: flex; gap: 10px; align-items: flex-start; padding: 9px 0; border-top: 1px solid var(--border-color); }
    .pa-due:first-of-type { border-top: 0; padding-top: 0; }
    .pa-due .tile { width: 30px; height: 30px; border-radius: 8px; flex: none; display: flex;
        align-items: center; justify-content: center; color: var(--t);
        background: color-mix(in srgb, var(--t) 13%, transparent); }
    .pa-due .tile svg { width: 15px; height: 15px; }
    .pa-due .m { min-width: 0; flex: 1 1 auto; }
    .pa-due .m b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); line-height: 1.35; }
    .pa-due .m span { display: block; font-size: 11px; color: var(--text-muted); }
    .pa-due .at { font-size: 11px; font-weight: 700; color: var(--bad-text, #dc2626); white-space: nowrap; text-align: right; }
    .pa-due .at i { display: block; font-style: normal; font-weight: 600; color: var(--text-muted); }

    .pa-snap { display: flex; flex-direction: column; }
    .pa-snaprow { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border-color); }
    .pa-snaprow:first-child { border-top: 0; padding-top: 0; }
    .pa-snaprow .tile { width: 26px; height: 26px; border-radius: 7px; flex: none; display: flex;
        align-items: center; justify-content: center; color: var(--t);
        background: color-mix(in srgb, var(--t) 13%, transparent); }
    .pa-snaprow .tile svg { width: 14px; height: 14px; }
    .pa-snaprow .l { flex: 1 1 auto; min-width: 0; font-size: 12.5px;
        color: var(--text-secondary, var(--text-muted)); white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis; }
    .pa-snaprow .v { font-size: 13px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .pa-snaprow .c { font-size: 10.5px; font-weight: 700; white-space: nowrap; min-width: 42px; text-align: right; }
    .pa-snaprow .c.up { color: #059669; }
    .pa-snaprow .c.down { color: #dc2626; }
    .pa-snaprow .c.flat { color: var(--text-muted); font-weight: 600; }

    @media (max-width: 1150px) { .pa-layout { grid-template-columns: 1fr; } .pa-rail { position: static; } }
    @media (max-width: 980px) {
        .pa-item { grid-template-columns: 18px 40px minmax(0,1fr); row-gap: 9px; }
        .pa-badge, .pa-when, .pa-go, .pa-kebab { grid-column: 3; justify-self: start; text-align: left; }
    }
</style>
@endpush

@section('content')
@php
    $paths = [
        'stack' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M2 13h20"/><path d="M11 13h2"/>',
        'cal'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'list'  => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'chat'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'cash'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'doc'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'team'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/>',
        'box'   => '<path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'cup'   => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/>',
        'send'  => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
        'in'    => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
    ];
    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';

    $tabIcon = [
        'all' => 'list', 'urgent' => 'alert', 'soon' => 'clock',
        'clients' => 'chat', 'payments' => 'cash', 'agreements' => 'doc',
    ];
    $tabTint = [
        'all' => '#2563eb', 'urgent' => '#dc2626', 'soon' => '#d97706',
        'clients' => '#2563eb', 'payments' => '#059669', 'agreements' => '#7c3aed',
    ];
@endphp


<div class="pa-layout">
    <div>
    <div class="pa-top">
        <div class="pa-head">
            <span class="ic">{!! $svg('stack') !!}</span>
            <div>
                <h1>Priority Actions</h1>
                {{-- Two sentences, two lines, the way hers reads. Left to
                     wrap on its own it broke in the middle of the second. --}}
                <p>Your most important tasks, all in one place.<br>
                   Stay on top of what needs your attention to keep your business running smoothly.</p>
            </div>
        </div>

        <div class="pa-date">
            <span class="d">{!! $svg('cal') !!} {{ now()->timezone($tz)->format('D, M j, Y') }}</span>
            {{-- And how many there actually are, which the heading cannot say. --}}
            <span class="n">
                @if ($total === 0)
                    Nothing needs your attention
                @else
                    {{ $total }} {{ \Illuminate\Support\Str::plural('item', $total) }} need{{ $total === 1 ? 's' : '' }} your attention
                @endif
            </span>

            {{-- Her picker. It changes the schedule card beside it, which is the
                 only thing on this page that has a day to look at. --}}
            <form method="GET" action="{{ route('professional.priority.index') }}">
                @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
                <select name="span" onchange="this.form.submit()" aria-label="Schedule span">
                    <option value="today" @selected($span === 'today')>Today</option>
                    <option value="week"  @selected($span === 'week')>Next 7 days</option>
                </select>
            </form>
        </div>
    </div>

        <div class="pa-tabs">
            @foreach ($tabs as $key => $meta)
                <a class="pa-tab {{ $tab === $key ? 'on' : '' }}" style="--c: {{ $tabTint[$key] ?? '#64748b' }};"
                   href="{{ route('professional.priority.index', $key === 'all' ? [] : ['tab' => $key]) }}">
                    {!! $svg($tabIcon[$key] ?? 'list') !!} {{ $meta['label'] }} ({{ $meta['count'] }})
                </a>
            @endforeach
        </div>

        @forelse ($items as $item)
            @php
                [$toneLabel, $toneColour] = $tones[$item['tone']] ?? $tones['open'];

                if (! in_array($item['tone'], ['urgent', 'soon', 'new'], true)) {
                    $toneColour = $kindTint[$item['kind']] ?? $toneColour;
                }

                $icon = $kindIcon[$item['kind']] ?? 'list';
            @endphp
            <div class="pa-item" style="--t: {{ $toneColour }};">
                <input type="checkbox" aria-label="Select {{ $item['title'] }}">

                <span class="tile">{!! $svg($icon) !!}</span>

                {{-- Her badge names the urgency where there is one and the
                     kind of thing where there is not, so a row says what it
                     is before you read a word of it. --}}
                <span><span class="pa-badge">{{ $badge($item) }}</span></span>

                <div>
                    <b>{{ $item['title'] }}</b>
                    @if ($item['subject'])
                        <div class="pa-subject">
                            {{ $item['subject'] }}@if ($item['where'] ?? null) <span style="opacity:.7;">&bull; {{ $item['where'] }}</span>@endif
                        </div>
                    @endif
                    @if ($item['detail'])
                        <div class="pa-detail" title="{{ $item['detail'] }}">{{ $item['detail'] }}</div>
                    @endif
                </div>

                <div class="pa-when">
                    @if (($item['sent'] ?? null) && ($item['sent_as'] ?? null))
                        <span>{{ $item['sent_as'] }}: {{ $item['sent']->timezone($tz)->format('M j, Y') }}</span>
                    @elseif ($item['when'])
                        <span>{{ $item['when'] }}</span>
                    @endif

                    @if ($item['due'])
                        <span class="due">Due: {{ $item['due']->timezone($tz)->format('M j, Y') }}</span>
                        <span class="due">({{ $item['due']->humanAgo() }})</span>
                    @endif

                    @if (! ($item['sent'] ?? null) && ! $item['when'] && ! $item['due'])
                        {{-- Nothing recorded a date on this one, so none is
                             made up to fill the column. --}}
                        <span>No date on this one</span>
                    @endif
                </div>

                <a class="pa-go" href="{{ $item['url'] }}">{{ $item['cta'] }}</a>

                <a class="pa-kebab" href="{{ $item['url'] }}" style="display:flex;align-items:center;justify-content:center;text-decoration:none;"
                   aria-label="Open {{ $item['title'] }}">&#8942;</a>
            </div>
        @empty
            <div class="pa-empty">
                <b>Nothing here</b>
                <span>
                    @if ($tab === 'all')
                        When a client signs an agreement, sends a request or a deadline comes close, it appears on this list.
                    @else
                        Nothing in this group right now. Try All.
                    @endif
                </span>
            </div>
        @endforelse
    </div>

    <aside class="pa-rail">
        <div class="pa-card">
            <h4>
                <span class="t">{!! $svg('cal') !!} {{ $span === 'week' ? 'Your Next 7 Days' : 'Your Schedule Today' }}</span>
                <a href="{{ route('professional.calendar.index') }}">View Calendar &rarr;</a>
            </h4>
            @if (count($today))
                <div class="pa-line">
                    @foreach ($today as $slot)
                        <div class="pa-row">
                            <span class="t">{{ $slot['at']->timezone($tz)->format($span === 'week' ? 'D g:i A' : 'g:i A') }}</span>
                            <span class="m">
                                <b>{{ $slot['title'] }}</b>
                                @if ($slot['where']) <span>{{ $slot['where'] }}</span> @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pa-none">{{ $span === 'week' ? 'Nothing booked in the next seven days.' : 'Nothing booked for today.' }}</div>
            @endif
        </div>

        <div class="pa-card">
            <h4>
                <span class="t">{!! $svg('clock', 'style="color:#dc2626;"') !!} Upcoming Deadlines</span>
                <a href="{{ route('professional.priority.index') }}">View All &rarr;</a>
            </h4>
            @forelse ($deadlines as $item)
                @php
                    [$dLabel, $dColour] = $tones[$item['tone']] ?? $tones['open'];
                @endphp
                <div class="pa-due" style="--t: {{ $dColour }};">
                    <span class="tile">{!! $svg($kindIcon[$item['kind']] ?? 'list') !!}</span>
                    <span class="m">
                        <b>{{ $item['title'] }}</b>
                        @if ($item['subject'])<span>{{ $item['subject'] }}</span>@endif
                    </span>
                    <span class="at">
                        {{ $item['due']->timezone($tz)->format('M j') }}
                        <i>{{ $item['due']->humanAgo() }}</i>
                    </span>
                </div>
            @empty
                <div class="pa-none">Nothing with a date on it.</div>
            @endforelse
        </div>

        <div class="pa-card">
            <h4>
                <span class="t">{!! $svg('chart') !!} Business Snapshot</span>
                <a href="{{ route('professional.reports.index') }}">View Reports &rarr;</a>
            </h4>
            <p class="pa-none" style="margin:-4px 0 10px;">Last 30 Days</p>

            <div class="pa-snap">
                @php
                    // Blue for the counts, green only for money. Five
                    // different colours down one short list made each row
                    // look like its own kind of thing.
                    $rows = [
                        ['in',   '#2563eb', 'Requests received', $snapshot['requests'], $snapshot['change']['requests']],
                        ['send', '#2563eb', 'Bids placed',       $snapshot['bids'],     $snapshot['change']['bids']],
                        ['cup',  '#2563eb', 'Bookings won',      $snapshot['won'],      $snapshot['change']['won']],
                    ];
                @endphp

                @foreach ($rows as [$icon, $tint, $label, $value, $change])
                    <div class="pa-snaprow" style="--t: {{ $tint }};">
                        <span class="tile">{!! $svg($icon) !!}</span>
                        <span class="l">{{ $label }}</span>
                        <span class="v">{{ $value }}</span>
                        {{-- Nothing at all where there was nothing to compare
                             against: everything is not an infinite
                             improvement on nothing, and a word in the column
                             reads like a figure. --}}
                        <span class="c {{ $change === null ? 'flat' : ($change >= 0 ? 'up' : 'down') }}"
                              title="{{ $change === null
                                  ? 'No earlier 30 days to compare against'
                                  : 'Against the 30 days before these' }}">
                            {{ $change === null ? '' : ($change >= 0 ? "↑ " : "↓ ") . abs($change) . '%' }}
                        </span>
                    </div>
                @endforeach

                <div class="pa-snaprow" style="--t: #059669;">
                    <span class="tile">{!! $svg('cash') !!}</span>
                    <span class="l">Earned after fees</span>
                    <span class="v">{{ $money($snapshot['earned']) }}</span>
                    <span class="c flat"></span>
                </div>

                <div class="pa-snaprow" style="--t: #2563eb;">
                    <span class="tile">{!! $svg('chart') !!}</span>
                    <span class="l">Average bid</span>
                    <span class="v">{{ $snapshot['average'] ? $money($snapshot['average']) : 'No bids' }}</span>
                    <span class="c flat"></span>
                </div>
            </div>
        </div>
    </aside>
</div>
@endsection
