{{--
    The side of a request page: how it works, what it costs, what to say.

    Sir Peter, 2026-09-10, on the recreated mockups: "we lack data for the users
    in these current we have." The screens were not missing records — they were
    missing the explanation beside the form, which every one of his mockups has.

    And 2026-09-09, the rule underneath it: "if all of these workflows
    ultimately lead to an agreement, I would make the core agreement-driving
    information consistent across all of them." Four flows had four hand-written
    rails, so the $2.99 was explained four different ways. The words now come
    from config/request-help.php, once, for all four.

    $flow  'br' | 'er' | 'dr' | 'toolkit'
--}}
@php
    $panels = config('request-help.' . $flow, []);
@endphp

@include('partials._help_rail_styles')

@php
    // One place for the icons, so a panel names a shape rather than carrying
    // a path around in the config file.
    $shapes = [
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'dollar' => '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'bolt'   => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'info'   => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'send'   => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
    ];
@endphp

{{-- The page owns the <aside class="hr-rail"> so it can put its own card
     first -- the wizard's progress bar, for instance. --}}
@foreach($panels as $panel)
        <div class="hr-card">
            <h4>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    {!! $shapes[$panel['icon']] ?? $shapes['info'] !!}
                </svg>{{ $panel['title'] }}
            </h4>

            @if(! empty($panel['steps']))
                @foreach($panel['steps'] as $i => $step)
                    <div class="hr-step">
                        <span class="hr-n">{{ $i + 1 }}</span>
                        <span><b>{{ $step['b'] }}</b><span class="t">{{ $step['t'] }}</span></span>
                    </div>
                @endforeach
            @else
                @foreach($panel['items'] ?? [] as $item)
                    <div class="hr-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><b>{{ $item['b'] }}</b><span class="t">{{ $item['t'] }}</span></span>
                    </div>
                @endforeach
            @endif
    </div>
@endforeach
