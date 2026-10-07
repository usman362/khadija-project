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

    .av-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; font-size: 12px; color: var(--text-secondary); }
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
    $avLink  = fn (array $over) => route('client.calendar.index', array_merge(
        request()->only(['calview', 'cal', 'anchor']), ['tab' => 'availability'], $over
    ));
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
        </div>
    </div>

    @if(session('status'))
        <p style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:var(--ok-text);padding:9px 14px;border-radius:10px;font-size:13px;margin:0 0 12px;">{{ session('status') }}</p>
    @endif

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

    <div class="av-legend">
        @foreach(Availability::STATES as [$label, $colour])
            <span><i style="background:{{ $colour }};border-color:{{ $colour }};"></i>{{ $label }}</span>
        @endforeach
        <span><i style="background:transparent;border:1px solid var(--border-color);"></i>Not set</span>
        <span style="color:var(--text-muted);">Click a day to change it.</span>
    </div>

    <div class="av-block">
        <h3>Block a stretch of dates</h3>
        <p>For a holiday, or a period you know will not work. It is the same thing as marking each day busy, done in one go.</p>
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
                </select>
            </div>
            <button type="submit">Apply to these days</button>
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
