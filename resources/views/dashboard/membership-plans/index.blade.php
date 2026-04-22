@extends('layouts.dashboard')

@section('title', 'Membership Plans')

@section('content')
<style>
    :root,
    [data-bs-theme="light"] {
        --mp-bg: #ffffff;
        --mp-card-bg: #ffffff;
        --mp-card-border: #e9ecef;
        --mp-card-shadow: rgba(0,0,0,0.06);
        --mp-featured-shadow: rgba(99,102,241,0.15);
        --mp-text: #212529;
        --mp-text-muted: #6c757d;
        --mp-price-color: #212529;
        --mp-feature-icon: #198754;
        --mp-feature-excluded: #dc3545;
        --mp-divider: #e9ecef;
        --mp-current-bg: #e8f5e9;
        --mp-current-border: #198754;
        --mp-current-text: #198754;
    }

    [data-bs-theme="dark"] {
        --mp-bg: #0c1427;
        --mp-card-bg: #111a2e;
        --mp-card-border: #1e2d4a;
        --mp-card-shadow: rgba(0,0,0,0.2);
        --mp-featured-shadow: rgba(99,102,241,0.25);
        --mp-text: #e1e4e8;
        --mp-text-muted: #8b949e;
        --mp-price-color: #e1e4e8;
        --mp-feature-icon: #3fb950;
        --mp-feature-excluded: #f85149;
        --mp-divider: #1e2d4a;
        --mp-current-bg: rgba(63,185,80,0.1);
        --mp-current-border: #3fb950;
        --mp-current-text: #3fb950;
    }

    .mp-page {
        background: var(--mp-bg);
        min-height: 100%;
    }

    .mp-header {
        text-align: center;
        padding: 2rem 1rem 1rem;
    }

    .mp-header h2 {
        color: var(--mp-text);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .mp-header p {
        color: var(--mp-text-muted);
        font-size: 1.05rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .mp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .mp-card {
        background: var(--mp-card-bg);
        border: 2px solid var(--mp-card-border);
        border-radius: 16px;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 12px var(--mp-card-shadow);
    }

    .mp-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px var(--mp-card-shadow);
    }

    .mp-card.featured {
        border-color: #6366f1;
        box-shadow: 0 4px 20px var(--mp-featured-shadow);
    }

    .mp-card.current-plan {
        border-color: var(--mp-current-border);
        background: var(--mp-current-bg);
    }

    .mp-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .mp-plan-name {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--mp-text);
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .mp-plan-desc {
        color: var(--mp-text-muted);
        font-size: 0.9rem;
        text-align: center;
        margin-bottom: 1.25rem;
        min-height: 2.5rem;
    }

    .mp-price-block {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .mp-price {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--mp-price-color);
        line-height: 1;
    }

    .mp-price-cycle {
        color: var(--mp-text-muted);
        font-size: 0.9rem;
        font-weight: 400;
    }

    .mp-divider {
        border: none;
        border-top: 1px solid var(--mp-divider);
        margin: 0 0 1.25rem;
    }

    .mp-limits {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .mp-limit-item {
        text-align: center;
    }

    .mp-limit-value {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--mp-text);
        display: block;
    }

    .mp-limit-label {
        font-size: 0.75rem;
        color: var(--mp-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .mp-features {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem;
        flex: 1;
    }

    .mp-features li {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.4rem 0;
        font-size: 0.9rem;
        color: var(--mp-text);
    }

    .mp-features li .icon-included {
        color: var(--mp-feature-icon);
        flex-shrink: 0;
    }

    .mp-features li .icon-excluded {
        color: var(--mp-feature-excluded);
        flex-shrink: 0;
    }

    .mp-features li.excluded {
        color: var(--mp-text-muted);
        text-decoration: line-through;
    }

    .mp-action {
        margin-top: auto;
    }

    .mp-current-label {
        text-align: center;
        color: var(--mp-current-text);
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.6rem;
        border-radius: 8px;
        border: 2px solid var(--mp-current-border);
        background: transparent;
    }

    .mp-subscribe-btn {
        width: 100%;
        padding: 0.7rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .mp-subscribe-btn:hover {
        opacity: 0.9;
    }

    .mp-subscribe-btn.featured-btn {
        background: #6366f1;
        color: #fff;
    }

    .mp-subscribe-btn.default-btn {
        background: transparent;
        border: 2px solid var(--mp-card-border);
        color: var(--mp-text);
    }

    .mp-subscribe-btn.default-btn:hover {
        border-color: #6366f1;
        color: #6366f1;
    }

    .mp-subscription-info {
        text-align: center;
        margin-top: 2rem;
        padding: 0 1.5rem 2rem;
    }

    .mp-subscription-info .card {
        background: var(--mp-card-bg);
        border-color: var(--mp-card-border);
        max-width: 600px;
        margin: 0 auto;
    }

    .mp-subscription-info .card-body {
        color: var(--mp-text);
    }

    @media (max-width: 768px) {
        .mp-grid {
            grid-template-columns: 1fr;
            padding: 1rem;
        }
    }
</style>

@if(session('status'))
    <div class="alert alert-success mx-3 mt-3">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger mx-3 mt-3">{{ session('error') }}</div>
@endif

<div class="mp-page">
    <div class="mp-header">
        <h2>Choose Your Plan</h2>
        <p>Select the perfect membership plan that fits your needs. Upgrade or downgrade anytime.</p>
    </div>

    <div class="mp-grid">
        @foreach($plans as $plan)
            @php
                $isCurrentPlan = $activeSubscription && $activeSubscription->membership_plan_id === $plan->id;
            @endphp
            <div class="mp-card {{ $plan->is_featured ? 'featured' : '' }} {{ $isCurrentPlan ? 'current-plan' : '' }}">
                @if($plan->badge_text)
                    <span class="mp-badge bg-{{ $plan->badge_color ?? 'primary' }} text-white">
                        {{ $plan->badge_text }}
                    </span>
                @endif

                <div class="mp-plan-name">{{ $plan->name }}</div>

                @if($plan->description)
                    <div class="mp-plan-desc">{{ $plan->description }}</div>
                @else
                    <div class="mp-plan-desc">&nbsp;</div>
                @endif

                <div class="mp-price-block">
                    <span class="mp-price">{{ $plan->formattedPrice() }}</span>
                    @if(!$plan->isFree())
                        <span class="mp-price-cycle">{{ $plan->billingLabel() }}</span>
                    @endif
                </div>
                @if(!$plan->isFree() && in_array($plan->billing_cycle, ['6_month', '12_month', '18_month']))
                    <div class="mp-price-note text-muted" style="font-size:12px;margin-top:-6px;margin-bottom:10px;">
                        One-time charge &middot; {{ $plan->contractTermLabel() }}
                    </div>
                @endif

                <hr class="mp-divider">

                <div class="mp-limits">
                    <div class="mp-limit-item">
                        <span class="mp-limit-value">{{ $plan->max_events ?? '∞' }}</span>
                        <span class="mp-limit-label">Events</span>
                    </div>
                    <div class="mp-limit-item">
                        <span class="mp-limit-value">{{ $plan->max_bookings ?? '∞' }}</span>
                        <span class="mp-limit-label">Bookings</span>
                    </div>
                    <div class="mp-limit-item">
                        <span class="mp-limit-value">
                            @if($plan->has_chat)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--mp-feature-icon)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--mp-feature-excluded)" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            @endif
                        </span>
                        <span class="mp-limit-label">Chat</span>
                    </div>
                </div>

                <ul class="mp-features">
                    @foreach($plan->features as $feature)
                        <li class="{{ !$feature->is_included ? 'excluded' : '' }}">
                            @if($feature->is_included)
                                <svg class="icon-included" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg class="icon-excluded" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            @endif
                            {{ $feature->feature }}
                        </li>
                    @endforeach
                </ul>

                <div class="mp-action">
                    @if($isCurrentPlan)
                        <div class="mp-current-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align: -2px; margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Current Plan
                        </div>
                    @else
                        <form method="POST" action="{{ route('app.membership-plans.subscribe', $plan) }}" id="subscribeForm{{ $plan->id }}">
                            @csrf
                            <button type="button" class="mp-subscribe-btn {{ $plan->is_featured ? 'featured-btn' : 'default-btn' }}"
                                data-mp-subscribe
                                data-plan-name="{{ $plan->name }}"
                                data-plan-price="{{ $plan->formattedPrice() }}{{ $plan->billingLabel() }}"
                                data-plan-free="{{ $plan->isFree() ? '1' : '0' }}"
                                data-form-id="subscribeForm{{ $plan->id }}">
                                @if($plan->isFree())
                                    Get Started Free
                                @elseif($activeSubscription)
                                    Switch to {{ $plan->name }}
                                @else
                                    Choose {{ $plan->name }}
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($activeSubscription)
        <div class="mp-subscription-info">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <strong>Current Plan:</strong> {{ $activeSubscription->plan->name }}
                            <span class="text-muted ms-2">
                                Since {{ $activeSubscription->starts_at->format('M d, Y') }}
                                @if($activeSubscription->expires_at)
                                    &middot; Expires {{ $activeSubscription->expires_at->format('M d, Y') }}
                                @endif
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('app.membership-plans.history') }}" class="btn btn-sm btn-outline-secondary">
                                Subscription History
                            </a>
                            <form method="POST" action="{{ route('app.membership-plans.cancel') }}" class="d-inline" id="cancelSubForm">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-mp-cancel>
                                    Cancel Plan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ── Subscribe Confirmation Modal ─────────────────────── --}}
