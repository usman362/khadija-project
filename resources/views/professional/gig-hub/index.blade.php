@extends('layouts.professional')

@section('title', 'Gig Operations Hub')
@section('page-title', 'Gig Operations Hub')
@section('page-subtitle', 'Everything for your event business, in one place.')

{{-- The one workspace a professional runs a gig from.
     Contracts, My Gigs and this page all showed the same jobs in different
     layouts, so they merged into tabs here (R42, amended 2026-08-05). Each
     tab is the old page's own body, unchanged — Sir Peter's condition was
     "a UI and workflow consolidation only, it does not change the ownership
     of data or calculations". --}}

@php
    $tabs = [
        // "kit" and "directions" named an inventory and a route that do not
        // exist; the cards drawing them were removed on 2026-11-01. A Blade
        // comment cannot go here: this is inside @php, and {{-- --}} is not
        // PHP.
        'overview'  => ['Overview',  'Where your gigs stand, and what needs doing.'],
        'gigs'      => ['My Gigs',   'Every gig, with the calendar and filters.'],
        'contracts' => ['Contracts', 'Paperwork, status and history.'],
    ];
@endphp

@push('styles')
<style>
    .goh-tabs { display: flex; align-items: flex-end; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 6px; }
    .goh-tab {
        padding: 9px 16px; font-size: 13px; font-weight: 700; color: var(--text-muted);
        border: 1px solid transparent; border-bottom: none; border-radius: 9px 9px 0 0;
        text-decoration: none; margin-bottom: -1px; white-space: nowrap;
    }
    .goh-tab:hover { color: var(--text-primary); background: var(--bg-card-hover); }
    .goh-tab.on { color: var(--brand-text); background: var(--bg-card); border-color: var(--border-color); border-bottom-color: var(--bg-card); }
    .goh-tabhint { font-size: 12px; color: var(--text-muted); margin: 10px 0 14px; }

    /* The button that starts something new, at the end of the tab row. */
    .goh-new { position: relative; margin: 0 0 6px auto; }
    .goh-new > summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center;
        gap: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 800;
        padding: 9px 16px; border-radius: 9px; white-space: nowrap; }
    .goh-new > summary::-webkit-details-marker { display: none; }
    .goh-new ul { position: absolute; right: 0; top: calc(100% + 7px); z-index: 30; min-width: 212px;
        margin: 0; padding: 6px; list-style: none; background: var(--bg-card);
        border: 1px solid var(--border-color); border-radius: 11px; box-shadow: 0 16px 40px rgba(0,0,0,.2); }
    .goh-new li a { display: block; padding: 9px 11px; border-radius: 8px; font-size: 12.5px;
        font-weight: 600; color: var(--text-primary); text-decoration: none; }
    .goh-new li a:hover { background: var(--bg-card-hover, rgba(128,128,128,.1)); }

    /* Today. Above the tabs because it belongs to the whole page, and
       because at nine in the morning it is the only part that matters. */
    .goh-today { background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 14px; padding: 16px 18px; margin-bottom: 14px; }
    .goh-today-h { display: flex; align-items: baseline; gap: 9px; margin: 0 0 12px; }
    .goh-today-h b { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .goh-today-h span { font-size: 12px; color: var(--text-muted); }
    .goh-live { display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        padding: 12px 0; border-top: 1px solid var(--border-color); }
    .goh-live:first-of-type { border-top: 0; padding-top: 0; }
    .goh-live-main { flex: 1 1 240px; min-width: 0; }
    .goh-live-main b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .goh-live-main span { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .goh-live-state { font-size: 11.5px; color: var(--text-muted); flex: none; }
    .goh-live-btn { flex: none; border: 0; cursor: pointer; font-size: 12.5px; font-weight: 800;
        padding: 9px 15px; border-radius: 9px; background: var(--brand-text, #ea580c); color: #fff; }
    .goh-live-undo { flex: none; background: none; border: 0; cursor: pointer;
        font-size: 11.5px; font-weight: 700; color: var(--text-muted); text-decoration: underline; }
</style>
@endpush

@section('content')

    {{-- Today's gigs, and the one action that belongs to the day itself. --}}
    @if(($liveToday ?? collect())->isNotEmpty())
        <div class="goh-today">
            <p class="goh-today-h">
                <b>Happening today</b>
                <span>{{ $liveToday->count() }} {{ \Illuminate\Support\Str::plural('gig', $liveToday->count()) }}</span>
            </p>

            @foreach($liveToday as $live)
                <div class="goh-live">
                    <span class="goh-live-main">
                        <b>{{ $live->event?->title ?? 'Untitled event' }}</b>
                        <span>
                            {{ $live->client?->name ?? 'Client' }}
                            @if($live->event?->starts_at) · {{ $live->event->starts_at->format('g:i A') }} @endif
                            @if($live->event?->location) · {{ $live->event->location }} @endif
                        </span>
                    </span>

                    {{-- Says only what was recorded, and who recorded it. --}}
                    <span class="goh-live-state">{{ $live->checkInLine() }}</span>

                    @if($live->hasCheckedIn(auth()->user()))
                        <form method="POST" action="{{ route('bookings.check-in.undo', $live) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="goh-live-undo">Undo check-in</button>
                        </form>
                    @elseif($live->canCheckIn(auth()->user()))
                        <form method="POST" action="{{ route('bookings.check-in', $live) }}">
                            @csrf
                            <button type="submit" class="goh-live-btn">I am on site</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="goh-tabs">
        @foreach($tabs as $key => [$label, $hint])
            <a href="{{ route('professional.gig-hub.index', $key === 'overview' ? [] : ['tab' => $key]) }}"
               class="goh-tab {{ $tab === $key ? 'on' : '' }}"
               @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach

        <details class="goh-new">
            <summary>+ Create New</summary>
            <ul>
                <li><a href="{{ route('professional.packages.create') }}">A package</a></li>
                <li><a href="{{ route('professional.bidding-board.index') }}">A bid on an open request</a></li>
                <li><a href="{{ route('professional.availability.index') }}">A block in your calendar</a></li>
                <li><a href="{{ route('professional.team.index') }}">A shift for your crew</a></li>
            </ul>
        </details>
    </div>

    <div class="goh-tabhint">{{ $tabs[$tab][1] }}</div>

    @include('professional.gig-hub.tabs.' . $tab)

    <script>
        // One menu open at a time, and clicking away closes it.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.goh-new[open]').forEach(function (d) {
                if (!d.contains(e.target)) { d.removeAttribute('open'); }
            });
        });
    </script>
@endsection
