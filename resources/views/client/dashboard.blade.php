@extends('layouts.client')

@section('title', 'Dashboard')
@section('page-subtitle', 'Send offers, hire, and plan your event.')

@push('styles')
<style>
    /* ═══════════════════ Dashboard-only overrides ═══════════════════
       Matches Khadija's "clients dashboard overview" mockup. Coral / warm
       accent on top of the layout's existing CSS-variable system so this
       file stays small and inherits theme toggling for free. */

    /* Date-range selector row */
    .od-daterange-row { display: flex; justify-content: flex-end; margin-bottom: 16px; }
    .od-daterange {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 12.5px; font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
    }
    .od-daterange svg { width: 14px; height: 14px; color: var(--text-muted); }
    .od-daterange-wrap { position: relative; }
    .od-daterange-wrap > summary { list-style: none; cursor: pointer; }
    .od-daterange-wrap > summary::-webkit-details-marker { display: none; }
    .od-daterange-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 30;
        min-width: 180px; background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 10px; padding: 5px; box-shadow: 0 12px 28px -14px rgba(15,27,53,.45); }
    .od-daterange-opt { display: block; padding: 8px 10px; border-radius: 7px; font-size: 13px;
        color: var(--text-primary); text-decoration: none; }
    .od-daterange-opt:hover { background: var(--bg-card-hover); }
    .od-daterange-opt.is-on { background: rgba(249,115,22,.10); color: var(--brand-text); font-weight: 700; }
    .od-daterange .chev { width: 13px; height: 13px; }

    /* Stats row — 4 cards with mini sparkline */
    .od-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 16px; }
    .od-stat {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
    }
    .od-stat-head { display: flex; align-items: flex-start; gap: 11px; margin-bottom: 8px; }
    .od-stat-ico {
        width: 34px; height: 34px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .od-stat-ico svg { width: 16px; height: 16px; }
    .od-stat-ico.coral  { background: rgba(249, 115, 22, 0.12); color: var(--brand-text); }
    .od-stat-ico.green  { background: rgba(16, 185, 129, 0.12); color: var(--ok-text); }
    .od-stat-ico.indigo { background: rgba(249, 115, 22, 0.12); color: var(--brand-text); }
    .od-stat-ico.pink   { background: rgba(236, 72, 153, 0.12); color: #ec4899; }
    .od-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 500; }
    .od-stat-value { font-size: 21px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; margin-top: 2px; }
    /* Footer row: delta + sublabel on the left, sparkline on the right —
       no overlap (the old absolute sparkline collided with the text). */
    .od-stat-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .od-stat-meta { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .od-stat-delta { font-size: 11.5px; font-weight: 700; color: var(--ok-text); display: inline-flex; align-items: center; gap: 2px; }
    .od-stat-delta.flat { color: var(--text-muted); }
    .od-stat-sub { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }
    .od-stat-spark { flex-shrink: 0; opacity: 0.9; }
    .od-stat-delta.is-down { color: var(--bad-text, #dc2626); }

    /* Client badges */
    .od-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .od-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700;
        color: var(--text-primary); background: rgba(249,115,22,.10); border: 1px solid rgba(249,115,22,.28);
        border-radius: 999px; padding: 5px 11px; }
    .od-badge i { font-style: normal; font-size: 13px; }
    .od-badge-next b { display: block; font-size: 12.5px; color: var(--text-primary); }
    .od-badge-next span { display: block; font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 2px 0 7px; }
    .od-badge-bar { height: 5px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .od-badge-bar i { display: block; height: 100%; background: var(--brand, #f97316); border-radius: 999px; }
    .od-badge-next small { display: block; margin-top: 5px; font-size: 11px; color: var(--text-muted); }

    /* Main grid: Emergency · Client Profile · Special Badges · Calendar */
    /* Top zone: left column = Emergency/Profile/Badges (row A) + Gigs/Bookings
       (row B) stacked; right column = Calendar as its own tall card. The two
       columns are independent so the calendar's height never stretches the
       left cards. */
    .od-top {
        display: grid;
        grid-template-columns: minmax(0, 3.05fr) 1.5fr;
        gap: 14px;
        margin-bottom: 16px;
        align-items: start;
    }
    .od-top-left { min-width: 0; display: flex; flex-direction: column; gap: 14px; }
    .od-row-a { display: grid; grid-template-columns: 0.82fr 1.05fr 1.2fr; gap: 14px; align-items: start; }
    .od-row-b { display: grid; grid-template-columns: 1.9fr 1.2fr; gap: 14px; align-items: start; }
    .od-top-right { min-width: 0; }
    .od-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
    }
    .od-card-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .od-card-title { font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .od-card-link {
        font-size: 12.5px; font-weight: 600;
        color: var(--brand-text); text-decoration: none;
    }
    .od-card-link:hover { text-decoration: underline; }

    /* Emergency request card */
    .od-card.od-emergency {
        display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
        background: linear-gradient(180deg, rgba(239,68,68,0.07), rgba(249,115,22,0.03));
        border-color: rgba(239,68,68,0.20);
    }
    .od-emerg-icon {
        width: 50px; height: 50px; border-radius: 50%;
        background: #ef4444; color: #fff;
        display: flex; align-items: center; justify-content: center;
        margin: 0 0 12px;
        box-shadow: 0 6px 16px rgba(239,68,68,0.35);
    }
    .od-emerg-icon svg { width: 22px; height: 22px; }
    .od-emerg-title { font-size: 12.5px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px; }
    .od-emerg-urgent { font-size: 9px; font-weight: 800; color: var(--bad-text); letter-spacing: 1.5px; margin-top: 4px; }
    .od-emerg-desc { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin: 9px 0 12px; }
    .od-emerg-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        width: 100%; padding: 10px;
        background: #dc2626; color: #fff;
        border-radius: 9px; font-size: 12px; font-weight: 700;
        text-decoration: none;
    }
    .od-emerg-btn:hover { background: #dc2626; }
    .od-emerg-btn svg { width: 14px; height: 14px; }
    [data-theme="dark"] .od-emerg-icon, [data-theme="dark"] .od-emerg-btn { background: #dc2626; }
    [data-theme="dark"] .od-emerg-btn:hover { background: #b91c1c; }

    /* Planner profile card */
    .od-profile-row { display: flex; gap: 16px; align-items: center; }
    /* Trusted-Planner medal — dark shield with a gold crown + stars and a
       green ribbon, matching the reference mockup. */
    .od-profile-badge {
        position: relative;
        width: 78px; height: 96px;
        flex-shrink: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 3px;
        background: linear-gradient(160deg, #2b3344 0%, #161c28 100%);
        clip-path: polygon(50% 0%, 100% 14%, 100% 62%, 50% 100%, 0 62%, 0 14%);
        box-shadow: inset 0 0 0 2px rgba(245,158,11,0.55), 0 6px 16px rgba(0,0,0,0.25);
    }
    .od-profile-badge .crown { width: 30px; height: 30px; color: var(--warn-text); }
    .od-profile-badge .stars { font-size: 9px; color: var(--warn-text); letter-spacing: 1px; line-height: 1; }
    .od-profile-ribbon {
        position: absolute;
        bottom: 6px; left: 50%; transform: translateX(-50%);
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        font-size: 7px; font-weight: 800;
        padding: 3px 8px;
        border-radius: 3px;
        white-space: nowrap;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .od-profile-info { flex: 1; min-width: 0; }
    .od-profile-name {
        display: flex; align-items: center; gap: 6px;
        font-size: 15px; font-weight: 700; color: var(--text-primary);
    }
    .od-profile-name .ic-check { color: var(--ok-text); }
    .od-profile-tier { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .od-profile-stats { font-size: 12.5px; color: var(--text-muted); margin-top: 4px; }
    .od-profile-rating { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 13px; }
    .od-profile-rating .star { color: var(--warn-text); }
    .od-progress-wrap { margin-top: 14px; }
    .od-progress-bar { width: 100%; height: 5px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .od-progress-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #f97316); border-radius: 999px; transition: width 0.4s ease; }
    .od-progress-meta {
        display: flex; justify-content: space-between;
        margin-top: 6px;
        font-size: 11.5px; color: var(--text-muted);
    }
    .od-profile-cta {
        display: inline-flex; align-items: center;
        margin-top: 12px;
        padding: 7px 14px;
        font-size: 12.5px; font-weight: 600;
        background: rgba(249, 115, 22, 0.10);
        color: var(--brand-text);
        border: 1px solid rgba(249, 115, 22, 0.25);
        border-radius: 8px;
        text-decoration: none;
    }
    .od-profile-cta:hover { background: rgba(249, 115, 22, 0.18); }

    /* Planner Badges grid — 5 across (2 rows of 5), soft pastel circles
       with the icon in the matching brand colour (matches design). */
    .od-badges {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }
    .od-badge {
        display: flex; flex-direction: column; align-items: center;
        text-align: center; gap: 6px;
        padding: 6px 3px;
    }
    .od-badge.locked { opacity: 0.45; }
    .od-badge-ico {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .od-badge-ico svg { width: 17px; height: 17px; }
    .od-badge.b-fast    .od-badge-ico { background: rgba(16,185,129,0.14);  color: var(--ok-text); }
    .od-badge.b-luxury  .od-badge-ico { background: rgba(245,158,11,0.14);  color: var(--warn-text); }
    .od-badge.b-repeat  .od-badge-ico { background: rgba(99,102,241,0.14);  color: var(--accent-text); }
    .od-badge.b-trend   .od-badge-ico { background: rgba(249,115,22,0.14);  color: var(--brand-text); }
    .od-badge.b-verify  .od-badge-ico { background: rgba(14,165,233,0.14);  color: #0ea5e9; }
    .od-badge.b-fave    .od-badge-ico { background: rgba(239,68,68,0.14);   color: var(--bad-text); }
    .od-badge.b-negot   .od-badge-ico { background: rgba(139,92,246,0.14);  color: var(--accent-text); }
    .od-badge.b-mega    .od-badge-ico { background: rgba(6,182,212,0.14);   color: #06b6d4; }
    .od-badge.b-emerg   .od-badge-ico { background: rgba(245,158,11,0.14);  color: var(--warn-text); }
    .od-badge.b-vip     .od-badge-ico { background: rgba(236,72,153,0.14);  color: #ec4899; }
    .od-badge-name { font-size: 9.5px; font-weight: 600; color: var(--text-secondary); line-height: 1.25; text-align: center; min-height: 24px; display: flex; align-items: flex-start; justify-content: center; }

    /* Calendar */
    .od-cal-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .od-cal-month { font-size: 14px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
    .od-cal-nav { display: flex; gap: 4px; }
    .od-cal-nav button, .od-cal-nav-btn {
        width: 26px; height: 26px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .od-cal-nav button:hover, .od-cal-nav-btn:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .od-cal-nav-btn { text-decoration: none; font-size: 15px; line-height: 1; }
    .od-cal-tabs { display: flex; gap: 4px; }
    .od-cal-tab {
        text-decoration: none;
        padding: 4px 10px;
        font-size: 11.5px; font-weight: 600;
        border-radius: 6px;
        color: var(--text-muted);
        cursor: pointer;
        border: 1px solid transparent;
    }
    .od-cal-tab.is-active {
        background: rgba(249, 115, 22, 0.10);
        color: var(--brand-text);
        border-color: rgba(249, 115, 22, 0.30);
    }
    .od-cal {
        display: grid;
        /* minmax(0, 1fr), not 1fr.
           A grid column's default minimum is its content, so the day cells
           refused to shrink past about 56px — at that point the seven columns
           no longer fitted the card and Saturday was cut off at the right edge.
           Anyone on a narrow screen, or zoomed in, lost a day of the week. */
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 4px;
        font-size: 12px;
    }
    .od-cal .od-cal-dow {
        font-size: 10.5px; font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        text-align: center; padding: 6px 0;
    }
    .od-cal-day {
        aspect-ratio: 1;
        min-width: 0;   /* same reason: let the column decide the width */
        border-radius: 8px;
        background: var(--bg-card-hover);
        border: 1px solid transparent;
        padding: 4px;
        display: flex; flex-direction: column;
        gap: 2px;
        cursor: pointer;
        transition: border-color 0.15s;
    }
    .od-cal-day:hover { border-color: rgba(249, 115, 22, 0.40); }
    .od-cal-day.muted { opacity: 0.4; }
    .od-cal-day.has-event { background: rgba(249, 115, 22, 0.05); }
    .od-cal-more { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

    /* Day view — an agenda, one line per booking, the way a calendar's day
       reads everywhere else. */
    .od-cal-agenda { display: flex; flex-direction: column; }
    .od-agenda-row { display: flex; align-items: center; gap: 10px; padding: 11px 4px;
        border-bottom: 1px solid var(--border-color); text-decoration: none; color: inherit; }
    .od-agenda-row:last-child { border-bottom: 0; }
    .od-agenda-row:hover { background: var(--bg-card-hover); }
    .od-agenda-time { flex: none; width: 68px; font-size: 12px; font-weight: 700;
        color: var(--text-muted); font-variant-numeric: tabular-nums; }
    .od-agenda-dot { flex: none; width: 8px; height: 8px; border-radius: 50%; }
    .od-agenda-body { min-width: 0; }
    .od-agenda-body b { display: block; font-size: 13.5px; color: var(--text-primary);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .od-agenda-body small { font-size: 11.5px; font-weight: 600; }
    .od-agenda-empty { font-size: 12.5px; opacity: .75; margin: 14px 2px; }

    /* ── While a panel is being fetched ──────────────────────────
       The click is answered at once — the control that was pressed takes its
       selected state immediately and the panel it changes goes quiet — so the
       wait reads as "working", not as "nothing happened". */
    .od-live { position: relative; transition: opacity .12s ease; }
    .od-live.is-busy { opacity: .45; pointer-events: none; }

    /* A thin indeterminate bar along the top of whatever is being replaced. */
    .od-live.is-busy::after {
        content: ''; position: absolute; left: 0; right: 0; top: 0; height: 2px;
        border-radius: 2px; overflow: hidden;
        background: linear-gradient(90deg,
            transparent 0%, var(--brand, #f97316) 35%, var(--brand, #f97316) 65%, transparent 100%);
        background-size: 42% 100%; background-repeat: no-repeat;
        animation: odSweep .9s linear infinite;
    }
    @keyframes odSweep {
        from { background-position: -45% 0; }
        to   { background-position: 145% 0; }
    }

    /* The control that was just pressed, before the answer arrives. */
    .od-cal-tab.is-pending, .od-daterange-opt.is-pending {
        background: rgba(249, 115, 22, 0.10); color: var(--brand-text);
        border-color: rgba(249, 115, 22, 0.30);
    }
    .od-cal-nav-btn.is-pending { background: var(--bg-card-hover); color: var(--text-primary); }

    @media (prefers-reduced-motion: reduce) {
        .od-live.is-busy::after { animation: none; background: var(--brand, #f97316); }
    }
    .od-cal-num { font-weight: 600; color: var(--text-primary); font-size: 11.5px; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; }
    /* Today — number sits in a solid orange circle (matches reference). */
    .od-cal-day.today .od-cal-num { background: #c2410c; color: #fff;  /* 2.80 -> 5.18 */ border-radius: 50%; font-weight: 800; }
    a.od-cal-event { text-decoration: none; }
    .od-cal-event {
        font-size: 8.5px; line-height: 1.15;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 600;
        /* allow wrap to 2 lines like the reference */
        white-space: normal;
        word-break: break-word;
        min-width: 0;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .od-cal-event.ev-coral  { background: rgba(249,115,22,0.15); color: var(--brand-text); }
    .od-cal-event.ev-pink   { background: rgba(239,68,68,0.15);  color: var(--bad-text); }
    .od-cal-event.ev-purple { background: rgba(99,102,241,0.15); color: #4338ca; }
    [data-theme="dark"] .od-cal-event.ev-coral  { color: #fdba74; }
    [data-theme="dark"] .od-cal-event.ev-pink   { color: #fca5a5; }
    [data-theme="dark"] .od-cal-event.ev-purple { color: #a5b4fc; }
    .od-cal-legend {
        display: flex; flex-wrap: wrap; gap: 12px;
        margin-top: 12px;
        font-size: 11px; color: var(--text-muted);
    }
    .od-cal-legend-dot {
        display: inline-block; width: 8px; height: 8px; border-radius: 50%;
        margin-right: 5px; vertical-align: middle;
    }

    /* My Gigs Overview (4 mini boxes) + Upcoming Bookings */
    .od-mini-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .od-mini {
        padding: 11px 8px;
        border-radius: 9px;
        background: var(--bg-card-hover);
        text-align: center;
        border: 1px solid var(--border-color);
    }
    .od-mini-value { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .od-mini-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .od-mini.col-coral { border-top: 3px solid #f97316; }
    .od-mini.col-amber { border-top: 3px solid #f59e0b; }
    .od-mini.col-green { border-top: 3px solid #10b981; }
    .od-mini.col-blue  { border-top: 3px solid #f97316; }

    .od-gigs-list { margin-top: 14px; display: flex; flex-direction: column; gap: 10px; }
    .od-gig-row {
        display: flex; align-items: center; gap: 11px;
        padding: 10px;
        border-radius: 9px;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        text-decoration: none;
        color: inherit;
    }
    .od-gig-row:hover { border-color: rgba(249, 115, 22, 0.40); }
    .od-gig-thumb {
        width: 34px; height: 34px; border-radius: 8px;
        background: rgba(249, 115, 22, 0.15);
        color: var(--brand-text);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .od-gig-info { flex: 1; min-width: 0; }
    .od-gig-name { font-size: 12.5px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .od-gig-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .od-pill {
        font-size: 10.5px; font-weight: 700;
        padding: 3px 8px; border-radius: 999px;
    }
    .od-pill.requested { background: rgba(249, 115, 22, 0.15); color: var(--brand-text); }
    .od-pill.confirmed { background: rgba(16, 185, 129, 0.15); color: var(--ok-text); }
    .od-pill.pending   { background: rgba(245, 158, 11, 0.15); color: var(--warn-text); }

    .od-empty-illus { padding: 26px 12px; text-align: center; color: var(--text-muted); font-size: 13px; }
    .od-empty-illus svg { width: 64px; height: 64px; opacity: 0.5; margin-bottom: 8px; }

    /* To-Do List + Attendee Management + Achievements + Activity row */
    .od-row-3 {
        display: grid;
        grid-template-columns: 1fr 1.55fr 0.95fr 1.05fr;
        gap: 14px;
        margin-bottom: 16px;
        align-items: start;
    }
    .od-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin: 6px 0 14px; }
    .od-tab {
        padding: 5px 11px;
        font-size: 11.5px; font-weight: 600;
        border-radius: 999px;
        background: var(--bg-card-hover);
        color: var(--text-muted);
        cursor: pointer;
        border: 1px solid transparent;
    }
    .od-tab.is-active {
        background: rgba(249, 115, 22, 0.15);
        color: var(--brand-text);
        border-color: rgba(249, 115, 22, 0.30);
    }
    .od-todo { display: flex; flex-direction: column; gap: 8px; }
    .od-todo-row {
        display: flex; align-items: center; gap: 9px;
        padding: 8px 10px;
        border-radius: 8px;
        background: var(--bg-card-hover);
    }
    .od-todo-check {
        width: 16px; height: 16px;
        border-radius: 4px;
        border: 1.5px solid var(--border-color);
        flex-shrink: 0;
    }
    .od-todo-text { flex: 1; min-width: 0; font-size: 12.5px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .od-todo-due { white-space: nowrap; flex-shrink: 0; }
    .od-todo-pri { flex-shrink: 0; }
    .od-todo-due { font-size: 11px; color: var(--text-muted); }
    .od-todo-pri {
        font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 999px;
        text-transform: uppercase;
    }
    .od-todo-pri.high   { background: rgba(239, 68, 68, 0.15); color: var(--bad-text); }
    .od-todo-pri.medium { background: rgba(245, 158, 11, 0.15); color: var(--warn-text); }
    .od-todo-pri.low    { background: rgba(16, 185, 129, 0.15); color: var(--ok-text); }

    /* Attendee summary — one line per event, linking to that event's list (R60) */
    .od-att-row { display: block; padding: 9px 0; border-bottom: 1px solid var(--border-color); text-decoration: none; }
    .od-att-row:last-child { border-bottom: 0; }
    .od-att-name { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .od-att-counts { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

    /* Achievements medal — layered gold "coin" with a ring, matching the
       reference award-rosette look (was a flat orange disc). */
    .od-ach-circle {
        position: relative;
        width: 84px; height: 84px;
        border-radius: 50%;
        /* Contrast: white numerals on #fcd34d measured 1.44:1 against a 3.0
           requirement. The lightest stop is what the text has to survive. */
        background: radial-gradient(circle at 50% 35%, #d97706 0%, #b45309 55%, #92400e 100%);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 24px; font-weight: 900;
        margin: 10px auto 8px;
        box-shadow: 0 8px 24px rgba(217, 119, 6, 0.35), inset 0 0 0 4px rgba(255,255,255,0.35), inset 0 0 0 7px rgba(217,119,6,0.4);
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    /* Two ribbon tails hanging below the medal */
    .od-ach-circle::before,
    .od-ach-circle::after {
        content: '';
        position: absolute;
        bottom: -14px;
        width: 14px; height: 26px;
        background: linear-gradient(180deg, #ef4444, #dc2626);
        z-index: -1;
    }
    .od-ach-circle::before { left: 32px; transform: rotate(8deg); clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 78%, 0 100%); }
    .od-ach-circle::after  { right: 32px; transform: rotate(-8deg); clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 78%, 0 100%); }
    .od-ach-label { text-align: center; font-size: 13px; color: var(--text-muted); margin-bottom: 12px; margin-top: 6px; }
    .od-ach-encouragement { text-align: center; font-size: 12.5px; color: var(--text-primary); font-weight: 500; margin-bottom: 12px; }

    /* Activity timeline */
    .od-activity { display: flex; flex-direction: column; gap: 14px; }
    .od-activity-row {
        display: flex; gap: 12px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border-color);
    }
    .od-activity-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .od-activity-ico {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .od-activity-ico svg { width: 16px; height: 16px; }
    .od-activity-ico.green { background: rgba(16, 185, 129, 0.15); color: var(--ok-text); }
    .od-activity-ico.blue  { background: rgba(99, 102, 241, 0.15); color: var(--accent-text); }
    .od-activity-ico.pink  { background: rgba(236, 72, 153, 0.15); color: #ec4899; }
    .od-activity-ico.amber { background: rgba(245, 158, 11, 0.15); color: var(--warn-text); }
    .od-activity-body { flex: 1; min-width: 0; }
    .od-activity-title { font-size: 13px; font-weight: 600; color: var(--text-primary); }
    .od-activity-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; display: flex; gap: 8px; align-items: center; }
    .od-activity-time { white-space: nowrap; }

    @media (max-width: 1500px) {
        .od-top { grid-template-columns: 1fr; }
        .od-row-3 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 1100px) {
        .od-stats { grid-template-columns: repeat(2, 1fr); }
        .od-top { grid-template-columns: 1fr; }
        .od-row-a { grid-template-columns: 1fr; }
        .od-row-b { grid-template-columns: 1fr; }
        .od-row-3 { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .od-stats { grid-template-columns: 1fr; }
        .od-badges { grid-template-columns: repeat(2, 1fr); }
        .od-mini-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endpush

@section('content')
@php
    /* Pre-compute the data the new design surfaces. Stats that the
       controller doesn't supply (Total Spent, Saved Pros, Achievements)
       are derived on the fly from existing models; some "social" stats
       are intentionally static placeholders until the relevant features
       ship — they're marked with comments so the next pass knows to wire
       them up. */
    $user = auth()->user();

    /* Total spent — money that actually left the client, i.e. completed
       payments. It used to sum bookings.total_amount / agreed_price, and
       NEITHER column exists (the amount lives in bookings.price), so the query
       threw every time and the catch swallowed it: the card read $0.00 for
       everyone, forever, and looked like a real answer. */
    /*
     * The period the two money/history cards answer for.
     *
     * The control above the cards was a <button> that printed the current
     * month and did nothing — and nothing on the page was filtered by a date
     * range at all, so it implied a filter that did not exist while every card
     * beneath it said "All time".
     *
     * Only the two cards that HAVE a period take one. Active Gigs and Saved
     * Professionals are counts of what is true right now, not of what happened
     * in a window, so they say so instead of pretending to narrow.
     */
    $periods = [
        'all'    => ['All time', null],
        'month'  => ['This month', \Carbon\Carbon::now()->startOfMonth()],
        'last'   => ['Last month', \Carbon\Carbon::now()->startOfMonth()->subMonth()],
        'q'      => ['Last 3 months', \Carbon\Carbon::now()->startOfMonth()->subMonths(2)],
        'year'   => ['This year', \Carbon\Carbon::now()->startOfYear()],
    ];

    $periodKey   = array_key_exists((string) request()->query('period'), $periods)
        ? (string) request()->query('period')
        : 'all';
    $periodLabel = $periods[$periodKey][0];
    $periodFrom  = $periods[$periodKey][1];
    $periodTo    = $periodKey === 'last'
        ? \Carbon\Carbon::now()->startOfMonth()->subMonth()->endOfMonth()
        : \Carbon\Carbon::now()->endOfDay();

    $inPeriod = fn ($query, string $column) => $periodFrom
        ? $query->whereBetween($column, [$periodFrom, $periodTo])
        : $query;

    $periodLink = fn (string $key) => route('client.dashboard', array_merge(
        request()->query(), ['period' => $key]
    ));

    $totalSpent = (float) $inPeriod(
        \App\Models\Payment::where('user_id', $user->id)->where('status', 'completed'),
        'created_at'
    )->sum('amount');

    /*
     * The four cards' trend lines.
     *
     * Each card carried a hand-drawn rising sparkline and "▲ 0%" — the same
     * seven points and the same zero for every client, so a card reading
     * $0.00 sat under a line climbing to the right. A picture of a trend
     * nobody has is worse than no picture: it is read as one.
     *
     * Six months of the client's own history, counted from the timestamps
     * that already exist. Where there is nothing to compare against, the card
     * says so rather than drawing a flat claim.
     */
    $trendMonths = collect(range(5, 0))->map(fn ($back) => \Carbon\Carbon::now()->startOfMonth()->subMonths($back));

    $seriesFor = function (callable $countFor) use ($trendMonths) {
        return $trendMonths->map(fn ($m) => (float) $countFor($m->copy(), $m->copy()->endOfMonth()))->all();
    };

    $spentSeries = $seriesFor(fn ($from, $to) => \App\Models\Payment::where('user_id', $user->id)
        ->where('status', 'completed')
        ->whereBetween('created_at', [$from, $to])
        ->sum('amount'));

    $completedSeries = $seriesFor(fn ($from, $to) => \App\Models\Booking::where('client_id', $user->id)
        ->where('status', 'completed')
        ->whereBetween('updated_at', [$from, $to])
        ->count());

    $savedSeries = $seriesFor(fn ($from, $to) => \Illuminate\Support\Facades\DB::table('saved_professionals')
        ->where('client_id', $user->id)
        ->whereBetween('created_at', [$from, $to])
        ->count());

    // Gigs opened per month — the card's own number is a point-in-time count,
    // so the line is what was started, which is the thing that has a history.
    $gigsSeries = $seriesFor(fn ($from, $to) => \App\Models\Event::where('client_id', $user->id)
        ->whereBetween('created_at', [$from, $to])
        ->count());

    $savedPros = $user->savedProfessionals()->count();

    /* This month against last, or nothing at all. A percentage needs a figure
       to be a percentage OF; last month at zero has none. */
    $trendDelta = function (array $series): ?array {
        $now  = (float) ($series[count($series) - 1] ?? 0);
        $prev = (float) ($series[count($series) - 2] ?? 0);

        if ($prev <= 0.0) {
            return null;
        }

        $pct = (int) round((($now - $prev) / $prev) * 100);

        return ['pct' => abs($pct), 'up' => $pct >= 0];
    };

    /* A polyline through the client's own months, scaled to its own peak. */
    $sparkPoints = function (array $series): ?string {
        $max = max($series);

        if ($max <= 0.0) {
            return null;   // nothing happened; draw nothing
        }

        $step = count($series) > 1 ? 60 / (count($series) - 1) : 60;

        return collect($series)
            ->map(fn ($v, $i) => round($i * $step, 1) . ',' . round(20 - ($v / $max) * 16, 1))
            ->implode(' ');
    };

    /*
     * Calendar.
     *
     * It rendered the current month and nothing else: the ‹ › buttons had no
     * handler, Today/Month/Week were <span>s that did nothing, and every entry
     * was painted the same colour beneath a legend naming four statuses the
     * grid never used. So the panel claimed a colour code it did not have, and
     * three of its five controls were decoration.
     *
     * The month or week being looked at is now part of the address, which is
     * what makes ‹ › work at all — and makes a particular month something the
     * client can bookmark or send to us.
     */
    $now = \Carbon\Carbon::now();

    $calView = in_array(request()->query('calview'), ['day', 'week'], true)
        ? request()->query('calview')
        : 'month';

    // A bad date in the address is not a broken page; it is this month.
    try {
        $calAnchor = request()->filled('cal')
            ? \Carbon\Carbon::createFromFormat('Y-m-d', (string) request()->query('cal'))->startOfDay()
            : $now->copy();
    } catch (\Throwable) {
        $calAnchor = $now->copy();
    }

    if ($calView === 'day') {
        // One day, read as a list — which is what "Today" means. It used to
        // jump the month grid to today's month, so pressing it on the month
        // you were already looking at changed nothing at all.
        $firstCalDate = $calAnchor->copy()->startOfDay();
        $lastCalDate  = $calAnchor->copy()->endOfDay();
        $calTitle     = $calAnchor->isToday()
            ? 'Today · ' . $calAnchor->format('D j M')
            : $calAnchor->format('D j M Y');
        $calPrev      = $calAnchor->copy()->subDay();
        $calNext      = $calAnchor->copy()->addDay();
    } elseif ($calView === 'week') {
        $firstCalDate = $calAnchor->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $lastCalDate  = $calAnchor->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
        $calTitle     = $firstCalDate->format('M j') . ' – ' . $lastCalDate->format('M j, Y');
        $calPrev      = $calAnchor->copy()->subWeek();
        $calNext      = $calAnchor->copy()->addWeek();
    } else {
        $monthStart   = $calAnchor->copy()->startOfMonth();
        $monthEnd     = $calAnchor->copy()->endOfMonth();
        $firstCalDate = $monthStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $lastCalDate  = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
        $calTitle     = $calAnchor->format('F Y');
        $calPrev      = $monthStart->copy()->subMonth();
        $calNext      = $monthStart->copy()->addMonth();
    }

    // Keeps whatever else is in the address — the date-range picker above sets
    // its own parameters and must survive a month change.
    $calLink = fn (array $over) => route('client.dashboard', array_merge(
        request()->query(), $over
    )) . '#calendar';

    $eventsByDate = \App\Models\Event::where('client_id', $user->id)
        ->whereBetween('starts_at', [$firstCalDate->copy()->startOfDay(), $lastCalDate->copy()->endOfDay()])
        ->orderBy('starts_at')
        ->get()
        ->groupBy(fn ($e) => $e->starts_at?->format('Y-m-d'));

    /*
     * The colour says the stage, and the legend lists exactly these.
     *
     * Event::stage() is already the one place that decides what an event's
     * state is — status against is_published, with status winning. Reading it
     * here means the calendar cannot disagree with the rest of the portal.
     */
    $calStages = [
        'confirmed' => ['Booked', '#10b981'],
        'open'      => ['Open for proposals', '#f59e0b'],
        'draft'     => ['Draft — not sent yet', '#9ca3af'],
        'completed' => ['Completed', '#6366f1'],
        'cancelled' => ['Cancelled', '#ef4444'],
    ];

    // Only the stages actually on screen. A legend listing states this month
    // does not contain is a legend explaining someone else's calendar.
    $calStagesShown = $eventsByDate->flatten()
        ->map(fn ($e) => $e->stage())
        ->unique()
        ->filter(fn ($st) => isset($calStages[$st]))
        ->values();

    /* No invented calendar entries. A client with nothing booked used to see
       five events they had never created -- Wedding, Baltimore MD; Brand
       Launch, Washington DC -- on their own calendar, "so it isn't blank".
       An empty calendar is the true answer, and the panel says so below. */

    // My Gigs Overview — split bookings by status (matches design columns).
    $gigsRequested = \App\Models\Booking::where('client_id', $user->id)->where('status', 'requested')->count();
    $gigsBidding   = \App\Models\Booking::where('client_id', $user->id)->where('status', 'pending')->count();
    $gigsHired     = \App\Models\Booking::where('client_id', $user->id)->where('status', 'confirmed')->count();
    $gigsDone      = $stats['completed_bookings'] ?? 0;

    /*
     * Checklist row 191 — "Active Gigs: 21" disagreed with the Gigs Overview
     * beside it.
     *
     * They were counting different things under one word. The tile read
     * OPEN EVENTS; the overview counts BOOKINGS by status. An event with no
     * professional hired yet is a request, not an active gig.
     *
     * Active now means what the overview means: requested plus hired. The two
     * widgets add up because one of them is the other's total.
     */
    $activeGigs = $gigsRequested + $gigsBidding + $gigsHired;

    /*
     * Rows 192 and 222 — the profile and badge widgets.
     *
     * Both were entirely hardcoded: "Trusted Planner", "Tier 3 of 6", "15
     * completed events", "4.6 (86 reviews)", "35 / 50", and ten badge icons
     * every client saw whether they had earned any. The figures did not even
     * agree with each other — 15 completed on one line, 35 on the next.
     *
     * Real numbers now, from the same ClientStats the Reports page and the
     * public portfolio read. No tier and no badges are claimed: the badge
     * rulebook is still PROPOSED, not locked, and inventing a ladder here
     * would be inventing the rule it is waiting on.
     */
    $clientFigures = \App\Support\ClientStats::for($user);

    // Upcoming bookings — confirmed + future.
    $upcomingBookings = \App\Models\Booking::where('client_id', $user->id)
        ->whereIn('status', ['confirmed', 'requested'])
        ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', now()))
        ->with(['event:id,title,starts_at', 'supplier:id,name'])
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get();

    // Recent activity — merge latest events + bookings into one feed.
    $activity = collect();
    foreach ($recentEvents->take(2) as $ev) {
        $activity->push([
            'type' => 'event', 'icon' => 'green',
            'title' => 'You posted a new gig',
            'meta'  => $ev->title,
            'when'  => $ev->created_at,
            'tag'   => ucfirst($ev->status ?? 'Requested'),
            'tag_class' => 'requested',
        ]);
    }
    foreach ($recentBookings->take(2) as $bk) {
        $activity->push([
            'type' => 'booking', 'icon' => 'blue',
            'title' => 'Proposal received',
            'meta'  => 'from ' . ($bk->supplier?->name ?? 'a professional'),
            'when'  => $bk->created_at,
            'tag'   => 'New',
            'tag_class' => 'confirmed',
        ]);
    }
    $activity = $activity->sortByDesc('when')->take(4);

    // Planner tier — derived from completed events count. Brand wants this
    // wired to a config table eventually; static thresholds for now.
    // Lifetime — the badge tiers below are an achievement, not a window.
    $completedEvents = $stats['completed_bookings'] ?? 0;

    // What the card answers for, which does take the period.
    $completedInPeriod = $periodFrom
        ? \App\Models\Booking::where('client_id', $user->id)->where('status', 'completed')
            ->whereBetween('updated_at', [$periodFrom, $periodTo])->count()
        : $completedEvents;
    $tiers = [
        ['name' => 'New Planner',     'min' => 0,   'max' => 5],
        ['name' => 'Rising Planner',  'min' => 5,   'max' => 15],
        ['name' => 'Trusted Planner', 'min' => 15,  'max' => 50],
        ['name' => 'Elite Planner',   'min' => 50,  'max' => 200],
        ['name' => 'Master Planner',  'min' => 200, 'max' => 1000],
    ];
    $currentTier = collect($tiers)->first(fn ($t) => $completedEvents >= $t['min'] && $completedEvents < $t['max']) ?? $tiers[0];
    $nextTier    = collect($tiers)->first(fn ($t) => $t['min'] > $completedEvents) ?? end($tiers);
    $tierIndex   = array_search($currentTier['name'], array_column($tiers, 'name')) + 1;
    $tierTotal   = count($tiers);
    $tierProgress = $currentTier['max'] > $currentTier['min']
        ? min(100, (($completedEvents - $currentTier['min']) / ($currentTier['max'] - $currentTier['min'])) * 100)
        : 0;
@endphp

{{-- ── Date range selector (top-right) ──────────────────────── --}}
{{-- A real period, not a label. This printed the current month and did
     nothing, above four cards that all said "All time". --}}
<div class="od-daterange-row od-live" id="odPeriod">
    <details class="od-daterange-wrap">
        <summary class="od-daterange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ $periodLabel }}
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="od-daterange-menu">
            @foreach($periods as $key => [$label, $from])
                <a class="od-daterange-opt {{ $key === $periodKey ? 'is-on' : '' }}"
                   data-live href="{{ $periodLink($key) }}">{{ $label }}</a>
            @endforeach
        </div>
    </details>
</div>

{{-- ── Stats row (4 cards) ────────────────────────────────────── --}}
<div class="od-stats od-live" id="odStats">
    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico coral">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Total Spent</div>
                <div class="od-stat-value">${{ number_format($totalSpent, 2) }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div>
                @if($__d = $trendDelta($spentSeries))
                    <span class="od-stat-delta {{ $__d['up'] ? '' : 'is-down' }}">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="{{ $__d['up'] ? '18 15 12 9 6 15' : '18 9 12 15 6 9' }}"/>
                        </svg>{{ $__d['pct'] }}%
                    </span>
                @endif
                <span class="od-stat-sub">{{ $periodLabel }}</span>
            </div>
            @if($__pts = $sparkPoints($spentSeries))
                <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none" aria-hidden="true"><polyline points="{{ $__pts }}" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Active Gigs</div>
                <div class="od-stat-value">{{ $activeGigs }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div>
                @if($__d = $trendDelta($gigsSeries))
                    <span class="od-stat-delta {{ $__d['up'] ? '' : 'is-down' }}">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="{{ $__d['up'] ? '18 15 12 9 6 15' : '18 9 12 15 6 9' }}"/>
                        </svg>{{ $__d['pct'] }}%
                    </span>
                @endif
                <span class="od-stat-sub">In progress</span>
            </div>
            @if($__pts = $sparkPoints($gigsSeries))
                <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none" aria-hidden="true"><polyline points="{{ $__pts }}" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico indigo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Completed Events</div>
                <div class="od-stat-value">{{ number_format($completedInPeriod) }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div>
                @if($__d = $trendDelta($completedSeries))
                    <span class="od-stat-delta {{ $__d['up'] ? '' : 'is-down' }}">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="{{ $__d['up'] ? '18 15 12 9 6 15' : '18 9 12 15 6 9' }}"/>
                        </svg>{{ $__d['pct'] }}%
                    </span>
                @endif
                <span class="od-stat-sub">{{ $periodLabel }}</span>
            </div>
            @if($__pts = $sparkPoints($completedSeries))
                <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none" aria-hidden="true"><polyline points="{{ $__pts }}" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico pink">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Saved Professionals</div>
                {{-- Was the literal 0, whatever the client had saved. --}}
                <div class="od-stat-value">{{ number_format($savedPros) }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div>
                @if($__d = $trendDelta($savedSeries))
                    <span class="od-stat-delta {{ $__d['up'] ? '' : 'is-down' }}">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="{{ $__d['up'] ? '18 15 12 9 6 15' : '18 9 12 15 6 9' }}"/>
                        </svg>{{ $__d['pct'] }}%
                    </span>
                @endif
                <span class="od-stat-sub">Favorites</span>
            </div>
            @if($__pts = $sparkPoints($savedSeries))
                <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none" aria-hidden="true"><polyline points="{{ $__pts }}" stroke="#ec4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @endif
        </div>
    </div>
</div>

{{-- ── Top zone: Emergency·Profile·Badges + Gigs·Bookings (left, 2 rows) · Calendar (right, spans both) ── --}}
<div class="od-top">
<div class="od-top-left">
    <div class="od-row-a">

    {{-- Emergency Request --}}
    <div class="od-card od-emergency">
        <div class="od-emerg-icon">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h6l-1 8 10-12h-6l1-8z"/></svg>
        </div>
        <div class="od-emerg-title">EMERGENCY REQUEST</div>
        <div class="od-emerg-urgent">URGENT</div>
        <p class="od-emerg-desc">Need help now? Post your request and verified pros can apply right away.</p>
        <a href="{{ route('client.esr.create') }}" class="od-emerg-btn">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h6l-1 8 10-12h-6l1-8z"/></svg>
            Post a Rush Request
        </a>
    </div>

    {{-- Your Client Profile --}}
    <div class="od-card od-a-profile">
        <div class="od-card-head">
            <span class="od-card-title">Your Client Profile</span>
        </div>
        <div class="od-profile-row">
            <div class="od-profile-badge">
                <svg class="crown" viewBox="0 0 24 24" fill="currentColor"><path d="M2 6l4 4 6-7 6 7 4-4v12H2z"/><circle cx="2" cy="5" r="1.4"/><circle cx="22" cy="5" r="1.4"/><circle cx="12" cy="2.5" r="1.4"/></svg>
                @if($clientFigures['rating'])
                    <span class="stars">{{ str_repeat('★', (int) round($clientFigures['rating'])) }}</span>
                @endif
            </div>
            <div class="od-profile-info">
                <div class="od-profile-name">
                    {{ $user->name }}
                    @if($user->hasVerifiedEmail())
                        <svg class="ic-check" width="15" height="15" viewBox="0 0 24 24" fill="#10b981" stroke="#fff" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11" fill="none"/></svg>
                    @endif
                </div>
                <div class="od-profile-stats">
                    {{ number_format($clientFigures['completed_events']) }} {{ \Illuminate\Support\Str::plural('event', $clientFigures['completed_events']) }} completed
                </div>
                <div class="od-profile-rating">
                    @if($clientFigures['reviews_count'] > 0)
                        <span class="star">★</span>
                        <span style="color:var(--text-muted);">
                            {{ number_format($clientFigures['rating'], 1) }}
                            ({{ $clientFigures['reviews_count'] }} from professionals)
                        </span>
                    @else
                        <span style="color:var(--text-muted);">No reviews from professionals yet</span>
                    @endif
                </div>
            </div>
        </div>
        {{-- No tier bar. The badge and tier rulebook is still proposed, not
             locked, so a ladder here would invent the rule it is waiting on.
             What was here read "Tier 3 of 6 · 15 completed events" above
             "35 / 50 events" — two different totals for one client. --}}
        {{-- "View my public profile" led to the client portfolio, removed
             2026-08-25. A client has no public profile to view. --}}
        <a href="{{ route('client.profile.index') }}" class="od-profile-cta">Profile &amp; settings</a>
    </div>

    {{-- Client Special Badges — checklist row 192.

         The widget showed ten badge icons to every client, earned or not,
         while a count elsewhere on the page said something different. Nobody
         had ten badges; nobody had any, because no badge has an award rule
         yet — the badge rulebook is PROPOSED, not locked.

         So it shows what is true. Ten decorative icons implying ten
         achievements is the kind of thing a client notices on the first
         screen and stops trusting the rest of the page over. --}}
    <div class="od-card od-a-badges">
        <div class="od-card-head">
            <span class="od-card-title">Client Badges</span>
        </div>

        {{-- The card said what earns a badge and then never awarded one. These
             are those same three sentences, measured: events completed, paid on
             or before the balance was due, and professionals booked more than
             once. Counted from the record on every load, so a badge cannot
             outlive the thing that earned it. --}}
        @php $__badges = \App\Domain\Badges\ClientBadges::progressFor($user); @endphp

        @if($__badges->where('earned', true)->isNotEmpty())
            <div class="od-badges">
                @foreach($__badges->where('earned', true) as $b)
                    <span class="od-badge" title="{{ $b['blurb'] }}">
                        <i>{{ $b['icon'] }}</i>{{ $b['name'] }}
                    </span>
                @endforeach
            </div>
        @endif

        @php $__next = $__badges->where('earned', false)->first(); @endphp

        @if($__next)
            {{-- What is left to do, rather than an empty panel that reads as
                 "you have nothing". --}}
            <div class="od-badge-next">
                <b>{{ $__next['icon'] }} {{ $__next['name'] }}</b>
                <span>{{ $__next['blurb'] }}</span>
                <div class="od-badge-bar"><i style="width: {{ $__next['need'] > 0 ? round(($__next['progress'] / $__next['need']) * 100) : 0 }}%;"></i></div>
                <small>{{ $__next['progress'] }} of {{ $__next['need'] }}</small>
            </div>
        @elseif($__badges->isNotEmpty())
            <p style="font-size:12.5px;color:var(--text-muted);line-height:1.6;margin:10px 0 0;">
                That is every badge — all of them earned.
            </p>
        @endif
    </div>


    </div>{{-- /.od-row-a --}}

    <div class="od-row-b">
    {{-- Your Gigs Overview --}}
    <div class="od-card od-a-gigs">
        <div class="od-card-head">
            <span class="od-card-title">Your Gigs Overview</span>
            <a href="{{ route('client.events.index') }}" class="od-card-link">View All Gigs</a>
        </div>
        <div class="od-mini-grid">
            <div class="od-mini col-coral"><div class="od-mini-value">{{ $gigsRequested }}</div><div class="od-mini-label">Requested</div></div>
            <div class="od-mini col-amber"><div class="od-mini-value">{{ $gigsBidding }}</div><div class="od-mini-label">Bidding</div></div>
            <div class="od-mini col-blue"><div class="od-mini-value">{{ $gigsHired }}</div><div class="od-mini-label">Hired</div></div>
            <div class="od-mini col-green"><div class="od-mini-value">{{ $gigsDone }}</div><div class="od-mini-label">Completed</div></div>
        </div>

        @if($recentEvents->count())
            <div class="od-gigs-list">
                @foreach($recentEvents->take(2) as $event)
                    <a href="{{ route('client.events.show', $event) }}" class="od-gig-row">
                        <div class="od-gig-thumb"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                        <div class="od-gig-info">
                            <div class="od-gig-name">{{ $event->title }}</div>
                            <div class="od-gig-meta">
                                {{ $event->starts_at?->format('M d, Y') ?? '—' }}
                                @if(isset($event->budget) && $event->budget) · Budget: ${{ number_format($event->budget, 0) }} @endif
                            </div>
                        </div>
                        <span class="od-pill {{ in_array($event->status, ['confirmed', 'published']) ? 'confirmed' : 'requested' }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <a href="{{ route('client.post-event.choose') }}" class="od-profile-cta" style="margin-top: 14px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Post an Event
        </a>
    </div>

    {{-- Upcoming Bookings --}}
    <div class="od-card od-a-bookings">
        <div class="od-card-head">
            <span class="od-card-title">Upcoming Bookings</span>
            <a href="{{ route('client.bookings.index') }}" class="od-card-link">View All</a>
        </div>
        @if($upcomingBookings->count())
            <div class="od-gigs-list">
                @foreach($upcomingBookings as $bk)
                    <div class="od-gig-row">
                        <div class="od-gig-thumb"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div>
                        <div class="od-gig-info">
                            <div class="od-gig-name">{{ $bk->event?->title ?? 'Booking' }}</div>
                            <div class="od-gig-meta">
                                {{ $bk->supplier?->name ?? '—' }}
                                @if($bk->event?->starts_at) · {{ $bk->event->starts_at->format('M d, Y') }} @endif
                            </div>
                        </div>
                        <span class="od-pill {{ $bk->status }}">{{ ucfirst($bk->status) }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="od-empty-illus">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <div>You have no upcoming bookings.</div>
                <div style="font-size:11.5px;margin-top:4px;">Once you book a professional, your upcoming events will appear here.</div>
            </div>
        @endif
    </div>
    </div>{{-- /.od-row-b --}}
    </div>{{-- /.od-top-left --}}

    {{-- Calendar — its own tall card on the right column --}}
    <div class="od-top-right">
        <div class="od-card od-live" id="odCalCard">
            <div class="od-card-head">
                <span class="od-card-title">My Calendar &amp; Availability</span>
            </div>
            {{-- Links, not buttons with nothing behind them: the month being
                 looked at is in the address, so ‹ › work without script and
                 the view survives a reload or a shared link. --}}
            <div class="od-cal-head" id="calendar">
                <div class="od-cal-month">
                    <a class="od-cal-nav-btn" data-live href="{{ $calLink(['cal' => $calPrev->format('Y-m-d')]) }}"
                       aria-label="{{ $calView === 'week' ? 'Previous week' : 'Previous month' }}">‹</a>
                    <a class="od-cal-nav-btn" data-live href="{{ $calLink(['cal' => $calNext->format('Y-m-d')]) }}"
                       aria-label="{{ $calView === 'week' ? 'Next week' : 'Next month' }}">›</a>
                    {{ $calTitle }}
                </div>
                <div class="od-cal-tabs">
                    <a class="od-cal-tab {{ $calView === 'day' ? 'is-active' : '' }}" data-live
                       href="{{ $calLink(['calview' => 'day', 'cal' => $now->format('Y-m-d')]) }}">Today</a>
                    <a class="od-cal-tab {{ $calView === 'month' ? 'is-active' : '' }}" data-live
                       href="{{ $calLink(['calview' => 'month', 'cal' => $calAnchor->format('Y-m-d')]) }}">Month</a>
                    <a class="od-cal-tab {{ $calView === 'week' ? 'is-active' : '' }}" data-live
                       href="{{ $calLink(['calview' => 'week', 'cal' => $calAnchor->format('Y-m-d')]) }}">Week</a>
                </div>
            </div>
            @if($calView === 'day')
                {{-- One day, listed. A seven-column grid holding a single
                     column of one is not a day view. --}}
                @php $dayList = $eventsByDate->get($calAnchor->format('Y-m-d'), collect()); @endphp
                <div class="od-cal-agenda">
                    @forelse($dayList as $ev)
                        @php
                            $stage = $ev->stage();
                            [$stageLabel, $stageColour] = $calStages[$stage] ?? ['Event', '#f97316'];
                        @endphp
                        <a class="od-agenda-row" href="{{ route('client.events.show', $ev) }}">
                            <span class="od-agenda-time">{{ $ev->starts_at?->format('g:i A') ?? 'All day' }}</span>
                            <span class="od-agenda-dot" style="background:{{ $stageColour }};"></span>
                            <span class="od-agenda-body">
                                <b>{{ $ev->title }}</b>
                                <small style="color:{{ $stageColour }};">{{ $stageLabel }}</small>
                            </span>
                        </a>
                    @empty
                        <p class="od-agenda-empty">
                            Nothing on {{ $calAnchor->isToday() ? 'today' : $calAnchor->format('D j M') }}.
                            <a href="{{ $calLink(['calview' => 'month', 'cal' => $calAnchor->format('Y-m-d')]) }}" data-live style="font-weight:600;">See the month</a>
                        </p>
                    @endforelse
                </div>
            @else
            <div class="od-cal">
                @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dow)
                    <div class="od-cal-dow">{{ $dow }}</div>
                @endforeach
                @php
                    $cursor = $firstCalDate->copy();
                    $todayKey = $now->format('Y-m-d');
                @endphp
                @while($cursor <= $lastCalDate)
                    @php
                        $key      = $cursor->format('Y-m-d');
                        // In the week view every day on screen belongs to it.
                        $inMonth  = $calView === 'week' || $cursor->month === $calAnchor->month;
                        $dayEvs   = $eventsByDate->get($key, collect());

                        $classes  = 'od-cal-day';
                        if (!$inMonth)                      $classes .= ' muted';
                        if ($key === $todayKey)             $classes .= ' today';
                        if ($dayEvs->count())               $classes .= ' has-event';
                    @endphp
                    <div class="{{ $classes }}">
                        <div class="od-cal-num">{{ $cursor->day }}</div>
                        @foreach($dayEvs->take(2) as $ev)
                            @php
                                $stage = $ev->stage();
                                [$stageLabel, $stageColour] = $calStages[$stage] ?? ['Event', '#f97316'];
                            @endphp
                            {{-- The entry opens the event, and its colour is the
                                 stage the rest of the portal reports for it. --}}
                            <a class="od-cal-event" href="{{ route('client.events.show', $ev) }}"
                               style="background:{{ $stageColour }}1f;color:{{ $stageColour }};"
                               title="{{ $ev->title }} — {{ $stageLabel }}">{{ \Illuminate\Support\Str::limit($ev->title, 10) }}</a>
                        @endforeach
                        @if($dayEvs->count() > 2)
                            <div class="od-cal-more">+{{ $dayEvs->count() - 2 }} more</div>
                        @endif
                    </div>
                    @php $cursor->addDay(); @endphp
                @endwhile
            </div>
            @endif
            @if($eventsByDate->isEmpty() && $calView !== 'day')
                {{-- Says the true thing instead of filling the grid with
                     events the client never created. --}}
                <p style="font-size:12.5px;opacity:.7;margin:10px 2px 0;">
                    Nothing scheduled {{ $calView === 'week' ? 'this week' : 'this month' }}.
                    <a href="{{ route('client.post-event.choose') }}" style="font-weight:600;">Post an event</a> to see it here.
                </p>
            @endif

            {{-- Only what is on screen. The legend used to name Booked, Pending,
                 On Hold and Unavailable — four states this calendar never drew,
                 under entries that were all one colour. --}}
            @if($calStagesShown->isNotEmpty())
                <div class="od-cal-legend">
                    @foreach($calStagesShown as $stage)
                        <span><span class="od-cal-legend-dot" style="background:{{ $calStages[$stage][1] }};"></span>{{ $calStages[$stage][0] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Row 3: To-Do · Attendee Management · Achievements · Recent Activity ── --}}
<div class="od-row-3">
    <div class="od-card">
        <div class="od-card-head">
            <span class="od-card-title">My To-Do List</span>
        </div>
        {{-- Real outstanding work, from the controller. The four chores and the
             tab counts that used to sit here were hardcoded and counted
             nothing. Every row below links to the screen that clears it. --}}
        <div class="od-todo">
            @forelse($todos as $todo)
                <a class="od-todo-row" href="{{ $todo['url'] }}" style="text-decoration:none;color:inherit;">
                    <span class="od-todo-check"></span>
                    <span class="od-todo-text">
                        {{ $todo['title'] }}
                        <small style="display:block;font-size:11.5px;opacity:.65;font-weight:400;">{{ $todo['meta'] }}</small>
                    </span>
                    <span class="od-todo-pri {{ $todo['level'] }}">{{ $todo['level'] === 'high' ? 'Needs you' : 'When you can' }}</span>
                </a>
            @empty
                <div class="od-todo-row" style="opacity:.7">
                    <span class="od-todo-text">Nothing needs you right now.</span>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Attendee Management — Rule R60.

         This card used to be an account-wide table: 120/75/10/35 hardcoded,
         five invented guests with invented email addresses, and Add / Import /
         edit / delete buttons wired to nothing. Worse than fabricated, it was
         ungrouped — a client running two weddings the same month saw one flat
         list with nothing saying which guest belonged to which event. That is
         Developer Checklist row 223, and R60's answer is that the list lives
         on the event and the dashboard shows a summary that links back. --}}
    <div class="od-card">
        <div class="od-card-head">
            <span class="od-card-title">Attendee Management</span>
            <a class="od-card-link" href="{{ route('client.events.index') }}">Manage Events</a>
        </div>
        @forelse($attendeeSummaries as $row)
            <a class="od-att-row" href="{{ route('client.events.show', ['event' => $row['id'], 'tab' => 'attendees']) }}">
                <span class="od-att-name">{{ $row['title'] }}</span>
                <span class="od-att-counts">
                    {{ $row['total'] }} Guests
                    · <b style="color:var(--ok-text);">{{ $row['confirmed'] }}</b> Confirmed
                    · <b style="color:var(--bad-text);">{{ $row['cancelled'] }}</b> Cancelled
                    · <b style="color:var(--text-muted);">{{ $row['no_response'] }}</b> No Response
                </span>
            </a>
        @empty
            <p style="font-size:12.5px;color:var(--text-muted);margin:6px 0 0;">
                No guest lists yet. Open an event and add guests there — each event keeps its own list.
            </p>
        @endforelse
    </div>

    {{-- OA-134: "Your Achievements — 15 Badges Earned. Keep going! You're
         doing great." The 15 was a literal in this template. It counted
         nothing, and it sat on the same dashboard as the Client Badges card
         above, which correctly says the client has none — the page told them
         both at once.

         Removed rather than made to count something. No badge has an award
         rule yet; the rulebook is PM-14 and is still being written. When it
         exists, the honest card above is where the count belongs. --}}

    <div class="od-card">
        <div class="od-card-head">
            <span class="od-card-title">Recent Activity</span>
        </div>
        @if($activity->count())
            <div class="od-activity">
                @foreach($activity as $a)
                    <div class="od-activity-row">
                        <div class="od-activity-ico {{ $a['icon'] }}">
                            @if($a['icon'] === 'green')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            @endif
                        </div>
                        <div class="od-activity-body">
                            <div class="od-activity-title">{{ $a['title'] }}</div>
                            <div class="od-activity-meta">
                                <span>{{ \Illuminate\Support\Str::limit($a['meta'], 40) }}</span>
                                <span class="od-pill {{ $a['tag_class'] }}">{{ $a['tag'] }}</span>
                            </div>
                        </div>
                        <span class="od-activity-time" style="font-size:11px;color:var(--text-muted);">{{ $a['when']?->format('M d, Y') ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="od-empty-illus" style="padding: 18px 6px;">No activity yet.</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/*
 * The calendar and the period selector change what is on screen without
 * taking the whole page with them.
 *
 * Both are plain links, and they stay plain links — the month is in the
 * address so it survives a reload, a bookmark and a shared URL, and the page
 * still works with this script switched off. What this adds is that a click
 * fetches the same address and swaps only the two panels that changed, which
 * is what makes it feel immediate instead of a full reload.
 */
(function () {
    // The period control is swapped too: it names the period being shown, so
    // leaving it behind would let the button and the cards disagree.
    var PANELS = ['odPeriod', 'odStats', 'odCalCard'];
    var busy = 0;

    function swap(doc, id) {
        var here = document.getElementById(id), fresh = doc.getElementById(id);
        if (here && fresh) here.replaceWith(fresh);
    }

    function each(fn) {
        PANELS.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) fn(el);
        });
    }

    /* Held back by a beat.
       The answer usually arrives in well under a tenth of a second, and a
       loading state that appears and disappears inside that reads as a flicker
       — worse than no loading state at all. It is shown only once the wait is
       long enough to be noticed. */
    var BUSY_AFTER_MS = 140;
    var busyTimer = null;

    function busyOn() {
        clearTimeout(busyTimer);
        busyTimer = setTimeout(function () {
            each(function (el) {
                el.classList.add('od-live', 'is-busy');
                el.setAttribute('aria-busy', 'true');
            });
        }, BUSY_AFTER_MS);
    }

    function busyOff() {
        clearTimeout(busyTimer);
        each(function (el) {
            el.classList.remove('is-busy');
            el.setAttribute('aria-busy', 'false');
        });
    }

    function go(url, push) {
        var mine = ++busy;
        busyOn();

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                // A newer click already won; this answer is out of date.
                if (mine !== busy) return;

                var doc = new DOMParser().parseFromString(html, 'text/html');
                PANELS.forEach(function (id) { swap(doc, id); });

                if (push !== false) history.pushState({ odUrl: url }, '', url);
            })
            // Whatever went wrong, the link still works the ordinary way.
            .catch(function () { window.location.href = url; })
            .then(function () { if (mine === busy) busyOff(); });
    }

    // Delegated: both panels are replaced wholesale, so a listener bound to
    // the links themselves would die on the first click.
    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[data-live]') : null;
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;

        e.preventDefault();

        // Close the period menu behind the click.
        var open = a.closest('details');
        if (open) open.open = false;

        /* Answer the press before the server does.
           The control that was clicked takes the selected look straight away,
           so the wait reads as "working" rather than as a dead button — the
           swap replaces these nodes a moment later anyway. */
        var group = a.closest('.od-cal-tabs, .od-daterange-menu');
        if (group) {
            group.querySelectorAll('.is-active, .is-pending').forEach(function (el) {
                el.classList.remove('is-active', 'is-pending');
            });
        }
        a.classList.add('is-pending');

        go(a.href);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.odUrl) go(e.state.odUrl, false);
    });
})();
</script>
@endpush

@endsection
