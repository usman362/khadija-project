@extends('layouts.client')

@section('title', 'Compare Proposals — ' . $event->title)
@section('page-title', 'Compare Proposals')

{{-- Screen 3 of the client BSR set.

     Two columns from Peter's mockup are deliberately absent: Distance and
     Response Time. No coordinates are stored on a professional's profile and
     nothing records reply speed, so both could only be invented — and a client
     choosing who to hire is exactly the wrong place to show a made-up number.
     City stands in for distance, since that IS real. --}}

@section('content')
<style>
    .cp-head { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
    .cp-back { display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 12.5px; font-weight: 600; margin-bottom: 10px; }
    .cp-title { font-size: 22px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
    .cp-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: 13px; color: var(--text-secondary); font-weight: 600; }
    .cp-sealed { display: flex; gap: 9px; background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.2); border-radius: 12px; padding: 12px 15px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px; }

    .cp-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
    .cp-bar input, .cp-bar select { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 8px 11px; font-size: 12.5px; color: var(--text-primary); font-family: inherit; }
    .cp-bar .s { flex: 1 1 220px; min-width: 170px; }
    .cp-bar button { background: #f97316; border: 0; border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 800; color: #fff; cursor: pointer; font-family: inherit; }
    .cp-bar a.clear { font-size: 12.5px; font-weight: 700; color: var(--text-muted); text-decoration: none; }

    .cp-selbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 15px; margin-bottom: 12px; font-size: 12.5px; color: var(--text-secondary); }
    .cp-selbar b { color: var(--text-primary); }
    .cp-cmp { background: #f97316; border: 0; border-radius: 9px; padding: 8px 15px; font-size: 12.5px; font-weight: 800; color: #fff; cursor: pointer; font-family: inherit; }
    .cp-cmp[disabled] { opacity: .45; cursor: not-allowed; }

    .cp-row { display: grid; grid-template-columns: 26px minmax(0,1fr) auto; gap: 14px; align-items: flex-start; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 18px; margin-bottom: 11px; }
    .cp-row.sel { border-color: #f97316; box-shadow: 0 0 0 1px #f97316 inset; }
    .cp-name { font-size: 15px; font-weight: 800; color: var(--text-primary); text-decoration: none; }
    .cp-name:hover { color: var(--brand-text); }
    .cp-facts { display: flex; gap: 9px; flex-wrap: wrap; margin-top: 6px; font-size: 12px; color: var(--text-muted); align-items: center; }
    .cp-tag { border-radius: 6px; padding: 2px 8px; font-size: 10.5px; font-weight: 800; }
    .cp-tag.ok { background: rgba(22,163,74,.13); color: var(--ok-text); }
    .cp-tag.no { background: rgba(100,116,139,.15); color: var(--text-muted); }
    .cp-note { font-size: 13px; color: var(--text-secondary); margin-top: 8px; line-height: 1.6; }
    .cp-right { text-align: right; min-width: 210px; }
    .cp-amt { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .cp-budget { font-size: 11.5px; font-weight: 700; }
    .cp-state { display: inline-block; border-radius: 999px; padding: 3px 11px; font-size: 11px; font-weight: 800; margin-bottom: 6px; }
    .cp-state.responded { background: rgba(37,99,235,.12); color: var(--info-text); }
    .cp-state.negotiating { background: rgba(124,58,237,.14); color: var(--accent-text); }
    .cp-state.accepted { background: rgba(22,163,74,.14); color: var(--ok-text); }
    .cp-state.declined, .cp-state.not_selected { background: rgba(100,116,139,.16); color: var(--text-muted); }
    .cp-acts { display: flex; gap: 6px; justify-content: flex-end; margin-top: 9px; flex-wrap: wrap; }
    .cp-btn { border: 1px solid var(--border-color); background: transparent; border-radius: 9px; padding: 6px 12px; font-size: 12px; font-weight: 700; color: var(--text-secondary); text-decoration: none; cursor: pointer; font-family: inherit; }
    .cp-btn.go { background: #15803d; border-color: #16a34a; color: #fff; }
    .cp-btn.no { border-color: rgba(220,38,38,.4); color: var(--bad-text); }

    .cp-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 14px; padding: 48px 20px; text-align: center; }
    .cp-empty b { display: block; font-size: 15px; color: var(--text-primary); margin-bottom: 6px; }
    .cp-empty p { font-size: 13px; color: var(--text-muted); }

    /* Side-by-side panel, opened by the Compare button. */
    .cp-panel { display: none; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; margin-bottom: 14px; overflow-x: auto; }
    .cp-panel.on { display: block; }
    .cp-panel table { width: 100%; border-collapse: collapse; min-width: 520px; }
    .cp-panel th, .cp-panel td { text-align: left; padding: 9px 12px; font-size: 13px; border-bottom: 1px solid var(--border-color); }
    .cp-panel th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); font-weight: 800; }
    .cp-panel td b { color: var(--text-primary); }
</style>

@if(session('status'))
    <div class="cl-card" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;margin-bottom:16px;font-size:13.5px;">✅ {{ session('status') }}</div>
@endif

@php $f = $filters; @endphp

<div class="cp-head">
    <a class="cp-back" href="{{ route('client.events.show', ['event' => $event, 'tab' => 'proposals']) }}">← Back to the request</a>
    <div class="cp-title">{{ $event->title }}</div>
    <div class="cp-meta">
        <span>{{ $total }} {{ Str::plural('proposal', $total) }} received</span>
        @if($event->starts_at)<span>📅 {{ $event->starts_at->format('M j, Y') }}</span>@endif
        @if($event->location)<span>📍 {{ $event->location }}</span>@endif
        @if($event->budget)<span>💰 Budget ${{ number_format($event->budget) }}</span>@endif
    </div>
</div>

<div class="cp-sealed">
    🔒 <span><b>Sealed proposals.</b> Each amount is visible only to you and the professional who sent it — they cannot see each other's bids, rankings or negotiations. Compare the full scope, terms and qualifications, not only price.</span>
</div>

<form method="GET" action="{{ route('client.proposals.compare', $event) }}" class="cp-bar">
    <input class="s" type="search" name="q" value="{{ $f['q'] }}" placeholder="Search professional or city…">
    <select name="only" aria-label="All professionals">
        <option value="">All professionals</option>
        <option value="verified" @selected($f['only'] === 'verified')>Fully verified only</option>
        <option value="insured" @selected($f['only'] === 'insured')>Insured only</option>
    </select>
    <select name="sort" aria-label="Lowest bid first">
        <option value="amount" @selected($f['sort'] === 'amount')>Lowest bid first</option>
        <option value="rating" @selected($f['sort'] === 'rating')>Highest rated</option>
        <option value="years" @selected($f['sort'] === 'years')>Most experienced</option>
        <option value="newest" @selected($f['sort'] === 'newest')>Most recent</option>
    </select>
    <button type="submit">Apply</button>
    @if($f['q'] || $f['only'])
        <a class="clear" href="{{ route('client.proposals.compare', $event) }}">Clear</a>
    @endif
</form>

@if($rows->isNotEmpty())
    <div class="cp-selbar">
        <span><b id="cpCount">0</b> of 3 selected to compare</span>
        <span style="display:flex;gap:8px;">
            <button type="button" class="cp-btn" id="cpClear">Clear</button>
            <button type="button" class="cp-cmp" id="cpGo" disabled>Compare selected</button>
        </span>
    </div>

    <div class="cp-panel" id="cpPanel"></div>
@endif

@forelse($rows as $r)
    @php $b = $r['bid']; $pro = $r['pro']; @endphp
    <article class="cp-row" data-cp
             data-name="{{ $pro->name ?? 'Professional' }}"
             data-amount="${{ number_format($b->amount) }}"
             data-rating="{{ $r['rating'] ? $r['rating'] . ' (' . $r['reviews'] . ')' : 'No reviews yet' }}"
             data-years="{{ $r['years'] ? $r['years'] . ' yrs' : '—' }}"
             data-city="{{ $r['city'] ?: '—' }}"
             data-insured="{{ $r['insured'] ? 'Insured' : 'Not on file' }}"
             data-verified="{{ $r['verified'] ? 'Verified' : 'Not verified' }}">
        <input type="checkbox" class="cp-check" style="margin-top:4px;" aria-label="Select {{ $pro->name ?? 'professional' }} to compare">

        <div style="min-width:0;">
            @if($pro)
                <a class="cp-name" href="{{ route('public.professional.show', $pro) }}">{{ $pro->name }}</a>
            @else
                <span class="cp-name">Professional</span>
            @endif
            <div class="cp-facts">
                @if($r['verified'])<span class="cp-tag ok">✓ Verified</span>@else<span class="cp-tag no">Not verified</span>@endif
                @if($r['insured'])<span class="cp-tag ok">Insured</span>@else<span class="cp-tag no">No insurance on file</span>@endif
                @if($r['rating'])<span>★ {{ $r['rating'] }} ({{ $r['reviews'] }})</span>@else<span>No reviews yet</span>@endif
                @if($r['years'])<span>{{ $r['years'] }} yrs experience</span>@endif
                @if($r['city'])<span>📍 {{ $r['city'] }}</span>@endif
                @if($b->category)<span>{{ $b->category->name }}</span>@endif
                <span>Submitted {{ $b->created_at->humanAgo() }}</span>
            </div>
            @if($b->note)<p class="cp-note">{{ $b->note }}</p>@endif
            @if($b->replies->isNotEmpty())
                @php $last = $b->replies->last(); @endphp
                <p class="cp-note" style="border-top:1px dashed var(--border-color);padding-top:8px;">
                    Last message from <b>{{ $last->user?->name ?? 'them' }}</b> {{ $last->created_at->humanAgo() }}@if($last->counter_amount) — countered at <b>${{ number_format($last->counter_amount) }}</b>@endif
                </p>
            @endif
        </div>

        <div class="cp-right">
            <span class="cp-state {{ $r['state'] }}">{{ Str::title(str_replace('_', ' ', $r['state'])) }}</span>
            <div class="cp-amt">${{ number_format($b->amount) }}</div>
            @if($event->budget)
                <div class="cp-budget" style="color:{{ $r['overBudget'] ? '#d97706' : '#16a34a' }};">
                    {{ $r['overBudget'] ? 'Above budget' : 'Within budget' }}
                </div>
            @endif
            <div class="cp-acts">
                @if($pro)<a class="cp-btn" href="{{ route('public.professional.show', $pro) }}">Profile</a>@endif
                @if(! $awardedTo && $r['state'] !== 'declined')
                    {{-- R12: Reply is a counter-offer, not a message — the two are
                         deliberately different things. General questions belong in
                         the message thread, so that's where this points. --}}
                    <a class="cp-btn" href="{{ route('client.chat.index') }}">Message</a>
                    <form method="POST" action="{{ route('client.proposals.decline', $b) }}" style="display:inline;"
                          onsubmit="return confirm('Decline this proposal?');">
                        @csrf
                        <button type="submit" class="cp-btn no">Decline</button>
                    </form>
                    {{-- Selecting opens the finalization rather than booking on the
                         spot: scope, price, schedule, terms, contract and deposit
                         all get agreed first, and either side can still back out
                         until it is signed and funded. --}}
                    <form method="POST" action="{{ route('client.finalize.start', $b) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="cp-btn go">Select &amp; finalize</button>
                    </form>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="cp-empty">
        <b>No proposals to compare yet</b>
        <p>{{ $f['q'] || $f['only'] ? 'Nothing matches this filter — try clearing it.' : 'Professionals are being notified. Proposals appear here as they arrive.' }}</p>
    </div>
@endforelse

@if($rows->isNotEmpty())
<script>
(function () {
    // Compare is a client-side view over rows already on the page — picking two
    // or three to line up shouldn't cost a round trip.
    var MAX = 3;
    var rows  = [].slice.call(document.querySelectorAll('[data-cp]'));
    var count = document.getElementById('cpCount');
    var go    = document.getElementById('cpGo');
    var panel = document.getElementById('cpPanel');

    function selected() { return rows.filter(function (r) { return r.querySelector('.cp-check').checked; }); }

    function sync() {
        var s = selected();
        count.textContent = s.length;
        go.disabled = s.length < 2;
        rows.forEach(function (r) {
            var on = r.querySelector('.cp-check').checked;
            r.classList.toggle('sel', on);
            // Block a fourth rather than silently ignoring the click.
            r.querySelector('.cp-check').disabled = !on && s.length >= MAX;
        });
    }

    rows.forEach(function (r) { r.querySelector('.cp-check').addEventListener('change', sync); });

    document.getElementById('cpClear').addEventListener('click', function () {
        rows.forEach(function (r) { r.querySelector('.cp-check').checked = false; });
        panel.classList.remove('on');
        sync();
    });

    go.addEventListener('click', function () {
        var s = selected();
        if (s.length < 2) return;
        var fields = [['name','Professional'],['amount','Bid'],['rating','Rating'],
                      ['years','Experience'],['city','Based in'],['verified','Verification'],['insured','Insurance']];
        var html = '<table><tr><th></th>' + s.map(function (r) {
            return '<th>' + r.dataset.name + '</th>';
        }).join('') + '</tr>';
        fields.slice(1).forEach(function (f) {
            html += '<tr><th>' + f[1] + '</th>' + s.map(function (r) {
                return '<td><b>' + r.dataset[f[0]] + '</b></td>';
            }).join('') + '</tr>';
        });
        panel.innerHTML = html + '</table>';
        panel.classList.add('on');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    sync();
})();
</script>
@endif
@endsection
