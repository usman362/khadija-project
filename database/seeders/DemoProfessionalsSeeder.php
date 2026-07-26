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
    private const IMG = 'https://images.unsplash.com/';

    public function run(): void
    {
        $reviewers = $this->reviewerPool();

        foreach ($this->professionals() as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'password'],
            );
            $user->syncRoles([RoleName::SUPPLIER->value]);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline'         => $data['headline'],
                    'company_name'     => $data['company'],
                    'bio'              => $data['bio'],
                    'city'             => $data['city'],
                    'state'            => $data['state'],
                    'country'          => 'United States',
                    'hourly_rate'      => $data['rate'],
                    'experience_years' => $data['years'],
                    'skills'           => $data['skills'],
                    'languages'        => $data['languages'],
                    'portfolio'        => array_map(fn ($id) => self::IMG . $id . '?w=700&q=75&auto=format&fit=crop', $data['portfolio']),
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

        $ids = Category::whereIn('name', $serviceNames)->pluck('id')->all();

        if ($ids === []) {
            $this->command?->warn("  No categories matched for {$user->name} — skipped.");

            return;
        }

        $user->serviceCategories()->sync($ids);
    }

    /** A small pool of client accounts used as review authors. */
    private function reviewerPool(): array
    {
        $pool = [];
        foreach ([
            ['Olivia Bennett', 'olivia.demo@example.test'],
            ['Marcus Lee', 'marcus.demo@example.test'],
            ['Priya Sharma', 'priya.demo@example.test'],
            ['James Carter', 'james.demo@example.test'],
            ['Sofia Alvarez', 'sofia.demo@example.test'],
        ] as [$name, $email]) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password']);
            $u->syncRoles([RoleName::CLIENT->value]);
            $pool[] = $u;
        }

        return $pool;
    }

    /** Build a few Event → Booking → Review chains so the pro shows a rating. */
    private function seedReviews(User $supplier, array $reviewers, array $data): void
    {
        if ($supplier->reviewsReceived()->count() > 0) {
            return; // already seeded
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

    /** @return array<int, array<string, mixed>> */
    private function professionals(): array
    {
        return [
            [
                'name' => 'Duy Nguyen', 'email' => 'duy.demo@example.test',
                'company' => 'Skyline Films', 'headline' => 'Lead Cinematographer & Drone Pilot',
                'city' => 'Los Angeles', 'state' => 'CA', 'rate' => 150, 'years' => 7, 'verified' => true,
                'skills' => ['Cinematography', 'Drone / Aerial', 'Same-Day Edits', 'Color Grading'],
                'services' => ['Event Videography', 'Videography Services', 'Photography & Videography', 'Event Photography'],
                'languages' => ['English', 'Vietnamese'],
                'portfolio' => ['photo-1519741497674-611481863552', 'photo-1606800052052-a08af7148866', 'photo-1606800052052-a08af7148866', 'photo-1511578314322-379afb476865'],
                'bio' => 'Award-winning cinematographer capturing weddings and brand films with a cinematic, story-first approach.',
                'eventTitle' => 'Wedding Film',
            ],
            [
                'name' => 'Horizon Audio', 'email' => 'horizon.demo@example.test',
                'company' => 'Horizon Audio', 'headline' => 'Premium Event DJ & A/V Visual Systems',
                'city' => 'Austin', 'state' => 'TX', 'rate' => 130, 'years' => 10, 'verified' => true,
                'skills' => ['DJ / MC', 'Sound Engineering', 'Lighting', 'Live Streaming'],
                'services' => ['DJs & Sound Services', 'Lighting & Stage Setup', 'Music & Entertainment'],
                'languages' => ['English', 'Spanish'],
                'portfolio' => ['photo-1519741497674-611481863552', 'photo-1511578314322-379afb476865', 'photo-1511578314322-379afb476865'],
                'bio' => 'Full-service DJ and A/V production for weddings, corporate galas, and festivals.',
                'eventTitle' => 'Corporate Gala',
            ],
            [
                'name' => 'Mix Masters', 'email' => 'mixmasters.demo@example.test',
                'company' => 'Mix Masters', 'headline' => 'Wedding & Party DJ Specialists',
                'city' => 'San Diego', 'state' => 'CA', 'rate' => 90, 'years' => 6, 'verified' => true,
                'skills' => ['Open-Format DJ', 'MC / Emcee', 'Uplighting', 'Photo Booth'],
                'services' => ['DJs & Sound Services', 'Music & Entertainment', 'Photo Booths'],
                'languages' => ['English'],
                'portfolio' => ['photo-1511578314322-379afb476865', 'photo-1519741497674-611481863552', 'photo-1492684223066-81342ee5ff30'],
                'bio' => 'High-energy DJs who read the room and keep the dance floor packed all night.',
                'eventTitle' => 'Birthday Party',
            ],
            [
                'name' => 'Elena Rossi', 'email' => 'elena.demo@example.test',
                'company' => 'Rossi Studio', 'headline' => 'Fine-Art Wedding Photographer',
                'city' => 'New York', 'state' => 'NY', 'rate' => 175, 'years' => 9, 'verified' => true,
                'skills' => ['Photography', 'Editorial', 'Album Design', 'Engagement Shoots'],
                'services' => ['Event Photography', 'Photography Services', 'Photography & Videography'],
                'languages' => ['English', 'Italian'],
                'portfolio' => ['photo-1606800052052-a08af7148866', 'photo-1606800052052-a08af7148866', 'photo-1519741497674-611481863552'],
                'bio' => 'Timeless, editorial-style photography for couples who love art and authenticity.',
                'eventTitle' => 'Wedding Photography',
            ],
            [
                'name' => 'Bloom & Vine Co.', 'email' => 'bloomvine.demo@example.test',
                'company' => 'Bloom & Vine Co.', 'headline' => 'Floral & Décor Designers',
                'city' => 'Chicago', 'state' => 'IL', 'rate' => 120, 'years' => 8, 'verified' => true,
                'skills' => ['Floral Design', 'Tablescapes', 'Arch & Backdrop', 'Installations'],
                'services' => ['Floral Design & Arrangements', 'Floral Arrangements', 'Decor & Floral Services', 'Event Decor & Styling'],
                'languages' => ['English'],
                'portfolio' => ['photo-1465495976277-4387d4b0b4c6', 'photo-1469371670807-013ccf25f16a', 'photo-1519741497674-611481863552'],
                'bio' => 'Lush, seasonal florals and full-room décor that transform any venue.',
                'eventTitle' => 'Wedding Florals',
            ],
            [
                'name' => 'Grand Affair Planning', 'email' => 'grandaffair.demo@example.test',
                'company' => 'Grand Affair', 'headline' => 'Full-Service Event Planners',
                'city' => 'Miami', 'state' => 'FL', 'rate' => 200, 'years' => 12, 'verified' => true,
                'skills' => ['Full Planning', 'Day-of Coordination', 'Vendor Sourcing', 'Budgeting'],
                'services' => ['Event Planning & Production', 'Planning, Coordination & Management', 'Event Coordination'],
                'languages' => ['English', 'Spanish', 'Portuguese'],
                'portfolio' => ['photo-1505373877841-8d25f7d46678', 'photo-1511578314322-379afb476865', 'photo-1465495976277-4387d4b0b4c6'],
                'bio' => 'From concept to last dance — we plan luxury weddings and corporate events end to end.',
                'eventTitle' => 'Luxury Wedding',
            ],
            [
                'name' => 'The Velvet Notes', 'email' => 'velvetnotes.demo@example.test',
                'company' => 'The Velvet Notes', 'headline' => 'Live Jazz & Soul Band',
                'city' => 'Nashville', 'state' => 'TN', 'rate' => 250, 'years' => 11, 'verified' => false,
                'skills' => ['Live Band', 'Jazz / Soul', 'Ceremony Music', 'Custom Requests'],
                'services' => ['Live Bands & Musical Acts', 'Solo Musicians & Vocalists', 'Music & Entertainment'],
                'languages' => ['English'],
                'portfolio' => ['photo-1511578314322-379afb476865', 'photo-1519741497674-611481863552', 'photo-1511578314322-379afb476865'],
                'bio' => 'A seven-piece live band bringing timeless jazz and soul to weddings and galas.',
                'eventTitle' => 'Reception Music',
            ],
            [
                'name' => 'Lumière Lighting', 'email' => 'lumiere.demo@example.test',
                'company' => 'Lumière Lighting', 'headline' => 'Event Lighting & Staging',
                'city' => 'Las Vegas', 'state' => 'NV', 'rate' => 95, 'years' => 5, 'verified' => true,
                'skills' => ['Uplighting', 'Stage Design', 'Gobo / Monogram', 'Pin Spotting'],
                'services' => ['Lighting Services', 'Lighting & Stage Setup', 'Spotlight & Stage Lighting'],
                'languages' => ['English'],
                'portfolio' => ['photo-1492684223066-81342ee5ff30', 'photo-1511578314322-379afb476865', 'photo-1519741497674-611481863552'],
                'bio' => 'We sculpt rooms with light — from intimate receptions to large-scale productions.',
                'eventTitle' => 'Event Lighting',
            ],
            [
                'name' => 'Saffron Table Catering', 'email' => 'saffron.demo@example.test',
                'company' => 'Saffron Table', 'headline' => 'Gourmet Event Catering',
                'city' => 'Seattle', 'state' => 'WA', 'rate' => 45, 'years' => 9, 'verified' => true,
                'skills' => ['Plated Dinners', 'Stations', 'Dietary Menus', 'Bar Service'],
                'services' => ['Catering Services', 'Catering Coordination', 'Food Services', 'Beverage Services'],
                'languages' => ['English'],
                'portfolio' => ['photo-1414235077428-338989a2e8c0', 'photo-1555244162-803834f70033', 'photo-1465495976277-4387d4b0b4c6'],
                'bio' => 'Seasonal, locally-sourced menus crafted for weddings and corporate events.',
                'eventTitle' => 'Catered Dinner',
            ],
            [
                'name' => 'Glow Studio', 'email' => 'glowstudio.demo@example.test',
                'company' => 'Glow Studio', 'headline' => 'Bridal Hair & Makeup Artists',
                'city' => 'Portland', 'state' => 'OR', 'rate' => 110, 'years' => 6, 'verified' => false,
                'skills' => ['Bridal Makeup', 'Hair Styling', 'Airbrush', 'On-Location Glam'],
                'services' => ['Guest & Attendee Experience', 'Event Staffing'],
                'languages' => ['English'],
                'portfolio' => ['photo-1465495976277-4387d4b0b4c6', 'photo-1606800052052-a08af7148866', 'photo-1519741497674-611481863552'],
                'bio' => 'On-location glam for brides and bridal parties — flawless, photo-ready looks.',
                'eventTitle' => 'Bridal Glam',
            ],
        ];
    }
}
