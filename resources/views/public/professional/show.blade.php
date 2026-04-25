@extends('layouts.public')

@section('title', $pro->name . ' — Professional Profile')

@push('styles')
<style>
/* ============================================================
   Public Professional Profile
   ------------------------------------------------------------
   The "store front" a visitor lands on when browsing a pro.
   White cards on the dark public layout, with:
     - Hero (cover + avatar + name + CTA + trust strip)
     - Sticky right sidebar: Top Rated seal, Satisfaction,
       Verified Credentials (all signature magazine-ad pieces)
     - Left column: About, Skills, Portfolio gallery, Reviews
     - Similar pros row at the bottom
     - Mobile-only sticky "Request a Quote" action bar
   ============================================================ */

.pp-page {
    max-width: 1180px;
    margin: 0 auto;
    padding: 100px 24px 120px;
    color: var(--text-primary, #1f2937);
}
@media (max-width: 720px) { .pp-page { padding: 90px 16px 90px; } }

/* ── Breadcrumb ───────────────────────────────────────────── */
.pp-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-muted, #94a3b8);
}
.pp-breadcrumb a {
    color: var(--text-light, #c8cdd8);
    text-decoration: none;
    transition: color 0.15s;
}
.pp-breadcrumb a:hover { color: #fff; }
.pp-breadcrumb svg { width: 12px; height: 12px; opacity: 0.4; }
.pp-breadcrumb .current { color: #fff; font-weight: 500; }

.pp-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 28px;
    align-items: start;
}
@media (max-width: 900px) { .pp-grid { grid-template-columns: 1fr; } }

/* ── Hero card ─────────────────────────────────────────────── */
.pp-hero {
    position: relative;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(255,255,255,0.06);
    margin-bottom: 24px;
}
.pp-hero-cover {
    height: 180px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    background-size: cover; background-position: center;
    position: relative;
}
.pp-hero-cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 55%, rgba(0,0,0,0.18) 100%);
    pointer-events: none;
}
.pp-hero-actions-top {
    position: absolute;
    top: 14px;
    right: 14px;
    display: flex;
    gap: 8px;
    z-index: 2;
}
.pp-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}
.pp-icon-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
.pp-icon-btn svg { width: 16px; height: 16px; }

