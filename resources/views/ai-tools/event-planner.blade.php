@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Event Planner')
@section('page-title', 'AI Event Planner')
@section('page-subtitle', 'Your event, organised end-to-end')

{{-- AI Event Planner (client). Milestone checklist + progress + AI
     recommendations + marketplace suggestions + deadlines. Representative. --}}

@push('styles')
<style>
    .ep { --ep: #f97316; --ep-strong: #ea580c; }
    .ep-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 18px; align-items: start; }

    .ep-hero { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 18px; margin-bottom: 16px; }
    .ep-hero-top { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .ep-hero h2 { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .ep-hero .meta { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .ep-prog { margin-left: auto; text-align: center; }
    .ep-prog b { font-size: 22px; font-weight: 800; color: var(--ep); } .ep-prog span { display: block; font-size: 10.5px; color: var(--text-muted); }
    .ep-bar { height: 7px; border-radius: 999px; background: var(--border-color); overflow: hidden; margin-top: 12px; }
    .ep-bar > i { display: block; height: 100%; background: linear-gradient(90deg, var(--ep), var(--ep-strong)); }

    .ep-phases { display: flex; align-items: center; gap: 5px; margin-bottom: 16px; flex-wrap: wrap; }
    .ep-phase { display: flex; align-items: center; gap: 7px; }
    .ep-pdot { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; border: 2px solid var(--border-color); color: var(--text-muted); background: var(--bg-card); }
    .ep-phase.done .ep-pdot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .ep-phase.active .ep-pdot { background: var(--ep); border-color: var(--ep); color: #fff; }
    .ep-phase span { font-size: 11.5px; font-weight: 700; color: var(--text-secondary); }
    .ep-pline { width: 16px; height: 2px; background: var(--border-color); }

    .ep-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
    .ep-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .ep-task { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border-color); }
    .ep-task:last-child { border-bottom: none; }
    .ep-check { width: 20px; height: 20px; border-radius: 6px; border: 2px solid var(--border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #fff; }
    .ep-task.done .ep-check { background: #16a34a; border-color: #16a34a; }
    .ep-task.progress .ep-check { background: var(--ep); border-color: var(--ep); }
    .ep-tname { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .ep-task.done .ep-tname { color: var(--text-muted); text-decoration: line-through; }
    .ep-pri { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 999px; }
    .ep-pri.High { background: rgba(220,38,38,.12); color: #dc2626; } .ep-pri.Medium { background: rgba(217,119,6,.14); color: #d97706; } .ep-pri.Low { background: rgba(100,116,139,.14); color: #64748b; }
    .ep-due { font-size: 11.5px; color: var(--text-muted); min-width: 96px; text-align: right; }
    .ep-status { font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; min-width: 78px; text-align: center; }
    .ep-status.done { background: rgba(22,163,74,.12); color: #15803d; } .ep-status.progress { background: rgba(249,115,22,.12); color: var(--ep-strong); } .ep-status.todo { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); }

    .ep-rail { display: flex; flex-direction: column; gap: 16px; }
    .ep-pan { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .ep-pan h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ep-rec { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 7px 0 7px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .ep-rec:last-child { border-bottom: none; } .ep-rec::before { content: '✨'; position: absolute; left: 2px; top: 6px; }
    .ep-mk { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .ep-mk:last-child { border-bottom: none; }
    .ep-mk-av { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, var(--ep), var(--ep-strong)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0; }
    .ep-mk-main { flex: 1; min-width: 0; } .ep-mk-main h6 { font-size: 12px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; } .ep-mk-main span { font-size: 10.5px; color: var(--text-muted); }
    .ep-mk .pr { font-size: 11.5px; font-weight: 800; color: var(--ep); }
    .ep-dl { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 0; border-bottom: 1px dashed var(--border-color); font-size: 12px; }
    .ep-dl:last-child { border-bottom: none; }
    .ep-dl .d { font-weight: 800; } .ep-dl .d.high { color: #dc2626; } .ep-dl .d.med { color: #d97706; }

    @media (max-width: 1000px) { .ep-grid { grid-template-columns: minmax(0,1fr); } .ep-phases { overflow-x: auto; } }
</style>
@endpush

@section('content')
<div class="ep">
    {{-- Hero --}}
    <div class="ep-hero">
        <div class="ep-hero-top">
            <div>
                <h2>{{ $event['name'] }}</h2>
                <div class="meta">📅 {{ $event['date'] }} · 📍 {{ $event['location'] }} · 👥 {{ $event['guests'] }} guests · {{ $event['days_left'] }} days to go</div>
            </div>
            <div class="ep-prog"><b>{{ $event['progress'] }}%</b><span>Planned</span></div>
        </div>
        <div class="ep-bar"><i style="width: {{ $event['progress'] }}%"></i></div>
    </div>

    {{-- Phase timeline --}}
    <div class="ep-phases">
        @foreach($phases as $i => [$label, $state])
            <div class="ep-phase {{ $state }}"><span class="ep-pdot">@if($state==='done')✓@else{{ $i+1 }}@endif</span><span>{{ $label }}</span></div>
            @if(!$loop->last)<span class="ep-pline"></span>@endif
        @endforeach
    </div>

    <div class="ep-grid">
        {{-- Checklist --}}
        <div class="ep-card">
            <div class="ep-card-hd">✅ Smart Checklist · 6-Month Phase</div>
            @foreach($tasks as [$name, $pri, $due, $status])
                <div class="ep-task {{ $status }}">
                    <span class="ep-check">@if($status==='done')✓@endif</span>
                    <span class="ep-tname">{{ $name }}</span>
                    <span class="ep-pri {{ $pri }}">{{ $pri }}</span>
                    <span class="ep-due">{{ $due }}</span>
                    <span class="ep-status {{ $status }}">{{ ['done'=>'Done','progress'=>'In Progress','todo'=>'To Do'][$status] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Sidebar --}}
        <aside class="ep-rail">
            <div class="ep-pan">
                <h4>🤖 AI Recommendations</h4>
                @foreach($recommendations as $r)<div class="ep-rec">{{ $r }}</div>@endforeach
            </div>
            <div class="ep-pan">
                <h4>🛍 Marketplace Suggestions</h4>
                @foreach($marketplace as [$name, $cat, $rating, $price])
                    <div class="ep-mk">
                        <span class="ep-mk-av">{{ strtoupper(substr($name,0,1)) }}</span>
                        <div class="ep-mk-main"><h6>{{ $name }}</h6><span>{{ $cat }} · ★ {{ $rating }}</span></div>
                        <span class="pr">{{ $price }}</span>
                    </div>
                @endforeach
            </div>
            <div class="ep-pan">
                <h4>⏰ Upcoming Deadlines</h4>
                @foreach($deadlines as [$task, $date, $tone])
                    <div class="ep-dl"><span>{{ $task }}</span><span class="d {{ $tone }}">{{ $date }}</span></div>
                @endforeach
            </div>
            <div class="ep-pan">
                <h4>💡 AI Planning Tips</h4>
                @foreach($tips as $t)<div class="ep-rec" style="padding-left:22px;">{{ $t }}</div>@endforeach
            </div>
        </aside>
    </div>
</div>
@endsection
