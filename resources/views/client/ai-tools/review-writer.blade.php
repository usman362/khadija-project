@extends('layouts.client')

@section('title', 'AI Review Writer')
@section('page-title', 'AI Review Writer')
@section('page-subtitle', 'Rate your experience and share a few thoughts — AI will craft a polished, helpful review you can post anywhere.')

{{-- AI Review Writer — deterministic, dynamic review generator (no LLM).
     Builds 6 formats in one pass; tabs switch instantly. Page-scoped. --}}

@php
    // Cookie rating icon — golden cookie with scattered chocolate chips.
    $rw_cookie = function ($filled) {
        $base = $filled ? '#e0a458' : '#e5e7eb';
        $chip = $filled ? '#7c3f12' : '#cbd5e1';
        return '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.5" fill="' . $base . '"/>'
            . '<circle cx="8.6" cy="8.4" r="1.5" fill="' . $chip . '"/><circle cx="15.2" cy="8.8" r="1.1" fill="' . $chip . '"/>'
            . '<circle cx="12.2" cy="12.6" r="1.3" fill="' . $chip . '"/><circle cx="7.8" cy="14.8" r="1.2" fill="' . $chip . '"/>'
            . '<circle cx="16" cy="14.4" r="1.4" fill="' . $chip . '"/><circle cx="11.4" cy="16.6" r="0.9" fill="' . $chip . '"/></svg>';
    };
    // 3D-style filled white glyphs (orange cut-outs give depth on the glossy
    // orange badge sphere).
    $rw_badge_ico = function ($ic) {
        return match ($ic) {
            'crown' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.6 4.4 9l4.3 3.8L12 6l3.3 6.8L19.6 9 21 17.6a1 1 0 0 1-1 1.2H4a1 1 0 0 1-1-1.2z"/><rect x="6" y="19.6" width="12" height="1.8" rx="0.9" opacity="0.85"/><circle cx="12" cy="5" r="1.7"/><circle cx="3.6" cy="8" r="1.4"/><circle cx="20.4" cy="8" r="1.4"/></svg>',
            'clock' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="9.5"/><path d="M12.2 6.6a1 1 0 0 1 1 1v3.9l2.5 2.5a1 1 0 1 1-1.4 1.4l-2.8-2.8a1 1 0 0 1-.3-.7V7.6a1 1 0 0 1 1-1z" fill="#e2670d"/></svg>',
            'chat'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 3.5h16a2.5 2.5 0 0 1 2.5 2.5v8A2.5 2.5 0 0 1 20 16.5H10l-5.2 4.1a.6.6 0 0 1-1-.5V16.5A2.5 2.5 0 0 1 1.5 14V6A2.5 2.5 0 0 1 4 3.5z"/><circle cx="8" cy="10" r="1.3" fill="#e2670d"/><circle cx="12" cy="10" r="1.3" fill="#e2670d"/><circle cx="16" cy="10" r="1.3" fill="#e2670d"/></svg>',
            'gem'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 3h14l3.2 5.4L12 21.5 1.8 8.4z"/><path d="M1.8 8.4h20.4M9 3 7 8.4l5 13.1 5-13.1L15 3" stroke="#e2670d" stroke-width="1.1" fill="none" stroke-linejoin="round" opacity="0.8"/></svg>',
            'star'  => '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.1 8.6 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.6 12 2"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 20 5v6.2c0 5-3.5 8.6-8 10.3-4.5-1.7-8-5.3-8-10.3V5z"/><path d="M8.5 12l2.3 2.3L15.7 9.5" stroke="#e2670d" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        };
    };

    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Write your own review by hand — no AI, just your words.'],
        'semi'    => ['Help Me Plan', '#ea580c', 'AI drafts a review — edit the wording before you post it.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter a few thoughts and AI writes the full review for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp

@push('styles')
<style>
    .rw { --rw: #ea580c; --rw-strong: #c2410c; --rw-soft: rgba(234,88,12,0.08); padding-top: 22px; }
    .rw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px 22px; }
    .rw-mb { margin-bottom: 18px; }

    /* hero */
    .rw-hero { display: flex; align-items: center; gap: 18px; background: linear-gradient(135deg, rgba(251,146,60,0.12), rgba(234,88,12,0.06)); border: 1px solid rgba(234,88,12,0.22); border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 18px; }
    .rw-hero-ico { width: 60px; height: 60px; border-radius: 16px; background: linear-gradient(135deg, #fb923c, #ea580c); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 8px 18px rgba(234,88,12,0.35), inset 0 1.5px 0 rgba(255,255,255,0.45); }
    .rw-hero-ico svg { width: 40px; height: 40px; }
    .rw-hero-txt { flex: 1; }
    .rw-hero-txt b { font-size: 19px; font-weight: 800; color: var(--rw-strong); }
    .rw-hero-txt p { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }
    .rw-unlimited { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; color: var(--rw); background: rgba(234,88,12,0.1); border: 1px solid rgba(234,88,12,0.25); border-radius: 999px; padding: 6px 14px; white-space: nowrap; }
    .rw-unlimited svg { width: 14px; height: 14px; }

    /* rating cards */
    .rw-ratings { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
    .rw-rcard { border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; background: var(--bg-card); }
    .rw-rcard .lbl { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .rw-rcard .lbl svg { width: 13px; height: 13px; color: var(--rw); }
    .rw-rcard .val { font-size: 26px; font-weight: 800; color: var(--text-primary); margin: 6px 0 4px; }
    .rw-cookies { display: flex; gap: 2px; }
    .rw-cookies svg { width: 14px; height: 14px; }
    .rw-rcard .tag { font-size: 11px; font-weight: 700; color: #059669; margin-top: 5px; }
    .rw-rcard .tag.vg { color: #d97706; }

    /* main grid */
    .rw-main { display: grid; grid-template-columns: minmax(0,2.2fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .rw-col { display: flex; flex-direction: column; gap: 18px; }
    .rw-23 { display: grid; grid-template-columns: minmax(0,1.55fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .rw-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .rw-sec-h .n { width: 26px; height: 26px; border-radius: 8px; background: var(--rw-soft); color: var(--rw); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0; }
    .rw-sec-h b { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .rw-sec-sub { font-size: 12px; color: var(--text-muted); margin: 0 0 16px; padding-left: 35px; }

    /* form */
    .rw-form-grid { display: grid; grid-template-columns: minmax(0,1.5fr) minmax(0,1fr); gap: 18px; }
    .rw-fld { margin-bottom: 14px; }
    .rw-fld label { display: block; font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .rw-fld label .opt { color: var(--text-muted); font-weight: 500; }
    .rw-input { width: 100%; box-sizing: border-box; padding: 11px 13px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .rw-input:focus { outline: none; border-color: var(--rw); }
    .rw-2col { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
    .rw-cookie-input { display: flex; gap: 4px; cursor: pointer; }
    .rw-cookie-input svg { width: 28px; height: 28px; }
    .rw-cookie-lbl { font-size: 12px; font-weight: 700; color: #059669; margin-top: 4px; }
    .rw-tones { display: flex; gap: 8px; flex-wrap: wrap; }
    .rw-tone { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-secondary); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .rw-tone.on { background: var(--rw); border-color: var(--rw); color: #fff; }
    .rw-tone svg { width: 14px; height: 14px; }
    .rw-textarea { width: 100%; box-sizing: border-box; min-height: 70px; padding: 11px 13px; border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; resize: vertical; outline: none; }
    .rw-textarea:focus { border-color: var(--rw); }
    .rw-gen-btn { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; padding: 12px 22px; border: none; border-radius: 10px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .rw-gen-btn svg { width: 15px; height: 15px; }

    /* tips + keywords (right of form) */
    .rw-tips { background: rgba(234,88,12,0.04); border: 1px solid rgba(234,88,12,0.15); border-radius: 12px; padding: 15px; }
    .rw-tips-h { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; color: var(--rw-strong); margin-bottom: 12px; }
    .rw-tips-h svg { width: 16px; height: 16px; }
    .rw-tip { display: flex; gap: 8px; font-size: 11.5px; color: var(--text-secondary); padding: 4px 0; line-height: 1.4; }
    .rw-tip svg { width: 13px; height: 13px; color: var(--rw); flex-shrink: 0; margin-top: 2px; }
    .rw-kw-h { font-size: 12px; font-weight: 800; color: var(--text-primary); margin: 14px 0 9px; }
    .rw-kws { display: flex; flex-wrap: wrap; gap: 7px; }
    .rw-kw { font-size: 11.5px; font-weight: 700; color: var(--rw); background: rgba(234,88,12,0.08); border: 1px solid rgba(234,88,12,0.2); border-radius: 999px; padding: 5px 11px; cursor: pointer; }
    .rw-kw:hover { background: rgba(234,88,12,0.16); }

    /* generated review */
    .rw-gr-h { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
    .rw-gr-h-l { display: flex; align-items: center; gap: 10px; }
    .rw-tone-badge { font-size: 11px; font-weight: 800; color: var(--rw); background: var(--rw-soft); border-radius: 6px; padding: 3px 10px; }
    .rw-gr-actions { display: flex; gap: 8px; }
    .rw-mini-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-secondary); font-size: 12px; font-weight: 700; cursor: pointer; font-family: inherit; }
    .rw-mini-btn svg { width: 13px; height: 13px; }
    .rw-tabs { display: flex; gap: 4px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); margin-bottom: 14px; }
    .rw-tab { padding: 9px 13px; font-size: 12px; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .rw-tab.on { color: var(--rw); border-bottom-color: var(--rw); }
    .rw-review-text { font-size: 13.5px; color: var(--text-primary); line-height: 1.7; white-space: pre-wrap; word-break: break-word; overflow-wrap: break-word; min-height: 120px; padding: 4px 0; }
    .rw-gr-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .rw-wc { font-size: 12px; color: var(--text-muted); }
    .rw-use-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 9px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; text-decoration: none; }
    .rw-use-btn svg { width: 14px; height: 14px; }

    /* upload */
    .rw-drop { border: 2px dashed var(--border-color); border-radius: 12px; padding: 26px; text-align: center; color: var(--text-muted); font-size: 12.5px; }
    .rw-drop svg { width: 30px; height: 30px; color: var(--rw); margin-bottom: 8px; }
    .rw-thumbs { display: flex; gap: 9px; margin-top: 12px; }
    .rw-thumb { width: 64px; height: 50px; border-radius: 8px; flex-shrink: 0; border: 1px solid var(--border-color); }
    .rw-thumb.add { display: flex; align-items: center; justify-content: center; color: var(--text-muted); cursor: pointer; }
    .rw-thumb.add svg { width: 18px; height: 18px; }

    /* sidebar */
    .rw-side-h { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
    .rw-side-h svg { width: 17px; height: 17px; color: var(--rw); }
    .rw-side-h b { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .rw-rep { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
    .rw-ring { width: 76px; height: 76px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; position: relative; background: var(--bg-card-hover); }
    .rw-check input:focus-visible + .box { outline: 2px solid var(--rw); outline-offset: 2px; }
    .rw-ring::before { content: ''; position: absolute; inset: 7px; border-radius: 50%; background: var(--bg-card); }
    .rw-ring b { position: relative; font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .rw-rep-info b { font-size: 16px; font-weight: 800; color: #059669; }
    .rw-rep-info p { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0; }
    .rw-bar-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
    .rw-bar-row .k { font-size: 12px; color: var(--text-secondary); flex: 0 0 96px; display: flex; align-items: center; gap: 6px; }
    .rw-bar-row .k svg { width: 13px; height: 13px; color: var(--rw); }
    .rw-bar { flex: 1; height: 7px; border-radius: 4px; background: var(--bg-card-hover); overflow: hidden; }
    .rw-bar > i { display: block; height: 100%; background: linear-gradient(90deg, #fb923c, #ea580c); }
    .rw-bar-row .v { font-size: 12px; font-weight: 800; color: var(--text-primary); flex: 0 0 34px; text-align: right; }
    .rw-link { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 800; color: var(--rw); text-decoration: none; margin-top: 10px; }
    .rw-link svg { width: 13px; height: 13px; }
    .rw-badges { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px 12px; align-items: start; }
    .rw-badge { display: flex; flex-direction: column; align-items: center; }
    .rw-badge-ic { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #fdba74, #ea580c); display: flex; align-items: center; justify-content: center; color: #fff; margin: 0 auto 9px; box-shadow: 0 5px 12px rgba(234,88,12,0.32), inset 0 1.5px 0 rgba(255,255,255,0.5); }
    .rw-badge-ic svg { width: 23px; height: 23px; }
    .rw-badge span { font-size: 11px; font-weight: 700; color: var(--text-secondary); line-height: 1.3; text-align: center; min-height: 2.6em; }

    .rw-ev-row { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: var(--text-secondary); padding: 6px 0; }
    .rw-ev-row svg { width: 15px; height: 15px; color: var(--rw); flex-shrink: 0; }
    .rw-ev-row b { color: var(--text-primary); font-weight: 700; }
    .rw-check { display: flex; align-items: center; gap: 10px; padding: 8px 0; cursor: pointer; }
    .rw-check input { display: none; }
    .rw-check .box { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rw-check input:checked + .box { background: var(--rw); border-color: var(--rw); }
    .rw-check .box svg { display: none; width: 12px; height: 12px; color: #fff; }
    .rw-check input:checked + .box svg { display: block; }
    .rw-check span { font-size: 12.5px; color: var(--text-secondary); }
    .rw-pub-btns { display: flex; gap: 9px; margin-top: 14px; }
    .rw-draft-btn { flex: 1; padding: 11px; border: 1px solid var(--rw); border-radius: 9px; background: var(--bg-card); color: var(--rw); font-size: 12.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .rw-pub-btn { flex: 1; padding: 11px; border: none; border-radius: 9px; background: linear-gradient(135deg, #fb923c, #ea580c); color: #fff; font-size: 12.5px; font-weight: 800; cursor: pointer; font-family: inherit; }

    /* bottom row */
    .rw-bottom { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 18px; margin-top: 18px; }
    .rw-cl-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border-color); }
    .rw-cl-row:first-of-type { border-top: none; }
    .rw-cl-row .l { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-secondary); }
    .rw-cl-row .l svg { width: 15px; height: 15px; color: #10b981; }
    .rw-cl-row .y { font-size: 12px; font-weight: 800; color: #059669; }
    .rw-platforms { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; }
    .rw-plat { text-align: center; border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 6px; cursor: pointer; }
    .rw-plat-ic { width: 30px; height: 30px; border-radius: 8px; background: var(--rw-soft); display: flex; align-items: center; justify-content: center; color: var(--rw); margin: 0 auto 5px; font-size: 13px; font-weight: 800; }
    .rw-plat span { font-size: 10px; color: var(--text-muted); }

    @media (max-width: 1200px) { .rw-main { grid-template-columns: 1fr; } .rw-ratings { grid-template-columns: repeat(3, minmax(0,1fr)); } .rw-bottom { grid-template-columns: 1fr; } }
    @media (max-width: 900px) { .rw-23 { grid-template-columns: 1fr; } }
    @media (max-width: 760px) { .rw-form-grid, .rw-2col { grid-template-columns: 1fr; } .rw-ratings { grid-template-columns: repeat(2, minmax(0,1fr)); } .rw-badges { grid-template-columns: repeat(2,1fr); } }
</style>
@endpush

@push('styles')
<style>
    .rw-review-text[contenteditable="true"] { outline: none; cursor: text; }
    .rw-review-text[contenteditable="true"]:focus { box-shadow: inset 0 0 0 2px rgba(234,88,12,.35); border-radius: 8px; }
    .rw-review-text[data-placeholder]:empty:before { content: attr(data-placeholder); color: var(--text-muted); }
</style>
@endpush

@section('content')
<div class="rw" data-compose-url="{{ route('ai-tools.review-writer.compose') }}" data-level="{{ $level }}">

    @include('partials._ai_quota_badge', ['status' => $status, 'tool' => 'AI Review Writer'])

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--rw,#ea580c);text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    {{-- hero --}}
    <div class="rw-hero">
        <span class="rw-hero-ico">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="rwDoc" x1="12" y1="6" x2="32" y2="40"><stop stop-color="#fff"/><stop offset="1" stop-color="#fff7ed"/></linearGradient></defs>
                <ellipse cx="23" cy="43" rx="13" ry="2.4" fill="#7c2d12" opacity="0.3"/>
                <rect x="12.6" y="7.4" width="21" height="33" rx="4.5" fill="#9a3412"/>
                <rect x="11" y="5.5" width="21" height="33" rx="4.5" fill="url(#rwDoc)"/>
                <rect x="11" y="5.5" width="21" height="9" rx="4.5" fill="#fff" opacity="0.5"/>
                <rect x="15" y="15" width="13" height="2" rx="1" fill="#fb923c"/>
                <rect x="15" y="20" width="13" height="2" rx="1" fill="#fdba74"/>
                <rect x="15" y="25" width="9" height="2" rx="1" fill="#fed7aa"/>
                <g transform="rotate(50 31 22)">
                    <rect x="28.5" y="6.5" width="5" height="20" rx="0.8" fill="#ea580c"/>
                    <rect x="28.5" y="6.5" width="2" height="20" fill="#fdba74"/>
                    <polygon points="28.5,26.5 33.5,26.5 31,32.5" fill="#fcd34d"/>
                    <polygon points="29.6,30 32.4,30 31,32.5" fill="#1f2937"/>
                    <rect x="28.3" y="3.4" width="5.4" height="3.3" rx="1" fill="#cbd5e1"/>
                    <rect x="28.3" y="0.8" width="5.4" height="3.2" rx="1.4" fill="#fda4af"/>
                </g>
            </svg>
        </span>
        <div class="rw-hero-txt"><b>AI Review Writer</b><p>Our AI analyses your experience and helps you write clear, honest, and impactful reviews.</p></div>
        <span class="rw-unlimited"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.74-8z"/></svg>UNLIMITED</span>
    </div>

    {{-- rating cards --}}
    <div class="rw-ratings">
        @foreach($metrics['cards'] as [$label, $score, $tag])
            <div class="rw-rcard">
                <div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>{{ $label }}</div>
                <div class="val">{{ $score }}</div>
                <div class="rw-cookies">@for($i = 1; $i <= 5; $i++){!! $rw_cookie($i <= round($score)) !!}@endfor</div>
                <div class="tag {{ $tag === 'Very Good' ? 'vg' : '' }}">{{ $tag }}</div>
            </div>
        @endforeach
        <div class="rw-rcard">
            <div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>Would Hire Again</div>
            <div class="val" style="color:#059669;">Yes</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">93% probability</div>
            <div class="tag">High likelihood</div>
        </div>
    </div>

    {{-- main --}}
    <div class="rw-main">
        {{-- LEFT --}}
        <div class="rw-col">
            {{-- share experience --}}
            <div class="rw-card">
                <div class="rw-sec-h"><span class="n">1</span><b>Share Your Experience</b></div>
                <p class="rw-sec-sub">{{ $isManual ? 'Add a few details for context, then write your review below.' : ($isSemi ? 'A few keywords are enough — AI drafts a review you can edit.' : 'A few keywords are enough — AI writes a natural, helpful review.') }}</p>
                <div class="rw-form-grid">
                    <div>
                        <div class="rw-2col">
                            <div class="rw-fld"><label>Professional / Service Provider <span style="color:var(--rw);">*</span></label><input class="rw-input" id="rw-provider" value="{{ $defaults['provider'] }}" placeholder="e.g. Sarah Bennett Photography"></div>
                            <div class="rw-fld"><label>Service Type <span class="opt">(optional)</span></label><input class="rw-input" id="rw-service" placeholder="e.g. Wedding Photography"></div>
                        </div>
                        <div class="rw-2col">
                            <div class="rw-fld"><label>Event Type <span class="opt">(optional)</span></label><input class="rw-input" id="rw-event" value="{{ $defaults['event'] }}" placeholder="e.g. My wedding in March"></div>
                            <div class="rw-fld"><label>Your Rating <span style="color:var(--rw);">*</span></label><div class="rw-cookie-input" id="rw-rating">@for($i = 1; $i <= 5; $i++)<span data-v="{{ $i }}">{!! $rw_cookie($i <= 5) !!}</span>@endfor</div><div class="rw-cookie-lbl" id="rw-rating-lbl">Excellent</div></div>
                        </div>
                        <div class="rw-fld">
                            <label>Preferred Tone <span style="color:var(--rw);">*</span></label>
                            <div class="rw-tones">
                                <button type="button" class="rw-tone" data-tone="friendly"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>Friendly &amp; Warm</button>
                                <button type="button" class="rw-tone on" data-tone="balanced"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 7l9-4 9 4M5 7v6a7 3 0 0 0 14 0V7"/></svg>Balanced</button>
                                <button type="button" class="rw-tone" data-tone="professional"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>Professional</button>
                            </div>
                        </div>
                        <div class="rw-fld"><label>Your Quick Thoughts <span style="color:var(--rw);">*</span> <span class="opt">(bullet points, keywords, or full sentences — anything works)</span></label><textarea class="rw-textarea" id="rw-thoughts" placeholder="e.g. On time, great energy, captured amazing candid shots, professional team, delivered edits in 2 weeks...">{{ $defaults['thoughts'] }}</textarea></div>
                        @unless($isManual)<button type="button" class="rw-gen-btn" id="rw-generate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.9 4.1L18 8l-4.1 1.9L12 14l-1.9-4.1L6 8l4.1-1.9L12 2z"/></svg>{{ $isSemi ? '✨ Suggest a Review' : '🤖 Write My Review' }}</button>@endunless
                    </div>
                    <div class="rw-tips">
                        <div class="rw-tips-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg>Smart Review Tips</div>
                        <div class="rw-tip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Be specific about what you loved.</div>
                        <div class="rw-tip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Mention key strengths.</div>
                        <div class="rw-tip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Share how the vendor impacted your event.</div>
                        <div class="rw-tip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Add details about timeliness or communication.</div>
                        <div class="rw-kw-h">AI Suggested Keywords</div>
                        <div class="rw-kws">@foreach($keywords as $kw)<span class="rw-kw" data-kw="{{ $kw }}">{{ $kw }}</span>@endforeach</div>
                    </div>
                </div>
            </div>

            <div class="rw-23">
            {{-- generated review --}}
            <div class="rw-card">
                <div class="rw-gr-h">
                    <div class="rw-gr-h-l"><span class="rw-sec-h" style="margin:0;"><span class="n">2</span><b>{{ $isManual ? 'Your Review' : ($isMax ? 'AI-Written Review' : 'AI-Drafted Review') }}</b></span>@unless($isManual)<span class="rw-tone-badge" id="rw-tone-badge">{{ $review['toneLabel'] }}</span>@endunless</div>
                    @unless($isManual)
                    <div class="rw-gr-actions">
                        <button type="button" class="rw-mini-btn" id="rw-rewrite"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Rewrite</button>
                        <button type="button" class="rw-mini-btn" id="rw-regen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>Regenerate</button>
                    </div>
                    @endunless
                </div>
                @unless($isManual)
                <div class="rw-tabs" id="rw-tabs">
                    <span class="rw-tab" data-fmt="short">Short Review</span>
                    <span class="rw-tab on" data-fmt="detailed">Detailed Review</span>
                    <span class="rw-tab" data-fmt="social">Social Media</span>
                    <span class="rw-tab" data-fmt="google">Google Review</span>
                    <span class="rw-tab" data-fmt="linkedin">LinkedIn</span>
                    <span class="rw-tab" data-fmt="custom">Custom</span>
                </div>
                @endunless
                <div class="rw-review-text" id="rw-review-text" @unless($isMax)contenteditable="true" spellcheck="true"@endunless @if($isManual)data-placeholder="Write your review here…" style="min-height:150px;"@endif>{{ $isManual ? '' : $review['formats']['detailed'] }}</div>
                <div class="rw-gr-foot">
                    <span class="rw-wc">Word Count: <b id="rw-wc">{{ $review['words']['detailed'] }}</b></span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="rw-mini-btn" id="rw-copy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Copy</button>
                        <button type="button" class="rw-mini-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>Save</button>
                    </div>
                </div>
                <div class="rw-gr-foot" style="margin-top:8px;">
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="rw-mini-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6"/></svg>Translate</button>
                        <button type="button" class="rw-mini-btn" id="rw-adjust"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="6" x2="3" y2="6"/><line x1="15" y1="12" x2="3" y2="12"/><line x1="17" y1="18" x2="3" y2="18"/></svg>Adjust Length</button>
                    </div>
                    <a href="{{ route('client.reviews.index') }}" class="rw-use-btn">Use This Review <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>

            {{-- add photos --}}
            <div class="rw-card">
                <div class="rw-sec-h"><span class="n">3</span><b>Add Photos or Videos <span class="opt" style="font-weight:500;color:var(--text-muted);font-size:12px;">(optional)</span></b></div>
                <p class="rw-sec-sub">Help others see the amazing results!</p>
                <div class="rw-drop">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <div><b style="color:var(--text-secondary);">Drag &amp; drop files here</b> or click to upload</div>
                    <div style="font-size:11px;margin-top:3px;">JPG, PNG, MP4 up to 50MB</div>
                </div>
                <div class="rw-thumbs">
                    <div class="rw-thumb" style="background:linear-gradient(135deg,#fdba74,#ea580c);"></div>
                    <div class="rw-thumb" style="background:linear-gradient(135deg,#fcd34d,#d97706);"></div>
                    <div class="rw-thumb" style="background:linear-gradient(135deg,#fb923c,#c2410c);"></div>
                    <div class="rw-thumb add"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
                </div>
            </div>
            </div>
        </div>

        {{-- RIGHT sidebar --}}
        <div class="rw-col">
            <div class="rw-card">
                <div class="rw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6 4.5 2.3 7.1L12 16.8 5.7 21l2.3-7.1-6-4.5h7.6z"/></svg><b>Reputation Insights</b></div>
                <div class="rw-rep">
                    <span class="rw-ring" style="background:conic-gradient(#ea580c {{ $metrics['reputation']['score'] / 5 * 100 }}%, var(--bg-card-hover) 0);"><b>{{ $metrics['reputation']['score'] }}</b></span>
                    <div class="rw-rep-info"><b>Excellent</b><p>Based on {{ $metrics['reputation']['count'] }} reviews</p><p>{{ $metrics['reputation']['rank'] }}</p></div>
                </div>
                @foreach($metrics['reputation']['bars'] as [$k, $v])
                    <div class="rw-bar-row"><span class="k">{{ $k }}</span><span class="rw-bar"><i style="width:{{ $v }}%;"></i></span><span class="v">{{ $v }}%</span></div>
                @endforeach
                <a href="{{ route('client.reviews.index') }}" class="rw-link">View full reputation <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>

            <div class="rw-card">
                <div class="rw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14z"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/></svg><b>Vendor Badges</b></div>
                <div class="rw-badges">
                    @foreach($metrics['badges'] as [$name, $ic])
                        <div class="rw-badge"><span class="rw-badge-ic">{!! $rw_badge_ico($ic) !!}</span><span>{{ $name }}</span></div>
                    @endforeach
                </div>
                <a href="{{ route('client.reviews.index') }}" class="rw-link" style="display:block;text-align:center;">View all badges →</a>
            </div>

            <div class="rw-card">
                <div class="rw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><b>Event Details</b></div>
                <div class="rw-ev-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg><b>Corporate Gala 2026</b></div>
                <div class="rw-ev-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>New York, NY</div>
                <div class="rw-ev-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Jun 15, 2026</div>
                <div class="rw-ev-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Guest Count <b>200</b></div>
                <a href="{{ route('client.events.index') }}" class="rw-link">Edit Event Details <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>

            <div class="rw-card">
                <div class="rw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><b>Privacy &amp; Publishing</b></div>
                <label class="rw-check"><input type="checkbox" checked><span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span>Make review public</span></label>
                <label class="rw-check"><input type="checkbox" checked><span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span>Show on vendor profile</span></label>
                <label class="rw-check"><input type="checkbox"><span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span>Show on my profile</span></label>
                <label class="rw-check"><input type="checkbox"><span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span><span>Anonymous review</span></label>
                <div class="rw-pub-btns">
                    <button type="button" class="rw-draft-btn">Save as Draft</button>
                    <a href="{{ route('client.reviews.index') }}" class="rw-pub-btn" style="text-align:center;text-decoration:none;line-height:1.6;">Publish Review</a>
                </div>
            </div>
        </div>
    </div>

    {{-- bottom row --}}
    <div class="rw-bottom">
        <div class="rw-card">
            <div class="rw-sec-h"><span class="n">4</span><b>Contract &amp; Service Checklist</b></div>
            <p class="rw-sec-sub">How well did this vendor meet the agreement?</p>
            @foreach($metrics['checklist'] as [$label, $val])
                <div class="rw-cl-row"><span class="l"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $label }}</span><span class="y">{{ $val }}</span></div>
            @endforeach
            <a href="{{ route('client.reviews.index') }}" class="rw-link" style="display:block;text-align:center;">View Full Checklist →</a>
        </div>
        <div class="rw-card">
            <div class="rw-sec-h"><span class="n">5</span><b>Private Notes</b></div>
            <p class="rw-sec-sub">For your reference only</p>
            <textarea class="rw-textarea" id="rw-notes" style="min-height:120px;" placeholder="Great vendor, very reliable. Will book again for next year's gala. Loved how they handled the lighting challenges."></textarea>
            <div style="font-size:11px;color:var(--text-muted);margin:8px 0;">Only you can see this note.</div>
            <button type="button" class="rw-draft-btn" id="rw-save-notes" style="width:100%;">Save Notes</button>
        </div>
        <div class="rw-card">
            <div class="rw-sec-h"><span class="n">6</span><b>Share Review</b></div>
            <p class="rw-sec-sub">Post your review on multiple platforms</p>
            <div class="rw-platforms">
                @foreach($metrics['platforms'] as $p)
                    <div class="rw-plat"><span class="rw-plat-ic">{{ substr($p, 0, 1) }}</span><span>{{ $p }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.rw');
    if (!root) return;
    const LEVEL = root.dataset.level || 'maximum';
    const url = root.dataset.composeUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    let review = @json($review);
    let fmt = 'detailed', rating = 5, tone = 'balanced';
    const RWORDS = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

    function cookieSvg(on) {
        const base = on ? '#e0a458' : '#e5e7eb', chip = on ? '#7c3f12' : '#cbd5e1';
        return '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.5" fill="' + base + '"/>'
            + '<circle cx="8.6" cy="8.4" r="1.5" fill="' + chip + '"/><circle cx="15.2" cy="8.8" r="1.1" fill="' + chip + '"/>'
            + '<circle cx="12.2" cy="12.6" r="1.3" fill="' + chip + '"/><circle cx="7.8" cy="14.8" r="1.2" fill="' + chip + '"/>'
            + '<circle cx="16" cy="14.4" r="1.4" fill="' + chip + '"/><circle cx="11.4" cy="16.6" r="0.9" fill="' + chip + '"/></svg>';
    }
    function paintRating() {
        $('rw-rating').querySelectorAll('span').forEach((s, i) => { s.innerHTML = cookieSvg((i + 1) <= rating); });
        $('rw-rating-lbl').textContent = RWORDS[rating];
    }
    function showFmt(f) {
        fmt = f;
        $('rw-review-text').textContent = review.formats[f];
        $('rw-wc').textContent = review.words[f];
        document.querySelectorAll('.rw-tab').forEach((t) => t.classList.toggle('on', t.dataset.fmt === f));
    }

    $('rw-rating').querySelectorAll('span').forEach((s) => s.addEventListener('click', function () { rating = +this.dataset.v; paintRating(); }));
    document.querySelectorAll('.rw-tone').forEach((b) => b.addEventListener('click', function () {
        document.querySelectorAll('.rw-tone').forEach((x) => x.classList.remove('on'));
        this.classList.add('on'); tone = this.dataset.tone;
    }));
    document.querySelectorAll('.rw-kw').forEach((k) => k.addEventListener('click', function () {
        const ta = $('rw-thoughts'); ta.value = (ta.value ? ta.value.replace(/\s*$/, '') + ', ' : '') + this.dataset.kw;
    }));
    document.querySelectorAll('.rw-tab').forEach((t) => t.addEventListener('click', function () { showFmt(this.dataset.fmt); }));

    async function generate(btn) {
        const o = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ provider: $('rw-provider').value, service: $('rw-service').value, event: $('rw-event').value, rating: rating, tone: tone, thoughts: $('rw-thoughts').value }),
            });
            if (res.ok) { const d = await res.json(); review = d.review; const tb = $('rw-tone-badge'); if (tb) tb.textContent = review.toneLabel; showFmt(fmt); }
        } catch (e) { /* keep last review */ }
        finally { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = o; } }
    }

    $('rw-generate')?.addEventListener('click', function () { generate(this); });
    $('rw-regen')?.addEventListener('click', function () { generate(this); });
    $('rw-rewrite')?.addEventListener('click', function () { generate(this); });
    $('rw-copy')?.addEventListener('click', function () {
        // Copy what's actually in the box (reflects manual writing + semi edits).
        navigator.clipboard?.writeText($('rw-review-text')?.innerText || review.formats[fmt]);
        const o = this.innerHTML; this.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Copied!';
        setTimeout(() => { this.innerHTML = o; }, 1500);
    });
    $('rw-save-notes')?.addEventListener('click', function () { const o = this.textContent; this.textContent = 'Saved!'; setTimeout(() => { this.textContent = o; }, 1400); });

    // Do It Myself starts with a blank box the user fills — don't seed AI text.
    if (LEVEL !== 'manual') showFmt('detailed');
})();
</script>
@endsection
