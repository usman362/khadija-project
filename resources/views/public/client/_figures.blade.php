{{--
    The client's public figures, rendered once and included twice.

    Sections 2 (Quick Stats) and 7 (Client Statistics) of the R53 spec draw
    four of the same numbers, and the spec is explicit about it: "build this as
    one shared component, not two". Two widgets computing the same four values
    on one page is how they end up disagreeing.

    $stats comes from App\Support\ClientStats — the Client Dashboard reads the
    same array, so the third copy cannot drift either.

    Every value here can be null, and null prints an em dash. A client with no
    completed events has no cancellation rate; showing 0% would be a claim.

    @param array $stats
    @param array $only   which keys to draw, in order
--}}
@php
    $labels = [
        'completed_events'     => 'Completed Events',
        'repeat_professionals' => 'Repeat Professionals',
        'rating'               => 'Avg Professional Rating',
        'response_rate'        => 'Response Rate',
        'response_hours'       => 'Avg Response Time',
        'cancellation_rate'    => 'Cancellation Rate',
        'member_since'         => 'Member Since',
        'last_active'          => 'Last Active',
    ];

    $render = function (string $key) use ($stats) {
        $value = $stats[$key] ?? null;

        if ($value === null) {
            return '—';
        }

        return match ($key) {
            'response_rate', 'cancellation_rate' => $value . '%',
            'response_hours' => \App\Support\ResponseStats::describe($value),
            'rating'         => number_format($value, 1),
            'member_since'   => $value->format('M Y'),
            'last_active'    => $value->diffForHumans(),
            default          => number_format($value),
        };
    };
@endphp

<div class="cp-figures">
    @foreach($only as $key)
        <div class="cp-figure">
            <div class="cp-figure-v">{{ $render($key) }}</div>
            <div class="cp-figure-k">{{ $labels[$key] }}</div>
        </div>
    @endforeach
</div>
