@extends('layouts.client')

@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('page-subtitle', 'Contracts, payments and progress.')

@push('styles')
<style>
    /* Same shape as before — stat tiles, a detailed card body, a progress
       strip, a sticky rail — but every value is read from the database.
       The columns that had nothing behind them (tax status, document lists,
       a summariser, invented milestone dates) are gone; what replaced them
       is the booking's own agreement-log history. */

    .bk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 290px; gap: 18px; align-items: start; }
    .bk-main { min-width: 0; }
    .bk-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    /* ── Stat tiles double as the status filter ──────────────── */
    .bk-stats { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 10px; margin-bottom: 16px; }
    .bk-stat {
        display: flex; align-items: center; gap: 11px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius); padding: 13px 14px;
        text-decoration: none; transition: border-color .15s, background .15s;
    }
    .bk-stat:hover { background: var(--bg-card-hover); }
    .bk-stat.is-active { border-color: #ea580c; background: rgba(234,88,12,0.06); }
    .bk-stat-ico { width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .bk-stat-ico svg { width: 16px; height: 16px; }
    .bk-stat-ico.brand  { background: rgba(234,88,12,0.12);  color: var(--brand-text); }
    .bk-stat-ico.indigo { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .bk-stat-ico.amber  { background: rgba(245,158,11,0.12); color: var(--warn-text); }
    .bk-stat-ico.green  { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .bk-stat-ico.violet { background: rgba(139,92,246,0.12); color: var(--accent-text); }
    .bk-stat-ico.red    { background: rgba(239,68,68,0.12);  color: var(--bad-text); }
    .bk-stat-n { font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1.05; }
    .bk-stat-l { font-size: 11px; color: var(--text-muted); font-weight: 600; }

    .bk-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .bk-showing { font-size: 12.5px; color: var(--text-muted); }
    .bk-showing b { color: var(--text-primary); }
    .bk-search { position: relative; min-width: 240px; flex: 0 1 320px; }
    .bk-search input {
        width: 100%; height: 36px; box-sizing: border-box;
        padding: 0 14px 0 34px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font-size: 12.5px; font-family: inherit; outline: none;
    }
    .bk-search input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.12); }
    .bk-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted); }

    /* ── Booking card ────────────────────────────────────────── */
    .bk-cards { display: flex; flex-direction: column; gap: 14px; }
    .bk-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); overflow: hidden; }
    .bk-head { display: flex; align-items: flex-start; gap: 12px; padding: 16px 18px; }
    .bk-ico {
        width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(234,88,12,0.12); color: var(--brand-text);
        font-weight: 800; font-size: 16px;
    }
    .bk-title { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .bk-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .bk-sub b { color: var(--text-secondary); font-weight: 600; }
    .bk-verified { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: var(--ok-text); }
    .bk-verified svg { width: 12px; height: 12px; }
    .bk-pill {
        margin-left: auto; flex-shrink: 0;
        font-size: 10.5px; font-weight: 800; letter-spacing: .3px;
        padding: 4px 11px; border-radius: 999px; text-transform: uppercase;
    }
    .bk-pill.confirmed { background: rgba(16,185,129,0.14); color: var(--ok-text); }
    .bk-pill.requested { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .bk-pill.completed { background: rgba(99,102,241,0.14); color: var(--accent-text); }
    .bk-pill.cancelled { background: rgba(239,68,68,0.14);  color: var(--bad-text); }

    /* three-column body */
    .bk-body { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0; border-top: 1px solid var(--border-color); }
    .bk-col { padding: 15px 18px; }
    .bk-col + .bk-col { border-left: 1px solid var(--border-color); }
    .bk-col-h {
        display: flex; align-items: center; gap: 7px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase;
        color: var(--text-muted); margin-bottom: 11px;
    }
    .bk-col-h svg { width: 13px; height: 13px; }
    .bk-kv { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 12.5px; padding: 4px 0; }
    .bk-kv .k { color: var(--text-muted); white-space: nowrap; }
    .bk-kv .v { color: var(--text-primary); font-weight: 700; text-align: right; min-width: 0; }
    .bk-kv .v.muted { color: var(--text-muted); font-weight: 500; font-style: italic; }
    .bk-kv .v.money { font-size: 13.5px; }
    .bk-tag { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 999px; margin-left: 6px; }
    .bk-tag.paid { background: rgba(16,185,129,0.14); color: var(--ok-text); }
    .bk-tag.test { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .bk-stars { color: var(--warn-text); letter-spacing: 1px; }

    /* progress strip built from the agreement log */
    .bk-track { padding: 14px 18px 16px; border-top: 1px solid var(--border-color); background: var(--bg-card-hover); }
    .bk-track-h { font-size: 10.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; }
    .bk-steps { display: flex; align-items: flex-start; }
    .bk-step { flex: 1; position: relative; text-align: center; min-width: 0; }
    .bk-step::before {
        content: ''; position: absolute; top: 9px; left: -50%; width: 100%; height: 2px;
        background: var(--border-color);
    }
    .bk-step:first-child::before { display: none; }
    .bk-step.done::before { background: #059669; }
    .bk-step.stopped::before { background: #b91c1c; }
    .bk-dot {
        position: relative; z-index: 1; width: 20px; height: 20px; border-radius: 50%;
        margin: 0 auto 7px; display: flex; align-items: center; justify-content: center;
        background: var(--bg-card); border: 2px solid var(--border-color); color: #fff;
    }
    .bk-dot svg { width: 11px; height: 11px; }
    .bk-step.done .bk-dot    { background: #059669; border-color: #059669; }
    .bk-step.stopped .bk-dot { background: #b91c1c; border-color: #b91c1c; }
    .bk-step-l { font-size: 11.5px; font-weight: 700; color: var(--text-primary); }
    .bk-step.pending .bk-step-l { color: var(--text-muted); font-weight: 600; }
    .bk-step-d { font-size: 10.5px; color: var(--text-muted); margin-top: 1px; }

    .bk-actions { display: flex; flex-wrap: wrap; gap: 8px; padding: 13px 18px; border-top: 1px solid var(--border-color); }
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

    .bk-empty { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 56px 20px; text-align: center; }
    .bk-empty h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 0 0 5px; }
    .bk-empty p  { font-size: 13px; color: var(--text-muted); margin: 0; }

    /* ── Rail ────────────────────────────────────────────────── */
    .bk-rc { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 15px 16px; }
    .bk-rc h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; }
    .bk-donut { position: relative; width: 122px; height: 122px; margin: 0 auto 13px; border-radius: 50%; }
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
    .bk-fin + .bk-fin { border-top: 1px solid var(--border-color); }
    .bk-fin .l { color: var(--text-muted); }
    .bk-fin .v { font-weight: 800; color: var(--text-primary); }
    .bk-note { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 9px 0 0; padding-top: 9px; border-top: 1px solid var(--border-color); }
    .bk-rail-link { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; font-size: 12px; font-weight: 700; color: var(--brand-text); text-decoration: none; }
    .bk-rail-link svg { width: 12px; height: 12px; }

    .bk-next { display: flex; gap: 11px; align-items: center; padding: 8px 0; }
    .bk-next + .bk-next { border-top: 1px solid var(--border-color); }
    .bk-next .d { flex-shrink: 0; width: 36px; text-align: center; }
    .bk-next .d .m { font-size: 9.5px; font-weight: 800; color: var(--brand-text); text-transform: uppercase; }
    .bk-next .d .n { font-size: 16px; font-weight: 800; color: var(--text-primary); line-height: 1; }
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

    @media (max-width: 1240px) { .bk-stats { grid-template-columns: repeat(3, minmax(0,1fr)); } }
    @media (max-width: 1100px) {
        .bk-layout { grid-template-columns: 1fr; }
        .bk-rail { position: static; }
    }
    @media (max-width: 860px) {
        .bk-body { grid-template-columns: 1fr; }
        .bk-col + .bk-col { border-left: none; border-top: 1px solid var(--border-color); }
        .bk-stats { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .bk-steps { flex-direction: column; gap: 12px; }
        .bk-step { display: flex; align-items: center; gap: 10px; text-align: left; }
        .bk-step::before { display: none; }
        .bk-dot { margin: 0; }
    }
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

        {{-- The tiles are the status filter. They used to sit above a row of tabs
             carrying the same six numbers; one control now does both jobs. --}}
        @php
            $tiles = [
                ['all',         'All Bookings', 'brand',  '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
                ['upcoming',    'Upcoming',     'indigo', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ['in_progress', 'In Progress',  'amber',  '<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>'],
                ['pending',     'Pending',      'violet', '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
                ['completed',   'Completed',    'green',  '<polyline points="20 6 9 17 4 12"/>'],
                ['cancelled',   'Cancelled',    'red',    '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
            ];
        @endphp
        <div class="bk-stats">
            @foreach($tiles as [$key, $label, $tone, $path])
                <a href="{{ route('client.bookings.index', array_filter(['tab' => $key === 'all' ? null : $key, 'q' => request('q')])) }}"
                   class="bk-stat {{ $tab === $key ? 'is-active' : '' }}">
                    <span class="bk-stat-ico {{ $tone }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
                    </span>
                    <span>
                        <span class="bk-stat-n">{{ $counts[$key] }}</span>
                        <span class="bk-stat-l" style="display:block;">{{ $label }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="bk-bar">
            <div class="bk-showing">
                Showing <b>{{ $bookings->count() }}</b> of <b>{{ $bookings->total() }}</b>
                {{ $tab === 'all' ? 'bookings' : strtolower($tabs[$tab]) }}
                @if(request('q')) matching “{{ request('q') }}” @endif
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
                    $event   = $booking->event;
                    $pro     = $booking->supplier;
                    $profile = $pro?->profile;
                    $deposit = $deposits->get(($booking->event_id ?? '') . ':' . ($booking->supplier_id ?? ''));
                    $moves   = $booking->allowedTransitionsFor(auth()->user());
                    $review  = $myReviews->get($booking->id);
                    $log     = $history->get($booking->id) ?? collect();
                    $agreement = $booking->latestAgreement;

                    $price      = (float) ($booking->price ?? 0);
                    $paid       = (float) ($deposit->amount ?? 0);
                    $cancelled  = $booking->status === 'cancelled';

                    // When each state was reached. The log records transitions, so
                    // the entry point is the booking's own creation.
                    $reachedAt = ['requested' => $booking->created_at];
                    foreach ($log as $entry) {
                        $reachedAt[$entry->to_status] = $entry->created_at;
                    }
                    if ($booking->booked_at) {
                        $reachedAt['confirmed'] ??= $booking->booked_at;
                    }
                    $order = ['requested' => 0, 'confirmed' => 1, 'completed' => 2];
                    $now   = $order[$booking->status] ?? 0;
                @endphp
                <div class="bk-card">
                    <div class="bk-head">
                        <div class="bk-ico">{{ strtoupper(mb_substr($event?->title ?: 'B', 0, 1)) }}</div>
                        <div style="flex:1;min-width:0;">
                            <div class="bk-title">{{ $event?->title ?: 'Booking #' . $booking->id }}</div>
                            <div class="bk-sub">
                                <b>{{ $pro?->name ?? 'Professional removed' }}</b>
                                @if($profile?->trade_license_verified_at)
                                    <span class="bk-verified">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified
                                    </span>
                                @endif
                                @if($event?->categories->isNotEmpty())
                                    · {{ $event->categories->pluck('name')->join(', ') }}
                                @endif
                            </div>
                        </div>
                        <span class="bk-pill {{ $booking->status }}">{{ $booking->status }}</span>
                    </div>

                    <div class="bk-body">
                        {{-- Event --}}
                        <div class="bk-col">
                            <div class="bk-col-h">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                Event
                            </div>
                            <div class="bk-kv">
                                <span class="k">Date</span>
                                <span class="v {{ $event?->starts_at ? '' : 'muted' }}">{{ $event?->starts_at?->format('M d, Y') ?? 'Not set' }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Time</span>
                                <span class="v {{ $event?->starts_at ? '' : 'muted' }}">
                                    @if($event?->starts_at)
                                        {{ $event->starts_at->format('g:i A') }}@if($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif
                                    @else
                                        Not set
                                    @endif
                                </span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Location</span>
                                <span class="v {{ ($event?->venue || $event?->location) ? '' : 'muted' }}">{{ $event?->venue ?: ($event?->location ?: 'Not set') }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Guests</span>
                                <span class="v {{ $event?->guest_count ? '' : 'muted' }}">{{ $event?->guest_count ? number_format($event->guest_count) : 'Not set' }}</span>
                            </div>
                        </div>

                        {{-- Money --}}
                        <div class="bk-col">
                            <div class="bk-col-h">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                Money
                            </div>
                            <div class="bk-kv">
                                <span class="k">Agreed price</span>
                                <span class="v money {{ $price > 0 ? '' : 'muted' }}">{{ $price > 0 ? '$' . number_format($price) : 'Not recorded' }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Deposit</span>
                                <span class="v {{ $deposit ? '' : 'muted' }}">
                                    @if($deposit)
                                        ${{ number_format($paid) }}
                                        {{-- Test-mode deposits let the flow be walked through before live
                                             payments are on; label it so a screenshot can't imply otherwise. --}}
                                        <span class="bk-tag {{ ($deposit->metadata['mode'] ?? null) === 'test' ? 'test' : 'paid' }}">{{ ($deposit->metadata['mode'] ?? null) === 'test' ? 'Test' : 'Paid' }}</span>
                                    @else
                                        None taken
                                    @endif
                                </span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Outstanding</span>
                                <span class="v {{ $price > 0 ? '' : 'muted' }}">{{ $price > 0 ? '$' . number_format(max(0, $price - $paid)) : '—' }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Currency</span>
                                <span class="v">{{ $booking->currency ?: 'USD' }}</span>
                            </div>
                        </div>

                        {{-- Record --}}
                        <div class="bk-col">
                            <div class="bk-col-h">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Record
                            </div>
                            <div class="bk-kv">
                                <span class="k">Booking ID</span>
                                <span class="v">#{{ $booking->id }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Created</span>
                                <span class="v">{{ $booking->created_at?->format('M d, Y') ?? '—' }}</span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Contract</span>
                                <span class="v {{ $agreement ? '' : 'muted' }}">
                                    @if(! $agreement)
                                        Not created
                                    @elseif($agreement->isFullyAccepted())
                                        Signed by both <span class="bk-tag paid">✓</span>
                                    @elseif($agreement->isRejected())
                                        Rejected
                                    @elseif($agreement->clientAccepted())
                                        Awaiting professional
                                    @else
                                        Awaiting your signature
                                    @endif
                                </span>
                            </div>
                            <div class="bk-kv">
                                <span class="k">Your review</span>
                                <span class="v {{ $review ? 'bk-stars' : 'muted' }}">
                                    {{ $review ? str_repeat('★', (int) $review->rating) . str_repeat('☆', 5 - (int) $review->rating) : 'Not left yet' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Progress: the statuses this booking actually passed through,
                         dated from the agreement log. Nothing is projected. --}}
                    <div class="bk-track">
                        <div class="bk-track-h">Progress</div>
                        <div class="bk-steps">
                            @php
                                $steps = [
                                    ['requested', 'Requested'],
                                    ['confirmed', 'Confirmed'],
                                    ['completed', 'Completed'],
                                ];
                                if ($cancelled) {
                                    $steps = [['requested', 'Requested'], ['cancelled', 'Cancelled']];
                                }
                            @endphp
                            @foreach($steps as $i => [$key, $label])
                                @php
                                    $at    = $reachedAt[$key] ?? null;
                                    $state = $cancelled
                                        ? ($key === 'cancelled' ? 'stopped' : 'done')
                                        : (($order[$key] ?? 99) <= $now ? 'done' : 'pending');
                                @endphp
                                <div class="bk-step {{ $state }}">
                                    <div class="bk-dot">
                                        @if($state === 'done')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        @elseif($state === 'stopped')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="bk-step-l">{{ $label }}</div>
                                        {{-- Only a reached step carries a date. The log is append-only,
                                             so a booking moved back to an earlier status still has the
                                             later timestamps in it — printing them under a step that has
                                             not been reached would read as a future date. --}}
                                        <div class="bk-step-d">{{ $state === 'pending' ? 'Not yet' : ($at?->format('M d, Y') ?? '—') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bk-actions">
                        <a href="{{ route('client.chat.index') }}" class="bk-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Message {{ \Illuminate\Support\Str::before($pro?->name ?? 'pro', ' ') }}
                        </a>
                        @if($event)
                            <a href="{{ route('client.events.show', $event) }}" class="bk-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View event
                            </a>
                        @endif
                        {{-- The PDF route refuses anything not accepted by both sides,
                             so the button only appears when it would actually work. --}}
                        @if($agreement?->isFullyAccepted())
                            <a href="{{ route('app.agreements.download', $agreement) }}" class="bk-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Contract PDF
                            </a>
                        @elseif($agreement)
                            <a href="{{ route('app.agreements.show', $agreement) }}" class="bk-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                View contract
                            </a>
                        @endif
                        @if($booking->status === 'completed' && ! $review)
                            <a href="{{ route('client.reviews.index') }}" class="bk-btn primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15 9 22 9.3 16.5 13.7 18.5 21 12 17 5.5 21 7.5 13.7 2 9.3 9 9"/></svg>
                                Leave a review
                            </a>
                        @endif
                        {{-- Only the transitions the state machine will actually accept
                             from this client on this booking. --}}
                        @foreach($moves as $to)
                            <form method="POST" action="{{ route('client.bookings.update-status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $to }}">
                                <button type="submit" class="bk-btn {{ $to === 'cancelled' ? 'danger' : 'primary' }}">
                                    {{ $to === 'confirmed' ? 'Confirm booking' : ($to === 'cancelled' ? 'Cancel booking' : ucfirst($to)) }}
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
                // With nothing booked a single 0deg stop paints a solid disc;
                // draw an empty track instead.
                $ring = $shown > 0 ? 'conic-gradient(' . implode(', ', $stops) . ')' : 'var(--bg-card-hover)';
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
            <a href="{{ route('client.payments.index') }}" class="bk-rail-link">
                See all payments
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
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
