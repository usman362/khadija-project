@extends('layouts.professional')

@section('title', 'Gig Operations Hub')
@section('page-title', 'Gig Operations Hub')
@section('page-subtitle', 'Everything for a gig, in one place.')

{{-- The one workspace a professional runs a gig from.
     Contracts, My Gigs and this page all showed the same jobs in different
     layouts, so they merged into tabs here (R42, amended 2026-08-05). Each
     tab is the old page's own body, unchanged — Sir Peter's condition was
     "a UI and workflow consolidation only, it does not change the ownership
     of data or calculations". --}}

@php
    $tabs = [
        'overview'  => ['Overview',  'The gig itself — crew, kit, directions, signing.'],
        'gigs'      => ['My Gigs',   'Every gig, with the calendar and filters.'],
        'contracts' => ['Contracts', 'Paperwork, status and history.'],
    ];
@endphp

@push('styles')
<style>
    .goh-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 6px; overflow-x: auto; }
    .goh-tab {
        padding: 11px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted);
        border: 1px solid transparent; border-bottom: none; border-radius: 10px 10px 0 0;
        text-decoration: none; margin-bottom: -1px; white-space: nowrap;
    }
    .goh-tab:hover { color: var(--text-primary); background: var(--bg-card-hover); }
    .goh-tab.on { color: var(--brand-text); background: var(--bg-card); border-color: var(--border-color); border-bottom-color: var(--bg-card); }
    .goh-tabhint { font-size: 13px; color: var(--text-muted); margin: 12px 0 20px; }
</style>
@endpush

@section('content')

    <div class="goh-tabs">
        @foreach($tabs as $key => [$label, $hint])
            <a href="{{ route('professional.gig-hub.index', $key === 'overview' ? [] : ['tab' => $key]) }}"
               class="goh-tab {{ $tab === $key ? 'on' : '' }}"
               @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    <div class="goh-tabhint">{{ $tabs[$tab][1] }}</div>

    @include('professional.gig-hub.tabs.' . $tab)

@endsection
