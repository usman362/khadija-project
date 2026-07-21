@extends($aiLayout ?? 'layouts.client')

@section('title', 'Venue Compatibility Check')
@section('page-title', 'Venue Compatibility Check')
@section('page-subtitle', 'Score a venue, map the layout, spot the gaps')

{{-- Venue Compatibility Check (client). Venue score + intelligence summary + gap
     analysis + interactive layout map + required vendors. Representative. --}}

@push('styles')
<style>
    .va { --va: var(--brand, #16a34a); }
    .va-hero { display: flex; align-items: center; gap: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 18px; margin-bottom: 16px; flex-wrap: wrap; }
    .va-hero h2 { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .va-hero .a { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .va-kpis { margin-left: auto; display: flex; gap: 22px; }
    .va-kpi { text-align: center; } .va-kpi b { display: block; font-size: 20px; font-weight: 800; color: var(--va); } .va-kpi span { font-size: 10.5px; color: var(--text-muted); }

    .va-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
    .va-sum { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 13px 15px; }
    .va-sum b { font-size: 18px; font-weight: 800; color: var(--text-primary); } .va-sum .l { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .va-sbar { height: 5px; border-radius: 999px; background: var(--border-color); margin-top: 8px; overflow: hidden; } .va-sbar > i { display: block; height: 100%; background: var(--va); }

    .va-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    .va-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .va-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14.5px; font-weight: 800; color: var(--text-primary); }

    /* gap analysis */
    .va-gap { display: grid; grid-template-columns: 150px 1fr 1fr; gap: 12px; padding: 11px 16px; border-bottom: 1px solid var(--border-color); font-size: 12px; align-items: start; }
    .va-gap:last-child { border-bottom: none; }
    .va-gap .cat { font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 7px; }
    .va-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; } .va-dot.good { background: #16a34a; } .va-dot.warn { background: #d97706; }
    .va-gap .d { color: var(--text-muted); } .va-gap .r { color: var(--text-secondary); font-weight: 600; }

    /* map */
    .va-map { position: relative; height: 300px; margin: 14px; border-radius: 12px; overflow: hidden;
        background: linear-gradient(160deg, #d9ead3, #c7e0bf); }
    [data-theme="dark"] .va-map { background: linear-gradient(160deg, #1e3a2a, #16271d); }
    .va-zone { position: absolute; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .va-zone i { width: 14px; height: 14px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); box-shadow: 0 2px 6px rgba(0,0,0,.3); }
    .va-zone span { font-size: 10px; font-weight: 800; color: #0f1b35; background: rgba(255,255,255,.85); padding: 2px 7px; border-radius: 6px; white-space: nowrap; }

    .va-vendors { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 14px 16px; }
    .va-vd { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px; text-align: center; }
    .va-vd .e { font-size: 22px; } .va-vd span { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-top: 5px; }

    .va-rail { display: flex; flex-direction: column; gap: 16px; }
    .va-pan { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .va-pan h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .va-alert { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .va-alert:last-child { border-bottom: none; } .va-alert::before { content: '⚠️'; position: absolute; left: 0; top: 7px; font-size: 11px; }
    .va-hc { display: flex; justify-content: space-between; font-size: 12.5px; padding: 7px 0; border-bottom: 1px dashed var(--border-color); }
    .va-hc:last-child { border-bottom: none; } .va-hc b { color: var(--text-primary); font-weight: 800; }
    .va-score-ring { text-align: center; } .va-score-ring b { font-size: 30px; font-weight: 800; color: var(--va); } .va-score-ring span { font-size: 11px; color: var(--text-muted); }

    @media (max-width: 1000px) { .va-grid { grid-template-columns: minmax(0,1fr); } .va-summary { grid-template-columns: 1fr 1fr; } .va-vendors { grid-template-columns: 1fr 1fr; } .va-gap { grid-template-columns: 1fr; } }

    /* Interactive analyzer form */
    .va-form-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 16px; }
    .va-form-card h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .va-form-card .sub { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .va-fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 640px) { .va-fgrid { grid-template-columns: 1fr; } }
    .va-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .va-inp { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .va-inp:focus { outline: none; border-color: var(--va); box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
    .va-chk { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-top: 26px; }
    .va-go { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 11px 22px; border: none; border-radius: 10px; background: linear-gradient(135deg, var(--va), #15803d); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .va-go:disabled { opacity: .6; cursor: not-allowed; }
    .va-err { display: none; margin-top: 14px; padding: 11px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 13px; }
    .va-err.open { display: block; }
    .va-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 13px; }
    .va-loading.open { display: block; }
    .va-spin { width: 40px; height: 40px; border: 3px solid var(--border-color); border-top-color: var(--va); border-radius: 50%; margin: 0 auto 12px; animation: vaspin .8s linear infinite; }
    @keyframes vaspin { to { transform: rotate(360deg); } }
    .va-out { display: none; margin-bottom: 16px; }
    .va-out.open { display: block; animation: vafade .3s ease; }
    @keyframes vafade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .va-verdict { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px; margin-bottom: 14px; font-weight: 800; font-size: 15px; }
    .va-verdict.good { background: rgba(22,163,74,.1); border: 1px solid rgba(22,163,74,.3); color: #15803d; }
    .va-verdict.tight { background: rgba(217,119,6,.1); border: 1px solid rgba(217,119,6,.3); color: #d97706; }
    .va-verdict.over { background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; }
    .va-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
    @media (max-width: 640px) { .va-metrics { grid-template-columns: 1fr; } }
    .va-metric { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .va-metric b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .va-metric .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .va-bd { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); font-size: 13px; }
    .va-bd:last-child { border-bottom: none; }
    .va-bd .st { font-size: 10.5px; font-weight: 800; padding: 3px 10px; border-radius: 999px; text-transform: uppercase; }
    .va-bd .st.good { background: rgba(22,163,74,.12); color: #15803d; }
    .va-bd .st.tight { background: rgba(217,119,6,.12); color: #d97706; }
    .va-bd .st.over { background: rgba(220,38,38,.12); color: #dc2626; }

    /* Help Me Plan — editable output fields */
    .va-edit { width: 100%; padding: 7px 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-body, var(--bg-card)); color: var(--text-primary); font-size: 13px; font-family: inherit; }
    .va-edit:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
    .va-metric .va-edit { max-width: 150px; padding: 4px 8px; font-size: 22px; font-weight: 800; }
    .va-verdict .va-edit { max-width: 340px; font-size: 15px; font-weight: 800; }

    /* Do It Myself — hand-built venue scorecard */
    .va-mano h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 4px 0 12px; }
    .va-frow { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
    .va-frow .fname { flex: 1; min-width: 140px; }
    .va-frow input[type="range"] { flex: 1.4; min-width: 140px; accent-color: var(--va); }
    .va-frow .fscore { width: 40px; text-align: right; font-weight: 800; color: var(--text-primary); }
    .va-mdel { border: none; background: rgba(220,38,38,.1); color: #dc2626; border-radius: 8px; width: 34px; height: 34px; cursor: pointer; font-size: 16px; flex: 0 0 auto; }
    .va-madd { margin-top: 4px; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #15803d; background: rgba(22,163,74,.09); border: 1px solid rgba(22,163,74,.28); border-radius: 10px; padding: 9px 15px; cursor: pointer; font-family: inherit; }
    .va-mscore { display: flex; align-items: center; gap: 18px; margin-top: 16px; padding: 16px; border: 1px solid var(--border-color); border-radius: 14px; }
    .va-mscore .big { font-size: 34px; font-weight: 800; color: var(--va); line-height: 1; }
    .va-mverdict { font-size: 15px; font-weight: 800; }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Score the venue factors yourself — we average them into an overall verdict, no AI.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'the tool analyses the space — adjust the figures, verdict and notes and utilization recalculates live.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your space and the tool analyses capacity, fit and gaps for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="va" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:#15803d;text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built venue scorecard, no AI, computed client-side --}}
    <div class="va-form-card va-mano">
        <h3>🏛 Score My Venue</h3>
        <div class="sub">Rate each factor yourself — we average them into an overall venue score. No AI.</div>
        <div style="margin-bottom:14px;">
            <label class="va-lbl">Venue Name (optional)</label>
            <input type="text" id="vamName" class="va-inp" placeholder="e.g. The Garden Estate">
        </div>
        <h4>📋 Venue Factors</h4>
        <div id="vamRows"></div>
        <button type="button" id="vamAdd" class="va-madd">+ Add factor</button>
        <div class="va-mscore">
            <div><div class="big" id="vamScore">0</div><div style="font-size:11px;color:var(--text-muted);">Overall Score / 100</div></div>
            <div class="va-mverdict" id="vamVerdict"></div>
        </div>
        <div style="margin-top:16px;font-size:12px;color:var(--text-muted);">Want AI to analyse the space, gaps and hidden costs for you? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:#15803d;font-weight:700;text-decoration:none;">Upgrade →</a></div>
    </div>
    @else
    {{-- Interactive analyzer (Help Me Plan / Coordinate It For Me) --}}
    <div class="va-form-card">
        <h3>📐 Analyze My Venue Space</h3>
        <div class="sub">{{ $isSemi ? 'AI suggests a capacity and space fit you can adjust before using.' : 'Enter the venue size and your guest count and AI works out capacity and space fit.' }}</div>
        <form id="vaForm">
            <div class="va-fgrid">
                <div>
                    <label class="va-lbl">Venue Size (sq ft)</label>
                    <input type="number" name="venue_sqft" class="va-inp" min="1" step="1" placeholder="e.g. 3000" required>
                </div>
                <div>
                    <label class="va-lbl">Guest Count</label>
                    <input type="number" name="guest_count" class="va-inp" min="1" max="100000" placeholder="e.g. 150" required>
                </div>
                <div>
                    <label class="va-lbl">Seating Style</label>
                    <select name="seating_style" class="va-inp" required>
                        <option value="banquet">Banquet (seated dinner)</option>
                        <option value="theater">Theater (rows)</option>
                        <option value="cocktail">Cocktail (standing)</option>
                        <option value="classroom">Classroom (tables + rows)</option>
                    </select>
                </div>
                <div>
                    <label class="va-chk"><input type="checkbox" name="has_dancefloor" value="1"> Include a dance floor</label>
                </div>
            </div>
            <button type="submit" class="va-go" id="vaGo">{{ $isSemi ? '✨ Suggest Venue Analysis' : '🤖 Analyze My Venue' }}</button>
            <div class="va-err" id="vaErr"></div>
        </form>
    </div>

    <div class="va-loading" id="vaLoading">
        <div class="va-spin"></div>
        Analyzing the space...
    </div>

    {{-- Computed analysis --}}
    <div class="va-out" id="vaOut">
        <x-add-to-event tool-key="venue-analyzer" tool-name="AI Venue Compatibility Check" :event-id="request('event_id')" />
        <div class="va-verdict" id="vaVerdict"></div>
        <div class="va-metrics">
            <div class="va-metric"><b id="vaReq"></b><div class="l">Space Needed (sq ft)</div></div>
            <div class="va-metric"><b id="vaCap"></b><div class="l">Estimated Max Capacity</div></div>
            <div class="va-metric"><b id="vaUtil"></b><div class="l">Utilization</div></div>
        </div>
        <div class="va-card" style="margin-bottom:16px;">
            <div class="va-card-hd">🔍 Area Breakdown</div>
            <div id="vaBreakdown" style="padding: 6px 16px;"></div>
        </div>
        <div class="va-pan">
            <h4>💡 Suggestions</h4>
            <div id="vaTips"></div>
        </div>
    </div>

    <div class="va-hero">
        <div>
            <h2>🏛 {{ $venue['name'] }}</h2>
            <div class="a">📍 {{ $venue['address'] }}</div>
        </div>
        <div class="va-kpis">
            <div class="va-kpi"><b>{{ $venue['score'] }}%</b><span>Venue Score</span></div>
            <div class="va-kpi"><b>{{ $venue['capacity'] }}</b><span>Capacity</span></div>
            <div class="va-kpi"><b>{{ $venue['compatibility'] }}%</b><span>Compatibility</span></div>
        </div>
    </div>

    <div class="va-summary">
        @foreach($summary as [$lbl, $val, $tone])
            <div class="va-sum"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div><div class="va-sbar"><i style="width: {{ $val }}"></i></div></div>
        @endforeach
    </div>

    <div class="va-grid">
        <div>
            {{-- Interactive map --}}
            <div class="va-card">
                <div class="va-card-hd">🗺 Interactive Venue Map</div>
                <div class="va-map">
                    @foreach($zones as [$label, $x, $y, $color])
                        <div class="va-zone" style="left: {{ $x }}%; top: {{ $y }}%;"><i style="background: {{ $color }};"></i><span>{{ $label }}</span></div>
                    @endforeach
                </div>
            </div>

            {{-- Gap analysis --}}
            <div class="va-card">
                <div class="va-card-hd">🔍 AI Gap Analysis</div>
                @foreach($gaps as [$cat, $tone, $detail, $rec])
                    <div class="va-gap">
                        <span class="cat"><span class="va-dot {{ $tone }}"></span> {{ $cat }}</span>
                        <span class="d">{{ $detail }}</span>
                        <span class="r">✨ {{ $rec }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Required vendors --}}
            <div class="va-card">
                <div class="va-card-hd">🧩 Required Vendors & Services</div>
                <div class="va-vendors">
                    @foreach($vendors as [$name, $emoji])
                        <div class="va-vd"><div class="e">{{ $emoji }}</div><span>{{ $name }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="va-rail">
            <div class="va-pan va-score-ring">
                <h4 style="justify-content:center;">Overall Venue Score</h4>
                <b>{{ $venue['score'] }}</b><span>/ 100 — Excellent</span>
            </div>
            <div class="va-pan">
                <h4>🚨 AI Alerts</h4>
                @foreach($alerts as $a)<div class="va-alert">{{ $a }}</div>@endforeach
            </div>
            <div class="va-pan">
                <h4>💸 Hidden Costs to Plan For</h4>
                @foreach($hidden_costs as [$item, $cost])<div class="va-hc"><span>{{ $item }}</span><b>{{ $cost }}</b></div>@endforeach
            </div>
        </aside>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('vaForm');
    if (!form) return;
    const go = document.getElementById('vaGo');
    const loading = document.getElementById('vaLoading');
    const out = document.getElementById('vaOut');
    const errEl = document.getElementById('vaErr');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const LEVEL = document.querySelector('.va')?.dataset.level || 'maximum';
    let lastGuests = 0; // captured from the form so "Help Me Plan" can recompute utilization live

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const num = n => Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        go.disabled = true;

        const fd = new FormData(form);
        const payload = Object.fromEntries(fd.entries());
        payload.has_dancefloor = fd.get('has_dancefloor') ? true : false;
        lastGuests = parseInt(payload.guest_count, 10) || 0;

        try {
            const r = await fetch('{{ route("ai-tools.venue-analyzer.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            go.disabled = false;
            if (!data.success) {
                errEl.textContent = data.message || 'Could not analyze the space. Please check your inputs.';
                errEl.classList.add('open');
                return;
            }
            render(data.result);
            if (window.aiAttachSet) window.aiAttachSet('Venue analysis', data.result);
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
        // "Help Me Plan" renders the key figures/verdict/notes as editable fields
        // and recomputes utilization live; "Coordinate It For Me" is read-only.
        const editable = LEVEL === 'semi';

        const v = res.verdict || '';
        const cls = v.startsWith('Over') ? 'over' : (v.startsWith('Tight') ? 'tight' : 'good');
        const icon = cls === 'over' ? '🚫' : (cls === 'tight' ? '⚠️' : '✅');
        const verdictEl = document.getElementById('vaVerdict');
        verdictEl.className = 'va-verdict ' + cls;
        verdictEl.innerHTML = editable
            ? '<span>' + icon + '</span><input class="va-edit" value="' + esc(v) + '">'
            : '<span>' + icon + '</span><span>' + esc(v) + '</span>';

        const reqEl = document.getElementById('vaReq');
        const capEl = document.getElementById('vaCap');
        const utilEl = document.getElementById('vaUtil');
        if (editable) {
            reqEl.innerHTML = '<input class="va-edit" type="number" min="0" value="' + (Number(res.required_sqft) || 0) + '">';
            capEl.innerHTML = '<input class="va-edit" id="vaCapEdit" type="number" min="0" value="' + (Number(res.max_capacity) || 0) + '">';
            utilEl.textContent = res.utilization_pct + '%';
            // Editing the estimated capacity recomputes utilization from the guests submitted.
            const capInput = document.getElementById('vaCapEdit');
            capInput?.addEventListener('input', function () {
                const cap = parseInt(capInput.value, 10) || 0;
                const util = cap > 0 ? Math.round((lastGuests / cap) * 1000) / 10 : 0;
                utilEl.textContent = util + '%';
            });
        } else {
            reqEl.textContent = num(res.required_sqft);
            capEl.textContent = num(res.max_capacity);
            utilEl.textContent = res.utilization_pct + '%';
        }

        // Area breakdown — read-only qualitative statuses in both modes.
        document.getElementById('vaBreakdown').innerHTML = (res.breakdown || []).map(b => `
            <div class="va-bd"><span>${esc(b.area)}</span><span class="st ${esc(b.status)}">${esc(b.status)}</span></div>`).join('');

        const tipsEl = document.getElementById('vaTips');
        tipsEl.innerHTML = editable
            ? (res.tips || []).map(t => '<div style="margin-bottom:8px;"><input class="va-edit" value="' + esc(t) + '"></div>').join('')
            : (res.tips || []).map(t => `<div class="va-alert" style="padding-left:22px;">${esc(t)}</div>`).join('');
    }
})();

// Do It Myself — hand-built venue scorecard (no AI, no server call).
(function () {
    const rows = document.getElementById('vamRows');
    const add  = document.getElementById('vamAdd');
    const scoreEl   = document.getElementById('vamScore');
    const verdictEl = document.getElementById('vamVerdict');
    if (!rows || !add) return;

    function verdictFor(score) {
        if (score >= 85) return ['Excellent venue', '#15803d'];
        if (score >= 70) return ['Good venue', '#16a34a'];
        if (score >= 50) return ['Fair — some trade-offs', '#d97706'];
        return ['Weak fit — keep looking', '#dc2626'];
    }
    function recalc() {
        const scores = Array.from(rows.querySelectorAll('input[type="range"]')).map(function (i) { return parseInt(i.value, 10) || 0; });
        const avg = scores.length ? Math.round(scores.reduce(function (a, b) { return a + b; }, 0) / scores.length) : 0;
        if (scoreEl) scoreEl.textContent = avg;
        if (verdictEl) { const vf = verdictFor(avg); verdictEl.textContent = vf[0]; verdictEl.style.color = vf[1]; }
    }
    function addRow(name, score) {
        if (score == null) score = 75;
        const row = document.createElement('div');
        row.className = 'va-frow';
        row.innerHTML =
            '<input type="text" class="va-inp fname" placeholder="Factor (e.g. Parking)">' +
            '<input type="range" min="0" max="100" value="' + score + '">' +
            '<span class="fscore">' + score + '</span>' +
            '<button type="button" class="va-mdel" title="Remove">&times;</button>';
        row.querySelector('input[type="text"]').value = name || '';
        const range = row.querySelector('input[type="range"]');
        const out   = row.querySelector('.fscore');
        range.addEventListener('input', function () { out.textContent = range.value; recalc(); });
        row.querySelector('.va-mdel').addEventListener('click', function () { row.remove(); recalc(); });
        rows.appendChild(row);
    }
    // Seed the common venue factors the user can rescore, rename or remove.
    [['Capacity', 85], ['Location', 80], ['Price', 70], ['Amenities', 75], ['Accessibility', 65]]
        .forEach(function (f) { addRow(f[0], f[1]); });
    add.addEventListener('click', function () { addRow(); });
    recalc();
})();
</script>
@endpush
