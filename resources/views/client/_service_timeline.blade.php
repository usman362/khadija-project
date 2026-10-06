{{--
    The per-service timeline.

    Sir Peter, 26 September: "we need to have the timeline added into this as
    a sub-step per service, so that the bidders can see when each service is
    to start/end or a rough idea... just bc an event starts at a certain time
    and date, the timeline might needed as a option so that services dont over
    lap or maybe they will need to."

    And on 6 October: "if the client selects a MSRs when filling out the BR,
    ER, or DR, then each of them should make and have the timeline for each
    services requested."

    So it is drawn here and nowhere else, and the three request forms include
    it. Written out three times it would come to mean three different things,
    which is the fault this project keeps finding.

    Takes $services (the chosen ones, in order), $times (what was entered),
    and optionally $eventStart and $eventEnd so a blank row can say it follows
    the event.

    $live = true for the one-page forms, the Emergency and Direct Requests,
    where services are ticked on the same screen rather than settled on an
    earlier step. There the rows are built as services are ticked, from the
    same markup, and what somebody has already typed into a row survives
    ticking another service.
--}}
@php
    $__svcs    = collect($services ?? []);
    $__times   = $times ?? [];
    $__evStart = $eventStart ?? '';
    $__evEnd   = $eventEnd ?? '';
    $__live    = $live ?? false;
@endphp

@if($__svcs->isNotEmpty() || $__live)
@once
@push('styles')
<style>
    .bw-times { display: flex; flex-direction: column; gap: 10px; }
    .bw-time-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .bw-time-tip { margin: 0; font-size: 11.5px; font-weight: 600; color: var(--accent-text, #1d4ed8);
        background: rgba(29,78,216,.08); border-radius: 9px; padding: 7px 11px; max-width: 320px; line-height: 1.4; }
    .bw-time-row { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 12px 14px; align-items: center;
        border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 14px; }
    .bw-time-note { grid-column: 1 / -1; margin-bottom: 0; }
    .bw-time-note label { font-size: 11px; }
    .bw-time-name { font-size: 13.5px; font-weight: 700; color: var(--text-primary); min-width: 0; }
    .bw-time-fields { display: flex; align-items: flex-end; gap: 10px; }
    .bw-time-fields .bw-field label { font-size: 11px; }
    .bw-time-fields input[type=time] { width: 130px; }
    .bw-time-for { font-size: 11.5px; font-weight: 700; color: var(--text-muted); white-space: nowrap; padding-bottom: 10px; }
    .bw-time-overlap { margin: 12px 0 0; font-size: 12px; color: var(--text-secondary); line-height: 1.45; }
    .bw-time-overlap[hidden] { display: none; }
    @media (max-width: 760px) {
        .bw-time-row { grid-template-columns: 1fr; }
        .bw-time-fields { flex-wrap: wrap; }
    }
</style>
@endpush
@endonce

<div class="bw-sec" data-bw-timeline @if($__live && $__svcs->isEmpty()) hidden @endif>
    <div class="bw-sec-h bw-time-head">
        <div>
            <b>Service schedule / Timeline (per service)</b>
            <span>Set the start and end time for each service. Professionals see this timeline when they bid.</span>
        </div>
        <p class="bw-time-tip">If the exact times are not set yet, give a rough time or write a note.</p>
    </div>

    <div class="bw-times" data-bw-times>
        @foreach($__svcs as $__svc)
            <div class="bw-time-row" data-bw-time-row>
                <div class="bw-time-name">{{ $__svc->name }}</div>
                <div class="bw-time-fields">
                    <div class="bw-field" style="margin-bottom:0;">
                        <label for="st_{{ $__svc->id }}">Starts</label>
                        <input type="time" id="st_{{ $__svc->id }}"
                               name="service_times[{{ $__svc->id }}][start]"
                               value="{{ $__times[$__svc->id]['start'] ?? '' }}"
                               data-bw-start data-event-start="{{ $__evStart }}">
                    </div>
                    <div class="bw-field" style="margin-bottom:0;">
                        <label for="en_{{ $__svc->id }}">Ends</label>
                        <input type="time" id="en_{{ $__svc->id }}"
                               name="service_times[{{ $__svc->id }}][end]"
                               value="{{ $__times[$__svc->id]['end'] ?? '' }}"
                               data-bw-end data-event-end="{{ $__evEnd }}">
                    </div>
                    <span class="bw-time-for" data-bw-span>Follows the event</span>
                </div>
                {{-- The single "Anything they should know about timing?" box
                     came off because, in Sir Peter's words, it "is asked after
                     each service". This is where it is asked, and where a rough
                     time goes when the exact one is not settled. --}}
                <div class="bw-field bw-time-note">
                    <label for="nt_{{ $__svc->id }}">Timing note <span class="bw-optional">Optional</span></label>
                    <input type="text" id="nt_{{ $__svc->id }}"
                           name="service_times[{{ $__svc->id }}][note]" maxlength="150"
                           data-counter="ntCount{{ $__svc->id }}"
                           value="{{ $__times[$__svc->id]['note'] ?? '' }}"
                           placeholder="e.g. setup can start from 3pm">
                    <div class="bw-hint" style="text-align:right;"><span id="ntCount{{ $__svc->id }}">0</span> / 150</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Named, never refused. Two services at once is often the point: the
         photographer shoots while the DJ plays. --}}
    <p class="bw-time-overlap" data-bw-overlap hidden></p>
