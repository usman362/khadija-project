@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Guest Capacity Planner')
@section('page-title', 'AI Guest Capacity Planner')
@section('page-subtitle', 'Comfort, flow and legal capacity for your guest count')

{{-- AI Guest Capacity Planner (client). Capacity stats + heatmap simulation +
     capacity insights + what-if sliders. Representative data. --}}

@push('styles')
<style>
    .gc { --gc: #0ea5e9; }
    .gc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .gc-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .gc-stat b { display: block; font-size: 25px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .gc-stat.good b { color: #16a34a; }
    .gc-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .gc-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 18px; align-items: start; }
    .gc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .gc-card-hd { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px; }
    .gc-card-hd h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .gc-legend { display: flex; gap: 12px; }
    .gc-leg { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: var(--text-secondary); }
    .gc-leg i { width: 10px; height: 10px; border-radius: 3px; }

    .gc-map { position: relative; height: 300px; margin: 14px; border-radius: 12px; overflow: hidden; background: #0e1726;
        background-image:
            radial-gradient(circle at 70% 78%, rgba(220,38,38,.55), transparent 22%),
            radial-gradient(circle at 45% 50%, rgba(234,179,8,.45), transparent 26%),
            radial-gradient(circle at 25% 30%, rgba(22,163,74,.45), transparent 26%),
            linear-gradient(160deg, #16271d, #0e1726); }
    .gc-table { position: absolute; width: 30px; height: 30px; border-radius: 50%; border: 2px dashed rgba(255,255,255,.6); transform: translate(-50%,-50%); }
    .gc-stage { position: absolute; left: 50%; top: 8%; transform: translateX(-50%); font-size: 10px; font-weight: 800; color: #fff; background: rgba(255,255,255,.18); padding: 3px 14px; border-radius: 6px; }

    .gc-ins { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; padding: 14px 16px; }
    .gc-in { border: 1px solid var(--border-color); border-radius: 11px; padding: 11px 13px; }
    .gc-in h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .gc-in .s { font-size: 11px; font-weight: 800; margin-top: 3px; } .gc-in .s.good { color: #16a34a; } .gc-in .s.warn { color: #d97706; }

    .gc-bars { padding: 16px; }
    .gc-cap { margin-bottom: 14px; }
    .gc-cap-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; } .gc-cap-top b { font-weight: 800; color: var(--text-primary); } .gc-cap-top span { color: var(--text-muted); }
    .gc-track { height: 12px; border-radius: 999px; background: var(--border-color); position: relative; overflow: hidden; }
    .gc-track > i { position: absolute; left: 0; top: 0; height: 100%; border-radius: 999px; }
    .gc-marker { position: absolute; top: -4px; width: 2px; height: 20px; background: var(--text-primary); }

    .gc-rail { display: flex; flex-direction: column; gap: 16px; }
    .gc-pan { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .gc-pan h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .gc-slider { margin-bottom: 14px; }
    .gc-slider label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; } .gc-slider label b { color: var(--gc); }
    .gc-slider input { width: 100%; accent-color: var(--gc); }
    .gc-tip { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .gc-tip:last-child { border-bottom: none; } .gc-tip::before { content: '✨'; position: absolute; left: 2px; top: 7px; }

    @media (max-width: 1000px) { .gc-grid { grid-template-columns: minmax(0,1fr); } .gc-stats, .gc-ins { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="gc">
    <div class="gc-stats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="gc-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="gc-grid">
        <div>
            {{-- Heatmap --}}
            <div class="gc-card">
                <div class="gc-card-hd">
                    <h3>🗺 Venue Layout &amp; Heatmap Simulation</h3>
                    <div class="gc-legend">
                        <span class="gc-leg"><i style="background:#16a34a;"></i> Low</span>
                        <span class="gc-leg"><i style="background:#eab308;"></i> Medium</span>
                        <span class="gc-leg"><i style="background:#dc2626;"></i> High</span>
                    </div>
                </div>
                <div class="gc-map">
                    <span class="gc-stage">STAGE</span>
                    @foreach($tables as [$x, $y])<span class="gc-table" style="left: {{ $x }}%; top: {{ $y }}%;"></span>@endforeach
                </div>
            </div>

            {{-- Insights --}}
            <div class="gc-card">
                <div class="gc-card-hd"><h3>📊 Capacity Insights</h3></div>
                <div class="gc-ins">
                    @foreach($insights as [$name, $rating, $tone])
                        <div class="gc-in"><h6>{{ $name }}</h6><div class="s {{ $tone }}">{{ $rating }}</div></div>
                    @endforeach
                </div>
            </div>

            {{-- Legal vs comfort --}}
            <div class="gc-card">
                <div class="gc-card-hd"><h3>⚖️ Legal vs Comfort Capacity</h3></div>
                <div class="gc-bars">
                    <div class="gc-cap">
                        <div class="gc-cap-top"><b>Your event: {{ $capacity['expected'] }} guests</b><span>Comfort {{ $capacity['comfort'] }} · Legal {{ $capacity['legal'] }}</span></div>
                        <div class="gc-track">
                            <i style="width: {{ round($capacity['comfort']/$capacity['legal']*100) }}%; background: rgba(22,163,74,.4);"></i>
                            <i style="width: {{ round($capacity['expected']/$capacity['legal']*100) }}%; background: #16a34a;"></i>
                            <span class="gc-marker" style="left: {{ round($capacity['comfort']/$capacity['legal']*100) }}%;" title="Comfort"></span>
                        </div>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted);">You’re <b style="color:#16a34a;">comfortably under</b> capacity — room for {{ $capacity['comfort'] - $capacity['expected'] }} more guests before it feels tight.</p>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="gc-rail">
            <div class="gc-pan">
                <h4>🔧 What-If Adjustments</h4>
                <div class="gc-slider"><label>Guest Count <b>185</b></label><input type="range" min="50" max="280" value="185"></div>
                <div class="gc-slider"><label>Event Duration <b>5 hrs</b></label><input type="range" min="2" max="10" value="5"></div>
                <div class="gc-slider"><label>Crowd Profile <b>Balanced</b></label><input type="range" min="0" max="2" value="1"></div>
                <div class="gc-slider"><label>Service Level <b>Seated</b></label><input type="range" min="0" max="2" value="2"></div>
            </div>
            <div class="gc-pan">
                <h4>✨ AI Tips</h4>
                @foreach($tips as $t)<div class="gc-tip">{{ $t }}</div>@endforeach
            </div>
        </aside>
    </div>
</div>
@endsection
