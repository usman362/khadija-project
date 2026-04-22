@extends('layouts.client')

@section('title', 'AI Budget Allocator')
@section('page-title', 'AI Budget Allocator')

@push('styles')
<style>
    .bat-hero {
        background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.08));
        border: 1px solid rgba(99,102,241,0.25);
        border-radius: var(--radius);
        padding: 26px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .bat-hero-icon {
        width: 56px; height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .bat-hero-icon svg { width: 28px; height: 28px; }
    .bat-hero-content h2 {
        font-size: 18px; font-weight: 700; color: var(--text-primary);
        margin-bottom: 4px;
    }
    .bat-hero-content p {
        font-size: 13px; color: var(--text-muted); line-height: 1.5;
        margin: 0;
    }

    /* Quota badge */
    .bat-quota-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .bat-quota-ok        { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .bat-quota-low       { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
    .bat-quota-exhausted { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .bat-quota-unlimited { background: rgba(99,102,241,0.12); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }

    /* Form card */
    .bat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 28px 32px;
        margin-bottom: 20px;
    }
    .bat-card-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    .bat-card-desc {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 22px;
    }

    .bat-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 640px) { .bat-form-grid { grid-template-columns: 1fr; } }
    .bat-full { grid-column: 1 / -1; }
    .bat-label {
        display: block;
        font-size: 12.5px; font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .bat-input, .bat-select, .bat-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        font-size: 13.5px;
        transition: all 0.2s;
        font-family: inherit;
    }
    .bat-input:focus, .bat-select:focus, .bat-textarea:focus {
        outline: none; border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }
    .bat-textarea { resize: vertical; min-height: 72px; }
    .bat-input-group {
        display: flex;
        gap: 8px;
    }
    .bat-currency {
        flex-shrink: 0;
        width: 70px;
        text-align: center;
        background: rgba(99,102,241,0.08);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: #a5b4fc;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bat-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 26px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .bat-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(99,102,241,0.3);
    }
    .bat-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Upgrade prompt */
    .bat-upgrade {
        background: linear-gradient(135deg, rgba(239,68,68,0.06), rgba(245,158,11,0.06));
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: var(--radius);
        padding: 32px;
        text-align: center;
    }
    .bat-upgrade-icon {
        width: 60px; height: 60px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
    }
    .bat-upgrade-icon svg { width: 28px; height: 28px; }
    .bat-upgrade h3 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .bat-upgrade p { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

    /* Result */
    .bat-result {
        display: none;
        background: var(--bg-secondary);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: var(--radius);
        padding: 28px 32px;
    }
    .bat-result.open { display: block; animation: batFade 0.3s ease; }
    @keyframes batFade {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .bat-result-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    .bat-result-title {
        font-size: 16px; font-weight: 700; color: var(--text-primary);
        display: flex; align-items: center; gap: 8px;
    }
    .bat-result-title svg { width: 20px; height: 20px; color: #10b981; }
    .bat-result-total {
        font-size: 22px; font-weight: 800;
        color: #10b981;
    }

    .bat-summary {
        padding: 14px 18px;
        background: rgba(99,102,241,0.05);
        border-left: 3px solid #6366f1;
        border-radius: var(--radius-sm);
        font-size: 13.5px;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 22px;
    }

    /* Allocations */
    .bat-alloc-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
    .bat-alloc {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 14px 18px;
    }
    .bat-alloc-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .bat-alloc-cat {
        font-size: 14px; font-weight: 700; color: var(--text-primary);
        display: flex; align-items: center; gap: 8px;
    }
    .bat-alloc-dot { width: 10px; height: 10px; border-radius: 50%; }
    .bat-alloc-amt {
        font-size: 14px; font-weight: 700; color: var(--text-primary);
    }
    .bat-alloc-pct {
        font-size: 11px; color: var(--text-muted);
        margin-left: 6px;
    }
    .bat-alloc-bar {
        height: 6px; border-radius: 3px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
        margin-bottom: 8px;
    }
    .bat-alloc-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s cubic-bezier(0.16,1,0.3,1);
    }
    .bat-alloc-notes {
        font-size: 12px; color: var(--text-muted);
        line-height: 1.5;
    }

    /* Tips */
    .bat-tips {
        background: rgba(245,158,11,0.05);
        border: 1px solid rgba(245,158,11,0.2);
        border-radius: var(--radius-sm);
        padding: 16px 20px;
    }
    .bat-tips-title {
        font-size: 13px; font-weight: 700;
        color: #f59e0b;
        margin-bottom: 10px;
        display: flex; align-items: center; gap: 8px;
    }
    .bat-tips-title svg { width: 16px; height: 16px; }
    .bat-tips ul { margin: 0; padding-left: 18px; }
    .bat-tips li { font-size: 12.5px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 5px; }

    /* Loading state */
    .bat-loading {
        display: none;
        text-align: center;
        padding: 40px 20px;
    }
    .bat-loading.open { display: block; }
    .bat-spinner {
        width: 48px; height: 48px;
        border: 3px solid rgba(99,102,241,0.2);
        border-top-color: #6366f1;
        border-radius: 50%;
        margin: 0 auto 14px;
        animation: batSpin 0.8s linear infinite;
    }
    @keyframes batSpin { to { transform: rotate(360deg); } }
    .bat-loading-text { font-size: 13px; color: var(--text-muted); }

    .bat-error {
        display: none;
        padding: 12px 16px;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        border-radius: var(--radius-sm);
        font-size: 13px;
        margin-bottom: 16px;
    }
    .bat-error.open { display: block; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="bat-hero">
    <div class="bat-hero-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div class="bat-hero-content" style="flex:1;">
        <h2>AI Budget Allocator</h2>
        <p>Let AI break down your event budget into smart category allocations. Enter your event details and get a complete spending plan with expert tips.</p>
    </div>
    <div>
        @if($status['enabled'])
            @if($status['unlimited'])
                <span class="bat-quota-badge bat-quota-unlimited">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.739-8z"/></svg>
                    Unlimited
                </span>
            @elseif($status['remaining'] > 3)
                <span class="bat-quota-badge bat-quota-ok">{{ $status['remaining'] }} / {{ $status['quota'] }} left this month</span>
            @elseif($status['remaining'] > 0)
                <span class="bat-quota-badge bat-quota-low">Only {{ $status['remaining'] }} left this month</span>
            @else
                <span class="bat-quota-badge bat-quota-exhausted">Monthly limit reached</span>
            @endif
        @endif
    </div>
</div>

@if(!$status['enabled'])
    {{-- Locked — upgrade prompt --}}
    <div class="bat-upgrade">
        <div class="bat-upgrade-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h3>This is a Premium Feature</h3>
        <p>The AI Budget Allocator is not included in your current plan.<br>Upgrade to unlock AI-powered event planning tools.</p>
        <a href="{{ route('app.membership-plans.index') }}" class="bat-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>
            Upgrade Plan
        </a>
    </div>
@else
    {{-- Form --}}
    <div class="bat-card">
        <div class="bat-card-title">Event Details</div>
        <div class="bat-card-desc">Fill in what you know — the more detail, the better the AI allocation.</div>

        <div class="bat-error" id="batError"></div>

        <form id="batForm">
            <div class="bat-form-grid">
                <div>
                    <label class="bat-label">Event Type *</label>
                    <select name="event_type" class="bat-select" required>
                        <option value="">Select type...</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday Party">Birthday Party</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Baby Shower">Baby Shower</option>
                        <option value="Anniversary">Anniversary</option>
                        <option value="Graduation">Graduation</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Conference">Conference</option>
                        <option value="Concert">Concert</option>
                        <option value="Private Party">Private Party</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="bat-label">Guest Count</label>
                    <input type="number" name="guest_count" class="bat-input" min="1" max="100000" placeholder="e.g. 150">
                </div>

                <div class="bat-full">
                    <label class="bat-label">Total Budget *</label>
                    <div class="bat-input-group">
                        <select name="currency" class="bat-currency">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="PKR">PKR</option>
                            <option value="INR">INR</option>
                            <option value="AED">AED</option>
                        </select>
                        <input type="number" name="total_budget" class="bat-input" min="1" step="0.01" required placeholder="e.g. 5000">
                    </div>
                </div>

                <div>
                    <label class="bat-label">Location (optional)</label>
                    <input type="text" name="location" class="bat-input" maxlength="200" placeholder="e.g. Karachi, Pakistan">
                </div>
                <div>
                    <label class="bat-label">Event Date (optional)</label>
                    <input type="date" name="date" class="bat-input">
                </div>

                <div class="bat-full">
                    <label class="bat-label">Priorities / Must-Haves (optional)</label>
                    <input type="text" name="priorities" class="bat-input" maxlength="500" placeholder="e.g. Great food, live band, professional photography">
                </div>

                <div class="bat-full">
                    <label class="bat-label">Additional Notes (optional)</label>
                    <textarea name="notes" class="bat-textarea" maxlength="500" placeholder="Anything else we should know?"></textarea>
                </div>
            </div>

            <div style="margin-top: 22px;">
                <button type="submit" class="bat-btn" id="batSubmit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Generate Budget with AI
                </button>
            </div>
        </form>
    </div>

    {{-- Loading --}}
    <div class="bat-loading" id="batLoading">
        <div class="bat-spinner"></div>
        <div class="bat-loading-text">Crunching numbers and optimizing your budget...</div>
    </div>

    {{-- Result --}}
    <div class="bat-result" id="batResult">
        <div class="bat-result-header">
            <div class="bat-result-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Your Budget Allocation
            </div>
            <div class="bat-result-total" id="batResultTotal"></div>
        </div>

        <div class="bat-summary" id="batSummary"></div>

        <div class="bat-alloc-list" id="batAllocList"></div>

        <div class="bat-tips" id="batTipsBox" style="display:none;">
            <div class="bat-tips-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4"/><path d="M22 12a10 10 0 1 0-8.5 9.87"/><path d="M12 6v6l4 2"/></svg>
                Expert Tips
            </div>
            <ul id="batTipsList"></ul>
        </div>
    </div>
@endif

<script>
(function () {
    const form    = document.getElementById('batForm');
    if (!form) return;

    const submit  = document.getElementById('batSubmit');
    const loading = document.getElementById('batLoading');
    const result  = document.getElementById('batResult');
    const errEl   = document.getElementById('batError');

    const COLORS = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#06b6d4','#3b82f6','#f97316','#ef4444'];
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        result.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.budget-allocator.allocate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Failed to generate budget.';
                errEl.classList.add('open');
                return;
            }

            renderResult(data.result);
            result.classList.add('open');
            result.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            loading.classList.remove('open');
            submit.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('open');
        }
    });

    function renderResult(res) {
        document.getElementById('batResultTotal').textContent = res.currency + ' ' + formatNum(res.total);
        document.getElementById('batSummary').textContent = res.summary || '';

        const list = document.getElementById('batAllocList');
        list.innerHTML = '';

        (res.allocations || []).forEach((a, i) => {
            const color = COLORS[i % COLORS.length];
            const div = document.createElement('div');
            div.className = 'bat-alloc';
            div.innerHTML = `
                <div class="bat-alloc-row">
                    <div class="bat-alloc-cat">
                        <span class="bat-alloc-dot" style="background:${color};"></span>
                        ${escapeHtml(a.category)}
                    </div>
                    <div class="bat-alloc-amt">
                        ${escapeHtml(res.currency)} ${formatNum(a.amount)}
                        <span class="bat-alloc-pct">(${a.percent}%)</span>
                    </div>
                </div>
                <div class="bat-alloc-bar">
                    <div class="bat-alloc-fill" style="width:0%;background:${color};"></div>
                </div>
                ${a.notes ? `<div class="bat-alloc-notes">${escapeHtml(a.notes)}</div>` : ''}
            `;
            list.appendChild(div);
            // Animate bar
            setTimeout(() => { div.querySelector('.bat-alloc-fill').style.width = a.percent + '%'; }, 100);
        });

        // Tips
        const tipsBox  = document.getElementById('batTipsBox');
        const tipsList = document.getElementById('batTipsList');
        tipsList.innerHTML = '';
        if (res.tips && res.tips.length) {
            res.tips.forEach(t => {
                const li = document.createElement('li');
                li.textContent = t;
                tipsList.appendChild(li);
            });
            tipsBox.style.display = 'block';
        } else {
            tipsBox.style.display = 'none';
        }
    }

    function formatNum(n) {
        return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
    }
    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }
})();
</script>

@endsection
