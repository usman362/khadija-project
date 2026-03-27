@extends('layouts.professional')

@section('title', 'Reviews')
@section('page-title', 'Reviews')

@section('content')
    {{-- Header --}}
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Reviews</h2>
        <p style="color: var(--text-muted); font-size: 14px;">Client feedback and ratings about your work.</p>
    </div>

    {{-- Stat Cards --}}
    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Reviews</div>
                    <div class="cl-stat-value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 10h4.764a2 2 0 0 1 1.789 2.894l-3.646 7.073A2 2 0 0 1 14.202 21H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.05a2 2 0 0 1 1.977 1.694l1.5 8.306a2 2 0 0 0 1.977 1.694H14z"/><path d="M9 5L7 9"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Positive</div>
                    <div class="cl-stat-value">{{ $stats['positive'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 14H5.764a2 2 0 0 0-1.789 2.894l3.646 7.073A2 2 0 0 0 9.798 21H18a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1.05a2 2 0 0 0-1.977 1.694l-1.5 8.306a2 2 0 0 1-1.977 1.694H10z"/><path d="M15 5l2-4"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Negative</div>
                    <div class="cl-stat-value">{{ $stats['negative'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card" style="flex-direction: row; align-items: center;">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 10.26 23.77 11.64 17.88 17.69 19.24 26.5 12 22.77 4.76 26.5 6.12 17.69 0.22 11.64 8.9 10.26 12 2"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Avg Rating</div>
                    <div class="cl-stat-value">{{ number_format($stats['avg_rating'] ?? 0, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    <div class="cl-card">
        <div class="cl-empty">
            <div class="cl-empty-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 10.26 23.77 11.64 17.88 17.69 19.24 26.5 12 22.77 4.76 26.5 6.12 17.69 0.22 11.64 8.9 10.26 12 2"/></svg>
            </div>
            <div class="cl-empty-title">No reviews yet</div>
            <div class="cl-empty-text">Reviews from your clients will appear here.</div>
        </div>
    </div>
@endsection
