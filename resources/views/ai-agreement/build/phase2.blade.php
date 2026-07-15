@extends($aiLayout ?? 'layouts.client')

@section('title', 'Agreement — Negotiation')
@section('page-title', 'Agreement Builder')
@section('page-subtitle', 'Phase 2 — Collaboration & Negotiation')

{{-- Agreement Builder · Phase 2. Both parties collaborate on the tool draft —
     editing clauses, tracking versions and redlines, and threading comments —
     until every clause is agreed. Data is representative pending the model. --}}

@php
    $statusMeta = [
        'agreed'       => ['Agreed', '#16a34a', 'rgba(22,163,74,.12)'],
        'edited'       => ['Edited', '#2563eb', 'rgba(37,99,235,.12)'],
        'ai-suggested' => ['Suggested', '#6366f1', 'rgba(99,102,241,.14)'],
        'disputed'     => ['Needs Response', '#d97706', 'rgba(217,119,6,.14)'],
    ];
@endphp

@push('styles')
<style>
    .aab { --aab: #6366f1; --aab-strong: #4f46e5; }
    .aab-phase { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; font-weight: 800; font-size: 12.5px; letter-spacing: .4px; padding: 8px 16px; border-radius: 999px; }
    .aab-phase b { opacity: .85; font-weight: 700; }

    /* cycle sub-stepper */
    .aab-cycle { display: flex; align-items: center; gap: 6px; margin: 18px 0 18px; flex-wrap: wrap; }
    .aab-cyc { display: flex; align-items: center; gap: 8px; }
    .aab-cyc-dot { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; flex-shrink: 0; border: 2px solid var(--border-color); color: var(--text-muted); background: var(--bg-card); }
    .aab-cyc.done .aab-cyc-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .aab-cyc.active .aab-cyc-dot { background: var(--aab); border-color: var(--aab); color: #fff; box-shadow: 0 0 0 4px rgba(99,102,241,.18); }
    .aab-cyc-label { font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .aab-cyc.active .aab-cyc-label { color: var(--text-primary); }
    .aab-cyc-line { width: 22px; height: 2px; background: var(--border-color); }

    /* status bar */
    .aab-statusbar { display: flex; align-items: center; gap: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); padding: 14px 18px; margin-bottom: 18px; flex-wrap: wrap; }
    .aab-vtag { font-size: 12px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); padding: 5px 11px; border-radius: 8px; }
    .aab-status-txt { font-size: 13px; color: var(--text-secondary); font-weight: 600; }
    .aab-prog { margin-left: auto; display: flex; align-items: center; gap: 10px; min-width: 180px; }
    .aab-prog .aab-bar { flex: 1; }
    .aab-prog b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .aab-bar { height: 7px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .aab-bar > i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--aab), var(--aab-strong)); }

    .aab-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 20px; align-items: start; }
    .aab-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); }
    .aab-sec-head { padding: 16px 18px 4px; }
    .aab-sec-head h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .aab-sec-head p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

    /* clauses */
    .aab-clauses { padding: 8px 18px 18px; }
    .aab-clause { border: 1px solid var(--border-color); border-radius: 13px; padding: 14px 16px; margin-bottom: 12px; }
    .aab-clause.is-suggested { border-color: rgba(99,102,241,.5); background: rgba(99,102,241,.05); }
    .aab-clause.is-disputed { border-color: rgba(217,119,6,.45); }
    .aab-clause-top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .aab-clause-top h5 { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .aab-chip { font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px; }
    .aab-clause-body { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; }
    .aab-change { margin-top: 8px; font-size: 11.5px; color: #d97706; background: rgba(217,119,6,.1); border-radius: 8px; padding: 6px 10px; display: flex; align-items: center; gap: 6px; }
    .aab-change.added { color: #2563eb; background: rgba(37,99,235,.1); }
    .aab-clause-acts { display: flex; gap: 8px; margin-top: 10px; }
    .aab-mini { font-size: 11.5px; font-weight: 700; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
    .aab-mini.accent { border: none; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; }

    /* sidebar */
    .aab-side { display: flex; flex-direction: column; gap: 16px; }
    .aab-side-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); padding: 16px; }
    .aab-side-card h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; display: flex; align-items: center; gap: 7px; }
    .aab-side-card h4 svg { width: 15px; height: 15px; color: var(--aab); fill: none; stroke: currentColor; stroke-width: 2; }

    .aab-ver { display: flex; gap: 11px; padding-bottom: 13px; position: relative; }
    .aab-ver:last-child { padding-bottom: 0; }
    .aab-ver::before { content: ''; position: absolute; left: 6px; top: 16px; bottom: 0; width: 2px; background: var(--border-color); }
    .aab-ver:last-child::before { display: none; }
    .aab-ver-dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid var(--border-color); background: var(--bg-card); flex-shrink: 0; margin-top: 2px; z-index: 1; }
    .aab-ver.current .aab-ver-dot { border-color: var(--aab); background: var(--aab); }
    .aab-ver-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .aab-ver-main h6 span { color: var(--text-muted); font-weight: 600; }
    .aab-ver-main p { font-size: 11.5px; color: var(--text-secondary); margin-top: 1px; }
    .aab-ver-main time { font-size: 10.5px; color: var(--text-muted); }

    .aab-cmt { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
    .aab-cmt:last-child { margin-bottom: 0; }
    .aab-cmt-bubble { font-size: 12px; color: var(--text-secondary); background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 11px; padding: 8px 11px; line-height: 1.45; }
    .aab-cmt.pro .aab-cmt-bubble { background: rgba(99,102,241,.08); border-color: rgba(99,102,241,.3); }
    .aab-cmt-who { font-size: 10.5px; color: var(--text-muted); font-weight: 700; }
    .aab-cmt.pro .aab-cmt-who { text-align: right; }
    .aab-cmt.pro .aab-cmt-bubble { margin-left: 22px; }
    .aab-cmt.client .aab-cmt-bubble { margin-right: 22px; }

    .aab-sugg { font-size: 12px; color: var(--text-secondary); padding: 8px 0 8px 24px; position: relative; border-bottom: 1px dashed var(--border-color); line-height: 1.45; }
    .aab-sugg:last-child { border-bottom: none; }
    .aab-sugg::before { content: '✨'; position: absolute; left: 2px; top: 7px; font-size: 12px; }

    .aab-actions { display: flex; flex-direction: column; gap: 9px; }
    .aab-btn { border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
    .aab-btn.ghost { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary); }
    .aab-btn svg { width: 16px; height: 16px; }

    @media (max-width: 920px) { .aab-grid { grid-template-columns: minmax(0,1fr); } }
</style>
@endpush

@section('content')
<div class="aab">
    <span class="aab-phase">🤝 PHASE 2 <b>· Collaboration & Negotiation</b></span>

    {{-- Negotiation cycle --}}
    <div class="aab-cycle">
        @foreach($cycle as $i => [$label, $state])
            <div class="aab-cyc {{ $state }}">
                <span class="aab-cyc-dot">@if($state==='done')✓@else{{ $i+1 }}@endif</span>
                <span class="aab-cyc-label">{{ $label }}</span>
            </div>
            @if(!$loop->last)<span class="aab-cyc-line"></span>@endif
        @endforeach
    </div>

    {{-- Status bar --}}
    <div class="aab-statusbar">
        <span class="aab-vtag">{{ $version }}</span>
        <span class="aab-status-txt">{{ $status }}</span>
        <div class="aab-prog">
            <div class="aab-bar"><i style="width: {{ $progress }}%"></i></div>
            <b>{{ $progress }}%</b>
        </div>
    </div>

    <div class="aab-grid">
        {{-- Clauses --}}
        <div class="aab-card">
            <div class="aab-sec-head">
                <h3>Agreement Draft · {{ $event }}</h3>
                <p>The tool built the agreement. Both parties refine each clause until everything is fair, accurate and agreed.</p>
            </div>
            <div class="aab-clauses">
                @foreach($clauses as $cl)
                    @php [$lbl, $col, $bg] = $statusMeta[$cl['status']]; @endphp
                    <div class="aab-clause {{ $cl['status']==='ai-suggested' ? 'is-suggested' : ($cl['status']==='disputed' ? 'is-disputed' : '') }}">
                        <div class="aab-clause-top">
                            <h5>{{ $cl['title'] }}</h5>
                            <span class="aab-chip" style="color: {{ $col }}; background: {{ $bg }};">{{ $lbl }}</span>
                        </div>
                        <div class="aab-clause-body">{{ $cl['body'] }}</div>
                        @if(!empty($cl['change']))
                            <div class="aab-change {{ $cl['status']==='edited' ? 'added' : '' }}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                {{ $cl['change'] }}
                            </div>
                        @endif
                        <div class="aab-clause-acts">
                            @if($cl['status']==='ai-suggested')
                                <button class="aab-mini accent">✓ Accept Suggestion</button>
                                <button class="aab-mini">Edit</button>
                            @elseif($cl['status']==='disputed')
                                <button class="aab-mini accent">Respond</button>
                                <button class="aab-mini">Edit Clause</button>
                            @else
                                <button class="aab-mini">Edit</button>
                                <button class="aab-mini">Comment</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="aab-side">
            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 1 .5 4"/><polyline points="3 16 3 11 8 11"/></svg> Version History</h4>
                @foreach($versions as $v)
                    <div class="aab-ver {{ !empty($v['current']) ? 'current' : '' }}">
                        <span class="aab-ver-dot"></span>
                        <div class="aab-ver-main">
                            <h6>{{ $v['v'] }} <span>· {{ $v['by'] }}</span></h6>
                            <p>{{ $v['note'] }}</p>
                            <time>{{ $v['time'] }}</time>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Negotiation Thread</h4>
                @foreach($comments as $c)
                    <div class="aab-cmt {{ $c['side'] }}">
                        <span class="aab-cmt-who">{{ $c['who'] }} · {{ $c['time'] }}</span>
                        <div class="aab-cmt-bubble">{{ $c['msg'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/></svg> Negotiation Assistant</h4>
                @foreach($ai_suggestions as $s)
                    <div class="aab-sugg">{{ $s }}</div>
                @endforeach
            </div>

            <div class="aab-actions">
                <a href="{{ route('ai-agreement.finalize') }}" class="aab-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Finalize &amp; Proceed to Signing</a>
                <button class="aab-btn ghost"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Revision to Client</button>
            </div>
        </aside>
    </div>
</div>
@endsection
