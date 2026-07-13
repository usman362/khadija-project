@extends($aiLayout ?? 'layouts.client')

@section('title', 'Proposal Builder')
@section('page-title', 'Proposal Builder')
@section('page-subtitle', 'Create winning proposals in seconds with the power of AI.')

{{-- Proposal Builder — deterministic, dynamic proposal generator (no LLM /
     no quota). Parses the event description + tone/focus/length and assembles
     a tailored proposal. Copy / Download work client-side; Send opens the
     client's Messages. All page-scoped — the shared layout is untouched. --}}

@push('styles')
<style>
    .pw { --pw: #db2777; --pw-strong: #be185d; --pw-soft: rgba(236,72,153,0.08); padding-top: 22px; }
    .pw-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px 24px; }

    /* header */
    .pw-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .pw-head-l { display: flex; align-items: center; gap: 16px; }
    .pw-head-ico { width: 62px; height: 62px; border-radius: 18px; background: linear-gradient(135deg, #f472b6, #db2777); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 8px 18px rgba(219,39,119,0.35), inset 0 1.5px 0 rgba(255,255,255,0.4); }
    .pw-head-ico svg { width: 42px; height: 42px; }
    .pw-head-txt h1 { font-size: 28px; font-weight: 800; color: var(--pw-strong); margin: 0; }
    .pw-head-txt p { font-size: 13.5px; color: var(--text-muted); margin: 3px 0 0; }
    .pw-back { display: inline-flex; align-items: center; gap: 8px; padding: 11px 18px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 13.5px; font-weight: 700; color: var(--pw-strong); text-decoration: none; background: var(--bg-card); white-space: nowrap; }
    .pw-back svg { width: 15px; height: 15px; }

    /* main grid */
    .pw-main { display: grid; grid-template-columns: minmax(0,1.7fr) minmax(0,1fr); gap: 20px; align-items: start; }
    .pw-col { display: flex; flex-direction: column; gap: 20px; }
    .pw-sec-num { font-size: 16px; font-weight: 800; color: var(--pw); margin: 0 0 6px; }
    .pw-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; }

    .pw-textarea { width: 100%; box-sizing: border-box; min-height: 120px; padding: 14px 16px; border: 1.5px solid rgba(236,72,153,0.35); border-radius: 12px; background: var(--pw-soft); color: var(--text-primary); font-size: 14px; font-family: inherit; line-height: 1.6; resize: vertical; outline: none; }
    .pw-textarea:focus { border-color: var(--pw); }
    .pw-qf-label { font-size: 12.5px; font-weight: 700; color: var(--text-primary); margin: 16px 0 10px; }
    .pw-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .pw-chip { padding: 8px 16px; border: 1px solid rgba(236,72,153,0.3); border-radius: 999px; background: var(--bg-card); color: var(--pw-strong); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; transition: all .15s; }
    .pw-chip:hover { background: var(--pw-soft); border-color: var(--pw); }
    /* Semi assist bar */
    .pw-assist { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 800; color: var(--pw-strong); background: var(--pw-soft); border: 1px solid rgba(236,72,153,0.3); border-radius: 999px; padding: 6px 13px; cursor: pointer; font-family: inherit; }
    .pw-assist:hover { background: rgba(236,72,153,0.14); }
    .pw-assist[disabled] { opacity: .5; cursor: default; }
    textarea.pw-proposal { border: 1.5px solid rgba(236,72,153,0.35); border-radius: 12px; padding: 14px 16px; background: var(--bg-card); outline: none; }
    textarea.pw-proposal:focus { border-color: var(--pw); }

    /* generated proposal */
    .pw-gen { background: linear-gradient(135deg, rgba(236,72,153,0.06), rgba(219,39,119,0.03)); border: 1px solid rgba(236,72,153,0.2); border-radius: var(--radius-lg); padding: 22px 24px; }
    .pw-proposal { font-size: 14.5px; color: var(--text-primary); line-height: 1.7; white-space: pre-wrap; word-break: break-word; overflow-wrap: break-word; min-height: 150px; }
    .pw-gen-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .pw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; border-radius: 10px; font-size: 13px; font-weight: 800; cursor: pointer; font-family: inherit; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; }
    .pw-btn svg { width: 15px; height: 15px; }
    .pw-btn-pink { background: linear-gradient(135deg, #ec4899, #db2777); color: #fff; border: none; }
    .pw-btn-pink:hover { filter: brightness(1.05); }
    .pw-gen-sep { border: none; border-top: 1px solid rgba(236,72,153,0.18); margin: 18px 0; }
    .pw-gen-send { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .pw-gen-send p { font-size: 13px; color: var(--text-secondary); margin: 0; }

    /* right sidebar cards */
    .pw-side-h { display: flex; align-items: center; gap: 9px; margin-bottom: 16px; }
    .pw-side-h svg { width: 18px; height: 18px; color: var(--pw); }
    .pw-side-h b { font-size: 15px; font-weight: 800; color: var(--pw-strong); }
    .pw-why-row { display: flex; gap: 12px; padding: 9px 0; }
    .pw-why-ico { width: 34px; height: 34px; border-radius: 10px; background: var(--pw-soft); display: flex; align-items: center; justify-content: center; color: var(--pw); flex-shrink: 0; }
    .pw-why-ico svg { width: 17px; height: 17px; }
    .pw-why-row b { font-size: 13px; font-weight: 800; color: var(--text-primary); display: block; }
    .pw-why-row p { font-size: 11.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }

    .pw-field { margin-bottom: 14px; }
    .pw-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 7px; }
    .pw-select-wrap { position: relative; }
    .pw-select { width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; font-family: inherit; appearance: none; -webkit-appearance: none; cursor: pointer; }
    .pw-select:focus { outline: none; border-color: var(--pw); }
    .pw-select-wrap .chev { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .pw-regen { width: 100%; margin-top: 4px; }

    .pw-tip-row { display: flex; align-items: flex-start; gap: 10px; padding: 7px 0; font-size: 12.5px; color: var(--text-secondary); line-height: 1.45; }
    .pw-tip-row svg { width: 15px; height: 15px; color: var(--pw); flex-shrink: 0; margin-top: 2px; }

    /* how it works */
    .pw-hiw-h { font-size: 17px; font-weight: 800; color: var(--pw-strong); margin: 0 0 18px; }
    .pw-steps { display: flex; align-items: flex-start; justify-content: space-between; gap: 2px; }
    .pw-step { text-align: center; flex: 1; min-width: 0; }
    .pw-step-ico { width: 56px; height: 56px; border-radius: 16px; background: var(--pw-soft); display: flex; align-items: center; justify-content: center; color: var(--pw); margin: 0 auto 10px; }
    .pw-step-ico svg { width: 26px; height: 26px; }
    .pw-step b { font-size: 12px; font-weight: 800; color: var(--pw-strong); display: block; line-height: 1.3; }
    .pw-step p { font-size: 11px; color: var(--text-muted); margin: 6px 0 0; line-height: 1.45; }
    .pw-step-arr { display: flex; align-items: center; padding-top: 20px; color: rgba(236,72,153,0.5); flex-shrink: 0; }
    .pw-step-arr svg { width: 16px; height: 16px; }

    /* powerful features */
    .pw-feat-h { font-size: 16px; font-weight: 800; color: var(--pw-strong); margin: 0 0 18px; }
    .pw-feats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 18px; }
    .pw-feat { display: flex; flex-direction: column; gap: 8px; }
    .pw-feat-ico { width: 38px; height: 38px; border-radius: 11px; background: var(--pw-soft); display: flex; align-items: center; justify-content: center; color: var(--pw); }
    .pw-feat-ico svg { width: 19px; height: 19px; }
    .pw-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pw-feat p { font-size: 11px; color: var(--text-muted); margin: 0; line-height: 1.45; }

    /* bottom banner */
    .pw-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(236,72,153,0.09), rgba(219,39,119,0.05)); border: 1px solid rgba(236,72,153,0.2); border-radius: var(--radius-lg); padding: 18px 22px; }
    .pw-banner-ico { width: 46px; height: 46px; border-radius: 12px; background: rgba(236,72,153,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pw-banner-ico svg { width: 24px; height: 24px; color: var(--pw); }
    .pw-banner-txt { flex: 1; min-width: 230px; }
    .pw-banner-txt b { font-size: 16px; color: var(--pw-strong); }
    .pw-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .pw-banner a { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, #ec4899, #db2777); color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; text-decoration: none; white-space: nowrap; cursor: pointer; }
    .pw-banner a svg { width: 16px; height: 16px; }

    .pw-mb { margin-bottom: 20px; }
    @media (max-width: 1100px) { .pw-main { grid-template-columns: 1fr; } .pw-feats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .pw-steps { flex-wrap: wrap; } .pw-step { flex-basis: 45%; } .pw-step-arr { display: none; } .pw-feats { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Write your proposal yourself — templates & structure, no AI.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'Write a draft, then let AI improve, rewrite or expand it — you approve.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Describe the event and AI writes the whole proposal for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="pw" data-level="{{ $level }}" data-gen-url="{{ route('ai-tools.proposal-writer.generate') }}" data-examples='@json($examples)'>

    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:18px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--pw);text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    {{-- header --}}
    <div class="pw-head">
        <div class="pw-head-l">
            <span class="pw-head-ico">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs><linearGradient id="pwDoc" x1="12" y1="6" x2="32" y2="40"><stop stop-color="#ffffff"/><stop offset="1" stop-color="#ffe4f1"/></linearGradient></defs>
                    {{-- shadow + 3D paper with depth edge --}}
                    <ellipse cx="23" cy="43" rx="13" ry="2.4" fill="#9d174d" opacity="0.30"/>
                    <rect x="12.6" y="7.4" width="21" height="33" rx="4.5" fill="#9d174d"/>
                    <rect x="11" y="5.5" width="21" height="33" rx="4.5" fill="url(#pwDoc)"/>
                    <rect x="11" y="5.5" width="21" height="9" rx="4.5" fill="#ffffff" opacity="0.5"/>
                    {{-- text lines --}}
                    <rect x="15" y="15" width="13" height="2" rx="1" fill="#f472b6"/>
                    <rect x="15" y="20" width="13" height="2" rx="1" fill="#f9a8d4"/>
                    <rect x="15" y="25" width="9" height="2" rx="1" fill="#fbcfe8"/>
                    <rect x="15" y="30" width="11" height="2" rx="1" fill="#fbcfe8"/>
                    {{-- 3D pencil writing across the paper --}}
                    <g transform="rotate(50 31 22)">
                        <rect x="28.5" y="6.5" width="5" height="20" rx="0.8" fill="#ec4899"/>
                        <rect x="28.5" y="6.5" width="2" height="20" fill="#f9a8d4"/>
                        <polygon points="28.5,26.5 33.5,26.5 31,32.5" fill="#fcd34d"/>
                        <polygon points="29.6,30 32.4,30 31,32.5" fill="#1f2937"/>
                        <rect x="28.3" y="3.4" width="5.4" height="3.3" rx="1" fill="#cbd5e1"/>
                        <rect x="28.3" y="0.8" width="5.4" height="3.2" rx="1.4" fill="#fda4af"/>
                    </g>
                </svg>
            </span>
            <div class="pw-head-txt"><h1>Proposal Builder</h1><p>Create winning proposals in seconds with the power of AI.</p></div>
        </div>
        <a href="{{ route('ai-tools.budget-allocator') }}" class="pw-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Back to AI Toolkit</a>
    </div>

    {{-- main --}}
    <div class="pw-main pw-mb">
        {{-- LEFT --}}
        <div class="pw-col">
            @if($isMax)
            <div class="pw-card">
                <div class="pw-sec-num">1. Client Event Description</div>
                <p class="pw-sec-sub">Paste or write the client's event details.</p>
                <textarea class="pw-textarea" id="pw-desc" rows="4">{{ $description }}</textarea>
                <div class="pw-qf-label">Quick Fill Examples</div>
                <div class="pw-chips">
                    @foreach($examples as $label => $text)
                        <button type="button" class="pw-chip" data-key="{{ $label }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="pw-gen">
                <div class="pw-sec-num">{{ $isManual ? 'Write Your Proposal' : ($isSemi ? 'Your Draft — refine with AI' : '2. Your AI Generated Proposal') }}</div>
                @if($isSemi || $isMax)
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:12px;">
                    <button type="button" class="pw-assist" data-assist="improve">✨ Improve</button>
                    <button type="button" class="pw-assist" data-assist="rewrite">✨ Rewrite</button>
                    <button type="button" class="pw-assist" data-assist="expand">✨ Expand</button>
                    @if($isSemi)
                        <select class="pw-select" id="pw-tone" style="max-width:190px;margin-left:auto;">@foreach($tones as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                    @endif
                </div>
                @endif
                <textarea class="pw-proposal" id="pw-proposal" rows="9" placeholder="{{ $isManual ? 'Write your proposal here…' : 'Your proposal will appear here — edit freely.' }}">{{ $proposal }}</textarea>
                <div class="pw-gen-actions">
                    <button type="button" class="pw-btn pw-btn-pink" id="pw-copy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Copy Proposal</button>
                    <button type="button" class="pw-btn" id="pw-download"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Download</button>
                </div>
                <hr class="pw-gen-sep">
                <div class="pw-gen-send">
                    <p>Happy with your proposal? Send it to the client and stand out!</p>
                    <a class="pw-btn pw-btn-pink" id="pw-send" href="{{ route('client.chat.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send Proposal</a>
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="pw-col">
            <div class="pw-card">
                <div class="pw-side-h"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><b>Why Use Proposal Builder?</b></div>
                <div class="pw-why-row"><span class="pw-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>Save Time</b><p>Generate professional proposals in seconds.</p></div></div>
                <div class="pw-why-row"><span class="pw-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><div><b>Personalized</b><p>AI customizes your message to match the client's event theme.</p></div></div>
                <div class="pw-why-row"><span class="pw-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><div><b>Win More Gigs</b><p>Stand out with well-written, tailored proposals every time.</p></div></div>
                <div class="pw-why-row"><span class="pw-why-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span><div><b>Error-Free</b><p>Polished, professional, and ready to send.</p></div></div>
            </div>

            @if($isMax)
            <div class="pw-card">
                <div class="pw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg><b>Customize Your Proposal</b></div>
                <div class="pw-field">
                    <label>Tone</label>
                    <div class="pw-select-wrap">
                        <select class="pw-select" id="pw-tone">@foreach($tones as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
                <div class="pw-field">
                    <label>Focus</label>
                    <div class="pw-select-wrap">
                        <select class="pw-select" id="pw-focus">@foreach($focuses as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
                <div class="pw-field">
                    <label>Length</label>
                    <div class="pw-select-wrap">
                        <select class="pw-select" id="pw-length">@foreach($lengths as $k => $v)<option value="{{ $k }}" @selected($k === 'medium')>{{ $v }}</option>@endforeach</select>
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
                <button type="button" class="pw-btn pw-btn-pink pw-regen" id="pw-regen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l1.6 3.3 3.6.5-2.6 2.5.6 3.6L12 10.7 8.8 12.4l.6-3.6L6.8 6.3l3.6-.5L12 2z"/></svg>Regenerate Proposal</button>
            </div>
            @endif

            <div class="pw-card">
                <div class="pw-side-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><b>Tips for Winning Proposals</b></div>
                <div class="pw-tip-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Mention the client's event theme.</div>
                <div class="pw-tip-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Highlight your relevant experience.</div>
                <div class="pw-tip-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Keep it warm, professional, and confident.</div>
                <div class="pw-tip-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Show how you'll make their day special.</div>
            </div>
        </div>
    </div>

    {{-- how it works --}}
    <div class="pw-card pw-mb">
        <div class="pw-hiw-h">How It Works (For Clients)</div>
        <div class="pw-steps">
            <div class="pw-step"><span class="pw-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10.5 13.5l1.5 1.5 3-3"/></svg></span><b>1. Add Event Details</b><p>Share the client's event description or requirements.</p></div>
            <span class="pw-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="pw-step"><span class="pw-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 4.1L18 9l-4.1 1.9L12 15l-1.9-4.1L6 9l4.1-1.9L12 3z"/><path d="M5 16l.9 2L8 19l-2.1.9L5 22l-.9-2.1L2 19l2.1-1L5 16z"/></svg></span><b>2. AI Generates Proposal</b><p>Our AI writes a personalized, professional proposal for you.</p></div>
            <span class="pw-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="pw-step"><span class="pw-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span><b>3. Customize (Optional)</b><p>Adjust tone, focus, and length to match your style.</p></div>
            <span class="pw-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="pw-step"><span class="pw-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></span><b>4. Copy or Download</b><p>Copy the proposal or download it as a file.</p></div>
            <span class="pw-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
            <div class="pw-step"><span class="pw-step-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></span><b>5. Send to Client</b><p>Send your proposal and increase your chances of getting booked!</p></div>
        </div>
    </div>

    {{-- powerful features --}}
    <div class="pw-card pw-mb">
        <div class="pw-feat-h">Powerful Features</div>
        <div class="pw-feats">
            <div class="pw-feat"><span class="pw-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><b>Smart Personalization</b><p>AI reads the event details and tailors your proposal automatically.</p></div>
            <div class="pw-feat"><span class="pw-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="12" r="2.5"/><circle cx="16" cy="16" r="2.5"/><path d="M11.5 7.5 9 10.5M15 14l-1.5-5"/></svg></span><b>Multiple Tones</b><p>Choose from professional, friendly, confident, or creative tones.</p></div>
            <div class="pw-feat"><span class="pw-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></span><b>One-Click Copy</b><p>Copy your proposal instantly to send across any platform.</p></div>
            <div class="pw-feat"><span class="pw-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span><b>Download Options</b><p>Download as .txt or .docx for easy sharing and record keeping.</p></div>
            <div class="pw-feat"><span class="pw-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 15 15"/></svg></span><b>Proposal History</b><p>Access all your generated proposals anytime in one place.</p></div>
        </div>
    </div>

    {{-- bottom banner --}}
    <div class="pw-banner">
        <span class="pw-banner-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        <div class="pw-banner-txt"><b>Write Better. Win More. Grow Your Business.</b><p>Proposal Builder helps you create personalized, high-converting proposals that impress clients and win more bookings.</p></div>
        <a id="pw-create"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Create Your Proposal Now ✨</a>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.pw');
    if (!root) return;
    const url = root.dataset.genUrl;
    const examples = JSON.parse(root.dataset.examples || '{}');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (id) => document.getElementById(id);
    const box = $('pw-proposal');

    async function call(payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        return res.ok ? res.json() : { success: false, message: 'Request failed.' };
    }

    // Maximum — full auto-generate from the event description.
    async function generateFull(btn) {
        const original = btn ? btn.innerHTML : null;
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const data = await call({ action: 'full', description: $('pw-desc')?.value || '', tone: $('pw-tone')?.value, focus: $('pw-focus')?.value, length: $('pw-length')?.value });
            if (data.success && data.proposal) box.value = data.proposal;
        } catch (e) {}
        finally { if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.innerHTML = original; } }
    }

    // Semi — improve / rewrite / expand the current draft.
    async function assist(action, btn) {
        if (!box.value.trim()) { box.focus(); return; }
        const label = btn.innerHTML; btn.disabled = true; btn.innerHTML = '…';
        try {
            const data = await call({ action, draft: box.value, tone: $('pw-tone')?.value });
            if (data.success && data.proposal) box.value = data.proposal;
            else if (data.message) alert(data.message);
        } catch (e) {}
        finally { btn.disabled = false; btn.innerHTML = label; }
    }

    // Wire — elements only exist for the relevant level, so guard everything.
    document.querySelectorAll('.pw-chip').forEach((chip) => chip.addEventListener('click', () => {
        if (examples[chip.dataset.key]) $('pw-desc').value = examples[chip.dataset.key];
        generateFull($('pw-regen'));
    }));
    $('pw-regen')?.addEventListener('click', function () { generateFull(this); });
    ['pw-focus', 'pw-length'].forEach((id) => $(id)?.addEventListener('change', () => generateFull($('pw-regen'))));
    if ($('pw-regen')) $('pw-tone')?.addEventListener('change', () => generateFull($('pw-regen')));
    document.querySelectorAll('.pw-assist').forEach((b) => b.addEventListener('click', () => assist(b.dataset.assist, b)));

    $('pw-copy')?.addEventListener('click', function () {
        navigator.clipboard?.writeText(box.value);
        const o = this.innerHTML;
        this.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Copied!';
        setTimeout(() => { this.innerHTML = o; }, 1600);
    });

    $('pw-download')?.addEventListener('click', function () {
        const blob = new Blob([box.value], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'proposal.txt';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    });

    $('pw-send')?.addEventListener('click', function () { navigator.clipboard?.writeText(box.value); });
    $('pw-create')?.addEventListener('click', function (e) { e.preventDefault(); (($('pw-desc') || box)).focus(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
})();
</script>
@endsection
