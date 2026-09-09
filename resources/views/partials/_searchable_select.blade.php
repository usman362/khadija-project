{{--
    Long dropdowns you can type into.

    Event type is 106 occasions, the service lists are longer still, and a
    native dropdown gives you no way to look through them — you scroll, or you
    know the first letter and hope.

    This upgrades the select element that is already on the page rather than
    replacing it. The element stays in the DOM and stays the thing that submits, so every
    form, every `change` listener and every validation message keeps working;
    if this script never runs, the page is exactly what it was before.

    Applied automatically to any single select with enough options to be worth
    searching. Opt out with data-no-search on the select.
--}}
@once
@push('styles')
<style>
    /* The native control stays in the layout — it holds the value and it is
       what a form submits — but the visible control is the button below it. */
    .ss-host { position: relative; }
    .ss-host > select.ss-native { position: absolute; opacity: 0; pointer-events: none;
        width: 100%; height: 100%; left: 0; top: 0; }

    .ss-btn { width: 100%; display: flex; align-items: center; justify-content: space-between;
        gap: 10px; text-align: left; font: inherit; font-size: 13.5px;
        color: var(--text-primary, #111827); background: var(--bg-page, #fff);
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 10px;
        padding: 10px 12px; cursor: pointer; }
    .ss-btn:focus-visible, .ss-host.is-open .ss-btn {
        outline: none; border-color: var(--brand, #f97316);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand, #f97316) 14%, transparent); }
    .ss-btn .ss-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ss-btn.is-placeholder .ss-label { color: var(--text-muted, #6b7280); }
    .ss-btn svg { width: 14px; height: 14px; flex: none; color: var(--text-muted, #6b7280); }

    .ss-panel { position: absolute; z-index: 60; left: 0; right: 0; top: calc(100% + 5px);
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 12px; box-shadow: 0 18px 40px -18px rgba(15,27,53,.45);
        padding: 8px; display: none; }
    .ss-host.is-open .ss-panel { display: block; }
    .ss-host.is-up .ss-panel { top: auto; bottom: calc(100% + 5px); }

    .ss-search { width: 100%; font: inherit; font-size: 13px; padding: 8px 10px;
        border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px;
        background: var(--bg-page, #fff); color: var(--text-primary, #111827); }
    .ss-list { max-height: 260px; overflow-y: auto; margin-top: 7px; }
    .ss-opt { display: block; width: 100%; text-align: left; font: inherit; font-size: 13.5px;
        padding: 8px 10px; border: 0; background: none; border-radius: 8px; cursor: pointer;
        color: var(--text-primary, #111827); }
    .ss-opt:hover, .ss-opt.is-cursor { background: var(--bg-card-hover, #f1f5f9); }
    .ss-opt.is-on { background: rgba(249,115,22,.10); color: var(--brand-text, #c2410c); font-weight: 700; }
    .ss-opt mark { background: rgba(249,115,22,.22); color: inherit; border-radius: 3px; padding: 0 1px; }
    .ss-group { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: var(--text-muted, #6b7280); padding: 9px 10px 4px; }
    .ss-none { font-size: 12.5px; color: var(--text-muted, #6b7280); padding: 12px 10px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Below this a native dropdown is easier than a panel; above it, scrolling
    // for the one you want is the problem this solves.
    var MIN_OPTIONS = 8;

    var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

    /* Matches on any word, so "photo booth" finds "360 Photo Booths" and
       "booth photo" finds it too — the order somebody types words in is not
       the order they appear in a category name. */
    function matches(text, terms) {
        var hay = text.toLowerCase();
        return terms.every(function (t) { return hay.indexOf(t) !== -1; });
    }

    function highlight(text, terms) {
        if (!terms.length) return esc(text);

        var out = esc(text), lower = text.toLowerCase();
        var first = terms[0];
        var at = lower.indexOf(first);
        if (at === -1) return out;

        return esc(text.slice(0, at)) + '<mark>' + esc(text.slice(at, at + first.length)) + '</mark>'
            + esc(text.slice(at + first.length));
    }

    function build(select) {
        var host = document.createElement('div');
        host.className = 'ss-host';
        select.parentNode.insertBefore(host, select);
        host.appendChild(select);
        select.classList.add('ss-native');
        select.setAttribute('tabindex', '-1');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ss-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<span class="ss-label"></span>'
            + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';

        var panel = document.createElement('div');
        panel.className = 'ss-panel';

        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'ss-search';
        search.placeholder = 'Type to search…';
        search.setAttribute('autocomplete', 'off');

        var list = document.createElement('div');
        list.className = 'ss-list';
        list.setAttribute('role', 'listbox');

        panel.appendChild(search);
        panel.appendChild(list);
        host.appendChild(btn);
        host.appendChild(panel);

        var cursor = -1, rows = [];

        function label() {
            var opt = select.options[select.selectedIndex];
            var text = opt ? opt.text.trim() : '';
            btn.querySelector('.ss-label').textContent = text || 'Choose…';
            // A placeholder option has no value; it reads as unanswered.
            btn.classList.toggle('is-placeholder', !(opt && opt.value));
        }

        function render() {
            var terms = search.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
            list.innerHTML = '';
            rows = [];

            var group = null;

            Array.prototype.forEach.call(select.options, function (opt, i) {
                if (opt.disabled && !opt.value) return;   // the "— Choose —" line
                if (terms.length && !matches(opt.text, terms)) return;

                var parent = opt.parentNode;
                if (parent && parent.tagName === 'OPTGROUP' && parent.label !== group) {
                    group = parent.label;
                    var g = document.createElement('div');
                    g.className = 'ss-group';
                    g.textContent = group;
                    list.appendChild(g);
                }

                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'ss-opt' + (i === select.selectedIndex ? ' is-on' : '');
                row.setAttribute('role', 'option');
                row.dataset.index = i;
                row.innerHTML = highlight(opt.text.trim(), terms);
                list.appendChild(row);
                rows.push(row);
            });

            if (!rows.length) {
                var none = document.createElement('div');
                none.className = 'ss-none';
                none.textContent = 'Nothing matches “' + search.value.trim() + '”.';
                list.appendChild(none);
            }

            cursor = -1;
        }

        function moveCursor(step) {
            if (!rows.length) return;
            cursor = (cursor + step + rows.length) % rows.length;
            rows.forEach(function (r, i) { r.classList.toggle('is-cursor', i === cursor); });
            rows[cursor].scrollIntoView({ block: 'nearest' });
        }

        function choose(index) {
            select.selectedIndex = index;
            // The page's own listeners are on the select, so they hear this.
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            label();
            close();
            btn.focus();
        }

        function open() {
            host.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');

            // Upwards when there is more room above than below.
            var box = host.getBoundingClientRect();
            host.classList.toggle('is-up', window.innerHeight - box.bottom < 300 && box.top > 300);

            search.value = '';
            render();
            search.focus();
        }

        function close() {
            host.classList.remove('is-open', 'is-up');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function () {
            host.classList.contains('is-open') ? close() : open();
        });

        search.addEventListener('input', render);

        search.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); moveCursor(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); moveCursor(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                // Enter on a single match takes it, which is what typing the
                // whole name and pressing enter is meant to do.
                var pick = cursor >= 0 ? rows[cursor] : (rows.length === 1 ? rows[0] : null);
                if (pick) choose(Number(pick.dataset.index));
            }
            else if (e.key === 'Escape') { close(); btn.focus(); }
        });

        list.addEventListener('click', function (e) {
            var row = e.target.closest('.ss-opt');
            if (row) choose(Number(row.dataset.index));
        });

        document.addEventListener('click', function (e) {
            if (!host.contains(e.target)) close();
        });

        // Something else on the page may set the value — the "use my address"
        // helpers and the wizards do — so the button follows the select.
        select.addEventListener('change', label);

        label();
    }

    function upgrade(root) {
        (root || document).querySelectorAll('select').forEach(function (select) {
            if (select.multiple || select.disabled) return;
            if (select.dataset.noSearch !== undefined) return;
            if (select.closest('.ss-host')) return;              // already done
            if (select.options.length < MIN_OPTIONS) return;

            build(select);
        });
    }

    upgrade();

    // Panels that arrive later — the request wizards and the dashboard swap
    // whole regions in — are upgraded when they land.
    new MutationObserver(function (records) {
        records.forEach(function (r) {
            Array.prototype.forEach.call(r.addedNodes, function (n) {
                if (n.nodeType === 1) upgrade(n);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
@endpush
@endonce
