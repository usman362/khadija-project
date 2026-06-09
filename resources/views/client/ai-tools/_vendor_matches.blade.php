{{-- Vendor match cards (server render; JS renderMatches() mirrors this). --}}
@forelse($matches as $m)
    @php
        $full = (int) floor($m['rating']);
        $half = ($m['rating'] - $full) >= 0.5;
    @endphp
    <div class="vm-match">
        <div class="vm-match-top">
            <span class="vm-avatar" style="background:linear-gradient(135deg, {{ $m['grad'] }});">{{ $m['initials'] }}</span>
            <div class="vm-match-main">
                <div class="vm-match-name">{{ $m['name'] }}</div>
                <div class="vm-stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $full)
                            <svg viewBox="0 0 24 24" fill="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @elseif($i === $full + 1 && $half)
                            <svg viewBox="0 0 24 24"><defs><linearGradient id="vmhalf{{ $loop->parent->index ?? 0 }}{{ $i }}"><stop offset="50%" stop-color="#f59e0b"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#vmhalf{{ $loop->parent->index ?? 0 }}{{ $i }})"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="#d1d5db"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @endif
                    @endfor
                    <span class="vm-reviews">({{ $m['reviews'] }})</span>
                </div>
                <div class="vm-tags">
                    @foreach($m['tags'] as $t)<span class="vm-tag">{{ $t }}</span>@endforeach
                    @if($m['available'])<span class="vm-tag vm-tag-avail">Available</span>@endif
                </div>
            </div>
            <div class="vm-match-right">
                <span class="vm-match-pct">{{ $m['match'] }}% Match</span>
                <span class="vm-match-price">${{ number_format($m['price']) }}</span>
            </div>
        </div>
        <div class="vm-why"><b>Why matched?</b> {{ $m['why'] }}</div>
    </div>
@empty
    <div class="vm-empty">No vendors match these filters. Try widening your budget or lowering the match threshold.</div>
@endforelse
