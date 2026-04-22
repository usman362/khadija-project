@extends('layouts.public')

@section('title', ($policy->title ?? $fallbackTitle) . ' - ' . config('app.name', 'Khadija'))

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&display=swap" rel="stylesheet">
<style>
    /* ─── POLICY CONTENT ──────────────────────────── */
    .policy-hero {
        position: relative;
        padding: 140px 0 60px;
        text-align: center;
        overflow: hidden;
    }
    .policy-hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }
    .policy-hero-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.18;
    }
    .policy-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(11,15,26,0.7) 0%, rgba(11,15,26,0.95) 80%, var(--bg-dark) 100%);
    }
    .policy-hero .container { position: relative; z-index: 1; }
    .policy-hero::before {
        content: '';
        position: absolute;
        top: -30%; left: 50%; transform: translateX(-50%);
        width: 600px; height: 600px;
        background: radial-gradient(circle, rgba(59,130,246,0.1), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
    }
    .policy-hero-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(139,92,246,0.18));
        border: 1px solid rgba(59,130,246,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
    }
    .policy-hero-icon svg { width: 30px; height: 30px; }

    .policy-hero h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .policy-hero h1 .gradient-text {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .policy-date {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .policy-body {
        max-width: 820px;
        margin: 0 auto;
        padding: 40px 24px 80px;
    }

    .policy-body h2 {
        font-size: 1.4rem;
        font-weight: 700;
        margin-top: 36px;
        margin-bottom: 14px;
        color: var(--text-white);
    }

    .policy-body h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 20px;
        margin-bottom: 10px;
        color: var(--text-light);
    }

    .policy-body p {
        color: var(--text-light);
        margin-bottom: 14px;
        line-height: 1.75;
    }

    .policy-body ul, .policy-body ol {
        margin: 14px 0 14px 24px;
        color: var(--text-light);
    }

    .policy-body li {
        margin-bottom: 8px;
        line-height: 1.7;
    }

    .policy-body strong { color: var(--text-white); }
    .policy-body a { color: var(--primary); }
    .policy-body a:hover { text-decoration: underline; }

    .policy-body .highlight-box {
        background: var(--bg-card);
        border-left: 4px solid var(--primary);
        padding: 20px;
        margin: 24px 0;
        border-radius: 4px;
    }

    .policy-body .highlight-box p { margin: 0; }

    /* ─── E-SIGNATURE SECTION ──────────────────────────── */
    .esign-section {
        max-width: 820px;
        margin: 0 auto 60px;
        padding: 0 24px;
    }
    .esign-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 36px 40px;
    }
    .esign-box.signed {
        border-color: rgba(16,185,129,0.4);
        background: rgba(16,185,129,0.05);
    }
    .esign-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-white);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .esign-title svg { width: 22px; height: 22px; flex-shrink: 0; }
    .esign-desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 28px;
        line-height: 1.6;
    }
    .esign-tabs {
        display: flex;
        gap: 0;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 24px;
        width: fit-content;
    }
    .esign-tab {
        padding: 10px 24px;
        font-size: 0.875rem;
        font-weight: 600;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .esign-tab.active {
        background: var(--primary);
        color: #fff;
    }
    .esign-tab:not(.active):hover { color: var(--text-white); }
    .esign-panel { display: none; }
    .esign-panel.active { display: block; }
    .esign-input {
        width: 100%;
        padding: 14px 18px;
        background: rgba(255,255,255,0.04);
        border: 1.5px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-white);
        font-size: 1.1rem;
        font-family: 'Caveat', 'Inter', cursive;
        letter-spacing: 0.5px;
        transition: border-color 0.2s;
        outline: none;
    }
    .esign-input:focus { border-color: var(--primary); }
    .esign-input::placeholder { color: var(--text-muted); font-size: 0.95rem; font-family: 'Inter', sans-serif; }
    .esign-canvas-wrap {
        position: relative;
        border: 1.5px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
        background: rgba(255,255,255,0.03);
    }
    .esign-canvas {
        display: block;
        width: 100%;
        height: 140px;
        cursor: crosshair;
        touch-action: none;
    }
    .esign-canvas-hint {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        font-size: 0.82rem;
        color: var(--text-muted);
        pointer-events: none;
        user-select: none;
    }
    .esign-canvas-clear {
        position: absolute;
        top: 8px;
        right: 10px;
        background: rgba(239,68,68,0.15);
        border: 1px solid rgba(239,68,68,0.3);
        color: #ef4444;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 6px;
        cursor: pointer;
    }
    .esign-canvas-clear:hover { background: rgba(239,68,68,0.25); }
    .esign-meta {
        margin-top: 16px;
        padding: 12px 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        border: 1px solid var(--border-color);
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }
    .esign-meta span { display: flex; align-items: center; gap: 6px; }
    .esign-meta svg { width: 14px; height: 14px; }
    .esign-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        padding: 12px 32px;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }
    .esign-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .esign-btn svg { width: 18px; height: 18px; }
    .esign-error {
        margin-top: 10px;
        font-size: 0.82rem;
        color: #f87171;
    }
    /* Signed state */
    .esign-signed-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(16,185,129,0.12);
        border: 1px solid rgba(16,185,129,0.3);
        color: #10b981;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .esign-signed-badge svg { width: 18px; height: 18px; }
    .esign-signed-details {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.7;
    }
    .esign-signed-details strong { color: var(--text-light); }
    /* Login prompt */
    .esign-login-prompt {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 24px;
        background: rgba(59,130,246,0.07);
        border: 1px solid rgba(59,130,246,0.2);
        border-radius: 12px;
    }
    .esign-login-prompt svg { width: 28px; height: 28px; color: var(--primary); flex-shrink: 0; }
    .esign-login-prompt p { font-size: 0.9rem; color: var(--text-light); margin: 0; }
    .esign-login-prompt a { color: var(--primary); font-weight: 600; }

    /* ─── RESPONSIVE ──────────────────────────── */
    @media (max-width: 768px) {
        .policy-hero h1 { font-size: 1.75rem; }
        .policy-hero { padding: 100px 0 30px; }
        .esign-box { padding: 24px 20px; }
    }
