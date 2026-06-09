@extends('layouts.client')

@section('title', 'My Gigs')
@section('page-title', 'My Gigs')
@section('page-subtitle', 'Manage your events, master list, and professionals in one place.')

@push('styles')
<style>
    /* ═══════════════════ My Gigs ═══════════════════
       Matches Khadija's "My Gigs" mockup — 5 stat cards, view tabs,
       master-list table, professional-status bar, recent activity +
       quick actions, and a right rail (Event Overview donut / Pro
       Status / Payment Summary / Upcoming Deadlines). */
    .mg-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .mg-main { min-width: 0; }
    .mg-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    .mg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* View-mode tab pills */
    .mg-viewtabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .mg-viewtab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 9px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        font-size: 12.5px; font-weight: 600; color: var(--text-secondary);
        cursor: pointer; white-space: nowrap;
    }
    .mg-viewtab svg { width: 14px; height: 14px; }
    .mg-viewtab.active { background: rgba(249,115,22,0.10); color: #f97316; border-color: rgba(249,115,22,0.30); }

    /* Stat cards */
    .mg-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .mg-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start; }
    .mg-stat-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-stat-ico svg { width: 18px; height: 18px; }
    .mg-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .mg-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .mg-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .mg-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .mg-stat-ico.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .mg-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .mg-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mg-stat-delta { font-size: 10.5px; color: #10b981; font-weight: 700; margin-top: 2px; }
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
    .mg-filter-btn.coral { background: #f97316; color: #fff; border-color: #f97316; }

    /* Sub-tabs */
    .mg-subtabs { display: flex; gap: 22px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .mg-subtab {
        padding: 10px 2px; font-size: 13px; font-weight: 600;
        color: var(--text-muted); cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .mg-subtab.active { color: #f97316; border-bottom-color: #f97316; }

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
    .mg-status-confirmed   { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-status-pending     { background: rgba(245,158,11,0.18); color: #d97706; }
    .mg-status-published   { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-status-in_progress { background: rgba(99,102,241,0.15); color: #6366f1; }
    .mg-status-not_started, .mg-status-not_scheduled { background: var(--border-color); color: var(--text-muted); }
    .mg-status-cancelled   { background: rgba(239,68,68,0.15); color: #ef4444; }
    .mg-row-kebab { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 2px 6px; }

    /* Professional Status Overview bar */
    .mg-pso { margin-top: 18px; }
    .mg-pso-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .mg-pso-legend { display: flex; gap: 18px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 10px; }
    .mg-pso-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; vertical-align: middle; }
    .mg-pso-legend b { color: var(--text-primary); margin-left: 4px; }
    .mg-pso-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: var(--border-color); }

    /* Recent activity + quick actions */
    .mg-row2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 16px; margin-top: 18px; }
    .mg-act-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-act-row:last-child { border-bottom: 0; }
    .mg-act-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-act-dot svg { width: 14px; height: 14px; }
    .mg-act-dot.green { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-act-dot.amber { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .mg-act-dot.indigo{ background: rgba(99,102,241,0.15); color: #6366f1; }
    .mg-act-body { flex: 1; min-width: 0; }
    .mg-act-text { font-size: 12.5px; color: var(--text-primary); }
    .mg-act-time { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }
    .mg-qa-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .mg-qa { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; padding: 14px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-primary); position: relative; }
    .mg-qa:hover { border-color: rgba(249,115,22,0.30); }
    .mg-qa svg { width: 18px; height: 18px; color: #6366f1; }
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
    .mg-rail-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 12px; font-weight: 600; color: #f97316; text-decoration: none; }
    .mg-rail-link svg { width: 12px; height: 12px; }
    .mg-pay-total { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .mg-pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; text-align: center; font-size: 10.5px; }
    .mg-pay-grid b { display: block; font-size: 14px; font-weight: 800; }
    .mg-pay-grid .paid b { color: #10b981; }
    .mg-pay-grid .pend b { color: #f59e0b; }
    .mg-pay-grid .over b { color: #ef4444; }
    .mg-dl-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-dl-row:last-of-type { border-bottom: 0; }
    .mg-dl-bar { width: 3px; align-self: stretch; border-radius: 999px; background: #f59e0b; flex-shrink: 0; }
    .mg-dl-body { flex: 1; min-width: 0; }
    .mg-dl-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .mg-dl-sub { font-size: 10.5px; color: var(--text-muted); }
    .mg-dl-due { font-size: 10.5px; color: #f59e0b; font-weight: 700; white-space: nowrap; }

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

    /* Two column for view + preview */
    .cl-two-col { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
    @media (max-width: 1024px) { .cl-two-col { grid-template-columns: 1fr; } }

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
</style>
@endpush

@section('content')
<div class="mg-layout">
<div class="mg-main">

    {{-- Header button row (greeting lives in the topbar) --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button class="cl-btn cl-btn-primary" style="background:#f97316;border-color:#f97316;" onclick="document.getElementById('postEventModal').classList.add('show')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Post New Event
        </button>
    </div>

    {{-- View-mode tabs --}}
    <div class="mg-viewtabs" id="viewTabs">
        <button class="cl-tab mg-viewtab active" data-tab="masterlist">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Master List
        </button>
        <button class="cl-tab mg-viewtab" data-tab="calendar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendar View
        </button>
        <button class="cl-tab mg-viewtab" data-tab="details">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            Details View
        </button>
    </div>

    {{-- Stat cards --}}
    <div class="mg-stats">
        <div class="mg-stat">
            <div class="mg-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div><div class="mg-stat-label">Total Events</div><div class="mg-stat-value">{{ $stats['total'] }}</div><div class="mg-stat-delta flat">This month</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div><div class="mg-stat-label">Confirmed</div><div class="mg-stat-value">{{ $stats['confirmed'] }}</div><div class="mg-stat-delta flat">Pros booked</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="mg-stat-label">Pending</div><div class="mg-stat-value">{{ $stats['pending'] }}</div><div class="mg-stat-delta flat">Awaiting</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/></svg></div>
            <div><div class="mg-stat-label">Paid</div><div class="mg-stat-value">{{ $stats['paid'] }}</div><div class="mg-stat-delta flat">Completed</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div><div class="mg-stat-label">Total Spent</div><div class="mg-stat-value">${{ number_format($totalSpent, 0) }}</div><div class="mg-stat-delta flat">This month</div></div>
        </div>
    </div>

    {{-- Filter row --}}
    <form method="GET" action="{{ route('client.events.index') }}" class="mg-filter-row">
        <input type="hidden" name="tab" value="masterlist">
        <select name="status" class="mg-filter-select" onchange="this.form.submit()">
            <option value="">All Events</option>
            @foreach (['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <div class="mg-filter-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="mg-filter-search" placeholder="Search events, professionals..." value="{{ request('search') }}">
        </div>
        <button type="submit" class="mg-filter-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters</button>
        <button type="button" class="mg-filter-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export</button>
        <button type="button" class="mg-filter-btn coral" onclick="document.getElementById('postEventModal').classList.add('show')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create Master List</button>
    </form>

    {{-- ════════════ MASTER LIST (default) ════════════ --}}
    <div class="cl-tab-content active" id="tab-masterlist">
        <div class="mg-card" style="padding:0;overflow:hidden;">
            {{-- Sub-tabs (visual) --}}
            <div class="mg-subtabs" style="padding:0 18px;">
                <span class="mg-subtab active">Event Master List</span>
                <span class="mg-subtab">Professional Schedule</span>
                <span class="mg-subtab">Payment Tracker</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="mg-table">
                    <thead>
                        <tr>
                            <th style="padding-left:18px;">Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pros Needed</th>
                            <th>Confirmed</th>
                            <th>Pending</th>
                            <th>Status</th>
                            <th>Budget / Spent</th>
                            <th style="padding-right:18px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            @php
                                $bk = $event->bookings ?? collect();
                                $needed    = $bk->count() ?: ($event->professionals_needed ?? '—');
                                $confirmed = $bk->where('status', 'confirmed')->count();
                                $pending   = $bk->where('status', 'requested')->count();
                                $budget    = $event->budget ?? 0;
                                $spent     = $bk->where('status', 'completed')->sum(fn($b) => $b->total_amount ?? $b->agreed_price ?? 0);
                            @endphp
                            <tr>
                                <td style="padding-left:18px;">
                                    <div class="ev-name">{{ $event->title }}</div>
                                    <div class="ev-sub">{{ $event->starts_at?->format('M d, Y') ?? 'No date' }}@if($event->starts_at) · {{ $event->starts_at->format('g:i A') }}@endif</div>
                                </td>
                                <td>{{ $event->starts_at?->format('M d, Y') ?? '—' }}</td>
                                <td>{{ $event->starts_at?->format('g:i A') ?? '—' }}@if($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif</td>
                                <td class="num">{{ $needed }}</td>
                                <td class="num" style="color:#10b981;">{{ $confirmed }}</td>
                                <td class="num" style="color:#f59e0b;">{{ $pending }}</td>
                                <td><span class="mg-status-pill mg-status-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span></td>
                                <td style="white-space:nowrap;font-weight:600;color:var(--text-primary);">${{ number_format($budget, 0) }} / ${{ number_format($spent, 0) }}</td>
                                <td style="padding-right:18px;text-align:right;">
                                    <a href="{{ route('client.events.show', $event) }}" class="mg-row-kebab" style="text-decoration:none;">⋯</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No events yet. Click <b>Post New Event</b> to get started.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())
                <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;">
                    <span>Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} events</span>
                    {{ $events->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        {{-- Professional Status Overview bar --}}
        <div class="mg-pso">
            <div class="mg-pso-title">Professional Status Overview</div>
            <div class="mg-pso-legend">
                <span><span class="dot" style="background:#10b981;"></span>Confirmed<b>{{ $proStatus['confirmed'] }}</b></span>
                <span><span class="dot" style="background:#f59e0b;"></span>Pending<b>{{ $proStatus['pending'] }}</b></span>
                <span><span class="dot" style="background:#94a3b8;"></span>Not Scheduled<b>{{ $proStatus['not_scheduled'] }}</b></span>
                <span><span class="dot" style="background:#ef4444;"></span>Cancelled<b>{{ $proStatus['cancelled'] }}</b></span>
                <span><span class="dot" style="background:#8b5cf6;"></span>Rescheduled<b>{{ $proStatus['rescheduled'] }}</b></span>
            </div>
            @php
                $psTotal = max(1, array_sum($proStatus));
                $psColors = ['confirmed'=>'#10b981','pending'=>'#f59e0b','not_scheduled'=>'#94a3b8','cancelled'=>'#ef4444','rescheduled'=>'#8b5cf6'];
            @endphp
            <div class="mg-pso-bar">
                @foreach($psColors as $k => $c)
                    @php $w = ($proStatus[$k] / $psTotal) * 100; @endphp
                    @if($w > 0)<div style="width:{{ $w }}%;background:{{ $c }};"></div>@endif
                @endforeach
            </div>
        </div>

        {{-- Recent Activity + Quick Actions --}}
        <div class="mg-row2">
            <div class="mg-card">
                <div class="mg-rail-head"><div class="mg-rail-title">Recent Professional Activity</div><a href="#" style="font-size:11px;color:#f97316;text-decoration:none;font-weight:600;">View All</a></div>
                @forelse($events->take(3) as $ev)
                    <div class="mg-act-row">
                        <div class="mg-act-dot green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <div class="mg-act-body"><div class="mg-act-text">Activity on <b>{{ \Illuminate\Support\Str::limit($ev->title, 24) }}</b></div></div>
                        <div class="mg-act-time">{{ $ev->updated_at?->diffForHumans() ?? '' }}</div>
                    </div>
                @empty
                    <div style="font-size:12px;color:var(--text-muted);padding:12px 0;text-align:center;">No recent activity</div>
                @endforelse
            </div>
            <div class="mg-card">
                <div class="mg-rail-head"><div class="mg-rail-title">Quick Actions</div></div>
                <div class="mg-qa-grid">
                    <a href="{{ route('client.events.index') }}?create=1" class="mg-qa" onclick="event.preventDefault();document.getElementById('postEventModal').classList.add('show');"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg><span>Add Event</span></a>
                    <a href="{{ route('client.search.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg><span>Invite Professionals</span></a>
                    <a href="{{ route('client.bookings.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>View Proposals</span></a>
                    <a href="{{ route('client.bookings.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span>Manage Bookings</span></a>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════ CALENDAR VIEW ════════════ --}}
    <div class="cl-tab-content" id="tab-calendar">
        <div class="cl-card">
            @php
                $currentDate = \Carbon\Carbon::create($year, $month, 1);
                $daysInMonth = $currentDate->daysInMonth;
                $firstDayOfWeek = $currentDate->dayOfWeek; // 0=Sun
                $today = now();
                $prevMonth = $currentDate->copy()->subMonth();
                $nextMonth = $currentDate->copy()->addMonth();

                // Index events by day
                $eventsByDay = [];
                foreach ($calendarEvents as $ce) {
                    $day = $ce->starts_at->day;
                    $eventsByDay[$day][] = $ce;
                }
            @endphp

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div class="cl-calendar-month">{{ $currentDate->format('F Y') }}</div>
                <div class="cl-calendar-nav">
                    <a href="{{ route('client.events.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" style="text-decoration:none;">
                        <button><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
                    </a>
                    <a href="{{ route('client.events.index', ['month' => now()->month, 'year' => now()->year]) }}" style="text-decoration:none;">
                        <button class="today-btn">Today</button>
                    </a>
                    <a href="{{ route('client.events.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" style="text-decoration:none;">
                        <button><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                    </a>
                </div>
            </div>

            <table class="cl-calendar">
                <thead>
                    <tr>
                        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    @php $dayCounter = 1; $started = false; @endphp
                    @for ($row = 0; $row < 6 && $dayCounter <= $daysInMonth; $row++)
                        <tr>
                            @for ($col = 0; $col < 7; $col++)
                                @if (!$started && $col < $firstDayOfWeek)
                                    <td><div class="cl-calendar-day empty"></div></td>
                                @elseif ($dayCounter <= $daysInMonth)
                                    @php
                                        $started = true;
                                        $isToday = $today->year == $year && $today->month == $month && $today->day == $dayCounter;
                                        $dayEvents = $eventsByDay[$dayCounter] ?? [];
                                    @endphp
                                    <td>
                                        <div class="cl-calendar-day {{ $isToday ? 'today' : '' }}">
                                            <div class="day-num">{{ $dayCounter }}</div>
                                            @foreach (array_slice($dayEvents, 0, 2) as $de)
                                                <div class="cl-calendar-event">{{ Str::limit($de->title, 14) }}</div>
                                            @endforeach
                                            @if (count($dayEvents) > 2)
                                                <div style="font-size:10px; color: var(--text-muted); margin-top:2px;">+{{ count($dayEvents) - 2 }} more</div>
                                            @endif
                                        </div>
                                    </td>
                                    @php $dayCounter++; @endphp
                                @else
                                    <td><div class="cl-calendar-day empty"></div></td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════════ DETAILS VIEW ════════════ --}}
    <div class="cl-tab-content" id="tab-details">
        {{-- Stats Row --}}
        <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Events</div>
                        <div class="cl-stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Open Events</div>
                        <div class="cl-stat-value">{{ $stats['open'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon yellow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Upcoming</div>
                        <div class="cl-stat-value">{{ $stats['upcoming'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon pink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Budget</div>
                        <div class="cl-stat-value">${{ number_format($stats['total_budget'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search + Filter --}}
        <div class="cl-card" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('client.events.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="details">
                <div style="flex: 1; min-width: 200px;">
                    <div class="cl-search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search events..." value="{{ request('search') }}">
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <select name="status" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Status</option>
                        @foreach (['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select name="category" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </form>
        </div>

        {{-- Events List --}}
        @if($events->count())
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($events as $event)
                    <div class="cl-event-card">
                        <div class="cl-event-date-badge">
                            @if($event->starts_at)
                                <div class="month">{{ $event->starts_at->format('M') }}</div>
                                <div class="day">{{ $event->starts_at->format('d') }}</div>
                            @else
                                <div class="month">No</div>
                                <div class="day">—</div>
                            @endif
                        </div>
                        <div class="cl-event-info">
                            <div class="cl-event-title">{{ $event->title }}</div>
                            <div class="cl-event-meta">
                                @if($event->categories->count())
                                    @foreach($event->categories as $cat)
                                        <span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                            {{ $cat->name }}
                                        </span>
                                    @endforeach
                                @endif
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $event->created_at->diffForHumans() }}
                                </span>
                                <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                            </div>
                        </div>
                        <div class="cl-event-actions">
                            @if(!$event->is_published)
                                <form method="POST" action="{{ route('client.events.publish', $event) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Publish</button>
                                </form>
                            @endif
                            <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-ghost cl-btn-sm">View</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($events->hasPages())
                <div class="cl-pagination">
                    @if($events->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $events->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($events->getUrlRange(1, $events->lastPage()) as $page => $url)
                        @if($page == $events->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </div>
            @endif
        @else
            <div class="cl-card">
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="16" x2="15" y2="16"/></svg>
                    </div>
                    <div class="cl-empty-title">No events found yet</div>
                    <div class="cl-empty-text">Create your first event to get started with hiring professionals.</div>
                    <button class="cl-btn cl-btn-primary" onclick="document.getElementById('postEventModal').classList.add('show')">Create Your First Event</button>
                </div>
            </div>
        @endif
    </div>

</div>{{-- /.mg-main --}}

    {{-- ════════════ RIGHT RAIL ════════════ --}}
    <aside class="mg-rail">

        {{-- Event Overview donut --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Event Overview</div><select class="mg-rail-sel"><option>This Month</option></select></div>
            @php
                $evTotal = max(1, $stats['total']);
                $evPie = [
                    ['lbl'=>'Confirmed',  'val'=>$stats['confirmed'], 'color'=>'#10b981'],
                    ['lbl'=>'Pending',    'val'=>$stats['pending'],   'color'=>'#f59e0b'],
                    ['lbl'=>'In Progress','val'=>$proStatus['not_scheduled'], 'color'=>'#6366f1'],
                    ['lbl'=>'Not Started','val'=>max(0, $stats['total'] - $stats['confirmed'] - $stats['pending']), 'color'=>'#94a3b8'],
                ];
                $cur = 0; $segs = [];
                foreach ($evPie as $p) { $deg = ($p['val'] / $evTotal) * 360; $segs[] = "{$p['color']} {$cur}deg ".($cur+$deg)."deg"; $cur += $deg; }
                $evConic = 'conic-gradient('.implode(', ', $segs).')';
            @endphp
            <div class="mg-donut" style="background:{{ $evConic }};border-radius:50%;">
                <div style="position:absolute;inset:13px;background:var(--bg-card);border-radius:50%;z-index:1;"></div>
                <div class="mg-donut-center"><span class="num">{{ $stats['total'] }}</span><span class="lbl">Total Events</span></div>
            </div>
            <div class="mg-legend">
                @foreach($evPie as $p)
                    @php $pp = $stats['total'] > 0 ? round(($p['val']/$stats['total'])*100) : 0; @endphp
                    <div class="row"><span class="dot" style="background:{{ $p['color'] }};"></span><span class="lbl">{{ $p['lbl'] }}</span><span class="val">{{ $p['val'] }} ({{ $pp }}%)</span></div>
                @endforeach
            </div>
        </div>

        {{-- Professional Status --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Professional Status</div></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Confirmed</span><span class="val">{{ $proStatus['confirmed'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pending</span><span class="val">{{ $proStatus['pending'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Not Scheduled</span><span class="val">{{ $proStatus['not_scheduled'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>Cancelled</span><span class="val">{{ $proStatus['cancelled'] }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Rescheduled</span><span class="val">{{ $proStatus['rescheduled'] }}</span></div>
            <a href="#" class="mg-rail-link">View All <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Payment Summary --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Payment Summary</div><select class="mg-rail-sel"><option>This Month</option></select></div>
            <div style="font-size:11px;color:var(--text-muted);">Total Spent</div>
            <div class="mg-pay-total">${{ number_format($payment['total'], 0) }}</div>
            <div class="mg-pay-grid">
                <div class="paid"><b>${{ number_format($payment['paid'], 0) }}</b><span style="color:var(--text-muted);">Paid</span></div>
                <div class="pend"><b>${{ number_format($payment['pending'], 0) }}</b><span style="color:var(--text-muted);">Pending</span></div>
                <div class="over"><b>${{ number_format($payment['overdue'], 0) }}</b><span style="color:var(--text-muted);">Overdue</span></div>
            </div>
            <a href="{{ route('app.payments.history') }}" class="mg-rail-link">View Payment Tracker <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Upcoming Deadlines --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Upcoming Deadlines</div><a href="#" style="font-size:11px;color:#f97316;text-decoration:none;font-weight:600;">View All</a></div>
            @forelse($deadlines as $dl)
                @php $daysLeft = (int) ceil(now()->diffInHours($dl->starts_at, false) / 24); @endphp
                <div class="mg-dl-row">
                    <span class="mg-dl-bar"></span>
                    <div class="mg-dl-body">
                        <div class="mg-dl-title">{{ \Illuminate\Support\Str::limit($dl->title, 22) }}</div>
                        <div class="mg-dl-sub">Finalize event details</div>
                    </div>
                    <span class="mg-dl-due">Due in {{ max(0, $daysLeft) }} day{{ $daysLeft === 1 ? '' : 's' }}</span>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No upcoming deadlines</div>
            @endforelse
        </div>
    </aside>

</div>{{-- /.mg-layout --}}

    {{-- ════════════ POST EVENT MODAL (Clients only) ════════════ --}}
    <div class="cl-modal-overlay" id="postEventModal">
        <div class="cl-modal" style="max-width: 720px;">
            <form method="POST" action="{{ route('client.events.store') }}">
                @csrf
                <div class="cl-modal-header">
                    <div>
                        <div class="cl-modal-title">Post an Event</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Fill out the details to create a new event and invite professionals.</p>
                    </div>
                    <button type="button" class="cl-modal-close" onclick="document.getElementById('postEventModal').classList.remove('show')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="cl-modal-body">
                    <div class="cl-form-group">
                        <label class="cl-form-label">Event Title *</label>
                        <input type="text" name="title" class="cl-form-input" placeholder="e.g. Wedding Ceremony, Corporate Gala" required>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Description</label>
                        <textarea name="description" class="cl-form-textarea" rows="4" placeholder="Describe your event, expectations, and requirements..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Start Date & Time</label>
                            <input type="datetime-local" name="starts_at" class="cl-form-input">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">End Date & Time</label>
                            <input type="datetime-local" name="ends_at" class="cl-form-input">
                        </div>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Categories <span style="font-weight:400; color: var(--text-muted);">(select one or more)</span></label>
                        <div class="cl-multiselect-wrap" id="categoryMultiselect">
                            <div class="cl-multiselect-toggle" onclick="this.parentElement.classList.toggle('open')">
                                <span class="cl-multiselect-placeholder">Select categories...</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                            <div class="cl-multiselect-dropdown">
                                <div class="cl-multiselect-search">
                                    <input type="text" placeholder="Search categories..." oninput="filterCategories(this.value)">
                                </div>
                                <div class="cl-multiselect-options">
                                    @foreach ($categories as $cat)
                                        <label class="cl-multiselect-option" data-name="{{ strtolower($cat->name) }}">
                                            <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}">
                                            <span class="cl-multiselect-check">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            </span>
                                            <span>{{ $cat->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Location</label>
                            <input type="text" name="location" class="cl-form-input" placeholder="City, Venue, or Address">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Budget <span style="opacity:.6;font-weight:400">(USD, optional)</span></label>
                            <input type="number" name="budget" class="cl-form-input" placeholder="e.g. 2500" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="cl-modal-footer">
                    <button type="button" class="cl-btn cl-btn-ghost" onclick="document.getElementById('postEventModal').classList.remove('show')">Cancel</button>
                    <button type="submit" class="cl-btn cl-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Tab switching
    document.querySelectorAll('#viewTabs .cl-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#viewTabs .cl-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.cl-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });

    // Open modal if ?create=1
    if (new URLSearchParams(window.location.search).get('create') === '1') {
        document.getElementById('postEventModal').classList.add('show');
    }

    // Open details tab if ?tab=details
    if (new URLSearchParams(window.location.search).get('tab') === 'details') {
        document.querySelector('[data-tab="details"]').click();
    }

    // Close modal on overlay click
    document.getElementById('postEventModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('postEventModal').classList.remove('show');
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
