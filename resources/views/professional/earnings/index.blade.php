@extends('layouts.professional')

@section('title', 'Earnings')
@section('page-title', 'Earnings')

@push('styles')
<style>
    .cl-table {
        width: 100%;
        border-collapse: collapse;
    }
    .cl-table thead {
        border-bottom: 1px solid var(--border-color);
    }
    .cl-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cl-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 13px;
    }
    .cl-table tbody tr:hover {
        background: rgba(255,255,255,0.02);
    }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Earnings</h2>
        </div>
        <button class="cl-btn cl-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Withdraw Funds
        </button>
    </div>

    {{-- Stat Cards --}}
    <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Earnings</div>
                    <div class="cl-stat-value">${{ number_format($stats['total_earnings'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Withdrawn</div>
                    <div class="cl-stat-value">${{ number_format($stats['withdrawn'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Pending</div>
                    <div class="cl-stat-value">${{ number_format($stats['pending'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon pink">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Available Balance</div>
                    <div class="cl-stat-value">${{ number_format($stats['available'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search + Filter --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('professional.earnings.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div class="cl-search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search earnings..." value="{{ request('search') }}">
                </div>
            </div>
            <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
        </form>
    </div>

    {{-- Empty State --}}
    <div class="cl-card">
        <div class="cl-empty">
            <div class="cl-empty-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="cl-empty-title">No earnings recorded yet</div>
            <div class="cl-empty-text">Complete gigs to start earning. Your earnings will appear here.</div>
        </div>
    </div>
@endsection
