@extends('layouts.professional')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Welcome --}}
    <div style="margin-bottom: 28px;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Welcome back, {{ auth()->user()?->name }} <span style="font-size: 24px;">👋</span></h2>
        <p style="color: var(--text-muted); font-size: 14px;">Here's your professional overview</p>
    </div>

    {{-- Stat Cards --}}
    <div class="cl-grid cl-grid-4" style="margin-bottom: 28px;">
        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><path d="M12 1v6m0 6v6"/><path d="M4.22 4.22l4.24 4.24m3.08 3.08l4.24 4.24"/><path d="M1 12h6m6 0h6"/><path d="M4.22 19.78l4.24-4.24m3.08-3.08l4.24-4.24"/><path d="M19.78 19.78l-4.24-4.24m-3.08-3.08l-4.24-4.24"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Available Balance</div>
                    <div class="cl-stat-value">${{ number_format($stats['available_balance'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">This Month</div>
                    <div class="cl-stat-value">${{ number_format($stats['this_month_earnings'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon yellow">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Booked</div>
                    <div class="cl-stat-value">{{ $stats['total_booked'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon pink">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Average Rating</div>
                    <div class="cl-stat-value">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="cl-grid cl-grid-3" style="margin-bottom: 28px;">
        <div class="cl-card">
            <div style="display: flex; align-items: flex-start; gap: 16px;">
                <div class="cl-stat-icon blue" style="width: 48px; height: 48px; min-width: 48px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">My Gigs</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 12px;">Manage your gigs and services for clients.</p>
                    <a href="{{ route('professional.gigs.index') }}" style="display: inline-flex; align-items: center; gap: 8px; color: var(--accent-blue); font-size: 14px; font-weight: 500; text-decoration: none;">
                        View Gigs
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div style="display: flex; align-items: flex-start; gap: 16px;">
                <div class="cl-stat-icon green" style="width: 48px; height: 48px; min-width: 48px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3"/></svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Proposals</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 12px;">Send and track proposals for new events.</p>
                    <a href="{{ route('professional.proposals.index') }}" style="display: inline-flex; align-items: center; gap: 8px; color: var(--accent-green); font-size: 14px; font-weight: 500; text-decoration: none;">
                        View Proposals
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div style="display: flex; align-items: flex-start; gap: 16px;">
                <div class="cl-stat-icon pink" style="width: 48px; height: 48px; min-width: 48px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Earnings</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 12px;">Track your balance and withdraw earnings.</p>
                    <a href="{{ route('professional.earnings.index') }}" style="display: inline-flex; align-items: center; gap: 8px; color: var(--accent-pink); font-size: 14px; font-weight: 500; text-decoration: none;">
                        View Earnings
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Two Column Layout: Recent Bookings + Recent Reviews --}}
    <div class="cl-grid cl-grid-3" style="margin-bottom: 28px;">
        {{-- Recent Bookings --}}
        <div class="cl-card" style="grid-column: span 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 600;">Recent Bookings</h3>
                <a href="{{ route('professional.proposals.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">View All</a>
            </div>

            @if($recentBookings->count())
                <table class="cl-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                        <tr>
                            <td style="color: var(--text-primary); font-weight: 500;">{{ $booking->event?->title ?? 'N/A' }}</td>
                            <td>{{ $booking->client?->name ?? '—' }}</td>
                            <td>{{ $booking->event?->starts_at?->format('M d, Y') ?? '—' }}</td>
                            <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="cl-empty-title">No bookings yet</div>
                    <div class="cl-empty-text">Your bookings will appear here once clients book you for events.</div>
                </div>
            @endif
        </div>

        {{-- Recent Reviews --}}
        <div class="cl-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 600;">Recent Reviews</h3>
                <a href="{{ route('professional.reviews.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">View All</a>
            </div>

            <div class="cl-empty" style="text-align: center;">
                <div class="cl-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div class="cl-empty-title">No reviews yet</div>
                <div class="cl-empty-text">Client reviews will appear here after completed bookings.</div>
            </div>
        </div>
    </div>
@endsection
