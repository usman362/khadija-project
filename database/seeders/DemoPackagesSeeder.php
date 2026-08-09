<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo professional packages for the public "Shop Packages" catalogue
 * (/packages) and the client "Browse Packages" grid. Without published
 * packages those pages render an empty state. This seeds ~10 realistic,
 * ready-to-book packages spread across the demo suppliers and real
 * categories, each with a fixed scope, price and "what's included" list.
 *
 * Images are intentionally left empty — the Shop grid + detail page fall
 * back to representative stock imagery, so no file processing is needed.
 *
 * Idempotent: packages are firstOrCreate'd by slug. Safe to run repeatedly.
 * Local/demo only — not wired into production seeding.
 */
class DemoPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = User::whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->orderBy('id')->get();

        if ($suppliers->isEmpty()) {
            $this->command?->warn('No suppliers found — run DemoProfessionalsSeeder first.');
            return;
        }

        $byEmail = $suppliers->keyBy('email');

        foreach ($this->packages() as $i => $data) {
            /*
             * Owned by a professional who actually does this work.
             *
             * This was round-robin — `$suppliers[$i % count]` — which handed
             * "Corporate Conference — Catering & AV" to a wedding
             * photographer, "Full-Service Wedding Planning" to a florist and
             * a DJ package to a live jazz band. On /browse-packages nobody
             * looked at the owner. On the professional's own profile page the
             * packages sit under their name, and a caterer's package on a
             * photographer's profile is the first thing anyone notices.
             *
             * Named per package rather than matched by keyword: a fuzzy match
             * would put this back one renamed headline from now.
             */
            $supplier = $byEmail[$data['owner']] ?? $suppliers[$i % $suppliers->count()];
            $category = $this->resolveCategory($data['category']);

            $package = Package::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'user_id'        => $supplier->id,
                    'category_id'    => $category?->id,
                    'services'       => $data['services'],
                    'title'          => $data['title'],
                    'type'           => $data['type'],
                    'description'    => $data['description'],
                    'price'          => $data['price'],
                    'price_unit'     => $data['price_unit'],
                    'duration'       => $data['duration'],
                    'coverage'       => $data['coverage'],
                    'team'           => $data['team'],
                    'guests'         => $data['guests'],
                    // Option B (Peter, approved): a professional may only advertise
                    // a package in their own state. Derived from the owner's
                    // profile, never authored per package, so the two can never
                    // disagree — the old seed had an LA pro "serving NY, NJ, CT".
                    'serves_regions' => $supplier->profile?->state,
                    'availability'   => $data['availability'] ?? 'Available Weekends',
                    'savings_pct'    => $data['savings_pct'] ?? null,
                    'includes'       => $data['includes'],
                    'images'         => [],
                    'is_active'      => true,
                    'sort_order'     => $data['sort'] ?? 0,
                ],
            );

            // firstOrCreate leaves an existing row alone, but the owner and the
            // service area are both derived rather than authored — so they are
            // re-synced on every run. Without the owner line, rows seeded before
            // this fix keep their round-robin professional forever.
            if ($package->user_id !== $supplier->id) {
                $package->update(['user_id' => $supplier->id]);
            }

            if ($package->serves_regions !== $supplier->profile?->state) {
                $package->update(['serves_regions' => $supplier->profile?->state]);
            }
        }

        $this->command?->info('Seeded ' . count($this->packages()) . ' demo packages.');
    }

    /** Match a category by name fragment, else fall back to any active category. */
    private function resolveCategory(string $needle): ?Category
    {
        return Category::where('is_active', true)
                ->where('name', 'like', "%{$needle}%")
                ->orderBy('parent_id')
                ->first()
            ?? Category::where('is_active', true)->inRandomOrder()->first();
    }

    private function packages(): array
    {
        return [
            [
                'title' => 'Elegant Wedding Photo & Video Package', 'owner' => 'elena.demo@example.test', 'category' => 'Photograph', 'type' => 'solo',
                'services' => ['Photography', 'Videography', 'Floral Design', 'Planning / Coordination'],
                'price' => 3250, 'price_unit' => 'from', 'duration' => 'Up to 10 Hours', 'coverage' => 'Up to 10 Hours',
                'team' => ['1 Lead Photographer', '1 Videographer', '1 Floral Designer'], 'guests' => 'Up to 150',
                'availability' => 'Available Weekends', 'savings_pct' => 15,
                'description' => "Complete visual storytelling with premium photography, cinematic video, and custom floral design — all coordinated on one timeline.",
                'includes' => ['Full-day photo coverage', 'Cinematic highlight film', 'Custom floral design', 'Timeline planning', 'Edited online gallery'],
                'sort' => 96,
            ],
            [
                'title' => 'Ultimate Visual Bundle', 'owner' => 'duy.demo@example.test', 'category' => 'Photograph', 'type' => 'solo',
                'services' => ['Photography', 'Videography'],
                'price' => 4850, 'price_unit' => 'from', 'duration' => 'Up to 12 Hours', 'coverage' => 'Up to 12 Hours',
                'team' => ['1 Lead Photographer', '1 Assistant', '1 Videographer', '1 Drone Pilot'], 'guests' => 'Up to 200',
                'availability' => 'Available Weekends', 'savings_pct' => 20,
                'description' => "Photo + video team working together to capture every angle and emotion of your special day, including drone coverage and a highlights film.",
                'includes' => ['Dual photo + video team', 'Drone aerial coverage', 'Highlights film', 'Full-length feature edit', 'Same-day sneak peek'],
                'sort' => 94,
            ],
            [
                'title' => 'Luxury Event Styling & Floral Experience', 'owner' => 'bloomvine.demo@example.test', 'category' => 'Floral', 'type' => 'solo',
                'services' => ['Floral Design', 'Decor & Design', 'Lighting & Tech', 'Planning / Coordination'],
                'price' => 2950, 'price_unit' => 'from', 'duration' => 'Setup + Event Day', 'coverage' => 'Setup + Event Day',
                'team' => ['2 Designers', '2 Floral Stylists', '1 Tech Specialist'], 'guests' => 'Up to 150',
                'availability' => 'Available Weekends', 'savings_pct' => 15,
                'description' => "Full-service design, floral styling, and lighting to transform your event from ordinary to unforgettable.",
                'includes' => ['Custom floral design', 'Full venue styling', 'Ambient + accent lighting', 'Design concept & mood board', 'Setup & teardown'],
                'sort' => 92,
            ],
            [
                'title' => 'Complete Celebration — Photo, DJ & Décor', 'owner' => 'mixmasters.demo@example.test', 'category' => 'DJ', 'type' => 'solo',
                'services' => ['Photography', 'DJ / Entertainment', 'Decor & Design', 'Lighting & Tech'],
                'price' => 5600, 'price_unit' => 'from', 'duration' => 'Up to 8 Hours', 'coverage' => 'Up to 8 Hours',
                'team' => ['1 Photographer', '1 DJ + MC', '1 Décor Lead', '1 Lighting Tech'], 'guests' => 'Up to 250',
                'availability' => 'Available Weekends', 'savings_pct' => 18,
                'description' => "A coordinated crew handling photography, music, décor and lighting so your celebration runs seamlessly end to end.",
                'includes' => ['Event photography', 'DJ + sound + MC', 'Themed décor styling', 'Uplighting + dance-floor wash', 'Single point of contact'],
                'sort' => 90,
            ],
            [
                'title' => 'Corporate Conference — Catering & AV', 'owner' => 'saffron.demo@example.test', 'category' => 'Cater', 'type' => 'solo',
                'services' => ['Catering / Food', 'Lighting & Tech', 'Planning / Coordination'],
                'price' => 8400, 'price_unit' => 'from', 'duration' => 'Multi-day', 'coverage' => 'Up to 3 Days',
                'team' => ['Catering crew (6)', '2 AV Technicians', '1 Event Coordinator'], 'guests' => 'Up to 400',
                'availability' => 'Weekdays & Weekends', 'savings_pct' => 12,
                'description' => "Plated or buffet catering plus full stage, sound and AV for multi-day conferences and corporate galas.",
                'includes' => ['Catering (all meals)', 'Stage, screens & sound', 'AV technicians', 'Run-of-show coordination', 'On-site support'],
                'sort' => 82,
            ],
            [
                'title' => 'Full-Service Wedding Planning', 'owner' => 'grandaffair.demo@example.test', 'category' => 'Planning', 'type' => 'solo',
                'services' => ['Planning / Coordination', 'Decor & Design', 'Floral Design'],
                'price' => 4200, 'price_unit' => 'from', 'duration' => '3–6 months lead', 'coverage' => 'Planning + Event Day',
                'team' => ['1 Lead Planner', '1 Day-of Coordinator', '1 Assistant'], 'guests' => 'Up to 200',
                'availability' => 'Available Weekends', 'savings_pct' => 14,
                'description' => "End-to-end planning from concept to day-of execution — vendor sourcing, budget, timeline and on-site coordination.",
                'includes' => ['Vendor sourcing & vetting', 'Budget & timeline management', 'Design concept & mood board', 'Day-of coordination team', 'Unlimited planning calls'],
                'sort' => 88,
            ],
            [
                'title' => 'Cinematic Videography + Drone', 'owner' => 'duy.demo@example.test', 'category' => 'Video', 'type' => 'solo',
                'services' => ['Videography'],
                'price' => 2600, 'price_unit' => 'from', 'duration' => 'Full day', 'coverage' => 'Up to 10 Hours',
                'team' => ['1 Lead Videographer', '1 Drone Pilot'], 'guests' => 'Up to 180',
                'availability' => 'Available Weekends', 'savings_pct' => null,
                'description' => "A cinematic highlight film plus a full-length feature edit in 4K, with drone coverage and licensed music.",
                'includes' => ['4K full-day coverage', '3–5 min highlight film', 'Full-length feature edit', 'Drone aerial coverage', 'Licensed music'],
                'sort' => 74,
            ],
            [
                'title' => 'DJ & Live Sound — Reception Party', 'owner' => 'horizon.demo@example.test', 'category' => 'DJ', 'type' => 'solo',
                'services' => ['DJ / Entertainment', 'Lighting & Tech'],
                'price' => 1200, 'price_unit' => 'from', 'duration' => '5 hours', 'coverage' => 'Up to 5 Hours',
                'team' => ['1 DJ + MC', '1 Sound Tech'], 'guests' => 'Up to 150',
                'availability' => 'Available Weekends', 'savings_pct' => 10,
                'description' => "Professional DJ and MC with a full sound system and dance-floor lighting — playlist built with you.",
                'includes' => ['Pro DJ + MC', 'Full PA sound system', 'Dance-floor lighting', 'Custom playlist planning', 'Wireless mics for toasts'],
                'sort' => 66,
            ],
            [
                'title' => 'Birthday & Celebration Décor', 'owner' => 'lumiere.demo@example.test', 'category' => 'Decor', 'type' => 'solo',
                'services' => ['Decor & Design', 'Floral Design'],
                'price' => 550, 'price_unit' => 'from', 'duration' => 'Per event', 'coverage' => 'Setup + Event Day',
                'team' => ['1 Décor Stylist', '1 Assistant'], 'guests' => 'Up to 80',
                'availability' => 'Available Weekends', 'savings_pct' => null,
                'description' => "Themed balloon installations, backdrops and table styling for birthdays and milestone celebrations.",
                'includes' => ['Themed balloon install', 'Photo backdrop', 'Table styling', 'Setup & teardown'],
                'sort' => 44,
            ],
            [
                'title' => 'Grand Gala Production Package', 'owner' => 'grandaffair.demo@example.test', 'category' => 'Planning', 'type' => 'solo',
                'services' => ['Planning / Coordination', 'Catering / Food', 'Lighting & Tech', 'DJ / Entertainment'],
                'price' => 15000, 'price_unit' => 'from', 'duration' => 'Per event', 'coverage' => 'Full Production',
                'team' => ['1 Production Lead', 'Catering crew (8)', '2 AV Techs', '1 DJ', 'Event staff (6)'], 'guests' => 'Up to 500',
                'availability' => 'By Arrangement', 'savings_pct' => 16,
                'description' => "Full production for large galas and fundraisers — catering, AV, staging, lighting and event staffing under one managed package.",
                'includes' => ['Dedicated production lead', 'Catering & bar service', 'Stage, AV & lighting', 'Event staffing', 'Run-of-show management'],
                'sort' => 86,
            ],
        ];
    }
}