</style>
@endpush

@section('content')

<!-- ─── HERO ───────────────────────────────── -->
<section class="policy-hero">
    <div class="policy-hero-bg">
        <img src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1600&q=80&auto=format&fit=crop" alt="Documents and papers" loading="eager">
    </div>
    <div class="container">
        <div class="policy-hero-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h1><span class="gradient-text">{{ $policy->title ?? $fallbackTitle }}</span></h1>
        <p class="policy-date">Last updated: {{ $policy ? $policy->updated_at->format('F j, Y') : now()->format('F j, Y') }}</p>
    </div>
</section>

<!-- ─── CONTENT ───────────────────────────────── -->
<div class="policy-body">
    @if($policy && $policy->content)
        {!! $policy->content !!}
    @else
        <p style="text-align:center; color: var(--text-muted);">This policy page has not been set up yet. Please check back later.</p>
    @endif
</div>

@if(!empty($policyType))
<!-- ─── E-SIGNATURE ───────────────────────────────── -->
<section class="esign-section">
    @php
        $policyLabels  = ['privacy_policy' => 'Privacy Policy', 'ai_usage_agreement' => 'AI Usage Agreement', 'terms_of_service' => 'Terms of Service'];
        $policyLabel   = $policyLabels[$policyType] ?? 'Policy';
        $policyVersion = $policy?->updated_at?->format('Y.m') ?? '1.0';
    @endphp

    @auth
        @if(session('sign_status') === 'signed')
            <div class="esign-box signed">
                <div class="esign-signed-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Successfully Signed
                </div>
                <div class="esign-signed-details">
                    You have officially signed the <strong>{{ $policyLabel }}</strong>.<br>
                    Your e-signature has been recorded and can be referenced for compliance purposes.
                </div>
            </div>
        @elseif(!empty($existingSignature))
            <div class="esign-box signed">
                <div class="esign-title" style="color:#10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    You have already signed this policy
                </div>
                <div class="esign-signed-details">
                    Signed by: <strong>{{ auth()->user()->name }}</strong><br>
                    Date: <strong>{{ $existingSignature->signed_at->format('F j, Y \a\t g:i A') }}</strong><br>
                    Method: <strong>{{ ucfirst($existingSignature->signature_type) }} Signature</strong><br>
                    Version: <strong>{{ $existingSignature->policy_version }}</strong>
                </div>
            </div>
        @else
            <div class="esign-box">
                <div class="esign-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Sign This {{ $policyLabel }}
                </div>
                <p class="esign-desc">
                    By signing below, you confirm that you have read, understood, and agree to the terms of this
                    <strong style="color:var(--text-white);">{{ $policyLabel }}</strong>. Your electronic signature is legally binding.
                </p>

                {{-- Error display --}}
                @if($errors->has('signature_data'))
                    <div class="esign-error">{{ $errors->first('signature_data') }}</div>
                @endif

                {{-- Tab switcher --}}
                <div class="esign-tabs">
                    <button type="button" class="esign-tab active" onclick="switchTab('typed', this)">Type Signature</button>
                    <button type="button" class="esign-tab" onclick="switchTab('drawn', this)">Draw Signature</button>
                </div>

                <form action="{{ route('policy.sign') }}" method="POST" id="signForm">
                    @csrf
                    <input type="hidden" name="policy_type"    value="{{ $policyType }}">
                    <input type="hidden" name="policy_version" value="{{ $policyVersion }}">
                    <input type="hidden" name="signature_type" id="sigType" value="typed">
                    <input type="hidden" name="signature_data" id="sigData">

                    {{-- Typed panel --}}
                    <div class="esign-panel active" id="panel-typed">
                        <input type="text" id="typedInput" class="esign-input"
                               placeholder="Type your full name here..."
                               autocomplete="name" value="{{ auth()->user()->name }}">
                    </div>

                    {{-- Drawn panel --}}
                    <div class="esign-panel" id="panel-drawn">
                        <div class="esign-canvas-wrap">
                            <canvas id="sigCanvas" class="esign-canvas"></canvas>
                            <span class="esign-canvas-hint" id="canvasHint">Draw your signature here</span>
                            <button type="button" class="esign-canvas-clear" onclick="clearCanvas()">Clear</button>
                        </div>
                    </div>

                    {{-- Metadata strip --}}
                    <div class="esign-meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            {{ auth()->user()->name }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            {{ now()->format('F j, Y') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            v{{ $policyVersion }}
                        </span>
                    </div>

                    <button type="submit" class="esign-btn" onclick="return prepareSubmit()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        I Agree &amp; Sign
                    </button>
                </form>
            </div>
        @endif
    @else
        <div class="esign-box">
            <div class="esign-login-prompt">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                <p>
                    Want to officially sign this {{ $policyLabel }}?
                    <a href="{{ route('login') }}?intended={{ urlencode(request()->fullUrl()) }}">Log in to your account</a>
                    to add your e-signature and keep a record of your acceptance.
                </p>
            </div>
        </div>
    @endauth
