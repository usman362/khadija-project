@extends($aiLayout ?? 'layouts.client')

@section('title', 'Agreement — Draft & Auto-Fill')
@section('page-title', 'Agreement Builder')
@section('page-subtitle', 'Draft Generation — auto-filled & required sections')

{{-- Agreement Builder · Draft. Every section is tagged AI-GENERATED (green —
     the auto-filled it from Phase-1 evidence) or REQUIRED (amber — the user must
     complete it). Confidence score reflects how complete the tool draft is. --}}

@push('styles')
<style>
    .aab { --aab: #6366f1; --aab-strong: #4f46e5; --ai: #16a34a; --req: #d97706; }
    .aab-phase { display: inline-flex; align-items: center; gap: 9px; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); color: #fff; font-weight: 800; font-size: 12.5px; letter-spacing: .4px; padding: 8px 16px; border-radius: 999px; }

    .aab-dhead { display: flex; align-items: center; gap: 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg,16px); padding: 16px 18px; margin: 16px 0 14px; flex-wrap: wrap; }
    .aab-dhead h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .aab-dhead p { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .aab-score { margin-left: auto; text-align: center; }
    .aab-score b { display: block; font-size: 26px; font-weight: 800; color: var(--ai); line-height: 1; }
    .aab-score span { font-size: 10.5px; color: var(--text-muted); font-weight: 700; }

    .aab-legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
    .aab-leg { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .aab-leg i { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }
    .aab-leg.ai i { background: var(--ai); }
    .aab-leg.req i { background: var(--req); }

    .aab-secgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .aab-sec { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); overflow: hidden; }
    .aab-sec-hd { display: flex; align-items: center; gap: 9px; padding: 12px 15px; border-bottom: 1px solid var(--border-color); }
    .aab-sec.ai .aab-sec-hd { background: rgba(22,163,74,.08); border-bottom-color: rgba(22,163,74,.25); }
    .aab-sec.req .aab-sec-hd { background: rgba(217,119,6,.08); border-bottom-color: rgba(217,119,6,.25); }
    .aab-sec-hd h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .aab-tag { margin-left: auto; font-size: 9.5px; font-weight: 800; letter-spacing: .3px; padding: 3px 8px; border-radius: 999px; color: #fff; white-space: nowrap; }
    .aab-sec.ai .aab-tag { background: var(--ai); }
    .aab-sec.req .aab-tag { background: var(--req); }
    .aab-sec-dot { width: 9px; height: 9px; border-radius: 50%; }
    .aab-sec.ai .aab-sec-dot { background: var(--ai); }
    .aab-sec.req .aab-sec-dot { background: var(--req); }
    .aab-sec-bd { padding: 13px 15px; }

    /* auto-filled fields */
    .aab-kv { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; padding: 5px 0; border-bottom: 1px dashed var(--border-color); }
    .aab-kv:last-child { border-bottom: none; }
    .aab-kv span { color: var(--text-muted); }
    .aab-kv b { color: var(--text-primary); font-weight: 700; text-align: right; }
    .aab-list { list-style: none; }
    .aab-list li { font-size: 12.5px; color: var(--text-secondary); padding: 4px 0 4px 18px; position: relative; }
    .aab-list li::before { content: '✓'; position: absolute; left: 0; color: var(--ai); font-weight: 800; }
    .aab-conf-note { margin-top: 10px; font-size: 11px; color: var(--ai); font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }

    /* required inputs */
    .aab-field { margin-bottom: 11px; }
    .aab-field:last-child { margin-bottom: 0; }
    .aab-field label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 5px; }
    .aab-field input, .aab-field select, .aab-field textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 11px; font-size: 13px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .aab-field input::placeholder, .aab-field textarea::placeholder { color: var(--text-muted); }
    .aab-field textarea { resize: vertical; min-height: 56px; }
    .aab-req-star { color: var(--req); }

    .aab-foot { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 18px; flex-wrap: wrap; }
    .aab-foot p { font-size: 12.5px; color: var(--text-muted); }
    .aab-foot p b { color: var(--req); }
    .aab-btn { border: none; border-radius: 12px; padding: 12px 22px; font-size: 14px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--aab), var(--aab-strong)); cursor: pointer; display: inline-flex; align-items: center; gap: 9px; text-decoration: none; }
    .aab-btn svg { width: 17px; height: 17px; }

    @media (max-width: 920px) { .aab-secgrid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="aab">
    <span class="aab-phase">📄 Draft Generation · Review &amp; Auto-Fill</span>

    {{-- Header with AI confidence score --}}
    <div class="aab-dhead">
        <div>
            <h3>Draft Agreement · {{ $event }}</h3>
            <p>The auto-filled the green sections from your evidence. Complete the amber sections, then continue.</p>
        </div>
        <div class="aab-score">
            <b>{{ $confidence }}%</b>
            <span>CONFIDENCE</span>
        </div>
    </div>

    <div class="aab-legend">
        <span class="aab-leg ai"><i></i> Auto-filled — auto-filled, editable</span>
        <span class="aab-leg req"><i></i> Requires Your Input</span>
    </div>

    <form onsubmit="return false;">
        <div class="aab-secgrid">
            @foreach($sections as $sec)
                <div class="aab-sec {{ $sec['type'] }}">
                    <div class="aab-sec-hd">
                        <span class="aab-sec-dot"></span>
                        <h5>{{ $sec['title'] }}</h5>
                        <span class="aab-tag">{{ $sec['type']==='ai' ? 'AUTO-FILLED' : 'REQUIRED' }}</span>
                    </div>
                    <div class="aab-sec-bd">
                        @if($sec['type']==='ai')
                            @if(!empty($sec['fields']))
                                @foreach($sec['fields'] as [$k, $v])
                                    <div class="aab-kv"><span>{{ $k }}</span><b>{{ $v }}</b></div>
                                @endforeach
                            @endif
                            @if(!empty($sec['list']))
                                <ul class="aab-list">
                                    @foreach($sec['list'] as $li)<li>{{ $li }}</li>@endforeach
                                </ul>
                            @endif
                            <span class="aab-conf-note">✓ Auto-filled · {{ $sec['conf'] }}% confidence</span>
                        @else
                            @foreach($sec['inputs'] as $field)
                                @php [$flabel, $ftype, $fval] = [$field[0], $field[1], $field[2] ?? '']; @endphp
                                <div class="aab-field">
                                    <label>{{ $flabel }} <span class="aab-req-star">*</span></label>
                                    @if($ftype==='textarea')
                                        <textarea placeholder="Enter {{ strtolower($flabel) }}…">{{ is_string($fval) ? $fval : '' }}</textarea>
                                    @elseif($ftype==='select')
                                        <select>
                                            <option value="">Select…</option>
                                            @foreach((array) $fval as $opt)<option>{{ $opt }}</option>@endforeach
                                        </select>
                                    @else
                                        <input type="{{ $ftype }}" value="{{ is_string($fval) ? $fval : '' }}" placeholder="Enter {{ strtolower($flabel) }}…">
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="aab-foot">
            <p><b>6 sections</b> need your input · 4 auto-filled</p>
            <a href="{{ route('ai-agreement.negotiate') }}" class="aab-btn">
                Continue to Negotiation
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </form>
</div>
@endsection
