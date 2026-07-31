@extends('layouts.landing')

@section('title', 'Not available in your area yet')

@push('styles')
<style>
    .sa-wrap { background: var(--bg-soft, #f8fafc); min-height: 70vh; padding: 56px 20px 80px; }
    .sa { max-width: 580px; margin: 0 auto; background: #fff; border: 1px solid var(--line, #e7ebf2); border-radius: 20px; padding: 40px 42px; }
    .sa-ic { width: 62px; height: 62px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; background: linear-gradient(135deg, #fb923c, #c2410c); }
    .sa-ic svg { width: 30px; height: 30px; color: #fff; }
    .sa h1 { font-size: 26px; font-weight: 800; color: var(--ink, #0f1b35); letter-spacing: -.4px; margin: 0 0 12px; }
    .sa p { font-size: 15px; color: var(--muted, #64748b); line-height: 1.6; margin: 0 0 14px; }
    .sa-where { display: inline-block; background: var(--bg-soft, #f1f5f9); border-radius: 999px; padding: 5px 14px; font-size: 13.5px; font-weight: 700; color: var(--ink, #0f1b35); margin-bottom: 18px; }
    .sa-can { background: var(--bg-soft, #f8fafc); border: 1px solid var(--line, #e7ebf2); border-radius: 13px; padding: 18px 20px; margin: 22px 0 0; }
    .sa-can b { display: block; font-size: 14px; font-weight: 800; color: var(--ink, #0f1b35); margin-bottom: 10px; }
    .sa-can ul { margin: 0; padding: 0; list-style: none; }
    .sa-can li { display: flex; gap: 9px; align-items: flex-start; font-size: 14px; color: var(--ink, #0f1b35); padding: 5px 0; }
    .sa-can svg { width: 15px; height: 15px; color: #047857; flex-shrink: 0; margin-top: 3px; }
    .sa-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 26px; }
    .sa-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 11px; font-size: 14.5px; font-weight: 800; text-decoration: none; border: 1px solid var(--line, #e7ebf2); background: #fff; color: var(--ink, #0f1b35); }
    .sa-btn.primary { background: #ea580c; border-color: #ea580c; color: #fff; }
</style>
@endpush

@section('content')
<div class="sa-wrap">
    <div class="sa">
        <div class="sa-ic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>

        <h1>We haven't opened here yet</h1>
        <span class="sa-where">{{ $where }}</span>

        <p>
            Your account is active and stays that way. Booking and bidding are the
            parts that need us to operate in your state — every one of them ends in
            a contract, and we won't put you in one we can't stand behind yet.
        </p>

        <div class="sa-can">
            <b>What you can still do</b>
            <ul>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Use every planning tool — budget, timeline, checklists, capacity</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Browse professionals and packages</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Build out your profile so you're ready on day one</li>
            </ul>
        </div>

        <div class="sa-btns">
            <a class="sa-btn primary" href="{{ route('register.welcome') }}">Tell me when you launch here</a>
            <a class="sa-btn" href="{{ url('/dashboard') }}">Back to my dashboard</a>
        </div>
    </div>
</div>
@endsection
