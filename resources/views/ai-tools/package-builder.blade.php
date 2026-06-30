@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Package Builder')
@section('page-title', 'AI Package Builder')
@section('page-subtitle', 'Build, price & compare your service packages')

{{-- AI Package Builder (professional). Tiered packages + margins + comparison +
     AI suggestions. Representative data. --}}

@push('styles')
<style>
    .pb { --pb: #6366f1; --pb-strong: #4f46e5; }
    .pb-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .pb-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .pb-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .pb-stat.good b { color: #16a34a; } .pb-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .pb-tiers { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
    .pb-tier { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; position: relative; }
    .pb-tier.best { border-color: var(--pb); box-shadow: 0 0 0 1px var(--pb); }
    .pb-best { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 800; color: #fff; background: var(--pb); padding: 3px 12px; border-radius: 999px; white-space: nowrap; }
    .pb-tname { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pb-tname i { width: 12px; height: 12px; border-radius: 50%; }
    .pb-price { font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 8px 0 2px; } .pb-price small { font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .pb-sub { font-size: 11.5px; color: var(--text-muted); margin-bottom: 12px; }
    .pb-marg { display: inline-flex; gap: 6px; font-size: 10.5px; font-weight: 800; color: #16a34a; background: rgba(22,163,74,.1); padding: 3px 9px; border-radius: 999px; margin-bottom: 12px; align-self: flex-start; }
    .pb-inc { list-style: none; flex: 1; margin-bottom: 12px; }
    .pb-inc li { font-size: 12px; color: var(--text-secondary); padding: 4px 0 4px 18px; position: relative; } .pb-inc li::before { content: '✓'; position: absolute; left: 0; color: #16a34a; font-weight: 800; }
    .pb-addon { font-size: 10.5px; color: var(--text-muted); padding: 2px 0; }
    .pb-btn { border: none; border-radius: 10px; padding: 10px; font-size: 12.5px; font-weight: 800; cursor: pointer; margin-top: 8px; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary); }
    .pb-tier.best .pb-btn, .pb-btn.primary { background: linear-gradient(135deg, var(--pb), var(--pb-strong)); color: #fff; border: none; }

    .pb-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .pb-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .pb-comp { width: 100%; border-collapse: collapse; }
    .pb-comp th, .pb-comp td { padding: 10px 14px; font-size: 12.5px; text-align: center; border-bottom: 1px solid var(--border-color); }
    .pb-comp th { font-weight: 800; color: var(--text-primary); } .pb-comp th:first-child, .pb-comp td:first-child { text-align: left; color: var(--text-secondary); font-weight: 700; }
    .pb-comp td { color: var(--text-secondary); }

    .pb-sugg { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; padding: 9px 16px 9px 38px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .pb-sugg:last-child { border-bottom: none; } .pb-sugg::before { content: '✨'; position: absolute; left: 16px; top: 9px; }

    @media (max-width: 1000px) { .pb-stats, .pb-tiers { grid-template-columns: 1fr 1fr; } .pb-card { overflow-x: auto; } }
</style>
@endpush

@section('content')
<div class="pb">
    <div class="pb-stats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="pb-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="pb-tiers">
        @foreach($tiers as [$name, $price, $margin, $hours, $color, $best, $includes, $addons])
            <div class="pb-tier {{ $best ? 'best' : '' }}">
                @if($best)<span class="pb-best">★ Recommended · Best Value</span>@endif
                <div class="pb-tname"><i style="background: {{ $color }};"></i> {{ $name }}</div>
                <div class="pb-price">${{ number_format($price) }} <small>/ {{ $hours }}</small></div>
                <div class="pb-sub">Most popular for {{ strtolower($name) }}-tier clients</div>
                <span class="pb-marg">📈 {{ $margin }} margin</span>
                <ul class="pb-inc">@foreach($includes as $inc)<li>{{ $inc }}</li>@endforeach</ul>
                <div>@foreach($addons as $a)<div class="pb-addon">{{ $a }}</div>@endforeach</div>
                <button class="pb-btn {{ $best ? 'primary' : '' }}">Customize Package</button>
            </div>
        @endforeach
    </div>

    <div class="pb-card">
        <div class="pb-card-hd">📊 Package Comparison Overview</div>
        <table class="pb-comp">
            <thead><tr><th>Feature</th>@foreach($tiers as $t)<th>{{ $t[0] }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach($compare as [$feature, $vals])
                    <tr><td>{{ $feature }}</td>@foreach($vals as $v)<td>{{ $v }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pb-card">
        <div class="pb-card-hd">✨ AI Package Suggestions</div>
        @foreach($suggestions as $s)<div class="pb-sugg">{{ $s }}</div>@endforeach
    </div>
</div>
@endsection