<div id="mpModal" class="mpm-backdrop">
    <div class="mpm-card">
        <button type="button" class="mpm-close" data-mpm-close>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="mpm-icon" id="mpmIcon"></div>
        <h3 class="mpm-title" id="mpmTitle"></h3>
        <p class="mpm-desc" id="mpmDesc"></p>

        <div class="mpm-actions">
            <button type="button" class="mpm-btn mpm-btn-cancel" data-mpm-close>Cancel</button>
            <button type="button" class="mpm-btn mpm-btn-confirm" id="mpmConfirm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span id="mpmConfirmLabel">Confirm</span>
            </button>
        </div>
    </div>
</div>

<style>
    .mpm-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.75);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }
    .mpm-backdrop.open { display: flex; }
    .mpm-card {
        position: relative;
        max-width: 460px;
        width: 100%;
        background: var(--mp-card-bg, #111827);
        border: 1px solid var(--mp-border, rgba(255,255,255,0.08));
        border-radius: 20px;
        padding: 40px 36px 32px;
        text-align: center;
        box-shadow: 0 25px 80px rgba(0,0,0,0.6);
        animation: mpmSlideIn 0.3s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes mpmSlideIn {
        from { transform: translateY(24px) scale(0.96); opacity: 0; }
        to   { transform: translateY(0) scale(1); opacity: 1; }
    }
    .mpm-close {
        position: absolute; top: 14px; right: 14px;
        width: 34px; height: 34px; border-radius: 50%;
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
        color: var(--mp-text-muted, #94a3b8); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .mpm-close:hover { background: rgba(239,68,68,0.12); color: #ef4444; }
    .mpm-icon {
        width: 76px; height: 76px; margin: 0 auto 20px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .mpm-icon.subscribe {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 12px 40px rgba(99,102,241,0.35);
    }
    .mpm-icon.cancel-sub {
        background: linear-gradient(135deg, #ef4444, #f97316);
        box-shadow: 0 12px 40px rgba(239,68,68,0.3);
    }
    .mpm-icon svg { width: 34px; height: 34px; color: #fff; }
    .mpm-title {
        font-size: 1.35rem; font-weight: 800;
        color: var(--mp-text, #fff); margin: 0 0 10px;
    }
    .mpm-desc {
        font-size: 0.9rem; color: var(--mp-text-muted, #94a3b8);
        line-height: 1.65; margin: 0 0 28px;
    }
    .mpm-actions { display: flex; gap: 12px; }
    .mpm-btn {
        flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 13px 20px; border-radius: 12px; font-size: 0.9rem; font-weight: 700;
        cursor: pointer; border: none; font-family: inherit; transition: all 0.2s;
    }
    .mpm-btn-cancel {
        background: transparent; color: var(--mp-text-muted, #94a3b8);
        border: 1.5px solid var(--mp-border, rgba(255,255,255,0.1));
    }
    .mpm-btn-cancel:hover { background: rgba(255,255,255,0.04); color: var(--mp-text, #fff); }
    .mpm-btn-confirm {
        color: #fff; background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 6px 20px rgba(99,102,241,0.35);
    }
    .mpm-btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(99,102,241,0.45); }
    .mpm-btn-confirm.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 6px 20px rgba(239,68,68,0.35);
    }
    .mpm-btn-confirm.danger:hover { box-shadow: 0 10px 28px rgba(239,68,68,0.45); }
    @media (max-width: 480px) {
        .mpm-card { padding: 32px 24px 24px; }
        .mpm-actions { flex-direction: column-reverse; }
    }
</style>

@endsection

@push('scripts')
<script>
(function () {
    const modal      = document.getElementById('mpModal');
    const iconEl     = document.getElementById('mpmIcon');
    const titleEl    = document.getElementById('mpmTitle');
    const descEl     = document.getElementById('mpmDesc');
    const confirmBtn = document.getElementById('mpmConfirm');
    const confirmLbl = document.getElementById('mpmConfirmLabel');
    let pendingFormId = null;

    function open(config) {
        iconEl.className  = 'mpm-icon ' + config.iconClass;
        iconEl.innerHTML  = config.iconSvg;
        titleEl.textContent    = config.title;
        descEl.textContent     = config.desc;
        confirmLbl.textContent = config.confirmLabel;
        confirmBtn.className   = 'mpm-btn mpm-btn-confirm ' + (config.btnDanger ? 'danger' : '');
        pendingFormId          = config.formId;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => confirmBtn.focus(), 50);
    }

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        pendingFormId = null;
    }

    // Subscribe button trigger
    document.addEventListener('click', function (e) {
        const sub = e.target.closest('[data-mp-subscribe]');
        if (sub) {
            e.preventDefault();
            const name  = sub.getAttribute('data-plan-name');
            const price = sub.getAttribute('data-plan-price');
            const free  = sub.getAttribute('data-plan-free') === '1';
            open({
                title: free ? 'Get Started Free' : 'Switch to ' + name,
                desc: free
                    ? 'You are about to activate the ' + name + ' plan. No payment required — get started immediately!'
                    : 'You are about to subscribe to the ' + name + ' plan for ' + price + '. You will be redirected to a secure payment page.',
                confirmLabel: free ? 'Activate Free Plan' : 'Proceed to Payment',
                iconClass: 'subscribe',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
                btnDanger: false,
                formId: sub.getAttribute('data-form-id'),
            });
            return;
        }

        // Cancel subscription trigger
        const cancel = e.target.closest('[data-mp-cancel]');
        if (cancel) {
            e.preventDefault();
            open({
                title: 'Cancel Your Subscription?',
                desc: 'Your current plan features will remain active until the end of your billing period. After that, your account will revert to the free tier.',
                confirmLabel: 'Yes, Cancel Plan',
                iconClass: 'cancel-sub',
                iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                btnDanger: true,
                formId: 'cancelSubForm',
            });
            return;
        }

        // Close
        if (e.target === modal || e.target.closest('[data-mpm-close]')) {
            close();
        }
    });

    // Confirm — submit the pending form
    confirmBtn.addEventListener('click', function () {
        if (pendingFormId) {
            document.getElementById(pendingFormId).submit();
        }
    });

    // ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });
})();
</script>
@endpush
