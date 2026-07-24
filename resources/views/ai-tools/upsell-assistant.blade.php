@extends($aiLayout ?? 'layouts.professional')

@section('title', 'Upsell Assistant')
@section('page-title', 'Upsell Assistant')
@section('page-subtitle', 'Spot the right add-ons to grow each booking')

@push('styles')
<style>
    .us { --us: var(--brand, #2563eb); }
    .us-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .us-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .us-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; } .us-stat.good b { color: #16a34a; } .us-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .us-grid { display: grid; grid-template-columns: 320px minmax(0,1fr); gap: 18px; align-items: start; }
    .us-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .us-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }
    .us-kv { display: flex; justify-content: space-between; font-size: 13px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); } .us-kv:last-of-type { border-bottom: none; } .us-kv span { color: var(--text-muted); } .us-kv b { color: var(--text-primary); font-weight: 800; }
    .us-toggle { display: flex; gap: 8px; margin: 14px 0; } .us-tg { flex: 1; text-align: center; font-size: 11.5px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; cursor: pointer; color: var(--text-secondary); } .us-tg.on { background: var(--us); border-color: var(--us); color: #fff; }
    .us-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--us), #1d4ed8); cursor: pointer; }
    .us-add { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); } .us-add:last-child { border-bottom: none; }
    .us-chk { width: 20px; height: 20px; border-radius: 6px; border: 2px solid var(--border-color); flex-shrink: 0; }
    .us-add.sel .us-chk { background: var(--us); border-color: var(--us); }
    .us-add-main { flex: 1; min-width: 0; } .us-add-main h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); } .us-add-main span { font-size: 11px; color: var(--text-muted); }
    .us-price { font-size: 14px; font-weight: 800; color: #16a34a; } .us-like { font-size: 10px; font-weight: 800; color: var(--us); background: rgba(37,99,235,.1); padding: 2px 8px; border-radius: 999px; }
    .us-moment { font-size: 12px; color: var(--text-secondary); line-height: 1.5; background: rgba(37,99,235,.07); border: 1px dashed var(--us); border-radius: 10px; padding: 11px; margin-top: 14px; }
    @media (max-width: 1000px) { .us-grid { grid-template-columns: minmax(0,1fr); } .us-stats { grid-template-columns: 1fr 1fr; } }

    /* Interactive upsell finder */
    .us-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .us-tool h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .us-tool p.desc { font-size: 12.5px; color: var(--text-muted); margin-bottom: 16px; }
    .us-form-grid { display: grid; grid-template-columns: 1fr 1fr 180px; gap: 14px; }
    @media (max-width: 800px) { .us-form-grid { grid-template-columns: 1fr; } }
    .us-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .us-field input { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-secondary)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .us-field input:focus { outline: none; border-color: var(--us); box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
    .us-run { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 11px 24px; border: none; border-radius: 10px; background: linear-gradient(135deg, var(--us), #1d4ed8); color: #fff; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .us-run:disabled { opacity: .6; cursor: not-allowed; }
    .us-err { display: none; margin-top: 14px; padding: 11px 14px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .us-err.on { display: block; }
    .us-load { display: none; margin-top: 14px; font-size: 12.5px; color: var(--text-muted); }
    .us-load.on { display: block; }
    .us-res { display: none; margin-top: 18px; }
    .us-res.on { display: block; }
    .us-res-summary { font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; background: rgba(37,99,235,.06); border-left: 3px solid var(--us); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
    .us-up { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
    .us-up:last-child { border-bottom: none; }
    .us-up-main { flex: 1; min-width: 0; } .us-up-main h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .us-up-main span { font-size: 11.5px; color: var(--text-muted); line-height: 1.4; }
    .us-up .p { font-size: 14px; font-weight: 800; color: #16a34a; white-space: nowrap; }
    .us-bundle { margin-top: 16px; background: rgba(37,99,235,.07); border: 1px dashed var(--us); border-radius: 12px; padding: 14px; }
    .us-bundle h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
    .us-bundle-row { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
    .us-bundle-price { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .us-bundle-save { font-size: 12px; font-weight: 800; color: #16a34a; }
    .us-bundle-uplift { font-size: 11.5px; font-weight: 800; color: var(--us); background: rgba(37,99,235,.12); padding: 3px 9px; border-radius: 999px; }
    .us-script { margin-top: 14px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; background: var(--bg-primary, var(--bg-secondary)); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 14px; }
    .us-script .lbl { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); margin-bottom: 6px; }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Build your own add-on list by hand — add each extra and price, see the total. No AI.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'AI suggests add-ons and a bundle — tweak the prices and the totals update.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'AI spots the add-ons, bundles them, writes the pitch and shows the full picture.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="us" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--us,#2563eb);text-decoration:none;">Upgrade for more →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built add-on list, no AI --}}
    <div class="us-tool">
        <h3>➕ Build My Add-on List</h3>
        <p class="desc">Add each upsell you want to offer and its price — we will total the extra revenue. No AI.</p>
        <div id="usmRows" style="display:flex;flex-direction:column;gap:10px;"></div>
        <button type="button" id="usmAdd" style="margin-top:14px;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--us);background:rgba(37,99,235,.09);border:1px solid rgba(37,99,235,.28);border-radius:10px;padding:9px 15px;cursor:pointer;font-family:inherit;">+ Add upsell</button>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border-color);font-size:14px;">Total extra revenue: <b id="usmTotal" style="color:#16a34a;font-size:20px;">$0</b></div>
        <div style="margin-top:12px;font-size:12px;color:var(--text-muted);">Want us to find the best add-ons + write the pitch for you? <a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="color:var(--us);font-weight:700;text-decoration:none;">Upgrade →</a></div>
    </div>
    @else
    {{-- Interactive upsell finder (Help Me Plan / Coordinate It For Me) --}}
    <div class="us-tool">
        <h3>🔍 Upsell Finder</h3>
        <p class="desc">{{ $isSemi ? 'Enter a booked service, event type and package price — AI suggests add-ons and a bundle you can edit before sending.' : 'Enter a booked service, event type and package price to get relevant add-on suggestions, a bundle and a message you can send. Prices are estimates you can adjust.' }}</p>

        <form id="usForm">
            <div class="us-form-grid">
                <div class="us-field">
                    <label>Booked service</label>
                    <input type="text" name="booked_service" maxlength="120" required placeholder="e.g. Photography">
                </div>
                <div class="us-field">
                    <label>Event type</label>
                    <input type="text" name="event_type" maxlength="120" required placeholder="e.g. Wedding">
                </div>
                <div class="us-field">
                    <label>Package price ($)</label>
                    <input type="number" name="package_price" min="1" step="0.01" required placeholder="e.g. 1850">
                </div>
            </div>
            <button type="submit" class="us-run" id="usRun">{{ $isSemi ? '✨ Suggest add-ons' : '🔍 Find upsell opportunities' }}</button>
        </form>

        <div class="us-err" id="usErr"></div>
        <div class="us-load" id="usLoad">Finding relevant add-ons…</div>

        <div class="us-res" id="usRes">
            <div class="us-res-summary" id="usSummary"></div>
            <div id="usUpList"></div>
            <div class="us-bundle" id="usBundle">
                <h4 id="usBundleName"></h4>
                <div class="us-bundle-row">
                    <span class="us-bundle-price" id="usBundlePrice"></span>
                    <span class="us-bundle-save" id="usBundleSave"></span>
                    <span class="us-bundle-uplift" id="usBundleUplift"></span>
                </div>
            </div>
            <div class="us-script">
                <span class="lbl">Suggested message</span>
                <span id="usScript"></span>
            </div>
        </div>
    </div>
    @endif

    @if($isMax)
    {{-- Coordinate It For Me — full picture (stats + current booking + suggested add-ons) --}}
    <div class="us-stats">@foreach($stats as [$lbl, $val, $tone])<div class="us-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="us-grid">
        <div class="us-card">
            <h3>📦 Current Booking</h3>
            <div class="us-kv"><span>Client</span><b>{{ $booking['client'] }}</b></div>
            <div class="us-kv"><span>Event</span><b>{{ $booking['event'] }}</b></div>
            <div class="us-kv"><span>Booked Package</span><b>{{ $booking['package'] }}</b></div>
            <div class="us-kv"><span>Details</span><b>{{ $booking['guests'] }}</b></div>
            <div class="us-toggle"><span class="us-tg on">Maximize revenue</span><span class="us-tg">Improve experience</span><span class="us-tg">Fill schedule</span></div>
            <button class="us-btn">🔍 Find Upsell Opportunities</button>
        </div>
        <div class="us-card">
            <h3>✨ Suggested Add-ons</h3>
            @foreach($addons as $i => [$name, $price, $like, $tag])
                <div class="us-add {{ $i < 2 ? 'sel' : '' }}">
                    <span class="us-chk"></span>
                    <div class="us-add-main"><h5>{{ $name }} @if($tag)<span class="us-like">{{ $tag }}</span>@endif</h5><span>{{ $like }}% likely to accept</span></div>
                    <span class="us-price">+${{ number_format($price) }}</span>
                </div>
            @endforeach
            <div class="us-moment">⏱ {{ $moment }}</div>
            <button class="us-btn" style="margin-top:14px;">+ Add Selected to Proposal</button>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('usForm');
    if (!form) return;
    const LEVEL = document.querySelector('.us')?.dataset.level || 'maximum';

    const run   = document.getElementById('usRun');
    const load  = document.getElementById('usLoad');
    const res   = document.getElementById('usRes');
    const errEl = document.getElementById('usErr');
    const csrf  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('on');
        res.classList.remove('on');
        load.classList.add('on');
        run.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.upsell-assistant.compute") }}', {
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
                errEl.textContent = data.message || 'Could not generate suggestions.';
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

    function render(x) {
        document.getElementById('usSummary').textContent = x.summary || '';

        const editable = LEVEL === 'semi';
        const pkg = parseFloat(form.package_price.value) || 0;
        const list = document.getElementById('usUpList');
        list.innerHTML = '';
        (x.upsells || []).forEach((u, i) => {
            const row = document.createElement('div');
            row.className = 'us-up';
            const price = editable
                ? `+$<input class="us-edit-price" data-i="${i}" type="number" min="0" value="${Math.round(u.price)}" style="width:92px;font-size:14px;font-weight:800;color:#16a34a;border:1px solid var(--border-color);border-radius:8px;padding:2px 8px;background:var(--bg-card);font-family:inherit;">`
                : `+$${fmt(u.price)}`;
            row.innerHTML = `
                <div class="us-up-main">
                    <h5>${esc(u.name)}</h5>
                    <span>${esc(u.why)}</span>
                </div>
                <span class="p">${price}</span>`;
            list.appendChild(row);
        });

        const b = x.bundle || {};
        document.getElementById('usBundleName').textContent   = b.name || 'Bundle';
        document.getElementById('usScript').textContent       = x.script || '';

        // Help Me Plan — prices are editable; the bundle recomputes live (top 3, 12% off).
        function paintBundle(price, saves, uplift) {
            document.getElementById('usBundlePrice').textContent  = '$' + fmt(price);
            document.getElementById('usBundleSave').textContent   = saves ? ('saves $' + fmt(saves)) : '';
            document.getElementById('usBundleUplift').textContent = '+' + uplift + '% vs. package';
        }
        if (editable) {
            const recompute = () => {
                const vals = Array.from(list.querySelectorAll('.us-edit-price')).map(i => parseFloat(i.value) || 0);
                const top = vals.slice(0, 3).reduce((a, c) => a + c, 0);
                const price = Math.round(top * 0.88);
                const saves = Math.round(top - price);
                const uplift = pkg > 0 ? Math.round((price / pkg) * 1000) / 10 : 0;
                paintBundle(price, saves, uplift);
            };
            list.querySelectorAll('.us-edit-price').forEach(i => i.addEventListener('input', recompute));
            recompute();
        } else {
            paintBundle(b.price, b.saves, x.revenue_uplift_pct);
        }
    }

    function fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
})();

// Do It Myself — hand-built add-on list (no AI, no server call).
(function () {
    const rows = document.getElementById('usmRows');
    const add = document.getElementById('usmAdd');
    const totalEl = document.getElementById('usmTotal');
    if (!rows || !add) return;
    const fmt = (n) => Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
    function recompute() {
        let t = 0;
        rows.querySelectorAll('.usm-price').forEach(i => t += (parseFloat(i.value) || 0));
        if (totalEl) totalEl.textContent = '$' + fmt(t);
    }
    function addRow(name = '', price = '') {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;';
        div.innerHTML =
            '<input type="text" placeholder="Add-on (e.g. Extra hour)" class="usm-inp" style="flex:2;min-width:150px;padding:9px 12px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' +
            '<span style="color:var(--text-muted);">$</span><input type="number" min="0" placeholder="Price" class="usm-inp usm-price" style="width:110px;padding:9px 10px;border:1px solid var(--border-color);border-radius:9px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:13px;">' +
            '<button type="button" title="Remove" style="border:none;background:rgba(220,38,38,.1);color:#dc2626;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:16px;flex:0 0 auto;">&times;</button>';
        div.querySelector('input[type="text"]').value = name;
        div.querySelector('.usm-price').value = price;
        div.querySelector('.usm-price').addEventListener('input', recompute);
        div.querySelector('button').addEventListener('click', () => { div.remove(); recompute(); });
        rows.appendChild(div);
    }
    // Seed a few common upsells the pro can rename, price or remove.
    [['Extra Hour of Coverage', 300], ['Premium Album Upgrade', 350], ['Highlight Film', 799]].forEach(([n, p]) => addRow(n, p));
    add.addEventListener('click', () => { addRow(); recompute(); });
    recompute();
})();
</script>
@endpush
@endsection
