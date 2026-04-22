@extends('layouts.client')

@section('title', 'My Bookings')
@section('page-title', 'My Bookings')

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

    /* ── Review modal ─────────────────────────────────────────
       Scoped `.rv-*` classes so it doesn't collide with other
       modals on the page (like the ones in the chat). */
    .rv-modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        align-items: center; justify-content: center;
        padding: 16px;
    }
    .rv-modal-overlay.show { display: flex; }
    .rv-modal {
        background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 14px;
        width: 520px; max-width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }
    .rv-modal-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 22px;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
    }
    .rv-modal-title { font-size: 18px; font-weight: 700; margin: 0; color: var(--text-primary); }
    .rv-modal-close {
        background: none; border: none;
        font-size: 24px; line-height: 1;
        color: var(--text-muted);
        cursor: pointer;
        width: 30px; height: 30px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
    }
    .rv-modal-close:hover { background: rgba(0,0,0,0.08); }
    .rv-modal-body { padding: 18px 22px; }
    .rv-modal-foot {
        display: flex; gap: 10px; justify-content: flex-end;
        padding: 14px 22px;
        border-top: 1px solid var(--border-color, #e2e8f0);
    }
    .rv-target {
        font-size: 13px; color: var(--text-muted);
        margin-bottom: 14px;
        padding: 10px 12px;
        background: var(--bg-primary, #f8fafc);
        border-radius: 8px;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    .rv-target strong { color: var(--text-primary); }
    .rv-label {
        display: block;
        font-size: 13px; font-weight: 600;
        color: var(--text-secondary);
        margin: 12px 0 6px;
    }
    .rv-label-hint { color: var(--text-muted); font-weight: 400; }
    .rv-input, .rv-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm, 8px);
        color: var(--text-primary);
        font-size: 14px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.15s;
    }
    .rv-input:focus, .rv-textarea:focus { border-color: var(--accent-blue); }
    .rv-textarea { resize: vertical; min-height: 110px; }
    .rv-stars { display: flex; gap: 4px; }
    .rv-star {
        background: none; border: none; cursor: pointer;
        font-size: 32px; line-height: 1;
        color: #e2e8f0;
        padding: 0 2px;
        transition: color 0.1s, transform 0.1s;
    }
    .rv-star:hover { transform: scale(1.15); }
    .rv-star.active { color: #f59e0b; }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">My Bookings</h2>
            <p style="color: var(--text-muted); font-size: 14px;">Manage your confirmed bookings and job requests.</p>
        </div>
        <a href="{{ route('client.events.index') }}?create=1" class="cl-btn cl-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Post New Event
        </a>
    </div>

    {{-- Stat Cards Row --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px;">
        <a href="{{ route('client.bookings.index', ['tab' => 'all']) }}" class="cl-booking-stat-card {{ $tab === 'all' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="cl-booking-stat-name">All Bookings</div>
            <div class="cl-booking-stat-sub">Total</div>
            <div class="cl-booking-stat-count">{{ $stats['all'] }}</div>
        </a>

        <a href="{{ route('client.bookings.index', ['tab' => 'upcoming']) }}" class="cl-booking-stat-card {{ $tab === 'upcoming' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-green-soft); color: var(--accent-green);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
            </div>
            <div class="cl-booking-stat-name">Upcoming</div>
            <div class="cl-booking-stat-sub">Active</div>
            <div class="cl-booking-stat-count">{{ $stats['upcoming'] }}</div>
        </a>

        <a href="{{ route('client.bookings.index', ['tab' => 'in_progress']) }}" class="cl-booking-stat-card {{ $tab === 'in_progress' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-orange-soft); color: var(--accent-orange);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div class="cl-booking-stat-name">In Progress</div>
            <div class="cl-booking-stat-sub">Active</div>
            <div class="cl-booking-stat-count">{{ $stats['in_progress'] }}</div>
        </a>

        <a href="{{ route('client.bookings.index', ['tab' => 'pending']) }}" class="cl-booking-stat-card {{ $tab === 'pending' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-pink-soft); color: var(--accent-pink);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
            </div>
            <div class="cl-booking-stat-name">Pending Requests</div>
            <div class="cl-booking-stat-sub">Review</div>
            <div class="cl-booking-stat-count">{{ $stats['pending'] }}</div>
        </a>

        <a href="{{ route('client.bookings.index', ['tab' => 'completed']) }}" class="cl-booking-stat-card {{ $tab === 'completed' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-green-soft); color: var(--accent-green);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="cl-booking-stat-name">Completed</div>
            <div class="cl-booking-stat-sub">Done</div>
            <div class="cl-booking-stat-count">{{ $stats['completed'] }}</div>
        </a>

        <a href="{{ route('client.bookings.index', ['tab' => 'cancelled']) }}" class="cl-booking-stat-card {{ $tab === 'cancelled' ? 'active' : '' }}">
            <div class="cl-booking-stat-icon" style="background: var(--accent-red-soft); color: var(--accent-red);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="cl-booking-stat-name">Cancelled</div>
            <div class="cl-booking-stat-sub">Lost</div>
            <div class="cl-booking-stat-count">{{ $stats['cancelled'] }}</div>
        </a>
    </div>

    {{-- Status Tabs --}}
    <div class="cl-status-tabs">
        @php
            $tabs = [
                'all' => ['label' => 'All', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>', 'count' => $stats['all']],
                'upcoming' => ['label' => 'Upcoming', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>', 'count' => $stats['upcoming']],
                'in_progress' => ['label' => 'In Progress', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>', 'count' => $stats['in_progress']],
                'pending' => ['label' => 'Job Requests', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>', 'count' => $stats['pending']],
                'completed' => ['label' => 'Completed', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>', 'count' => $stats['completed']],
                'cancelled' => ['label' => 'Cancelled', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>', 'count' => $stats['cancelled']],
            ];
        @endphp

        @foreach ($tabs as $key => $t)
            <a href="{{ route('client.bookings.index', ['tab' => $key]) }}" class="cl-status-tab {{ $tab === $key ? 'active' : '' }}">
                {!! $t['icon'] !!}
                {{ $t['label'] }}
                <span class="tab-count">{{ $t['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('client.bookings.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div style="flex: 1;">
                <div class="cl-search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search by event, professional, or service..." value="{{ request('search') }}">
                </div>
            </div>
            <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Search</button>
        </form>
    </div>

    {{-- Bookings List --}}
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
                    $counterparty = $booking->supplier?->name ?? 'Professional';
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
                            @if($booking->event?->categories->count())
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
                                    {{ $booking->event->categories->pluck('name')->join(', ') }}
                                </span>
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
                            <form method="POST" action="{{ route('client.bookings.update-status', $booking) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Confirm</button>
                            </form>
                            <form method="POST" action="{{ route('client.bookings.update-status', $booking) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm" style="color: var(--accent-red);">Cancel</button>
                            </form>
                        @elseif($booking->status === 'completed')
                            @if(in_array($booking->id, $reviewedBookingIds))
                                <span class="cl-badge" style="background: rgba(16,185,129,0.12); color: #10b981;">✓ Reviewed</span>
                            @else
                                <button type="button"
                                        class="cl-btn cl-btn-primary cl-btn-sm js-open-review"
                                        data-booking-id="{{ $booking->id }}"
                                        data-supplier="{{ $booking->supplier?->name ?? 'Professional' }}"
                                        data-event="{{ $booking->event?->title ?? 'this booking' }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    Leave Review
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Review Modal ────────────────────────────────────────
             Shared modal for all "Leave Review" buttons on this page.
             The trigger's data-* attributes populate the form each time
             it opens, so we need one DOM node instead of one per card. --}}
        <div id="reviewModal" class="rv-modal-overlay" aria-hidden="true">
            <div class="rv-modal" role="dialog" aria-modal="true">
                <form method="POST" action="{{ route('reviews.store') }}" id="reviewForm">
                    @csrf
                    <input type="hidden" name="booking_id" id="rv-booking-id">
                    <div class="rv-modal-head">
                        <h3 class="rv-modal-title">Leave a Review</h3>
                        <button type="button" class="rv-modal-close" aria-label="Close" onclick="closeReviewModal()">×</button>
                    </div>
                    <div class="rv-modal-body">
                        <div class="rv-target" id="rv-target"></div>

                        <label class="rv-label">Your Rating</label>
                        <div class="rv-stars" id="rv-stars">
                            @for($s = 1; $s <= 5; $s++)
                                <button type="button" class="rv-star" data-star="{{ $s }}" aria-label="{{ $s }} stars">★</button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="rv-rating" value="5">

                        <label class="rv-label" for="rv-title">Title <span class="rv-label-hint">(optional)</span></label>
                        <input type="text" class="rv-input" id="rv-title" name="title" maxlength="150"
                               placeholder="Summarise the job in one line">

                        <label class="rv-label" for="rv-comment">Your Review</label>
                        <textarea class="rv-textarea" id="rv-comment" name="comment" rows="5" required
                                  minlength="10" maxlength="2000"
                                  placeholder="What went well? Would you hire this pro again?"></textarea>
                    </div>
                    <div class="rv-modal-foot">
                        <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" onclick="closeReviewModal()">Cancel</button>
                        <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Submit Review</button>
                    </div>
                </form>
            </div>
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
                    <div class="cl-empty-title">No Bookings Yet</div>
                    <div class="cl-empty-text">Your bookings will appear here once you accept job requests.</div>
                @elseif($tab === 'pending')
                    <div class="cl-empty-title">No Pending Requests</div>
                    <div class="cl-empty-text">When professionals submit proposals, they'll appear here for review.</div>
                @else
                    <div class="cl-empty-title">No {{ ucfirst(str_replace('_', ' ', $tab)) }} Bookings</div>
                    <div class="cl-empty-text">No bookings with this status found.</div>
                @endif
                <a href="{{ route('client.bookings.index', ['tab' => 'pending']) }}" class="cl-btn cl-btn-primary cl-btn-sm" style="margin-top: 8px;">Review Job Requests</a>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
/**
 * Review modal controller. One modal DOM node serves all "Leave Review"
 * triggers on the page — we repopulate its hidden booking_id, subject line,
 * and star state each time it opens. Close paths: X button, Cancel button,
 * Escape key, backdrop click.
 */
(function () {
    const modal    = document.getElementById('reviewModal');
    const form     = document.getElementById('reviewForm');
    const bookingInput = document.getElementById('rv-booking-id');
    const ratingInput  = document.getElementById('rv-rating');
    const target   = document.getElementById('rv-target');
    const stars    = document.querySelectorAll('#rv-stars .rv-star');
    if (!modal) return;

    // Paint star highlights for a given rating (1-5).
    function paintStars(n) {
        stars.forEach((s, i) => s.classList.toggle('active', i < n));
    }

    // Attach hover + click handlers once.
    stars.forEach((btn, i) => {
        btn.addEventListener('mouseenter', () => paintStars(i + 1));
        btn.addEventListener('click',      () => { ratingInput.value = i + 1; paintStars(i + 1); });
    });
    document.getElementById('rv-stars').addEventListener('mouseleave', () => {
        paintStars(parseInt(ratingInput.value, 10));
    });

    // Triggers on booking cards.
    document.querySelectorAll('.js-open-review').forEach(btn => {
        btn.addEventListener('click', () => {
            bookingInput.value = btn.dataset.bookingId;
            target.innerHTML = 'Reviewing <strong>' + btn.dataset.supplier + '</strong> for <strong>' + btn.dataset.event + '</strong>.';
            ratingInput.value = 5;
            paintStars(5);
            form.querySelector('[name=title]').value = '';
            form.querySelector('[name=comment]').value = '';
            modal.classList.add('show');
        });
    });

    // Close paths.
    window.closeReviewModal = function () { modal.classList.remove('show'); };
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('show'); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) modal.classList.remove('show');
    });
})();
</script>
@endpush
