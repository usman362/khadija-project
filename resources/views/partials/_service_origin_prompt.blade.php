{{-- Asks a professional to set their service origin and travel radius.

     Shown only while the origin cannot be matched on. The wording is careful
     about WHY, because the honest reason today is not the obvious one:
     distance matching is still switched off (config geo.state_matching), so
     saying "clients can't find you" would be false. What is true is that this
     is the thing standing between us and switching it on — none of the
     professionals on record has set one.

     Sir Peter, 2026-08-31: distance to the venue is the right filter, not the
     state a business address happens to sit in. --}}
@php
    $__pro = auth()->user();
    $__needsOrigin = $__pro
        && $__pro->hasRole('professional')
        && ! \App\Support\RadiusMatching::originIsMatchable($__pro);
    $__failed = $__pro?->profile?->origin_precision === \App\Domain\Geolocation\LocationPrecision::UNRESOLVED
        && ($__pro?->profile?->service_origin_line || $__pro?->profile?->service_origin_zip || $__pro?->profile?->service_origin_city);
@endphp

@if($__needsOrigin)
    <div class="so-prompt">
        <span class="so-prompt-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
        </span>

        <div class="so-prompt-body">
            @if($__failed)
                <b>We could not place your service origin</b>
                <p>The address you gave could not be found on a map, so we cannot work out how far you are from an event. Check it and save again.</p>
            @else
                <b>Where do you travel from, and how far?</b>
                <p>
                    We are moving to matching by distance to the event rather than by which state
                    your business address is in — so a job forty minutes away is not hidden from you
                    because it is over a state line. Setting yours is what lets us make that change.
                </p>
            @endif
        </div>

        <a href="{{ route('professional.profile.index', ['tab' => 'general']) }}#service-origin" class="so-prompt-btn">
            {{ $__failed ? 'Check the address' : 'Set it now' }}
        </a>
    </div>
@endif
