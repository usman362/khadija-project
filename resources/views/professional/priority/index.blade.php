@extends('layouts.professional')

@section('title', 'Priority Actions')
@section('page-title', 'Priority Actions')

{{-- Khadijah's 28 October screen: one list of the things actually waiting on
     this professional, with tabs across the top and a right hand column for
     today, the deadlines coming, and the last thirty days.

     What this replaced was four counters. "3 contracts" tells somebody three
     contracts need signing without telling them which three, so every row here
     names its own record and carries the button that opens it. --}}

@php
    $tones = [
        'urgent' => ['Urgent',   '#ef4444'],
        'soon'   => ['Due soon', '#f59e0b'],
        'new'    => ['New',      '#2563eb'],
        'open'   => ['Open',     '#64748b'],
    ];
    $money = fn ($v) => '$' . number_format((float) $v, 2);
@endphp

@push('styles')
<style>
    .pa-layout { display: grid; grid-template-columns: minmax(0,1fr) 330px; gap: 20px; align-items: start; }
    .pa-head { display: flex; align-items: center; gap: 13px; margin-bottom: 16px; }
    .pa-head .ic { width: 42px; height: 42px; border-radius: 12px; flex: none;
        background: var(--accent-blue, #2563eb); color: #fff; display: flex; align-items: center; justify-content: center; }
    .pa-head .ic svg { width: 21px; height: 21px; }
    .pa-head h1 { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pa-head p { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }

    .pa-tabs { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 16px; }
    .pa-tab { padding: 8px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 700;
        text-decoration: none; color: var(--text-secondary, var(--text-muted));
        border: 1px solid var(--border-color); background: var(--bg-card); white-space: nowrap; }
    .pa-tab.on { background: var(--text-primary); color: var(--bg-card); border-color: var(--text-primary); }
    .pa-tab .n { opacity: .65; margin-left: 4px; }

    .pa-item { display: grid; grid-template-columns: 86px minmax(0,1fr) auto; gap: 14px; align-items: center;
        background: var(--bg-card); border: 1px solid var(--border-color); border-left-width: 3px;
        border-radius: 12px; padding: 14px 16px; margin-bottom: 11px; }
    .pa-badge { font-size: 9.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase;
        padding: 3px 8px; border-radius: 5px; color: #fff; display: inline-block; }
    .pa-kind { display: block; font-size: 10.5px; color: var(--text-muted); margin-top: 5px; font-weight: 700; }
    .pa-item b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .pa-subject { font-size: 12.5px; color: var(--text-secondary, var(--text-muted)); margin-top: 2px; }
    .pa-detail { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .pa-when { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
    .pa-go { font-size: 12.5px; font-weight: 700; padding: 9px 15px; border-radius: 9px; text-decoration: none;
        background: var(--accent-blue, #2563eb); color: #fff; white-space: nowrap; }

    .pa-empty { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;
        padding: 30px 20px; text-align: center; }
    .pa-empty b { display: block; font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .pa-empty span { font-size: 13px; color: var(--text-muted); }

    .pa-rail { display: flex; flex-direction: column; gap: 15px; position: sticky; top: 20px; }
    .pa-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px 16px; }
    .pa-card h4 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 11px; }
    .pa-row { display: flex; gap: 10px; align-items: baseline; padding: 7px 0; border-top: 1px solid var(--border-color); }
    .pa-row:first-of-type { border-top: 0; }
    .pa-row .t { font-size: 11.5px; font-weight: 800; color: var(--accent-blue, #2563eb); white-space: nowrap; flex: none; }
    .pa-row .m { min-width: 0; }
    .pa-row .m b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .pa-row .m span { font-size: 11px; color: var(--text-muted); }
    .pa-none { font-size: 12.5px; color: var(--text-muted); }

    .pa-figs { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; }
    .pa-fig b { display: block; font-size: 17px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .pa-fig span { font-size: 10.5px; color: var(--text-muted); }

    @media (max-width: 1100px) { .pa-layout { grid-template-columns: 1fr; } .pa-rail { position: static; } }
    @media (max-width: 620px) { .pa-item { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pa-head">
    <span class="ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
    </span>
    <div>
        <h1>Priority Actions</h1>
        <p>
            @if ($total === 0)
                Nothing is waiting on you right now.
            @else
                {{ $total }} {{ \Illuminate\Support\Str::plural('thing', $total) }} waiting on you.
            @endif
        </p>
    </div>
</div>

<div class="pa-layout">
    <div>
        <div class="pa-tabs">
            @foreach ($tabs as $key => $meta)
                <a class="pa-tab {{ $tab === $key ? 'on' : '' }}"
                   href="{{ route('professional.priority.index', $key === 'all' ? [] : ['tab' => $key]) }}">
                    {{ $meta['label'] }}<span class="n">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        @forelse ($items as $item)
            @php [$toneLabel, $toneColour] = $tones[$item['tone']] ?? $tones['open']; @endphp
            <div class="pa-item" style="border-left-color: {{ $toneColour }};">
                <div>
                    <span class="pa-badge" style="background: {{ $toneColour }};">{{ $toneLabel }}</span>
                    <span class="pa-kind">{{ $item['kind'] }}</span>
                </div>
                <div>
                    <b>{{ $item['title'] }}</b>
                    @if ($item['subject'])
                        <div class="pa-subject">{{ $item['subject'] }}</div>
                    @endif
                    @if ($item['detail'])
                        <div class="pa-detail">{{ $item['detail'] }}</div>
                    @endif
                    @if ($item['when'])
                        <div class="pa-when">{{ $item['when'] }}</div>
                    @endif
                </div>
                <a class="pa-go" href="{{ $item['url'] }}">{{ $item['cta'] }}</a>
            </div>
        @empty
            <div class="pa-empty">
                <b>Nothing here</b>
                <span>
                    @if ($tab === 'all')
                        When a client signs an agreement, sends a request or a deadline comes close, it appears on this list.
                    @else
                        Nothing in this group right now. Try All.
                    @endif
                </span>
            </div>
        @endforelse
    </div>

    <aside class="pa-rail">
        <div class="pa-card">
            <h4>Your schedule today</h4>
            @forelse ($today as $slot)
                <div class="pa-row">
                    <span class="t">{{ $slot['at']->timezone(config('app.display_timezone'))->format('g:i A') }}</span>
                    <span class="m">
                        <b>{{ $slot['title'] }}</b>
                        @if ($slot['where']) <span>{{ $slot['where'] }}</span> @endif
                    </span>
                </div>
            @empty
                <div class="pa-none">Nothing booked for today.</div>
            @endforelse
        </div>

        <div class="pa-card">
            <h4>Upcoming deadlines</h4>
            @forelse ($deadlines as $item)
                <div class="pa-row">
                    <span class="m">
                        <b>{{ $item['title'] }}</b>
                        <span>{{ $item['when'] ?? $item['due']->timezone(config('app.display_timezone'))->format('j M Y') }}</span>
                    </span>
                </div>
            @empty
                <div class="pa-none">Nothing with a date on it.</div>
            @endforelse
        </div>

        <div class="pa-card">
            <h4>Your last 30 days</h4>
            <div class="pa-figs">
                <div class="pa-fig"><b>{{ $snapshot['requests'] }}</b><span>Requests received</span></div>
                <div class="pa-fig"><b>{{ $snapshot['bids'] }}</b><span>Bids placed</span></div>
                <div class="pa-fig"><b>{{ $snapshot['won'] }}</b><span>Bookings won</span></div>
                <div class="pa-fig"><b>{{ $money($snapshot['earned']) }}</b><span>Earned, after fees</span></div>
            </div>
        </div>
    </aside>
</div>
@endsection
