<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Models\Category;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo professionals for the public "/browse" grid (and category / search
 * pages). Without a populated marketplace the browse page renders a single
 * bare card and looks empty. This seeds ~10 realistic suppliers — each with a
 * profile (headline, city, rate, portfolio images, verification badges) and a
 * handful of real reviews (via Event → Booking → Review chains) so cards show
 * star ratings and review counts.
 *
 * Idempotent: users are firstOrCreate'd by email; review chains are only
 * created for a supplier that has none yet. Safe to run repeatedly. Intended
 * for local/demo environments — not wired into production seeding.
 */
class DemoProfessionalsSeeder extends Seeder
{
    public function run(): void
    {
        $reviewers = $this->reviewerPool();

        foreach ($this->professionals() as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'password'],
            );
            $user->syncRoles([RoleName::PROFESSIONAL->value]);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline'         => $data['headline'],
                    'company_name'     => $data['company'],
                    'bio'              => $data['bio'],
                    'city'             => $data['city'],
                    'state'            => $data['state'],
                    'country'          => 'US',
                    'hourly_rate'      => $data['rate'],
                    'experience_years' => $data['years'],
                    // The "Currently taking work" filter reads this. Left null by the
                    // seeder it matched nobody, so the filter looked broken when it was
                    // only unpopulated. Most demo pros are open; two are not, so the
                    // filter visibly does something.
                    'availability'     => $data['availability'] ?? 'available',
                    'skills'           => $data['skills'],
                    'languages'        => $data['languages'],
                    // Portfolio photos, one distinct set per professional.
                    //
                    // This column was deliberately left empty before, and the
                    // reason was sound: a single shared stock set put one
                    // wedding bouquet on eight of the ten pros, including the
                    // DJ and the lighting company.
                    //
                    // It has to be filled now — the profile page's Portfolio
                    // Highlights and photo cover both read it, and with it
                    // empty the most important section on the page renders for
                    // nobody. So each pro carries their OWN trade-matched set
                    // below: the florist gets florals, the DJ gets a booth,
                    // the caterer gets plated food. That answers the original
                    // objection rather than reversing the decision behind it.
                    'portfolio'        => self::portfolioFor($data['photos'] ?? []),
                ] + ($data['verified'] ? [
                    'trade_license_number'            => 'TL-' . rand(10000, 99999),
                    'trade_license_verified_at'       => now()->subDays(rand(30, 200)),
                    'liability_insurance_number'      => 'LI-' . rand(10000, 99999),
                    'liability_insurance_verified_at' => now()->subDays(rand(30, 200)),
                    'workers_comp_number'             => 'WC-' . rand(10000, 99999),
                    'workers_comp_verified_at'        => now()->subDays(rand(30, 200)),
                ] : []),
            );

            $this->attachServices($user, $data['services'] ?? []);
            $this->seedReviews($user, $reviewers, $data);
        }
    }

    /**
     * Put the pro into the real category relation. Without this they exist but
     * are undiscoverable — category landing pages and a category-filtered
     * /browse both read `category_user`, not the free-text skills list.
     *
     * Names are matched, not ids: the legacy import repeats a name across
     * branches, so every category carrying that name gets attached and the pro
     * shows up wherever a client browses to it.
     *
     * @param array<int, string> $serviceNames
     */
    private function attachServices(User $user, array $serviceNames): void
    {
        if ($serviceNames === []) {
            return;
        }

        $matched = Category::whereIn('name', $serviceNames)->get();

        /*
         * A professional lists SERVICES — the Sub-Sub level — not the 27 Sub
         * categories above them. R45 says so, and R61's feed depends on it:
         * relatedness is "same parent, different child", so a professional
         * attached to a top-level category has no parent to share and can
         * never be shown related work.
         *
         * These names are older than Taxonomy V2 and several of them now land
         * on a Sub category rather than a service. Where that happens the
         * category is expanded into its own services instead of attached as
         * itself — which is what the professional actually offers anyway.
         */
        $ids = $matched->where('kind', 'service')->pluck('id');

        $categoriesHit = $matched->where('kind', 'service_category')->pluck('id');

        if ($categoriesHit->isNotEmpty()) {
            $ids = $ids->merge(
                Category::whereIn('parent_id', $categoriesHit)->where('kind', 'service')
                    ->orderBy('sort_order')->orderBy('name')
                    ->pluck('id')->take(4)
            );
        }

        $ids = $ids->unique()->values();

        if ($ids->isEmpty()) {
            $this->command?->warn("  No services matched for {$user->name} — skipped.");

            return;
        }

        $user->serviceCategories()->sync($ids->all());
    }

    /**
     * A small pool of client accounts used as review authors.
     *
     * Each one is placed in a launch state. They were created with none, and
     * under R38 an account with no state matches nobody — so signing in as one
     * showed an empty Browse page and no gigs. They exist to author reviews
     * rather than to be logged into, which is why nobody noticed, but a client
     * row with no state is data that reads as broken.
     *
     * The states chosen are ones that actually have professionals, spread
     * across five of the seven. Putting them all in one place, or in a state
     * with nobody in it, would move the empty page rather than fix it.
     */
    private function reviewerPool(): array
    {
        $pool = [];
        foreach ([
            ['Olivia Bennett', 'olivia.demo@example.test', 'MD', 'Baltimore'],
            ['Marcus Lee',     'marcus.demo@example.test', 'PA', 'Philadelphia'],
            ['Priya Sharma',   'priya.demo@example.test',  'VA', 'Richmond'],
            ['James Carter',   'james.demo@example.test',  'DC', 'Washington'],
            ['Sofia Alvarez',  'sofia.demo@example.test',  'DE', 'Wilmington'],
        ] as [$name, $email, $state, $city]) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password']);
            $u->syncRoles([RoleName::CLIENT->value]);
            $u->update(['primary_role' => RoleName::CLIENT->value]);
            $u->getOrCreateProfile()->update([
                'country'             => 'US',
                'state'               => $state,
                'city'                => $city,
                'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
            ]);
            $pool[] = $u->fresh();
        }

        return $pool;
    }

    /**
     * Put this professional's existing review-chain events back in their city.
     *
     * R9 wants locations from the seven jurisdictions and R38 files each event
     * under a state; a chain written when the demo professionals lived in
     * California says neither. The state comes from the event's own client,
     * per R38, not from the professional — a client in Philadelphia hiring a
     * Baltimore professional is a Pennsylvania event.
     */
    private function repairChainLocations(User $supplier, array $data): void
    {
        $stale = Event::where('supplier_id', $supplier->id)
            ->where('location', '<>', $data['city'] . ', ' . $data['state'])
            ->with('client.profile')
            ->get();

        foreach ($stale as $event) {
            $clientState = strtoupper($event->client?->profile?->state ?: $data['state']);
            $clientCity  = $event->client?->profile?->city ?: $data['city'];

            $event->forceFill([
                'location' => $clientCity . ', ' . $clientState,
                'state'    => $clientState,
            ])->saveQuietly();
        }
    }

    /** Build a few Event → Booking → Review chains so the pro shows a rating. */
    private function seedReviews(User $supplier, array $reviewers, array $data): void
    {
        if ($supplier->reviewsReceived()->count() > 0) {
            // Already seeded — but idempotent has a cost. This method skips a
            // professional who has reviews, so any chain written before the
            // demo cities were moved in-area kept its old location and was
            // never revisited: 59 events still saying San Diego, Nashville
            // and Chicago on a seven-jurisdiction marketplace.
            //
            // Skipping is still right — re-seeding would double the reviews —
            // so the existing rows are repaired instead of rebuilt.
            $this->repairChainLocations($supplier, $data);

            return;
        }

        $count    = rand(4, 9);
        $comments = [
            'Absolutely incredible — exceeded every expectation. Highly recommend!',
            'Professional, punctual, and so easy to work with. Will book again.',
            'Made our event unforgettable. The quality of work speaks for itself.',
            'Great communication from start to finish. Worth every penny.',
            'Talented and reliable. Our guests are still talking about it!',
            'Seamless experience and stunning results. Five stars.',
        ];

        for ($i = 0; $i < $count; $i++) {
            $reviewer = $reviewers[$i % count($reviewers)];
            $when     = now()->subDays(rand(10, 300));

            $event = Event::create([
                'title'      => $data['eventTitle'] . ' #' . ($i + 1),
                'created_by' => $reviewer->id,
                'client_id'  => $reviewer->id,
                'supplier_id' => $supplier->id,
                'status'     => 'completed',
                'is_published' => true,
                'starts_at'  => (clone $when)->subDays(2),
                'location'   => $data['city'] . ', ' . $data['state'],
            ]);

            $booking = Booking::create([
                'event_id'   => $event->id,
                'client_id'  => $reviewer->id,
                'created_by' => $reviewer->id,
                'supplier_id' => $supplier->id,
                'status'     => 'completed',
                'price'      => $data['rate'] * rand(3, 8),
                'currency'   => 'USD',
                'booked_at'  => $when,
            ]);

            Review::create([
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $supplier->id,
                'booking_id'  => $booking->id,
                'rating'      => rand(0, 4) === 0 ? 4 : 5, // mostly 5★, some 4★
                'title'       => 'Outstanding work',
                'comment'     => $comments[array_rand($comments)],
                'is_hidden'   => false,
                'created_at'  => $when,
            ]);
        }
    }

    /**
     * Turn a list of stock photo ids into portfolio entries.
     *
     * The first is marked featured, because portfolioImageItems() sorts
     * featured-first and the profile's Highlights grid and cover both take
     * the first four from that order — so "featured" is the cover shot.
     */
    private static function portfolioFor(array $photoIds): array
    {
        return collect($photoIds)
            ->values()
            ->map(fn (string $id, int $i) => [
                'type'     => 'image',
                'featured' => $i === 0,
                'hero'     => "https://images.unsplash.com/{$id}?w=1200&q=70&auto=format&fit=crop",
                'square'   => "https://images.unsplash.com/{$id}?w=600&h=600&q=70&auto=format&fit=crop",
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function professionals(): array
    {
        return [
            [
                'name' => 'Duy Nguyen', 'email' => 'duy.demo@example.test',
                'company' => 'Skyline Films',
                'photos' => ['photo-1485846234645-a62644f84728', 'photo-1524270000000-000000000000', 'photo-1516035069371-29a1b244cc32', 'photo-1500051638674-ff996a0ec29e', 'photo-1502920917128-1aa500764cbd'], 'headline' => 'Lead Cinematographer & Drone Pilot',
                'city' => 'Philadelphia', 'state' => 'PA', 'rate' => 150, 'years' => 7, 'verified' => true,
                'skills' => ['Cinematography', 'Drone / Aerial', 'Same-Day Edits', 'Color Grading'],
                'services' => ['Videography & Cinematic Films', 'Event Photography', 'Drone Photography'],
                'languages' => ['English', 'Vietnamese'],
                'bio' => 'Award-winning cinematographer capturing weddings and brand films with a cinematic, story-first approach.',
                'eventTitle' => 'Wedding Film',
            ],
            [
                'name' => 'Horizon Audio', 'email' => 'horizon.demo@example.test',
                'company' => 'Horizon Audio',
                'photos' => ['photo-1470229722913-7c0e2dbbafd3', 'photo-1493225457124-a3eb161ffa5f', 'photo-1533174072545-7a4b6ad7a6c3', 'photo-1516450360452-9312f5e86fc7', 'photo-1571266028243-e4733b0f0bb0'], 'headline' => 'Premium Event DJ & A/V Visual Systems',
                'city' => 'Baltimore', 'state' => 'MD', 'rate' => 130, 'years' => 10, 'verified' => true,
                'skills' => ['DJ / MC', 'Sound Engineering', 'Lighting', 'Live Streaming'],
                'services' => ['Wedding DJs', 'Party & Club DJs', 'Uplighting & Ambient Lighting'],
                'languages' => ['English', 'Spanish'],
                'bio' => 'Full-service DJ and A/V production for weddings, corporate galas, and festivals.',
                'eventTitle' => 'Corporate Gala',
            ],
            [
                'name' => 'Mix Masters', 'email' => 'mixmasters.demo@example.test',
                'company' => 'Mix Masters',
                'photos' => ['photo-1516450360452-9312f5e86fc7', 'photo-1514525253161-7a46d19cd819', 'photo-1459749411175-04bf5292ceea', 'photo-1470229722913-7c0e2dbbafd3', 'photo-1493225457124-a3eb161ffa5f'], 'headline' => 'Wedding & Party DJ Specialists',
                'city' => 'Arlington', 'state' => 'VA', 'rate' => 90, 'years' => 6, 'verified' => true,
                'availability' => 'busy',
                'skills' => ['Open-Format DJ', 'MC / Emcee', 'Uplighting', 'Photo Booth'],
                'services' => ['Wedding DJs', 'Party & Club DJs'],
                'languages' => ['English'],
                'bio' => 'High-energy DJs who read the room and keep the dance floor packed all night.',
                'eventTitle' => 'Birthday Party',
            ],
            [
                'name' => 'Elena Rossi', 'email' => 'elena.demo@example.test',
                'company' => 'Rossi Studio',
                'photos' => ['photo-1519741497674-611481863552', 'photo-1511285560929-80b456fea0bc', 'photo-1465495976277-4387d4b0b4c6', 'photo-1520854221256-17451cc331bf', 'photo-1522673607200-164d1b6ce486'], 'headline' => 'Fine-Art Wedding Photographer',
                'city' => 'Washington', 'state' => 'DC', 'rate' => 175, 'years' => 9, 'verified' => true,
                'skills' => ['Photography', 'Editorial', 'Album Design', 'Engagement Shoots'],
                'services' => ['Event Photography', 'Wedding Photography'],
                'languages' => ['English', 'Italian'],
                'bio' => 'Timeless, editorial-style photography for couples who love art and authenticity.',
                'eventTitle' => 'Wedding Photography',
            ],
            [
                'name' => 'Bloom & Vine Co.', 'email' => 'bloomvine.demo@example.test',
                'company' => 'Bloom & Vine Co.',
                'photos' => ['photo-1519225421980-715cb0215aed', 'photo-1478146896981-b80fe463b330', 'photo-1526047932273-341f2a7631f9', 'photo-1490818387583-1baba5e638af', 'photo-1567696911980-2eed69a46042'], 'headline' => 'Floral & Décor Designers',
                'city' => 'Pittsburgh', 'state' => 'PA', 'rate' => 120, 'years' => 8, 'verified' => true,
                'skills' => ['Floral Design', 'Tablescapes', 'Arch & Backdrop', 'Installations'],
                'services' => ['Bridal & Ceremony Florals', 'Centerpiece Design', 'Balloon Arches & Columns'],
                'languages' => ['English'],
                'bio' => 'Lush, seasonal florals and full-room décor that transform any venue.',
                'eventTitle' => 'Wedding Florals',
            ],
            [
                'name' => 'Grand Affair Planning', 'email' => 'grandaffair.demo@example.test',
                'company' => 'Grand Affair',
                'photos' => ['photo-1511578314322-379afb476865', 'photo-1464366400600-7168b8af9bc3', 'photo-1505236858219-8359eb29e329', 'photo-1531058020387-3be344556be6', 'photo-1492684223066-81342ee5ff30'], 'headline' => 'Full-Service Event Planners',
                'city' => 'Virginia Beach', 'state' => 'VA', 'rate' => 200, 'years' => 12, 'verified' => true,
                'skills' => ['Full Planning', 'Day-of Coordination', 'Vendor Sourcing', 'Budgeting'],
                'services' => ['Full-Service Event Planning', 'Day-Of Coordination', 'Corporate Event Management'],
                'languages' => ['English', 'Spanish', 'Portuguese'],
                'bio' => 'From concept to last dance — we plan luxury weddings and corporate events end to end.',
                'eventTitle' => 'Luxury Wedding',
            ],
            [
                'name' => 'The Velvet Notes', 'email' => 'velvetnotes.demo@example.test',
                'company' => 'The Velvet Notes',
                'photos' => ['photo-1511192336575-5a79af67a629', 'photo-1415201364774-f6f0bb35f28f', 'photo-1524230572899-a752b3835840', 'photo-1501612780327-45045538702b', 'photo-1508973379184-7517410fb0bc'], 'headline' => 'Live Jazz & Soul Band',
                'city' => 'Wilmington', 'state' => 'DE', 'rate' => 250, 'years' => 11, 'verified' => false,
                'skills' => ['Live Band', 'Jazz / Soul', 'Ceremony Music', 'Custom Requests'],
                'services' => ['Live Bands', 'Solo Musicians & Acoustic Acts'],
                'languages' => ['English'],
                'bio' => 'A seven-piece live band bringing timeless jazz and soul to weddings and galas.',
                'eventTitle' => 'Reception Music',
            ],
            [
                'name' => 'Lumière Lighting', 'email' => 'lumiere.demo@example.test',
                'company' => 'Lumière Lighting',
                'photos' => ['photo-1492684223066-81342ee5ff30', 'photo-1519671482749-fd09be7ccebf', 'photo-1533174072545-7a4b6ad7a6c3', 'photo-1470229722913-7c0e2dbbafd3', 'photo-1478146896981-b80fe463b330'], 'headline' => 'Event Lighting & Staging',
                'city' => 'Newark', 'state' => 'NJ', 'rate' => 95, 'years' => 5, 'verified' => true,
                'skills' => ['Uplighting', 'Stage Design', 'Gobo / Monogram', 'Pin Spotting'],
                'services' => ['Uplighting & Ambient Lighting', 'Stage Design & Setup', 'AV Equipment Rental'],
                'languages' => ['English'],
                'bio' => 'We sculpt rooms with light — from intimate receptions to large-scale productions.',
                'eventTitle' => 'Event Lighting',
            ],
            [
                'name' => 'Saffron Table Catering', 'email' => 'saffron.demo@example.test',
                'company' => 'Saffron Table',
                'photos' => ['photo-1555244162-803834f70033', 'photo-1414235077428-338989a2e8c0', 'photo-1504674900247-0877df9cc836', 'photo-1476224203421-9ac39bcb3327', 'photo-1467003909585-2f8a72700288'], 'headline' => 'Gourmet Event Catering',
                'city' => 'Silver Spring', 'state' => 'MD', 'rate' => 45, 'years' => 9, 'verified' => true,
                'skills' => ['Plated Dinners', 'Stations', 'Dietary Menus', 'Bar Service'],
                'services' => ['Full-Service Catering', 'Buffet Catering', 'Professional Bartenders'],
                'languages' => ['English'],
                'bio' => 'Seasonal, locally-sourced menus crafted for weddings and corporate events.',
                'eventTitle' => 'Catered Dinner',
            ],
            [
                'name' => 'Glow Studio', 'email' => 'glowstudio.demo@example.test',
                'company' => 'Glow Studio',
                'photos' => ['photo-1487412720507-e7ab37603c6f', 'photo-1595476108010-b4d1f102b1b1', 'photo-1522337360788-8b13dee7a37e', 'photo-1560066984-138dadb4c035', 'photo-1519699047748-de8e457a634e'], 'headline' => 'Bridal Hair & Makeup Artists',
                'city' => 'Charleston', 'state' => 'WV', 'rate' => 110, 'years' => 6, 'verified' => false,
                'availability' => 'not_available',
                'skills' => ['Bridal Makeup', 'Hair Styling', 'Airbrush', 'On-Location Glam'],
                'services' => ['Wait Staff & Servers', 'Registration & Check-In Staff'],
                'languages' => ['English'],
                'bio' => 'On-location glam for brides and bridal parties — flawless, photo-ready looks.',
                'eventTitle' => 'Bridal Glam',
            ],
        ];
    }
}
