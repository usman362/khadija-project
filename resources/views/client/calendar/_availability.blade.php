{{--
    My Availability: the month, where every day takes one of three answers.

    Sir Peter, 24 September: "i noticed that it says unavailable at the bottom,
    but why not Available as well?"

    Because nothing recorded it. A day nobody has answered is drawn plain, and
    says so, rather than being coloured free. The control that sets an answer
    is the control that removes it: pressing the state a day already holds
    clears it, so there is no separate undo to go looking for.
--}}
@once
@push('styles')
<style>
    .av-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .av-head h2 { margin: 0; font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .av-head p { margin: 3px 0 0; font-size: 12.5px; color: var(--text-muted); }
    .av-nav { display: flex; align-items: center; gap: 6px; }
    .av-nav a { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 10px;
        border: 1px solid var(--border-color); border-radius: 9px; text-decoration: none; color: var(--text-secondary); font-size: 13px; font-weight: 700; }
    .av-nav a:hover { background: var(--bg-card-hover); }

    .av-nav a.is-on { border-color: var(--accent-orange, #ea580c); background: rgba(249,115,22,.1);
                      color: var(--accent-orange, #ea580c); font-weight: 800; }

    /* The same month as rows. One line per day, in order, with the day's own
       one-click control at the end of it. */
    .av-list { display: flex; flex-direction: column; gap: 6px; }
    .av-row { display: flex; align-items: center; gap: 12px; border: 1px solid var(--border-color);
              border-radius: 11px; padding: 9px 12px; }
    .av-row.is-today { border-color: var(--accent-orange, #ea580c); }
    .av-row-day { font-size: 13px; font-weight: 800; color: var(--text-primary); min-width: 108px; }
    .av-row-dow { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .av-row-ev { font-size: 11.5px; color: var(--info-text); }
    .av-row .av-set { flex: none; margin-left: auto; }
    .av-row .av-btn { flex: none; }
    @media (max-width: 560px) { .av-row-day { min-width: 0; } .av-row-dow { display: none; } }

    .av-grid { width: 100%; border-collapse: separate; border-spacing: 6px; table-layout: fixed; }
    .av-grid th { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; padding-bottom: 2px; }
    .av-day { border: 1px solid var(--border-color); border-radius: 12px; padding: 7px 8px 8px; min-height: 76px; display: flex; flex-direction: column; gap: 6px; }
    .av-day.is-muted { opacity: .45; }
    .av-day.is-today { border-color: var(--accent-orange, #ea580c); }
    .av-num { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .av-ev { font-size: 10.5px; font-weight: 700; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .av-set { display: flex; margin-top: auto; }
    .av-btn { flex: 1; display: inline-flex; align-items: center; gap: 6px; border: 0; background: none; border-radius: 8px;
        padding: 4px 2px; font-family: inherit; font-size: 11px; font-weight: 700; cursor: pointer; color: var(--text-muted); text-align: left; }
    .av-btn.is-set { color: var(--text-primary); }
    .av-btn:hover { background: var(--bg-card-hover, #f1f5f9); }
    .av-btn i { width: 9px; height: 9px; border-radius: 50%; flex: none; display: inline-block;
        background: transparent; border: 1.5px solid var(--border-color); }

    /* At the top, as a key for the grid below it. It used to sit under
       the calendar, where it explained colours the reader had already had
       to guess at. */
    .av-legend { display: flex; flex-wrap: wrap; gap: 14px; margin: 2px 0 14px; padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color); font-size: 12px; color: var(--text-secondary); }
    .av-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .av-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

    /*
     * Two bare boxes in a row at the foot of the card: no labels, nothing
     * saying which was the first day and which the last, and a shape that
     * matched nothing else on the client's screens. It read as something
     * left over rather than a tool.
     *
     * It is a panel now, with each field named above it, and the fields take
     * the layout's own form styling so they look like every other field this
     * client fills in.
     */
    .av-warn { grid-column: 1 / -1; font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.55; }
    .av-block { margin-top: 18px; padding: 16px 18px; border-radius: 14px;
        border: 1px solid var(--border-color); background: var(--bg-subtle, rgba(0,0,0,.02)); }
    .av-block h3 { margin: 0 0 4px; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .av-block > p { margin: 0 0 14px; font-size: 12.5px; color: var(--text-muted); max-width: 62ch; }
    .av-block form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
    .av-field { display: flex; flex-direction: column; gap: 5px; min-width: 168px; }
    .av-field label { font-size: 11.5px; font-weight: 700; letter-spacing: .02em;
        text-transform: uppercase; color: var(--text-muted); }
    .av-input, .av-block select { font-family: inherit; font-size: 13.5px; padding: 9px 12px;
        border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-input, var(--bg-card));
        color: var(--text-primary); width: 100%; cursor: pointer; }
    .av-input::placeholder { color: var(--text-muted); }
    .av-input:focus, .av-block select:focus { outline: none;
        border-color: var(--accent-orange, #ea580c);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-orange, #ea580c) 14%, transparent); }
    .av-block button { font-family: inherit; font-size: 13.5px; font-weight: 800; padding: 10px 20px; border: 0;
        border-radius: 10px; background: var(--accent-orange, #ea580c); color: #fff; cursor: pointer; }
    .av-block button:hover { background: var(--brand-strong, #c2410c); }
    @media (max-width: 560px) { .av-field { min-width: 0; flex: 1 1 100%; } .av-block button { width: 100%; } }

    @media (max-width: 640px) { .av-day { min-height: 0; } .av-set { flex-direction: column; } }
</style>
@endpush
@endonce

@php
    use App\Domain\Calendar\Availability;

    $c       = $calendar;
    $back    = request()->fullUrl();
    // Where this grid's month arrows point. It used to be hardcoded to the
    // calendar page, which is where My Availability lived until 12 October.
    $avRoute = $avRoute ?? 'client.check-availability.index';
    $avFixed = $avFixed ?? ['tab' => 'mine'];
    $avLink  = fn (array $over) => route($avRoute, array_filter(
        array_merge(request()->only(['cal', 'anchor', 'avview']), $avFixed, $over),
        fn ($v) => $v !== null && $v !== '',
    ));

    // Grid unless asked otherwise. A month of dates reads as a grid by
    // default; the list is for a phone, and for anybody who would rather read
    // thirty one lines than thirty one squares.
    $avView = request()->query('avview') === 'list' ? 'list' : 'grid';
@endphp

<div class="cl-card ec-card">
    <div class="av-head">
        <div>
            <h2>{{ $c['title'] }}</h2>
            <p>Say which days suit you. A day you have not answered stays blank, because nobody has said anything about it.</p>
        </div>
        <div class="av-nav">
            <a href="{{ $avLink(['cal' => $c['prev']->format('Y-m-d')]) }}" aria-label="Previous month">&lsaquo;</a>
            <a href="{{ $avLink(['cal' => now()->format('Y-m-d')]) }}">Today</a>
            <a href="{{ $avLink(['cal' => $c['next']->format('Y-m-d')]) }}" aria-label="Next month">&rsaquo;</a>
            {{-- Sir Peter, 20 October: "once a user uses or clicks this button,
                 the client can not go back to the original calendar like setup,
                 but why not?" He was right. View as list was a one-way door: it
                 sent you to the calendar page in list view and left no way back
                 to this grid at all. It is a toggle now, and it stays on this
                 page. --}}
            <a href="{{ $avLink(['avview' => null]) }}" class="{{ $avView === 'grid' ? 'is-on' : '' }}"
               aria-current="{{ $avView === 'grid' ? 'true' : 'false' }}">Grid</a>
            <a href="{{ $avLink(['avview' => 'list']) }}" class="{{ $avView === 'list' ? 'is-on' : '' }}"
               aria-current="{{ $avView === 'list' ? 'true' : 'false' }}">List</a>
        </div>
    </div>

    <div class="av-legend">
        @foreach(Availability::STATES as [$label, $colour])
            <span><i style="background:{{ $colour }};border-color:{{ $colour }};"></i>{{ $label }}</span>
        @endforeach
        <span><i style="background:transparent;border:1px solid var(--border-color);"></i>Not set</span>
        <span style="color:var(--text-muted);">Click a day to change it.</span>
    </div>

    @if(session('status'))
        <p style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:var(--ok-text);padding:9px 14px;border-radius:10px;font-size:13px;margin:0 0 12px;">{{ session('status') }}</p>
    @endif

    @if($avView === 'list')
        {{-- The same month, read as a list. Only this month's own days: the
             greyed neighbours either side exist because a grid has to fill its
             first and last rows, and in a list they read as padding. --}}
        <div class="av-list">
            @php $cursor = $c['anchor']->copy()->startOfMonth(); @endphp
            @while($cursor->month === $c['anchor']->month)
                @php
                    $key   = $cursor->format('Y-m-d');
                    $state = $availability->get($key)?->state;
                    $evs   = $c['byDate']->get($key, collect());

                    // The same cycle as the grid: an untouched day becomes
                    // available, anything else becomes unavailable, and posting
                    // the state a day already holds clears it.
                    $next = $state === null
                        ? Availability::AVAILABLE
                        : Availability::UNAVAILABLE;

                    [$word, $dot] = $state
                        ? Availability::STATES[$state]
                        : ['Not set', null];
                @endphp
                <div class="av-row {{ $cursor->isToday() ? 'is-today' : '' }}">
                    <span class="av-row-day">
                        {{ $cursor->format('M j') }}
                        <span class="av-row-dow">{{ $cursor->format('D') }}</span>
                    </span>

                    @if($evs->isNotEmpty())
                        <span class="av-row-ev" title="{{ $evs->pluck('title')->implode(', ') }}">
                            {{ $evs->count() }} {{ \Illuminate\Support\Str::plural('event', $evs->count()) }}
                        </span>
                    @endif

                    <form method="POST" action="{{ route('client.calendar.availability') }}" class="av-set">
                        @csrf
                        <input type="hidden" name="day" value="{{ $key }}">
                        <input type="hidden" name="state" value="{{ $next }}">
                        <input type="hidden" name="back" value="{{ $back }}">
                        <button type="submit" class="av-btn {{ $state ? 'is-set' : '' }}"
                                title="{{ $cursor->format('M j') }}: {{ strtolower($word) }}. Click to change."
                                aria-label="{{ $cursor->format('l, F j') }}: {{ strtolower($word) }}. Click to change.">
                            <i style="{{ $dot ? 'background:' . $dot . ';border-color:' . $dot . ';' : '' }}"></i>{{ $word }}
                        </button>
                    </form>
                </div>
                @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    @else
    <table class="av-grid">
        <thead><tr>@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)<th>{{ $dow }}</th>@endforeach</tr></thead>
        <tbody>
            @php $cursor = $c['first']->copy(); @endphp
            @while($cursor->lte($c['last']))
                <tr>
                    @for($i = 0; $i < 7; $i++)
                        @php
                            $key   = $cursor->format('Y-m-d');
                            $state = $availability->get($key)?->state;
                            $evs   = $c['byDate']->get($key, collect());
                            $muted = $cursor->month !== $c['anchor']->month;
                        @endphp
                        <td>
                            <div class="av-day {{ $muted ? 'is-muted' : '' }} {{ $cursor->isToday() ? 'is-today' : '' }}">
                                <span class="av-num">{{ $cursor->day }}</span>
                                @if($evs->isNotEmpty())
                                    <span class="av-ev" title="{{ $evs->pluck('title')->implode(', ') }}">{{ $evs->count() }} {{ \Illuminate\Support\Str::plural('event', $evs->count()) }}</span>
                                @endif
                                @php
                                    /*
                                     * Sir Peter, 4 Oct, with a version from
                                     * ChatGPT beside ours: one row per day, a
                                     * dot and a word, one click. He is right
                                     * that two buttons on every square was
                                     * noisier than it needed to be.
                                     *
                                     * His version defaults every day to
                                     * Unavailable. That part is not taken: a
                                     * new client would open the month to
                                     * thirty one red dots, with the platform
                                     * saying they are busy on days nobody
                                     * asked them about. So the click cycles
                                     * through three, and an untouched day is
                                     * hollow and says Not set.
                                     *
                                     * Posting the state a day already holds
                                     * clears it, which is what turns the last
                                     * step of the cycle back to nothing.
                                     */
                                    $next = $state === null
                                        ? Availability::AVAILABLE
                                        : Availability::UNAVAILABLE;

                                    [$word, $dot] = $state
                                        ? Availability::STATES[$state]
                                        : ['Not set', null];
                                @endphp
                                <form method="POST" action="{{ route('client.calendar.availability') }}" class="av-set">
                                    @csrf
                                    <input type="hidden" name="day" value="{{ $key }}">
                                    <input type="hidden" name="state" value="{{ $next }}">
                                    <input type="hidden" name="back" value="{{ $back }}">
                                    <button type="submit" class="av-btn {{ $state ? 'is-set' : '' }}"
                                            title="{{ $cursor->format('M j') }}: {{ strtolower($word) }}. Click to change."
                                            aria-label="{{ $cursor->format('l, F j') }}: {{ strtolower($word) }}. Click to change.">
                                        <i style="{{ $dot ? 'background:' . $dot . ';border-color:' . $dot . ';' : '' }}"></i>{{ $word }}
                                    </button>
                                </form>
                            </div>
                        </td>
                        @php $cursor->addDay(); @endphp
                    @endfor
                </tr>
            @endwhile
        </tbody>
    </table>
    @endif

    {{-- Sir Peter, 20 October: "Rename the feature everywhere to Set
         Availability for Multiple Dates ... 'Block' suggests that dates can
         only be marked Unavailable, whereas the existing dropdown allows users
         to select an availability status."

         He is right, and the old name is where the missing third choice came
         from: it began as blocking, and blocking only ever meant one answer.
         There was also a second copy of this in the Quick Actions rail, named
         differently, which he asked to be removed: "we should avoid presenting
         the same feature multiple times on one webpage." --}}
    <div class="av-block" id="avRange">
        <h3>Set availability for multiple dates</h3>
        <p>A holiday, a run of days that already suit you, or a stretch you want to take an answer back off. The same thing as clicking each day, done in one go.</p>
        <form method="POST" action="{{ route('client.calendar.availability.range') }}">
            @csrf
            <input type="hidden" name="back" value="{{ $back }}">
            <div class="av-field">
                <label for="av-from">First day</label>
                {{-- The class travels to the box the client can see: the one
                     in the markup is hidden the moment the picker loads. --}}
                <input type="date" id="av-from" name="from" class="av-input" required
                       value="{{ old('from') }}" max="{{ now()->addYears(3)->format('Y-m-d') }}">
            </div>
            <div class="av-field">
                <label for="av-to">Last day</label>
                <input type="date" id="av-to" name="to" class="av-input" required
                       value="{{ old('to') }}" max="{{ now()->addYears(3)->format('Y-m-d') }}">
            </div>
            <div class="av-field">
                <label for="av-state">Mark them as</label>
                <select id="av-state" name="state">
                    <option value="{{ Availability::UNAVAILABLE }}">Unavailable</option>
                    <option value="{{ Availability::AVAILABLE }}">Available</option>
                    {{-- The third choice, which the range never had. Not set is
                         the absence of an answer rather than an answer, so it
                         clears those days instead of writing to them. --}}
                    <option value="not_set">Not set</option>
                </select>
            </div>
            {{-- "If a selected date already has an availability status, inform
                 the user that applying the new status will replace it." Said
                 once, here, rather than as a confirmation dialog on a form that
                 is easy to run again. --}}
            <p class="av-warn">Any day in the range that already has an answer will be replaced. Your events and bookings are not touched.</p>
            <button type="submit">Apply to selected dates</button>
        </form>
        {{-- The first day fills the second's floor the moment it is chosen, so
             a stretch cannot be asked for backwards. --}}
        <script>
        (function () {
            var from = document.getElementById('av-from'), to = document.getElementById('av-to');
            if (! from || ! to) return;
            from.addEventListener('change', function () {
                to.min = from.value;
                if (to.value && to.value < from.value) to.value = from.value;
            });
        })();
        </script>
    </div>
</div>
