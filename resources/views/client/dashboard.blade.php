@extends('layouts.client')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Welcome --}}
    <div style="margin-bottom: 28px;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Welcome back, {{ auth()->user()?->name }} <span style="font-size: 24px;">👋</span></h2>
        <p style="color: var(--text-muted); font-size: 14px;">
            Here's what's happening with your events and bookings.
        </p>
    </div>

    {{-- Stat Cards --}}
    <div class="cl-grid cl-grid-4" style="margin-bottom: 28px;">
        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Events</div>
                    <div class="cl-stat-value">{{ $stats['total_events'] }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Open Events</div>
                    <div class="cl-stat-value">{{ $stats['open_events'] }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon yellow">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Bookings</div>
                    <div class="cl-stat-value">{{ $stats['total_bookings'] }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon pink">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Active Bookings</div>
                    <div class="cl-stat-value">{{ $stats['active_bookings'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="cl-grid cl-grid-3" style="margin-bottom: 28px;">
        {{-- Recent Events --}}
        <div class="cl-card" style="grid-column: span 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 600;">Recent Events</h3>
                <a href="{{ route('client.events.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">View All</a>
            </div>

            @if($recentEvents->count())
                <table class="cl-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentEvents as $event)
                        <tr>
                            <td style="color: var(--text-primary); font-weight: 500;">{{ $event->title }}</td>
                            <td>{{ $event->categories->pluck('name')->join(', ') ?: '—' }}</td>
                            <td><span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span></td>
                            <td>{{ $event->starts_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="cl-empty-title">No events yet</div>
                    <div class="cl-empty-text">Create your first event to get started.</div>
                    <a href="{{ route('client.events.index') }}?create=1" class="cl-btn cl-btn-primary cl-btn-sm">Post New Event</a>
                </div>
            @endif
        </div>

        {{-- Quick Actions + Subscription --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            {{-- Subscription --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">My Plan</h3>
                @if($subscription)
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div class="cl-stat-icon purple" style="width: 40px; height: 40px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        </div>
                        <div>
                            <div style="font-weight: 600;">{{ $subscription->plan?->name ?? 'Active Plan' }}</div>
                            <span class="cl-badge cl-badge-confirmed">Active</span>
                        </div>
                    </div>
                    @if($subscription->expires_at)
                        <p style="font-size: 13px; color: var(--text-muted);">Expires: {{ $subscription->expires_at->format('M d, Y') }}</p>
                    @endif
                @else
                    <div style="text-align: center; padding: 12px 0;">
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 12px;">No active subscription</p>
                        <a href="{{ route('app.membership-plans.index') }}" class="cl-btn cl-btn-primary cl-btn-sm">Browse Plans</a>
                    </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="{{ route('client.events.index') }}?create=1" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--accent-blue-soft); color: var(--accent-blue); text-decoration: none; font-size: 14px; font-weight: 500; transition: var(--transition);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Post New Event
                    </a>
                    <a href="{{ route('client.events.index') }}" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--accent-blue-soft); color: var(--accent-blue); text-decoration: none; font-size: 14px; font-weight: 500; transition: var(--transition);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        View Events
                    </a>
                    <a href="{{ route('client.bookings.index') }}" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--accent-green-soft); color: var(--accent-green); text-decoration: none; font-size: 14px; font-weight: 500; transition: var(--transition);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        View Bookings
                    </a>
                    <a href="{{ route('client.chat.index') }}" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--accent-cyan-soft); color: var(--accent-cyan); text-decoration: none; font-size: 14px; font-weight: 500; transition: var(--transition);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Open Messages
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Bookings --}}
    <div class="cl-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 600;">Recent Bookings</h3>
            <a href="{{ route('client.bookings.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">View All</a>
        </div>

        @if($recentBookings->count())
            <table class="cl-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Professional</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBookings as $booking)
                    <tr>
                        <td style="color: var(--text-primary); font-weight: 500;">{{ $booking->event?->title ?? 'N/A' }}</td>
                        <td>{{ $booking->supplier?->name ?? '—' }}</td>
                        <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>{{ $booking->created_at?->format('M d, Y') }}</td>
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
                <div class="cl-empty-text">
                    Your bookings will appear here once professionals respond to your events.
                </div>
            </div>
        @endif
    </div>
@endsection
