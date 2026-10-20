<?php

namespace App\Domain\Requests;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Where the event is, asked the same way on every request form.
 *
 * Sir Peter, 27 Sep: "the city and state is vital to make sure that the system
 * or website doesn't mix up the street address with other cities and state bc
 * the street names might overlap." Then on 20 Oct, looking at the bidding
 * request's location card: "should this also be included in the ER and DR
 * webpages? so that the clients are providing checkable locations for the
 * professional/influencer user to make sure that's not a fake post?"
 *
 * It should, and only this part of it. The bidding request also asks whether
 * you have a venue at all, and if you do not, which towns you would consider
 * and what kind of space -- which exists because that request is broadcast and
 * professionals bid against those towns. An emergency request is at a place
 * that already exists, and a direct request goes to one named professional. The
 * part that makes an address checkable is the street, the city and the state,
 * and that belongs on all three.
 *
 * Before this, the emergency form took one free-text box and the direct form
 * took one with no check on it at all, so "The Grand Ballroom" and a street
 * with no town both went straight through -- the fault Peter reported in
 * October when he typed a fake address and watched it pass.
 *
 * The rules live here rather than in three controllers because this is the
 * third time a location rule has had to be found in several places at once.
 */
class EventAddress
{
    /**
     * What a request form accepts for a location.
     *
     * `requireNumber: false` on purpose: a client who has not settled on a
     * venue can still say "Baltimore", and refusing that would turn an optional
     * question into a required one.
     */
    public static function rules(): array
    {
        return [
            'location' => ['nullable', 'string', 'max:200', new \App\Rules\PlaceNotALink, new \App\Rules\PlausibleAddress(requireNumber: false)],
            'city'     => ['nullable', 'string', 'max:120', new \App\Rules\PlaceNotALink],
            'venue'    => ['nullable', 'string', 'max:200', new \App\Rules\PlaceNotALink],
        ];
    }

    /** The sentence shown when a street arrives with no town behind it. */
    public static function cityMessage(): string
    {
        return 'Add the city. The same street name exists in several towns, '
            . 'and the city is what tells us which one you mean.';
    }

    /**
     * A street with no town is not a location.
     *
     * Checked only when a street was actually given. Leaving the whole address
     * for later is still allowed and asks for nothing, which is what keeps the
     * question optional rather than optional-looking.
     */
    public static function assertCityGiven(array $data): void
    {
        $street = trim((string) ($data['location'] ?? ''));

        if ($street === '' || ! preg_match('/\d/', $street)) {
            return;
        }

        if (trim((string) ($data['city'] ?? '')) === '') {
            throw ValidationException::withMessages(['city' => static::cityMessage()]);
        }
    }

    /**
     * The state the request is served in, which is the client's own.
     *
     * Shown, never asked. A state field stood on the bidding request once and
     * was taken out because choosing one changed nothing: every request is
     * matched on the client's own state, so a client who picked another would
     * have posted a request no professional could answer.
     */
    public static function stateNameFor(?User $user): ?string
    {
        $code = $user?->profile?->state;

        return $code ? (config('geo.allowed_states')[$code] ?? $code) : null;
    }

    /** The columns to store, from what the form posted. */
    public static function columns(array $data): array
    {
        return [
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'city'     => filled($data['city'] ?? null) ? trim($data['city']) : null,
            'venue'    => filled($data['venue'] ?? null) ? trim($data['venue']) : null,
        ];
    }
}
