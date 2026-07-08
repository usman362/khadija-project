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

    /* Interactive estimator */
    .ao-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .ao-tool h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .ao-tool p.desc { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .ao-form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    @media (max-width: 800px) { .ao-form-grid { grid-template-columns: 1fr 1fr; } }
    .ao-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .ao-field input { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-secondary)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .ao-field input:focus { outline: none; border-color: var(--ao); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .ao-run { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 11px 24px; border: none; border-radius: 10px; background: linear-gradient(135deg, var(--ao), var(--ao-strong)); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .ao-run:disabled { opacity: .6; cursor: not-allowed; }
    .ao-err { display: none; margin-top: 14px; padding: 11px 14px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .ao-err.on { display: block; }
    .ao-load { display: none; margin-top: 14px; font-size: 12.5px; color: var(--text-muted); }
    .ao-load.on { display: block; }
    .ao-res { display: none; margin-top: 18px; }
    .ao-res.on { display: block; }
    .ao-res-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
    @media (max-width: 800px) { .ao-res-metrics { grid-template-columns: 1fr 1fr; } }
    .ao-metric { background: var(--bg-primary, var(--bg-secondary)); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; }
    .ao-metric b { display: block; font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .ao-metric .lbl { font-size: 11px; color: var(--text-muted); margin-top: 5px; }
    .ao-status-pill { display: inline-block; font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 999px; background: rgba(99,102,241,.12); color: var(--ao); margin-bottom: 14px; }
    .ao-res-summary { font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; background: rgba(99,102,241,.05); border-left: 3px solid var(--ao); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; }
    .ao-res-sugg { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .ao-res-sugg:last-child { border-bottom: none; } .ao-res-sugg::before { content: '✨'; position: absolute; left: 2px; top: 7px; }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Work out your own weekly capacity and open slots — just the math, no AI advice.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'Enter your pattern — AI estimates utilization and suggests how to optimize it.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'AI reads your pattern, optimizes availability and fills your calendar plan for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="ao" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--ao,#2563eb);text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    {{-- Interactive availability estimator --}}
    <div class="ao-tool">
        <h3>📊 Availability Estimator</h3>
        <p class="desc">{{ $isManual ? 'Enter your working pattern to work out your weekly capacity, utilization and open slots yourself.' : 'Enter your working pattern to get an estimated weekly capacity, utilization and open-slot count' . ($isSemi ? ' plus AI suggestions to optimize it.' : '. Figures are planning estimates.') }}</p>

        <form id="aoForm">
            <div class="ao-form-grid">
                <div class="ao-field">
                    <label>Working days / week</label>
                    <input type="number" name="working_days" min="1" max="7" step="1" required placeholder="e.g. 5">
                </div>
                <div class="ao-field">
                    <label>Hours / day</label>
                    <input type="number" name="hours_per_day" min="1" max="16" step="0.5" required placeholder="e.g. 8">
                </div>
                <div class="ao-field">
                    <label>Avg gig length (hrs)</label>
                    <input type="number" name="avg_gig_hours" min="0.5" max="24" step="0.5" required placeholder="e.g. 4">
                </div>
                <div class="ao-field">
                    <label>Bookings / week</label>
                    <input type="number" name="current_bookings_per_week" min="0" max="100" step="1" required placeholder="e.g. 6">
                </div>
            </div>
            <button type="submit" class="ao-run" id="aoRun">
                {{ $isManual ? '🧮 Calculate my availability' : ($isSemi ? '✨ Estimate + suggest' : '⚡ Optimize my availability') }}
            </button>
        </form>

        <div class="ao-err" id="aoErr"></div>
        <div class="ao-load" id="aoLoad">Calculating your availability estimate…</div>

        <div class="ao-res" id="aoRes">
            <span class="ao-status-pill" id="aoStatus"></span>
            <div class="ao-res-metrics">
                <div class="ao-metric"><b id="aoCapacity"></b><div class="lbl">Weekly capacity (hrs)</div></div>
                <div class="ao-metric"><b id="aoBooked"></b><div class="lbl">Booked hours</div></div>
                <div class="ao-metric"><b id="aoUtil"></b><div class="lbl">Utilization</div></div>
                <div class="ao-metric"><b id="aoSlots"></b><div class="lbl">Open slots</div></div>
            </div>
            <div class="ao-res-summary" id="aoSummary"></div>
            <div id="aoSuggList"></div>
        </div>
    </div>

    @if($isMax)
    {{-- Coordinate It For Me — full auto dashboard (calendar + opportunities + forecast) --}}
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
    @endif
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('aoForm');
    if (!form) return;
    const LEVEL = document.querySelector('.ao')?.dataset.level || 'maximum';

    const run   = document.getElementById('aoRun');
    const load  = document.getElementById('aoLoad');
    const res   = document.getElementById('aoRes');
    const errEl = document.getElementById('aoErr');
    const csrf  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('on');
        res.classList.remove('on');
        load.classList.add('on');
        run.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        // Do It Myself — pure client-side math, no AI suggestions, no server call.
        if (LEVEL === 'manual') {
            load.classList.remove('on');
            run.disabled = false;
            const local = computeLocal(payload);
            if (!local) {
                errEl.textContent = 'Please fill in all four fields with valid numbers.';
                errEl.classList.add('on');
                return;
            }
            render(local);
            res.classList.add('on');
            res.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        try {
            const r = await fetch('{{ route("ai-tools.availability-optimizer.compute") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const data = await r.json();
            load.classList.remove('on');
            run.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not calculate an estimate.';
                errEl.classList.add('on');
                return;
            }

            render(data.result);
            res.classList.add('on');
            res.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            load.classList.remove('on');
            run.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('on');
        }
    });

    // Client-side capacity math mirroring the server (Do It Myself — no AI advice).
    function computeLocal(p) {
        const wd = parseFloat(p.working_days), hpd = parseFloat(p.hours_per_day);
        const gig = parseFloat(p.avg_gig_hours), bk = parseFloat(p.current_bookings_per_week);
        if (![wd, hpd, gig, bk].every(n => !isNaN(n)) || wd < 1 || hpd < 1 || gig < 0.5 || bk < 0) return null;
        const cap = Math.round(wd * hpd * 10) / 10;
        const booked = Math.round(bk * gig * 10) / 10;
        const util = cap > 0 ? Math.round(Math.min(100, Math.max(0, booked / cap * 100)) * 10) / 10 : 0;
        const remaining = Math.max(0, cap - booked);
        const slots = gig > 0 ? Math.floor(remaining / gig) : 0;
        const status = util < 60 ? 'Under-booked — room to grow' : (util < 85 ? 'Healthy' : 'Near capacity');
        return {
            weekly_capacity_hours: cap, booked_hours: booked, utilization_pct: util, open_slots: slots,
            status: status, suggestions: [],
            summary: 'Your weekly capacity is about ' + cap + 'h; you are using roughly ' + booked + 'h (' + util + '% utilization), leaving about ' + remaining + 'h / ' + slots + ' open slot(s). These figures are your own worksheet math.',
        };
    }

    function render(x) {
        document.getElementById('aoStatus').textContent   = x.status;
        document.getElementById('aoCapacity').textContent = x.weekly_capacity_hours;
        document.getElementById('aoBooked').textContent   = x.booked_hours;
        document.getElementById('aoUtil').textContent     = x.utilization_pct + '%';
        document.getElementById('aoSlots').textContent    = x.open_slots;
        document.getElementById('aoSummary').textContent  = x.summary || '';

        const list = document.getElementById('aoSuggList');
        list.innerHTML = '';
        (x.suggestions || []).forEach(s => {
            const d = document.createElement('div');
            d.className = 'ao-res-sugg';
            d.textContent = s;
            list.appendChild(d);
        });
    }
})();
</script>
@endpush
@endsection
