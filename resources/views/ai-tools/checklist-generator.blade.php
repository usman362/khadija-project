@extends($aiLayout ?? 'layouts.client')

@section('title', 'Smart Checklist')
@section('page-title', 'Smart Checklist')
@section('page-subtitle', 'Your prioritised plan, budget and vendor status in one place')

{{-- Smart Checklist (client). Planning command-center: priorities,
     budget summary, vendor status, AI recommendations. Representative data. --}}

@push('styles')
<style>
    .cg { --cg: var(--brand, #16a34a); }
    .cg-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
    .cg-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .cg-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; } .cg-stat.good b { color: #16a34a; }
    .cg-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .cg-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    .cg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .cg-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14.5px; font-weight: 800; color: var(--text-primary); }

    .cg-task { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border-color); }
    .cg-task:last-child { border-bottom: none; }
    .cg-check { width: 20px; height: 20px; border-radius: 6px; border: 2px solid var(--border-color); flex-shrink: 0; }
    .cg-task.progress .cg-check { background: #f97316; border-color: #f97316; }
    .cg-tname { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .cg-pri { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 999px; }
    .cg-pri.High { background: rgba(220,38,38,.12); color: #dc2626; } .cg-pri.Medium { background: rgba(217,119,6,.14); color: #d97706; } .cg-pri.Low { background: rgba(100,116,139,.14); color: #64748b; }
    .cg-due { font-size: 11.5px; color: var(--text-muted); min-width: 60px; text-align: right; }

    .cg-rec { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border-color); }
    .cg-rec:last-child { border-bottom: none; }
    .cg-rec-main { flex: 1; min-width: 0; } .cg-rec-main h5 { font-size: 13px; font-weight: 800; color: var(--text-primary); } .cg-rec-main p { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; line-height: 1.45; }
    .cg-rbtn { font-size: 11.5px; font-weight: 800; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); border-radius: 8px; padding: 7px 13px; cursor: pointer; white-space: nowrap; }

    /* budget */
    .cg-bud { padding: 16px; }
    .cg-bud-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
    .cg-bud-top b { font-size: 22px; font-weight: 800; color: var(--text-primary); } .cg-bud-top span { font-size: 12px; color: var(--text-muted); }
    .cg-bud-bar { height: 12px; border-radius: 999px; overflow: hidden; display: flex; margin-bottom: 14px; }
    .cg-bud-bar > i { height: 100%; }
    .cg-line { display: flex; align-items: center; gap: 9px; padding: 6px 0; font-size: 12.5px; border-bottom: 1px dashed var(--border-color); }
    .cg-line:last-child { border-bottom: none; }
    .cg-line i { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
    .cg-line .nm { flex: 1; color: var(--text-secondary); } .cg-line b { color: var(--text-primary); font-weight: 800; }

    /* vendors */
    .cg-vd { display: flex; align-items: center; gap: 10px; padding: 9px 16px; border-bottom: 1px solid var(--border-color); }
    .cg-vd:last-child { border-bottom: none; }
    .cg-vd-main { flex: 1; min-width: 0; } .cg-vd-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); } .cg-vd-main span { font-size: 11px; color: var(--text-muted); }
    .cg-vst { font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; }
    .cg-vst.Confirmed { background: rgba(22,163,74,.12); color: #15803d; } .cg-vst.Pending { background: rgba(217,119,6,.14); color: #d97706; } .cg-vst { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); }

    @media (max-width: 1000px) { .cg-grid { grid-template-columns: minmax(0,1fr); } .cg-stats { grid-template-columns: repeat(2,1fr); } }

    /* Generator form */
    .cg-gen { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .cg-gen h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .cg-gen .sub { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .cg-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .cg-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .cg-field input, .cg-field select { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .cg-field input:focus, .cg-field select:focus { outline: none; border-color: var(--cg); }
    .cg-gen-btn { margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .cg-gen-btn:disabled { opacity: .6; cursor: not-allowed; }
    .cg-err { display: none; margin-top: 12px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 12.5px; }
    .cg-err.on { display: block; }
    .cg-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 13px; }
    .cg-loading.on { display: block; }

    /* Results */
    .cg-out { display: none; }
    .cg-out.on { display: block; }
    .cg-out-sum { padding: 13px 16px; background: rgba(22,163,74,.06); border-left: 3px solid #16a34a; border-radius: 8px; font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin-bottom: 16px; }
    .cg-tf { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; margin-bottom: 14px; }
    .cg-tf-hd { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--border-color); }
    .cg-tf-hd h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .cg-tf-hd .due { font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .cg-tf-item { display: flex; align-items: center; gap: 11px; padding: 10px 16px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-primary); }
    .cg-tf-item:last-child { border-bottom: none; }
    .cg-tf-item .box { width: 17px; height: 17px; border-radius: 5px; border: 2px solid var(--border-color); flex-shrink: 0; }
    /* Help Me Plan — editable task input */
    .cg-edit { flex: 1; min-width: 0; padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-body, var(--bg-card)); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .cg-edit:focus { outline: none; border-color: var(--cg); box-shadow: 0 0 0 3px rgba(22,163,74,.14); }
    @media (max-width: 700px) { .cg-form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Build your checklist by hand — add each task yourself, no AI plan.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'instantly drafts a milestone checklist — reword any task before you use it.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your event and AI builds the full milestone checklist for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="cg" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:#15803d;text-decoration:none;">Upgrade for more →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built checklist, no AI --}}
    <div class="cg-gen">
        <h3>🧩 Build My Checklist</h3>
        <div class="sub">Add each task yourself — name it, pick a timeframe and a due date. No AI, fully yours.</div>
        <div id="cgmRows" style="display:flex;flex-direction:column;gap:10px;"></div>
        <button type="button" id="cgmAdd" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#15803d;background:rgba(22,163,74,.09);border:1px solid rgba(22,163,74,.28);border-radius:10px;padding:9px 15px;cursor:pointer;font-family:inherit;">+ Add task</button>
        <div style="margin-top:16px;font-size:12px;color:var(--text-muted);">Want the AI to build this checklist for you automatically? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:#15803d;font-weight:700;text-decoration:none;">Upgrade →</a></div>
    </div>
    @else
    @php $pct = round($budget['spent'] / $budget['total'] * 100); @endphp
    {{-- Generator (Help Me Plan / Coordinate It For Me) --}}
    <div class="cg-gen">
        <h3>🧩 Generate Your Checklist</h3>
        <div class="sub">{{ $isSemi ? 'Enter your event details and instantly drafts a milestone checklist you can reword.' : 'Enter your event details and AI builds the full milestone checklist with estimated due dates.' }}</div>
        <form id="cgForm">
            <div class="cg-form-grid">
                <div class="cg-field">
                    <label>Event Type</label>
                    <select name="event_type" required>
                        <option value="">Select type…</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday Party">Birthday Party</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Conference">Conference</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Baby Shower">Baby Shower</option>
                        <option value="Anniversary">Anniversary</option>
                        <option value="Graduation">Graduation</option>
                        <option value="Private Party">Private Party</option>
                    </select>
                </div>
                <div class="cg-field">
                    <label>Event Date</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="cg-field">
                    <label>Guest Count (optional)</label>
                    <input type="number" name="guest_count" min="1" max="100000" placeholder="e.g. 120">
                </div>
            </div>
            <button type="submit" class="cg-gen-btn" id="cgSubmit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                {{ $isSemi ? 'Suggest My Checklist' : 'Build My Full Checklist' }}
            </button>
            <div class="cg-err" id="cgErr"></div>
        </form>
    </div>

    <div class="cg-loading" id="cgLoading">Building your suggested checklist…</div>

    {{-- Generated results --}}
    <div class="cg-out" id="cgOut">
        <div class="cg-out-sum" id="cgSummary"></div>
        <div id="cgGroups"></div>
        <x-add-to-event tool-key="checklist-generator" tool-name="Smart Checklist" :event-id="request('event_id')" />
    </div>
    <div class="cg-stats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="cg-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="cg-grid">
        <div>
            {{-- Priorities --}}
            <div class="cg-card">
                <div class="cg-card-hd">🎯 Upcoming Priorities</div>
                @foreach($priorities as [$task, $pri, $due, $status])
                    <div class="cg-task {{ $status }}">
                        <span class="cg-check"></span>
                        <span class="cg-tname">{{ $task }}</span>
                        <span class="cg-pri {{ $pri }}">{{ $pri }}</span>
                        <span class="cg-due">{{ $due }}</span>
                    </div>
                @endforeach
            </div>

            {{-- AI recommendations --}}
            <div class="cg-card">
                <div class="cg-card-hd">📋 Recommendations</div>
                @foreach($recommendations as [$title, $desc, $cta])
                    <div class="cg-rec">
                        <div class="cg-rec-main"><h5>{{ $title }}</h5><p>{{ $desc }}</p></div>
                        <button class="cg-rbtn">{{ $cta }}</button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sidebar: budget + vendors --}}
        <aside>
            <div class="cg-card">
                <div class="cg-card-hd">💰 Budget Summary</div>
                <div class="cg-bud">
                    <div class="cg-bud-top"><b>${{ number_format($budget['spent']) }}</b><span>of ${{ number_format($budget['total']) }} ({{ $pct }}%)</span></div>
                    <div class="cg-bud-bar">
                        @foreach($budget['lines'] as [$nm, $amt, $color])
                            <i style="width: {{ round($amt/$budget['total']*100) }}%; background: {{ $color }};"></i>
                        @endforeach
                    </div>
                    @foreach($budget['lines'] as [$nm, $amt, $color])
                        <div class="cg-line"><i style="background: {{ $color }};"></i><span class="nm">{{ $nm }}</span><b>${{ number_format($amt) }}</b></div>
                    @endforeach
                </div>
            </div>

            <div class="cg-card">
                <div class="cg-card-hd">🤝 Vendor Status</div>
                @foreach($vendors as [$name, $cat, $status])
                    <div class="cg-vd">
                        <div class="cg-vd-main"><h6>{{ $name ?: 'Not booked yet' }}</h6><span>{{ $cat }}</span></div>
                        <span class="cg-vst {{ $status === 'Confirmed' ? 'Confirmed' : ($status === 'Pending' ? 'Pending' : '') }}">{{ $status }}</span>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('cgForm');
    if (!form) return;

    const submit  = document.getElementById('cgSubmit');
    const loading = document.getElementById('cgLoading');
    const out     = document.getElementById('cgOut');
    const errEl   = document.getElementById('cgErr');
    const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const LEVEL   = document.querySelector('.cg')?.dataset.level || 'maximum';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('on');
        out.classList.remove('on');
        loading.classList.add('on');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.checklist-generator.compute") }}', {
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
            loading.classList.remove('on');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not generate checklist.';
                errEl.classList.add('on');
                return;
            }
            render(data.result);
            out.classList.add('on');
            out.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            loading.classList.remove('on');
            submit.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('on');
        }
    });

    function render(res) {
        document.getElementById('cgSummary').textContent = res.summary || '';
        const wrap = document.getElementById('cgGroups');
        wrap.innerHTML = '';
        (res.groups || []).forEach(function (g) {
            const items = (g.items || []).map(function (it) {
                // Help Me Plan (semi) → each task is an editable input the client
                // can reword; Coordinate It For Me (maximum) → read-only text.
                if (LEVEL === 'semi') {
                    return '<div class="cg-tf-item"><span class="box"></span>' +
                        '<input type="text" class="cg-edit" value="' + esc(it) + '"></div>';
                }
                return '<div class="cg-tf-item"><span class="box"></span>' + esc(it) + '</div>';
            }).join('');
            const block = document.createElement('div');
            block.className = 'cg-tf';
            block.innerHTML =
                '<div class="cg-tf-hd"><h4>' + esc(g.timeframe) + '</h4>' +
                '<span class="due">Target: ' + esc(g.due_date) + '</span></div>' + items;
            wrap.appendChild(block);
        });
        var __n = (res.groups || []).reduce(function (s, g) { return s + (g.items || []).length; }, 0);
        if (window.aiAttachSet) window.aiAttachSet('Checklist · ' + __n + ' tasks', res);
    }

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
        });
    }
})();

