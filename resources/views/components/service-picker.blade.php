@props([
    'categories' => collect(),
    'name' => 'services',
    'selected' => [],
    'accent' => '#f97316',
    'accentStrong' => '#ea580c',
    'valueField' => 'id', // 'id' (Direct Offer) or 'name' (MSR store matches on name)
])

@php
    // Legacy category tree carries many duplicate names — dedupe so the browse
    // grid reads clean (Peter's "Services You Need" mockup) instead of a wall.
    $svcList = collect($categories)->unique('name')->sortBy('name')->values();
    $selectedVals = collect($selected)->map(fn ($v) => (string) $v)->all();
    $valOf = fn ($cat) => (string) ($valueField === 'name' ? $cat->name : $cat->id);
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
    .svc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; max-height: 300px; overflow-y: auto; contain: paint; padding: 4px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-subtle, rgba(0,0,0,.015)); }
    .svc-item { display: flex; align-items: center; gap: 9px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 12.5px; font-weight: 600; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; }
    .svc-item:hover { border-color: var(--svc); }
    .svc-item input { position: absolute; opacity: 0; pointer-events: none; }
    .svc-box { flex-shrink: 0; width: 17px; height: 17px; border: 1.5px solid var(--border-color); border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; transition: all .12s; }
    .svc-box svg { width: 11px; height: 11px; color: #fff; opacity: 0; }
    .svc-item.sel { border-color: var(--svc); background: rgba(249,115,22,.08); color: var(--svc-strong); font-weight: 700; }
    .svc-item.sel .svc-box { background: var(--svc); border-color: var(--svc); }
    .svc-item.sel .svc-box svg { opacity: 1; }
    .svc-item.hide { display: none; }
    .svc-none { grid-column: 1 / -1; font-size: 12.5px; color: var(--text-muted); padding: 16px 4px; text-align: center; display: none; }

    @media (max-width: 900px) { .svc-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 680px) { .svc-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 460px) { .svc-grid { grid-template-columns: 1fr; } .svc-search button { padding: 0 14px; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function initPicker(root) {
        var grid     = root.querySelector('.svc-grid');
        var selBox   = root.querySelector('.svc-selected');
        var searchEl = root.querySelector('.svc-search input');
        var none     = root.querySelector('.svc-none');
        var counts   = root.querySelectorAll('[data-svc-count]');

        function labelOf(item) { return item.querySelector('.svc-text').textContent; }

        function refresh() {
            var sel = Array.prototype.filter.call(grid.querySelectorAll('.svc-item'), function (i) { return i.querySelector('input').checked; });
            // counters
            counts.forEach(function (c) {
                c.textContent = c.hasAttribute('data-svc-suffix') ? (sel.length + ' selected') : sel.length;
            });
            // selected keyword tags
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

        // The <label> toggles its checkbox natively on click; listen to the
        // resulting change so we never double-toggle it back.
        grid.querySelectorAll('.svc-item').forEach(function (item) {
            var cb = item.querySelector('input');
            cb.addEventListener('change', function () {
                item.classList.toggle('sel', cb.checked);
                refresh();
            });
        });

        if (searchEl) {
            searchEl.addEventListener('input', function () {
                var q = searchEl.value.trim().toLowerCase();
                var shown = 0;
                grid.querySelectorAll('.svc-item').forEach(function (item) {
                    var match = !q || (item.getAttribute('data-name') || '').indexOf(q) !== -1;
                    item.classList.toggle('hide', !match);
                    if (match) shown++;
                });
                if (none) none.style.display = shown ? 'none' : 'block';
            });
            searchEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        }

        var searchBtn = root.querySelector('.svc-search button');
        if (searchBtn) searchBtn.addEventListener('click', function () { if (searchEl) searchEl.focus(); });

        var clear = root.querySelector('.svc-clear');
        if (clear) clear.addEventListener('click', function () {
            grid.querySelectorAll('.svc-item.sel').forEach(function (item) {
                item.classList.remove('sel'); item.querySelector('input').checked = false;
            });
            refresh();
        });

        refresh();
    }
    document.querySelectorAll('[data-svc-picker]').forEach(initPicker);
})();
</script>
@endpush
@endonce

<div class="svc-picker" data-svc-picker>
    <div class="svc-search">
        <svg class="svc-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search services, or type multiple keywords…" autocomplete="off">
        <button type="button">Search</button>
    </div>

    <div class="svc-head">
        <span class="lbl">Selected keywords (<span data-svc-count>0</span>)</span>
        <button type="button" class="svc-clear">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Clear all
        </button>
    </div>
    <div class="svc-selected" data-empty="Pick services below to add them here."></div>

    <div class="svc-head">
        <span class="lbl">Or browse all services</span>
        <span class="cnt"><span data-svc-count data-svc-suffix>0 selected</span></span>
    </div>
    <div class="svc-grid">
        @foreach($svcList as $cat)
            @php($val = $valOf($cat))
            <label class="svc-item {{ in_array($val, $selectedVals) ? 'sel' : '' }}" data-name="{{ \Illuminate\Support\Str::lower($cat->name) }}">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $val }}" @checked(in_array($val, $selectedVals))>
                <span class="svc-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
                <span class="svc-text">{{ $cat->name }}</span>
            </label>
        @endforeach
        <div class="svc-none">No services match your search.</div>
    </div>
</div>
