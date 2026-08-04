@extends('layouts.client')

@section('title', 'Toolkit Add-Ons')
@section('page-title', 'Toolkit Add-Ons')
@section('page-subtitle', 'What each paid tool level unlocks.')

@push('styles')
<style>
    .tt-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 0; }
    .tt-tab {
        padding: 11px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted);
        border: 1px solid transparent; border-bottom: none; border-radius: 10px 10px 0 0;
        text-decoration: none; margin-bottom: -1px; background: transparent;
    }
    .tt-tab:hover { color: var(--text-primary); background: var(--bg-card-hover); }
    .tt-tab.on {
        color: var(--brand-text); background: var(--bg-card);
        border-color: var(--border-color); border-bottom-color: var(--bg-card);
    }
    .tt-panel { border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 12px 12px; background: var(--bg-card); }
    .tt-scroll { overflow-x: auto; }
    .tt-table { width: 100%; border-collapse: collapse; min-width: 520px; }
    .tt-table th {
        text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--text-muted); font-weight: 700; padding: 13px 18px;
        border-bottom: 1px solid var(--border-color);
    }
    .tt-table td { padding: 14px 18px; border-bottom: 1px solid var(--border-color); font-size: 14px; vertical-align: top; }
    .tt-table tr:last-child td { border-bottom: none; }
    .tt-name { font-weight: 600; color: var(--text-primary); }
    .tt-purpose { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
    .tt-mark { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; white-space: nowrap; }
    .tt-in  { color: #059669; }
    .tt-out { color: var(--text-muted); }
    .tt-pending { color: #b45309; }
    .tt-note { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .tt-banner {
        display: flex; gap: 12px; align-items: flex-start;
        background: rgba(245,158,11,0.09); border-left: 3px solid #f59e0b;
        padding: 13px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13.5px;
    }
    .tt-foot { padding: 14px 18px; font-size: 13px; color: var(--text-muted); }
</style>
@endpush

@section('content')

    {{-- Rule R31 says the tier breakdown must come from Peter. Until every row
         is confirmed, the page says so rather than presenting a proposal to a
         paying client as if it were settled. --}}
    @if($unconfirmed->isNotEmpty())
        <div class="tt-banner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <b>{{ $unconfirmed->count() }} of {{ $unconfirmed->count() + 1 }} tools are still being finalised.</b>
                Rows marked <span class="tt-pending">Being finalised</span> are not confirmed yet, so please don't rely on them when choosing an add-on.
            </div>
        </div>
    @endif

    <div class="tt-tabs">
        @foreach($tiers as $key => $label)
            <a href="{{ route('client.toolkit.tiers', ['tier' => $key]) }}"
               class="tt-tab {{ $tab === $key ? 'on' : '' }}"
               @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>

    <div class="tt-panel">
        <div class="tt-scroll">
            <table class="tt-table">
                <thead>
                    <tr>
                        <th scope="col">Tool</th>
                        <th scope="col" style="width: 190px;">{{ $tiers[$tab] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>
                                <div class="tt-name">{{ $row['title'] }}</div>
                                @if($row['purpose'])
                                    <div class="tt-purpose">{{ $row['purpose'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if(! $row['confirmed'])
                                    <span class="tt-mark tt-pending">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Being finalised
                                    </span>
                                @elseif($row['included'])
                                    <span class="tt-mark tt-in">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Included
                                    </span>
                                @else
                                    <span class="tt-mark tt-out">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Not included
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tt-foot">
            Only the tools included in your paid add-on appear in your toolkit.
            The Manual level has no toolkit access.
        </div>
    </div>

@endsection
