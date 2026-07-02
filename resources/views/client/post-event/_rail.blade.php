{{-- "Your Event At A Glance" — standard summary rail card. Expects $summary. --}}
<div class="pe-rail-card">
    <h4>Your Event At A Glance</h4>
    <div class="pe-rail-row"><span class="k">Event Type</span><span class="v">{{ $summary['event_type'] }}</span></div>
    <div class="pe-rail-row"><span class="k">Date</span><span class="v">{{ $summary['date'] }}</span></div>
    <div class="pe-rail-row"><span class="k">Time</span><span class="v">{{ $summary['time'] }}</span></div>
    <div class="pe-rail-row"><span class="k">Location</span><span class="v">{{ $summary['location'] }}</span></div>
    <div class="pe-rail-row"><span class="k">Guests</span><span class="v">{{ $summary['guests'] }}</span></div>
    <div class="pe-rail-row"><span class="k">Budget</span><span class="v">{{ $summary['budget'] }}</span></div>
</div>
