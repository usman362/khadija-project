@extends('layouts.public')

@section('title', 'How It Works | ' . config('app.name'))

@push('styles')
<style>
    /* ───────────────────────────────────────────────────────────
       HOW IT WORKS — official explainer page.
       Walks through Registration → Browsing → Referrals →
       Booking → Memberships → Commissions → Support → Reviews.
       Three audiences are highlighted: GigProfessionals, Clients,
       and Influencers.
       ─────────────────────────────────────────────────────────── */

    /* ─── HERO ─── */
    .hiw-hero {
        padding: 180px 0 50px;
        position: relative;
        overflow: hidden;
    }
    /* Photographic cover image behind the hero, dimmed and overlaid
       with a vertical gradient so the eyebrow/title stay legible. */
    .hiw-hero-bg {
        position: absolute; inset: 0; z-index: 0;
    }
    .hiw-hero-bg img {
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0.30;
    }
    .hiw-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(900px 420px at 18% 10%, rgba(59,130,246,0.20), transparent 55%),
            radial-gradient(800px 400px at 85% 0%, rgba(139,92,246,0.22), transparent 55%),
            radial-gradient(700px 300px at 50% 100%, rgba(249,115,22,0.10), transparent 60%),
            linear-gradient(180deg, rgba(11,15,26,0.55) 0%, rgba(11,15,26,0.92) 80%, var(--bg-dark) 100%);
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
        letter-spacing: -0.02em; margin-bottom: 18px;
        line-height: 1.1;
    }
    .hiw-hero h1 .grad {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hiw-hero p.lede {
        font-size: 1.05rem; color: var(--text-muted);
        max-width: 760px; margin: 0 auto 26px;
        line-height: 1.7;
    }
    .hiw-audiences {
        display: inline-flex; flex-wrap: wrap; gap: 10px;
        justify-content: center;
    }
    .hiw-aud-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 18px; border-radius: 999px;
        font-size: 13px; font-weight: 700;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.10);
        color: var(--text-light);
    }
    .hiw-aud-pill .swatch { width: 8px; height: 8px; border-radius: 50%; }
    .hiw-aud-pill.is-pro    .swatch { background: #f97316; box-shadow: 0 0 8px rgba(249,115,22,0.6); }
    .hiw-aud-pill.is-client .swatch { background: #3b82f6; box-shadow: 0 0 8px rgba(59,130,246,0.6); }
    .hiw-aud-pill.is-influ  .swatch { background: #8b5cf6; box-shadow: 0 0 8px rgba(139,92,246,0.6); }

    /* ─── STICKY STEPPER NAV ─── */
    .hiw-stepper-wrap {
        position: sticky; top: 70px; z-index: 50;
        background: rgba(8, 11, 22, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid rgba(255,255,255,0.06);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 14px 0;
        margin: 0 0 40px;
    }
    .hiw-stepper {
        display: flex; align-items: center;
        gap: 0; max-width: 1100px; margin: 0 auto;
        padding: 0 16px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .hiw-stepper::-webkit-scrollbar { display: none; }
    .hiw-step-link {
        display: inline-flex; flex-direction: column; align-items: center;
        flex: 1 0 auto;
        padding: 6px 10px;
        text-decoration: none;
        position: relative;
        min-width: 88px;
    }
    .hiw-step-link .num {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; color: #fff;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        margin-bottom: 6px;
        transition: all 0.25s;
    }
    .hiw-step-link.is-active .num,
    .hiw-step-link:hover .num {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border-color: transparent;
        box-shadow: 0 6px 16px rgba(139,92,246,0.4);
        transform: scale(1.05);
    }
    .hiw-step-link .lbl {
        font-size: 11px; font-weight: 700;
        color: var(--text-muted);
        white-space: nowrap;
        letter-spacing: 0.2px;
        transition: color 0.2s;
    }
    .hiw-step-link.is-active .lbl,
    .hiw-step-link:hover .lbl { color: #fff; }
    /* Connector lines between step pills */
    .hiw-step-link:not(:last-child)::after {
        content: '';
        position: absolute;
        right: -8px; top: 21px;
        width: 16px; height: 2px;
        background: rgba(255,255,255,0.10);
    }

    /* ─── STEP PANELS ─── */
    .hiw-steps-section { padding: 0 0 40px; }
    .hiw-step-panel {
        position: relative;
        scroll-margin-top: 160px;
        max-width: 1080px;
        margin: 0 auto 28px;
        padding: 36px 32px;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(8px);
        overflow: hidden;
    }
    /* Left edge accent stripe — color depends on the audience of each step */
    .hiw-step-panel::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 5px;
        background: linear-gradient(180deg, var(--gradient-start), var(--gradient-end));
        opacity: 0.85;
    }
    .hiw-step-panel.aud-pro::before    { background: linear-gradient(180deg, #f97316, #f59e0b); }
    .hiw-step-panel.aud-client::before { background: linear-gradient(180deg, #3b82f6, #06b6d4); }
    .hiw-step-panel.aud-influ::before  { background: linear-gradient(180deg, #8b5cf6, #6366f1); }
    .hiw-step-panel.aud-mixed::before  { background: linear-gradient(180deg, #f97316, #8b5cf6); }
    .hiw-step-panel.aud-all::before    { background: linear-gradient(180deg, #22c55e, #14b8a6); }

    /* Down-arrow connector between step panels */
    .hiw-connector {
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 28px;
        width: 40px; height: 40px;
        border-radius: 50%;
        background: rgba(139,92,246,0.10);
        border: 1px solid rgba(139,92,246,0.25);
        color: #a78bfa;
    }

    /* Two-column header inside each panel */
    .hiw-panel-head {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 24px;
        align-items: center;
        margin-bottom: 24px;
    }
    @media (max-width: 700px) {
        .hiw-step-panel { padding: 28px 22px; }
        .hiw-panel-head { grid-template-columns: 1fr; gap: 12px; }
    }
    .hiw-step-num {
        width: 110px; height: 110px;
        border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 3rem; font-weight: 900; color: #fff;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 18px 40px rgba(139,92,246,0.35);
        letter-spacing: -2px;
        position: relative;
        font-family: 'Inter', system-ui, sans-serif;
    }
    .hiw-step-panel.aud-pro    .hiw-step-num { background: linear-gradient(135deg, #f97316, #f59e0b); box-shadow: 0 18px 40px rgba(249,115,22,0.35); }
    .hiw-step-panel.aud-client .hiw-step-num { background: linear-gradient(135deg, #3b82f6, #06b6d4); box-shadow: 0 18px 40px rgba(59,130,246,0.35); }
    .hiw-step-panel.aud-influ  .hiw-step-num { background: linear-gradient(135deg, #8b5cf6, #6366f1); box-shadow: 0 18px 40px rgba(139,92,246,0.35); }
    .hiw-step-panel.aud-mixed  .hiw-step-num { background: linear-gradient(135deg, #f97316, #8b5cf6); box-shadow: 0 18px 40px rgba(139,92,246,0.35); }
    .hiw-step-panel.aud-all    .hiw-step-num { background: linear-gradient(135deg, #22c55e, #14b8a6); box-shadow: 0 18px 40px rgba(34,197,94,0.30); }

    .hiw-step-num small {
        position: absolute; top: 12px; left: 14px;
        font-size: 0.65rem; font-weight: 700; letter-spacing: 1px;
        text-transform: uppercase;
        opacity: 0.7;
    }

    .hiw-panel-title { min-width: 0; }
    .hiw-panel-title .audience-tags {
        display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;
    }
    .hiw-aud-tag {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 800; letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .hiw-aud-tag .swatch { width: 6px; height: 6px; border-radius: 50%; }
    .hiw-aud-tag.tag-pro    { color: #fdba74; background: rgba(249,115,22,0.10); border: 1px solid rgba(249,115,22,0.30); }
    .hiw-aud-tag.tag-pro    .swatch { background: #f97316; }
    .hiw-aud-tag.tag-client { color: #93c5fd; background: rgba(59,130,246,0.10); border: 1px solid rgba(59,130,246,0.30); }
    .hiw-aud-tag.tag-client .swatch { background: #3b82f6; }
    .hiw-aud-tag.tag-influ  { color: #c4b5fd; background: rgba(139,92,246,0.10); border: 1px solid rgba(139,92,246,0.30); }
    .hiw-aud-tag.tag-influ  .swatch { background: #8b5cf6; }
    .hiw-aud-tag.tag-all    { color: #86efac; background: rgba(34,197,94,0.10); border: 1px solid rgba(34,197,94,0.28); }
    .hiw-aud-tag.tag-all    .swatch { background: #22c55e; }

    .hiw-panel-title h2 {
        font-size: 1.75rem; font-weight: 800;
        margin-bottom: 6px; letter-spacing: -0.01em;
        color: #fff;
    }
    .hiw-panel-title p.tag-line {
        color: var(--text-muted); font-size: 14px;
        line-height: 1.6;
        margin: 0;
    }

    /* The detail-block grid inside each panel */
    .hiw-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .hiw-detail-grid.is-single { grid-template-columns: 1fr; }
    .hiw-detail-grid.is-three  { grid-template-columns: repeat(3, 1fr); }
    @media (max-width: 900px) {
        .hiw-detail-grid,
        .hiw-detail-grid.is-three { grid-template-columns: 1fr; }
    }

    .hiw-detail {
        position: relative;
        padding: 22px 22px 22px 64px;
        background: rgba(255,255,255,0.025);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        transition: border-color 0.2s, background 0.2s;
    }
    .hiw-detail:hover {
        border-color: rgba(139,92,246,0.30);
        background: rgba(139,92,246,0.05);
    }
    .hiw-detail-num {
        position: absolute; left: 18px; top: 22px;
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        box-shadow: 0 5px 14px rgba(139,92,246,0.35);
    }
    .hiw-step-panel.aud-pro    .hiw-detail-num { background: linear-gradient(135deg, #f97316, #f59e0b); box-shadow: 0 5px 14px rgba(249,115,22,0.30); }
    .hiw-step-panel.aud-client .hiw-detail-num { background: linear-gradient(135deg, #3b82f6, #06b6d4); box-shadow: 0 5px 14px rgba(59,130,246,0.30); }
    .hiw-step-panel.aud-influ  .hiw-detail-num { background: linear-gradient(135deg, #8b5cf6, #6366f1); box-shadow: 0 5px 14px rgba(139,92,246,0.30); }
    .hiw-step-panel.aud-mixed.aud-mixed-tone-pro    .hiw-detail-num { background: linear-gradient(135deg, #f97316, #f59e0b); }
    .hiw-step-panel.aud-mixed.aud-mixed-tone-influ  .hiw-detail-num { background: linear-gradient(135deg, #8b5cf6, #6366f1); }
    .hiw-detail.detail-pro    .hiw-detail-num { background: linear-gradient(135deg, #f97316, #f59e0b); }
    .hiw-detail.detail-influ  .hiw-detail-num { background: linear-gradient(135deg, #8b5cf6, #6366f1); }
    .hiw-detail.detail-client .hiw-detail-num { background: linear-gradient(135deg, #3b82f6, #06b6d4); }
    .hiw-step-panel.aud-all    .hiw-detail-num { background: linear-gradient(135deg, #22c55e, #14b8a6); box-shadow: 0 5px 14px rgba(34,197,94,0.30); }

    .hiw-detail h3 {
        font-size: 1rem; font-weight: 800;
        margin-bottom: 6px; color: #fff;
    }
    .hiw-detail p {
        color: var(--text-muted); font-size: 13.5px;
        line-height: 1.65; margin: 0;
    }

    /* Audience sub-headers when a panel splits content by audience */
    .hiw-audience-block { margin-bottom: 22px; }
    .hiw-audience-block:last-child { margin-bottom: 0; }
    .hiw-audience-header {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px dashed rgba(255,255,255,0.08);
    }
    .hiw-audience-header h4 {
        font-size: 0.95rem; font-weight: 800;
        margin: 0; color: #fff;
        text-transform: uppercase; letter-spacing: 1px;
    }

    /* Stat tiles for section 6 */
    .hiw-stats {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 14px; margin-top: 20px;
    }
    @media (max-width: 720px) { .hiw-stats { grid-template-columns: 1fr; } }
    .hiw-stat {
        padding: 22px 22px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(139,92,246,0.10), rgba(59,130,246,0.10));
        border: 1px solid rgba(139,92,246,0.22);
        text-align: center;
    }
    .hiw-stat .v {
        font-size: 1.9rem; font-weight: 900;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        line-height: 1; margin-bottom: 8px;
    }
    .hiw-stat .k {
        font-size: 12px; font-weight: 700;
        color: var(--text-muted); letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ─── CLOSING SUMMARY + CTA ─── */
    .hiw-final-cta {
        padding: 60px 0 110px;
        text-align: center;
        position: relative;
    }
    .hiw-final-cta::before {
        content: ''; position: absolute; inset: 0;
        background:
            radial-gradient(700px 320px at 50% 30%, rgba(139,92,246,0.14), transparent 60%),
            radial-gradient(560px 280px at 20% 80%, rgba(249,115,22,0.10), transparent 60%);
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
    .hiw-final-cta p {
        color: var(--text-muted); font-size: 16px;
        max-width: 640px; margin: 0 auto 28px;
        line-height: 1.65;
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
    .hiw-final-cta .btn-row {
        display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;
    }

    /* ─── RESPONSIVE TYPOGRAPHY ─── */
    @media (max-width: 700px) {
        .hiw-step-num { width: 80px; height: 80px; font-size: 2rem; border-radius: 18px; margin: 0 auto; }
        .hiw-panel-title { text-align: center; }
        .hiw-panel-title .audience-tags { justify-content: center; }
        .hiw-stepper-wrap { top: 60px; padding: 10px 0; }
        .hiw-step-link { min-width: 70px; }
        .hiw-step-link .lbl { font-size: 10px; }
    }
    @media (max-width: 560px) {
        .hiw-hero { padding: 140px 0 40px; }
        .hiw-hero h1 { font-size: 2rem; }
        .hiw-hero p.lede { font-size: 15px; }
        .hiw-panel-title h2 { font-size: 1.35rem; }
        .hiw-final-cta h2 { font-size: 1.6rem; }
        .hiw-detail { padding: 18px 18px 18px 56px; }
        .hiw-detail-num { left: 14px; top: 18px; width: 28px; height: 28px; font-size: 12px; }
    }
</style>
@endpush

@push('meta')
    <meta name="description" content="How GigResource works — the platform that connects event service providers (GigProfessionals) with clients and Influencers. Registration, browsing, referrals, bookings, memberships, commissions, and support — all in one place.">
@endpush

@section('content')

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hiw-hero">
    {{-- Cover banner: a wedding ceremony scene — bride & groom — that
         signals the kind of event GigResource professionals deliver.
         Dimmed via .hiw-hero-bg::after for legibility. --}}
    <div class="hiw-hero-bg">
        <img src="https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=1800&q=80&auto=format&fit=crop" alt="" loading="eager">
    </div>
    <div class="container">
        <div class="hiw-eyebrow">
            <span class="dot"></span> How GigResource Works
        </div>
        <h1>How <span class="grad">it works</span></h1>
        <p class="lede">
            The GigResource platform connects event service providers (referred to as &ldquo;GigProfessionals&rdquo;) with clients and businesses seeking their services. Our comprehensive system simplifies the process of finding and hiring suitable professionals for various events, from weddings to corporate functions. Below is a detailed overview of how the GigResource website functions, promoting seamless interaction between Influencers, vendors, and clients.
        </p>

        <div class="hiw-audiences" aria-label="Audiences served">
            <span class="hiw-aud-pill is-pro"><span class="swatch"></span>GigProfessionals</span>
            <span class="hiw-aud-pill is-client"><span class="swatch"></span>Clients</span>
            <span class="hiw-aud-pill is-influ"><span class="swatch"></span>Influencers</span>
        </div>
    </div>
</section>

<!-- ── STICKY STEPPER NAV ────────────────────────────────────── -->
<div class="hiw-stepper-wrap" id="hiwStepperWrap" role="navigation" aria-label="Steps navigation">
    <div class="hiw-stepper">
        <a href="#step-1" class="hiw-step-link is-active" data-step="1"><span class="num">1</span><span class="lbl">Registration</span></a>
        <a href="#step-2" class="hiw-step-link" data-step="2"><span class="num">2</span><span class="lbl">Browsing</span></a>
        <a href="#step-3" class="hiw-step-link" data-step="3"><span class="num">3</span><span class="lbl">Referrals</span></a>
        <a href="#step-4" class="hiw-step-link" data-step="4"><span class="num">4</span><span class="lbl">Booking</span></a>
        <a href="#step-5" class="hiw-step-link" data-step="5"><span class="num">5</span><span class="lbl">Memberships</span></a>
        <a href="#step-6" class="hiw-step-link" data-step="6"><span class="num">6</span><span class="lbl">Commissions</span></a>
        <a href="#step-7" class="hiw-step-link" data-step="7"><span class="num">7</span><span class="lbl">Support</span></a>
        <a href="#step-8" class="hiw-step-link" data-step="8"><span class="num">8</span><span class="lbl">Reviews</span></a>
    </div>
</div>

<!-- ── 8 STEP PANELS ─────────────────────────────────────────── -->
<section class="hiw-steps-section">
    <div class="container">

        {{-- ── 1. REGISTRATION ── --}}
        <article class="hiw-step-panel aud-mixed" id="step-1">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>01</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-pro"><span class="swatch"></span>For GigProfessionals</span>
                        <span class="hiw-aud-tag tag-influ"><span class="swatch"></span>For Influencers</span>
                    </div>
                    <h2>Registration</h2>
                    <p class="tag-line">Two distinct sign-up paths — one for the professionals offering services, and one for the influencers who help promote them.</p>
                </div>
            </div>

            <div class="hiw-audience-block">
                <div class="hiw-audience-header">
                    <span class="hiw-aud-tag tag-pro"><span class="swatch"></span>For GigProfessionals</span>
                </div>
                <div class="hiw-detail-grid">
                    <div class="hiw-detail detail-pro">
                        <div class="hiw-detail-num">A</div>
                        <h3>Sign Up</h3>
                        <p>GigProfessionals create an account by completing a registration form that includes basic information such as name, business type, contact details, and service offerings.</p>
                    </div>
                    <div class="hiw-detail detail-pro">
                        <div class="hiw-detail-num">B</div>
                        <h3>Profile Setup</h3>
                        <p>After registration, vendors set up profiles with services, rates, availability, and any relevant certifications or accolades. They can also upload photos or showcase previous work.</p>
                    </div>
                </div>
            </div>

            <div class="hiw-audience-block">
                <div class="hiw-audience-header">
                    <span class="hiw-aud-tag tag-influ"><span class="swatch"></span>For Influencers</span>
                </div>
                <div class="hiw-detail-grid">
                    <div class="hiw-detail detail-influ">
                        <div class="hiw-detail-num">A</div>
                        <h3>Join the Influencer Program</h3>
                        <p>Individuals interested in becoming Influencers can register on the platform by providing their email address and payment methods for receiving commissions.</p>
                    </div>
                    <div class="hiw-detail detail-influ">
                        <div class="hiw-detail-num">B</div>
                        <h3>Access to Tools</h3>
                        <p>Once registered, Influencers receive access to a personalized Influencer Dashboard with their unique referral link, tracking metrics, and promotional resources.</p>
                    </div>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 2. BROWSING AND SEARCHING ── --}}
        <article class="hiw-step-panel aud-client" id="step-2">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>02</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-client"><span class="swatch"></span>For Clients</span>
                    </div>
                    <h2>Browsing and Searching</h2>
                    <p class="tag-line">The discovery flow that lets clients find the right professional for any event in just a few clicks.</p>
                </div>
            </div>

            <div class="hiw-detail-grid">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Search Functionality</h3>
                    <p>Clients can easily navigate the website to find service providers based on their event needs. Filters allow users to search by location, service category (such as DJs, caterers, photographers, etc.), and availability.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Professional Profiles</h3>
                    <p>Each professional has a dedicated profile showcasing their services, pricing, customer reviews, and contact information. Clients can read reviews and assess the experience and suitability of the service providers.</p>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 3. MAKING REFERRALS ── --}}
        <article class="hiw-step-panel aud-influ" id="step-3">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>03</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-influ"><span class="swatch"></span>For Influencers</span>
                    </div>
                    <h2>Making Referrals</h2>
                    <p class="tag-line">How Influencers turn their reach into real revenue through unique tracked links.</p>
                </div>
            </div>

            <div class="hiw-detail-grid">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Promote Services</h3>
                    <p>Influencers can share their unique referral links through social media, email marketing, and other channels to attract potential clients to GigResource.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Tracking Referrals</h3>
                    <p>Influencer earnings are tracked through cookies and referral codes embedded in their shared links, allowing the platform to identify successful referrals accurately.</p>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 4. BOOKING SERVICES ── --}}
        <article class="hiw-step-panel aud-client" id="step-4">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>04</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-client"><span class="swatch"></span>For Clients</span>
                    </div>
                    <h2>Booking Services</h2>
                    <p class="tag-line">From initial inquiry to confirmed booking — secure communication and payment, every step of the way.</p>
                </div>
            </div>

            <div class="hiw-detail-grid">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Inquiry Submission</h3>
                    <p>Clients can submit inquiries or requests for quotes directly to vendors they&rsquo;re interested in. This facilitates direct communication between clients and providers to discuss details, pricing, and availability.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Booking Confirmation</h3>
                    <p>Once both parties agree on terms, clients can finalize bookings through a secure payment system on the platform. This typically involves payment for a free trial or a qualifying membership plan.</p>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 5. MEMBERSHIP PLANS ── --}}
        <article class="hiw-step-panel aud-pro" id="step-5">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>05</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-pro"><span class="swatch"></span>For GigProfessionals</span>
                    </div>
                    <h2>Membership Plans</h2>
                    <p class="tag-line">GigProfessionals choose how prominent they want to be — and unlock the tools that match their growth stage.</p>
                </div>
            </div>

            <div class="hiw-detail-grid">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Membership Options</h3>
                    <p>GigProfessionals can choose from various membership plans that offer different levels of visibility and features on the platform.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Features Included</h3>
                    <p>Memberships may include premium features such as enhanced profile visibility, promotional opportunities, and access to analytics about service performance.</p>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 6. COMMISSION STRUCTURE ── --}}
        <article class="hiw-step-panel aud-influ" id="step-6">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>06</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-influ"><span class="swatch"></span>For Influencers</span>
                    </div>
                    <h2>Commission Structure for Influencers</h2>
                    <p class="tag-line">Influencers earn commissions based on successfully referred vendors who purchase paid memberships.</p>
                </div>
            </div>

            <div class="hiw-detail-grid is-three">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Base Commission</h3>
                    <p>Influencers earn for each vendor who signs up and completes a paid membership. The specific rate depends on the monthly number of referred vendors.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Tiered Bonuses</h3>
                    <p>Influencers may qualify for tiered bonuses for higher referral numbers, earning additional commissions based on performance.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">C</div>
                    <h3>Payout Process</h3>
                    <p>Influencers must meet the payout threshold of $150 in commissions to receive payments, which are processed within 14 business days.</p>
                </div>
            </div>

            <div class="hiw-stats">
                <div class="hiw-stat">
                    <div class="v">$150</div>
                    <div class="k">Payout threshold</div>
                </div>
                <div class="hiw-stat">
                    <div class="v">14 days</div>
                    <div class="k">Business-day processing</div>
                </div>
                <div class="hiw-stat">
                    <div class="v">Tiered</div>
                    <div class="k">Bonus structure</div>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 7. SUPPORT AND RESOURCES ── --}}
        <article class="hiw-step-panel aud-all" id="step-7">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>07</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-all"><span class="swatch"></span>For All Users</span>
                    </div>
                    <h2>Support and Resources</h2>
                    <p class="tag-line">Help, learning, and community — available to every user across the platform.</p>
                </div>
            </div>

            <div class="hiw-detail-grid is-three">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">A</div>
                    <h3>Help Center</h3>
                    <p>GigResource provides a comprehensive help center that includes FAQs and contact options for customer support.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">B</div>
                    <h3>Training and Webinars</h3>
                    <p>The platform offers training resources and optional webinars for both Influencers and GigProfessionals to improve their marketing strategies and service offerings.</p>
                </div>
                <div class="hiw-detail">
                    <div class="hiw-detail-num">C</div>
                    <h3>Community Engagement</h3>
                    <p>Users can engage with others in the community through forums or events and gain insights into best practices for promoting their services or leveraging influencer marketing.</p>
                </div>
            </div>
        </article>

        <div class="hiw-connector" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        {{-- ── 8. FEEDBACK AND REVIEWS ── --}}
        <article class="hiw-step-panel aud-all" id="step-8">
            <div class="hiw-panel-head">
                <div class="hiw-step-num"><small>Step</small>08</div>
                <div class="hiw-panel-title">
                    <div class="audience-tags">
                        <span class="hiw-aud-tag tag-all"><span class="swatch"></span>For All Users</span>
                    </div>
                    <h2>Feedback and Reviews</h2>
                    <p class="tag-line">The two-way feedback loop that keeps quality high and helps every future client choose with confidence.</p>
                </div>
            </div>

            <div class="hiw-detail-grid is-single">
                <div class="hiw-detail">
                    <div class="hiw-detail-num">★</div>
                    <h3>Leave honest reviews after every booking</h3>
                    <p>All users are encouraged to leave reviews and feedback after utilizing services. This information not only helps improve overall quality but also assists potential clients in making informed choices when selecting vendors.</p>
                </div>
            </div>
        </article>

    </div>
</section>

<!-- ── CLOSING SUMMARY + CTA ─────────────────────────────────── -->
<section class="hiw-final-cta">
    <div class="container">
        <h2>Designed for <span class="grad">every event, every role.</span></h2>
        <p>
            The GigResource platform is designed to facilitate effective connections between event service providers and clients while empowering Influencers to generate income through referrals. By offering a streamlined process for searching, booking, and managing vendor services, GigResource enhances the overall experience of planning any event — ensuring clients find the perfect match for their specific needs.
        </p>
        <div class="btn-row">
            <a href="{{ route('public.browse') }}" class="hiw-btn hiw-btn-primary">
                Browse professionals
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <a href="{{ route('register', ['role' => 'supplier']) }}" class="hiw-btn hiw-btn-coral">
                Become a GigProfessional
            </a>
            <a href="{{ route('register') }}" class="hiw-btn hiw-btn-ghost">
                Join as Influencer
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Sticky stepper — highlight the active step as the user scrolls.
    // Uses IntersectionObserver so it stays smooth even on long pages.
    (function() {
        const links = document.querySelectorAll('.hiw-step-link');
        const panels = document.querySelectorAll('.hiw-step-panel');
        if (!links.length || !panels.length) return;

        // Smooth scroll for stepper clicks (with sticky-nav offset)
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                const id = link.getAttribute('href');
                const target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                const navOffset = 150;
                const top = target.getBoundingClientRect().top + window.scrollY - navOffset;
                window.scrollTo({ top, behavior: 'smooth' });
            });
        });

        // Highlight the link whose panel is currently in view
        const setActive = (stepId) => {
            links.forEach(l => l.classList.toggle('is-active', l.dataset.step === stepId));
        };

        const io = new IntersectionObserver((entries) => {
            // Pick the entry closest to the top of the viewport
            const visible = entries
                .filter(e => e.isIntersecting)
                .sort((a, b) => Math.abs(a.boundingClientRect.top) - Math.abs(b.boundingClientRect.top));
            if (visible.length) {
                const id = visible[0].target.id;     // e.g. "step-3"
                const num = id.split('-')[1];
                setActive(num);
            }
        }, { rootMargin: '-30% 0px -55% 0px', threshold: 0 });

        panels.forEach(p => io.observe(p));
    })();
</script>

{{-- ─── SEO: Structured Data (JSON-LD) ────────────────────────────
     Organization + HowTo schema so search engines understand the
     full 8-step process documented on this page.

     The `@` keys (`@context`, `@type`) collide with Laravel 12's
     new Blade directives. Concatenate them at runtime so Blade
     never sees a bare `@directive`.
-----------------------------------------------------------------}}
@php
    $_ctx = '@' . 'context';
    $_typ = '@' . 'type';

    $orgSchema = [
        $_ctx         => 'https://schema.org',
        $_typ         => 'Organization',
        'name'        => config('app.name'),
        'url'         => url('/'),
        'logo'        => asset('logos/logo-light.png'),
        'description' => 'GigResource connects event service providers (GigProfessionals) with clients and Influencers — a comprehensive platform for finding, booking, and promoting event services.',
        'sameAs'      => [
            'https://www.facebook.com/gigresource/',
            'https://www.instagram.com/gigresource2025/',
            'https://www.tiktok.com/@gigresource123/',
        ],
    ];

    $howToSchema = [
        $_ctx         => 'https://schema.org',
        $_typ         => 'HowTo',
        'name'        => 'How GigResource works',
        'description' => 'The eight-step overview of how the GigResource platform connects GigProfessionals, Clients, and Influencers.',
        'step' => [
            [$_typ => 'HowToStep', 'position' => 1, 'name' => 'Registration',                          'text' => 'GigProfessionals sign up and set up their profile. Influencers join the program and gain access to the Influencer Dashboard with a unique referral link.'],
            [$_typ => 'HowToStep', 'position' => 2, 'name' => 'Browsing and Searching',                'text' => 'Clients use filters by location, category, and availability to find the right professional. Each professional profile shows services, pricing, reviews, and contact info.'],
            [$_typ => 'HowToStep', 'position' => 3, 'name' => 'Making Referrals',                      'text' => 'Influencers share their unique referral link across channels. Earnings are tracked accurately via cookies and referral codes.'],
            [$_typ => 'HowToStep', 'position' => 4, 'name' => 'Booking Services',                      'text' => 'Clients submit inquiries directly to vendors and finalize bookings through a secure payment system once both parties agree on terms.'],
            [$_typ => 'HowToStep', 'position' => 5, 'name' => 'Membership Plans',                      'text' => 'GigProfessionals choose from membership tiers that offer different levels of visibility, promotional opportunities, and analytics access.'],
            [$_typ => 'HowToStep', 'position' => 6, 'name' => 'Commission Structure for Influencers',  'text' => 'Influencers earn base commission per paid-member vendor, qualify for tiered bonuses, and receive payouts after reaching $150 — processed within 14 business days.'],
            [$_typ => 'HowToStep', 'position' => 7, 'name' => 'Support and Resources',                 'text' => 'A help center, training and webinars, and community forums are available to all users.'],
            [$_typ => 'HowToStep', 'position' => 8, 'name' => 'Feedback and Reviews',                  'text' => 'All users are encouraged to leave honest reviews after every booking — improving quality and helping future clients choose wisely.'],
        ],
    ];
@endphp

<script type="application/ld+json">{!! json_encode($orgSchema,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($howToSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
