@extends('layouts.client')

@section('title', 'My Events')
@section('page-title', 'My Events')

@push('styles')
<style>
    .cl-calendar-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cl-calendar-nav button {
        width: 36px; height: 36px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: var(--transition);
    }
    .cl-calendar-nav button:hover { background: rgba(255,255,255,0.05); }
    .cl-calendar-month {
        font-size: 20px;
        font-weight: 700;
        min-width: 200px;
        text-align: center;
    }
    .cl-calendar-nav .today-btn {
        width: auto;
        padding: 0 16px;
        background: var(--accent-blue);
        color: #fff;
        border-color: var(--accent-blue);
        font-size: 13px;
        font-weight: 600;
    }
    .cl-calendar-nav .today-btn:hover { opacity: 0.9; }

    /* Event card in details view */
    .cl-event-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px;
        border-radius: var(--radius);
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .cl-event-card:hover { border-color: var(--border-glow); background: rgba(255,255,255,0.04); }

    .cl-event-date-badge {
        width: 52px; flex-shrink: 0;
        text-align: center;
        padding: 8px 0;
        border-radius: var(--radius-sm);
        background: var(--accent-blue-soft);
    }
    .cl-event-date-badge .month { font-size: 10px; text-transform: uppercase; font-weight: 600; color: var(--accent-blue); letter-spacing: 0.5px; }
    .cl-event-date-badge .day { font-size: 22px; font-weight: 800; color: var(--accent-blue); line-height: 1.2; }

    .cl-event-info { flex: 1; min-width: 0; }
    .cl-event-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .cl-event-meta { font-size: 13px; color: var(--text-muted); display: flex; gap: 16px; flex-wrap: wrap; }
    .cl-event-meta span { display: flex; align-items: center; gap: 4px; }

    .cl-event-actions { display: flex; gap: 8px; flex-shrink: 0; }

    /* Two column for view + preview */
    .cl-two-col { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
    @media (max-width: 1024px) { .cl-two-col { grid-template-columns: 1fr; } }

    /* Live Preview */
    .cl-preview-card {
        position: sticky;
        top: calc(var(--navbar-height) + 20px);
    }
    .cl-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: var(--accent-green-soft);
        color: var(--accent-green);
        margin-bottom: 12px;
    }
    .cl-preview-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
    .cl-preview-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
    .cl-preview-meta { display: flex; flex-direction: column; gap: 8px; }
    .cl-preview-meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }

    .cl-tab-content { display: none; }
    .cl-tab-content.active { display: block; }

    /* ── Multi-Select Category Dropdown ── */
    .cl-multiselect-wrap {
        position: relative;
    }
    .cl-multiselect-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: rgba(255,255,255,0.03);
        color: var(--text-primary);
        cursor: pointer;
        font-size: 14px;
        min-height: 44px;
        flex-wrap: wrap;
        gap: 6px;
        transition: var(--transition);
    }
    [data-theme="light"] .cl-multiselect-toggle {
        background: rgba(0,0,0,0.02);
    }
    .cl-multiselect-toggle:hover {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-placeholder {
        color: var(--text-muted);
    }
    .cl-multiselect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        flex: 1;
    }
    .cl-multiselect-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        background: var(--accent-blue-soft);
        color: var(--accent-blue);
        font-size: 12px;
        font-weight: 500;
    }
    .cl-multiselect-tag .tag-remove {
        cursor: pointer;
        opacity: 0.7;
        display: flex;
    }
    .cl-multiselect-tag .tag-remove:hover { opacity: 1; }
    .cl-multiselect-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 100;
        max-height: 280px;
        overflow: hidden;
        display: none;
        flex-direction: column;
    }
    .cl-multiselect-wrap.open .cl-multiselect-dropdown {
        display: flex;
    }
    .cl-multiselect-search {
        padding: 8px;
        border-bottom: 1px solid var(--border-color);
    }
    .cl-multiselect-search input {
        width: 100%;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }
    .cl-multiselect-search input:focus {
        border-color: var(--accent-blue);
    }
    .cl-multiselect-options {
        overflow-y: auto;
        max-height: 220px;
        padding: 4px;
    }
    .cl-multiselect-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 14px;
        color: var(--text-primary);
        transition: background 0.15s;
    }
    .cl-multiselect-option:hover {
        background: rgba(99,102,241,0.08);
    }
    .cl-multiselect-option input[type="checkbox"] {
        display: none;
    }
    .cl-multiselect-check {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.15s;
    }
    .cl-multiselect-check svg {
        display: none;
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check {
        background: var(--accent-blue);
        border-color: var(--accent-blue);
    }
    .cl-multiselect-option input:checked + .cl-multiselect-check svg {
        display: block;
        stroke: #fff;
    }
    .cl-multiselect-option.hidden {
        display: none;
    }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">My Events</h2>
            <p style="color: var(--text-muted); font-size: 14px;">Manage your events and invite professionals.</p>
        </div>
        <button class="cl-btn cl-btn-primary" onclick="document.getElementById('postEventModal').classList.add('show')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Post New Event
        </button>
    </div>

    {{-- Tabs --}}
    <div class="cl-tabs" id="viewTabs">
        <button class="cl-tab active" data-tab="calendar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-2px; margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendar View
        </button>
        <button class="cl-tab" data-tab="details">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-2px; margin-right:4px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Details View
        </button>
    </div>

    {{-- ════════════ CALENDAR VIEW ════════════ --}}
    <div class="cl-tab-content active" id="tab-calendar">
        <div class="cl-card">
            @php
                $currentDate = \Carbon\Carbon::create($year, $month, 1);
                $daysInMonth = $currentDate->daysInMonth;
                $firstDayOfWeek = $currentDate->dayOfWeek; // 0=Sun
                $today = now();
                $prevMonth = $currentDate->copy()->subMonth();
                $nextMonth = $currentDate->copy()->addMonth();

                // Index events by day
                $eventsByDay = [];
                foreach ($calendarEvents as $ce) {
                    $day = $ce->starts_at->day;
                    $eventsByDay[$day][] = $ce;
                }
            @endphp

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div class="cl-calendar-month">{{ $currentDate->format('F Y') }}</div>
                <div class="cl-calendar-nav">
                    <a href="{{ route('client.events.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" style="text-decoration:none;">
                        <button><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
                    </a>
                    <a href="{{ route('client.events.index', ['month' => now()->month, 'year' => now()->year]) }}" style="text-decoration:none;">
                        <button class="today-btn">Today</button>
                    </a>
                    <a href="{{ route('client.events.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" style="text-decoration:none;">
                        <button><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                    </a>
                </div>
            </div>

            <table class="cl-calendar">
                <thead>
                    <tr>
                        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    @php $dayCounter = 1; $started = false; @endphp
                    @for ($row = 0; $row < 6 && $dayCounter <= $daysInMonth; $row++)
                        <tr>
                            @for ($col = 0; $col < 7; $col++)
                                @if (!$started && $col < $firstDayOfWeek)
                                    <td><div class="cl-calendar-day empty"></div></td>
                                @elseif ($dayCounter <= $daysInMonth)
                                    @php
                                        $started = true;
                                        $isToday = $today->year == $year && $today->month == $month && $today->day == $dayCounter;
                                        $dayEvents = $eventsByDay[$dayCounter] ?? [];
                                    @endphp
                                    <td>
                                        <div class="cl-calendar-day {{ $isToday ? 'today' : '' }}">
                                            <div class="day-num">{{ $dayCounter }}</div>
                                            @foreach (array_slice($dayEvents, 0, 2) as $de)
                                                <div class="cl-calendar-event">{{ Str::limit($de->title, 14) }}</div>
                                            @endforeach
                                            @if (count($dayEvents) > 2)
                                                <div style="font-size:10px; color: var(--text-muted); margin-top:2px;">+{{ count($dayEvents) - 2 }} more</div>
                                            @endif
                                        </div>
                                    </td>
                                    @php $dayCounter++; @endphp
                                @else
                                    <td><div class="cl-calendar-day empty"></div></td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════════════ DETAILS VIEW ════════════ --}}
    <div class="cl-tab-content" id="tab-details">
        {{-- Stats Row --}}
        <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Events</div>
                        <div class="cl-stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Open Events</div>
                        <div class="cl-stat-value">{{ $stats['open'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon yellow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Upcoming</div>
                        <div class="cl-stat-value">{{ $stats['upcoming'] }}</div>
                    </div>
                </div>
            </div>
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon pink">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Budget</div>
                        <div class="cl-stat-value">${{ number_format($stats['total_budget'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search + Filter --}}
        <div class="cl-card" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('client.events.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="details">
                <div style="flex: 1; min-width: 200px;">
                    <div class="cl-search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search events..." value="{{ request('search') }}">
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <select name="status" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Status</option>
                        @foreach (['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select name="category" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </form>
        </div>

        {{-- Events List --}}
        @if($events->count())
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($events as $event)
                    <div class="cl-event-card">
                        <div class="cl-event-date-badge">
                            @if($event->starts_at)
                                <div class="month">{{ $event->starts_at->format('M') }}</div>
                                <div class="day">{{ $event->starts_at->format('d') }}</div>
                            @else
                                <div class="month">No</div>
                                <div class="day">—</div>
                            @endif
                        </div>
                        <div class="cl-event-info">
                            <div class="cl-event-title">{{ $event->title }}</div>
                            <div class="cl-event-meta">
                                @if($event->categories->count())
                                    @foreach($event->categories as $cat)
                                        <span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                            {{ $cat->name }}
                                        </span>
                                    @endforeach
                                @endif
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $event->created_at->diffForHumans() }}
                                </span>
                                <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                            </div>
                        </div>
                        <div class="cl-event-actions">
                            @if(!$event->is_published)
                                <form method="POST" action="{{ route('client.events.publish', $event) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Publish</button>
                                </form>
                            @endif
                            <a href="{{ route('client.events.show', $event) }}" class="cl-btn cl-btn-ghost cl-btn-sm">View</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($events->hasPages())
                <div class="cl-pagination">
                    @if($events->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $events->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($events->getUrlRange(1, $events->lastPage()) as $page => $url)
                        @if($page == $events->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </div>
            @endif
        @else
            <div class="cl-card">
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="16" x2="15" y2="16"/></svg>
                    </div>
                    <div class="cl-empty-title">No events found yet</div>
                    <div class="cl-empty-text">Create your first event to get started with hiring professionals.</div>
                    <button class="cl-btn cl-btn-primary" onclick="document.getElementById('postEventModal').classList.add('show')">Create Your First Event</button>
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════ POST EVENT MODAL (Clients only) ════════════ --}}
    <div class="cl-modal-overlay" id="postEventModal">
        <div class="cl-modal" style="max-width: 720px;">
            <form method="POST" action="{{ route('client.events.store') }}">
                @csrf
                <div class="cl-modal-header">
                    <div>
                        <div class="cl-modal-title">Post an Event</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Fill out the details to create a new event and invite professionals.</p>
                    </div>
                    <button type="button" class="cl-modal-close" onclick="document.getElementById('postEventModal').classList.remove('show')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="cl-modal-body">
                    <div class="cl-form-group">
                        <label class="cl-form-label">Event Title *</label>
                        <input type="text" name="title" class="cl-form-input" placeholder="e.g. Wedding Ceremony, Corporate Gala" required>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Description</label>
                        <textarea name="description" class="cl-form-textarea" rows="4" placeholder="Describe your event, expectations, and requirements..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Start Date & Time</label>
                            <input type="datetime-local" name="starts_at" class="cl-form-input">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">End Date & Time</label>
                            <input type="datetime-local" name="ends_at" class="cl-form-input">
                        </div>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Categories <span style="font-weight:400; color: var(--text-muted);">(select one or more)</span></label>
                        <div class="cl-multiselect-wrap" id="categoryMultiselect">
                            <div class="cl-multiselect-toggle" onclick="this.parentElement.classList.toggle('open')">
                                <span class="cl-multiselect-placeholder">Select categories...</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                            <div class="cl-multiselect-dropdown">
                                <div class="cl-multiselect-search">
                                    <input type="text" placeholder="Search categories..." oninput="filterCategories(this.value)">
                                </div>
                                <div class="cl-multiselect-options">
                                    @foreach ($categories as $cat)
                                        <label class="cl-multiselect-option" data-name="{{ strtolower($cat->name) }}">
                                            <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}">
                                            <span class="cl-multiselect-check">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            </span>
                                            <span>{{ $cat->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cl-form-group">
                        <label class="cl-form-label">Location</label>
                        <input type="text" name="location" class="cl-form-input" placeholder="City, Venue, or Address">
                    </div>
                </div>
                <div class="cl-modal-footer">
                    <button type="button" class="cl-btn cl-btn-ghost" onclick="document.getElementById('postEventModal').classList.remove('show')">Cancel</button>
                    <button type="submit" class="cl-btn cl-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Tab switching
    document.querySelectorAll('#viewTabs .cl-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#viewTabs .cl-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.cl-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });

    // Open modal if ?create=1
    if (new URLSearchParams(window.location.search).get('create') === '1') {
        document.getElementById('postEventModal').classList.add('show');
    }

    // Open details tab if ?tab=details
    if (new URLSearchParams(window.location.search).get('tab') === 'details') {
        document.querySelector('[data-tab="details"]').click();
    }

    // Close modal on overlay click
    document.getElementById('postEventModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('postEventModal').classList.remove('show');
            document.querySelectorAll('.cl-multiselect-wrap.open').forEach(el => el.classList.remove('open'));
        }
    });

    // ── Multi-Select Category Logic ──
    function updateMultiselectDisplay() {
        const wrap = document.getElementById('categoryMultiselect');
        const toggle = wrap.querySelector('.cl-multiselect-toggle');
        const checked = wrap.querySelectorAll('input[type="checkbox"]:checked');
        const placeholder = toggle.querySelector('.cl-multiselect-placeholder');

        // Remove existing tags
        toggle.querySelectorAll('.cl-multiselect-tags').forEach(el => el.remove());

        if (checked.length === 0) {
            if (placeholder) placeholder.style.display = '';
        } else {
            if (placeholder) placeholder.style.display = 'none';
            const tagsContainer = document.createElement('div');
            tagsContainer.className = 'cl-multiselect-tags';
            checked.forEach(cb => {
                const name = cb.closest('.cl-multiselect-option').querySelector('span:last-child').textContent;
                const tag = document.createElement('span');
                tag.className = 'cl-multiselect-tag';
                tag.innerHTML = name + ' <span class="tag-remove" data-id="' + cb.value + '"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>';
                tagsContainer.appendChild(tag);
            });
            toggle.insertBefore(tagsContainer, toggle.querySelector('svg:last-child'));
        }
    }

    // Checkbox change handler
    document.querySelectorAll('#categoryMultiselect input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', updateMultiselectDisplay);
    });

    // Tag remove handler (delegated)
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.tag-remove');
        if (removeBtn) {
            e.stopPropagation();
            const id = removeBtn.dataset.id;
            const cb = document.querySelector('#categoryMultiselect input[value="' + id + '"]');
            if (cb) { cb.checked = false; updateMultiselectDisplay(); }
        }
    });

    // Search/filter categories
    function filterCategories(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('#categoryMultiselect .cl-multiselect-option').forEach(opt => {
            const name = opt.dataset.name;
            opt.classList.toggle('hidden', q && !name.includes(q));
        });
    }

    // Close multiselect on outside click
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.cl-multiselect-wrap.open').forEach(wrap => {
            if (!wrap.contains(e.target)) wrap.classList.remove('open');
        });
    });
</script>
@endpush
