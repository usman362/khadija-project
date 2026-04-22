@extends('layouts.professional')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

@push('styles')
<style>
    /* ── Transactions layout ─────────────────────────────── */
    .tx-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    @media (max-width: 1100px) { .tx-layout { grid-template-columns: 1fr; } }

    .tx-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 12px;
    }
    .tx-panel-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tx-panel-title svg { width: 18px; height: 18px; opacity: 0.7; }

    /* ── Filter bar ──────────────────────────────────────── */
    .tx-filter-bar {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 0;
    }
    .tx-filter-bar .cl-search-box { flex: 1; min-width: 200px; }
    .tx-filter-bar input[type="date"],
    .tx-filter-bar select {
        min-width: 130px;
    }
    .tx-content-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: rgba(99,102,241,0.08);
        color: var(--text-primary);
        border: 1px solid rgba(99,102,241,0.25);
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    /* ── Kebab export menu ───────────────────────────────── */
    .tx-export-wrap { position: relative; display: inline-block; }
    .tx-kebab-btn {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--text-muted);
        width: 38px;
        height: 38px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tx-kebab-btn:hover { background: rgba(255,255,255,0.04); color: var(--text-primary); }
    .tx-export-menu {
        position: absolute;
        top: 46px;
        right: 0;
        min-width: 180px;
        background: #fff;
        color: #0f172a;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        overflow: hidden;
        display: none;
        z-index: 20;
    }
    .tx-export-menu.open { display: block; }
    .tx-export-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
    }
    .tx-export-menu a:hover { background: #f1f5f9; }
    .tx-export-menu a + a { border-top: 1px solid #e2e8f0; }

    /* ── Activity feed ───────────────────────────────────── */
    .tx-activity-item {
        display: flex;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .tx-activity-item:last-child { border-bottom: none; }
    .tx-activity-dot {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: rgba(99,102,241,0.15);
        color: var(--accent-blue);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .tx-activity-dot svg { width: 16px; height: 16px; }
    .tx-activity-body { flex: 1; min-width: 0; }
    .tx-activity-title { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
    .tx-activity-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    /* ── Jump to bottom ──────────────────────────────────── */
    .tx-jump-bottom {
        position: fixed;
        right: 24px;
        bottom: 24px;
        background: var(--accent-blue);
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 13px;
        display: none;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 30px rgba(99,102,241,0.4);
        cursor: pointer;
        z-index: 15;
    }
    .tx-jump-bottom.visible { display: inline-flex; }
    .tx-jump-bottom svg { width: 16px; height: 16px; }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Transactions</h2>
        <p style="color: var(--text-muted); font-size: 14px;">View all your financial transactions and activities.</p>
    </div>

    {{-- Stat Cards --}}
    <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Transactions</div>
                    <div class="cl-stat-value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Earned</div>
                    <div class="cl-stat-value">${{ number_format($stats['earned'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Withdrawals</div>
                    <div class="cl-stat-value">${{ number_format($stats['withdrawn'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon pink">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 5l-3.5-3-3.5 3"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Pending</div>
                    <div class="cl-stat-value">${{ number_format($stats['pending'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter + Export bar --}}
    <div class="cl-card" style="margin-bottom: 0;">
        <form method="GET" action="{{ route('professional.transactions.index') }}" class="tx-filter-bar">
            <div class="cl-search-box">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" placeholder="Search transactions..." value="{{ $filters['search'] }}">
            </div>

            <input type="date" name="date_from" class="cl-form-input" value="{{ $filters['date_from'] }}">
            <input type="date" name="date_to" class="cl-form-input" value="{{ $filters['date_to'] }}">

            <select name="status" class="cl-form-select" style="padding: 10px 14px;">
                <option value="">All Status</option>
                <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="failed" {{ $filters['status'] === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>

            {{-- Content-type filter: events / services / professionals / bookings / payouts --}}
            <select name="content_type" class="cl-form-select" style="padding: 10px 14px;" title="Filter the results by content type">
                @foreach($contentFilters as $value => $label)
                    <option value="{{ $value }}" {{ $filters['content_type'] === $value ? 'selected' : '' }}>
                        Filter: {{ $label }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>

            {{-- Export kebab --}}
            <div class="tx-export-wrap" style="margin-left: auto;">
                <button type="button" class="tx-kebab-btn" onclick="txToggleExport(event)" aria-label="Export options">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                </button>
                <div class="tx-export-menu" id="txExportMenu">
                    <a href="{{ route('professional.transactions.export.csv', request()->query()) }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export to CSV
                    </a>
                    <a href="{{ route('professional.transactions.export.pdf', request()->query()) }}" target="_blank">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Export to PDF
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Two-column layout: Transactions | Activity --}}
    <div class="tx-layout">
        {{-- Transactions --}}
        <div class="cl-card">
            <div class="tx-panel-header">
                <div class="tx-panel-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Transactions
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    {{ $transactions->total() }} total
                </div>
            </div>

            @if($transactions->total() === 0)
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h8v8H8z"/></svg>
                    </div>
                    <div class="cl-empty-title">No transactions yet</div>
                    <div class="cl-empty-text">All your financial transactions will be recorded here.</div>
                </div>
            @else
                <table class="cl-table">
                    <thead><tr><th>Date</th><th>Description</th><th>Type</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($transactions as $txn)
                            <tr>
                                <td>{{ $txn['date'] ?? '—' }}</td>
                                <td>{{ $txn['description'] ?? '—' }}</td>
                                <td>{{ $txn['type'] ?? '—' }}</td>
                                <td><span class="cl-badge">{{ $txn['status'] ?? '—' }}</span></td>
                                <td class="text-end">${{ number_format($txn['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Pagination strip — visible even when empty so the UI scaffold matches the design --}}
            <div class="cl-pagination" style="margin-top: 20px;">
                @if($transactions->onFirstPage())
                    <span class="disabled"><span>« Previous</span></span>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}">« Previous</a>
                @endif

                @php
                    // Render a compact page-number strip matching the client's annotation:
                    // "Previous 1 2 3 4 5 ... 12 Next"
                    $total    = max($transactions->lastPage(), 1);
                    $current  = $transactions->currentPage();
                    $window   = 2; // pages on each side of current
                    $pages    = [];
                    for ($i = 1; $i <= $total; $i++) {
                        if ($i === 1 || $i === $total || ($i >= $current - $window && $i <= $current + $window)) {
                            $pages[] = $i;
                        } elseif (end($pages) !== '…') {
                            $pages[] = '…';
                        }
                    }
                @endphp
                @foreach($pages as $p)
                    @if($p === '…')
                        <span><span>…</span></span>
                    @elseif($p === $current)
                        <span class="active"><span>{{ $p }}</span></span>
                    @else
                        <a href="{{ $transactions->url($p) }}">{{ $p }}</a>
                    @endif
                @endforeach

                @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}">Next »</a>
                @else
                    <span class="disabled"><span>Next »</span></span>
                @endif
            </div>
        </div>

        {{-- Activity --}}
        <div class="cl-card">
            <div class="tx-panel-header">
                <div class="tx-panel-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Activity
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    Live feed
                </div>
            </div>

            @if($activity->total() === 0)
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h8v8H8z"/></svg>
                    </div>
                    <div class="cl-empty-title">No activity yet</div>
                    <div class="cl-empty-text">Booking confirmations, proposal updates, and payout events will appear here.</div>
                </div>
            @else
                @foreach($activity as $act)
                    <div class="tx-activity-item">
                        <div class="tx-activity-dot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div class="tx-activity-body">
                            <div class="tx-activity-title">{{ $act['title'] ?? 'Activity' }}</div>
                            <div class="tx-activity-meta">{{ $act['meta'] ?? '' }}</div>
                        </div>
                    </div>
                @endforeach
            @endif

            @if($activity->hasPages())
                <div class="cl-pagination" style="margin-top: 20px;">
                    @if($activity->onFirstPage())
                        <span class="disabled"><span>«</span></span>
                    @else
                        <a href="{{ $activity->previousPageUrl() }}">«</a>
                    @endif
                    <span class="active"><span>{{ $activity->currentPage() }} / {{ $activity->lastPage() }}</span></span>
                    @if($activity->hasMorePages())
                        <a href="{{ $activity->nextPageUrl() }}">»</a>
                    @else
                        <span class="disabled"><span>»</span></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Floating "Jump to bottom" button --}}
    <button type="button" class="tx-jump-bottom" id="txJumpBottom" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
        Jump to bottom
    </button>

    <script>
        function txToggleExport(e) {
            e.stopPropagation();
            document.getElementById('txExportMenu').classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('txExportMenu');
            if (menu && !e.target.closest('.tx-export-wrap')) menu.classList.remove('open');
        });

        // Show jump-to-bottom button once user scrolls past the filter bar
        (function () {
            const btn = document.getElementById('txJumpBottom');
            if (!btn) return;
            window.addEventListener('scroll', () => {
                const scrolled = window.scrollY > 300;
                const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 80);
                btn.classList.toggle('visible', scrolled && !nearBottom);
            }, { passive: true });
        })();
    </script>
@endsection
