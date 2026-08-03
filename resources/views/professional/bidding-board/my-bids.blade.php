@extends('layouts.professional')

@section('title', 'My Bids')
@section('page-title', 'My Bids')
@section('page-subtitle', 'Every bid, reply and outcome.')

{{-- Professional — My Bids.

     States are DERIVED, not stored (see BID_STATES in the controller): an award
     on the event decides won vs not-selected, a reply means a live negotiation,
     a past event date means expired. Only submitted and withdrawn are written.
     Peter's mockup also shows Drafts and Declined tabs — there is no draft-save
     flow and no client-decline record, so they aren't rendered as tabs that
     could only ever read zero. --}}

@push('styles')
<style>
    .mb { max-width: 1180px; }
    .mb-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .mb-back { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .mb-flash { display: flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-size: 13px; font-weight: 600; padding: 11px 16px; border-radius: 12px; margin-bottom: 16px; }
    .mb-err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    .mb-grid { display: grid; grid-template-columns: minmax(0,1fr) 290px; gap: 20px; align-items: start; }
    @media (max-width: 1080px) { .mb-grid { grid-template-columns: minmax(0,1fr); } .mb-rail { position: static !important; } }

    /* Stat strip doubles as the state filter — the mockup had a stat row and a
       status tab row saying the same numbers twice. */
    .mb-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 16px; }
    @media (max-width: 900px) { .mb-stats { grid-template-columns: repeat(2, 1fr); } }
    .mb-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 13px 15px; text-decoration: none; display: block; }
    .mb-stat.on { border-color: #2563eb; box-shadow: 0 0 0 1px #2563eb inset; }
    .mb-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mb-stat span { font-size: 12px; font-weight: 600; color: var(--text-secondary); }

    .mb-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
    .mb-tab { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .mb-tab.on { background: #2563eb; border-color: transparent; color: #fff; }
    .mb-tab .n { font-size: 11px; font-weight: 800; opacity: .8; }
    .mb-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
    .mb-filters input, .mb-filters select { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 8px 11px; font-size: 12.5px; color: var(--text-primary); font-family: inherit; }
    .mb-filters .go { background: #2563eb; border: 0; border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 800; color: #fff; cursor: pointer; font-family: inherit; }
    .mb-filters .clear { font-size: 12.5px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .mb-search { flex: 1 1 220px; min-width: 170px; }

    .mb-row { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 18px; margin-bottom: 11px; display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 14px; align-items: start; }
    @media (max-width: 620px) { .mb-row { grid-template-columns: 1fr; } .mb-right { text-align: left !important; } .mb-acts { justify-content: flex-start !important; } }
    .mb-title { font-size: 15px; font-weight: 800; color: var(--text-primary); text-decoration: none; }
    .mb-title:hover { color: var(--info-text); }
    .mb-meta { font-size: 12px; color: var(--text-secondary); margin-top: 5px; display: flex; gap: 9px; flex-wrap: wrap; align-items: center; }
    .mb-chip { display: inline-flex; align-items: center; border-radius: 6px; padding: 2px 8px; font-size: 10.5px; font-weight: 800; }
    .mb-chip.BR { background: rgba(37,99,235,.12); color: var(--info-text); }
    .mb-chip.ER { background: rgba(220,38,38,.12); color: var(--bad-text); }
    .mb-chip.DR { background: rgba(124,58,237,.12); color: var(--accent-text); }
    .mb-chip.scope { background: rgba(100,116,139,.14); color: var(--text-secondary); }
    .mb-right { text-align: right; min-width: 200px; }
    .mb-amt { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .mb-net { font-size: 11.5px; color: var(--text-secondary); margin-top: 1px; }
    .mb-state { display: inline-block; border-radius: 999px; padding: 3px 11px; font-size: 11px; font-weight: 800; margin-bottom: 6px; }
    .mb-state.submitted { background: rgba(37,99,235,.12); color: var(--info-text); }
    .mb-state.negotiating { background: rgba(124,58,237,.14); color: var(--accent-text); }
    .mb-state.won { background: rgba(22,163,74,.14); color: var(--ok-text); }
    .mb-state.not_selected, .mb-state.withdrawn { background: rgba(100,116,139,.16); color: var(--text-secondary); }
    .mb-state.expired { background: rgba(217,119,6,.14); color: var(--warn-text); }
    .mb-acts { display: flex; gap: 7px; justify-content: flex-end; margin-top: 9px; flex-wrap: wrap; }
    .mb-btn { border: 1px solid var(--border-color); background: transparent; border-radius: 9px; padding: 6px 12px; font-size: 12px; font-weight: 700; color: var(--text-secondary); text-decoration: none; cursor: pointer; font-family: inherit; }
    .mb-btn.danger { border-color: rgba(220,38,38,.4); color: var(--bad-text); }

    .mb-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 14px; padding: 48px 20px; text-align: center; }
    .mb-empty h4 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .mb-empty p { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; }
    .mb-empty a { display: inline-flex; background: #2563eb; color: #fff; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 700; text-decoration: none; }

    .mb-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 84px; }
    .mb-rc { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px; }
    .mb-rc h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 11px; }
    .mb-rl { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; padding: 5px 0; }
    .mb-rl span { color: var(--text-secondary); }
    .mb-rl b { color: var(--text-primary); font-weight: 800; }
    .mb-seal { background: #f5f3ff; border-color: #ddd6fe; }
    .mb-seal h4 { color: #5b21b6; }
    .mb-seal ul { margin: 0; padding-left: 17px; font-size: 12px; line-height: 1.7; color: #5b21b6; }
</style>
@endpush

@section('content')
<div class="mb">
    @if(session('status'))<div class="mb-flash">✅ {{ session('status') }}</div>@endif
    @error('bid')<div class="mb-flash mb-err">⚠️ {{ $message }}</div>@enderror

    @php $f = $filters; @endphp

    <div class="mb-top">
        <a class="mb-back" href="{{ route('professional.bidding-board.index') }}">← Return to Bidding Board</a>
        <span style="font-size:12.5px;font-weight:700;color:var(--text-secondary);">
            {{ $rows->count() }} shown of {{ $counts['all'] }} total
        </span>
    </div>

    <div class="mb-stats">
        @foreach([
            ['all', 'All bids'],
            ['submitted', 'Awaiting client'],
            ['negotiating', 'Negotiating'],
            ['won', 'Won'],
            ['expired', 'Expired'],
        ] as [$key, $label])
            <a class="mb-stat {{ $f['state'] === $key ? 'on' : '' }}"
               href="{{ route('professional.bidding-board.my-bids', array_filter(array_merge($f, ['state' => $key]))) }}">
                <b>{{ $counts[$key] ?? 0 }}</b><span>{{ $label }}</span>
            </a>
        @endforeach
    </div>

    {{-- Request type. Same model as the board: BR / ER / DR are the types,
         single vs multi service is the scope filter below. --}}
    <div class="mb-tabs">
        <a class="mb-tab {{ $f['type'] === '' ? 'on' : '' }}"
           href="{{ route('professional.bidding-board.my-bids', array_filter(array_merge($f, ['type' => null]))) }}">
            All types <span class="n">{{ $counts['all'] }}</span>
        </a>
        @foreach(['BR', 'ER', 'DR'] as $key)
            <a class="mb-tab {{ $f['type'] === $key ? 'on' : '' }}"
               href="{{ route('professional.bidding-board.my-bids', array_filter(array_merge($f, ['type' => $key]))) }}">
                {{ $key }} <span class="n">{{ $typeCounts[$key] }}</span>
            </a>
        @endforeach
        {{-- Closed states only appear once they can actually happen. --}}
        @foreach(['not_selected' => 'Not selected', 'withdrawn' => 'Withdrawn'] as $key => $label)
            @if(($counts[$key] ?? 0) > 0)
                <a class="mb-tab {{ $f['state'] === $key ? 'on' : '' }}"
                   href="{{ route('professional.bidding-board.my-bids', array_filter(array_merge($f, ['state' => $key]))) }}">
                    {{ $label }} <span class="n">{{ $counts[$key] }}</span>
                </a>
            @endif
        @endforeach
    </div>

    <form method="GET" action="{{ route('professional.bidding-board.my-bids') }}" class="mb-filters">
        <input type="hidden" name="state" value="{{ $f['state'] }}">
        <input type="hidden" name="type" value="{{ $f['type'] }}">
        <input class="mb-search" type="search" name="q" value="{{ $f['q'] }}" placeholder="Search event, client or service…">
        <select name="scope">
            <option value="">Single &amp; multi-service</option>
            <option value="single" @selected($f['scope'] === 'single')>SSR — single service</option>
            <option value="multi" @selected($f['scope'] === 'multi')>MSR — multi-service</option>
        </select>
        <button type="submit" class="go">Apply</button>
        @if($f['q'] || $f['scope'])
            <a class="clear" href="{{ route('professional.bidding-board.my-bids', array_filter(['state' => $f['state'], 'type' => $f['type']])) }}">Clear</a>
        @endif
    </form>

    <div class="mb-grid">
        <div>
            @forelse($rows as $r)
                @php $bid = $r['bid']; $e = $r['event']; @endphp
                <article class="mb-row">
                    <div>
                        @if($e)
                            <a class="mb-title" href="{{ route('professional.gigs.show', $e) }}">{{ $e->title }}</a>
                        @else
                            <span class="mb-title">Request no longer available</span>
                        @endif
                        <div class="mb-meta">
                            <span class="mb-chip {{ $r['type'] }}">{{ $r['type'] }}</span>
                            <span class="mb-chip scope">{{ $r['scope'] === 'multi' ? 'MSR · multi-service' : 'SSR · single service' }}</span>
                            @if($bid->category)<span>{{ $bid->category->name }}</span>@endif
                            @if($e?->client)<span>{{ $e->client->name }}</span>@endif
                            @if($e?->starts_at)<span>📅 {{ $e->starts_at->format('M j, Y') }}</span>@endif
                            <span>Bid {{ $bid->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- The negotiation thread, unchanged — this is where
                             counters and replies already live. --}}
                        @include('professional.bidding-board._bid-thread', ['bid' => $bid])
                    </div>

                    <div class="mb-right">
                        <span class="mb-state {{ $r['state'] }}">{{ Str::title(str_replace('_', ' ', $r['state'])) }}</span>
                        <div class="mb-amt">${{ number_format($bid->amount) }}</div>
                        <div class="mb-net">${{ number_format($r['net']) }} after {{ rtrim(rtrim(number_format($payout['pct'], 2), '0'), '.') }}% commission</div>
                        <div class="mb-acts">
                            <span class="mb-btn" style="cursor:default;">{{ $bid->is_public ? '📣 Public' : '🔒 Sealed' }}</span>
                            @if(in_array($r['state'], ['submitted', 'negotiating'], true))
                                <form method="POST" action="{{ route('professional.bidding-board.toggle', $bid) }}">
                                    @csrf
                                    <button type="submit" class="mb-btn">{{ $bid->is_public ? 'Seal again' : 'Make public' }}</button>
                                </form>
                                <form method="POST" action="{{ route('professional.bidding-board.withdraw', $bid) }}"
                                      onsubmit="return confirm('Withdraw this bid? The client will no longer see it.');">
                                    @csrf
                                    <button type="submit" class="mb-btn danger">Withdraw</button>
                                </form>
                            @endif
                            @if($e)
                                <a class="mb-btn" href="{{ route('professional.gigs.show', $e) }}">View request</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="mb-empty">
                    <div style="font-size:36px;">🔒</div>
                    <h4>No bids here yet</h4>
                    <p>{{ $f['state'] === 'all' && $f['type'] === '' && ! $f['q'] && ! $f['scope']
                        ? 'Head to the Bidding Board and place your first bid — submitting is free.'
                        : 'Nothing matches this filter. Try another tab, or clear your search.' }}</p>
                    <a href="{{ route('professional.bidding-board.index') }}">Browse open requests →</a>
                </div>
            @endforelse
        </div>

        <aside class="mb-rail">
            <div class="mb-rc">
                <h4>Bid activity</h4>
                @foreach(['all' => 'Total bids', 'submitted' => 'Awaiting client', 'negotiating' => 'Negotiating', 'won' => 'Won', 'not_selected' => 'Not selected', 'withdrawn' => 'Withdrawn', 'expired' => 'Expired'] as $k => $label)
                    <div class="mb-rl"><span>{{ $label }}</span><b>{{ $counts[$k] ?? 0 }}</b></div>
                @endforeach
            </div>

            {{-- Net, not gross: commission comes off at payout, so a gross total
                 would overstate what actually arrives. --}}
            <div class="mb-rc">
                <h4>Estimated payout</h4>
                <div class="mb-rl"><span>Won</span><b>${{ number_format($payout['won']) }}</b></div>
                <div class="mb-rl"><span>In negotiation</span><b>${{ number_format($payout['negotiating']) }}</b></div>
                <div class="mb-rl"><span>Awaiting client</span><b>${{ number_format($payout['submitted']) }}</b></div>
                <div class="mb-rl" style="border-top:1px solid var(--border-color);margin-top:6px;padding-top:9px;">
                    <span>Shown after commission</span><b>{{ rtrim(rtrim(number_format($payout['pct'], 2), '0'), '.') }}%</b>
                </div>
            </div>

            <div class="mb-rc mb-seal">
                <h4>🔒 Sealed bidding</h4>
                <ul>
                    <li>Your amount and terms are visible only to you and the client.</li>
                    <li>Submitting, revising and negotiating are free.</li>
                    <li>Withdraw any time before the request is awarded.</li>
                    <li>Commission is deducted at payout, not at bid.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
