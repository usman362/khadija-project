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

        {{-- "Actionable" meant the service matched. It did not mean the wizard
             would open: the dashboard offered Bid on a request that was not
             taking proposals and the button answered 410. Asked properly now,
             of the same place the wizard asks. 13 November. --}}
        @php
            $__canBid = $actionable
                && auth()->check()
                && \App\Support\BidAccess::allows(auth()->user(), $event->loadMissing('categories'));
        @endphp
        @if($__canBid)
            <a class="of-bid" href="{{ route('professional.bid.step', ['event' => $event->id]) }}">Bid</a>
        @elseif($actionable)
            <span class="of-locked">Closed to bids</span>
        @else
            <span class="of-locked">Not your service</span>
        @endif
    </div>
</div>
