@extends('layouts.professional')

@section('title', 'Gigs')
@section('page-title', 'Gigs')

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

    /* Event card */
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
    .cl-event-date-badge.green { background: var(--accent-green-soft); }
    .cl-event-date-badge.green .month, .cl-event-date-badge.green .day { color: var(--accent-green); }

    .cl-event-info { flex: 1; min-width: 0; }
    .cl-event-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .cl-event-meta { font-size: 13px; color: var(--text-muted); display: flex; gap: 16px; flex-wrap: wrap; }
    .cl-event-meta span { display: flex; align-items: center; gap: 4px; }

    .cl-event-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }

    .cl-tab-content { display: none; }
    .cl-tab-content.active { display: block; }

    /* Browse event budget highlight */
    .cl-budget-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        background: var(--accent-green-soft);
        color: var(--accent-green);
    }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Gigs</h2>
            <p style="color: var(--text-muted); font-size: 14px;">Browse available jobs or manage your assigned gigs.</p>
        </div>
        <a href="{{ route('professional.proposals.index') }}" class="cl-btn cl-btn-ghost">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            My Proposals
        </a>
    </div>

    {{-- Tabs --}}
    <div class="cl-tabs" id="viewTabs">
        <button class="cl-tab {{ $view === 'browse' ? 'active' : '' }}" data-tab="browse">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-2px; margin-right:4px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Browse Available Jobs
            @if($browseStats['total'] > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:10px;font-size:11px;font-weight:600;background:var(--accent-green-soft);color:var(--accent-green);margin-left:4px;">{{ $browseStats['total'] }}</span>
            @endif
        </button>
        <button class="cl-tab {{ $view === 'my-gigs' ? 'active' : '' }}" data-tab="my-gigs">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-2px; margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            My Gigs
            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:10px;font-size:11px;font-weight:600;background:var(--accent-blue-soft);color:var(--accent-blue);margin-left:4px;">{{ $stats['total'] }}</span>
        </button>
        <button class="cl-tab {{ $view === 'calendar' ? 'active' : '' }}" data-tab="calendar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:-2px; margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendar
        </button>
    </div>

    {{-- ════════════ BROWSE AVAILABLE JOBS ════════════ --}}
    <div class="cl-tab-content {{ $view === 'browse' ? 'active' : '' }}" id="tab-browse">
        {{-- Search + Filter --}}
        <div class="cl-card" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('professional.gigs.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="view" value="browse">
                <div style="flex: 1; min-width: 200px;">
                    <div class="cl-search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search available jobs..." value="{{ $view === 'browse' ? request('search') : '' }}">
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <select name="category" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($view === 'browse' && request('category') == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </form>
        </div>

        {{-- Browse Stats --}}
        <div class="cl-card" style="margin-bottom: 20px; display: flex; align-items: center; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-sm); background: var(--accent-green-soft); display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent-green)" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <div>
                <div style="font-size: 22px; font-weight: 800; color: var(--text-primary);">{{ $browseStats['total'] }}</div>
                <div style="font-size: 13px; color: var(--text-muted);">Available event jobs open for proposals</div>
            </div>
        </div>

        {{-- Browse Jobs List --}}
        @if($browseEvents->count())
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($browseEvents as $event)
                    <div class="cl-event-card">
                        <div class="cl-event-date-badge green">
                            @if($event->starts_at)
                                <div class="month">{{ $event->starts_at->format('M') }}</div>
                                <div class="day">{{ $event->starts_at->format('d') }}</div>
                            @else
                                <div class="month">TBD</div>
                                <div class="day">—</div>
                            @endif
                        </div>
                        <div class="cl-event-info">
                            <div class="cl-event-title">{{ $event->title }}</div>
                            <div class="cl-event-meta">
                                @if($event->client)
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        {{ $event->client->name }}
                                    </span>
                                @endif
                                @if($event->category)
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                        {{ $event->category->name }}
                                    </span>
                                @endif
                                @if($event->budget)
                                    <span class="cl-budget-tag">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        ${{ number_format($event->budget, 0) }}
                                    </span>
                                @endif
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Posted {{ $event->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                        <div class="cl-event-actions">
                            <a href="{{ route('professional.gigs.show', $event) }}" class="cl-btn cl-btn-primary cl-btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($browseEvents->hasPages())
                <div class="cl-pagination" style="margin-top: 20px;">
                    @if($browseEvents->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $browseEvents->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($browseEvents->getUrlRange(1, $browseEvents->lastPage()) as $page => $url)
                        @if($page == $browseEvents->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($browseEvents->hasMorePages())
                        <a href="{{ $browseEvents->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </div>
            @endif
        @else
            <div class="cl-card">
                <div class="cl-empty">
                    <div class="cl-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <div class="cl-empty-title">No available jobs right now</div>
                    <div class="cl-empty-text">New event jobs published by clients will appear here. Check back soon!</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════ MY GIGS ════════════ --}}
    <div class="cl-tab-content {{ $view === 'my-gigs' ? 'active' : '' }}" id="tab-my-gigs">
        {{-- Stats Row --}}
        <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
            <div class="cl-card">
                <div class="cl-stat-card">
                    <div class="cl-stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Total Gigs</div>
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
                        <div class="cl-stat-label">Active</div>
                        <div class="cl-stat-value">{{ $stats['active'] }}</div>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div class="cl-stat-label">Completed</div>
                        <div class="cl-stat-value">{{ $stats['completed'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search + Filter --}}
        <div class="cl-card" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('professional.gigs.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="view" value="my-gigs">
                <div style="flex: 1; min-width: 200px;">
                    <div class="cl-search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search my gigs..." value="{{ $view === 'my-gigs' ? request('search') : '' }}">
                    </div>
                </div>
                <div style="min-width: 150px;">
                    <select name="status" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Status</option>
                        @foreach (['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ ($view === 'my-gigs' && request('status') === $s) ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select name="category" class="cl-form-select" style="padding: 10px 14px;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($view === 'my-gigs' && request('category') == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
            </form>
        </div>

        {{-- Gigs List --}}
        @if($myGigs->count())
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($myGigs as $event)
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
                                @if($event->client)
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        {{ $event->client->name }}
                                    </span>
                                @endif
                                @if($event->category)
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                        {{ $event->category->name }}
                                    </span>
                                @endif
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $event->created_at->diffForHumans() }}
                                </span>
                                <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                            </div>
                        </div>
                        <div class="cl-event-actions">
                            <a href="{{ route('professional.gigs.show', $event) }}" class="cl-btn cl-btn-ghost cl-btn-sm">View</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($myGigs->hasPages())
                <div class="cl-pagination" style="margin-top: 20px;">
                    @if($myGigs->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $myGigs->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($myGigs->getUrlRange(1, $myGigs->lastPage()) as $page => $url)
                        @if($page == $myGigs->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($myGigs->hasMorePages())
                        <a href="{{ $myGigs->nextPageUrl() }}">&raquo;</a>
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
                    <div class="cl-empty-title">No gigs assigned yet</div>
                    <div class="cl-empty-text">Gigs you are assigned to will appear here. Browse available jobs to find new opportunities!</div>
                    <a href="{{ route('professional.gigs.index', ['view' => 'browse']) }}" class="cl-btn cl-btn-primary cl-btn-sm" style="margin-top: 8px;">Browse Available Jobs</a>
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════ CALENDAR VIEW ════════════ --}}
    <div class="cl-tab-content {{ $view === 'calendar' ? 'active' : '' }}" id="tab-calendar">
        <div class="cl-card">
            @php
                $currentDate = \Carbon\Carbon::create($year, $month, 1);
                $daysInMonth = $currentDate->daysInMonth;
                $firstDayOfWeek = $currentDate->dayOfWeek;
                $today = now();
                $prevMonth = $currentDate->copy()->subMonth();
                $nextMonth = $currentDate->copy()->addMonth();

                $eventsByDay = [];
                foreach ($calendarEvents as $ce) {
                    $day = $ce->starts_at->day;
                    $eventsByDay[$day][] = $ce;
                }
            @endphp

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div class="cl-calendar-month">{{ $currentDate->format('F Y') }}</div>
                <div class="cl-calendar-nav">
                    <a href="{{ route('professional.gigs.index', ['view' => 'calendar', 'month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" style="text-decoration:none;">
                        <button><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
                    </a>
                    <a href="{{ route('professional.gigs.index', ['view' => 'calendar', 'month' => now()->month, 'year' => now()->year]) }}" style="text-decoration:none;">
                        <button class="today-btn">Today</button>
                    </a>
                    <a href="{{ route('professional.gigs.index', ['view' => 'calendar', 'month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" style="text-decoration:none;">
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

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('view', this.dataset.tab);
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endpush
