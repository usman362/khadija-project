@extends($aiLayout ?? 'layouts.client')

@section('title', 'Guest Capacity Calculator')
@section('page-title', 'Guest Capacity Calculator')
@section('page-subtitle', 'Comfort, flow and legal capacity for your guest count')

{{-- Guest Capacity Calculator (client). Capacity stats + heatmap simulation +
     capacity insights + what-if sliders. Representative data. --}}

@push('styles')
<style>
    .gc { --gc: var(--brand, #0ea5e9); }
    .gc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .gc-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .gc-stat b { display: block; font-size: 25px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .gc-stat.good b { color: #16a34a; }
    .gc-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .gc-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 18px; align-items: start; }
    .gc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .gc-card-hd { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px; }
    .gc-card-hd h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .gc-legend { display: flex; gap: 12px; }
    .gc-leg { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: var(--text-secondary); }
    .gc-leg i { width: 10px; height: 10px; border-radius: 3px; }

    .gc-map { position: relative; height: 300px; margin: 14px; border-radius: 12px; overflow: hidden; background: #0e1726;
        background-image:
            radial-gradient(circle at 70% 78%, rgba(220,38,38,.55), transparent 22%),
            radial-gradient(circle at 45% 50%, rgba(234,179,8,.45), transparent 26%),
            radial-gradient(circle at 25% 30%, rgba(22,163,74,.45), transparent 26%),
            linear-gradient(160deg, #16271d, #0e1726); }
    .gc-table { position: absolute; width: 30px; height: 30px; border-radius: 50%; border: 2px dashed rgba(255,255,255,.6); transform: translate(-50%,-50%); }
    .gc-stage { position: absolute; left: 50%; top: 8%; transform: translateX(-50%); font-size: 10px; font-weight: 800; color: #fff; background: rgba(255,255,255,.18); padding: 3px 14px; border-radius: 6px; }

    .gc-ins { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; padding: 14px 16px; }
    .gc-in { border: 1px solid var(--border-color); border-radius: 11px; padding: 11px 13px; }
    .gc-in h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .gc-in .s { font-size: 11px; font-weight: 800; margin-top: 3px; } .gc-in .s.good { color: #16a34a; } .gc-in .s.warn { color: #d97706; }

    .gc-bars { padding: 16px; }
    .gc-cap { margin-bottom: 14px; }
    .gc-cap-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; } .gc-cap-top b { font-weight: 800; color: var(--text-primary); } .gc-cap-top span { color: var(--text-muted); }
    .gc-track { height: 12px; border-radius: 999px; background: var(--border-color); position: relative; overflow: hidden; }
    .gc-track > i { position: absolute; left: 0; top: 0; height: 100%; border-radius: 999px; }
    .gc-marker { position: absolute; top: -4px; width: 2px; height: 20px; background: var(--text-primary); }

    .gc-rail { display: flex; flex-direction: column; gap: 16px; }
    .gc-pan { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .gc-pan h4 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .gc-slider { margin-bottom: 14px; }
    .gc-slider label { display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; } .gc-slider label b { color: var(--gc); }
    .gc-slider input { width: 100%; accent-color: var(--gc); }
    .gc-tip { font-size: 12px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .gc-tip:last-child { border-bottom: none; } .gc-tip::before { content: '✨'; position: absolute; left: 2px; top: 7px; }

    @media (max-width: 1000px) { .gc-grid { grid-template-columns: minmax(0,1fr); } .gc-stats, .gc-ins { grid-template-columns: 1fr 1fr; } }

    /* Compute form */
    .gc-gen { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .gc-gen h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .gc-gen .sub { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .gc-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .gc-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .gc-field input, .gc-field select { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .gc-field input:focus, .gc-field select:focus { outline: none; border-color: var(--gc); }
    .gc-gen-btn { margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .gc-gen-btn:disabled { opacity: .6; cursor: not-allowed; }
    .gc-err { display: none; margin-top: 12px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 12.5px; }
    .gc-err.on { display: block; }
    /* Help Me Plan — editable capacity figure inside a stat card */
    .gc-edit { width: 100%; max-width: 130px; padding: 4px 8px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-body, var(--bg-card)); color: var(--text-primary); font-size: 22px; font-weight: 800; font-family: inherit; }
    .gc-edit:focus { outline: none; border-color: var(--gc); box-shadow: 0 0 0 3px rgba(14,165,233,.15); }
    .gc-mano { margin-top: 4px; }
    .gc-note { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin-top: 4px; }
    @media (max-width: 700px) { .gc-form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Enter your own room size, guests and space-per-guest — the math runs right here, no AI.'],
        'semi'    => ['Help Me Plan', '#0ea5e9', 'AI estimates your capacity — adjust the comfort and legal figures and the score updates live.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your space and AI works out comfort, legal capacity and flow for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="gc" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:#0284c7;text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built capacity calculator, no AI, computed client-side --}}
    <div class="gc-gen">
        <h3>📐 Build My Capacity Estimate</h3>
        <div class="sub">Enter your own numbers and adjust space-per-guest — the math runs right here, no AI.</div>
        <div class="gc-form-grid">
            <div class="gc-field">
                <label>Room Size (sq ft)</label>
                <input type="number" id="gcmSqft" min="20" step="1" value="2400">
            </div>
            <div class="gc-field">
                <label>Layout</label>
                <select id="gcmLayout">
                    <option value="12">Banquet (seated dining)</option>
                    <option value="8">Theater (rows of chairs)</option>
                    <option value="6">Cocktail (standing / mingling)</option>
                    <option value="10">Mixed</option>
                </select>
            </div>
            <div class="gc-field">
                <label>Space per Guest (sq ft)</label>
                <input type="number" id="gcmPer" min="1" step="0.5" value="12">
            </div>
            <div class="gc-field">
                <label>Expected Guests</label>
                <input type="number" id="gcmGuests" min="1" max="100000" value="185">
            </div>
        </div>
        <div class="gc-mano">
            <div class="gc-stats" id="gcmStats"></div>
            <div class="gc-card">
                <div class="gc-card-hd"><h3>⚖️ Legal vs Comfort Capacity</h3></div>
                <div class="gc-bars">
                    <div class="gc-cap">
                        <div class="gc-cap-top" id="gcmCapTop"></div>
                        <div class="gc-track" id="gcmTrack"></div>
                    </div>
                    <p class="gc-note" id="gcmNote"></p>
                </div>
            </div>
            <div style="font-size:12px;color:var(--text-muted);">Want AI to estimate flow, insights and tips automatically? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:#0284c7;font-weight:700;text-decoration:none;">Upgrade →</a></div>
        </div>
    </div>
    @else
    {{-- AI planner (Help Me Plan / Coordinate It For Me) --}}
    <div class="gc-gen">
        <h3>📐 Estimate Your Capacity</h3>
        <div class="sub">{{ $isSemi ? 'AI estimates comfort and legal capacity — you can adjust the figures and the score recalculates.' : 'Enter your space and guest details and AI estimates comfort, legal capacity and flow insights.' }}</div>
        <form id="gcForm">
            <div class="gc-form-grid">
                <div class="gc-field">
                    <label>Room Size (sq ft)</label>
                    <input type="number" name="room_sqft" min="20" step="1" required placeholder="e.g. 2400">
                </div>
                <div class="gc-field">
                    <label>Seating Style</label>
                    <select name="seating_style" required>
                        <option value="banquet">Banquet (seated dining)</option>
                        <option value="theater">Theater (rows of chairs)</option>
                        <option value="cocktail">Cocktail (standing / mingling)</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <div class="gc-field">
                    <label>Expected Guests</label>
                    <input type="number" name="guest_count" min="1" max="100000" required placeholder="e.g. 185">
                </div>
            </div>
            <button type="submit" class="gc-gen-btn" id="gcSubmit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4"/><path d="M22 12a10 10 0 1 0-8.5 9.87"/><path d="M12 6v6l4 2"/></svg>
                {{ $isSemi ? 'Suggest Capacity Plan' : 'Build My Capacity Plan' }}
            </button>
            <div class="gc-err" id="gcErr"></div>
        </form>
    </div>

    <div class="gc-stats" id="gcStats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="gc-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="gc-grid">
        <div>
            {{-- Heatmap --}}
            <div class="gc-card">
                <div class="gc-card-hd">
                    <h3>🗺 Venue Layout &amp; Heatmap Simulation</h3>
                    <div class="gc-legend">
                        <span class="gc-leg"><i style="background:#16a34a;"></i> Low</span>
                        <span class="gc-leg"><i style="background:#eab308;"></i> Medium</span>
                        <span class="gc-leg"><i style="background:#dc2626;"></i> High</span>
                    </div>
                </div>
                <div class="gc-map">
                    <span class="gc-stage">STAGE</span>
                    @foreach($tables as [$x, $y])<span class="gc-table" style="left: {{ $x }}%; top: {{ $y }}%;"></span>@endforeach
                </div>
            </div>

            {{-- Insights --}}
            <div class="gc-card">
                <div class="gc-card-hd"><h3>📊 Capacity Insights</h3></div>
                <div class="gc-ins" id="gcInsights">
                    @foreach($insights as [$name, $rating, $tone])
                        <div class="gc-in"><h6>{{ $name }}</h6><div class="s {{ $tone }}">{{ $rating }}</div></div>
                    @endforeach
                </div>
            </div>

            {{-- Legal vs comfort --}}
            <div class="gc-card">
                <div class="gc-card-hd"><h3>⚖️ Legal vs Comfort Capacity</h3></div>
                <div class="gc-bars">
                    <div class="gc-cap">
                        <div class="gc-cap-top" id="gcCapTop"><b>Your event: {{ $capacity['expected'] }} guests</b><span>Comfort {{ $capacity['comfort'] }} · Legal {{ $capacity['legal'] }}</span></div>
                        <div class="gc-track" id="gcTrack">
                            <i style="width: {{ round($capacity['comfort']/$capacity['legal']*100) }}%; background: rgba(22,163,74,.4);"></i>
                            <i style="width: {{ round($capacity['expected']/$capacity['legal']*100) }}%; background: #16a34a;"></i>
                            <span class="gc-marker" style="left: {{ round($capacity['comfort']/$capacity['legal']*100) }}%;" title="Comfort"></span>
                        </div>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted);" id="gcCapNote">You’re <b style="color:#16a34a;">comfortably under</b> capacity — room for {{ $capacity['comfort'] - $capacity['expected'] }} more guests before it feels tight.</p>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="gc-rail">
            <div class="gc-pan">
                <h4>🔧 What-If Adjustments</h4>
                <div class="gc-slider"><label>Guest Count <b>185</b></label><input type="range" min="50" max="280" value="185"></div>
                <div class="gc-slider"><label>Event Duration <b>5 hrs</b></label><input type="range" min="2" max="10" value="5"></div>
                <div class="gc-slider"><label>Crowd Profile <b>Balanced</b></label><input type="range" min="0" max="2" value="1"></div>
                <div class="gc-slider"><label>Service Level <b>Seated</b></label><input type="range" min="0" max="2" value="2"></div>
            </div>
            <div class="gc-pan">
                <h4>✨ AI Tips</h4>
                <div id="gcTips">@foreach($tips as $t)<div class="gc-tip">{{ $t }}</div>@endforeach</div>
            </div>
        </aside>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('gcForm');
    if (!form) return;

    const submit = document.getElementById('gcSubmit');
    const errEl  = document.getElementById('gcErr');
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const LEVEL  = document.querySelector('.gc')?.dataset.level || 'maximum';

    // Comfort score from guests vs comfort capacity — mirrors the server formula
    // so "Help Me Plan" can recompute live when the user edits the figures.
    const state = { expected: 0, comfort: 0, legal: 0 };
    function computeScore(guests, comfort) {
        comfort = Math.max(comfort, 1);
        const ratio = guests / comfort;
        let pct;
        if (ratio <= 0.75) pct = 100;
        else if (ratio <= 1.0) pct = Math.round(100 - ((ratio - 0.75) / 0.25) * 20);
        else if (ratio <= 1.5) pct = Math.round(80 - ((ratio - 1.0) / 0.5) * 40);
        else pct = Math.max(5, Math.round(40 - ((ratio - 1.5) * 30)));
        return Math.max(0, Math.min(100, pct));
    }
    const scoreTone   = pct => pct >= 80 ? 'good' : (pct >= 50 ? 'warn' : '');
    const comfortTone = (guests, comfort) => guests <= comfort ? 'good' : 'warn';

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('on');
        submit.disabled = true;
        const prev = submit.innerHTML;
        submit.innerHTML = 'Estimating…';

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.guest-capacity.compute") }}', {
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
            submit.disabled = false;
            submit.innerHTML = prev;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not estimate capacity.';
                errEl.classList.add('on');
                return;
            }
            render(data.result);
        } catch (err) {
            submit.disabled = false;
            submit.innerHTML = prev;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('on');
        }
    });

    function statCard(label, value, tone, id) {
        return '<div class="gc-stat ' + esc(tone) + '"' + (id ? ' id="' + id + '"' : '') +
            '><b>' + esc(value) + '</b><div class="l">' + esc(label) + '</div></div>';
    }
    function editStatCard(label, value, tone, capKey) {
        return '<div class="gc-stat ' + esc(tone) + '">' +
            '<input type="number" class="gc-edit" data-cap="' + capKey + '" min="1" value="' + esc(value) + '">' +
            '<div class="l">' + esc(label) + '</div></div>';
    }

    // Capacity bar + note — reads the current figures (edited or as-returned).
    function renderCapacity(expected, comfort, legal) {
        const L = Math.max(legal, 1);
        const comfortPct  = Math.min(100, Math.round(comfort / L * 100));
        const expectedPct = Math.min(100, Math.round(expected / L * 100));
        document.getElementById('gcCapTop').innerHTML =
            '<b>Your event: ' + esc(expected) + ' guests</b>' +
            '<span>Comfort ' + esc(comfort) + ' · Legal ' + esc(legal) + '</span>';
        document.getElementById('gcTrack').innerHTML =
            '<i style="width:' + comfortPct + '%; background: rgba(22,163,74,.4);"></i>' +
            '<i style="width:' + expectedPct + '%; background:#16a34a;"></i>' +
            '<span class="gc-marker" style="left:' + comfortPct + '%;" title="Comfort"></span>';
        const room = comfort - expected;
        document.getElementById('gcCapNote').innerHTML = room >= 0
            ? 'You’re <b style="color:#16a34a;">comfortably under</b> the comfort estimate — room for about ' + room + ' more guests before it feels tight.'
            : 'You’re about <b style="color:#d97706;">' + Math.abs(room) + ' guests over</b> the comfort estimate — consider a larger space or a different seating style.';
    }

    // Help Me Plan — recompute the score + bar live when a figure is edited.
    function onSemiEdit() {
        const cEl = document.querySelector('.gc-edit[data-cap="comfort"]');
        const lEl = document.querySelector('.gc-edit[data-cap="legal"]');
        state.comfort = Math.max(parseInt(cEl?.value, 10) || 0, 0);
        state.legal   = Math.max(parseInt(lEl?.value, 10) || 0, 0);
        const score = computeScore(state.expected, state.comfort);
        const scoreCard = document.getElementById('gcScoreCard');
        if (scoreCard) {
            scoreCard.className = 'gc-stat ' + scoreTone(score);
            scoreCard.querySelector('b').textContent = score + '%';
        }
        if (cEl) cEl.closest('.gc-stat').className = 'gc-stat ' + comfortTone(state.expected, state.comfort);
        renderCapacity(state.expected, state.comfort, state.legal);
    }

    function render(res) {
        const cap = res.capacity || {};
        state.expected = Number(cap.expected) || 0;
        state.comfort  = Number(cap.comfort)  || 0;
        state.legal    = Number(cap.legal)    || 0;
        const score = res.comfort_score_pct != null ? res.comfort_score_pct : computeScore(state.expected, state.comfort);

        // Stats — in "Help Me Plan" the comfort + legal figures are editable.
        const stats = document.getElementById('gcStats');
        if (LEVEL === 'semi') {
            stats.innerHTML =
                statCard('Expected Guests', state.expected, '') +
                editStatCard('Recommended Capacity', state.comfort, comfortTone(state.expected, state.comfort), 'comfort') +
                statCard('Guest Comfort Score', score + '%', scoreTone(score), 'gcScoreCard') +
                editStatCard('Legal Capacity', state.legal, '', 'legal');
            stats.querySelectorAll('.gc-edit').forEach(function (inp) { inp.addEventListener('input', onSemiEdit); });
        } else {
            stats.innerHTML =
                statCard('Expected Guests', state.expected, '') +
                statCard('Recommended Capacity', state.comfort, comfortTone(state.expected, state.comfort)) +
                statCard('Guest Comfort Score', score + '%', scoreTone(score)) +
                statCard('Legal Capacity', state.legal, '');
        }

        // Insights — the AI's qualitative read, shown read-only in both modes.
        const ins = document.getElementById('gcInsights');
        ins.innerHTML = (res.insights || []).map(function (i) {
            return '<div class="gc-in"><h6>' + esc(i[0]) + '</h6><div class="s ' + esc(i[2]) + '">' + esc(i[1]) + '</div></div>';
        }).join('');

        renderCapacity(state.expected, state.comfort, state.legal);

        // Tips
        document.getElementById('gcTips').innerHTML = (res.tips || []).map(function (t) {
            return '<div class="gc-tip">' + esc(t) + '</div>';
        }).join('');
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
        });
    }
})();

// Do It Myself — hand-built capacity calculator (no AI, no server call).
(function () {
    const sqftEl   = document.getElementById('gcmSqft');
    const layoutEl = document.getElementById('gcmLayout');
    const perEl    = document.getElementById('gcmPer');
    const guestsEl = document.getElementById('gcmGuests');
    const statsEl  = document.getElementById('gcmStats');
    const topEl    = document.getElementById('gcmCapTop');
    const trackEl  = document.getElementById('gcmTrack');
    const noteEl   = document.getElementById('gcmNote');
    if (!sqftEl || !statsEl || !trackEl) return;

    const scard = (label, value, tone) =>
        '<div class="gc-stat ' + tone + '"><b>' + value + '</b><div class="l">' + label + '</div></div>';

    function calc() {
        const sqft   = Math.max(parseFloat(sqftEl.value) || 0, 0);
        const per    = Math.max(parseFloat(perEl.value) || 1, 1);
        const guests = Math.max(parseInt(guestsEl.value, 10) || 0, 0);
        const comfort = Math.max(Math.floor(sqft / per), 0);
        const legal   = Math.max(Math.floor(sqft / 7), 0); // ~7 sq ft/person life-safety rule of thumb
        const room    = comfort - guests;

        statsEl.innerHTML =
            scard('Expected Guests', guests, '') +
            scard('Recommended Capacity', comfort, guests <= comfort ? 'good' : 'warn') +
            scard('Legal Capacity', legal, '');

        const L = Math.max(legal, 1);
        const comfortPct  = Math.min(100, Math.round(comfort / L * 100));
        const expectedPct = Math.min(100, Math.round(guests / L * 100));
        topEl.innerHTML = '<b>Your event: ' + guests + ' guests</b><span>Comfort ' + comfort + ' · Legal ' + legal + '</span>';
        trackEl.innerHTML =
            '<i style="width:' + comfortPct + '%; background: rgba(22,163,74,.4);"></i>' +
            '<i style="width:' + expectedPct + '%; background:#16a34a;"></i>' +
            '<span class="gc-marker" style="left:' + comfortPct + '%;" title="Comfort"></span>';
        noteEl.innerHTML = room >= 0
            ? 'Estimated room for about <b style="color:#16a34a;">' + room + ' more guests</b> before spacing feels tight (comfort estimate ' + comfort + ').'
            : 'About <b style="color:#d97706;">' + Math.abs(room) + ' over</b> the comfort estimate of ' + comfort + ' — consider more space or a tighter layout.';
    }

    // Changing the layout seeds a typical space-per-guest the user can override.
    layoutEl?.addEventListener('change', function () { perEl.value = layoutEl.value; calc(); });
    [sqftEl, perEl, guestsEl].forEach(function (el) { el?.addEventListener('input', calc); });
    calc();
})();
</script>
@endpush
