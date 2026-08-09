<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Demo OPEN gigs — real Event records (not hardcoded) so the Bidding Board,
 * browse and category pages have live, biddable data to show. Idempotent.
 */
class DemoGigsSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Real V2 services, not invented ones.
         *
         * This used to firstOrCreate eight categories of its own — Photography,
         * Catering, DJ & Music and so on — which landed in the taxonomy as
         * orphans with no kind and no parent. Professionals list services from
         * the real V2 tree, so a demo gig and a demo professional could never
         * match on anything, and the Opportunity Feed and Fit Score both read
         * as broken when the data was.
         *
         * These are Sub-Sub services (kind=service), which is the level R45
         * says a professional lists and R61's relatedness is computed at. A
         * gig tagged with a top-level category has no siblings, so nothing can
         * ever be related to it.
         */
        $cats = Category::where('kind', 'service')
            ->whereIn('name', [
                'Wedding Photography', 'Event Photography', 'Videography & Cinematic Films',
                'Full-Service Catering', 'Buffet Catering',
                'Wedding DJs', 'Live Bands',
                'Balloon Arches & Columns', 'Centerpiece Design',
                'Full-Service Event Planning', 'Wedding Planning',
                'Uplighting & Ambient Lighting',
            ])
            ->get()->keyBy('name');

        if ($cats->isEmpty()) {
            $this->command?->warn('  V2 taxonomy not seeded — run the category seeder first.');

            return;
        }

        // Drop the orphans this seeder used to create.
        Category::whereIn('slug', [
            'photography', 'catering', 'dj-music', 'floral-decor',
            'event-planning', 'venue', 'videography', 'lighting',
        ])->whereNull('parent_id')->whereNull('kind')->delete();

        // A client to own the gigs (fall back to any client).
        $client = User::where('email', 'client@example.com')->first()
            ?? User::role('client')->first();
        if (! $client) {
            return;
        }

        /*
         * Every one of these used to be in Los Angeles, Chicago, Miami, Tampa,
         * Austin or Seattle — none of them in the seven jurisdictions. R9 says
         * locations come from that list, and once R38 stamped each gig with
         * its client's state, the board showed "Austin, TX" on a gig it had
         * correctly filed under Maryland. Two fields disagreeing in public.
         *
         * They are spread across five launch states rather than all placed in
         * one, for two reasons. Nine of the thirteen demo professionals work
         * outside Maryland and would otherwise see an empty board and an empty
         * feed; and Fit Score's proximity component only does visible work
         * when some gigs are in a professional's own city and some are not.
         *
         * The owning client is the one who lives in that state — a gig's state
         * comes from whoever raised it, so seeding it any other way would put
         * the two fields straight back out of step.
         */
        $gigs = [
            ['Luxury Garden Wedding Photography', 'Seeking a photographer for a 150-guest garden wedding — ceremony, reception, family portraits and candids.', 'Philadelphia, PA', 'marcus.demo@example.test', 2500, 40, ['Wedding Photography']],
            ['Corporate Gala — Full Production', 'Annual corporate gala needs catering, AV, lighting and a planner. Black-tie, 300 guests.', 'Baltimore, MD', null, 18000, 55, ['Full-Service Catering', 'Full-Service Event Planning', 'Uplighting & Ambient Lighting', 'Videography & Cinematic Films']],
            ['Waterfront Wedding — Photo + Video + DJ', 'Sunset waterfront wedding wants a photographer, videographer and DJ. Relaxed, boho vibe.', 'Wilmington, DE', 'sofia.demo@example.test', 6500, 70, ['Wedding Photography', 'Videography & Cinematic Films', 'Wedding DJs']],
            ['Birthday Party Décor & Balloons', 'Black, gold & white décor for a 30th birthday, 80 guests. Setup and teardown included.', 'Philadelphia, PA', 'marcus.demo@example.test', 600, 12, ['Balloon Arches & Columns']],
            ['Wedding Planner — Full Service', 'Engaged couple needs a full-service planner to manage vendors, timeline and day-of coordination.', 'Richmond, VA', 'priya.demo@example.test', 4000, 90, ['Wedding Planning']],
            ['Conference Catering — 200 Guests', 'Two-day tech conference needs breakfast + lunch catering for 200. Dietary options required.', 'Washington, DC', 'james.demo@example.test', 5200, 21, ['Buffet Catering']],
        ];

        foreach ($gigs as [$title, $desc, $loc, $ownerEmail, $budget, $daysOut, $catList]) {
            $owner = $ownerEmail ? (User::where('email', $ownerEmail)->first() ?? $client) : $client;

            $event = Event::updateOrCreate(
                ['title' => $title, 'client_id' => $owner->id],
                [
                    'description'  => $desc,
                    'location'     => $loc,
                    // Set explicitly rather than left to the model hook, so a
                    // re-seed corrects a row whose state is already wrong.
                    'state'        => strtoupper($owner->profile?->state ?: 'MD'),
                    'budget'       => $budget,
                    'status'       => 'pending',
                    'is_published' => true,
                    'starts_at'    => Carbon::now()->addDays($daysOut),
                    'ends_at'      => Carbon::now()->addDays($daysOut)->addHours(6),
                    'created_by'   => $owner->id,
                    'source'       => 'user',
                ]
            );
            $event->categories()->sync(collect($catList)->map(fn ($n) => $cats[$n]->id)->all());
        }

        // The old out-of-area copies, left behind under the previous owner.
        Event::where('client_id', $client->id)
            ->whereIn('location', ['Los Angeles, CA', 'Chicago, IL', 'Miami, FL', 'Tampa, FL', 'Austin, TX', 'Seattle, WA'])
            ->delete();
    }
}
