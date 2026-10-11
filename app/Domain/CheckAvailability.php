<?php

namespace App\Domain;

use App\Models\Event;
use App\Models\User;
use App\Support\Availability;
use App\Domain\Auth\Enums\RoleName;
use App\Support\SupplierProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Before I hire somebody, are they free on my date?"
 *
 * Half of this answer is real and half of it cannot be, and the difference
 * matters more here than anywhere else on the client side, because a client
 * reading a green tick will go and book that person.
 *
 * WHAT IS REAL: the client's own events and their dates, who the professionals
 * and influencers are, what they do, and **when they are already busy**.
 * `Availability::busyDates()` is derived from bookings and shifts, so a red
 * mark is a fact.
 *
 * WHAT CANNOT BE: a green one. "I am free that day" is something the supplier
 * records about themselves, and their side has nowhere to record it yet. Every
 * date we have no commitment for is therefore genuinely UNKNOWN, not free.
 *
 * So the page runs in one of two modes, and says which. Left alone it shows
 * only what it knows: red where there is a booking, grey everywhere else. In
 * SAMPLE mode, asked for so the screen can be reviewed with something in it,
 * the unknown greys are filled in with a made-up pattern and the page carries
 * a banner saying so. Nothing in sample mode is ever saved or acted upon.
 */
final class CheckAvailability
{
    public const FREE    = 'available';
    public const BUSY    = 'unavailable';
    public const UNKNOWN = 'unanswered';

    public const LABELS = [
        self::FREE    => 'Available',
        self::BUSY    => 'Unavailable',
        self::UNKNOWN => 'Not Answered',
    ];

    /**
     * The dates a client wants checked: the event's own, plus its backups.
     *
     * @return Collection<int, array{key:string, label:string, at:CarbonInterface}>
     */
    public static function dates(?Event $event): Collection
    {
        if (! $event?->starts_at) {
            return collect();
        }

        $out = collect([[
            'key'   => 'primary',
            'label' => 'Primary Date',
            'at'    => $event->starts_at,
        ]]);

        // backup_dates is where a client records "or this one instead". Most
        // events carry none, and then the column simply is not offered rather
        // than being filled with dates nobody chose.
        foreach (collect($event->backup_dates ?? [])->take(2)->values() as $i => $raw) {
            $at = rescue(fn () => Carbon::parse($raw), null, false);

            if ($at) {
                $out->push([
                    'key'   => 'backup' . ($i + 1),
                    'label' => 'Backup Date ' . ($i + 1),
                    'at'    => $at,
                ]);
            }
        }

        return $out;
    }

    /**
     * Suppliers and how they stand on each of those dates.
     *
     * @param  Collection<int, array>  $dates
     * @return Collection<int, array>
     */
    public static function suppliers(
        Collection $dates,
        string $kind = 'professional',
        bool $sample = false,
        string $q = '',
    ): Collection {
        $role = $kind === 'influencer' ? RoleName::INFLUENCER : RoleName::PROFESSIONAL;

        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', $role->value))
            ->when($q !== '', fn ($b) => $b->where('name', 'like', '%' . $q . '%'))
            ->with(['profile:id,user_id,headline,city,state', 'serviceCategories:id,name'])
            ->withAvg(['reviewsReceived as rating' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews' => fn ($r) => $r->where('is_hidden', false)])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'avatar'   => $u->avatar_url,
                'noun'     => SupplierProfile::noun($u),
                'where'    => trim(implode(', ', array_filter([$u->profile?->city, $u->profile?->state]))) ?: null,
                'services' => $u->serviceCategories->pluck('name')->take(2)->implode(', ') ?: $u->profile?->headline,
                'rating'   => $u->rating ? round((float) $u->rating, 1) : null,
                'reviews'  => (int) $u->reviews,
                'profile'  => SupplierProfile::url($u),
                'states'   => self::statesFor($u, $dates, $sample),
            ])
            ->values();
    }

    /**
     * How one supplier stands on each date.
     *
     * @return array<string, string> date key => one of the three constants
     */
    private static function statesFor(User $u, Collection $dates, bool $sample): array
    {
        $busy = array_flip(Availability::busyDates($u, 365));
        $out = [];

        foreach ($dates as $d) {
            $day = $d['at']->toDateString();

            if (isset($busy[$day])) {
                // A booking. True whichever mode the page is in.
                $out[$d['key']] = self::BUSY;
                continue;
            }

            $out[$d['key']] = $sample
                ? self::invent($u->id, $day)
                : self::UNKNOWN;
        }

        return $out;
    }

    /**
     * A made-up answer, for reviewing the screen with something in it.
     *
     * Deterministic on purpose: the same person on the same date reads the
     * same way on every reload, so the page can be looked at twice without
     * the pattern shifting underfoot. It is still invented, which is why
     * nothing may be saved or acted upon from sample mode.
     */
    private static function invent(int $userId, string $day): string
    {
        $n = crc32($userId . ':' . $day) % 10;

        return match (true) {
            $n < 6  => self::FREE,      // most people, most days
            $n < 9  => self::BUSY,
            default => self::UNKNOWN,   // some never answer, which is realistic
        };
    }
}