.pp-hero-body {
    padding: 0 28px 24px;
    display: flex;
    gap: 20px;
    align-items: flex-end;
    margin-top: -60px;
    flex-wrap: wrap;
}
.pp-hero-avatar {
    width: 128px; height: 128px;
    border-radius: 50%;
    border: 5px solid #fff;
    background: #fff;
    box-shadow: 0 10px 28px rgba(0,0,0,0.25);
    object-fit: cover;
    flex-shrink: 0;
}
.pp-hero-meta { flex: 1; min-width: 220px; padding-bottom: 8px; }
.pp-hero-name {
    font-size: 28px; font-weight: 800;
    color: #0f172a;
    margin: 8px 0 4px;
    line-height: 1.1;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.pp-hero-name .verified-check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #fff;
    flex-shrink: 0;
}
.pp-hero-name .verified-check svg { width: 12px; height: 12px; }
.pp-hero-headline {
    font-size: 15px; color: #475569; font-weight: 500;
    margin-bottom: 10px;
}
.pp-hero-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.pp-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px;
    background: #f1f5f9; color: #475569;
    border-radius: 999px;
    font-size: 12.5px; font-weight: 500;
}
.pp-tag svg { width: 12px; height: 12px; }
.pp-tag.featured { background: #fef3c7; color: #92400e; }
.pp-tag.top {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #fff;
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
}
.pp-tag.rating {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #9a3412;
    font-weight: 700;
}
.pp-tag.rating .star { color: #f59e0b; }

/* Primary / secondary / ghost CTAs */
.pp-hero-cta {
    padding-bottom: 8px;
    display: flex; gap: 8px; flex-wrap: wrap;
}
.pp-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 11px 20px;
    border-radius: 11px;
    font-size: 14px; font-weight: 700;
    text-decoration: none;
    transition: all 0.18s ease;
    border: 1px solid transparent;
    cursor: pointer;
    font-family: inherit;
}
.pp-btn svg { width: 15px; height: 15px; }
.pp-btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    color: #fff;
    box-shadow: 0 8px 22px rgba(59, 130, 246, 0.38);
}
.pp-btn-primary:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(59, 130, 246, 0.5);
}
.pp-btn-secondary {
    background: #0f172a; color: #fff;
}
.pp-btn-secondary:hover { background: #1e293b; transform: translateY(-1px); }
.pp-btn-outline { background: #fff; color: #334155; border-color: #cbd5e1; }
.pp-btn-outline:hover { border-color: #2563eb; color: #2563eb; }

/* ── Trust strip below hero body ─────────────────────────── */
.pp-trust-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: linear-gradient(180deg, #fafbfc 0%, #f1f5f9 100%);
    border-top: 1px solid #e5e7eb;
    padding: 16px 28px;
    gap: 14px;
}
@media (max-width: 640px) { .pp-trust-strip { grid-template-columns: repeat(2, 1fr); row-gap: 16px; } }
.pp-trust-item {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.pp-trust-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #3b82f6;
}
.pp-trust-icon svg { width: 16px; height: 16px; }
.pp-trust-text { min-width: 0; }
.pp-trust-label { font-size: 10.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
.pp-trust-value { font-size: 13px; color: #0f172a; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pp-trust-value.accent { color: #059669; }

/* ── Generic content card ──────────────────────────────────── */
.pp-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22);
    border: 1px solid rgba(255,255,255,0.04);
}
.pp-card-title {
    font-size: 16px; font-weight: 700;
    color: #0f172a;
    margin: 0 0 16px;
    display: flex; align-items: center; gap: 8px;
}
.pp-card-title svg { color: #64748b; }
.pp-card-body { font-size: 14px; line-height: 1.65; color: #334155; }

/* ── Satisfaction Results (magazine-ad style) ──────────────── */
.pp-card-satisfaction { padding: 22px; }
.pp-sat-head {
    font-size: 15px; font-weight: 700; color: #0f172a;
    margin-bottom: 14px; text-align: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.pp-sat-bars {
    display: flex; flex-direction: column; gap: 7px;
    margin-bottom: 16px;
}
.pp-sat-row {
    display: grid;
    grid-template-columns: 58px 1fr 48px;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}
.pp-sat-label {
    color: #f59e0b;
    letter-spacing: 1px;
    font-size: 11px;
    white-space: nowrap;
}
.pp-sat-bar-track {
    height: 10px;
    background: #f1f5f9;
    border-radius: 5px;
    overflow: hidden;
    position: relative;
}
.pp-sat-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 5px;
    transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
}
.pp-sat-count {
    text-align: right;
    color: #64748b;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}
.pp-sat-overall {
    text-align: center;
    padding: 12px 0 2px;
    border-top: 1px dashed #e2e8f0;
}
.pp-sat-score {
    font-size: 14px; font-weight: 700; color: #0f172a;
    display: inline-flex; align-items: baseline; gap: 4px;
}
.pp-sat-score .big { font-size: 26px; }
.pp-sat-score .unit { font-size: 13px; color: #64748b; font-weight: 500; }
.pp-sat-count-total {
    font-size: 12px; color: #64748b;
    margin-top: 2px;
}
.pp-sat-cta {
    display: block;
    text-align: center;
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.15s;
}
.pp-sat-cta:hover { color: #1d4ed8; }

/* ── Verified Credentials (checklist) ──────────────────────── */
.pp-card-verified { padding: 22px; }
.pp-ver-head {
    font-size: 15px; font-weight: 700; color: #0f172a;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
    text-align: center;
}
.pp-ver-list { display: flex; flex-direction: column; gap: 10px; }
.pp-ver-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 8px 2px;
}
.pp-ver-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #10b981; color: #fff;
    font-size: 13px; font-weight: 700;
    margin-top: 1px;
}
.pp-ver-item.none .pp-ver-check { background: #e2e8f0; color: #94a3b8; }
.pp-ver-item.pending .pp-ver-check { background: #f59e0b; color: #fff; }
.pp-ver-body { flex: 1; min-width: 0; }
.pp-ver-label {
    font-size: 13.5px; font-weight: 600; color: #0f172a;
    line-height: 1.3;
}
.pp-ver-item.none .pp-ver-label { color: #94a3b8; text-decoration: line-through; }
.pp-ver-num {
    font-size: 11px; color: #64748b;
    margin-top: 2px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    letter-spacing: 0.5px;
}
.pp-ver-status {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #10b981;
}
.pp-ver-item.pending .pp-ver-status { color: #f59e0b; }
.pp-ver-item.none .pp-ver-status { color: #94a3b8; }

/* ── Reviews feed ──────────────────────────────────────────── */
.pp-review {
    padding: 18px 0;
    border-bottom: 1px solid #f1f5f9;
    display: flex; gap: 14px;
}
.pp-review:last-child { border-bottom: none; padding-bottom: 4px; }
.pp-review-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    flex-shrink: 0;
    background: #e2e8f0;
    object-fit: cover;
}
.pp-review-body { flex: 1; min-width: 0; }
.pp-review-head {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.pp-review-name { font-size: 14px; font-weight: 700; color: #0f172a; }
.pp-review-stars { color: #f59e0b; font-size: 13px; letter-spacing: 1px; }
.pp-review-date { font-size: 11px; color: #94a3b8; margin-left: auto; }
.pp-review-title { font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 2px; }
.pp-review-text { font-size: 13.5px; color: #475569; line-height: 1.55; }
.pp-review-response {
    margin-top: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border-left: 3px solid #2563eb;
    border-radius: 4px;
    font-size: 12.5px;
    color: #475569;
}
.pp-review-response strong { color: #0f172a; }
.pp-empty-reviews {
    text-align: center; padding: 32px 12px;
    color: #94a3b8; font-size: 13.5px;
    font-style: italic;
}

/* ── Skill chips ───────────────────────────────────────────── */
.pp-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 4px; }
.pp-chip {
    padding: 5px 13px;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 600;
}

/* ── Portfolio gallery ─────────────────────────────────────── */
.pp-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
}
.pp-gallery-item {
    position: relative;
    display: block;
    aspect-ratio: 4 / 3;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, #eef2ff, #fce7f3);
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.pp-gallery-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.18);
}
.pp-gallery-item img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
.pp-gallery-caption {
    position: absolute;
    inset: auto 0 0 0;
    padding: 30px 14px 12px;
    background: linear-gradient(180deg, transparent, rgba(0,0,0,0.72));
    color: #fff;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.3;
    pointer-events: none;
}
.pp-gallery-caption small { display: block; font-size: 10.5px; opacity: 0.8; font-weight: 400; margin-top: 2px; }
.pp-gallery-item.no-image {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 18px;
    background: linear-gradient(135deg, #f0f4ff 0%, #fce7f3 100%);
    color: #0f172a;
}
.pp-gallery-item.no-image .title { font-size: 14px; font-weight: 700; line-height: 1.3; }
.pp-gallery-item.no-image .desc  { font-size: 12px; color: #64748b; line-height: 1.45; flex: 1; margin: 10px 0; }
.pp-gallery-item.no-image .link {
    font-size: 11.5px; font-weight: 700; color: #2563eb;
    display: inline-flex; align-items: center; gap: 4px;
}
.pp-gallery-item.no-image .link svg { width: 11px; height: 11px; }

/* ── Top-rated seal ────────────────────────────────────────── */
.pp-seal {
    display: flex; align-items: center; gap: 12px;
    padding: 14px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #fcd34d;
    border-radius: 14px;
    margin-bottom: 20px;
    box-shadow: 0 6px 18px rgba(251, 191, 36, 0.25);
}
.pp-seal-icon {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.4);
}
.pp-seal-text { font-size: 12.5px; color: #92400e; line-height: 1.35; }
.pp-seal-text strong { display: block; font-size: 14px; color: #78350f; margin-bottom: 1px; }

/* ── Sidebar sticky wrapper ────────────────────────────────── */
.pp-sidebar {
    position: sticky;
    top: 140px;
    align-self: start;
}
@media (max-width: 900px) { .pp-sidebar { position: static; } }

/* ── Similar pros ──────────────────────────────────────────── */
.pp-similar {
    margin-top: 40px;
}
.pp-similar-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.pp-similar-head h2 {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.01em;
}
.pp-similar-head a {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary, #3b82f6);
    display: inline-flex; align-items: center; gap: 4px;
    transition: gap 0.15s;
}
.pp-similar-head a:hover { gap: 8px; }
.pp-similar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}
.pp-mini {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: var(--bg-card, #151d35);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    text-decoration: none;
    transition: transform 0.2s, border-color 0.2s;
}
.pp-mini:hover {
    transform: translateY(-3px);
    border-color: rgba(139, 92, 246, 0.35);
}
.pp-mini img {
    width: 52px; height: 52px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.pp-mini .info { min-width: 0; flex: 1; }
.pp-mini .name { font-size: 14px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pp-mini .headline { font-size: 12px; color: var(--text-muted, #94a3b8); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 2px 0 4px; }
.pp-mini .rating { font-size: 12px; color: #ffb648; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.pp-mini .rating .count { color: var(--text-muted, #94a3b8); font-weight: 500; }

/* ── Mobile sticky CTA bar ─────────────────────────────────── */
.pp-sticky-cta {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
    background: rgba(11, 15, 26, 0.94);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-top: 1px solid rgba(255,255,255,0.08);
    display: none;
    gap: 10px;
    z-index: 500;
    box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.4);
}
.pp-sticky-cta .pp-btn { flex: 1; justify-content: center; padding: 13px; font-size: 14px; }
.pp-sticky-cta .pp-btn.secondary-sm {
    flex: 0 0 auto;
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);
    padding: 13px;
}
@media (max-width: 720px) { .pp-sticky-cta { display: flex; } }

</style>
@endpush

@section('content')
@php
    $topRated = $pro->isTopRated();
    $primaryHref = auth()->check() ? route('client.chat.index', ['to' => $pro->id]) : route('login');
@endphp

<div class="pp-page">

    {{-- ── Breadcrumb ──────────────────────────────────────── --}}
    <nav class="pp-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}">Home</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <a href="{{ route('public.browse') }}">Browse Professionals</a>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="current">{{ $pro->name }}</span>
    </nav>

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="pp-hero">
        <div class="pp-hero-cover"
             @if($pro->cover_image_url) style="background-image: url('{{ $pro->cover_image_url }}');" @endif>
            <div class="pp-hero-actions-top">
                <button type="button" class="pp-icon-btn" aria-label="Save" title="Save" onclick="this.classList.toggle('saved')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                </button>
                <button type="button" class="pp-icon-btn" aria-label="Share" title="Share"
                        onclick="if(navigator.share){navigator.share({title:'{{ addslashes($pro->name) }}',url:location.href})}else{navigator.clipboard.writeText(location.href);this.setAttribute('title','Link copied')}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </button>
            </div>
        </div>
        <div class="pp-hero-body">
            <img src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}" class="pp-hero-avatar">
            <div class="pp-hero-meta">
                <div class="pp-hero-name">
                    {{ $pro->name }}
                    @if($isFullyVerified)
                        <span class="verified-check" title="Verified credentials">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    @endif
                </div>
                @if($profile->headline)
                    <div class="pp-hero-headline">{{ $profile->headline }}</div>
                @endif
                <div class="pp-hero-tags">
                    @if($stats['count'] > 0)
                        <span class="pp-tag rating">
                            <span class="star">★</span>
                            {{ number_format($stats['average'], 1) }} <span style="opacity: 0.6; font-weight: 500;">({{ $stats['count'] }} {{ \Illuminate\Support\Str::plural('review', $stats['count']) }})</span>
                        </span>
                    @endif
                    @if($profile->city || $profile->country)
                        <span class="pp-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ trim(collect([$profile->city, $profile->country])->filter()->implode(', ')) }}
                        </span>
                    @endif
                    @if($profile->hourly_rate)
                        <span class="pp-tag">From ${{ number_format($profile->hourly_rate, 0) }}/hr</span>
                    @endif
                    @if($profile->experience_years)
                        <span class="pp-tag">{{ $profile->experience_years }}+ yrs experience</span>
                    @endif
                    @if($topRated)
                        <span class="pp-tag top">★ Top Rated Pro</span>
                    @endif
                </div>
            </div>
            <div class="pp-hero-cta">
                <a href="{{ $primaryHref }}" class="pp-btn pp-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Request a Quote
                </a>
                <a href="{{ $primaryHref }}" class="pp-btn pp-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Message
                </a>
                <a href="#reviews" class="pp-btn pp-btn-outline">See reviews</a>
            </div>
        </div>

        {{-- Trust strip --}}
        <div class="pp-trust-strip">
            <div class="pp-trust-item">
                <div class="pp-trust-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="pp-trust-text">
                    <div class="pp-trust-label">Responds</div>
                    <div class="pp-trust-value">{{ $responseSignals['response_time'] }}</div>
                </div>
            </div>
            <div class="pp-trust-item">
                <div class="pp-trust-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="pp-trust-text">
                    <div class="pp-trust-label">Reply rate</div>
                    <div class="pp-trust-value {{ $responseSignals['reply_rate'] !== '—' ? 'accent' : '' }}">{{ $responseSignals['reply_rate'] }}</div>
                </div>
            </div>
            <div class="pp-trust-item">
                <div class="pp-trust-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="pp-trust-text">
                    <div class="pp-trust-label">Member since</div>
                    <div class="pp-trust-value">{{ $responseSignals['member_since'] }}</div>
                </div>
            </div>
            <div class="pp-trust-item">
                <div class="pp-trust-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div class="pp-trust-text">
                    <div class="pp-trust-label">Rating</div>
                    <div class="pp-trust-value">{{ $stats['count'] > 0 ? number_format($stats['average'], 1) . ' ★' : 'New pro' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main grid ────────────────────────────────────────── --}}
    <div class="pp-grid">
        {{-- Left column --}}
        <div>
            @if($profile->bio)
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        About
                    </h3>
                    <div class="pp-card-body" style="white-space: pre-line;">{{ $profile->bio }}</div>
                </div>
            @endif

            @if(!empty($profile->skills))
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Skills &amp; Specialties
                    </h3>
                    <div class="pp-chips">
                        @foreach((array) $profile->skills as $skill)
                            <span class="pp-chip">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($profile->portfolio))
                <div class="pp-card">
                    <h3 class="pp-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Portfolio
                        <span style="margin-left:auto;font-size:12px;color:#64748b;font-weight:500;">{{ count((array) $profile->portfolio) }} {{ \Illuminate\Support\Str::plural('project', count((array) $profile->portfolio)) }}</span>
                    </h3>
                    <div class="pp-gallery">
                        @foreach((array) $profile->portfolio as $item)
                            @php
                                $url = $item['url'] ?? null;
                                $img = $item['image'] ?? null;
                                // Treat URL as image if it ends with a common image extension.
                                if (!$img && $url && preg_match('/\.(jpe?g|png|webp|gif|avif)(\?.*)?$/i', $url)) {
                                    $img = $url;
                                }
                            @endphp
                            @if($img)
                                <a href="{{ $url ?: $img }}" target="_blank" rel="noopener" class="pp-gallery-item">
                                    <img src="{{ $img }}" alt="{{ $item['title'] ?? '' }}" loading="lazy">
                                    @if(!empty($item['title']))
                                        <div class="pp-gallery-caption">
                                            {{ $item['title'] }}
                                            @if(!empty($item['description']))
                                                <small>{{ \Illuminate\Support\Str::limit($item['description'], 40) }}</small>
                                            @endif
                                        </div>
                                    @endif
                                </a>
                            @else
                                <div class="pp-gallery-item no-image">
                                    <div class="title">{{ $item['title'] ?? 'Project' }}</div>
                                    @if(!empty($item['description']))
                                        <div class="desc">{{ $item['description'] }}</div>
                                    @endif
                                    @if($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="link">
                                            View project
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Reviews feed --}}
            <div class="pp-card" id="reviews">
                <h3 class="pp-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Recent Reviews
                    <span style="margin-left:auto;font-size:12px;color:#64748b;font-weight:500;">
                        {{ $stats['count'] }} total
                    </span>
                </h3>
                @forelse($reviews as $r)
                    <div class="pp-review">
                        <img src="{{ $r->reviewer?->avatar_url ?? 'https://ui-avatars.com/api/?name=?&size=88&background=cbd5e1&color=64748b' }}"
                             alt="{{ $r->reviewer?->name ?? 'Former client' }}"
                             class="pp-review-avatar">
                        <div class="pp-review-body">
                            <div class="pp-review-head">
                                <span class="pp-review-name">{{ $r->reviewer?->name ?? 'Former client' }}</span>
                                <span class="pp-review-stars" aria-label="{{ $r->rating }} out of 5">
                                    {{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}
                                </span>
                                <span class="pp-review-date">{{ $r->created_at->diffForHumans() }}</span>
                            </div>
                            @if($r->title)
                                <div class="pp-review-title">{{ $r->title }}</div>
                            @endif
                            <div class="pp-review-text">{{ $r->comment }}</div>
                            @if($r->response)
                                <div class="pp-review-response">
                                    <strong>{{ $pro->name }} responded:</strong>
                                    {{ $r->response }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pp-empty-reviews">No reviews yet — be the first to work with {{ $pro->name }}.</div>
                @endforelse
            </div>
        </div>

        {{-- Right STICKY column: Top Rated seal, Satisfaction, Verified --}}
        <div class="pp-sidebar">
            @if($topRated)
                <div class="pp-seal">
                    <div class="pp-seal-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="pp-seal-text">
                        <strong>Top Rated Pro</strong>
                        Verified credentials &amp; 4.5+ rating
                    </div>
                </div>
            @endif

            {{-- Satisfaction histogram card --}}
            <div class="pp-card pp-card-satisfaction">
                <div class="pp-sat-head">Customer Satisfaction Results</div>

                @php
                    $maxCount = max(1, max($stats['histogram']));
                @endphp

                <div class="pp-sat-bars">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        @php
                            $count = $stats['histogram'][$star] ?? 0;
                            $pct = ($count / $maxCount) * 100;
                        @endphp
                        <div class="pp-sat-row">
                            <div class="pp-sat-label">{{ str_repeat('★', $star) }}</div>
                            <div class="pp-sat-bar-track">
                                <div class="pp-sat-bar-fill" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="pp-sat-count">{{ number_format($count) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="pp-sat-overall">
                    <div class="pp-sat-score">
                        Rating:
                        <span class="big">{{ number_format($stats['average'], 1) }}</span>
                        <span class="unit">out of 5</span>
                    </div>
                    <div class="pp-sat-count-total">
                        Based on {{ number_format($stats['count']) }} verified {{ \Illuminate\Support\Str::plural('review', $stats['count']) }}
                    </div>
                </div>

                @if($stats['count'] > 0)
                    <a href="#reviews" class="pp-sat-cta">Read all reviews →</a>
                @endif
            </div>

            {{-- Verified Credentials --}}
            <div class="pp-card pp-card-verified">
                <div class="pp-ver-head">Verified Credentials</div>
                <div class="pp-ver-list">
                    @foreach($badges as $key => $label)
                        @php
                            $status    = $profile->badgeStatus($key);
                            $number    = $profile->{$key . '_number'};
                            $statusTxt = ['verified' => 'Verified', 'pending' => 'Pending review', 'none' => 'Not submitted'][$status];
                        @endphp
                        <div class="pp-ver-item {{ $status }}">
                            <div class="pp-ver-check">
                                @if($status === 'verified') ✓
                                @elseif($status === 'pending') …
                                @else ✕
                                @endif
                            </div>
                            <div class="pp-ver-body">
                                <div class="pp-ver-label">{{ $label }}</div>
                                @if($number && $status === 'verified')
                                    <div class="pp-ver-num">#{{ $number }}</div>
                                @endif
                                <div class="pp-ver-status">{{ $statusTxt }}</div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pp-ver-item {{ $topRated ? '' : 'none' }}">
                        <div class="pp-ver-check">{!! $topRated ? '✓' : '✕' !!}</div>
                        <div class="pp-ver-body">
                            <div class="pp-ver-label">{{ config('app.name') }} Top Rated</div>
                            <div class="pp-ver-status">
                                {{ $topRated ? 'Awarded' : 'Needs 5+ reviews at 4.5★ and full verification' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Similar pros row ─────────────────────────────────── --}}
    @if($similar->isNotEmpty())
        <div class="pp-similar">
            <div class="pp-similar-head">
                <h2>
                    @if($profile->city)
                        Other professionals in {{ $profile->city }}
                    @else
                        Similar professionals
                    @endif
                </h2>
                <a href="{{ route('public.browse', array_filter(['city' => $profile->city])) }}">
                    See all
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
            <div class="pp-similar-grid">
                @foreach($similar as $sp)
                    <a href="{{ route('public.professional.show', $sp) }}" class="pp-mini">
                        <img src="{{ $sp->avatar_url }}" alt="{{ $sp->name }}" loading="lazy">
                        <div class="info">
                            <div class="name">{{ $sp->name }}</div>
                            <div class="headline">{{ $sp->profile?->headline ?? 'Event professional' }}</div>
                            @if(($sp->reviews_count ?? 0) > 0)
                                <span class="rating">★ {{ number_format($sp->reviews_avg, 1) }} <span class="count">({{ $sp->reviews_count }})</span></span>
                            @else
                                <span class="rating" style="color: var(--text-muted);">New</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- ── Mobile sticky CTA bar ───────────────────────────────── --}}
<div class="pp-sticky-cta" aria-label="Quick actions">
    <a href="{{ $primaryHref }}" class="pp-btn pp-btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Request a Quote
    </a>
    <a href="{{ $primaryHref }}" class="pp-btn secondary-sm" aria-label="Message {{ $pro->name }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </a>
</div>
@endsection
