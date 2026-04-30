@extends('layouts.public')

{{--
    Shared error-page chrome.
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
        min-height: calc(100vh - 68px);
        padding: 140px 0 80px;
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
            radial-gradient(900px 420px at 18% 10%, rgba(59,130,246,0.18), transparent 55%),
            radial-gradient(800px 400px at 85% 0%, rgba(139,92,246,0.18), transparent 55%),
            radial-gradient(700px 300px at 50% 100%, rgba(249,115,22,0.10), transparent 60%);
        pointer-events: none;
    }
    .err-section .container { position: relative; z-index: 1; max-width: 720px; }

    .err-emoji {
        font-size: 72px;
        line-height: 1;
        margin-bottom: 18px;
        filter: drop-shadow(0 12px 30px rgba(139,92,246,0.30));
    }
    .err-code {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 6px 16px;
        margin-bottom: 22px;
        border-radius: 999px;
        background: rgba(139,92,246,0.14);
        border: 1px solid rgba(139,92,246,0.32);
        color: #c4b5fd;
        font-size: 12px; font-weight: 800; letter-spacing: 1.2px;
        text-transform: uppercase;
    }
    .err-code .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 0 8px rgba(139,92,246,0.6);
    }

    .err-title {
        font-size: 2.6rem; font-weight: 900;
        letter-spacing: -0.02em; line-height: 1.1;
        margin-bottom: 14px;
    }
    .err-title .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .err-tagline {
        color: var(--text-muted);
        font-size: 1.05rem;
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
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        box-shadow: 0 10px 26px rgba(139,92,246,0.35);
    }
    .err-btn-primary:hover { transform: translateY(-1px); opacity: 0.95; }
    .err-btn-ghost {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.15);
        color: #fff;
    }
    .err-btn-ghost:hover { border-color: rgba(139,92,246,0.45); background: rgba(139,92,246,0.08); }

    /* Helpful links row below the actions — for 404 specifically */
    .err-helpful {
        margin-top: 38px;
        padding-top: 26px;
        border-top: 1px dashed rgba(255,255,255,0.10);
    }
    .err-helpful h3 {
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--text-muted);
        margin-bottom: 14px;
    }
    .err-helpful-links {
        display: flex; flex-wrap: wrap; gap: 8px;
        justify-content: center;
    }
    .err-helpful-links a {
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        color: var(--text-light);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .err-helpful-links a:hover {
        background: rgba(139,92,246,0.10);
        border-color: rgba(139,92,246,0.40);
        color: #fff;
    }

    @media (max-width: 600px) {
        .err-section { padding: 110px 16px 60px; }
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
    <div class="container">
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
