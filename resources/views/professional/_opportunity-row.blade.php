{{--
    One opportunity, as a card.

    Khadijah's reference screen puts three of these side by side: the job, when
    and where it is with the distance, the services it names as chips, then
    three checks saying whether it suits this professional, and one button.
    This was a row in a list with a percentage on the right, which said how
    well it fitted without saying why.

    `actionable` is the difference between the feed's two blocks. R61 keeps
    Bid gated to a professional's actually-listed services, so a related card
    shows the work and says plainly why there is no button on it rather than
    offering one that would be refused.

    @param array $row  ['event' => Event, 'fit' => int]
    @param bool  $actionable
--}}
@php
    $event = $row['event'];
    $days  = $event->starts_at ? (int) round(now()->diffInDays($event->starts_at, false)) : null;
    $card  = \App\Support\OpportunityCard::for($event, auth()->user(), $actionable);

    /*
     * "Actionable" means the service matched. It does not mean the wizard
     * will open: the dashboard offered Bid on a request that was not taking
     * proposals and the button answered 410. Asked here of the same place
     * the wizard asks.
     */
    $canBid = $actionable
        && auth()->check()
        && \App\Support\BidAccess::allows(auth()->user(), $event->loadMissing('categories'));

    $when = $days === null ? null
        : ($days < 0 ? null : ($days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : $days.' days away')));
@endphp

<article class="of-card-item {{ $actionable ? '' : 'is-related' }}">
    <h4 class="of-ci-title" title="{{ $event->title }}">{{ $event->title }}</h4>

    <div class="of-ci-line">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span>{{ $event->starts_at?->format('M j, Y') ?? 'Date to be set' }}@if($when), {{ $when }}@endif</span>
    </div>

    <div class="of-ci-line">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>{{ $event->location ?: ($event->state ?: 'Location to be set') }}@if($card['distance']) ({{ $card['distance'] }})@endif</span>
    </div>

    @if($event->budget)
        <div class="of-ci-line">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span>${{ number_format($event->budget) }}</span>
        </div>
    @endif

    @if($card['chips'])
        <div class="of-ci-chips">
            @foreach($card['chips'] as $chip)
                <span class="of-ci-chip" title="{{ $chip }}">{{ $chip }}</span>
            @endforeach
        </div>
    @endif

    {{-- Three claims about this job and this professional, each answered from
         a record. App\Support\OpportunityCard says where each one comes from. --}}
    <ul class="of-ci-checks">
        @foreach([$card['services'], $card['travel'], $card['client']] as $check)
            <li class="s-{{ $check['state'] }}">
                @if($check['state'] === 'yes')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>
                @elseif($check['state'] === 'no')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                @elseif($check['state'] === 'new')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16.5" x2="12" y2="16.6"/></svg>
                @endif
                <span>{{ $check['text'] }}</span>
            </li>
        @endforeach
    </ul>

    <div class="of-ci-foot">
        <span class="of-ci-fit" title="How well this job suits your services, pricing and calendar.">{{ $row['fit'] }}% fit</span>
        @if($canBid)
            <a class="of-ci-go" href="{{ route('professional.bid.step', ['event' => $event->id]) }}">View details</a>
        @elseif($actionable)
            <span class="of-ci-shut">Closed to bids</span>
        @else
            <a class="of-ci-add" href="{{ route('professional.services.index') }}">Add the service</a>
        @endif
    </div>
</article>
