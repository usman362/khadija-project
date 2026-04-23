@extends('layouts.public')

@section('title', 'How It Works | ' . config('app.name'))

@push('styles')
<style>
    /* ───────────────────────────────────────────────────────────
       HOW IT WORKS — standalone explainer page.
       Dual-audience (clients + pros), comparison table, FAQ.
       ─────────────────────────────────────────────────────────── */

    /* ─── HERO ─── */
    .hiw-hero {
        padding: 140px 0 56px;
        position: relative;
        overflow: hidden;
    }
    .hiw-hero::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(900px 420px at 18% 10%, rgba(59,130,246,0.18), transparent 55%),
            radial-gradient(800px 400px at 85% 0%, rgba(139,92,246,0.18), transparent 55%),
            radial-gradient(700px 300px at 50% 100%, rgba(249,115,22,0.08), transparent 60%);
        pointer-events: none;
    }
    .hiw-hero .container { position: relative; z-index: 1; text-align: center; }
    .hiw-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 999px;
        background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.28);
        font-size: 12px; font-weight: 700; letter-spacing: 1px;
        text-transform: uppercase; color: #c4b5fd;
        margin-bottom: 20px;
    }
    .hiw-eyebrow .dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 0 8px rgba(139,92,246,0.6);
    }
    .hiw-hero h1 {
        font-size: 3rem; font-weight: 900;
        letter-spacing: -0.02em; margin-bottom: 14px;
        line-height: 1.1;
    }
    .hiw-hero h1 .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hiw-hero p.lede {
        font-size: 1.1rem; color: var(--text-muted);
        max-width: 640px; margin: 0 auto 32px;
        line-height: 1.6;
    }

    /* ─── AUDIENCE TOGGLE ─── */
    .hiw-toggle {
        display: inline-flex; padding: 6px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 999px; backdrop-filter: blur(8px);
        margin-bottom: 0;
    }
    .hiw-toggle button {
        padding: 10px 26px; border: none; background: transparent;
        color: var(--text-muted); font-size: 14px; font-weight: 700;
        border-radius: 999px; cursor: pointer; transition: all 0.3s;
        font-family: inherit;
    }
    .hiw-toggle button:hover { color: var(--text-light); }
    .hiw-toggle button.is-active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        box-shadow: 0 6px 18px rgba(139,92,246,0.35);
    }

    /* ─── STEPS SECTION ─── */
    .hiw-steps-section {
        padding: 60px 0 80px;
        position: relative;
    }
    .hiw-steps-head {
        text-align: center; margin-bottom: 40px;
    }
    .hiw-steps-head h2 {
        font-size: 2rem; font-weight: 800;
        margin-bottom: 10px; letter-spacing: -0.01em;
    }
    .hiw-steps-head p { color: var(--text-muted); font-size: 15px; max-width: 580px; margin: 0 auto; }

    .hiw-steps {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
        position: relative;
    }
    @media (max-width: 1000px) { .hiw-steps { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px)  { .hiw-steps { grid-template-columns: 1fr; } }

    .hiw-step {
        position: relative;
        padding: 32px 24px; border-radius: 20px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        backdrop-filter: blur(10px);
        transition: transform 0.3s, border-color 0.3s, background 0.3s;
    }
    .hiw-step:hover {
        transform: translateY(-4px);
        border-color: rgba(139,92,246,0.28);
        background: rgba(139,92,246,0.05);
    }
    .hiw-step-num {
        position: absolute; top: -14px; left: 24px;
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 6px 16px rgba(139,92,246,0.4);
    }
    .hiw-step-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 18px;
        background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(139,92,246,0.15));
        border: 1px solid rgba(139,92,246,0.25);
    }
    .hiw-step-icon svg { color: #8b5cf6; }
    .hiw-step h3 {
        font-size: 1.1rem; font-weight: 700; margin-bottom: 8px;
    }
    .hiw-step p {
        font-size: 14px; color: var(--text-muted); line-height: 1.6;
    }

    /* Pro flow gets a warm coral accent to differentiate from client blue/purple */
    .hiw-flow.is-pro .hiw-step:hover { border-color: rgba(249,115,22,0.35); background: rgba(249,115,22,0.04); }
    .hiw-flow.is-pro .hiw-step-icon {
        background: linear-gradient(135deg, rgba(249,115,22,0.15), rgba(245,158,11,0.15));
        border-color: rgba(249,115,22,0.25);
    }
    .hiw-flow.is-pro .hiw-step-icon svg { color: #fb923c; }
    .hiw-flow.is-pro .hiw-step-num {
        background: linear-gradient(135deg, #f97316, #f59e0b);
        box-shadow: 0 6px 16px rgba(249,115,22,0.4);
    }

    .hiw-flow { display: none; }
    .hiw-flow.is-active { display: block; }

    .hiw-flow-cta {
        text-align: center; margin-top: 40px;
    }
    .hiw-btn {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 14px 28px; border-radius: 12px;
        font-weight: 700; font-size: 15px; text-decoration: none;
        transition: all 0.2s; cursor: pointer; border: none;
        font-family: inherit;
    }
    .hiw-btn-primary {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff; box-shadow: 0 10px 26px rgba(139,92,246,0.35);
    }
    .hiw-btn-primary:hover { transform: translateY(-1px); opacity: 0.95; }
    .hiw-btn-coral {
        background: linear-gradient(135deg, #f97316, #f59e0b);
        color: #fff; box-shadow: 0 10px 26px rgba(249,115,22,0.35);
    }
    .hiw-btn-coral:hover { transform: translateY(-1px); opacity: 0.95; }
    .hiw-btn-ghost {
        background: rgba(255,255,255,0.04);
        border: 1.5px solid rgba(255,255,255,0.15); color: #fff;
    }
    .hiw-btn-ghost:hover { border-color: #8b5cf6; background: rgba(139,92,246,0.08); }

    /* ─── VIDEO SECTION ─── */
    .hiw-video-section { padding: 40px 0 80px; }
    .hiw-video-wrap {
        max-width: 920px; margin: 0 auto;
        position: relative; border-radius: 24px; overflow: hidden;
        background: linear-gradient(135deg, #1a2440, #0f1629);
        border: 1px solid rgba(139,92,246,0.25);
        aspect-ratio: 16 / 9;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 30px 80px rgba(0,0,0,0.4);
    }
    .hiw-video-wrap::before {
        content: ''; position: absolute; inset: 0;
        background:
            radial-gradient(600px 300px at 50% 50%, rgba(139,92,246,0.25), transparent 60%),
            radial-gradient(400px 200px at 20% 80%, rgba(59,130,246,0.18), transparent 55%);
        pointer-events: none;
    }
    .hiw-video-play {
        position: relative; z-index: 1;
        width: 88px; height: 88px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border: none; cursor: pointer;
        box-shadow: 0 10px 40px rgba(139,92,246,0.55), 0 0 0 10px rgba(139,92,246,0.12);
        transition: transform 0.3s;
    }
    .hiw-video-play:hover { transform: scale(1.08); }
    .hiw-video-play svg { color: #fff; margin-left: 4px; }
    .hiw-video-caption {
        position: absolute; bottom: 24px; left: 0; right: 0;
        text-align: center; z-index: 1;
        color: rgba(255,255,255,0.75); font-size: 14px; font-weight: 500;
    }

    /* ─── COMPARISON TABLE ─── */
    .hiw-compare-section { padding: 60px 0 80px; }
    .hiw-compare-head { text-align: center; margin-bottom: 40px; }
    .hiw-compare-head h2 {
        font-size: 2rem; font-weight: 800; margin-bottom: 10px;
    }
    .hiw-compare-head p { color: var(--text-muted); font-size: 15px; }

    .hiw-compare {
        max-width: 960px; margin: 0 auto;
        border-radius: 20px; overflow: hidden;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.02);
    }
    .hiw-compare-table { width: 100%; border-collapse: collapse; }
    .hiw-compare-table thead th {
        padding: 20px 18px;
        font-size: 14px; font-weight: 700; text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.02);
    }
    .hiw-compare-table thead th:first-child { color: var(--text-muted); font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; }
    .hiw-compare-table thead th.hiw-col-us {
        background: linear-gradient(135deg, rgba(59,130,246,0.12), rgba(139,92,246,0.12));
        color: #fff;
        position: relative;
    }
    .hiw-compare-table thead th.hiw-col-us::before {
        content: 'RECOMMENDED'; position: absolute; top: 4px; right: 10px;
        font-size: 9px; font-weight: 800; letter-spacing: 1px;
        color: #c4b5fd;
    }
    .hiw-compare-table thead th.hiw-col-other { color: var(--text-light); }
    .hiw-compare-table td {
        padding: 16px 18px; font-size: 14px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }
    .hiw-compare-table tbody tr:last-child td { border-bottom: none; }
    .hiw-compare-table td:first-child {
        color: var(--text-light); font-weight: 600;
        width: 32%;
    }
    .hiw-compare-table td.hiw-col-us {
        background: rgba(139,92,246,0.04);
        color: #fff; font-weight: 600;
    }
    .hiw-cell-check, .hiw-cell-x, .hiw-cell-half {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600;
    }
    .hiw-cell-check { color: #86efac; }
    .hiw-cell-x     { color: #fca5a5; }
    .hiw-cell-half  { color: #fbbf24; }

    @media (max-width: 720px) {
        .hiw-compare-table { font-size: 13px; }
        .hiw-compare-table thead th,
        .hiw-compare-table td { padding: 12px 10px; }
        .hiw-compare-table td:first-child,
        .hiw-compare-table thead th:first-child { width: 40%; font-size: 12px; }
    }

    /* ─── FAQ ─── */
    .hiw-faq-section { padding: 60px 0 80px; }
    .hiw-faq-wrap { max-width: 780px; margin: 0 auto; }
    .hiw-faq-head { text-align: center; margin-bottom: 36px; }
    .hiw-faq-head h2 { font-size: 2rem; font-weight: 800; margin-bottom: 10px; }
    .hiw-faq-head p  { color: var(--text-muted); font-size: 15px; }

    .hiw-faq-item {
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        background: rgba(255,255,255,0.02);
        margin-bottom: 12px;
        overflow: hidden;
        transition: border-color 0.3s, background 0.3s;
    }
    .hiw-faq-item[open] {
        border-color: rgba(139,92,246,0.3);
        background: rgba(139,92,246,0.04);
    }
    .hiw-faq-item summary {
        padding: 18px 22px; cursor: pointer;
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; list-style: none;
        font-size: 15px; font-weight: 600; color: #fff;
    }
    .hiw-faq-item summary::-webkit-details-marker { display: none; }
    .hiw-faq-item summary::after {
        content: ''; width: 22px; height: 22px; flex-shrink: 0;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='%238b5cf6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
        transition: transform 0.25s;
    }
    .hiw-faq-item[open] summary::after { transform: rotate(180deg); }
    .hiw-faq-body {
        padding: 0 22px 18px;
        font-size: 14px; color: var(--text-muted); line-height: 1.7;
    }

    /* ─── FINAL CTA ─── */
    .hiw-final-cta {
        padding: 80px 0 100px;
        text-align: center;
        position: relative;
    }
    .hiw-final-cta::before {
        content: ''; position: absolute; inset: 0;
        background:
            radial-gradient(600px 300px at 50% 30%, rgba(139,92,246,0.12), transparent 60%),
            radial-gradient(500px 260px at 20% 80%, rgba(249,115,22,0.08), transparent 60%);
        pointer-events: none;
    }
    .hiw-final-cta .container { position: relative; z-index: 1; }
    .hiw-final-cta h2 {
        font-size: 2.2rem; font-weight: 900; margin-bottom: 14px;
        letter-spacing: -0.01em;
    }
    .hiw-final-cta h2 .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hiw-final-cta p { color: var(--text-muted); font-size: 16px; max-width: 520px; margin: 0 auto 28px; }
    .hiw-final-cta .btn-row {
        display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;
    }

    @media (max-width: 560px) {
        .hiw-hero h1 { font-size: 2rem; }
        .hiw-hero p.lede { font-size: 15px; }
        .hiw-steps-head h2,
        .hiw-compare-head h2,
        .hiw-faq-head h2,
        .hiw-final-cta h2 { font-size: 1.5rem; }
    }

    /* ─── VIDEO MODAL ─── */
    .hiw-modal {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(5, 8, 15, 0.85);
        backdrop-filter: blur(12px);
        display: none;
        align-items: center; justify-content: center;
        padding: 24px;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .hiw-modal.is-open {
        display: flex;
        opacity: 1;
    }
    .hiw-modal-inner {
        position: relative;
        width: 100%; max-width: 1000px;
        aspect-ratio: 16 / 9;
        border-radius: 20px; overflow: hidden;
        background: #000;
        box-shadow: 0 40px 100px rgba(0,0,0,0.6);
        transform: scale(0.96);
        transition: transform 0.25s ease;
    }
    .hiw-modal.is-open .hiw-modal-inner { transform: scale(1); }
    .hiw-modal-inner iframe {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        border: 0;
    }
    .hiw-modal-close {
        position: absolute; top: -44px; right: 0;
        width: 36px; height: 36px; border-radius: 50%;
        border: none; cursor: pointer;
        background: rgba(255,255,255,0.1);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s, transform 0.2s;
    }
    .hiw-modal-close:hover { background: rgba(255,255,255,0.2); transform: rotate(90deg); }
    @media (max-width: 560px) {
        .hiw-modal-close { top: -40px; right: 4px; width: 32px; height: 32px; }
    }
</style>
@endpush

@push('meta')
    <meta name="description" content="How GigResource works — book verified event professionals with escrow payments, or join as a pro to grow your business. Trusted by 10,000+ clients and verified suppliers.">
@endpush

@section('content')

@php
    // Short, embeddable overview video. Using YouTube "nocookie" embed domain
    // to avoid setting tracking cookies until the user actually plays it.
    // Replace this ID with the real overview reel when one is recorded.
    $videoId = 'dQw4w9WgXcQ';

    // FAQ content pulled into PHP so the template stays readable; editing
    // copy here doesn't pollute the markup with long paragraphs. Also reused
    // below to emit FAQPage JSON-LD for SEO rich snippets.
    $faqs = [
        [
            'q' => 'Is GigResource free to use?',
            'a' => 'Yes — creating a client account and browsing professionals is free. Professionals can sign up free too; we only take a small service fee on completed bookings.',
        ],
        [
            'q' => 'How do you verify professionals?',
            'a' => 'Every verified professional uploads a trade license, proof of liability insurance, and workers\' comp documentation. Our team reviews each submission manually before the verified badge is awarded.',
        ],
        [
            'q' => 'What if I\'m not happy with the service?',
            'a' => 'All payments are held in escrow until the job is marked complete. If something goes wrong, our support team steps in to mediate — and you can leave an honest review that future clients will see.',
        ],
        [
            'q' => 'How fast do professionals respond?',
            'a' => 'Verified pros typically respond within 2 hours. New pros aim for under 24 hours. Response time and reply rate are shown on every profile so you can plan accordingly.',
        ],
        [
            'q' => 'Can I book multiple services for one event?',
            'a' => 'Absolutely. Many clients book a photographer, caterer, and DJ all through GigResource. Each booking is tracked separately in your dashboard, but you see everything in one place.',
        ],
        [
            'q' => 'How do payouts work for professionals?',
            'a' => 'Once the job is marked complete and the client approves it, funds are released to your account within 24 hours. You can withdraw to your bank account at any time.',
        ],
        [
            'q' => 'Do you cover my city?',
            'a' => 'We currently operate in New York, Los Angeles, Chicago, Austin, Miami, Nashville, Seattle, and several other major U.S. metros — with new cities launching every quarter. Use the Browse page to filter by city.',
        ],
    ];
@endphp

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hiw-hero">
    <div class="container">
        <div class="hiw-eyebrow">
            <span class="dot"></span> How GigResource works
        </div>
        <h1>Book verified pros.<br><span class="grad">Get hired for real work.</span></h1>
        <p class="lede">
            A simple, trusted flow for both sides — whether you're planning an event or growing a service business. Here's how it all comes together.
        </p>

        <div class="hiw-toggle" role="tablist" aria-label="Audience toggle">
            <button type="button" class="is-active" data-hiw-aud="client" role="tab">I'm a Client</button>
            <button type="button" data-hiw-aud="pro" role="tab">I'm a Professional</button>
        </div>
    </div>
</section>

<!-- ── STEPS ─────────────────────────────────────────────────── -->
<section class="hiw-steps-section">
    <div class="container">

        {{-- CLIENT FLOW --}}
        <div class="hiw-flow is-active" data-hiw-flow="client">
            <div class="hiw-steps-head">
                <h2>From idea to unforgettable event — in 4 steps</h2>
                <p>We handle the vetting, the payments, and the paperwork. You handle the fun part.</p>
            </div>

            <div class="hiw-steps">
                <div class="hiw-step">
                    <div class="hiw-step-num">1</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <h3>Tell us what you need</h3>
                    <p>Search by category, city, or budget. Filter for verified pros and real reviews — not paid ads.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">2</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <h3>Compare & message</h3>
                    <p>Shortlist a few pros, ask questions directly, and compare quotes side-by-side in one inbox.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">3</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    </div>
                    <h3>Book with escrow</h3>
                    <p>Pay securely — we hold the funds until the job is done. No chasing invoices, no surprises.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">4</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <h3>Enjoy & review</h3>
                    <p>Celebrate your event, then leave an honest rating. Your review helps the next client choose wisely.</p>
                </div>
            </div>

            <div class="hiw-flow-cta">
                <a href="{{ route('public.browse') }}" class="hiw-btn hiw-btn-primary">
                    Browse professionals
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>

        {{-- PROFESSIONAL FLOW --}}
        <div class="hiw-flow is-pro" data-hiw-flow="pro">
            <div class="hiw-steps-head">
                <h2>Turn your craft into a steady stream of bookings</h2>
                <p>Stop chasing leads on Instagram. Clients come to you — already vetted, already interested.</p>
            </div>

            <div class="hiw-steps">
                <div class="hiw-step">
                    <div class="hiw-step-num">1</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <h3>Build your profile</h3>
                    <p>Add a headline, portfolio, pricing, and skills. It takes 10 minutes and showcases your best work.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">2</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h3>Get verified</h3>
                    <p>Upload your license, insurance, and workers' comp. Verified pros get a trusted badge and priority in search.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">3</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <h3>Receive requests</h3>
                    <p>Interested clients message you directly. Accept, decline, or negotiate — you're always in control.</p>
                </div>

                <div class="hiw-step">
                    <div class="hiw-step-num">4</div>
                    <div class="hiw-step-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>Get paid fast</h3>
                    <p>Payments release to you within 24 hours of job completion. Transparent fees, no hidden charges.</p>
                </div>
            </div>

            <div class="hiw-flow-cta">
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="hiw-btn hiw-btn-coral">
                    Become a professional
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>

    </div>
</section>

<!-- ── VIDEO ─────────────────────────────────────────────────── -->
<section class="hiw-video-section">
    <div class="container">
        <div class="hiw-video-wrap" role="button" tabindex="0" aria-label="Play overview video" data-hiw-video-trigger>
            <button type="button" class="hiw-video-play" aria-label="Play video" data-hiw-video-trigger>
                <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
            </button>
            <div class="hiw-video-caption">▶ Watch the 90-second overview</div>
        </div>
    </div>
</section>

<!-- ── VIDEO MODAL ───────────────────────────────────────────── -->
<div class="hiw-modal" id="hiwModal" role="dialog" aria-modal="true" aria-label="Overview video" data-video-id="{{ $videoId }}">
    <div class="hiw-modal-inner">
        <button type="button" class="hiw-modal-close" aria-label="Close video" data-hiw-modal-close>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        {{-- iframe src is injected on open so the video doesn't preload on page visit --}}
        <iframe id="hiwModalFrame"
                src="about:blank"
                title="GigResource overview video"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen></iframe>
    </div>
</div>

<!-- ── COMPARISON TABLE ──────────────────────────────────────── -->
<section class="hiw-compare-section">
    <div class="container">
        <div class="hiw-compare-head">
            <h2>Why GigResource vs the alternatives?</h2>
            <p>The old way of booking pros is slow, risky, and full of guesswork. Here's what changes.</p>
        </div>

        <div class="hiw-compare">
            <table class="hiw-compare-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th class="hiw-col-us">GigResource</th>
                        <th class="hiw-col-other">Instagram DMs</th>
                        <th class="hiw-col-other">Generic marketplaces</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Verified licenses &amp; insurance</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">✓ Every pro</span></td>
                        <td><span class="hiw-cell-x">✗ None</span></td>
                        <td><span class="hiw-cell-half">~ Sometimes</span></td>
                    </tr>
                    <tr>
                        <td>Payment held in escrow</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">✓ Yes</span></td>
                        <td><span class="hiw-cell-x">✗ Pay upfront</span></td>
                        <td><span class="hiw-cell-half">~ Varies</span></td>
                    </tr>
                    <tr>
                        <td>Honest, unfiltered reviews</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">✓ Verified bookings only</span></td>
                        <td><span class="hiw-cell-x">✗ Comments only</span></td>
                        <td><span class="hiw-cell-half">~ Can be gamed</span></td>
                    </tr>
                    <tr>
                        <td>Response time guarantee</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">✓ 2 hours (verified)</span></td>
                        <td><span class="hiw-cell-x">✗ Hit or miss</span></td>
                        <td><span class="hiw-cell-x">✗ Days</span></td>
                    </tr>
                    <tr>
                        <td>Dispute resolution</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">✓ Built-in mediation</span></td>
                        <td><span class="hiw-cell-x">✗ You're on your own</span></td>
                        <td><span class="hiw-cell-half">~ Slow &amp; generic</span></td>
                    </tr>
                    <tr>
                        <td>Service fee</td>
                        <td class="hiw-col-us"><span class="hiw-cell-check">Small flat fee</span></td>
                        <td>Free (but risky)</td>
                        <td><span class="hiw-cell-half">~ 15–20%</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ── FAQ ───────────────────────────────────────────────────── -->
<section class="hiw-faq-section">
    <div class="container">
        <div class="hiw-faq-wrap">
            <div class="hiw-faq-head">
                <h2>Frequently asked questions</h2>
                <p>Still have questions? The answers are below — or talk to us anytime.</p>
            </div>

            @foreach ($faqs as $faq)
                <details class="hiw-faq-item">
                    <summary>{{ $faq['q'] }}</summary>
                    <div class="hiw-faq-body">{{ $faq['a'] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

<!-- ── FINAL CTA ─────────────────────────────────────────────── -->
<section class="hiw-final-cta">
    <div class="container">
        <h2>Ready to make it <span class="grad">happen?</span></h2>
        <p>Thousands of successful events start with a single message. Start browsing, or join as a pro — it's free.</p>
        <div class="btn-row">
            <a href="{{ route('public.browse') }}" class="hiw-btn hiw-btn-primary">
                Browse professionals
            </a>
            <a href="{{ route('register', ['role' => 'supplier']) }}" class="hiw-btn hiw-btn-ghost">
                Become a professional
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Audience toggle — swap client/pro flow on tab click.
    (function() {
        const buttons = document.querySelectorAll('.hiw-toggle [data-hiw-aud]');
        const flows   = document.querySelectorAll('[data-hiw-flow]');
        if (!buttons.length) return;

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const aud = btn.dataset.hiwAud;
                buttons.forEach(b => b.classList.toggle('is-active', b === btn));
                flows.forEach(f => f.classList.toggle('is-active', f.dataset.hiwFlow === aud));
                // Smooth scroll to the steps section so the change is visible on mobile
                const first = document.querySelector('.hiw-steps-section');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    })();

    // Video modal — lazy-loads the YouTube iframe only when the user
    // actually clicks play, so first-visit pageweight stays low and no
    // tracking cookies are set until the user opts in.
    (function() {
        const modal   = document.getElementById('hiwModal');
        const frame   = document.getElementById('hiwModalFrame');
        const triggers = document.querySelectorAll('[data-hiw-video-trigger]');
        const closers  = document.querySelectorAll('[data-hiw-modal-close]');
        if (!modal || !frame) return;

        const videoId = modal.dataset.videoId;
        const embedSrc = 'https://www.youtube-nocookie.com/embed/'
                       + encodeURIComponent(videoId)
                       + '?autoplay=1&rel=0&modestbranding=1';

        function open(e) {
            if (e) e.stopPropagation();
            frame.src = embedSrc;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            modal.classList.remove('is-open');
            document.body.style.overflow = '';
            // Clear src so the video stops playing and doesn't keep running in background
            frame.src = 'about:blank';
        }

        triggers.forEach(t => {
            t.addEventListener('click', open);
            t.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(e); }
            });
        });
        closers.forEach(c => c.addEventListener('click', close));

        // Backdrop click closes (but not inner clicks)
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        // Escape closes
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
    })();
</script>

{{-- ─── SEO: Structured Data (JSON-LD) ────────────────────────────
     FAQPage schema surfaces the questions as rich snippets in Google
     search results. Organization + HowTo schema improve brand SERP
     presentation.

     The `@` keys (`@context`, `@type`) collide with Laravel 12's new
     Blade directives (`@context`, etc.), so the arrays are built with
     concatenated keys and then the JSON is printed through a single
     {!! !!} — Blade never sees a bare @directive.
-----------------------------------------------------------------}}
@php
    $_ctx = '@' . 'context';
    $_typ = '@' . 'type';

    $faqSchema = [
        $_ctx        => 'https://schema.org',
        $_typ        => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            $_typ            => 'Question',
            'name'           => $f['q'],
            'acceptedAnswer' => [
                $_typ   => 'Answer',
                'text'  => $f['a'],
            ],
        ], $faqs),
    ];

    $orgSchema = [
        $_ctx         => 'https://schema.org',
        $_typ         => 'Organization',
        'name'        => config('app.name'),
        'url'         => url('/'),
        'logo'        => asset('logos/logo-light.png'),
        'description' => 'GigResource connects event clients with verified professionals — photographers, DJs, caterers, and more — through a trusted, escrow-backed marketplace.',
        'sameAs'      => [
            'https://www.facebook.com/gigresource/',
            'https://www.instagram.com/gigresource2025/',
            'https://www.tiktok.com/@gigresource123/',
        ],
    ];

    $howToSchema = [
        $_ctx         => 'https://schema.org',
        $_typ         => 'HowTo',
        'name'        => 'How to book a verified professional on ' . config('app.name'),
        'description' => 'A four-step flow for clients — search, compare, book with escrow, and review.',
        'totalTime'   => 'PT10M',
        'step' => [
            [$_typ => 'HowToStep', 'position' => 1, 'name' => 'Tell us what you need', 'text' => 'Search by category, city, or budget. Filter for verified pros and real reviews — not paid ads.'],
            [$_typ => 'HowToStep', 'position' => 2, 'name' => 'Compare & message',     'text' => 'Shortlist a few pros, ask questions directly, and compare quotes side-by-side in one inbox.'],
            [$_typ => 'HowToStep', 'position' => 3, 'name' => 'Book with escrow',      'text' => 'Pay securely — we hold the funds until the job is done. No chasing invoices, no surprises.'],
            [$_typ => 'HowToStep', 'position' => 4, 'name' => 'Enjoy & review',        'text' => 'Celebrate your event, then leave an honest rating. Your review helps the next client choose wisely.'],
        ],
    ];
@endphp

<script type="application/ld+json">{!! json_encode($faqSchema,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($orgSchema,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($howToSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
