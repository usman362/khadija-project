{{-- Market insight rows for the AI Pricing Assistant (initial server render;
     the JS render() mirrors this markup on recalculation). --}}
@foreach($insights as $i)
    <div class="apa-mi">
        @if($i['icon'] === 'fire')
            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $i['color'] }}" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.07-2.14-.22-4.05 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.15.43-2.29 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
        @elseif($i['icon'] === 'badge')
            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $i['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $i['color'] }}" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        @endif
        <span>{{ $i['text'] }}</span>
    </div>
@endforeach
