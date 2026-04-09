{{-- Role switcher — shows for client/supplier users.
     Behavior:
     - If user has BOTH roles → compact toggle showing the *other* mode
     - If user has ONLY one role → subtle "Enable X Mode" button --}}

@php
    $__u = auth()->user();
    $__hasClient  = $__u?->hasRole(\App\Domain\Auth\Enums\RoleName::CLIENT->value);
    $__hasSupplier = $__u?->hasRole(\App\Domain\Auth\Enums\RoleName::SUPPLIER->value);
    $__isAdmin    = $__u?->isAdmin();
    $__active     = $__u?->activeRole();
@endphp

@if($__u && !$__isAdmin && ($__hasClient || $__hasSupplier))
<div class="role-switcher">
    @if($__hasClient && $__hasSupplier)
        {{-- Both roles: show compact toggle to switch to the OTHER one --}}
        @php
            $__target    = $__active === 'supplier' ? 'client' : 'supplier';
            $__targetLbl = $__target === 'supplier' ? 'Professional' : 'Client';
            $__currentLbl= $__active === 'supplier' ? 'Professional' : 'Client';
        @endphp
        <div class="rs-current" title="Current mode">
            <span class="rs-dot rs-dot-{{ $__active }}"></span>
            <span class="rs-label">{{ $__currentLbl }}</span>
        </div>
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
            $__target    = $__hasClient ? 'supplier' : 'client';
            $__targetLbl = $__target === 'supplier' ? 'Professional' : 'Client';
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
    .rs-current {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 30px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-secondary, #cbd5e1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    [data-theme="light"] .rs-current {
        background: rgba(0,0,0,0.03);
        border-color: rgba(0,0,0,0.08);
    }
    .rs-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .rs-dot-client   { background: #3b82f6; box-shadow: 0 0 6px rgba(59,130,246,0.6); }
    .rs-dot-supplier { background: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.6); }

    .rs-form { margin: 0; }
    .rs-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
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
        box-shadow: 0 4px 12px rgba(99,102,241,0.35);
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
        color: #6366f1;
    }

    @media (max-width: 768px) {
        .rs-current .rs-label { display: none; }
        .rs-btn { padding: 6px 10px; font-size: 0.72rem; }
    }
</style>
