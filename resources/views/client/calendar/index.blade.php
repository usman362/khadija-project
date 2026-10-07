@extends('layouts.client')

@section('title', 'Calendar & Availability')
@section('page-title', 'Calendar & Availability')
@section('page-subtitle', 'Your events and who is working on them.')

@push('styles')
<style>
    .cal-layout { display: grid; grid-template-columns: minmax(0,1fr) var(--cl-rail); gap: var(--cl-rail-gap); align-items: start; }
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
            <span class="cal-tab is-off" title="Professionals do not record their available dates yet.">
                <span class="cal-tab-ico" style="background:rgba(29,78,216,.1);color:#1d4ed8;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span><b>Hired Professionals</b><span>Waiting on their side</span></span>
            </span>
            <span class="cal-tab is-off" title="The influencer side is not open yet.">
                <span class="cal-tab-ico" style="background:rgba(109,40,217,.1);color:#6d28d9;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/></svg>
                </span>
                <span><b>Hired Influencers</b><span>Waiting on their side</span></span>
            </span>
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
                <span>{{ $calendar['view'] === 'month' ? 'Events this month' : ($calendar['view'] === 'week' ? 'Events this week' : 'Events today') }}</span>
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
                <b>Your hired professionals' availability is not here yet.</b> Nothing records when they are
                free, only when they are already booked, and entering it is theirs to do.
            </p>
        </div>
    </aside>
</div>
@endsection
