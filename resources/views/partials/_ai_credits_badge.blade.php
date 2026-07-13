{{-- GigResource IQ™ — visible "AI Assist Credits" balance (monthly).
     Only renders for metered users when the credit economy is enabled. --}}
@php
    $creditUser = auth()->user();
    $showCredits = $creditUser
        && \App\Domain\AiFeatures\AiAccess::creditsEnabled()
        && ! $creditUser->isAdmin()
        && $creditUser->aiCreditsGrant() < PHP_INT_MAX;
@endphp
@if($showCredits)
    @php
        $grant = $creditUser->aiCreditsGrant();
        $rem   = $creditUser->aiCreditsRemaining();
        $used  = max(0, $grant - $rem);
        $pct   = $grant > 0 ? min(100, round($used / $grant * 100)) : 100;
        $low   = $grant > 0 && $rem <= max(1, $grant * 0.15);
        $out   = $rem <= 0;
        $accent = $out ? '#ef4444' : ($low ? '#f59e0b' : '#f97316');
    @endphp
    <div class="gr-credits" title="AI Assist Credits — reset on the 1st of each month">
        <svg viewBox="0 0 24 24" fill="{{ $accent }}" stroke="{{ $accent }}" stroke-width="1.2" style="width:15px;height:15px;flex-shrink:0;">
            <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>
        </svg>
        <span class="gr-credits-txt"><b>{{ number_format($rem) }}</b> / {{ number_format($grant) }} AI Credits</span>
        <span class="gr-credits-bar"><i style="width:{{ $pct }}%; background:{{ $accent }};"></i></span>
        @if($out)
            <a href="{{ route('app.membership-plans.index') }}" class="gr-credits-up">Upgrade</a>
        @endif
    </div>
    <style>
        .gr-credits { display:inline-flex; align-items:center; gap:9px; padding:7px 14px; border-radius:999px;
            background:rgba(249,115,22,.10); border:1px solid rgba(249,115,22,.28); font-size:12.5px; font-weight:800; color:#0f1b35; white-space:nowrap; }
        .gr-credits-bar { width:60px; height:6px; border-radius:999px; background:rgba(15,27,53,.10); overflow:hidden; }
        .gr-credits-bar i { display:block; height:100%; border-radius:999px; }
        .gr-credits-up { margin-left:2px; padding:3px 10px; border-radius:999px; background:#f97316; color:#fff !important; font-size:11px; text-decoration:none; }
        [data-theme="dark"] .gr-credits { color:#e2e8f0; }
        @media (max-width:640px){ .gr-credits-bar{ display:none; } }
    </style>
@endif
