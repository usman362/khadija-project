{{--
    The messenger that pops up from the bottom of the screen — Sir Peter's
    Idea 2, gated by Idea 3.

    It is a way IN to the conversations that already exist, not a second
    messaging system: every list, message and send below goes through the same
    endpoints the Messages page uses, so nothing anybody says depends on what
    they pay. What the membership decides is whether this dock is on the page
    at all — see MessengerAccess.

    Minimising keeps the conversation open; closing puts it away without
    ending anything. Neither touches the thread.
--}}
@auth
{{-- Not on the messages page itself: it is a shortcut to the page you are
     already on, and its button sat over the details column's Chat options
     and figures (Ali, 2026-09-11). --}}
@if(\App\Support\MessengerAccess::dock(auth()->user())
    && ! request()->routeIs('client.chat.*', 'professional.chat.*', 'app.chat.*'))
@once
@push('styles')
<style>
    .md { position: fixed; right: 18px; bottom: 18px; z-index: 900; display: flex;
          align-items: flex-end; gap: 12px; font-family: inherit; }

    /*
     * Two floating buttons, one corner.
     *
     * The AI assistant's bubble (partials/_ai_chatbot_widget) sits in the
     * corner: 24px in, 58px round. This launcher sat at 18px, right behind it,
     * so on every page with both a client saw one button and a sliver of
     * orange. It stacks above the bubble instead, centred on it with a 14px
     * gap. Only where the bubble is actually on the page: with the assistant
     * off, the launcher keeps the corner. MessageDockClearsTheChatbotTest
     * holds these numbers to the bubble's.
     */
    body:has(.aic-bubble) .md { right: 27px; bottom: 96px; }   /* 24 + (58-52)/2 · 24 + 58 + 14 */

    /*
     * A phone has no room beside the launcher. The window opened to its left
     * and ran 55px off a 375px screen, cutting "Messages" to "ges". Below
     * 520px it opens above the launcher instead, as wide as the screen allows
     * with the same margin on both sides.
     */
    @media (max-width: 520px) {
        .md { flex-direction: column; align-items: flex-end; }
        .md-win { width: calc(100vw - 36px); }                              /* right: 18px, both sides */
        body:has(.aic-bubble) .md-win { width: calc(100vw - 54px); }        /* right: 27px, both sides */
    }

    .md-launch { width: 52px; height: 52px; border-radius: 50%; border: 0; cursor: pointer;
        background: var(--brand, #f97316); color: #fff; box-shadow: 0 10px 26px -8px rgba(15,27,53,.5);
        display: flex; align-items: center; justify-content: center; position: relative; }
    .md-launch svg { width: 22px; height: 22px; }
    .md-launch .md-dot { position: absolute; top: 2px; right: 2px; min-width: 18px; height: 18px;
        border-radius: 999px; background: #dc2626; color: #fff; font-size: 10.5px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; padding: 0 5px; }

    .md-win { width: 340px; max-width: calc(100vw - 36px); background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 14px; overflow: hidden;
        box-shadow: 0 24px 60px -22px rgba(15,27,53,.55); display: none; flex-direction: column; }
    .md.is-open .md-win { display: flex; }
    /* Minimised keeps the window — and the conversation inside it — exactly
       where it was; only the body is put away. */
    .md.is-min .md-body, .md.is-min .md-compose { display: none; }

    .md-head { display: flex; align-items: center; gap: 8px; padding: 10px 12px;
        border-bottom: 1px solid var(--border-color, #e5e7eb); background: var(--bg-card-hover, #f8fafc); }
    .md-head b { font-size: 13.5px; color: var(--text-primary, #111827); flex: 1; min-width: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-icon { border: 0; background: none; cursor: pointer; color: var(--text-muted, #6b7280);
        width: 26px; height: 26px; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
    .md-icon:hover { background: var(--bg-card, #fff); color: var(--text-primary, #111827); }
    .md-icon svg { width: 15px; height: 15px; }

    .md-body { height: 300px; overflow-y: auto; padding: 10px 12px; }
    .md-row { display: flex; gap: 9px; align-items: center; width: 100%; text-align: left;
        border: 0; background: none; cursor: pointer; padding: 9px 8px; border-radius: 9px; }
    .md-row:hover { background: var(--bg-card-hover, #f1f5f9); }
    .md-av { width: 30px; height: 30px; border-radius: 50%; background: var(--brand, #f97316);
        color: #fff; font-size: 11.5px; font-weight: 800; display: flex; align-items: center;
        justify-content: center; flex: none; }
    .md-row-who { min-width: 0; flex: 1; }
    .md-row-who b { display: block; font-size: 12.5px; color: var(--text-primary, #111827);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-row-who span { display: block; font-size: 11.5px; color: var(--text-muted, #6b7280);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-unread { flex: none; min-width: 18px; height: 18px; border-radius: 999px; background: #dc2626;
        color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center;
        justify-content: center; padding: 0 5px; }

    .md-msg { max-width: 82%; margin-bottom: 8px; padding: 8px 11px; border-radius: 11px;
        font-size: 12.5px; line-height: 1.5; background: var(--bg-card-hover, #f1f5f9);
        color: var(--text-primary, #111827); word-break: break-word; }
    .md-msg.is-mine { margin-left: auto; background: rgba(249,115,22,.12); }
    .md-msg small { display: block; margin-top: 3px; font-size: 10.5px; color: var(--text-muted, #6b7280); }

    .md-compose { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
    .md-compose input { flex: 1; min-width: 0; border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 9px; padding: 8px 11px; font: inherit; font-size: 12.5px;
        background: var(--bg-page, #fff); color: var(--text-primary, #111827); }
    .md-compose button { border: 0; background: var(--brand, #f97316); color: #fff; border-radius: 9px;
        padding: 0 14px; font-weight: 800; font-size: 12.5px; cursor: pointer; }
    .md-note { padding: 18px 12px; font-size: 12.5px; color: var(--text-muted, #6b7280); text-align: center; }

    /* display beats the hidden attribute, so anything here that is both
       flex AND hideable has to say so — otherwise the back button, the
       composer and the unread badge stay on screen while "hidden" and go on
       swallowing clicks. */
    .md-icon[hidden], .md-compose[hidden], .md-dot[hidden] { display: none !important; }

    @media (max-width: 520px) { .md { right: 12px; bottom: 12px; } .md-win { width: calc(100vw - 24px); } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var dock = document.getElementById('msgDock');
    if (! dock) return;

    var win     = dock.querySelector('.md-win');
    var title   = dock.querySelector('[data-md-title]');
    var body    = dock.querySelector('[data-md-body]');
    var compose = dock.querySelector('[data-md-compose]');
    var input   = dock.querySelector('[data-md-input]');
    var back    = dock.querySelector('[data-md-back]');
    var dot     = dock.querySelector('[data-md-dot]');

    var urls = {
        list: @json(route('conversations.index')),
        show: @json(route('conversations.show', ['conversation' => '__ID__'])),
        send: @json(route('conversations.messages.store', ['conversation' => '__ID__'])),
        read: @json(route('conversations.mark-read', ['conversation' => '__ID__'])),
    };

    var me  = @json(auth()->id());
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var open = null;   // the conversation being read, if any

    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }
    function initials(n) { return (n || '?').trim().charAt(0).toUpperCase(); }
    function at(url, id) { return url.replace('__ID__', id); }

    function get(url) {
        return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (! r.ok) throw new Error(r.status); return r.json(); });
    }

    /* The list. Names come from the other participants, so a conversation
       reads as the person you are talking to rather than its own id. */
    function showList() {
        open = null;
        title.textContent = 'Messages';
        back.hidden = true;
        compose.hidden = true;
        body.innerHTML = '<div class="md-note">Loading…</div>';

        get(urls.list).then(function (data) {
            var rows = (data.data || data || []);

            if (! rows.length) {
                body.innerHTML = '<div class="md-note">No conversations yet.</div>';
                return;
            }

            body.innerHTML = rows.map(function (c) {
                var others = (c.participants || []).filter(function (p) { return p.id !== me; });
                var who = others.map(function (p) { return p.name; }).join(', ') || 'Conversation';
                var unread = Number(c.unread_count || 0);

                return '<button type="button" class="md-row" data-open="' + c.id + '">'
                    + '<span class="md-av">' + esc(initials(who)) + '</span>'
                    + '<span class="md-row-who"><b>' + esc(who) + '</b>'
                    + '<span>' + esc(c.last_message_body || 'No messages yet') + '</span></span>'
                    + (unread ? '<span class="md-unread">' + unread + '</span>' : '')
                    + '</button>';
            }).join('');

            badge(rows.reduce(function (n, c) { return n + (c.muted_at ? 0 : Number(c.unread_count || 0)); }, 0));
        }).catch(function () {
            body.innerHTML = '<div class="md-note">Could not load your messages.</div>';
        });
    }

    function showThread(id) {
        open = id;
        back.hidden = false;
        compose.hidden = false;
        body.innerHTML = '<div class="md-note">Loading…</div>';

        get(at(urls.show, id)).then(function (data) {
            var conv = data.conversation || {};
            var others = (conv.participants || []).filter(function (p) { return p.id !== me; });
            title.textContent = others.map(function (p) { return p.name; }).join(', ') || 'Conversation';

            // The endpoint hands them back newest first; a thread reads the
            // other way round.
            var msgs = ((data.messages || {}).data || []).slice().reverse();

            body.innerHTML = msgs.map(function (m) {
                var mine = m.sender_id === me;
                return '<div class="md-msg' + (mine ? ' is-mine' : '') + '">' + esc(m.body)
                    + '<small>' + esc(mine ? 'You' : ((m.sender || {}).name || '')) + '</small></div>';
            }).join('') || '<div class="md-note">No messages yet.</div>';

            body.scrollTop = body.scrollHeight;
            input.focus();

            // Opening it is reading it.
            fetch(at(urls.read, id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function () { badge(0); })
                .catch(function () {});
        }).catch(function () {
            body.innerHTML = '<div class="md-note">Could not open this conversation.</div>';
        });
    }

    function badge(n) {
        if (! dot) return;
        dot.textContent = n > 99 ? '99+' : n;
        dot.hidden = ! n;
    }

    dock.addEventListener('click', function (e) {
        var row = e.target.closest('[data-open]');
        if (row) { showThread(row.dataset.open); return; }

        if (e.target.closest('[data-md-launch]')) {
            dock.classList.toggle('is-open');
            dock.classList.remove('is-min');
            if (dock.classList.contains('is-open') && ! open) showList();
            return;
        }

        // Minimise keeps the conversation exactly where it was.
        if (e.target.closest('[data-md-min]')) { dock.classList.toggle('is-min'); return; }

        // Close puts it away. Nothing is ended and nothing is deleted.
        if (e.target.closest('[data-md-close]')) { dock.classList.remove('is-open', 'is-min'); return; }

        if (e.target.closest('[data-md-back]')) { showList(); }
    });

    compose.addEventListener('submit', function (e) {
        e.preventDefault();

        var text = input.value.trim();
        if (! text || ! open) return;

        input.value = '';

        fetch(at(urls.send, open), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ body: text }),
        })
            .then(function (r) { if (! r.ok) throw new Error(r.status); return r.json(); })
            .then(function (m) {
                body.insertAdjacentHTML('beforeend',
                    '<div class="md-msg is-mine">' + esc(m.body) + '<small>You</small></div>');
                body.scrollTop = body.scrollHeight;
            })
            .catch(function () {
                // Put it back rather than losing what they typed.
                input.value = text;
                body.insertAdjacentHTML('beforeend', '<div class="md-note">That did not send. Try again.</div>');
            });
    });

    // The unread count on the launcher, without opening anything.
    get(urls.list).then(function (data) {
        var rows = (data.data || data || []);
        badge(rows.reduce(function (n, c) { return n + (c.muted_at ? 0 : Number(c.unread_count || 0)); }, 0));
    }).catch(function () {});
})();
</script>
@endpush
@endonce

<div class="md" id="msgDock">
    <div class="md-win" role="dialog" aria-label="Messages">
        <div class="md-head">
            <button type="button" class="md-icon" data-md-back hidden aria-label="Back to conversations">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <b data-md-title>Messages</b>
            <button type="button" class="md-icon" data-md-min aria-label="Minimise">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <button type="button" class="md-icon" data-md-close aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="md-body" data-md-body></div>

        <form class="md-compose" data-md-compose hidden>
            <input type="text" data-md-input placeholder="Write a message…" maxlength="5000" autocomplete="off">
            <button type="submit">Send</button>
        </form>
    </div>

    <button type="button" class="md-launch" data-md-launch aria-label="Messages">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.9 9.9 0 0 1-4.2-.9L3 20l1.3-3.8A8.2 8.2 0 0 1 3 11.5a8.4 8.4 0 0 1 9-8.4 8.4 8.4 0 0 1 9 8.4z"/></svg>
        <span class="md-dot" data-md-dot hidden>0</span>
    </button>
</div>
@endif
@endauth
