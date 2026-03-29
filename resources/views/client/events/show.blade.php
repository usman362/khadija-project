@extends('layouts.client')

@section('title', $event->title)
@section('page-title', 'Event Details')

@section('content')
    <div style="margin-bottom: 24px;">
        <a href="{{ route('client.events.index') }}" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 13px; margin-bottom: 12px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to My Events
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $event->title }}</h2>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    @if($event->is_published)
                        <span class="cl-badge cl-badge-published">Published</span>
                    @endif
                    @if($event->categories->count())
                        @foreach($event->categories as $cat)
                            <span style="font-size: 13px; color: var(--text-muted);">{{ $cat->name }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                @if(!$event->is_published)
                    <form method="POST" action="{{ route('client.events.publish', $event) }}">
                        @csrf
                        <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Publish Event</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="cl-grid cl-grid-3">
        <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 20px;">
            {{-- Description --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Description</h3>
                <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.7;">
                    {{ $event->description ?: 'No description provided.' }}
                </p>
            </div>

            {{-- Bookings --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Bookings ({{ $event->bookings->count() }})</h3>
                @if($event->bookings->count())
                    <table class="cl-table">
                        <thead><tr><th>Professional</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @foreach($event->bookings as $booking)
                            <tr>
                                <td style="color: var(--text-primary); font-weight: 500;">{{ $booking->supplier?->name ?? '—' }}</td>
                                <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                                <td>{{ $booking->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--text-muted); font-size: 14px;">No bookings for this event yet.</p>
                @endif
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Event Details</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Start Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->starts_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">End Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->ends_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    </div>
                    @if($event->categories->count())
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Categories</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            @foreach($event->categories as $cat)
                                <span class="cl-badge" style="font-size: 12px;">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Created</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($event->supplier)
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Assigned Professional</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->supplier->name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
