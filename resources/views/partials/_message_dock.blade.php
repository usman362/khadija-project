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
          flex-direction: column; align-items: flex-end; gap: 12px; font-family: inherit; }

    /*
     * Two floating buttons, one corner.
     *
     * The AI assistant's bubble (partials/_ai_chatbot_widget) sits in the
     * corner: 24px in, 58px round. The Messages button stacks above it,
     * centred on it with a 14px gap, and only where the bubble is actually on
     * the page. MessageDockClearsTheChatbotTest holds these numbers to the
     * bubble's.
     */
    body:has(.aic-bubble) .md { right: 27px; bottom: 96px; }   /* 24 + (58-52)/2 · 24 + 58 + 14 */

    /* The launcher: an icon, the same round shape as the AI bubble beside it
       (Ali, 2026-09-11: the labelled pill from the mockup looked heavy there).
       The window above it is the mockup's. */
    .md-launch { width: 52px; height: 52px; border-radius: 50%; border: 0; cursor: pointer;
        background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; box-shadow: 0 12px 28px -10px rgba(234,88,12,.7);
        display: flex; align-items: center; justify-content: center; position: relative; }
    .md-launch svg { width: 23px; height: 23px; }
    .md-launch .md-dot { position: absolute; top: -3px; right: -3px; min-width: 20px; height: 20px;
        border-radius: 999px; background: #dc2626; color: #fff; font-size: 11px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; padding: 0 6px; border: 2px solid #fff; }

    /*
     * The window opens where the AI assistant's panel opens, the same size,
     * over the corner buttons (Ali, 2026-09-11). Only one of the two is open
     * at a time; see gr:float-open below. MessageDockClearsTheChatbotTest holds
     * the numbers to the panel's.
     */
    .md-win { position: fixed; right: 24px; bottom: 24px; width: 380px; height: 600px;
        max-width: calc(100vw - 48px); max-height: calc(100vh - 48px); z-index: 9999;
        background: var(--bg-card, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 18px; overflow: hidden;
        box-shadow: 0 28px 70px -24px rgba(15,27,53,.55); display: none; flex-direction: column; }
    .md.is-open .md-win { display: flex; }
    /* Open, the window covers the corner, so its button steps aside like the
       assistant's bubble does. Minimised, the window is just its header. */
    .md.is-open .md-launch { visibility: hidden; }
    .md.is-min .md-win { height: auto; }
    /* Minimised keeps the window, and the conversation inside it, exactly
       where it was; only the body is put away. */
    .md.is-min .md-body, .md.is-min .md-compose, .md.is-min .md-tools, .md.is-min .md-foot { display: none; }

    .md-head { display: flex; align-items: flex-start; gap: 6px; padding: 16px 16px 10px; }
    .md-head-t { flex: 1; min-width: 0; }
    .md-head-t b { display: block; font-size: 19px; font-weight: 800; color: var(--text-primary, #111827);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-head-t small { display: block; font-size: 12.5px; color: var(--text-muted, #6b7280); margin-top: 2px; }
    .md-icon { border: 0; background: none; cursor: pointer; color: var(--text-muted, #6b7280);
        width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex: none; }
    .md-icon:hover { background: var(--bg-card-hover, #f1f5f9); color: var(--text-primary, #111827); }
    .md-icon svg { width: 17px; height: 17px; }

    .md-tools { padding: 0 16px; }
    .md-search { display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 12px; padding: 0 12px; height: 42px; background: var(--bg-card, #fff); }
    .md-search svg { width: 17px; height: 17px; color: var(--text-muted, #6b7280); flex: none; }
    .md-search input { border: 0; outline: 0; flex: 1; min-width: 0; font: inherit; font-size: 13.5px; background: transparent; color: var(--text-primary, #111827); }
    .md-tabs { display: flex; margin-top: 10px; border-bottom: 1px solid var(--border-color, #e5e7eb); }
    .md-tab { flex: 1; border: 0; background: none; cursor: pointer; font: inherit; font-size: 13.5px; font-weight: 700;
        color: var(--text-secondary, #374151); padding: 10px 0; border-bottom: 2.5px solid transparent; margin-bottom: -1px;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .md-tab.is-on { color: #ea580c; border-bottom-color: #ea580c; }
    .md-tab i { font-style: normal; min-width: 18px; height: 18px; border-radius: 999px; background: #dc2626; color: #fff;
        font-size: 10.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }

    .md-body { flex: 1; min-height: 0; overflow-y: auto; padding: 6px 8px; }
    .md-row { display: flex; gap: 12px; align-items: flex-start; width: 100%; text-align: left; position: relative;
        border: 0; background: none; cursor: pointer; padding: 12px 10px; border-radius: 12px; font: inherit; }
    .md-row + .md-row { border-top: 1px solid var(--border-color, #f1f5f9); }
    .md-row:hover { background: var(--bg-card-hover, #f8fafc); }
    .md-row.is-unread { background: #fff4ec; }
    .md-avw { position: relative; flex: none; }
    .md-av { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; display: block; background: #f97316; }
    .md-on { position: absolute; right: 0; bottom: 1px; width: 12px; height: 12px; border-radius: 50%; background: #22c55e; border: 2px solid #fff; }
    .md-row-who { min-width: 0; flex: 1; }
    .md-row-top { display: flex; align-items: baseline; gap: 8px; }
    .md-row-top b { flex: 1; min-width: 0; font-size: 14px; color: var(--text-primary, #111827); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-row-top time { font-size: 11.5px; color: var(--text-muted, #6b7280); flex: none; }
    .md-sub, .md-ctx, .md-last { display: block; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .md-sub { color: var(--text-secondary, #4b5563); }
    .md-ctx { color: var(--text-muted, #6b7280); }
    .md-last { color: var(--text-primary, #111827); margin-top: 2px; }
    .md-row-side { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex: none; padding-top: 20px; }
    .md-unread { min-width: 20px; height: 20px; border-radius: 999px; background: #dc2626;
        color: #fff; font-size: 10.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 6px; }
    .md-star { border: 0; background: none; cursor: pointer; color: #cbd5e1; font-size: 16px; line-height: 1; padding: 0; }
    .md-star.is-on { color: #f59e0b; }
    .md-star:hover { color: #f59e0b; }

    .md-foot { padding: 10px 16px 16px; }
    .md-foot a { display: flex; align-items: center; justify-content: center; gap: 6px; height: 44px; border-radius: 12px;
        border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-weight: 800; font-size: 14px; text-decoration: none; }
    .md-foot a:hover { background: #dbeafe; }

    .md-msg { max-width: 82%; margin: 0 8px 8px; padding: 8px 11px; border-radius: 11px;
        font-size: 12.5px; line-height: 1.5; background: var(--bg-card-hover, #f1f5f9);
        color: var(--text-primary, #111827); word-break: break-word; }
    .md-msg.is-mine { margin-left: auto; background: rgba(249,115,22,.12); }
    .md-msg small { display: block; margin-top: 3px; font-size: 10.5px; color: var(--text-muted, #6b7280); }

    .md-compose { display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid var(--border-color, #e5e7eb); }
    .md-compose input { flex: 1; min-width: 0; border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 10px; padding: 9px 12px; font: inherit; font-size: 13px;
        background: var(--bg-page, #fff); color: var(--text-primary, #111827); }
    .md-compose button { border: 0; background: #f97316; color: #fff; border-radius: 10px;
        padding: 0 16px; font-weight: 800; font-size: 13px; cursor: pointer; }
    .md-note { padding: 22px 12px; font-size: 13px; color: var(--text-muted, #6b7280); text-align: center; }

    /* display beats the hidden attribute, so anything here that is both
       flex AND hideable has to say so. */
    .md-icon[hidden], .md-compose[hidden], .md-dot[hidden], .md-tools[hidden], .md-foot[hidden],
    .md-head-t small[hidden], .md-tab i[hidden] { display: none !important; }

    /* A phone: full screen, as the assistant's panel is. */
    @media (max-width: 520px) {
        .md { right: 12px; bottom: 12px; }
        .md-win { right: 0; bottom: 0; left: 0; width: 100%; max-width: none; height: 100%; max-height: 100vh; border-radius: 0; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var dock = document.getElementById('msgDock');
    if (! dock) return;

    var title   = dock.querySelector('[data-md-title]');
    var sub     = dock.querySelector('[data-md-sub]');
    var body    = dock.querySelector('[data-md-body]');
    var tools   = dock.querySelector('[data-md-tools]');
    var foot    = dock.querySelector('[data-md-foot]');
    var compose = dock.querySelector('[data-md-compose]');
    var input   = dock.querySelector('[data-md-input]');
    var back    = dock.querySelector('[data-md-back]');
    var dot     = dock.querySelector('[data-md-dot]');
    var search  = dock.querySelector('[data-md-search]');
    var unreadTab = dock.querySelector('[data-md-unread-count]');

    var urls = {
        list: @json(route('conversations.index')),
        show: @json(route('conversations.show', ['conversation' => '__ID__'])),
        send: @json(route('conversations.messages.store', ['conversation' => '__ID__'])),
        read: @json(route('conversations.mark-read', ['conversation' => '__ID__'])),
        fav:  @json(route('conversations.favorite', ['conversation' => '__ID__'])),
    };

    var me   = @json(auth()->id());
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var open = null;          // the conversation being read, if any
    var tab  = 'recent';      // recent | unread | favorites
    var timer = null;

    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }
    function at(url, id) { return url.replace('__ID__', id); }
    function ago(iso) {
        if (! iso) return '';
        var s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
        if (s < 60) return 'now';
        if (s < 3600) return Math.floor(s / 60) + 'm';
        if (s < 86400) return Math.floor(s / 3600) + 'h';
        if (s < 604800) return Math.floor(s / 86400) + 'd';
        return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function get(url) {
        return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (! r.ok) throw new Error(r.status); return r.json(); });
    }

    function listUrl() {
        var q = [];
        if (tab !== 'recent') q.push('filter=' + tab);
        if (search.value.trim()) q.push('search=' + encodeURIComponent(search.value.trim()));
        return urls.list + (q.length ? '?' + q.join('&') : '');
    }

    function row(c) {
        var p = c.peer || { name: 'Conversation', avatar: '', online: false, subtitle: '' };
        var unread = Number(c.unread_count || 0);
        var ctx = [c.event && c.event.title, c.request_type].filter(Boolean).join(' · ');

        return '<div class="md-row' + (unread ? ' is-unread' : '') + '" role="button" tabindex="0" data-open="' + c.id + '">'
            + '<span class="md-avw"><img class="md-av" src="' + esc(p.avatar) + '" alt="">'
            + (p.online ? '<span class="md-on" title="Online"></span>' : '') + '</span>'
            + '<span class="md-row-who">'
            +   '<span class="md-row-top"><b>' + esc(p.name) + '</b><time>' + esc(ago(c.last_message_at)) + '</time></span>'
            +   (p.subtitle ? '<span class="md-sub">' + esc(p.subtitle) + '</span>' : '')
            +   (ctx ? '<span class="md-ctx">' + esc(ctx) + '</span>' : '')
            +   '<span class="md-last">' + esc(c.last_message_body || 'No messages yet') + '</span>'
            + '</span>'
            + '<span class="md-row-side">'
            +   (unread ? '<span class="md-unread">' + unread + '</span>' : '')
            +   '<button type="button" class="md-star' + (c.favorited_at ? ' is-on' : '') + '" data-fav="' + c.id + '" aria-label="' + (c.favorited_at ? 'Remove from favorites' : 'Add to favorites') + '">' + (c.favorited_at ? '★' : '☆') + '</button>'
            + '</span>'
            + '</div>';
    }

    /* The list. A row reads as the person you are talking to. */
    function showList() {
        open = null;
        title.textContent = 'Messages';
        sub.hidden = false;
        back.hidden = true;
        compose.hidden = true;
        tools.hidden = false;
        foot.hidden = false;
        body.innerHTML = '<div class="md-note">Loading…</div>';

        get(listUrl()).then(function (data) {
            var rows = (data.data || data || []);

            if (tab === 'recent' && ! search.value.trim()) { counts(rows); }

            if (! rows.length) {
                body.innerHTML = '<div class="md-note">' + ({
                    recent: search.value.trim() ? 'No conversations match that.' : 'No conversations yet.',
                    unread: 'You are all caught up.',
                    favorites: 'Star a conversation to keep it here.',
                })[tab] + '</div>';
                return;
            }

            body.innerHTML = rows.map(row).join('');
        }).catch(function () {
            body.innerHTML = '<div class="md-note">Could not load your messages.</div>';
        });
    }

    function showThread(id) {
        open = id;
        sub.hidden = true;
        back.hidden = false;
        compose.hidden = false;
        tools.hidden = true;
        foot.hidden = true;
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
                .then(refreshCounts)
                .catch(function () {});
        }).catch(function () {
            body.innerHTML = '<div class="md-note">Could not open this conversation.</div>';
        });
    }

    /* The unread total on the launcher, and on the Unread tab. Muted
       conversations do not count. */
    function counts(rows) {
        var unreadMsgs = rows.reduce(function (n, c) { return n + (c.muted_at ? 0 : Number(c.unread_count || 0)); }, 0);
        var unreadConvs = rows.filter(function (c) { return ! c.muted_at && Number(c.unread_count || 0) > 0; }).length;
        dot.textContent = unreadMsgs > 99 ? '99+' : unreadMsgs;
        dot.hidden = ! unreadMsgs;
        unreadTab.textContent = unreadConvs;
        unreadTab.hidden = ! unreadConvs;
    }

    function refreshCounts() { get(urls.list).then(function (d) { counts(d.data || d || []); }).catch(function () {}); }

    function setTab(name) {
        tab = name;
        dock.querySelectorAll('[data-md-tab]').forEach(function (b) { b.classList.toggle('is-on', b.dataset.mdTab === name); });
        showList();
    }

    dock.addEventListener('click', function (e) {
        var fav = e.target.closest('[data-fav]');
        if (fav) {
            e.stopPropagation();
            fetch(at(urls.fav, fav.dataset.fav), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    fav.classList.toggle('is-on', d.favorited);
                    fav.textContent = d.favorited ? '★' : '☆';
                    fav.setAttribute('aria-label', d.favorited ? 'Remove from favorites' : 'Add to favorites');
                    if (tab === 'favorites' && ! d.favorited) showList();
                })
                .catch(function () {});
            return;
        }

        var rowEl = e.target.closest('[data-open]');
        if (rowEl) { showThread(rowEl.dataset.open); return; }

        var t = e.target.closest('[data-md-tab]');
        if (t) { setTab(t.dataset.mdTab); return; }

        if (e.target.closest('[data-md-launch]')) {
            dock.classList.toggle('is-open');
            dock.classList.remove('is-min');
            if (dock.classList.contains('is-open')) {
                // One window in the corner at a time: the assistant closes.
                document.dispatchEvent(new CustomEvent('gr:float-open', { detail: 'messages' }));
                if (! open) showList();
            }
            return;
        }

        // Minimise keeps the conversation exactly where it was.
        if (e.target.closest('[data-md-min]')) { dock.classList.toggle('is-min'); return; }

        // Close puts it away. Nothing is ended and nothing is deleted.
        if (e.target.closest('[data-md-close]')) { dock.classList.remove('is-open', 'is-min'); return; }

        if (e.target.closest('[data-md-back]')) { showList(); }
    });

    dock.addEventListener('keydown', function (e) {
        var rowEl = e.target.closest('[data-open]');
        if (rowEl && (e.key === 'Enter' || e.key === ' ') && ! e.target.closest('[data-fav]')) { e.preventDefault(); showThread(rowEl.dataset.open); }
    });

    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(showList, 300);
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

    // The assistant opened: this one steps out of the way.
    document.addEventListener('gr:float-open', function (e) {
        if (e.detail !== 'messages') dock.classList.remove('is-open', 'is-min');
    });

    // The unread count on the launcher, without opening anything.
    refreshCounts();
})();
</script>
@endpush
@endonce

@php
    // "Open as full page" and "View All Messages" go to this person's own
    // Messages page.
    $__mdUser = auth()->user();
    $__mdFull = $__mdUser->isProfessionalMode()
        ? route('professional.chat.index')
        : ($__mdUser->hasRole('client') ? route('client.chat.index') : route('app.chat.index'));
@endphp
<div class="md" id="msgDock">
    <div class="md-win" role="dialog" aria-label="Messages">
        <div class="md-head">
            <button type="button" class="md-icon" data-md-back hidden aria-label="Back to conversations">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div class="md-head-t">
                <b data-md-title>Messages</b>
                <small data-md-sub>Stay connected while you work</small>
            </div>
            <a class="md-icon" href="{{ $__mdFull }}" data-md-full aria-label="Open as full page" title="Open as full page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="M4 9V5a1 1 0 0 1 1-1h4M15 4h4a1 1 0 0 1 1 1v4M20 15v4a1 1 0 0 1-1 1h-4M9 20H5a1 1 0 0 1-1-1v-4"/></svg>
            </a>
            <button type="button" class="md-icon" data-md-min aria-label="Minimise">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <button type="button" class="md-icon" data-md-close aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="md-tools" data-md-tools>
            <label class="md-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.5" y2="16.5"/></svg>
                <input type="search" data-md-search placeholder="Search messages…" aria-label="Search messages" autocomplete="off">
            </label>
            <div class="md-tabs" role="tablist">
                <button type="button" class="md-tab is-on" data-md-tab="recent" role="tab">Recent</button>
                <button type="button" class="md-tab" data-md-tab="unread" role="tab">Unread <i data-md-unread-count hidden>0</i></button>
                <button type="button" class="md-tab" data-md-tab="favorites" role="tab">Favorites</button>
            </div>
        </div>

        <div class="md-body" data-md-body></div>

        <div class="md-foot" data-md-foot>
            <a href="{{ $__mdFull }}">View All Messages
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

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
