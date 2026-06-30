@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Availability Optimizer')
@section('page-title', 'AI Availability Optimizer')
@section('page-subtitle', 'Fill the gaps, tighten turnarounds, lift revenue')

{{-- AI Availability Optimizer (professional). Reads the calendar to surface
     open slots, tight turnarounds and revenue lift. Representative data. --}}

@push('styles')
<style>
    .ao { --ao: #6366f1; --ao-strong: #4f46e5;
          --c-confirmed:#16a34a; --c-tight:#d97706; --c-open:#6366f1; --c-personal:#64748b; }
    .ao-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
    .ao-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .ao-stat b { display: block; font-size: 24px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .ao-stat .lbl { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .ao-stat .sub { font-size: 10.5px; font-weight: 800; margin-top: 4px; }
    .ao-stat.good .sub { color: #16a34a; } .ao-stat.warn .sub { color: #d97706; }

    .ao-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; margin-bottom: 18px; }
    .ao-card-hd { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; }
    .ao-card-hd h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .ao-legend { display: flex; gap: 12px; flex-wrap: wrap; }
    .ao-leg { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--text-secondary); }
    .ao-leg i { width: 10px; height: 10px; border-radius: 3px; }

    .ao-cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: var(--border-color); padding: 1px; }
    .ao-col { background: var(--bg-card); min-height: 230px; padding: 8px 7px; }
    .ao-col-hd { font-size: 11.5px; font-weight: 800; color: var(--text-secondary); text-align: center; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 8px; }
    .ao-ev { border-radius: 8px; padding: 7px 8px; margin-bottom: 7px; border-left: 3px solid; }
    .ao-ev .t { font-size: 9.5px; color: var(--text-muted); font-weight: 700; }
    .ao-ev .n { font-size: 11.5px; font-weight: 800; color: var(--text-primary); line-height: 1.25; margin-top: 1px; }
    .ao-ev.confirmed { background: rgba(22,163,74,.10); border-color: var(--c-confirmed); }
    .ao-ev.tight { background: rgba(217,119,6,.10); border-color: var(--c-tight); }
    .ao-ev.open { background: rgba(99,102,241,.10); border-color: var(--c-open); }
    .ao-ev.personal { background: rgba(100,116,139,.12); border-color: var(--c-personal); }

    .ao-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .ao-panel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .ao-panel h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 13px; }

    .ao-opp { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); }
    .ao-opp:last-child { border-bottom: none; }
    .ao-opp-main { flex: 1; min-width: 0; } .ao-opp-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .ao-opp-main span { font-size: 11px; color: var(--text-muted); }
    .ao-opp .val { font-size: 12.5px; font-weight: 800; color: #16a34a; }
    .ao-opp .mt { font-size: 9.5px; font-weight: 800; color: var(--ao); background: rgba(99,102,241,.12); border-radius: 999px; padding: 2px 7px; }

    .ao-bars { display: flex; align-items: flex-end; gap: 7px; height: 100px; margin-bottom: 8px; }
    .ao-bars > i { flex: 1; border-radius: 5px 5px 0 0; background: linear-gradient(var(--ao), var(--ao-strong)); min-height: 6px; }
    .ao-fore-total { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .ao-fore-total span { font-size: 11px; color: var(--text-muted); font-weight: 600; }

    .ao-sugg { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .ao-sugg:last-child { border-bottom: none; } .ao-sugg::before { content: '✨'; position: absolute; left: 2px; top: 7px; }

    @media (max-width: 1000px) { .ao-stats { grid-template-columns: repeat(2,1fr); } .ao-3 { grid-template-columns: 1fr; } .ao-cal { overflow-x: auto; } }
</style>
@endpush

@section('content')
<div class="ao">
    {{-- Stat tiles --}}
    <div class="ao-stats">
        @foreach($stats as [$lbl, $val, $sub, $tone])
            <div class="ao-stat {{ $tone }}">
                <b>{{ $val }}</b>
                <div class="lbl">{{ $lbl }}</div>
                <div class="sub">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    {{-- Calendar --}}
    <div class="ao-card">
        <div class="ao-card-hd">
            <h3>This Week · May 18 – 24</h3>
            <div class="ao-legend">
                @foreach($legend as [$type, $label])
                    <span class="ao-leg"><i style="background: var(--c-{{ $type }});"></i> {{ $label }}</span>
                @endforeach
            </div>
        </div>
        <div class="ao-cal">
            @foreach($days as $di => $day)
                <div class="ao-col">
                    <div class="ao-col-hd">{{ $day }}</div>
                    @foreach($events as [$ed, $type, $time, $title])
                        @if($ed === $di)
                            <div class="ao-ev {{ $type }}"><div class="t">{{ $time }}</div><div class="n">{{ $title }}</div></div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Panels --}}
    <div class="ao-3">
        <div class="ao-panel">
            <h4>🎯 Upcoming Opportunities</h4>
            @foreach($opportunities as [$title, $when, $val, $match])
                <div class="ao-opp">
                    <div class="ao-opp-main"><h6>{{ $title }}</h6><span>{{ $when }}</span></div>
                    <span class="val">{{ $val }}</span>
                    <span class="mt">{{ $match }}%</span>
                </div>
            @endforeach
        </div>

        <div class="ao-panel">
            <h4>📈 Revenue Forecast</h4>
            <div class="ao-bars">
                @foreach($forecast['bars'] as $b)<i style="height: {{ $b }}%"></i>@endforeach
            </div>
            <div class="ao-fore-total">{{ $forecast['total'] }} <span>projected next 90 days</span></div>
        </div>

        <div class="ao-panel">
            <h4>✨ Smart Booking Suggestions</h4>
            @foreach($suggestions as $s)
                <div class="ao-sugg">{{ $s }}</div>
            @endforeach
        </div>
    </div>
</div>
@endsection
