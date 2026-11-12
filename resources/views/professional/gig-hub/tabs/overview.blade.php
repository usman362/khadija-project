{{-- Gig Operations Hub, overview. Khadijah's 28 October screen.

     Three of her figures are not here, because nothing records them:

       "6 Unread Messages"   no message carries a read state. What is counted
                             instead is the threads where the last word was
                             the client's, which is the thing that actually
                             needs answering.
       Event thumbnails      no event stores an image, so each row carries a
                             tile rather than a stock photograph.
       "Next event in 5      this one IS real, worked out from the soonest
        days"                booked event, and says so when there is none. --}}

@php
    $tz    = config('app.display_timezone');
    // $money is already the Earnings array from the controller, so the
    // formatter gets its own name rather than overwriting it.
    $cash = fn ($v) => '$' . number_format((float) $v, 0);
    $on    = fn ($d) => $d?->timezone($tz)->format('M j, Y');

    $paths = [
        'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'right'  => '<path d="m9 18 6-6-6-6"/>',
        'gigs'   => '<path d="M20 7h-3V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M9 7V5h6v2"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'tick'   => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'alert'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'pen'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'cash'   => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'hist'   => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>',
        'team'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/>',
        'box'    => '<path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>',
        'alert'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
    ];
    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';

    $kindIcon = [
        'Agreement' => 'doc', 'Request' => 'chat', 'Bid' => 'clock',
        'Staffing' => 'team', 'Package' => 'box', 'Scheduling' => 'cal', 'Payment' => 'cash',
    ];
    $toneTint = ['urgent' => '#dc2626', 'soon' => '#d97706', 'new' => '#2563eb', 'open' => '#64748b'];
@endphp

@push('styles')
<style>
    .gh-tiles { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:14px; }
    .gh-tile { display:flex; gap:14px; align-items:center; background:var(--bg-card);
        border:1px solid var(--border-color); border-radius:14px; padding:17px 18px; text-decoration:none; }
    .gh-tile .ic { width:46px; height:46px; border-radius:12px; flex:none; display:flex; align-items:center;
        justify-content:center; color:var(--c); background:color-mix(in srgb, var(--c) 13%, transparent); }
    .gh-tile .ic svg { width:23px; height:23px; }
    .gh-tile .m { flex:1 1 auto; min-width:0; }
    .gh-tile .n { display:block; font-size:27px; font-weight:800; color:var(--text-primary); line-height:1.15; }
    .gh-tile .l { display:block; font-size:13px; color:var(--text-secondary, var(--text-muted)); }
    /* Her sub-line is coloured by what it says, not by the card's icon:
       green when there is something coming, amber when something is on
       you, blue when a client is waiting. */
    .gh-tile .s { display:block; font-size:11.5px; color:var(--s, var(--c)); font-weight:600; margin-top:3px; }
    .gh-tile .go { color:var(--text-muted); flex:none; }
    .gh-tile .go svg { width:17px; height:17px; }

    .gh-layout { display:grid; grid-template-columns:minmax(0,1fr) 322px; gap:18px; align-items:start; }
    /* The tracks are minmax(0,1fr), but the COLUMNS inside them are grid items
       of their own with an auto minimum, so the widest thing in a column still
       sets that column's width. The table scroller below is 560px wide by
       design, and without this it dragged the whole left column to 598 on a
       375px screen, taking every tile and card in it over the edge. */
    .gh-layout > * { min-width: 0; }
    .gh-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px;
        padding:16px 18px; margin-bottom:16px; }
    .gh-card h3 { display:flex; align-items:center; justify-content:space-between; gap:12px;
        font-size:15px; font-weight:800; color:var(--text-primary); margin:0 0 14px; }
    .gh-card h3 .t { display:inline-flex; align-items:center; gap:10px; min-width:0; }
    .gh-card h3 .t > .ic { width:30px; height:30px; border-radius:8px; flex:none; display:flex;
        align-items:center; justify-content:center; color:var(--accent-blue, #2563eb);
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 12%, transparent); }
    .gh-card h3 .t > .ic svg { width:16px; height:16px; }
    .gh-card h3 a { font-size:12px; font-weight:700; color:var(--accent-blue, #2563eb); text-decoration:none; white-space:nowrap; }
    .gh-card h3 small { display:block; font-weight:600; font-size:12px; color:var(--text-muted); margin-top:2px; }

    /* Fixed, or the browser re-sizes the columns to fit its own idea of the
       content and the colgroup above is ignored. Her widths then do not hold. */
    /* min-width: 0 on the scroller AND on the card holding it. Without it the
       table's own min-width travels up through them: a grid item's minimum is
       its content, so the card grew to 600px on a 375px screen and took every
       tile beside it over the edge. The scroller has to be allowed to be
       narrower than what it scrolls. */
    .gh-tablewrap { overflow-x: auto; min-width: 0; }
    .gh-card { min-width: 0; }
    /* A minimum, so the fixed column widths have something real to divide up
       on a narrow screen instead of crushing the last two to nothing. */
    .gh-table { width:100%; min-width: 560px; border-collapse:collapse; font-size:12.5px; table-layout:fixed; }
    .gh-table th { text-align:left; font-size:11px; font-weight:700; color:var(--text-muted);
        padding:0 8px 9px; border-bottom:1px solid var(--border-color); white-space:nowrap; }
    .gh-table td { padding:9px 8px; border-bottom:1px solid var(--border-color);
        vertical-align:middle; color:var(--text-primary); }
    .gh-table tr:last-child td { border-bottom:0; }
    .gh-who { display:flex; gap:11px; align-items:center; }
    /* No event stores a photograph, so this is a tile: the same light tint
       and coloured icon every other tile on this page uses. */
    /* .gh-who span sets a muted colour and outranks a bare .gh-thumb, which
       is why the icon came out slate instead of the row's own tint. */
    .gh-who .gh-thumb { width:50px; height:41px; border-radius:9px; flex:none; display:flex;
        align-items:center; justify-content:center; color:var(--t);
        background:color-mix(in srgb, var(--t) 13%, transparent); }
    .gh-who .gh-thumb svg { width:19px; height:19px; }
    .gh-who b { display:block; font-size:13px; font-weight:800; color:var(--text-primary); line-height:1.3; }
    .gh-who span { display:block; font-size:11.5px; color:var(--text-muted); }
    .gh-chip { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700;
        padding:4px 10px; border-radius:20px; white-space:nowrap; }
    .gh-chip svg { width:12px; height:12px; }
    .gh-type { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600;
        color:var(--text-primary); white-space:nowrap; }
    .gh-type svg { width:14px; height:14px; flex:none; color:var(--t); }
    .gh-dates { font-size:11.5px; color:var(--text-muted); line-height:1.6; }
    .gh-dates b { display:flex; align-items:center; gap:5px; font-size:12px; font-weight:700;
        color:var(--text-primary); white-space:nowrap; }
    .gh-dates b svg { width:12px; height:12px; flex:none; color:var(--text-muted); }
    .gh-dates .hot { color:var(--bad-text, #dc2626); font-weight:700; }
    .gh-dates span { display:flex; align-items:center; gap:5px; }
    .gh-dates span svg { width:12px; height:12px; flex:none; }
    .gh-dates .ok { color:var(--ok-text, #047857); font-weight:700; }
    .gh-amt { font-size:13.5px; font-weight:800; white-space:nowrap; }
    .gh-acts { display:inline-flex; gap:6px; align-items:center; }
    .gh-btn { font-size:11.5px; font-weight:700; padding:6px 13px; border-radius:8px; text-decoration:none;
        border:1px solid var(--border-color); color:var(--text-primary); background:var(--bg-card); white-space:nowrap; }
    .gh-kebab { width:26px; height:27px; border-radius:7px; border:0;
        background:none; color:var(--text-muted); font-size:15px; line-height:1;
        display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
    .gh-kebab:hover { background:var(--bg-card-hover, rgba(128,128,128,.1)); }

    /* What happens if you click a gig: five columns, each with a preview. */
    .gh-deep { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:14px; }
    .gh-deep > div { border:1px solid var(--border-color); border-radius:12px; padding:13px; }
    .gh-deep h5 { display:flex; align-items:center; gap:7px; font-size:12.5px; font-weight:800;
        color:var(--text-primary); margin:0 0 6px; }
    .gh-deep h5 svg { width:15px; height:15px; flex:none; color:var(--accent-blue, #2563eb); }
    .gh-deep p { font-size:11px; color:var(--text-muted); line-height:1.5; margin:0 0 10px; }
    .gh-prev { background:var(--bg-page, rgba(128,128,128,.06)); border-radius:9px; padding:10px 11px;
        font-size:11px; color:var(--text-secondary, var(--text-muted)); line-height:1.5; }
    .gh-prev b { display:block; font-size:11.5px; color:var(--text-primary); }
    .gh-prev .hot { color:var(--bad-text, #dc2626); font-weight:700; }
    .gh-prev .row { display:flex; justify-content:space-between; gap:8px; padding:2px 0; }

    /* Right rail */
    .gh-rail { display:flex; flex-direction:column; gap:16px; position:sticky; top:20px; }
    .gh-rail .gh-card { margin:0; }
    .gh-rail .gh-due { background:#feedf1; border-color:#fbd5de; }
    .gh-rail h4 { display:flex; align-items:center; justify-content:space-between; gap:10px;
        font-size:14px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .gh-rail h4 .t { display:inline-flex; align-items:center; gap:8px; }
    .gh-rail h4 svg { width:17px; height:17px; flex:none; }
    .gh-rail h4 a { font-size:11.5px; font-weight:700; color:var(--accent-blue, #2563eb); text-decoration:none; }
    .gh-rail .hint { font-size:11.5px; color:var(--text-muted); margin:0 0 11px; }
    .gh-line { display:flex; gap:11px; align-items:flex-start; padding:9px 0; border-top:1px solid var(--border-color); }
    .gh-line:first-of-type { border-top:0; }
    .gh-line .tile { width:30px; height:30px; border-radius:8px; flex:none; display:flex; align-items:center;
        justify-content:center; color:var(--t); background:color-mix(in srgb, var(--t) 13%, transparent); }
    .gh-line .tile svg { width:15px; height:15px; }
    .gh-line .m { min-width:0; flex:1 1 auto; }
    .gh-line .m b { display:block; font-size:12.5px; font-weight:700; color:var(--text-primary); line-height:1.35; }
    .gh-line .m span { display:block; font-size:11px; color:var(--text-muted); }
    .gh-line .at { font-size:11px; font-weight:700; white-space:nowrap; text-align:right; flex:none; color:var(--bad-text, #dc2626); }
    .gh-line .at i { display:block; font-style:normal; font-weight:600; color:var(--text-muted); }
    .gh-line .at.cool { color:var(--text-muted); font-weight:600; }
    .gh-none { font-size:12.5px; color:var(--text-muted); line-height:1.6; }

    @media (max-width: 1200px) { .gh-layout { grid-template-columns:1fr; } .gh-rail { position:static; }
        .gh-deep { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 820px) { .gh-tiles { grid-template-columns:1fr; } .gh-deep { grid-template-columns:1fr; } }
</style>
@endpush


<div class="gh-layout">
    <div>
        <div class="gh-tiles">
            {{-- Tile colours sampled from her file: #6e00fa, #fe6e00, #00a35f. --}}
            <a class="gh-tile" style="--c:#6e00fa; --s:#089454;" href="{{ route('professional.calendar.index') }}">
                <span class="ic">{!! $svg('cal') !!}</span>
                <span class="m">
                    <span class="n">{{ $stats['upcoming'] }}</span>
                    <span class="l">Upcoming Events</span>
                    <span class="s">
                        @if($stats['next_event'])
                            Next event {{ $stats['next_event']->humanAgo() }}
                        @else
                            Nothing booked ahead
                        @endif
                    </span>
                </span>
                <span class="go">{!! $svg('right') !!}</span>
            </a>

            <a class="gh-tile" style="--c:#fe6e00; --s:#d77100;" href="{{ route('professional.agreements.index') }}">
                <span class="ic">{!! $svg('doc') !!}</span>
                <span class="m">
                    <span class="n">{{ $stats['contracts'] }}</span>
                    <span class="l">Pending Contracts</span>
                    <span class="s">
                        {{ $stats['to_sign'] }} {{ $stats['to_sign'] === 1 ? 'needs' : 'need' }} your signature
                    </span>
                </span>
                <span class="go">{!! $svg('right') !!}</span>
            </a>

            <a class="gh-tile" style="--c:#00a35f; --s:#2563eb;" href="{{ route('professional.chat.index') }}">
                <span class="ic">{!! $svg('chat') !!}</span>
                <span class="m">
                    <span class="n">{{ $stats['waiting'] }}</span>
                    {{-- Her card says "Unread Messages". No message on GigResource
                         carries a read state, so this counts the threads where the
                         client spoke last, which is the thing that needs answering. --}}
                    <span class="l">Threads Awaiting You</span>
                    <span class="s">of {{ $stats['waiting_threads'] }} {{ \Illuminate\Support\Str::plural('conversation', $stats['waiting_threads']) }}</span>
                </span>
                <span class="go">{!! $svg('right') !!}</span>
            </a>
        </div>

        <div class="gh-card">
            <h3>
                <span class="t"><span class="ic">{!! $svg('gigs') !!}</span> Recent Gigs &amp; Contracts</span>
                <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}">View All Gigs &rarr;</a>
            </h3>

            @if($gigs->count())
                {{-- Her column widths, measured off the 28 October file:
                     the card is 906px wide there and the headings sit at
                     +0 / +241 / +332 / +488 / +720 / +819. --}}
                {{-- Scrolls sideways rather than spilling. The column widths
                     below are measured off a 906px card, and table-layout
                     fixed holds them, so on a phone the last two columns and
                     their buttons simply hung outside the card. Every other
                     wide table on this site is in a scroller; this one was
                     not. --}}
                <div class="gh-tablewrap">
                <table class="gh-table">
                    <colgroup>
                        <col style="width:28.4%;"><col style="width:10.0%;"><col style="width:17.3%;">
                        <col style="width:25.5%;"><col style="width:11.0%;"><col style="width:7.8%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Event / Client</th><th>Type</th><th>Status</th>
                            <th>Key Dates</th><th>Amount</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($gigs as $gig)
                        @php
                            $event = $gig->event;
                            $tint  = ['#2563eb', '#7c3aed', '#db2777', '#d97706', '#059669'][crc32((string) $gig->id) % 5];

                            [$type, $typeIcon, $typeTint] = match ($gig->status) {
                                'completed'   => ['Completed', 'tick',  '#059669'],
                                'in_progress' => ['Booking',   'gigs',  '#2563eb'],
                                'confirmed'   => ['Booking',   'gigs',  '#2563eb'],
                                default       => ['Proposal',  'doc',   '#7c3aed'],
                            };

                            /*
                             * Row 133 — one source for the badge, shared with
                             * the Contracts tab, so the two tabs of one page
                             * cannot label the same gig differently. They did,
                             * and writing a second map here is how that
                             * happened.
                             */
                            [$state, , $stateKey] = \App\Support\GigStatus::for($gig);
                            // Sampled from her file: amber #d77100, green
                            // #089454, blue #002efa, slate #003365.
                            $stateTint = [
                                'awaiting_client'  => '#d77100',
                                'booked'           => '#089454',
                                'payment_secured'  => '#089454',
                                'work_in_progress' => '#002efa',
                                'awaiting_payment' => '#d77100',
                                'paid'             => '#089454',
                                'cancelled'        => '#dc2626',
                            ][$stateKey] ?? '#003365';
                        @endphp
                        <tr>
                            <td>
                                <div class="gh-who">
                                    {{-- No event on this platform stores an image. A light
                                         tile with the page's own icon, rather than a
                                         saturated block of colour shouting down the row. --}}
                                    <span class="gh-thumb" style="--t: {{ $tint }};">{!! $svg('cal') !!}</span>
                                    <span>
                                        <b>{{ $event?->title ?? 'Untitled event' }}</b>
                                        <span>{{ $gig->client?->name ?: 'Client not named' }}</span>
                                    </span>
                                </div>
                            </td>

                            {{-- Her Type column is an icon and a word, with no
                                 pill behind it. Only Status is a pill, which is
                                 what makes the two columns readable apart. --}}
                            <td>
                                <span class="gh-type" style="--t: {{ $typeTint }};">
                                    {!! $svg($typeIcon) !!} {{ $type }}
                                </span>
                            </td>

                            {{-- Her status pill carries its own mark: a clock
                                 while somebody is waiting, a tick once it is
                                 settled. --}}
                            @php
                                $stateIcon = match ($stateKey) {
                                    'awaiting_client', 'awaiting_payment' => 'clock',
                                    'cancelled'                           => 'alert',
                                    default                               => 'tick',
                                };
                            @endphp
                            <td>
                                <span class="gh-chip" style="background: {{ $stateTint }}1f; color: {{ $stateTint }};">
                                    {!! $svg($stateIcon) !!} {{ $state }}
                                </span>
                            </td>

                            {{-- Two lines, the way hers reads: the date, then
                                 how far off it is. "Updated 2 hours ago" on a
                                 third line pushed every row to triple height
                                 and told nobody anything they could act on. --}}
                            <td class="gh-dates">
                                @if($event?->starts_at)
                                    <b>{!! $svg('cal') !!} Event: {{ $on($event->starts_at) }}</b>
                                    <span class="{{ $event->starts_at->isFuture() ? 'ok' : 'hot' }}">
                                        {!! $svg('clock') !!} {{ $event->starts_at->humanAgo() }}
                                    </span>
                                @else
                                    <b>No event date</b>
                                    <span>Updated {{ $gig->updated_at?->humanAgo() }}</span>
                                @endif
                            </td>

                            <td class="gh-amt">{{ $gig->price ? $cash($gig->price) : 'No figure' }}</td>

                            <td>
                                <span class="gh-acts">
                                    @if($event)
                                        <a class="gh-btn" href="{{ route('professional.gigs.show', $event) }}">View</a>
                                        <a class="gh-kebab" href="{{ route('professional.gigs.show', $event) }}"
                                           aria-label="Open {{ $event->title }}">&#8942;</a>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <p class="gh-none">No gigs yet. One appears here as soon as a client books you.</p>
            @endif
        </div>

        <div class="gh-card">
            <h3>
                <span class="t"><span class="ic">{!! $svg('hist') !!}</span>
                    <span>What Happens If You Click a Gig?
                        <small>Clicking any event opens a page with the tools to run that specific job.</small>
                    </span>
                </span>
            </h3>

            <div class="gh-deep">
                <div>
                    <h5>{!! $svg('cal') !!} Event Details</h5>
                    <p>See client info, event date, location and service details.</p>
                    <div class="gh-prev">
                        @if($gigs->first()?->event)
                            <b>{{ $gigs->first()->event->title }}</b>
                            <span>{{ $on($gigs->first()->event->starts_at) }}</span>
                            <span>{{ $gigs->first()->event->location ?: 'Location not set' }}</span>
                        @else
                            Nothing to show yet.
                        @endif
                    </div>
                </div>

                <div>
                    <h5>{!! $svg('pen') !!} Agreement Workspace</h5>
                    <p>Review, negotiate and e-sign the agreement.</p>
                    <div class="gh-prev">
                        <b>Agreement status</b>
                        @if($stats['to_sign'] > 0)
                            <span class="hot">{{ $stats['to_sign'] }} awaiting your signature</span>
                        @elseif($stats['contracts'] > 0)
                            <span>{{ $stats['contracts'] }} in progress, none on you</span>
                        @else
                            <span>Nothing outstanding</span>
                        @endif
                    </div>
                </div>

                <div>
                    <h5>{!! $svg('chat') !!} Messages</h5>
                    <p>Keep all client communication in one place.</p>
                    <div class="gh-prev">
                        <b>Threads awaiting you</b>
                        <span>{{ $stats['waiting'] }} of {{ $stats['waiting_threads'] }}</span>
                    </div>
                </div>

                <div>
                    <h5>{!! $svg('cash') !!} Payments &amp; Invoices</h5>
                    <p>Track deposits, balances and payouts.</p>
                    <div class="gh-prev">
                        <span class="row"><span>Earned, after fees</span><b>{{ $cash($money['earned']) }}</b></span>
                        <span class="row"><span>Paid out</span><b>{{ $cash($money['withdrawn']) }}</b></span>
                        <span class="row"><span>Available</span><b>{{ $cash($money['available']) }}</b></span>
                    </div>
                </div>

                <div>
                    <h5>{!! $svg('clock') !!} Calendar</h5>
                    <p>See how this event fits your schedule.</p>
                    <div class="gh-prev">
                        <b>Next booked event</b>
                        @if($stats['next_event'])
                            <span>{{ $on($stats['next_event']) }}</span>
                            <span>{{ $stats['next_event']->humanAgo() }}</span>
                        @else
                            <span>Nothing booked ahead</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <aside class="gh-rail">
        {{-- Tinted, as hers is: the one card on this page that is about a
             clock running out should not look like the rest. --}}
        <div class="gh-card gh-due">
            <h4>
                <span class="t">{!! $svg('clock', 'style="color:#dc2626;"') !!} Upcoming Deadlines</span>
                <a href="{{ route('professional.priority.index') }}">View All &rarr;</a>
            </h4>
            <p class="hint">Items that need your attention soon.</p>

            @forelse($deadlines as $item)
                <div class="gh-line" style="--t: {{ $toneTint[$item['tone']] ?? '#64748b' }};">
                    <span class="tile">{!! $svg($kindIcon[$item['kind']] ?? 'doc') !!}</span>
                    <span class="m">
                        <b>{{ $item['title'] }}</b>
                        @if($item['subject'])<span>{{ $item['subject'] }}</span>@endif
                    </span>
                    <span class="at">
                        Due {{ $on($item['due']) }}
                        <i>{{ $item['due']->humanAgo() }}</i>
                    </span>
                </div>
            @empty
                <p class="gh-none">Nothing with a date on it.</p>
            @endforelse
        </div>

        <div class="gh-card">
            <h4>
                <span class="t">{!! $svg('hist') !!} Recent Activity</span>
                <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}">View All &rarr;</a>
            </h4>
            <p class="hint">Latest updates across your gigs and messages.</p>

            @forelse($activity as $row)
                @php
                    /*
                     * Her rows are coloured by what happened, not all one
                     * blue: money green, paperwork amber, a cancellation
                     * red, a message blue.
                     */
                    [$aTint, $aIcon] = match (true) {
                        str_contains($row['what'], 'cancelled')  => ['#dc2626', 'alert'],
                        str_contains($row['what'], 'completed')  => ['#059669', 'tick'],
                        str_contains($row['what'], 'confirmed')  => ['#059669', 'tick'],
                        str_contains($row['what'], 'signed')     => ['#d97706', 'pen'],
                        str_contains($row['what'], 'greement')   => ['#d97706', 'pen'],
                        str_contains($row['what'], 'asked')      => ['#2563eb', 'chat'],
                        default                                  => ['#2563eb', 'gigs'],
                    };
                @endphp
                <div class="gh-line" style="--t: {{ $aTint }};">
                    <span class="tile">{!! $svg($aIcon) !!}</span>
                    <span class="m">
                        <b>{{ $row['what'] }}</b>
                        @if($row['subject'])<span>{{ $row['subject'] }}</span>@endif
                    </span>
                    <span class="at cool">{{ $row['at']?->humanAgo() }}</span>
                </div>
            @empty
                <p class="gh-none">Nothing has happened on your gigs yet.</p>
            @endforelse
        </div>
    </aside>
</div>
