{{--
    The client's calendar: a day, a week or a month.

    It is drawn here and nowhere else. My Events shows it in a tab and
    Calendar & Availability shows it as a page of its own, and before this it
    was written out twice, which is how two screens come to disagree about
    what colour a stage wears. App\Support\ClientCalendar lays the dates out;
    this draws them.

    Takes $calendar, and optionally $calRoute and $calFixed: the address the
    controls link back to, since each page drives its own.
--}}
@once
@push('styles')
<style>

    /* Calendar */
    .ec-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .ec-title { display: flex; align-items: center; gap: 8px; }
    .ec-title .cl-calendar-month { margin: 0 0 0 6px; }
    .ec-nav { width: 34px; height: 34px; border-radius: 9px; border: 1px solid var(--border-color); display: inline-flex;
        align-items: center; justify-content: center; font-size: 18px; line-height: 1; color: var(--text-secondary); text-decoration: none; }
    .ec-nav:hover { background: var(--bg-card-hover); color: var(--text-primary); }
    .ec-views { display: inline-flex; padding: 3px; gap: 3px; border: 1px solid var(--border-color); border-radius: 10px; }
    .ec-view { padding: 6px 13px; border-radius: 7px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .ec-view:hover { color: var(--text-primary); }
    .ec-view.is-active { background: #c2410c; color: #fff; }
    .ec-view.lv-pending { background: rgba(249,115,22,.12); color: #c2410c; opacity: 1; }
    a.ec-daylink { text-decoration: none; color: inherit; border-radius: 50%; }
    a.ec-daylink:hover { text-decoration: underline; }
    .ec-muted { opacity: .45; }
    .ec-ev { display: block; margin-top: 3px; padding: 2px 6px; border-radius: 5px; font-size: 11px; font-weight: 700;
        text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ec-more { display: block; margin-top: 3px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-decoration: none; }
    .ec-more:hover { color: #c2410c; }
    /* Seven equal columns. With the table's default layout a column widened
       to fit its longest entry, so the one day with an event pushed the rest
       of the week into slivers. */
    .ec-grid { table-layout: fixed; }
    .ec-grid .cl-calendar-day { min-width: 0; overflow: hidden; }
    /* Day and week — the time grid */
    .tg { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: var(--bg-card); }
    .tg-head { display: grid; border-bottom: 1px solid var(--border-color); background: var(--bg-card); }
    .tg-dayhead { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 8px 0 7px;
        text-decoration: none; color: var(--text-secondary); border-left: 1px solid var(--border-color); }
    .tg-dayhead small { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
    .tg-dayhead b { font-size: 17px; font-weight: 800; width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: var(--text-primary); }
    .tg-dayhead:hover b { background: var(--bg-card-hover); }
    .tg-dayhead.is-today small { color: #c2410c; }
    .tg-dayhead.is-today b { background: #c2410c; color: #fff; }
    .tg-day .tg-dayhead { align-items: flex-start; padding-left: 14px; flex-direction: row; gap: 8px; }
    .tg-body { max-height: 576px; overflow-y: auto; }
    .tg-inner { display: grid; position: relative;
        /* One line per hour, and a fainter one at the half. */
        background-image: linear-gradient(to bottom, var(--border-color) 1px, transparent 1px),
                          linear-gradient(to bottom, color-mix(in srgb, var(--border-color) 45%, transparent) 1px, transparent 1px);
        background-size: 100% 48px, 100% 24px; }
    .tg-gutter { position: relative; background: var(--bg-card); }
    .tg-gutter span { position: absolute; right: 8px; transform: translateY(-50%); font-size: 11px; font-weight: 600;
        color: var(--text-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tg-gutter span:first-child { transform: none; top: 3px !important; }
    .tg-col { position: relative; border-left: 1px solid var(--border-color); }
    .tg-col.is-today { background: rgba(249,115,22,.04); }
    .tg-ev { position: absolute; box-sizing: border-box; border-left: 3px solid; border-radius: 6px; padding: 4px 7px;
        text-decoration: none; overflow: hidden; display: flex; flex-direction: column; gap: 1px; z-index: 1;
        box-shadow: 0 1px 2px rgba(15,23,42,.06); }
    .tg-ev:hover { z-index: 2; box-shadow: 0 4px 12px rgba(15,23,42,.14); }
    .tg-ev b { font-size: 12.5px; font-weight: 800; line-height: 1.25; color: var(--text-primary);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tg-ev small { font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tg-ev.is-short { flex-direction: row; align-items: center; gap: 6px; padding-top: 2px; padding-bottom: 2px; }
    .tg-day .tg-ev b { font-size: 14px; }
    .tg-now { position: absolute; left: 0; right: 0; height: 2px; background: #ef4444; z-index: 3; pointer-events: none; }
    .tg-now::before { content: ''; position: absolute; left: -5px; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: #ef4444; }
    .ec-agenda { display: flex; flex-direction: column; }
    .ec-agenda-row { display: flex; align-items: center; gap: 12px; padding: 13px 6px; border-bottom: 1px solid var(--border-color);
        text-decoration: none; color: inherit; }
    .ec-agenda-row:last-child { border-bottom: 0; }
    .ec-agenda-row:hover { background: var(--bg-card-hover); }
    .ec-agenda-time { flex: none; width: 92px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); font-variant-numeric: tabular-nums; }
    .ec-agenda-time small { display: block; font-weight: 600; color: var(--text-muted); }
    .ec-dot { flex: none; width: 9px; height: 9px; border-radius: 50%; }
    .ec-agenda-body b { display: block; font-size: 14px; color: var(--text-primary); }
    .ec-agenda-body small { font-size: 12px; font-weight: 700; }
    .ec-empty { font-size: 13px; color: var(--text-muted); margin: 14px 2px 0; }
    .ec-empty a { font-weight: 700; color: #c2410c; }
    .ec-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; font-size: 12px; color: var(--text-secondary); }
    .ec-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .ec-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
</style>
@endpush
@endonce

@php
    $c = $calendar;
    $calRoute = $calRoute ?? 'client.events.index';
    $calFixed = $calFixed ?? ['tab' => 'calendar'];
    $calLink  = fn (array $over) => route($calRoute, array_merge(
        request()->except(['page', 'month', 'year']), $calFixed, $over
    ));
    $calStages = \App\Support\ClientCalendar::STAGES;
    $unit = ['day' => 'day', 'week' => 'week', 'month' => 'month'][$c['view']];
    $perDay = $c['view'] === 'week' ? 6 : 3;
@endphp
<div class="cl-card ec-card" id="mgCal" data-live-region>
    <div class="ec-head">
        <div class="ec-title">
            <a class="ec-nav" href="{{ $calLink(['calview' => $c['view'], 'cal' => $c['prev']->format('Y-m-d')]) }}" aria-label="Previous {{ $unit }}">‹</a>
            <a class="ec-nav" href="{{ $calLink(['calview' => $c['view'], 'cal' => $c['next']->format('Y-m-d')]) }}" aria-label="Next {{ $unit }}">›</a>
            <h3 class="cl-calendar-month">{{ $c['title'] }}</h3>
        </div>
        <div class="ec-views" role="group" aria-label="Calendar view">
            <a class="ec-view {{ $c['view'] === 'day' && $c['anchor']->isToday() ? 'is-active' : '' }}"
               href="{{ $calLink(['calview' => 'day', 'cal' => now()->format('Y-m-d')]) }}">Today</a>
            <a class="ec-view {{ $c['view'] === 'week' ? 'is-active' : '' }}"
               href="{{ $calLink(['calview' => 'week', 'cal' => $c['anchor']->format('Y-m-d')]) }}">Week</a>
            <a class="ec-view {{ $c['view'] === 'month' ? 'is-active' : '' }}"
               href="{{ $calLink(['calview' => 'month', 'cal' => $c['anchor']->format('Y-m-d')]) }}">Month</a>
        </div>
    </div>

    @if($c['view'] !== 'month')
        {{-- Day and week: a time grid. Hours down the side; each event
             at its start, as tall as it lasts; overlaps side by side.
             They used to be month-style boxes with the event as a pill
             at the top, and no time anywhere on screen. --}}
        @php $g = \App\Support\ClientCalendar::timeGrid($c); @endphp
        @if($c['view'] === 'day' && empty($g['cols'][0]['items']))
            <p class="ec-empty" style="margin:0 0 12px;">
                Nothing on {{ $c['anchor']->isToday() ? 'today' : $c['anchor']->format('l, M j') }}.
                <a href="{{ $calLink(['calview' => 'week', 'cal' => $c['anchor']->format('Y-m-d')]) }}">See the week</a>
            </p>
        @endif
        <div class="tg tg-{{ $c['view'] }}">
            <div class="tg-head" style="grid-template-columns: 58px repeat({{ count($g['cols']) }}, minmax(0, 1fr));">
                <span></span>
                @foreach($g['cols'] as $col)
                    @php $d = $col['date']; @endphp
                    {{-- The day's name opens that day. --}}
                    <a class="tg-dayhead {{ $d->isToday() ? 'is-today' : '' }}"
                       href="{{ $calLink(['calview' => 'day', 'cal' => $d->format('Y-m-d')]) }}"
                       aria-label="{{ $d->format('l, F j') }}">
                        <small>{{ $d->format('D') }}</small><b>{{ $d->day }}</b>
                    </a>
                @endforeach
            </div>
            <div class="tg-body" data-scroll-to="{{ $g['scrollTo'] }}">
                <div class="tg-inner" style="height: {{ $g['height'] }}px; grid-template-columns: 58px repeat({{ count($g['cols']) }}, minmax(0, 1fr));">
                    <div class="tg-gutter">
                        @for($h = $g['from']; $h < $g['to']; $h++)
                            <span style="top: {{ ($h - $g['from']) * \App\Support\ClientCalendar::HOUR_PX }}px;">{{ \Carbon\Carbon::createFromTime($h % 24)->format('g A') }}</span>
                        @endfor
                    </div>
                    @foreach($g['cols'] as $col)
                        <div class="tg-col {{ $col['date']->isToday() ? 'is-today' : '' }}">
                            @foreach($col['items'] as $it)
                                @php
                                    $ev = $it['event'];
                                    [$stLabel, $stColour] = $calStages[$ev->stage()] ?? ['Event', '#f97316'];
                                    $w = 100 / $col['lanes'];
                                    $tall = ($it['stop'] - $it['start']) * $g['px'];
                                @endphp
                                <a class="tg-ev {{ $tall < 40 ? 'is-short' : '' }}" href="{{ route('client.events.show', $ev) }}" data-no-live
                                   style="top: {{ round($it['start'] * $g['px']) }}px; height: {{ round($tall) }}px;
                                          left: calc({{ $it['lane'] * $w }}% + 2px); width: calc({{ $w }}% - 4px);
                                          background: {{ $stColour }}1f; border-left-color: {{ $stColour }}; color: {{ $stColour }};"
                                   title="{{ $ev->title }} · {{ $ev->starts_at->format('g:i A') }} – {{ $it['ends']->format('g:i A') }} · {{ $stLabel }}">
                                    <b>{{ $ev->title }}</b>
                                    <small>{{ $ev->starts_at->format('g:i A') }} – {{ $it['ends']->format('g:i A') }}@if($c['view'] === 'day') · {{ $stLabel }}@endif</small>
                                </a>
                            @endforeach
                            @if($col['date']->isToday() && $g['nowTop'] !== null)
                                <div class="tg-now" style="top: {{ $g['nowTop'] }}px;" aria-hidden="true"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <table class="cl-calendar ec-grid ec-{{ $c['view'] }}">
            <thead><tr>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)<th>{{ $dow }}</th>@endforeach</tr></thead>
            <tbody>
                @php $cursor = $c['first']->copy(); @endphp
                @while($cursor->lte($c['last']))
                    <tr>
                        @for($i = 0; $i < 7; $i++)
                            @php
                                $key    = $cursor->format('Y-m-d');
                                $dayEvs = $c['byDate']->get($key, collect());
                                $muted  = $c['view'] === 'month' && $cursor->month !== $c['anchor']->month;
                            @endphp
                            <td>
                                <div class="cl-calendar-day {{ $cursor->isToday() ? 'today' : '' }} {{ $muted ? 'ec-muted' : '' }}">
                                    {{-- The number opens that day's list. --}}
                                    <a class="day-num ec-daylink" href="{{ $calLink(['calview' => 'day', 'cal' => $key]) }}"
                                       aria-label="{{ $cursor->format('l, F j') }}{{ $dayEvs->count() ? ': ' . $dayEvs->count() . ' event' . ($dayEvs->count() === 1 ? '' : 's') : '' }}">{{ $cursor->day }}</a>
                                    @foreach($dayEvs->take($perDay) as $ev)
                                        @php [$stLabel, $stColour] = $calStages[$ev->stage()] ?? ['Event', '#f97316']; @endphp
                                        {{-- Coloured by the stage every other screen reports. --}}
                                        <a href="{{ route('client.events.show', $ev) }}" class="cl-calendar-event ec-ev" data-no-live
                                           style="background:{{ $stColour }}1f;color:{{ $stColour }};border-left:3px solid {{ $stColour }};"
                                           title="{{ $ev->title }}: {{ $stLabel }}">{{ \Illuminate\Support\Str::limit($ev->title, $c['view'] === 'week' ? 22 : 14) }}</a>
                                    @endforeach
                                    @if($dayEvs->count() > $perDay)
                                        <a class="ec-more" href="{{ $calLink(['calview' => 'day', 'cal' => $key]) }}">+{{ $dayEvs->count() - $perDay }} more</a>
                                    @endif
                                </div>
                            </td>
                            @php $cursor->addDay(); @endphp
                        @endfor
                    </tr>
                @endwhile
            </tbody>
        </table>
        @if($c['byDate']->isEmpty())
            <p class="ec-empty">Nothing scheduled this {{ $unit }}. <a href="{{ route('client.post-event.choose') }}" data-no-live>Post an event</a> to see it here.</p>
        @endif
    @endif

    @if($c['stagesShown']->isNotEmpty() && $c['view'] !== 'day')
        {{-- Only the stages on screen. Not on the day view, where each
             event already names its stage. --}}
        <div class="ec-legend">
            @foreach($c['stagesShown'] as $st)
                <span><i style="background:{{ $calStages[$st][1] }};"></i>{{ $calStages[$st][0] }}</span>
            @endforeach
        </div>
    @endif
</div>
