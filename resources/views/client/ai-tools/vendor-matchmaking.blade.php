@extends('layouts.client')

@section('title', 'AI Vendor Matchmaking')
@section('page-title', 'AI Vendor Matchmaking')

@push('styles')
<style>
    .vm-hero {
        background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.08));
        border: 1px solid rgba(16,185,129,0.25);
        border-radius: var(--radius);
        padding: 26px 28px;
        margin-bottom: 24px;
        display: flex; align-items: center; gap: 20px;
    }
    .vm-hero-icon {
        width: 56px; height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        display: flex; align-items: center; justify-content: center;
        color: #fff; flex-shrink: 0;
    }
    .vm-hero-icon svg { width: 28px; height: 28px; }
    .vm-hero-content h2 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .vm-hero-content p { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0; }

    /* Quota badge (reusable) */
    .vm-quota {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 20px;
        font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .vm-quota-ok        { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .vm-quota-low       { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
    .vm-quota-exhausted { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .vm-quota-unlimited { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }

    .vm-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 28px 32px;
        margin-bottom: 20px;
    }
    .vm-card-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .vm-card-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }

    .vm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 640px) { .vm-form-grid { grid-template-columns: 1fr; } }
    .vm-full { grid-column: 1 / -1; }
    .vm-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
    .vm-input, .vm-select, .vm-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        font-size: 13.5px;
        font-family: inherit;
    }
    .vm-input:focus, .vm-select:focus, .vm-textarea:focus {
        outline: none; border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    }
    .vm-textarea { resize: vertical; min-height: 80px; }

    .vm-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 26px;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        color: #fff; border: none;
        border-radius: var(--radius-sm);
        font-size: 14px; font-weight: 700;
        cursor: pointer; font-family: inherit;
        transition: all 0.2s;
    }
    .vm-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
    .vm-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Upgrade */
    .vm-upgrade {
        background: linear-gradient(135deg, rgba(239,68,68,0.06), rgba(245,158,11,0.06));
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: var(--radius);
        padding: 32px; text-align: center;
    }
    .vm-upgrade-icon {
        width: 60px; height: 60px; margin: 0 auto 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        display: flex; align-items: center; justify-content: center; color: #fff;
    }
    .vm-upgrade h3 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .vm-upgrade p { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

    /* Loading */
    .vm-loading { display: none; text-align: center; padding: 40px 20px; }
    .vm-loading.open { display: block; }
    .vm-spinner {
        width: 48px; height: 48px;
        border: 3px solid rgba(16,185,129,0.2);
        border-top-color: #10b981;
        border-radius: 50%;
        margin: 0 auto 14px;
        animation: vmSpin 0.8s linear infinite;
    }
    @keyframes vmSpin { to { transform: rotate(360deg); } }

    .vm-error {
        display: none;
        padding: 12px 16px;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        border-radius: var(--radius-sm);
        font-size: 13px; margin-bottom: 16px;
    }
    .vm-error.open { display: block; }

    /* Results */
    .vm-result { display: none; }
    .vm-result.open { display: block; animation: vmFade 0.3s ease; }
    @keyframes vmFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .vm-summary {
        padding: 14px 18px;
        background: rgba(16,185,129,0.05);
        border-left: 3px solid #10b981;
        border-radius: var(--radius-sm);
        font-size: 13.5px;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .vm-match {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 20px 22px;
        margin-bottom: 14px;
        position: relative;
        overflow: hidden;
    }
    .vm-match::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(to bottom, #10b981, #06b6d4);
    }
    .vm-match-top {
        display: flex; align-items: flex-start; gap: 14px;
        margin-bottom: 10px;
    }
    .vm-match-rank {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #10b981, #06b6d4);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 15px;
        flex-shrink: 0;
    }
    .vm-match-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }
    .vm-match-info { flex: 1; min-width: 0; }
    .vm-match-name { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
    .vm-match-headline { font-size: 12.5px; color: var(--text-muted); font-style: italic; }
    .vm-match-score {
        padding: 4px 12px;
        background: rgba(16,185,129,0.12);
        color: #10b981;
        border-radius: 20px;
        font-size: 12px; font-weight: 700;
        white-space: nowrap;
    }
    .vm-match-meta {
        display: flex; flex-wrap: wrap; gap: 10px;
        margin: 10px 0 12px;
    }
    .vm-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        font-size: 11px; font-weight: 600;
        color: var(--text-secondary);
    }
    .vm-chip svg { width: 11px; height: 11px; }
    .vm-match-reasoning {
        font-size: 13px; color: var(--text-secondary); line-height: 1.6;
        padding: 10px 14px;
        background: rgba(16,185,129,0.04);
        border-radius: var(--radius-sm);
    }
    .vm-match-skills { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
    .vm-skill {
        padding: 2px 8px;
        background: rgba(99,102,241,0.1);
        color: #a5b4fc;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 600;
    }
    .vm-match-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .vm-verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        background: rgba(16,185,129,0.12);
        color: #10b981;
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.2px;
    }
    .vm-verified-badge svg { width: 11px; height: 11px; }
</style>
@endpush

@section('content')

<div class="vm-hero">
    <div class="vm-hero-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
    </div>
    <div class="vm-hero-content" style="flex:1;">
        <h2>AI Vendor Matchmaking</h2>
        <p>Describe your event and AI will rank the best-matching professionals from our network — with specific reasoning why each fits your needs.</p>
    </div>
    <div>
        @if($status['enabled'])
            @if($status['unlimited'])
                <span class="vm-quota vm-quota-unlimited">Unlimited</span>
            @elseif($status['remaining'] > 3)
                <span class="vm-quota vm-quota-ok">{{ $status['remaining'] }} / {{ $status['quota'] }} left</span>
            @elseif($status['remaining'] > 0)
                <span class="vm-quota vm-quota-low">Only {{ $status['remaining'] }} left</span>
            @else
                <span class="vm-quota vm-quota-exhausted">Limit reached</span>
            @endif
        @endif
    </div>
</div>

@if(!$status['enabled'])
    <div class="vm-upgrade">
        <div class="vm-upgrade-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h3>This is a Premium Feature</h3>
        <p>AI Vendor Matchmaking is not included in your current plan.<br>Upgrade to get personalized professional recommendations.</p>
        <a href="{{ route('app.membership-plans.index') }}" class="vm-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>
            Upgrade Plan
        </a>
    </div>
@else
    <div class="vm-card">
        <div class="vm-card-title">Event Requirements</div>
        <div class="vm-card-desc">The more specific you are, the better the matches.</div>

        <div class="vm-error" id="vmError"></div>

        <form id="vmForm">
            <div class="vm-form-grid">
                <div>
                    <label class="vm-label">Event Type *</label>
                    <select name="event_type" class="vm-select" required>
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
                        <option value="Photography Only">Photography Only</option>
                        <option value="Catering Only">Catering Only</option>
                        <option value="DJ / Music Only">DJ / Music Only</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="vm-label">Max Budget (optional)</label>
                    <input type="number" name="budget" class="vm-input" min="0" step="0.01" placeholder="e.g. 2000">
                </div>
                <div>
                    <label class="vm-label">Guest Count (optional)</label>
                    <input type="number" name="guest_count" class="vm-input" min="1" placeholder="e.g. 100">
                </div>
                <div>
                    <label class="vm-label">Location (optional)</label>
                    <input type="text" name="location" class="vm-input" maxlength="200" placeholder="e.g. Karachi">
                </div>
                <div class="vm-full">
                    <label class="vm-label">Event Date (optional)</label>
                    <input type="date" name="date" class="vm-input">
                </div>
                <div class="vm-full">
                    <label class="vm-label">Specific Requirements (optional)</label>
                    <textarea name="requirements" class="vm-textarea" maxlength="1000" placeholder="e.g. Need someone with experience in outdoor weddings, portrait photography style, English-speaking..."></textarea>
                </div>
            </div>

            <div style="margin-top: 22px;">
                <button type="submit" class="vm-btn" id="vmSubmit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Find Perfect Matches
                </button>
            </div>
        </form>
    </div>

    <div class="vm-loading" id="vmLoading">
        <div class="vm-spinner"></div>
        <div style="font-size: 13px; color: var(--text-muted);">Analyzing professionals and finding your best matches...</div>
    </div>

    <div class="vm-result" id="vmResult">
        <div class="vm-summary" id="vmSummary"></div>
        <div id="vmMatches"></div>
    </div>
@endif

<script>
(function () {
    const form    = document.getElementById('vmForm');
    if (!form) return;

    const submit  = document.getElementById('vmSubmit');
    const loading = document.getElementById('vmLoading');
    const result  = document.getElementById('vmResult');
    const errEl   = document.getElementById('vmError');
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        result.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.vendor-matchmaking.match") }}', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Failed to find matches.';
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
        document.getElementById('vmSummary').textContent = res.summary || '';

        const container = document.getElementById('vmMatches');
        container.innerHTML = '';

        if (!res.matches || res.matches.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">No matches found.</div>';
            return;
        }

        const BADGE_LABELS = {
            trade_license: 'Trade License',
            liability_insurance: 'Liability Insurance',
            workers_comp: "Workers' Comp",
        };
        const CHECK_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';

        res.matches.forEach(m => {
            const card = document.createElement('div');
            card.className = 'vm-match';
            const skillsHtml = (m.skills || []).slice(0, 6).map(s =>
                `<span class="vm-skill">${escapeHtml(s)}</span>`
            ).join('');

            const badgesHtml = (m.verified_badges || [])
                .filter(b => BADGE_LABELS[b])
                .map(b => `<span class="vm-verified-badge" title="Verified by GigResource admin">${CHECK_SVG} ${escapeHtml(BADGE_LABELS[b])}</span>`)
                .join('');

            card.innerHTML = `
                <div class="vm-match-top">
                    <div class="vm-match-rank">#${m.rank}</div>
                    <img src="${escapeHtml(m.avatar_url)}" alt="" class="vm-match-avatar">
                    <div class="vm-match-info">
                        <div class="vm-match-name">${escapeHtml(m.name)}</div>
                        ${m.headline ? `<div class="vm-match-headline">${escapeHtml(m.headline)}</div>` : ''}
                    </div>
                    <div class="vm-match-score">${m.match_score}% match</div>
                </div>
                <div class="vm-match-meta">
                    ${m.hourly_rate ? `<span class="vm-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>$${Number(m.hourly_rate).toLocaleString()}/hr</span>` : ''}
                    ${m.experience ? `<span class="vm-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>${m.experience} yrs exp</span>` : ''}
                    ${m.availability ? `<span class="vm-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>${escapeHtml(m.availability)}</span>` : ''}
                </div>
                <div class="vm-match-reasoning">${escapeHtml(m.reasoning)}</div>
                ${badgesHtml ? `<div class="vm-match-badges">${badgesHtml}</div>` : ''}
                ${skillsHtml ? `<div class="vm-match-skills">${skillsHtml}</div>` : ''}
            `;
            container.appendChild(card);
        });
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }
})();
</script>

@endsection
