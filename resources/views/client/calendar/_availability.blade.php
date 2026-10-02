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
    .av-set { display: flex; gap: 5px; margin-top: auto; }
    .av-btn { flex: 1; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 8px; padding: 4px 0;
        font-family: inherit; font-size: 10.5px; font-weight: 800; cursor: pointer; color: var(--text-muted); }
    .av-btn:hover { border-color: var(--text-muted); }
    .av-btn.on-free { background: rgba(16,185,129,.14); border-color: #10b981; color: #047857; }
    .av-btn.on-busy { background: rgba(148,163,184,.2); border-color: #94a3b8; color: #475569; }

    .av-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; font-size: 12px; color: var(--text-secondary); }
    .av-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .av-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

    .av-block { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); }
    .av-block h3 { margin: 0 0 4px; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .av-block p { margin: 0 0 10px; font-size: 12.5px; color: var(--text-muted); }
    .av-block form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .av-block input[type="date"], .av-block select { font-family: inherit; font-size: 13px; padding: 8px 10px;
        border: 1px solid var(--border-color); border-radius: 9px; background: var(--bg-card); color: var(--text-primary); }
    .av-block button { font-family: inherit; font-size: 13px; font-weight: 800; padding: 9px 16px; border: 0; border-radius: 9px;
        background: var(--accent-orange, #ea580c); color: #fff; cursor: pointer; }

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
                                <span class="av-set">
                                    @foreach([Availability::AVAILABLE => ['Free', 'on-free'], Availability::UNAVAILABLE => ['Busy', 'on-busy']] as $value => [$word, $cls])
                                        <form method="POST" action="{{ route('client.calendar.availability') }}" style="display:contents;">
                                            @csrf
                                            <input type="hidden" name="day" value="{{ $key }}">
                                            <input type="hidden" name="state" value="{{ $value }}">
                                            <input type="hidden" name="back" value="{{ $back }}">
                                            <button type="submit" class="av-btn {{ $state === $value ? $cls : '' }}"
                                                    title="{{ $cursor->format('M j') }}: {{ $state === $value ? 'marked ' . strtolower(Availability::STATES[$value][0]) . ', press to clear' : 'mark ' . strtolower(Availability::STATES[$value][0]) }}"
                                                    aria-pressed="{{ $state === $value ? 'true' : 'false' }}">{{ $word }}</button>
                                        </form>
                                    @endforeach
                                </span>
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
            <span><i style="background:{{ $colour }};"></i>{{ $label }}</span>
        @endforeach
        <span><i style="background:transparent;border:1px solid var(--border-color);"></i>Not answered</span>
    </div>

    <div class="av-block">
        <h3>Block a stretch of dates</h3>
        <p>For a holiday, or a period you know will not work. It is the same thing as marking each day busy, done in one go.</p>
        <form method="POST" action="{{ route('client.calendar.availability.range') }}">
            @csrf
            <input type="hidden" name="back" value="{{ $back }}">
            <input type="date" name="from" required aria-label="First day">
            <input type="date" name="to" required aria-label="Last day">
            <select name="state" aria-label="Mark them as">
                <option value="{{ Availability::UNAVAILABLE }}">Mark unavailable</option>
                <option value="{{ Availability::AVAILABLE }}">Mark available</option>
            </select>
            <button type="submit">Apply</button>
        </form>
    </div>
</div>
