{{--
    Controls that change this page change it in place.

    Ali, 2026-09-10, on My Events: "button press karne me abhi reload bhi
    horaha hai… saari functionality real time chalni chaiye."

    Every control stays an ordinary link or GET form, so the state is in the
    address — a reload, a bookmark or a shared link shows the same thing, and
    the page still works with scripts off. What this adds: inside
    [data-live-scope], a link or GET form that points at THIS page is fetched
    instead of followed, and every [data-live-region] is swapped for its fresh
    copy. Links to other pages, downloads and POST forms behave as they always
    did.

    Same shape as the dashboard's calendar (2026-09-02): the loading state is
    held back 140ms so a fast answer does not flicker.
--}}
@once
@push('styles')
<style>
    /* :where() has no specificity, so a region the page has already
       positioned keeps it. Without it this rule, loaded last, turned the My
       Events rail from sticky into relative — it stopped following the scroll. */
    :where([data-live-region]) { position: relative; }
    [data-live-region] { transition: opacity .12s ease; }
    [data-live-region].lv-busy { opacity: .5; pointer-events: none; }
    [data-live-region].lv-busy::after {
        content: ''; position: absolute; left: 0; right: 0; top: 0; height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, transparent 0%, var(--brand, #f97316) 35%, var(--brand, #f97316) 65%, transparent 100%);
        background-size: 42% 100%; background-repeat: no-repeat;
        animation: lvSweep .9s linear infinite;
    }
    @keyframes lvSweep { from { background-position: -45% 0; } to { background-position: 145% 0; } }
    @media (prefers-reduced-motion: reduce) {
        [data-live-region].lv-busy::after { animation: none; background: var(--brand, #f97316); }
    }
    .lv-pending { opacity: .7; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var scope = document.querySelector('[data-live-scope]');
    if (!scope || !window.fetch || !window.DOMParser || !window.URLSearchParams) return;

    var BUSY_AFTER_MS = 140;
    var seq = 0, busyTimer = null;

    function regions() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-live-region][id]'));
    }

    function samePage(url) {
        try {
            var u = new URL(url, location.href);
            return u.origin === location.origin && u.pathname === location.pathname;
        } catch (e) { return false; }
    }

    // What goes quiet while waiting: the region the control lives in, or the
    // regions it names — the filter row dims the results, never itself, so
    // the box being typed in stays bright.
    function targetsFor(el) {
        var named = el && el.closest ? el.closest('[data-live-busy]') : null;
        if (named) {
            return named.getAttribute('data-live-busy').split(/\s+/)
                .map(function (id) { return document.getElementById(id); }).filter(Boolean);
        }
        var own = el && el.closest ? el.closest('[data-live-region]') : null;
        return own ? [own] : regions();
    }

    function busyOn(targets) {
        clearTimeout(busyTimer);
        busyTimer = setTimeout(function () {
            targets.forEach(function (el) { el.classList.add('lv-busy'); el.setAttribute('aria-busy', 'true'); });
        }, BUSY_AFTER_MS);
    }

    function busyOff() {
        clearTimeout(busyTimer);
        regions().forEach(function (el) { el.classList.remove('lv-busy'); el.setAttribute('aria-busy', 'false'); });
    }

    function go(url, origin, push) {
        var mine = ++seq;
        busyOn(targetsFor(origin));

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                // A newer click already won; this answer is out of date.
                if (mine !== seq) return;

                // Whoever is typing keeps their place — and what they typed
                // since this request left, which the answer does not know.
                var act = document.activeElement, keep = null;
                if (act && act.name && act.closest && act.closest('[data-live-region]')) {
                    keep = { name: act.name, value: act.value, s: null, e: null };
                    try { keep.s = act.selectionStart; keep.e = act.selectionEnd; } catch (err) {}
                }

                var doc = new DOMParser().parseFromString(html, 'text/html');
                regions().forEach(function (here) {
                    var fresh = doc.getElementById(here.id);
                    if (fresh) here.replaceWith(fresh);
                });

                if (keep) {
                    var f = document.querySelector('[data-live-region] [name="' + keep.name + '"]');
                    if (f) {
                        if ('value' in f && f.type !== 'checkbox' && f.type !== 'radio') f.value = keep.value;
                        f.focus();
                        try { if (keep.s !== null) f.setSelectionRange(keep.s, keep.e); } catch (err) {}
                    }
                }

                if (push !== false) history.pushState({ lv: url }, '', url);
                document.dispatchEvent(new CustomEvent('live:swapped'));
            })
            // Whatever went wrong, the control still works the ordinary way.
            .catch(function () { window.location.href = url; })
            .then(function () { if (mine === seq) busyOff(); });
    }

    history.replaceState({ lv: location.href }, '', location.href);

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[href]') : null;
        if (!a || !scope.contains(a)) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.hasAttribute('download') || (a.target && a.target !== '_self') || a.hasAttribute('data-no-live')) return;
        if (!samePage(a.href)) return;

        var u = new URL(a.href, location.href);
        if (u.search === location.search && u.hash) return;   // a jump within the page

        e.preventDefault();
        a.classList.add('lv-pending');
        go(u.toString(), a);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!scope.contains(form) || (form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
        if (!samePage(form.action)) return;

        e.preventDefault();

        var u = new URL(form.action, location.href), params = new URLSearchParams();
        new FormData(form).forEach(function (v, k) { if (v !== '') params.append(k, v); });
        u.search = params.toString();

        go(u.toString(), form);
    });

    // Search as you type. Looked up again when the wait is over, because the
    // box may have been swapped for a fresh copy in the meantime.
    var typing = null;
    document.addEventListener('input', function (e) {
        var i = e.target;
        if (!i.matches || !i.matches('[data-live-search]') || !scope.contains(i)) return;
        clearTimeout(typing);
        typing = setTimeout(function () {
            var cur = document.querySelector('[data-live-search][name="' + i.name + '"]') || i;
            if (cur.form && cur.form.requestSubmit) cur.form.requestSubmit();
        }, 300);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.lv) go(e.state.lv, null, false);
    });
})();
</script>
@endpush
@endonce
