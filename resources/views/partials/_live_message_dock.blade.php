{{--
    Live Message Dock (Sir Peter, 2026-09-18, developer guide v2).

    A bar fixed to the bottom of every signed-in client page: the unread total,
    a few conversation tabs, and Do Not Disturb. A tab opens a small chat
    window above the bar, so a message can be read and answered without
    leaving the page.

    It is a way into the conversations that already exist, not a second
    messaging system. Every list, message, send, read and mute below goes
    through the same endpoints the Messages page uses, so the two can never
    disagree. What the browser remembers (which tabs are open, which one is
    expanded, Do Not Disturb) is presentation only.

    Not on the Messages page itself: that page is the full version of the same
    thing.
--}}
@auth
@if(\App\Support\MessengerAccess::dock(auth()->user()) && ! request()->routeIs('client.chat.*'))
@php
    $__lmdUser = auth()->user();
    $__lmdFull = route('client.chat.index');
    $__lmdInitials = collect(explode(' ', trim($__lmdUser->name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

@once
@push('styles')
<style>
    /* ── The bar ─────────────────────────────────────────────── */
    .lmd-bar { position: fixed; bottom: 0; left: var(--sidebar-width, 236px); right: 0; z-index: 890;
        display: flex; align-items: center; gap: 10px; padding: 10px 18px;
        background: var(--bg-card, #fff); border-top: 1px solid var(--border-color, #e5e7eb);
        box-shadow: 0 -8px 24px -18px rgba(15,27,53,.35); font-family: inherit; }
    html.cl-side-mini .lmd-bar { left: var(--sidebar-collapsed, 72px); }
    /* The page keeps room for the bar, so it never covers a Save, Accept or
       Continue at the bottom of a page. */
    body.has-lmd .cl-main { padding-bottom: 86px; }

    .lmd-home { display: flex; align-items: center; gap: 10px; border: 0; background: none; cursor: pointer;
        padding: 4px 12px 4px 4px; border-radius: 12px; text-align: left; font: inherit; flex: none; }
    .lmd-home:hover { background: var(--bg-card-hover, #f8fafc); }
    .lmd-me { width: 40px; height: 40px; border-radius: 10px; background: #2563eb; color: #fff; font-weight: 800;
        font-size: 14px; display: flex; align-items: center; justify-content: center; flex: none; }
    .lmd-home b { display: flex; align-items: center; gap: 6px; font-size: 13.5px; color: var(--text-primary, #111827); }
    .lmd-home small { display: block; font-size: 11.5px; color: var(--text-muted, #6b7280); }
    .lmd-count { min-width: 20px; height: 20px; border-radius: 999px; background: #dc2626; color: #fff; font-size: 11px;
        font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 6px; }

    .lmd-sep { width: 1px; align-self: stretch; background: var(--border-color, #e5e7eb); flex: none; }

    .lmd-tabs { display: flex; gap: 8px; flex: 1; min-width: 0; overflow: hidden; }
    .lmd-empty { align-self: center; border: 0; background: none; padding: 0; font: inherit; font-size: 12.5px; color: var(--text-muted, #6b7280); cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lmd-empty:hover { color: var(--text-primary, #111827); }
    .lmd-tab { display: flex; align-items: center; gap: 9px; flex: 0 1 210px; min-width: 160px;
        border: 1.5px solid var(--border-color, #e5e7eb); border-radius: 12px; background: var(--bg-card, #fff);
        padding: 7px 10px; cursor: pointer; text-align: left; font: inherit; position: relative; }
    .lmd-tab:hover { border-color: #93c5fd; }
    .lmd-tab.is-unread { border-color: #2563eb; box-shadow: 0 0 0 1px #2563eb inset; }
    .lmd-tab.is-open { background: #eff6ff; border-color: #2563eb; }
    .lmd-av { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; background: #e5e7eb; flex: none; display: block; }
    .lmd-tab-t { min-width: 0; flex: 1; }
    .lmd-tab-t b { display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--text-primary, #111827);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lmd-tab-t small { display: block; font-size: 11px; color: var(--text-muted, #6b7280); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lmd-tab .lmd-count { position: absolute; top: -7px; right: -7px; border: 2px solid var(--bg-card, #fff); }

    /* Conversation state, kept apart from the role colours. */
    .lmd-st { width: 8px; height: 8px; border-radius: 50%; flex: none; background: #cbd5e1; }
    .lmd-st.is-unread { background: #dc2626; }
    .lmd-st.is-responded { width: auto; height: auto; background: none; color: #16a34a; font-size: 11px; font-weight: 800; }

    .lmd-btn { border: 1.5px solid var(--border-color, #e5e7eb); background: var(--bg-card, #fff); border-radius: 10px;
        height: 42px; min-width: 42px; padding: 0 10px; cursor: pointer; font: inherit; font-size: 13px; font-weight: 800;
        color: #2563eb; display: inline-flex; align-items: center; justify-content: center; gap: 6px; flex: none; }
    .lmd-btn:hover { background: #eff6ff; }
    .lmd-btn svg { width: 17px; height: 17px; }

    .lmd-dnd { display: flex; align-items: center; gap: 8px; flex: none; font-size: 11.5px; color: var(--text-muted, #6b7280); }
    .lmd-dnd b { display: block; font-size: 12px; color: var(--text-primary, #111827); }
    .lmd-switch { position: relative; width: 38px; height: 22px; border-radius: 999px; background: #cbd5e1; border: 0;
        cursor: pointer; flex: none; transition: background .15s; }
    .lmd-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%;
        background: #fff; transition: left .15s; }
    .lmd-switch[aria-checked="true"] { background: #2563eb; }
    .lmd-switch[aria-checked="true"]::after { left: 19px; }
    .lmd-set { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700;
        color: var(--text-secondary, #374151); text-decoration: none; flex: none; }
    .lmd-set svg { width: 17px; height: 17px; }

    /* ── The overflow list (+N) ──────────────────────────────── */
    .lmd-more { position: fixed; z-index: 895; width: 300px; max-height: 360px; overflow-y: auto;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 14px;
        box-shadow: 0 20px 50px -20px rgba(15,27,53,.5); padding: 6px; }
    .lmd-more button { display: flex; align-items: center; gap: 10px; width: 100%; border: 0; background: none;
        padding: 8px; border-radius: 10px; cursor: pointer; text-align: left; font: inherit; }
    .lmd-more button:hover { background: var(--bg-card-hover, #f8fafc); }

    /* ── The chat window ─────────────────────────────────────── */
    .lmd-chat { position: fixed; right: 24px; bottom: var(--lmd-base, 24px); z-index: 9990;
        width: 380px; max-width: calc(100vw - 48px); height: var(--lmd-chat-h, 460px);
        display: flex; flex-direction: column; background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 18px; overflow: hidden;
        box-shadow: 0 28px 70px -24px rgba(15,27,53,.55); }
    .lmd-chat[hidden] { display: none; }
    .lmd-ch { display: flex; align-items: center; gap: 10px; padding: 12px 12px 10px; border-bottom: 1px solid var(--border-color, #e5e7eb); }
    .lmd-ch .lmd-av { width: 42px; height: 42px; }
    .lmd-ch-t { flex: 1; min-width: 0; }
    .lmd-ch-t a, .lmd-ch-t span.n { display: block; font-size: 15px; font-weight: 800; color: var(--text-primary, #111827);
        text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lmd-ch-t a:hover { text-decoration: underline; }
    .lmd-ch-t small { display: block; font-size: 11.5px; color: var(--text-muted, #6b7280); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lmd-ch-t .on { color: #16a34a; font-weight: 700; }
    .lmd-ic { border: 0; background: none; width: 30px; height: 30px; border-radius: 8px; cursor: pointer;
        color: var(--text-muted, #6b7280); display: inline-flex; align-items: center; justify-content: center; flex: none; position: relative; }
    .lmd-ic:hover { background: var(--bg-card-hover, #f1f5f9); color: var(--text-primary, #111827); }
    .lmd-ic svg { width: 17px; height: 17px; }
    .lmd-menu { position: absolute; top: 34px; right: 0; z-index: 2; width: 190px; background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; box-shadow: 0 16px 40px -18px rgba(15,27,53,.5); padding: 5px; }
    .lmd-menu[hidden] { display: none; }
    .lmd-menu a, .lmd-menu button { display: block; width: 100%; text-align: left; border: 0; background: none; padding: 8px 10px;
        border-radius: 8px; font: inherit; font-size: 13px; color: var(--text-primary, #111827); text-decoration: none; cursor: pointer; }
    .lmd-menu a:hover, .lmd-menu button:hover { background: var(--bg-card-hover, #f1f5f9); }

    .lmd-body { flex: 1; min-height: 0; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px; overscroll-behavior: contain; }
    .lmd-msg { max-width: 82%; padding: 9px 12px; border-radius: 14px; font-size: 13px; line-height: 1.5; word-break: break-word;
        background: var(--bg-card-hover, #f1f5f9); color: var(--text-primary, #111827); align-self: flex-start; }
    .lmd-msg.is-mine { align-self: flex-end; background: #eff6ff; }
    .lmd-msg small { display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 3px; font-size: 10.5px; color: var(--text-muted, #6b7280); }
    .lmd-tick { font-weight: 800; letter-spacing: -2px; }
    .lmd-tick.is-read { color: #2563eb; }
    .lmd-msg.is-failed { background: #fef2f2; }
    .lmd-msg.is-failed small { color: #b91c1c; }
    .lmd-msg.is-failed button { border: 0; background: none; color: #b91c1c; font: inherit; font-weight: 800; text-decoration: underline; cursor: pointer; padding: 0; }
    .lmd-note { font-size: 12.5px; color: var(--text-muted, #6b7280); text-align: center; padding: 18px 8px; }

    .lmd-compose { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
    .lmd-compose input { flex: 1; min-width: 0; border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px;
        padding: 10px 12px; font: inherit; font-size: 13px; background: var(--bg-card, #fff); color: var(--text-primary, #111827); }
    .lmd-compose input:focus { outline: none; border-color: #2563eb; }
    .lmd-compose button { border: 0; border-radius: 12px; background: #2563eb; color: #fff; width: 46px; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; flex: none; }
    .lmd-compose button svg { width: 18px; height: 18px; }
    .lmd-cf { display: flex; align-items: center; gap: 14px; padding: 8px 14px 10px; font-size: 12.5px; }
    .lmd-cf button, .lmd-cf a { border: 0; background: none; padding: 0; font: inherit; font-weight: 700; cursor: pointer;
        color: var(--text-secondary, #374151); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .lmd-cf a { color: #2563eb; }
    .lmd-cf .is-muted { color: #dc2626; }

    /* ── A phone: one bubble, the list opens as a sheet ───────── */
    .lmd-bubble { display: none; position: fixed; right: 16px; bottom: 16px; z-index: 890; border: 0; cursor: pointer;
        align-items: center; gap: 8px; padding: 12px 16px; border-radius: 999px; background: #2563eb; color: #fff;
        font: inherit; font-weight: 800; font-size: 14px; box-shadow: 0 14px 30px -12px rgba(37,99,235,.7); }
    .lmd-bubble svg { width: 20px; height: 20px; }
    .lmd-bubble .lmd-count { background: #fff; color: #2563eb; }

    /* A smaller laptop: the controls give up their words before the
       conversations give up their tabs. */
    @media (max-width: 1360px) {
        .lmd-home small, .lmd-dnd span, .lmd-set span { display: none; }
        .lmd-dnd::before { content: 'DND'; font-weight: 800; color: var(--text-secondary, #374151); font-size: 11.5px; }
    }

    @media (max-width: 768px) {
        .lmd-bar, .lmd-chat { display: none !important; }
        .lmd-bubble { display: inline-flex; }
        body.has-lmd .cl-main { padding-bottom: 80px; }
    }

    /* display beats the hidden attribute, so these say so. */
    .lmd-count[hidden], .lmd-btn[hidden], .lmd-menu a[hidden], .lmd-cf a[hidden] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var bar = document.getElementById('lmdBar');
    if (! bar) return;

    var chat   = document.getElementById('lmdChat');
    var tabsEl = bar.querySelector('[data-lmd-tabs]');
    var moreBtn = bar.querySelector('[data-lmd-more]');
    var totalEl = document.querySelectorAll('[data-lmd-total]');
    var dndBtn = bar.querySelector('[data-lmd-dnd]');
    var me = @json(auth()->id());
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var urls = {
        list: @json(route('conversations.index')),
        show: @json(route('conversations.show', ['conversation' => '__ID__'])),
        send: @json(route('conversations.messages.store', ['conversation' => '__ID__'])),
        read: @json(route('conversations.mark-read', ['conversation' => '__ID__'])),
        mute: @json(route('conversations.mute', ['conversation' => '__ID__'])),
        page: @json(route('client.chat.show', ['conversation' => '__ID__'])),
    };
    var POLL_MS = 20000;
    var KEY = 'lmd.v1', SEEN = 'lmd.seen', DND = 'lmd.dnd';

    document.body.classList.add('has-lmd');

    function at(u, id) { return u.replace('__ID__', id); }
    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }
    function ago(iso) {
        if (! iso) return '';
        var d = new Date(iso), s = Math.max(0, (Date.now() - d.getTime()) / 1000);
        if (s < 60) return 'now';
        if (s < 86400 && d.getDate() === new Date().getDate()) return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        if (s < 172800) return 'Yesterday';
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
    function read(k, fb) { try { var v = JSON.parse(sessionStorage.getItem(k)); return v == null ? fb : v; } catch (e) { return fb; } }
    function write(k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    function get(u) {
        return fetch(u, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (! r.ok) throw new Error(r.status); return r.json(); });
    }
    function post(u, body) {
        return fetch(u, { method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : null })
            .then(function (r) { if (! r.ok) throw new Error(r.status); return r.json(); });
    }

    /*
     * What the dock shows, remembered for the browser session only (guide §7):
     * the tabs the client opened, the one expanded, and the ones they closed
     * (with the time, so a newer message can bring a closed one back, §12).
     */
    var state = Object.assign({ tabs: [], expanded: null, closed: {} }, read(KEY, {}));
    function save() { write(KEY, state); }

    var convs = {};        // id -> conversation row from the list endpoint
    var first = true;      // the first load sets the baseline; it never pings

    /* ── Do Not Disturb (§8) ─────────────────────────────────── */
    function dndOn() { try { return localStorage.getItem(DND) === '1'; } catch (e) { return false; } }
    function paintDnd() { dndBtn.setAttribute('aria-checked', dndOn() ? 'true' : 'false'); }
    dndBtn.addEventListener('click', function () {
        try { localStorage.setItem(DND, dndOn() ? '0' : '1'); } catch (e) {}
        paintDnd();
    });
    paintDnd();

    /* One short ping, made here so there is no sound file to load. */
    var audio = null;
    function ping() {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            var o = audio.createOscillator(), g = audio.createGain();
            o.type = 'sine'; o.frequency.value = 880;
            g.gain.setValueAtTime(0.0001, audio.currentTime);
            g.gain.exponentialRampToValueAtTime(0.18, audio.currentTime + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + 0.22);
            o.connect(g); g.connect(audio.destination);
            o.start(); o.stop(audio.currentTime + 0.24);
        } catch (e) {}
    }

    function status(c) {
        if (Number(c.unread_count || 0) > 0) return 'unread';
        if (c.last_message_sender_id === me) return 'responded';
        return 'read';
    }

    /* ── Poll the same list the Messages page reads ───────────── */
    function load() {
        return get(urls.list).then(function (data) {
            var rows = data.data || [];
            var seen = read(SEEN, {});
            var pinged = false, total = 0;

            rows.forEach(function (c) {
                convs[c.id] = c;
                var unread = Number(c.unread_count || 0);
                if (! c.muted_at) total += unread;

                var incoming = unread > 0 && c.last_message_sender_id !== me;
                var isNew = incoming && c.last_message_at && seen[c.id] !== c.last_message_at;

                if (isNew) {
                    // A closed card comes back when something newer arrives.
                    var closedAt = state.closed[c.id];
                    if (closedAt && new Date(closedAt) < new Date(c.last_message_at)) delete state.closed[c.id];

                    if (state.tabs.indexOf(c.id) === -1 && ! state.closed[c.id]) state.tabs.unshift(c.id);

                    // One ping per new message, never repeated, never on the
                    // first load, never when muted or Do Not Disturb.
                    if (! first && ! pinged && ! dndOn() && ! c.muted_at) { ping(); pinged = true; }
                }

                if (c.last_message_at) seen[c.id] = c.last_message_at;
            });

            write(SEEN, seen);
            state.tabs = state.tabs.filter(function (id) { return convs[id]; });
            save();
            first = false;

            totalEl.forEach(function (el) { el.textContent = total > 99 ? '99+' : total; el.hidden = ! total; });
            renderTabs();

            // The open conversation follows along without being reopened.
            if (state.expanded && convs[state.expanded] && chat.dataset.last !== String(convs[state.expanded].last_message_at)) {
                loadThread(state.expanded, true);
            }
        }).catch(function () {});
    }

    /* ── Tabs (§2, §9) ───────────────────────────────────────── */
    function capacity() {
        var room = tabsEl.getBoundingClientRect().width;
        return Math.max(1, Math.min(4, Math.floor((room + 8) / 168)));
    }

    function tabHtml(c) {
        var p = c.peer || { name: 'Conversation', avatar: '' };
        var st = status(c), unread = Number(c.unread_count || 0);
        var who = c.last_message_sender_id === me ? 'You: ' : '';
        return '<button type="button" class="lmd-tab' + (st === 'unread' ? ' is-unread' : '') + (state.expanded === c.id ? ' is-open' : '')
            + '" data-lmd-open="' + c.id + '" title="' + esc(p.name) + '">'
            + '<img class="lmd-av" src="' + esc(p.avatar) + '" alt="">'
            + '<span class="lmd-tab-t"><b>' + (st === 'responded' ? '<span class="lmd-st is-responded" title="You replied">✓</span>'
                : '<span class="lmd-st is-' + st + '" title="' + (st === 'unread' ? 'Unread' : 'Read, not answered yet') + '"></span>')
            + esc(p.name) + '</b><small>' + esc(who + (c.last_message_body || '')) + (c.last_message_at ? ' · ' + esc(ago(c.last_message_at)) : '') + '</small></span>'
            + (unread ? '<span class="lmd-count">' + unread + '</span>' : '')
            + '</button>';
    }

    var overflow = [];
    function renderTabs() {
        var ids = state.tabs.filter(function (id) { return convs[id]; });
        var cap = capacity();
        var shown = ids.slice(0, cap);

        // The expanded one never drops out of view while it is being used.
        if (state.expanded && ids.indexOf(state.expanded) >= cap) {
            shown[cap - 1] = state.expanded;
        }

        overflow = ids.filter(function (id) { return shown.indexOf(id) === -1; });
        // Nothing open: say what the empty space is for, instead of a blank bar.
        tabsEl.innerHTML = shown.length
            ? shown.map(function (id) { return tabHtml(convs[id]); }).join('')
            : '<button type="button" class="lmd-empty" data-lmd-list>No chats open. Click + to open a conversation.</button>';
        moreBtn.hidden = ! overflow.length;
        moreBtn.textContent = '+' + overflow.length;
        layout();
    }

    function openMore() {
        var list = document.querySelector('.lmd-more');
        if (list) { list.remove(); return; }
        list = document.createElement('div');
        list.className = 'lmd-more';
        list.innerHTML = overflow.map(function (id) {
            var c = convs[id], p = c.peer || {};
            var unread = Number(c.unread_count || 0);
            return '<button type="button" data-lmd-open="' + id + '"><img class="lmd-av" src="' + esc(p.avatar) + '" alt="">'
                + '<span class="lmd-tab-t"><b>' + esc(p.name) + '</b><small>' + esc(c.last_message_body || '') + ' · ' + esc(ago(c.last_message_at)) + '</small></span>'
                + (unread ? '<span class="lmd-count">' + unread + '</span>' : '') + '</button>';
        }).join('');
        document.body.appendChild(list);
        var r = moreBtn.getBoundingClientRect();
        list.style.left = Math.max(12, r.right - 300) + 'px';
        list.style.bottom = (window.innerHeight - r.top + 8) + 'px';
    }

    /* ── The chat window (§5, §6) ────────────────────────────── */
    var cHead = chat.querySelector('[data-lmd-head]');
    var cBody = chat.querySelector('[data-lmd-body]');
    var cForm = chat.querySelector('[data-lmd-form]');
    var cInput = chat.querySelector('[data-lmd-input]');
    var cMenu = chat.querySelector('[data-lmd-menu]');

    function tick(m) {
        // Read only when the other side's read is on record. There is no
        // "delivered" on record, so it is not shown (guide §6).
        var theirs = (m.reads || []).some(function (r) { return r.user_id !== me; });
        return theirs ? '<span class="lmd-tick is-read" title="Read">✓✓</span>' : '<span class="lmd-tick" title="Sent">✓</span>';
    }

    function msgHtml(m) {
        var mine = m.sender_id === me;
        var time = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';
        return '<div class="lmd-msg' + (mine ? ' is-mine' : '') + '">' + esc(m.body)
            + '<small>' + esc(time) + (mine ? ' ' + tick(m) : '') + '</small></div>';
    }

    function paintHead(c) {
        var p = c.peer || {};
        var sub = [p.subtitle, p.gr_id].filter(Boolean).join(' · ');
        var name = p.profile ? '<a href="' + esc(p.profile) + '" target="_blank" rel="noopener">' + esc(p.name) + '</a>' : '<span class="n">' + esc(p.name) + '</span>';
        var av = '<img class="lmd-av" src="' + esc(p.avatar) + '" alt="">';
        cHead.innerHTML = (p.profile ? '<a href="' + esc(p.profile) + '" target="_blank" rel="noopener" aria-label="View profile">' + av + '</a>' : av)
            + '<div class="lmd-ch-t">' + name + '<small>' + esc(sub) + (p.online ? ' · <span class="on">● Online now</span>' : '') + '</small></div>';

        chat.querySelectorAll('[data-lmd-page]').forEach(function (a) { a.href = at(urls.page, c.id); });
        chat.querySelectorAll('[data-lmd-profile]').forEach(function (a) { a.hidden = ! p.profile; if (p.profile) a.href = p.profile; });
        chat.querySelectorAll('[data-lmd-mute]').forEach(function (b) {
            b.textContent = c.muted_at ? 'Unmute' : 'Mute';
            b.classList.toggle('is-muted', !! c.muted_at);
        });
    }

    function loadThread(id, quiet) {
        var c = convs[id];
        if (! c) return;
        paintHead(c);
        if (! quiet) cBody.innerHTML = '<div class="lmd-note">Loading…</div>';

        get(at(urls.show, id)).then(function (data) {
            var msgs = ((data.messages || {}).data || []).slice().reverse();
            var nearBottom = cBody.scrollHeight - cBody.scrollTop - cBody.clientHeight < 60;
            cBody.innerHTML = msgs.map(msgHtml).join('') || '<div class="lmd-note">No messages yet. Say hello.</div>';
            if (! quiet || nearBottom) cBody.scrollTop = cBody.scrollHeight;
            chat.dataset.last = String(c.last_message_at);

            // Opening it is reading it, by the same rule as the Messages page.
            if (Number(c.unread_count || 0) > 0) {
                post(at(urls.read, id)).then(function () { c.unread_count = 0; renderTabs(); load(); }).catch(function () {});
            }
        }).catch(function () {
            cBody.innerHTML = '<div class="lmd-note">Could not open this conversation.</div>';
        });
    }

    function expand(id) {
        id = Number(id);
        delete state.closed[id];
        if (state.tabs.indexOf(id) === -1) state.tabs.unshift(id);
        state.expanded = id;
        save();
        chat.hidden = false;
        cMenu.hidden = true;
        renderTabs();
        loadThread(id);
        setTimeout(function () { cInput.focus(); }, 50);
        // One floating window at a time in the corner with the assistant.
        document.dispatchEvent(new CustomEvent('gr:float-open', { detail: 'live-dock' }));
    }

    function minimize() {
        state.expanded = null; save();
        chat.hidden = true;
        renderTabs();
    }

    function closeCard(id) {
        id = Number(id || state.expanded);
        state.tabs = state.tabs.filter(function (t) { return t !== id; });
        state.closed[id] = new Date().toISOString();
        if (state.expanded === id) { state.expanded = null; chat.hidden = true; }
        save();
        renderTabs();
    }

    function send(text) {
        var id = state.expanded;
        var pending = document.createElement('div');
        pending.className = 'lmd-msg is-mine';
        pending.innerHTML = esc(text) + '<small>Sending…</small>';
        cBody.querySelector('.lmd-note')?.remove();
        cBody.appendChild(pending);
        cBody.scrollTop = cBody.scrollHeight;

        post(at(urls.send, id), { body: text }).then(function (m) {
            pending.outerHTML = msgHtml(m);
            var c = convs[id];
            if (c) { c.last_message_sender_id = me; c.last_message_body = m.body; c.last_message_at = m.created_at; }
            chat.dataset.last = String(c && c.last_message_at);
            renderTabs();
        }).catch(function () {
            pending.classList.add('is-failed');
            pending.querySelector('small').innerHTML = 'Did not send. <button type="button">Retry</button>';
            pending.querySelector('button').addEventListener('click', function () { pending.remove(); send(text); });
        });
    }

    cForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = cInput.value.trim();
        if (! text || ! state.expanded) return;
        cInput.value = '';
        send(text);
    });

    document.addEventListener('click', function (e) {
        var o = e.target.closest('[data-lmd-open]');
        if (o) {
            document.querySelector('.lmd-more')?.remove();
            var id = Number(o.dataset.lmdOpen);
            if (state.expanded === id && ! chat.hidden) { minimize(); } else { expand(id); }
            return;
        }
        if (e.target.closest('[data-lmd-more]')) { openMore(); return; }
        if (! e.target.closest('.lmd-more')) document.querySelector('.lmd-more')?.remove();

        if (e.target.closest('[data-lmd-min]')) { minimize(); return; }
        if (e.target.closest('[data-lmd-close]')) { cMenu.hidden = true; closeCard(); return; }
        if (e.target.closest('[data-lmd-dots]')) { cMenu.hidden = ! cMenu.hidden; return; }
        if (! e.target.closest('[data-lmd-menu]')) cMenu.hidden = true;

        if (e.target.closest('[data-lmd-mute]')) {
            var id2 = state.expanded;
            post(at(urls.mute, id2)).then(function (d) {
                if (convs[id2]) convs[id2].muted_at = d.muted ? new Date().toISOString() : null;
                paintHead(convs[id2]);
                load();
            }).catch(function () {});
            cMenu.hidden = true;
            return;
        }

        // Live Messages and "+" open the list of every conversation.
        if (e.target.closest('[data-lmd-list]')) {
            if (window.grMessages) window.grMessages.toggle();
        }
    });

    /* ── The right-hand column (§ stacking) ──────────────────── *
     * The chat window sits on the bar; the messages list, when it is open,
     * sits on the chat window; the notifications sit on top of both. Each
     * takes a share of the height that is left, so all three fit.
     */
    function layout() {
        var root = document.documentElement.style;
        var desktop = window.innerWidth > 768;
        var base = desktop ? bar.offsetHeight + 12 : 24;
        var room = window.innerHeight - 96 - base;         // below the header
        var chatOpen = desktop && ! chat.hidden;
        var listOpen = !! document.querySelector('#msgDock.is-open:not(.is-min)');
        var notifOpen = !! document.querySelector('.tbm[data-notif-menu].open');

        // The notifications keep a readable share when they sit on top.
        var forWindows = room - (notifOpen && (chatOpen || listOpen) ? 170 : 0);
        var chatH = chatOpen ? Math.round(Math.min(460, listOpen ? forWindows * 0.52 : forWindows)) : 0;
        var listH = Math.round(Math.max(220, Math.min(600, window.innerHeight * 0.58, chatOpen ? forWindows - chatH - 12 : forWindows)));
        var notifBottom = base + (chatOpen ? chatH + 12 : 0) + (listOpen ? listH + 12 : 0);

        root.setProperty('--lmd-base', base + 'px');
        root.setProperty('--lmd-chat-h', chatH + 'px');
        root.setProperty('--lmd-chat', chatOpen ? (chatH + 12) + 'px' : '0px');
        root.setProperty('--md-h', listH + 'px');
        root.setProperty('--notif-bottom', notifBottom + 'px');
        root.setProperty('--notif-max', Math.max(140, window.innerHeight - notifBottom - 16) + 'px');
    }

    window.addEventListener('resize', renderTabs);
    new MutationObserver(layout).observe(document.getElementById('msgDock') || bar, { attributes: true, attributeFilter: ['class'] });
    new MutationObserver(layout).observe(chat, { attributes: true, attributeFilter: ['hidden'] });
    var bell = document.querySelector('.tbm[data-notif-menu]');
    if (bell) new MutationObserver(layout).observe(bell, { attributes: true, attributeFilter: ['class'] });

    // The assistant opened in the corner: the chat window steps down to its tab.
    document.addEventListener('gr:float-open', function (e) {
        if (e.detail === 'ai' && ! chat.hidden) minimize();
    });

    // Whoever opens a conversation from the Messages list gets it here.
    window.grLiveDock = { open: function (id) { if (window.innerWidth <= 768) return false; expand(id); return true; } };

    // Back where the client left it on the last page (§7).
    if (state.expanded) { chat.hidden = false; }
    layout();
    load().then(function () { if (state.expanded) loadThread(state.expanded); });
    setInterval(function () { if (! document.hidden) load(); }, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (! document.hidden) load(); });
})();
</script>
@endpush
@endonce

<div class="lmd-bar" id="lmdBar" role="region" aria-label="Live Messages">
    <button type="button" class="lmd-home" data-lmd-list title="All conversations">
        <span class="lmd-me">{{ $__lmdInitials ?: 'Me' }}</span>
        <span>
            <b>Live Messages <span class="lmd-count" data-lmd-total hidden>0</span></b>
            <small>Stay connected while you work.</small>
        </span>
    </button>
    <span class="lmd-sep"></span>

    <div class="lmd-tabs" data-lmd-tabs></div>

    <button type="button" class="lmd-btn" data-lmd-list title="Start or open another conversation" aria-label="Open another conversation">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
    <button type="button" class="lmd-btn" data-lmd-more hidden aria-label="More conversations">+0</button>
    <span class="lmd-sep"></span>

    <div class="lmd-dnd">
        <button type="button" class="lmd-switch" role="switch" aria-checked="false" data-lmd-dnd aria-label="Do Not Disturb"></button>
        <span><b>Do Not Disturb</b>Still receive messages (no sound)</span>
    </div>
    <a class="lmd-set" href="{{ route('client.notifications.index') }}" title="Message Settings" aria-label="Message Settings">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
        <span>Message Settings</span>
    </a>
</div>

<div class="lmd-chat" id="lmdChat" role="dialog" aria-label="Conversation" hidden>
    <div class="lmd-ch">
        <div data-lmd-head style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;"></div>
        <button type="button" class="lmd-ic" data-lmd-min aria-label="Minimize" title="Minimize">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>
        <a class="lmd-ic" data-lmd-page href="{{ $__lmdFull }}" aria-label="Open in Messages" title="Open in Messages">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="M4 9V5a1 1 0 0 1 1-1h4M15 4h4a1 1 0 0 1 1 1v4M20 15v4a1 1 0 0 1-1 1h-4M9 20H5a1 1 0 0 1-1-1v-4"/></svg>
        </a>
        <button type="button" class="lmd-ic" data-lmd-close aria-label="Close" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <span style="position:relative;">
            <button type="button" class="lmd-ic" data-lmd-dots aria-label="More options" title="More options">
                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
            </button>
            <div class="lmd-menu" data-lmd-menu hidden>
                <button type="button" data-lmd-mute>Mute</button>
                <a data-lmd-profile href="{{ $__lmdFull }}" target="_blank" rel="noopener" hidden>View Profile</a>
                <a data-lmd-page href="{{ $__lmdFull }}">Open in Messages</a>
                <button type="button" data-lmd-close>Close</button>
            </div>
        </span>
    </div>

    <div class="lmd-body" data-lmd-body></div>

    <form class="lmd-compose" data-lmd-form>
        <input type="text" data-lmd-input placeholder="Type a message…" maxlength="5000" autocomplete="off" aria-label="Type a message">
        <button type="submit" aria-label="Send">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
        </button>
    </form>
    <div class="lmd-cf">
        <button type="button" data-lmd-mute>Mute</button>
        <a data-lmd-profile href="{{ $__lmdFull }}" target="_blank" rel="noopener" hidden>View Profile</a>
        <a data-lmd-page href="{{ $__lmdFull }}">Open in Messages</a>
    </div>
</div>

<button type="button" class="lmd-bubble" data-lmd-list aria-label="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.9 9.9 0 0 1-4.2-.9L3 20l1.3-3.8A8.2 8.2 0 0 1 3 11.5a8.4 8.4 0 0 1 9-8.4 8.4 8.4 0 0 1 9 8.4z"/></svg>
    Messages <span class="lmd-count" data-lmd-total hidden>0</span>
</button>
@endif
@endauth
