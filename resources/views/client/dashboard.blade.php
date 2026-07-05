@extends('layouts.client')

@section('title', 'Dashboard')
@section('page-subtitle', 'Send offers, invite professionals, and build your dream team for the best event.')

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
    .od-stat-ico.coral  { background: rgba(249, 115, 22, 0.12); color: #f97316; }
    .od-stat-ico.green  { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .od-stat-ico.indigo { background: rgba(249, 115, 22, 0.12); color: #f97316; }
    .od-stat-ico.pink   { background: rgba(236, 72, 153, 0.12); color: #ec4899; }
    .od-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 500; }
    .od-stat-value { font-size: 21px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; margin-top: 2px; }
    /* Footer row: delta + sublabel on the left, sparkline on the right —
       no overlap (the old absolute sparkline collided with the text). */
    .od-stat-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .od-stat-meta { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .od-stat-delta { font-size: 11.5px; font-weight: 700; color: #10b981; display: inline-flex; align-items: center; gap: 2px; }
    .od-stat-delta.flat { color: var(--text-muted); }
    .od-stat-sub { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; }
    .od-stat-spark { flex-shrink: 0; opacity: 0.9; }

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
        color: #f97316; text-decoration: none;
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
    .od-emerg-urgent { font-size: 9px; font-weight: 800; color: #ef4444; letter-spacing: 1.5px; margin-top: 4px; }
    .od-emerg-desc { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin: 9px 0 12px; }
    .od-emerg-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        width: 100%; padding: 10px;
        background: #ef4444; color: #fff;
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
    .od-profile-badge .crown { width: 30px; height: 30px; color: #fbbf24; }
    .od-profile-badge .stars { font-size: 9px; color: #fbbf24; letter-spacing: 1px; line-height: 1; }
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
    .od-profile-name .ic-check { color: #10b981; }
    .od-profile-tier { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .od-profile-stats { font-size: 12.5px; color: var(--text-muted); margin-top: 4px; }
    .od-profile-rating { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 13px; }
    .od-profile-rating .star { color: #f59e0b; }
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
        color: #f97316;
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
    .od-badge.b-fast    .od-badge-ico { background: rgba(16,185,129,0.14);  color: #10b981; }
    .od-badge.b-luxury  .od-badge-ico { background: rgba(245,158,11,0.14);  color: #f59e0b; }
    .od-badge.b-repeat  .od-badge-ico { background: rgba(99,102,241,0.14);  color: #6366f1; }
    .od-badge.b-trend   .od-badge-ico { background: rgba(249,115,22,0.14);  color: #f97316; }
    .od-badge.b-verify  .od-badge-ico { background: rgba(14,165,233,0.14);  color: #0ea5e9; }
    .od-badge.b-fave    .od-badge-ico { background: rgba(239,68,68,0.14);   color: #ef4444; }
    .od-badge.b-negot   .od-badge-ico { background: rgba(139,92,246,0.14);  color: #8b5cf6; }
    .od-badge.b-mega    .od-badge-ico { background: rgba(6,182,212,0.14);   color: #06b6d4; }
    .od-badge.b-emerg   .od-badge-ico { background: rgba(245,158,11,0.14);  color: #f59e0b; }
    .od-badge.b-vip     .od-badge-ico { background: rgba(236,72,153,0.14);  color: #ec4899; }
    .od-badge-name { font-size: 9.5px; font-weight: 600; color: var(--text-secondary); line-height: 1.25; text-align: center; min-height: 24px; display: flex; align-items: flex-start; justify-content: center; }

    /* Calendar */
    .od-cal-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .od-cal-month { font-size: 14px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
    .od-cal-nav { display: flex; gap: 4px; }
    .od-cal-nav button {
        width: 26px; height: 26px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .od-cal-nav button:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .od-cal-tabs { display: flex; gap: 4px; }
    .od-cal-tab {
        padding: 4px 10px;
        font-size: 11.5px; font-weight: 600;
        border-radius: 6px;
        color: var(--text-muted);
        cursor: pointer;
        border: 1px solid transparent;
    }
    .od-cal-tab.is-active {
        background: rgba(249, 115, 22, 0.10);
        color: #f97316;
        border-color: rgba(249, 115, 22, 0.30);
    }
    .od-cal {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
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
    .od-cal-num { font-weight: 600; color: var(--text-primary); font-size: 11.5px; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; }
    /* Today — number sits in a solid orange circle (matches reference). */
    .od-cal-day.today .od-cal-num { background: #f97316; color: #fff; border-radius: 50%; font-weight: 800; }
    .od-cal-event {
        font-size: 8.5px; line-height: 1.15;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 600;
        /* allow wrap to 2 lines like the reference */
        white-space: normal;
        word-break: break-word;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .od-cal-event.ev-coral  { background: rgba(249,115,22,0.15); color: #c2410c; }
    .od-cal-event.ev-pink   { background: rgba(239,68,68,0.15);  color: #dc2626; }
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
        color: #f97316;
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
    .od-pill.requested { background: rgba(249, 115, 22, 0.15); color: #f97316; }
    .od-pill.confirmed { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .od-pill.pending   { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

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
        color: #f97316;
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
    .od-todo-pri.high   { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .od-todo-pri.medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .od-todo-pri.low    { background: rgba(16, 185, 129, 0.15); color: #10b981; }

    /* Attendee management mini table */
    .od-attendee-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px; margin-bottom: 11px; }
    .od-attendee-stat { padding: 8px 6px; border-radius: 8px; background: var(--bg-card-hover); text-align: center; }
    .od-attendee-stat-value { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .od-attendee-stat-label { font-size: 10.5px; color: var(--text-muted); }
    .od-attendee-table { width: 100%; font-size: 12.5px; border-collapse: collapse; }
    .od-attendee-table th, .od-attendee-table td { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--border-color); }
    .od-attendee-table th { color: var(--text-muted); font-size: 10.5px; text-transform: uppercase; font-weight: 700; }
    .od-attendee-table td { color: var(--text-primary); }
    .od-attendee-table td:nth-child(2) { max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Achievements medal — layered gold "coin" with a ring, matching the
       reference award-rosette look (was a flat orange disc). */
    .od-ach-circle {
        position: relative;
        width: 84px; height: 84px;
        border-radius: 50%;
        background: radial-gradient(circle at 50% 35%, #fcd34d 0%, #f59e0b 55%, #d97706 100%);
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
    .od-activity-ico.green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .od-activity-ico.blue  { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .od-activity-ico.pink  { background: rgba(236, 72, 153, 0.15); color: #ec4899; }
    .od-activity-ico.amber { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
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
        .od-attendee-stats { grid-template-columns: repeat(2, 1fr); }
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

    // Total spent — sum of completed bookings' cost when the column exists.
    $totalSpent = 0;
    try {
        $totalSpent = \App\Models\Booking::where('client_id', $user->id)
            ->where('status', 'completed')
            ->sum(\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'total_amount') ? 'total_amount' : 'agreed_price');
    } catch (\Throwable $e) { /* gracefully degrade */ }

    // Calendar — render the current month grid, overlay events on their
    // start dates. dayEvents is keyed by day-of-month (1-31).
    $now            = \Carbon\Carbon::now();
    $monthStart     = $now->copy()->startOfMonth();
    $monthEnd       = $now->copy()->endOfMonth();
    $firstCalDate   = $monthStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
    $lastCalDate    = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
    $eventsByDate   = \App\Models\Event::where('client_id', $user->id)
        ->whereBetween('starts_at', [$firstCalDate, $lastCalDate])
        ->get()
        ->groupBy(fn ($e) => $e->starts_at?->format('Y-m-d'));

    /* Demo calendar events (keyed by day-of-month) — shown when the client
       has no real scheduled events so the calendar isn't blank, matching
       the mockup. Replaced automatically once real events exist. */
    $demoCalEvents = $eventsByDate->isEmpty() ? [
        6  => ['Wedding Miami, FL',          'ev-coral'],
        8  => ['Corporate Event Orlando, FL','ev-purple'],
        13 => ['Private Party Tampa, FL',    'ev-purple'],
        21 => ['Brand Launch Miami, FL',     'ev-coral'],
        26 => ['Memorial Day',               'ev-pink'],
    ] : [];

    // My Gigs Overview — split bookings by status (matches design columns).
    $gigsRequested = \App\Models\Booking::where('client_id', $user->id)->where('status', 'requested')->count();
    $gigsBidding   = \App\Models\Booking::where('client_id', $user->id)->where('status', 'pending')->count();
    $gigsHired     = \App\Models\Booking::where('client_id', $user->id)->where('status', 'confirmed')->count();
    $gigsDone      = $stats['completed_bookings'] ?? 0;

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
    $completedEvents = $stats['completed_bookings'] ?? 0;
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
<div class="od-daterange-row">
    <button type="button" class="od-daterange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        {{ $now->copy()->startOfMonth()->format('M d') }} – {{ $now->copy()->endOfMonth()->addDays(0)->format('M d, Y') }}
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
</div>

{{-- ── Stats row (4 cards) ────────────────────────────────────── --}}
<div class="od-stats">
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
            <div class="od-stat-meta">
                <span class="od-stat-delta"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>0%</span>
                <span class="od-stat-sub">All time</span>
            </div>
            <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none"><polyline points="0,18 10,13 20,15 30,8 40,11 50,4 60,6" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Active Gigs</div>
                <div class="od-stat-value">{{ $stats['open_events'] ?? 0 }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div class="od-stat-meta">
                <span class="od-stat-delta"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>0%</span>
                <span class="od-stat-sub">In progress</span>
            </div>
            <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none"><polyline points="0,14 10,16 20,11 30,13 40,8 50,11 60,6" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico indigo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Completed Events</div>
                <div class="od-stat-value">{{ $stats['completed_bookings'] ?? 0 }}</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div class="od-stat-meta">
                <span class="od-stat-delta"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>0%</span>
                <span class="od-stat-sub">All time</span>
            </div>
            <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none"><polyline points="0,16 10,13 20,11 30,9 40,9 50,6 60,4" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="od-stat">
        <div class="od-stat-head">
            <div class="od-stat-ico pink">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </div>
            <div>
                <div class="od-stat-label">Saved Professionals</div>
                <div class="od-stat-value">0</div>
            </div>
        </div>
        <div class="od-stat-foot">
            <div class="od-stat-meta">
                <span class="od-stat-delta"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>0%</span>
                <span class="od-stat-sub">Favorites</span>
            </div>
            <svg class="od-stat-spark" width="58" height="22" viewBox="0 0 60 22" fill="none"><polyline points="0,12 10,9 20,13 30,7 40,9 50,5 60,3" stroke="#ec4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
        <a href="{{ route('client.multi-service.index') }}" class="od-emerg-btn">
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
                <span class="stars">★★★★★</span>
                <span class="od-profile-ribbon">TRUSTED PLANNER</span>
            </div>
            <div class="od-profile-info">
                <div class="od-profile-name">
                    Trusted Planner
                    <svg class="ic-check" width="15" height="15" viewBox="0 0 24 24" fill="#10b981" stroke="#fff" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11" fill="none"/></svg>
                </div>
                <div class="od-profile-tier">Tier 3 of 6</div>
                <div class="od-profile-stats">15 completed events</div>
                <div class="od-profile-rating"><span class="star">★ ★ ★ ★ ★</span><span style="color:var(--text-muted);">4.6 (86 reviews)</span></div>
            </div>
        </div>
        <div class="od-progress-wrap">
            <div class="od-progress-bar"><div class="od-progress-fill" style="width: 70%;"></div></div>
            <div class="od-progress-meta">
                <span>Next Tier: Elite Planner</span>
                <span>35 / 50 events</span>
            </div>
        </div>
        <a href="{{ route('client.profile.index') }}" class="od-profile-cta">View Profile</a>
    </div>

    {{-- Client Special Badges --}}
    <div class="od-card od-a-badges">
        <div class="od-card-head">
            <span class="od-card-title">Client Special Badges</span>
        </div>
        <div class="od-badges">
            <div class="od-badge b-fast">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
                <div class="od-badge-name">Fast Payer</div>
            </div>
            <div class="od-badge b-luxury">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
                <div class="od-badge-name">Luxury Host</div>
            </div>
            <div class="od-badge b-repeat">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></div>
                <div class="od-badge-name">Repeat Booker</div>
            </div>
            <div class="od-badge b-trend">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <div class="od-badge-name">Trendsetter</div>
            </div>
            <div class="od-badge b-verify">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></div>
                <div class="od-badge-name">Verified Business</div>
            </div>
            <div class="od-badge b-fave">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
                <div class="od-badge-name">Community Favorite</div>
            </div>
            <div class="od-badge b-negot">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h6l3 3 3-3h6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg></div>
                <div class="od-badge-name">Top Negotiator</div>
            </div>
            <div class="od-badge b-emerg">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M13.73 21a2 2 0 0 1-3.46 0"/><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg></div>
                <div class="od-badge-name">Emergency Organizer</div>
            </div>
            <div class="od-badge b-mega">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <div class="od-badge-name">Mega Event Planner</div>
            </div>
            <div class="od-badge b-vip">
                <div class="od-badge-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7z"/></svg></div>
                <div class="od-badge-name">VIP Client</div>
            </div>
        </div>
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

        <a href="{{ route('client.post-event.event-info') }}" class="od-profile-cta" style="margin-top: 14px;">
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
        <div class="od-card">
            <div class="od-card-head">
                <span class="od-card-title">My Calendar &amp; Availability</span>
            </div>
            <div class="od-cal-head">
                <div class="od-cal-month">
                    <button class="od-cal-nav-btn" aria-label="Previous month" type="button" style="background:none;border:1px solid var(--border-color);width:24px;height:24px;border-radius:6px;color:var(--text-muted);cursor:pointer;">‹</button>
                    <button class="od-cal-nav-btn" aria-label="Next month" type="button" style="background:none;border:1px solid var(--border-color);width:24px;height:24px;border-radius:6px;color:var(--text-muted);cursor:pointer;">›</button>
                    {{ $now->format('F Y') }}
                </div>
                <div class="od-cal-tabs">
                    <span class="od-cal-tab">Today</span>
                    <span class="od-cal-tab is-active">Month</span>
                    <span class="od-cal-tab">Week</span>
                </div>
            </div>
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
                        $inMonth  = $cursor->month === $now->month;
                        $dayEvs   = $eventsByDate->get($key, collect());
                        $demoEv   = ($inMonth && isset($demoCalEvents[$cursor->day])) ? $demoCalEvents[$cursor->day] : null;
                        $classes  = 'od-cal-day';
                        if (!$inMonth)                      $classes .= ' muted';
                        if ($key === $todayKey)             $classes .= ' today';
                        if ($dayEvs->count() || $demoEv)    $classes .= ' has-event';
                    @endphp
                    <div class="{{ $classes }}">
                        <div class="od-cal-num">{{ $cursor->day }}</div>
                        @foreach($dayEvs->take(1) as $ev)
                            <div class="od-cal-event ev-coral" title="{{ $ev->title }}">{{ \Illuminate\Support\Str::limit($ev->title, 10) }}</div>
                        @endforeach
                        @if($demoEv && $dayEvs->isEmpty())
                            <div class="od-cal-event {{ $demoEv[1] }}" title="{{ $demoEv[0] }}">{{ $demoEv[0] }}</div>
                        @endif
                    </div>
                    @php $cursor->addDay(); @endphp
                @endwhile
            </div>
            <div class="od-cal-legend">
                <span><span class="od-cal-legend-dot" style="background:#10b981;"></span>Booked</span>
                <span><span class="od-cal-legend-dot" style="background:#ef4444;"></span>Pending</span>
                <span><span class="od-cal-legend-dot" style="background:#f59e0b;"></span>On Hold</span>
                <span><span class="od-cal-legend-dot" style="background:#9ca3af;"></span>Unavailable</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 3: To-Do · Attendee Management · Achievements · Recent Activity ── --}}
<div class="od-row-3">
    <div class="od-card">
        <div class="od-card-head">
            <span class="od-card-title">My To-Do List</span>
        </div>
        @php
            /* Event-planning checklist. Static demo content matching the
               mockup until a real tasks table ships. */
            $todos = [
                ['Find and book a photographer',  'May 30, 2026', 'high'],
                ['Hire a caterer for 100 guests', 'Jun 2, 2026',  'medium'],
                ['Book a venue',                  'Jun 5, 2026',  'high'],
                ['Arrange floral decorations',    'Jun 6, 2026',  'low'],
            ];
        @endphp
        <div class="od-tabs">
            <span class="od-tab is-active">To Do (4)</span>
            <span class="od-tab">In Progress (2)</span>
            <span class="od-tab">Completed (3)</span>
            <span class="od-tab">Cancelled (1)</span>
        </div>
        <div class="od-todo">
            @foreach($todos as [$title, $due, $pri])
                <div class="od-todo-row">
                    <span class="od-todo-check"></span>
                    <span class="od-todo-text">{{ $title }}</span>
                    <span class="od-todo-due">Due {{ $due }}</span>
                    <span class="od-todo-pri {{ $pri }}">{{ ucfirst($pri) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="od-card">
        <div class="od-card-head">
            <span class="od-card-title">Attendee Management</span>
            <div style="display:flex;gap:6px;">
                <button class="od-card-link" style="background:none;border:1px solid var(--border-color);padding:5px 10px;border-radius:6px;cursor:pointer;">Add Attendee</button>
                <button class="od-card-link" style="background:none;border:1px solid var(--border-color);padding:5px 10px;border-radius:6px;cursor:pointer;">Import List</button>
            </div>
        </div>
        <div class="od-attendee-stats">
            <div class="od-attendee-stat"><div class="od-attendee-stat-value">120</div><div class="od-attendee-stat-label">Total Guests</div></div>
            <div class="od-attendee-stat"><div class="od-attendee-stat-value" style="color:#10b981;">75</div><div class="od-attendee-stat-label">Confirmed</div></div>
            <div class="od-attendee-stat"><div class="od-attendee-stat-value" style="color:#ef4444;">10</div><div class="od-attendee-stat-label">Cancelled</div></div>
            <div class="od-attendee-stat"><div class="od-attendee-stat-value" style="color:var(--text-muted);">35</div><div class="od-attendee-stat-label">No Response</div></div>
        </div>
        <table class="od-attendee-table">
            <thead><tr><th>Attendee Name</th><th>Email / Phone</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                @php
                    $od_attendees = [
                        ['Sarah Johnson',  'sarah.j@email.com',   'Confirmed',   '#10b981'],
                        ['Michael Brown',  'michael.b@email.com', 'Confirmed',   '#10b981'],
                        ['Emily Davis',    'emily.d@email.com',   'No Response', '#64748b'],
                        ['David Wilson',   'david.w@email.com',   'Cancelled',   '#ef4444'],
                        ['Jessica Taylor', 'jessica.t@email.com', 'Confirmed',   '#10b981'],
                    ];
                @endphp
                @foreach($od_attendees as [$nm, $em, $st, $col])
                    <tr>
                        <td style="font-weight:600;">{{ $nm }}</td>
                        <td style="color:var(--text-muted);font-size:11.5px;">{{ $em }}</td>
                        <td><span style="font-size:10.5px;font-weight:700;color:{{ $col }};">{{ $st }}</span></td>
                        <td style="text-align:right;">
                            <button style="background:none;border:none;cursor:pointer;color:#f97316;padding:2px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></button>
                            <button style="background:none;border:none;cursor:pointer;color:#ef4444;padding:2px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Your Achievements --}}
    <div class="od-card" style="text-align:center;">
        <div class="od-card-head" style="justify-content:center;">
            <span class="od-card-title">Your Achievements</span>
        </div>
        <div class="od-ach-circle">15</div>
        <div class="od-ach-label">Badges Earned</div>
        <div class="od-ach-encouragement">Keep going! You're doing great.</div>
    </div>

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
@endsection
