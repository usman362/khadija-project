@extends('layouts.client')

@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('page-subtitle', 'Every professional you have booked, and what is still owed.')

@push('styles')
<style>
    /* The page used to carry six stat cards over six tabs saying the same
       thing, plus four columns of per-booking detail that no table backs:
       tax status, document lists, a milestone timeline, a summariser. All of
       it was invented at render time. What is left is what the database can
       actually answer. */

    .bk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 270px; gap: 18px; align-items: start; }
    .bk-main { min-width: 0; }
    .bk-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    /* ── Tabs double as the counters ─────────────────────────── */
    .bk-bar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
    .bk-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .bk-tab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 13px; border-radius: 9px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        color: var(--text-secondary); text-decoration: none;
        font-size: 12.5px; font-weight: 600; white-space: nowrap;
    }
    .bk-tab:hover { background: var(--bg-card-hover); }
    .bk-tab .n {
        font-size: 11px; font-weight: 800; min-width: 18px; text-align: center;
        padding: 1px 5px; border-radius: 999px;
        background: var(--bg-card-hover); color: var(--text-muted);
    }
    .bk-tab.is-active { background: #ea580c; border-color: #ea580c; color: #fff; }
    .bk-tab.is-active .n { background: rgba(255,255,255,0.25); color: #fff; }

    .bk-search { position: relative; min-width: 250px; flex: 0 1 320px; }
    .bk-search input {
        width: 100%; height: 36px; box-sizing: border-box;
        padding: 0 14px 0 34px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font-size: 12.5px; font-family: inherit; outline: none;
    }
    .bk-search input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.12); }
    .bk-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted); }

    /* ── Booking card ────────────────────────────────────────── */
    .bk-cards { display: flex; flex-direction: column; gap: 12px; }
    .bk-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }
    .bk-head { display: flex; align-items: flex-start; gap: 12px; }
    .bk-ico {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(234,88,12,0.12); color: var(--brand-text);
        font-weight: 800; font-size: 15px;
    }
    .bk-title { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .bk-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .bk-sub b { color: var(--text-secondary); font-weight: 600; }
    .bk-pill {
        margin-left: auto; flex-shrink: 0;
        font-size: 10.5px; font-weight: 800; letter-spacing: .3px;
        padding: 4px 10px; border-radius: 999px; text-transform: uppercase;
    }
    .bk-pill.confirmed { background: rgba(16,185,129,0.14); color: var(--ok-text); }
    .bk-pill.requested { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .bk-pill.completed { background: rgba(99,102,241,0.14); color: var(--accent-text); }
    .bk-pill.cancelled { background: rgba(239,68,68,0.14);  color: var(--bad-text); }

    .bk-facts { display: flex; flex-wrap: wrap; gap: 8px 22px; margin: 13px 0 0; padding-top: 13px; border-top: 1px solid var(--border-color); }
    .bk-fact { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-muted); }
    .bk-fact svg { width: 14px; height: 14px; flex-shrink: 0; }
    .bk-fact b { color: var(--text-primary); font-weight: 700; }
    .bk-fact .muted { color: var(--text-muted); font-style: italic; }

    .bk-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 13px; }
    .bk-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 13px; border-radius: 8px; cursor: pointer;
        font-size: 12px; font-weight: 700; font-family: inherit;
        text-decoration: none; border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary);
    }
    .bk-btn:hover { background: var(--bg-card-hover); }
    .bk-btn svg { width: 13px; height: 13px; }
    .bk-btn.primary { background: #ea580c; border-color: #ea580c; color: #fff; }
    .bk-btn.danger  { color: var(--bad-text); border-color: rgba(239,68,68,0.35); }

    .bk-empty { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 52px 20px; text-align: center; }
    .bk-empty h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 0 0 5px; }
    .bk-empty p  { font-size: 13px; color: var(--text-muted); margin: 0; }

    /* ── Rail ────────────────────────────────────────────────── */
    .bk-rc { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 15px 16px; }
    .bk-rc h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; }
    .bk-donut { position: relative; width: 118px; height: 118px; margin: 0 auto 13px; border-radius: 50%; }
    .bk-donut .hole { position: absolute; inset: 15px; background: var(--bg-card); border-radius: 50%; }
    .bk-donut .mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .bk-donut .mid .n { font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .bk-donut .mid .l { font-size: 10.5px; color: var(--text-muted); margin-top: 2px; }
    .bk-leg { display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; }
    .bk-leg .row { display: flex; align-items: center; gap: 8px; }
    .bk-leg .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .bk-leg .l { flex: 1; color: var(--text-secondary); }
    .bk-leg .v { font-weight: 700; color: var(--text-primary); }

    .bk-fin { display: flex; justify-content: space-between; align-items: baseline; font-size: 12.5px; padding: 6px 0; }
    .bk-fin .l { color: var(--text-muted); }
    .bk-fin .v { font-weight: 800; color: var(--text-primary); }
    .bk-note { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 8px 0 0; padding-top: 9px; border-top: 1px solid var(--border-color); }

    .bk-next { display: flex; gap: 10px; align-items: center; padding: 7px 0; }
    .bk-next + .bk-next { border-top: 1px solid var(--border-color); }
    .bk-next .d { flex-shrink: 0; width: 34px; text-align: center; }
    .bk-next .d .m { font-size: 9.5px; font-weight: 800; color: var(--brand-text); text-transform: uppercase; }
    .bk-next .d .n { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .bk-next .b { min-width: 0; }
    .bk-next .b .t { font-size: 12px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bk-next .b .s { font-size: 11px; color: var(--text-muted); }

    .bk-qa { display: flex; flex-direction: column; gap: 7px; }
    .bk-qa a {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 11px; border-radius: 8px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        font-size: 12.5px; font-weight: 700; color: var(--text-primary); text-decoration: none;
    }
    .bk-qa a:hover { background: var(--bg-card-hover); }
    .bk-qa svg { width: 14px; height: 14px; color: var(--text-muted); }

    @media (max-width: 1100px) { .bk-layout { grid-template-columns: 1fr; } .bk-rail { position: static; } }
</style>
@endpush

@section('content')
<div class="bk-layout">
    <div class="bk-main">

        @if(session('status'))
            <div style="background:rgba(16,185,129,0.10);border:1px solid rgba(16,185,129,0.30);color:var(--ok-text);border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;font-weight:600;">{{ $errors->first() }}</div>
        @endif

        <div class="bk-bar">
            <div class="bk-tabs">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('client.bookings.index', array_filter(['tab' => $key === 'all' ? null : $key, 'q' => request('q')])) }}"
                       class="bk-tab {{ $tab === $key ? 'is-active' : '' }}">
                        {{ $label }} <span class="n">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
            <form method="GET" class="bk-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by event or professional…">
                @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
            </form>
        </div>

        <div class="bk-cards">
            @forelse($bookings as $booking)
                @php
                    $event    = $booking->event;
                    $deposit  = $deposits->get(($booking->event_id ?? '') . ':' . ($booking->supplier_id ?? ''));
                    $moves    = $booking->allowedTransitionsFor(auth()->user());
                    $canReview = $booking->status === 'completed' && ! in_array($booking->id, $reviewedBookingIds, true);
                @endphp
                <div class="bk-card">
                    <div class="bk-head">
                        <div class="bk-ico">{{ strtoupper(mb_substr($event?->title ?: 'B', 0, 1)) }}</div>
                        <div style="flex:1;min-width:0;">
                            <div class="bk-title">{{ $event?->title ?: 'Booking #' . $booking->id }}</div>
                            <div class="bk-sub">
                                <b>{{ $booking->supplier?->name ?? 'Professional removed' }}</b>
                                @if($event?->categories->isNotEmpty())
                                    · {{ $event->categories->pluck('name')->join(', ') }}
                                @endif
                            </div>
                        </div>
                        <span class="bk-pill {{ $booking->status }}">{{ $booking->status }}</span>
                    </div>

                    <div class="bk-facts">
                        <span class="bk-fact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            @if($event?->starts_at)
                                <b>{{ $event->starts_at->format('M d, Y') }}</b>
                            @else
                                <span class="muted">Date not set</span>
                            @endif
                        </span>
                        <span class="bk-fact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            @if($event?->location)
                                <b>{{ $event->location }}</b>
                            @else
                                <span class="muted">Location not set</span>
                            @endif
                        </span>
                        <span class="bk-fact">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            @if($booking->price)
                                Agreed <b>${{ number_format((float) $booking->price) }}</b>
                            @else
                                <span class="muted">No price recorded</span>
                            @endif
                        </span>
                        @if($deposit)
                            <span class="bk-fact">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                Deposit <b>${{ number_format((float) $deposit->amount) }}</b> paid
                                {{-- Test-mode deposits exist so the flow can be walked through before
                                     live payments are switched on; say so rather than let a screenshot
                                     imply money moved. --}}
                                @if(($deposit->metadata['mode'] ?? null) === 'test')
                                    <span class="muted">(test mode)</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="bk-actions">
                        <a href="{{ route('client.chat.index') }}" class="bk-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Message
                        </a>
                        @if($event)
                            <a href="{{ route('client.events.show', $event) }}" class="bk-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View event
                            </a>
                        @endif
                        @if($canReview)
                            <a href="{{ route('client.reviews.index') }}" class="bk-btn primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 13.7 18.5 21 12 17 5.5 21 7.5 13.7 2 9.3 9 9"/></svg>
                                Leave a review
                            </a>
                        @endif
                        {{-- Only the transitions the state machine actually allows this
                             client on this booking — no button that will be refused. --}}
                        @foreach($moves as $to)
                            <form method="POST" action="{{ route('client.bookings.update-status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $to }}">
                                <button type="submit" class="bk-btn {{ $to === 'cancelled' ? 'danger' : 'primary' }}">
                                    {{ $to === 'confirmed' ? 'Confirm booking' : ($to === 'cancelled' ? 'Cancel' : ucfirst($to)) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bk-empty">
                    <h3>No bookings here</h3>
                    <p>
                        @if(request('q'))
                            Nothing matches “{{ request('q') }}”.
                        @elseif($tab === 'all')
                            A booking appears once you finalize with a professional.
                        @else
                            Nothing in {{ $tabs[$tab] }} right now.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if($bookings->hasPages())
            <div style="margin-top:16px;">{{ $bookings->onEachSide(1)->links() }}</div>
        @endif
    </div>

    <aside class="bk-rail">
        <div class="bk-rc">
            <h4>By status</h4>
            @php
                $slices = [
                    ['In Progress', $counts['in_progress'], '#059669'],
                    ['Upcoming',    $counts['upcoming'],    '#4338ca'],
                    ['Completed',   $counts['completed'],   '#6d28d9'],
                    ['Pending',     $counts['pending'],     '#b45309'],
                    ['Cancelled',   $counts['cancelled'],   '#b91c1c'],
                ];
                $shown = array_sum(array_column($slices, 1));
                $cursor = 0; $stops = [];
                foreach ($slices as [$lbl, $val, $col]) {
                    $deg = $shown > 0 ? ($val / $shown) * 360 : 0;
                    $stops[] = "{$col} {$cursor}deg " . ($cursor + $deg) . 'deg';
                    $cursor += $deg;
                }
                // With nothing booked the ring would be a single 0deg stop and
                // render as a solid disc; draw it as an empty track instead.
                $ring = $shown > 0
                    ? 'conic-gradient(' . implode(', ', $stops) . ')'
                    : 'var(--bg-card-hover)';
            @endphp
            <div class="bk-donut" style="background: {{ $ring }};">
                <div class="hole"></div>
                <div class="mid"><span class="n">{{ $counts['all'] }}</span><span class="l">Total</span></div>
            </div>
            <div class="bk-leg">
                @foreach($slices as [$lbl, $val, $col])
                    <div class="row">
                        <span class="dot" style="background:{{ $col }};"></span>
                        <span class="l">{{ $lbl }}</span>
                        <span class="v">{{ $val }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bk-rc">
            <h4>Money</h4>
            <div class="bk-fin"><span class="l">Agreed total</span><span class="v">${{ number_format($financial['agreed_total']) }}</span></div>
            <div class="bk-fin"><span class="l">Deposits paid</span><span class="v">${{ number_format($financial['deposits_paid']) }}</span></div>
            <div class="bk-fin"><span class="l">Still outstanding</span><span class="v">${{ number_format($financial['outstanding']) }}</span></div>
            <p class="bk-note">Agreed total covers every booking except cancelled ones. Deposits are the payments already taken against them.</p>
        </div>

        @if($nextEvents->isNotEmpty())
            <div class="bk-rc">
                <h4>Next up</h4>
                @foreach($nextEvents as $b)
                    <div class="bk-next">
                        <div class="d">
                            <div class="m">{{ $b->event->starts_at->format('M') }}</div>
                            <div class="n">{{ $b->event->starts_at->format('d') }}</div>
                        </div>
                        <div class="b">
                            <div class="t">{{ $b->event->title }}</div>
                            <div class="s">{{ $b->supplier?->name ?? 'Professional' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="bk-rc">
            <h4>Quick actions</h4>
            <div class="bk-qa">
                <a href="{{ route('client.post-event.choose') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Post an event
                </a>
                <a href="{{ route('client.payments.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Payments
                </a>
                <a href="{{ route('client.reviews.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15 9 22 9.3 16.5 13.7 18.5 21 12 17 5.5 21 7.5 13.7 2 9.3 9 9"/></svg>
                    Reviews
                </a>
            </div>
        </div>
    </aside>
</div>
@endsection
