{{--
    Proposals by Service (Sir Peter's proposals page, 18 Sep).

    One block per service asked for, each a table of the proposals for it:
    who, the date they confirmed, price, their message, status, and Accept /
    Reply / Decline. Beside it: how far the request has got, the date every
    service must share, tips, and the one-date rule.

    Expects: $event, $coverage, $bids, $bidDates, $type.
--}}
@php
    use App\Domain\Requests\EventDates;
    use App\Domain\Requests\ProposalDate;

    $pbsRows    = $coverage->filter(fn ($r) => $r['service']);
    $pbsRows    = $pbsRows->isNotEmpty() ? $pbsRows : $coverage;
    $pbsTotal   = max(1, $pbsRows->count());
    $pbsWith    = $pbsRows->where('state', '!=', 'open')->count();
    $pbsAwarded = $pbsRows->where('state', 'awarded')->count();
    $pbsWaiting = $pbsRows->count() - $pbsWith;
    $pbsPct     = (int) round($pbsWith / $pbsTotal * 100);
    $pbsOpts    = EventDates::options($event);
    $pbsLocked  = ProposalDate::lockedDate($event);
    $pbsSort    = in_array(request('sort'), ['recommended', 'price', 'rating', 'newest'], true) ? request('sort') : 'recommended';
    $pbsSorts   = ['recommended' => 'Recommended', 'price' => 'Lowest price', 'rating' => 'Highest rating', 'newest' => 'Newest'];
    $pbsTints   = ['orange', 'blue', 'purple', 'green'];

    $pbsRating = fn ($sup) => $sup ? $sup->reviewsReceived->where('is_hidden', false)->avg('rating') : null;
    $pbsDateRank = [ProposalDate::CONFIRMED => 0, ProposalDate::NO_DATE => 1, ProposalDate::DIFFERENT => 2, ProposalDate::UNCONFIRMED => 3, ProposalDate::CLASH => 4, ProposalDate::MISMATCH => 5];
    $pbsOrder = function ($list) use ($pbsSort, $pbsRating, $bidDates, $pbsDateRank) {
        return match ($pbsSort) {
            'price'  => $list->sortBy('amount'),
            'rating' => $list->sortByDesc(fn ($b) => $pbsRating($b->supplier) ?? 0),
            'newest' => $list->sortByDesc('created_at'),
            // Recommended: the ones that hold for the date first, then cheapest.
            default  => $list->sortBy(fn ($b) => [$pbsDateRank[$bidDates[$b->id] ?? 'no_date'] ?? 9, (float) $b->amount]),
        };
    };
@endphp

<style>
    .pbs { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
    /* Narrower than the drawing: the side panel moves under the tables, its
       cards side by side, so every column of the tables still fits. */
    @media (max-width: 1440px) {
        .pbs { grid-template-columns: 1fr; }
        .pbs-rail { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
        .pbs-rail > div { margin-bottom: 0; }
    }
    .pbs-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    @media (max-width: 900px) { .pbs-cards { grid-template-columns: 1fr; } }
    .pbs-card { display: flex; align-items: center; gap: 12px; border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); padding: 14px 16px; }
    .pbs-card.is-ok { background: rgba(22,163,74,.06); border-color: rgba(22,163,74,.25); }
    .pbs-ico { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex: none; color: #fff; }
    .pbs-ico svg { width: 22px; height: 22px; }
    .pbs-card b { display: block; font-size: 14px; color: var(--text-primary); }
    .pbs-card span { display: block; font-size: 12.5px; color: var(--text-secondary); }
    .pbs-card .pbs-big { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pbs-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .pbs-head h3 { font-size: 20px; font-weight: 800; color: #1e3a8a; margin: 0; }
    .pbs-head p { font-size: 13px; color: var(--text-secondary); margin: 3px 0 0; }
    .pbs-sort { border: 1px solid var(--border-color); border-radius: 9px; padding: 7px 10px; font: inherit; font-size: 12.5px; font-weight: 700; background: var(--bg-card); color: var(--text-primary); }
    .pbs-svc { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); overflow: hidden; margin-bottom: 14px; }
    .pbs-svc-h { display: flex; align-items: center; gap: 14px; padding: 12px 16px; }
    .pbs-svc-h.t-orange { background: #fff7ed; } .pbs-svc-h.t-orange .pbs-svc-i, .pbs-svc-h.t-orange h4 { color: #c2410c; }
    .pbs-svc-h.t-blue { background: #eff6ff; } .pbs-svc-h.t-blue .pbs-svc-i, .pbs-svc-h.t-blue h4 { color: #1d4ed8; }
    .pbs-svc-h.t-purple { background: #f5f3ff; } .pbs-svc-h.t-purple .pbs-svc-i, .pbs-svc-h.t-purple h4 { color: #1e3a8a; }
    .pbs-svc-h.t-green { background: #f0fdf4; } .pbs-svc-h.t-green .pbs-svc-i, .pbs-svc-h.t-green h4 { color: #15803d; }
    .pbs-svc-i svg { width: 34px; height: 34px; }
    .pbs-svc-h h4 { font-size: 16px; font-weight: 800; margin: 0; }
    .pbs-svc-h small { font-size: 12.5px; color: var(--text-secondary); }
    .pbs-svc-h .pbs-got { margin-left: auto; font-size: 12px; font-weight: 800; border-radius: 999px; padding: 4px 10px; background: #dcfce7; color: #15803d; white-space: nowrap; }
    .pbs-svc-h .pbs-got.is-wait { background: var(--bg-muted, #f3f4f6); color: var(--text-muted); }
    .pbs-svc-h .pbs-cmp { font-size: 12px; font-weight: 700; color: #1d4ed8; text-decoration: none; white-space: nowrap; }
    .pbs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .pbs-table th { text-align: left; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); padding: 8px 10px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .pbs-table td { padding: 11px 10px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .pbs-table tr:last-child td { border-bottom: 0; }
    .pbs-pro { display: flex; gap: 10px; align-items: center; min-width: 180px; }
    .pbs-pro img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; flex: none; background: #e5e7eb; }
    .pbs-pro a { font-weight: 800; color: var(--text-primary); text-decoration: none; display: block; font-size: 13px; }
    .pbs-pro a:hover { text-decoration: underline; }
    .pbs-pro small { display: block; font-size: 11px; color: var(--text-muted); }
    .pbs-pro .pbs-star { color: #d97706; }
    .pbs-date { display: flex; gap: 7px; align-items: flex-start; min-width: 150px; }
    .pbs-date i { width: 20px; height: 20px; border-radius: 50%; flex: none; display: inline-flex; align-items: center; justify-content: center; font-style: normal; font-weight: 800; font-size: 12px; color: #fff; margin-top: 1px; }
    .pbs-date i.is-ok { background: #16a34a; } .pbs-date i.is-warn { background: #f59e0b; } .pbs-date i.is-bad { background: #dc2626; }
    .pbs-date b { display: block; font-size: 12.5px; color: var(--text-primary); }
    .pbs-date small { display: block; font-size: 11px; color: var(--text-secondary); }
    .pbs-date em { display: block; font-style: normal; font-size: 11px; font-weight: 700; color: #dc2626; }
    .pbs-price { font-size: 14px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .pbs-msg { font-size: 12px; color: var(--text-secondary); min-width: 150px; max-width: 240px; line-height: 1.45; }
    .pbs-st { font-size: 11px; font-weight: 800; border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
    .pbs-st.is-info { background: #dbeafe; color: #1d4ed8; } .pbs-st.is-ok { background: #dcfce7; color: #15803d; } .pbs-st.is-no { background: var(--bg-muted, #f3f4f6); color: var(--text-muted); }
    .pbs-act { display: flex; gap: 5px; align-items: center; justify-content: flex-end; white-space: nowrap; position: relative; }
    .pbs-act form { margin: 0; }
    .pbs-btn { border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); border-radius: 7px; padding: 5px 10px; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
    .pbs-btn.is-accept { background: #ea580c; border-color: #ea580c; color: #fff; }
    .pbs-btn.is-reply { color: #1d4ed8; }
    .pbs-btn.is-diff { background: #fee2e2; border-color: #fecaca; color: #b91c1c; cursor: help; }
    .pbs-btn.is-won { background: #dcfce7; border-color: #bbf7d0; color: #15803d; }
    .pbs-act details { position: static; }
    .pbs-act summary { list-style: none; }
    .pbs-act summary::-webkit-details-marker { display: none; }
    .pbs-pop { position: absolute; right: 0; top: calc(100% + 6px); z-index: 20; width: 280px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 16px 40px -18px rgba(15,27,53,.5); padding: 10px; text-align: left; white-space: normal; }
    .pbs-pop form { display: flex; flex-direction: column; gap: 6px; }
    .pbs-pop textarea, .pbs-pop input { border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 9px; font: inherit; font-size: 12.5px; background: var(--bg-card); color: var(--text-primary); }
    .pbs-pop a, .pbs-pop button.pbs-li { display: block; width: 100%; text-align: left; border: 0; background: none; padding: 7px 8px; border-radius: 7px; font: inherit; font-size: 12.5px; color: var(--text-primary); text-decoration: none; cursor: pointer; }
    .pbs-pop a:hover, .pbs-pop button.pbs-li:hover { background: var(--bg-card-hover, #f1f5f9); }
    .pbs-kebab { border: 0; background: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 2px 5px; }
    .pbs-empty { padding: 16px; font-size: 13px; color: var(--text-muted); }
    .pbs-booked { padding: 9px 16px; font-size: 12.5px; color: #15803d; background: rgba(22,163,74,.07); }
    .pbs-rail > div { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); padding: 16px; margin-bottom: 14px; }
    .pbs-rail h4 { font-size: 15px; font-weight: 800; color: #1e3a8a; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
    .pbs-ring { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
    .pbs-ring-c { width: 78px; height: 78px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex: none; }
    .pbs-ring-c span { width: 62px; height: 62px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pbs-ring b { display: block; font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .pbs-ring small { font-size: 13px; color: var(--text-secondary); }
    .pbs-checks { list-style: none; padding: 0; margin: 0 0 10px; }
    .pbs-checks li { display: flex; gap: 8px; align-items: flex-start; font-size: 12.5px; color: var(--text-secondary); padding: 3px 0; line-height: 1.45; }
    .pbs-checks li::before { content: '✓'; width: 18px; height: 18px; border-radius: 50%; background: #16a34a; color: #fff; font-size: 10.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex: none; margin-top: 1px; }
    .pbs-link { font-size: 13px; font-weight: 700; color: #1d4ed8; text-decoration: none; }
    .pbs-dates summary { list-style: none; cursor: pointer; text-align: center; border: 1px solid #bfdbfe; border-radius: 9px; padding: 7px; font-size: 12.5px; font-weight: 700; color: #1d4ed8; margin-top: 10px; }
    .pbs-dates div { font-size: 12px; color: var(--text-secondary); padding: 4px 0; }
    .pbs-rail .pbs-imp { border-color: #fecaca; background: #fef2f2; }
    .pbs-rail .pbs-imp h4 { color: #b91c1c; }
    .pbs-rail .pbs-imp p { font-size: 12.5px; color: #b91c1c; line-height: 1.55; margin: 0; }
    .pbs-back { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 14px; font-size: 13px; font-weight: 700; color: #1d4ed8; text-decoration: none; background: var(--bg-card); }
</style>

{{-- The three cards across the top: coverage, the date, the budget. --}}
<div class="pbs-cards">
    <div class="pbs-card {{ $pbsWith === $pbsRows->count() && $pbsRows->count() ? 'is-ok' : '' }}">
        <div class="pbs-ico" style="background:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><b>{{ $pbsWith }} of {{ $pbsRows->count() }} {{ Str::plural('service', $pbsRows->count()) }} {{ $pbsWith === 1 ? 'has' : 'have' }} proposals</b><span>You have {{ $bids->count() }} total {{ Str::plural('proposal', $bids->count()) }}</span></div>
    </div>
    <div class="pbs-card">
        <div class="pbs-ico" style="background:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div>
            <b style="color:#1d4ed8;">{{ $pbsLocked ? 'Event date' : 'Primary event date' }}</b>
            @if($pbsOpts)
                <span class="pbs-big">{{ \Illuminate\Support\Carbon::parse($pbsOpts[0]['date'])->format('D, M j, Y') }}</span>
                <span>{{ $pbsOpts[0]['start'] ? \Illuminate\Support\Str::after(EventDates::label($pbsOpts[0], false), ' · ') : 'Time not set' }}</span>
            @else
                <span class="pbs-big">No date yet</span>
            @endif
        </div>
    </div>
    <div class="pbs-card">
        <div class="pbs-ico" style="background:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div>
            <span>Estimated budget</span>
            <span class="pbs-big">
                @if($event->budget_min && $event->budget_max) ${{ number_format((float) $event->budget_min) }} – ${{ number_format((float) $event->budget_max) }}
                @elseif($event->budget) ${{ number_format((float) $event->budget) }}
                @else Not set @endif
            </span>
        </div>
    </div>
</div>

<div class="pbs">
    <div>
        <div class="pbs-head">
            <div>
                <h3>Proposals by Service</h3>
                <p>Review and compare proposals for each service. Accept one professional per service.</p>
            </div>
            <form method="GET" action="{{ route('client.events.show', $event) }}">
                <input type="hidden" name="tab" value="proposals">
                <select name="sort" class="pbs-sort" onchange="this.form.submit()" aria-label="Sort proposals">
                    @foreach($pbsSorts as $k => $l)<option value="{{ $k }}" @selected($pbsSort === $k)>Sort by: {{ $l }}</option>@endforeach
                </select>
            </form>
        </div>

        @foreach($pbsRows as $i => $row)
            @php
                $svc = $row['service'];
                $tint = $pbsTints[$loop->index % count($pbsTints)];
                $count = $row['bids']->count();
            @endphp
            <section class="pbs-svc" id="{{ $svc ? 'service-' . $svc->id : 'service-all' }}">
                <div class="pbs-svc-h t-{{ $tint }}">
                    <span class="pbs-svc-i">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/></svg>
                    </span>
                    <div>
                        <h4>{{ $svc->name ?? 'Whole request' }}</h4>
                        <small>{{ $count }} {{ Str::plural('proposal', $count) }} &nbsp;|&nbsp; Select 1 professional</small>
                    </div>
                    @if($svc && $count > 1)
                        <a class="pbs-cmp" href="{{ route('client.proposals.compare', [$event, 'service' => $svc->id]) }}">Compare {{ $count }} side by side</a>
                    @endif
                    <span class="pbs-got {{ $row['booking'] ? '' : ($count ? '' : 'is-wait') }}">
                        {{ $row['booking'] ? 'Booked' : ($count ? $count . ' received' : 'Waiting') }}
                    </span>
                </div>

                @if($row['booking'])
                    <div class="pbs-booked">✓ Booked with <b>{{ rtrim($row['booking']->supplier->name ?? 'a professional', '.') }}</b>. The other proposals for this service can no longer be chosen.</div>
                @endif

                @if($count)
                    <div style="overflow-x:auto;">
                    <table class="pbs-table">
                        <thead><tr><th>Professional</th><th>Confirmed Date &amp; Time</th><th>Price</th><th>Message</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>
                        @foreach($pbsOrder($row['bids']) as $bid)
                            @php
                                $sup = $bid->supplier;
                                $date = $bidDates[$bid->id] ?? ProposalDate::NO_DATE;
                                $won = $row['booking'] && (int) $row['booking']->supplier_id === (int) $bid->supplier_id;
                                $warn = ProposalDate::needsWarning($date) ? ProposalDate::warning($date, $event->starts_at, $sup?->name, $bid) : null;
                                $blocked = ProposalDate::blocks($date);
                                $rating = $pbsRating($sup);
                                $ratingN = $sup ? $sup->reviewsReceived->where('is_hidden', false)->count() : 0;
                                $closed = in_array($bid->status, ['declined', 'withdrawn'], true);
                                $status = match (true) {
                                    $won => ['Accepted', 'ok'],
                                    $bid->status === 'declined' => ['Declined', 'no'],
                                    $bid->status === 'withdrawn' => ['Withdrawn', 'no'],
                                    $bid->replies->isNotEmpty() => ['Replied', 'info'],
                                    default => ['New', 'info'],
                                };
                                $opt = EventDates::option($event, ProposalDate::dayOf($bid));
                                $mark = match ($date) {
                                    ProposalDate::CONFIRMED => ['✓', 'ok'],
                                    ProposalDate::MISMATCH, ProposalDate::CLASH => ['!', 'bad'],
                                    ProposalDate::NO_DATE => ['–', 'warn'],
                                    default => ['!', 'warn'],
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="pbs-pro">
                                        <img src="{{ $sup?->avatar_url }}" alt="">
                                        <div>
                                            @if($sup)<a href="{{ route('public.professional.show', $sup) }}">{{ $sup->name }}</a>@else<b>Professional</b>@endif
                                            @if($sup?->public_id)<small>{{ \App\Support\GigResourceId::display($sup->public_id) }}</small>@endif
                                            <small>@if($ratingN)<span class="pbs-star">★</span> {{ number_format($rating, 1) }} ({{ $ratingN }} {{ Str::plural('review', $ratingN) }})@else No reviews yet @endif</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="pbs-date" title="{{ ProposalDate::label($date, $event->starts_at, $bid) }}">
                                        <i class="is-{{ $mark[1] }}">{{ $mark[0] }}</i>
                                        <div>
                                            @if($opt)
                                                <b>{{ \Illuminate\Support\Carbon::parse($opt['date'])->format('M j, Y') }}</b>
                                                @if($opt['start'])<small>{{ \Illuminate\Support\Str::after(EventDates::label($opt, false), ' · ') }}</small>@endif
                                            @else
                                                <b>{{ $date === ProposalDate::NO_DATE ? 'No date set' : 'Not confirmed' }}</b>
                                            @endif
                                            @if($date === ProposalDate::DIFFERENT || $date === ProposalDate::MISMATCH)<em>Different date</em>@endif
                                            @if($date === ProposalDate::CLASH)<em>Booked elsewhere that day</em>@endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="pbs-price">${{ number_format($bid->amount) }}</span></td>
                                <td><div class="pbs-msg">{{ $bid->note ? '"' . \Illuminate\Support\Str::limit($bid->note, 90) . '"' : ($bid->availability_note ? \Illuminate\Support\Str::limit($bid->availability_note, 90) : '—') }}</div></td>
                                <td><span class="pbs-st is-{{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td>
                                    <div class="pbs-act">
                                        @if($won)
                                            <span class="pbs-btn is-won">Booked</span>
                                        @elseif(! $row['booking'] && ! $closed)
                                            {{-- A date that does not hold asks once more; one for
                                                 a different day from an accepted service cannot
                                                 be accepted at all. --}}
                                            @if($blocked)
                                                <span class="pbs-btn is-diff" title="{{ ProposalDate::warning($date, $event->starts_at, $sup?->name, $bid) }}">Different date</span>
                                            @else
                                                <form method="POST" action="{{ route('client.finalize.start', $bid) }}"
                                                      @if($warn) onsubmit="return confirm(@js($warn . ' Continue anyway?'));" @endif>
                                                    @csrf
                                                    <button type="submit" class="pbs-btn is-accept">Accept</button>
                                                </form>
                                            @endif
                                        @endif
                                        @if(! $won && ! $closed)
                                            <details data-pbs-pop>
                                                <summary class="pbs-btn is-reply">Reply</summary>
                                                <div class="pbs-pop">
                                                    <form method="POST" action="{{ route('client.proposals.reply', $bid) }}">
                                                        @csrf
                                                        <textarea name="note" rows="3" maxlength="1000" placeholder="Ask a question or explain a counter-offer" aria-label="Reply to {{ $sup?->name }}"></textarea>
                                                        <input type="number" name="counter_amount" min="1" placeholder="Counter $ (optional)" aria-label="Counter amount">
                                                        <button type="submit" class="pbs-btn is-accept">Send reply</button>
                                                    </form>
                                                </div>
                                            </details>
                                            @unless($row['booking'])
                                                <form method="POST" action="{{ route('client.proposals.decline', $bid) }}" onsubmit="return confirm('Decline this proposal?');">
                                                    @csrf
                                                    <button type="submit" class="pbs-btn">Decline</button>
                                                </form>
                                            @endunless
                                        @endif
                                        <details data-pbs-pop>
                                            <summary class="pbs-kebab" aria-label="More actions">⋯</summary>
                                            <div class="pbs-pop" style="width:200px;">
                                                @if($sup)<a href="{{ route('public.professional.show', $sup) }}">View profile</a>@endif
                                                @if($svc && $count > 1)<a href="{{ route('client.proposals.compare', [$event, 'service' => $svc->id]) }}">Compare side by side</a>@endif
                                                <a href="{{ route('client.chat.index') }}">Messages</a>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                            @if($bid->replies->isNotEmpty())
                                @php $last = $bid->replies->last(); @endphp
                                <tr><td colspan="6" style="padding-top:0;font-size:11.5px;color:var(--text-muted);">Last reply from {{ $last->user?->name ?? 'someone' }} {{ $last->created_at->humanAgo() }}@if($last->counter_amount) · countered at ${{ number_format($last->counter_amount) }}@endif</td></tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="pbs-empty">{{ $type === 'DR' ? 'The professional you sent this to has not responded yet.' : 'No proposals for this service yet. Professionals who offer it are being notified.' }}</div>
                @endif
            </section>
        @endforeach

        <a class="pbs-back" href="{{ route('client.events.index') }}">← Back to My Events</a>
    </div>

    <aside class="pbs-rail">
        <div>
            <h4>Event Progress</h4>
            <div class="pbs-ring">
                <div class="pbs-ring-c" style="background:conic-gradient(#16a34a {{ $pbsPct * 3.6 }}deg, #e5e7eb 0);"><span>{{ $pbsPct }}%</span></div>
                <div><b>{{ $pbsWith }} of {{ $pbsRows->count() }}</b><small>services covered</small></div>
            </div>
            <ul class="pbs-checks">
                <li>{{ $pbsWith }} {{ Str::plural('service', $pbsWith) }} with proposals</li>
                <li>{{ $pbsWaiting }} {{ Str::plural('service', $pbsWaiting) }} waiting</li>
                <li>{{ $pbsAwarded }} {{ Str::plural('service', $pbsAwarded) }} accepted</li>
            </ul>
            @php $pbsOpen = \App\Domain\Requests\ServiceCoverage::uncovered($coverage); @endphp
            @if($pbsOpen->isNotEmpty() && $pbsRows->count() > 1)
                {{-- Sir Peter, 17 Sep: name the services nobody has bid on yet. --}}
                <p style="font-size:12.5px;color:#9a3412;background:#fff7ed;border-radius:9px;padding:8px 10px;margin:0 0 10px;">
                    <b>Still uncovered:</b> {{ $pbsOpen->pluck('service.name')->implode(', ') }}. This request stays open for {{ $pbsOpen->count() === 1 ? 'it' : 'them' }} while you choose the rest.
                </p>
            @endif
            <a class="pbs-link" href="{{ route('client.events.show', ['event' => $event, 'tab' => 'overview']) }}">View event details →</a>
        </div>

        <div>
            <h4>Event Date &amp; Time</h4>
            @if($pbsOpts)
                <div style="font-size:13px;color:var(--text-primary);"><b>{{ $pbsLocked ? 'Booked for' : 'Primary' }}:</b> {{ \Illuminate\Support\Carbon::parse($pbsOpts[0]['date'])->format('M j, Y') }}</div>
                @if($pbsOpts[0]['start'])<div style="font-size:12.5px;color:var(--text-secondary);">{{ \Illuminate\Support\Str::after(EventDates::label($pbsOpts[0], false), ' · ') }}</div>@endif
                <details class="pbs-dates">
                    <summary>View all date options</summary>
                    @foreach($pbsOpts as $o)
                        <div>{{ EventDates::label($o) }} {{ $o['primary'] ? '(preferred)' : '(backup)' }}</div>
                    @endforeach
                </details>
            @else
                <div style="font-size:13px;color:var(--text-muted);">No date set yet.</div>
            @endif
        </div>

        <div>
            <h4>Quick Tips</h4>
            <ul class="pbs-checks" style="margin:0;">
                <li>Compare proposals within each service</li>
                <li>Check the confirmed date and time for each proposal</li>
                <li>You can only accept one professional per service</li>
                <li>All accepted professionals must be available for the same date and time</li>
                <li>If a proposal shows a different date, you'll see a warning when you try to accept it</li>
                <li>Proposals are sealed: each amount is seen only by you and its sender</li>
            </ul>
        </div>

        <div class="pbs-imp">
            <h4>Important</h4>
            <p>All professionals you accept must be available for the same event date and time. We'll warn you if you try to accept a proposal with a different date.</p>
        </div>
    </aside>
</div>

<script>
    // One Reply or menu open at a time; a click elsewhere closes it.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details[data-pbs-pop][open]').forEach(function (d) {
            if (! d.contains(e.target)) d.removeAttribute('open');
        });
    });
</script>
