@extends('layouts.client')

@section('title', 'Hired Talent')

@push('styles')
<style>
    .ht { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; align-items: start; }
    .ht-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; }
    .ht-pad { padding: 16px 18px; }

    /* Head */
    .ht-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
    .ht-head-ico { width: 54px; height: 54px; border-radius: 15px; background: rgba(249,115,22,.12);
        display: flex; align-items: center; justify-content: center; flex: none; }
    .ht-head-ico svg { width: 27px; height: 27px; color: var(--accent-orange, #ea580c); }
    .ht-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .ht-head p { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }

    /* Left column */
    .ht-filters { padding: 14px 14px 10px; border-bottom: 1px solid var(--border-color); }
    .ht-search { position: relative; margin-bottom: 10px; }
    .ht-search svg { position: absolute; left: 11px; top: 11px; width: 15px; height: 15px; color: var(--text-muted); }
    .ht-search input { width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 9px 11px 9px 33px; font: inherit; font-size: 13px; color: var(--text-primary); background: var(--bg-card); }
    .ht-search input:focus { outline: none; border-color: var(--accent-orange, #ea580c); }

    .ht-pills { display: flex; flex-wrap: wrap; gap: 6px; }
    .ht-pill { font-size: 11.5px; font-weight: 700; padding: 6px 11px; border-radius: 999px;
        border: 1px solid var(--border-color); color: var(--text-secondary); text-decoration: none; white-space: nowrap; }
    .ht-pill.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.1); color: var(--brand-text, #c2410c); }
    .ht-pill.is-empty { opacity: .55; }

    .ht-list { max-height: 640px; overflow-y: auto; }
    .ht-ev { padding: 11px 14px 4px; }
    .ht-ev-d { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .ht-ev-d svg { width: 14px; height: 14px; color: var(--accent-orange, #ea580c); flex: none; }
    .ht-ev-t { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0 22px; }

    .ht-row { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left;
        padding: 9px 14px; border: 0; background: none; cursor: pointer; font: inherit; border-left: 3px solid transparent; }
    .ht-row:hover { background: var(--bg-card-hover, #f1f5f9); }
    .ht-row.is-on { background: rgba(249,115,22,.07); border-left-color: var(--accent-orange, #ea580c); }
    .ht-av { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; flex: none; }
    .ht-row-mid { min-width: 0; flex: 1; }
    .ht-row-n { font-size: 13px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ht-row-s { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ht-chip { font-size: 10.5px; font-weight: 800; padding: 3px 8px; border-radius: 999px; white-space: nowrap; }
    .ht-chip.ok   { background: rgba(16,185,129,.12); color: var(--ok-text, #047857); }
    .ht-chip.wait { background: rgba(249,115,22,.12); color: var(--brand-text, #c2410c); }
    .ht-chip.done { background: rgba(37,99,235,.10); color: var(--info-text, #1d4ed8); }
    .ht-chip.off  { background: var(--bg-card-hover, #f1f5f9); color: var(--text-muted); }

    /* Right column */
    .ht-detail-h { display: flex; align-items: flex-start; gap: 16px; padding: 20px; border-bottom: 1px solid var(--border-color); }
    .ht-detail-av { width: 76px; height: 76px; border-radius: 50%; object-fit: cover; flex: none; }
    .ht-detail-n { font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .ht-detail-sub { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }
    .ht-detail-sp { flex: 1; }

    .ht-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 18px 20px; }
    .ht-fact-l { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); }
    .ht-fact-v { font-size: 13.5px; font-weight: 600; color: var(--text-primary); margin-top: 3px; line-height: 1.45; }
    .ht-fact-v .dim { color: var(--text-muted); font-weight: 500; }

    .ht-money { margin: 0 20px 18px; border: 1px solid var(--border-color); border-radius: 13px; padding: 15px 17px; }
    .ht-money-h { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); margin-bottom: 9px; }
    .ht-money-row { display: flex; gap: 20px; flex-wrap: wrap; }
    .ht-money-n { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .ht-money-n.due { color: var(--accent-orange, #ea580c); }
    .ht-money-k { font-size: 11px; color: var(--text-muted); }

    .ht-acts { display: flex; flex-wrap: wrap; gap: 9px; padding: 0 20px 20px; }
    .ht-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 10px;
        border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary);
        font-size: 12.5px; font-weight: 700; text-decoration: none; }
    .ht-btn:hover { border-color: var(--accent-orange, #ea580c); }
    .ht-btn.go { background: var(--accent-orange, #ea580c); border-color: transparent; color: #fff; }

    .ht-none { padding: 40px 24px; text-align: center; }
    .ht-none p { font-size: 13.5px; color: var(--text-muted); margin: 0 0 4px; line-height: 1.6; }
    .ht-none b { color: var(--text-primary); }

    .ht-detail { display: none; }
    .ht-detail.is-on { display: block; }

    @media (max-width: 1000px) { .ht { grid-template-columns: 1fr; } .ht-list { max-height: none; } .ht-facts { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
@php
    $fmtMoney = fn ($n) => $n === null ? null : '$' . number_format((float) $n, 2);
@endphp

<div class="ht-head">
    <span class="ht-head-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
    </span>
    <div>
        <h1>Hired Talent</h1>
        <p>Everyone you have booked, by event, with where the money stands.</p>
    </div>
</div>

<div class="ht">
    {{-- ── Who you hired ─────────────────────────────── --}}
    <div class="ht-card">
        <div class="ht-filters">
            <form method="GET" class="ht-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search a name, event or service…" autocomplete="off">
                @if($group !== 'all')<input type="hidden" name="who" value="{{ $group }}">@endif
                @if($state !== 'all')<input type="hidden" name="show" value="{{ $state }}">@endif
            </form>

            @php
                $link = fn (array $over) => route('client.hired-talent.index', array_filter(
                    array_merge(['who' => $group, 'show' => $state, 'q' => $q], $over),
                    fn ($v) => $v !== null && $v !== '' && $v !== 'all',
                ));
            @endphp

            <div class="ht-pills" style="margin-bottom:6px;">
                <a href="{{ $link(['who' => 'all']) }}" class="ht-pill {{ $group === 'all' ? 'is-on' : '' }}">Everyone</a>
                <a href="{{ $link(['who' => 'professionals']) }}"
                   class="ht-pill {{ $group === 'professionals' ? 'is-on' : '' }} {{ $kinds['professionals'] === 0 ? 'is-empty' : '' }}">Professionals ({{ $kinds['professionals'] }})</a>
                {{-- Shown at zero rather than hidden: "nobody yet" is a real
                     answer, and the influencer side is not open to hire from. --}}
                <a href="{{ $link(['who' => 'influencers']) }}"
                   class="ht-pill {{ $group === 'influencers' ? 'is-on' : '' }} {{ $kinds['influencers'] === 0 ? 'is-empty' : '' }}">Influencers ({{ $kinds['influencers'] }})</a>
            </div>
            <div class="ht-pills">
                <a href="{{ $link(['show' => 'all']) }}" class="ht-pill {{ $state === 'all' ? 'is-on' : '' }}">All {{ $counts['all'] }}</a>
                <a href="{{ $link(['show' => 'active']) }}" class="ht-pill {{ $state === 'active' ? 'is-on' : '' }}">Active {{ $counts['active'] }}</a>
                <a href="{{ $link(['show' => 'past']) }}" class="ht-pill {{ $state === 'past' ? 'is-on' : '' }}">Past {{ $counts['past'] }}</a>
            </div>
        </div>

        <div class="ht-list">
            @forelse($groups as $g)
                <div class="ht-ev">
                    <div class="ht-ev-d">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        {{ $g['event']['at']?->format('F j, Y') ?? 'Date not set' }}
                    </div>
                    <div class="ht-ev-t">{{ $g['event']['title'] ?? 'Untitled event' }}</div>
                </div>
                @foreach($g['rows'] as $r)
                    <button type="button" class="ht-row" data-ht-row="{{ $r['id'] }}">
                        <img class="ht-av" src="{{ $r['supplier']['avatar'] }}" alt="">
                        <span class="ht-row-mid">
                            <span class="ht-row-n">{{ $r['supplier']['name'] }}</span>
                            <span class="ht-row-s">{{ $r['service'] ?? $r['supplier']['headline'] ?? 'Service not recorded' }}</span>
                        </span>
                        <span class="ht-chip {{ $r['tone'] }}">{{ $r['state'] }}</span>
                    </button>
                @endforeach
            @empty
                <div class="ht-none">
                    @if($counts['all'] === 0)
                        <p><b>You have not hired anyone yet.</b></p>
                        <p>Once you accept a proposal, whoever you booked appears here with their event and payments.</p>
                    @elseif($group === 'influencers')
                        <p><b>No influencers yet.</b></p>
                        <p>Influencers cannot be hired through the site yet. When that opens, the ones you book will show up here beside your professionals.</p>
                    @elseif($q !== '')
                        <p><b>Nothing matches that.</b></p>
                        <p>Try a different search, or <a href="{{ route('client.hired-talent.index') }}" style="font-weight:700;">show everyone</a>.</p>
                    @elseif($state === 'active')
                        {{-- Not the same as a search finding nothing: every
                             booking they have is finished, which is good news
                             rather than an empty result. --}}
                        <p><b>Nothing on right now.</b></p>
                        <p>All {{ $counts['past'] }} of your bookings are finished. <a href="{{ $link(['show' => 'past']) }}" style="font-weight:700;">See the past ones</a>.</p>
                    @elseif($state === 'past')
                        <p><b>Nothing finished yet.</b></p>
                        <p>Bookings move here once the work is done or cancelled.</p>
                    @else
                        <p><b>Nothing matches that.</b></p>
                        <p>Try <a href="{{ route('client.hired-talent.index') }}" style="font-weight:700;">showing everyone</a>.</p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── The one you picked ────────────────────────── --}}
    <div class="ht-card" style="min-height:220px;">
        @if($shown === 0)
            <div class="ht-none">
                <p>Nothing to show on this side yet.</p>
            </div>
        @else
            <div class="ht-none" data-ht-empty>
                <p>Pick somebody on the left to see their event, their booking and their payments.</p>
            </div>
        @endif

        @foreach($groups as $g)
            @foreach($g['rows'] as $r)
                <div class="ht-detail" data-ht-detail="{{ $r['id'] }}">
                    <div class="ht-detail-h">
                        <img class="ht-detail-av" src="{{ $r['supplier']['avatar'] }}" alt="">
                        <div>
                            <h2 class="ht-detail-n">{{ $r['supplier']['name'] }}</h2>
                            <p class="ht-detail-sub">{{ $r['service'] ?? $r['supplier']['headline'] ?? 'Service not recorded' }}</p>
                        </div>
                        <span class="ht-detail-sp"></span>
                        <span class="ht-chip {{ $r['tone'] }}">{{ $r['state'] }}</span>
                    </div>

                    <div class="ht-facts">
                        <div>
                            <div class="ht-fact-l">Event</div>
                            <div class="ht-fact-v">{{ $r['event']['title'] ?? 'Untitled event' }}</div>
                        </div>
                        <div>
                            <div class="ht-fact-l">When</div>
                            <div class="ht-fact-v">
                                @if($r['event']['at'])
                                    {{ $r['event']['at']->format('l, F j, Y') }}<br>
                                    <span class="dim">{{ $r['event']['at']->format('g:i A') }}@if($r['event']['ends']) &ndash; {{ $r['event']['ends']->format('g:i A') }}@endif</span>
                                @else
                                    <span class="dim">Not set</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="ht-fact-l">Where</div>
                            <div class="ht-fact-v">{!! $r['event']['where'] ?: '<span class="dim">Not recorded</span>' !!}</div>
                        </div>
                    </div>

                    <div class="ht-money">
                        <div class="ht-money-h">Payment</div>
                        @if($r['money'])
                            <div class="ht-money-row">
                                <div>
                                    <div class="ht-money-n">{{ $fmtMoney($r['money']['total']) ?? '—' }}</div>
                                    <div class="ht-money-k">Agreed</div>
                                </div>
                                <div>
                                    <div class="ht-money-n">{{ $fmtMoney($r['money']['paid']) }}</div>
                                    <div class="ht-money-k">Paid</div>
                                </div>
                                <div>
                                    <div class="ht-money-n {{ $r['money']['balance'] > 0 ? 'due' : '' }}">{{ $fmtMoney($r['money']['balance']) }}</div>
                                    <div class="ht-money-k">
                                        {{ $r['money']['balance'] > 0 ? 'Still owed' : 'Nothing owed' }}@if($r['money']['due'] && $r['money']['balance'] > 0), due {{ $r['money']['due']->format('M j') }}@endif
                                    </div>
                                </div>
                                <div>
                                    <div class="ht-money-n">{{ $r['money']['label'] }}</div>
                                    <div class="ht-money-k">Status</div>
                                </div>
                            </div>
                        @else
                            {{-- Not zero. Nothing went through the platform for
                                 this booking, and saying "$0" would read as
                                 settled when it is simply unrecorded. --}}
                            <p style="font-size:13px;color:var(--text-muted);margin:0;">No payment has gone through the site for this booking.</p>
                        @endif
                    </div>

                    <div class="ht-acts">
                        @if($r['urls']['event'])
                            <a class="ht-btn go" href="{{ $r['urls']['event'] }}">View Event Details</a>
                        @endif
                        @if($r['urls']['profile'])
                            <a class="ht-btn" href="{{ $r['urls']['profile'] }}">View {{ ucfirst($r['supplier']['noun']) }} Profile</a>
                        @endif
                        <a class="ht-btn" href="{{ $r['urls']['payments'] }}">Payments</a>
                        <a class="ht-btn" href="{{ route('client.chat.index') }}">Message {{ ucfirst($r['supplier']['noun']) }}</a>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var rows = document.querySelectorAll('[data-ht-row]');
    if (!rows.length) return;

    var empty = document.querySelector('[data-ht-empty]');

    function show(id) {
        document.querySelectorAll('[data-ht-detail]').forEach(function (d) {
            d.classList.toggle('is-on', d.getAttribute('data-ht-detail') === id);
        });
        rows.forEach(function (r) {
            r.classList.toggle('is-on', r.getAttribute('data-ht-row') === id);
        });
        if (empty) empty.style.display = 'none';
    }

    rows.forEach(function (r) {
        r.addEventListener('click', function () { show(r.getAttribute('data-ht-row')); });
    });

    // The first one, so the right side is never a blank panel on arrival.
    show(rows[0].getAttribute('data-ht-row'));
})();
</script>
@endpush
