@extends('layouts.professional')

@section('title', 'Payments & Payouts')
@section('page-title', 'Payments & Payouts')

{{-- Khadijah's 28 October screen.

     Two things on her screen are deliberately worded differently here,
     because the platform does not do them:

       "Payouts are processed automatically based on your settings"
       "No manual withdrawal needed"
                        payouts on GigResource are requested, not scheduled.
                        The button asks for one and the line under it says so.

       "Est. Payout Date ... (Estimated)"
                        nothing schedules a next payout, so there is no date
                        to estimate. A payout that has happened carries its
                        real date; everything else says it is not scheduled. --}}

@push('styles')
<style>
    .pm-head { display:flex; justify-content:space-between; align-items:flex-start; gap:24px; flex-wrap:wrap; margin-bottom:18px; }
    .pm-head > div:first-child { flex:1 1 auto; min-width:0; }
    .pm-head h1 { font-size:25px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .pm-head p { font-size:13px; color:var(--text-muted); margin:0; max-width:700px; }
    .pm-head-acts { display:flex; flex-direction:column; align-items:flex-end; gap:5px; }
    .pm-head-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .pm-head-note { font-size:10.5px; color:var(--text-muted); text-align:right; max-width:230px; line-height:1.45; }

    .pm-btn { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700;
        padding:9px 16px; border-radius:9px; text-decoration:none; background:var(--accent-blue, #2563eb);
        color:#fff; border:1px solid var(--accent-blue, #2563eb); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .pm-btn svg { width:15px; height:15px; }
    .pm-ghost { background:var(--bg-card); color:var(--text-primary); border-color:var(--border-color); }
    .pm-ghost.blue { color:var(--accent-blue, #2563eb); }
    .pm-clear { font-size:12.5px; font-weight:700; color:var(--accent-blue, #2563eb);
        text-decoration:none; white-space:nowrap; }

    .pm-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:16px 18px; margin-bottom:16px; }

    .pm-sums { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:16px; margin-bottom:16px; }
    .pm-sum { display:flex; gap:14px; align-items:flex-start;
        border:1px solid var(--border-color); border-radius:13px; padding:16px; background:var(--bg-card); }
    .pm-sum .ic { width:44px; height:44px; border-radius:11px; flex:none; display:flex;
        align-items:center; justify-content:center; color:var(--c);
        background:color-mix(in srgb, var(--c) 13%, transparent); }
    .pm-sum .ic svg { width:22px; height:22px; }
    .pm-sum .m { min-width:0; flex:1 1 auto; }
    .pm-sum .k { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:600;
        color:var(--text-secondary, var(--text-muted)); line-height:1.3; }
    /* The value and, where there is one, the badge beside it on the same line. */
    .pm-sum .row { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .pm-sum .v { display:block; font-size:24px; font-weight:800; color:var(--text-primary); line-height:1.3; }
    .pm-sum .n { display:block; font-size:11.5px; color:var(--text-muted); line-height:1.5; }
    .pm-pill { display:inline-block; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px;
        background:color-mix(in srgb, var(--c) 14%, transparent); color:var(--c); white-space:nowrap; }

    .pm-info { width:13px; height:13px; flex:none; color:var(--text-muted); cursor:help; }

    /* The tier band: four panels, each its own box, not one flat strip. */
    .pm-tier { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.18fr) minmax(0,1.35fr) minmax(0,.92fr);
        gap:14px; align-items:stretch; }
    .pm-tier > div { padding:14px 16px; border-radius:12px; }
    .pm-tier .p1 { padding-left:2px; }
    .pm-tier .p2 { background:color-mix(in srgb, var(--accent-blue, #2563eb) 6%, transparent);
        border:1px solid color-mix(in srgb, var(--accent-blue, #2563eb) 18%, transparent); }
    .pm-tier .p3 { background:var(--bg-card); border:1px solid var(--border-color); }
    .pm-tier .p4 { background:rgba(16,185,129,.07); border:1px solid rgba(16,185,129,.25); }
    .pm-tier h4 { display:flex; align-items:center; gap:6px; font-size:13px; font-weight:800;
        color:var(--text-primary); margin:0 0 10px; }

    .pm-badge-row { display:flex; align-items:flex-start; gap:13px; }
    .pm-medal { width:58px; height:58px; border-radius:50%; flex:none; display:flex; align-items:center; justify-content:center;
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 12%, transparent); }
    .pm-medal svg { width:34px; height:34px; stroke-width:1.5; }
    .pm-tier .lbl { display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--text-muted); }
    .pm-tier .big { display:block; font-size:20px; font-weight:800; color:var(--text-primary);
        line-height:1.25; margin:1px 0 3px; white-space:nowrap; }
    .pm-tier .sm { display:block; font-size:12px; color:var(--text-muted); line-height:1.5; }
    .pm-since { display:flex; align-items:center; gap:9px; flex-wrap:wrap; margin-top:9px; }
    .pm-live { display:inline-block; font-size:11px; font-weight:700; padding:3px 11px; border-radius:20px;
        background:rgba(16,185,129,.15); color:#047857; }

    .pm-rule { display:flex; gap:8px; align-items:flex-start; font-size:11.5px;
        color:var(--text-secondary, var(--text-muted)); line-height:1.5; margin:0 0 8px; }
    .pm-rule i { width:15px; height:15px; flex:none; margin-top:1px; border-radius:50%;
        background:#059669; color:#fff; display:flex; align-items:center; justify-content:center; }
    .pm-rule i svg { width:9px; height:9px; stroke-width:3.5; }
    .pm-link { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700;
        color:var(--accent-blue, #2563eb); text-decoration:none; }

    /* The sum reads across, the way she wrote it: amount, fee, what is left. */
    .pm-calc { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:0;
        border-top:1px solid var(--border-color); padding-top:10px; }
    .pm-calc > div { padding:0 9px; border-left:1px solid var(--border-color); }
    .pm-calc > div:first-child { border-left:0; padding-left:0; }
    .pm-calc b { display:block; font-size:15px; font-weight:800; color:var(--text-primary); line-height:1.3; white-space:nowrap; }
    .pm-calc .minus { color:#dc2626; }
    .pm-calc .plus { color:#059669; }
    .pm-calc span { display:block; font-size:11px; color:var(--text-muted); line-height:1.45; margin-top:3px; }

    /* Two rows, as her screen has them: the search on its own line, then
       everything that narrows it. One line could not hold both without
       cutting every label in half. */
    .pm-filters { display:flex; flex-direction:column; gap:11px; }
    .pm-filter-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .pm-search { position:relative; flex:1 1 100%; }
    .pm-search svg { position:absolute; left:11px; top:50%; transform:translateY(-50%);
        width:15px; height:15px; color:var(--text-muted); pointer-events:none; }
    .pm-search .pm-in { padding-left:33px; }
    .pm-in { width:100%; font:inherit; font-size:12.5px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }
    select.pm-in { flex:0 1 auto; width:auto; min-width:168px; }
    .pm-filters .pm-btn { padding:9px 16px; }
    /* One control, the way her date range reads, with two real inputs in it. */
    .pm-dates { display:flex; align-items:center; gap:6px; flex:0 0 auto;
        border:1px solid var(--border-color); border-radius:9px; padding:3px 10px;
        background:var(--bg-page, var(--bg-card)); color:var(--text-muted); font-size:12px; }
    .pm-dates svg { width:15px; height:15px; flex:none; }
    .pm-dates input { width:116px; padding:6px 0; font-size:12px; border:0; background:none;
        color:var(--text-primary); font-family:inherit; }
    .pm-dates input:focus { outline:none; }

    .pm-table { width:100%; border-collapse:collapse; font-size:12.5px; table-layout:fixed; }
    .pm-table th { text-align:left; font-size:9.5px; font-weight:800; color:var(--text-muted);
        text-transform:uppercase; letter-spacing:.2px; padding:10px 6px;
        border-bottom:1px solid var(--border-color); line-height:1.4; }
    .pm-table th .as { display:block; font-weight:600; letter-spacing:0; text-transform:none; font-size:10px; opacity:.85; }
    .pm-table th .hd { display:inline-flex; align-items:center; gap:5px; }
    .pm-sort { display:inline-flex; align-items:center; gap:4px; color:inherit; text-decoration:none; }
    .pm-sort svg { width:12px; height:12px; }
    /* The arrow for the way it is NOT sorted stays faint. */
    .pm-sort.newest .up, .pm-sort.oldest .dn { opacity:.3; }
    /* Her rows sit on one middle line: the date beside a three line event
       block reads as a stray caption when it is pinned to the top. */
    .pm-table td { padding:13px 6px; border-bottom:1px solid var(--border-color);
        vertical-align:middle; color:var(--text-primary);
        border-left:1px solid color-mix(in srgb, var(--border-color) 55%, transparent); }
    .pm-table td:first-child, .pm-table th:first-child { border-left:0; }
    .pm-table th { border-left:1px solid color-mix(in srgb, var(--border-color) 55%, transparent); }
    .pm-table tr.open td { border-bottom:0; }
    .pm-table tr.detail td { border-bottom:2px solid var(--border-color); border-left:0;
        box-shadow:inset 3px 0 0 var(--accent-blue, #2563eb);
        background:var(--bg-page, var(--bg-card)); padding-left:14px; }
    /* Money reads down a column, so it is centred under its own heading. */
    .pm-table th:nth-child(n+3):nth-child(-n+8),
    .pm-table td:nth-child(n+3):nth-child(-n+8) { text-align:center; }
    .pm-table th:nth-child(n+3):nth-child(-n+8) > * { justify-content:center; }
    /* Payment status is a chip with a sentence under it, so it reads left. */
    .pm-table th:nth-child(4), .pm-table td:nth-child(4) { text-align:left; }
    .pm-table th:nth-child(4) > * { justify-content:flex-start; }

    .pm-when { font-size:12px; font-weight:700; white-space:nowrap; }
    .pm-what a { display:block; font-size:13px; font-weight:800; color:var(--accent-blue, #2563eb);
        text-decoration:none; line-height:1.3; }
    .pm-what span { display:block; font-size:11px; color:var(--text-muted); line-height:1.5; }
    .pm-chip { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700;
        padding:4px 11px; border-radius:20px; white-space:nowrap; }
    .pm-chip svg { width:12px; height:12px; }
    .pm-sub { display:block; font-size:10.5px; color:var(--text-muted); margin-top:3px; line-height:1.45; }
    .pm-money { font-size:13.5px; font-weight:700; }
    .pm-net { font-size:14px; font-weight:800; color:#059669; }
    .pm-muted { color:var(--text-muted); font-weight:500; }

    .pm-detail { display:grid; grid-template-columns:repeat(auto-fit, minmax(215px,1fr)); gap:0; padding:2px 0 8px; }
    .pm-detail > div { padding:0 16px; border-left:1px solid var(--border-color); }
    .pm-detail > div:first-child { border-left:0; padding-left:0; }
    .pm-detail h5 { display:flex; align-items:center; justify-content:space-between; gap:8px;
        font-size:12.5px; font-weight:800; color:var(--accent-blue, #2563eb); margin:0 0 10px; }
    .pm-line { display:flex; justify-content:space-between; gap:12px; font-size:11.5px;
        color:var(--text-secondary, var(--text-muted)); padding:4px 0; }
    .pm-line b { font-weight:700; color:var(--text-primary); white-space:nowrap; }
    .pm-total { background:rgba(16,185,129,.1); border-radius:7px; padding:7px 10px; margin-top:4px; }
    .pm-total span, .pm-total b { color:#047857; font-weight:800; font-size:12px; }
    .pm-note { font-size:11px; color:var(--text-muted); line-height:1.6; font-style:italic; margin:6px 0 0; }
    .pm-rel a { display:flex; align-items:center; gap:7px; font-size:11.5px; font-weight:700;
        color:var(--accent-blue, #2563eb); text-decoration:none; padding:4px 0; }
    .pm-rel svg { width:13px; height:13px; }
    .pm-tierbox { font-size:11px; color:var(--text-secondary, var(--text-muted)); line-height:1.6; }
    .pm-tierbox .tag { font-size:10px; font-weight:700; padding:2px 9px; border-radius:20px;
        background:rgba(236,72,153,.13); color:#be185d; white-space:nowrap; flex:none; }

    .pm-acts { display:inline-flex; gap:6px; align-items:center; justify-content:flex-end; }
    .pm-open { font:inherit; font-size:11.5px; font-weight:700; padding:7px 11px; border-radius:8px;
        border:1px solid color-mix(in srgb, var(--accent-blue, #2563eb) 25%, transparent);
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 10%, transparent);
        color:var(--accent-blue, #2563eb); cursor:pointer; white-space:nowrap; }
    .pm-chev { width:30px; height:30px; flex:none; border-radius:8px; cursor:pointer; padding:0;
        border:1px solid var(--border-color); background:var(--bg-card);
        color:var(--text-muted); display:inline-flex; align-items:center; justify-content:center; }
    .pm-chev svg { width:14px; height:14px; }

    .pm-foot { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;
        margin-top:13px; font-size:12px; }
    .pm-foot .grpag-info { display:none; }
    .pm-none { text-align:center; padding:34px 18px; }
    .pm-none h2 { font-size:16px; font-weight:800; color:var(--text-primary); margin:0 0 6px; }
    .pm-none p { font-size:13px; color:var(--text-muted); margin:0; }
    .pm-foottext { font-size:11px; color:var(--text-muted); margin:10px 2px 0; line-height:1.6; }

    @media (max-width: 1250px) { .pm-tier { grid-template-columns:repeat(2, minmax(0,1fr)); row-gap:18px; }
        .pm-tier > div:nth-child(3) { border-left:0; padding-left:2px; } }
    @media (max-width: 1100px) { .pm-sums { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 900px) {
        .pm-tier { grid-template-columns:1fr; }
        .pm-tier > div { border-left:0; padding-left:2px; }
        .pm-table thead { display:none; }
        .pm-table td { display:block; border:0; padding:3px 0; }
        .pm-table tr { display:block; border-bottom:1px solid var(--border-color); padding:12px 0; }
    }
</style>
@endpush

@section('content')
@php
    // The sign belongs in front of the currency, not between it and the
    // digits: "$-3,500.00" reads like a typo on a money screen.
    $money = fn ($v) => ((float) $v < 0 ? "−$" : '$') . number_format(abs((float) $v), 2);
    $tz    = config('app.display_timezone');
    $on    = fn ($d) => $d?->timezone($tz)->format('M j, Y');
    $pct   = fn ($r) => rtrim(rtrim(number_format((float) $r, 2), '0'), '.') . '%';

    $icons = [
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'check'  => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'bag'    => '<path d="M12 2a3 3 0 0 0-3 3c0 1 .5 2 1 2.5L6 10c-2 1.5-3 4-3 6a6 6 0 0 0 6 6h6a6 6 0 0 0 6-6c0-2-1-4.5-3-6l-4-2.5c.5-.5 1-1.5 1-2.5a3 3 0 0 0-3-3z"/><path d="M12 12v4"/>',
        'medal'  => '<path d="m8.2 13.5-1.7 7.5L12 18l5.5 3-1.7-7.5" fill="#3b5bdb" stroke="#3b5bdb"/><circle cx="12" cy="9" r="7" fill="#f59e0b" stroke="#d97706"/><path d="m12 5.6 1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3L8.7 8l2.3-.3z" fill="#fff" stroke="none"/>',
        'tick'   => '<path d="m4 12 5 5L20 6"/>',
        'up'     => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
        'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'down'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'gear'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'ban'    => '<circle cx="12" cy="12" r="10"/><path d="m5 5 14 14"/>',
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'pen'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'bill'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 11v6"/><path d="M14 13h-3a1.5 1.5 0 0 0 0 3h2a1.5 1.5 0 0 1 0 3H10"/>',
        'chev'   => '<path d="m6 9 6 6 6-6"/>',
        'chevup' => '<path d="m18 15-6-6-6 6"/>',
        'i'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    ];
    $svg = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($icons[$n] ?? '') . '</svg>';
    $tip = fn (string $t) => '<svg class="pm-info" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round"><title>' . e($t) . '</title>' . $icons['i'] . '</svg>';
@endphp

<div class="pm-head">
    <div>
        <h1>Payments &amp; Payouts</h1>
        <p>Track your earnings, fees, and payouts, all in one place.</p>
    </div>
    <div class="pm-head-acts">
        <div class="pm-head-row">
            <form method="GET" action="{{ route('professional.earnings.index') }}">
                <select class="pm-in" name="window" onchange="this.form.submit()" aria-label="Period">
                    @foreach($windows as $key => $label)
                        {{-- PHP turns the numeric keys into ints, so this has
                             to compare as strings or nothing is ever picked. --}}
                        <option value="{{ $key }}" @selected($window === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <a class="pm-btn pm-ghost" href="{{ route('professional.earnings.export', request()->query()) }}">
                {!! $svg('down') !!} Export
            </a>
            <a class="pm-btn" href="{{ route('professional.transactions.index') }}">
                {!! $svg('gear') !!} Payout Settings
            </a>
        </div>
        {{-- Her line reads "Payouts are processed automatically based on your
             settings". They are not: a payout is requested on the Transactions
             page and paid once it is approved. --}}
        <p class="pm-head-note">Payouts are paid once you request one and it is approved.</p>
    </div>
</div>

<div class="pm-sums">
    <div class="pm-sum" style="--c:#2563eb;">
        <span class="ic">{!! $svg('wallet') !!}</span>
        <span class="m">
            <span class="k">Total Net Earnings {!! $tip('Your completed bookings, less the platform fee on your tier.') !!}</span>
            <span class="row"><span class="v">{{ $money($stats['earned']) }}</span></span>
            <span class="n">Sum of all net earnings from completed and eligible transactions.</span>
        </span>
    </div>

    <div class="pm-sum" style="--c:#059669;">
        <span class="ic">{!! $svg('check') !!}</span>
        <span class="m">
            <span class="k">Paid Out {!! $tip('Payouts already transferred to you.') !!}</span>
            <span class="row"><span class="v">{{ $money($stats['withdrawn']) }}</span></span>
            <span class="n">Transferred to your bank account. Nothing has been paid out yet when this reads zero.</span>
            @if($stats['payoutCount'])
                <span class="pm-pill" style="margin-top:7px;">{{ $stats['payoutCount'] }} {{ \Illuminate\Support\Str::plural('payout', $stats['payoutCount']) }}</span>
            @endif
        </span>
    </div>

    <div class="pm-sum" style="--c:#d97706;">
        <span class="ic">{!! $svg('clock') !!}</span>
        <span class="m">
            <span class="k">Pending Earnings {!! $tip('Bookings agreed but not yet completed and released.') !!}</span>
            <span class="row">
                <span class="v">{{ $money($stats['pending']) }}</span>
                @if($stats['pendingCount'])
                    <span class="pm-pill">{{ $stats['pendingCount'] }} {{ \Illuminate\Support\Str::plural('item', $stats['pendingCount']) }}</span>
                @endif
            </span>
            <span class="n">Earnings not yet paid out. Each one's state is in the table below.</span>
        </span>
    </div>

    <div class="pm-sum" style="--c:#db2777;">
        <span class="ic">{!! $svg('bag') !!}</span>
        <span class="m">
            <span class="k">Available for Payout {!! $tip('Earned, less what has been paid out and what is already requested.') !!}</span>
            <span class="row">
                <span class="v">{{ $money($stats['available']) }}</span>
                @if($stats['requested'] > 0)
                    <span class="pm-pill">{{ $money($stats['requested']) }} requested</span>
                @endif
            </span>
            {{-- Her line says no manual withdrawal is needed. On this platform
                 it is: a payout happens when you ask for one. --}}
            <span class="n">What you can request a payout for right now.</span>
        </span>
    </div>
</div>

<div class="pm-tier" style="margin-bottom:16px;">
    <div class="p1">
        <div class="pm-badge-row">
            <span class="pm-medal">{!! $svg('medal') !!}</span>
            <span>
                <span class="lbl">Your Current Tier {!! $tip('The membership your platform fee comes from.') !!}</span>
                <b class="big">{{ $tier['label'] }} Tier ({{ $pct($tier['rate']) }})</b>
                <span class="sm">{{ $pct($tier['rate']) }} platform fee</span>
                <span class="pm-since">
                    @if($tier['since'])
                        <span class="sm">Active since {{ $on($tier['since']) }}</span>
                    @endif
                    @if($tier['active'])
                        <span class="pm-live">Active</span>
                    @else
                        {{-- No subscription means Starter terms, and the band
                             should say that rather than show a badge nobody
                             bought. --}}
                        <span class="sm">No paid membership, so Starter terms apply.</span>
                    @endif
                </span>
            </span>
        </div>
    </div>

    <div class="p2">
        <h4>How Fee Rates Work {!! $tip('The rules behind every fee on this page.') !!}</h4>
        {{-- Her screen says the rate is locked to the transaction at the moment
             the booking was accepted. GigResource does not store a rate per
             booking, so the rate that applies is the one on your membership
             now. Said plainly here rather than claiming a lock that does not
             exist, on the one screen where that claim is about money. --}}
        <p class="pm-rule"><i>{!! $svg('tick') !!}</i> <span>Every fee on this page uses the rate from your membership today, which is {{ $pct($tier['rate']) }}.</span></p>
        <p class="pm-rule"><i>{!! $svg('tick') !!}</i> <span>Change your membership and the rate changes for every transaction here, past ones included. Nothing is locked to the day a booking was accepted.</span></p>
        <p class="pm-rule"><i>{!! $svg('tick') !!}</i> <span>The fee is charged once, at payout, and on the amount actually earned, which on a cancelled booking is only what the terms let you retain.</span></p>
        <a class="pm-link" href="{{ route('app.membership-plans.index') }}">View tier details &rarr;</a>
    </div>

    <div class="p3">
        <h4>Your Tier Earnings <span style="font-weight:600;color:var(--text-muted);">({{ $windows[$window] ?? 'this period' }})</span></h4>
        <div class="pm-calc">
            <div>
                <b>{{ $money($tier['period']['gross']) }}</b>
                <span>Total service amount<br>(completed bookings)</span>
            </div>
            <div>
                <b class="minus">&minus; {{ $money($tier['period']['fees']) }}</b>
                <span>Total platform fees<br>({{ $pct($tier['rate']) }})</span>
            </div>
            <div>
                <b class="plus">= {{ $money($tier['period']['net']) }}</b>
                <span>Net earnings<br>(paid out + pending)</span>
            </div>
        </div>
    </div>

    <div class="p4">
        @if($tier['next'])
            <h4>Next Tier: {{ $tier['next']['label'] }} ({{ $pct($tier['next']['rate']) }}) {!! $svg('up', 'style="width:14px;height:14px;color:#059669;"') !!}</h4>
            <span class="sm">
                Over {{ strtolower($windows[$window] ?? 'this period') }} you could have paid
                {{ $money($tier['next']['wouldPay']) }} in platform fees instead of
                {{ $money($tier['period']['fees']) }} at {{ $pct($tier['next']['rate']) }}
                (save {{ $money($tier['next']['save']) }}).
            </span>
        @elseif($tier['lowest'])
            <h4>You are on the lowest rate</h4>
            <span class="sm">{{ $tier['label'] }} is {{ $pct($tier['rate']) }}, the least anyone pays. There is no cheaper tier to move to.</span>
        @else
            <h4>Next Tier</h4>
            {{-- With nothing earned in the window there is no saving to
                 measure, and a figure here would be measured on nothing. --}}
            <span class="sm">Nothing completed in this period, so there is no fee to compare against another tier.</span>
        @endif
        <a class="pm-link" style="margin-top:9px;" href="{{ route('app.membership-plans.index') }}">Compare tiers &rarr;</a>
    </div>
</div>

<div class="pm-card">
    <form method="GET" action="{{ route('professional.earnings.index') }}" class="pm-filters">
        @if($window !== '90')<input type="hidden" name="window" value="{{ $window }}">@endif

        <div class="pm-filter-row">
            <span class="pm-search">
                {!! $svg('search') !!}
                <input type="search" class="pm-in" name="search" value="{{ $filters['search'] }}"
                       placeholder="Search by client name, event name, or status...">
            </span>
        </div>

        <div class="pm-filter-row">
        <select class="pm-in" name="booking" aria-label="Booking status">
            <option value="">All Booking Statuses</option>
            @foreach(['requested' => 'Requested', 'confirmed' => 'Confirmed', 'in_progress' => 'In progress',
                      'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $label)
                <option value="{{ $k }}" @selected($filters['booking'] === $k)>{{ $label }}</option>
            @endforeach
        </select>

        <select class="pm-in" name="payment" aria-label="Payment status">
            <option value="">All Payment Statuses</option>
            <option value="paid"       @selected($filters['payment'] === 'paid')>Paid Out</option>
            <option value="pending"    @selected($filters['payment'] === 'pending')>Pending</option>
            <option value="not_earned" @selected($filters['payment'] === 'not_earned')>Not Earned</option>
        </select>

        <select class="pm-in" name="type" aria-label="Event type">
            <option value="">All Event Types</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($filters['type'] === $t)>{{ $t }}</option>
            @endforeach
        </select>

        {{-- One control, two real inputs: her range reads as a single pill. --}}
        <span class="pm-dates">
            {!! $svg('cal') !!}
            <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="From">
            &ndash;
            <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="To">
        </span>

        <button type="submit" class="pm-btn">Apply Filters</button>

        <a class="pm-btn pm-ghost" href="{{ route('professional.earnings.index') }}">Clear</a>

        <a class="pm-btn pm-ghost blue" href="{{ route('professional.earnings.export', request()->query()) }}">
            {!! $svg('down') !!} Export CSV
        </a>
        </div>
    </form>
</div>

<div class="pm-card">
@if($transactions->count())
    <table class="pm-table">
        <colgroup>
            <col style="width:86px;"><col style="width:21%;"><col style="width:94px;">
            <col style="width:116px;"><col style="width:102px;"><col style="width:104px;">
            <col style="width:98px;"><col style="width:102px;"><col style="width:162px;">
        </colgroup>
        <thead>
            <tr>
                <th>
                    <a class="pm-sort {{ $sort }}" href="{{ route('professional.earnings.index',
                        array_merge(request()->query(), ['sort' => $sort === 'newest' ? 'oldest' : 'newest'])) }}"
                       title="{{ $sort === 'newest' ? 'Newest first. Click for oldest first.' : 'Oldest first. Click for newest first.' }}">
                        Date
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path class="up" d="m8 10 4-4 4 4"/><path class="dn" d="m8 14 4 4 4-4"/>
                        </svg>
                    </a>
                </th>
                <th>Event / Client</th>
                <th><span class="hd">Booking<br>Status {!! $tip('What was agreed with the client.') !!}</span></th>
                <th><span class="hd">Payment<br>Status {!! $tip('Where the money has actually got to.') !!}</span></th>
                <th><span class="hd" style="white-space:nowrap;">Client Paid {!! $tip('What the client was charged for your service.') !!}</span><span class="as">(Your Service)</span></th>
                <th><span class="hd" style="white-space:nowrap;">Platform Fee {!! $tip('Taken once, at payout.') !!}</span><span class="as">(Actual Rate)</span></th>
                <th><span class="hd">Net Earnings {!! $tip('What is left for you on this transaction.') !!}</span></th>
                <th><span class="hd">Payout Date {!! $tip('The date a payout was made. Nothing schedules a next one, so nothing is estimated.') !!}</span></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transactions as $t)
            @php
                $b = $t['booking'];
                [$bookLabel, $bookColour] = match ($b->status) {
                    'completed'   => ['Completed',   '#7c3aed'],
                    'confirmed'   => ['Confirmed',   '#059669'],
                    'in_progress' => ['In progress', '#2563eb'],
                    'cancelled'   => ['Cancelled',   '#dc2626'],
                    default       => ['Requested',   '#d97706'],
                };
                [$payColour, $payIcon] = match ($t['state']) {
                    'Paid Out'   => ['#059669', 'check'],
                    'Pending'    => ['#d97706', 'clock'],
                    default      => ['#64748b', 'ban'],
                };
            @endphp
            <tr class="open">
                <td class="pm-when">{{ $on($t['date']) }}</td>

                <td class="pm-what">
                    @if($b->event)
                        <a href="{{ route('professional.gigs.show', $b->event) }}">{{ $b->event->title }}</a>
                    @else
                        {{-- No event behind this row, so there is nothing to
                             open: a span, not a link that goes nowhere. --}}
                        <span style="font-size:13px;font-weight:800;">Untitled event</span>
                    @endif
                    <span>{{ $b->client?->name ?: 'Client not named' }}</span>
                    {{-- A real middle dot, not the entity: inside {{ }} the
                         ampersand is escaped and "&middot;" reaches the page
                         as those eight characters. --}}
                    <span>{{ collect([$b->event?->event_type])
                        ->merge($b->event?->categories?->pluck('name') ?? [])
                        ->filter()->take(2)->implode(" · ") ?: 'Event type not set' }}</span>
                </td>

                <td>
                    <span class="pm-chip" style="background: {{ $bookColour }}1f; color: {{ $bookColour }};">{{ $bookLabel }}</span>
                </td>

                <td>
                    <span class="pm-chip" style="background: {{ $payColour }}1f; color: {{ $payColour }};">
                        {!! $svg($payIcon) !!} {{ $t['state'] }}
                    </span>
                    <span class="pm-sub">{{ $t['state_note'] }}</span>
                </td>

                <td>
                    <span class="pm-money">{{ $money($t['price']) }}</span>
                    <span class="pm-sub">(your service)</span>
                </td>

                <td>
                    {{-- A real minus sign, not the entity: inside {{ }} an
                         ampersand is escaped and "&minus;" reaches the page
                         as those seven characters. --}}
                    <span class="pm-money">{{ $t['fee'] > 0 ? "− " . $money($t['fee']) : $money(0) }}</span>
                    <span class="pm-sub">({{ $pct($t['rate']) }})</span>
                </td>

                <td><span class="pm-net">{{ $money($t['net']) }}</span></td>

                <td>
                    @if($t['paid_at'])
                        <span class="pm-money" style="font-size:12px;">{{ $on($t['paid_at']) }}</span>
                        <span class="pm-sub">(Paid Out)</span>
                    @else
                        {{-- Nothing on this platform schedules a payout, so
                             there is no date to estimate here. --}}
                        <span class="pm-muted">Not scheduled</span>
                    @endif
                </td>

                <td>
                    <span class="pm-acts">
                        <button type="button" class="pm-open" data-tx="{{ $b->id }}">View Breakdown</button>
                        <button type="button" class="pm-chev" data-tx="{{ $b->id }}" aria-label="Open this breakdown">
                            {!! $svg('chev', 'class="cv"') !!}
                        </button>
                    </span>
                </td>
            </tr>

            <tr class="detail" id="tx-{{ $b->id }}" hidden>
                <td colspan="9">
                    <div class="pm-detail">
                        <div>
                            <h5>Payment Details</h5>
                            <p class="pm-line"><span>Professional service amount</span><b>{{ $money($t['price']) }}</b></p>
                            @if($b->status === 'cancelled')
                                <p class="pm-line"><span>Cancellation adjustment</span>
                                    <b class="{{ $t['adjust'] < 0 ? 'minus' : '' }}" style="{{ $t['adjust'] < 0 ? 'color:#dc2626;' : '' }}">{{ $money($t['adjust']) }}</b></p>
                            @endif
                            <p class="pm-line"><span>Amount used for fee calculation</span><b>{{ $money($t['basis']) }}</b></p>
                            <p class="pm-line"><span>Platform fee ({{ $pct($t['rate']) }})</span><b style="color:#dc2626;">&minus; {{ $money($t['fee']) }}</b></p>
                            <p class="pm-line pm-total">
                                <span>Net earnings ({{ strtolower($t['state']) }})</span><b>{{ $money($t['net']) }}</b>
                            </p>
                        </div>

                        <div>
                            <h5>Status &amp; Notes</h5>
                            <p class="pm-line" style="justify-content:flex-start;gap:7px;">
                                <span style="color: {{ $payColour }}; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
                                    {!! $svg($payIcon, 'style="width:13px;height:13px;"') !!}
                                    {{ $t['state'] }}{{ $t['paid_at'] ? ' on ' . $on($t['paid_at']) : '' }}
                                </span>
                            </p>
                            <p class="pm-note">{{ $t['state_note'] }}</p>
                            @if($t['cancel_note'])
                                <p class="pm-note">{{ $t['cancel_note'] }}</p>
                            @endif
                            @if(! $t['paid_at'] && $t['net'] > 0)
                                <p class="pm-note">No payout is scheduled. You request one on the Transactions page.</p>
                            @endif
                        </div>

                        <div class="pm-rel">
                            <h5>Related Links</h5>
                            @if($b->event)
                                <a href="{{ route('professional.gigs.show', $b->event) }}">{!! $svg('doc') !!} View Event Details</a>
                            @endif
                            <a href="{{ route('professional.agreements.index') }}">{!! $svg('pen') !!} View Agreement</a>
                            {{-- There is no separate invoices page; Transactions is
                                 where this platform keeps the paperwork. --}}
                            <a href="{{ route('professional.transactions.index') }}">{!! $svg('bill') !!} View Invoice</a>
                        </div>

                        <div class="pm-tierbox">
                            <h5><span>Tier &amp; Fee Info</span> <span class="tag">{{ $tier['label'] }} Tier ({{ $pct($t['rate']) }})</span></h5>
                            {{-- Her panel explains that this rate was locked in
                                 when the booking was accepted. Nothing stores a
                                 rate per booking here, so the honest version is
                                 the one that tells a professional what would
                                 happen if they changed membership. --}}
                            This rate comes from your membership as it stands today. GigResource does not
                            keep a separate rate per booking, so changing tier changes this figure for
                            this transaction too.
                            <a class="pm-link" style="margin-top:8px;" href="{{ route('app.membership-plans.history') }}">View tier history &rarr;</a>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="pm-foot">
        <span class="pm-muted">
            Showing {{ $transactions->firstItem() ?? 0 }}&ndash;{{ $transactions->lastItem() ?? 0 }}
            of {{ $transactions->total() }} {{ \Illuminate\Support\Str::plural('transaction', $transactions->total()) }}
        </span>
        {{ $transactions->onEachSide(1)->links() }}
    </div>

    <p class="pm-foottext">
        Payouts are requested, not automatic, so no payout date is estimated here. Platform fees use
        your membership rate as it stands today; GigResource does not store a separate rate per booking.
    </p>
@else
    <div class="pm-none">
        <h2>No transactions yet</h2>
        <p>
            @if(array_filter($filters))
                Nothing matches these filters. Clear them to see everything.
            @else
                A transaction appears here once a client books you.
            @endif
        </p>
    </div>
@endif
</div>

<script>
(function () {
    /* One breakdown open at a time, so a long list does not become a wall. */
    Array.prototype.slice.call(document.querySelectorAll('[data-tx]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id  = btn.dataset.tx;
            var row = document.getElementById('tx-' + id);
            if (!row) { return; }

            var opening = row.hidden;

            document.querySelectorAll('tr.detail').forEach(function (r) { r.hidden = true; });
            document.querySelectorAll('.pm-open').forEach(function (b) { b.textContent = 'View Breakdown'; });
            document.querySelectorAll('.pm-chev .cv').forEach(function (c) { c.style.transform = ''; });

            row.hidden = !opening;

            /* The label and the chevron both belong to this row, whichever
               of the two was clicked. */
            var open = document.querySelector('.pm-open[data-tx="' + id + '"]');
            var chev = document.querySelector('.pm-chev[data-tx="' + id + '"] .cv');
            if (open) { open.textContent = opening ? 'Hide Breakdown' : 'View Breakdown'; }
            if (chev) { chev.style.transform = opening ? 'rotate(180deg)' : ''; }
        });
    });
})();
</script>
@endsection
