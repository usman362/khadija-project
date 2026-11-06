@extends('layouts.professional')

@section('title', 'My Packages')
@section('page-title', 'My Packages')
@section('page-subtitle', 'Create, manage, preview, publish, and update the service packages clients can find in Package Search.')

@php
    use App\Support\PackageProgress;

    $f = $filters;
    $carry = array_filter([
        'q'    => $f['q'] ?: null,
        'tab'  => $f['tab'] !== 'all' ? $f['tab'] : null,
        'sort' => $f['sort'] !== 'newest' ? $f['sort'] : null,
    ]);
    $link = fn (array $over = []) => route('professional.packages.index', array_filter(
        array_merge($carry, $over), fn ($v) => $v !== null && $v !== ''
    ));

    // Icon + tone per state, so the tiles, the badge and the tab all describe
    // one package the same way wherever it appears.
    // Her tabs carry the short name of each state, so the whole bar fits on
    // one line beside the search. The tiles keep the longer label.
    $short = [
        'published' => 'Published', 'draft' => 'Draft', 'ready' => 'Ready',
        'unpublished' => 'Unpublished', 'archived' => 'Archived',
    ];

    $tone = [
        'published'   => ['ok',    'globe'],
        'draft'       => ['muted', 'file'],
        'ready'       => ['info',  'rocket'],
        'unpublished' => ['warn',  'hide'],
        'archived'    => ['muted', 'box'],
    ];

    /*
     * Drawn, not typed. An emoji is the operating system's picture: it is a
     * different shape, weight and colour on every machine, so a row of them
     * never lines up with the rest of the page the way her icons do.
     */
    $paths = [
        'box'    => '<path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>',
        'globe'  => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20a15 15 0 0 1 0-20z"/>',
        'file'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'rocket' => '<path d="M5 13c-1.5 1.5-2 5-2 5s3.5-.5 5-2a2.8 2.8 0 0 0-3-3z"/><path d="M14.5 9.5 9 15l-3-3 5.5-5.5A9 9 0 0 1 21 3a9 9 0 0 1-3.5 9.5z"/><circle cx="15" cy="9" r="1.3"/>',
        'hide'   => '<path d="M9.9 4.2A10 10 0 0 1 12 4c6.4 0 10 8 10 8a18 18 0 0 1-2.4 3.4"/><path d="M6.6 6.6A18 18 0 0 0 2 12s3.6 8 10 8a10 10 0 0 0 5.4-1.6"/><path d="m2 2 20 20"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'team'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/>',
        'pin'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'cam'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'copy'   => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'cash'   => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'bolt'   => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'tick'   => '<path d="m4 12 5 5L20 6"/>',
        'funnel' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'help'   => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
    ];

    $ico = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';
@endphp

