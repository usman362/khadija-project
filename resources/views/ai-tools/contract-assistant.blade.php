@extends($aiLayout ?? 'layouts.client')

@section('title', 'Contract Assistant')
@section('page-title', 'Contract Assistant')
@section('page-subtitle', 'Generate a plain-English draft agreement from your event details')

@push('styles')
<style>
    .ca { --ca: #7c3aed; }
    .ca-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .ca-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .ca-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; } .ca-stat.good b { color: #16a34a; } .ca-stat.warn b { color: #d97706; } .ca-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .ca-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .ca-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .ca-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ca-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin: 0 0 6px; }
    .ca-in, .ca-sel { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; margin-bottom: 12px; }
    .ca-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .ca-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--ca), #6d28d9); cursor: pointer; }
    .ca-btn:disabled { opacity: .6; cursor: not-allowed; }
    .ca-clause { padding: 12px 0; border-bottom: 1px dashed var(--border-color); } .ca-clause:last-of-type { border-bottom: none; }
    .ca-clause h6 { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .ca-clause pre { font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.55; white-space: pre-wrap; font-family: inherit; }
    .ca-summary { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; background: rgba(124,58,237,.06); border: 1px solid rgba(124,58,237,.25); border-radius: 10px; padding: 12px; margin-bottom: 12px; }
    .ca-disc { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin-top: 12px; padding: 10px; border: 1px dashed var(--border-color); border-radius: 9px; }
    .ca-empty { font-size: 12.5px; color: var(--text-muted); }
    .ca-err { display: none; font-size: 12.5px; color: #dc2626; background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3); border-radius: 9px; padding: 10px; margin-bottom: 12px; }
    .ca-err.on { display: block; }

    /* Help Me Plan — editable draft fields */
    .ca-edit { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 11px; font-size: 12.5px; color: var(--text-primary); background: var(--bg-body, var(--bg-card)); font-family: inherit; margin-bottom: 10px; }
    .ca-edit:focus { outline: none; border-color: var(--ca); box-shadow: 0 0 0 3px rgba(124,58,237,.15); }
    .ca-edit-h { font-weight: 800; }
    textarea.ca-edit { line-height: 1.55; resize: vertical; }

    /* Do It Myself — hand-built agreement builder */
    .ca-crow { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px; margin-bottom: 12px; position: relative; }
    .ca-crow .ca-del { position: absolute; top: 10px; right: 10px; border: none; background: rgba(220,38,38,.1); color: #dc2626; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; font-size: 16px; }
    .ca-crow-h { padding-right: 44px; }
    .ca-add { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #6d28d9; background: rgba(124,58,237,.09); border: 1px solid rgba(124,58,237,.28); border-radius: 10px; padding: 9px 15px; cursor: pointer; font-family: inherit; }

    @media (max-width: 1000px) { .ca-grid { grid-template-columns: minmax(0,1fr); } .ca-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Assemble your own draft by hand — add, edit and remove your own clauses. No AI.'],
        'semi'    => ['Help Me Plan', '#7c3aed', 'instantly drafts the agreement — reword any clause before you use it.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'Enter your details and instantly drafts the full agreement for you.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="ca" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:#6d28d9;text-decoration:none;">Upgrade for more AI →</a>@endunless
    </div>

    @if($isManual)
    {{-- Do It Myself — hand-built agreement, no AI --}}
    <div class="ca-card ca-mano">
        <h3>📝 Build My Agreement</h3>
        <div style="font-size:12.5px;color:var(--text-muted);margin:-6px 0 14px;">Assemble your own draft by hand — add, edit and remove clauses. No AI.</div>
        <label class="ca-lbl">Agreement Title</label>
        <input class="ca-in" id="camTitle" placeholder="e.g. Service Agreement — Wedding Floral &amp; Décor">
        <div id="camClauses"></div>
        <button type="button" id="camAdd" class="ca-add">+ Add clause</button>
        <div class="ca-disc" style="display:block;margin-top:14px;">⚠️ This is a draft template for your convenience and is not legal advice — have a professional review it before signing.</div>
    </div>
    @else
    <div class="ca-stats">@foreach($stats as [$lbl, $val, $tone])<div class="ca-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="ca-grid">
        <div class="ca-card">
            <h3>📄 Event & Agreement Details</h3>
            <div style="font-size:12.5px;color:var(--text-muted);margin:-6px 0 12px;">{{ $isSemi ? 'instantly drafts an agreement you can reword clause by clause before using.' : 'instantly drafts a full plain-English agreement from your details.' }}</div>
            <div class="ca-err" id="caErr"></div>
            <form id="caForm">
                <label class="ca-lbl">Service *</label>
                <input class="ca-in" name="service" required placeholder="e.g. Wedding floral &amp; décor">
                <div class="ca-row">
                    <div>
                        <label class="ca-lbl">Client name</label>
                        <input class="ca-in" name="client_name" placeholder="e.g. Sarah Johnson">
                    </div>
                    <div>
                        <label class="ca-lbl">Provider name</label>
                        <input class="ca-in" name="provider_name" placeholder="e.g. Elite Events Co.">
                    </div>
                </div>
                <div class="ca-row">
                    <div>
                        <label class="ca-lbl">Total price *</label>
                        <input class="ca-in" name="total_price" type="number" min="0" step="0.01" required placeholder="e.g. 7500">
                    </div>
                    <div>
                        <label class="ca-lbl">Event date *</label>
                        <input class="ca-in" name="event_date" type="date" required>
                    </div>
                </div>
                <div class="ca-row">
                    <div>
                        <label class="ca-lbl">Deposit %</label>
                        <input class="ca-in" name="deposit_pct" type="number" min="0" max="100" step="1" placeholder="30">
                    </div>
                    <div>
                        <label class="ca-lbl">Cancellation terms</label>
                        <select class="ca-sel" name="cancellation">
                            <option value="standard">Standard</option>
                            <option value="flexible">Flexible</option>
                            <option value="strict">Strict</option>
                        </select>
                    </div>
                </div>
                <button class="ca-btn" id="caBtn" type="submit">{{ $isSemi ? '✨ Suggest Draft Agreement' : '🤖 Draft My Agreement' }}</button>
            </form>
        </div>
        <div class="ca-card">
            <h3 id="caTitle">📝 Draft Agreement</h3>
            <div id="caOut"><p class="ca-empty">Fill in the details and generate a draft agreement — it will appear here.</p></div>
            <div class="ca-disc" id="caDisc" style="display:none;"></div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('caForm');
    if (!form) return;
    const btn  = document.getElementById('caBtn');
    const out  = document.getElementById('caOut');
    const err  = document.getElementById('caErr');
    const disc = document.getElementById('caDisc');
    const titleEl = document.getElementById('caTitle');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const LEVEL = document.querySelector('.ca')?.dataset.level || 'maximum';

    function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        err.classList.remove('on');
        btn.disabled = true;
        out.innerHTML = '<p class="ca-empty">Building your draft agreement…</p>';
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const r = await fetch('{{ route("ai-tools.contract-assistant.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            btn.disabled = false;
            if (!data.success) {
                out.innerHTML = '<p class="ca-empty">Nothing to show yet.</p>';
                err.textContent = data.message || 'Could not generate the agreement.';
                err.classList.add('on');
                return;
            }
            render(data.result);
        } catch (ex) {
            btn.disabled = false;
            out.innerHTML = '<p class="ca-empty">Nothing to show yet.</p>';
            err.textContent = 'Network error. Please try again.';
            err.classList.add('on');
        }
    });

    function render(res) {
        // "Help Me Plan" renders the title, summary and every clause as editable
        // fields the user can reword; "Coordinate It For Me" is read-only.
        const editable = LEVEL === 'semi';
        titleEl.textContent = '📝 ' + (res.title || 'Draft Agreement');
        let html = '';
        if (editable) {
            html += '<label class="ca-lbl">Title</label>' +
                '<input class="ca-edit ca-edit-h" value="' + esc(res.title || '') + '">';
            if (res.summary) {
                html += '<label class="ca-lbl">Summary</label>' +
                    '<textarea class="ca-edit" rows="3">' + esc(res.summary) + '</textarea>';
            }
            (res.clauses || []).forEach(c => {
                html += '<div class="ca-clause">' +
                    '<input class="ca-edit ca-edit-h" value="' + esc(c.heading) + '">' +
                    '<textarea class="ca-edit" rows="5">' + esc(c.body) + '</textarea></div>';
            });
        } else {
            if (res.summary) html += '<div class="ca-summary">' + esc(res.summary) + '</div>';
            (res.clauses || []).forEach(c => {
                html += '<div class="ca-clause"><h6>' + esc(c.heading) + '</h6><pre>' + esc(c.body) + '</pre></div>';
            });
        }
        out.innerHTML = html || '<p class="ca-empty">No clauses generated.</p>';
        if (res.disclaimer) { disc.textContent = '⚠️ ' + res.disclaimer; disc.style.display = 'block'; }
    }
})();

// Do It Myself — hand-built agreement builder (no AI, no server call).
(function () {
    const wrap = document.getElementById('camClauses');
    const add  = document.getElementById('camAdd');
    if (!wrap || !add) return;

    function addClause(heading, body) {
        const row = document.createElement('div');
        row.className = 'ca-crow';
        row.innerHTML =
            '<button type="button" class="ca-del" title="Remove">&times;</button>' +
            '<input class="ca-edit ca-edit-h ca-crow-h" placeholder="Clause heading (e.g. 1. Scope of Services)">' +
            '<textarea class="ca-edit" rows="4" placeholder="Write this clause in your own words…"></textarea>';
        row.querySelector('input').value = heading || '';
        row.querySelector('textarea').value = body || '';
        row.querySelector('.ca-del').addEventListener('click', function () { row.remove(); });
        wrap.appendChild(row);
    }

    // Seed a few common clause prompts the user can rewrite, add to or remove.
    [
        ['1. Scope of Services', 'Describe exactly what will be provided, where and when.'],
        ['2. Fees & Payment Schedule', 'State the total fee, the deposit amount and when the balance is due.'],
        ['3. Cancellation & Refunds', 'Explain what happens if either party cancels, and any refund terms.'],
    ].forEach(function (c) { addClause(c[0], c[1]); });

    add.addEventListener('click', function () { addClause(); });
})();
</script>
@endpush
