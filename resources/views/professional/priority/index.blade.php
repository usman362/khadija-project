@extends('layouts.professional')

@section('title', 'Priority Actions')

{{-- Priority Actions — explainer + a LIVE feed of real urgent items
     (contracts to sign, new proposals, open shifts, secure payment) aggregated by
     ProfessionalPriorityController. Example/spotlight uses the newest
     requested booking, with a demo fallback. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $ev = $spotlight?->event;
    // Example values — real spotlight or representative demo.
    $exDate   = $ev?->starts_at?->format('M jS, g:i A') ?? 'Oct 26th, 2:00 PM';
    $exEnd    = $ev?->ends_at?->format('g:i A') ?? '4:00 PM';
    $exLoc    = $ev?->location ?? '123 Maple St, Springfield';
    $exBudget = $ev?->budget ? $money($ev->budget) : '$2,000 – $3,000';
    $exClient = $spotlight?->client?->name ?? 'Sarah J.';
    $exTitle  = $ev?->title ?? 'Birthday Performance';
@endphp

@push('styles')
<style>
    .pa { --pa-blue: #2563eb; }
    .pa-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }

    .pa-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 16px; align-items: start; }

    /* Header */
    .pa-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 18px; }
    .pa-h-ico { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg,#a78bfa,#6d28d9); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 8px 18px rgba(124,58,237,0.35); }
    .pa-h-ico svg { width: 28px; height: 28px; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.15)); }
    .pa-h-spacer { flex: 1; }
    .pa-h-art { flex-shrink: 0; width: 168px; align-self: center; }
    .pa-h-art svg { width: 100%; height: auto; }
    @media (max-width: 980px) { .pa-h-art { display: none; } }
    .pa-header h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pa-header .tag { font-size: 14px; color: var(--text-muted); margin: 2px 0 10px; }
    .pa-header p { font-size: 13px; color: var(--text-secondary); margin: 0; line-height: 1.6; max-width: 560px; }

    /* Explainer section */
    .pa-sec { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,0.9fr); gap: 18px; padding: 18px 0; border-top: 1px solid var(--border-color); }
    .pa-sec:first-of-type { border-top: none; }
    .pa-sec-l { min-width: 0; }
    .pa-sec-h { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .pa-sec-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pa-sec-ico svg { width: 18px; height: 18px; }
    .pa-sec-t { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pa-sec-t span { font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .pa-sec-d { font-size: 12.5px; color: var(--text-muted); margin: 0 0 10px; }
    .pa-chk { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); padding: 3px 0; }
    .pa-chk svg { width: 14px; height: 14px; color: #10b981; flex-shrink: 0; }
    .pa-bullet { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); padding: 3px 0; }
    .pa-bullet .d { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

    /* Example box */
    .pa-ex { background: rgba(37,99,235,0.04); border: 1px solid rgba(37,99,235,0.14); border-radius: 12px; padding: 14px 16px; align-self: start; }
    .pa-ex-h { font-size: 12px; font-weight: 800; color: var(--pa-blue); margin-bottom: 10px; }
    .pa-ex-row { display: flex; align-items: center; gap: 9px; font-size: 12px; color: var(--text-secondary); padding: 4px 0; }
    .pa-ex-row svg { width: 14px; height: 14px; color: var(--pa-blue); flex-shrink: 0; }
    .pa-ex-row b { color: var(--text-primary); }
    .pa-ex-quote { font-size: 12.5px; font-style: italic; color: var(--text-secondary); line-height: 1.5; }
    .pa-ex-client { font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-top: 8px; }
    .pa-ex-timer { font-size: 11px; color: #dc2626; font-weight: 700; margin-top: 8px; }
    .pa-ex-timer .big { font-size: 22px; font-weight: 800; display: block; }
    .pa-ex-action { font-size: 12.5px; color: var(--text-secondary); }
    .pa-ex-action b { color: #059669; }

    /* How it works */
    .pa-hiw-title { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 18px 0 14px; }
    .pa-steps { display: flex; align-items: stretch; gap: 6px; }
    .pa-step { flex: 1; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 10px; text-align: center; }
    .pa-step-ico { width: 42px; height: 42px; border-radius: 11px; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 9px; }
    .pa-step-ico svg { width: 21px; height: 21px; }
    .pa-step h4 { font-size: 12px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .pa-step p { font-size: 10px; color: var(--text-muted); line-height: 1.35; margin: 0; }
    .pa-step-arr { display: flex; align-items: center; color: var(--text-muted); }
    .pa-step-arr svg { width: 16px; height: 16px; }

    /* What happens cards */
    .pa-wh-title { font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 18px 0 12px; }
    .pa-whs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; align-items: stretch; }
    .pa-wh { border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; display: flex; flex-direction: column; }
    .pa-wh.c1 { background: rgba(239,68,68,0.04); } .pa-wh.c2 { background: rgba(249,115,22,0.04); }
    .pa-wh.c3 { background: rgba(37,99,235,0.04); } .pa-wh.c4 { background: rgba(16,185,129,0.04); }
    .pa-wh-h { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
    .pa-wh-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
    .pa-wh-ico svg { width: 15px; height: 15px; }
    .pa-wh-nm { font-size: 12px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .pa-wh p { font-size: 10.5px; color: var(--text-muted); line-height: 1.4; margin: 0 0 9px; }
    .pa-wh-meta { font-size: 11px; font-weight: 700; color: var(--text-primary); margin-bottom: 9px; margin-top: auto; }
    .pa-wh-btn { display: block; text-align: center; padding: 8px; border-radius: 8px; color: #fff; border: none; font-size: 11px; font-weight: 800; cursor: pointer; text-decoration: none; }

    .pa-short { display: flex; align-items: center; gap: 14px; background: rgba(37,99,235,0.05); border: 1px solid rgba(37,99,235,0.18); border-radius: 12px; padding: 15px 18px; margin-top: 16px; }
    .pa-short .ic { width: 36px; height: 36px; border-radius: 10px; background: rgba(37,99,235,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pa-short .ic svg { width: 18px; height: 18px; }
    .pa-short-txt { flex: 1; }
    .pa-short-txt b { font-size: 12.5px; color: var(--text-primary); }
    .pa-short-txt p { font-size: 11.5px; color: var(--text-muted); margin: 1px 0 0; }
    .pa-short a { display: inline-flex; align-items: center; gap: 7px; background: #2563eb; color: #fff; font-size: 12px; font-weight: 800; padding: 10px 16px; border-radius: 9px; text-decoration: none; white-space: nowrap; }
    .pa-short a svg { width: 14px; height: 14px; }

    /* Sidebar */
    .pa-side { display: flex; flex-direction: column; gap: 16px; }
    .pa-side-h { font-size: 12px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.4px; margin-bottom: 4px; }
    .pa-side-sub { font-size: 11.5px; color: var(--text-muted); margin: 0 0 12px; line-height: 1.45; }
    .pa-live { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .pa-live-h { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--border-color); }
    .pa-live-h b { font-size: 12px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px; }
    .pa-live-count { background: #ef4444; color: #fff; font-size: 10px; font-weight: 800; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; }
    .pa-li { display: flex; gap: 10px; padding: 11px 14px; border-bottom: 1px solid var(--border-color); }
    .pa-li-ico { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .pa-li-ico svg { width: 15px; height: 15px; }
    .pa-li-body { flex: 1; min-width: 0; }
    .pa-li-t { font-size: 11.5px; font-weight: 800; color: var(--text-primary); }
    .pa-li-d { font-size: 10.5px; color: var(--text-muted); line-height: 1.4; }
    .pa-li-d b { color: var(--text-secondary); }
    .pa-li-btn { font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 6px; align-self: flex-start; white-space: nowrap; border: 1px solid; }
    .b-accept { color: #059669; border-color: rgba(16,185,129,0.4); background: rgba(16,185,129,0.08); }
    .b-counter { color: #d97706; border-color: rgba(245,158,11,0.4); background: rgba(245,158,11,0.08); }
    .b-decline { color: #dc2626; border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.08); }
    .pa-live-foot { padding: 11px 14px; text-align: center; }
    .pa-live-foot a { font-size: 12px; font-weight: 800; color: var(--pa-blue); text-decoration: none; }
    .pa-create { display: flex; align-items: center; justify-content: center; gap: 7px; margin: 4px 14px 0; padding: 9px; border-radius: 8px; background: #2563eb; color: #fff; font-size: 11.5px; font-weight: 800; text-decoration: none; }
    .pa-create svg { width: 13px; height: 13px; }

    .pa-tip { display: flex; gap: 10px; padding: 9px 0; }
    .pa-tip svg { width: 18px; height: 18px; flex-shrink: 0; }
    .pa-tip b { font-size: 12px; color: var(--text-primary); }
    .pa-tip p { font-size: 10.5px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.4; }

    .pa-notif { background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18); border-radius: 14px; padding: 16px; text-align: center; }
    .pa-notif h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 0 0 6px; }
    .pa-notif p { font-size: 11.5px; color: var(--text-muted); margin: 0 0 12px; line-height: 1.45; }
    .pa-notif a { display: inline-flex; align-items: center; gap: 7px; background: #2563eb; color: #fff; font-size: 12px; font-weight: 800; padding: 10px 16px; border-radius: 9px; text-decoration: none; }

    @media (max-width: 1200px) { .pa-grid { grid-template-columns: 1fr; } .pa-sec, .pa-whs { grid-template-columns: 1fr 1fr; } .pa-steps { flex-wrap: wrap; } .pa-step-arr { display: none; } .pa-step { flex: 1 1 28%; } }
    @media (max-width: 760px) { .pa-sec, .pa-whs { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pa">
    <div class="pa-grid">

        {{-- ─── MAIN ─── --}}
        <div>
            {{-- Header --}}
            <div class="pa-card" style="margin-bottom:16px;">
                <div class="pa-header">
                    <span class="pa-h-ico"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9z"/><path d="M13.73 21a2 2 0 0 1-3.46 0z"/></svg></span>
                    <div>
                        <h1>Priority Actions</h1>
                        <div class="tag">Your smart assistant for the most important offers and tasks.</div>
                        <p>Priority Actions shows you the urgent things you need to look at right now so you never miss out on great opportunities.</p>
                    </div>
                    <span class="pa-h-spacer"></span>
                    <div class="pa-h-art" aria-hidden="true">
                        <svg viewBox="0 0 170 138" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="92" cy="74" rx="74" ry="58" fill="#eef2ff"/>
                            {{-- clip --}}
                            <rect x="78" y="14" width="22" height="13" rx="3" fill="#64748b"/>
                            <rect x="83" y="10" width="12" height="9" rx="3" fill="#94a3b8"/>
                            {{-- clipboard --}}
                            <rect x="50" y="22" width="78" height="96" rx="10" fill="#2563eb"/>
                            <rect x="50" y="22" width="78" height="96" rx="10" fill="url(#pgrad)" opacity="0.25"/>
                            <rect x="59" y="31" width="60" height="78" rx="6" fill="#ffffff"/>
                            {{-- check rows --}}
                            @php $rows = [44, 66, 88]; @endphp
                            @foreach($rows as $ry)
                                <rect x="66" y="{{ $ry }}" width="15" height="15" rx="4.5" fill="#10b981"/>
                                <path d="M69.5 {{ $ry + 7.5 }}l2.6 2.6 4.4-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="86" y="{{ $ry + 3 }}" width="26" height="3.4" rx="1.7" fill="#cbd5e1"/>
                                <rect x="86" y="{{ $ry + 9 }}" width="18" height="3.4" rx="1.7" fill="#e2e8f0"/>
                            @endforeach
                            {{-- bell --}}
                            <g transform="translate(108,84)">
                                <path d="M30 22a11 11 0 0 0-22 0c0 12-4 15-4 15h30s-4-3-4-15z" fill="#f59e0b"/>
                                <path d="M30 22a11 11 0 0 0-22 0c0 12-4 15-4 15h13V22a11 11 0 0 1 11-11c.7 0 1.4.06 2 .17A11 11 0 0 0 30 22z" fill="#fbbf24"/>
                                <circle cx="19" cy="40" r="3.4" fill="#d97706"/>
                            </g>
                            {{-- sparkles --}}
                            <path d="M156 28l2 6.5 6.5 2-6.5 2-2 6.5-2-6.5-6.5-2 6.5-2z" fill="#f59e0b"/>
                            <path d="M28 30l1.6 4.4 4.4 1.6-4.4 1.6-1.6 4.4-1.6-4.4-4.4-1.6 4.4-1.6z" fill="#60a5fa"/>
                            <path d="M150 96l1.3 3.6 3.6 1.3-3.6 1.3-1.3 3.6-1.3-3.6-3.6-1.3 3.6-1.3z" fill="#a78bfa"/>
                            <defs><linearGradient id="pgrad" x1="50" y1="22" x2="128" y2="118"><stop stop-color="#fff"/><stop offset="1" stop-color="#1e40af"/></linearGradient></defs>
                        </svg>
                    </div>
                </div>

                {{-- WHAT --}}
                <div class="pa-sec">
                    <div class="pa-sec-l">
                        <div class="pa-sec-h"><span class="pa-sec-ico" style="background:rgba(239,68,68,0.12);color:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span class="pa-sec-t">WHAT <span>(Event Details)</span></span></div>
                        <p class="pa-sec-d">Shows the important facts about the event.</p>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>When the event happens</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Where it happens</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Guest count, budget, and other key details</div>
                    </div>
                    <div class="pa-ex">
                        <div class="pa-ex-h">Example:</div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span><b>Date:</b> {{ $exDate }} – {{ $exEnd }}</span></div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span><b>Location:</b> {{ $exLoc }}</span></div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><span><b>Guest Count:</b> 120 Guests</span></div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><span><b>Budget Range:</b> {{ $exBudget }}</span></div>
                    </div>
                </div>

                {{-- WHY --}}
                <div class="pa-sec">
                    <div class="pa-sec-l">
                        <div class="pa-sec-h"><span class="pa-sec-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><span class="pa-sec-t">WHY <span>(Client Message)</span></span></div>
                        <p class="pa-sec-d">Shows why the client needs your help.</p>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>The client tells you what they want</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Helps you understand the event better</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>You can decide if it's a good fit for you</div>
                    </div>
                    <div class="pa-ex">
                        <div class="pa-ex-h">Example:</div>
                        <div class="pa-ex-quote">"My son's 10th birthday! He loves magic and we want a special performance for his friends."</div>
                        <div class="pa-ex-client">Client: {{ $exClient }}</div>
                    </div>
                </div>

                {{-- EXTRAS --}}
                <div class="pa-sec">
                    <div class="pa-sec-l">
                        <div class="pa-sec-h"><span class="pa-sec-ico" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pa-sec-t">EXTRAS <span>(Offer &amp; Urgency)</span></span></div>
                        <p class="pa-sec-d">Shows the offer details and how soon you need to act.</p>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>How much the client is offering</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>How much time you have to respond</div>
                        <div class="pa-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>You can decide if it's a good offer</div>
                    </div>
                    <div class="pa-ex">
                        <div class="pa-ex-h">Example:</div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg><span><b>Offer:</b> {{ $spotlight?->price ? $money($spotlight->price) : '$300.00' }}</span></div>
                        <div class="pa-ex-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span><b>Urgency:</b> Offer expires in 4h 32m</span></div>
                        <div class="pa-ex-timer">Time Remaining:<span class="big" id="pa-timer">04:32:15</span></div>
                    </div>
                </div>

                {{-- SYSTEM ACTIONS --}}
                <div class="pa-sec">
                    <div class="pa-sec-l">
                        <div class="pa-sec-h"><span class="pa-sec-ico" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></span><span class="pa-sec-t">SYSTEM ACTIONS <span>(What You Can Do)</span></span></div>
                        <p class="pa-sec-d">Quick buttons to help you take action fast.</p>
                        <div class="pa-bullet"><span class="d" style="background:#10b981;"></span><b style="color:var(--text-primary);">Accept</b> – Take the offer</div>
                        <div class="pa-bullet"><span class="d" style="background:#f59e0b;"></span><b style="color:var(--text-primary);">Counter</b> – Make your own offer</div>
                        <div class="pa-bullet"><span class="d" style="background:#ef4444;"></span><b style="color:var(--text-primary);">Decline</b> – Politely say no</div>
                    </div>
                    <div class="pa-ex">
                        <div class="pa-ex-h">Example:</div>
                        <div class="pa-ex-action">Click <b>"Accept"</b> if you want the job! The client will be notified instantly.</div>
                    </div>
                </div>

                {{-- How it works --}}
                <div class="pa-hiw-title">HOW IT WORKS FOR PROFESSIONALS</div>
                <div class="pa-steps">
                    @php
                        $steps = [['bell','1. Get Notified',"You'll be alerted when a priority action arrives."],['target','2. Review Details','Check the what, why, and extras.'],['clip','3. Take Action','Accept, counter, or decline the offer.'],['shield','4. Client Reviews','The client reviews and awards the job.'],['trophy','5. You Win & Deliver','You deliver great work and get paid!']];
                    @endphp
                    @foreach($steps as $i => $s)
                        <div class="pa-step">
                            <div class="pa-step-ico">
                                @switch($s[0])
                                    @case('target')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>@break
                                    @case('clip')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>@break
                                    @case('shield')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>@break
                                    @case('trophy')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>@break
                                    @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                @endswitch
                            </div>
                            <h4>{{ $s[1] }}</h4>
                            <p>{{ $s[2] }}</p>
                        </div>
                        @if($i < count($steps)-1)<div class="pa-step-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>@endif
                    @endforeach
                </div>

                {{-- What happens --}}
                <div class="pa-wh-title">WHAT HAPPENS WHEN YOU CLICK THIS CARD?</div>
                <div class="pa-whs">
                    <div class="pa-wh c1">
                        <div class="pa-wh-h"><span class="pa-wh-ico" style="background:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="pa-wh-nm">Contract awaiting signature</span></div>
                        <p>A secure legal page shows the full digital contract between you and the client.</p>
                        <div class="pa-wh-meta">{{ $cards['contracts'] }} pending</div>
                        <a href="{{ route('professional.contracts.index') }}" class="pa-wh-btn" style="background:#ef4444;">Review &amp; Sign</a>
                    </div>
                    <div class="pa-wh c2">
                        <div class="pa-wh-h"><span class="pa-wh-ico" style="background:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg></span><span class="pa-wh-nm">Staffing shortage detected</span></div>
                        <p>Your crew dashboard shows exactly which jobs are empty.</p>
                        <div class="pa-wh-meta">{{ $cards['staffing'] }} open shift{{ $cards['staffing'] == 1 ? '' : 's' }}</div>
                        <a href="{{ route('professional.team.index') }}" class="pa-wh-btn" style="background:#f97316;">Hire Staff</a>
                    </div>
                    <div class="pa-wh c3">
                        <div class="pa-wh-h"><span class="pa-wh-ico" style="background:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pa-wh-nm">New bids to respond to</span></div>
                        <p>The active negotiation screen shows a countdown timer so you don't miss it.</p>
                        <div class="pa-wh-meta">{{ $cards['bids'] }} awaiting you</div>
                        <a href="{{ route('professional.proposals.index') }}" class="pa-wh-btn" style="background:#2563eb;">Respond Now</a>
                    </div>
                    <div class="pa-wh c4">
                        <div class="pa-wh-h"><span class="pa-wh-ico" style="background:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><span class="pa-wh-nm">Payment released</span></div>
                        <p>Your financial vault shows the payment has been released safely.</p>
                        <div class="pa-wh-meta">Amount: {{ $money($cards['escrow']) }}</div>
                        <a href="{{ route('professional.earnings.index') }}" class="pa-wh-btn" style="background:#10b981;">View Payment</a>
                    </div>
                </div>

                {{-- In short --}}
                <div class="pa-short">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/></svg></span>
                    <div class="pa-short-txt"><b>In Short</b><p>Priority Actions makes sure you see the right things at the right time, so you can win more gigs and grow your business!</p></div>
                    <a href="{{ route('professional.dashboard') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Go to Dashboard</a>
                </div>
            </div>
        </div>

        {{-- ─── SIDEBAR ─── --}}
        <div class="pa-side">
            <div class="pa-card">
                <div class="pa-side-h">LIVE EXAMPLE</div>
                <p class="pa-side-sub">This is what Priority Actions looks like when you have new items.</p>
                <div class="pa-live">
                    <div class="pa-live-h"><b>PRIORITY ACTIONS</b><span class="pa-live-count">{{ $priorityCount ?: 3 }}</span></div>
                    <div class="pa-li">
                        <span class="pa-li-ico" style="background:rgba(239,68,68,0.1);color:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                        <div class="pa-li-body"><div class="pa-li-t">The WHAT (Event Details)</div><div class="pa-li-d">{{ $exDate }} – {{ $exEnd }}<br>{{ \Illuminate\Support\Str::limit($exLoc, 24) }}</div></div>
                        <span class="pa-li-btn b-accept">Accept</span>
                    </div>
                    <div class="pa-li">
                        <span class="pa-li-ico" style="background:rgba(37,99,235,0.1);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                        <div class="pa-li-body"><div class="pa-li-t">The WHY (Client Message)</div><div class="pa-li-d">Special performance request. <b>Client: {{ $exClient }}</b></div></div>
                        <span class="pa-li-btn b-counter">Counter</span>
                    </div>
                    <div class="pa-li">
                        <span class="pa-li-ico" style="background:rgba(249,115,22,0.1);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        <div class="pa-li-body"><div class="pa-li-t">The EXTRAS (Offer &amp; Urgency)</div><div class="pa-li-d">Offer: {{ $spotlight?->price ? $money($spotlight->price) : '$300.00' }} · Expires in 4h 32m</div></div>
                        <span class="pa-li-btn b-decline">Decline</span>
                    </div>
                    <div class="pa-live-foot"><a href="{{ route('professional.proposals.index') }}">View All Actions →</a></div>
                </div>
            </div>

            <div class="pa-card">
                <div class="pa-side-h" style="margin-bottom:10px;">QUICK TIPS</div>
                <div class="pa-tip"><svg viewBox="0 0 24 24" fill="#f59e0b" stroke="none"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg><div><b>Act Fast</b><p>Urgent offers can expire. The quicker you respond, the better your chances!</p></div></div>
                <div class="pa-tip"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><div><b>Read Details Carefully</b><p>Understanding the client's message helps you make the best decision.</p></div></div>
                <div class="pa-tip"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M12 3v18M3 7h18M6 7l-3 6h6zM18 7l-3 6h6z"/></svg><div><b>Use Counter Wisely</b><p>If the offer is too low, you can make a counter offer and negotiate.</p></div></div>
                <div class="pa-tip"><svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><div><b>Stay Organized</b><p>Check your Priority Actions often so nothing important gets missed.</p></div></div>
            </div>

            <div class="pa-notif">
                <h4>Ready to Win More Gigs?</h4>
                <p>Enable smart notifications and stay ahead of the competition.</p>
                <a href="{{ route('professional.notifications.index') }}">Go to Notification Settings →</a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    let t = 4 * 3600 + 32 * 60 + 15;
    const el = document.getElementById('pa-timer');
    if (!el) return;
    setInterval(function () {
        if (t <= 0) return;
        t--;
        const h = String(Math.floor(t / 3600)).padStart(2, '0');
        const m = String(Math.floor((t % 3600) / 60)).padStart(2, '0');
        const s = String(t % 60).padStart(2, '0');
        el.textContent = h + ':' + m + ':' + s;
    }, 1000);
})();
</script>
@endsection
