@extends('layouts.client')

@section('title', 'Calendar & Availability')
@section('page-title', 'Calendar & Availability')
@section('page-subtitle', 'Your events and who is working on them.')

@push('styles')
<style>
    .cal-layout { display: grid; grid-template-columns: minmax(0,1fr) var(--cl-rail); gap: var(--cl-rail-gap); align-items: start; }
    .cal-page-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
    .cal-page-ico { width: 52px; height: 52px; border-radius: 15px; background: rgba(249,115,22,.12);
        display: flex; align-items: center; justify-content: center; flex: none; }
    .cal-page-ico svg { width: 26px; height: 26px; color: var(--accent-orange, #ea580c); }
    .cal-page-txt { flex: 1; min-width: 0; }
    .cal-page-txt h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .cal-page-txt p { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }
    .cal-page-go { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 11px;
        background: var(--accent-orange, #ea580c); color: #fff; font-size: 13.5px; font-weight: 800;
        text-decoration: none; white-space: nowrap; flex: none; }
    .cal-page-go svg { width: 16px; height: 16px; }
    @media (max-width: 620px) { .cal-page-txt p { display: none; } .cal-page-go { padding: 10px 13px; } }

    .cal-quick-row { display: flex; align-items: center; gap: 11px; padding: 10px 0; text-decoration: none;
        border-top: 1px solid var(--border-color); }
    .cal-quick-row:first-of-type { border-top: 0; }
    .cal-quick-ico { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center;
        justify-content: center; flex: none; }
    .cal-quick-ico svg { width: 17px; height: 17px; }
    .cal-quick-txt { flex: 1; min-width: 0; }
    .cal-quick-txt b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .cal-quick-txt span { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .cal-quick-go { color: var(--text-muted); font-size: 18px; flex: none; }
    .cal-quick-row:hover .cal-quick-go { color: var(--accent-orange, #ea580c); }

    .cal-tips .cal-rail-h h3 { display: flex; align-items: center; gap: 7px; }
    .cal-tips .cal-rail-h svg { width: 15px; height: 15px; color: var(--info-text, #1d4ed8); flex: none; }
    .cal-tips ul { margin: 0; padding-left: 17px; }
    .cal-tips li { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; margin-bottom: 6px; }
    .cal-tips li:last-child { margin-bottom: 0; }
    .cal-tips b { color: var(--text-secondary); }

    .cal-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 88px; }
    .cal-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 18px; }

    /* Sir Peter's four tabs. Three of them are waiting on something, and say
       so on the tab rather than opening onto an empty month. */
    .cal-tabs { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; margin-bottom: 16px; }
    .cal-tab { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--border-color);
        background: var(--bg-card); text-align: left; font-family: inherit; cursor: pointer; width: 100%; }
    .cal-tab.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.08); }
    a.cal-tab { text-decoration: none; }
    .cal-tab.is-off { cursor: default; opacity: .62; }
    .cal-tab-ico { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex: none; }
    .cal-tab-ico svg { width: 17px; height: 17px; }
    .cal-tab b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .cal-tab span { font-size: 11.5px; color: var(--text-muted); }

    .cal-counts { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0; margin-top: 16px;
        border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; }
    .cal-count { padding: 14px 10px; text-align: center; }
    .cal-count + .cal-count { border-left: 1px solid var(--border-color); }
    .cal-count b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .cal-count span { font-size: 12px; color: var(--text-muted); }

    .cal-rail-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .cal-rail-h h3 { margin: 0; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .cal-rail-h a { font-size: 12px; font-weight: 700; color: var(--accent-orange, #ea580c); text-decoration: none; }
    .cal-up { display: flex; gap: 12px; padding: 10px 0; text-decoration: none; align-items: flex-start; }
    .cal-up + .cal-up { border-top: 1px solid var(--border-color); }
    .cal-up-d { flex: none; width: 42px; text-align: center; }
    .cal-up-d i { display: block; font-style: normal; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: var(--accent-orange, #ea580c); }
    .cal-up-d b { display: block; font-size: 17px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .cal-up-b { min-width: 0; flex: 1; }
    .cal-up-t { font-size: 13px; font-weight: 700; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cal-up-m { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .cal-pill { display: inline-block; margin-top: 6px; font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .cal-none { font-size: 12.5px; color: var(--text-muted); padding: 6px 0; }

    .cal-wait { font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; }
    .cal-wait b { color: var(--text-primary); }

    @media (max-width: 1024px) { .cal-layout { grid-template-columns: 1fr; } .cal-rail { position: static; } }
    @media (max-width: 760px)  { .cal-tabs { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
{{-- Sir Peter's calendar carries its own header and the one action that
     belongs on it. Sync to Google/Outlook and Report Issue are on his image
     too and are deliberately not here: neither exists, and a button that does
     nothing is the thing he asked us to stop shipping. --}}
<div class="cal-page-head">
    <span class="cal-page-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </span>
    <div class="cal-page-txt">
        <h1>{{ $tab === 'availability' ? 'My Availability' : 'Calendar' }}</h1>
        <p>{{ $tab === 'availability'
            ? 'Say which days suit you, so your planning and your requests line up.'
            : 'Your events, bookings and the dates that matter, in one place.' }}</p>
    </div>
    <a class="cal-page-go" href="{{ route('client.post-event.choose') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Event
    </a>
</div>
<div class="cal-layout">
    <div>
        {{-- Sir Peter's four tabs, 24 September. Only the first has anything
             behind it: nothing on this platform records when somebody is
             free. A professional's calendar is built from their bookings and
             shifts, which say when they are busy. So the other three say what
             they are waiting for instead of opening onto a month of invented
             free days. --}}
        @php
            $tabLink = fn (string $t) => route('client.calendar.index', array_merge(
                request()->only(['calview', 'cal', 'anchor']), ['tab' => $t]
            ));
        @endphp
        <div class="cal-tabs">
            <a href="{{ $tabLink('calendar') }}" class="cal-tab {{ $tab === 'calendar' ? 'is-on' : '' }}">
                <span class="cal-tab-ico" style="background:rgba(249,115,22,.14);color:#ea580c;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                <span><b>My Calendar</b><span>My events and schedule</span></span>
            </a>
            <a href="{{ $tabLink('availability') }}" class="cal-tab {{ $tab === 'availability' ? 'is-on' : '' }}">
                <span class="cal-tab-ico" style="background:rgba(16,185,129,.12);color:#059669;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span><b>My Availability</b><span>Set your available dates</span></span>
            </a>
            {{-- These two were switched off, and the reason written on them,
                 "Professionals do not record their available dates yet", was
                 the reason Check Availability is waiting rather than this one.
                 Who you hired is a booking, and we know that completely. They
                 are now one page, as Sir Peter asked: "once someone is hired,
                 the Client's purpose is essentially the same regardless of
                 role". --}}
            <a href="{{ route('client.hired-talent.index') }}" class="cal-tab">
                <span class="cal-tab-ico" style="background:rgba(29,78,216,.1);color:#1d4ed8;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span><b>Hired Talent</b><span>Who you have booked</span></span>
            </a>
            {{-- It opens, and then it is honest on arrival: we know who is
                 booked, never who is free, because that is theirs to say. --}}
            <a href="{{ route('client.check-availability.index') }}" class="cal-tab">
                <span class="cal-tab-ico" style="background:rgba(109,40,217,.1);color:#6d28d9;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="9 16 11 18 15 14"/></svg>
                </span>
                <span><b>Check Availability</b><span>Before you hire</span></span>
            </a>
        </div>

        @if($tab === 'calendar')
            @include('client._calendar', [
                'calendar' => $calendar,
                'calRoute' => 'client.calendar.index',
                'calFixed' => [],
            ])
        @else
            @include('client.calendar._availability', [
                'calendar'     => $calendar,
                'availability' => $availability,
                'tally'        => $tally,
            ])
        @endif

        {{-- Counted from the same events the grid above is drawn from, over
             the range on screen, so the two can never disagree. --}}
        @if($tab === 'calendar')
        <div class="cal-counts">
            <div class="cal-count">
                <b>{{ $counts['in_range'] }}</b>
                {{-- Named after the range actually counted. A list spans the
                     month it is listing, so it is not "today". --}}
                <span>{{ match($calendar['view']) {
                    'month', 'list' => 'Events this month',
                    'week'          => 'Events this week',
                    default         => 'Events today',
                } }}</span>
            </div>
            <div class="cal-count"><b>{{ $counts['confirmed'] }}</b><span>Booked</span></div>
            <div class="cal-count"><b>{{ $counts['needs_you'] }}</b><span>Taking proposals</span></div>
        </div>
        @endif
    </div>

    <aside class="cal-rail">
        <div class="cal-card">
            <div class="cal-rail-h">
                <h3>Upcoming Events</h3>
                <a href="{{ route('client.events.index') }}">View all</a>
            </div>
            @forelse($upcoming as $ev)
                @php [$stLabel, $stColour] = \App\Support\ClientCalendar::STAGES[$ev->stage()] ?? ['Event', '#f97316']; @endphp
                <a class="cal-up" href="{{ route('client.events.show', $ev) }}" style="text-decoration:none;">
                    <span class="cal-up-d">
                        <i>{{ $ev->starts_at->format('M') }}</i>
                        <b>{{ $ev->starts_at->format('j') }}</b>
                    </span>
                    <span class="cal-up-b">
                        <span class="cal-up-t">{{ $ev->title }}</span>
                        <span class="cal-up-m">
                            {{ $ev->starts_at->format('g:i A') }}@if($ev->ends_at) &ndash; {{ $ev->ends_at->format('g:i A') }}@endif
                            @php $place = \App\Domain\Requests\VenueRule::place($ev->location) ?: trim(($ev->city ? $ev->city . ', ' : '') . ($ev->state ?? '')); @endphp
                            @if($place) &middot; {{ $place }} @endif
                        </span>
                        {{-- Under the line rather than beside it: "Open for
                             proposals" beside a long title had nowhere to go
                             on a 340px rail, and sat on top of it. --}}
                        <span class="cal-pill" style="background:{{ $stColour }}1f;color:{{ $stColour }};">{{ $stLabel }}</span>
                    </span>
                </a>
            @empty
                <p class="cal-none">Nothing booked ahead. <a href="{{ route('client.post-event.choose') }}">Post an event</a> and it appears here.</p>
            @endforelse
        </div>

        <div class="cal-card">
            <div class="cal-rail-h"><h3>Your availability</h3></div>
            {{-- The range in words. It used to print the view's own name, so
                 a day view said "you have not marked any days this day". --}}
            @php
                $__span = match ($calendar['view']) {
                    'day'  => 'today',
                    'week' => 'this week',
                    default => 'this month',
                };
            @endphp
            @if($tally[\App\Domain\Calendar\Availability::AVAILABLE] || $tally[\App\Domain\Calendar\Availability::UNAVAILABLE])
                <p class="cal-wait">
                    {{ ucfirst($__span) }}: <b>{{ $tally[\App\Domain\Calendar\Availability::AVAILABLE] }}</b>
                    {{ \Illuminate\Support\Str::plural('day', $tally[\App\Domain\Calendar\Availability::AVAILABLE]) }} marked available,
                    <b>{{ $tally[\App\Domain\Calendar\Availability::UNAVAILABLE] }}</b> blocked.
                    Every other day is simply unanswered.
                </p>
            @else
                <p class="cal-wait">
                    You have not marked any days {{ $__span }}. Open
                    <a href="{{ route('client.calendar.index', ['tab' => 'availability']) }}">My Availability</a>
                    to say which days suit you.
                </p>
            @endif
            <p class="cal-wait" style="margin-top:10px;">
                <b>Who you have hired is on its own page.</b>
                <a href="{{ route('client.hired-talent.index') }}">Hired Talent</a> lists them by event, and
                <a href="{{ route('client.check-availability.index') }}">Check Availability</a> says who is
                already booked on a date. Nothing records when they are free; that is theirs to enter.
            </p>
        </div>

        @if($tab === 'availability')
            {{-- Quick Actions, from his My Availability image. Each one points
                 at something that already works on this page: the range form
                 below the grid, the list view, and clearing a month. There is
                 no fourth button, because there is no fourth thing. --}}
            <div class="cal-card cal-quick">
                <div class="cal-rail-h"><h3>Quick actions</h3></div>
                <a class="cal-quick-row" href="#avRange">
                    <span class="cal-quick-ico" style="background:rgba(249,115,22,.12);color:#ea580c;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="cal-quick-txt"><b>Set a stretch of dates</b><span>Mark a whole run of days in one go.</span></span>
                    <span class="cal-quick-go">&rsaquo;</span>
                </a>
                <a class="cal-quick-row" href="{{ route('client.calendar.index', ['calview' => 'list', 'cal' => $calendar['anchor']->format('Y-m-d')]) }}">
                    <span class="cal-quick-ico" style="background:rgba(37,99,235,.1);color:#1d4ed8;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </span>
                    <span class="cal-quick-txt"><b>View as list</b><span>Your month as a list instead of a grid.</span></span>
                    <span class="cal-quick-go">&rsaquo;</span>
                </a>
            </div>
        @endif

        {{-- The tips card from Sir Peter's calendar. Plain copy about how the
             page works, which is the one thing on his rail that needs nothing
             behind it to be true. --}}
        <div class="cal-card cal-tips">
            <div class="cal-rail-h">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    {{ $tab === 'availability' ? 'Tips' : 'Calendar tips' }}
                </h3>
            </div>
            <ul>
                @if($tab === 'availability')
                    <li>Click a day to move it between available, unavailable and not set.</li>
                    <li>Not set is not the same as free. It means nobody has said anything about that day.</li>
                    <li>Use <b>Block a stretch of dates</b> for a holiday, rather than clicking each day.</li>
                @else
                    <li>Every event, booking and date you care about sits on this one page.</li>
                    <li>A date opens that day. An entry opens the event behind it.</li>
                    <li><b>List</b> is the same month read as a list, and the easiest view on a phone.</li>
                @endif
            </ul>
        </div>
    </aside>
</div>
@endsection
