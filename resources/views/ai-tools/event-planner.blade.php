@extends($aiLayout ?? 'layouts.client')

@section('title', 'Guided Event Planner')
@section('page-title', 'Guided Event Planner')
@section('page-subtitle', 'Your event, organised end-to-end')

{{-- Guided Event Planner (client). Milestone checklist + progress + AI
     recommendations + marketplace suggestions + deadlines. Representative. --}}

@push('styles')
<style>
    .ep { --ep: var(--brand, #f97316); --ep-strong: var(--brand-strong, #ea580c); }
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

    /* Interactive planner form */
    .ep-form-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .ep-form-card h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .ep-form-card .sub { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .ep-fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ep-fgrid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) { .ep-fgrid { grid-template-columns: 1fr; } }
    .ep-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .ep-inp { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .ep-inp:focus { outline: none; border-color: var(--ep); box-shadow: 0 0 0 3px rgba(249,115,22,.15); }
    .ep-go { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 11px 22px; border: none; border-radius: 10px; background: linear-gradient(135deg, var(--ep), var(--ep-strong)); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .ep-go:disabled { opacity: .6; cursor: not-allowed; }
    .ep-err { display: none; margin-top: 14px; padding: 11px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 13px; }
    .ep-err.open { display: block; }
    .ep-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 13px; }
    .ep-loading.open { display: block; }
    .ep-spin { width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--ep); border-radius: 50%; margin: 0 auto 12px; animation: epspin .8s linear infinite; }
    @keyframes epspin { to { transform: rotate(360deg); } }
    .ep-out { display: none; }
    .ep-out.open { display: block; animation: epfade .3s ease; }
    @keyframes epfade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .ep-out-summary { padding: 13px 16px; background: rgba(249,115,22,.06); border-left: 3px solid var(--ep); border-radius: 10px; font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin-bottom: 16px; }
    .ep-chip { display: inline-block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 999px; padding: 5px 11px; margin: 0 6px 6px 0; }
    .ep-ms { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .ep-ms:last-child { border-bottom: none; }
    .ep-ms .ml { flex: 1; font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .ep-ms .md { font-size: 12px; color: var(--text-muted); min-width: 100px; text-align: right; }
    .ep-ms .mst { font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; min-width: 76px; text-align: center; }
    .ep-ms .mst.done { background: rgba(22,163,74,.12); color: #15803d; }
    .ep-ms .mst.due-soon { background: rgba(249,115,22,.12); color: var(--ep-strong); }
    .ep-ms .mst.upcoming { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); }
    .ep-bs { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); font-size: 13px; }
    .ep-bs:last-child { border-bottom: none; }
    .ep-bs .amt { font-weight: 800; color: var(--text-primary); }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Build your own checklist by hand — add each task yourself, no AI plan.'],
        'semi'    => ['Help Me Plan', '#f97316', 'instantly drafts a milestone plan and budget split — tweak the amounts before you use it.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your event and AI builds the full plan, milestones and budget for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="ep" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--ep-strong);text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built checklist, no AI --}}
    <div class="ep-form-card">
        <h3>🗓 Build My Checklist</h3>
        <div class="sub">Add each task yourself — set a name, priority and due date. No AI, fully yours.</div>
        <div id="epmRows" style="display:flex;flex-direction:column;gap:10px;"></div>
        <button type="button" id="epmAdd" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--ep-strong);background:rgba(249,115,22,.09);border:1px solid rgba(249,115,22,.28);border-radius:10px;padding:9px 15px;cursor:pointer;font-family:inherit;">+ Add task</button>
        <div style="margin-top:16px;font-size:12px;color:var(--text-muted);">Want the AI to build this plan for you automatically? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:var(--ep-strong);font-weight:700;text-decoration:none;">Upgrade →</a></div>
    </div>
    @else
    {{-- Interactive planner (Help Me Plan / Coordinate It For Me) --}}
    <div class="ep-form-card">
        <h3>🗓 Plan My Event</h3>
        <div class="sub">{{ $isSemi ? "Enter your details and instantly drafts a plan you can adjust." : "Enter your details and AI builds the full milestone plan, vendor list and budget split." }}</div>
        <form id="epForm">
            <div class="ep-fgrid">
                <div>
                    <label class="ep-lbl">Event Type</label>
                    <select name="event_type" class="ep-inp" required>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday Party">Birthday Party</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Conference">Conference</option>
                        <option value="Gala">Gala</option>
                        <option value="Baby Shower">Baby Shower</option>
                        <option value="Anniversary">Anniversary</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="ep-lbl">Event Date</label>
                    <input type="date" name="event_date" class="ep-inp" required>
                </div>
                <div>
                    <label class="ep-lbl">Guest Count</label>
                    <input type="number" name="guest_count" class="ep-inp" min="1" max="100000" placeholder="e.g. 120" required>
                </div>
                <div>
                    <label class="ep-lbl">Total Budget (USD)</label>
                    <input type="number" name="budget" class="ep-inp" min="1" step="1" placeholder="e.g. 25000" required>
                </div>
                <div class="full">
                    <label class="ep-lbl">Location (optional)</label>
                    <input type="text" name="location" class="ep-inp" maxlength="200" placeholder="e.g. Los Angeles, CA">
                </div>
            </div>
            <button type="submit" class="ep-go" id="epGo">{{ $isSemi ? '✨ Suggest My Plan' : '🤖 Build My Full Plan' }}</button>
            <div class="ep-err" id="epErr"></div>
        </form>
    </div>

    <div class="ep-loading" id="epLoading">
        <div class="ep-spin"></div>
        Building your event plan...
    </div>

    {{-- Computed results --}}
    <div class="ep-out" id="epOut">
        <div class="ep-out-summary" id="epSummary"></div>
        <div class="ep-grid">
            <div class="ep-card">
                <div class="ep-card-hd">✅ Milestone Plan</div>
                <div id="epMilestones" style="padding: 4px 16px;"></div>
            </div>
            <aside class="ep-rail">
                <div class="ep-pan">
                    <h4>🧩 Vendor Categories</h4>
                    <div id="epVendors"></div>
                </div>
                <div class="ep-pan">
                    <h4>💰 Budget Split</h4>
                    <div id="epBudget"></div>
                </div>
                <div class="ep-pan">
                    <h4>💡 Planning Tips</h4>
                    <div id="epTips"></div>
                </div>
            </aside>
        </div>
    </div>

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
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('epForm');
    if (!form) return;
    const go = document.getElementById('epGo');
    const loading = document.getElementById('epLoading');
    const out = document.getElementById('epOut');
    const errEl = document.getElementById('epErr');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const LEVEL = document.querySelector('.ep')?.dataset.level || 'maximum';

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        go.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const r = await fetch('{{ route("ai-tools.event-planner.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            go.disabled = false;
            if (!data.success) {
                errEl.textContent = data.message || 'Could not build the plan. Please check your inputs.';
                errEl.classList.add('open');
                return;
            }
            render(data.result);
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
        document.getElementById('epSummary').textContent = res.summary || '';

        const statusLabel = { 'done': 'Done', 'due-soon': 'Due Soon', 'upcoming': 'Upcoming' };
        document.getElementById('epMilestones').innerHTML = (res.milestones || []).map(m => `
            <div class="ep-ms">
                <span class="ml">${esc(m.label)}</span>
                <span class="md">${esc(m.due_date)}</span>
                <span class="mst ${esc(m.status)}">${esc(statusLabel[m.status] || m.status)}</span>
            </div>`).join('');

        document.getElementById('epVendors').innerHTML = (res.vendor_categories || [])
            .map(v => `<span class="ep-chip">${esc(v)}</span>`).join('');

        const budgetEl = document.getElementById('epBudget');
        if (LEVEL === 'semi') {
            // Help Me Plan — amounts are editable, with a live running total.
            budgetEl.innerHTML = (res.budget_split || []).map((b, i) => `
                <div class="ep-bs"><span>${esc(b.category)}</span>
                    <span class="amt">$<input type="number" class="ep-bamt" data-i="${i}" value="${Math.round(b.amount)}" style="width:86px;text-align:right;padding:5px 8px;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-body,var(--bg-card));color:var(--text-primary);font-weight:800;font-family:inherit;font-size:12.5px;"></span>
                </div>`).join('')
                + `<div class="ep-bs" style="border-top:2px solid var(--border-color);margin-top:4px;padding-top:9px;"><span style="font-weight:800;">Total</span><span class="amt" id="epBudgetTotal"></span></div>`;
            const recalc = () => {
                let t = 0; budgetEl.querySelectorAll('.ep-bamt').forEach(inp => t += (parseFloat(inp.value) || 0));
                document.getElementById('epBudgetTotal').textContent = money(t);
            };
            budgetEl.querySelectorAll('.ep-bamt').forEach(inp => inp.addEventListener('input', recalc));
            recalc();
        } else {
            // Coordinate It For Me — read-only.
            budgetEl.innerHTML = (res.budget_split || []).map(b => `
                <div class="ep-bs"><span>${esc(b.category)}</span><span class="amt">${money(b.amount)}</span></div>`).join('');
        }

        document.getElementById('epTips').innerHTML = (res.tips || [])
            .map(t => `<div class="ep-rec" style="padding-left:22px;">${esc(t)}</div>`).join('');
    }
})();

// Do It Myself — hand-built checklist (no AI, no server call).
(function () {
    const rows = document.getElementById('epmRows');
    const add = document.getElementById('epmAdd');
    if (!rows || !add) return;
    const priorities = ['High', 'Medium', 'Low'];
    function addRow(name = '', pri = 'Medium', due = '') {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;';
        div.innerHTML =
            '<input type="text" placeholder="Task name" class="ep-inp" style="flex:2;min-width:150px;">' +
            '<select class="ep-inp" style="flex:0 0 auto;width:auto;">' + priorities.map(p => '<option ' + (p === pri ? 'selected' : '') + '>' + p + '</option>').join('') + '</select>' +
            '<input type="date" class="ep-inp" style="flex:0 0 auto;width:auto;">' +
            '<button type="button" title="Remove" style="border:none;background:rgba(220,38,38,.1);color:#dc2626;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:16px;flex:0 0 auto;">&times;</button>';
        div.querySelector('input[type="text"]').value = name;
        div.querySelector('input[type="date"]').value = due;
        div.querySelector('button').addEventListener('click', () => div.remove());
        rows.appendChild(div);
    }
    // Seed a few common starter tasks the client can rename or remove.
    [['Set overall budget', 'High'], ['Book venue', 'High'], ['Book key vendors (catering, photo)', 'Medium'], ['Send invites', 'Medium']]
        .forEach(([n, p]) => addRow(n, p, ''));
    add.addEventListener('click', () => addRow());
})();
</script>
@endpush
