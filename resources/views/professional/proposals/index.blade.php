@extends('layouts.professional')

@section('title', 'Proposals & Bids')
@section('page-title', 'Proposals & Bids')

{{-- Khadijah's 28 October screen, "My Proposals / Bids".

     Three figures on her screen have nothing behind them on this platform and
     are not invented here:

       Est. Margin / Est. profit   nothing records what a job costs this
                                   professional to deliver, so the column
                                   carries the one deduction that is known,
                                   the platform fee, and what is left after it.

       Viewed / Not Viewed         nothing records a client opening a
                                   proposal. What is recorded is whether they
                                   have said anything back, which is the thing
                                   that actually moves it on.

       Event photo                 no event on this platform stores an image,
                                   so the thumbnail is a tile, not a stock
                                   picture of somebody else's wedding. --}}

@push('styles')
<style>
    .pp-head { display:flex; justify-content:space-between; align-items:flex-start; gap:24px; flex-wrap:wrap; margin-bottom:18px; }
    .pp-head > div:first-child { flex:1 1 auto; min-width:0; }
    .pp-head h1 { font-size:25px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .pp-head p { font-size:13px; color:var(--text-muted); margin:0; max-width:760px; }
    .pp-head-acts { display:flex; gap:10px; flex-wrap:wrap; }

    .pp-btn { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700;
        padding:9px 16px; border-radius:9px; text-decoration:none; background:var(--accent-blue, #2563eb);
        color:#fff; border:1px solid var(--accent-blue, #2563eb); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .pp-btn svg { width:15px; height:15px; }
    .pp-ghost { background:var(--bg-card); color:var(--accent-blue, #2563eb); border-color:var(--border-color); }
    .pp-ghost:disabled { color:var(--text-muted); opacity:.55; cursor:default; }
    .pp-clear { font-size:12.5px; font-weight:700; color:var(--accent-blue, #2563eb);
        text-decoration:none; white-space:nowrap; background:none; border:0; cursor:pointer; font-family:inherit; }

    .pp-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:16px 18px; margin-bottom:16px; }

    /* Four cards: icon in a tinted box, then the label, the figure, the note. */
    .pp-sums { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .pp-sum { display:flex; gap:13px; align-items:flex-start; text-decoration:none;
        border:1px solid var(--border-color); border-radius:13px; padding:15px 16px; background:var(--bg-card); }
    .pp-sum.on { border-color:var(--c); background:color-mix(in srgb, var(--c) 7%, var(--bg-card)); }
    .pp-sum .ic { width:38px; height:38px; border-radius:10px; flex:none; display:flex;
        align-items:center; justify-content:center; color:var(--c);
        background:color-mix(in srgb, var(--c) 13%, transparent); }
    .pp-sum .ic svg { width:19px; height:19px; }
    .pp-sum .k { display:block; font-size:12.5px; font-weight:800; color:var(--c); }
    .pp-sum .v { display:block; font-size:27px; font-weight:800; color:var(--text-primary); line-height:1.15; margin:1px 0 3px; }
    .pp-sum .n { display:block; font-size:11px; color:var(--text-muted); line-height:1.45; }

    .pp-filters { display:flex; gap:9px; align-items:center; flex-wrap:wrap; }
    .pp-search { position:relative; flex:1 1 210px; min-width:180px; }
    .pp-search svg { position:absolute; left:11px; top:50%; transform:translateY(-50%);
        width:15px; height:15px; color:var(--text-muted); pointer-events:none; }
    .pp-search .pp-in { padding-left:33px; }
    .pp-in { width:100%; font:inherit; font-size:12.5px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }
    select.pp-in { flex:0 1 auto; width:auto; min-width:124px; max-width:168px; }
    .pp-filters .ss-host { flex:0 1 auto; min-width:124px; max-width:168px; }

    /* Tabs: a tinted pill each, the open one underlined. */
    .pp-tabs { display:flex; gap:6px; flex-wrap:wrap; margin:12px 0 0; }
    .pp-tab { display:inline-flex; align-items:center; gap:6px; padding:7px 13px 8px; border-radius:9px;
        font-size:12.5px; font-weight:700; text-decoration:none; color:var(--text-secondary, var(--text-muted));
        background:color-mix(in srgb, var(--c) 9%, transparent); border-bottom:2.5px solid transparent; white-space:nowrap; }
    .pp-tab svg { width:14px; height:14px; color:var(--c); }
    .pp-tab.on { color:var(--c); border-bottom-color:var(--c); background:color-mix(in srgb, var(--c) 14%, transparent); }

    .pp-bulk { display:flex; align-items:center; gap:10px; flex-wrap:wrap;
        padding:11px 0 13px; border-bottom:1px solid var(--border-color); margin-bottom:4px; }
    .pp-bulk b { font-size:12.5px; font-weight:800; color:var(--text-primary); }
    .pp-bulk .sp { flex:1 1 auto; }

    .pp-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .pp-table th { text-align:left; font-size:10.5px; font-weight:800; color:var(--text-muted);
        padding:10px 8px; border-bottom:1px solid var(--border-color); line-height:1.3; }
    .pp-table th .as { display:block; font-weight:600; font-size:9px; opacity:.8; }
    .pp-table td { padding:12px 8px; border-bottom:1px solid var(--border-color); vertical-align:top; color:var(--text-primary); }
    .pp-table tr.detail td { background:var(--bg-page, var(--bg-card)); border-bottom:2px solid var(--border-color); }
    .pp-table td:first-child, .pp-table th:first-child { padding-left:2px; padding-right:2px; width:26px; }
    /* Her column widths. Fixed, because the client column has to stay wide
       enough for the name and the place to sit side by side: left to itself
       the browser squeezes it and every fact wraps onto a line of its own. */
    .pp-table { table-layout:fixed; min-width: 760px; }
    /* Scrolls sideways rather than spilling. The column widths are fixed and
       measured for a desktop card, so on a phone the table simply ran off the
       page: 1231px of it on a 375px screen. The wrapper and its column need a
       zero minimum or the table's own drags them both wide again. */
    .pp-tablewrap { overflow-x: auto; min-width: 0; }
    .pp-table col.c-who  { width:300px; }
    .pp-table col.c-sm   { width:100px; }
    .pp-table col.c-md   { width:118px; }
    .pp-table col.c-next { width:106px; }
    .pp-table col.c-act  { width:134px; }

    .pp-who { display:flex; gap:11px; align-items:flex-start; min-width:230px; }
    .pp-thumb { width:52px; height:52px; border-radius:9px; flex:none; color:#fff; font-size:16px; font-weight:800;
        display:flex; align-items:center; justify-content:center; }
    .pp-who a.t { display:block; font-size:13px; font-weight:800; color:var(--accent-blue, #2563eb);
        text-decoration:none; line-height:1.3; margin-bottom:3px; }
    .pp-facts { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1px 10px; }
    .pp-facts span { display:flex; align-items:center; gap:4px; font-size:10.5px; color:var(--text-muted);
        min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .pp-facts svg { width:11px; height:11px; flex:none; }
    .pp-chips { display:flex; gap:4px; flex-wrap:wrap; margin-top:5px; }
    .pp-tag { font-size:9.5px; font-weight:800; padding:3px 8px; border-radius:5px; white-space:nowrap; }

    .pp-price { font-size:16px; font-weight:800; line-height:1.2; }
    .pp-sub { display:block; font-size:10.5px; color:var(--text-muted); margin-top:2px; }
    .pp-svcs { display:flex; gap:4px; flex-wrap:wrap; margin-top:5px; }
    .pp-svcs i { font-style:normal; font-size:9.5px; font-weight:700; padding:2px 5px; border-radius:5px;
        background:var(--bg-page, rgba(128,128,128,.08)); border:1px solid var(--border-color); color:var(--text-muted); }

    .pp-chip { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:800;
        padding:4px 10px; border-radius:20px; white-space:nowrap; }
    .pp-chip svg { width:12px; height:12px; }

    .pp-when b { display:block; font-size:12px; font-weight:700; }
    .pp-ago { display:block; font-size:11px; color:var(--text-muted); }
    .pp-what { display:block; font-size:10.5px; color:var(--text-muted); margin-top:3px; line-height:1.4; }

    .pp-exp { display:flex; gap:5px; align-items:flex-start; font-size:11.5px; font-weight:800; line-height:1.35; }
    .pp-exp svg { width:13px; height:13px; flex:none; margin-top:1px; }
    .pp-exp-date { display:block; font-size:10.5px; font-weight:600; }
    .pp-muted { color:var(--text-muted); font-weight:500; }

    .pp-next b { display:block; font-size:11.5px; font-weight:800; line-height:1.35; }
    .pp-next span { display:block; font-size:10.5px; color:var(--text-muted); margin-top:2px; line-height:1.4; }

    .pp-fee b { display:block; font-size:15px; font-weight:800; line-height:1.2; }
    .pp-bar { height:5px; border-radius:3px; background:var(--border-color); margin-top:6px; overflow:hidden; max-width:92px; }
    .pp-bar i { display:block; height:100%; background:#10b981; }

    .pp-acts { display:flex; gap:4px; align-items:flex-start; flex-wrap:wrap; }
    .pp-acts .pp-btn { padding:6px 8px; font-size:10.5px; }
    .pp-acts .pp-btn svg { width:12px; height:12px; }
    .pp-acts .pp-chev { margin-left:auto; }
    .pp-kebab { width:26px; height:28px; border-radius:7px; border:1px solid var(--border-color);
        background:var(--bg-card); color:var(--text-muted); cursor:pointer; font-size:14px; line-height:1; padding:0; }
    .pp-chev { background:none; border:0; cursor:pointer; color:var(--text-muted); padding:2px 4px; font-family:inherit; }
    .pp-chev svg { width:15px; height:15px; }
    .pp-menu { position:relative; }
    .pp-menu ul { position:absolute; right:0; top:32px; z-index:30; min-width:178px; margin:0; padding:5px;
        list-style:none; background:var(--bg-card); border:1px solid var(--border-color);
        border-radius:10px; box-shadow:0 10px 26px rgba(0,0,0,.14); }
    .pp-menu ul[hidden] { display:none; }
    .pp-menu li button, .pp-menu li a { display:block; width:100%; text-align:left; font:inherit; font-size:12px;
        font-weight:600; padding:7px 9px; border:0; border-radius:7px; background:none; color:var(--text-primary);
        text-decoration:none; cursor:pointer; }
    .pp-menu li button:hover, .pp-menu li a:hover { background:var(--bg-page, rgba(128,128,128,.1)); }

    /* The opened row: four panels, in her order. */
    .pp-detail { display:grid; grid-template-columns:repeat(auto-fit, minmax(235px,1fr)); gap:18px; padding:4px 0 6px; }
    .pp-detail h4 { display:flex; align-items:center; gap:7px; font-size:12px; font-weight:800;
        color:var(--text-primary); margin:0 0 9px; }
    .pp-detail h4 svg { width:14px; height:14px; color:var(--accent-blue, #2563eb); }
    .pp-detail p { font-size:12px; margin:0 0 5px; line-height:1.55; }
    .pp-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:800;
        color:var(--accent-blue, #2563eb); text-decoration:none; margin-top:6px; }
    .pp-alert { border-left:3px solid #ef4444; background:rgba(239,68,68,0.07); border-radius:9px; padding:11px 13px; }
    .pp-alert b { display:flex; align-items:center; gap:7px; font-size:12.5px; font-weight:800; color:#b91c1c; margin-bottom:5px; }
    .pp-alert b svg { width:14px; height:14px; }
    .pp-alert span { font-size:11.5px; color:var(--text-secondary, var(--text-muted)); line-height:1.55; }
    .pp-quote { background:var(--bg-card); border:1px solid var(--border-color); border-radius:9px; padding:10px 12px; }
    .pp-ver { display:flex; gap:9px; padding-bottom:9px; }
    .pp-ver .dot { width:9px; height:9px; border-radius:50%; flex:none; margin-top:4px;
        background:var(--accent-blue, #2563eb); }
    .pp-ver .dot.old { background:var(--border-color); }
    .pp-ver .m { min-width:0; flex:1 1 auto; }
    .pp-ver .m b { display:flex; justify-content:space-between; gap:12px; font-size:12px; font-weight:800; }
    .pp-ver .m span { display:block; font-size:11px; color:var(--text-muted); }
    .pp-row { display:flex; justify-content:space-between; align-items:center; gap:12px;
        font-size:12px; padding:7px 0; border-bottom:1px solid var(--border-color); }
    .pp-row:last-child { border-bottom:0; }
    .pp-row b { font-weight:800; white-space:nowrap; }
    .pp-view { font-size:11px; font-weight:700; padding:3px 11px; border-radius:6px; text-decoration:none;
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 11%, transparent); color:var(--accent-blue, #2563eb); }

    .pp-foot { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;
        margin-top:13px; font-size:12px; }
    .pp-foot-r { display:flex; align-items:center; gap:11px; }
    /* The shared paginator prints its own "showing x to y of z results". The
       footer already says it on the left, in proposals rather than results. */
    .pp-foot .grpag-info { display:none; }
    .pp-none { text-align:center; padding:34px 18px; }
    .pp-none h2 { font-size:16px; font-weight:800; color:var(--text-primary); margin:0 0 6px; }
    .pp-none p { font-size:13px; color:var(--text-muted); margin:0 0 15px; }
    .pp-note { font-size:11px; color:var(--text-muted); margin:10px 2px 0; line-height:1.55; }

    @media (max-width: 1500px) { .pp-table { font-size:12px; } .pp-table td, .pp-table th { padding-left:6px; padding-right:6px; } }
    @media (max-width: 1100px) { .pp-sums { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 900px) {
        .pp-table thead { display:none; }
        .pp-table td { display:block; border:0; padding:3px 0; }
        .pp-table tr { display:block; border-bottom:1px solid var(--border-color); padding:12px 0; }
    }
</style>
@endpush

@section('content')
@php
    $money  = fn ($v) => '$' . number_format((float) $v, 0);
    $tz     = config('app.display_timezone');
    $onDate = fn ($d) => $d?->timezone($tz)->format('M j, Y');

    /*
     * Where the proposal stands. "In Negotiation" is not a status the database
     * keeps: it is an open proposal the client has already written back about,
     * which is a different thing from one nobody has touched.
     */
    $statusOf = function ($booking) use ($replied) {
        return match (true) {
            $booking->status === 'cancelled'   => ['Declined',      '#dc2626'],
            $booking->status === 'completed'   => ['Completed',     '#7c3aed'],
            $booking->status === 'in_progress' => ['In progress',   '#2563eb'],
            $booking->status === 'confirmed'   => ['Accepted',      '#059669'],
            $replied->contains($booking->id)   => ['In Negotiation', '#2563eb'],
            default                            => ['Pending',       '#d97706'],
        };
    };

    $icons = [
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check'  => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'alert'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'warn'   => '<path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'undo'   => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>',
        'user'   => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'pin'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'group'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'help'   => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'box'    => '<path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>',
        'back'   => '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-1"/>',
        'down'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'up'     => '<path d="m18 15-6-6-6 6"/>',
        'chev'   => '<path d="m6 9 6 6 6-6"/>',
        'eye'    => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'list'   => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'tag'    => '<path d="M20.6 13.4 12 22l-9-9V3h10z"/><path d="M7 7h.01"/>',
    ];

    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($icons[$n] ?? '') . '</svg>';

    $tabs = [
        'all'       => ['All',               $stats['all'],       '#2563eb', 'list'],
        'mine'      => ['Needs My Response', $stats['mine'],      '#dc2626', 'chat'],
        'theirs'    => ['Awaiting Client',   $stats['theirs'],    '#7c3aed', 'clock'],
        'accepted'  => ['Accepted',          $stats['accepted'],  '#059669', 'check'],
        'declined'  => ['Declined',          $stats['declined'],  '#dc2626', 'alert'],
        'expired'   => ['Expired',           $stats['expired'],   '#ea580c', 'clock'],
        'withdrawn' => ['Withdrawn',         $stats['withdrawn'], '#d97706', 'undo'],
        'archived'  => ['Archived',          $stats['archived'],  '#64748b', 'box'],
    ];

    $link = fn (array $over = []) => route('professional.proposals.index', array_merge(array_filter([
        'tab'      => $tab !== 'all' ? $tab : null,
        'search'   => $search ?: null,
        'type'     => $type ?: null,
        'status'   => $status ?: null,
        'activity' => $activity ?: null,
        'period'   => $period ?: null,
        'per'      => $perPage !== 6 ? $perPage : null,
    ]), $over));
@endphp

<div class="pp-head">
    <div>
        <h1>My Proposals / Bids</h1>
        <p>Manage your submitted and received proposals, track client responses, and take the next step toward booked events.</p>
    </div>
    <div class="pp-head-acts">
        {{-- There is no separate professional help guide; the FAQ is the help
             page this platform actually has, so the button goes there. --}}
        <a class="pp-btn pp-ghost" href="{{ route('public.faq') }}">{!! $svg('help') !!} View Help Guide</a>
        <a class="pp-btn" href="{{ route('professional.bidding-board.index') }}">{!! $svg('search') !!} Browse Event Jobs</a>
    </div>
</div>

<div class="pp-sums">
    @foreach ([
        ['all',      'All Proposals',           'All submitted and received proposals',         '#2563eb', 'doc'],
        ['mine',     'Needs My Response',       'Client replied, countered, or requested changes', '#ea580c', 'chat'],
        ['theirs',   'Awaiting Client Response','No client response yet',                       '#7c3aed', 'clock'],
        ['accepted', 'Accepted',                'Ready to move forward',                        '#059669', 'check'],
    ] as [$key, $label, $note, $colour, $icon])
        <a class="pp-sum {{ $tab === $key ? 'on' : '' }}" style="--c: {{ $colour }};"
           href="{{ $link(['tab' => $key === 'all' ? null : $key, 'page' => null]) }}">
            <span class="ic">{!! $svg($icon) !!}</span>
            <span>
                <span class="k">{{ $label }}</span>
                <span class="v">{{ $stats[$key] }}</span>
                <span class="n">{{ $note }}</span>
            </span>
        </a>
    @endforeach
</div>

<div class="pp-card">
    <form method="GET" action="{{ route('professional.proposals.index') }}" class="pp-filters" id="pp-filter-form">
        @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        @if($perPage !== 6)<input type="hidden" name="per" value="{{ $perPage }}">@endif

        <span class="pp-search">
            {!! $svg('search') !!}
            <input type="search" class="pp-in" name="search" value="{{ $search }}"
                   placeholder="Search proposals by event name, client, or location...">
        </span>

        <select class="pp-in" name="type" aria-label="Event type">
            <option value="">All Event Types</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
            @endforeach
        </select>

        <select class="pp-in" name="status" aria-label="Proposal status">
            <option value="">All Proposal Statuses</option>
            <option value="pending"     @selected($status === 'pending')>Pending</option>
            <option value="negotiation" @selected($status === 'negotiation')>In Negotiation</option>
            <option value="accepted"    @selected($status === 'accepted')>Accepted</option>
            <option value="completed"   @selected($status === 'completed')>Completed</option>
            <option value="declined"    @selected($status === 'declined')>Declined</option>
        </select>

        <select class="pp-in" name="activity" aria-label="Client activity">
            <option value="">All Client Activity</option>
            <option value="replied"  @selected($activity === 'replied')>Replied</option>
            <option value="silent"   @selected($activity === 'silent')>No reply yet</option>
            <option value="accepted" @selected($activity === 'accepted')>Accepted</option>
            <option value="declined" @selected($activity === 'declined')>Declined</option>
        </select>

        <select class="pp-in" name="period" aria-label="Time period">
            <option value="">All Time</option>
            <option value="7"   @selected($period === '7')>Last 7 days</option>
            <option value="30"  @selected($period === '30')>Last 30 days</option>
            <option value="90"  @selected($period === '90')>Last 90 days</option>
            <option value="365" @selected($period === '365')>Last 12 months</option>
        </select>

        <span class="pp-btn pp-ghost" style="cursor:default; padding:9px 12px;">
            {!! $svg('filter') !!} More Filters @if($applied)({{ $applied }})@endif
        </span>

        <button type="submit" class="pp-btn">{!! $svg('search') !!} Search</button>

        @if($search !== '' || $applied)
            <a class="pp-clear" href="{{ route('professional.proposals.index', $tab === 'all' ? [] : ['tab' => $tab]) }}">Clear</a>
        @endif
    </form>

    <div class="pp-tabs">
        @foreach($tabs as $key => [$label, $count, $colour, $icon])
            <a class="pp-tab {{ $tab === $key ? 'on' : '' }}" style="--c: {{ $colour }};"
               href="{{ $link(['tab' => $key === 'all' ? null : $key, 'page' => null]) }}">
                {!! $svg($icon) !!} {{ $label }} ({{ $count }})
            </a>
        @endforeach
    </div>
</div>

<div class="pp-card">
@if($bookings->count())
    {{-- Archive and Withdraw act on the rows that are ticked, so they live in
         one form with the table. Export sends the list as it is filtered. --}}
    {{-- Each button names its own action, but the form carries a default so a
         browser that ignores formaction still has somewhere to send it. --}}
    <form method="POST" id="pp-bulk-form" action="{{ route('professional.proposals.archive') }}">
        @csrf
        <input type="hidden" name="ids" id="pp-ids" value="">

        <div class="pp-bulk">
            <input type="checkbox" id="pp-all" aria-label="Select every proposal on this page">
            <b><span id="pp-count">0</span> selected</b>

            <button type="submit" class="pp-btn pp-ghost" id="pp-archive" disabled
                    formaction="{{ route('professional.proposals.archive') }}">
                {!! $svg('box') !!} {{ $tab === 'archived' ? 'Put back' : 'Archive' }}
            </button>
            @if($tab === 'archived')<input type="hidden" name="undo" value="1">@endif

            <button type="submit" class="pp-btn pp-ghost" id="pp-withdraw" disabled
                    formaction="{{ route('professional.proposals.withdraw') }}"
                    onclick="return confirm('Withdraw the selected proposals? The client will no longer see your offer.');">
                {!! $svg('undo') !!} Withdraw
            </button>

            <a class="pp-btn pp-ghost" href="{{ route('professional.proposals.export') }}">
                {!! $svg('down') !!} Export CSV
            </a>

            <span class="sp"></span>
            <button type="button" class="pp-clear" id="pp-none">Clear Selection</button>
        </div>

        <div class="pp-tablewrap">
        <table class="pp-table">
            <colgroup>
                <col style="width:22px;">
                <col class="c-who">
                <col class="c-md">
                <col class="c-sm">
                <col class="c-sm">
                <col class="c-md">
                <col class="c-sm">
                <col class="c-next">
                <col class="c-sm">
                <col class="c-act">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>Event / Client</th>
                    <th>Your Proposal</th>
                    <th>Proposal Status</th>
                    <th>Client Activity</th>
                    <th>Last Activity<span class="as">(as of {{ $onDate(now()) }})</span></th>
                    <th>Expires</th>
                    <th>Next Action</th>
                    {{-- Her column reads "Est. Margin". Nothing on this platform
                         records what a job costs this professional to deliver, so
                         the one deduction that is actually known goes here. --}}
                    <th>After Fees<span class="as">(platform fee taken off)</span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($bookings as $booking)
                @php
                    $d = $detail[$booking->id] ?? [];
                    [$chipLabel, $chipColour] = $statusOf($booking);
                    [$actLabel, $actColour]   = $d['activity'] ?? ['No reply yet', '#64748b'];
                    $services = $d['services'] ?? [];
                    $closes   = $d['closes_at'] ?? null;
                    $tone     = ['red' => '#dc2626', 'amber' => '#d97706', 'blue' => 'var(--accent-blue, #2563eb)'][$d['next_tone'] ?? 'blue'];
                @endphp
                <tr>
                    <td><input type="checkbox" class="pp-pick" value="{{ $booking->id }}"
                               aria-label="Select {{ $booking->event?->title ?? 'this proposal' }}"></td>

                    <td>
                        <div class="pp-who">
                            {{-- No event on this platform stores a photo, so this is a
                                 tile and not a stock picture of somebody else's day. --}}
                            <span class="pp-thumb" style="background: {{ $chipColour }};">
                                {{ strtoupper(mb_substr($booking->event?->title ?: ($booking->client?->name ?: '?'), 0, 1)) }}
                            </span>
                            <span style="min-width:0;">
                                @if($booking->event)
                                    <a class="t" href="{{ route('professional.gigs.show', $booking->event) }}">{{ $booking->event->title }}</a>
                                @else
                                    {{-- No event behind this row, so there is
                                         nothing to open. --}}
                                    <b style="font-size:13px;">Untitled event</b>
                                @endif

                                <span class="pp-facts">
                                    <span>{!! $svg('user') !!} {{ $booking->client?->name ?: 'Client not named' }}</span>
                                    <span>{!! $svg('pin') !!} {{ $booking->event?->location ?: 'Location not set' }}</span>
                                    <span>{!! $svg('cal') !!}
                                        {{ $booking->event?->starts_at
                                            ? $booking->event->starts_at->timezone($tz)->format('D, M j, Y')
                                            : 'Date not set' }}</span>
                                    <span>{!! $svg('group') !!}
                                        {{ $booking->event?->guest_count
                                            ? number_format($booking->event->guest_count) . ' guests'
                                            : 'Guests not stated' }}</span>
                                </span>

                                <span class="pp-chips">
                                    @if($booking->event?->event_type)
                                        <span class="pp-tag" style="background:rgba(236,72,153,.12); color:#be185d;">{{ $booking->event->event_type }}</span>
                                    @endif
                                    <span class="pp-tag" style="background:rgba(37,99,235,.12); color:#1d4ed8;">{{ $d['source'] ?? 'Direct Request' }}</span>
                                </span>
                            </span>
                        </div>
                    </td>

                    <td>
                        <span class="pp-price">{{ $booking->price ? $money($booking->price) : 'No figure' }}</span>
                        <span class="pp-sub">{{ count($services) }} {{ \Illuminate\Support\Str::plural('service', count($services)) }}</span>
                        @if($services)
                            <span class="pp-svcs">
                                @foreach(array_slice($services, 0, 2) as $name)
                                    <i title="{{ $name }}">{{ \Illuminate\Support\Str::limit($name, 11) }}</i>
                                @endforeach
                                @if(count($services) > 2)<i>+{{ count($services) - 2 }} more</i>@endif
                            </span>
                        @endif
                        <span class="pp-sub">Version {{ $d['version'] ?? 1 }}</span>
                    </td>

                    <td>
                        <span class="pp-chip" style="background: {{ $chipColour }}1f; color: {{ $chipColour }};">
                            {!! $svg($booking->status === 'cancelled' ? 'alert' : ($chipLabel === 'In Negotiation' ? 'chat' : ($chipLabel === 'Pending' ? 'clock' : 'check'))) !!}
                            {{ $chipLabel }}
                        </span>
                    </td>

                    <td>
                        {{-- Nothing records a client opening a proposal, so this is
                             what they have actually done: written back, settled it,
                             or nothing at all. --}}
                        <span class="pp-chip" style="background: {{ $actColour }}1f; color: {{ $actColour }};">
                            {!! $svg($actLabel === 'Replied' ? 'chat' : ($actLabel === 'Declined' ? 'alert' : ($actLabel === 'Accepted' ? 'check' : 'clock'))) !!}
                            {{ $actLabel }}
                        </span>
                    </td>

                    <td class="pp-when">
                        <b>{{ $onDate($booking->updated_at) }}</b>
                        <span class="pp-ago">({{ $booking->updated_at?->humanAgo() }})</span>
                        <span class="pp-what">{{ $d['last_what'] ?? '' }}</span>
                    </td>

                    <td>
                        @if($closes && $closes->isPast())
                            <span class="pp-exp" style="color:#dc2626;">
                                {!! $svg('alert') !!}
                                <span>Expired<span class="pp-exp-date">({{ $onDate($closes) }})</span></span>
                            </span>
                        @elseif($closes)
                            @php
                                $daysLeft = (int) ceil(now()->diffInHours($closes) / 24);
                                $urgency  = $daysLeft <= 1 ? '#d97706' : ($daysLeft <= 7 ? '#dc2626' : '#059669');
                            @endphp
                            <span class="pp-exp" style="color: {{ $urgency }};">
                                {!! $svg($daysLeft <= 7 ? 'alert' : 'check') !!}
                                <span>Expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}<span class="pp-exp-date">({{ $onDate($closes) }})</span></span>
                            </span>
                        @else
                            <span class="pp-muted">No expiration set</span>
                        @endif
                    </td>

                    <td class="pp-next">
                        <b style="color: {{ $tone }};">{{ $d['next'] ?? '' }}</b>
                        @if($d['next_note'] ?? null)<span>{{ $d['next_note'] }}</span>@endif
                    </td>

                    <td class="pp-fee">
                        @if($d['net'] ?? null)
                            <b>{{ $money($d['net']) }}</b>
                            <span class="pp-sub">after {{ rtrim(rtrim(number_format($d['rate'], 2), '0'), '.') }}% fee</span>
                            <span class="pp-bar"><i style="width: {{ round($d['net'] / max((float) $booking->price, 0.01) * 100) }}%;"></i></span>
                        @else
                            <span class="pp-muted">No figure</span>
                        @endif
                    </td>

                    <td>
                        <span class="pp-acts">
                            <button type="button" class="pp-btn pp-ghost" data-prop="{{ $booking->id }}">
                                {!! $svg('search') !!} {{ $booking->status === 'cancelled' ? 'View Reason' : 'View Details' }}
                            </button>
                            <span class="pp-menu">
                                <button type="button" class="pp-kebab" data-menu="{{ $booking->id }}" aria-label="More for this proposal">&#8942;</button>
                                <ul id="menu-{{ $booking->id }}" hidden>
                                    @if($booking->event)
                                        <li><a href="{{ route('professional.gigs.show', $booking->event) }}">View event details</a></li>
                                    @endif
                                    @if($booking->status === 'requested')
                                        <li><a href="{{ route('professional.gig-hub.index', ['tab' => 'contracts']) }}">Manage in Gig Hub</a></li>
                                    @endif
                                    <li><a href="{{ route('professional.chat.index') }}">Message the client</a></li>
                                    <li><button type="submit" formaction="{{ route('professional.proposals.archive') }}"
                                                onclick="document.getElementById('pp-ids').value='{{ $booking->id }}';">
                                        {{ $booking->archived_at ? 'Put back' : 'Archive this proposal' }}</button></li>
                                    @if($booking->status === 'requested')
                                        <li><button type="submit" formaction="{{ route('professional.proposals.withdraw') }}"
                                                    onclick="document.getElementById('pp-ids').value='{{ $booking->id }}'; return confirm('Withdraw this proposal?');">
                                            Withdraw this proposal</button></li>
                                    @endif
                                </ul>
                            </span>
                            <button type="button" class="pp-chev" data-prop="{{ $booking->id }}" aria-label="Open this proposal">{!! $svg('chev') !!}</button>
                        </span>
                    </td>
                </tr>

                <tr class="detail" id="prop-{{ $booking->id }}" hidden>
                    <td colspan="10">
                        <div class="pp-detail">
                            @if($d['late'] ?? false)
                                {{-- It sounds impossible and it is not: a request can sit
                                     unanswered past its own date. --}}
                                <div class="pp-alert">
                                    <b>{!! $svg('warn') !!} Date Issue Detected</b>
                                    <span>
                                        This proposal was submitted on {{ $onDate($d['sent_at']) }}, which is
                                        {{ (int) floor($d['event_at']->diffInDays($d['sent_at'])) }} days after the event date
                                        ({{ $onDate($d['event_at']) }}). This event has already taken place.
                                    </span>
                                    @if($booking->event)
                                        <a class="pp-link" href="{{ route('professional.gigs.show', $booking->event) }}">View event details &rarr;</a>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <h4>{!! $svg('chat') !!} Last Message from Client</h4>
                                @if($d['message'] ?? null)
                                    <p class="pp-muted" style="font-size:11px;">{{ $d['message']->created_at->timezone($tz)->format('M j, Y, g:i A') }}</p>
                                    <div class="pp-quote">{{ \Illuminate\Support\Str::limit($d['message']->body, 220) }}</div>
                                    <a class="pp-link" href="{{ route('professional.chat.index') }}">Reply in Messages &rarr;</a>
                                @else
                                    <p class="pp-muted">The client has not written anything yet.</p>
                                @endif
                            </div>

                            <div>
                                <h4>{!! $svg('doc') !!} Proposal Version History</h4>
                                {{-- Nothing keeps copies of past proposals. What is
                                     recorded is when this one was written and whether
                                     it was changed afterwards, so that is what is
                                     listed: no invented earlier versions. --}}
                                @forelse($d['history'] ?? [] as $v)
                                    <div class="pp-ver">
                                        <span class="dot {{ $v['current'] ? '' : 'old' }}"></span>
                                        <span class="m">
                                            <b>
                                                <span>Version {{ $v['v'] }}{{ $v['current'] ? ' (Current)' : '' }}</span>
                                                @if($v['amount'])<span>${{ number_format((float) $v['amount'], 0) }}</span>@endif
                                            </b>
                                            <span>{{ $v['at'] ? 'Submitted ' . $onDate($v['at']) : 'Date not recorded' }}</span>
                                            <span>&bull; {{ $v['note'] }}</span>
                                        </span>
                                    </div>
                                @empty
                                    <p class="pp-muted">Nothing recorded for this one.</p>
                                @endforelse
                            </div>

                            <div>
                                <h4>{!! $svg('tag') !!} Services &amp; Pricing</h4>
                                @forelse($d['lines'] ?? [] as $line)
                                    <div class="pp-row">
                                        <span>{{ $line['label'] }}</span>
                                        <span style="display:flex; align-items:center; gap:10px;">
                                            @if($line['amount'] !== null)
                                                <b>${{ number_format((float) $line['amount'], 0) }}</b>
                                            @else
                                                <b class="pp-muted">No price given</b>
                                            @endif
                                            @if($booking->event)
                                                <a class="pp-view" href="{{ route('professional.gigs.show', $booking->event) }}">View</a>
                                            @endif
                                        </span>
                                    </div>
                                @empty
                                    <p class="pp-muted">No services were listed on this proposal.</p>
                                @endforelse

                                @if($booking->price)
                                    <div class="pp-row" style="border-top:1px solid var(--border-color); margin-top:4px;">
                                        <span style="font-weight:800;">You keep, after the platform fee</span>
                                        <b>{{ $money($d['net']) }}</b>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </form>

    <div class="pp-foot">
        <span class="pp-muted">
            Showing {{ $bookings->firstItem() ?? 0 }}&ndash;{{ $bookings->lastItem() ?? 0 }}
            of {{ $bookings->total() }} {{ \Illuminate\Support\Str::plural('proposal', $bookings->total()) }}
        </span>
        <span class="pp-foot-r">
            {{ $bookings->onEachSide(1)->links() }}
            <form method="GET" action="{{ route('professional.proposals.index') }}">
                @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
                @foreach(['search' => $search, 'type' => $type, 'status' => $status, 'activity' => $activity, 'period' => $period] as $k => $v)
                    @if($v !== '')<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <select class="pp-in" name="per" onchange="this.form.submit()" aria-label="Proposals per page">
                    @foreach([6, 12, 24, 48] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per page</option>
                    @endforeach
                </select>
            </form>
        </span>
    </div>

    <p class="pp-note">
        Estimated margin is not shown. Nothing on GigResource records what a job costs you to deliver, so
        "After Fees" gives the one deduction that is known: the platform fee on your own tier.
        Client Activity is what the client has done, not whether they opened the proposal, which is not recorded.
    </p>
@else
    <div class="pp-none">
        <h2>Nothing here</h2>
        <p>
            @if($tab === 'all')
                Proposals appear here once you bid on a request or a client sends you one.
            @else
                Nothing in this group right now. Try All.
            @endif
        </p>
        <a class="pp-btn" href="{{ route('professional.bidding-board.index') }}">{!! $svg('search') !!} Browse Event Jobs</a>
    </div>
@endif
</div>

<script>
(function () {
    var ids   = document.getElementById('pp-ids');
    var count = document.getElementById('pp-count');
    var picks = Array.prototype.slice.call(document.querySelectorAll('.pp-pick'));

    function sync() {
        var on = picks.filter(function (c) { return c.checked; });
        if (ids)   { ids.value = on.map(function (c) { return c.value; }).join(','); }
        if (count) { count.textContent = on.length; }

        ['pp-archive', 'pp-withdraw'].forEach(function (id) {
            var b = document.getElementById(id);
            if (b) { b.disabled = on.length === 0; }
        });
    }

    picks.forEach(function (c) { c.addEventListener('change', sync); });

    var all = document.getElementById('pp-all');
    if (all) {
        all.addEventListener('change', function () {
            picks.forEach(function (c) { c.checked = all.checked; });
            sync();
        });
    }

    var none = document.getElementById('pp-none');
    if (none) {
        none.addEventListener('click', function () {
            picks.forEach(function (c) { c.checked = false; });
            if (all) { all.checked = false; }
            sync();
        });
    }

    sync();

    /* One row open at a time, so a long list does not become a wall. */
    Array.prototype.slice.call(document.querySelectorAll('[data-prop]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = document.getElementById('prop-' + btn.dataset.prop);
            if (!row) { return; }

            var opening = row.hidden;
            document.querySelectorAll('tr.detail').forEach(function (r) { r.hidden = true; });
            row.hidden = !opening;
        });
    });

    document.querySelectorAll('[data-menu]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var list = document.getElementById('menu-' + btn.dataset.menu);
            var open = list && list.hidden;
            document.querySelectorAll('.pp-menu ul').forEach(function (u) { u.hidden = true; });
            if (list) { list.hidden = !open; }
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.pp-menu ul').forEach(function (u) { u.hidden = true; });
    });
})();
</script>
@endsection