@push('styles')
<style>
    .mp-layout { display: grid; grid-template-columns: minmax(0,1fr) 252px; gap: 18px; align-items: start; }

    /* Header */
    .mp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .mp-head h2 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .mp-head p { font-size: 13.5px; color: var(--text-muted); margin: 0; max-width: 560px; line-height: 1.5; }
    .mp-new { display: inline-flex; align-items: center; gap: 8px; background: var(--accent-blue); color: #fff; padding: 11px 18px; border-radius: 10px; font-weight: 800; text-decoration: none; font-size: 13.5px; white-space: nowrap; border: none; cursor: pointer; }
    .mp-new:hover { filter: brightness(1.08); }

    /* Stat tiles */
    .mp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(132px, 1fr)); gap: 11px; margin-bottom: 16px; }
    .mp-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 15px; text-decoration: none; display: block; }
    .mp-stat:hover { border-color: var(--accent-blue); }
    .mp-stat.on { border-color: var(--accent-blue); box-shadow: 0 0 0 1px var(--accent-blue) inset; }
    .mp-stat-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .mp-stat-lbl { font-size: 10.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: var(--text-muted); line-height: 1.3; }
    .mp-stat-ico { width: 17px; height: 17px; flex: none; color: var(--text-muted); }
    .mp-stat-ico svg { width: 17px; height: 17px; display: block; }
    .mp-stat-ico.ok { color: var(--ok-text); }
    .mp-stat-ico.info { color: var(--accent-blue); }
    .mp-stat-ico.warn { color: var(--warn-text); }
    .mp-stat-n { font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 8px 0 3px; }
    .mp-stat-note { font-size: 11px; color: var(--text-muted); line-height: 1.35; }
    .mp-stat-note.ok { color: var(--ok-text); }
    .mp-stat-note.warn { color: var(--warn-text); }

    /* Toolbar */
    /* One line, the way hers reads: search, the state tabs, then the filter
       and the sort. The tabs are the part that gives when the window is
       narrow, so nothing else is pushed onto a second row. */
    .mp-bar { display: flex; align-items: center; gap: 9px; flex-wrap: nowrap; margin-bottom: 16px; }
    .mp-search { position: relative; flex: 0 0 200px; }
    .mp-search input { width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 9px 12px 9px 34px; font-size: 13px; color: var(--text-primary); font-family: inherit; }
    .mp-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }
    .mp-tabs { display: flex; gap: 5px; flex: 1 1 auto; min-width: 0; overflow-x: auto;
        scrollbar-width: none; padding-bottom: 1px; }
    .mp-tabs::-webkit-scrollbar { display: none; }
    .mp-tab { padding: 7px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; text-decoration: none; color: var(--text-secondary, var(--text-muted)); border: 1px solid var(--border-color); background: var(--bg-card); white-space: nowrap; }
    .mp-tab.on { background: var(--text-primary); color: var(--bg-card); border-color: var(--text-primary); }
    .mp-bar-right { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 7px; }
    .mp-sel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 10px; font-size: 12px; font-weight: 700; color: var(--text-primary); font-family: inherit; cursor: pointer; }

    /* Rows */
    .mp-rows { display: flex; flex-direction: column; gap: 12px; }
    .mp-row { display: grid; grid-template-columns: 96px minmax(0,1fr) 168px 226px; gap: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px; align-items: start; }
    .mp-thumb { width: 96px; height: 96px; border-radius: 10px; object-fit: cover; background: linear-gradient(135deg,#1e3a5f,#2d1b69); display: block; }
    .mp-title { font-size: 15.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 5px; line-height: 1.3; }
    /* Every link inside a row carries a class on purpose: the portal styles
       `article a:not([class])` for blog prose, the row is an <article>, and a
       classless anchor in here came out indigo and underlined. */
    .mp-title-link { color: var(--text-primary); text-decoration: none; }
    .mp-title-link:hover { color: var(--accent-blue); }
    .mp-links a, .mp-qa, .mp-help-link, .mp-stat, .mp-tab, .mp-btn, .mp-menu a { text-decoration: none; }
    .mp-svcs { font-size: 12px; color: var(--accent-blue); font-weight: 700; margin-bottom: 6px; line-height: 1.5; }
    .mp-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin: 0 0 9px; }
    .mp-chips { display: flex; flex-wrap: wrap; gap: 7px; }
    .mp-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 8px; padding: 3px 9px; }
    .mp-chip svg { width: 12px; height: 12px; flex: none; }
    .mp-vis svg { width: 13px; height: 13px; flex: none; }
    .mp-new svg { width: 15px; height: 15px; }
    .mp-card h4 svg { width: 15px; height: 15px; flex: none; }
    .mp-qa .ic svg { width: 15px; height: 15px; }
    .mp-filt { display: inline-flex; align-items: center; gap: 6px; color: var(--text-primary); cursor: pointer; }
    .mp-filters { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin: 0 0 14px;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; }
    .mp-filters[hidden] { display: none; }
    .mp-filters label { display: flex; flex-direction: column; gap: 5px; font-size: 11.5px;
        font-weight: 700; color: var(--text-muted); }
    .mp-filters input, .mp-filters select { background: var(--bg-page, var(--bg-card));
        border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 9px;
        font-size: 12.5px; color: var(--text-primary); font-family: inherit; width: 128px; }
    .mp-filters .mp-link { color: var(--accent-blue); font-size: 12px; font-weight: 700; padding-bottom: 8px; }
    .mp-filt svg { width: 14px; height: 14px; }
    /* Her step line: what is missing sits under the name of the step. */
    .mp-prog-lbl .pc { color: var(--text-muted); font-weight: 600; }
    .mp-prog-lbl .need { display: block; margin-top: 2px; }

    .mp-badge { display: inline-block; font-size: 11px; font-weight: 800; padding: 4px 11px; border-radius: 999px; margin-bottom: 7px; }
    .mp-badge.ok { background: rgba(16,163,74,.14); color: var(--ok-text); }
    .mp-badge.info { background: var(--accent-blue-soft); color: var(--accent-blue); }
    .mp-badge.warn { background: rgba(217,119,6,.14); color: var(--warn-text); }
    .mp-badge.muted { background: rgba(100,116,139,.16); color: var(--text-muted); }
    .mp-vis { font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 5px; line-height: 1.4; }
    .mp-vis.ok { color: var(--ok-text); }
    .mp-vis.off { color: var(--text-muted); }
    .mp-when { font-size: 11.5px; color: var(--text-muted); margin-top: 5px; }
    .mp-prog { margin: 0 0 10px; max-width: 420px; }
    .mp-prog-lbl { font-size: 11px; color: var(--text-muted); margin-bottom: 5px; line-height: 1.4; }
    .mp-prog-track { height: 6px; border-radius: 4px; background: var(--border-color); overflow: hidden; }
    .mp-prog-fill { height: 100%; background: var(--accent-blue); border-radius: 4px; }

    .mp-money { text-align: right; }
    .mp-money .lbl { font-size: 11px; color: var(--text-muted); font-weight: 700; }
    .mp-money .amt { font-size: 21px; font-weight: 800; color: var(--text-primary); line-height: 1.15; }
    .mp-money .none { font-size: 13.5px; font-weight: 700; color: var(--text-muted); }
    .mp-acts { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; margin-top: 10px; align-items: center; }
    .mp-btn { padding: 6px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 700; white-space: nowrap; text-decoration: none; border: 1px solid var(--border-color); color: var(--text-primary); background: transparent; cursor: pointer; font-family: inherit; }
    .mp-btn:hover { background: var(--bg-card-hover); }
    .mp-btn.primary { background: var(--accent-blue); border-color: var(--accent-blue); color: #fff; }
    .mp-btn.danger { color: var(--bad-text); border-color: rgba(239,68,68,.32); }
    .mp-links { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-top: 9px; font-size: 12px; font-weight: 700; }
    .mp-links .mp-link, .mp-links button { color: var(--accent-blue); background: none; border: none; padding: 0; cursor: pointer; font-size: 12px; font-weight: 700; font-family: inherit; text-decoration: none; }
    .mp-links .sep { color: var(--text-muted); font-weight: 400; }
    .mp-links .danger { color: var(--bad-text); }

    /* The "…" overflow menu */
    .mp-more { position: relative; }
    .mp-more > summary { list-style: none; cursor: pointer; padding: 6px 9px; border-radius: 8px; font-size: 11.5px; font-weight: 700; border: 1px solid var(--border-color); color: var(--text-primary); }
    .mp-more > summary::-webkit-details-marker { display: none; }
    .mp-more[open] > summary { background: var(--bg-card-hover); }
    .mp-menu { position: absolute; right: 0; top: calc(100% + 5px); z-index: 20; min-width: 176px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 11px; padding: 6px; box-shadow: 0 16px 40px rgba(0,0,0,.28); text-align: left; }
    .mp-menu .mp-mi, .mp-menu button { display: block; width: 100%; text-align: left; padding: 8px 10px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: var(--text-primary); background: none; border: none; cursor: pointer; font-family: inherit; text-decoration: none; }
    .mp-menu .mp-mi:hover, .mp-menu button:hover { background: var(--bg-card-hover); }
    .mp-menu .danger { color: var(--bad-text); }

    /* Pager */
    .mp-pager { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-top: 18px; }
    .mp-pager-note { font-size: 12.5px; color: var(--text-muted); }

    /* Right rail */
    .mp-rail { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px; }
    .mp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px; }
    .mp-card h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; display: flex; align-items: center; gap: 7px; }
    .mp-qa { display: flex; gap: 10px; align-items: flex-start; padding: 8px 0; text-decoration: none; width: 100%; background: none; border: none; cursor: pointer; font-family: inherit; text-align: left; }
    .mp-qa .ic { width: 30px; height: 30px; border-radius: 8px; background: var(--accent-blue-soft); color: var(--accent-blue); display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
    .mp-qa b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .mp-qa span { font-size: 11.5px; color: var(--text-muted); }
    .mp-steps { counter-reset: s; margin: 0; padding: 0; list-style: none; }
    .mp-steps li { counter-increment: s; display: flex; gap: 9px; font-size: 12px; color: var(--text-muted); line-height: 1.45; padding: 5px 0; }
    .mp-steps li::before { content: counter(s); flex-shrink: 0; width: 18px; height: 18px; border-radius: 50%; background: var(--accent-blue-soft); color: var(--accent-blue); font-size: 10.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .mp-tips { margin: 0; padding: 0; list-style: none; }
    .mp-tips li { display: flex; gap: 8px; font-size: 12px; color: var(--text-muted); line-height: 1.45; padding: 5px 0; }
    .mp-tips li::before { content: "✓"; color: var(--ok-text); font-weight: 800; flex-shrink: 0; }
    .mp-help { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 0 0 10px; }
    .mp-help-link { font-size: 12.5px; font-weight: 800; color: var(--accent-blue); }
    .mp-dup-list { margin-top: 8px; display: flex; flex-direction: column; gap: 5px; }
    .mp-dup-row { display: flex; align-items: center; gap: 8px; font-size: 12px; }
    .mp-dup-row span { color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mp-dup-row button { margin-left: auto; background: none; border: none; color: var(--accent-blue); font-size: 11.5px; font-weight: 800; cursor: pointer; font-family: inherit; white-space: nowrap; }

    .mp-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px; padding: 56px 24px; text-align: center; color: var(--text-muted); }
    .mp-empty h3 { color: var(--text-primary); margin: 0 0 8px; font-size: 18px; }
    .mp-flash { background: rgba(16,163,74,.12); border: 1px solid rgba(16,163,74,.35); color: var(--ok-text); padding: 11px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px; }

    @media (max-width: 1200px) { .mp-layout { grid-template-columns: 1fr; } .mp-rail { position: static; } }
    @media (max-width: 900px) {
        .mp-bar { flex-wrap: wrap; }
        .mp-search { flex: 1 1 100%; }
        .mp-stats { grid-template-columns: repeat(2, 1fr); }
        .mp-row { grid-template-columns: 1fr; }
        .mp-money, .mp-acts, .mp-links { text-align: left; justify-content: flex-start; }
        .mp-thumb { width: 100%; height: 150px; }
    }
</style>
@endpush

@section('content')
<div class="mp-head">
    <div>
        <h2>My Packages</h2>
        <p>Create, manage, preview, publish, and update the service packages clients can find in Package Search.</p>
    </div>
    <a href="{{ route('professional.packages.create') }}" class="mp-new">{!! $ico('plus') !!} Create a Package</a>
</div>

@if(session('status'))<div class="mp-flash">{{ session('status') }}</div>@endif

{{-- The tiles are the tabs. Two controls that filter the same list, sitting
     one above the other, is one control drawn twice — so clicking a tile
     filters, and the tile shows it is the one selected. --}}
<div class="mp-layout">
    <div>
<div class="mp-stats">
    <a class="mp-stat {{ $f['tab'] === 'all' ? 'on' : '' }}" href="{{ $link(['tab' => null, 'page' => null]) }}">
        <div class="mp-stat-top"><span class="mp-stat-lbl">All Packages</span><span class="mp-stat-ico">{!! $ico('box') !!}</span></div>
        <div class="mp-stat-n">{{ $counts['all'] }}</div>
        <div class="mp-stat-note">Everything you have created</div>
    </a>
    @foreach($shelf as $state => [$label, $note])
        @php [$class, $icon] = $tone[$state]; @endphp
        {{-- Archived is the one state most professionals never use. A tile
             reading zero for it crowds out the four that mean something, so it
             appears once there is something in it. --}}
        @continue($state === 'archived' && $counts[$state] === 0 && $f['tab'] !== 'archived')
        <a class="mp-stat {{ $f['tab'] === $state ? 'on' : '' }}" href="{{ $link(['tab' => $state, 'page' => null]) }}">
            <div class="mp-stat-top"><span class="mp-stat-lbl">{{ $label }}</span><span class="mp-stat-ico {{ $class }}">{!! $ico($icon) !!}</span></div>
            <div class="mp-stat-n">{{ $counts[$state] }}</div>
            <div class="mp-stat-note {{ $state === 'published' ? 'ok' : ($state === 'unpublished' ? 'warn' : '') }}">{{ $note }}</div>
        </a>
    @endforeach
</div>

        <div class="mp-bar">
            <form class="mp-search" method="GET" action="{{ route('professional.packages.index') }}">
                @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
                @if($f['sort'] !== 'newest')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="{{ $f['q'] }}" placeholder="Search your packages…" aria-label="Search your packages">
            </form>

            <div class="mp-tabs" id="mp-tabs">
                <a class="mp-tab {{ $f['tab'] === 'all' ? 'on' : '' }}" href="{{ $link(['tab' => null, 'page' => null]) }}">All ({{ $counts['all'] }})</a>
                @foreach($shelf as $state => [$label, $note])
                    {{-- A tab for a state nobody has anything in is a dead end,
                         so it only appears once there is something behind it. --}}
                    @if($counts[$state] > 0 || $f['tab'] === $state)
                        <a class="mp-tab {{ $f['tab'] === $state ? 'on' : '' }}" href="{{ $link(['tab' => $state, 'page' => null]) }}">{{ $short[$state] }} ({{ $counts[$state] }})</a>
                    @endif
                @endforeach
            </div>

            <div class="mp-bar-right">
                {{-- Her Filters button, holding the two that do not fit on the
                     bar. It opens already when one of them is in use, so a
                     narrowed list never looks like the whole shelf. --}}
                @php $extra = (int) ($f['min'] !== '') + (int) ($f['max'] !== '') + (int) ($f['occasion'] !== ''); @endphp
                <button type="button" class="mp-sel mp-filt" id="mp-filt-btn" aria-expanded="{{ $extra ? 'true' : 'false' }}">
                    {!! $ico('funnel') !!} Filters{{ $extra ? ' (' . $extra . ')' : '' }}
                </button>
                <form method="GET" action="{{ route('professional.packages.index') }}">
                    @if($f['q'])<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                    @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
                    <select id="mp-sort" name="sort" class="mp-sel" aria-label="Sort packages" onchange="this.form.submit()">
                        <option value="newest" @selected($f['sort']==='newest')>Sort: Newest</option>
                        <option value="oldest" @selected($f['sort']==='oldest')>Sort: Oldest</option>
                        <option value="price_high" @selected($f['sort']==='price_high')>Sort: Price high to low</option>
                        <option value="price_low" @selected($f['sort']==='price_low')>Sort: Price low to high</option>
                        <option value="title" @selected($f['sort']==='title')>Sort: Name A–Z</option>
                    </select>
                </form>
            </div>
        </div>

        <form method="GET" action="{{ route('professional.packages.index') }}"
              class="mp-filters" id="mp-filters" @if(! $extra) hidden @endif>
            @if($f['q'] !== '')<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
            @if($f['tab'] !== 'all')<input type="hidden" name="tab" value="{{ $f['tab'] }}">@endif
            @if($f['sort'] !== 'newest')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif

            <label>Price from
                <input type="number" name="min" min="0" step="50" value="{{ $f['min'] }}" placeholder="Any">
            </label>
            <label>up to
                <input type="number" name="max" min="0" step="50" value="{{ $f['max'] }}" placeholder="Any">
            </label>

            {{-- Offered only when this professional's own packages carry
                 occasions, so the control never narrows to nothing. --}}
            @if($occasions)
                <label>Occasion
                    <select name="occasion" aria-label="Occasion">
                        <option value="">All occasions</option>
                        @foreach($occasions as $o)
                            <option value="{{ $o }}" @selected($f['occasion'] === $o)>{{ $o }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <button type="submit" class="mp-btn primary">Apply</button>
            @if($extra)
                <a class="mp-link" href="{{ $link(['min' => null, 'max' => null, 'occasion' => null]) }}">Clear filters</a>
            @endif
        </form>

        @if($packages->count())
            <div class="mp-rows">
                @foreach($packages as $pkg)
                    @php
                        $state = PackageProgress::shelfState($pkg);
                        [$badgeClass, $icon] = $tone[$state];
                        $next = PackageProgress::nextStep($pkg);
                        $hero = $pkg->heroUrls(1)[0] ?? $pkg->fallbackHeroUrl(320);
                        $svcs = $pkg->services ?: [];
                    @endphp
                    <article class="mp-row">
                        <div>
                            <img class="mp-thumb" src="{{ $hero }}" alt="{{ $pkg->title }}" loading="lazy">
                        </div>

                        <div>
                            <h3 class="mp-title"><a class="mp-title-link" href="{{ route('professional.packages.edit', $pkg) }}">{{ $pkg->title }}</a></h3>
                            @if($svcs)
                                <div class="mp-svcs">{{ implode(' • ', array_slice($svcs, 0, 4)) }}@if(count($svcs) > 4) +{{ count($svcs) - 4 }}@endif</div>
                            @endif
                            @if($pkg->purpose || $pkg->description)
                                <p class="mp-desc">{{ \Illuminate\Support\Str::limit($pkg->purpose ?: $pkg->description, 110) }}</p>
                            @endif

                            @if($next)
                                {{-- The bar counts the wizard's own four steps, and
                                     names the one that is actually stopping this
                                     package going live. "50% complete" on its own
                                     tells nobody what to do next. --}}
                                <div class="mp-prog">
                                    <div class="mp-prog-lbl">
                                        Step {{ $next['n'] }} of 4: {{ $next['label'] }}
                                        <span class="pc">&bull; {{ PackageProgress::percent($pkg) }}% complete</span>
                                        <span class="need">needs {{ $next['missing'] }}</span>
                                    </div>
                                    <div class="mp-prog-track">
                                        <div class="mp-prog-fill" style="width: {{ PackageProgress::percent($pkg) }}%;"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="mp-chips">
                                @if($pkg->coverage || $pkg->duration)
                                    <span class="mp-chip">{!! $ico('clock') !!} {{ $pkg->coverage ?: $pkg->duration }}</span>
                                @endif
                                @if($pkg->team)
                                    <span class="mp-chip">{!! $ico('team') !!} {{ $pkg->team }} {{ \Illuminate\Support\Str::plural('Professional', $pkg->team) }}</span>
                                @endif
                                @if($pkg->guests)<span class="mp-chip">{!! $ico('team') !!} {{ $pkg->guests }}</span>@endif
                                {{-- One state, not a list. R38: a package is bookable
                                     in its professional's own state, so "MD, PA, VA"
                                     would advertise coverage the platform will not
                                     let them sell. --}}
                                @if($pkg->serves_regions)<span class="mp-chip">{!! $ico('pin') !!} {{ $pkg->serves_regions }}</span>@endif
                                @if($pkg->photosCount())<span class="mp-chip">{!! $ico('cam') !!} {{ $pkg->photosCount() }}</span>@endif
                            </div>
                        </div>

                        <div>
                            <span class="mp-badge {{ $badgeClass }}">{{ $shelf[$state][0] === 'Drafts' ? 'Draft' : $shelf[$state][0] }}</span>
                            @if($state === 'published')
                                <div class="mp-vis ok">{!! $ico('globe') !!} Visible in Package Search</div>
                                <div class="mp-when">Published on {{ $pkg->updated_at?->format('M j, Y') }}</div>
                            @else
                                {{-- Her wording, which is not the same twice: a package
                                     that was live and is now off reads "hidden", one that
                                     has never been live reads "not visible". --}}
                                <div class="mp-vis off">{!! $ico('hide') !!}
                                    {{ $state === 'unpublished' ? 'Hidden from Clients' : 'Not visible to Clients' }}
                                </div>
                                <div class="mp-when">
                                    @if($state === 'unpublished')
                                        Unpublished on {{ $pkg->updated_at?->format('M j, Y') }}
                                    @elseif($state === 'ready')
                                        Completed on {{ $pkg->updated_at?->format('M j, Y') }}
                                    @elseif($state === 'archived')
                                        Archived on {{ $pkg->updated_at?->format('M j, Y') }}
                                    @else
                                        Last edited {{ $pkg->updated_at?->format('M j, Y') }}
                                    @endif
                                </div>
                            @endif

                        </div>

                        <div class="mp-money">
                            @if($pkg->price > 0)
                                <div class="lbl">Starting at</div>
                                <div class="amt">{{ $pkg->priceLabel() }}</div>
                            @else
                                <div class="none">Pricing not set</div>
                            @endif

                            <div class="mp-acts">
                                @if($state === 'draft')
                                    <a class="mp-btn primary" href="{{ route('professional.packages.edit', $pkg) }}">Continue Setup</a>
                                @elseif($state === 'ready')
                                    <a class="mp-btn" href="{{ route('public.package', $pkg->slug) }}">Preview as Client</a>
                                    <form method="POST" action="{{ route('professional.packages.status', $pkg) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="mp-btn primary">Publish</button>
                                    </form>
                                @elseif($state === 'unpublished')
                                    <a class="mp-btn" href="{{ route('public.package', $pkg->slug) }}">View</a>
                                    <a class="mp-btn" href="{{ route('professional.packages.edit', $pkg) }}">Edit</a>
                                    <form method="POST" action="{{ route('professional.packages.status', $pkg) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="mp-btn primary">Republish</button>
                                    </form>
                                @elseif($state === 'archived')
                                    <form method="POST" action="{{ route('professional.packages.status', $pkg) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="draft">
                                        <button type="submit" class="mp-btn">Restore as draft</button>
                                    </form>
                                @else
                                    <a class="mp-btn" href="{{ route('public.package', $pkg->slug) }}">View</a>
                                    <a class="mp-btn" href="{{ route('professional.packages.edit', $pkg) }}">Edit</a>
                                @endif

                                <details class="mp-more">
                                    <summary aria-label="More actions for {{ $pkg->title }}">···</summary>
                                    <div class="mp-menu">
                                        {{-- Only what the row is not already offering. Edit
                                             appearing as a button AND a menu item is one
                                             action drawn twice. --}}
                                        @unless(in_array($state, ['published', 'unpublished', 'draft'], true))
                                            <a class="mp-mi" href="{{ route('professional.packages.edit', $pkg) }}">Edit</a>
                                        @endunless
                                        @unless(in_array($state, ['published', 'draft', 'ready'], true))
                                            <a class="mp-mi" href="{{ route('public.package', $pkg->slug) }}">Preview as client</a>
                                        @endunless
                                        <form method="POST" action="{{ route('professional.packages.duplicate', $pkg) }}">
                                            @csrf
                                            <button type="submit">Duplicate</button>
                                        </form>
                                        @if($state !== 'archived')
                                            <form method="POST" action="{{ route('professional.packages.status', $pkg) }}"
                                                  onsubmit="return confirm('Archive this package? It stops appearing in Package Search and stays here for your records.')">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="archived">
                                                <button type="submit">Archive</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('professional.packages.destroy', $pkg) }}"
                                              onsubmit="return confirm('Delete this package for good? Archiving keeps the record instead.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="danger">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            </div>

                            <div class="mp-links">
                                @if($state === 'published')
                                    <a class="mp-link" href="{{ route('public.package', $pkg->slug) }}">Preview as Client</a>
                                    <span class="sep">|</span>
                                    <form method="POST" action="{{ route('professional.packages.status', $pkg) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="paused">
                                        <button type="submit">Unpublish</button>
                                    </form>
                                @elseif($state === 'draft')
                                    <a class="mp-link" href="{{ route('public.package', $pkg->slug) }}">Preview as Client</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mp-pager">
                <span class="mp-pager-note">
                    Showing {{ $packages->firstItem() }} to {{ $packages->lastItem() }} of {{ $packages->total() }} package{{ $packages->total() === 1 ? '' : 's' }}
                </span>
                <div>{{ $packages->onEachSide(1)->links() }}</div>
            </div>
        @else
            <div class="mp-empty">
                @if($f['q'] !== '' || $f['tab'] !== 'all')
                    <h3>Nothing here</h3>
                    <p>No package matches that. <a href="{{ route('professional.packages.index') }}" style="color:var(--accent-blue);font-weight:800;">Show all packages</a></p>
                @else
                    <h3>No packages yet</h3>
                    <p>Create a package to let clients book your services directly: no back-and-forth.</p>
                    <a href="{{ route('professional.packages.create') }}" class="mp-new" style="margin-top:14px;">{!! $ico('plus') !!} Create your first package</a>
                @endif
            </div>
        @endif
    </div>

    {{-- ── Right rail ────────────────────────────────────────────── --}}
    <aside class="mp-rail">
        <div class="mp-card">
            <h4>{!! $ico('bolt', 'style="color:#d97706;"') !!} Quick Actions</h4>
            <a class="mp-qa" href="{{ route('professional.packages.create') }}">
                <span class="ic">{!! $ico('plus') !!}</span>
                <span><b>Create a New Package</b><span>Build a package from scratch</span></span>
            </a>

            {{-- Duplicating needs something to duplicate, so the picker is the
                 action rather than a link that would have to ask afterwards. --}}
            @if($duplicatable->count())
                <details>
                    <summary class="mp-qa" style="list-style:none;">
                        <span class="ic">{!! $ico('copy') !!}</span>
                        <span><b>Duplicate a Package</b><span>Copy an existing package</span></span>
                    </summary>
                    <div class="mp-dup-list">
                        @foreach($duplicatable as $d)
                            <form method="POST" action="{{ route('professional.packages.duplicate', $d) }}" class="mp-dup-row">
                                @csrf
                                <span>{{ \Illuminate\Support\Str::limit($d->title, 26) }}</span>
                                <button type="submit">Copy</button>
                            </form>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- These two are the AI tools that already do the job. The mockup
                 labels them "Package Templates" and "Pricing Guide"; naming them
                 after the pages they actually open means nobody arrives
                 expecting something else. --}}
            <a class="mp-qa" href="{{ route('ai-tools.package-builder') }}">
                <span class="ic">{!! $ico('grid') !!}</span>
                <span><b>Package Builder</b><span>Get a starting package suggested for you</span></span>
            </a>
            <a class="mp-qa" href="{{ route('ai-tools.pricing-assistant') }}">
                <span class="ic">{!! $ico('cash') !!}</span>
                <span><b>Pricing Calculator</b><span>Work out what to charge</span></span>
            </a>
        </div>

        <div class="mp-card">
            <h4>How Publishing Works</h4>
            <ol class="mp-steps">
                <li>Complete all 4 Create a Package steps.</li>
                <li>Save as Draft at any point: a draft stays private.</li>
                <li>Preview as Client, then Publish.</li>
                <li>Published packages appear in Package Search.</li>
                <li>Unpublish anytime, or Archive when no longer in use.</li>
            </ol>
        </div>

        <div class="mp-card">
            <h4>{!! $ico('tick', 'style="color:#059669;"') !!} Package Tips</h4>
            <ul class="mp-tips">
                <li>Use clear names, descriptions and pricing.</li>
                <li>Keep availability and coverage up to date.</li>
                <li>High-quality images get more views.</li>
                <li>Unpublish instead of deleting.</li>
                <li>Archive past packages to keep the history.</li>
            </ul>
        </div>

        <div class="mp-card">
            <h4>{!! $ico('help') !!} Need Help?</h4>
            <p class="mp-help">We're here to help you create, publish and manage your packages.</p>
            <a class="mp-help-link" href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">Contact Support →</a>
        </div>
    </aside>
</div>

<script>
    (function () {
        var btn = document.getElementById('mp-filt-btn');
        var box = document.getElementById('mp-filters');
        if (btn && box) {
            btn.addEventListener('click', function () {
                box.hidden = !box.hidden;
                btn.setAttribute('aria-expanded', box.hidden ? 'false' : 'true');
            });
        }
    })();

    // One overflow menu open at a time, and clicking away closes it.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.mp-more[open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });
</script>
@endsection
