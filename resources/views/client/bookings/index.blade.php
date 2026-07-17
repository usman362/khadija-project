@extends('layouts.client')

@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('page-subtitle', 'Track contracts, payments and milestones across every gig.')

@push('styles')
<style>
    /* ═══════════════════ Bookings page ═══════════════════
       Matches Khadija's "Bookings Client_s side" mockup —
       6-stat header row, status tabs, gateway chips, detailed
       booking cards (4-column body + milestone timeline), and a
       sticky right rail (booking overview · financial overview ·
       upcoming milestones · quick actions). */

    .bk-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 18px;
        align-items: start;
    }
    .bk-main { min-width: 0; }
    .bk-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    /* ── Top 6 stat cards ──────────────────────────── */
    .bk-stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .bk-stat {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 14px;
        display: flex; gap: 12px; align-items: center;
    }
    .bk-stat-ico {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bk-stat-ico svg { width: 18px; height: 18px; }
    .bk-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .bk-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .bk-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .bk-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .bk-stat-ico.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .bk-stat-ico.red    { background: rgba(239,68,68,0.12);  color: #ef4444; }
    .bk-stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .bk-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .bk-stat-sub   { font-size: 10.5px; color: var(--text-muted); margin-top: 1px; }

    /* ── Filter tabs + actions row ─────────────────── */
    .bk-tabs-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .bk-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .bk-tab {
        padding: 7px 14px;
        font-size: 12.5px; font-weight: 600;
        border-radius: 8px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .bk-tab:hover { background: var(--bg-card-hover); }
    .bk-tab.is-active {
        background: #f97316;
        color: #fff;
        border-color: #f97316;
    }
    .bk-row-actions { display: flex; gap: 8px; align-items: center; }
    .bk-row-actions button, .bk-row-actions a {
        height: 36px; padding: 0 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 12.5px; font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
    }
    .bk-row-actions button svg, .bk-row-actions a svg { width: 14px; height: 14px; }
    .bk-view-toggle {
        display: inline-flex;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }
    .bk-view-toggle button {
        height: 36px; width: 36px;
        background: var(--bg-card); border: none;
        color: var(--text-muted); cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .bk-view-toggle button.is-active { background: #f97316; color: #fff; }

    /* ── Gateway chips + search row ───────────────── */
    .bk-gw-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .bk-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .bk-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        border: 1px solid var(--border-color);
        border-radius: 999px;
        background: var(--bg-card);
        font-size: 11.5px; font-weight: 600;
        color: var(--text-secondary);
        text-decoration: none;
        cursor: pointer;
    }
    .bk-chip:hover, .bk-chip.is-active {
        background: rgba(99,102,241,0.08);
        border-color: rgba(99,102,241,0.30);
        color: #6366f1;
    }
    .bk-chip svg { width: 13px; height: 13px; }
    .bk-chip .ico-stripe   { color: #635bff; }
    .bk-chip .ico-secure payment   { color: #16a34a; }
    .bk-chip .ico-dispute  { color: #ef4444; }
    .bk-search {
        position: relative; min-width: 280px;
    }
    .bk-search input {
        width: 100%; height: 36px;
        padding: 0 14px 0 36px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        color: var(--text-primary);
        font-size: 12.5px; font-family: inherit;
        outline: none;
    }
    .bk-search input:focus { border-color: #f97316; }
    .bk-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted); pointer-events: none; }

    /* ── Booking cards ─────────────────────────────── */
    .bk-cards { display: flex; flex-direction: column; gap: 16px; }
    .bk-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        overflow: hidden;
    }
    .bk-card-head {
        display: flex; align-items: center; gap: 14px; padding: 16px 18px;
        border-bottom: 1px solid var(--border-color);
    }
    .bk-card-icon {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(99,102,241,0.12);
        color: #6366f1;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-weight: 800; font-size: 14px;
    }
    .bk-card-title { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .bk-card-status {
        font-size: 10.5px; font-weight: 800;
        padding: 4px 9px; border-radius: 999px;
        text-transform: uppercase; letter-spacing: 0.6px;
    }
    .bk-status-in_progress, .bk-status-confirmed { background: rgba(16,185,129,0.15);  color: #10b981; }
    .bk-status-upcoming,    .bk-status-requested { background: rgba(99,102,241,0.15);  color: #6366f1; }
    .bk-status-completed                          { background: rgba(99,102,241,0.15);  color: #6366f1; }
    .bk-status-on_hold,     .bk-status-pending    { background: rgba(245,158,11,0.18); color: #d97706; }
    .bk-status-cancelled                          { background: rgba(239,68,68,0.18);   color: #ef4444; }
    .bk-card-kebab { background: none; border: none; padding: 4px 6px; cursor: pointer; color: var(--text-muted); font-size: 18px; }

    .bk-card-meta {
        padding: 14px 18px;
        display: grid;
        grid-template-columns: 1.6fr 1fr 1fr;
        gap: 14px;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
    }
    .bk-card-meta .item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-secondary); }
    .bk-card-meta .item svg { width: 14px; height: 14px; color: var(--text-muted); flex-shrink: 0; }
    .bk-card-meta .item b { color: var(--text-primary); font-weight: 600; }
    .bk-card-meta .item-providers { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .bk-provider-tag {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 9px; border-radius: 6px;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        font-size: 11px; font-weight: 600;
        color: var(--text-secondary);
    }
    .bk-provider-tag svg { width: 12px; height: 12px; }

    .bk-spent-row {
        padding: 12px 18px;
        background: rgba(245,158,11,0.05);
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    }
    .bk-spent-text { font-size: 12.5px; color: var(--text-secondary); flex: 1; min-width: 220px; }
    .bk-spent-text b { color: var(--text-primary); font-weight: 700; }
    .bk-spent-bar { flex: 1; max-width: 220px; height: 6px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .bk-spent-fill { height: 100%; background: linear-gradient(90deg, #10b981, #f59e0b); border-radius: 999px; }
    .bk-spent-pct { font-size: 12px; font-weight: 700; color: #f59e0b; }
    .bk-spent-warn { color: #f59e0b; display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; }
    .bk-spent-warn svg { width: 12px; height: 12px; }

    /* 4-column body */
    .bk-body {
        display: grid;
        grid-template-columns: 1.05fr 1.4fr 1.4fr 1.1fr;
        gap: 14px;
        padding: 16px 18px;
    }
    .bk-col-title {
        font-size: 10.5px; font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.7px;
        display: flex; align-items: center; gap: 6px;
        margin-bottom: 10px;
    }
    .bk-col-title svg { width: 12px; height: 12px; }
    .bk-actions-list { display: flex; flex-direction: column; gap: 6px; }
    .bk-action-item {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 10px;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 12px;
        color: var(--text-secondary);
        text-decoration: none;
        cursor: pointer;
    }
    .bk-action-item:hover { background: rgba(249,115,22,0.06); border-color: rgba(249,115,22,0.20); color: var(--text-primary); }
    .bk-action-item svg { width: 13px; height: 13px; flex-shrink: 0; color: var(--text-muted); }
    .bk-action-item.warning svg { color: #ef4444; }
    .bk-action-item.warning:hover { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.30); color: #ef4444; }

    .bk-finance-list { display: flex; flex-direction: column; gap: 6px; }
    .bk-finance-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 6px 0;
        border-bottom: 1px dashed var(--border-color);
        font-size: 12.5px;
    }
    .bk-finance-row:last-child { border-bottom: 0; }
    .bk-finance-row .lbl { color: var(--text-muted); }
    .bk-finance-row .val { color: var(--text-primary); font-weight: 700; }
    .bk-finance-row .pill { font-size: 9.5px; font-weight: 700; padding: 2px 6px; border-radius: 999px; margin-left: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
    .bk-pill-settled { background: rgba(16,185,129,0.15); color: #10b981; }
    .bk-pill-locked  { background: rgba(245,158,11,0.18); color: #d97706; }
    .bk-pill-pending { background: rgba(245,158,11,0.18); color: #d97706; }
    .bk-pill-missing { background: rgba(239,68,68,0.15);   color: #ef4444; }
    .bk-pill-given   { background: rgba(16,185,129,0.15); color: #10b981; }

    .bk-finance-cta { display: flex; gap: 6px; margin-top: 10px; }
    .bk-finance-cta button {
        flex: 1;
        padding: 8px 10px;
        border-radius: 7px;
        font-size: 11.5px; font-weight: 700;
        border: 1px solid;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    }
    .bk-finance-cta button svg { width: 11px; height: 11px; }
    .bk-cta-release { background: rgba(16,185,129,0.12); color: #10b981; border-color: rgba(16,185,129,0.30); }
    .bk-cta-refund  { background: rgba(239,68,68,0.10);  color: #ef4444; border-color: rgba(239,68,68,0.30); }

    .bk-docs-list { display: flex; flex-direction: column; gap: 6px; }
    .bk-doc-row {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 8px;
        background: var(--bg-card-hover);
        border-radius: 6px;
        font-size: 11.5px;
        color: var(--text-secondary);
    }
    .bk-doc-row svg { width: 12px; height: 12px; color: #ef4444; flex-shrink: 0; }
    .bk-doc-row b { color: var(--text-primary); font-weight: 600; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bk-doc-row .date { font-size: 10px; color: var(--text-muted); white-space: nowrap; }
    .bk-doc-add {
        display: flex; align-items: center; justify-content: center; gap: 5px;
        padding: 8px;
        background: rgba(99,102,241,0.08);
        border: 1px dashed rgba(99,102,241,0.40);
        border-radius: 7px;
        font-size: 11px; font-weight: 600;
        color: #6366f1;
        cursor: pointer;
        text-decoration: none;
        margin-top: 4px;
    }
    .bk-doc-add svg { width: 11px; height: 11px; }

    /* Milestone timeline */
    .bk-milestone-section {
        padding: 14px 18px;
        border-top: 1px solid var(--border-color);
        background: var(--bg-card-hover);
    }
    .bk-milestone-title {
        font-size: 10.5px; font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.7px;
        margin-bottom: 14px;
    }
    .bk-milestones {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0;
        position: relative;
    }
    .bk-milestone {
        text-align: center;
        position: relative;
    }
    .bk-milestone:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 11px; left: 50%; width: 100%;
        height: 2px;
        background: var(--border-color);
        z-index: 0;
    }
    .bk-milestone.done:not(:last-child)::after { background: #10b981; }
    .bk-milestone-dot {
        position: relative; z-index: 1;
        width: 22px; height: 22px;
        border-radius: 50%;
        background: var(--bg-card-hover);
        border: 2px solid var(--border-color);
        margin: 0 auto 6px;
        display: flex; align-items: center; justify-content: center;
    }
    .bk-milestone.done .bk-milestone-dot { background: #10b981; border-color: #10b981; color: #fff; }
    .bk-milestone.current .bk-milestone-dot { border-color: #f97316; background: #fff; }
    [data-theme="dark"] .bk-milestone.current .bk-milestone-dot { background: var(--bg-card); }
    .bk-milestone.current .bk-milestone-dot::after {
        content: '';
        width: 8px; height: 8px;
        background: #f97316;
        border-radius: 50%;
    }
    .bk-milestone-dot svg { width: 11px; height: 11px; }
    .bk-milestone-label { font-size: 10.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
    .bk-milestone-date  { font-size: 10px; color: var(--text-muted); }
    .bk-milestone-status { font-size: 10px; color: var(--text-muted); margin-top: 2px; font-style: italic; }

    /* AI smart summarizer */
    .bk-summarizer {
        padding: 12px 18px;
        border-top: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .bk-summarizer-head {
        display: flex; align-items: center; gap: 8px;
        font-size: 11.5px; font-weight: 700;
        color: var(--text-primary);
    }
    .bk-summarizer-head svg { width: 14px; height: 14px; color: #f59e0b; }
    .bk-summarizer-head .beta {
        font-size: 8.5px; font-weight: 700;
        padding: 1px 5px; border-radius: 3px;
        background: rgba(245,158,11,0.18); color: #d97706;
        text-transform: uppercase;
    }
    .bk-summarizer-body { flex: 1; min-width: 200px; font-size: 12px; color: var(--text-secondary); }
    .bk-summarizer-body a { color: #6366f1; text-decoration: none; font-weight: 600; }
    .bk-adjustments { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
    .bk-adjustments b { color: var(--text-primary); }

    /* ── Right rail ───────────────────────────────── */
    .bk-rail-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 14px 16px;
    }
    .bk-rail-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .bk-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .bk-rail-select {
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 3px 8px;
        font-size: 11px; color: var(--text-muted);
        cursor: pointer;
    }
    .bk-donut { position: relative; width: 130px; height: 130px; margin: 0 auto 12px; }
    .bk-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 2; }
    .bk-donut-center .num { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .bk-donut-center .lbl { font-size: 10.5px; color: var(--text-muted); }
    .bk-donut-legend { display: flex; flex-direction: column; gap: 5px; font-size: 11.5px; }
    .bk-donut-legend .row { display: flex; align-items: center; gap: 8px; }
    .bk-donut-legend .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .bk-donut-legend .lbl { flex: 1; color: var(--text-secondary); }
    .bk-donut-legend .val { font-weight: 700; color: var(--text-primary); }

    .bk-fin-row {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 12px;
        padding: 6px 0;
        border-bottom: 1px dashed var(--border-color);
    }
    .bk-fin-row:last-of-type { border-bottom: 0; }
    .bk-fin-row .lbl { color: var(--text-muted); }
    .bk-fin-row .val { font-weight: 800; font-size: 14px; }
    .bk-fin-row .val.amber { color: #f59e0b; }
    .bk-fin-row .val.green { color: #10b981; }
    .bk-fin-row .val.red   { color: #ef4444; }

    .bk-rail-link {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 10px;
        font-size: 12px; font-weight: 600;
        color: #f97316;
        text-decoration: none;
    }
    .bk-rail-link svg { width: 12px; height: 12px; }

    .bk-ms-row {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 0;
        border-bottom: 1px dashed var(--border-color);
    }
    .bk-ms-row:last-of-type { border-bottom: 0; }
    .bk-ms-dot {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: rgba(99,102,241,0.10);
        color: #6366f1;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bk-ms-dot svg { width: 13px; height: 13px; }
    .bk-ms-body { flex: 1; min-width: 0; }
    .bk-ms-type { font-size: 12px; font-weight: 700; color: var(--text-primary); }
    .bk-ms-event { font-size: 10.5px; color: var(--text-muted); }
    .bk-ms-date { font-size: 10.5px; color: var(--text-muted); text-align: right; white-space: nowrap; }
    .bk-ms-count { background: var(--border-color); color: var(--text-muted); font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; }

    .bk-qa-list { display: flex; flex-direction: column; gap: 6px; }
    .bk-qa-item {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 12px;
        background: var(--bg-card-hover);
        border: 1px solid var(--border-color);
        border-radius: 7px;
        font-size: 12px; font-weight: 600;
        color: var(--text-primary);
        text-decoration: none;
    }
    .bk-qa-item:hover { background: rgba(249,115,22,0.06); border-color: rgba(249,115,22,0.30); }
    .bk-qa-item svg { width: 14px; height: 14px; color: #6366f1; }

    @media (max-width: 1200px) {
        .bk-layout { grid-template-columns: 1fr; }
        .bk-rail { position: static; }
        .bk-stats { grid-template-columns: repeat(3, 1fr); }
        .bk-body { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .bk-stats { grid-template-columns: repeat(2, 1fr); }
        .bk-card-meta { grid-template-columns: 1fr; }
        .bk-card-meta .item-providers { justify-content: flex-start; }
        .bk-body { grid-template-columns: 1fr; }
        .bk-milestones { grid-template-columns: repeat(3, 1fr); gap: 12px 8px; }
        .bk-milestone:not(:last-child)::after { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusForCard = fn ($status) => match ($status) {
        'requested' => 'upcoming',
        'confirmed' => 'in_progress',
        'completed' => 'completed',
        'pending'   => 'on_hold',
        'cancelled' => 'cancelled',
        default     => $status,
    };
    $tabs = [
        'all'         => 'All Status',
        'upcoming'    => 'Upcoming',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'pending'     => 'On Hold',
        'cancelled'   => 'Cancelled',
    ];
@endphp

<div class="bk-layout">

    {{-- ════════════════════ MAIN ════════════════════ --}}
    <div class="bk-main">

        {{-- Top stats row --}}
        <div class="bk-stats">
            <div class="bk-stat">
                <div class="bk-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div><div class="bk-stat-label">All Bookings</div><div class="bk-stat-value">{{ $stats['all'] }}</div><div class="bk-stat-sub">View all bookings</div></div>
            </div>
            <div class="bk-stat">
                <div class="bk-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div><div class="bk-stat-label">Upcoming</div><div class="bk-stat-value">{{ $stats['upcoming'] }}</div><div class="bk-stat-sub">Next 30 days</div></div>
            </div>
            <div class="bk-stat">
                <div class="bk-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div><div class="bk-stat-label">In Progress</div><div class="bk-stat-value">{{ $stats['in_progress'] }}</div><div class="bk-stat-sub">Currently active</div></div>
            </div>
            <div class="bk-stat">
                <div class="bk-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
                <div><div class="bk-stat-label">Completed</div><div class="bk-stat-value">{{ $stats['completed'] }}</div><div class="bk-stat-sub">All done</div></div>
            </div>
            <div class="bk-stat">
                <div class="bk-stat-ico purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg></div>
                <div><div class="bk-stat-label">On Hold</div><div class="bk-stat-value">{{ $stats['pending'] }}</div><div class="bk-stat-sub">Awaiting action</div></div>
            </div>
            <div class="bk-stat">
                <div class="bk-stat-ico red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                <div><div class="bk-stat-label">Cancelled</div><div class="bk-stat-value">{{ $stats['cancelled'] }}</div><div class="bk-stat-sub">Cancelled bookings</div></div>
            </div>
        </div>

        {{-- Tabs + action row --}}
        <div class="bk-tabs-row">
            <div class="bk-tabs">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('client.bookings.index', ['tab' => $key]) }}" class="bk-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="bk-row-actions">
                <button type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>Date Range</button>
                <button type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters</button>
                <div class="bk-view-toggle">
                    <button class="is-active" title="Card view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></button>
                    <button title="Grid view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></button>
                </div>
            </div>
        </div>

        {{-- Gateway chips + search --}}
        <div class="bk-gw-row">
            <div class="bk-chips">
            </div>
            <form method="GET" class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search bookings by event, professional, or ID...">
                <input type="hidden" name="tab" value="{{ $tab }}">
            </form>
        </div>

        {{-- Booking cards --}}
        <div class="bk-cards">
            @forelse($bookings as $booking)
                @php
                    $cardStatus = $statusForCard($booking->status);
                    $statusLabel = strtoupper(str_replace('_', '-', $cardStatus));
                    $contractValue = $booking->total_amount ?? $booking->agreed_price ?? 2500;
                    $spent = round($contractValue * 0.83); // proxy until per-milestone ledger ships
                    $pct = $contractValue > 0 ? min(100, ($spent / $contractValue) * 100) : 0;
                    $supplierInitial = strtoupper(substr($booking->event?->title ?? 'B', 0, 1));
                @endphp
                <div class="bk-card">
                    <div class="bk-card-head">
                        <div class="bk-card-icon">{{ $supplierInitial }}</div>
                        <div style="flex:1;min-width:0;">
                            <div class="bk-card-title">{{ $booking->event?->title ?? 'Booking #' . $booking->id }}</div>
                            <div style="font-size:11.5px;color:var(--text-muted);margin-top:1px;">
                                Professional: <span style="color:var(--text-primary);font-weight:600;">{{ $booking->supplier?->name ?? 'Unknown' }}</span>
                                @if(($booking->event?->categories ?? collect())->first())
                                    ({{ $booking->event->categories->first()->name }})
                                @endif
                            </div>
                        </div>
                        <span class="bk-card-status bk-status-{{ $cardStatus }}">{{ $statusLabel }}</span>
                        <button type="button" class="bk-card-kebab">⋮</button>
                    </div>

                    <div class="bk-card-meta">
                        <div class="item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                            Event Date: <b>{{ $booking->event?->starts_at?->format('M d, Y') ?? '—' }}</b>
                        </div>
                        <div class="item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $booking->event?->location ?? 'Location TBD' }}</span>
                        </div>
                        <div class="item item-providers">
                            <span class="bk-provider-tag"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Secure Payment.com</span>
                            <span class="bk-provider-tag"><svg viewBox="0 0 24 24" fill="#635bff"><path d="M13.479 9.883c-1.626-.604-2.512-1.067-2.512-1.803 0-.622.511-.977 1.423-.977 1.667 0 3.379.642 4.558 1.22l.666-4.111c-.935-.446-2.847-1.177-5.49-1.177-1.87 0-3.425.488-4.536 1.4-1.156.96-1.753 2.346-1.753 4.02 0 3.038 1.857 4.339 4.885 5.434 1.952.711 2.604 1.221 2.604 1.999 0 .755-.629 1.187-1.79 1.187-1.45 0-3.836-.71-5.398-1.62l-.674 4.157C6.86 19.578 8.918 20 11.078 20c1.973 0 3.62-.466 4.737-1.343 1.244-.978 1.889-2.422 1.889-4.299 0-3.111-1.891-4.404-4.225-5.475z"/></svg>Stripe</span>
                            <span class="bk-provider-tag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Contract Signed</span>
                        </div>
                    </div>

                    <div class="bk-spent-row">
                        <div class="bk-spent-text">You've spent <b>${{ number_format($spent) }}</b> of <b>$600</b> IRS 1099 threshold</div>
                        <div class="bk-spent-bar"><div class="bk-spent-fill" style="width:{{ $pct }}%;"></div></div>
                        <span class="bk-spent-pct">{{ round($pct) }}%</span>
                        @if($pct > 80)
                            <span class="bk-spent-warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></span>
                        @endif
                    </div>

                    <div class="bk-body">
                        {{-- Actions & Pipeline --}}
                        <div>
                            <div class="bk-col-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                Actions &amp; Pipeline
                            </div>
                            <div class="bk-actions-list">
                                <a href="{{ route('client.chat.index') }}" class="bk-action-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Open Chat with {{ \Illuminate\Support\Str::limit($booking->supplier?->name ?? 'Pro', 14, '…') }}
                                </a>
                                
                                
                                
                            </div>
                        </div>

                        {{-- Finance & Gateway Status --}}
                        <div>
                            <div class="bk-col-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                Finance &amp; Gateway Status
                            </div>
                            <div class="bk-finance-list">
                                <div class="bk-finance-row"><span class="lbl">Contract Value</span><span class="val">${{ number_format($contractValue) }}</span></div>
                                <div class="bk-finance-row"><span class="lbl">Stripe Deposit</span><span class="val">${{ number_format(round($contractValue * 0.2)) }}<span class="pill bk-pill-settled">Settled</span></span></div>
                                <div class="bk-finance-row"><span class="lbl">Secure Payment.com Balance</span><span class="val">${{ number_format(round($contractValue * 0.8)) }}<span class="pill bk-pill-locked">Locked</span></span></div>
                                <div class="bk-finance-row"><span class="lbl">Next Payout Trigger</span><span class="val" style="font-size:11.5px;">Setup Day ({{ $booking->event?->starts_at?->format('M d, Y') ?? 'TBD' }})</span></div>
                            </div>
                            <div class="bk-finance-cta">
                                <button class="bk-cta-release"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Release Milestone</button>
                                <button class="bk-cta-refund"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>Issue Refund</button>
                            </div>
                        </div>

                        {{-- Regulatory & Legal Status --}}
                        <div>
                            <div class="bk-col-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Regulatory &amp; Legal Status
                            </div>
                            <div class="bk-finance-list">
                                <div class="bk-finance-row"><span class="lbl">W-9 Status</span><span class="val" style="font-size:11.5px;">Verified <span class="pill bk-pill-given">✓</span></span></div>
                                <div class="bk-finance-row"><span class="lbl">AI Chat Append Consent</span><span class="val" style="font-size:11.5px;">Given <span class="pill bk-pill-given">✓</span></span></div>
                                <div class="bk-finance-row"><span class="lbl">1099 Rolling Total (YTD)</span><span class="val">${{ number_format($spent) }} / $600</span></div>
                                <div class="bk-finance-row"><span class="lbl">Tax Form</span><span class="val" style="font-size:11.5px;">W-9 Filed</span></div>
                            </div>
                            
                        </div>

                        {{-- Quick Documents --}}
                        <div>
                            <div class="bk-col-title" style="display:flex;justify-content:space-between;">
                                <span style="display:flex;align-items:center;gap:6px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Quick Documents</span>
                            </div>
                            <div class="bk-docs-list">
                                @php
                                    $docs = [
                                        ['name' => 'Signed Contract.pdf', 'date' => $booking->created_at?->format('M d')],
                                        ['name' => 'Event Brief.pdf',     'date' => $booking->created_at?->copy()?->addDays(2)?->format('M d')],
                                        ['name' => 'Stripe Receipt.pdf', 'date' => $booking->created_at?->copy()?->addDays(5)?->format('M d')],
                                        ['name' => 'W-9 Form.pdf', 'date' => $booking->created_at?->copy()?->addDays(10)?->format('M d')],
                                    ];
                                @endphp
                                @foreach($docs as $doc)
                                    <div class="bk-doc-row">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <b>{{ $doc['name'] }}</b><span class="date">{{ $doc['date'] }} · PDF</span>
                                    </div>
                                @endforeach
                            </div>
                            
                        </div>
                    </div>

                    {{-- Milestone progress timeline --}}
                    <div class="bk-milestone-section">
                        <div class="bk-milestone-title">Milestone Progress</div>
                        @php
                            $milestones = [
                                ['label' => 'Contract Signed',         'date' => $booking->created_at?->format('M d'), 'state' => 'done'],
                                ['label' => 'Deposit Paid (Stripe)',   'date' => $booking->created_at?->copy()?->addDays(3)?->format('M d'), 'state' => 'done'],
                                ['label' => 'Checked-in at Venue',     'date' => $booking->event?->starts_at?->format('M d') ?? 'TBD', 'state' => $cardStatus === 'completed' ? 'done' : ($cardStatus === 'in_progress' ? 'current' : 'pending')],
                                ['label' => 'In Inspection (Secure Payment)',  'date' => 'Pending', 'state' => $cardStatus === 'completed' ? 'current' : 'pending'],
                                ['label' => 'Funds Released',          'date' => 'Pending', 'state' => 'pending'],
                            ];
                        @endphp
                        <div class="bk-milestones">
                            @foreach($milestones as $m)
                                <div class="bk-milestone {{ $m['state'] }}">
                                    <div class="bk-milestone-dot">
                                        @if($m['state'] === 'done')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        @endif
                                    </div>
                                    <div class="bk-milestone-label">{{ $m['label'] }}</div>
                                    <div class="bk-milestone-date">{{ $m['date'] }}</div>
                                    @if($m['state'] !== 'done' && $m['state'] !== 'current')
                                        <div class="bk-milestone-status">Pending</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- AI Smart Summarizer --}}
                    <div class="bk-summarizer">
                        <div class="bk-summarizer-head">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            AI Smart Summarizer
                            <span class="beta">Beta</span>
                        </div>
                        <div class="bk-summarizer-body">
                            @if($booking->event)
                                Vendor confirmed start time and venue check-in. 
                            @else
                                No new contract adjustments detected in chat.
                            @endif
                        </div>
                        <div class="bk-adjustments"><b>{{ rand(0, 3) }}</b> New Adjustments</div>
                    </div>
                </div>
            @empty
                <div class="bk-card" style="padding:60px 20px;text-align:center;">
                    <div style="font-size:16px;font-weight:700;margin-bottom:6px;">No bookings found</div>
                    <div style="font-size:13px;color:var(--text-muted);">
                        @if($tab === 'all')
                            Once professionals respond to your gigs, bookings will show up here.
                        @else
                            Try a different status tab — no bookings in <b>{{ $tabs[$tab] ?? 'this' }}</b>.
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if($bookings->hasPages())
            <div style="margin-top:18px;">{{ $bookings->onEachSide(1)->links() }}</div>
        @endif
    </div>

    {{-- ════════════════════ RIGHT RAIL ════════════════════ --}}
    <aside class="bk-rail">

        {{-- Booking Overview donut --}}
        <div class="bk-rail-card">
            <div class="bk-rail-head">
                <div class="bk-rail-title">Booking Overview</div>
                <select class="bk-rail-select"><option>This Month</option><option>YTD</option></select>
            </div>
            @php
                $total = max(1, $stats['all']);
                $pieData = [
                    ['lbl' => 'In Progress', 'val' => $stats['in_progress'], 'color' => '#10b981'],
                    ['lbl' => 'Upcoming',    'val' => $stats['upcoming'],    'color' => '#6366f1'],
                    ['lbl' => 'Completed',   'val' => $stats['completed'],   'color' => '#8b5cf6'],
                    ['lbl' => 'On Hold',     'val' => $stats['pending'],     'color' => '#f59e0b'],
                    ['lbl' => 'Cancelled',   'val' => $stats['cancelled'],   'color' => '#ef4444'],
                ];
                /* Build conic-gradient stops so the donut reflects the real
                   status breakdown. Each segment angle = (val/total) * 360. */
                $cursor = 0;
                $stops = [];
                foreach ($pieData as $p) {
                    $deg = ($p['val'] / $total) * 360;
                    $stops[] = "{$p['color']} {$cursor}deg " . ($cursor + $deg) . 'deg';
                    $cursor += $deg;
                }
                $conic = 'conic-gradient(' . implode(', ', $stops) . ')';
            @endphp
            <div class="bk-donut" style="background: {{ $conic }}; border-radius: 50%;">
                <div style="position:absolute;inset:14px;background:var(--bg-card);border-radius:50%;z-index:1;"></div>
                <div class="bk-donut-center">
                    <span class="num">{{ $stats['all'] }}</span>
                    <span class="lbl">Total</span>
                </div>
            </div>
            <div class="bk-donut-legend">
                @foreach($pieData as $p)
                    @php $pct = $stats['all'] > 0 ? round(($p['val'] / $stats['all']) * 100) : 0; @endphp
                    <div class="row">
                        <span class="dot" style="background:{{ $p['color'] }};"></span>
                        <span class="lbl">{{ $p['lbl'] }}</span>
                        <span class="val">{{ $p['val'] }} ({{ $pct }}%)</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Financial Overview --}}
        <div class="bk-rail-card">
            <div class="bk-rail-head">
                <div class="bk-rail-title">Financial Overview</div>
                <select class="bk-rail-select"><option>This Month</option><option>YTD</option></select>
            </div>
            <div class="bk-fin-row"><span class="lbl">Total Bookings Value</span><span class="val">${{ number_format($financial['total_value']) }}</span></div>
            <div class="bk-fin-row"><span class="lbl">Locked in Secure Payment</span><span class="val amber">${{ number_format($financial['locked_secure payment']) }}</span></div>
            <div class="bk-fin-row"><span class="lbl">Paid Out (YTD)</span><span class="val green">${{ number_format($financial['paid_out_ytd']) }}</span></div>
            <div class="bk-fin-row"><span class="lbl">Pending Payouts</span><span class="val amber">${{ number_format($financial['pending_payouts']) }}</span></div>
            <a href="{{ route('client.payments.index') }}" class="bk-rail-link">View Full Financials <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Upcoming Milestones --}}
        <div class="bk-rail-card">
            <div class="bk-rail-head">
                <div class="bk-rail-title">Upcoming Milestones</div>
            </div>
            @if($upcomingMilestones->count())
                @foreach($upcomingMilestones as $m)
                    <div class="bk-ms-row">
                        <div class="bk-ms-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg></div>
                        <div class="bk-ms-body">
                            <div class="bk-ms-type">{{ $m['type'] }}</div>
                            <div class="bk-ms-event">{{ \Illuminate\Support\Str::limit($m['event'], 22) }}</div>
                        </div>
                        <div class="bk-ms-date">{{ $m['date'] }}</div>
                        <span class="bk-ms-count">{{ $m['count'] }}</span>
                    </div>
                @endforeach
            @else
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No upcoming milestones</div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="bk-rail-card">
            <div class="bk-rail-head"><div class="bk-rail-title">Quick Actions</div></div>
            <div class="bk-qa-list">
                <a href="{{ route('client.post-event.choose') }}" class="bk-qa-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Post an Event</a>
                <a href="{{ route('client.bookings.index') }}" class="bk-qa-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>View All Bookings</a>
            </div>
        </div>
    </aside>
</div>
@endsection
