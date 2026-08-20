@extends('layouts.landing')

@section('title', 'Event Hierarchy Review — GigResource')
@section('meta_description', 'Walk the event hierarchy: main event, main service, sub-main service and specific component.')

@php
    $tone = [1 => '#1e3a8a', 2 => '#15803d', 3 => '#ea580c', 4 => '#7e22ce'];
@endphp

@push('styles')
<style>
    .eh-wrap { background: var(--bg-soft); padding: 26px 0 60px; }
    .eh-shell { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

    .eh-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 6px; }
    .eh-head h1 { font-size: clamp(1.5rem, 3vw, 2rem); margin: 0; }
    .eh-head p { color: var(--muted); font-size: 14px; margin: 5px 0 0; }
    .eh-reset { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--line); background: #fff; border-radius: 10px; padding: 9px 15px; font-size: 13px; font-weight: 700; color: var(--ink-2); cursor: pointer; font-family: inherit; }
    .eh-reset:hover { border-color: var(--orange); color: var(--orange-dark); }

    .eh-panel { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 20px; margin-top: 18px; }

    .eh-levels { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }
    .eh-level { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; background: #fff; }
    .eh-level.is-off { opacity: .55; }
    .eh-level-h { color: #fff; padding: 11px 14px; text-align: center; }
    .eh-level-h b { display: block; font-size: 10.5px; font-weight: 800; letter-spacing: .5px; opacity: .85; }
    .eh-level-h span { display: block; font-size: 12.5px; font-weight: 800; letter-spacing: .3px; }
    .eh-level-b { padding: 13px 14px 15px; }
    .eh-level-b label { display: block; font-size: 12.5px; font-weight: 700; color: var(--ink-2); margin-bottom: 7px; }
    .eh-level-b select { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 9px 11px; font-size: 13px; font-family: inherit; color: var(--ink); background: #fff; cursor: pointer; }
    .eh-level-b select:disabled { background: var(--bg-soft); color: var(--faint); cursor: not-allowed; }
    .eh-num { width: 22px; height: 22px; border-radius: 50%; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 10px auto 0; }
    .eh-note { font-size: 11px; color: var(--muted); margin-top: 7px; line-height: 1.45; min-height: 15px; }

    /* The three outcomes */
    .eh-msg { display: flex; gap: 11px; align-items: flex-start; border-radius: 13px; padding: 13px 16px; margin-top: 18px; font-size: 13.5px; line-height: 1.55; }
    .eh-msg b { display: block; font-weight: 800; margin-bottom: 2px; }
    .eh-msg span.sub { color: var(--muted); font-size: 12.5px; }
    .eh-msg.is-idle { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    .eh-msg.is-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .eh-msg.is-blocked { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

    .eh-how { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 16px 18px; }
    .eh-how h3 { font-size: 13.5px; color: #1e40af; margin: 0 0 10px; display: flex; align-items: center; gap: 8px; }
    .eh-how ol { margin: 0; padding-left: 18px; }
    .eh-how li { font-size: 12.5px; color: #1e3a8a; line-height: 1.6; }

    .eh-two { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }

    .eh-summary { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 16px 18px; margin-top: 18px; }
    .eh-summary h3 { font-size: 13.5px; color: #1e40af; margin: 0 0 9px; }
    .eh-summary li { font-size: 12.5px; color: #1e3a8a; line-height: 1.6; }
    .eh-source { font-size: 12px; color: var(--muted); margin-top: 14px; line-height: 1.55; border-top: 1px solid var(--line); padding-top: 12px; }
    .eh-source b { color: var(--ink); }

    .eh-path { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .eh-crumb { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 700; color: #fff; }

    @media (max-width: 1000px) { .eh-two { grid-template-columns: 1fr; } .eh-levels { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 560px) { .eh-levels { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="eh-wrap">
    <div class="eh-shell">
        <div class="eh-head">
            <div>
                <h1>Event Hierarchy Review</h1>
                <p>How the system responds to what you pick, level by level.</p>
            </div>
            <button type="button" class="eh-reset" id="ehReset">⟲ Reset All</button>
        </div>

        <div class="eh-two">
            <div>
                <div class="eh-panel">
                    <div class="eh-levels">
                        @foreach($levels as $n => $level)
                            <div class="eh-level {{ $n === 1 ? '' : 'is-off' }}" data-level-card="{{ $n }}">
                                <div class="eh-level-h" style="background: {{ $tone[$n] }};">
                                    <b>LEVEL {{ $n }}</b>
                                    <span>{{ Str::upper($level['label']) }}</span>
                                </div>
                                <div class="eh-level-b">
                                    <label for="ehL{{ $n }}">{{ $level['prompt'] }}</label>
                                    <select id="ehL{{ $n }}" data-level="{{ $n }}" @disabled($n !== 1)>
                                        @if($n === 1)
                                            <option value="">— Choose a Main Event —</option>
                                            @foreach($events as $event)
                                                <option value="{{ $event->id }}">{{ $event->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="">— Select Level {{ $n - 1 }} First —</option>
                                        @endif
                                    </select>
                                    <div class="eh-note" data-note="{{ $n }}"></div>
                                    <div class="eh-num" style="background: {{ $tone[$n] }};">{{ $n }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- The three outcomes from the diagram, in one place: the
                         page is only ever in one of them. --}}
                    <div class="eh-msg is-idle" id="ehMsg">
                        <span>ℹ️</span>
                        <span>
                            <b>Please start by selecting a Main Event (Level 1).</b>
                            <span class="sub">This will unlock the next options.</span>
                        </span>
                    </div>

                    <div class="eh-path" id="ehPath"></div>
                </div>

                <div class="eh-summary">
                    <h3>💡 Summary</h3>
                    <ul>
                        <li>The order is fixed: Level 1 → Level 2 → Level 3 → Level 4.</li>
                        <li>Only data that exists in the source is shown.</li>
                        <li>Out-of-order choices are refused, with a message that says why.</li>
                    </ul>

                    {{-- Said out loud rather than left for somebody to discover.
                         The four-level tree the workflow was drawn from is the
                         old v1 import; the live tree is v2 and stops at three. --}}
                    <p class="eh-source">
                        Reading the <b>{{ Str::upper($version) }}</b> category tree, which goes
                        <b>{{ $depth }} level{{ $depth === 1 ? '' : 's' }}</b> deep.
                        @if($depth < 4)
                            Nothing is listed below Level {{ $depth }} in this tree, so Level 4 stays
                            empty until those components are added to the source.
                        @endif
                    </p>
                </div>
            </div>

            <div class="eh-how">
                <h3>ℹ️ HOW IT WORKS</h3>
                <ol>
                    @foreach($levels as $n => $level)
                        <li>{{ $n === 4 ? 'View' : 'Choose' }} a {{ $level['label'] }} (Level {{ $n }})</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var OPTIONS_URL = @json(route('public.event-hierarchy.options'));
    var LABELS = @json(collect($levels)->map(fn ($l) => $l['label'])->all());
    var TONE = @json($tone);

    var selects = {};
    for (var n = 1; n <= 4; n++) selects[n] = document.getElementById('ehL' + n);

    function card(n) { return document.querySelector('[data-level-card="' + n + '"]'); }
    function note(n) { return document.querySelector('[data-note="' + n + '"]'); }

    function message(kind, title, sub) {
        var box = document.getElementById('ehMsg');
        box.className = 'eh-msg is-' + kind;
        box.innerHTML = '<span>' + (kind === 'ok' ? '✅' : kind === 'blocked' ? '⚠️' : 'ℹ️') + '</span>' +
            '<span><b></b><span class="sub"></span></span>';
        box.querySelector('b').textContent = title;
        box.querySelector('.sub').textContent = sub || '';
    }

    // Shut every level below n, and clear what was in them. Clearing rather
    // than leaving the old value is the point: a Level 3 chosen under the
    // previous Level 2 is not a valid answer to the new one.
    function lockBelow(n) {
        for (var i = n + 1; i <= 4; i++) {
            selects[i].innerHTML = '<option value="">— Select Level ' + (i - 1) + ' First —</option>';
            selects[i].value = '';
            selects[i].disabled = true;
            card(i).classList.add('is-off');
            note(i).textContent = '';
        }
        drawPath();
    }

    function drawPath() {
        var out = [];
        for (var i = 1; i <= 4; i++) {
            var s = selects[i];
            if (s.value) {
                out.push('<span class="eh-crumb" style="background:' + TONE[i] + '">' +
                    s.options[s.selectedIndex].text + '</span>');
            }
        }
        document.getElementById('ehPath').innerHTML = out.join('');
    }

    function load(level, parentId) {
        var target = selects[level];
        target.disabled = true;
        target.innerHTML = '<option value="">Loading…</option>';

        fetch(OPTIONS_URL + '?level=' + level + '&parent=' + encodeURIComponent(parentId))
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                // Outcome B, as the server sees it.
                if (!res.ok || res.d.blocked) {
                    target.innerHTML = '<option value="">— Select Level ' + (level - 1) + ' First —</option>';
                    target.disabled = true;
                    card(level).classList.add('is-off');
                    message('blocked', res.d.message || 'Please select a Main Event (Level 1) first.',
                        (res.d.detail || '') + ' Your selection has been cleared.');
                    return;
                }

                var opts = res.d.options || [];

                if (!opts.length) {
                    target.innerHTML = '<option value="">— Nothing listed —</option>';
                    target.disabled = true;
                    card(level).classList.add('is-off');
                    note(level).textContent = res.d.empty_reason || '';
                    return;
                }

                var html = '<option value="">— Choose a ' + LABELS[level] + ' —</option>';
                opts.forEach(function (o) {
                    html += '<option value="' + o.id + '">' + o.name + (o.tier ? ' · ' + o.tier : '') + '</option>';
                });
                target.innerHTML = html;
                target.disabled = false;
                card(level).classList.remove('is-off');
                note(level).textContent = opts.length + ' option' + (opts.length === 1 ? '' : 's') + ' from the source.';
            })
            .catch(function () {
                target.innerHTML = '<option value="">— Could not load —</option>';
                note(level).textContent = 'Something went wrong loading this level.';
            });
    }

    Object.keys(selects).forEach(function (key) {
        var level = parseInt(key, 10);

        selects[level].addEventListener('change', function () {
            lockBelow(level);

            if (!this.value) {
                if (level === 1) {
                    message('idle', 'Please start by selecting a Main Event (Level 1).',
                        'This will unlock the next options.');
                }
                drawPath();
                return;
            }

            var chosen = this.options[this.selectedIndex].text;

            if (level === 1) {
                message('ok', 'Great! You selected "' + chosen + '".',
                    'Now choose a Main Service (Level 2) to see related options.');
            } else if (level < 4) {
                message('ok', 'Selected "' + chosen + '".',
                    'Now choose a ' + LABELS[level + 1] + ' (Level ' + (level + 1) + ').');
            } else {
                message('ok', 'Selected "' + chosen + '".', 'That is the full path.');
            }

            drawPath();

            if (level < 4) load(level + 1, this.value);
        });
    });

    document.getElementById('ehReset').addEventListener('click', function () {
        selects[1].value = '';
        lockBelow(1);
        message('idle', 'Please start by selecting a Main Event (Level 1).',
            'This will unlock the next options.');
    });
})();
</script>
@endsection
