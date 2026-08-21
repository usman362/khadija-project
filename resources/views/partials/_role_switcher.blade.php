{{-- Role switcher — shows for client/supplier users.
     Behavior:
     - If user has BOTH roles → compact toggle showing the *other* mode
     - If user has ONLY one role → subtle "Enable X Mode" button --}}

@php
    $__u = auth()->user();
    $__hasClient  = $__u?->hasRole(\App\Domain\Auth\Enums\RoleName::CLIENT->value);
    $__hasSupplier = $__u?->hasRole(\App\Domain\Auth\Enums\RoleName::PROFESSIONAL->value);
    $__isAdmin    = $__u?->isAdmin();
    $__active     = $__u?->activeRole();

    /*
     * The button names the portal you are NOT looking at — which is not always
     * the opposite of your active role.
     *
     * A shared page can draw the client chrome around a professional. When
     * that happened, this button read from the role and offered "Switch to
     * Client", pointing further into the portal the user was trying to leave,
     * and the professional sidebar was then reachable only with the browser's
     * Back button. So the chrome decides, and each layout says which one it is.
     *
     * For a user whose chrome and role already agree — everyone, normally —
     * this is the same button it always was.
     */
    $__portal = $portal ?? $__active;
@endphp

@if($__u && !$__isAdmin && ($__hasClient || $__hasSupplier))
<div class="role-switcher">
    @if($__hasClient && $__hasSupplier)
        {{-- Both roles: show compact toggle to switch to the OTHER one --}}
        @php
            $__target    = $__portal === 'professional' ? 'client' : 'professional';
            $__targetLbl = $__target === 'professional' ? 'Professional' : 'Client';
        @endphp
        {{-- The current-mode pill is gone: it labelled the portal you were
             already looking at, next to a button that names the other one. --}}
        <form action="{{ route('role.switch') }}" method="POST" class="rs-form">
            @csrf
            <input type="hidden" name="role" value="{{ $__target }}">
            <button type="submit" class="rs-btn" title="Switch to {{ $__targetLbl }} mode">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                Switch to {{ $__targetLbl }}
            </button>
        </form>
    @else
        {{-- Only one role: quick-enable the other (opens shared modal) --}}
        @php
            $__target    = $__hasClient ? 'professional' : 'client';
            $__targetLbl = $__target === 'professional' ? 'Professional' : 'Client';
        @endphp
        <button type="button" class="rs-btn rs-btn-enable"
                data-role-enable="{{ $__target }}"
                title="Enable {{ $__targetLbl }} mode">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Become a {{ $__targetLbl }}
        </button>
    @endif
</div>
@endif

<style>
    .role-switcher {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 6px;
    }

    .rs-form { margin: 0; }
    .rs-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        white-space: nowrap;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: #fff;
        border: none;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .rs-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(249,115,22,0.35);
    }
    .rs-btn-enable {
        background: transparent;
        border: 1.5px solid rgba(99,102,241,0.4);
        color: #a5b4fc;
    }
    .rs-btn-enable:hover {
        background: rgba(99,102,241,0.1);
        border-color: #6366f1;
    }
    [data-theme="light"] .rs-btn-enable {
        color: #4f46e5;   /* 4.47 -> 6.29 on white */
    }

    @media (max-width: 768px) {
        .rs-btn { padding: 6px 10px; font-size: 0.72rem; }
    }
</style>