</section>
@endif

@endsection

@if(!empty($policyType))
@push('scripts')
<script>
    // ── Canvas drawing ──
    const canvas = document.getElementById('sigCanvas');
    let ctx, drawing = false, hasDrawn = false;

    function resizeCanvas() {
        if (!canvas) return;
        const rect = canvas.parentElement.getBoundingClientRect();
        if (rect.width === 0) return; // panel still hidden, skip
        canvas.width  = rect.width;
        canvas.height = 140;
        if (!ctx) ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#a5b4fc';
        ctx.lineWidth   = 2.5;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
    }

    // ── Tab switching ──
    function switchTab(tab, btn) {
        document.querySelectorAll('.esign-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.esign-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        document.getElementById('sigType').value = tab;

        // Canvas needs resize after panel becomes visible
        if (tab === 'drawn') {
            setTimeout(resizeCanvas, 50);
        }
    }

    if (canvas) {
        ctx = canvas.getContext('2d');
        window.addEventListener('resize', resizeCanvas);

        function getPos(e) {
            const r = canvas.getBoundingClientRect();
            const src = e.touches ? e.touches[0] : e;
            return { x: src.clientX - r.left, y: src.clientY - r.top };
        }

        canvas.addEventListener('mousedown',  e => { drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove',  e => { if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasDrawn = true; document.getElementById('canvasHint').style.display = 'none'; });
        canvas.addEventListener('mouseup',    () => drawing = false);
        canvas.addEventListener('mouseleave', () => drawing = false);
        canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); }, {passive:false});
        canvas.addEventListener('touchmove',  e => { e.preventDefault(); if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasDrawn = true; document.getElementById('canvasHint').style.display = 'none'; }, {passive:false});
        canvas.addEventListener('touchend',   () => drawing = false);
    }

    function clearCanvas() {
        if (ctx) { ctx.clearRect(0, 0, canvas.width, canvas.height); hasDrawn = false; document.getElementById('canvasHint').style.display = ''; }
    }

    // ── Form submission ──
    function prepareSubmit() {
        const type = document.getElementById('sigType').value;

        if (type === 'typed') {
            const val = document.getElementById('typedInput').value.trim();
            if (!val) { alert('Please type your full name to sign.'); return false; }
            document.getElementById('sigData').value = val;
        } else {
            if (!hasDrawn) { alert('Please draw your signature before submitting.'); return false; }
            document.getElementById('sigData').value = canvas.toDataURL('image/png');
        }
        return true;
    }
</script>
@endpush
@endif
