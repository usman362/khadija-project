@extends('layouts.client')

@section('title', 'Toolkit Tiers')
@section('page-title', 'Toolkit Tiers')
@section('page-subtitle', 'What each tier unlocks.')

@push('styles')
<style>
    .tt-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); }
    .tt-tab {
        padding: 11px 20px; font-size: 14px; font-weight: 600; color: var(--text-muted);
        border: 1px solid transparent; border-bottom: none; border-radius: 10px 10px 0 0;
        text-decoration: none; margin-bottom: -1px; background: transparent;
    }
    .tt-tab:hover { color: var(--text-primary); background: var(--bg-card-hover); }
    .tt-tab.on { color: var(--brand-text); background: var(--bg-card); border-color: var(--border-color); border-bottom-color: var(--bg-card); }
    .tt-tab .n { font-weight: 500; font-size: 12.5px; opacity: 0.75; margin-left: 5px; }
    .tt-panel { border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 12px 12px; background: var(--bg-card); }
    .tt-head { padding: 16px 18px; border-bottom: 1px solid var(--border-color); display: flex; gap: 14px; align-items: baseline; flex-wrap: wrap; }
    .tt-price { font-size: 22px; font-weight: 700; color: var(--text-primary); }
    .tt-price small { font-size: 13px; font-weight: 500; color: var(--text-muted); margin-left: 4px; }
    .tt-count { font-size: 13.5px; color: var(--text-muted); }
    .tt-locked { font-size: 12.5px; color: #b45309; background: rgba(245,158,11,0.12); padding: 3px 9px; border-radius: 999px; font-weight: 600; }
    .tt-scroll { overflow-x: auto; }
    .tt-table { width: 100%; border-collapse: collapse; min-width: 520px; }
    .tt-table th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-muted); font-weight: 700; padding: 12px 18px; border-bottom: 1px solid var(--border-color); }
    .tt-table td { padding: 13px 18px; border-bottom: 1px solid var(--border-color); font-size: 14px; vertical-align: top; }
    .tt-table tr:last-child td { border-bottom: none; }
    .tt-name { font-weight: 600; color: var(--text-primary); }
    .tt-purpose { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
    .tt-mark { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; white-space: nowrap; }
    .tt-in  { color: #059669; }
    .tt-out { color: var(--text-muted); }
    .tt-empty { padding: 34px 18px; text-align: center; color: var(--text-muted); font-size: 14px; }
    .tt-foot { padding: 14px 18px; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-color); }
</style>
@endpush

@section('content')

    <div class="tt-tabs">
        @foreach($tiers as $key => $label)
            <a href="{{ route('client.toolkit.tiers', ['tier' => $key]) }}"
               class="tt-tab {{ $tab === $key ? 'on' : '' }}"
               @if($tab === $key) aria-current="page" @endif>
                {{ $label }}<span class="n">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div class="tt-panel">
        <div class="tt-head">
            <div class="tt-price">
                @if($prices[$tab] > 0)
                    ${{ number_format($prices[$tab], 2) }}<small>one-time</small>
                @else
                    Free
                @endif
            </div>
            <div class="tt-count">
                {{ $counts[$tab] }} of {{ $rows->count() }} tools
            </div>
            {{-- Elite is offered Maximum only; Starter membership is Manual only. --}}
            @if(! in_array($tab, $purchasable, true))
                <span class="tt-locked">Not offered on your membership</span>
            @endif
        </div>

        @if($counts[$tab] === 0)
            {{-- Manual is a preset: always nothing, on both sides. Listing
                 twelve "Not included" rows would only make it look broken. --}}
            <div class="tt-empty">
                <b>Manual includes no tools.</b><br>
                Choose Semi or Maximum to unlock the toolkit.
            </div>
        @else
            <div class="tt-scroll">
                <table class="tt-table">
                    <thead>
                        <tr>
                            <th scope="col">Tool</th>
                            <th scope="col" style="width: 170px;">{{ $tiers[$tab] }}</th>
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
                                    @if($row['included'])
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
        @endif

        <div class="tt-foot">
            Toolkit access is a one-time purchase and stays with your account while it is open.
            Upgrading from Semi to Maximum pays the difference.
        </div>
    </div>

@endsection
