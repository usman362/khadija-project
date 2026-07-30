@extends('layouts.landing')

@section('title', 'Welcome to GigResource')

@push('styles')
<style>
    .wc-wrap { background: var(--bg-soft, #f8fafc); min-height: 70vh; padding: 56px 20px 80px; }
    .wc { max-width: 620px; margin: 0 auto; background: #fff; border: 1px solid var(--line, #e7ebf2); border-radius: 20px; padding: 40px 42px; }
    .wc-ic { width: 62px; height: 62px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
    .wc-ic svg { width: 30px; height: 30px; color: #fff; }
    .wc-ic.ok   { background: linear-gradient(135deg, #34d399, #047857); }
    .wc-ic.soon { background: linear-gradient(135deg, #fb923c, #c2410c); }
    .wc h1 { font-size: 27px; font-weight: 800; color: var(--ink, #0f1b35); letter-spacing: -.4px; margin: 0 0 10px; }
    .wc p  { font-size: 15px; color: var(--muted, #64748b); line-height: 1.6; margin: 0 0 14px; }
    .wc p b { color: var(--ink, #0f1b35); }
    .wc-where { display: inline-block; background: var(--bg-soft, #f1f5f9); border-radius: 999px; padding: 5px 14px; font-size: 13.5px; font-weight: 700; color: var(--ink, #0f1b35); }
    .wc-hr { height: 1px; background: var(--line, #e7ebf2); margin: 26px 0; }
    .wc-sub { font-size: 16px; font-weight: 800; color: var(--ink, #0f1b35); margin: 0 0 8px; }
    .wc-check { display: flex; gap: 11px; align-items: flex-start; background: var(--bg-soft, #f8fafc); border: 1px solid var(--line, #e7ebf2); border-radius: 12px; padding: 14px 16px; cursor: pointer; }
    .wc-check input { width: 17px; height: 17px; margin-top: 1px; accent-color: #ea580c; flex-shrink: 0; }
    .wc-check span { font-size: 14px; color: var(--ink, #0f1b35); font-weight: 600; }
    .wc-note { font-size: 12.5px; color: var(--muted, #64748b); margin-top: 9px; }
    .wc-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 26px; }
    .wc-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 11px; font-size: 14.5px; font-weight: 800; text-decoration: none; cursor: pointer; border: 1px solid var(--line, #e7ebf2); background: #fff; color: var(--ink, #0f1b35); font-family: inherit; }
    .wc-btn.primary { background: #ea580c; border-color: #ea580c; color: #fff; }
    .wc-ok { background: rgba(16,185,129,0.10); border: 1px solid rgba(16,185,129,0.30); color: #047857; border-radius: 10px; padding: 10px 14px; font-size: 13.5px; font-weight: 700; margin-bottom: 18px; }
</style>
@endpush

@section('content')
<div class="wc-wrap">
    <div class="wc">
        @if(session('status'))
            <div class="wc-ok">{{ session('status') }}</div>
        @endif

        @if($supported)
            <div class="wc-ic ok">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h1>Welcome to GigResource, {{ \Illuminate\Support\Str::before($user->name, ' ') }}!</h1>
            <p>Your account has been created and you have <b>full access</b> to the platform.</p>
            <p>GigResource is available in <span class="wc-where">{{ $where }}</span> — you can start browsing professionals and posting requests right away.</p>

            <div class="wc-btns">
                <a href="{{ url('/dashboard') }}" class="wc-btn primary">Go to my dashboard</a>
            </div>
        @else
            <div class="wc-ic soon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <h1>Thank you for registering with GigResource!</h1>
            <p>We're excited to have you join us. Your account has been <b>successfully created and saved</b>.</p>
            <p>At this time, GigResource is not yet available in your area — <span class="wc-where">{{ $where }}</span>.</p>

            <div class="wc-hr"></div>

            <div class="wc-sub">Stay updated on expansion</div>
            <p style="margin-bottom:14px;">Would you like to be notified when GigResource becomes available in your area?</p>

            <form method="POST" action="{{ route('register.welcome.opt-in') }}">
                @csrf
                <label class="wc-check">
                    <input type="checkbox" name="expansion_opt_in" value="1" @checked($optedIn)>
                    <span>Yes, notify me when GigResource launches in my region</span>
                </label>
                <div class="wc-note">You can update this preference anytime in your account settings.</div>

                <div class="wc-btns">
                    <button type="submit" class="wc-btn primary">Save my preference</button>
                    <a href="{{ url('/dashboard') }}" class="wc-btn">Continue to my account</a>
                </div>
            </form>

            <div class="wc-hr"></div>
            <p style="margin:0;">We appreciate your interest and look forward to serving you in the future.</p>
        @endif
    </div>
</div>
@endsection
