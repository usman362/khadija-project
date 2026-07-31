{{-- Shown on every portal page while an account is out of area.

     Without it the first sign anything is different is a 403 wall, which reads
     like a broken site rather than a deliberate answer. Renders nothing at all
     for a supported account, so it costs nothing on the normal path. --}}
@if(! \App\Support\ServiceArea::allows(auth()->user()) && ! auth()->user()?->hasRole('admin'))
    <div class="sab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <div class="sab-t">
            <b>We haven't opened in your area yet.</b>
            <span>Your account, profile and planning tools all work — booking and bidding switch on when we launch where you are.</span>
        </div>
        <a href="{{ route('register.welcome') }}">Notify me</a>
    </div>
    <style>
        .sab { display: flex; align-items: center; gap: 13px; background: rgba(234,88,12,0.10); border: 1px solid rgba(234,88,12,0.30); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; }
        .sab > svg { width: 19px; height: 19px; color: var(--brand-text); flex-shrink: 0; }
        .sab-t { min-width: 0; }
        .sab-t b { display: block; font-size: 13.5px; color: var(--text-primary); }
        .sab-t span { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; }
        .sab a { margin-left: auto; flex-shrink: 0; font-size: 12.5px; font-weight: 700; text-decoration: none; color: #fff; background: #ea580c; padding: 7px 15px; border-radius: 9px; }
        @media (max-width: 640px) { .sab { flex-wrap: wrap; } .sab a { margin-left: 0; } }
    </style>
@endif