</div>

@if($__live)
@push('scripts')
<script>
/*
 * The one-page forms pick their services on the same screen, so the rows are
 * built as services are ticked. The markup is the same markup, written once
 * here, because a second copy in JavaScript would drift from the Blade one
 * the moment either changed.
 *
 * What somebody has already typed survives: rows are kept and re-ordered
 * rather than thrown away and rebuilt, so ticking a fourth service does not
 * wipe the times entered for the first three.
 */
(function () {
    var sec = document.querySelector('[data-bw-timeline]');
    var box = sec && sec.querySelector('[data-bw-times]');
    if (! box) return;

    function rowFor(id, name) {
        var row = box.querySelector('[data-bw-time-row][data-svc="' + id + '"]');
        if (row) return row;

        row = document.createElement('div');
        row.className = 'bw-time-row';
        row.setAttribute('data-bw-time-row', '');
        row.setAttribute('data-svc', id);
        row.innerHTML =
            '<div class="bw-time-name"></div>' +
            '<div class="bw-time-fields">' +
                '<div class="bw-field" style="margin-bottom:0;">' +
                    '<label for="st_' + id + '">Starts</label>' +
                    '<input type="time" id="st_' + id + '" name="service_times[' + id + '][start]" data-bw-start>' +
                '</div>' +
                '<div class="bw-field" style="margin-bottom:0;">' +
                    '<label for="en_' + id + '">Ends</label>' +
                    '<input type="time" id="en_' + id + '" name="service_times[' + id + '][end]" data-bw-end>' +
                '</div>' +
                '<span class="bw-time-for" data-bw-span>Follows the event</span>' +
            '</div>' +
            '<div class="bw-field bw-time-note">' +
                '<label for="nt_' + id + '">Timing note <span class="bw-optional">Optional</span></label>' +
                '<input type="text" id="nt_' + id + '" name="service_times[' + id + '][note]" maxlength="150"' +
                       ' data-counter="ntCount' + id + '" placeholder="e.g. setup can start from 3pm">' +
                '<div class="bw-hint" style="text-align:right;"><span id="ntCount' + id + '">0</span> / 150</div>' +
            '</div>';
        row.querySelector('.bw-time-name').textContent = name;
        return row;
    }

    function sync() {
        var picked = Array.prototype.filter.call(
            document.querySelectorAll('.svc-item input:checked'),
            function (i) { return i.value; }
        );

        var keep = {};

        picked.forEach(function (input) {
            var id   = input.value;
            var name = (input.closest('.svc-item').querySelector('.svc-text') || {}).textContent || 'This service';
            keep[id] = true;
            box.appendChild(rowFor(id, name.trim()));
        });

        // A row for a service nobody is asking for any more must not submit
        // times for it.
        Array.prototype.forEach.call(box.querySelectorAll('[data-bw-time-row][data-svc]'), function (row) {
            if (! keep[row.dataset.svc]) row.remove();
        });

        sec.hidden = picked.length === 0;
        document.dispatchEvent(new CustomEvent('bw:timeline-changed'));
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('.svc-item input')) sync();
    });

    sync();
})();
</script>
@endpush
@endif

