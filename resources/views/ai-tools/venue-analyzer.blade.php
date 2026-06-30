@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Venue Analyzer')
@section('page-title', 'AI Venue Analyzer')
@section('page-subtitle', 'Score a venue, map the layout, spot the gaps')

{{-- AI Venue Analyzer (client). Venue score + intelligence summary + gap
     analysis + interactive layout map + required vendors. Representative. --}}

@push('styles')
<style>
    .va { --va: #16a34a; }
    .va-hero { display: flex; align-items: center; gap: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 18px; margin-bottom: 16px; flex-wrap: wrap; }
    .va-hero h2 { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .va-hero .a { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .va-kpis { margin-left: auto; display: flex; gap: 22px; }
    .va-kpi { text-align: center; } .va-kpi b { display: block; font-size: 20px; font-weight: 800; color: var(--va); } .va-kpi span { font-size: 10.5px; color: var(--text-muted); }

    .va-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
    .va-sum { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 13px 15px; }
    .va-sum b { font-size: 18px; font-weight: 800; color: var(--text-primary); } .va-sum .l { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .va-sbar { height: 5px; border-radius: 999px; background: var(--border-color); margin-top: 8px; overflow: hidden; } .va-sbar > i { display: block; height: 100%; background: var(--va); }

    .va-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    .va-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .va-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14.5px; font-weight: 800; color: var(--text-primary); }

    /* gap analysis */
    .va-gap { display: grid; grid-template-columns: 150px 1fr 1fr; gap: 12px; padding: 11px 16px; border-bottom: 1px solid var(--border-color); font-size: 12px; align-items: start; }
    .va-gap:last-child { border-bottom: none; }
    .va-gap .cat { font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 7px; }
    .va-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; } .va-dot.good { background: #16a34a; } .va-dot.warn { background: #d97706; }
    .va-gap .d { color: var(--text-muted); } .va-gap .r { color: var(--text-secondary); font-weight: 600; }

    /* map */
    .va-map { position: relative; height: 300px; margin: 14px; border-radius: 12px; overflow: hidden;
        background: linear-gradient(160deg, #d9ead3, #c7e0bf); }
    [data-theme="dark"] .va-map { background: linear-gradient(160deg, #1e3a2a, #16271d); }
    .va-zone { position: absolute; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .va-zone i { width: 14px; height: 14px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); box-shadow: 0 2px 6px rgba(0,0,0,.3); }
    .va-zone span { font-size: 10px; font-weight: 800; color: #0f1b35; background: rgba(255,255,255,.85); padding: 2px 7px; border-radius: 6px; white-space: nowrap; }

    .va-vendors { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 14px 16px; }
    .va-vd { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px; text-align: center; }
    .va-vd .e { font-size: 22px; } .va-vd span { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-top: 5px; }

    .va-rail { display: flex; flex-direction: column; gap: 16px; }
    .va-pan { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .va-pan h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .va-alert { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .va-alert:last-child { border-bottom: none; } .va-alert::before { content: '⚠️'; position: absolute; left: 0; top: 7px; font-size: 11px; }
    .va-hc { display: flex; justify-content: space-between; font-size: 12.5px; padding: 7px 0; border-bottom: 1px dashed var(--border-color); }
    .va-hc:last-child { border-bottom: none; } .va-hc b { color: var(--text-primary); font-weight: 800; }
    .va-score-ring { text-align: center; } .va-score-ring b { font-size: 30px; font-weight: 800; color: var(--va); } .va-score-ring span { font-size: 11px; color: var(--text-muted); }

    @media (max-width: 1000px) { .va-grid { grid-template-columns: minmax(0,1fr); } .va-summary { grid-template-columns: 1fr 1fr; } .va-vendors { grid-template-columns: 1fr 1fr; } .va-gap { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="va">
    <div class="va-hero">
        <div>
            <h2>🏛 {{ $venue['name'] }}</h2>
            <div class="a">📍 {{ $venue['address'] }}</div>
        </div>
        <div class="va-kpis">
            <div class="va-kpi"><b>{{ $venue['score'] }}%</b><span>Venue Score</span></div>
            <div class="va-kpi"><b>{{ $venue['capacity'] }}</b><span>Capacity</span></div>
            <div class="va-kpi"><b>{{ $venue['compatibility'] }}%</b><span>Compatibility</span></div>
        </div>
    </div>

    <div class="va-summary">
        @foreach($summary as [$lbl, $val, $tone])
            <div class="va-sum"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div><div class="va-sbar"><i style="width: {{ $val }}"></i></div></div>
        @endforeach
    </div>

    <div class="va-grid">
        <div>
            {{-- Interactive map --}}
            <div class="va-card">
                <div class="va-card-hd">🗺 Interactive Venue Map</div>
                <div class="va-map">
                    @foreach($zones as [$label, $x, $y, $color])
                        <div class="va-zone" style="left: {{ $x }}%; top: {{ $y }}%;"><i style="background: {{ $color }};"></i><span>{{ $label }}</span></div>
                    @endforeach
                </div>
            </div>

            {{-- Gap analysis --}}
            <div class="va-card">
                <div class="va-card-hd">🔍 AI Gap Analysis</div>
                @foreach($gaps as [$cat, $tone, $detail, $rec])
                    <div class="va-gap">
                        <span class="cat"><span class="va-dot {{ $tone }}"></span> {{ $cat }}</span>
                        <span class="d">{{ $detail }}</span>
                        <span class="r">✨ {{ $rec }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Required vendors --}}
            <div class="va-card">
                <div class="va-card-hd">🧩 Required Vendors & Services</div>
                <div class="va-vendors">
                    @foreach($vendors as [$name, $emoji])
                        <div class="va-vd"><div class="e">{{ $emoji }}</div><span>{{ $name }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="va-rail">
            <div class="va-pan va-score-ring">
                <h4 style="justify-content:center;">Overall Venue Score</h4>
                <b>{{ $venue['score'] }}</b><span>/ 100 — Excellent</span>
            </div>
            <div class="va-pan">
                <h4>🚨 AI Alerts</h4>
                @foreach($alerts as $a)<div class="va-alert">{{ $a }}</div>@endforeach
            </div>
            <div class="va-pan">
                <h4>💸 Hidden Costs to Plan For</h4>
                @foreach($hidden_costs as [$item, $cost])<div class="va-hc"><span>{{ $item }}</span><b>{{ $cost }}</b></div>@endforeach
            </div>
        </aside>
    </div>
</div>
@endsection
