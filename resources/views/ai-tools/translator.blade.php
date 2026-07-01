@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Translator')
@section('page-title', 'AI Translator')
@section('page-subtitle', 'A built-in event phrasebook across five languages')

@push('styles')
<style>
    .tr { --tr: #7c3aed; }
    .tr-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .tr-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .tr-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; } .tr-stat.good b { color: #16a34a; } .tr-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .tr-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .tr-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .tr-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; } .tr-card .det { font-size: 11.5px; color: var(--text-muted); margin-bottom: 12px; }
    .tr-text { width: 100%; border: 1.5px solid var(--border-color); border-radius: 11px; padding: 13px; font-size: 13.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; line-height: 1.6; resize: vertical; min-height: 110px; }
    .tr-langs { display: flex; gap: 7px; flex-wrap: wrap; margin: 14px 0; } .tr-lang { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 7px 13px; cursor: pointer; color: var(--text-secondary); user-select: none; } .tr-lang.on { background: var(--tr); border-color: var(--tr); color: #fff; }
    .tr-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--tr), #6d28d9); cursor: pointer; }
    .tr-btn:disabled { opacity: .6; cursor: not-allowed; }
    .tr-out { background: rgba(124,58,237,.06); border: 1px solid rgba(124,58,237,.3); border-radius: 12px; padding: 14px; font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; }
    .tr-tag { font-size: 10.5px; font-weight: 800; color: var(--tr); margin-bottom: 8px; }
    .tr-sug { margin-top: 14px; } .tr-sug h4 { font-size: 12px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
    .tr-sug-item { border: 1px dashed var(--border-color); border-radius: 9px; padding: 9px 11px; margin-bottom: 7px; } .tr-sug-item .en { font-size: 12px; color: var(--text-muted); } .tr-sug-item .tt { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .tr-note { font-size: 11px; color: var(--text-muted); margin-top: 10px; line-height: 1.5; }
    .tr-empty { font-size: 12.5px; color: var(--text-muted); }
    .tr-err { display: none; font-size: 12.5px; color: #dc2626; background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.3); border-radius: 9px; padding: 10px; margin-bottom: 12px; }
    .tr-err.on { display: block; }
    @media (max-width: 1000px) { .tr-grid { grid-template-columns: minmax(0,1fr); } .tr-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="tr">
    <div class="tr-stats">@foreach($stats as [$lbl, $val, $tone])<div class="tr-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="tr-grid">
        <div class="tr-card">
            <h3>🌐 Phrase to translate</h3>
            <div class="det">Built-in event phrasebook — try common booking phrases</div>
            <div class="tr-err" id="trErr"></div>
            <form id="trForm">
                <textarea class="tr-text" name="text" required placeholder="e.g. Thank you for booking"></textarea>
                <div class="tr-langs" id="trLangs">
                    <span class="tr-lang on" data-lang="spanish">Spanish</span>
                    <span class="tr-lang" data-lang="french">French</span>
                    <span class="tr-lang" data-lang="german">German</span>
                    <span class="tr-lang" data-lang="italian">Italian</span>
                    <span class="tr-lang" data-lang="portuguese">Portuguese</span>
                </div>
                <input type="hidden" name="target_language" id="trLang" value="spanish">
                <button class="tr-btn" id="trBtn" type="submit">🔄 Translate</button>
            </form>
        </div>
        <div class="tr-card">
            <h3>✨ Translation</h3>
            <div class="det" id="trTargetDet">Pick a language and translate</div>
            <div id="trOut"><p class="tr-empty">The translation and closest phrasebook matches will appear here.</p></div>
            <p class="tr-note">Live translation of any free text activates once an AI key is configured. Until then, this built-in phrasebook covers the most common event phrases across five languages.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('trForm');
    if (!form) return;
    const btn  = document.getElementById('trBtn');
    const out  = document.getElementById('trOut');
    const err  = document.getElementById('trErr');
    const det  = document.getElementById('trTargetDet');
    const langInput = document.getElementById('trLang');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.getElementById('trLangs').addEventListener('click', function (e) {
        const l = e.target.closest('.tr-lang');
        if (!l) return;
        this.querySelectorAll('.tr-lang').forEach(x => x.classList.remove('on'));
        l.classList.add('on');
        langInput.value = l.getAttribute('data-lang');
    });

    function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        err.classList.remove('on');
        btn.disabled = true;
        out.innerHTML = '<p class="tr-empty">Looking up your phrase…</p>';
        const payload = Object.fromEntries(new FormData(form).entries());
        try {
            const r = await fetch('{{ route("ai-tools.translator.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            btn.disabled = false;
            if (!data.success) {
                out.innerHTML = '<p class="tr-empty">Nothing to show yet.</p>';
                err.textContent = data.message || 'Could not translate that.';
                err.classList.add('on');
                return;
            }
            render(data.result);
        } catch (ex) {
            btn.disabled = false;
            out.innerHTML = '<p class="tr-empty">Nothing to show yet.</p>';
            err.textContent = 'Network error. Please try again.';
            err.classList.add('on');
        }
    });

    function render(res) {
        det.textContent = res.target_language + (res.matched ? ' · phrasebook match' : ' · no exact match');
        let html = '<div class="tr-tag">' + (res.matched ? 'TRANSLATED' : 'NOTE') + '</div>';
        html += '<div class="tr-out">' + esc(res.translation) + '</div>';
        if (res.summary) html += '<p class="tr-note">' + esc(res.summary) + '</p>';
        if (res.phrasebook_suggestions && res.phrasebook_suggestions.length) {
            html += '<div class="tr-sug"><h4>Available event phrases in ' + esc(res.target_language) + '</h4>';
            res.phrasebook_suggestions.forEach(s => {
                html += '<div class="tr-sug-item"><div class="en">' + esc(s.en) + '</div><div class="tt">' + esc(s.translated) + '</div></div>';
            });
            html += '</div>';
        }
        out.innerHTML = html;
    }
})();
</script>
@endpush
