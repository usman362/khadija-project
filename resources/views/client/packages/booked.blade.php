@extends('layouts.client')

@section('title', 'Booking sent')
@section('page-title', 'Booking sent')
@section('page-subtitle', 'It is with the professional now.')

@push('styles')
<style>
    .bk-wrap { max-width: 620px; }
    .bk-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 24px; }
    .bk-tick { width: 46px; height: 46px; border-radius: 50%; background: rgba(16,185,129,0.12); color: var(--ok-text, #10b981);
               display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800; margin-bottom: 14px; }
    .bk-h { font-size: 18px; font-weight: 800; color: var(--text-primary); margin: 0 0 6px; }
    .bk-sub { font-size: 13px; color: var(--text-muted); line-height: 1.55; margin: 0 0 18px; }
    .bk-line { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 9px 0; border-top: 1px solid var(--border-color); }
    .bk-line .l { color: var(--text-muted); }
    .bk-line .v { color: var(--text-primary); font-weight: 700; text-align: right; }
    .bk-next { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border-color); }
    .bk-next p { font-size: 12px; font-weight: 800; color: var(--text-primary); margin: 0 0 8px; }
    .bk-next ol { margin: 0; padding-left: 18px; font-size: 12.5px; color: var(--text-primary); line-height: 1.7; }
    .bk-acts { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
    .bk-btn { padding: 10px 16px; border-radius: 9px; font-size: 12.5px; font-weight: 800; text-decoration: none; }
    .bk-btn.primary { background: var(--brand-text, #f97316); color: #fff; }
    .bk-btn.ghost { border: 1px solid var(--border-color); color: var(--text-primary); }
</style>
@endpush

@section('content')
<div class="bk-wrap">
    <div class="bk-card">
        <div class="bk-tick">✓</div>
        <h2 class="bk-h">Sent to {{ $booking->supplier?->name }}</h2>
        <p class="bk-sub">
            Nothing has been charged. They have your date and the package you picked, and the request is
            waiting on them — you will see it move in Bookings.
        </p>

        <div class="bk-line"><span class="l">Event</span><span class="v">{{ $booking->event?->title }}</span></div>
        <div class="bk-line"><span class="l">Date</span><span class="v">{{ $booking->booked_at?->format('D, M j, Y') }}</span></div>
        <div class="bk-line"><span class="l">Professional</span><span class="v">{{ $booking->supplier?->name }}</span></div>
        <div class="bk-line"><span class="l">Amount once accepted</span><span class="v">${{ number_format((float) $booking->price, 2) }}</span></div>
        <div class="bk-line"><span class="l">Status</span><span class="v">Waiting on the professional</span></div>

        <div class="bk-next">
            <p>What happens next</p>
            <ol>
                <li>{{ $booking->supplier?->name }} accepts or declines the date.</li>
                <li>If they accept, the booking becomes confirmed and you will be told what to pay and when.</li>
                <li>If they decline, nothing is owed and you can book someone else.</li>
            </ol>
        </div>

        <div class="bk-acts">
            <a href="{{ route('client.bookings.index') }}" class="bk-btn primary">View my bookings</a>
            <a href="{{ route('client.chat.index') }}" class="bk-btn ghost">Message them</a>
            <a href="{{ route('public.packages') }}" class="bk-btn ghost">Browse more packages</a>
        </div>
    </div>
</div>
@endsection
