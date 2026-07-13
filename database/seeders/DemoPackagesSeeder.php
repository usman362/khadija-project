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
        $suppliers = User::whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->orderBy('id')->get();

        if ($suppliers->isEmpty()) {
            $this->command?->warn('No suppliers found — run DemoProfessionalsSeeder first.');
            return;
        }

        foreach ($this->packages() as $i => $data) {
            $supplier = $suppliers[$i % $suppliers->count()];
            $category = $this->resolveCategory($data['category']);

            Package::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'user_id'     => $supplier->id,
                    'category_id' => $category?->id,
                    'title'       => $data['title'],
                    'type'        => $data['type'],
                    'description' => $data['description'],
                    'price'       => $data['price'],
                    'price_unit'  => $data['price_unit'],
                    'duration'    => $data['duration'],
                    'includes'    => $data['includes'],
                    'images'      => [],
                    'is_active'   => true,
                    'sort_order'  => $data['sort'] ?? 0,
                ],
            );
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
                'title' => 'Signature Wedding Photography', 'category' => 'Photograph', 'type' => 'solo',
                'price' => 2200, 'price_unit' => 'from', 'duration' => 'Full day (8 hrs)',
                'description' => "Full-day coverage of your ceremony and reception, shot by a lead photographer with a second shooter for wide + detail angles.\n\nYou'll get a private, downloadable online gallery of professionally edited high-resolution images, plus print rights.",
                'includes' => ['8 hours of coverage', 'Second shooter included', 'Edited high-res online gallery', 'Print release', 'Sneak-peek preview within 48 hrs'],
                'sort' => 90,
            ],
            [
                'title' => 'Corporate Event Catering — Plated Dinner', 'category' => 'Cater', 'type' => 'solo',
                'price' => 3400, 'price_unit' => 'from', 'duration' => 'Per event',
                'description' => "A plated three-course dinner service for conferences, galas and company milestones. Menu tailored to your headcount with vegetarian, vegan and gluten-free options built in.",
                'includes' => ['3-course plated menu', 'Dietary options included', 'Service staff & setup', 'Linens & tableware', 'Teardown & cleanup'],
                'sort' => 80,
            ],
            [
                'title' => 'DJ & Live Sound — Reception Party', 'category' => 'DJ', 'type' => 'solo',
                'price' => 1200, 'price_unit' => 'from', 'duration' => '5 hours',
                'description' => "Professional DJ and MC with a full sound system and dance-floor lighting. We build the playlist with you and read the room to keep the floor full.",
                'includes' => ['Pro DJ + MC', 'Full PA sound system', 'Dance-floor lighting', 'Custom playlist planning', 'Wireless mics for toasts'],
                'sort' => 70,
            ],
            [
                'title' => 'Floral Design & Full Venue Styling', 'category' => 'Floral', 'type' => 'solo',
                'price' => 1800, 'price_unit' => 'from', 'duration' => 'Per event',
                'description' => "Custom centerpieces, ceremony arch, aisle styling and full reception décor designed around your palette and theme. Includes delivery, setup and same-night teardown.",
                'includes' => ['Custom centerpieces', 'Ceremony arch / backdrop', 'Aisle & table styling', 'Delivery & setup', 'Same-night teardown'],
                'sort' => 60,
            ],
            [
                'title' => 'Full-Service Event Planning & Coordination', 'category' => 'Planning', 'type' => 'co-op',
                'price' => 4200, 'price_unit' => 'from', 'duration' => '3–6 months lead',
                'description' => "End-to-end planning from concept to day-of execution — vendor sourcing, budget management, timeline building and on-site coordination so you can actually enjoy the day.",
                'includes' => ['Vendor sourcing & vetting', 'Budget & timeline management', 'Design concept & mood board', 'Day-of coordination team', 'Unlimited planning calls'],
                'sort' => 95,
            ],
            [
                'title' => 'Cinematic Wedding Videography', 'category' => 'Video', 'type' => 'solo',
                'price' => 2600, 'price_unit' => 'from', 'duration' => 'Full day',
                'description' => "A cinematic highlight film plus a full-length feature edit of your day. Drone coverage available on request. Delivered in crisp 4K with licensed music.",
                'includes' => ['4K full-day coverage', '3–5 min highlight film', 'Full-length feature edit', 'Licensed music', 'Optional drone coverage'],
                'sort' => 85,
            ],
            [
                'title' => 'Venue Uplighting & Ambient Lighting', 'category' => 'Light', 'type' => 'solo',
                'price' => 900, 'price_unit' => 'from', 'duration' => 'Per event',
                'description' => "Transform your space with wireless uplighting in your accent color, a dance-floor wash and an optional custom monogram projection.",
                'includes' => ['Wireless uplighting', 'Dance-floor wash', 'Custom monogram (optional)', 'Setup & teardown', 'On-call technician'],
                'sort' => 50,
            ],
            [
                'title' => 'Birthday & Celebration Décor', 'category' => 'Decor', 'type' => 'solo',
                'price' => 550, 'price_unit' => 'from', 'duration' => 'Per event',
                'description' => "Themed balloon installations, backdrops and table styling for birthdays and milestone celebrations. Fully set up before your guests arrive.",
                'includes' => ['Themed balloon install', 'Photo backdrop', 'Table styling', 'Setup & teardown'],
                'sort' => 40,
            ],
            [
                'title' => 'Photo + Video + DJ — Celebration Bundle', 'category' => 'Photograph', 'type' => 'co-op',
                'price' => 5200, 'price_unit' => 'from', 'duration' => 'Full day',
                'description' => "A coordinated team covering photo, video and music so everything runs on one timeline — no gaps, no clashing vendors. One point of contact for all three.",
                'includes' => ['Full-day photography', 'Cinematic video edit', 'DJ + sound + lighting', 'Coordinated timeline', 'Single point of contact'],
                'sort' => 88,
            ],
            [
                'title' => 'Grand Gala Production Package', 'category' => 'Planning', 'type' => 'co-op',
                'price' => 15000, 'price_unit' => 'from', 'duration' => 'Per event',
                'description' => "Full production for large galas and fundraisers — catering, AV, staging, lighting and event staffing under one managed package with a dedicated production lead.",
                'includes' => ['Dedicated production lead', 'Catering & bar service', 'Stage, AV & lighting', 'Event staffing', 'Run-of-show management'],
                'sort' => 92,
            ],
        ];
    }
}
