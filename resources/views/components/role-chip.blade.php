@props(['user' => null, 'noun' => null])
{{--
    Which kind of account somebody is, in that account's own colour.

    Sir Peter, 20 October: "Influencer schedule will be add on my events like
    professional schedule." The bookings were already on My Events -- that table
    has never filtered by role -- but the column was headed "Professional" and
    nothing on the row said otherwise, so a booked influencer was shown as a
    professional.

    The word comes from App\Support\SupplierProfile, which is the same source the
    profile link and the message button read, so a row cannot call somebody an
    influencer and then offer to open their professional profile. The colours
    come from App\Support\RoleColours, which Peter set in stone on 23 September.

    Pass a user, or a noun already worked out by whatever built the row.
--}}
@php
    $rcNoun = $noun ?: ($user ? \App\Support\SupplierProfile::noun($user) : null);
@endphp
@if($rcNoun)
    <span class="role-chip"
          style="background:{{ \App\Support\RoleColours::tintFor($rcNoun) }};color:{{ \App\Support\RoleColours::strongFor($rcNoun) }};">
        {{ ucfirst($rcNoun) }}
    </span>
@endif

@once
@push('styles')
<style>
    .role-chip { display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 999px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .2px; line-height: 1.5; }
</style>
@endpush
@endonce
