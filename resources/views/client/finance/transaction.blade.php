@extends('layouts.client')

@section('title', 'Transaction')
@section('page-title', 'Transaction')
@section('page-subtitle', 'One booking, and everything we know about the money on it.')

@push('styles')
<style>
    .tx-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .tx-grid { grid-template-columns: 1fr; } }
    .tx-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 20px; }
    .tx-card + .tx-card { margin-top: 14px; }
    .tx-h { font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 0 0 14px; }
    .tx-amount { font-size: 34px; font-weight: 900; color: var(--text-primary); line-height: 1.1; }
    .tx-badge { display: inline-block; padding: 4px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 800; margin-top: 10px; }
    .tx-badge.amber  { background: rgba(245,158,11,0.13); color: var(--warn-text, #b45309); }
    .tx-badge.indigo { background: rgba(99,102,241,0.13); color: var(--accent-text, #4f46e5); }
    .tx-badge.green  { background: rgba(16,185,129,0.13); color: var(--ok-text, #047857); }
    .tx-badge.slate  { background: rgba(100,116,139,0.13); color: #475569; }
    .tx-money-line { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; margin-top: 12px; }
    .tx-line { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 10px 0; border-top: 1px solid var(--border-color); }
    .tx-line:first-of-type { border-top: 0; padding-top: 0; }
    .tx-line .l { color: var(--text-muted); }
    .tx-line .v { color: var(--text-primary); font-weight: 700; text-align: right; }
    .tx-line .v a { color: var(--brand-text); text-decoration: none; }
    .tx-tl { list-style: none; margin: 0; padding: 0; }
    .tx-tl li { position: relative; padding: 0 0 16px 22px; border-left: 2px solid var(--border-color); }
    .tx-tl li:last-child { padding-bottom: 0; border-left-color: transparent; }
    .tx-tl li::before { content: ''; position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; border-radius: 50%;
                        background: var(--bg-card); border: 2px solid var(--border-color); }
    .tx-tl li.done::before { background: var(--ok-text, #10b981); border-color: var(--ok-text, #10b981); }
    .tx-tl .st { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .tx-tl .by { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .tx-empty { font-size: 12.5px; color: var(--text-muted); }
    .tx-acts { display: flex; flex-direction: column; gap: 9px; margin-top: 14px; }
    .tx-btn { display: block; text-align: center; padding: 10px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 800; text-decoration: none; }
    .tx-btn.primary { background: var(--brand-text, #f97316); color: #fff; }
    .tx-btn.ghost { border: 1px solid var(--border-color); color: var(--text-primary); }
    .tx-back { display: inline-block; font-size: 12.5px; font-weight: 700; color: var(--brand-text); text-decoration: none; margin-bottom: 14px; }
</style>
@endpush

@section('content')
<a href="{{ route('client.payments.index') }}" class="tx-back">← All payments</a>

<div class="tx-grid">
    <div>
        <div class="tx-card">
            <p class="tx-h">Amount</p>
            <div class="tx-amount">
                @if ($booking->price !== null)
                    ${{ number_format((float) $booking->price, 2) }}
                @else
                    &mdash;
                @endif
            </div>
            <span class="tx-badge {{ $money['tone'] }}">{{ $money['label'] }}</span>

            {{-- The single sentence that tells the client whether money has
                 moved. No provider is wired to this app, so no screen may
                 imply that it took a payment. --}}
            <p class="tx-money-line">{{ $money['line'] }}</p>

            @if ($booking->price === null)
                <p class="tx-money-line">No amount was recorded on this booking, so there is nothing to show here yet.</p>
            @endif
        </div>

        <div class="tx-card">
            <p class="tx-h">What it is for</p>
            <div class="tx-line">
                <span class="l">Event</span>
                <span class="v">
                    @if ($booking->event)
                        <a href="{{ route('client.events.show', $booking->event) }}">{{ $booking->event->title }}</a>
                    @else &mdash; @endif
                </span>
            </div>
            <div class="tx-line">
                <span class="l">Date</span>
                <span class="v">{{ $booking->event?->starts_at?->format('D, M j, Y') ?? $booking->booked_at?->format('D, M j, Y') ?? '—' }}</span>
            </div>
            @if ($booking->event?->location)
                <div class="tx-line"><span class="l">Location</span><span class="v">{{ $booking->event->location }}</span></div>
            @endif
            <div class="tx-line">
                <span class="l">Professional</span>
                <span class="v"><a href="{{ route('public.professional.show', $booking->supplier) }}">{{ $booking->supplier?->name ?? '—' }}</a></span>
            </div>
            @if ($booking->supplier?->profile?->company_name)
                <div class="tx-line"><span class="l">Business</span><span class="v">{{ $booking->supplier->profile->company_name }}</span></div>
            @endif
            <div class="tx-line">
                <span class="l">Came from</span>
                <span class="v">{{ \Illuminate\Support\Str::headline((string) ($booking->source ?: 'Booking')) }}</span>
            </div>
            <div class="tx-line">
                <span class="l">Reference</span>
                <span class="v">#{{ $booking->id }}</span>
            </div>
            @if ($booking->notes)
                <div class="tx-line" style="display:block">
                    <span class="l" style="display:block;margin-bottom:5px">Notes</span>
                    <span class="v" style="text-align:left;font-weight:500;white-space:pre-line">{{ $booking->notes }}</span>
                </div>
            @endif
        </div>

        <div class="tx-card">
            <p class="tx-h">History</p>
            @if ($history->count())
                <ul class="tx-tl">
                    @foreach ($history as $log)
                        <li class="done">
                            <div class="st">{{ \Illuminate\Support\Str::headline((string) $log->to_status) }}</div>
                            <div class="by">
                                {{ $log->changer?->name ?? 'System' }}
                                · {{ $log->created_at?->humanAgo() }}
                                @if ($log->created_at) ({{ $log->created_at->format('M j, Y g:i A') }}) @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tx-empty">Nothing has changed on this booking since it was created.</p>
            @endif
        </div>
    </div>

    <aside>
        <div class="tx-card">
            <p class="tx-h">What you can do</p>
            <div class="tx-acts">
                <a href="{{ route('client.bookings.index') }}" class="tx-btn primary">Open in Bookings</a>
                <a href="{{ route('client.chat.index') }}" class="tx-btn ghost">Message {{ $booking->supplier?->name }}</a>
                @if (in_array($booking->status, ['requested', 'confirmed'], true))
                    <a href="{{ route('cancellations.index') }}" class="tx-btn ghost">Cancel this booking</a>
                @endif
                <a href="{{ route('disputes.index') }}" class="tx-btn ghost">Raise a problem</a>
            </div>

            <p style="font-size:11.5px;color:var(--text-muted);line-height:1.55;margin:14px 0 0;padding-top:14px;border-top:1px solid var(--border-color)">
                GigResource does not hold or transfer money for this booking. It records what was agreed
                and who agreed it.
            </p>
        </div>
    </aside>
</div>
@endsection
