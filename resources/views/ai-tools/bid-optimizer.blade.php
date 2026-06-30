@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Bid Optimizer')
@section('page-title', 'AI Bid Optimizer')
@section('page-subtitle', 'The best bid for you — balanced against your margin')

@push('styles')
<style>
    .bo { --bo: #2563eb; }
    .bo-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .bo-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .bo-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; } .bo-stat.good b { color: #16a34a; } .bo-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .bo-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    .bo-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .bo-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }
    .bo-kv { display: flex; justify-content: space-between; font-size: 13px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); } .bo-kv:last-of-type { border-bottom: none; } .bo-kv span { color: var(--text-muted); } .bo-kv b { color: var(--text-primary); font-weight: 800; }
    .bo-toggle { display: flex; gap: 8px; margin: 14px 0; }
    .bo-tg { flex: 1; text-align: center; font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; cursor: pointer; color: var(--text-secondary); background: var(--bg-card); }
    .bo-tg.on { background: var(--bo); border-color: var(--bo); color: #fff; }
    .bo-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 14px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bo), #1d4ed8); cursor: pointer; }
    .bo-ring { text-align: center; padding: 6px 0 12px; }
    .bo-ring .num { font-size: 34px; font-weight: 800; color: var(--text-primary); } .bo-ring .sub { font-size: 12px; color: #16a34a; font-weight: 800; }
    .bo-scale { display: flex; height: 8px; border-radius: 999px; overflow: hidden; margin: 14px 0 6px; } .bo-scale i { height: 100%; } .bo-scale .l { background: #d97706; } .bo-scale .r { background: #dc2626; } .bo-scale .m { background: #16a34a; }
    .bo-row { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); }
    .bo-strat { font-size: 12px; color: var(--text-secondary); line-height: 1.5; background: rgba(37,99,235,.07); border: 1px dashed var(--bo); border-radius: 10px; padding: 11px; margin-top: 14px; }
    @media (max-width: 1000px) { .bo-grid { grid-template-columns: minmax(0,1fr); } .bo-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="bo">
    <div class="bo-stats">
        @foreach($stats as [$lbl, $val, $tone])<div class="bo-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach
    </div>
    <div class="bo-grid">
        <div class="bo-card">
            <h3>📄 The Gig You're Bidding On</h3>
            <div class="bo-kv"><span>Gig</span><b>{{ $gig['title'] }}</b></div>
            <div class="bo-kv"><span>Client Budget</span><b>{{ $gig['client_budget'] }}</b></div>
            <div class="bo-kv"><span>Event Date</span><b>{{ $gig['date'] }}</b></div>
            <div class="bo-kv"><span>Your Target Margin</span><b>{{ $gig['target_margin'] }}</b></div>
            <div class="bo-kv"><span>Urgency</span><b>{{ $gig['urgency'] }}</b></div>
            <div class="bo-toggle">
                <span class="bo-tg">Win the bid</span><span class="bo-tg">Protect margin</span><span class="bo-tg on">Balanced</span>
            </div>
            <button class="bo-btn">⚡ Optimize My Bid</button>
        </div>
        <div class="bo-card">
            <h3>🎯 Recommended Bid</h3>
            <div class="bo-ring"><div class="num">${{ number_format($bid['recommended']) }}</div><div class="sub">Great spot · {{ $bid['win'] }}% win · {{ $bid['margin'] }}% margin</div></div>
            <div class="bo-scale"><i class="l" style="width:33%"></i><i class="m" style="width:34%"></i><i class="r" style="width:33%"></i></div>
            <div class="bo-row"><span>{{ $bid['low']['label'] }} ${{ number_format($bid['low']['amount']) }}</span><span>{{ $bid['high']['label'] }} ${{ number_format($bid['high']['amount']) }}</span></div>
            <div class="bo-strat">💡 {{ $strategy }}</div>
        </div>
    </div>
</div>
@endsection
