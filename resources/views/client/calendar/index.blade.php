@extends('layouts.client')

@section('title', 'My Calendar')
@section('page-title', 'My Calendar')
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
        <h1>My Calendar</h1>
        <p>Your events, bookings and the dates that matter, in one place.</p>
    </div>
    <a class="cal-page-go" href="{{ route('client.post-event.choose') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Event
    </a>
</div>
<div class="cal-layout">
    <div>
        {{-- Sir Peter, 12 October: the calendar stands alone as a webpage. The
             four cross-links that used to sit here, and the My Availability
             tab among them, moved into the Availability page's own tabs. --}}
        @include('client._calendar', [
            'calendar' => $calendar,
            'calRoute' => 'client.calendar.index',
            'calFixed' => [],
        ])

        {{-- Counted from the same events the grid above is drawn from, over
             the range on screen, so the two can never disagree. --}}
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
            <p class="cal-wait">
                <b>Availability and Hired Talent each have their own page.</b>
                <a href="{{ route('client.check-availability.index', ['tab' => 'mine']) }}">Availability</a>
                is where you say which days suit you, and where you check whether somebody is already
                booked. <a href="{{ route('client.hired-talent.index') }}">Hired Talent</a> lists who you
                have hired, by event.
            </p>
        </div>

        {{-- The tips card from Sir Peter's calendar. Plain copy about how the
             page works, which is the one thing on his rail that needs nothing
             behind it to be true. --}}
        <div class="cal-card cal-tips">
            <div class="cal-rail-h">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Calendar tips
                </h3>
            </div>
            <ul>
                <li>Every event, booking and date you care about sits on this one page.</li>
                <li>A date opens that day. An entry opens the event behind it.</li>
                <li><b>List</b> is the same month read as a list, and the easiest view on a phone.</li>
            </ul>
        </div>
    </aside>
</div>
@endsection
