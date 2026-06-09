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

    // Live send → append the returned message immediately.
    const form = document.querySelector(cfg.form);
    const input = document.querySelector(cfg.input);
    if (form) form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = (input.value || '').trim();
        if (!body) return;
        const btn = form.querySelector('[type="submit"]');
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
        try {
            const res = await fetch(cfg.sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ body: body }),
            });
            if (res.ok) { const m = await res.json(); add(m, true); input.value = ''; }
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
