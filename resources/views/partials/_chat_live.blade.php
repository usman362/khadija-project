{{-- Live messaging engine. A page sets window.CHAT_LIVE = {
       sendUrl, showUrl, readUrl, meId, seen:[ids], box:'#container',
       bubble: function(msg, mine){ return html },
     } then includes this partial. Handles: live send (append, no reload),
     polling for incoming messages, and mark-as-read on open. --}}
<script>
(function () {
    const cfg = window.CHAT_LIVE;
    if (!cfg) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || cfg.csrf || '';
    const box = document.querySelector(cfg.box);
    if (!box) return;
    const seen = new Set(cfg.seen || []);

    function add(m, forceMine) {
        if (!m || (m.id != null && seen.has(m.id))) return;
        if (m.id != null) seen.add(m.id);
        const mine = forceMine === true || m.mine === true || m.sender_id === cfg.meId;
        box.insertAdjacentHTML('beforeend', cfg.bubble(m, mine));
        box.scrollTop = box.scrollHeight;
    }
    box.scrollTop = box.scrollHeight;

    // Mark the conversation read on open.
    if (cfg.readUrl) fetch(cfg.readUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).catch(() => {});

    const form = document.querySelector(cfg.form);
    const input = document.querySelector(cfg.input);

    // ── Attachments ────────────────────────────────────────────────────────
    // A file is uploaded the moment it is picked and held as an id until the
    // message is sent, because that is the shape the API already has: upload
    // returns an id, send takes attachment_ids. cfg.fileInput / cfg.chips are
    // optional — a page without them keeps the plain text-only behaviour.
    const pending = [];
    const fileInput = cfg.fileInput ? document.querySelector(cfg.fileInput) : null;
    const chips = cfg.chips ? document.querySelector(cfg.chips) : null;

    function drawChips() {
        if (!chips) return;
        chips.innerHTML = pending.map((p, i) => {
            // A chip appears the moment a file is picked, and says it is still
            // going up. Before this, nothing at all happened between choosing a
            // file and the upload finishing — on a slow line or a large file the
            // page looked broken, so people picked the same file again.
            if (p.uploading) {
                return '<span class="chat-chip is-uploading">'
                    + '<span class="chat-chip-spin" aria-hidden="true"></span>'
                    + escapeHtml(p.name) + ' <em>uploading…</em></span>';
            }

            return '<span class="chat-chip">' + escapeHtml(p.name)
                + '<button type="button" data-i="' + i + '" aria-label="Remove attachment">&times;</button></span>';
        }).join('');
        chips.style.display = pending.length ? '' : 'none';
    }
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    if (chips) chips.addEventListener('click', (e) => {
        const b = e.target.closest('button[data-i]');
        if (!b) return;
        pending.splice(parseInt(b.dataset.i, 10), 1);
        drawChips();
    });

    if (fileInput) fileInput.addEventListener('change', async function () {
        for (const file of Array.from(this.files || [])) {
            // Show it FIRST, then upload. drawChips() used to be called only
            // after the whole loop finished, so the one moment the person needed
            // feedback was the one moment the page said nothing.
            const chip = { id: null, name: file.name, uploading: true };
            pending.push(chip);
            drawChips();

            const fd = new FormData();
            fd.append('file', file);
            fd.append('conversation_id', cfg.conversationId);

            const drop = () => {
                const at = pending.indexOf(chip);
                if (at > -1) pending.splice(at, 1);
            };

            try {
                const res = await fetch(cfg.uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: fd,
                });
                const d = await res.json();

                if (res.ok && d.id) {
                    chip.id = d.id;
                    chip.uploading = false;
                } else {
                    // A chip for a file that never arrived would be sent as
                    // nothing, so it goes rather than sitting there looking ready.
                    drop();
                    if (cfg.onError) cfg.onError(d.message || 'That file could not be attached.');
                }
            } catch (err) {
                drop();
                if (cfg.onError) cfg.onError('That file could not be attached.');
            }

            drawChips();
        }
        this.value = '';
    });

    // Live send → append the returned message immediately.
    if (form) form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = (input.value || '').trim();
        // An attachment on its own still needs a body — the API requires one,
        // so say what was sent rather than failing silently.
        const ready = pending.filter((p) => p.id);
        const text = body || (ready.length ? 'Sent ' + ready.length + ' attachment' + (ready.length > 1 ? 's' : '') + '.' : '');
        if (!text) return;
        const btn = form.querySelector('[type="submit"]');
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(cfg.sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                // Only what actually finished uploading. A chip still in flight has no
                // id yet, and sending null would have the server reject the whole
                // message rather than the one file.
                body: JSON.stringify({ body: text, attachment_ids: pending.filter((p) => p.id).map((p) => p.id) }),
            });
            if (res.ok) {
                const m = await res.json();
                add(m, true);
                input.value = '';
                pending.length = 0;
                drawChips();
            }
        } catch (err) { /* keep input on failure */ }
        if (btn) { btn.disabled = false; btn.style.opacity = ''; }
    });

    // Poll for incoming messages (returns desc; append the new ones in order).
    async function poll() {
        if (!cfg.showUrl) return;
        try {
            const res = await fetch(cfg.showUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const d = await res.json();
            const arr = (d.messages && d.messages.data) ? d.messages.data : (d.messages || []);
            arr.slice().reverse().forEach((m) => add(m));
        } catch (e) { /* ignore */ }
    }
    setInterval(poll, 6000);
})();
</script>
