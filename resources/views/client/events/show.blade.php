@extends('layouts.client')

@section('title', $event->title)
@section('page-title', 'Event Details')

@section('content')
    @if ($errors->any())
        <div class="cl-card" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;margin-bottom:18px;font-size:13.5px;">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- ── Header ───────────────────────────────────────────── --}}
    <div style="margin-bottom: 24px;">
        <a href="{{ route('client.events.index') }}" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 13px; margin-bottom: 12px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to My Events
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $event->title }}</h2>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    @if($event->is_published)
                        <span class="cl-badge cl-badge-published">Published</span>
                    @else
                        <span class="cl-badge" style="background:#fef3c7;color:#b45309;">Draft</span>
                    @endif
                    @foreach($event->categories as $cat)
                        <span style="font-size: 13px; color: var(--text-muted);">{{ $cat->name }}</span>
                    @endforeach
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" onclick="document.getElementById('editEventModal').classList.add('show')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
                @if(!$event->is_published)
                    <form method="POST" action="{{ route('client.events.publish', $event) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm" style="background:#f97316;border-color:#f97316;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                            Publish Event
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="cl-grid cl-grid-3">
        {{-- ── Main column ───────────────────────────────────── --}}
        <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 20px;">
            {{-- Description --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Description</h3>
                <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.7;">
                    {{ $event->description ?: 'No description provided yet. Click Edit to add details about your event.' }}
                </p>
            </div>

            {{-- Proposals & Bookings --}}
            @php
                $proposals = $event->bookings->where('status', 'requested');
                $active    = $event->bookings->whereIn('status', ['confirmed', 'completed']);
            @endphp
            <div class="cl-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="font-size: 16px; font-weight: 600;">Professionals &amp; Proposals ({{ $event->bookings->count() }})</h3>
                    <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Find Professionals
                    </a>
                </div>
                @if($event->bookings->count())
                    <table class="cl-table">
                        <thead><tr><th>Professional</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                        <tbody>
                            @foreach($event->bookings->sortByDesc('created_at') as $booking)
                            <tr>
                                <td style="color: var(--text-primary); font-weight: 500;">{{ $booking->supplier?->name ?? '—' }}</td>
                                <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                                <td style="color:var(--text-muted);">{{ $booking->created_at->format('M d, Y') }}</td>
                                <td style="text-align:right;">
                                    <a href="{{ route('client.bookings.index') }}" style="color:var(--accent-orange,#f97316);font-size:12.5px;font-weight:600;text-decoration:none;">View →</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align:center;padding:28px 16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#fff4ec;color:#f97316;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                        </div>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom:14px;">No professionals yet. {{ $event->is_published ? 'Find and invite pros to start receiving proposals.' : 'Publish your event so professionals can find it — or search and invite them directly.' }}</p>
                        <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-primary cl-btn-sm" style="background:#f97316;border-color:#f97316;">Find Professionals</a>
                    </div>
                @endif
            </div>

            {{-- Sealed bids received — the client (event owner) sees every amount;
                 other professionals can't. Sorted lowest-first. --}}
            <div class="cl-card" style="margin-top:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <h3 style="font-size:16px;font-weight:600;">🔒 Sealed Bids Received ({{ $bids->count() }})</h3>
                </div>
                <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:16px;">
                    Bid amounts are hidden from other professionals — only you can see them here.
                </p>
                @if($bids->count())
                    <table class="cl-table">
                        <thead><tr><th>Professional</th><th>Bid Amount</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                            @foreach($bids as $bid)
                            <tr>
                                <td style="color:var(--text-primary);font-weight:500;">
                                    {{ $bid->supplier?->name ?? '—' }}
                                    @if($loop->first)<span class="cl-badge" style="background:#ecfdf5;color:#065f46;margin-left:6px;">Lowest</span>@endif
                                </td>
                                <td style="color:var(--text-primary);font-weight:700;">${{ number_format($bid->amount) }}</td>
                                <td style="color:var(--text-muted);">{{ $bid->created_at->format('M d, Y') }}</td>
                                <td style="text-align:right;">
                                    <a href="{{ route('client.chat.index') }}" style="color:var(--accent-orange,#f97316);font-size:12.5px;font-weight:600;text-decoration:none;">Message →</a>
                                </td>
                            </tr>
                            @if($bid->note)
                            <tr><td colspan="4" style="color:var(--text-muted);font-size:12.5px;padding-top:0;">↳ {{ $bid->note }}</td></tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align:center;padding:24px 16px;color:var(--text-muted);font-size:13.5px;">
                        No sealed bids yet. {{ $event->is_published ? 'Professionals can bid on this event from their bidding board.' : 'Publish your event so professionals can place sealed bids.' }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Right rail ────────────────────────────────────── --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Event Details</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Start Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->starts_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">End Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->ends_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Location</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->location ?: '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Budget</div>
                        <div style="font-size: 14px; font-weight: 600; color:#16a34a;">{{ $event->budget ? '$'.number_format($event->budget, 2) : 'Not set' }}</div>
                    </div>
                    @if($event->categories->count())
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 5px;">Categories</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            @foreach($event->categories as $cat)
                                <span class="cl-badge" style="font-size: 12px;">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Created</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($event->supplier)
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Assigned Professional</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->supplier->name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 14px;">Quick Actions</h3>
                <div style="display:flex;flex-direction:column;gap:9px;">
                    <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Find Professionals
                    </a>
                    <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;" onclick="document.getElementById('editEventModal').classList.add('show')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Event
                    </button>
                    <a href="{{ route('ai-tools.budget-allocator') }}" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Plan Budget with AI
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════ EDIT EVENT MODAL ════════════ --}}
    <div class="cl-modal-overlay" id="editEventModal">
        <div class="cl-modal" style="max-width: 720px;">
            <form method="POST" action="{{ route('client.events.update', $event) }}">
                @csrf
                @method('PATCH')
                <div class="cl-modal-header">
                    <div>
                        <div class="cl-modal-title">Edit Event</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Update your event details below.</p>
                    </div>
                    <button type="button" class="cl-modal-close" onclick="document.getElementById('editEventModal').classList.remove('show')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="cl-modal-body">
                    <div class="cl-form-group">
                        <label class="cl-form-label">Event Title *</label>
                        <input type="text" name="title" class="cl-form-input" value="{{ old('title', $event->title) }}" required>
                    </div>
                    <div class="cl-form-group">
                        <label class="cl-form-label">Description</label>
                        <textarea name="description" class="cl-form-textarea" rows="4" placeholder="Describe your event, expectations, and requirements...">{{ old('description', $event->description) }}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Start Date &amp; Time</label>
                            <input type="datetime-local" name="starts_at" class="cl-form-input" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">End Date &amp; Time</label>
                            <input type="datetime-local" name="ends_at" class="cl-form-input" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="cl-form-group">
                        <label class="cl-form-label">Categories <span style="font-weight:400; color: var(--text-muted);">(select one or more)</span></label>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach ($categories as $cat)
                                <label style="display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border,#e2e8f0);border-radius:9px;padding:7px 12px;font-size:13px;cursor:pointer;">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" {{ in_array($cat->id, old('category_ids', $selectedCategoryIds)) ? 'checked' : '' }}>
                                    {{ $cat->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Location</label>
                            <input type="text" name="location" class="cl-form-input" value="{{ old('location', $event->location) }}" placeholder="City, Venue, or Address">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Budget <span style="opacity:.6;font-weight:400">(USD, optional)</span></label>
                            <input type="number" name="budget" class="cl-form-input" value="{{ old('budget', $event->budget) }}" placeholder="e.g. 2500" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="cl-modal-footer">
                    <button type="button" class="cl-btn cl-btn-ghost" onclick="document.getElementById('editEventModal').classList.remove('show')">Cancel</button>
                    <button type="submit" class="cl-btn cl-btn-primary" style="background:#f97316;border-color:#f97316;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', function(){ document.getElementById('editEventModal').classList.add('show'); });</script>
    @endif
@endsection
