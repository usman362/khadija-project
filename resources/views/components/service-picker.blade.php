@props([
    'categories' => collect(),
    'name' => 'services',
    'selected' => [],
    'accent' => '#f97316',
    'accentStrong' => '#ea580c',
    'valueField' => 'id', // 'id' (Direct Request) or 'name' (MSR store matches on name)
    // Single-service mode: picking one service clears the previous pick, so a
    // single-service request (SSR, or an ER scoped to one service) can't be
    // submitted with two. Flip it live by setting data-svc-single on the root.
    'single' => false,
    // The optional extra choice under a service ("Buffet Catering → Breakfast").
    // Off where the picker is not filling in a request — a professional listing
    // what they do is answering a different question.
    'details' => false,
    'detailName' => 'service_details',
    'detailSelected' => [],
    // How many professionals in the client's state offer each service, keyed by
    // id. Shown on the row so a service nobody covers is known before the rest
    // of the form is filled in, not five steps later.
    'proCounts' => [],
    // "Can't find it?" free text, saved with the request.
    'missing' => false,
    'missingName' => 'service_missing',
    'missingValue' => null,
    // The page carries its own event type chooser, so the whole coverage map
    // ships and the order follows the client's pick without a reload.
    'liveEventType' => false,
    'eventType' => null,
])

@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    // Legacy category tree carries many duplicate names — dedupe so the browse
    // grid reads clean (Peter's "Services You Need" mockup) instead of a wall.
    $svcList = collect($categories)->unique('name')->sortBy('name')->values();
    $selectedVals = collect($selected)->map(fn ($v) => (string) $v)->all();
    $valOf = fn ($cat) => (string) ($valueField === 'name' ? $cat->name : $cat->id);

    /*
     * Level 2 is the shape of the list, not a label on it.
     *
     * Khadijah's taxonomy is event type → category → service → optional detail,
     * and the categories are what make 241 services readable. The client is
     * never shown a level number: they see "Catering & Food Services" with its
     * services under it.
     */
    /*
     * The callers each select their own columns — some fetch id and name only.
     * Rather than make five controllers remember what this component needs,
     * the two extra fields are looked up here for the ids on the page.
     */
    $meta = Category::query()->whereIn('id', $svcList->pluck('id'))
        ->get(['id', 'parent_id', 'search_terms'])->keyBy('id');

    $parentOf = fn ($cat) => $meta[$cat->id]->parent_id ?? $cat->parent_id;
    $termsOf = fn ($cat) => $meta[$cat->id]?->searchTermList() ?? [];

    $groupNames = Category::query()
        ->whereIn('id', $meta->pluck('parent_id')->filter()->unique())
        ->pluck('name', 'id');

    $grouped = $svcList->groupBy($parentOf)
        ->sortBy(fn ($items, $parentId) => $groupNames[$parentId] ?? 'zz');

    // Level 4, loaded once for the whole grid rather than per service.
    $specialties = $details
        ? Category::query()
            ->where('kind', Category::SERVICE_SPECIALTY)
            ->whereIn('parent_id', $svcList->pluck('id'))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'parent_id'])
            ->groupBy('parent_id')
        : collect();

    $detailFor = fn ($cat) => (string) ($detailSelected[$cat->id] ?? '');

    /*
     * Which services are offered for which event type (Khadijah's level 1 to
     * level 3 coverage). Used to ORDER, never to hide: see the note on
     * applyRelevance for why the picker stopped hiding things.
     */
    $coverage = [];

    if ($liveEventType) {
        foreach (Category::query()->eventTypes()->with('services:id')->get() as $type) {
            $ids = $type->services->pluck('id')->all();

            if ($ids !== []) {
                $coverage[Str::lower($type->name)] = $ids;
            }
        }
    } elseif ($eventType) {
        $type = Category::query()->eventTypes()->where('name', $eventType)->first();

        if ($type) {
            $coverage[Str::lower($type->name)] = $type->services()->pluck('categories.id')->all();
        }
    }

    /*
     * Event type → what matters for it, from Peter's Category Masterlist.
     *
     * This replaces config/event-service-map.php, a hand-written keyword guess
     * that named 48 event types of which only 22 exist — so on 84 of the 106
     * live event types it did nothing, while the approved 139-row matrix
     * covering all 106 was never read. See App\Domain\Taxonomy\ServiceRelevance
     * for why this ORDERS rather than hides.
     */
    $relevance = \App\Domain\Taxonomy\ServiceRelevance::forBrowser();
