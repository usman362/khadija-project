@extends('layouts.professional')

@section('title', 'Lead Pipeline')

{{-- Lead Pipeline (Leads CRM) — explainer + a live snapshot of the pro's
     sales funnel (real: open opportunities + requested bookings, split by
     stage). "View All Leads" opens the full Proposals/Bids list. The
     follow-up / predictor preview cards are explainer UI. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $prioMap = [
        'High'   => ['#dc2626', 'rgba(220,38,38,0.10)'],
        'Medium' => ['#d97706', 'rgba(217,119,6,0.10)'],
        'Low'    => ['#2563eb', 'rgba(37,99,235,0.10)'],
    ];
    $avatars = ['linear-gradient(135deg,#8b5cf6,#6d28d9)','linear-gradient(135deg,#2563eb,#1d4ed8)','linear-gradient(135deg,#10b981,#047857)','linear-gradient(135deg,#ec4899,#be185d)','linear-gradient(135deg,#f59e0b,#b45309)'];
    // Stage display config for the progress pipeline.
    $pipeColors = ['new'=>'#2563eb','proposal'=>'#10b981','negotiation'=>'#8b5cf6','booked'=>'#1e3a8a'];
@endphp

@push('styles')
<style>
    .pl { --pl-blue: #2563eb; }
    .pl-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }

    /* ── Hero (funnel + title + feature row) ── */
    .pl-hero { display: grid; grid-template-columns: minmax(0,300px) minmax(0,1fr); gap: 22px; align-items: center; background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(139,92,246,0.04)); border: 1px solid var(--border-color); border-radius: 18px; padding: 26px 28px; margin-bottom: 20px; }
    .pl-funnel { display: flex; align-items: center; justify-content: center; }
    .pl-funnel svg { width: 100%; max-width: 280px; height: auto; }
    .pl-hero-r h1 { font-size: 32px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pl-hero-r .sub { font-size: 16px; color: var(--text-secondary); margin: 4px 0 18px; }
    .pl-feats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }
    .pl-feat-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .pl-feat-ico svg { width: 18px; height: 18px; }
    .pl-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; line-height: 1.3; }
    .pl-feat p { font-size: 11px; color: var(--text-muted); margin: 4px 0 0; line-height: 1.45; }

    /* ── Progress pipeline bar ── */
    .pl-pipe { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 20px; }
    .pl-stage { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 6px 14px; }
    .pl-stage-l .k { font-size: 13px; font-weight: 700; }
    .pl-stage-l .v { font-size: 34px; font-weight: 800; color: var(--text-primary); line-height: 1; margin-top: 2px; }
    .pl-stage-ico { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-stage-ico svg { width: 24px; height: 24px; }
    .pl-arrow { flex-shrink: 0; color: var(--text-muted); opacity: 0.6; }
    .pl-arrow svg { width: 22px; height: 22px; }

    /* ── Active leads list ── */
    .pl-lead { display: flex; align-items: center; gap: 14px; padding: 15px 6px; border-top: 1px solid var(--border-color); }
    .pl-lead:first-child { border-top: none; }
    .pl-lead-av { width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; font-weight: 800; }
    .pl-lead-mid { flex: 1; min-width: 0; }
    .pl-lead-name { font-size: 15px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pl-lead-meta { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .pl-lead-val { font-size: 15px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .pl-prio { font-size: 12.5px; font-weight: 800; padding: 6px 16px; border-radius: 999px; white-space: nowrap; min-width: 84px; text-align: center; }
    .pl-viewall { display: block; text-align: center; padding: 14px; margin-top: 8px; font-size: 14px; font-weight: 800; color: var(--pl-blue); text-decoration: none; }
    .pl-viewall svg { width: 16px; height: 16px; vertical-align: -3px; margin-left: 4px; }
    .pl-empty { padding: 30px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* ── Section heading ── */
    .pl-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .pl-sec-h svg { width: 20px; height: 20px; color: #2563eb; }
    .pl-sec-h b { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .pl-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; }

    /* ── Detailed Section Breakdown (4 cols) ── */
    .pl-bd { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .pl-bd-col { border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; }
    .pl-bd-h { font-size: 13.5px; font-weight: 800; color: #2563eb; margin-bottom: 6px; }
    .pl-bd-sub { font-size: 11.5px; color: var(--text-muted); margin: 0 0 12px; line-height: 1.5; }
    .pl-bd-row { display: flex; gap: 10px; padding: 7px 0; align-items: flex-start; }
    .pl-bd-row .ic { width: 26px; height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-bd-row .ic svg { width: 14px; height: 14px; }
    .pl-bd-row b { font-size: 12px; color: var(--text-primary); display: block; }
    .pl-bd-row p { font-size: 11px; color: var(--text-muted); margin: 1px 0 0; line-height: 1.4; }
    .pl-bd-funnel { display: flex; justify-content: center; padding: 10px 0; }
    .pl-bd-funnel svg { width: 120px; height: auto; }
    .pl-bd-note { font-size: 11.5px; color: var(--text-secondary); line-height: 1.5; margin: 10px 0 0; }
    .pl-bd-note b { color: #2563eb; }
    .pl-mock { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 9px; overflow: hidden; margin-bottom: 12px; }
    .pl-mock-bar { display: flex; gap: 4px; padding: 7px 9px; background: #1e293b; }
    .pl-mock-bar i { width: 7px; height: 7px; border-radius: 50%; background: #475569; }
    .pl-mock-body { display: grid; grid-template-columns: 30% 1fr; min-height: 78px; }
    .pl-mock-side { background: #1e293b; padding: 8px; }
    .pl-mock-side .ln { height: 6px; border-radius: 3px; background: #475569; margin-bottom: 6px; }
    .pl-mock-main { padding: 9px; }
    .pl-mock-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
    .pl-mock-row .dot { width: 14px; height: 14px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
    .pl-mock-row .ln { flex: 1; height: 6px; border-radius: 3px; background: var(--border-color); }
    .pl-bd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 1px solid var(--border-color); border-radius: 9px; font-size: 13px; font-weight: 800; color: var(--pl-blue); text-decoration: none; }
    .pl-bd-cta svg { width: 15px; height: 15px; }

    /* ── What happens if you click (4 cols) ── */
    .pl-cc { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .pl-cc-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; background: var(--bg-card); }
    .pl-cc-h { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 4px; }
    .pl-cc-h .ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-cc-h .ic svg { width: 16px; height: 16px; }
    .pl-cc-h b { font-size: 13px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .pl-cc-card > p { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 0 0 12px; }
    .pl-cc-prev { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px; }
    /* chat */
    .pl-chat-hd { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; }
    .pl-chat-av { width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 800; }
    .pl-chat-hd b { font-size: 11px; color: var(--text-primary); }
    .pl-chat-hd p { font-size: 9.5px; color: var(--text-muted); margin: 0; }
    .pl-bub { border-radius: 9px; padding: 6px 9px; font-size: 10px; line-height: 1.35; margin-bottom: 6px; max-width: 85%; }
    .pl-bub.them { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary); }
    .pl-bub.me { background: rgba(37,99,235,0.12); color: var(--text-primary); margin-left: auto; }
    .pl-bub .t { display: block; font-size: 8px; color: var(--text-muted); margin-top: 2px; }
    .pl-chat-icons { display: flex; gap: 14px; padding-top: 8px; border-top: 1px solid var(--border-color); margin-top: 8px; }
    .pl-chat-icons svg { width: 15px; height: 15px; color: var(--text-muted); }
    /* proposal */
    .pl-prop-amt { font-size: 22px; font-weight: 800; color: #10b981; }
    .pl-prop-k { font-size: 9.5px; color: var(--text-muted); }
    .pl-chk { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: var(--text-secondary); padding: 3px 0; }
    .pl-chk svg { width: 13px; height: 13px; color: #10b981; flex-shrink: 0; }
    /* follow-up flow */
    .pl-flow-step { display: flex; align-items: center; gap: 9px; padding: 7px 9px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); }
    .pl-flow-step .ic { width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-flow-step .ic svg { width: 13px; height: 13px; }
    .pl-flow-step span { font-size: 10px; color: var(--text-secondary); line-height: 1.3; }
    .pl-flow-arrow { display: flex; justify-content: center; padding: 3px 0; }
    .pl-flow-arrow svg { width: 13px; height: 13px; color: var(--text-muted); }
    /* predictor gauge */
    .pl-gauge { width: 120px; height: 64px; margin: 4px auto 8px; position: relative; }
    .pl-gauge .pct { position: absolute; bottom: 0; left: 0; right: 0; text-align: center; }
    .pl-gauge .pct b { font-size: 22px; font-weight: 800; color: #10b981; display: block; line-height: 1; }
    .pl-gauge .pct span { font-size: 8.5px; color: var(--text-muted); }

    .pl-cc-btn { display: flex; align-items: center; justify-content: center; gap: 6px; text-align: center; padding: 9px; border-radius: 8px; color: #fff; font-size: 11px; font-weight: 800; text-decoration: none; margin-top: 11px; }
    .pl-cc-btn svg { width: 13px; height: 13px; }

    /* ── Bottom banner ── */
    .pl-banner { display: flex; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(139,92,246,0.05)); border: 1px solid rgba(37,99,235,0.18); border-radius: 16px; padding: 18px 22px; margin-top: 20px; }
    .pl-banner .badge { width: 46px; height: 46px; border-radius: 12px; background: rgba(37,99,235,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pl-banner .badge svg { width: 24px; height: 24px; color: #2563eb; }
    .pl-banner-txt { flex: 1; }
    .pl-banner-txt b { font-size: 16px; color: var(--text-primary); }
    .pl-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 2px 0 0; }
    .pl-banner a { display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: #fff; font-size: 14px; font-weight: 800; padding: 13px 22px; border-radius: 11px; text-decoration: none; white-space: nowrap; }
    .pl-banner a svg { width: 16px; height: 16px; }

    @media (max-width: 1200px) { .pl-hero { grid-template-columns: 1fr; } .pl-bd, .pl-cc { grid-template-columns: repeat(2, minmax(0,1fr)); } .pl-feats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .pl-pipe { flex-wrap: wrap; } .pl-arrow { display: none; } .pl-bd, .pl-cc, .pl-feats { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pl">

    {{-- ════════ Hero: funnel + title + feature row ════════ --}}
    <div class="pl-hero">
        <div class="pl-funnel">
            <svg viewBox="0 0 240 210" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="plf" x1="40" y1="60" x2="200" y2="150"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient>
                    <linearGradient id="plp" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient>
                </defs>
                {{-- funnel body --}}
                <path d="M40 64 L200 64 L150 132 L150 184 Q150 192 142 190 L98 178 Q90 176 90 168 L90 132 Z" fill="url(#plf)"/>
                <ellipse cx="120" cy="64" rx="80" ry="18" fill="#2563eb"/>
                <ellipse cx="120" cy="62" rx="80" ry="16" fill="#60a5fa"/>
                <ellipse cx="120" cy="62" rx="62" ry="11" fill="#1d4ed8" opacity="0.55"/>
                {{-- people on the rim --}}
                @php $ppl = [[88,40],[120,32],[152,40]]; @endphp
                @foreach($ppl as [$cx,$cy])
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="9" fill="url(#plp)"/>
                    <path d="M{{ $cx-13 }} {{ $cy+26 }} a13 14 0 0 1 26 0 z" fill="url(#plp)"/>
                @endforeach
                {{-- leads dropping out --}}
                <circle cx="120" cy="202" r="7" fill="#2563eb"/>
                <circle cx="48" cy="150" r="6" fill="#3b82f6"/><path d="M70 138 L52 148" stroke="#93c5fd" stroke-width="2" stroke-dasharray="3 3"/>
                <circle cx="196" cy="150" r="6" fill="#3b82f6"/><path d="M172 138 L190 148" stroke="#93c5fd" stroke-width="2" stroke-dasharray="3 3"/>
                <circle cx="30" cy="120" r="4" fill="#93c5fd"/>
                <circle cx="212" cy="120" r="4" fill="#93c5fd"/>
            </svg>
        </div>
        <div class="pl-hero-r">
            <h1>Lead Pipeline</h1>
            <div class="sub">Track leads from inquiry to booking.</div>
            <div class="pl-feats">
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <b>Remembers Every Customer</b>
                    <p>Never forget an important lead.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
                    <b>Tracks Deal Progress</b>
                    <p>See exactly where each lead stands.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(239,68,68,0.12);color:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg></span>
                    <b>Prioritizes Hot Leads</b>
                    <p>Focus on the leads most likely to close.</p>
                </div>
                <div class="pl-feat">
                    <span class="pl-feat-ico" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span>
                    <b>Organizes Sales Flow</b>
                    <p>Visualize your path to monthly goals.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ Progress pipeline bar ════════ --}}
    <div class="pl-card" style="margin-bottom:20px;">
        <div class="pl-pipe">
            @php
                $stageIcons = [
                    'new'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>',
                    'proposal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="14" x2="15" y2="14"/></svg>',
                    'negotiation' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                    'booked'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1.2 14.2l-4-4 1.4-1.4 2.6 2.6 5.6-5.6 1.4 1.4z"/></svg>',
                ];
            @endphp
            @foreach($pipeline as $i => $stage)
                @php $col = $pipeColors[$stage['key']]; @endphp
                <div class="pl-stage">
                    <div class="pl-stage-l">
                        <div class="k" style="color:{{ $col }};">{{ $stage['label'] }}</div>
                        <div class="v">{{ $stage['count'] }}</div>
                    </div>
                    <span class="pl-stage-ico" style="background:{{ $stage['key']==='booked' ? '#2563eb' : 'rgba(37,99,235,0.07)' }};color:{{ $stage['key']==='booked' ? '#fff' : $col }};">{!! $stageIcons[$stage['key']] !!}</span>
                </div>
                @if(!$loop->last)
                    <span class="pl-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
                @endif
            @endforeach
        </div>

        {{-- Active leads list --}}
        @forelse($leads as $i => $lead)
            @php [$pc, $pbg] = $prioMap[$lead['priority']] ?? $prioMap['Medium']; $words = preg_split('/\s+/', trim($lead['name'])); $initials = strtoupper(substr($words[0] ?? 'L',0,1) . (count($words) > 1 ? substr(end($words),0,1) : '')); @endphp
            <div class="pl-lead">
                <span class="pl-lead-av" style="background:{{ $avatars[$i % count($avatars)] }};">{{ $initials ?: 'L' }}</span>
                <div class="pl-lead-mid">
                    <div class="pl-lead-name">{{ $lead['name'] }}</div>
                    <div class="pl-lead-meta">{{ \Illuminate\Support\Str::limit($lead['location'], 24) }} · {{ $lead['date'] ? $lead['date']->format('M d, Y') : 'Date TBD' }}</div>
                </div>
                <div class="pl-lead-val">{{ $money($lead['valueLow']) }} – {{ $money($lead['valueHigh']) }}</div>
                <span class="pl-prio" style="color:{{ $pc }};background:{{ $pbg }};">{{ $lead['priority'] }}</span>
            </div>
        @empty
            <div class="pl-empty">No active leads yet. New marketplace opportunities and client requests will appear here automatically.</div>
        @endforelse

        <a href="{{ route('professional.proposals.index') }}" class="pl-viewall">View All Leads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>

    {{-- ════════ Detailed Section Breakdown ════════ --}}
    <div class="pl-card" style="margin-bottom:20px;">
        <div class="pl-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg><b>Detailed Section Breakdown</b></div>
        <p class="pl-sec-sub">Understand how this dashboard helps you manage leads.</p>
        <div class="pl-bd">
            {{-- 1. Funnel Header --}}
            <div class="pl-bd-col">
                <div class="pl-bd-h">1. The Funnel Header</div>
                <p class="pl-bd-sub">The funnel visualizes how inquiries move through your sales process.</p>
                <div class="pl-bd-funnel">
                    <svg viewBox="0 0 120 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 34 L98 34 L74 70 L74 96 Q74 100 70 99 L50 93 Q46 92 46 88 L46 70 Z" fill="url(#plf2)"/>
                        <ellipse cx="60" cy="34" rx="38" ry="9" fill="#60a5fa"/>
                        <circle cx="44" cy="22" r="6" fill="#2563eb"/><circle cx="60" cy="18" r="6" fill="#2563eb"/><circle cx="76" cy="22" r="6" fill="#2563eb"/>
                        <circle cx="60" cy="106" r="4" fill="#2563eb"/>
                        <defs><linearGradient id="plf2" x1="22" y1="34" x2="98" y2="96"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs>
                    </svg>
                </div>
                <p class="pl-bd-note"><b>The Mission:</b> This area monitors your leads through every step of the sales journey.</p>
            </div>
            {{-- 2. Progress Pipeline --}}
            <div class="pl-bd-col">
                <div class="pl-bd-h">2. The Progress Pipeline</div>
                <p class="pl-bd-sub">Shows how many leads are in each stage of the sales process.</p>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><div><b>New Leads ({{ $stats['new'] }})</b><p>Just reached out</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div><b>Proposal Sent ({{ $stats['proposal'] }})</b><p>Received your proposal</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><div><b>Negotiation ({{ $stats['negotiation'] }})</b><p>Discussing details</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-1.2 14.2l-4-4 1.4-1.4 2.6 2.6 5.6-5.6 1.4 1.4z"/></svg></span><div><b>Booked ({{ $stats['booked'] }})</b><p>Contract signed</p></div></div>
            </div>
            {{-- 3. Active Leads List --}}
            <div class="pl-bd-col">
                <div class="pl-bd-h">3. The Active Leads List</div>
                <p class="pl-bd-sub">Highlights key details about your most important leads.</p>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(100,116,139,0.12);color:#64748b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><div><b>Client Profile</b><p>Who the lead is</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div><b>Value Bracket</b><p>Estimated project value</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(239,68,68,0.12);color:#ef4444;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg></span><div><b>Priority Tag</b><p>How hot the lead is</p></div></div>
                <div class="pl-bd-row"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div><b>Event Details</b><p>Location &amp; event date</p></div></div>
            </div>
            {{-- 4. Navigation Gate --}}
            <div class="pl-bd-col">
                <div class="pl-bd-h">4. The Navigation Gate</div>
                <p class="pl-bd-sub">Takes you to your full CRM to manage every lead.</p>
                <div class="pl-mock">
                    <div class="pl-mock-bar"><i></i><i></i><i></i></div>
                    <div class="pl-mock-body">
                        <div class="pl-mock-side"><div class="ln"></div><div class="ln" style="width:70%;"></div><div class="ln" style="width:85%;"></div><div class="ln" style="width:60%;"></div></div>
                        <div class="pl-mock-main"><div class="pl-mock-row"><span class="dot"></span><span class="ln"></span></div><div class="pl-mock-row"><span class="dot"></span><span class="ln" style="width:75%;"></span></div><div class="pl-mock-row"><span class="dot"></span><span class="ln" style="width:60%;"></span></div></div>
                    </div>
                </div>
                <a href="{{ route('professional.proposals.index') }}" class="pl-bd-cta">View All Leads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                <p class="pl-bd-note" style="color:var(--text-muted);">Access your complete lead list, filters, and advanced tools.</p>
            </div>
        </div>
    </div>

    {{-- ════════ What happens if you click ════════ --}}
    <div class="pl-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91 0z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg><b>What Happens if You Click?</b></div>
    <p class="pl-sec-sub">Unlock powerful tools to convert more leads into bookings.</p>
    <div class="pl-cc">
        {{-- Direct Message Center --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span><b>Direct Message Center</b></div>
            <p>View all conversations in one place — email, text &amp; calls.</p>
            <div class="pl-cc-prev">
                <div class="pl-chat-hd"><span class="pl-chat-av" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">{{ $leads->first()['name'] ?? false ? strtoupper(substr($leads->first()['name'],0,1)) : 'L' }}</span><div><b>{{ \Illuminate\Support\Str::limit($leads->first()['name'] ?? 'New Lead', 16) }}</b><p>{{ \Illuminate\Support\Str::limit($leads->first()['location'] ?? 'Location', 14) }}</p></div></div>
                <div class="pl-bub them">Hi! I'd like to learn more about your services.<span class="t">11:30 AM</span></div>
                <div class="pl-bub me">Great! I'd be happy to send you more info.<span class="t">11:32 AM</span></div>
                <div class="pl-chat-icons">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
            </div>
            <a href="{{ route('professional.chat.index') }}" class="pl-cc-btn" style="background:#2563eb;">Open Message Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        {{-- Instant Proposal Generator --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span><b>Instant Proposal Generator</b></div>
            <p>Create and send professional proposals in minutes.</p>
            <div class="pl-cc-prev">
                <div class="pl-prop-k">Proposal for</div>
                <div style="font-size:11px;font-weight:800;color:var(--text-primary);margin-bottom:6px;">{{ \Illuminate\Support\Str::limit($leads->first()['name'] ?? 'New Lead', 20) }}</div>
                <div class="pl-prop-amt">{{ $money($leads->first()['valueHigh'] ?? 4750) }}</div>
                <div class="pl-prop-k" style="margin-bottom:8px;">Estimated Total</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Event Details</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Services Included</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Pricing Breakdown</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Terms &amp; Conditions</div>
            </div>
            <a href="{{ route('professional.proposals.index') }}" class="pl-cc-btn" style="background:#10b981;">Create Proposal <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        {{-- Automatic Follow-Up Bots --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg></span><b>Automatic Follow-Up Bots</b></div>
            <p>Never let a lead slip away. We follow up for you.</p>
            <div class="pl-cc-prev">
                <div class="pl-flow-step"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22 6 12 13 2 6"/></svg></span><span>Lead receives proposal</span></div>
                <div class="pl-flow-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg></div>
                <div class="pl-flow-step"><span class="ic" style="background:rgba(217,119,6,0.12);color:#d97706;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span>No reply in 3 days</span></div>
                <div class="pl-flow-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg></div>
                <div class="pl-flow-step"><span class="ic" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/></svg></span><span>Automatic friendly follow-up sent</span></div>
            </div>
        </div>
        {{-- Probability Predictor --}}
        <div class="pl-cc-card">
            <div class="pl-cc-h"><span class="ic" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span><b>Probability Predictor</b></div>
            <p>AI predicts how likely a lead is to book your services.</p>
            <div class="pl-cc-prev">
                @php $pct = max(0, min(100, (int) ($conversion ?: 82))); $deg = $pct * 1.8; @endphp
                <div class="pl-gauge">
                    <svg viewBox="0 0 120 64" fill="none" style="width:100%;height:100%;">
                        <path d="M10 60 A50 50 0 0 1 110 60" stroke="var(--border-color)" stroke-width="11" fill="none" stroke-linecap="round"/>
                        <path d="M10 60 A50 50 0 0 1 110 60" stroke="url(#plg)" stroke-width="11" fill="none" stroke-linecap="round" stroke-dasharray="157" stroke-dashoffset="{{ 157 - (157 * $pct / 100) }}"/>
                        <defs><linearGradient id="plg" x1="10" y1="0" x2="110" y2="0"><stop stop-color="#ef4444"/><stop offset="0.5" stop-color="#f59e0b"/><stop offset="1" stop-color="#10b981"/></linearGradient></defs>
                    </svg>
                    <div class="pct"><b>{{ $pct }}%</b><span>{{ $pct >= 60 ? 'High' : ($pct >= 35 ? 'Medium' : 'Low') }} Chance of Booking</span></div>
                </div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Location Match</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>High Budget</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Fast Response</div>
                <div class="pl-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Engaged Lead</div>
            </div>
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="pl-banner">
        <span class="badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span>
        <div class="pl-banner-txt"><b>More Leads. Better Deals. Bigger Business.</b><p>Stay organized, follow up faster, and close more bookings.</p></div>
        <a href="{{ route('professional.proposals.index') }}">View All Leads <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
