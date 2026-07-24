@extends($aiLayout ?? 'layouts.client')

@section('title', 'Timeline Builder')
@section('page-title', 'Timeline Builder')
@section('page-subtitle', 'A conflict-free run-of-show for your event day')

{{-- Timeline Builder (client). Event-day run-of-show across vendor tracks
     with buffers + conflict detection. Representative data. --}}

@push('styles')
<style>
    .tb { --tb: var(--brand, #7c3aed); }
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
    .tb-btn.primary { border: none; background: linear-gradient(135deg, var(--tb), var(--brand-strong, #6d28d9)); color: #fff; }

    @media (max-width: 1000px) { .tb-stats { grid-template-columns: repeat(2,1fr); } .tb-card { overflow-x: auto; } }

    /* Interactive builder form */
    .tb-form-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .tb-form-card h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .tb-form-card .sub { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .tb-fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 640px) { .tb-fgrid { grid-template-columns: 1fr; } }
    .tb-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .tb-inp { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .tb-inp:focus { outline: none; border-color: var(--tb); box-shadow: 0 0 0 3px rgba(124,58,237,.15); }
    .tb-go { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 11px 22px; border: none; border-radius: 10px; background: linear-gradient(135deg, var(--tb), var(--brand-strong, #6d28d9)); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .tb-go:disabled { opacity: .6; cursor: not-allowed; }
    .tb-err { display: none; margin-top: 14px; padding: 11px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 13px; }
    .tb-err.open { display: block; }
    .tb-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 13px; }
    .tb-loading.open { display: block; }
    .tb-spin { width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--tb); border-radius: 50%; margin: 0 auto 12px; animation: tbspin .8s linear infinite; }
    @keyframes tbspin { to { transform: rotate(360deg); } }
    .tb-out { display: none; }
    .tb-out.open { display: block; animation: tbfade .3s ease; }
    @keyframes tbfade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .tb-out-summary { padding: 13px 16px; background: rgba(124,58,237,.06); border-left: 3px solid var(--tb); border-radius: 10px; font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin-bottom: 16px; }
    .tb-row { display: flex; align-items: center; gap: 14px; padding: 11px 4px; border-bottom: 1px dashed var(--border-color); }
    .tb-row:last-child { border-bottom: none; }
    .tb-row .t { font-size: 13px; font-weight: 800; color: var(--tb); min-width: 78px; }
    .tb-row .s { flex: 1; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .tb-row .dm { font-size: 11.5px; font-weight: 700; color: var(--text-muted); background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 999px; padding: 3px 10px; }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Build your run-of-show by hand — add each time slot yourself, no AI.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'AI suggests a timeline — edit the times and segments before you save.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your event and AI builds the entire run-of-show for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="tb" data-level="{{ $level }}">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:18px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:#2563eb;text-decoration:none;">Upgrade for more →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built run-of-show, no AI --}}
    <div class="tb-form-card">
        <h3>🛠 Build My Run-of-Show</h3>
        <div class="sub">Add each slot yourself — set the time and what's happening.</div>
        <div id="tbmRows" style="display:flex;flex-direction:column;gap:8px;"></div>
        <button type="button" id="tbmAdd" style="margin-top:12px;display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:#2563eb;background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.25);border-radius:9px;padding:8px 14px;cursor:pointer;font-family:inherit;">+ Add time slot</button>
    </div>
    @else
    {{-- Interactive builder --}}
    <div class="tb-form-card">
        <h3>🛠 Build My Run-of-Show</h3>
        <div class="sub">{{ $isSemi ? "Enter your event — AI suggests a timeline you can edit." : "Enter your event details and AI builds the run-of-show with real clock times." }}</div>
        <form id="tbForm">
            <div class="tb-fgrid">
                <div>
                    <label class="tb-lbl">Event Type</label>
                    <select name="event_type" class="tb-inp" required>
                        <option value="Wedding">Wedding</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Conference">Conference</option>
                        <option value="Birthday Party">Birthday Party</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="tb-lbl">Event Date</label>
                    <input type="date" name="event_date" class="tb-inp" required>
                </div>
                <div>
                    <label class="tb-lbl">Start Time</label>
                    <input type="time" name="start_time" class="tb-inp" value="16:00" required>
                </div>
                <div>
                    <label class="tb-lbl">Duration (hours)</label>
                    <input type="number" name="duration_hours" class="tb-inp" min="2" max="12" step="0.5" value="6" required>
                </div>
            </div>
            <button type="submit" class="tb-go" id="tbGo">{{ $isSemi ? '✨ Suggest a Timeline' : '🤖 Build My Timeline' }}</button>
            <div class="tb-err" id="tbErr"></div>
        </form>
    </div>
    @endif

    <div class="tb-loading" id="tbLoading">
        <div class="tb-spin"></div>
        Building your run-of-show...
    </div>

    {{-- Computed schedule --}}
    <div class="tb-out" id="tbOut">
        <x-add-to-event tool-key="timeline-builder" tool-name="Timeline Builder" :event-id="request('event_id')" />
        <div class="tb-out-summary" id="tbSummary"></div>
        <div class="tb-card">
            <h3>📋 Suggested Run-of-Show</h3>
            <div id="tbSchedule"></div>
        </div>
    </div>

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

@push('scripts')
<script>
(function () {
    const form = document.getElementById('tbForm');
    if (!form) return;
    const go = document.getElementById('tbGo');
    const loading = document.getElementById('tbLoading');
    const out = document.getElementById('tbOut');
    const errEl = document.getElementById('tbErr');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const LEVEL = document.querySelector('.tb')?.dataset.level || 'maximum';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        go.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const r = await fetch('{{ route("ai-tools.timeline-builder.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            go.disabled = false;
            if (!data.success) {
                errEl.textContent = data.message || 'Could not build the timeline. Please check your inputs.';
                errEl.classList.add('open');
                return;
            }
            render(data.result);
            if (window.aiAttachSet) window.aiAttachSet('Event timeline', data.result);
            out.classList.add('open');
            out.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            loading.classList.remove('open');
            go.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('open');
        }
    });

    function render(res) {
        document.getElementById('tbSummary').textContent = (LEVEL === 'semi' ? 'Suggested timeline — edit any time or segment below. ' : '') + (res.summary || '');
        const sched = document.getElementById('tbSchedule');
        if (LEVEL === 'semi') {
            sched.innerHTML = (res.schedule || []).map(s => `
                <div class="tb-row" style="gap:8px;">
                    <input type="time" class="tb-inp" style="max-width:120px;" value="${to24(s.time)}">
                    <input class="tb-inp" value="${esc(s.segment)}">
                    <span class="dm">${esc(s.duration_min)} min</span>
                </div>`).join('');
        } else {
            sched.innerHTML = (res.schedule || []).map(s => `
                <div class="tb-row">
                    <span class="t">${esc(s.time)}</span>
                    <span class="s">${esc(s.segment)}</span>
                    <span class="dm">${esc(s.duration_min)} min</span>
                </div>`).join('');
        }
    }
    // "5:30 PM" -> "17:30" for the editable time input
    function to24(t) {
        const m = String(t).match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!m) return '';
        let h = parseInt(m[1], 10) % 12; if (/pm/i.test(m[3])) h += 12;
        return String(h).padStart(2, '0') + ':' + m[2];
    }
})();

// Do It Myself — manual run-of-show builder (no AI)
(function () {
    const rows = document.getElementById('tbmRows');
    if (!rows) return;
    function addRow(time = '', act = '') {
        const div = document.createElement('div');
        div.style.cssText = 'display:grid;grid-template-columns:120px 1fr 36px;gap:8px;align-items:center;';
        div.innerHTML = `<input type="time" class="tb-inp" value="${time}"><input class="tb-inp" placeholder="What's happening (e.g. Ceremony)" value="${act}"><button type="button" title="Remove" style="background:none;border:1px solid var(--border-color);color:var(--text-muted);border-radius:8px;height:36px;cursor:pointer;">×</button>`;
        div.querySelector('button').addEventListener('click', () => div.remove());
        rows.appendChild(div);
    }
    document.getElementById('tbmAdd').addEventListener('click', () => addRow());
    [['16:00', 'Guest Arrival'], ['16:30', 'Ceremony'], ['17:30', 'Cocktail Hour'], ['18:30', 'Dinner Service'], ['20:00', 'Dancing']].forEach(([t, a]) => addRow(t, a));
})();
</script>
@endpush
