@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Agreement — Execution')
@section('page-title', 'AI Agreement Builder')
@section('page-subtitle', 'Phase 3 — Execution & Finalization')

{{-- AI Agreement Builder · Phase 3. Finalize, sign and activate. Both parties
     receive a final, legally structured, signed document held in a secure,
     auditable archive. Representative data pending the signing/archive service. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
@endphp

@push('styles')
<style>
    .aab { --aab: #6366f1; --aab-strong: #4f46e5; }
    .aab-phase { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; font-weight: 800; font-size: 12.5px; letter-spacing: .4px; padding: 8px 16px; border-radius: 999px; }
    .aab-phase b { opacity: .85; font-weight: 700; }

    .aab-steps { display: flex; align-items: center; gap: 6px; margin: 18px 0 18px; flex-wrap: wrap; }
    .aab-step { display: flex; align-items: center; gap: 8px; flex: 1 1 0; min-width: 140px; }
    .aab-step-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; flex-shrink: 0; border: 2px solid var(--border-color); color: var(--text-muted); background: var(--bg-card); }
    .aab-step.done .aab-step-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .aab-step.active .aab-step-dot { background: var(--aab); border-color: var(--aab); color: #fff; box-shadow: 0 0 0 4px rgba(99,102,241,.18); }
    .aab-step-label { font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .aab-step.active .aab-step-label, .aab-step.done .aab-step-label { color: var(--text-primary); }
    .aab-step-line { flex: 1; height: 2px; background: var(--border-color); min-width: 10px; }

    /* ACTIVE banner */
    .aab-active { display: flex; align-items: center; gap: 16px; border-radius: var(--radius-lg,16px); padding: 20px 22px; margin-bottom: 18px; color: #fff;
        background: linear-gradient(120deg, #16a34a, #0f9d58); flex-wrap: wrap; }
    .aab-active-ic { width: 50px; height: 50px; border-radius: 14px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .aab-active-ic svg { width: 26px; height: 26px; }
    .aab-active h3 { color: #fff; font-size: 19px; font-weight: 800; }
    .aab-active p { color: rgba(255,255,255,.9); font-size: 13px; margin-top: 3px; }
    .aab-active .ref { margin-left: auto; text-align: right; }
    .aab-active .ref b { display: block; font-size: 16px; font-weight: 800; }
    .aab-active .ref span { font-size: 11.5px; color: rgba(255,255,255,.85); }

    .aab-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 20px; align-items: start; }
    .aab-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); }
    .aab-sec-head { padding: 16px 18px 4px; }
    .aab-sec-head h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .aab-sec-head p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

    /* readiness */
    .aab-ready { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 18px; padding: 12px 18px 18px; }
    .aab-ready-item { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--text-secondary); font-weight: 600; }
    .aab-ready-item .ck { width: 20px; height: 20px; border-radius: 50%; background: #16a34a; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }

    /* signatures */
    .aab-sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 6px 18px 18px; }
    .aab-sig { border: 1px solid var(--border-color); border-radius: 13px; padding: 16px; }
    .aab-sig.signed { border-color: rgba(22,163,74,.4); background: rgba(22,163,74,.04); }
    .aab-sig-party { font-size: 11px; font-weight: 800; letter-spacing: .3px; color: var(--text-muted); text-transform: uppercase; }
    .aab-sig-name { font-size: 17px; font-weight: 800; color: var(--text-primary); margin: 6px 0 10px; font-family: 'Brush Script MT', cursive; }
    .aab-sig-status { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: #16a34a; }
    .aab-sig-meta { font-size: 11px; color: var(--text-muted); margin-top: 8px; line-height: 1.5; }

    .aab-side { display: flex; flex-direction: column; gap: 16px; }
    .aab-side-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); padding: 16px; }
    .aab-side-card h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; display: flex; align-items: center; gap: 7px; }
    .aab-side-card h4 svg { width: 15px; height: 15px; color: var(--aab); fill: none; stroke: currentColor; stroke-width: 2; }

    .aab-kv { display: flex; justify-content: space-between; font-size: 12.5px; padding: 6px 0; border-bottom: 1px dashed var(--border-color); }
    .aab-kv:last-child { border-bottom: none; }
    .aab-kv span { color: var(--text-muted); }
    .aab-kv b { color: var(--text-primary); font-weight: 800; }
    .aab-statuspill { display: inline-flex; align-items: center; gap: 6px; background: rgba(22,163,74,.12); color: #16a34a; font-weight: 800; font-size: 12px; padding: 4px 11px; border-radius: 999px; }

    .aab-arch { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); }
    .aab-arch:last-child { border-bottom: none; }
    .aab-arch .ck { width: 18px; height: 18px; border-radius: 50%; background: rgba(99,102,241,.14); color: var(--aab); display: flex; align-items: center; justify-content: center; font-size: 10px; flex-shrink: 0; margin-top: 1px; }
    .aab-arch h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .aab-arch p { font-size: 11px; color: var(--text-muted); }

    .aab-btn { border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; width: 100%; }
    .aab-btn svg { width: 16px; height: 16px; }
    .aab-amend { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; margin-top: 4px; }
    .aab-amend a { color: var(--aab); font-weight: 700; text-decoration: none; }

    @media (max-width: 920px) { .aab-grid { grid-template-columns: minmax(0,1fr); } .aab-ready, .aab-sigs { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="aab">
    <span class="aab-phase">✅ PHASE 3 <b>· Execution & Finalization</b></span>

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

    {{-- ACTIVE banner --}}
    <div class="aab-active">
        <span class="aab-active-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
        <div>
            <h3>Agreement Active</h3>
            <p>Signed by both parties · {{ $event }} · Effective {{ $effective }}</p>
        </div>
        <div class="ref">
            <b>{{ $ref }}</b>
            <span>{{ $money($amount) }} · Event {{ $event_date }}</span>
        </div>
    </div>

    <div class="aab-grid">
        <div>
            {{-- Readiness --}}
            <div class="aab-card" style="margin-bottom:16px;">
                <div class="aab-sec-head">
                    <h3>Agreement Readiness Check</h3>
                    <p>All clauses were agreed by both parties before signing.</p>
                </div>
                <div class="aab-ready">
                    @foreach($readiness as $r)
                        <div class="aab-ready-item"><span class="ck">✓</span> {{ $r }}</div>
                    @endforeach
                </div>
            </div>

            {{-- Signatures --}}
            <div class="aab-card">
                <div class="aab-sec-head">
                    <h3>Electronic Signatures</h3>
                    <p>Both parties signed the final document — legally binding and timestamped.</p>
                </div>
                <div class="aab-sigs">
                    @foreach($signatures as $sig)
                        <div class="aab-sig {{ $sig['signed'] ? 'signed' : '' }}">
                            <div class="aab-sig-party">{{ $sig['party'] }}</div>
                            <div class="aab-sig-name">{{ $sig['name'] }}</div>
                            <span class="aab-sig-status">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Signed
                            </span>
                            <div class="aab-sig-meta">{{ $sig['time'] }}<br>IP {{ $sig['ip'] }} · verified</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="aab-side">
            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Agreement Status</h4>
                <div class="aab-kv"><span>Status</span><span class="aab-statuspill">● {{ $agreement_status }}</span></div>
                <div class="aab-kv"><span>Reference</span><b>{{ $ref }}</b></div>
                <div class="aab-kv"><span>Total Value</span><b>{{ $money($amount) }}</b></div>
                <div class="aab-kv"><span>Effective</span><b>{{ $effective }}</b></div>
                <div class="aab-kv"><span>Event Date</span><b>{{ $event_date }}</b></div>
            </div>

            <div class="aab-side-card">
                <h4><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Secure Archive & Compliance</h4>
                @foreach($archive as [$t, $d])
                    <div class="aab-arch">
                        <span class="ck">✓</span>
                        <div><h6>{{ $t }}</h6><p>{{ $d }}</p></div>
                    </div>
                @endforeach
            </div>

            <a href="#" class="aab-btn" onclick="alert('Demo: final signed PDF download');return false;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Final Agreement (PDF)
            </a>

            <div class="aab-side-card">
                <h4 style="margin-bottom:8px;"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg> Need Changes Later?</h4>
                <p class="aab-amend">Active agreements can be amended with mutual consent. Any amendment creates a new signed version and is kept in the audit trail. <a href="{{ route('ai-agreement.negotiate') }}">Start an amendment →</a></p>
            </div>
        </aside>
    </div>
</div>
@endsection
