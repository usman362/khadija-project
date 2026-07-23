@extends('layouts.landing')

{{--
    Shared error-page chrome (light UI — matches the rest of the public site).
    Each specific code (404 / 500 / 403 / 419 / 503) extends this and supplies:
        @section('err-code', '404')
        @section('err-emoji', '🧭')
        @section('err-title', 'Page not found')
        @section('err-tagline', '...short helpful sentence...')
        @section('err-actions') ...override CTAs if needed... @endsection
    The layout handles the look + main CTAs + meta tags so each page
    stays a 15-line file rather than a 300-line copy-paste.
--}}

@section('title', '@yield(\'err-code\') · ' . config('app.name'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Something went off-track. Head back to GigResource to keep planning your next great event.">
@endpush

@push('styles')
<style>
    .err-section {
        position: relative;
        min-height: 68vh;
        padding: 110px 20px 90px;
        background: var(--bg-soft, #f7f9fc);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        overflow: hidden;
    }
    .err-section::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(760px 360px at 18% 8%, rgba(37,99,235,0.07), transparent 58%),
            radial-gradient(700px 340px at 84% 0%, rgba(249,115,22,0.07), transparent 58%);
        pointer-events: none;
    }
    .err-wrap { position: relative; z-index: 1; max-width: 700px; margin: 0 auto; }

    .err-emoji {
        font-size: 72px;
        line-height: 1;
        margin-bottom: 18px;
    }
    .err-code {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 6px 16px;
        margin-bottom: 22px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--line, #e6eaf1);
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(15,27,53,.05));
        color: var(--muted, #64748b);
        font-size: 12px; font-weight: 800; letter-spacing: 1.2px;
        text-transform: uppercase;
    }
    .err-code .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--blue, #2563eb), var(--orange, #f97316));
    }

    .err-title {
        font-family: var(--ff-head, inherit);
        font-size: 2.5rem; font-weight: 800;
        letter-spacing: -0.02em; line-height: 1.15;
        color: var(--ink, #0f1b35);
        margin-bottom: 13px;
    }
    .err-title .grad {
        background: linear-gradient(135deg, var(--blue, #2563eb), var(--orange, #f97316));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .err-tagline {
        color: var(--text, #475569);
        font-size: 1.02rem;
        line-height: 1.65;
        margin: 0 auto 32px;
        max-width: 540px;
    }

    .err-actions {
        display: flex; flex-wrap: wrap; gap: 12px;
        justify-content: center;
    }
    .err-btn {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 14px 26px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14.5px;
        font-family: inherit;
        border: none;
        cursor: pointer;
        transition: transform 0.2s, opacity 0.2s, background 0.2s, border-color 0.2s;
    }
    .err-btn-primary {
        background: linear-gradient(135deg, var(--blue-light, #3b82f6), var(--blue-dark, #1d4ed8));
        color: #fff;
        box-shadow: 0 10px 24px rgba(37,99,235,0.24);
    }
    .err-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(37,99,235,0.30); }
    .err-btn-ghost {
        background: #fff;
        border-color: var(--line, #e6eaf1);
        color: var(--ink, #0f1b35);
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(15,27,53,.05));
    }
    .err-btn-ghost:hover { border-color: var(--blue, #2563eb); color: var(--blue, #2563eb); }

    /* Helpful links row below the actions — for 404 specifically */
    .err-helpful {
        margin-top: 38px;
        padding-top: 26px;
        border-top: 1px solid var(--line-soft, #eef2f7);
    }
    .err-helpful h3 {
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--faint, #94a3b8);
        margin-bottom: 13px;
    }
    .err-helpful-links {
        display: flex; flex-wrap: wrap; gap: 8px;
        justify-content: center;
    }
    .err-helpful-links a {
        padding: 8px 16px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--line, #e6eaf1);
        color: var(--text, #475569);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .err-helpful-links a:hover { border-color: var(--blue, #2563eb); color: var(--blue, #2563eb); }

    @media (max-width: 600px) {
        .err-section { padding: 80px 16px 60px; }
        .err-emoji { font-size: 56px; }
        .err-title { font-size: 1.85rem; }
        .err-tagline { font-size: 0.95rem; }
        .err-btn { padding: 12px 20px; font-size: 13.5px; width: 100%; max-width: 320px; }
        .err-actions { flex-direction: column; align-items: center; }
    }
</style>
@endpush

@section('content')
<section class="err-section">
    <div class="err-wrap">
        <div class="err-emoji" aria-hidden="true">@yield('err-emoji', '😕')</div>

        <div class="err-code">
            <span class="dot"></span>
            Error @yield('err-code', '500')
        </div>

        <h1 class="err-title">@yield('err-title', 'Something went wrong')</h1>

        <p class="err-tagline">@yield('err-tagline', 'Our team has been notified. Try going back, or head home and start again.')</p>

        <div class="err-actions">
            @hasSection('err-actions')
                @yield('err-actions')
            @else
                <a href="{{ url('/') }}" class="err-btn err-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Back to Home
                </a>
                <a href="{{ route('public.browse') }}" class="err-btn err-btn-ghost">
                    Browse Professionals
                </a>
            @endif
        </div>

        @hasSection('err-helpful')
            <div class="err-helpful">
                <h3>You might be looking for</h3>
                @yield('err-helpful')
            </div>
        @endif
    </div>
</section>
@endsection
