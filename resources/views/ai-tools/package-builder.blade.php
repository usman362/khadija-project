@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Package Builder')
@section('page-title', 'AI Package Builder')
@section('page-subtitle', 'Build, price & compare your service packages')

{{-- AI Package Builder (professional). Tiered packages + margins + comparison +
     AI suggestions. Representative data. --}}

@push('styles')
<style>
    .pb { --pb: #6366f1; --pb-strong: #4f46e5; }
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
    .pb-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .pb-in { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .pb-in:focus { outline: none; border-color: var(--pb); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .pb-go { border: none; border-radius: 10px; padding: 11px 20px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--pb), var(--pb-strong)); cursor: pointer; }
    .pb-err { display: none; margin-top: 14px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .pb-err.open { display: block; }
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
    @media (max-width: 700px) { .pb-form { grid-template-columns: 1fr; } .pb-out-tiers { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pb">
    {{-- Interactive package builder --}}
    <div class="pb-tool">
        <h3>🧰 Build Your Tiers</h3>
        <div class="sub">Enter a service and base price to generate three estimated tiers with suggested add-on placement.</div>
        <form id="pbForm" class="pb-form">
            <div>
                <label class="pb-lbl">Service Name</label>
                <input type="text" name="service_name" class="pb-in" maxlength="120" required placeholder="e.g. Wedding Photography">
            </div>
            <div>
                <label class="pb-lbl">Base Price ($)</label>
                <input type="number" name="base_price" class="pb-in" min="1" step="0.01" required placeholder="e.g. 1250">
            </div>
            <div class="full">
                <label class="pb-lbl">Add-ons (comma separated, optional)</label>
                <input type="text" name="addons" class="pb-in" maxlength="600" placeholder="e.g. Extra hour, Second shooter, Prints, Album, Drone">
            </div>
            <div class="full">
                <button type="submit" class="pb-go" id="pbSubmit">✨ Build Packages</button>
            </div>
        </form>

        <div class="pb-err" id="pbError"></div>

        <div class="pb-loading" id="pbLoading">
            <div class="pb-spin"></div>
            Building your estimated tiers...
        </div>

        <div class="pb-out" id="pbOut">
            <div class="pb-out-sum" id="pbSum"></div>
            <div class="pb-out-tiers" id="pbTiers"></div>
            <ul class="pb-out-tips" id="pbTips"></ul>
        </div>
    </div>

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

    <div class="pb-card">
        <div class="pb-card-hd">✨ AI Package Suggestions</div>
        @foreach($suggestions as $s)<div class="pb-sugg">{{ $s }}</div>@endforeach
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('pbForm');
    if (!form) return;
    const submit = document.getElementById('pbSubmit');
    const loading = document.getElementById('pbLoading');
    const out = document.getElementById('pbOut');
    const errEl = document.getElementById('pbError');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.package-builder.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not build packages.';
                errEl.classList.add('open');
                return;
            }
            render(data.result);
            out.classList.add('open');
            out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            loading.classList.remove('open');
            submit.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('open');
        }
    });

    function render(res) {
        document.getElementById('pbSum').textContent = res.summary || '';
        const tiers = document.getElementById('pbTiers');
        tiers.innerHTML = '';
        (res.tiers || []).forEach(t => {
            const div = document.createElement('div');
            div.className = 'pb-out-tier';
            const items = (t.includes || []).map(i => `<li>${esc(i)}</li>`).join('');
            div.innerHTML = `<h4>${esc(t.name)}</h4><div class="p">$${fmt(t.price)}</div><ul>${items}</ul>`;
            tiers.appendChild(div);
        });
        const tips = document.getElementById('pbTips');
        tips.innerHTML = '';
        (res.tips || []).forEach(t => {
            const li = document.createElement('li');
            li.textContent = t;
            tips.appendChild(li);
        });
    }
    function fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
})();
</script>
@endpush
@endsection
