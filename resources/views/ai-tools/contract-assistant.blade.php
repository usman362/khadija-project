@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Contract Assistant')
@section('page-title', 'AI Contract Assistant')
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
    @media (max-width: 1000px) { .ca-grid { grid-template-columns: minmax(0,1fr); } .ca-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="ca">
    <div class="ca-stats">@foreach($stats as [$lbl, $val, $tone])<div class="ca-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="ca-grid">
        <div class="ca-card">
            <h3>📄 Event & Agreement Details</h3>
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
                <button class="ca-btn" id="caBtn" type="submit">📝 Generate Draft Agreement</button>
            </form>
        </div>
        <div class="ca-card">
            <h3 id="caTitle">📝 Draft Agreement</h3>
            <div id="caOut"><p class="ca-empty">Fill in the details and generate a draft agreement — it will appear here.</p></div>
            <div class="ca-disc" id="caDisc" style="display:none;"></div>
        </div>
    </div>
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
        titleEl.textContent = '📝 ' + (res.title || 'Draft Agreement');
        let html = '';
        if (res.summary) html += '<div class="ca-summary">' + esc(res.summary) + '</div>';
        (res.clauses || []).forEach(c => {
            html += '<div class="ca-clause"><h6>' + esc(c.heading) + '</h6><pre>' + esc(c.body) + '</pre></div>';
        });
        out.innerHTML = html || '<p class="ca-empty">No clauses generated.</p>';
        if (res.disclaimer) { disc.textContent = '⚠️ ' + res.disclaimer; disc.style.display = 'block'; }
    }
})();
</script>
@endpush
