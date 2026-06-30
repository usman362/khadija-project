@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Upsell Assistant')
@section('page-title', 'AI Upsell Assistant')
@section('page-subtitle', 'Spot the right add-ons to grow each booking')

@push('styles')
<style>
    .us { --us: #2563eb; }
    .us-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .us-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .us-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; } .us-stat.good b { color: #16a34a; } .us-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .us-grid { display: grid; grid-template-columns: 320px minmax(0,1fr); gap: 18px; align-items: start; }
    .us-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .us-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }
    .us-kv { display: flex; justify-content: space-between; font-size: 13px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); } .us-kv:last-of-type { border-bottom: none; } .us-kv span { color: var(--text-muted); } .us-kv b { color: var(--text-primary); font-weight: 800; }
    .us-toggle { display: flex; gap: 8px; margin: 14px 0; } .us-tg { flex: 1; text-align: center; font-size: 11.5px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; cursor: pointer; color: var(--text-secondary); } .us-tg.on { background: var(--us); border-color: var(--us); color: #fff; }
    .us-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--us), #1d4ed8); cursor: pointer; }
    .us-add { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); } .us-add:last-child { border-bottom: none; }
    .us-chk { width: 20px; height: 20px; border-radius: 6px; border: 2px solid var(--border-color); flex-shrink: 0; }
    .us-add.sel .us-chk { background: var(--us); border-color: var(--us); }
    .us-add-main { flex: 1; min-width: 0; } .us-add-main h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); } .us-add-main span { font-size: 11px; color: var(--text-muted); }
    .us-price { font-size: 14px; font-weight: 800; color: #16a34a; } .us-like { font-size: 10px; font-weight: 800; color: var(--us); background: rgba(37,99,235,.1); padding: 2px 8px; border-radius: 999px; }
    .us-moment { font-size: 12px; color: var(--text-secondary); line-height: 1.5; background: rgba(37,99,235,.07); border: 1px dashed var(--us); border-radius: 10px; padding: 11px; margin-top: 14px; }
    @media (max-width: 1000px) { .us-grid { grid-template-columns: minmax(0,1fr); } .us-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="us">
    <div class="us-stats">@foreach($stats as [$lbl, $val, $tone])<div class="us-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="us-grid">
        <div class="us-card">
            <h3>📦 Current Booking</h3>
            <div class="us-kv"><span>Client</span><b>{{ $booking['client'] }}</b></div>
            <div class="us-kv"><span>Event</span><b>{{ $booking['event'] }}</b></div>
            <div class="us-kv"><span>Booked Package</span><b>{{ $booking['package'] }}</b></div>
            <div class="us-kv"><span>Details</span><b>{{ $booking['guests'] }}</b></div>
            <div class="us-toggle"><span class="us-tg on">Maximize revenue</span><span class="us-tg">Improve experience</span><span class="us-tg">Fill schedule</span></div>
            <button class="us-btn">🔍 Find Upsell Opportunities</button>
        </div>
        <div class="us-card">
            <h3>✨ Suggested Add-ons</h3>
            @foreach($addons as $i => [$name, $price, $like, $tag])
                <div class="us-add {{ $i < 2 ? 'sel' : '' }}">
                    <span class="us-chk"></span>
                    <div class="us-add-main"><h5>{{ $name }} @if($tag)<span class="us-like">{{ $tag }}</span>@endif</h5><span>{{ $like }}% likely to accept</span></div>
                    <span class="us-price">+${{ number_format($price) }}</span>
                </div>
            @endforeach
            <div class="us-moment">⏱ {{ $moment }}</div>
            <button class="us-btn" style="margin-top:14px;">+ Add Selected to Proposal</button>
        </div>
    </div>
</div>
@endsection