// Do It Myself — hand-built checklist (no AI, no server call).
(function () {
    const rows = document.getElementById('cgmRows');
    const add  = document.getElementById('cgmAdd');
    if (!rows || !add) return;
    const timeframes = ['12+ weeks before', '8 weeks before', '4 weeks before', '2 weeks before', '1 week before', 'Day of'];
    function addRow(name = '', tf = '4 weeks before', due = '') {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;';
        div.innerHTML =
            '<input type="text" placeholder="Task name" class="cg-edit" style="flex:2;min-width:150px;">' +
            '<select class="cg-edit" style="flex:0 0 auto;width:auto;">' + timeframes.map(t => '<option ' + (t === tf ? 'selected' : '') + '>' + t + '</option>').join('') + '</select>' +
            '<input type="date" class="cg-edit" style="flex:0 0 auto;width:auto;">' +
            '<button type="button" title="Remove" style="border:none;background:rgba(220,38,38,.1);color:#dc2626;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:16px;flex:0 0 auto;">&times;</button>';
        div.querySelector('input[type="text"]').value = name;
        div.querySelector('input[type="date"]').value = due;
        div.querySelector('button').addEventListener('click', () => div.remove());
        rows.appendChild(div);
    }
    // Seed a few common starter tasks the client can rename, retime or remove.
    [['Set overall budget and guest list', '12+ weeks before'], ['Book venue', '12+ weeks before'], ['Book key vendors (catering, photo)', '8 weeks before'], ['Send invitations', '4 weeks before']]
        .forEach(([n, t]) => addRow(n, t, ''));
    add.addEventListener('click', () => addRow());
})();
</script>
@endpush
