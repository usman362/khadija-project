@extends('layouts.professional')

@section('title', 'My Clients')
@section('page-title', 'My Clients')

{{-- Who has hired this professional, one row per client rather than per
     booking. The client's own Hired Talent page groups by event, because they
     plan one event and hire several people for it. A professional works one
     event at a time, so the thing worth seeing here is the repeat.

     Every figure comes from bookings that already exist. Nothing is forecast:
     see App\Domain\WhoHiredMe for what was deliberately left out and why. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 2);
@endphp

@push('styles')
<style>
    .mc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); }

    .mc-intro { padding: 22px 24px; margin-bottom: 16px; }
    .mc-intro h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .mc-intro p { font-size: 14px; color: var(--text-secondary); margin: 6px 0 0; max-width: 62ch; line-height: 1.6; }

    .mc-sums { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 18px; }
    .mc-sum { padding: 16px 18px; }
    .mc-sum .k { font-size: 11.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: var(--text-muted); }
    .mc-sum .v { font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1.15; margin-top: 6px; }
    .mc-sum .n { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

    .mc-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .mc-tab { display: inline-block; padding: 8px 15px; border-radius: 999px; font-size: 13px; font-weight: 700;
        text-decoration: none; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); }
    .mc-tab.on { border-color: var(--info-text); color: var(--info-text); }

    .mc-row { padding: 18px 22px; margin-bottom: 12px; display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto; gap: 18px; align-items: start; }
    .mc-who { display: flex; gap: 13px; align-items: flex-start; min-width: 0; }
    .mc-av { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .mc-name { font-size: 15px; font-weight: 700; color: var(--text-primary); }
    .mc-meta { font-size: 12.5px; color: var(--text-muted); margin-top: 3px; line-height: 1.5; }
    .mc-back { display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 999px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase;
        background: rgba(16,185,129,0.12); color: var(--ok-text); vertical-align: middle; }

    .mc-k { font-size: 11px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; color: var(--text-muted); }
    .mc-v { font-size: 13.5px; color: var(--text-primary); margin-top: 3px; line-height: 1.5; }
    .mc-v.quiet { color: var(--text-muted); }
    .mc-v b { font-weight: 700; }

    .mc-acts { display: flex; flex-direction: column; gap: 8px; align-items: stretch; }
    .mc-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: nowrap;
        padding: 8px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; font-weight: 700; text-decoration: none; }
    .mc-btn:hover { border-color: var(--border-glow); color: var(--text-primary); }
    .mc-btn svg { width: 13px; height: 13px; }

    .mc-none { padding: 42px 24px; text-align: center; }
    .mc-none h2 { font-size: 17px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
    .mc-none p { font-size: 14px; color: var(--text-muted); margin: 0 auto 18px; max-width: 46ch; line-height: 1.6; }

    @media (max-width: 900px) {
        .mc-row { grid-template-columns: 1fr; }
        .mc-acts { flex-direction: row; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
<div class="mc-card mc-intro">
    <h1>My Clients</h1>
    <p>
        Everyone who has booked you, and what you have done for them. A client who has hired you
        before is the shortest route to the next job, so this is sorted by who you worked for most
        recently and marks the ones who came back.
    </p>
</div>

<div class="mc-sums">
    <div class="mc-card mc-sum">
        <div class="k">Clients</div>
        <div class="v">{{ $summary['clients'] }}</div>
        <div class="n">Have booked you at least once</div>
    </div>
    <div class="mc-card mc-sum">
        <div class="k">Came back</div>
        <div class="v">{{ $summary['repeat'] }}</div>
        <div class="n">Booked you more than once</div>
    </div>
    <div class="mc-card mc-sum">
        <div class="k">Still ahead</div>
        <div class="v">{{ $summary['upcoming'] }}</div>
        <div class="n">Bookings not yet finished</div>
    </div>
    <div class="mc-card mc-sum">
        <div class="k">Earned from them</div>
        <div class="v">{{ $money($summary['earned']) }}</div>
        {{-- Said plainly, because the same booking is a larger number on the
             client's own screen and the difference is the commission. --}}
        <div class="n">Completed work, after commission</div>
    </div>
</div>

<div class="mc-tabs">
    <a class="mc-tab {{ $show === 'all' ? 'on' : '' }}" href="{{ route('professional.clients.index') }}">Everyone</a>
    <a class="mc-tab {{ $show === 'repeat' ? 'on' : '' }}" href="{{ route('professional.clients.index', ['show' => 'repeat']) }}">Came back</a>
    <a class="mc-tab {{ $show === 'upcoming' ? 'on' : '' }}" href="{{ route('professional.clients.index', ['show' => 'upcoming']) }}">Work ahead</a>
</div>

@forelse($rows as $r)
    <div class="mc-card mc-row">
        <div class="mc-who">
            <img class="mc-av" src="{{ $r['client']['avatar'] }}" alt="">
            <div style="min-width:0;">
                <div class="mc-name">
                    {{ $r['client']['name'] ?? 'A client' }}
                    @if($r['returning'])<span class="mc-back">Came back</span>@endif
                </div>
                <div class="mc-meta">
                    @if($r['client']['ref']){{ $r['client']['ref'] }}@endif
                    @if($r['client']['where']) &middot; {{ $r['client']['where'] }}@endif
                    <br>
                    {{-- Built as one string: Blade ignores a directive that
                         follows a word character, so "completed@endif" is
                         printed rather than compiled. --}}
                    @php
                        $counts = [$r['bookings'] . ' ' . ($r['bookings'] === 1 ? 'booking' : 'bookings')];
                        if ($r['completed']) { $counts[] = $r['completed'] . ' completed'; }
                        if ($r['cancelled']) { $counts[] = $r['cancelled'] . ' cancelled'; }
                    @endphp
                    {{ implode(', ', $counts) }}
                    @if($r['services'])<br>{{ implode(', ', $r['services']) }}@endif
                </div>
            </div>
        </div>

        <div>
            <div class="mc-k">Last worked</div>
            @if($r['last'])
                <div class="mc-v">
                    <b>{{ $r['last']['title'] ?? 'An event' }}</b><br>
                    {{ $r['last']['at']?->format('M j, Y') }}
                    @if($r['last']['to_review'])
                        <br><span class="mc-v quiet">They have not reviewed this one yet.</span>
                    @endif
                </div>
            @else
                <div class="mc-v quiet">Nothing finished yet</div>
            @endif
        </div>

        <div>
            <div class="mc-k">Coming up</div>
            @if($r['next'])
                <div class="mc-v">
                    <b>{{ $r['next']['title'] ?? 'An event' }}</b><br>
                    {{ $r['next']['at']?->format('M j, Y') }}
                </div>
            @else
                <div class="mc-v quiet">Nothing booked</div>
            @endif
            <div class="mc-k" style="margin-top:12px;">Earned</div>
            <div class="mc-v">{{ $money($r['earned']) }}</div>
        </div>

        <div class="mc-acts">
            {{-- Straight into the conversation they already have. Sending
                 somebody to the message centre to search for a name they are
                 looking at is a step nobody needed. --}}
            <a class="mc-btn" href="{{ $r['conversation']
                ? route('professional.chat.show', $r['conversation'])
                : route('professional.chat.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                {{ $r['conversation'] ? 'Open messages' : 'Start a message' }}
            </a>
            @if($r['next'])
                <a class="mc-btn" href="{{ route('professional.gigs.show', $r['next']['id']) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Open the gig
                </a>
            @endif
        </div>
    </div>
@empty
    <div class="mc-card mc-none">
        <h2>
            @if($show === 'repeat')
                Nobody has booked you twice yet
            @elseif($show === 'upcoming')
                No work ahead with past clients
            @else
                Nobody has hired you yet
            @endif
        </h2>
        <p>
            @if($show === 'all')
                Once a client books you, they appear here with everything you have done for them.
            @else
                They will appear here as soon as that happens.
            @endif
        </p>
        <a class="mc-btn" style="display:inline-flex;" href="{{ route('professional.bidding-board.index') }}">Open the bidding board</a>
    </div>
@endforelse
@endsection
