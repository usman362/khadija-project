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

    .lmd-count { min-width: 20px; height: 20px; border-radius: 999px; background: #dc2626; color: #fff; font-size: 11px;
        font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 6px; }

    .lmd-sep { width: 1px; align-self: stretch; background: var(--border-color, #e5e7eb); flex: none; }

    /* Tabs, then + and +N straight after them (Sir Peter's dock), with the
       space left over pushed to the right, before Do Not Disturb. */
    .lmd-mid { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
    .lmd-tabs { display: flex; gap: 8px; flex: 0 1 auto; min-width: 0; overflow: hidden; }
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
    .lmd-tab-t small { display: flex; gap: 8px; align-items: baseline; }
    .lmd-tab-t small .lmd-pv { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lmd-tab-t small em { font-style: normal; flex: none; }
    .lmd-ok { font-style: normal; color: #16a34a; font-weight: 800; }
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
    .lmd-sound { border: 0; background: none; padding: 4px; cursor: pointer; color: #2563eb; display: inline-flex; flex: none; border-radius: 8px; }
    .lmd-sound:hover { background: #eff6ff; }
    .lmd-sound svg { width: 20px; height: 20px; }
    .lmd-sound [data-off] { display: none; }
    .lmd-sound[aria-pressed="true"] { color: var(--text-muted, #6b7280); }
    .lmd-sound[aria-pressed="true"] [data-on] { display: none; }
    .lmd-sound[aria-pressed="true"] [data-off] { display: inline; }
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

    /* ── Sir Peter's dock (18 Sep), matched piece by piece ─────── */
    .lmd-ch { padding: 14px 14px 12px; align-items: flex-start; }
    .lmd-ch .lmd-av { width: 46px; height: 46px; }
    .lmd-ch-t small.role { color: var(--text-secondary, #4b5563); }
    .lmd-ch-t .on { display: flex; align-items: center; gap: 5px; margin-top: 2px; font-size: 11.5px; }
    .lmd-ch-t .on::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #16a34a; }
    .lmd-ch .lmd-ic { margin-top: 6px; }
    .lmd-ic-box { border: 1.5px solid #2563eb; color: #2563eb; }
    .lmd-ic-box:hover { background: #eff6ff; color: #2563eb; }
    .lmd-body { background: var(--bg-card, #fff); gap: 10px; padding: 14px; }
    .lmd-msg { max-width: 80%; padding: 10px 13px; border-radius: 14px; background: var(--bg-card-hover, #f1f5f9); }
    .lmd-msg small { justify-content: flex-start; }
    .lmd-msg.is-mine { background: #e8f0fe; }
    .lmd-msg.is-mine small { justify-content: flex-end; }
    .lmd-att { display: flex; align-items: center; gap: 8px; margin-top: 6px; padding: 7px 9px; border-radius: 10px;
        background: rgba(255,255,255,.7); border: 1px solid var(--border-color, #e5e7eb); color: #2563eb; font-size: 12px;
        font-weight: 700; text-decoration: none; word-break: break-all; }
    .lmd-att img { width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; display: block; }
    .lmd-att.is-img { padding: 3px; display: block; }
    .lmd-typing { display: inline-flex; align-self: flex-start; align-items: center; gap: 10px; margin: 0 14px 8px;
        padding: 8px 14px; border-radius: 999px; background: var(--bg-card-hover, #f1f5f9); font-size: 12px; color: var(--text-secondary, #4b5563); }
    .lmd-typing[hidden] { display: none; }
    .lmd-dots { display: inline-flex; gap: 3px; }
    .lmd-dots i { width: 5px; height: 5px; border-radius: 50%; background: #64748b; animation: lmdDot 1.2s infinite; }
    .lmd-dots i:nth-child(2) { animation-delay: .15s; } .lmd-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes lmdDot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-2px); } }
    .lmd-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 14px 0; border-top: 1px solid var(--border-color, #e5e7eb); }
    .lmd-chips[hidden] { display: none; }
    .lmd-chip { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; padding: 4px 8px; border-radius: 8px;
        background: #eff6ff; color: #1d4ed8; font-size: 11.5px; font-weight: 700; }
    .lmd-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 220px; }
    .lmd-chip.is-bad { background: #fef2f2; color: #b91c1c; }
    .lmd-chip button { border: 0; background: none; color: inherit; cursor: pointer; font-weight: 800; padding: 0; }
    .lmd-compose { align-items: center; gap: 10px; padding: 12px 14px; }
    .lmd-field { flex: 1; min-width: 0; display: flex; align-items: center; gap: 2px; position: relative;
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; padding: 0 6px 0 0; background: var(--bg-card, #fff); }
    .lmd-field:focus-within { border-color: #2563eb; }
    .lmd-compose .lmd-field input { border: 0; padding: 12px 12px; background: transparent; }
    .lmd-compose .lmd-field input:focus { outline: none; }
    .lmd-compose .lmd-fi { border: 0; background: none; width: 32px; height: 32px; border-radius: 8px; color: var(--text-secondary, #4b5563); padding: 0; }
    .lmd-compose .lmd-fi:hover { background: var(--bg-card-hover, #f1f5f9); }
    .lmd-compose .lmd-fi svg { width: 19px; height: 19px; }
    .lmd-compose .lmd-send { width: 44px; height: 44px; border-radius: 12px; }
    .lmd-emojis { position: absolute; right: 0; bottom: calc(100% + 8px); z-index: 3; width: 232px; display: grid;
        grid-template-columns: repeat(8, 1fr); gap: 2px; padding: 8px; background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 12px; box-shadow: 0 16px 40px -18px rgba(15,27,53,.5); }
    .lmd-emojis[hidden] { display: none; }
    .lmd-emojis button { border: 0; background: none; font-size: 18px; line-height: 1; padding: 4px 0; border-radius: 6px; cursor: pointer; }
    .lmd-emojis button:hover { background: var(--bg-card-hover, #f1f5f9); }
    .lmd-cf { gap: 14px; padding: 10px 14px 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
    .lmd-cf svg { width: 16px; height: 16px; }
    .lmd-cf [data-lmd-mute] { color: var(--text-primary, #111827); }
    .lmd-cf [data-lmd-mute] svg { color: #dc2626; }
    .lmd-cf [data-lmd-profile] { color: var(--text-primary, #111827); }
    .lmd-cf [data-lmd-profile] svg { color: #2563eb; }
    .lmd-cf a.is-link { color: #2563eb; }
    .lmd-cf .lmd-cf-more { margin-left: auto; color: var(--text-secondary, #374151); letter-spacing: 1px; font-size: 13px; }

    /* The bar's right side, as drawn: speaker, switch, two lines, then settings. */
    .lmd-set { font-weight: 500; font-size: 12px; color: var(--text-secondary, #4b5563); padding-left: 14px;
        border-left: 1px solid var(--border-color, #e5e7eb); align-self: stretch; }
    .lmd-dnd span { font-size: 11px; line-height: 1.35; max-width: 150px; }
    .lmd-dnd small { display: block; font-size: 11px; }
    .lmd-cf > * { white-space: nowrap; }

    /* ── A phone: one bubble, the list opens as a sheet ───────── */
    .lmd-bubble { display: none; position: fixed; right: 16px; bottom: 16px; z-index: 890; border: 0; cursor: pointer;
        align-items: center; gap: 8px; padding: 12px 16px; border-radius: 999px; background: #2563eb; color: #fff;
        font: inherit; font-weight: 800; font-size: 14px; box-shadow: 0 14px 30px -12px rgba(37,99,235,.7); }
    .lmd-bubble svg { width: 20px; height: 20px; }
    .lmd-bubble .lmd-count { background: #fff; color: #2563eb; }

    /* A smaller laptop: the controls give up their words before the
       conversations give up their tabs. */
    @media (max-width: 1360px) {
        .lmd-dnd small, .lmd-set span { display: none; }
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
    var soundBtn = bar.querySelector('[data-lmd-sound]');
    var midEl = bar.querySelector('[data-lmd-mid]');
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
    function paintDnd() {
        var on = dndOn();
        dndBtn.setAttribute('aria-checked', on ? 'true' : 'false');
        soundBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        soundBtn.title = on ? 'Sound off (Do Not Disturb): click to turn it on' : 'Sound on: click to mute';
    }
    function toggleDnd() {
        try { localStorage.setItem(DND, dndOn() ? '0' : '1'); } catch (e) {}
        paintDnd();
        // Turning sound back on plays the ping once, so the client hears it works.
        if (! dndOn()) ping();
    }
    dndBtn.addEventListener('click', toggleDnd);
    soundBtn.addEventListener('click', toggleDnd);
    paintDnd();

    // Browsers only allow sound after the person has clicked on the page,
    // so the sound is readied on the first click; later pings then play.
    document.addEventListener('pointerdown', function () {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            if (audio.state === 'suspended') audio.resume();
        } catch (e) {}
    }, { once: true });

    /* One short ping, made here so there is no sound file to load. */
    var audio = null;
    function ping() {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            if (audio.state === 'suspended') audio.resume();
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
        // The group's width less the + and +N buttons beside the tabs.
        var room = midEl.getBoundingClientRect().width - 110;
        return Math.max(1, Math.min(4, Math.floor((room + 8) / 168)));
    }

    function tabHtml(c) {
        var p = c.peer || { name: 'Conversation', avatar: '' };
        var st = status(c), unread = Number(c.unread_count || 0);
        var mine = c.last_message_sender_id === me;
        var who = mine ? '' : ((p.name || '').split(' ')[0] + ': ');
        return '<button type="button" class="lmd-tab' + (st === 'unread' ? ' is-unread' : '') + (state.expanded === c.id ? ' is-open' : '')
            + '" data-lmd-open="' + c.id + '" title="' + esc(p.name) + '">'
            + '<img class="lmd-av" src="' + esc(p.avatar) + '" alt="">'
            // Red dot unread, grey dot read; the green tick on the preview line is "you replied".
            + '<span class="lmd-tab-t"><b><span class="lmd-st is-' + (st === 'unread' ? 'unread' : 'read') + '" title="'
                + (st === 'unread' ? 'Unread' : (st === 'responded' ? 'You replied' : 'Read, not answered yet')) + '"></span>'
            + esc(p.name) + '</b><small><span class="lmd-pv">' + (mine ? '<i class="lmd-ok">✓</i> ' : '') + esc(who + (c.last_message_body || '')) + '</span>'
            + (c.last_message_at ? '<em>' + esc(ago(c.last_message_at)) + '</em>' : '') + '</small></span>'
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
        // Only real conversations show; with none open the bar keeps just + (18 Sep).
        tabsEl.innerHTML = shown.map(function (id) { return tabHtml(convs[id]); }).join('');
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
    var cTyping = chat.querySelector('[data-lmd-typing]');
    var cTypingName = chat.querySelector('[data-lmd-typing-name]');
    var cChips = chat.querySelector('[data-lmd-chips]');
    var cFile = chat.querySelector('[data-lmd-file]');
    var cEmojis = chat.querySelector('[data-lmd-emojis]');
    var uploadUrl = @json(route('attachments.store'));
    var typingUrl = @json(route('conversations.typing', ['conversation' => '__ID__']));
    var pendingFiles = [];   // { name, id|null, bad }

    function tick(m) {
        // Read only when the other side's read is on record. There is no
        // "delivered" on record, so it is not shown (guide §6).
        var theirs = (m.reads || []).some(function (r) { return r.user_id !== me; });
        return theirs ? '<span class="lmd-tick is-read" title="Read">✓✓</span>' : '<span class="lmd-tick" title="Sent">✓</span>';
    }

    function msgHtml(m) {
        var mine = m.sender_id === me;
        var time = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '';
        var atts = (m.attachments || []).map(function (a) {
            var url = esc(a.url || '#'), name = esc(a.file_name || 'File');
            return a.is_image
                ? '<a class="lmd-att is-img" href="' + url + '" target="_blank" rel="noopener"><img src="' + url + '" alt="' + name + '" loading="lazy"></a>'
                : '<a class="lmd-att" href="' + url + '" target="_blank" rel="noopener">' + name + (a.size_label ? ' · ' + esc(a.size_label) : '') + '</a>';
        }).join('');
        return '<div class="lmd-msg' + (mine ? ' is-mine' : '') + '">' + esc(m.body) + atts
            + '<small>' + esc(time) + (mine ? ' ' + tick(m) : '') + '</small></div>';
    }

    function paintHead(c) {
        var p = c.peer || {};
        var sub = [p.subtitle, p.gr_id].filter(Boolean).join(' · ');
        var name = p.profile ? '<a href="' + esc(p.profile) + '" target="_blank" rel="noopener">' + esc(p.name) + '</a>' : '<span class="n">' + esc(p.name) + '</span>';
        var av = '<img class="lmd-av" src="' + esc(p.avatar) + '" alt="">';
        cHead.innerHTML = (p.profile ? '<a href="' + esc(p.profile) + '" target="_blank" rel="noopener" aria-label="View profile">' + av + '</a>' : av)
            + '<div class="lmd-ch-t">' + name + (sub ? '<small class="role">' + esc(sub) + '</small>' : '')
            + (p.online ? '<small class="on">Online now</small>' : '') + '</div>';

        chat.querySelectorAll('[data-lmd-page]').forEach(function (a) { a.href = at(urls.page, c.id); });
        chat.querySelectorAll('[data-lmd-profile]').forEach(function (a) { a.hidden = ! p.profile; if (p.profile) a.href = p.profile; });
        chat.querySelectorAll('[data-lmd-mute]').forEach(function (b) {
            var label = b.querySelector('span') || b;
            label.textContent = c.muted_at ? 'Unmute' : 'Mute';
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
            var html = msgs.map(msgHtml).join('') || '<div class="lmd-note">No messages yet. Say hello.</div>';
            // A quiet refresh that changed nothing leaves the list alone, so
            // a message being read does not jump.
            // A message still sending, or failed with its Retry, is not wiped.
            var busy = cBody.querySelector('.lmd-msg.is-failed, .lmd-msg[data-pending]');
            if (! quiet || (! busy && cBody.dataset.html !== html)) { cBody.innerHTML = html; cBody.dataset.html = html; }
            var typers = data.typing || [];
            cTyping.hidden = ! typers.length;
            cTypingName.textContent = typers.length ? String(typers[0]).split(' ')[0] + ' is typing…' : '';
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
        if (state.expanded !== id) { pendingFiles = []; paintChips(); cTyping.hidden = true; cBody.dataset.html = ''; }
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

    function send(text, attachmentIds) {
        var id = state.expanded;
        attachmentIds = attachmentIds || [];
        var pending = document.createElement('div');
        pending.className = 'lmd-msg is-mine';
        pending.dataset.pending = '1';
        pending.innerHTML = esc(text) + '<small>Sending…</small>';
        cBody.querySelector('.lmd-note')?.remove();
        cBody.appendChild(pending);
        cBody.scrollTop = cBody.scrollHeight;

        post(at(urls.send, id), { body: text, attachment_ids: attachmentIds }).then(function (m) {
            pending.outerHTML = msgHtml(m);
            cBody.dataset.html = '';
            var c = convs[id];
            if (c) { c.last_message_sender_id = me; c.last_message_body = m.body; c.last_message_at = m.created_at; }
            chat.dataset.last = String(c && c.last_message_at);
            renderTabs();
        }).catch(function () {
            pending.classList.add('is-failed');
            pending.querySelector('small').innerHTML = 'Did not send. <button type="button">Retry</button>';
            pending.querySelector('button').addEventListener('click', function () { pending.remove(); send(text, attachmentIds); });
        });
    }

    cForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (! state.expanded) return;
        if (pendingFiles.some(function (f) { return ! f.id && ! f.bad; })) return;   // still uploading
        var ids = pendingFiles.filter(function (f) { return f.id; }).map(function (f) { return f.id; });
        var text = cInput.value.trim();
        // The API needs a body; a file on its own says what was sent.
        if (! text && ids.length) text = 'Sent ' + ids.length + ' attachment' + (ids.length > 1 ? 's' : '') + '.';
        if (! text) return;
        cInput.value = '';
        pendingFiles = []; paintChips();
        cEmojis.hidden = true;
        send(text, ids);
    });

    /* ── Typing: tell the other side, at most every 3 seconds ──── */
    var lastTyping = 0;
    cInput.addEventListener('input', function () {
        var now = Date.now();
        if (! state.expanded || ! cInput.value.trim() || now - lastTyping < 3000) return;
        lastTyping = now;
        post(at(typingUrl, state.expanded)).catch(function () {});
    });

    // While a conversation is open, check it every few seconds: new replies
    // and "is typing" both come from here.
    setInterval(function () {
        if (state.expanded && ! chat.hidden && ! document.hidden) loadThread(state.expanded, true);
    }, 4000);

    /* ── Attachments: upload on pick, join on send ─────────────── */
    function paintChips() {
        cChips.hidden = ! pendingFiles.length;
        cChips.innerHTML = pendingFiles.map(function (f, i) {
            return '<span class="lmd-chip' + (f.bad ? ' is-bad' : '') + '"><span>' + esc(f.bad ? f.name + ': ' + f.bad : (f.id ? '' : 'Uploading… ') + f.name)
                + '</span><button type="button" data-lmd-chip-rm="' + i + '" aria-label="Remove">×</button></span>';
        }).join('');
        layout();
    }
    cFile.addEventListener('change', function () {
        var id = state.expanded;
        Array.prototype.forEach.call(cFile.files || [], function (file) {
            var f = { name: file.name, id: null, bad: null };
            pendingFiles.push(f);
            var fd = new FormData();
            fd.append('file', file);
            fd.append('conversation_id', id);
            fetch(uploadUrl, { method: 'POST', credentials: 'same-origin', body: fd,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(function (r) { return r.json().then(function (j) { if (! r.ok) throw j; return j; }); })
                .then(function (j) { f.id = j.id; paintChips(); })
                .catch(function (j) { f.bad = (j && (j.message || (j.errors && Object.values(j.errors)[0][0]))) || 'Could not upload'; paintChips(); });
        });
        cFile.value = '';
        paintChips();
    });

    /* ── Emoji: a small grid, inserted where the cursor is ─────── */
    var EMOJI = ['😀','😂','😊','😍','🥳','😎','🤔','😅','👍','👏','🙏','🙌','🎉','❤️','🔥','✨','✅','👌','💯','📸','🎵','🍰','🥂','📅'];
    cEmojis.innerHTML = EMOJI.map(function (e) { return '<button type="button" data-lmd-emo>' + e + '</button>'; }).join('');
    cEmojis.addEventListener('click', function (e) {
        var b = e.target.closest('[data-lmd-emo]');
        if (! b) return;
        var v = cInput.value, a = cInput.selectionStart ?? v.length, z = cInput.selectionEnd ?? v.length;
        cInput.value = v.slice(0, a) + b.textContent + v.slice(z);
        cInput.focus();
        cInput.setSelectionRange(a + b.textContent.length, a + b.textContent.length);
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

        if (e.target.closest('[data-lmd-attach]')) { cFile.click(); return; }
        if (e.target.closest('[data-lmd-emoji]')) { cEmojis.hidden = ! cEmojis.hidden; return; }
        if (! e.target.closest('[data-lmd-emojis]')) cEmojis.hidden = true;
        var rm = e.target.closest('[data-lmd-chip-rm]');
        if (rm) { pendingFiles.splice(Number(rm.dataset.lmdChipRm), 1); paintChips(); return; }

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
    {{-- No "Live Messages" block: the bar shows the conversations themselves (18 Sep). --}}

    <div class="lmd-mid" data-lmd-mid>
        <div class="lmd-tabs" data-lmd-tabs></div>

        <button type="button" class="lmd-btn" data-lmd-list title="Start or open another conversation" aria-label="Open another conversation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>
        <button type="button" class="lmd-btn" data-lmd-more hidden aria-label="More conversations">+0</button>
    </div>
    <span class="lmd-sep"></span>

    <div class="lmd-dnd">
        {{-- Sound on or off at a glance; the same setting as the switch. --}}
        <button type="button" class="lmd-sound" data-lmd-sound aria-pressed="false" title="Sound on: click to mute" aria-label="Message sound">
            <svg data-on viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg>
            <svg data-off viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/><line x1="22" y1="9" x2="16" y2="15"/><line x1="16" y1="9" x2="22" y2="15"/></svg>
        </button>
        <button type="button" class="lmd-switch" role="switch" aria-checked="false" data-lmd-dnd aria-label="Do Not Disturb"></button>
        <span><b>Do Not Disturb</b><small>Still receive messages (no sound)</small></span>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="14" height="14" rx="2"/><path d="M8 3h13v13"/></svg>
        </a>
        <button type="button" class="lmd-ic" data-lmd-close aria-label="Close" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <span style="position:relative;">
            <button type="button" class="lmd-ic lmd-ic-box" data-lmd-dots aria-label="More options" title="More options">
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

    {{-- "Sarah is typing…", from the other side's keystrokes (polled). --}}
    <div class="lmd-typing" data-lmd-typing hidden><span class="lmd-dots"><i></i><i></i><i></i></span><span data-lmd-typing-name></span></div>

    <div class="lmd-chips" data-lmd-chips hidden></div>
    <form class="lmd-compose" data-lmd-form>
        <div class="lmd-field">
            <input type="text" data-lmd-input placeholder="Type a message…" maxlength="5000" autocomplete="off" aria-label="Type a message">
            <button type="button" class="lmd-fi" data-lmd-attach aria-label="Add attachment" title="Add attachment">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <button type="button" class="lmd-fi" data-lmd-emoji aria-label="Add emoji" title="Add emoji">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
            </button>
            <div class="lmd-emojis" data-lmd-emojis hidden></div>
        </div>
        <input type="file" data-lmd-file accept="{{ \App\Http\Controllers\MessageAttachmentController::ACCEPT_ATTRIBUTE }}" multiple hidden>
        <button type="submit" class="lmd-send" aria-label="Send">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.4 20.4 22 12 2.4 3.6 2.4 10.2 16 12 2.4 13.8z"/></svg>
        </button>
    </form>
    <div class="lmd-cf">
        <button type="button" data-lmd-mute><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M13.7 21a2 2 0 0 1-3.4 0"/><path d="M18.6 13A17.9 17.9 0 0 1 18 8"/><path d="M6.3 6.3A5.8 5.8 0 0 0 6 8c0 7-3 9-3 9h14"/><path d="M18 8a6 6 0 0 0-9.3-5"/><line x1="2" y1="2" x2="22" y2="22"/></svg><span>Mute</span></button>
        <a data-lmd-profile href="{{ $__lmdFull }}" target="_blank" rel="noopener" hidden><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0z"/></svg>View Profile</a>
        <a class="is-link" data-lmd-page href="{{ $__lmdFull }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>Open in Messages</a>
        <button type="button" class="lmd-cf-more" data-lmd-dots aria-label="More options">•••</button>
    </div>
</div>

<button type="button" class="lmd-bubble" data-lmd-list aria-label="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.9 9.9 0 0 1-4.2-.9L3 20l1.3-3.8A8.2 8.2 0 0 1 3 11.5a8.4 8.4 0 0 1 9-8.4 8.4 8.4 0 0 1 9 8.4z"/></svg>
    Messages <span class="lmd-count" data-lmd-total hidden>0</span>
</button>
@endif
@endauth
