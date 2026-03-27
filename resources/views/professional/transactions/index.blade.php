@extends('layouts.professional')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

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

    {{-- Search + Filter --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('professional.transactions.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div class="cl-search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search transactions..." value="{{ request('search') }}">
                </div>
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="date_from" class="cl-form-input" value="{{ request('date_from') }}" placeholder="From date">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="date_to" class="cl-form-input" value="{{ request('date_to') }}" placeholder="To date">
            </div>
            <div style="min-width: 150px;">
                <select name="status" class="cl-form-select" style="padding: 10px 14px;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
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
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h8v8H8z"/></svg>
            </div>
            <div class="cl-empty-title">No transactions yet</div>
            <div class="cl-empty-text">All your financial transactions will be recorded here.</div>
        </div>
    </div>
@endsection
