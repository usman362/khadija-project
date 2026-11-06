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
</style>
@endpush

@section('content')

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