@endphp

@once
@push('styles')
<style>
    .svc-picker { --svc: {{ $accent }}; --svc-strong: {{ $accentStrong }}; }
    .svc-search { position: relative; display: flex; gap: 9px; margin-bottom: 14px; }
    .svc-search .svc-ico { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none; }
    .svc-search input { flex: 1; border: 1.5px solid var(--border-color); border-radius: 11px; padding: 11px 13px 11px 38px; font-size: 13.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .svc-search input:focus { outline: none; border-color: var(--svc); }
    .svc-search button { flex-shrink: 0; border: none; border-radius: 11px; padding: 0 20px; font-size: 13.5px; font-weight: 800; color: #fff; background: var(--svc); cursor: pointer; }
    .svc-search button:hover { background: var(--svc-strong); }

    .svc-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 9px; }
    .svc-head .lbl { font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .svc-head .cnt { font-size: 12px; font-weight: 700; color: var(--svc-strong); }
    .svc-clear { border: none; background: none; color: var(--svc-strong); font-size: 12px; font-weight: 700; cursor: pointer; padding: 0; display: inline-flex; align-items: center; gap: 5px; }
    .svc-clear svg { width: 13px; height: 13px; }

    .svc-selected { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; min-height: 22px; }
    .svc-selected:empty::before { content: attr(data-empty); font-size: 12px; color: var(--text-muted); font-style: italic; }
    .svc-tag { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--svc); background: rgba(249,115,22,.09); color: var(--svc-strong); border-radius: 999px; padding: 5px 10px 5px 11px; font-size: 12.5px; font-weight: 700; }
    .svc-tag .tick { color: var(--svc); font-weight: 800; }
    .svc-tag button { border: none; background: none; color: var(--svc-strong); cursor: pointer; font-size: 15px; line-height: 1; padding: 0; opacity: .7; }
    .svc-tag button:hover { opacity: 1; }

    /* contain:paint stops the inner scroll area's tall content from leaking
       into the page's scroll height (Chrome grid quirk → phantom empty space
       below the page). */
    .svc-grid { max-height: 360px; overflow-y: auto; contain: paint; padding: 6px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-subtle, rgba(0,0,0,.015)); }

    .svc-group + .svc-group { margin-top: 8px; }
    .svc-group-head { width: 100%; display: flex; align-items: center; gap: 9px; border: none; background: none; padding: 9px 8px; font-family: inherit; font-size: 13px; font-weight: 800; color: var(--text-primary); cursor: pointer; text-align: left; border-radius: 9px; }
    .svc-group-head:hover { background: var(--bg-card); }
    .svc-group-head .chev { width: 14px; height: 14px; flex-shrink: 0; transition: transform .15s; color: var(--text-muted); }
    .svc-group.open .svc-group-head .chev { transform: rotate(90deg); }
    .svc-group-head .n { margin-left: auto; font-size: 11px; font-weight: 700; color: var(--text-muted); }
    .svc-group-head .picked { font-size: 11px; font-weight: 800; color: var(--svc-strong); }
    .svc-group-head[data-tier]::after { content: attr(data-tier); font-size: 10px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; padding: 2px 7px; border-radius: 999px; order: 3; }
    .svc-group-head[data-tier="Essential"]::after  { background: #dcfce7; color: #15803d; }
    .svc-group-head[data-tier="Common"]::after     { background: #e0e7ff; color: #3730a3; }
    .svc-group-head[data-tier="Occasional"]::after { background: var(--bg-muted, #f3f4f6); color: var(--text-muted, #6b7280); }
    .svc-group-body { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; padding: 2px 4px 8px; }
    .svc-group:not(.open) .svc-group-body { display: none; }
    .svc-group.hide { display: none; }

    .svc-item { display: flex; align-items: center; gap: 9px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 12.5px; font-weight: 600; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; }
    .svc-item:hover { border-color: var(--svc); }
    .svc-item input { position: absolute; opacity: 0; pointer-events: none; }
    .svc-box { flex-shrink: 0; width: 17px; height: 17px; border: 1.5px solid var(--border-color); border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; transition: all .12s; }
    .svc-box svg { width: 11px; height: 11px; color: #fff; opacity: 0; }
    .svc-item.sel { border-color: var(--svc); background: rgba(249,115,22,.08); color: var(--svc-strong); font-weight: 700; }
    .svc-item.sel .svc-box { background: var(--svc); border-color: var(--svc); }
    .svc-item.sel .svc-box svg { opacity: 1; }
    .svc-cell.hide { display: none; }
    .svc-pros { margin-left: auto; flex-shrink: 0; min-width: 22px; text-align: center; font-size: 10.5px; font-weight: 800; border-radius: 999px; padding: 2px 6px; background: #dcfce7; color: #15803d; }
    .svc-pros.is-none { background: var(--bg-muted, #f3f4f6); color: var(--text-muted, #6b7280); }
    .svc-none { font-size: 12.5px; color: var(--text-muted); padding: 16px 4px; text-align: center; display: none; }

    /* Level 4. Appears only once its service is picked, because a detail with
       nothing to attach to is a question about nothing. */
    .svc-detail { margin-top: 6px; }
    .svc-detail[hidden] { display: none; }
    .svc-detail select { width: 100%; border: 1.5px solid var(--border-color); border-radius: 9px; padding: 8px 10px; font-size: 12px; font-family: inherit; color: var(--text-secondary); background: var(--bg-card); }
    .svc-detail select:focus { outline: none; border-color: var(--svc); }

    .svc-missing { margin-top: 12px; }
    .svc-missing > button { border: none; background: none; padding: 0; font-family: inherit; font-size: 12.5px; font-weight: 700; color: var(--svc-strong); cursor: pointer; text-decoration: underline; }
    .svc-missing-box { margin-top: 8px; }
    .svc-missing-box[hidden] { display: none; }
    .svc-missing-box input { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13px; font-family: inherit; color: var(--text-primary); background: var(--bg-card); }
    .svc-missing-box input:focus { outline: none; border-color: var(--svc); }
    .svc-missing-box .hint { margin: 6px 2px 0; font-size: 11.5px; color: var(--text-muted); }

    .svc-cascade { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--svc-strong, #ea580c); background: rgba(249,115,22,.08); border: 1px solid var(--svc, #f97316); border-radius: 9px; padding: 7px 11px; margin-bottom: 9px; }
    .svc-cascade[hidden] { display: none; }
    .svc-cascade svg { width: 14px; height: 14px; flex-shrink: 0; }
    .svc-cascade b { font-weight: 800; }
    .svc-cascade button { margin-left: auto; border: none; background: none; color: var(--svc-strong, #ea580c); font-weight: 800; font-size: 12px; cursor: pointer; text-decoration: underline; padding: 0; font-family: inherit; }

    @media (max-width: 900px) { .svc-group-body { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .svc-group-body { grid-template-columns: 1fr; } .svc-search button { padding: 0 14px; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var RELEVANCE = @json($relevance);

    function initPicker(root) {
        var grid     = root.querySelector('.svc-grid');
        var selBox   = root.querySelector('.svc-selected');
        var searchEl = root.querySelector('.svc-search input');
        var none     = root.querySelector('.svc-none');
        var counts   = root.querySelectorAll('[data-svc-count]');
        var coverage = JSON.parse(root.getAttribute('data-svc-coverage') || '{}');
        var groups   = Array.prototype.slice.call(grid.querySelectorAll('.svc-group'));
        var cells    = Array.prototype.slice.call(grid.querySelectorAll('.svc-cell'));

        function itemsIn(group) { return Array.prototype.slice.call(group.querySelectorAll('.svc-item')); }
        function labelOf(item) { return item.querySelector('.svc-text').textContent; }
        function cellOf(item) { return item.closest('.svc-cell'); }

        function refresh() {
            var sel = cells.map(function (c) { return c.querySelector('.svc-item'); })
                           .filter(function (i) { return i.querySelector('input').checked; });

            counts.forEach(function (c) {
                c.textContent = c.hasAttribute('data-svc-suffix') ? (sel.length + ' selected') : sel.length;
            });

            // The detail question belongs to a picked service only.
            cells.forEach(function (cell) {
                var on = cell.querySelector('.svc-item input').checked;
                var detail = cell.querySelector('.svc-detail');
                if (!detail) return;
                detail.hidden = !on;
                if (!on) detail.querySelector('select').value = '';
            });

            groups.forEach(function (group) {
                var picked = itemsIn(group).filter(function (i) { return i.querySelector('input').checked; }).length;
                var badge = group.querySelector('.picked');
                if (badge) badge.textContent = picked ? picked + ' picked' : '';
            });

            selBox.innerHTML = '';
            sel.forEach(function (item) {
                var tag = document.createElement('span');
                tag.className = 'svc-tag';
                tag.innerHTML = '<span class="tick">✓</span><span></span><button type="button" aria-label="Remove">×</button>';
                tag.querySelector('span:nth-child(2)').textContent = labelOf(item);
                tag.querySelector('button').addEventListener('click', function () {
                    item.querySelector('input').checked = false;
                    item.classList.remove('sel');
                    refresh();
                });
                selBox.appendChild(tag);
            });
        }

        function openGroup(group, on) { group.classList.toggle('open', on); }

        groups.forEach(function (group) {
            group.querySelector('.svc-group-head').addEventListener('click', function () {
                openGroup(group, !group.classList.contains('open'));
            });
        });

        // The <label> toggles its checkbox natively on click; listen to the
        // resulting change so we never double-toggle it back.
        cells.forEach(function (cell) {
            var item = cell.querySelector('.svc-item');
            var cb = item.querySelector('input');
            cb.addEventListener('change', function () {
                // Read the attribute per-event, not at init — the ER page flips
                // single mode when the client switches scope.
                if (cb.checked && root.getAttribute('data-svc-single') === '1') {
                    cells.forEach(function (other) {
                        if (other === cell) return;
                        var ocb = other.querySelector('.svc-item input');
                        if (ocb.checked) { ocb.checked = false; other.querySelector('.svc-item').classList.remove('sel'); }
                    });
                }
                item.classList.toggle('sel', cb.checked);
                refresh();
            });
        });

        // Switching into single mode with several already picked: keep the first.
        root.addEventListener('svc:single', function (e) {
            var on = !!(e.detail && e.detail.on);
            root.setAttribute('data-svc-single', on ? '1' : '0');
            if (!on) return;
            var kept = false;
            cells.forEach(function (cell) {
                var cb = cell.querySelector('.svc-item input');
                if (!cb.checked) return;
                if (kept) { cb.checked = false; cell.querySelector('.svc-item').classList.remove('sel'); }
                kept = true;
            });
            refresh();
        });

        if (searchEl) {
            searchEl.addEventListener('input', function () {
                var q = searchEl.value.trim().toLowerCase();
                var shown = 0;

                cells.forEach(function (cell) {
                    // Names alone are not how clients ask. "Cover band for hire"
                    // has to reach Live Bands, so the sheet's search terms are
                    // matched beside the name.
                    var hay = (cell.getAttribute('data-name') || '') + ' ' + (cell.getAttribute('data-terms') || '');
                    var match = !q || hay.indexOf(q) !== -1;
                    cell.classList.toggle('hide', !match);
                    if (match) shown++;
                });

                groups.forEach(function (group) {
                    var visible = itemsIn(group).filter(function (i) { return !cellOf(i).classList.contains('hide'); }).length;
                    group.classList.toggle('hide', visible === 0);
                    // A search is a request to see the results, not to go
                    // hunting for them behind closed headings.
                    if (q) openGroup(group, visible > 0);
                });

                if (!q) restoreOpen();
                if (none) none.style.display = shown ? 'none' : 'block';
            });
            searchEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        }

        var searchBtn = root.querySelector('.svc-search button');
        if (searchBtn) searchBtn.addEventListener('click', function () { if (searchEl) searchEl.focus(); });

        var clear = root.querySelector('.svc-clear');
        if (clear) clear.addEventListener('click', function () {
            cells.forEach(function (cell) {
                var item = cell.querySelector('.svc-item');
                item.classList.remove('sel');
                item.querySelector('input').checked = false;
            });
            refresh();
        });

        var missingToggle = root.querySelector('[data-svc-missing-toggle]');
        if (missingToggle) missingToggle.addEventListener('click', function () {
            var box = root.querySelector('[data-svc-missing-box]');
            box.hidden = !box.hidden;
            if (!box.hidden) box.querySelector('input').focus();
        });

        /*
         * Event type → what matters for it, from the Category Masterlist, plus
         * the sheet's own coverage of which services an event uses.
         *
         * Everything stays on the page. A tier is a ranking, not a permission:
         * the old keyword version HID every service it did not recognize, which
         * both lost the Essential/Common/Occasional distinction the file exists
         * to make and — because it recognized only 22 of 106 event types — did
         * nothing at all most of the time. Ordering keeps the client's choice
         * intact and still puts the right things first.
         *
         * The original DOM order is remembered so clearing the event type puts
         * the grid back exactly as it was rather than leaving it shuffled.
         */
        var cascadeBox  = root.querySelector('[data-svc-cascade]');
        var cascadeType = root.querySelector('[data-svc-cascade-type]');
        var cascadeAll  = root.querySelector('[data-svc-cascade-all]');
        var naturalGroups = groups.slice();
        var naturalCells = {};
        naturalGroups.forEach(function (g) { naturalCells[g.getAttribute('data-group')] = itemsIn(g).map(cellOf); });

        function restoreOpen() {
            groups.forEach(function (group, i) {
                var picked = itemsIn(group).some(function (item) { return item.querySelector('input').checked; });
                openGroup(group, picked || i === 0);
            });
        }

        function applyRelevance(type) {
            var key   = type ? String(type).toLowerCase() : null;
            var arche = key ? RELEVANCE.archetypeOf[key] : null;
            var tiers = arche ? RELEVANCE.tiers[arche] : null;
            var fits  = (key && coverage[key]) ? coverage[key] : null;

            naturalGroups.forEach(function (group) {
                group.querySelector('.svc-group-head').removeAttribute('data-tier');
            });

            // Covered services first inside each heading, the sheet's own answer
            // to what this event usually needs. Nothing is removed.
            naturalGroups.forEach(function (group) {
                var body = group.querySelector('.svc-group-body');
                var order = naturalCells[group.getAttribute('data-group')].slice();

                if (fits) {
                    order.sort(function (a, b) {
                        var fa = fits.indexOf(+a.getAttribute('data-id')) !== -1 ? 0 : 1;
                        var fb = fits.indexOf(+b.getAttribute('data-id')) !== -1 ? 0 : 1;
                        return fa - fb;
                    });
                }

                order.forEach(function (cell) { body.appendChild(cell); });
            });

            if (!tiers) {
                naturalGroups.forEach(function (g) { grid.insertBefore(g, none); });
                if (cascadeBox) cascadeBox.hidden = true;

                return;
            }

            var ranked = naturalGroups.slice().sort(function (a, b) {
                var ra = RELEVANCE.order.indexOf(tiers[a.getAttribute('data-group')] || null);
                var rb = RELEVANCE.order.indexOf(tiers[b.getAttribute('data-group')] || null);
                if (ra === -1) ra = RELEVANCE.order.length;
                if (rb === -1) rb = RELEVANCE.order.length;

                return ra - rb || naturalGroups.indexOf(a) - naturalGroups.indexOf(b);
            });

            ranked.forEach(function (group) {
                var tier = tiers[group.getAttribute('data-group')];
                if (tier) group.querySelector('.svc-group-head').setAttribute('data-tier', tier);
                grid.insertBefore(group, none);
            });

            if (cascadeType) cascadeType.textContent = type;
            if (cascadeBox) cascadeBox.hidden = false;
        }

        if (cascadeAll) cascadeAll.addEventListener('click', function () { applyRelevance(null); });
        document.addEventListener('etp:change', function (e) { applyRelevance(e.detail && e.detail.value); });

        restoreOpen();
        refresh();

        var initial = root.getAttribute('data-svc-event-type');
        if (initial) applyRelevance(initial);
    }

    document.querySelectorAll('[data-svc-picker]').forEach(initPicker);
})();
</script>
@endpush
@endonce

<div class="svc-picker" data-svc-picker
     @if($single) data-svc-single="1" @endif
     @if($eventType) data-svc-event-type="{{ $eventType }}" @endif
     data-svc-coverage="{{ json_encode($coverage) }}">
    <div class="svc-search">
        <svg class="svc-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search services, or type what you need…" autocomplete="off">
        <button type="button">Search</button>
    </div>

    <div class="svc-head">
        <span class="lbl">Selected services (<span data-svc-count>0</span>)</span>
        <button type="button" class="svc-clear">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Clear all
        </button>
    </div>
    <div class="svc-selected" data-empty="Pick services below to add them here."></div>

    <div class="svc-head">
        <span class="lbl">Or browse by category</span>
        <span class="cnt"><span data-svc-count data-svc-suffix>0 selected</span></span>
    </div>
    <div class="svc-cascade" data-svc-cascade hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        {{-- Says ordered, because ordered is what happens. The previous copy
             read "Showing services that fit …" with a "Show all" button beside
             it, which told the client things were being hidden from them. Now
             nothing is. --}}
        Sorted for <b data-svc-cascade-type></b>. Everything is still here, most relevant first.
        <button type="button" data-svc-cascade-all>Sort A–Z</button>
    </div>
    <div class="svc-grid">
        @foreach($grouped as $parentId => $items)
            {{-- data-group is the SERVICE CATEGORY these services sit under.
                 The masterlist ranks categories, not individual services, so
                 that id is what the relevance lookup needs. --}}
            <section class="svc-group" data-group="{{ $parentId }}">
                <button type="button" class="svc-group-head">
                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    <span>{{ $groupNames[$parentId] ?? 'Other services' }}</span>
                    <span class="n">{{ $items->count() }}</span>
                    <span class="picked"></span>
                </button>
                <div class="svc-group-body">
                    @foreach($items as $cat)
                        @php($val = $valOf($cat))
                        @php($options = $specialties[$cat->id] ?? collect())
                        <div class="svc-cell" data-cell data-id="{{ $cat->id }}"
                             data-name="{{ Str::lower($cat->name) }}"
                             data-terms="{{ Str::lower(implode(' ', $termsOf($cat))) }}">
                            <label class="svc-item {{ in_array($val, $selectedVals) ? 'sel' : '' }}">
                                <input type="checkbox" name="{{ $name }}[]" value="{{ $val }}" @checked(in_array($val, $selectedVals))>
                                <span class="svc-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                                <span class="svc-text">{{ $cat->name }}</span>
                                @if(array_key_exists($cat->id, (array) $proCounts))
                                    @php($pros = (int) $proCounts[$cat->id])
                                    <span class="svc-pros {{ $pros ? '' : 'is-none' }}"
                                          title="{{ $pros ? $pros . ' ' . Str::plural('professional', $pros) . ' in your state offer this' : 'Nobody in your state offers this yet. You can still ask' }}">{{ $pros }}</span>
                                @endif
                            </label>
                            @if($details && $options->isNotEmpty())
                                <div class="svc-detail" @if(! in_array($val, $selectedVals)) hidden @endif>
                                    <select name="{{ $detailName }}[{{ $cat->id }}]"
                                            aria-label="{{ $cat->name }}: which type, optional">
                                        <option value="">Any type, no preference</option>
                                        @foreach($options as $option)
                                            <option value="{{ $option->id }}" @selected($detailFor($cat) === (string) $option->id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
        <div class="svc-none">No services match your search.</div>
    </div>

    @if($missing)
        <div class="svc-missing">
            <button type="button" data-svc-missing-toggle>Can't find what you need?</button>
            <div class="svc-missing-box" data-svc-missing-box @if(! $missingValue) hidden @endif>
                <input type="text" name="{{ $missingName }}" maxlength="150" value="{{ $missingValue }}"
                       placeholder="Tell us in your own words, e.g. vintage car for the entrance">
                <p class="hint">We'll pass this on with your request.</p>
            </div>
        </div>
    @endif
</div>
