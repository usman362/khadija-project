{{-- Role Enable Modal — shared across client and professional layouts.
     Triggered by any button with [data-role-enable="supplier"] or [data-role-enable="client"].
     The modal content is populated dynamically based on the clicked button. --}}

<div id="roleEnableModal" class="rem-backdrop" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="remTitle">
    <div class="rem-card" role="document">
        <button type="button" class="rem-close" data-rem-close aria-label="Close">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="rem-icon" id="remIcon">
            {{-- Icon swapped by JS --}}
        </div>

        <h3 id="remTitle" class="rem-title">Enable New Mode</h3>
        <p id="remDesc" class="rem-desc">Are you sure you want to enable this mode?</p>

        <div class="rem-features" id="remFeatures"></div>

        <form id="remForm" action="{{ route('role.enable') }}" method="POST">
            @csrf
            <input type="hidden" name="role" id="remRoleInput" value="">
            <div class="rem-actions">
                <button type="button" class="rem-btn rem-btn-cancel" data-rem-close>Cancel</button>
                <button type="submit" class="rem-btn rem-btn-confirm" id="remConfirmBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="remConfirmLabel">Confirm</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .rem-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .rem-backdrop.open {
        display: flex;
        opacity: 1;
    }

    .rem-card {
        position: relative;
        max-width: 480px;
        width: 100%;
        background: #111827;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 40px 36px 32px;
        text-align: center;
        box-shadow: 0 25px 80px rgba(0,0,0,0.6);
        transform: translateY(24px) scale(0.96);
        opacity: 0;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
    }
    [data-theme="light"] .rem-card {
        background: #ffffff;
        border-color: rgba(0,0,0,0.08);
    }
    .rem-backdrop.open .rem-card {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .rem-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .rem-close:hover {
        background: rgba(239,68,68,0.12);
        border-color: rgba(239,68,68,0.3);
        color: #ef4444;
    }
    [data-theme="light"] .rem-close {
        background: rgba(0,0,0,0.03);
        border-color: rgba(0,0,0,0.08);
        color: #64748b;
    }

    .rem-icon {
        width: 84px;
        height: 84px;
        margin: 0 auto 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .rem-icon::before {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2px dashed currentColor;
        opacity: 0.25;
        animation: remSpin 14s linear infinite;
    }
    .rem-icon.rem-icon-client {
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        color: #60a5fa;
        box-shadow: 0 12px 40px rgba(59,130,246,0.35);
    }
    .rem-icon.rem-icon-supplier {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #34d399;
        box-shadow: 0 12px 40px rgba(16,185,129,0.35);
    }
    .rem-icon svg {
        width: 38px;
        height: 38px;
        color: #fff;
    }
    @keyframes remSpin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    .rem-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 10px;
        letter-spacing: -0.4px;
    }
    [data-theme="light"] .rem-title { color: #0f172a; }

    .rem-desc {
        font-size: 0.92rem;
        color: #94a3b8;
        line-height: 1.65;
        margin: 0 0 22px;
    }
    [data-theme="light"] .rem-desc { color: #64748b; }

    .rem-features {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 26px;
        text-align: left;
    }
    [data-theme="light"] .rem-features {
        background: rgba(0,0,0,0.02);
        border-color: rgba(0,0,0,0.06);
    }
    .rem-features .feat {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.83rem;
        color: #cbd5e1;
        margin-bottom: 10px;
        line-height: 1.55;
    }
    .rem-features .feat:last-child { margin-bottom: 0; }
    [data-theme="light"] .rem-features .feat { color: #475569; }
    .rem-features .feat svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        margin-top: 1px;
        color: #10b981;
    }

    .rem-actions {
        display: flex;
        gap: 12px;
        justify-content: stretch;
    }
    .rem-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 20px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        font-family: inherit;
    }
    .rem-btn-cancel {
        background: transparent;
        color: #94a3b8;
        border: 1.5px solid rgba(255,255,255,0.1);
    }
    .rem-btn-cancel:hover {
        background: rgba(255,255,255,0.04);
        color: #ffffff;
        border-color: rgba(255,255,255,0.2);
    }
    [data-theme="light"] .rem-btn-cancel {
        color: #64748b;
        border-color: rgba(0,0,0,0.1);
    }
    [data-theme="light"] .rem-btn-cancel:hover {
        background: rgba(0,0,0,0.03);
        color: #0f172a;
    }

    .rem-btn-confirm {
        color: #fff;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        box-shadow: 0 6px 20px rgba(99,102,241,0.35);
    }
    .rem-btn-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 28px rgba(99,102,241,0.45);
    }
    .rem-btn-confirm.rem-btn-client {
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
        box-shadow: 0 6px 20px rgba(59,130,246,0.35);
    }
    .rem-btn-confirm.rem-btn-client:hover {
        box-shadow: 0 10px 28px rgba(59,130,246,0.45);
    }
    .rem-btn-confirm.rem-btn-supplier {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 6px 20px rgba(16,185,129,0.35);
    }
    .rem-btn-confirm.rem-btn-supplier:hover {
        box-shadow: 0 10px 28px rgba(16,185,129,0.45);
    }

    @media (max-width: 480px) {
        .rem-card { padding: 32px 24px 24px; }
        .rem-title { font-size: 1.25rem; }
        .rem-actions { flex-direction: column-reverse; }
    }
</style>

<script>
(function () {
    'use strict';

    const MODE_DATA = {
        client: {
            title: 'Become a Client',
            desc:  "Enable Client Mode on your account. You'll be able to post events and hire professionals, while keeping all your existing data safe.",
            confirmLabel: 'Yes, Enable Client Mode',
            iconClass: 'rem-icon-client',
            btnClass:  'rem-btn-client',
            iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            features: [
                'Post events and hire verified professionals',
                'Manage all your bookings in one place',
                'Switch back to Professional mode anytime',
            ],
        },
        supplier: {
            title: 'Become a Professional',
            desc:  "Enable Professional Mode on your account. You'll be able to offer your services, browse events, and submit proposals — all from the same account.",
            confirmLabel: 'Yes, Enable Professional Mode',
            iconClass: 'rem-icon-supplier',
            btnClass:  'rem-btn-supplier',
            iconSvg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
            features: [
                'Browse marketplace events and submit proposals',
                'Track earnings and manage your gigs',
                'Switch back to Client mode anytime',
            ],
        },
    };

    const backdrop     = document.getElementById('roleEnableModal');
    if (!backdrop) return;

    const iconEl       = document.getElementById('remIcon');
    const titleEl      = document.getElementById('remTitle');
    const descEl       = document.getElementById('remDesc');
    const featuresEl   = document.getElementById('remFeatures');
    const roleInput    = document.getElementById('remRoleInput');
    const confirmBtn   = document.getElementById('remConfirmBtn');
    const confirmLabel = document.getElementById('remConfirmLabel');

    function openModal(targetRole) {
        const data = MODE_DATA[targetRole];
        if (!data) return;

        // Icon
        iconEl.className = 'rem-icon ' + data.iconClass;
        iconEl.innerHTML = data.iconSvg;

        // Text
        titleEl.textContent   = data.title;
        descEl.textContent    = data.desc;
        confirmLabel.textContent = data.confirmLabel;

        // Features
        featuresEl.innerHTML = data.features.map(f =>
            '<div class="feat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>' + f + '</span></div>'
        ).join('');

        // Hidden input + button class
        roleInput.value = targetRole;
        confirmBtn.className = 'rem-btn rem-btn-confirm ' + data.btnClass;

        // Show
        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Focus trap (basic)
        setTimeout(() => confirmBtn.focus(), 50);
    }

    function closeModal() {
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Trigger: any element with [data-role-enable="client|supplier"]
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-role-enable]');
        if (trigger) {
            e.preventDefault();
            openModal(trigger.getAttribute('data-role-enable'));
            return;
        }

        // Close on backdrop click or close button
        if (e.target === backdrop || e.target.closest('[data-rem-close]')) {
            closeModal();
        }
    });

    // Close on ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && backdrop.classList.contains('open')) {
            closeModal();
        }
    });
})();
</script>
