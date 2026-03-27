@extends('layouts.professional')

@section('title', $event->title)
@section('page-title', 'Gig Details')

@section('content')
    <div style="margin-bottom: 24px;">
        <a href="{{ route('professional.gigs.index') }}" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 13px; margin-bottom: 12px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to My Gigs
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $event->title }}</h2>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    @if($event->category)
                        <span style="font-size: 13px; color: var(--text-muted);">{{ $event->category->name }}</span>
                    @endif
                </div>
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
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Booking Details ({{ $event->bookings->count() }})</h3>
                @if($event->bookings->count())
                    <table class="cl-table">
                        <thead><tr><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @foreach($event->bookings as $booking)
                            <tr>
                                <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                                <td>{{ $booking->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--text-muted); font-size: 14px;">No booking details available.</p>
                @endif
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Gig Details</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Start Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->starts_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">End Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->ends_at?->format('M d, Y h:i A') ?? '—' }}</div>
                    </div>
                    @if($event->category)
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Category</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->category->name }}</div>
                    </div>
                    @endif
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Created</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($event->client)
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Client</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->client->name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
