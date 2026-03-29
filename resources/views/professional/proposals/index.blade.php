@extends('layouts.professional')

@section('title', 'My Proposals')
@section('page-title', 'My Proposals')

@push('styles')
<style>
    .cl-booking-stat-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 20px 12px;
        border-radius: var(--radius);
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        color: inherit;
        min-width: 0;
    }
    .cl-booking-stat-card:hover { border-color: var(--border-glow); background: var(--bg-card-hover); }
    .cl-booking-stat-card.active { border-color: var(--accent-blue); background: var(--accent-blue-soft); }

    .cl-booking-stat-icon {
        width: 40px; height: 40px;
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 10px;
    }

    .cl-booking-stat-name { font-size: 13px; font-weight: 600; color: var(--text-secondary); }
    .cl-booking-stat-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .cl-booking-stat-count { font-size: 22px; font-weight: 800; color: var(--text-primary); margin-top: 4px; }

    /* Booking card */
    .cl-booking-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        border-radius: var(--radius);
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .cl-booking-card:hover { border-color: var(--border-glow); background: rgba(255,255,255,0.04); }

    .cl-booking-avatar {
        width: 44px; height: 44px;
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 16px; color: #fff;
        flex-shrink: 0;
    }

    .cl-booking-info { flex: 1; min-width: 0; }
    .cl-booking-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .cl-booking-meta { display: flex; gap: 16px; font-size: 13px; color: var(--text-muted); flex-wrap: wrap; }
    .cl-booking-meta span { display: flex; align-items: center; gap: 4px; }

    .cl-booking-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }

    /* Status tabs */
    .cl-status-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 20px;
        overflow-x: auto;
    }
    .cl-status-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 12px 20px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: var(--transition);
        white-space: nowrap;
    }
    .cl-status-tab:hover { color: var(--text-secondary); }
    .cl-status-tab.active { color: var(--accent-blue); border-bottom-color: var(--accent-blue); }
    .cl-status-tab .tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        background: rgba(255,255,255,0.06);
        color: var(--text-muted);
    }
    .cl-status-tab.active .tab-count { background: var(--accent-blue-soft); color: var(--accent-blue); }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">My Proposals</h2>
            <p style="color: var(--text-muted); font-size: 14px;">Manage your submitted and received proposals</p>
        </div>
        <a href="{{ route('professional.gigs.index', ['view' => 'browse']) }}" class="cl-btn cl-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
            Browse Event Jobs
        </a>
    </div>

    {{-- Stat Cards Row --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px;">
        <a href="{{ route('professional.proposals.index', ['tab' => 'all']) }}" class="cl-booking-stat-card {{ $tab === 'all' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="cl-booking-stat-name">Submitted</div>
            <div class="cl-booking-stat-count">{{ $stats['all'] ?? 0 }}</div>
        </a>

        <a href="{{ route('professional.proposals.index', ['tab' => 'pending']) }}" class="cl-booking-stat-card {{ $tab === 'pending' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-yellow-soft); color: var(--accent-yellow);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="cl-booking-stat-name">Pending</div>
            <div class="cl-booking-stat-count">{{ $stats['pending'] ?? 0 }}</div>
        </a>

        <a href="{{ route('professional.proposals.index', ['tab' => 'accepted']) }}" class="cl-booking-stat-card {{ $tab === 'accepted' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-green-soft); color: var(--accent-green);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="cl-booking-stat-name">Accepted</div>
            <div class="cl-booking-stat-count">{{ $stats['accepted'] ?? 0 }}</div>
        </a>

        <a href="{{ route('professional.proposals.index', ['tab' => 'in_progress']) }}" class="cl-booking-stat-card {{ $tab === 'in_progress' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-orange-soft); color: var(--accent-orange);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div class="cl-booking-stat-name">In Progress</div>
            <div class="cl-booking-stat-count">{{ $stats['in_progress'] ?? 0 }}</div>
        </a>

        <a href="{{ route('professional.proposals.index', ['tab' => 'completed']) }}" class="cl-booking-stat-card {{ $tab === 'completed' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-green-soft); color: var(--accent-green);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="cl-booking-stat-name">Completed</div>
            <div class="cl-booking-stat-count">{{ $stats['completed'] ?? 0 }}</div>
        </a>

        <a href="{{ route('professional.proposals.index', ['tab' => 'cancelled']) }}" class="cl-booking-stat-card {{ $tab === 'cancelled' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-red-soft); color: var(--accent-red);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="cl-booking-stat-name">Declined</div>
            <div class="cl-booking-stat-count">{{ $stats['cancelled'] ?? 0 }}</div>
        </a>
    </div>

    {{-- Status Tabs --}}
    <div class="cl-status-tabs">
        @php
            $tabs = [
                'all' => ['label' => 'All Proposals', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>', 'count' => $stats['all']],
                'pending' => ['label' => 'Pending', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', 'count' => $stats['pending']],
                'accepted' => ['label' => 'Accepted', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>', 'count' => $stats['accepted']],
                'in_progress' => ['label' => 'In Progress', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>', 'count' => $stats['in_progress']],
                'completed' => ['label' => 'Completed', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>', 'count' => $stats['completed']],
                'cancelled' => ['label' => 'Declined', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>', 'count' => $stats['cancelled']],
            ];
        @endphp

        @foreach ($tabs as $key => $t)
            <a href="{{ route('professional.proposals.index', ['tab' => $key]) }}" class="cl-status-tab {{ $tab === $key ? 'active' : '' }}">
                {!! $t['icon'] !!}
                {{ $t['label'] }}
                <span class="tab-count">{{ $t['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('professional.proposals.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div style="flex: 1;">
                <div class="cl-search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search proposals..." value="{{ request('search') }}">
                </div>
            </div>
            <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Search</button>
        </form>
    </div>

    {{-- Proposals List --}}
    @if($bookings->count())
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($bookings as $booking)
                @php
                    $colors = [
                        'requested' => ['bg' => 'var(--accent-yellow)', 'soft' => 'var(--accent-yellow-soft)'],
                        'confirmed' => ['bg' => 'var(--accent-green)', 'soft' => 'var(--accent-green-soft)'],
                        'completed' => ['bg' => 'var(--accent-blue)', 'soft' => 'var(--accent-blue-soft)'],
                        'cancelled' => ['bg' => 'var(--accent-red)', 'soft' => 'var(--accent-red-soft)'],
                    ];
                    $c = $colors[$booking->status] ?? $colors['requested'];
                    $counterparty = $booking->client?->name ?? 'Client';
                    $initial = strtoupper(substr($counterparty, 0, 1));
                @endphp
                <div class="cl-booking-card">
                    <div class="cl-booking-avatar" style="background: {{ $c['bg'] }};">{{ $initial }}</div>
                    <div class="cl-booking-info">
                        <div class="cl-booking-title">{{ $booking->event?->title ?? 'N/A' }}</div>
                        <div class="cl-booking-meta">
                            <span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                {{ $counterparty }}
                            </span>
                            @if($booking->event?->categories?->count())
                                @foreach($booking->event->categories as $cat)
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
                                        {{ $cat->name }}
                                    </span>
                                @endforeach
                            @endif
                            @if($booking->event?->starts_at)
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
                                    {{ $booking->event->starts_at->format('M d, Y') }}
                                </span>
                            @endif
                            <span>{{ $booking->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="cl-booking-actions">
                        <span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span>

                        @if($booking->status === 'requested')
                            <form method="POST" action="{{ route('professional.proposals.update-status', $booking) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Accept</button>
                            </form>
                            <form method="POST" action="{{ route('professional.proposals.update-status', $booking) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm" style="color: var(--accent-red);">Decline</button>
                            </form>
                        @endif

                        @if($booking->event)
                            <a href="{{ route('professional.gigs.show', $booking->event) }}" class="cl-btn cl-btn-ghost cl-btn-sm">View Gig</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($bookings->hasPages())
            <div class="cl-pagination">
                @if($bookings->onFirstPage())
                    <span class="disabled"><span>&laquo;</span></span>
                @else
                    <a href="{{ $bookings->previousPageUrl() }}">&laquo;</a>
                @endif

                @foreach($bookings->getUrlRange(1, $bookings->lastPage()) as $page => $url)
                    @if($page == $bookings->currentPage())
                        <span class="active"><span>{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($bookings->hasMorePages())
                    <a href="{{ $bookings->nextPageUrl() }}">&raquo;</a>
                @else
                    <span class="disabled"><span>&raquo;</span></span>
                @endif
            </div>
        @endif
    @else
        <div class="cl-card">
            <div class="cl-empty">
                <div class="cl-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                </div>
                @if($tab === 'all')
                    <div class="cl-empty-title">No proposals yet</div>
                    <div class="cl-empty-text">Your proposals will appear here once you submit or receive them.</div>
                @elseif($tab === 'pending')
                    <div class="cl-empty-title">No Pending Proposals</div>
                    <div class="cl-empty-text">No proposals awaiting review right now.</div>
                @else
                    <div class="cl-empty-title">No {{ $tabs[$tab]['label'] ?? ucfirst($tab) }} Proposals</div>
                    <div class="cl-empty-text">No proposals with this status found.</div>
                @endif
                <a href="{{ route('professional.gigs.index', ['view' => 'browse']) }}" class="cl-btn cl-btn-primary cl-btn-sm" style="margin-top: 8px;">Browse Event Jobs</a>
            </div>
        </div>
    @endif
@endsection