@once
@push('scripts')
<script>
(function () {
    /*
     * The "0 / 150" beside a note.
     *
     * data-counter was on the old timing box too and nothing ever read it, so
     * the count sat at zero however much was typed. It counts now.
     */
    document.querySelectorAll('[data-counter]').forEach(function (field) {
        var out = document.getElementById(field.dataset.counter);
        if (!out) return;
        var show = function () { out.textContent = String(field.value.length); };
        field.addEventListener('input', show);
        show();
    });

    /*
     * Each service's hours: the duration beside it, and a plain line naming
     * any two that cross. The overlap is named, never refused — two services
     * at once is often the point, and Sir Peter's own note says so.
     */
    function minutes(v) {
        if (!v) return null;
        var p = v.split(':');
        return (Number(p[0]) * 60) + Number(p[1]);
    }

    function span(row) {
        var s = row.querySelector('[data-bw-start]'), e = row.querySelector('[data-bw-end]');
        var a = minutes(s.value) ?? minutes(s.dataset.eventStart);
        var b = minutes(e.value) ?? minutes(e.dataset.eventEnd);
        return (a === null || b === null || b <= a) ? null : { a: a, b: b, own: !!(s.value || e.value) };
    }

    function label(m) {
        var h = Math.floor(m / 60), r = m % 60;
        return ((h ? h + ' hr' + (h > 1 ? 's' : '') : '') + (r ? ' ' + r + ' min' : '')).trim();
    }

    function paint() {
        var rows = Array.prototype.slice.call(document.querySelectorAll('[data-bw-time-row]'));
        var spans = [];

        rows.forEach(function (row) {
            var out = row.querySelector('[data-bw-span]');
            var sp = span(row);
            if (!sp) { out.textContent = 'Follows the event'; spans.push(null); return; }
            out.textContent = label(sp.b - sp.a) + (sp.own ? '' : ' (follows the event)');
            spans.push(sp);
        });

        var names = rows.map(function (r) { return r.querySelector('.bw-time-name').textContent.trim(); });
        var pairs = [];
        for (var i = 0; i < spans.length; i++) {
            for (var j = i + 1; j < spans.length; j++) {
                if (spans[i] && spans[j] && spans[i].a < spans[j].b && spans[j].a < spans[i].b) {
                    pairs.push(names[i] + ' and ' + names[j]);
                }
            }
        }

        var note = document.querySelector('[data-bw-overlap]');
        if (!note) return;
        note.hidden = pairs.length === 0;
        note.textContent = pairs.length
            ? 'Running at the same time: ' + pairs.join(', ') + '. That is fine if you meant it.'
            : '';
    }

    document.querySelectorAll('[data-bw-time-row] input').forEach(function (i) {
        i.addEventListener('input', paint);
    });
    if (document.querySelector('[data-bw-time-row]')) paint();

    // Clicking a nearby day sets the date field rather than making the client
    // read the number here and retype the date somewhere else.
    var date = document.getElementById('av_date');
    document.querySelectorAll('.bw-day').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!date) return;
            date.value = b.dataset.date;
            document.querySelectorAll('.bw-day').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            date.form && date.form.requestSubmit
                ? null   // not submitted for them: they may still want to edit the time
                : null;
        });
    });

    var ta = document.getElementById('av_note'), out = document.getElementById('avCount');
    if (ta && out) {
        var tick = function () { out.textContent = ta.value.length; };
        ta.addEventListener('input', tick);
        tick();
    }
})();
</script>
@endpush
@endonce
@endif
