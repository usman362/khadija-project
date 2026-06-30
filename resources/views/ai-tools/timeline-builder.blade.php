@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Timeline Builder')
@section('page-title', 'AI Timeline Builder')
@section('page-subtitle', 'A conflict-free run-of-show for your event day')

{{-- AI Timeline Builder (client). Event-day run-of-show across vendor tracks
     with buffers + conflict detection. Representative data. --}}

@push('styles')
<style>
    .tb { --tb: #7c3aed; }
    .tb-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
    .tb-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .tb-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .tb-stat .lbl { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .tb-stat .sub { font-size: 10.5px; font-weight: 800; margin-top: 4px; }
    .tb-stat.good .sub { color: #16a34a; } .tb-stat.warn .sub { color: #d97706; }

    .tb-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; margin-bottom: 18px; }
    .tb-card h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }

    .tb-hours { display: grid; grid-template-columns: 110px 1fr; }
    .tb-hours .sp { }
    .tb-hours .hrs { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; }
    .tb-hours .hrs span { font-size: 11px; font-weight: 700; color: var(--text-muted); text-align: left; border-left: 1px solid var(--border-color); padding: 0 0 6px 6px; }

    .tb-track { display: grid; grid-template-columns: 110px 1fr; align-items: center; border-top: 1px solid var(--border-color); }
    .tb-tname { font-size: 12.5px; font-weight: 800; color: var(--text-primary); padding: 12px 8px 12px 0; display: flex; align-items: center; gap: 7px; }
    .tb-tname i { width: 9px; height: 9px; border-radius: 3px; flex-shrink: 0; }
    .tb-lane { position: relative; height: 56px; }
    .tb-lane::before { content: ''; position: absolute; inset: 0; background-image: repeating-linear-gradient(90deg, var(--border-color) 0 1px, transparent 1px calc(100%/9)); opacity: .5; }
    .tb-block { position: absolute; top: 11px; height: 34px; border-radius: 8px; display: flex; align-items: center; padding: 0 9px; font-size: 11px; font-weight: 800; color: #fff; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

    .tb-conflict { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--text-secondary); padding: 9px 0; border-bottom: 1px dashed var(--border-color); line-height: 1.5; }
    .tb-conflict:last-child { border-bottom: none; }
    .tb-conflict .w { width: 18px; height: 18px; border-radius: 50%; background: #d97706; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }

    .tb-acts { display: flex; gap: 10px; flex-wrap: wrap; }
    .tb-btn { font-size: 12.5px; font-weight: 800; border-radius: 10px; padding: 10px 16px; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); }
    .tb-btn.primary { border: none; background: linear-gradient(135deg, var(--tb), #6d28d9); color: #fff; }

    @media (max-width: 1000px) { .tb-stats { grid-template-columns: repeat(2,1fr); } .tb-card { overflow-x: auto; } }
</style>
@endpush

@section('content')
<div class="tb">
    <div class="tb-stats">
        @foreach($stats as [$lbl, $val, $sub, $tone])
            <div class="tb-stat {{ $tone }}"><b>{{ $val }}</b><div class="lbl">{{ $lbl }}</div><div class="sub">{{ $sub }}</div></div>
        @endforeach
    </div>

    <div class="tb-card">
        <h3>📅 Event Day Timeline · Sat, June 14</h3>
        <div style="min-width:680px;">
            <div class="tb-hours">
                <div class="sp"></div>
                <div class="hrs">@foreach($hours as $h)<span>{{ $h }}</span>@endforeach</div>
            </div>
            @foreach($tracks as [$name, $color, $blocks])
                <div class="tb-track">
                    <div class="tb-tname"><i style="background: {{ $color }};"></i> {{ $name }}</div>
                    <div class="tb-lane">
                        @foreach($blocks as [$label, $start, $width])
                            <div class="tb-block" style="left: {{ $start }}%; width: {{ $width }}%; background: {{ $color }};" title="{{ $label }}">{{ $label }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="tb-card">
        <h3>⚠️ Conflicts Detected (2)</h3>
        @foreach($conflicts as $c)
            <div class="tb-conflict"><span class="w">!</span> {{ $c }}</div>
        @endforeach
    </div>

    <div class="tb-acts">
        <span class="tb-btn primary">⚡ Auto-Schedule</span>
        <span class="tb-btn">🔀 What-If Simulator</span>
        <span class="tb-btn">⬇ Export Timeline</span>
        <span class="tb-btn">▶ Start Live Event Mode</span>
    </div>
</div>
@endsection
