@extends('layouts.professional')

@section('title', $event->title)
@section('page-title', 'Gig Details')

@push('styles')
<style>
    .gig-proposal-modal-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
        align-items: center; justify-content: center;
        padding: 24px;
    }
    .gig-proposal-modal-overlay.active { display: flex; }

    .gig-proposal-modal {
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 16px; max-width: 520px; width: 100%;
        padding: 32px; position: relative;
        animation: gig-modal-in 0.2s ease-out;
    }
    @keyframes gig-modal-in {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .gig-modal-close {
        position: absolute; top: 16px; right: 16px;
        width: 32px; height: 32px; border-radius: 8px;
        border: 1px solid var(--border-color); background: transparent;
        color: var(--text-muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .gig-modal-close:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }

    .gig-modal-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .gig-modal-subtitle { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }

    .gig-modal-textarea {
        width: 100%; min-height: 120px; padding: 14px;
        border-radius: 10px; border: 1.5px solid var(--border-color);
        background: var(--bg-secondary); color: var(--text-primary);
        font-size: 14px; font-family: inherit; resize: vertical;
        transition: border-color 0.2s;
    }
    .gig-modal-textarea::placeholder { color: var(--text-muted); }
    .gig-modal-textarea:focus {
        outline: none; border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(96,165,250,0.15);
    }

    .gig-modal-label {
        display: block; font-size: 13px; font-weight: 600;
        color: var(--text-secondary); margin-bottom: 8px;
    }
    .gig-modal-hint {
        font-size: 12px; color: var(--text-muted); margin-top: 6px;
    }
    .gig-modal-actions {
        display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;
    }

    /* Status indicator for already applied */
    .gig-proposal-sent {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 10px;
        background: var(--accent-green-soft); color: var(--accent-green);
        font-size: 14px; font-weight: 600;
    }

    .gig-detail-row {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 0; border-bottom: 1px solid var(--border-color);
    }
    .gig-detail-row:last-child { border-bottom: none; }
    .gig-detail-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .gig-detail-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
    .gig-detail-value { font-size: 14px; font-weight: 600; color: var(--text-primary); }
</style>
@endpush

@section('content')
    @php
        $hasProposal = $event->bookings
            ->where('supplier_id', auth()->id())
            ->whereIn('status', ['requested', 'confirmed'])
            ->count() > 0;

        $myProposal = $event->bookings
            ->where('supplier_id', auth()->id())
            ->first();

        $isOwnGig = $event->supplier_id === auth()->id();
        $isBrowsable = $event->status === 'published' && !$isOwnGig;
    @endphp

    {{-- Alerts --}}
    @if(session('status'))
        <div class="cl-card" style="margin-bottom: 16px; background: var(--accent-green-soft); border-color: var(--accent-green);">
            <div style="display: flex; align-items: center; gap: 8px; color: var(--accent-green); font-size: 14px; font-weight: 500;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('status') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="cl-card" style="margin-bottom: 16px; background: var(--accent-red-soft); border-color: var(--accent-red);">
            <div style="display: flex; align-items: center; gap: 8px; color: var(--accent-red); font-size: 14px; font-weight: 500;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div style="margin-bottom: 24px;">
        <a href="{{ route('professional.gigs.index', ['view' => $isOwnGig ? 'my-gigs' : 'browse']) }}" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 13px; margin-bottom: 12px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            {{ $isOwnGig ? 'Back to My Gigs' : 'Back to Browse Jobs' }}
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $event->title }}</h2>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    @if($event->category)
                        <span style="font-size: 13px; color: var(--text-muted);">{{ $event->category->name }}</span>
                    @endif
                    @if($event->budget)
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;background:var(--accent-green-soft);color:var(--accent-green);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            ${{ number_format($event->budget, 0) }} Budget
                        </span>
                    @endif
                </div>
            </div>

            {{-- Send Proposal / Already Sent --}}
            <div>
                @if($isBrowsable && !$hasProposal)
                    <button class="cl-btn cl-btn-primary" onclick="document.getElementById('proposalModal').classList.add('active')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Send Proposal
                    </button>
                @elseif($hasProposal)
                    <div class="gig-proposal-sent">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Proposal Sent — {{ ucfirst($myProposal->status ?? 'Pending') }}
                    </div>
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

            {{-- Bookings (only for own gigs) --}}
            @if($isOwnGig && $event->bookings->count())
                <div class="cl-card">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Booking Details ({{ $event->bookings->count() }})</h3>
                    <table class="cl-table">
                        <thead><tr><th>Client</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @foreach($event->bookings as $booking)
                            <tr>
                                <td>{{ $booking->client?->name ?? '—' }}</td>
                                <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                                <td>{{ $booking->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Event Details</h3>
                <div>
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Start Date</div>
                            <div class="gig-detail-value">{{ $event->starts_at?->format('M d, Y h:i A') ?? 'Not set' }}</div>
                        </div>
                    </div>
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-yellow-soft); color: var(--accent-yellow);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">End Date</div>
                            <div class="gig-detail-value">{{ $event->ends_at?->format('M d, Y h:i A') ?? 'Not set' }}</div>
                        </div>
                    </div>
                    @if($event->location)
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-pink-soft); color: var(--accent-pink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Location</div>
                            <div class="gig-detail-value">{{ $event->location }}</div>
                        </div>
                    </div>
                    @endif
                    @if($event->budget)
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-green-soft); color: var(--accent-green);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Budget</div>
                            <div class="gig-detail-value">${{ number_format($event->budget, 2) }}</div>
                        </div>
                    </div>
                    @endif
                    @if($event->category)
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Category</div>
                            <div class="gig-detail-value">{{ $event->category->name }}</div>
                        </div>
                    </div>
                    @endif
                    @if($event->client)
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Posted by</div>
                            <div class="gig-detail-value">{{ $event->client->name }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="gig-detail-row">
                        <div class="gig-detail-icon" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div class="gig-detail-label">Posted</div>
                            <div class="gig-detail-value">{{ $event->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Send Proposal (sidebar CTA for browse) --}}
            @if($isBrowsable && !$hasProposal)
                <div class="cl-card" style="text-align: center; padding: 24px;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent-blue)" stroke-width="1.5" style="margin-bottom: 12px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    <div style="font-size: 15px; font-weight: 600; margin-bottom: 4px;">Interested in this gig?</div>
                    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Send a proposal to let the client know you're available.</div>
                    <button class="cl-btn cl-btn-primary" style="width: 100%;" onclick="document.getElementById('proposalModal').classList.add('active')">
                        Send Proposal
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ SEND PROPOSAL MODAL ═══ --}}
    @if($isBrowsable && !$hasProposal)
    <div class="gig-proposal-modal-overlay" id="proposalModal">
        <div class="gig-proposal-modal">
            <button class="gig-modal-close" onclick="document.getElementById('proposalModal').classList.remove('active')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            <div class="gig-modal-title">Send Proposal</div>
            <div class="gig-modal-subtitle">Apply for "{{ $event->title }}"</div>

            <form method="POST" action="{{ route('professional.proposals.send', $event) }}">
                @csrf

                <div style="margin-bottom: 20px;">
                    <label class="gig-modal-label">Message to Client (Optional)</label>
                    <textarea name="notes" class="gig-modal-textarea" placeholder="Introduce yourself, describe your experience, and explain why you're a great fit for this event..."></textarea>
                    <div class="gig-modal-hint">The client will see this message along with your profile details.</div>
                </div>

                {{-- Event summary --}}
                <div style="padding: 14px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); margin-bottom: 8px;">
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 6px;">Applying for:</div>
                    <div style="font-size: 14px; font-weight: 600;">{{ $event->title }}</div>
                    <div style="display: flex; gap: 16px; margin-top: 6px; font-size: 12px; color: var(--text-muted);">
                        @if($event->starts_at)
                            <span>{{ $event->starts_at->format('M d, Y') }}</span>
                        @endif
                        @if($event->budget)
                            <span style="color: var(--accent-green);">${{ number_format($event->budget, 0) }} Budget</span>
                        @endif
                        @if($event->client)
                            <span>by {{ $event->client->name }}</span>
                        @endif
                    </div>
                </div>

                <div class="gig-modal-actions">
                    <button type="button" class="cl-btn cl-btn-ghost" onclick="document.getElementById('proposalModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="cl-btn cl-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Submit Proposal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    // Close modal on outside click
    document.getElementById('proposalModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('proposalModal')?.classList.remove('active');
    });
</script>
@endpush
