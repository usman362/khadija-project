@extends('layouts.professional')

@section('title', 'My Services & Pricing')
@section('page-title', 'My Services & Pricing')

{{-- What this professional offers and what each of it costs.

     Khadijah's 28 October screen: five counts across the top, the list grouped
     under its parent categories rather than run flat, and an Add Service
     Offering button so the one page you would add a service from is the page
     you price it on.

     Choosing WHICH services is still the same pivot the profile writes, so the
     two screens cannot disagree about the list. --}}

@push('styles')
<style>
    .sp-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px;
        flex-wrap:wrap; margin-bottom:16px; }
    .sp-head > div:first-child { flex:1 1 auto; min-width:0; }
    .sp-head h1 { font-size:25px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    /* One line, as hers is. Wrapped, it pushed the button down a row. */
    .sp-head p { font-size:13px; color:var(--text-muted); margin:0; line-height:1.55; }
    .sp-head .sp-btn { flex:none; }

    .sp-layout { display:grid; grid-template-columns:minmax(0,1fr) 240px; gap:18px; align-items:start; }
    /* A grid item's own minimum is its content, so the widest card in a
       column still sets that column's width however the track is declared. */
    .sp-layout > * { min-width: 0; }

    .sp-btn { display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700;
        padding:9px 15px; border-radius:9px; text-decoration:none; background:var(--accent-blue, #2563eb);
        color:#fff; border:1px solid var(--accent-blue, #2563eb); cursor:pointer; font-family:inherit; white-space:nowrap; }
    .sp-btn svg { width:15px; height:15px; flex:none; }
    .sp-ghost { background:var(--bg-card); color:var(--text-primary); border-color:var(--border-color); }

    /* Her tab bar: one card, six tabs, the open one filled. */
    .sp-tabbar { display:flex; gap:0; background:var(--bg-card); border:1px solid var(--border-color);
        border-radius:13px; padding:6px; margin-bottom:16px; overflow-x:auto; scrollbar-width:none; }
    .sp-tabbar::-webkit-scrollbar { display:none; }
    .sp-tab { position:relative; display:inline-flex; align-items:center; justify-content:center; gap:8px;
        flex:1 1 0; padding:11px 10px; border-radius:9px; font-size:13px; font-weight:700;
        text-decoration:none; color:var(--text-muted); white-space:nowrap; }
    /* A hairline between tabs, the way her bar divides them, rather than a
       border around every one. */
    .sp-tab + .sp-tab::before { content:""; position:absolute; left:0; top:22%; bottom:22%;
        width:1px; background:var(--border-color); }
    .sp-tab.on + .sp-tab::before, .sp-tab.on::before { display:none; }
    .sp-tab svg { width:16px; height:16px; flex:none; }
    .sp-tab.on { background:var(--accent-blue, #2563eb); color:#fff; }

    .sp-stats { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:13px; margin-bottom:18px; }
    .sp-stat { display:flex; gap:11px; align-items:flex-start; background:var(--bg-card);
        border:1px solid var(--border-color); border-radius:13px; padding:13px 14px; }
    .sp-stat .ic { width:36px; height:36px; border-radius:10px; flex:none; display:flex; align-items:center;
        justify-content:center; color:var(--c); background:color-mix(in srgb, var(--c) 13%, transparent); }
    .sp-stat .ic svg { width:18px; height:18px; }
    .sp-stat .m { min-width:0; }
    .sp-stat .l { display:block; font-size:12px; font-weight:600; color:var(--text-secondary, var(--text-muted));
        line-height:1.3; white-space:nowrap; }
    .sp-stat .n { display:block; font-size:24px; font-weight:800; color:var(--text-primary); line-height:1.25; margin:1px 0; }
    .sp-stat .s { display:block; font-size:11px; color:var(--text-muted); line-height:1.4; }

    .sp-cardhead { display:flex; gap:12px; align-items:center; margin-bottom:14px; }
    .sp-cardhead .ic { width:34px; height:34px; border-radius:9px; flex:none; display:flex; align-items:center;
        justify-content:center; color:var(--accent-blue, #2563eb);
        background:color-mix(in srgb, var(--accent-blue, #2563eb) 12%, transparent); }
    .sp-cardhead .ic svg { width:17px; height:17px; }
    .sp-cardhead > div { flex:1 1 auto; min-width:0; }
    .sp-cardhead .acts { display:flex; gap:9px; flex-wrap:wrap; flex:none; }
    .sp-addbox:not([hidden]) { display:block; margin-bottom:16px; padding:14px; border-radius:11px;
        background:var(--bg-page, rgba(128,128,128,.05)); border:1px solid var(--border-color); }

    .sp-views { display:inline-flex; gap:4px; background:var(--bg-page, rgba(128,128,128,.07));
        border:1px solid var(--border-color); border-radius:9px; padding:3px; }
    .sp-view { display:inline-flex; align-items:center; gap:6px; padding:6px 11px; border-radius:7px;
        font-size:12px; font-weight:700; border:0; background:none; color:var(--text-muted);
        cursor:pointer; font-family:inherit; white-space:nowrap; }
    .sp-view svg { width:14px; height:14px; }
    .sp-view.on { background:var(--accent-blue, #2563eb); color:#fff; }

    /* The service cards. */
    .sp-cards { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:15px; }
    .sp-svc:not([hidden]) { display:flex; }
    .sp-svc { flex-direction:column; border:1px solid var(--border-color); border-radius:13px;
        overflow:hidden; background:var(--bg-card); }
    .sp-svc.is-resting { opacity:.72; }
    /* The band carries the name. A header strip, not a picture frame: it is
       as tall as the words need and no taller, where the old tile was 112px
       of colour holding a single letter. */
    .sp-svc .media { position:relative; display:flex; align-items:center; gap:11px; padding:13px 15px;
        min-height:62px;
        background:linear-gradient(135deg, color-mix(in srgb, var(--t) 85%, #000), color-mix(in srgb, var(--t) 45%, #000)); }
    .sp-svc .media .mark { flex:none; width:34px; height:34px; border-radius:10px;
        display:flex; align-items:center; justify-content:center;
        font-size:15px; font-weight:800; color:#fff;
        background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22); }
    .sp-svc .media .name { flex:1 1 auto; min-width:0; font-size:14.5px; font-weight:800; color:#fff;
        line-height:1.25; }
    /* Static, so the pill sits beside the name instead of over it: the name
       is as long as it is, and a service called "Point-of-Sale & Cashless
       Payment Systems" would have run underneath an absolute one. */
    .sp-svc .live { position:static; flex:none; align-self:flex-start; font-size:10.5px; font-weight:700;
        padding:3px 10px; border-radius:20px; background:rgba(209,250,229,.95); color:#047857; }
    .sp-svc .live.off { background:rgba(241,245,249,.95); color:#475569; }
    .sp-svc .body { padding:12px 14px; flex:1 1 auto; }
    .sp-svc .chips { display:flex; gap:5px; flex-wrap:wrap; margin:9px 0 11px; }
    .sp-svc .chip { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700;
        padding:3px 8px; border-radius:6px; background:var(--bg-page, rgba(128,128,128,.08));
        color:var(--text-muted); border:1px solid var(--border-color); }
    .sp-svc .chip svg { width:11px; height:11px; }
    .sp-svc .chip.amber { background:rgba(217,119,6,.13); color:#b45309; border-color:transparent; }
    .sp-svc .chip.blue { background:rgba(37,99,235,.12); color:#1d4ed8; border-color:transparent; }
    .sp-svc .facts { display:grid; grid-template-columns:minmax(0,88px) minmax(0,1fr); gap:11px;
        border-top:1px solid var(--border-color); padding-top:11px; }
    .sp-svc .facts .k { display:block; font-size:10.5px; color:var(--text-muted); }
    .sp-svc .facts .v { display:block; font-size:19px; font-weight:800; color:var(--text-primary); line-height:1.2; }
    .sp-svc .facts .v.none { font-size:14px; color:var(--text-muted); }
    .sp-svc .facts .u { display:block; font-size:10.5px; color:var(--text-muted); }
    .sp-svc .facts ul { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:5px; }
    .sp-svc .facts li { display:flex; gap:6px; align-items:flex-start; font-size:10px; color:var(--text-muted); line-height:1.45; }
    .sp-svc .facts li svg { width:12px; height:12px; flex:none; margin-top:1px; }
    .sp-svc .facts .none { color:var(--text-muted); font-style:italic; }
    .sp-svc .foot { display:flex; border-top:1px solid var(--border-color); }
    .sp-svc .foot > * { flex:1 1 0; min-width:0; }
    .sp-svc .foot > * + * { border-left:1px solid var(--border-color); }
    .sp-svc .foot .link { display:flex; align-items:center; justify-content:center; gap:6px; width:100%;
        padding:10px 8px; font-size:12px; font-weight:700; border:0; background:none;
        color:var(--accent-blue, #2563eb); cursor:pointer; font-family:inherit; list-style:none; }
    .sp-svc .foot .link::-webkit-details-marker { display:none; }
    .sp-svc .foot svg { width:13px; height:13px; }
    .sp-svc .more { position:relative; }
    .sp-svc .more .menu { position:absolute; right:0; bottom:calc(100% + 5px); z-index:25; min-width:186px;
        background:var(--bg-card); border:1px solid var(--border-color); border-radius:11px;
        padding:5px; box-shadow:0 14px 34px rgba(0,0,0,.18); text-align:left; }
    .sp-svc .more .menu a, .sp-svc .more .menu button { display:block; width:100%; text-align:left;
        padding:8px 10px; border-radius:8px; font-size:12px; font-weight:600; border:0; background:none;
        color:var(--text-primary); text-decoration:none; cursor:pointer; font-family:inherit; }
    .sp-svc .more .menu a:hover, .sp-svc .more .menu button:hover { background:var(--bg-card-hover, rgba(128,128,128,.1)); }

    .sp-addmore { align-items:center; justify-content:center; text-align:center; gap:7px; padding:22px 18px;
        border-style:dashed; text-decoration:none; }
    .sp-addmore .plus { width:40px; height:40px; border-radius:50%; display:flex; align-items:center;
        justify-content:center; background:var(--accent-blue, #2563eb); color:#fff; }
    .sp-addmore .plus svg { width:19px; height:19px; }
    .sp-addmore b { font-size:14px; font-weight:800; color:var(--accent-blue, #2563eb); }
    .sp-addmore > span:not(.plus):not(.sp-btn) { font-size:11.5px; color:var(--text-muted); line-height:1.5; }
    .sp-listview[hidden] { display:none; }

    /* Right rail */
    .sp-rail { display:flex; flex-direction:column; gap:16px; position:sticky; top:20px; }
    .sp-side { background:var(--bg-card); border:1px solid var(--border-color); border-radius:13px; padding:15px; }
    .sp-side h4 { display:flex; align-items:center; gap:8px; font-size:13.5px; font-weight:800;
        color:var(--text-primary); margin:0 0 11px; }
    .sp-side h4 svg { width:16px; height:16px; flex:none; }
    .sp-side p { font-size:12px; color:var(--text-muted); line-height:1.55; margin:0 0 11px; }
    .sp-ticks { list-style:none; margin:0 0 11px; padding:0; display:flex; flex-direction:column; gap:9px; }
    .sp-ticks li { display:flex; gap:8px; font-size:12px; color:var(--text-secondary, var(--text-muted)); line-height:1.5; }
    .sp-ticks svg { width:14px; height:14px; flex:none; color:#059669; margin-top:1px; }
    .sp-link { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700;
        color:var(--accent-blue, #2563eb); text-decoration:none; }
    .sp-link svg { width:13px; height:13px; }

    @media (max-width: 1250px) { .sp-layout { grid-template-columns:minmax(0,1fr); } .sp-rail { position:static; } }
    @media (max-width: 1100px) { .sp-cards { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 1000px) { .sp-stats { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .sp-tools { flex-wrap:wrap; } }
    @media (max-width: 720px) { .sp-cards { grid-template-columns:minmax(0,1fr); } }
    /* Two of these side by side still need about 430px between them, so on a
       phone they went on over the right edge. */


    .sp-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:18px; margin-bottom:18px; }
    .sp-card > h2 { font-size:15px; font-weight:800; margin:0 0 4px; color:var(--text-primary); }
    .sp-card > .sub { font-size:12.5px; color:var(--text-muted); margin:0 0 16px; }
    .sp-cardhead h2 { font-size:17px; font-weight:800; margin:0 0 2px; color:var(--text-primary); }
    .sp-cardhead .sub { font-size:12.5px; color:var(--text-muted); margin:0; line-height:1.5; }

    /* It was flex-wrap:nowrap, and the search box had a 130px floor, so from
       about 1150px down the filter row simply grew past the card: at 1024 it
       ended 77px off the right of the screen. Wrapping only happens when the
       row cannot fit, so a wide screen still shows one line. */
    .sp-tools { display:flex; gap:9px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
    .sp-search { flex:1 1 170px; min-width:130px; }
    /* A select sizes itself to its longest option unless it is told not to,
       which is how "All Pricing Types" pushed the view toggle off the row. */
    .sp-tools select { flex:0 0 146px; width:146px; min-width:0; }
    .sp-tools .sp-views { margin-left:auto; flex:none; }
    .sp-in, .sp-sel { width:100%; font-family:inherit; font-size:13px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }

    .sp-group { border:1px solid var(--border-color); border-radius:12px; margin-bottom:12px; overflow:hidden; }
    .sp-group > summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:10px;
        padding:12px 14px; background:var(--bg-page, var(--bg-card)); font-size:13.5px; font-weight:800; color:var(--text-primary); }
    .sp-group > summary::-webkit-details-marker { display:none; }
    .sp-group > summary .chev { margin-left:auto; color:var(--text-muted); transition:transform .15s; }
    .sp-group[open] > summary .chev { transform:rotate(180deg); }
    .sp-group > summary .pill { font-size:10.5px; font-weight:700; color:var(--text-muted);
        border:1px solid var(--border-color); border-radius:20px; padding:2px 8px; background:var(--bg-card); }
    .sp-groupbody { padding:4px 14px 10px; }

    .sp-row { display:grid; grid-template-columns:minmax(0,2fr) 110px 120px 100px 110px 92px; gap:11px; align-items:end;
        padding:14px 0; border-bottom:1px solid var(--border-color); }
    .sp-row:last-child { border-bottom:none; }
    .sp-row.is-resting { opacity:.58; }
    .sp-name { font-size:13.5px; font-weight:700; color:var(--text-primary); }
    .sp-tags { display:flex; gap:5px; flex-wrap:wrap; margin-top:5px; }
    .sp-tag { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px;
        border:1px solid var(--border-color); color:var(--text-muted); background:var(--bg-page, var(--bg-card)); }
    .sp-tag.ok { border-color:#10b981; color:#047857; background:rgba(16,185,129,0.12); }
    .sp-tag.pkg { border-color:#6366f1; color:#4338ca; background:rgba(99,102,241,0.12); }
    .sp-tag.rest { border-color:#f59e0b; color:#b45309; background:rgba(245,158,11,0.14); }
    .sp-lab { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px;
        color:var(--text-muted); margin-bottom:4px; }
    .sp-mini { font-family:inherit; font-size:12.5px; font-weight:700; padding:8px 10px; border-radius:9px; width:100%;
        border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-secondary); cursor:pointer; }
    .sp-mini:hover { border-color:var(--accent-blue); color:var(--text-primary); }

    .sp-foot { display:flex; align-items:center; gap:14px; margin-top:16px; flex-wrap:wrap; }
    .sp-save { background:var(--accent-blue, #2563eb); color:#fff; border:none; border-radius:10px;
        padding:11px 20px; font-size:13.5px; font-weight:700; cursor:pointer; }
    .sp-note { font-size:12.5px; color:var(--text-muted); }
    .sp-empty { font-size:13px; color:var(--text-muted); line-height:1.6; }
    .sp-none { font-size:13px; color:var(--text-muted); padding:14px 0; }

    .sp-add { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:10px; align-items:end; }
    .sp-tips { list-style:none; padding:0; margin:0; }
    .sp-tips li { position:relative; padding-left:16px; margin-bottom:8px; font-size:12.5px; color:var(--text-secondary); }
    .sp-tips li::before { content:"\2713"; position:absolute; left:0; color:#10b981; font-weight:800; }

    /* The old pill tabs are gone. Their .sp-tab rules sat below the new ones
       and won on source order, which is why the open tab came out navy
       instead of blue. Third time a stale duplicate has done this. */
    .sp-three { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
    .sp-inline { display: grid; grid-template-columns: minmax(0,2fr) 120px 140px auto; gap: 11px; align-items: end; margin-top: 16px; }
    .sp-check { display: flex; gap: 9px; align-items: center; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    textarea.sp-in { font-family: inherit; resize: vertical; }
    @media (max-width: 860px) { .sp-three, .sp-inline { grid-template-columns: 1fr; } }
    @media (max-width: 1000px) { .sp-row { grid-template-columns:1fr 1fr 1fr; } .sp-tools, .sp-add { grid-template-columns:1fr; } }

    /* Last in the sheet on purpose.
       `.sp-tools` declares flex-wrap:nowrap at line 169, AFTER the 1000px
       media query that tries to set it to wrap, and at equal specificity the
       later rule wins however narrow the screen gets. So the filter row never
       wrapped and ran off the side of a phone, and a rule written anywhere
       above this one would have lost the same argument. */
    @media (max-width: 620px) {
        .sp-stats { grid-template-columns:minmax(0,1fr); }

        .sp-tools { flex-wrap:wrap; }
        .sp-tools > * { flex:1 1 100%; min-width:0; }

        .sp-cardhead { flex-wrap:wrap; }
        .sp-cardhead .acts { flex:1 1 100%; }

        /* Two columns, the field and its button, on a screen with room for
           one. */
        .sp-add { grid-template-columns:minmax(0,1fr); }
    }
</style>
@endpush

@section('content')
@php
    $paths = [
        'eye'    => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'tick'   => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'bolt'   => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'pause'  => '<circle cx="12" cy="12" r="10"/><path d="M10 9v6M14 9v6"/>',
        'box'    => '<path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>',
        'tag'    => '<path d="M20.6 13.4 12 22l-9-9V3h10z"/><path d="M7 7h.01"/>',
        'plus'   => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
        'car'    => '<path d="M5 17h14l-1.5-6.5A2 2 0 0 0 15.6 9H8.4a2 2 0 0 0-1.9 1.5z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/>',
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'zap'    => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'sort'   => '<path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="m21 8-4-4-4 4"/><path d="M17 4v16"/>',
        'add'    => '<path d="M12 5v14M5 12h14"/>',
        'list'   => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'cards'  => '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/>',
        'pen'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'cash'   => '<circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="M15 9.5a2.5 2.5 0 0 0-2.5-2h-1a2.25 2.25 0 0 0 0 4.5h1a2.25 2.25 0 0 1 0 4.5h-1A2.5 2.5 0 0 1 9 14.5"/>',
        'gear'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 9 19.4a1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'bulb'   => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>',
        'help'   => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'out'    => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
        'arrow'  => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'dots'   => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
    ];
    $ico = fn (string $n, string $extra = '') => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $extra . '>' . ($paths[$n] ?? '') . '</svg>';

    $spTabs = [
        'services'  => ['Services',          'tag'],
        'rules'     => ['Pricing Rules',     'cash'],
        'addons'    => ['Add-ons & Extras',  'plus'],
        'travel'    => ['Travel Pricing',    'car'],
        'templates' => ['Templates',         'doc'],
        'quickbid'  => ['Quick Bid Settings','zap'],
    ];
@endphp

<div class="sp-head">
    <div>
        <h1>My Services &amp; Pricing</h1>
        <p>Manage the services you offer, set your pricing, and customize options for Quick Bid, packages, and more.</p>
    </div>
    <a href="{{ route('professional.profile.index') }}" class="sp-btn sp-ghost">{!! $ico('eye') !!} Preview Public Profile</a>
</div>

{{-- Her six tabs. Services was the only one with anything behind it; the
     others had nowhere to store an answer, so a weekend surcharge or a travel
     charge was typed into every quote by hand. --}}
<div class="sp-tabbar">
    @foreach ($spTabs as $key => [$label, $icon])
        <a class="sp-tab {{ $tab === $key ? 'on' : '' }}"
           href="{{ route('professional.services.index', $key === 'services' ? [] : ['tab' => $key]) }}">
            {!! $ico($icon) !!} {{ $label }}
        </a>
    @endforeach
</div>

<div class="sp-layout">
<div>

<div class="sp-stats">
    @foreach ([
        ['total',    'Total Services',    'Active services you offer', '#2563eb', 'grid'],
        ['active',   'Active Services',   'Visible to clients',        '#059669', 'tick'],
        ['packaged', 'Package Usage',     'Services used in packages', '#7c3aed', 'box'],
        ['quickbid', 'Quick Bid Ready',   'Services with pricing rules','#d97706', 'bolt'],
        ['inactive', 'Inactive Services', 'Not visible to clients',    '#dc2626', 'pause'],
    ] as [$key, $label, $note, $colour, $icon])
        <div class="sp-stat" style="--c: {{ $colour }};">
            <span class="ic">{!! $ico($icon) !!}</span>
            <span class="m">
                <span class="l">{{ $label }}</span>
                <span class="n">{{ $counts[$key] }}</span>
                <span class="s">{{ $note }}</span>
            </span>
        </div>
    @endforeach
</div>

@if ($tab === 'services')
<div class="sp-card">
    <div class="sp-cardhead">
        <span class="ic">{!! $ico('grid') !!}</span>
        <div>
            <h2>Your Services</h2>
            <p class="sub">Create, edit, and organize the services you offer. These services can be used for
               Quick Bid, packages, and your public profile.</p>
        </div>
        <div class="acts">
            {{-- Reordering is the list view: the rows carry their own order and
                 the public profile reads it. Nothing else on this page can
                 change it, so the button opens the view that can. --}}
            <button type="button" class="sp-btn sp-ghost" id="spReorder">{!! $ico('sort') !!} Reorder Services</button>
            <button type="button" class="sp-btn" id="spAddOpen">{!! $ico('add') !!} Add Service</button>
        </div>
    </div>

    {{-- Add Service Offering. Khadijah asked for it here on 5 November; before
         this, the only way to add a service was to leave this page. --}}
    <div class="sp-addbox" id="spAddBox" hidden>
        @if (empty($addable))
            <div class="sp-none">You already offer every service on the site.</div>
        @else
            <form method="POST" action="{{ route('professional.services.add') }}" class="sp-add">
                @csrf
                <div>
                    <label class="sp-lab" for="spAdd">Service</label>
                    <select class="sp-sel" id="spAdd" name="category_id" required>
                        <option value="">Choose a service</option>
                        @foreach ($addable as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="sp-note" style="color:#b91c1c;margin-top:6px;">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="sp-btn">Add service offering</button>
            </form>
        @endif
    </div>

    @if ($services->isEmpty())
        <p class="sp-empty">
            Nothing on your list yet. Add your first service above, or pick several at once on your
            <a href="{{ route('professional.profile.index') }}">profile</a>.
        </p>
    @else
        <div class="sp-tools">
            <span class="sp-search">
                {!! $ico('tag', 'style="display:none;"') !!}
                <input type="search" id="spSearch" class="sp-in" placeholder="Search services..." oninput="spFilter()">
            </span>
            <select id="spCat" class="sp-sel" onchange="spFilter()">
                <option value="">All Categories</option>
                @foreach ($categories as $catName)
                    <option value="{{ $catName }}">{{ $catName }}</option>
                @endforeach
            </select>
            <select id="spStatus" class="sp-sel" onchange="spFilter()">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="resting">Inactive</option>
            </select>
            <select id="spPriced" class="sp-sel" onchange="spFilter()">
                <option value="">All Pricing Types</option>
                <option value="priced">Priced</option>
                <option value="unpriced">No price yet</option>
            </select>
            <span class="sp-views">
                <button type="button" class="sp-view on" data-view="card">{!! $ico('cards') !!} Card View</button>
                <button type="button" class="sp-view" data-view="list">{!! $ico('list') !!} List View</button>
            </span>
        </div>

        {{-- Card view is her screen. List view is the same services with their
             prices editable in place, which is the only way to change them and
             the only place the order can be set. --}}
        <div class="sp-cards" id="spCardView">
            @foreach ($services as $service)
                @php
                    $resting  = ! (bool) ($service->pivot->is_active ?? true);
                    $hasPrice = $service->pivot->price !== null;
                    $packaged = $inPackages->contains($service->name);
                    $tint     = ['#2563eb', '#7c3aed', '#db2777', '#d97706', '#059669'][crc32($service->name) % 5];
                @endphp
                <article class="sp-svc {{ $resting ? 'is-resting' : '' }}"
                         data-spcard
                         data-name="{{ Str::lower($service->name) }}"
                         data-cat="{{ $service->parent?->name }}"
                         data-status="{{ $resting ? 'resting' : 'active' }}"
                         data-priced="{{ $hasPrice ? 'priced' : 'unpriced' }}">
                    {{-- Sir Peter, 10 November: the service name belongs IN the
                         band, and the category line under it comes out.

                         His reason is a real one. The same service can sit in
                         two categories — 360 Photo Booths under Photo Booths
                         and 360 Video Booths under Photography — and printing
                         the category under each made two different services
                         look like one filed twice.

                         Still a tile and not a photograph: no picture is
                         stored for a service, and a stock image of somebody
                         else's work would be a claim about this one. --}}
                    <span class="media" style="--t: {{ $tint }};">
                        <span class="mark" aria-hidden="true">{{ Str::upper(Str::substr($service->name, 0, 1)) }}</span>
                        <b class="name">{{ $service->name }}</b>
                        <span class="live {{ $resting ? 'off' : '' }}">{{ $resting ? 'Inactive' : 'Active' }}</span>
                    </span>

                    <div class="body">
                        <span class="chips">
                            @if ($hasPrice)
                                <span class="chip amber">{!! $ico('bolt') !!} Quick Bid Ready</span>
                            @else
                                <span class="chip">No price yet</span>
                            @endif
                            @if ($packaged)
                                <span class="chip blue">{!! $ico('box') !!} In Packages</span>
                            @else
                                <span class="chip">{!! $ico('box') !!} Not in Packages</span>
                            @endif
                        </span>

                        <div class="facts">
                            <div>
                                @if ($hasPrice)
                                    <span class="k">{{ $units[$service->pivot->price_unit ?? 'from'] ?? 'Starting at' }}</span>
                                    <span class="v">${{ number_format((float) $service->pivot->price, 0) }}</span>
                                    <span class="u">{{ ($service->pivot->price_unit ?? 'from') === 'hourly' ? 'per hour' : 'per event' }}</span>
                                @else
                                    <span class="k">Price</span>
                                    <span class="v none">Not set</span>
                                    <span class="u">Clients cannot be quoted yet</span>
                                @endif
                            </div>
                            <ul>
                                <li>{!! $ico('clock') !!}
                                    @if ($service->pivot->duration_hours)
                                        Includes {{ $service->pivot->duration_hours }} {{ Str::plural('hour', $service->pivot->duration_hours) }}
                                    @else
                                        <span class="none">Hours not set</span>
                                    @endif
                                </li>
                                <li>{!! $ico('cash') !!}
                                    @if ($service->pivot->extra_hour_price !== null)
                                        ${{ number_format((float) $service->pivot->extra_hour_price, 0) }} per additional hour
                                    @else
                                        <span class="none">No extra hour rate</span>
                                    @endif
                                </li>
                                {{-- Her third line names what is included in the
                                     booking. Nothing records that per service,
                                     so this one says where it would come from
                                     rather than inventing it. --}}
                                <li>{!! $ico('gear') !!}
                                    <span class="none">Inclusions are not recorded per service</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="foot">
                        <button type="button" class="link" data-spedit>{!! $ico('pen') !!} Edit</button>
                        <details class="more">
                            <summary class="link">{!! $ico('dots') !!} More</summary>
                            <div class="menu">
                                <button type="submit" form="spToggle-{{ $service->id }}">
                                    {{ $resting ? 'Offer this again' : 'Rest this service' }}
                                </button>
                                <a href="{{ route('professional.packages.create') }}">Put it in a package</a>
                                <a href="{{ route('professional.profile.index') }}">See it on your profile</a>
                            </div>
                        </details>
                    </div>
                </article>
            @endforeach

            <a class="sp-svc sp-addmore" href="#" id="spAddMore">
                <span class="plus">{!! $ico('add') !!}</span>
                <b>Add Another Service</b>
                <span>Create a new service to offer to clients. You can set pricing, add options, and include it in packages.</span>
                <span class="sp-btn">{!! $ico('add') !!} Add Service</span>
            </a>
        </div>

        <div class="sp-listview" id="spListView" hidden>

        <form method="POST" action="{{ route('professional.services.update') }}">
            @csrf
            @method('PATCH')

            @foreach ($groups as $groupName => $groupServices)
                <details class="sp-group" data-spgroup open>
                    <summary>
                        <span>{{ $groupName }}</span>
                        <span class="pill" data-spcount>{{ $groupServices->count() }}</span>
                        <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="sp-groupbody">
                        @foreach ($groupServices as $service)
                            @php
                                $resting = ! (bool) ($service->pivot->is_active ?? true);
                                $hasPrice = $service->pivot->price !== null;
                                $packaged = $inPackages->contains($service->name);
                                $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                            @endphp
                            <div class="sp-row {{ $resting ? 'is-resting' : '' }}"
                                 data-sprow
                                 data-name="{{ Str::lower($service->name) }}"
                                 data-cat="{{ $service->parent?->name }}"
                                 data-status="{{ $resting ? 'resting' : 'active' }}"
                                 data-priced="{{ $hasPrice ? 'priced' : 'unpriced' }}">
                                <div>
                                    <div class="sp-name">{{ $service->name }}</div>
                                    <div class="sp-tags">
                                        @if ($resting)
                                            <span class="sp-tag rest">Resting</span>
                                        @else
                                            <span class="sp-tag ok">Active</span>
                                        @endif
                                        @if ($hasPrice) <span class="sp-tag">Ready to quote</span> @endif
                                        @if ($packaged) <span class="sp-tag pkg">In a package</span> @endif
                                    </div>
                                </div>
                                <div>
                                    <label class="sp-lab" for="price-{{ $service->id }}">Price</label>
                                    <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                                           id="price-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][price]"
                                           value="{{ $num($service->pivot->price) }}" placeholder="e.g. 1200">
                                </div>
                                <div>
                                    <label class="sp-lab" for="unit-{{ $service->id }}">Read as</label>
                                    <select class="sp-sel" id="unit-{{ $service->id }}" name="rows[{{ $service->id }}][price_unit]">
                                        @foreach ($units as $key => $label)
                                            <option value="{{ $key }}" @selected(($service->pivot->price_unit ?? 'from') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="sp-lab" for="hours-{{ $service->id }}">Includes</label>
                                    <input class="sp-in" type="number" min="1" max="24"
                                           id="hours-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][duration_hours]"
                                           value="{{ $service->pivot->duration_hours }}" placeholder="hours">
                                </div>
                                <div>
                                    <label class="sp-lab" for="extra-{{ $service->id }}">Extra hour</label>
                                    <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                                           id="extra-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][extra_hour_price]"
                                           value="{{ $num($service->pivot->extra_hour_price) }}" placeholder="optional">
                                </div>
                                <div>
                                    <span class="sp-lab">&nbsp;</span>
                                    {{-- Its own form, because a button inside the pricing form would
                                         submit that one. Resting keeps the price; see the migration. --}}
                                    <button type="submit" class="sp-mini"
                                            form="spToggle-{{ $service->id }}"
                                            title="{{ $resting ? 'Offer this again' : 'Stop offering this for now, keeping its price' }}">
                                        {{ $resting ? 'Resume' : 'Rest' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach

            <div class="sp-none" id="spNone" hidden>Nothing matches that. Clear the filters to see the rest.</div>

            <div class="sp-foot">
                <button type="submit" class="sp-save">Save pricing</button>
                <span class="sp-note">
                    {{ $priced }} of {{ $services->count() }} {{ $services->count() === 1 ? 'service has' : 'services have' }} a price.
                    A service with no price cannot answer a request on its own.
                </span>
            </div>
        </form>

        </div>

        {{-- Outside the pricing form: nesting a form in a form is not allowed,
             and the browser drops the inner one silently. --}}
        @foreach ($services as $service)
            <form id="spToggle-{{ $service->id }}" method="POST" action="{{ route('professional.services.toggle') }}" hidden>
                @csrf
                <input type="hidden" name="category_id" value="{{ $service->id }}">
            </form>
        @endforeach
    @endif
</div>

@endif

@if ($tab === 'templates')
    {{-- The reusable wording a Quick Bid goes out with. It is the one template
         this platform stores; the tab is not a heading over an empty page. --}}
    <div class="sp-card">
        <h2>Response template</h2>
        <p class="sub">The wording sent with every Quick Bid, so you are not retyping your terms on each reply.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="templates">
            <div>
                <label class="sp-lab" for="tplTerms">Your standard terms</label>
                <textarea class="sp-in" id="tplTerms" name="quick_bid_terms" rows="6" maxlength="600"
                          placeholder="e.g. Price includes setup and breakdown. A 30% deposit holds the date.">{{ old('quick_bid_terms', $rules->quick_bid_terms) }}</textarea>
                <p class="sp-note">Up to 600 characters. Leave it empty and a Quick Bid goes out with the price alone.</p>
            </div>
            <div class="sp-foot">
                <button type="submit" class="sp-btn">Save template</button>
            </div>
        </form>
    </div>
@endif

@if ($tab === 'addons')
    {{-- An extra is not a service. Nobody hires somebody for an extra hour of
         lighting with no lighting under it, so these are listed apart and
         priced apart. --}}
    <div class="sp-card">
        <h2>Add-ons and extras</h2>
        <p class="sub">The optional things you sell on top of a service. They appear beside your price when you quote.</p>

        @if ($addons->isEmpty())
            <div class="sp-none">Nothing yet. Add your first one below.</div>
        @else
            <table class="sp-table">
                <thead><tr><th>Extra</th><th>Price</th><th>Charged</th><th></th></tr></thead>
                <tbody>
                @foreach ($addons as $addon)
                    <tr>
                        <td><strong>{{ $addon->name }}</strong></td>
                        <td>{{ $addon->price !== null ? '$' . number_format((float) $addon->price, 2) : 'No price yet' }}</td>
                        <td>{{ ['flat' => 'Flat', 'hourly' => 'Per hour', 'per_person' => 'Per person'][$addon->price_unit] ?? 'Flat' }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('professional.services.addons.remove', $addon) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sp-mini">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('professional.services.addons.add') }}" class="sp-inline">
            @csrf
            <div>
                <label class="sp-lab" for="adName">Name</label>
                <input class="sp-in" type="text" id="adName" name="name" maxlength="80" required placeholder="e.g. Extra hour of lighting">
            </div>
            <div>
                <label class="sp-lab" for="adPrice">Price</label>
                <input class="sp-in" type="number" id="adPrice" name="price" step="0.01" min="0" placeholder="150">
            </div>
            <div>
                <label class="sp-lab" for="adUnit">Charged</label>
                <select class="sp-sel" id="adUnit" name="price_unit">
                    <option value="flat">Flat</option>
                    <option value="hourly">Per hour</option>
                    <option value="per_person">Per person</option>
                </select>
            </div>
            <button type="submit" class="sp-save">Add extra</button>
        </form>
        @error('name') <div class="sp-note" style="color:#b91c1c;">{{ $message }}</div> @enderror
    </div>
@endif

@if ($tab === 'rules')
    <div class="sp-card">
        <h2>Pricing rules</h2>
        <p class="sub">What you will not go below, and what you add on. Leave anything blank and it simply does not apply.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="rules">
            <div class="sp-three">
                <div>
                    <label class="sp-lab" for="rMin">Minimum booking</label>
                    <input class="sp-in" type="number" id="rMin" name="minimum_booking" step="0.01" min="0"
                           value="{{ old('minimum_booking', $rules->minimum_booking) }}" placeholder="e.g. 500">
                    <div class="sp-note">Blank means you have not set one. It does not mean zero.</div>
                </div>
                <div>
                    <label class="sp-lab" for="rWeekend">Weekend surcharge</label>
                    <input class="sp-in" type="number" id="rWeekend" name="weekend_surcharge_pct" min="0" max="100"
                           value="{{ old('weekend_surcharge_pct', $rules->weekend_surcharge_pct) }}" placeholder="percent">
                </div>
                <div>
                    <label class="sp-lab" for="rRush">Short notice surcharge</label>
                    <input class="sp-in" type="number" id="rRush" name="rush_surcharge_pct" min="0" max="100"
                           value="{{ old('rush_surcharge_pct', $rules->rush_surcharge_pct) }}" placeholder="percent">
                </div>
            </div>
            <button type="submit" class="sp-save" style="margin-top:14px;">Save pricing rules</button>
        </form>
    </div>
@endif

@if ($tab === 'travel')
    <div class="sp-card">
        <h2>Travel</h2>
        <p class="sub">How far you will go, and what you charge beyond the distance you cover for nothing.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="travel">
            <div class="sp-three">
                <div>
                    <label class="sp-lab" for="tFree">Free within</label>
                    <input class="sp-in" type="number" id="tFree" name="travel_free_miles" min="0" max="5000"
                           value="{{ old('travel_free_miles', $rules->travel_free_miles) }}" placeholder="miles">
                </div>
                <div>
                    <label class="sp-lab" for="tRate">Then per mile</label>
                    <input class="sp-in" type="number" id="tRate" name="travel_rate_per_mile" step="0.01" min="0"
                           value="{{ old('travel_rate_per_mile', $rules->travel_rate_per_mile) }}" placeholder="e.g. 1.50">
                </div>
                <div>
                    <label class="sp-lab" for="tMax">Will not travel past</label>
                    <input class="sp-in" type="number" id="tMax" name="travel_max_miles" min="0" max="5000"
                           value="{{ old('travel_max_miles', $rules->travel_max_miles) }}" placeholder="miles">
                </div>
            </div>
            <div class="sp-note" style="margin-top:10px;">
                This is what you charge to get there. Which states you can work in is a separate thing, set on your
                <a href="{{ route('professional.profile.index') }}">profile</a>.
            </div>
            <button type="submit" class="sp-save" style="margin-top:14px;">Save travel</button>
        </form>
    </div>
@endif

@if ($tab === 'quickbid')
    <div class="sp-card">
        <h2>Quick Bid</h2>
        <p class="sub">Answer a request with the prices you have already set, instead of working them out again.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="quickbid">
            <input type="hidden" name="quick_bid_enabled" value="0">
            <label class="sp-check">
                <input type="checkbox" name="quick_bid_enabled" value="1" @checked($rules->quick_bid_enabled)>
                <span>Fill a bid from my own prices</span>
            </label>
            <div class="sp-note">
                It fills the figure in. It never sends anything: a bid in your name is still yours to send.
                {{ $counts['quickbid'] }} of your {{ $counts['total'] }} services have a price it can use.
            </div>

            <div style="margin-top:14px;">
                <label class="sp-lab" for="qTerms">Terms to include</label>
                <textarea class="sp-in" id="qTerms" name="quick_bid_terms" rows="4" maxlength="600"
                          placeholder="e.g. Deposit due on booking. Final numbers one week before.">{{ old('quick_bid_terms', $rules->quick_bid_terms) }}</textarea>
            </div>

            <button type="submit" class="sp-save" style="margin-top:14px;">Save Quick Bid</button>
        </form>
    </div>
@endif

<div class="sp-card">
    <h2>Getting the most out of this page</h2>
    <ul class="sp-tips">
        <li>A service with a price can be quoted without you typing a number every time.</li>
        <li>The extra hour rate is what an approved time extension is charged at.</li>
        <li>Resting a service hides it from clients and keeps everything you set on it.</li>
        <li>Services already in a package are marked, so you know what a change reaches.</li>
    </ul>
</div>

<script>
(function () {
    /* Search and the two dropdowns narrow the same list. A group whose rows
       are all filtered out hides itself rather than sitting there empty. */
    window.spFilter = function () {
        var q      = (document.getElementById('spSearch') || {}).value || '';
        var cat    = (document.getElementById('spCat') || {}).value || '';
        var status = (document.getElementById('spStatus') || {}).value || '';
        var priced = (document.getElementById('spPriced') || {}).value || '';
        q = q.trim().toLowerCase();

        var match = function (el) {
            return (!q      || el.dataset.name.indexOf(q) !== -1)
                && (!cat    || el.dataset.cat === cat)
                && (!status || el.dataset.status === status)
                && (!priced || el.dataset.priced === priced);
        };

        /* The cards answer the same four controls the list does, so switching
           view never changes which services are on screen. */
        var cards = 0;
        document.querySelectorAll('[data-spcard]').forEach(function (card) {
            var ok = match(card);
            card.hidden = !ok;
            if (ok) { cards++; }
        });

        var shown = 0;

        document.querySelectorAll('[data-spgroup]').forEach(function (group) {
            var visible = 0;

            group.querySelectorAll('[data-sprow]').forEach(function (row) {
                var ok = match(row);
                row.hidden = !ok;
                if (ok) { visible++; }
            });

            group.hidden = visible === 0;
            var count = group.querySelector('[data-spcount]');
            if (count) { count.textContent = visible; }
            if (visible > 0 && (q || status || priced)) { group.open = true; }
            shown += visible;
        });

        var none = document.getElementById('spNone');
        if (none) { none.hidden = shown > 0; }
    };

    /* Card view is her screen; list view is the same services with their
       prices editable and their order settable. Both read the same filters. */
    function spView(which) {
        var card = document.getElementById('spCardView');
        var list = document.getElementById('spListView');
        if (!card || !list) { return; }

        card.hidden = which !== 'card';
        list.hidden = which !== 'list';

        document.querySelectorAll('.sp-view').forEach(function (b) {
            b.classList.toggle('on', b.dataset.view === which);
        });
    }

    document.querySelectorAll('.sp-view').forEach(function (b) {
        b.addEventListener('click', function () { spView(b.dataset.view); });
    });

    /* Reordering and editing a price both happen in the list, so the two
       buttons that offer them open it rather than promising a screen that
       does not exist. */
    var reorder = document.getElementById('spReorder');
    if (reorder) {
        reorder.addEventListener('click', function () {
            spView('list');
            document.getElementById('spListView').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    document.querySelectorAll('[data-spedit]').forEach(function (b) {
        b.addEventListener('click', function () {
            spView('list');
            var card = b.closest('[data-spcard]');
            var row  = card && document.querySelector('[data-sprow][data-name="' + card.dataset.name + '"]');
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                var price = row.querySelector('input[type=number]');
                if (price) { price.focus(); }
            }
        });
    });

    function spOpenAdd(e) {
        if (e) { e.preventDefault(); }
        var box = document.getElementById('spAddBox');
        if (!box) { return; }
        box.hidden = false;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var pick = document.getElementById('spAdd');
        if (pick) { pick.focus(); }
    }

    ['spAddOpen', 'spAddMore'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('click', spOpenAdd); }
    });

    /* A failed add bounces back with an error, so the box has to be open to
       show it rather than hiding the message it came back with. */
    if (document.querySelector('#spAddBox .sp-note')) { spOpenAdd(); }

    // One card menu open at a time, and clicking away closes it.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.sp-svc .more[open]').forEach(function (d) {
            if (!d.contains(e.target)) { d.removeAttribute('open'); }
        });
    });
})();
</script>
</div>

<aside class="sp-rail">
    <div class="sp-side">
        <h4>{!! $ico('bulb', 'style="color:#d97706;"') !!} Helpful Tips</h4>
        <ul class="sp-ticks">
            <li>{!! $ico('tick') !!} Add the services you offer with clear pricing and details.</li>
            <li>{!! $ico('tick') !!} Mark services as Quick Bid Ready to speed up your responses.</li>
            <li>{!! $ico('tick') !!} Use Add-ons &amp; Extras for optional items, such as lighting or extended hours.</li>
            <li>{!! $ico('tick') !!} Create Package Deals to bundle services for clients.</li>
        </ul>
        <a class="sp-link" href="{{ route('public.faq') }}">Learn more about Services &amp; Pricing {!! $ico('arrow') !!}</a>
    </div>

    <div class="sp-side">
        <h4>{!! $ico('eye') !!} Service Display on Your Profile</h4>
        <p>Active services are shown on your public profile and can be used for Quick Bid and package creation.</p>
        <a class="sp-btn" style="display:flex;justify-content:center;" href="{{ route('professional.profile.index') }}">View Public Profile</a>
    </div>

    <div class="sp-side">
        <h4>{!! $ico('help') !!} Need Help?</h4>
        <p>Check out our step-by-step guide or contact support if you have questions about setting up your services and pricing.</p>
        <a class="sp-btn sp-ghost" style="display:flex;justify-content:center;margin-bottom:9px;" href="{{ route('public.faq') }}">{!! $ico('out') !!} View Help Guide</a>
        <a class="sp-btn sp-ghost" style="display:flex;justify-content:center;" href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">{!! $ico('chat') !!} Contact Support</a>
    </div>
</aside>
</div>
@endsection
