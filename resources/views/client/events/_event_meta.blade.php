{{-- Sir Peter's request header, three lines: when; where; how many
     services, the budget and the reference the client can quote. --}}
<div class="ev-meta">
    <div class="ev-meta-row">
        @if($event->starts_at)
            <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ $event->starts_at->format('D, M j, Y') }}</div>
            <span class="ev-sep">|</span>
            <div>{{ $event->starts_at->format('g:i A') }}@if($event->ends_at && $event->ends_at->gt($event->starts_at)) – {{ $event->ends_at->format('g:i A') }}@endif</div>
        @else
            <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>No date yet</div>
        @endif
    </div>

    @if($event->location || $event->location_need === \App\Domain\Requests\VenueRule::NEED)
        <div class="ev-meta-row">
            <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                @if($event->location_need === \App\Domain\Requests\VenueRule::NEED)
                    {{ implode(', ', (array) $event->preferred_locations) ?: $event->location }} (need to find a venue)
                @else
                    {{ $event->location }}
                @endif
            </div>
        </div>
    @endif

    <div class="ev-meta-row">
        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>{{ $event->categories->count() }} {{ \Illuminate\Support\Str::plural('service', $event->categories->count()) }}</div>
        @if($event->budget_min || $event->budget_max)
            <span class="ev-sep">|</span><div>Budget: ${{ number_format((float) $event->budget_min) }} – ${{ number_format((float) $event->budget_max) }}</div>
        @elseif($event->budget)
            <span class="ev-sep">|</span><div>Budget: ${{ number_format((float) $event->budget) }}</div>
        @endif
        @if($event->guest_count)
            <span class="ev-sep">|</span><div>{{ number_format($event->guest_count) }} guests</div>
        @endif
        <span class="ev-sep">|</span><div>Request ID: <b style="margin-left:4px;">{{ $event->reference() }}</b></div>
    </div>
</div>
