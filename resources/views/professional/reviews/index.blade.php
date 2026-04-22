@extends('layouts.professional')

@section('title', 'Reviews')
@section('page-title', 'Reviews')

@push('styles')
<style>
    /* ── Stat cards row (4 across on desktop, stack on mobile) ── */
    .rev-stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    @media (max-width: 720px) { .rev-stats-row { grid-template-columns: 1fr 1fr; } }

    /* ── Histogram card ─────────────────────────────────────── */
    .rev-histo-card { padding: 20px; }
    .rev-histo-row {
        display: grid;
        grid-template-columns: 50px 1fr 50px;
        align-items: center;
        gap: 10px;
        padding: 4px 0;
        font-size: 13px;
    }
    .rev-histo-label { color: #f59e0b; letter-spacing: 1px; font-size: 12px; }
    .rev-histo-track {
        height: 10px;
        background: rgba(255,255,255,0.06);
        border-radius: 5px;
        overflow: hidden;
    }
    [data-theme="light"] .rev-histo-track { background: #f1f5f9; }
    .rev-histo-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 5px;
    }
    .rev-histo-count {
        text-align: right;
        color: var(--text-muted);
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    /* ── Review feed ────────────────────────────────────────── */
    .rev-item {
        padding: 16px 0;
        border-bottom: 1px solid var(--border-color);
        display: flex; gap: 14px;
    }
    .rev-item:last-child { border-bottom: none; }
    .rev-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        flex-shrink: 0; object-fit: cover;
        background: var(--border-color);
    }
    .rev-body { flex: 1; min-width: 0; }
    .rev-head {
        display: flex; align-items: center; gap: 8px;
        flex-wrap: wrap; margin-bottom: 4px;
    }
    .rev-name { font-weight: 600; color: var(--text-primary); }
    .rev-stars { color: #f59e0b; font-size: 13px; letter-spacing: 1px; }
    .rev-date { margin-left: auto; font-size: 12px; color: var(--text-muted); }
    .rev-context { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
    .rev-title { font-weight: 600; color: var(--text-primary); font-size: 14px; margin-bottom: 2px; }
    .rev-text { font-size: 13.5px; color: var(--text-secondary); line-height: 1.55; white-space: pre-line; }
    .rev-response {
        margin-top: 10px;
        padding: 10px 14px;
        background: rgba(99,102,241,0.06);
        border-left: 3px solid var(--accent-blue);
        border-radius: 4px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .rev-response strong { color: var(--text-primary); }
    .rev-respond-form { margin-top: 10px; }
    .rev-respond-form textarea {
        width: 100%;
        padding: 10px 12px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-primary);
        font-size: 13px; font-family: inherit;
        resize: vertical; min-height: 64px; outline: none;
    }
    .rev-respond-form textarea:focus { border-color: var(--accent-blue); }
    .rev-respond-actions {
        display: flex; gap: 8px; margin-top: 6px;
        align-items: center; justify-content: space-between;
    }
    .rev-respond-toggle {
        background: none; border: none;
        color: var(--accent-blue);
        font-size: 12px; font-weight: 600;
        cursor: pointer; padding: 4px 0;
    }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Reviews</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Client feedback and ratings about your work.</p>
    </div>

    @if(session('status'))
        <div class="cl-card" style="border-left: 3px solid #10b981; margin-bottom: 16px;">
            <div style="color: #10b981; font-size: 14px;">{{ session('status') }}</div>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="rev-stats-row">
        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Reviews</div>
                    <div class="cl-stat-value">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 10.26 23.77 11.64 17.88 17.69 19.24 26.5 12 22.77 4.76 26.5 6.12 17.69 0.22 11.64 8.9 10.26"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Avg Rating</div>
                    <div class="cl-stat-value">{{ number_format($stats['avg_rating'], 1) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 10h4.764a2 2 0 0 1 1.789 2.894l-3.646 7.073A2 2 0 0 1 14.202 21H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.05a2 2 0 0 1 1.977 1.694l1.5 8.306a2 2 0 0 0 1.977 1.694H14z"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Positive (4–5★)</div>
                    <div class="cl-stat-value">{{ number_format($stats['positive']) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 14H5.764a2 2 0 0 0-1.789 2.894l3.646 7.073A2 2 0 0 0 9.798 21H18a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1.05a2 2 0 0 0-1.977 1.694l-1.5 8.306a2 2 0 0 1-1.977 1.694H10z"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Negative (1–2★)</div>
                    <div class="cl-stat-value">{{ number_format($stats['negative']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Histogram --}}
    @if($stats['total'] > 0)
        <div class="cl-card rev-histo-card" style="margin-bottom: 20px;">
            <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 14px; color: var(--text-primary);">
                Rating Distribution
            </h3>
            @php $max = max(1, max($stats['histogram'])); @endphp
            @foreach([5, 4, 3, 2, 1] as $star)
                @php
                    $c = $stats['histogram'][$star] ?? 0;
                    $pct = ($c / $max) * 100;
                @endphp
                <div class="rev-histo-row">
                    <div class="rev-histo-label">{{ str_repeat('★', $star) }}</div>
                    <div class="rev-histo-track"><div class="rev-histo-fill" style="width: {{ $pct }}%;"></div></div>
                    <div class="rev-histo-count">{{ number_format($c) }}</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Reviews list --}}
    <div class="cl-card">
        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px; color: var(--text-primary);">
            Recent Reviews
        </h3>

        @forelse($reviews as $r)
            <div class="rev-item">
                <img src="{{ $r->reviewer?->avatar_url ?? 'https://ui-avatars.com/api/?name=?&size=88&background=475569&color=fff' }}"
                     alt="{{ $r->reviewer?->name ?? 'Former client' }}"
                     class="rev-avatar">
                <div class="rev-body">
                    <div class="rev-head">
                        <span class="rev-name">{{ $r->reviewer?->name ?? 'Former client' }}</span>
                        <span class="rev-stars">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                        <span class="rev-date">{{ $r->created_at->diffForHumans() }}</span>
                    </div>
                    @if($r->booking?->event)
                        <div class="rev-context">For: {{ $r->booking->event->title }}</div>
                    @endif
                    @if($r->title)
                        <div class="rev-title">{{ $r->title }}</div>
                    @endif
                    <div class="rev-text">{{ $r->comment }}</div>

                    @if($r->response)
                        <div class="rev-response">
                            <strong>Your response:</strong> {{ $r->response }}
                        </div>
                        <button type="button" class="rev-respond-toggle" data-target="resp-{{ $r->id }}">Edit response</button>
                    @else
                        <button type="button" class="rev-respond-toggle" data-target="resp-{{ $r->id }}">Respond publicly</button>
                    @endif

                    <form class="rev-respond-form" id="resp-{{ $r->id }}" method="POST"
                          action="{{ route('reviews.respond', $r) }}" style="display: none;">
                        @csrf @method('PATCH')
                        <textarea name="response" placeholder="Thanks for the feedback…" maxlength="1500">{{ $r->response }}</textarea>
                        <div class="rev-respond-actions">
                            <small style="color: var(--text-muted); font-size: 11px;">Your response is public.</small>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" data-cancel="resp-{{ $r->id }}">Cancel</button>
                                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="cl-empty" style="padding: 40px 12px;">
                <div class="cl-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 10.26 23.77 11.64 17.88 17.69 19.24 26.5 12 22.77 4.76 26.5 6.12 17.69 0.22 11.64 8.9 10.26"/></svg>
                </div>
                <div class="cl-empty-title">No reviews yet</div>
                <div class="cl-empty-text">Reviews from your clients will appear here once your completed bookings are rated.</div>
            </div>
        @endforelse

        @if($reviews->hasPages())
            <div class="cl-pagination" style="margin-top: 20px;">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
// Toggle response form visibility for each review.
document.querySelectorAll('.rev-respond-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = document.getElementById(btn.dataset.target);
        if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
});
document.querySelectorAll('[data-cancel]').forEach(btn => {
    btn.addEventListener('click', () => {
        const form = document.getElementById(btn.dataset.cancel);
        if (form) form.style.display = 'none';
    });
});
</script>
@endpush
