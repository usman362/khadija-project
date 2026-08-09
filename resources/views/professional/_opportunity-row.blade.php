{{--
    One row of the Opportunity Feed.

    `actionable` is the whole difference between the two blocks. R61 keeps Bid
    gated to a professional's actually-listed services, so a related row shows
    the work and says plainly why there is no button on it — rather than
    offering one that would be refused.

    @param array $row  ['event' => Event, 'fit' => int]
    @param bool  $actionable
--}}
@php
    $event = $row['event'];
    $days  = $event->starts_at ? (int) round(now()->diffInDays($event->starts_at, false)) : null;
@endphp

<div class="of-row">
    <div class="of-row-body">
        <div class="of-row-title">{{ $event->title }}</div>
        <div class="of-row-meta">
            {{ $event->categories->pluck('name')->take(2)->implode(' · ') ?: 'Service to be confirmed' }}
        </div>
        <div class="of-row-meta">
            {{ collect([
                $event->location,
                $event->starts_at?->format('M j'),
                $days !== null && $days >= 0 ? ($days === 0 ? 'today' : $days . ' days away') : null,
                $event->budget ? '$' . number_format($event->budget) : null,
            ])->filter()->implode(' · ') }}
        </div>
    </div>

    <div class="of-row-side">
        <div class="of-fit">{{ $row['fit'] }}%</div>
        <div class="of-fit-k">Fit</div>

        @if($actionable)
            <a class="of-bid" href="{{ route('professional.bid.step', ['event' => $event->id]) }}">Bid</a>
        @else
            <span class="of-locked">Not your service</span>
        @endif
    </div>
</div>
