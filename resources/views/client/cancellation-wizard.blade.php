@extends('layouts.client')

@section('title', 'Cancellation & Rejection Wizard')
@section('page-title', 'Cancellation & Rejection Wizard')
@section('page-subtitle', 'Categorize your disagreement and choose how you would like to proceed.')

{{-- Integrated Cancellation & Rejection Wizard — 3-step flow (Select Reason →
     Choose Outcome → Resolution Log). resolve() applies the chosen path to a
     real agreement when present, else runs the same logic on a demo context
     and returns a real resolution log. Page-scoped; layout untouched. --}}

@push('styles')
<style>
    .cw { --cw: #ea580c; --cw-indigo: #4f46e5; --cw-red: #dc2626; padding-top: 22px; }
    .cw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); }

    /* header */
    .cw-head { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 8px; }
    .cw-head h1 { font-size: 23px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .cw-badge { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 800; color: var(--cw); background: rgba(234,88,12,0.1); border: 1px solid rgba(234,88,12,0.25); border-radius: 999px; padding: 5px 13px; }
    .cw-badge svg { width: 15px; height: 15px; }
    .cw-agreement { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .cw-close { margin-left: auto; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; }
    .cw-close svg { width: 18px; height: 18px; }
    .cw-sub { font-size: 13.5px; color: var(--text-muted); margin: 0 0 20px; }

    /* progress */
    .cw-prog { display: flex; align-items: flex-start; justify-content: space-between; padding: 26px 40px 8px; }
    .cw-prog-step { display: flex; flex-direction: column; align-items: center; gap: 8px; position: relative; z-index: 1; }
    .cw-prog-num { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; border: 2px solid var(--border-color); background: var(--bg-card); color: var(--text-muted); }
    .cw-prog-step.active .cw-prog-num { background: var(--cw); border-color: var(--cw); color: #fff; }
    .cw-prog-step.done .cw-prog-num { background: #10b981; border-color: #10b981; color: #fff; }
    .cw-prog-lbl { font-size: 12.5px; font-weight: 700; color: var(--text-muted); }
    .cw-prog-step.active .cw-prog-lbl { color: var(--text-primary); }
    .cw-prog-line { flex: 1; height: 2px; background: var(--border-color); margin: 20px -10px 0; }

    /* steps grid */
    .cw-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 28px; padding: 8px 28px 26px; }
    .cw-grid > div, .cw-opt > div, .cw-path > div { min-width: 0; }
    .cw-opt b, .cw-opt p, .cw-path-h b, .cw-path > p { overflow-wrap: break-word; word-break: break-word; }
    .cw-path-h { flex-wrap: wrap; }
    .cw-step-h { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .cw-step-n { width: 26px; height: 26px; border-radius: 50%; background: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0; }
    .cw-step-h b { font-size: 16.5px; font-weight: 800; color: var(--text-primary); }
    .cw-step-help { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; line-height: 1.5; }

    /* reason options */
    .cw-opt { display: flex; gap: 13px; padding: 15px 17px; border: 1.5px solid var(--border-color); border-radius: 12px; margin-bottom: 12px; cursor: pointer; transition: all .15s; }
    .cw-opt:hover { border-color: rgba(234,88,12,0.4); }
    .cw-opt.sel { border-color: var(--cw); background: rgba(234,88,12,0.05); }
    .cw-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--border-color); flex-shrink: 0; margin-top: 1px; display: flex; align-items: center; justify-content: center; }
    .cw-opt.sel .cw-radio { border-color: var(--cw); background: var(--cw); }
    .cw-opt.sel .cw-radio::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #fff; }
    .cw-opt b { font-size: 13.5px; font-weight: 800; color: var(--text-primary); display: block; }
    .cw-opt p { font-size: 12px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.45; }
    .cw-textarea { width: 100%; box-sizing: border-box; min-height: 84px; margin-top: 10px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; resize: vertical; outline: none; }
    .cw-textarea:focus { border-color: var(--cw); }
    .cw-count { font-size: 11px; color: var(--text-muted); text-align: right; margin-top: 4px; }

    /* resolution paths */
    .cw-path { border: 1.5px solid var(--border-color); border-radius: 14px; padding: 18px; margin-bottom: 16px; cursor: pointer; }
    .cw-path.sel-a { border-color: var(--cw-indigo); background: rgba(79,70,229,0.04); }
    .cw-path.sel-b { border-color: var(--cw-red); background: rgba(220,38,38,0.04); }
    .cw-path-h { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .cw-path-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .cw-path.sel-a .cw-path-radio { border-color: var(--cw-indigo); background: var(--cw-indigo); }
    .cw-path.sel-b .cw-path-radio { border-color: var(--cw-red); background: var(--cw-red); }
    .cw-path.sel-a .cw-path-radio::after, .cw-path.sel-b .cw-path-radio::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #fff; }
    .cw-path-ico { display: inline-flex; }
    .cw-path-ico svg { width: 17px; height: 17px; }
    .cw-path-h b { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .cw-rec { font-size: 11px; font-weight: 800; color: var(--cw-indigo); background: rgba(79,70,229,0.12); border-radius: 6px; padding: 3px 9px; }
    .cw-path > p { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 12px; }
    .cw-chk { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: var(--text-secondary); padding: 4px 0; }
    .cw-chk svg { width: 16px; height: 16px; color: #10b981; flex-shrink: 0; }
    .cw-path-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: none; border-radius: 10px; color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; margin-top: 14px; }
    .cw-path-btn svg { width: 16px; height: 16px; }
    .cw-btn-indigo { background: linear-gradient(135deg, #6366f1, #4338ca); }
    .cw-btn-red { background: var(--cw-red); }

    /* bottom nav */
    .cw-nav { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-top: 20px; }
    .cw-nav-back { display: inline-flex; align-items: center; gap: 9px; padding: 13px 20px; border: 1px solid var(--border-color); border-radius: 11px; background: var(--bg-card); color: var(--text-secondary); font-size: 13.5px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .cw-nav-back svg { width: 15px; height: 15px; }
    .cw-nav-next { display: inline-flex; align-items: center; gap: 9px; padding: 13px 24px; border: none; border-radius: 11px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .cw-nav-next svg { width: 16px; height: 16px; }

    /* step 3 resolution log */
    .cw-log-wrap { display: none; padding: 8px 28px 28px; }
    .cw-log-wrap.show { display: block; }
    .cw-log-head { display: flex; align-items: center; gap: 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: 14px; padding: 18px 20px; margin-bottom: 20px; }
    .cw-log-head .ic { width: 46px; height: 46px; border-radius: 50%; background: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cw-log-head .ic svg { width: 24px; height: 24px; color: #fff; }
    .cw-log-head b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .cw-log-head p { font-size: 13px; color: var(--text-secondary); margin: 3px 0 0; }
    .cw-log-meta { font-size: 12px; color: var(--text-muted); margin-left: auto; text-align: right; flex-shrink: 0; }
    .cw-log-list { border: 1px solid var(--border-color); border-radius: 14px; padding: 8px 18px; }
    .cw-log-row { display: flex; gap: 13px; padding: 13px 0; border-top: 1px solid var(--border-color); }
    .cw-log-row:first-child { border-top: none; }
    .cw-log-ic { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cw-log-ic svg { width: 15px; height: 15px; }
    .cw-log-ic.ok { background: rgba(16,185,129,0.12); color: #10b981; }
    .cw-log-ic.info { background: rgba(79,70,229,0.12); color: var(--cw-indigo); }
    .cw-log-row b { font-size: 13.5px; font-weight: 800; color: var(--text-primary); display: block; }
    .cw-log-row p { font-size: 12px; color: var(--text-muted); margin: 2px 0 0; }
    .cw-log-done { display: inline-flex; align-items: center; gap: 9px; margin-top: 20px; padding: 13px 24px; border: none; border-radius: 11px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 14px; font-weight: 800; text-decoration: none; }
    .cw-log-done svg { width: 16px; height: 16px; }

    @media (max-width: 900px) { .cw-grid { grid-template-columns: 1fr; } .cw-prog { padding: 26px 8px 8px; } }
    @media (max-width: 640px) { .cw-prog { padding: 18px 4px 8px; } .cw-prog-lbl { font-size: 11px; } .cw-head h1 { font-size: 19px; } }
</style>
@endpush

@section('content')
<div class="cw"
     data-resolve-url="{{ route('cancellation-wizard.resolve', $agreement ? ['agreement' => $agreement] : []) }}"
     data-back-url="{{ route('client.dashboard') }}">

    {{-- header --}}
    <div class="cw-head">
        <h1>Integrated Cancellation &amp; Rejection Wizard</h1>
        <span class="cw-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>Rejection Workflow Active</span>
        <span class="cw-agreement">{{ $agreementNo }}</span>
        <a href="{{ route('client.dashboard') }}" class="cw-close" title="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></a>
    </div>
    <p class="cw-sub">Please categorize your disagreement and choose how you would like to proceed. Your current project state is securely preserved.</p>

    <div class="cw-card">
        {{-- progress --}}
        <div class="cw-prog">
            <div class="cw-prog-step active" id="cw-p1"><span class="cw-prog-num">1</span><span class="cw-prog-lbl">Select Reason</span></div>
            <div class="cw-prog-line"></div>
            <div class="cw-prog-step" id="cw-p2"><span class="cw-prog-num">2</span><span class="cw-prog-lbl">Choose Outcome</span></div>
            <div class="cw-prog-line"></div>
            <div class="cw-prog-step" id="cw-p3"><span class="cw-prog-num">3</span><span class="cw-prog-lbl">Resolution Log</span></div>
        </div>

        {{-- steps 1 + 2 --}}
        <div id="cw-steps">
            <div class="cw-grid">
                {{-- STEP 1 --}}
                <div>
                    <div class="cw-step-h"><span class="cw-step-n">1</span><b>Step 1: Categorize the Rejection</b></div>
                    <p class="cw-step-help">Help us understand why you're declining this agreement. This helps improve future matches and contracts.</p>
                    @foreach($reasons as $key => [$label, $desc])
                        <label class="cw-opt {{ $loop->first ? 'sel' : '' }}" data-reason="{{ $key }}">
                            <span class="cw-radio"></span>
                            <div><b>{{ $label }}</b><p>{{ $desc }}</p></div>
                        </label>
                    @endforeach
                    <textarea class="cw-textarea" id="cw-details" maxlength="500" placeholder="Describe the issue in detail..."></textarea>
                    <div class="cw-count"><span id="cw-count">0</span>/500</div>
                </div>

                {{-- STEP 2 --}}
                <div>
                    <div class="cw-step-h"><span class="cw-step-n">2</span><b>Step 2: Choose Resolution Path</b></div>
                    <p class="cw-step-help">Pick the best path for your situation. You can renegotiate with AI or cancel the gig.</p>

                    <div class="cw-path sel-a" data-path="negotiate">
                        <div class="cw-path-h">
                            <span class="cw-path-radio"></span>
                            <span class="cw-path-ico" style="color:var(--cw-indigo);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></span>
                            <b>Path A: Return to AI Negotiation Lounge</b>
                            <span class="cw-rec">Recommended</span>
                        </div>
                        <p>We'll archive this draft and re-open the chat with AI. Your rejection reason will be used as a prompt to generate a new Version 2 draft.</p>
                        <div class="cw-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Current draft will be saved as Version 1</div>
                        <div class="cw-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Your reason will be sent to AI Assistant</div>
                        <div class="cw-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI will generate updated terms for you</div>
                        <button type="button" class="cw-path-btn cw-btn-indigo" data-act="negotiate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Proceed with AI Negotiation</button>
                    </div>

                    <div class="cw-path" data-path="void">
                        <div class="cw-path-h">
                            <span class="cw-path-radio"></span>
                            <span class="cw-path-ico" style="color:var(--cw-red);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span>
                            <b>Path B: Cancel &amp; Void Completely</b>
                        </div>
                        <p>Permanently cancels this agreement draft. The gig brief will return to the active marketplace, or the bid selection sequence will open.</p>
                        <button type="button" class="cw-path-btn cw-btn-red" data-act="void"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>Void Agreement &amp; Cancel Gig</button>
                    </div>
                </div>
            </div>

            {{-- bottom nav --}}
            <div class="cw-nav" style="padding: 0 28px 26px;">
                <a href="{{ route('client.dashboard') }}" class="cw-nav-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Return to contract view without changes</a>
                <button type="button" class="cw-nav-next" id="cw-next">Next: Resolution Log <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>
            </div>
        </div>

        {{-- STEP 3: resolution log --}}
        <div class="cw-log-wrap" id="cw-log">
            <div class="cw-log-head">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                <div><b id="cw-log-outcome">Resolution Complete</b><p id="cw-log-message">Your request has been processed.</p></div>
                <div class="cw-log-meta" id="cw-log-meta"></div>
            </div>
            <div class="cw-log-list" id="cw-log-list"></div>
            <a href="{{ route('client.dashboard') }}" class="cw-log-done"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>Done — Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.cw');
    if (!root) return;
    const url = root.dataset.resolveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    let reason = 'financial', path = 'negotiate';

    // Reason selection.
    document.querySelectorAll('.cw-opt').forEach((el) => el.addEventListener('click', function () {
        document.querySelectorAll('.cw-opt').forEach((o) => o.classList.remove('sel'));
        this.classList.add('sel');
        reason = this.dataset.reason;
    }));
    // Details counter.
    $('cw-details').addEventListener('input', function () { $('cw-count').textContent = this.value.length; });

    // Path selection.
    document.querySelectorAll('.cw-path').forEach((el) => el.addEventListener('click', function (e) {
        if (e.target.closest('.cw-path-btn')) return;
        document.querySelectorAll('.cw-path').forEach((p) => p.classList.remove('sel-a', 'sel-b'));
        path = this.dataset.path;
        this.classList.add(path === 'negotiate' ? 'sel-a' : 'sel-b');
    }));

    function logIcon(kind) {
        return kind === 'info'
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
    }
    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    async function resolve(chosenPath, btn) {
        const o = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ reason: reason, details: $('cw-details').value, path: chosenPath }),
            });
            if (!res.ok) { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = o; } return; }
            const d = await res.json();
            $('cw-log-outcome').textContent = d.outcome;
            $('cw-log-message').textContent = d.message;
            $('cw-log-meta').textContent = d.timestamp;
            $('cw-log-list').innerHTML = d.log.map((l) =>
                '<div class="cw-log-row"><span class="cw-log-ic ' + l.kind + '">' + logIcon(l.kind) + '</span><div><b>' + esc(l.title) + '</b><p>' + esc(l.detail) + '</p></div></div>'
            ).join('');
            $('cw-steps').style.display = 'none';
            $('cw-log').classList.add('show');
            $('cw-p1').classList.remove('active'); $('cw-p1').classList.add('done');
            $('cw-p2').classList.remove('active'); $('cw-p2').classList.add('done');
            $('cw-p3').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = o; } }
    }

    document.querySelectorAll('.cw-path-btn').forEach((b) => b.addEventListener('click', function () { resolve(this.dataset.act, this); }));
    $('cw-next').addEventListener('click', function () { resolve(path, this); });
})();
</script>
@endsection
