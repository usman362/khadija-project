<?php

namespace App\Support;

use App\Domain\Geolocation\LocationPrecision;
use App\Models\{Bid, Booking, Category, Event, Package, Payout, PricingRule, Review, Shift, User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A demonstration professional's whole business, in one place.
 *
 * This was written inside a seeder, which is exactly where it could not be
 * used. Seeders run as a set, against everything, and refuse on production
 * for good reasons — a seeder was once run on the live database and left 47
 * duplicate rows. But a demonstration on the live site still needs an account
 * with a business behind it, and "post an event, bid on it, accept it" is
 * twenty minutes of clicking in front of whoever is watching.
 *
 * So the work lives here and two things drive it: the seeder, for a local
 * database, and `demo:business`, which takes ONE named account, says what it
 * would do before it does it, and can take all of it off again.
 *
 * Everything it writes carries the marker, so `remove()` takes exactly what
 * `build()` made and nothing of anybody else's.
 *
 * What it deliberately does NOT do unless asked: publish open requests. Those
 * are the only part of a demonstration business that other people can see —
 * they appear on the bidding board of every professional in the same state —
 * and on a live site that means real professionals reading invented work.
 */
final class DemoBusiness
{
    /** The marker that makes everything here findable again. */
    public const MARK = '[demo-business]';

    /**
     * How many jobs fall in each calendar month.
     *
     * A season, not a flat line. The reference screen reads "May to
     * September", and a chart of ones and twos would not show one.
     */
    public const SEASON = [
        1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 4, 6 => 5,
        7 => 4, 8 => 4, 9 => 3, 10 => 2, 11 => 1, 12 => 2,
    ];

    /** Event titles by month position, so two jobs in a month are not twins. */
    public const JOBS = [
        'Garden wedding reception',
        'Corporate anniversary dinner',
        'Charity gala evening',
        'Birthday celebration',
        'Product launch party',
        'School awards night',
        'Engagement party',
        'Retirement dinner',
    ];

    /**
     * Everything, for one professional.
     *
     * Returns what could not be done rather than throwing, because the two
     * callers want to say it differently: a seeder warns and moves to the
     * next account, the command stops and explains.
     */
    public function build(User $pro, User $client): ?string
    {
        $service = $this->service($pro);

        if (! $service) {
            return $pro->email.' lists no services, so there is nothing to book.';
        }

        $this->setup($pro, $service);
        $this->history($pro, $client, $service);
        $this->thisMonth($pro, $client, $service);
        $this->upcoming($pro, $client, $service);
        $this->today($pro, $client, $service);
        $this->money($pro);
        $this->reviews($pro, $client);
        $this->bids($pro);

        return null;
    }

    /** One service this professional actually offers, to book the work against. */
    public function service(User $pro): ?Category
    {
        return $pro->serviceCategories()
            ->where('categories.kind', Category::SERVICE)
            ->first();
    }

    /**
     * The four setup steps, finished.
     *
     * The reference screen opens on "Business Setup, 4 of 4 completed, All
     * set". Ours opens on "1 of 5 done" because the demo account has a list
     * of services and nothing else.
     */
    public function setup(User $pro, Category $service): void
    {
        // A price on the service they offer.
        if ($service->pivot?->price === null) {
            $pro->serviceCategories()->updateExistingPivot($service->id, ['price' => 850]);
        }

        // How far they travel, and what they are aiming at this month.
        PricingRule::updateOrCreate(
            ['user_id' => $pro->id],
            [
                'travel_free_miles'     => 25,
                'travel_rate_per_mile'  => 0.65,
                'travel_max_miles'      => 120,
                'monthly_revenue_goal'  => 20000,
            ]
        );

        // A published package.
        Package::firstOrCreate(
            ['user_id' => $pro->id, 'slug' => Str::slug($pro->name).'-signature-package'],
            [
                'category_id' => $service->id,
                'title'       => 'Signature event package',
                'description' => 'A full evening of coverage, set up and run by our own crew. '.self::MARK,
                'price'       => 2400,
                'price_unit'  => 'event',
                'state'       => $pro->profile?->state,
                'status'      => 'active',
                'is_active'   => true,
            ]
        );

        $profile = $pro->getOrCreateProfile();

        /*
         * Where they travel from, placed on the map.
         *
         * Without a resolved origin the Opportunity Feed cannot work out how
         * far a job is, so every card said "Set where you travel from" rather
         * than the distance and the radius tick the reference screen shows.
         * Baltimore's own coordinates, to the city, which is what a
         * professional who gives a city rather than a street gets.
         */
        $city = [
            'Baltimore'  => [39.2904, -76.6122],
            'Richmond'   => [37.5407, -77.4360],
            'Washington' => [38.9072, -77.0369],
        ][$profile->city ?? 'Baltimore'] ?? [39.2904, -76.6122];

        $profile->forceFill(array_filter([
            'identity_submitted_at' => $profile->identity_verified_at ? null : now()->subMonths(8),
            'identity_verified_at'  => $profile->identity_verified_at ? null : now()->subMonths(8)->addDays(2),
        ]) + [
            'service_origin_city'  => $profile->city ?? 'Baltimore',
            'service_origin_state' => $profile->state ?? 'MD',
            'origin_lat'           => $city[0],
            'origin_lng'           => $city[1],
            'origin_precision'     => \App\Domain\Geolocation\LocationPrecision::ZIP,
            'travel_radius_miles'  => $profile->travel_radius_miles ?: 120,
        ])->save();
    }

    /**
     * Twelve months of finished work, shaped into a season.
     *
     * Dated by the month the job happened in, because that is what "when your
     * work falls" asks. The current month is left out of this pass and built
     * separately, so the revenue goal measures only what this month has
     * actually earned.
     */
    public function history(User $pro, User $client, Category $service): void
    {
        $start = now()->startOfMonth()->subMonths(12);

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $count = self::SEASON[(int) $month->format('n')] ?? 1;

            for ($n = 0; $n < $count; $n++) {
                $day = $month->copy()->addDays(4 + $n * 6)->setTime(17, 0);

                $this->job(
                    $pro, $client, $service,
                    title: self::JOBS[($i + $n) % count(self::JOBS)],
                    starts: $day,
                    hours: 4,
                    price: 1250 + (($i * 7 + $n * 13) % 9) * 420,
                    status: 'completed',
                );
            }
        }
    }

    /** This month's work, which is what the revenue goal is measured against. */
    public function thisMonth(User $pro, User $client, Category $service): void
    {
        $month = now()->startOfMonth();

        $done = [
            ['Winter fundraiser evening', 3, 4200.0],
            ['Corporate holiday party', 9, 5100.0],
            ['Anniversary dinner', 15, 3800.0],
        ];

        foreach ($done as [$title, $day, $price]) {
            $at = $month->copy()->addDays($day - 1)->setTime(18, 0);

            // Only work that has already happened can be finished work.
            $this->job(
                $pro, $client, $service,
                title: $title,
                starts: $at,
                hours: 4,
                price: $price,
                status: $at->isPast() ? 'completed' : 'confirmed',
            );
        }
    }

    /**
     * Confirmed work still ahead, one piece of it short of crew.
     *
     * The unfilled shift is what puts "1 needs staff" under Upcoming gigs and
     * a row in Needs your attention, both of which the reference screen has.
     */
    public function upcoming(User $pro, User $client, Category $service): void
    {
        $ahead = [
            ['Riverside wedding reception', 6, 4600.0, true],
            ['Tech conference closing night', 13, 5400.0, false],
            ['Winter charity ball', 24, 6200.0, false],
        ];

        foreach ($ahead as [$title, $inDays, $price, $shortStaffed]) {
            $at = now()->addDays($inDays)->setTime(18, 30);

            $booking = $this->job(
                $pro, $client, $service,
                title: $title,
                starts: $at,
                hours: 5,
                price: $price,
                status: 'confirmed',
            );

            if ($shortStaffed && $booking->event_id) {
                Shift::firstOrCreate(
                    ['supplier_id' => $pro->id, 'event_id' => $booking->event_id, 'role' => 'Second operator'],
                    [
                        'location'  => $booking->event?->location,
                        'starts_at' => $at,
                        'ends_at'   => $at->copy()->addHours(5),
                        'slots'     => 1,
                        'status'    => 'open',
                    ]
                );
            }
        }
    }

    /**
     * Today, at three different times.
     *
     * Today's Schedule reads the clock, so these are built from the start of
     * today rather than from a fixed date, and the card fills whenever the
     * seeder was last run.
     */
    public function today(User $pro, User $client, Category $service): void
    {
        $today = Carbon::today(config('app.display_timezone', config('app.timezone')));

        $slots = [
            ['Hotel ballroom setup', 13, 2, 900.0],
            ['Corporate gala dinner', 16, 4, 5000.0],
            ['Late reception coverage', 21, 2, 1400.0],
        ];

        foreach ($slots as [$title, $hour, $hours, $price]) {
            $this->job(
                $pro, $client, $service,
                title: $title,
                starts: $today->copy()->setTime($hour, 0),
                hours: $hours,
                price: $price,
                status: 'confirmed',
            );
        }
    }

    /**
     * One payout already paid and one still requested.
     *
     * A requested payout is what "Payments waiting" counts, and both figures
     * come straight back out through App\Support\Earnings.
     *
     * The paid one is sized against what the year actually earned, not fixed.
     * Earnings computes the available balance as earned less paid less
     * requested, so a flat $3,200 against a year of work left ninety thousand
     * dollars sitting as withdrawable — a business that had never once been
     * paid. This leaves roughly a month's takings in the account, which is
     * what somebody who withdraws as they go would have.
     */
    public function money(User $pro): void
    {
        $earned = \App\Support\Earnings::forProfessional($pro)['earned'];
        $open   = 1850.0;
        $keep   = 4250.0;
        $paid   = max(0.0, round($earned - $keep - $open, 2));

        Payout::updateOrCreate(
            ['user_id' => $pro->id, 'reference' => 'DEMO-PAYOUT-PAID'],
            [
                'amount'       => $paid,
                'currency'     => 'USD',
                'method'       => 'bank_transfer',
                'status'       => 'paid',
                'note'         => 'Everything settled up to last month. '.self::MARK,
                'requested_at' => now()->subDays(40),
                'paid_at'      => now()->subDays(37),
            ]
        );

        Payout::updateOrCreate(
            ['user_id' => $pro->id, 'reference' => 'DEMO-PAYOUT-OPEN'],
            [
                'amount'       => $open,
                'currency'     => 'USD',
                'method'       => 'bank_transfer',
                'status'       => 'requested',
                'note'         => 'Waiting on review. '.self::MARK,
                'requested_at' => now()->subDays(2),
            ]
        );
    }

    /**
     * Two reviews, one of them unanswered.
     *
     * The unanswered one is a row in Needs your attention, and both together
     * give the profile a rating to show.
     */
    public function reviews(User $pro, User $client): void
    {
        $bookings = Booking::where('supplier_id', $pro->id)
            ->where('status', 'completed')
            ->latest('id')
            ->take(2)
            ->get();

        if ($bookings->count() < 2) {
            return;
        }

        Review::firstOrCreate(
            ['reviewer_id' => $client->id, 'reviewee_id' => $pro->id, 'booking_id' => $bookings[0]->id],
            [
                'rating'  => 5,
                'title'   => 'Ran the whole evening without us having to check',
                'comment' => 'Arrived early, set up quietly and kept to the running order. '.self::MARK,
            ]
        );

        Review::firstOrCreate(
            ['reviewer_id' => $client->id, 'reviewee_id' => $pro->id, 'booking_id' => $bookings[1]->id],
            [
                'rating'      => 4,
                'title'       => 'Good night, one slow start',
                'comment'     => 'Everything worked, though the first half hour was quieter than we wanted. '.self::MARK,
                'response'    => 'Thank you, we have moved the sound check earlier for the next one.',
                'response_at' => now()->subDays(20),
            ]
        );
    }

    /**
     * Bids at more than one stage.
     *
     * Bid Intelligence draws a ring of the professional's bids by status and
     * prints the same counts beside it. With every demo bid sitting at
     * "submitted" the ring was a single colour and the card said nothing a
     * number could not have said on its own.
     *
     * Each one is attached to work this professional actually did, so a bid
     * marked won has a booking behind it.
     */
    public function bids(User $pro): void
    {
        $bookings = Booking::where('supplier_id', $pro->id)
            ->whereNotNull('event_id')
            ->whereNotNull('category_id')
            ->with('event:id,starts_at')
            ->latest('id')
            ->take(9)
            ->get();

        if ($bookings->isEmpty()) {
            return;
        }

        foreach ($bookings as $i => $booking) {
            // Mostly won, a few still open, the odd one pulled: the shape of
            // somebody who gets most of what they quote for.
            $status = match (true) {
                $i < 5  => 'won',
                $i < 8  => 'submitted',
                default => 'withdrawn',
            };

            Bid::firstOrCreate(
                ['event_id' => $booking->event_id, 'supplier_id' => $pro->id],
                [
                    'category_id' => $booking->category_id,
                    'amount'      => $booking->price,
                    'note'        => 'Demo bid behind the dashboard. '.self::MARK,
                    'status'      => $status,
                    'is_public'   => true,
                    'submitted_at' => $booking->event?->starts_at?->copy()->subWeeks(5) ?? now()->subWeeks(5),
                ]
            );
        }
    }

    /**
     * One job: the event the client posted, and the booking that came of it.
     *
     * Matched on the title and the marker, so running this twice does not
     * leave a second copy of the same evening.
     */
    public function job(
        User $pro,
        User $client,
        Category $service,
        string $title,
        Carbon $starts,
        int $hours,
        float $price,
        string $status,
    ): Booking {
        $state = $pro->profile?->state ?? 'MD';
        $city  = $pro->profile?->city ?? 'Baltimore';
        $name  = $title.', '.$starts->format('M Y');

        $event = Event::where('client_id', $client->id)
            ->where('title', $name)
            ->where('description', 'like', '%'.self::MARK.'%')
            ->first();

        if (! $event) {
            $event = Event::create([
                'title'        => $name,
                'description'  => 'Demo booking behind the professional dashboard. '.self::MARK,
                'client_id'    => $client->id,
                'created_by'   => $client->id,
                'supplier_id'  => $pro->id,
                'category_id'  => $service->id,
                'status'       => $starts->isPast() ? 'completed' : 'pending',
                /*
                 * Never published, and this is not a detail.
                 *
                 * Publishing a request is an EVENT on the live platform, not
                 * a flag. Event::created fires announcePublication the moment
                 * published_at is set, which records a posting fee on the
                 * client's money ledger and emails them that their request is
                 * live; setting is_published later fires EventNotifier and
                 * mails every professional whose services match. A first run
                 * of this on production charged forty posting fees and died
                 * on an SMTP 550 halfway through, because the demonstration
                 * client's address does not receive mail.
                 *
                 * None of that is wanted here and none of it is needed. These
                 * are jobs already awarded to this professional, not requests
                 * looking for a bid, so they never had a public life. Left
                 * unpublished they also cannot reach the bidding board, the
                 * Opportunity Feed or anybody's search, which is what makes
                 * the whole thing safe to run on a live site.
                 *
                 * The dashboard reads bookings and their dates. It has never
                 * asked whether the request behind one was ever advertised.
                 */
                'is_published' => false,
                'published_at' => null,
                'starts_at'    => $starts,
                'ends_at'      => $starts->copy()->addHours($hours),
                'location'     => $city.', '.$state,
                'city'         => $city,
                'state'        => $state,
                'budget'       => $price,
            ]);

            $event->categories()->syncWithoutDetaching([$service->id]);
        }

        return Booking::firstOrCreate(
            ['event_id' => $event->id, 'supplier_id' => $pro->id],
            [
                'client_id'   => $client->id,
                'category_id' => $service->id,
                'created_by'  => $client->id,
                'status'      => $status,
                'price'       => $price,
                'currency'    => 'USD',
                'source'      => 'demo',
                'booked_at'   => $starts->copy()->subWeeks(4),
                'created_at'  => $starts->copy()->subWeeks(4),
            ]
        );
    }

    /**
     * Take back exactly what build() made.
     *
     * Scoped to this class's own marker, never to a pattern that happens to
     * look similar. A first pass also swept the open requests another seeder
     * writes, and the bids on them, which meant removing a demonstration
     * business could delete a bid somebody had really placed.
     *
     * Settings are left alone and named by the caller, because a travel
     * radius or a revenue goal is a preference somebody may have meant to
     * keep, and deleting a preference is not the same as deleting data.
     *
     * @return array<string, int>
     */
    public function remove(User $pro): array
    {
        $events = Event::where('description', 'like', '%'.self::MARK.'%')
            ->where('supplier_id', $pro->id)
            ->pluck('id');

        return [
            'reviews'  => Review::where('reviewee_id', $pro->id)
                ->where('comment', 'like', '%'.self::MARK.'%')->delete(),
            'bids'     => Bid::where('supplier_id', $pro->id)
                ->where('note', 'like', '%'.self::MARK.'%')->delete(),
            'shifts'   => Shift::where('supplier_id', $pro->id)
                ->whereIn('event_id', $events)->delete(),
            'bookings' => Booking::where('supplier_id', $pro->id)
                ->whereIn('event_id', $events)->delete(),
            'payouts'  => Payout::where('user_id', $pro->id)
                ->where('note', 'like', '%'.self::MARK.'%')->delete(),
            'packages' => Package::where('user_id', $pro->id)
                ->where('description', 'like', '%'.self::MARK.'%')->delete(),
            /*
             * Posting fees an earlier version of this caused.
             *
             * It used to create these events published, and publishing
             * records a platform fee on the client's ledger. A ledger is
             * append-only for real money; a fee for a demonstration request
             * that is about to stop existing is not real money, and leaving
             * it would show the client charges for events they no longer
             * have. Scoped to these events, so nothing else on the ledger is
             * touched.
             */
            'ledger'   => \App\Models\LedgerEntry::whereIn('event_id', $events)->delete(),
            'events'   => Event::whereIn('id', $events)->delete(),
        ];
    }

    /** What build() would write, without writing any of it. */
    public function plan(User $pro): array
    {
        $season = array_sum(self::SEASON);

        return [
            'finished jobs across twelve months' => $season,
            'jobs this month'                    => 3,
            'jobs still ahead'                   => 3,
            'jobs today'                         => 3,
            'payouts'                            => 2,
            'reviews'                            => 2,
            'bids'                               => 9,
            'packages'                           => 1,
        ];
    }
}
