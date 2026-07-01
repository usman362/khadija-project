@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Message Assistant')
@section('page-title', 'AI Message Assistant')
@section('page-subtitle', 'Clear, professional messages in the right tone')

@push('styles')
<style>
    .ma { --ma: #0d9488; }
    .ma-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .ma-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .ma-stat b { display: block; font-size: 21px; font-weight: 800; color: var(--text-primary); line-height: 1; } .ma-stat.good b { color: #0d9488; } .ma-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .ma-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .ma-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .ma-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ma-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin: 0 0 6px; }
    .ma-in, .ma-sel { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; margin-bottom: 12px; }
    textarea.ma-in { resize: vertical; min-height: 80px; }
    .ma-tones { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 14px; } .ma-tone { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 7px 13px; cursor: pointer; color: var(--text-secondary); user-select: none; } .ma-tone.on { background: var(--ma); border-color: var(--ma); color: #fff; }
    .ma-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--ma), #0f766e); cursor: pointer; }
    .ma-btn:disabled { opacity: .6; cursor: not-allowed; }
    .ma-var { margin-bottom: 14px; }
    .ma-ready { font-size: 10.5px; font-weight: 800; color: #0d9488; margin-bottom: 6px; }
    .ma-subj { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .ma-out { background: rgba(13,148,136,.06); border: 1px solid rgba(13,148,136,.3); border-radius: 12px; padding: 14px; font-size: 13px; color: var(--text-secondary); line-height: 1.6; white-space: pre-line; }
    .ma-copy { margin-top: 8px; border-radius: 10px; padding: 8px 14px; font-size: 12px; font-weight: 800; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); }
    .ma-summary { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 12px; }
    .ma-empty { font-size: 12.5px; color: var(--text-muted); }
    .ma-err { display: none; font-size: 12.5px; color: #dc2626; background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3); border-radius: 9px; padding: 10px; margin-bottom: 12px; }
    .ma-err.on { display: block; }
    @media (max-width: 1000px) { .ma-grid { grid-template-columns: minmax(0,1fr); } .ma-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="ma">
    <div class="ma-stats">@foreach($stats as [$lbl, $val, $tone])<div class="ma-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="ma-grid">
        <div class="ma-card">
            <h3>💬 What do you want to say?</h3>
            <div class="ma-err" id="maErr"></div>
            <form id="maForm">
                <label class="ma-lbl">Purpose</label>
                <select class="ma-sel" name="purpose">
                    <option value="follow up after sending a quote">Follow up after sending a quote</option>
                    <option value="request more details">Request more details</option>
                    <option value="confirm booking">Confirm booking</option>
                    <option value="politely decline">Politely decline</option>
                    <option value="thank after event">Thank after the event</option>
                </select>
                <label class="ma-lbl">Recipient name</label>
                <input class="ma-in" name="recipient_name" placeholder="e.g. Sarah Johnson">
                <label class="ma-lbl">Key points (one per line)</label>
                <textarea class="ma-in" name="key_points" placeholder="e.g.&#10;We're available on July 15&#10;The $1,850 package fits&#10;Ask about guest count"></textarea>
                <label class="ma-lbl">Tone</label>
                <div class="ma-tones" id="maTones">
                    <span class="ma-tone on" data-tone="friendly">Friendly</span>
                    <span class="ma-tone" data-tone="professional">Professional</span>
                    <span class="ma-tone" data-tone="warm">Warm</span>
                </div>
                <input type="hidden" name="tone" id="maTone" value="friendly">
                <button class="ma-btn" id="maBtn" type="submit">✍️ Draft My Message</button>
            </form>
        </div>
        <div class="ma-card">
            <h3>📨 Suggested Messages</h3>
            <div class="ma-summary" id="maSummary" style="display:none;"></div>
            <div id="maOut"><p class="ma-empty">Choose a purpose and tone, then draft your message — a few options will appear here.</p></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('maForm');
    if (!form) return;
    const btn  = document.getElementById('maBtn');
    const out  = document.getElementById('maOut');
    const err  = document.getElementById('maErr');
    const summaryEl = document.getElementById('maSummary');
    const toneInput = document.getElementById('maTone');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.getElementById('maTones').addEventListener('click', function (e) {
        const t = e.target.closest('.ma-tone');
        if (!t) return;
        this.querySelectorAll('.ma-tone').forEach(x => x.classList.remove('on'));
        t.classList.add('on');
        toneInput.value = t.getAttribute('data-tone');
    });

    function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        err.classList.remove('on');
        btn.disabled = true;
        out.innerHTML = '<p class="ma-empty">Drafting your message…</p>';
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const r = await fetch('{{ route("ai-tools.message-assistant.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            btn.disabled = false;
            if (!data.success) {
                out.innerHTML = '<p class="ma-empty">Nothing to show yet.</p>';
                err.textContent = data.message || 'Could not draft the message.';
                err.classList.add('on');
                return;
            }
            render(data.result);
        } catch (ex) {
            btn.disabled = false;
            out.innerHTML = '<p class="ma-empty">Nothing to show yet.</p>';
            err.textContent = 'Network error. Please try again.';
            err.classList.add('on');
        }
    });

    function render(res) {
        if (res.summary) { summaryEl.textContent = res.summary; summaryEl.style.display = 'block'; }
        let html = '';
        (res.variations || []).forEach(v => {
            html += '<div class="ma-var">'
                + '<div class="ma-ready">' + esc(v.label) + '</div>'
                + '<div class="ma-subj">Subject: ' + esc(v.subject) + '</div>'
                + '<div class="ma-out">' + esc(v.body) + '</div>'
                + '<button type="button" class="ma-copy" data-body="' + esc(v.body) + '">📋 Copy</button>'
                + '</div>';
        });
        out.innerHTML = html || '<p class="ma-empty">No drafts generated.</p>';
        out.querySelectorAll('.ma-copy').forEach(b => {
            b.addEventListener('click', () => {
                const txt = b.getAttribute('data-body') || '';
                navigator.clipboard?.writeText(txt);
                b.textContent = '✓ Copied';
                setTimeout(() => { b.textContent = '📋 Copy'; }, 1500);
            });
        });
    }
})();
</script>
@endpush
