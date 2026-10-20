{{--
    Where the event is: street, city, the state you are served in, venue name.

    Sir Peter, 20 October, looking at the bidding request's Event Location card:
    "should this also be included in the ER and DR webpages? so that the clients
    are providing checkable locations for the professional/influencer user to
    make sure that's not a fake post?"

    This is that part of it, and only that part. The bidding request also asks
    whether you have a venue at all and, if not, which towns you would consider
    -- which belongs to a request that is broadcast and bid on. See
    App\Domain\Requests\EventAddress for why the rest stays there.

    One partial, because the emergency form asked for "Venue or city" in a
    single box and the direct form asked for "Venue / Location" in another, each
    with its own wording and its own idea of what a location is.

    $cls   -- the form's own input class, so this looks native on either page.
    $label -- what the group is called above the fields.
--}}
@php
    $eaCls   = $cls ?? 'esr-input';
    $eaState = \App\Domain\Requests\EventAddress::stateNameFor(auth()->user());
@endphp

<div class="ea">
    <div class="ea-field">
        <label for="ea_location">{{ $label ?? 'Street address' }}</label>
        <input type="text" name="location" id="ea_location" class="{{ $eaCls }}"
               value="{{ old('location') }}" placeholder="e.g. 1234 Garden Way">
        @error('location')<p class="ea-err">{{ $message }}</p>@enderror
    </div>

    <div class="ea-pair">
        <div class="ea-field">
            {{-- Not a bare asterisk. The server has this as nullable and wants
                 it only once a street has been typed, because a client who has
                 not settled on a venue has no city to give, and an asterisk
                 that is not true teaches people to ignore asterisks. --}}
            <label for="ea_city">City <span class="ea-opt">Required once you give a street</span></label>
            <input type="text" name="city" id="ea_city" class="{{ $eaCls }}"
                   value="{{ old('city') }}" placeholder="e.g. Baltimore">
            @error('city')<p class="ea-err">{{ $message }}</p>@enderror
        </div>
        <div class="ea-field">
            <label for="ea_state">State</label>
            <input type="text" id="ea_state" class="{{ $eaCls }}" readonly
                   value="{{ $eaState ?: 'Not set on your account' }}">
        </div>
    </div>

    <p class="ea-hint">
        The street on its own is not enough: the same street name exists in several towns, so the city
        is what tells us which one you mean. Requests are served in your own state.
    </p>

    <div class="ea-field">
        <label for="ea_venue">Venue name <span class="ea-opt">Optional</span></label>
        <input type="text" name="venue" id="ea_venue" class="{{ $eaCls }}"
               value="{{ old('venue') }}" placeholder="e.g. Oregon Ridge Park">
        @error('venue')<p class="ea-err">{{ $message }}</p>@enderror
    </div>
</div>

@once
@push('styles')
<style>
    .ea-field { margin-bottom: 11px; }
    .ea-field:last-child { margin-bottom: 0; }
    .ea-field label { display: block; font-size: 12px; font-weight: 700;
        color: var(--text-secondary, #374151); margin-bottom: 6px; }
    .ea-opt { font-size: 10.5px; font-weight: 600; text-transform: uppercase;
        letter-spacing: .3px; color: var(--text-muted, #9ca3af); margin-left: 5px; }
    .ea-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ea-hint { font-size: 11.5px; line-height: 1.5; color: var(--text-muted, #6b7280);
        margin: 0 0 11px; }
    .ea-err { font-size: 11.5px; color: #b91c1c; font-weight: 600; margin: 5px 0 0; line-height: 1.45; }
    /* The state is shown, not asked, and should not look like somewhere to
       type. */
    .ea #ea_state { background: var(--bg-muted, #f9fafb); color: var(--text-muted, #6b7280); cursor: default; }
    @media (max-width: 520px) { .ea-pair { grid-template-columns: 1fr; } }
</style>
@endpush
@endonce
