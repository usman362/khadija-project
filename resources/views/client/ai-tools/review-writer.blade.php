@extends('layouts.client')

@section('title', 'AI Review Writer')
@section('page-title', 'AI Review Writer')

@push('styles')
<style>
    .rw-hero {
        background: linear-gradient(135deg, rgba(236,72,153,0.1), rgba(168,85,247,0.08));
        border: 1px solid rgba(236,72,153,0.25);
        border-radius: var(--radius);
        padding: 26px 28px;
        margin-bottom: 24px;
        display: flex; align-items: center; gap: 20px;
    }
    .rw-hero-icon {
        width: 56px; height: 56px; border-radius: 16px;
        background: linear-gradient(135deg, #ec4899, #a855f7);
        display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0;
    }
    .rw-hero-icon svg { width: 28px; height: 28px; }
    .rw-hero-content h2 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .rw-hero-content p { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0; }

    .rw-quota {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 20px;
        font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .rw-quota-ok        { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .rw-quota-low       { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
    .rw-quota-exhausted { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .rw-quota-unlimited { background: rgba(236,72,153,0.12); color: #ec4899; border: 1px solid rgba(236,72,153,0.3); }

    .rw-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        padding: 28px 32px;
        margin-bottom: 20px;
    }
    .rw-card-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .rw-card-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 22px; }

    .rw-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 640px) { .rw-form-grid { grid-template-columns: 1fr; } }
    .rw-full { grid-column: 1 / -1; }
    .rw-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
    .rw-input, .rw-select, .rw-textarea {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        color: var(--text-primary);
        font-size: 13.5px;
        font-family: inherit;
    }
    .rw-input:focus, .rw-select:focus, .rw-textarea:focus {
        outline: none; border-color: #ec4899;
        box-shadow: 0 0 0 3px rgba(236,72,153,0.15);
    }
    .rw-textarea { resize: vertical; min-height: 90px; }

    /* Star rating */
    .rw-stars { display: flex; gap: 6px; }
    .rw-star {
        width: 36px; height: 36px; border-radius: 8px;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.15s;
    }
    .rw-star svg { width: 20px; height: 20px; color: #64748b; transition: color 0.15s; }
    .rw-star:hover { transform: translateY(-2px); border-color: rgba(245,158,11,0.4); }
    .rw-star.active svg { color: #f59e0b; fill: #f59e0b; }
    .rw-rating-label { font-size: 12px; color: var(--text-muted); margin-top: 8px; font-style: italic; }

    /* Tone pills */
    .rw-tones { display: flex; gap: 8px; flex-wrap: wrap; }
    .rw-tone {
        padding: 8px 14px; border-radius: 20px;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 12.5px; font-weight: 600;
        cursor: pointer; transition: all 0.15s;
        font-family: inherit;
    }
    .rw-tone:hover { color: var(--text-primary); }
    .rw-tone.active {
        background: linear-gradient(135deg, #ec4899, #a855f7);
        color: #fff; border-color: transparent;
    }

    .rw-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 26px;
        background: linear-gradient(135deg, #ec4899, #a855f7);
        color: #fff; border: none;
        border-radius: var(--radius-sm);
        font-size: 14px; font-weight: 700;
        cursor: pointer; font-family: inherit;
        transition: all 0.2s;
    }
    .rw-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(236,72,153,0.3); }
    .rw-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .rw-btn-outline {
        background: transparent;
        color: var(--text-secondary);
        border: 1.5px solid var(--border-color);
    }
    .rw-btn-outline:hover { border-color: #ec4899; color: #ec4899; }

    .rw-upgrade {
        background: linear-gradient(135deg, rgba(239,68,68,0.06), rgba(245,158,11,0.06));
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: var(--radius);
        padding: 32px; text-align: center;
    }
    .rw-upgrade-icon {
        width: 60px; height: 60px; margin: 0 auto 14px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        display: flex; align-items: center; justify-content: center; color: #fff;
    }
    .rw-upgrade h3 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .rw-upgrade p { font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

    .rw-loading { display: none; text-align: center; padding: 40px 20px; }
    .rw-loading.open { display: block; }
    .rw-spinner {
        width: 48px; height: 48px;
        border: 3px solid rgba(236,72,153,0.2);
        border-top-color: #ec4899;
        border-radius: 50%;
        margin: 0 auto 14px;
        animation: rwSpin 0.8s linear infinite;
    }
    @keyframes rwSpin { to { transform: rotate(360deg); } }

    .rw-error {
        display: none;
        padding: 12px 16px;
        background: rgba(239,68,68,0.1);
        border: 1px solid rgba(239,68,68,0.3);
        color: #f87171;
        border-radius: var(--radius-sm);
        font-size: 13px; margin-bottom: 16px;
    }
    .rw-error.open { display: block; }

    .rw-result { display: none; }
    .rw-result.open { display: block; animation: rwFade 0.3s ease; }
    @keyframes rwFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .rw-review-box {
        background: linear-gradient(135deg, rgba(236,72,153,0.04), rgba(168,85,247,0.03));
        border: 1.5px solid rgba(236,72,153,0.25);
        border-radius: var(--radius);
        padding: 24px 28px;
        margin-bottom: 16px;
        position: relative;
    }
    .rw-review-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 14px;
    }
    .rw-review-title {
        font-size: 13px; font-weight: 700;
        color: #ec4899; text-transform: uppercase; letter-spacing: 1px;
        display: flex; align-items: center; gap: 8px;
    }
    .rw-review-title svg { width: 16px; height: 16px; }
    .rw-review-stars { color: #f59e0b; font-size: 18px; letter-spacing: 2px; }
    .rw-review-text {
        font-size: 15px; line-height: 1.75; color: var(--text-primary);
        white-space: pre-wrap;
    }
    .rw-short-box {
        padding: 14px 18px;
        background: rgba(99,102,241,0.06);
        border-left: 3px solid #6366f1;
        border-radius: var(--radius-sm);
        font-size: 13px; color: var(--text-secondary); line-height: 1.6;
        margin-bottom: 16px;
    }
    .rw-short-label {
        font-size: 11px; font-weight: 700; color: #a5b4fc;
        text-transform: uppercase; letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .rw-actions { display: flex; gap: 10px; flex-wrap: wrap; }
</style>
@endpush

@section('content')

<div class="rw-hero">
    <div class="rw-hero-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
    </div>
    <div class="rw-hero-content" style="flex:1;">
        <h2>AI Review Writer</h2>
        <p>Rate your experience and share a few thoughts — AI will craft a polished, helpful review you can post anywhere.</p>
    </div>
    <div>
        @if($status['enabled'])
            @if($status['unlimited'])
                <span class="rw-quota rw-quota-unlimited">Unlimited</span>
            @elseif($status['remaining'] > 3)
                <span class="rw-quota rw-quota-ok">{{ $status['remaining'] }} / {{ $status['quota'] }} left</span>
            @elseif($status['remaining'] > 0)
                <span class="rw-quota rw-quota-low">Only {{ $status['remaining'] }} left</span>
            @else
                <span class="rw-quota rw-quota-exhausted">Limit reached</span>
            @endif
        @endif
    </div>
</div>

@if(!$status['enabled'])
    <div class="rw-upgrade">
        <div class="rw-upgrade-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h3>This is a Premium Feature</h3>
        <p>AI Review Writer is not included in your current plan.<br>Upgrade to write polished reviews effortlessly.</p>
        <a href="{{ route('app.membership-plans.index') }}" class="rw-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>
            Upgrade Plan
        </a>
    </div>
@else
    <div class="rw-card">
        <div class="rw-card-title">Share Your Experience</div>
        <div class="rw-card-desc">A few keywords are enough — AI will turn them into a natural, helpful review.</div>

        <div class="rw-error" id="rwError"></div>

        <form id="rwForm">
            <div class="rw-form-grid">
                <div>
                    <label class="rw-label">Professional / Service Provider *</label>
                    <input type="text" name="professional_name" class="rw-input" maxlength="120" required placeholder="e.g. Sarah Ahmed Photography">
                </div>
                <div>
                    <label class="rw-label">Service Type (optional)</label>
                    <input type="text" name="service_type" class="rw-input" maxlength="120" placeholder="e.g. Wedding Photography">
                </div>

                <div>
                    <label class="rw-label">Event Type (optional)</label>
                    <input type="text" name="event_type" class="rw-input" maxlength="120" placeholder="e.g. My wedding in March">
                </div>

                <div>
                    <label class="rw-label">Your Rating *</label>
                    <div class="rw-stars" id="rwStars">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="rw-star" data-rating="{{ $i }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="rwRating" value="5">
                    <div class="rw-rating-label" id="rwRatingLabel">Excellent</div>
                </div>

                <div class="rw-full">
                    <label class="rw-label">Preferred Tone *</label>
                    <div class="rw-tones">
                        <button type="button" class="rw-tone" data-tone="friendly">😊 Friendly & Warm</button>
                        <button type="button" class="rw-tone active" data-tone="balanced">⚖️ Balanced</button>
                        <button type="button" class="rw-tone" data-tone="professional">💼 Professional</button>
                    </div>
                    <input type="hidden" name="tone" id="rwTone" value="balanced">
                </div>

                <div class="rw-full">
                    <label class="rw-label">Your Quick Thoughts * <span style="color:var(--text-muted);font-weight:400;">(bullet points, keywords, or full sentences — anything works)</span></label>
                    <textarea name="thoughts" class="rw-textarea" maxlength="1000" required
                        placeholder="e.g. On time, great energy, captured amazing candid shots, professional team, delivered edits in 2 weeks..."></textarea>
                </div>
            </div>

            <div style="margin-top: 22px;">
                <button type="submit" class="rw-btn" id="rwSubmit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Generate Review
                </button>
            </div>
        </form>
    </div>

    <div class="rw-loading" id="rwLoading">
        <div class="rw-spinner"></div>
        <div style="font-size: 13px; color: var(--text-muted);">Polishing your thoughts into a great review...</div>
    </div>

    <div class="rw-result" id="rwResult">
        <div class="rw-review-box">
            <div class="rw-review-header">
                <div class="rw-review-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Your Review
                </div>
                <div class="rw-review-stars" id="rwReviewStars"></div>
            </div>
            <div class="rw-review-text" id="rwReviewText"></div>
        </div>

        <div class="rw-short-box" id="rwShortBox" style="display:none;">
            <div class="rw-short-label">Short Version (for social media)</div>
            <div id="rwShortText"></div>
        </div>

        <div class="rw-actions">
            <button type="button" class="rw-btn" id="rwCopyBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy Review
            </button>
            <button type="button" class="rw-btn rw-btn-outline" id="rwRegenBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Regenerate
            </button>
        </div>
    </div>
@endif

<script>
(function () {
    const form = document.getElementById('rwForm');
    if (!form) return;

    const submit    = document.getElementById('rwSubmit');
    const loading   = document.getElementById('rwLoading');
    const result    = document.getElementById('rwResult');
    const errEl     = document.getElementById('rwError');
    const ratingIn  = document.getElementById('rwRating');
    const ratingLbl = document.getElementById('rwRatingLabel');
    const toneIn    = document.getElementById('rwTone');
    const stars     = document.querySelectorAll('#rwStars .rw-star');
    const tones     = document.querySelectorAll('.rw-tone');
    const csrf      = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const RATING_LABELS = {
        1: 'Very Dissatisfied',
        2: 'Dissatisfied',
        3: 'Average',
        4: 'Good',
        5: 'Excellent',
    };

    // ── Star rating ──
    function setRating(n) {
        ratingIn.value = n;
        ratingLbl.textContent = RATING_LABELS[n];
        stars.forEach((s, i) => s.classList.toggle('active', i < n));
    }
    stars.forEach((s, i) => {
        s.addEventListener('click', () => setRating(i + 1));
    });
    setRating(5);

    // ── Tone pills ──
    tones.forEach(t => {
        t.addEventListener('click', () => {
            tones.forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            toneIn.value = t.dataset.tone;
        });
    });

    // ── Submit ──
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        result.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.review-writer.compose") }}', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Failed to generate review.';
                errEl.classList.add('open');
                return;
            }

            const rating = parseInt(ratingIn.value, 10);
            document.getElementById('rwReviewStars').textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating);
            document.getElementById('rwReviewText').textContent = data.result.review;

            if (data.result.short) {
                document.getElementById('rwShortText').textContent = data.result.short;
                document.getElementById('rwShortBox').style.display = 'block';
            } else {
                document.getElementById('rwShortBox').style.display = 'none';
            }

            result.classList.add('open');
            result.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            loading.classList.remove('open');
            submit.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('open');
        }
    });

    // ── Copy ──
    document.getElementById('rwCopyBtn')?.addEventListener('click', async function () {
        const text = document.getElementById('rwReviewText').textContent;
        try {
            await navigator.clipboard.writeText(text);
            this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
            setTimeout(() => {
                this.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy Review';
            }, 2000);
        } catch (e) {
            alert('Please copy manually.');
        }
    });

    // ── Regenerate ──
    document.getElementById('rwRegenBtn')?.addEventListener('click', () => form.dispatchEvent(new Event('submit')));
})();
</script>

@endsection
