{{-- A month read as a list.

     Sir Peter's calendar offers List beside Month and Week. It is the same
     events over the same range, which is why it shares build()'s month
     bounds: the heading and the figures underneath cannot disagree with the
     grid the client just came from.

     It is also the view that works on a phone. Seven columns never did. --}}

@once
@push('styles')
<style>
    .cll { display: flex; flex-direction: column; }
    .cll-day { display: flex; gap: 14px; padding: 13px 4px; border-bottom: 1px solid var(--border-color); }
    .cll-day:last-child { border-bottom: 0; }
    .cll-when { width: 82px; flex: none; text-align: center; }
    .cll-when b { display: block; font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .cll-when span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .4px; color: var(--text-muted); margin-top: 2px; }
    .cll-when.is-today b { color: var(--accent-orange, #ea580c); }

    .cll-evs { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
    .cll-ev { display: flex; align-items: center; gap: 11px; padding: 9px 12px; border-radius: 11px;
        border: 1px solid var(--border-color); text-decoration: none; }
    .cll-ev:hover { border-color: var(--accent-orange, #ea580c); }
    .cll-bar { width: 4px; align-self: stretch; border-radius: 2px; flex: none; }
    .cll-mid { min-width: 0; flex: 1; }
    .cll-n { font-size: 13.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cll-m { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cll-st { font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }

    .cll-none { padding: 34px 10px; text-align: center; }
    .cll-none p { font-size: 13.5px; color: var(--text-muted); margin: 0 0 4px; }
    .cll-none b { color: var(--text-primary); }

    @media (max-width: 600px) { .cll-when { width: 54px; } .cll-st { display: none; } }
</style>
@endpush
@endonce

<div class="cll">
    @forelse($c['byDate'] as $day => $events)
        @php $at = \Illuminate\Support\Carbon::parse($day); @endphp
        <div class="cll-day">
            <div class="cll-when {{ $at->isToday() ? 'is-today' : '' }}">
                <b>{{ $at->day }}</b>
                <span>{{ $at->format('D') }}</span>
            </div>
            <div class="cll-evs">
                @foreach($events as $ev)
                    @php
                        [$stLabel, $stColour] = $calStages[$ev->stage()] ?? ['Event', '#f97316'];
                        $where = $ev->city ? $ev->city . ($ev->state ? ', ' . $ev->state : '') : ($ev->location ?: null);
                    @endphp
                    <a class="cll-ev" href="{{ route('client.events.show', $ev) }}">
                        <span class="cll-bar" style="background:{{ $stColour }};"></span>
                        <span class="cll-mid">
                            <span class="cll-n">{{ $ev->title }}</span>
                            <span class="cll-m">
                                {{ $ev->timeLabel() ?? $ev->starts_at?->format('g:i A') }}@if($where) &middot; {{ $where }}@endif
                            </span>
                        </span>
                        <span class="cll-st" style="background:{{ $stColour }}1f;color:{{ $stColour }};">{{ $stLabel }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="cll-none">
            <p><b>Nothing on in {{ $c['title'] }}.</b></p>
            <p>Move to another month, or <a href="{{ route('client.post-event.choose') }}" style="font-weight:700;">post an event</a>.</p>
        </div>
    @endforelse
</div>
