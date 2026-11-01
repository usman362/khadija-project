@extends('layouts.professional')

@section('title', 'My Calendar')

{{-- My Calendar — explainer + a live snapshot of the pro's schedule.
     REAL data: agenda unifies assigned Shifts + booking Events; the month
     grid marks days that have items; the availability strip derives
     busy/free per day. The phone-sync panel was removed on 2026-08-05 and
     the invented travel time on 2026-11-01; nothing drawn here is made up. --}}

@php
    $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    // Agenda row badge: all-day shows the date, timed shows the start time.
    $badgeFor = fn ($it) => $it['all_day'] ? $it['start']->format('M d') : $it['start']->format('g:i A');
@endphp

@push('styles')
<style>
    .mc { --mc-blue: #2563eb; }
    .mc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }

    /* ── Hero ── */
    .mc-hero { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(0,0.85fr); gap: 22px; align-items: stretch; background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(139,92,246,0.04)); border: 1px solid var(--border-color); border-radius: 18px; padding: 24px 26px; margin-bottom: 20px; }
    .mc-hero-l { display: flex; flex-direction: column; }
    .mc-hero-top { display: grid; grid-template-columns: 200px minmax(0,1fr); gap: 18px; align-items: center; margin-bottom: 18px; }
    .mc-art { display: flex; align-items: center; justify-content: center; }
    .mc-art svg { width: 100%; max-width: 190px; height: auto; }
    .mc-hero-l h1 { font-size: 34px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .mc-hero-l .sub { font-size: 15px; color: var(--text-secondary); margin: 6px 0 0; line-height: 1.45; }
    .mc-feats { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
    .mc-feat { display: flex; gap: 11px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); }
    .mc-feat-ico { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-feat-ico svg { width: 17px; height: 17px; }
    .mc-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; line-height: 1.3; }
    .mc-feat p { font-size: 10.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }

    /* Today card */
    .mc-today { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; display: flex; flex-direction: column; }
    .mc-today-h { display: flex; align-items: center; gap: 12px; padding-bottom: 14px; }
    .mc-today-ico { width: 44px; height: 44px; flex-shrink: 0; }
    .mc-today-ico svg { width: 100%; height: 100%; }
    .mc-today-h .lbl { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .mc-today-h .dt { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .mc-ag-list { border: 1px solid var(--border-color); border-radius: 12px; padding: 6px 12px; }
    .mc-ag { display: flex; align-items: center; gap: 13px; padding: 13px 4px; border-top: 1px solid var(--border-color); }
    .mc-ag:first-child { border-top: none; }
    .mc-ag-ic { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-ag-ic svg { width: 19px; height: 19px; }
    .mc-ag-mid { flex: 1; min-width: 0; }
    .mc-ag-title { font-size: 14px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mc-ag-sub { font-size: 12px; margin-top: 2px; }
    .mc-ag-badge { font-size: 12px; font-weight: 800; padding: 6px 13px; border-radius: 9px; white-space: nowrap; flex-shrink: 0; }
    .mc-viewcal { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; margin-top: 14px; border: 1px solid var(--border-color); border-radius: 11px; font-size: 14px; font-weight: 800; color: var(--mc-blue); text-decoration: none; }
    .mc-viewcal svg { width: 16px; height: 16px; }
    .mc-empty { padding: 26px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* ── Section heading ── */
    .mc-sec-h { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .mc-sec-h .ic { width: 30px; height: 30px; border-radius: 9px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-sec-h .ic svg { width: 17px; height: 17px; color: var(--info-text); }
    .mc-sec-h b { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .mc-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; padding-left: 40px; }


    /* ── What happens (4 cols) ── */
    .mc-cc { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .mc-cc-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; background: var(--bg-card); }
    .mc-cc-h { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 4px; }
    .mc-cc-h .ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-cc-h .ic svg { width: 16px; height: 16px; }
    .mc-cc-h b { font-size: 13px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .mc-cc-card > p { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 0 0 12px; }

    /* mini month grid */
    .mc-mini { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px; }
    .mc-mini-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .mc-mini-h b { font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .mc-mini-h svg { width: 13px; height: 13px; color: var(--text-muted); }
    .mc-mini-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px; }
    .mc-mini-dn { font-size: 8.5px; font-weight: 700; color: var(--text-muted); text-align: center; padding: 2px 0; }
    .mc-mini-day { position: relative; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 9.5px; color: var(--text-secondary); border-radius: 6px; }
    .mc-mini-day.out { color: var(--text-muted); opacity: 0.4; }
    .mc-mini-day.today { background: #2563eb; color: #fff; font-weight: 800; }
    .mc-mini-bars { position: absolute; bottom: 2px; left: 0; right: 0; display: flex; justify-content: center; gap: 2px; }
    .mc-mini-bars i { width: 5px; height: 3px; border-radius: 2px; }

    /* availability toggles */
    .mc-av-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-top: 1px solid var(--border-color); }
    .mc-av-row:first-child { border-top: none; }
    .mc-av-d { font-size: 12px; font-weight: 700; color: var(--text-primary); }
    .mc-av-s { font-size: 12px; font-weight: 700; }

    /* phone sync */
    .mc-sync { display: flex; align-items: center; justify-content: center; gap: 14px; padding: 6px 0 12px; }
    .mc-sync-app { text-align: center; }
    .mc-sync-app .box { width: 46px; height: 46px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; background: var(--bg-card-hover); }
    .mc-sync-app .box svg { width: 24px; height: 24px; }
    .mc-sync-app span { font-size: 9.5px; color: var(--text-muted); }
    .mc-sync-arr svg { width: 22px; height: 22px; color: var(--ok-text); }
    .mc-sync-done { display: flex; align-items: center; justify-content: center; gap: 7px; background: rgba(16,185,129,0.1); border-radius: 9px; padding: 9px; font-size: 11px; font-weight: 700; color: var(--ok-text); }
    .mc-sync-done svg { width: 14px; height: 14px; }

    /* travel time */
    .mc-tt-row { display: flex; align-items: center; gap: 9px; padding: 8px 0; }
    .mc-tt-row .k { font-size: 11px; color: var(--text-muted); width: 40px; flex-shrink: 0; }
    .mc-tt-row .v { font-size: 11.5px; font-weight: 700; color: var(--text-primary); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mc-tt-row .pin { width: 15px; height: 15px; flex-shrink: 0; }
    .mc-tt-big { display: flex; align-items: center; gap: 9px; padding-top: 10px; border-top: 1px solid var(--border-color); margin-top: 4px; }
    .mc-tt-big .k { font-size: 10.5px; color: var(--text-muted); }
    .mc-tt-big .v { font-size: 20px; font-weight: 800; }
    .mc-tt-big svg { width: 20px; height: 20px; }

    /* ── Bottom banner ── */
    .mc-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(139,92,246,0.05)); border: 1px solid rgba(37,99,235,0.18); border-radius: 16px; padding: 18px 22px; margin-top: 20px; }
    .mc-banner .clock { width: 48px; height: 48px; flex-shrink: 0; }
    .mc-banner .clock svg { width: 100%; height: 100%; }
    .mc-banner-txt { flex: 1; }
    .mc-banner-txt b { font-size: 17px; color: var(--text-primary); }
    .mc-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .mc-banner a { display: inline-flex; align-items: center; gap: 9px; background: #2563eb; color: #fff; font-size: 15px; font-weight: 800; padding: 14px 24px; border-radius: 12px; text-decoration: none; white-space: nowrap; }
    .mc-banner a svg { width: 17px; height: 17px; }

    @media (max-width: 1200px) { .mc-hero, .mc-hero-top { grid-template-columns: 1fr; } .mc-cc { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .mc-feats, .mc-cc { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="mc">

    {{-- ════════ Hero ════════ --}}
    <div class="mc-hero">
        <div class="mc-hero-l">
            <div class="mc-hero-top">
                <div class="mc-art">@include('professional.calendar._cal_clock', ['w' => 190])</div>
                <div>
                    <h1>My Calendar</h1>
                    <div class="sub">View your schedule, availability and important dates.</div>
                </div>
            </div>
            <div class="mc-feats">
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(37,99,235,0.12);color:var(--info-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
                    <div><b>Prevents Double-Booking</b><p>Stops you from scheduling two clients at the same time.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(16,185,129,0.12);color:var(--ok-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6M22 6l-3-3M9 1h6"/></svg></span>
                    <div><b>Improves Punctuality</b><p>Keeps event start times front and center so you're never late.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(139,92,246,0.12);color:var(--accent-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></span>
                    <div><b>Reduces Daily Stress</b><p>No more guessing what you have to do today.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(249,115,22,0.12);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <div><b>Protects Free Time</b><p>See when you're busy and when you're free to relax.</p></div>
                </div>
            </div>
        </div>

        {{-- Today card --}}
        <div class="mc-today">
            <div class="mc-today-h">
                <span class="mc-today-ico">@include('professional.calendar._cal_clock', ['w' => 44])</span>
                <div><span class="lbl">Today</span> &nbsp;•&nbsp; <span class="dt">{{ $now->format('M d, Y') }}</span></div>
            </div>
            <div class="mc-ag-list">
                @forelse($agenda as $it)
                    <div class="mc-ag">
                        <span class="mc-ag-ic" style="background:{{ $it['color'] }}1f;color:{{ $it['color'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                        <div class="mc-ag-mid">
                            <div class="mc-ag-title">{{ $it['title'] }}</div>
                            <div class="mc-ag-sub" style="color:{{ $it['color'] }};">{{ $it['all_day'] ? $it['start']->format('M d') . ' · All Day' : $it['start']->format('g:i A') }}</div>
                        </div>
                        <span class="mc-ag-badge" style="background:{{ $it['color'] }}1f;color:{{ $it['color'] }};">{{ $badgeFor($it) }}</span>
                    </div>
                @empty
                    <div class="mc-empty">No upcoming events. Win a gig or get assigned a shift to see it here.</div>
                @endforelse
            </div>
            <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}" class="mc-viewcal">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>

    {{-- A "Detailed Section Breakdown" stood here: three columns explaining
         this page, on this page.

         The first said "the calendar and clock symbolize organized time" and
         nothing else. The second listed the agenda again, directly under the
         agenda. The third drew grey bars with green ticks beside them — a
         mock-up of a screen, on a screen — and promised "the full monthly
         calendar and advanced tools" behind a link that goes to the Gig Hub.

         A tour of a page belongs somewhere other than the page, and a
         mock-up of an interface shown inside the interface is the clearest
         case there is of something drawn rather than built. --}}

    {{-- ════════ What happens if you click ════════ --}}
    {{-- This was headed "What Happens if You Click?" over "Unlock powerful
         scheduling tools to manage your time like a pro", which framed three
         cards of real data as a preview of something you would get later.
         They are not a preview. They are your month. --}}
    <div class="mc-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>Your month at a glance</b></div>
    <p class="mc-sec-sub">Where your days stand, and where you are needed next.</p>
    <div class="mc-cc">
        {{-- Monthly Grid View --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:var(--info-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>Monthly Grid View</b></div>
            {{-- "Drag and drop events to change dates" went with the rest:
                 nothing here is draggable and no handler exists. --}}
            <p>Every day this month, with a mark on the ones that have something on.</p>
            <div class="mc-mini">
                <div class="mc-mini-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg><b>{{ $monthLabel }}</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></div>
                <div class="mc-mini-grid">
                    @foreach($dayNames as $dn)<div class="mc-mini-dn">{{ $dn }}</div>@endforeach
                    @foreach($grid as $cell)
                        <div class="mc-mini-day {{ $cell['isToday'] ? 'today' : '' }} {{ $cell['inMonth'] ? '' : 'out' }}">
                            {{ $cell['day'] }}
                            @if(!empty($cell['markers']) && !$cell['isToday'])
                                <span class="mc-mini-bars">@foreach($cell['markers'] as $mk)<i style="background:{{ $mk }};"></i>@endforeach</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        {{-- Availability Toggles --}}
        <div class="mc-cc-card">
            {{-- This was "Availability Toggles — block off days so new clients
                 can't book you", with a switch on every row. The switches were
                 spans. They did nothing, they were always on, and there is no
                 availability setting on the professional side for them to do
                 anything TO: busy is worked out from whether the day already
                 has a booking.

                 So a professional could read it, see every day switched on,
                 and believe they had told the platform when they were free.
                 The days are real and worth showing; the control was not. --}}
            <div class="mc-cc-h"><span class="ic" style="background:rgba(139,92,246,0.12);color:var(--accent-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg></span><b>Your next few days</b></div>
            <p>Worked out from what is already booked.</p>
            <div style="border:1px solid var(--border-color);border-radius:10px;padding:4px 12px;">
                @foreach($availability as $av)
                    @php $colour = $av['busy'] ? '#ef4444' : '#10b981'; @endphp
                    <div class="mc-av-row">
                        <span class="mc-av-d">{{ $av['date']->format('M d') }}</span>
                        <span class="mc-av-s" style="color:{{ $colour }};">{{ $av['busy'] ? 'Booked' : 'Nothing on' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        {{-- Phone Synchronization panel removed 2026-08-05.

             It showed Apple and Google Calendar with a tick and the words
             "Synced Successfully", and none of it existed — no calendar
             integration is built at all. A professional reading that would
             believe their outside commitments were reaching GigResource and
             could double-book on the strength of it.

             Sir Peter has asked for calendar sync as a real feature (his
             Gap #5, 2026-08-05). When it is built, this panel comes back
             showing the actual connection state instead of a fixed tick. --}}

        {{-- A "Travel Time Calculator" stood here.

             It printed 35 mins, as a number typed into the page, and then
             worked out "Leave By" from it. Nothing measures travel time —
             there is no maps provider on the platform, which is Sir Peter's
             own outstanding item — so the figure was the same 35 minutes for
             a venue down the road and one three hours away.

             That is not decoration. A professional reading "Leave By 5:25
             PM" and believing it arrives late to somebody's wedding, and the
             site told them to.

             What IS known is where the next thing is and when it starts, so
             that is what the card shows now. The travel time comes back when
             there is something behind it. --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(249,115,22,0.12);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg></span><b>Where you are needed next</b></div>
            @php $next = $agenda->first(); @endphp
            @if($next)
                <p>{{ $next['title'] }}</p>
                <div class="mc-tt-row"><span class="k">Where</span><span class="v">{{ \Illuminate\Support\Str::limit($next['sub'] ?: 'Not given', 22) }}</span></div>
                <div class="mc-tt-row" style="border-top:1px solid var(--border-color);">
                    <span class="k">Starts</span>
                    <span class="v">{{ $next['all_day'] ? 'All day' : $next['start']?->format('g:i A') }}</span>
                </div>
                <div class="mc-tt-row" style="border-top:1px solid var(--border-color);">
                    <span class="k">Day</span><span class="v">{{ $next['start']?->format('D j M') }}</span>
                </div>
            @else
                <p>Nothing booked. Your next job appears here with its place and time.</p>
            @endif
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="mc-banner">
        <span class="clock">@include('professional.calendar._cal_clock', ['w' => 48])</span>
        {{-- "Win More" is a promise about somebody's business, which Sir
             Peter's rules on claims forbid. --}}
        <div class="mc-banner-txt"><b>Your days, in one place.</b><p>Shifts and bookings together, so one screen says where you are needed.</p></div>
        <a href="{{ route('professional.calendar.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Go to Full Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
