@extends('layouts.client')

@section('title', 'My Events')
@section('page-title', 'My Events')
@section('page-subtitle', 'Your events and who is working on them.')

@push('styles')
<style>
    /* ═══════════════════ My Gigs ═══════════════════
       Matches Khadija's "My Gigs" mockup — 5 stat cards, view tabs,
       master-list table, professional-status bar, recent activity +
       quick actions, and a right rail (Event Overview donut / Pro
       Status / Payment Summary / Upcoming Deadlines). */
    .mg-layout { display: grid; grid-template-columns: minmax(0,1fr) var(--cl-rail); gap: var(--cl-rail-gap); align-items: start; }
    .mg-main { min-width: 0; }
    .mg-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    .mg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* View-mode tab pills */
    .mg-viewtab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 9px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        font-size: 12.5px; font-weight: 600; color: var(--text-secondary);
        cursor: pointer; white-space: nowrap;
    }
    .mg-viewtab svg { width: 14px; height: 14px; }
    .mg-viewtab.active { background: rgba(249,115,22,0.10); color: var(--brand-text); border-color: rgba(249,115,22,0.30); }

    /* Stat cards */
    /* Sir Peter, 7 Oct: "can you make them all fit into one row by reducing
       the width so that they all fit in one row?" Seven across, and the tile
       gives up padding and icon rather than wrapping. */
    .mg-stats { display: grid; grid-template-columns: repeat(7, minmax(0,1fr)); gap: 8px; margin-bottom: 16px; }
    .mg-stats .mg-stat { padding: 11px 10px; gap: 8px; }
    .mg-stats .mg-stat-ico { width: 30px; height: 30px; border-radius: 8px; }
    .mg-stats .mg-stat-ico svg { width: 15px; height: 15px; }
    .mg-stats .mg-stat-label { font-size: 10.5px; }
    .mg-stats .mg-stat-value { font-size: 19px; }
    .mg-stats .mg-stat-delta { font-size: 10px; }
    @media (max-width: 1500px) { .mg-stats { grid-template-columns: repeat(4, minmax(0,1fr)); } }
    .mg-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start;
        text-decoration: none; transition: border-color .12s, box-shadow .12s; }
    a.mg-stat:hover { border-color: var(--text-muted); }
    a.mg-stat.is-on { border-color: var(--brand, #f97316); box-shadow: 0 0 0 1px var(--brand, #f97316) inset; }
    .mg-stat-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-stat-ico svg { width: 18px; height: 18px; }
    .mg-stat-ico.coral  { background: rgba(249,115,22,0.12); color: var(--brand-text); }
    .mg-stat-ico.green  { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .mg-stat-ico.amber  { background: rgba(245,158,11,0.12); color: var(--warn-text); }
    .mg-stat-ico.indigo { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .mg-stat-ico.purple { background: rgba(139,92,246,0.12); color: var(--accent-text); }
    .mg-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .mg-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mg-stat-delta { font-size: 10.5px; color: var(--ok-text); font-weight: 700; margin-top: 2px; }
    .mg-stat-delta.flat { color: var(--text-muted); }

    /* Filter row */
    .mg-filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
    .mg-filter-select, .mg-filter-search {
        height: 40px; border-radius: 9px;
        border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary);
        font-size: 13px; font-family: inherit; outline: none;
    }
    .mg-filter-select { padding: 0 12px; }
    .mg-filter-search-wrap { position: relative; flex: 1; min-width: 220px; }
    .mg-filter-search { width: 100%; padding: 0 14px 0 38px; }
    .mg-filter-search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .mg-filter-btn {
        height: 40px; padding: 0 14px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font-size: 12.5px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
    }
    .mg-filter-btn svg { width: 14px; height: 14px; }
    .mg-filter-btn.coral { background: #c2410c; color: #fff; border-color: #c2410c; }  /* 2.80 -> 5.18 */

    /* Sub-tabs */
    .mg-subtabs { display: flex; gap: 22px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .mg-subtab {
        padding: 10px 2px; font-size: 13px; font-weight: 600;
        color: var(--text-muted); cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .mg-subtab.active { color: var(--brand-text); border-bottom-color: #f97316; }

    /* Master-list table */
    .mg-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .mg-table th {
        text-align: left; padding: 12px 10px;
        font-size: 10.5px; font-weight: 700; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.4px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .mg-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .mg-table tr:hover td { background: var(--bg-card-hover); }
    .mg-table .ev-name { font-weight: 700; color: var(--text-primary); }
    .mg-table .ev-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 1px; }
    .mg-table .num { text-align: center; font-weight: 600; color: var(--text-primary); }
    .mg-status-pill { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 999px; text-transform: capitalize; white-space: nowrap; }
    .mg-status-confirmed   { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-status-pending     { background: rgba(245,158,11,0.18); color: var(--warn-text); }
    .mg-status-published   { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-status-in_progress { background: rgba(99,102,241,0.15); color: var(--accent-text); }
    .mg-status-not_started, .mg-status-not_scheduled { background: var(--border-color); color: var(--text-muted); }
    .mg-status-cancelled   { background: rgba(239,68,68,0.15); color: var(--bad-text); }
    .mg-status-open        { background: rgba(245,158,11,0.18); color: var(--warn-text); }
    .mg-status-booked, .mg-status-completed, .mg-status-paid { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-status-past, .mg-status-draft { background: var(--border-color); color: var(--text-muted); }
    .mg-status-partial     { background: rgba(99,102,241,0.15); color: var(--accent-text); }
    .mg-status-overdue     { background: rgba(239,68,68,0.15); color: var(--bad-text); }
    .mg-row-view { display: inline-block; font-size: 12px; font-weight: 700; color: var(--brand-text); text-decoration: none; padding: 5px 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-right: 4px; }
    .mg-row-view:hover { border-color: #f97316; }
    .mg-pay-table td { font-size: 12.5px; }
    .mg-pay-pro { display: flex; align-items: center; gap: 10px; min-width: 170px; }
    .mg-pay-pro img { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; flex: none; background: #e5e7eb; }
    .mg-pay-table .mg-menu { vertical-align: middle; }
    .mg-pay-about { display: flex; gap: 14px; align-items: center; margin: 12px 18px 16px; padding: 14px 16px; border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 12px; }
    .mg-pay-about-i { width: 38px; height: 38px; border-radius: 50%; background: #2563eb; color: #fff; font-weight: 800; font-size: 18px; font-family: Georgia, serif; display: flex; align-items: center; justify-content: center; flex: none; }
    .mg-pay-about b { display: block; font-size: 13.5px; color: var(--text-primary); margin-bottom: 4px; }
    .mg-pay-about ul { margin: 0; padding-left: 16px; font-size: 12px; color: var(--text-secondary); line-height: 1.6; }
    .mg-pay-about > a { margin-left: auto; font-size: 13px; font-weight: 700; color: #1d4ed8; text-decoration: none; white-space: nowrap; }
    @media (max-width: 900px) { .mg-pay-about { flex-wrap: wrap; } .mg-pay-about > a { margin-left: 0; } }
    .mg-rail-note { font-size: 12px; line-height: 1.5; color: var(--text-muted); }
    .mg-rail-note b { color: var(--text-primary); }
    .mg-row-kebab { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 2px 6px; }
    .mg-row-kebab:hover { color: var(--brand-text); }
    /* Row "more actions" menu — the kebab used to be a bare link to the event,
       which looked like a menu and behaved as a redirect. */
    .mg-menu { position: relative; display: inline-block; }
    .mg-menu-pop { display: none; position: absolute; right: 0; top: calc(100% + 5px); z-index: 40; min-width: 180px; background: var(--bg-card,#fff); border: 1px solid var(--border-color,#e5e7eb); border-radius: 10px; box-shadow: 0 10px 28px rgba(15,23,42,.13); padding: 5px; text-align: left; }
    .mg-menu.open .mg-menu-pop { display: block; }
    .mg-menu-pop a, .mg-menu-pop button { display: block; width: 100%; text-align: left; background: none; border: 0; font: inherit; font-size: 12.5px; font-weight: 600; color: var(--text-primary,#111827); text-decoration: none; padding: 8px 10px; border-radius: 7px; cursor: pointer; }
    .mg-menu-pop a:hover, .mg-menu-pop button:hover { background: rgba(249,115,22,.10); color: var(--brand-text); }
    .mg-menu-pop form { margin: 0; }

    /* Professional Status Overview bar */
    .mg-pso { margin-top: 18px; }
    .mg-pso-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .mg-pso-legend { display: flex; gap: 18px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 10px; }
    .mg-pso-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; vertical-align: middle; }
    .mg-pso-legend b { color: var(--text-primary); margin-left: 4px; }
    .mg-pso-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: var(--border-color); }

    /* Recent activity + quick actions */
    .mg-row2 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; margin-top: 18px; }
    .mg-act-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-act-row:last-child { border-bottom: 0; }
    .mg-act-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-act-dot svg { width: 14px; height: 14px; }
    .mg-act-dot.green { background: rgba(16,185,129,0.15); color: var(--ok-text); }
    .mg-act-dot.amber { background: rgba(245,158,11,0.15); color: var(--warn-text); }
    .mg-act-dot.indigo{ background: rgba(99,102,241,0.15); color: var(--accent-text); }
    .mg-act-dot.red   { background: rgba(239,68,68,0.15);  color: var(--danger-text, #b91c1c); }
    .mg-act-body { flex: 1; min-width: 0; }
    .mg-act-text { font-size: 12.5px; color: var(--text-primary); }
    .mg-act-time { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }
    .mg-qa { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; padding: 14px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-primary); position: relative; }
    .mg-qa:hover { border-color: rgba(249,115,22,0.30); }
    .mg-qa svg { width: 18px; height: 18px; color: var(--accent-text); }
    .mg-qa span { font-size: 12.5px; font-weight: 600; }
    .mg-qa-badge { position: absolute; top: 8px; right: 8px; background: #ef4444; color: #fff; font-size: 9px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }

    /* Right rail */
    .mg-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .mg-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .mg-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .mg-rail-sel { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; font-size: 10.5px; color: var(--text-muted); cursor: pointer; }
    .mg-donut { position: relative; width: 120px; height: 120px; margin: 4px auto 12px; }
    .mg-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 2; }
    .mg-donut-center .num { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .mg-donut-center .lbl { font-size: 10px; color: var(--text-muted); }
    .mg-legend { display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; }
    .mg-legend .row { display: flex; align-items: center; gap: 8px; }
    .mg-legend .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .mg-legend .lbl { flex: 1; color: var(--text-secondary); }
    .mg-legend .val { font-weight: 700; color: var(--text-primary); }
    .mg-pstat-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed var(--border-color); font-size: 12.5px; }
    .mg-pstat-row:last-of-type { border-bottom: 0; }
    .mg-pstat-row .lbl { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); }
    .mg-pstat-row .lbl svg { width: 13px; height: 13px; }
    .mg-pstat-row .val { font-weight: 700; color: var(--text-primary); }
    .mg-rail-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--brand-text); text-decoration: none; }
    .mg-rail-link svg { width: 12px; height: 12px; }
    .mg-pay-total { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .mg-pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; text-align: center; font-size: 10.5px; }
    .mg-pay-grid b { display: block; font-size: 14px; font-weight: 800; }
    .mg-pay-grid .paid b { color: var(--ok-text); }
    .mg-pay-grid .pend b { color: var(--warn-text); }
    .mg-pay-grid .over b { color: var(--bad-text); }
    .mg-dl-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-dl-row:last-of-type { border-bottom: 0; }
    .mg-dl-bar { width: 3px; align-self: stretch; border-radius: 999px; background: #f59e0b; flex-shrink: 0; }
    .mg-dl-body { flex: 1; min-width: 0; }
    .mg-dl-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .mg-dl-sub { font-size: 10.5px; color: var(--text-muted); }
    .mg-dl-due { font-size: 10.5px; color: var(--warn-text); font-weight: 700; white-space: nowrap; }

    @media (max-width: 1200px) {
        .mg-layout { grid-template-columns: 1fr; }
        .mg-rail { position: static; }
        .mg-stats { grid-template-columns: repeat(3, 1fr); }
        .mg-row2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 700px) {
        .mg-stats { grid-template-columns: repeat(2, 1fr); }
        .mg-table { font-size: 11px; }
    }

    .cl-calendar-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cl-calendar-nav button {
        width: 36px; height: 36px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .cl-calendar-nav button:hover { background: rgba(255,255,255,0.05); }
    .cl-calendar-month {
        font-size: 20px;
        font-weight: 700;
        min-width: 200px;
        text-align: center;
    }
    .cl-calendar-nav .today-btn {
        width: auto;
        padding: 0 16px;
        background: var(--accent-blue);
        color: #fff;
        border-color: var(--accent-blue);
        font-size: 13px;
        font-weight: 600;
    }
    .cl-calendar-nav .today-btn:hover { opacity: 0.9; }

    /* Event card in details view */
    .cl-event-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
        border-radius: var(--radius);
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .cl-event-card:hover { border-color: var(--border-glow); background: rgba(255,255,255,0.04); }

    .cl-event-date-badge {
        width: 52px; flex-shrink: 0;
        text-align: center;
        padding: 8px 0;
        border-radius: var(--radius-sm);
        background: var(--accent-blue-soft);
    }
    .cl-event-date-badge .month { font-size: 10px; text-transform: uppercase; font-weight: 600; color: var(--accent-blue); letter-spacing: 0.5px; }
    .cl-event-date-badge .day { font-size: 22px; font-weight: 800; color: var(--accent-blue); line-height: 1.2; }

    .cl-event-info { flex: 1; min-width: 0; }
    .cl-event-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .cl-event-meta { font-size: 13px; color: var(--text-muted); display: flex; gap: 16px; flex-wrap: wrap; }
    .cl-event-meta span { display: flex; align-items: center; gap: 4px; }

    .cl-event-actions { display: flex; gap: 8px; flex-shrink: 0; }

    /* A .cl-two-col rule stood here, a third column width for a block this
       page does not have: nothing in the markup ever carried the class. */

    /* Live Preview */
    .cl-preview-card {
        position: sticky;
        top: calc(var(--navbar-height) + 20px);
    }
    .cl-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: var(--accent-green-soft);
        color: var(--accent-green);
        margin-bottom: 12px;
    }
    .cl-preview-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
    .cl-preview-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
    .cl-preview-meta { display: flex; flex-direction: column; gap: 8px; }
    .cl-preview-meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }

    .cl-tab-content { display: none; }
    .cl-tab-content.active { display: block; }

    /* ── Multi-Select Category Dropdown ── */
    .cl-multiselect-wrap {
        position: relative;
    }
    .cl-multiselect-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: rgba(255,255,255,0.03);
        color: var(--text-primary);
        cursor: pointer;
        font-size: 14px;
        min-height: 44px;
        flex-wrap: wrap;
        gap: 6px;
        transition: var(--transition);
    }
    [data-theme="light"] .cl-multiselect-toggle {
        background: rgba(0,0,0,0.02);
    }
    .cl-multiselect-toggle:hover {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-placeholder {
        color: var(--text-muted);
    }
    .cl-multiselect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        flex: 1;
    }
    .cl-multiselect-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        background: var(--accent-blue-soft);
        color: var(--accent-blue);
        font-size: 12px;
        font-weight: 500;
    }
    .cl-multiselect-tag .tag-remove {
        cursor: pointer;
        opacity: 0.7;
        display: flex;
    }
    .cl-multiselect-tag .tag-remove:hover { opacity: 1; }
    .cl-multiselect-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 100;
        max-height: 280px;
        overflow: hidden;
        display: none;
        flex-direction: column;
    }
    .cl-multiselect-wrap.open .cl-multiselect-dropdown {
        display: flex;
    }
    .cl-multiselect-search {
        padding: 8px;
        border-bottom: 1px solid var(--border-color);
    }
    .cl-multiselect-search input {
        width: 100%;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }
    .cl-multiselect-search input:focus {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-options {
        overflow-y: auto;
        max-height: 220px;
        padding: 4px;
    }
    .cl-multiselect-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 14px;
        color: var(--text-primary);
        transition: background 0.15s;
    }
    .cl-multiselect-option:hover {
        background: rgba(99,102,241,0.08);
    }
    .cl-multiselect-option input[type="checkbox"] {
        display: none;
    }
    .cl-multiselect-check {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.15s;
    }
    .cl-multiselect-check svg {
        display: none;
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check {
        background: var(--accent-blue);
        border-color: var(--accent-blue);
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check svg {
        display: block;
        stroke: #fff;
    }
    .cl-multiselect-option.hidden {
        display: none;
    }
    /* The sub-tabs are buttons now, not decorative spans. */
    /* A button reset that keeps what .mg-subtab set: `border: 0` took the
       active underline with it and `font: inherit` undid the 13px size, which
       is why the live strip had no underline and oversized labels. */
    button.mg-subtab { background: none; border-width: 0 0 2px; border-style: solid; border-color: transparent;
        font-family: inherit; cursor: pointer; }
    .mg-filter-panel { flex-basis: 100%; display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;
        padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); }
    .mg-filter-panel[hidden] { display: none !important; }
    .mg-filter-panel label { display: flex; flex-direction: column; gap: 5px; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); }
    .mg-filter-count { display: inline-flex; min-width: 17px; height: 17px; padding: 0 5px; margin-left: 4px; border-radius: 999px;
        background: #f97316; color: #fff; font-size: 10.5px; font-weight: 800; align-items: center; justify-content: center; }
    .mg-filter-clear { font-size: 12.5px; font-weight: 700; color: #ea580c; text-decoration: none; align-self: center; }
    a.mg-filter-btn { text-decoration: none; }
    a.cl-calendar-event { display: block; text-decoration: none; color: inherit; }
    .mg-rail-period { font-size: 11px; font-weight: 700; color: var(--text-muted); }
</style>
@endpush

@section('content')
<div class="mg-layout" data-live-scope>
<div class="mg-main">


    {{-- View-mode tabs --}}
    {{-- The view tabs are gone with the views. Details View went on 7 October
         for counting the same events twice, and Calendar View on the same day,
         because the calendar is a page of its own in the left menu now. One
         tab leading to the only thing on the page is not a choice, and an
         empty row kept only for a script to find is worse: .mg-viewtabs sets
         display:flex, which overrode the hidden attribute on it. --}}

    {{-- The tiles are the status filter.

         Sir Peter, 7 Oct: "many duplicated for the same purpose, can you go
         thru this webpage and then make all the cards with the weblinks that
         belong?" Pressing one narrows the list; Total Events clears it. Every
         tile counts EVENTS and each is a subset of the first, so the figures
         still reconcile, and drafts and cancelled events have no tile of
         their own, which is why Total Events names them. --}}
    @php
        $mgLink = fn (?string $status) => route('client.events.index', array_filter(
            array_merge(request()->except(['page', 'status']), ['tab' => 'list', 'status' => $status])
        ));

        $notTiled = [];
        if ($stats['list_draft'])     { $notTiled[] = $stats['list_draft'] . ' ' . \Illuminate\Support\Str::plural('draft', $stats['list_draft']); }
        if ($stats['list_cancelled']) { $notTiled[] = $stats['list_cancelled'] . ' cancelled'; }

        $mgTiles = [
            [null, 'Total Events', 'coral', $stats['total'],
             $notTiled ? 'All time, incl. ' . implode(' and ', $notTiled) : 'All time',
             '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
            ['open', 'Open', 'amber', $stats['list_open'], 'Taking proposals',
             '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
            ['in_progress', 'In Progress', 'green', $stats['list_in_progress'], 'Professionals booked',
             '<polyline points="20 6 9 17 4 12"/>'],
            ['completed', 'Completed', 'indigo', $stats['list_completed'], 'Events finished',
             '<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/>'],
            ['past', 'Past Events', 'purple', $stats['list_past'], 'Date has passed',
             '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="14" x2="15" y2="20"/><line x1="15" y1="14" x2="9" y2="20"/>'],
            /*
             * Upcoming was one of the tiles Details View owned, and Sir Peter
             * kept it in green when the rest went. It is a date rather than a
             * stage, so it has no status to filter by: an upcoming event can
             * be open, booked or in progress.
             */
            [null, 'Upcoming', 'green', $stats['upcoming'], 'Still to happen',
             '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ];

        /*
         * The one figure Details View owned, kept when the view went. It is
         * money rather than a count of events, so it is not a filter and does
         * not pretend to be one: there is no list of "budget" to show.
         * Cancelled events are left out, because a budget for something that
         * is not happening is not money anyone is planning to spend.
         */
        $mgBudget = [number_format($stats['total_budget'], 0), 'Total Budget', 'Cancelled left out'];
    @endphp
    <div class="mg-stats">
        @foreach($mgTiles as $__i => [$status, $label, $tone, $value, $caption, $icon])
            @php
                // Upcoming is a figure, not a filter: it is the last entry and
                // has no status of its own.
                $isFilter = $__i < count($mgTiles) - 1;
                $isOn     = $isFilter && (request('status') === $status || (! request('status') && $status === null));
            @endphp
            {{-- data-no-live: this page swaps its list in place, and a tile
                 is a change of filter rather than a refresh of the same one,
                 so it navigates like a link. --}}
            <{{ $isFilter ? 'a' : 'div' }} class="mg-stat {{ $isOn ? 'is-on' : '' }}"
               @if($isFilter)
                   data-no-live
                   href="{{ $mgLink($status) }}"
                   @if($isOn) aria-current="page" @endif
                   title="{{ $status === null ? 'Show every event' : 'Show only ' . strtolower($label) }}"
               @endif>
                <div class="mg-stat-ico {{ $tone }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icon !!}</svg>
                </div>
                <div>
                    <div class="mg-stat-label">{{ $label }}</div>
                    <div class="mg-stat-value">{{ $value }}</div>
                    <div class="mg-stat-delta flat">{{ $caption }}</div>
                </div>
            </{{ $isFilter ? 'a' : 'div' }}>
        @endforeach

        <div class="mg-stat">
            <div class="mg-stat-ico amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="mg-stat-label">{{ $mgBudget[1] }}</div>
                <div class="mg-stat-value">${{ $mgBudget[0] }}</div>
                <div class="mg-stat-delta flat">{{ $mgBudget[2] }}</div>
            </div>
        </div>
    </div>

    {{-- Filter row --}}
    {{-- Filtering redraws the list and the details in place; this row
         stays bright so the box being typed in is never dimmed. --}}
    <form method="GET" action="{{ route('client.events.index') }}" class="mg-filter-row"
          id="mgFilters" data-live-region data-live-busy="mgListCard">
        <input type="hidden" name="tab" value="list">
        @if(request('period'))<input type="hidden" name="period" value="{{ request('period') }}">@endif
        {{-- requestSubmit, not submit(): submit() skips the submit event,
             so nothing listening could keep this on the page. --}}
        <select name="status" class="mg-filter-select" onchange="this.form.requestSubmit()" aria-label="All Statuses">
            <option value="">All Statuses</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" class="mg-filter-select" onchange="this.form.requestSubmit()" aria-label="All Event Types">
            <option value="">All Event Types</option>
            @foreach ($eventTypeOptions as $typeOpt)
                <option value="{{ $typeOpt }}" @selected(request('type') === $typeOpt)>{{ $typeOpt }}</option>
            @endforeach
        </select>
        <div class="mg-filter-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="mg-filter-search" placeholder="Search events, professionals..." value="{{ request('search') }}" data-live-search autocomplete="off">
        </div>
        @php $moreFilters = (int) request()->filled('category') + (int) request()->filled('when'); @endphp
        {{-- Was a second submit button with nothing behind it. It opens the
             filters the row has no room for: service and when. --}}
        <button type="button" class="mg-filter-btn" data-filter-toggle aria-expanded="{{ $moreFilters ? 'true' : 'false' }}" aria-controls="mgFilterPanel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters @if($moreFilters)<span class="mg-filter-count">{{ $moreFilters }}</span>@endif</button>
        @if(request()->hasAny(['search', 'status', 'type', 'category', 'when']))
            <a href="{{ route('client.events.index') }}" class="mg-filter-btn">Reset</a>
        @endif
        {{-- Was a button with no handler. Downloads exactly what is listed. --}}
        <a href="{{ route('client.events.export', request()->only(['search', 'status', 'type', 'category', 'when'])) }}" class="mg-filter-btn" download><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export</a>
        {{-- Post an Event moved up into the bar beside the search (Sir Peter,
             2 Oct), where it is on every page rather than only on this one. A
             second copy here would be the same button twice on one screen. --}}

        <div class="mg-filter-panel" id="mgFilterPanel" @unless($moreFilters) hidden @endunless>
            <label>Service
                <select name="category" class="mg-filter-select" aria-label="Service">
                    <option value="">Any service</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>When
                <select name="when" class="mg-filter-select" aria-label="When">
                    <option value="">Any time</option>
                    <option value="upcoming" @selected(request('when') === 'upcoming')>Upcoming</option>
                    <option value="past" @selected(request('when') === 'past')>Already happened</option>
                    <option value="undated" @selected(request('when') === 'undated')>No date yet</option>
                </select>
            </label>
            <button type="submit" class="mg-filter-btn coral">Apply</button>
            @if(request()->hasAny(['search', 'status', 'type', 'category', 'when']))
                <a href="{{ route('client.events.index') }}" class="mg-filter-clear">Clear all</a>
            @endif
        </div>
    </form>

    {{-- ════════════ EVENTS LIST (default) ════════════ --}}
    <div class="cl-tab-content active" id="tab-list">
        <div class="mg-card" style="padding:0;overflow:hidden;" id="mgListCard" data-live-region>
            {{-- These were three spans marked "(visual)" — two of them did
                 nothing when clicked. They are three real views now: the
                 events, who is booked for when, and what each booking costs. --}}
            <div class="mg-subtabs" style="padding:0 18px;" role="tablist">
                <button type="button" class="mg-subtab active" data-subtab="events" role="tab" aria-selected="true">Events List</button>
                <button type="button" class="mg-subtab" data-subtab="schedule" role="tab" aria-selected="false">Professional Schedule</button>
                <button type="button" class="mg-subtab" data-subtab="payments" role="tab" aria-selected="false">Payment Tracker</button>
            </div>
            <div data-subpane="events">
            <div style="overflow-x:auto;">
                <table class="mg-table">
                    <thead>
                        <tr>
                            <th style="padding-left:18px;">Event</th>
                            <th>Date &amp; Time</th>
                            <th>Type</th>
                            <th>Proposals</th>
                            <th>Confirmed</th>
                            <th>Status</th>
                            <th>Budget</th>
                            <th style="padding-right:18px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            @php
                                $bk = $event->bookings ?? collect();
                                // Confirmed out of the services asked for: one
                                // professional per service.
                                $needed    = $event->categories->count();
                                $confirmed = $bk->whereIn('status', ['confirmed', 'completed'])->count();
                                $stageKey  = $event->listStage();
                                $where     = $event->location_need === \App\Domain\Requests\VenueRule::NEED && $event->preferred_locations
                                    ? implode(', ', $event->preferred_locations) . ' (finding a venue)'
                                    // Issue #14: a web address is not a place.
                                    : (\App\Domain\Requests\VenueRule::place($event->venue)
                                        ?: \App\Domain\Requests\VenueRule::place($event->location));
                            @endphp
                            <tr>
                                <td style="padding-left:18px;">
                                    <a href="{{ route('client.events.show', $event) }}" class="ev-name" style="text-decoration:none;color:inherit;">{{ $event->title }}</a>
                                    <div class="ev-sub">{{ $where ?: 'Location not set' }}</div>
                                </td>
                                <td style="white-space:nowrap;">
                                    {{ $event->starts_at?->format('M j, Y') ?? 'No date yet' }}
                                    @if($event->starts_at)<div class="ev-sub">{{ $event->starts_at->format('g:i A') }}@if($event->ends_at && $event->ends_at->gt($event->starts_at)) – {{ $event->ends_at->format('g:i A') }}@endif</div>@endif
                                </td>
                                <td>{{ $event->event_type ?: '—' }}</td>
                                <td class="num">{{ $event->bids_count }}</td>
                                <td class="num" style="color:var(--ok-text);">{{ $confirmed }}@if($needed) / {{ $needed }}@endif</td>
                                <td><span class="mg-status-pill mg-status-{{ $stageKey }}">{{ \App\Models\Event::LIST_STAGES[$stageKey] ?? ucfirst($stageKey) }}</span></td>
                                <td style="white-space:nowrap;font-weight:600;color:var(--text-primary);">
                                    @if($event->budget_min && $event->budget_max)
                                        ${{ number_format($event->budget_min, 0) }} – ${{ number_format($event->budget_max, 0) }}
                                    @elseif($event->budget)
                                        ${{ number_format($event->budget, 0) }}
                                    @else
                                        <span style="color:var(--text-muted);font-weight:500;">Not set</span>
                                    @endif
                                </td>
                                <td style="padding-right:18px;text-align:right;white-space:nowrap;">
                                    {{-- The action this row's own stage calls for.

                                         It lived in Details View, which was removed on
                                         7 October for counting the same events twice.
                                         The counting was duplication; this was not, and
                                         a draft with no way to be finished is worse than
                                         a tile too many. --}}
                                    @php
                                        $rowProposals   = $event->bookings->where('status', 'requested')->count();
                                        $rowNegotiating = \App\Domain\Requests\RequestLifecycle::inExclusiveNegotiation($event);
                                    @endphp
                                    @if($event->isDraft())
                                        <a href="{{ route('client.events.show', $event) }}" class="mg-row-view" style="color:#c2410c;font-weight:800;">Continue Draft</a>
                                    @elseif($rowNegotiating)
                                        <a href="{{ route('client.events.show', $event) }}" class="mg-row-view">Open Negotiation</a>
                                    @elseif($rowProposals > 1)
                                        <a href="{{ route('client.proposals.compare', $event) }}" class="mg-row-view">Compare Proposals ({{ $rowProposals }})</a>
                                    @elseif($rowProposals === 1)
                                        <a href="{{ route('client.events.show', $event) }}" class="mg-row-view">Review Proposal</a>
                                    @endif
                                    <a href="{{ route('client.events.show', $event) }}" class="mg-row-view">View</a>
                                    <div class="mg-menu" data-row-menu>
                                        <button type="button" class="mg-row-kebab" aria-haspopup="true" aria-expanded="false" title="More actions">⋯</button>
                                        <div class="mg-menu-pop" data-row-menu-pop>
                                            <a href="{{ route('client.events.show', $event) }}">View event</a>
                                            <a href="{{ route('client.events.show', $event) }}#proposals">View proposals</a>
                                            @unless($event->is_published)
                                                <form method="POST" action="{{ route('client.events.publish', $event) }}">
                                                    @csrf
                                                    <button type="submit">Publish event</button>
                                                </form>
                                            @endunless
                                            <a href="{{ route('client.chat.index') }}">Message professionals</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">@if(request()->hasAny(['search', 'status', 'type', 'category', 'when']))No events match these filters. <a href="{{ route('client.events.index') }}">Clear filters</a>@else No events yet. Click <b>Post an Event</b> to get started.@endif</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())
                <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;">
                    <span>Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} matching this filter</span>
                    {{ $events->onEachSide(1)->links() }}
                </div>
            @endif
            </div>{{-- /events subpane --}}

            {{-- Who is booked, for which event, when. --}}
            <div data-subpane="schedule" hidden>
                <div style="overflow-x:auto;">
                    <table class="mg-table">
                        <thead><tr><th style="padding-left:18px;">Professional</th><th>Event</th><th>Service</th><th>Date</th><th>Time</th><th style="padding-right:18px;">Status</th></tr></thead>
                        <tbody>
                            @forelse($bookings->whereNotIn('status', \App\Domain\Finance\ClientTotals::VOID_STATUSES) as $b)
                                <tr>
                                    <td style="padding-left:18px;"><div class="ev-name">{{ $b->supplier?->name ?? 'Professional' }}</div></td>
                                    <td>@if($b->event)<a href="{{ route('client.events.show', $b->event_id) }}">{{ $b->event->title }}</a>@else, @endif</td>
                                    <td>{{ $b->category?->name ?? '—' }}</td>
                                    <td>{{ $b->event?->starts_at?->format('M d, Y') ?? 'Not scheduled' }}</td>
                                    <td>{{ $b->event?->starts_at?->format('g:i A') ?? '—' }}@if($b->event?->ends_at) – {{ $b->event->ends_at->format('g:i A') }}@endif</td>
                                    <td style="padding-right:18px;"><span class="mg-status-pill mg-status-{{ $b->status }}">{{ $b->status === 'requested' ? 'Awaiting reply' : ucfirst($b->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No professionals booked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- What each booking costs and where the money stands. --}}
            <div data-subpane="payments" hidden>
                <div style="overflow-x:auto;">
                    <table class="mg-table mg-pay-table">
                        <thead><tr><th style="padding-left:18px;">Professional</th><th>Event</th><th>Service</th><th>Event Date</th><th>Amount</th><th>Payment</th><th>Status</th><th>Due Date</th><th style="padding-right:18px;text-align:right;">Actions</th></tr></thead>
                        <tbody>
                            @forelse($payRows as $r)
                                <tr>
                                    <td style="padding-left:18px;">
                                        <div class="mg-pay-pro">
                                            <img src="{{ $r['avatar'] }}" alt="">
                                            <div><div class="ev-name">{{ $r['professional'] }}</div>@if($r['pro_id'])<div class="ev-sub">{{ $r['pro_id'] }}</div>@endif</div>
                                        </div>
                                    </td>
                                    <td>{{ $r['event'] ?? '—' }}</td>
                                    <td>{{ $r['service'] ?? '—' }}</td>
                                    <td style="white-space:nowrap;">{{ $r['date']?->format('M j, Y') ?? '—' }}</td>
                                    <td style="font-weight:600;color:var(--text-primary);white-space:nowrap;">{{ $r['amount'] !== null ? '$' . number_format($r['amount'], 0) : '—' }}</td>
                                    <td style="white-space:nowrap;">{{ $r['amount'] !== null ? '$' . number_format($r['paid'], 0) : '—' }}</td>
                                    <td style="white-space:nowrap;">
                                        <span class="mg-status-pill mg-status-{{ $r['status'] }}">{{ $r['label'] }}</span>
                                        @if($r['overdue'])<span class="mg-status-pill mg-status-overdue">Overdue</span>@endif
                                    </td>
                                    <td style="white-space:nowrap;">{{ $r['due']?->format('M j, Y') ?? '—' }}</td>
                                    <td style="padding-right:18px;text-align:right;white-space:nowrap;">
                                        @if($r['pay_url'])
                                            <a href="{{ $r['pay_url'] }}" class="mg-row-view">Pay Now</a>
                                        @elseif($r['set_url'])
                                            <a href="{{ $r['set_url'] }}" class="mg-row-view">Set Amount</a>
                                        @elseif($r['view_url'])
                                            <a href="{{ $r['view_url'] }}" class="mg-row-view">View</a>
                                        @endif
                                        <div class="mg-menu" data-row-menu>
                                            {{-- The same control, the same glyph: the two
                                                 rows on this page were drawn with different
                                                 dots. --}}
                                            <button type="button" class="mg-row-kebab" aria-haspopup="true" aria-expanded="false" title="More actions">⋯</button>
                                            <div class="mg-menu-pop" data-row-menu-pop>
                                                @if($r['view_url'])<a href="{{ $r['view_url'] }}">View details</a>@endif
                                                @if($r['event_id'])<a href="{{ route('client.events.show', $r['event_id']) }}">View event</a>@endif
                                                @if($r['profile_url'])<a href="{{ $r['profile_url'] }}">View professional</a>@endif
                                                <a href="{{ route('client.chat.index') }}">Message professional</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">Nothing to pay yet. Payments show here once you accept a proposal.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Sir Peter's "About payment statuses" box, in the rules this tab follows. --}}
                <div class="mg-pay-about">
                    <span class="mg-pay-about-i">i</span>
                    <div>
                        <b>About payment statuses</b>
                        <ul>
                            <li>"Overdue" is only shown when a payment amount is set and the due date has passed.</li>
                            <li>If no amount has been set yet, the status shows "Pending Amount" or "Not Scheduled" instead of "Overdue".</li>
                            <li>Payments are made to professionals through GigResource. Your own account is never listed here.</li>
                        </ul>
                    </div>
                    <a href="{{ url('/payment-policy') }}">Learn more about payments →</a>
                </div>
            </div>
        </div>

        {{-- Professional Status Overview bar --}}
        <div class="mg-pso">
            <div class="mg-pso-title">Professional Status Overview</div>
            <div class="mg-pso-legend">
                <span><span class="dot" style="background:#10b981;"></span>Confirmed<b>{{ $proStatus['confirmed'] }}</b></span>
                <span><span class="dot" style="background:#f59e0b;"></span>Pending<b>{{ $proStatus['pending'] }}</b></span>
                <span><span class="dot" style="background:#94a3b8;"></span>Not Scheduled<b>{{ $proStatus['not_scheduled'] }}</b></span>
                <span><span class="dot" style="background:#ef4444;"></span>Cancelled<b>{{ $proStatus['cancelled'] }}</b></span>
            </div>
            @php
                $psTotal = max(1, array_sum($proStatus));
                $psColors = ['confirmed'=>'#10b981','pending'=>'#f59e0b','not_scheduled'=>'#94a3b8','cancelled'=>'#ef4444'];
            @endphp
            <div class="mg-pso-bar">
                @foreach($psColors as $k => $c)
                    @php $w = ($proStatus[$k] / $psTotal) * 100; @endphp
                    @if($w > 0)<div style="width:{{ $w }}%;background:{{ $c }};"></div>@endif
                @endforeach
            </div>
        </div>

        {{-- Recent Activity, full width. Quick Actions beside it was removed
             on Ali's call, 2026-09-10 — every one of its links is already in
             the sidebar. --}}
        <div class="mg-row2">
            <div class="mg-card">
                <div class="mg-rail-head"><div class="mg-rail-title">Recent Professional Activity</div></div>
                {{-- Every row is one record that exists, timestamped when that
                     record was written — not "Activity on X" over an updated_at
                     that moves whenever anything at all is saved. --}}
                @forelse($activity as $a)
                    <div class="mg-act-row">
                        <div class="mg-act-dot {{ $a['kind'] === 'cancelled' ? 'red' : 'green' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <div class="mg-act-body"><div class="mg-act-text">{{ $a['text'] }} <b>{{ \Illuminate\Support\Str::limit($a['about'], 24) }}</b></div></div>
                        <div class="mg-act-time">{{ $a['when']->humanAgo() }}</div>
                    </div>
                @empty
                    <div style="font-size:12px;color:var(--text-muted);padding:12px 0;text-align:center;">No recent activity</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════════ CALENDAR VIEW ════════════ --}}
    {{-- A day, a week or a month, all in the address. Every control is a link
         to this page, so it changes in place (partials/_live_regions) and
         still works as a plain link. --}}
    {{-- ════════════ DETAILS VIEW ════════════ --}}
    {{-- Details View is gone. Sir Peter, 7 October, agreeing with the
         recommendation: it listed the same events a second time, in a
         different shape, with its own copy of the counts and its own
         search box. Two tabs earn their place by answering different
         questions. The one figure it owned, Total Budget, is a tile
         above the list now. --}}

</div>{{-- /.mg-main --}}

    {{-- ════════════ RIGHT RAIL ════════════ --}}
    <aside class="mg-rail" id="mgRail" data-live-region>

        {{-- Event Overview donut --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Event Overview</div>@include('client.events._period_select')</div>
            @php
                // Events by stage — every slice is an event, so they add up to
                // the number in the middle. It mixed booking counts in before.
                $evTotal = max(1, $overview['total']);
                $evPie = array_values(array_filter($overview['stages'], fn ($p) => $p['val'] > 0)) ?: [['lbl' => 'No events', 'val' => 0, 'color' => '#e5e7eb']];
                $cur = 0; $segs = [];
                foreach ($evPie as $p) { $deg = ($p['val'] / $evTotal) * 360; $segs[] = "{$p['color']} {$cur}deg ".($cur+$deg)."deg"; $cur += $deg; }
                $evConic = 'conic-gradient('.implode(', ', $segs).')';
            @endphp
            <div class="mg-donut" style="background:{{ $evConic }};border-radius:50%;">
                <div style="position:absolute;inset:13px;background:var(--bg-card);border-radius:50%;z-index:1;"></div>
                <div class="mg-donut-center"><span class="num">{{ $overview['total'] }}</span><span class="lbl">{{ $period === 'all' ? 'Total Events' : 'Posted ' . strtolower($periods[$period]) }}</span></div>
            </div>
            @php
                /*
                 * Shares that total exactly 100 (largest remainder). Rounding
                 * each slice on its own gave a legend reading 33/33/33 = 99%,
                 * and a client counting the events found the figure wrong.
                 */
                $pct = [];
                if ($overview['total'] > 0) {
                    $exact = [];
                    foreach ($evPie as $i => $p) { $exact[$i] = ($p['val'] / $overview['total']) * 100; $pct[$i] = (int) floor($exact[$i]); }
                    $short = 100 - array_sum($pct);
                    $order = array_keys($exact);
                    usort($order, fn ($a, $b) => ($exact[$b] - $pct[$b]) <=> ($exact[$a] - $pct[$a]));
                    for ($i = 0; $i < $short; $i++) { $pct[$order[$i % count($order)]]++; }
                }
            @endphp
            <div class="mg-legend">
                @foreach($evPie as $i => $p)
                    @php $pp = $pct[$i] ?? 0; @endphp
                    <div class="row"><span class="dot" style="background:{{ $p['color'] }};"></span><span class="lbl">{{ $p['lbl'] }}</span><span class="val">{{ $p['val'] }} ({{ $pp }}%)</span></div>
                @endforeach
            </div>
        </div>

        {{-- Past Event Status: how an event gets there. --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Past Event Status</div></div>
            <p class="mg-rail-note" style="margin:0;">When an event's date has passed and it was not completed or cancelled, it moves to <b>Past Event</b> automatically.</p>
        </div>

        {{-- Professional Status --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Professional Status</div>@if($period !== 'all')<span class="mg-rail-period">{{ $periods[$period] }}</span>@endif</div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Confirmed</span><span class="val">{{ $proStatus['confirmed'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pending</span><span class="val">{{ $proStatus['pending'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Not Scheduled</span><span class="val">{{ $proStatus['not_scheduled'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>Cancelled</span><span class="val">{{ $proStatus['cancelled'] }}</span></div>
        </div>

        {{-- Payment Summary --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Payment Summary</div>@include('client.events._period_select')</div>
            {{-- It said "Total Spent" over a figure that included money not yet
                 paid. It is everything booked; "Paid" below is what was spent. --}}
            <div style="font-size:11px;color:var(--text-muted);">Total booked</div>
            <div class="mg-pay-total">${{ number_format($payment['total'], 0) }}</div>
            <div class="mg-pay-grid">
                <div class="paid"><b>${{ number_format($payment['paid'], 0) }}</b><span style="color:var(--text-muted);">Paid</span></div>
                <div class="pend"><b>${{ number_format($payment['pending'], 0) }}</b><span style="color:var(--text-muted);">Pending</span></div>
                <div class="over"><b>${{ number_format($payment['overdue'], 0) }}</b><span style="color:var(--text-muted);">Overdue</span></div>
            </div>
            <a href="{{ route('client.events.index', ['tab' => 'list', 'sub' => 'payments']) }}" class="mg-rail-link" data-open-subtab="payments">View Payment Tracker <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Upcoming Payments: owed, with a due date, soonest first. --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Upcoming Payments</div><a href="{{ route('client.events.index', ['sub' => 'payments']) }}" class="mg-rail-link" style="margin:0;" data-open-subtab="payments">View All</a></div>
            @forelse($upcomingPayments as $up)
                <div class="mg-dl-row">
                    <span class="mg-dl-bar" @if($up['overdue']) style="background:#ef4444;" @endif></span>
                    <div class="mg-dl-body">
                        <div class="mg-dl-title">{{ \Illuminate\Support\Str::limit($up['professional'], 24) }}</div>
                        <div class="mg-dl-sub">${{ number_format($up['balance'], 0) }} · Due {{ $up['due']->format('M j, Y') }}</div>
                    </div>
                    <span class="mg-status-pill mg-status-{{ $up['overdue'] ? 'overdue' : $up['status'] }}">{{ $up['overdue'] ? 'Overdue' : $up['label'] }}</span>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No payments due</div>
            @endforelse
        </div>

        {{-- Upcoming Deadlines --}}
        <div class="mg-rail-card">
            {{-- These are event dates, not deadlines, and every row said
                 "Finalize event details" whatever state the event was in. --}}
            <div class="mg-rail-head"><div class="mg-rail-title">Coming Up</div></div>
            @forelse($deadlines as $dl)
                @php $daysLeft = (int) ceil(now()->diffInHours($dl->starts_at, false) / 24); @endphp
                <div class="mg-dl-row">
                    <span class="mg-dl-bar"></span>
                    <div class="mg-dl-body">
                        <div class="mg-dl-title">{{ \Illuminate\Support\Str::limit($dl->title, 22) }}</div>
                        <div class="mg-dl-sub">{{ $dl->starts_at->format('D, M j · g:i A') }}</div>
                    </div>
                    <span class="mg-dl-due">{{ $daysLeft <= 0 ? 'Today' : 'In ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') }}</span>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">Nothing in the next two weeks</div>
            @endforelse
        </div>
    </aside>

</div>{{-- /.mg-layout --}}

@endsection

@include('partials._row-menu-script')
@include('partials._live_regions')
@push('scripts')
<script>

    // Tab switching went with the tabs.

    // Open modal if ?create=1
    if (new URLSearchParams(window.location.search).get('create') === '1') {
        window.location.href='{{ route('client.post-event.choose') }}';
    }

    // Opening a named view went with the views; ?tab= means nothing here now.

    // Events List / Professional Schedule / Payment Tracker.
    // Delegated: the list card is swapped for a fresh copy on every filter,
    // and a listener bound to the old buttons would die with them.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-subtab]') : null;
        if (!btn) return;
        document.querySelectorAll('[data-subtab]').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        document.querySelectorAll('[data-subpane]').forEach(function (pane) {
            pane.hidden = pane.dataset.subpane !== btn.dataset.subtab;
        });
    });

    // "View Payment Tracker" in the rail opens that tab here, and ?sub=payments
    // opens it on arrival, so the link works from a fresh page too.
    function openSubtab(name) {
        var btn = document.querySelector('[data-subtab="' + name + '"]');
        if (btn) { btn.click(); btn.scrollIntoView({ block: 'nearest' }); return true; }
        return false;
    }
    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('[data-open-subtab]') : null;
        if (link && openSubtab(link.dataset.openSubtab)) e.preventDefault();
    });
    var wantSub = new URLSearchParams(location.search).get('sub');
    if (wantSub) openSubtab(wantSub);

    // Filters opens the service / when panel. Delegated for the same reason.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-filter-toggle]') : null;
        if (!btn) return;
        var panel = document.getElementById('mgFilterPanel');
        if (!panel) return;
        panel.hidden = !panel.hidden;
        btn.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    });

    // The time grid opens at its first event rather than at 7 AM.
    function scrollGrid() {
        var body = document.querySelector('#mgCal .tg-body[data-scroll-to]');
        if (body) body.scrollTop = parseInt(body.getAttribute('data-scroll-to'), 10) || 0;
    }
    scrollGrid();
    document.addEventListener('live:swapped', scrollGrid);
    // The calendar tab starts hidden, and a hidden box cannot be scrolled.
    document.addEventListener('click', function (e) {
    });

    // Close any open multi-select dropdowns on Escape.
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.cl-multiselect-wrap.open').forEach(el => el.classList.remove('open'));
        }
    });

    // ── Multi-Select Category Logic ──
    function updateMultiselectDisplay() {
        const wrap = document.getElementById('categoryMultiselect');
        const toggle = wrap.querySelector('.cl-multiselect-toggle');
        const checked = wrap.querySelectorAll('input[type="checkbox"]:checked');
        const placeholder = toggle.querySelector('.cl-multiselect-placeholder');

        // Remove existing tags
        toggle.querySelectorAll('.cl-multiselect-tags').forEach(el => el.remove());

        if (checked.length === 0) {
            if (placeholder) placeholder.style.display = '';
        } else {
            if (placeholder) placeholder.style.display = 'none';
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'cl-multiselect-tags';
            checked.forEach(cb => {
                const name = cb.closest('.cl-multiselect-option').querySelector('span:last-child').textContent;
                const tag = document.createElement('span');
                tag.className = 'cl-multiselect-tag';
                tag.innerHTML = name + ' <span class="tag-remove" data-id="' + cb.value + '"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>';
                tagsContainer.appendChild(tag);
            });
            toggle.insertBefore(tagsContainer, toggle.querySelector('svg:last-child'));
        }
    }

    // Checkbox change handler
    document.querySelectorAll('#categoryMultiselect input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateMultiselectDisplay);
    });

    // Tag remove handler (delegated)
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.tag-remove');
        if (removeBtn) {
            e.stopPropagation();
            const id = removeBtn.dataset.id;
            const cb = document.querySelector('#categoryMultiselect input[value="' + id + '"]');
            if (cb) { cb.checked = false; updateMultiselectDisplay(); }
        }
    });

    // Search/filter categories
    function filterCategories(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('#categoryMultiselect .cl-multiselect-option').forEach(opt => {
            const name = opt.dataset.name;
            opt.classList.toggle('hidden', q && !name.includes(q));
        });
    }

    // Close multiselect on outside click
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.cl-multiselect-wrap.open').forEach(wrap => {
            if (!wrap.contains(e.target)) wrap.classList.remove('open');
        });
    });
</script>
@endpush
