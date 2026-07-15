@extends($aiLayout ?? 'layouts.client')

@section('title', 'Agreement — Evidence Collection')
@section('page-title', 'Agreement Builder')
@section('page-subtitle', 'Phase 1 — Discovery & Evidence Collection')

{{-- Agreement Builder · Phase 1. The tool scans the existing booking
     conversation, accepted proposal and attachments to assemble the evidence
     for a first draft, with per-source confidence and a missing-info detector.
     Evidence shown is representative pending the extraction service. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $evIcons = [
        'chat'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'proposal' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'files'    => '<path d="M21 19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l2 3h7a2 2 0 0 1 2 2z"/>',
        'timeline' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'finance'  => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'services' => '<path d="M20 6 9 17l-5-5"/>',
    ];
@endphp

@push('styles')
<style>
    .aab { --aab: #6366f1; --aab-strong: #4f46e5; }
    .aab-phase { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; font-weight: 800; font-size: 12.5px; letter-spacing: .4px; padding: 8px 16px; border-radius: 999px; }
    .aab-phase b { opacity: .85; font-weight: 700; }

    /* stepper */
    .aab-steps { display: flex; align-items: center; gap: 6px; margin: 18px 0 22px; flex-wrap: wrap; }
    .aab-step { display: flex; align-items: center; gap: 9px; flex: 1 1 0; min-width: 150px; }
    .aab-step-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; flex-shrink: 0; border: 2px solid var(--border-color); color: var(--text-muted); background: var(--bg-card); }
    .aab-step.done .aab-step-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .aab-step.active .aab-step-dot { background: var(--aab); border-color: var(--aab); color: #fff; box-shadow: 0 0 0 4px rgba(99,102,241,.18); }
    .aab-step-label { font-size: 12.5px; font-weight: 700; color: var(--text-secondary); }
    .aab-step.active .aab-step-label { color: var(--text-primary); }
    .aab-step-line { flex: 1; height: 2px; background: var(--border-color); min-width: 12px; }

    /* hire banner */
    .aab-hire { display: flex; align-items: center; gap: 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 16px); padding: 16px 18px; margin-bottom: 18px; flex-wrap: wrap; }
    .aab-hire-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(22,163,74,.12); color: #16a34a; font-weight: 800; font-size: 12px; padding: 6px 12px; border-radius: 999px; }
    .aab-hire-av { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; }
    .aab-hire-main { min-width: 0; }
    .aab-hire-main h4 { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .aab-hire-main p { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .aab-hire-amt { margin-left: auto; text-align: right; }
    .aab-hire-amt b { display: block; font-size: 20px; font-weight: 800; color: var(--text-primary); }
    .aab-hire-amt span { font-size: 11.5px; color: var(--text-muted); }

    /* layout */
    .aab-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 20px; align-items: start; }
    .aab-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 16px); }
    .aab-sec-head { padding: 16px 18px 4px; }
    .aab-sec-head h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .aab-sec-head p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

    /* evidence cards */
    .aab-ev-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 14px 18px 18px; }
    .aab-ev { border: 1px solid var(--border-color); border-radius: 14px; padding: 14px; background: var(--bg-card); position: relative; }
    .aab-ev-top { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .aab-ev-ic { width: 34px; height: 34px; border-radius: 10px; background: rgba(99,102,241,.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .aab-ev-ic svg { width: 17px; height: 17px; color: var(--aab); fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .aab-ev-tt { min-width: 0; }
    .aab-ev-tt h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .aab-ev-tt span { font-size: 11px; color: var(--text-muted); }
    .aab-ev-conf { margin-left: auto; font-size: 11px; font-weight: 800; color: #16a34a; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; }
    .aab-ev li { list-style: none; font-size: 12px; color: var(--text-secondary); padding: 3px 0 3px 16px; position: relative; }
    .aab-ev li::before { content: '✓'; position: absolute; left: 0; color: #16a34a; font-weight: 800; }

    .aab-generate { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 18px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .aab-generate p { font-size: 12.5px; color: var(--text-muted); }
    .aab-btn { border: none; border-radius: 12px; padding: 12px 22px; font-size: 14px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); cursor: pointer; display: inline-flex; align-items: center; gap: 9px; text-decoration: none; }
    .aab-btn svg { width: 17px; height: 17px; }

    /* sidebar */
    .aab-side { display: flex; flex-direction: column; gap: 16px; }
    .aab-side-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 16px); padding: 16px; }
    .aab-side-card h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; display: flex; align-items: center; gap: 7px; }
    .aab-side-card h4 svg { width: 15px; height: 15px; color: var(--aab); fill: none; stroke: currentColor; stroke-width: 2; }
    .aab-src { margin-bottom: 12px; }
    .aab-src:last-child { margin-bottom: 0; }
    .aab-src-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; }
    .aab-src-top span { color: var(--text-secondary); font-weight: 600; }
    .aab-src-top b { color: var(--text-primary); font-weight: 800; }
    .aab-bar { height: 6px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .aab-bar > i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--aab), var(--aab-strong)); }
    .aab-miss { font-size: 12px; color: var(--text-secondary); padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .aab-miss:last-child { border-bottom: none; }
    .aab-miss::before { content: '!'; position: absolute; left: 0; top: 7px; width: 15px; height: 15px; border-radius: 50%; background: #f59e0b; color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .aab-conf-ring { text-align: center; }
    .aab-conf-num { font-size: 34px; font-weight: 800; color: var(--aab); }
    .aab-conf-lbl { font-size: 12px; color: var(--text-muted); }
    .aab-conf-bar { margin-top: 10px; }

    @media (max-width: 920px) { .aab-grid { grid-template-columns: minmax(0,1fr); } .aab-ev-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="aab">
    <span class="aab-phase">⚡ PHASE 1 <b>· Discovery & Evidence Collection</b></span>

    {{-- Stepper --}}
    <div class="aab-steps">
        @foreach($steps as $i => [$label, $state])
            <div class="aab-step {{ $state }}">
                <span class="aab-step-dot">@if($state==='done')✓@else{{ $i+1 }}@endif</span>
                <span class="aab-step-label">{{ $label }}</span>
            </div>
            @if(!$loop->last)<span class="aab-step-line"></span>@endif
        @endforeach
    </div>

    {{-- Hire decision banner --}}
    <div class="aab-hire">
        <span class="aab-hire-badge">✓ Hire Decision Made</span>
        <span class="aab-hire-av">{{ strtoupper(substr($hire['pro'],0,2)) }}</span>
        <div class="aab-hire-main">
            <h4>{{ $hire['pro'] }} <span style="color:var(--text-muted);font-weight:600;font-size:12px;">★ {{ $hire['rating'] }} ({{ $hire['reviews'] }})</span></h4>
            <p>{{ $hire['pro_role'] }} · for <strong>{{ $hire['event'] }}</strong> · {{ $hire['date'] }}</p>
        </div>
        <div class="aab-hire-amt">
            <b>{{ $money($hire['amount']) }}</b>
            <span>Proposal accepted</span>
        </div>
    </div>

    <div class="aab-grid">
        {{-- Evidence collection --}}
        <div class="aab-card">
            <div class="aab-sec-head">
                <h3>Evidence Collection &amp; Analysis</h3>
                <p>The tool gathered everything from your conversation, accepted proposal and files to build the most accurate agreement possible.</p>
            </div>
            <div class="aab-ev-grid">
                @foreach($evidence as $ev)
                    <div class="aab-ev">
                        <div class="aab-ev-top">
                            <span class="aab-ev-ic"><svg viewBox="0 0 24 24">{!! $evIcons[$ev['key']] ?? '' !!}</svg></span>
                            <div class="aab-ev-tt">
                                <h5>{{ $ev['title'] }}</h5>
                                <span>{{ $ev['meta'] }}</span>
                            </div>
                            <span class="aab-ev-conf">● {{ $ev['confidence'] }}%</span>
                        </div>
                        <ul>
                            @foreach($ev['items'] as $it)<li>{{ $it }}</li>@endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <div class="aab-generate">
                <p>Review the gaps on the right, then generate the first draft to move into negotiation.</p>
                <a href="{{ route('ai-agreement.draft') }}" class="aab-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/></svg>
                    Generate Draft Agreement
                </a>
            </div>
        </div>

        {{-- Sidebar: sources + missing + confidence --}}
        <aside class="aab-side">
            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Evidence Sources</h4>
                @foreach($sources as [$name, $pct])
                    <div class="aab-src">
                        <div class="aab-src-top"><span>{{ $name }}</span><b>{{ $pct }}%</b></div>
                        <div class="aab-bar"><i style="width: {{ $pct }}%"></i></div>
                    </div>
                @endforeach
            </div>

            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Missing-Info Detector</h4>
                @foreach($missing as $m)
                    <div class="aab-miss">{{ $m }}</div>
                @endforeach
            </div>

            <div class="aab-side-card aab-conf-ring">
                <h4 style="justify-content:center;"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Confidence Overview</h4>
                <div class="aab-conf-num">{{ $overall_confidence }}%</div>
                <div class="aab-conf-lbl">Overall draft confidence</div>
                <div class="aab-bar aab-conf-bar"><i style="width: {{ $overall_confidence }}%"></i></div>
            </div>
        </aside>
    </div>
</div>
@endsection
