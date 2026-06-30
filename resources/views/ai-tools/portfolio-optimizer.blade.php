@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Portfolio Optimizer')
@section('page-title', 'AI Portfolio Optimizer')
@section('page-subtitle', 'Lift your visibility, views and win-rate')

{{-- AI Portfolio Optimizer (professional). Profile audit + high-impact
     recommendations + gallery scoring + benchmark. Representative data. --}}

@push('styles')
<style>
    .po { --po: #6366f1; --po-strong: #4f46e5; }
    .po-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .po-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .po-stat b { display: block; font-size: 24px; font-weight: 800; color: #16a34a; line-height: 1; } .po-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .po-grid { display: grid; grid-template-columns: 270px minmax(0,1fr); gap: 18px; align-items: start; }
    .po-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .po-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14px; font-weight: 800; color: var(--text-primary); }

    .po-audit { padding: 6px 16px 14px; }
    .po-au { display: flex; align-items: center; gap: 9px; padding: 8px 0; font-size: 12.5px; color: var(--text-secondary); border-bottom: 1px dashed var(--border-color); }
    .po-au:last-child { border-bottom: none; }
    .po-au .ck { width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff; }
    .po-au.yes .ck { background: #16a34a; } .po-au.no .ck { background: var(--border-color); color: var(--text-muted); }
    .po-au.no { color: var(--text-muted); }

    .po-rec { display: flex; align-items: flex-start; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    .po-rec:last-child { border-bottom: none; }
    .po-rec-main { flex: 1; min-width: 0; } .po-rec-main h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); } .po-rec-main p { font-size: 12px; color: var(--text-muted); margin-top: 3px; line-height: 1.45; }
    .po-imp { font-size: 10px; font-weight: 800; color: #16a34a; background: rgba(22,163,74,.1); padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
    .po-pri { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 999px; }
    .po-pri.High { background: rgba(220,38,38,.12); color: #dc2626; } .po-pri.Medium { background: rgba(217,119,6,.14); color: #d97706; }
    .po-fix { border: none; border-radius: 8px; padding: 7px 13px; font-size: 11.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--po), var(--po-strong)); cursor: pointer; white-space: nowrap; }

    .po-gal { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 14px 16px; }
    .po-gi { position: relative; border-radius: 10px; overflow: hidden; }
    .po-gi img { width: 100%; height: 86px; object-fit: cover; display: block; }
    .po-gscore { position: absolute; right: 6px; top: 6px; font-size: 10px; font-weight: 800; color: #fff; padding: 2px 7px; border-radius: 999px; }

    .po-bench { padding: 14px 16px; }
    .po-bn { margin-bottom: 12px; } .po-bn:last-child { margin-bottom: 0; }
    .po-bn-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; } .po-bn-top span { color: var(--text-secondary); font-weight: 700; } .po-bn-top b { color: var(--text-primary); font-weight: 800; }
    .po-bbar { height: 8px; border-radius: 999px; background: var(--border-color); overflow: hidden; } .po-bbar > i { display: block; height: 100%; border-radius: 999px; background: var(--border-color); } .po-bn.me .po-bbar > i { background: linear-gradient(90deg, var(--po), var(--po-strong)); }

    .po-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .po-m { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 13px 15px; } .po-m b { font-size: 19px; font-weight: 800; color: var(--text-primary); } .po-m .l { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; } .po-m .s { font-size: 10.5px; color: #16a34a; font-weight: 700; margin-top: 2px; }

    @media (max-width: 1000px) { .po-grid { grid-template-columns: minmax(0,1fr); } .po-stats, .po-metrics { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="po">
    <div class="po-stats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="po-stat"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="po-grid">
        {{-- Audit --}}
        <div class="po-card">
            <div class="po-card-hd">✅ AI Portfolio Audit</div>
            <div class="po-audit">
                @foreach($audit as [$item, $done])
                    <div class="po-au {{ $done ? 'yes' : 'no' }}"><span class="ck">{{ $done ? '✓' : '○' }}</span> {{ $item }}</div>
                @endforeach
            </div>
        </div>

        {{-- Recommendations + gallery + benchmark --}}
        <div>
            <div class="po-card">
                <div class="po-card-hd">🚀 Top Recommendations</div>
                @foreach($recommendations as [$title, $desc, $pri, $impact])
                    <div class="po-rec">
                        <div class="po-rec-main">
                            <h5>{{ $title }} <span class="po-pri {{ $pri }}">{{ $pri }}</span></h5>
                            <p>{{ $desc }}</p>
                        </div>
                        <span class="po-imp">{{ $impact }}</span>
                        <button class="po-fix">Fix</button>
                    </div>
                @endforeach
            </div>

            <div class="po-card">
                <div class="po-card-hd">🖼 AI Gallery Optimizer</div>
                <div class="po-gal">
                    @foreach($gallery as [$img, $score])
                        @php $sc = $score >= 85 ? '#16a34a' : ($score >= 75 ? '#d97706' : '#dc2626'); @endphp
                        <div class="po-gi">
                            <span class="po-gscore" style="background: {{ $sc }};">{{ $score }}</span>
                            <img src="https://images.unsplash.com/{{ $img }}?w=240&q=65&auto=format&fit=crop" alt="" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="po-card">
                <div class="po-card-hd">📊 Competitor Benchmark</div>
                <div class="po-bench">
                    @foreach($benchmark as [$label, $val, $me])
                        <div class="po-bn {{ $me ? 'me' : '' }}">
                            <div class="po-bn-top"><span>{{ $label }}</span><b>{{ $val }}</b></div>
                            <div class="po-bbar"><i style="width: {{ $val }}%"></i></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="po-metrics">
        @foreach($metrics as [$lbl, $val, $sub])
            <div class="po-m"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div><div class="s">{{ $sub }}</div></div>
        @endforeach
    </div>
</div>
@endsection
