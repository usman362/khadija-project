@props([
    'name' => 'event_type',
    'selected' => null,
    'accent' => '#f97316',
])

@php
    $types    = config('event-types.types', []);
    $popular  = config('event-types.popular', []);
    $selected = old($name, $selected);
    $grouped  = collect($types)->sort()->groupBy(fn ($t) => strtoupper(mb_substr($t, 0, 1)));
    $letters  = $grouped->keys()->all();
@endphp

@once
@push('styles')
<style>
    .etp { --etp: {{ $accent }}; border: 1px solid var(--border-color); border-radius: 14px; padding: 14px; background: var(--bg-card); }
    .etp-search { position: relative; margin-bottom: 12px; }
    .etp-search > svg { position: absolute; left: 12px; top: 12px; width: 16px; height: 16px; color: var(--text-muted); }
    .etp-search input { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px 10px 36px; font-size: 13.5px; font-family: inherit; color: var(--text-primary); background: var(--bg-card); }
    .etp-search input:focus { outline: none; border-color: var(--etp); }
    .etp-sel { margin-top: 7px; font-size: 12px; color: var(--text-muted); }
    .etp-sel b { color: var(--etp); font-weight: 800; }

    .etp-lbl { font-size: 10.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: var(--text-muted); display: block; margin: 4px 0 7px; }
    .etp-popular { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
    .etp-pill { border: 1.5px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 6px 13px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); cursor: pointer; font-family: inherit; }
    .etp-pill:hover { border-color: var(--etp); }
    .etp-pill.sel { background: rgba(249,115,22,.1); border-color: var(--etp); color: var(--etp); }

    .etp-az { display: flex; flex-wrap: wrap; gap: 3px; margin-bottom: 10px; }
    .etp-letter { width: 24px; height: 24px; border: none; background: none; border-radius: 6px; font-size: 11.5px; font-weight: 800; color: var(--text-secondary); cursor: pointer; font-family: inherit; }
    .etp-letter:hover { background: rgba(249,115,22,.1); color: var(--etp); }
    .etp-letter.off { color: var(--border-color); cursor: default; }

    .etp-list { max-height: 240px; overflow-y: auto; contain: paint; padding-right: 4px; }
    .etp-group-h { font-size: 11px; font-weight: 800; color: var(--text-muted); padding: 8px 4px 4px; position: sticky; top: 0; background: var(--bg-card); }
    .etp-items { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 6px; }
    .etp-item { text-align: left; border: 1.5px solid var(--border-color); background: var(--bg-card); border-radius: 9px; padding: 8px 11px; font-size: 12.5px; font-weight: 600; color: var(--text-secondary); cursor: pointer; font-family: inherit; }
    .etp-item:hover { border-color: var(--etp); }
    .etp-item.sel { background: rgba(249,115,22,.1); border-color: var(--etp); color: var(--etp); font-weight: 700; }
    .etp-item.hide, .etp-group.hide { display: none; }
    .etp-none { font-size: 12.5px; color: var(--text-muted); padding: 14px 4px; display: none; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-etp]').forEach(function (root) {
        var hidden = root.querySelector('[data-etp-value]');
        var search = root.querySelector('[data-etp-search]');
        var selEl  = root.querySelector('[data-etp-selected]');
        var list   = root.querySelector('[data-etp-list]');
        var none   = root.querySelector('[data-etp-none]');

        function pick(val) {
            hidden.value = val;
            if (selEl) selEl.textContent = val || 'None selected';
            root.querySelectorAll('[data-etp-pick]').forEach(function (b) {
                b.classList.toggle('sel', b.getAttribute('data-etp-pick') === val);
            });
        }
        root.querySelectorAll('[data-etp-pick]').forEach(function (b) {
            b.addEventListener('click', function () { pick(b.getAttribute('data-etp-pick')); });
        });

        if (search) search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase(), shown = 0;
            list.querySelectorAll('.etp-group').forEach(function (grp) {
                var any = 0;
                grp.querySelectorAll('.etp-item').forEach(function (it) {
                    var m = !q || (it.getAttribute('data-name') || '').indexOf(q) !== -1;
                    it.classList.toggle('hide', !m); if (m) { any++; shown++; }
                });
                grp.classList.toggle('hide', any === 0);
            });
            if (none) none.style.display = shown ? 'none' : 'block';
        });

        root.querySelectorAll('[data-etp-letter]:not(.off)').forEach(function (b) {
            b.addEventListener('click', function () {
                var g = list.querySelector('[data-etp-grp="' + b.getAttribute('data-etp-letter') + '"]');
                if (g) list.scrollTop = g.offsetTop - list.offsetTop;
            });
        });
    });
})();
</script>
@endpush
@endonce

<div class="etp" data-etp>
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}" data-etp-value>

    <div class="etp-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search event types…" data-etp-search autocomplete="off">
        <div class="etp-sel">Selected: <b data-etp-selected>{{ $selected ?: 'None selected' }}</b></div>
    </div>

    <span class="etp-lbl">Popular</span>
    <div class="etp-popular">
        @foreach($popular as $p)
            <button type="button" class="etp-pill {{ $selected === $p ? 'sel' : '' }}" data-etp-pick="{{ $p }}">{{ $p }}</button>
        @endforeach
    </div>

    <span class="etp-lbl">Browse A–Z</span>
    <div class="etp-az">
        @foreach(range('A', 'Z') as $L)
            <button type="button" class="etp-letter {{ in_array($L, $letters) ? '' : 'off' }}" {{ in_array($L, $letters) ? '' : 'disabled' }} data-etp-letter="{{ $L }}">{{ $L }}</button>
        @endforeach
    </div>

    <div class="etp-list" data-etp-list>
        @foreach($grouped as $L => $items)
            <div class="etp-group" data-etp-grp="{{ $L }}">
                <div class="etp-group-h">{{ $L }}</div>
                <div class="etp-items">
                    @foreach($items as $t)
                        <button type="button" class="etp-item {{ $selected === $t ? 'sel' : '' }}" data-etp-pick="{{ $t }}" data-name="{{ \Illuminate\Support\Str::lower($t) }}">{{ $t }}</button>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="etp-none" data-etp-none>No event types match your search.</div>
    </div>
</div>
