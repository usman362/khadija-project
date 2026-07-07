@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Package Builder')
@section('page-title', 'AI Package Builder')
@section('page-subtitle', 'Build, price & compare your service packages')

{{-- AI Package Builder — Layer-3 aware. The SAME tool renders a different
     experience per the user's AI level ($level): manual / semi / maximum.
     Representative deterministic engine. --}}

@php
    $isManual = $level === 'manual';
    $isSemi   = $level === 'semi';
    $isMax    = $level === 'maximum';
    $isLocked = $level === 'none';
    $lvlMeta  = [
        'manual'  => ['Manual', '#64748b', 'You build packages by hand — templates & structure, no AI.'],
        'semi'    => ['Semi-Assisted', '#2563eb', 'AI suggests prices, descriptions and add-ons — you review and approve.'],
        'maximum' => ['Maximum AI', '#16a34a', 'Enter a service and let AI auto-generate the whole tiered package.'],
        'none'    => ['Locked', '#ef4444', 'This tool is currently unavailable.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['manual'];
@endphp

@push('styles')
<style>
    .pb { --pb: #6366f1; --pb-strong: #4f46e5; }

    /* Level banner */
    .pb-levelbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: var(--bg-card); border: 1px solid var(--border-color); border-left: 4px solid var(--lvl,#64748b); border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; }
    .pb-lvltag { font-size: 10.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: #fff; background: var(--lvl,#64748b); padding: 4px 11px; border-radius: 999px; }
    .pb-levelbar .d { font-size: 12.5px; color: var(--text-secondary); }
    .pb-upsell { margin-left: auto; font-size: 12px; font-weight: 700; color: var(--pb); text-decoration: none; }
    .pb-upsell:hover { text-decoration: underline; }

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

    /* Interactive builder */
    .pb-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .pb-tool h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .pb-tool .sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
    .pb-form { display: grid; grid-template-columns: 1fr 200px; gap: 14px; }
    .pb-form .full { grid-column: 1 / -1; }
    .pb-lblrow { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .pb-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .pb-in, .pb-area { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .pb-area { min-height: 78px; resize: vertical; }
    .pb-in:focus, .pb-area:focus { outline: none; border-color: var(--pb); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .pb-assist { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 800; color: var(--pb); background: rgba(99,102,241,.1); border: 1px solid rgba(99,102,241,.25); border-radius: 999px; padding: 3px 9px; cursor: pointer; }
    .pb-assist:hover { background: rgba(99,102,241,.18); }
    .pb-assist[disabled] { opacity: .5; cursor: default; }
    .pb-go { border: none; border-radius: 10px; padding: 11px 20px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--pb), var(--pb-strong)); cursor: pointer; }
    .pb-save { border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); border-radius: 10px; padding: 11px 20px; font-size: 13px; font-weight: 800; cursor: pointer; }
    .pb-err { display: none; margin-top: 14px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .pb-err.open { display: block; }
    .pb-note { display: none; margin-top: 8px; font-size: 11.5px; color: #16a34a; }
    .pb-note.open { display: block; }
    .pb-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 12.5px; }
    .pb-loading.open { display: block; }
    .pb-spin { width: 40px; height: 40px; border: 3px solid rgba(99,102,241,.2); border-top-color: var(--pb); border-radius: 50%; margin: 0 auto 12px; animation: pbSpin .8s linear infinite; }
    @keyframes pbSpin { to { transform: rotate(360deg); } }
    .pb-out { display: none; margin-top: 16px; }
    .pb-out.open { display: block; animation: pbFade .3s ease; }
    @keyframes pbFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .pb-out-sum { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; background: rgba(99,102,241,.07); border: 1px dashed var(--pb); border-radius: 10px; padding: 11px; margin-bottom: 14px; }
    .pb-out-tiers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 14px; }
    .pb-out-tier { background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; }
    .pb-out-tier h4 { font-size: 14px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pb-out-tier .p { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 4px 0 10px; }
    .pb-out-tier ul { list-style: none; margin: 0; padding: 0; }
    .pb-out-tier li { font-size: 11.5px; color: var(--text-secondary); padding: 3px 0 3px 16px; position: relative; } .pb-out-tier li::before { content: '✓'; position: absolute; left: 0; color: #16a34a; font-weight: 800; }
    .pb-out-tips { list-style: none; margin: 0; padding: 0; }
    .pb-out-tips li { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; padding: 7px 0 7px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .pb-out-tips li:last-child { border-bottom: none; } .pb-out-tips li::before { content: '✨'; position: absolute; left: 0; top: 6px; }
    .pb-locked { text-align: center; padding: 40px; color: var(--text-muted); }
    @media (max-width: 700px) { .pb-form { grid-template-columns: 1fr; } .pb-out-tiers { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pb" data-level="{{ $level }}" style="--lvl: {{ $lvlColor }};">

    {{-- Level banner --}}
    <div class="pb-levelbar">
        <span class="pb-lvltag">{{ $lvlLabel }}</span>
        <span class="d">{{ $lvlDesc }}</span>
        @unless($isMax || $isLocked)
            <a class="pb-upsell" href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}">Upgrade for more AI →</a>
        @endunless
    </div>

    @if($isLocked)
        <div class="pb-card"><div class="pb-locked">🔒 This tool has been turned off by the administrator.</div></div>
    @else

    {{-- ══ Builder — changes by level ══ --}}
    <div class="pb-tool">
        @if($isMax)
            <h3>🤖 Auto-Generate Full Package</h3>
            <div class="sub">Enter your service and base price — AI builds the complete tiered package for you.</div>
        @else
            <h3>🧰 Build Your Package</h3>
            <div class="sub">{{ $isSemi ? 'Fill in your package — use the ✨ helpers for AI suggestions you can edit.' : 'Fill in your package details. (AI assistance unlocks on higher membership tiers.)' }}</div>
        @endif

        <form id="pbForm" class="pb-form" autocomplete="off">
            <div class="full">
                <div class="pb-lblrow"><label class="pb-lbl">Package / Service Name</label></div>
                <input type="text" name="service_name" id="pbName" class="pb-in" maxlength="120" required placeholder="e.g. Wedding Photography">
            </div>

            @unless($isMax)
                <div class="full">
                    <div class="pb-lblrow">
                        <label class="pb-lbl">Description</label>
                        @if($isSemi)<button type="button" class="pb-assist" data-assist="description">✨ Improve writing</button>@endif
                    </div>
                    <textarea name="description" id="pbDesc" class="pb-area" maxlength="2000" placeholder="Describe what this package includes..."></textarea>
                </div>
            @endunless

            <div>
                <div class="pb-lblrow">
                    <label class="pb-lbl">Base Price ($)</label>
                    @if($isSemi)<button type="button" class="pb-assist" data-assist="price">✨ Suggest price</button>@endif
                </div>
                <input type="number" name="base_price" id="pbPrice" class="pb-in" min="1" step="0.01" {{ $isMax ? 'required' : '' }} placeholder="e.g. 1250">
            </div>

            <div class="{{ $isMax ? '' : 'full' }}">
                <div class="pb-lblrow">
                    <label class="pb-lbl">Add-ons (comma separated)</label>
                    @if($isSemi)<button type="button" class="pb-assist" data-assist="addons">✨ Suggest add-ons</button>@endif
                </div>
                <input type="text" name="addons" id="pbAddons" class="pb-in" maxlength="600" placeholder="e.g. Extra hour, Second shooter, Prints">
            </div>

            <div class="full">
                @if($isMax)
                    <button type="submit" class="pb-go" id="pbSubmit" data-action="full">🤖 Generate Full Package</button>
                @else
                    <button type="button" class="pb-save" id="pbSave">💾 Save Package</button>
                @endif
            </div>
        </form>

        <div class="pb-note" id="pbAssistNote"></div>
        <div class="pb-err" id="pbError"></div>
        <div class="pb-loading" id="pbLoading"><div class="pb-spin"></div>Working…</div>
        <div class="pb-out" id="pbOut">
            <div class="pb-out-sum" id="pbSum"></div>
            <div class="pb-out-tiers" id="pbTiers"></div>
            <ul class="pb-out-tips" id="pbTips"></ul>
        </div>
    </div>
    @endif

    {{-- ══ Example showcase (shared) ══ --}}
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

    {{-- AI suggestions only appear when the user actually has AI (semi/maximum) --}}
    @if($isSemi || $isMax)
    <div class="pb-card">
        <div class="pb-card-hd">✨ AI Package Suggestions</div>
        @foreach($suggestions as $s)<div class="pb-sugg">{{ $s }}</div>@endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const root = document.querySelector('.pb');
    const form = document.getElementById('pbForm');
    if (!form) return;
    const level = root.getAttribute('data-level');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = '{{ route("ai-tools.package-builder.compute") }}';
    const loading = document.getElementById('pbLoading');
    const out = document.getElementById('pbOut');
    const errEl = document.getElementById('pbError');
    const note = document.getElementById('pbAssistNote');

    async function call(payload) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        return r.json();
    }
    function showErr(m) { errEl.textContent = m; errEl.classList.add('open'); }
    function clearMsgs() { errEl.classList.remove('open'); note.classList.remove('open'); }

    // ── Maximum: full auto-generate ──
    const submit = document.getElementById('pbSubmit');
    if (submit) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearMsgs(); out.classList.remove('open'); loading.classList.add('open'); submit.disabled = true;
            try {
                const payload = Object.fromEntries(new FormData(form).entries());
                payload.action = 'full';
                const data = await call(payload);
                loading.classList.remove('open'); submit.disabled = false;
                if (!data.success) { showErr(data.message || 'Could not build packages.'); return; }
                renderFull(data.result); out.classList.add('open'); out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (err) { loading.classList.remove('open'); submit.disabled = false; showErr('Network error. Please try again.'); }
        });
    }

    // ── Semi: per-field ✨ assist buttons ──
    document.querySelectorAll('.pb-assist').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            clearMsgs();
            const action = btn.getAttribute('data-assist');
            btn.disabled = true; const label = btn.textContent; btn.textContent = '…';
            try {
                const data = await call({
                    action,
                    service_name: document.getElementById('pbName')?.value || '',
                    description: document.getElementById('pbDesc')?.value || '',
                });
                btn.disabled = false; btn.textContent = label;
                if (!data.success) { showErr(data.message || 'Could not get a suggestion.'); return; }
                const res = data.result || {};
                if (action === 'price' && res.price != null)     document.getElementById('pbPrice').value = res.price;
                if (action === 'description' && res.description)  document.getElementById('pbDesc').value = res.description;
                if (action === 'addons' && res.addons)            document.getElementById('pbAddons').value = res.addons;
                if (res.note) { note.textContent = '✨ ' + res.note; note.classList.add('open'); }
            } catch (err) { btn.disabled = false; btn.textContent = label; showErr('Network error. Please try again.'); }
        });
    });

    // ── Manual / Semi: Save (representative) ──
    const save = document.getElementById('pbSave');
    if (save) save.addEventListener('click', function () {
        clearMsgs();
        if (!document.getElementById('pbName').value.trim()) { showErr('Enter a package name first.'); return; }
        note.textContent = '✓ Package saved. (Demo — persistence wires up with the packages backend.)';
        note.classList.add('open');
    });

    function renderFull(res) {
        document.getElementById('pbSum').textContent = res.summary || '';
        const tiers = document.getElementById('pbTiers'); tiers.innerHTML = '';
        (res.tiers || []).forEach(t => {
            const div = document.createElement('div'); div.className = 'pb-out-tier';
            const items = (t.includes || []).map(i => `<li>${esc(i)}</li>`).join('');
            div.innerHTML = `<h4>${esc(t.name)}</h4><div class="p">$${fmt(t.price)}</div><ul>${items}</ul>`;
            tiers.appendChild(div);
        });
        const tips = document.getElementById('pbTips'); tips.innerHTML = '';
        (res.tips || []).forEach(t => { const li = document.createElement('li'); li.textContent = t; tips.appendChild(li); });
    }
    function fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
})();
</script>
@endpush
@endsection
