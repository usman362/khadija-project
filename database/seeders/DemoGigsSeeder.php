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
        // Ensure a real category set exists (most installs only have a test one).
        $catNames = ['Photography', 'Catering', 'DJ & Music', 'Floral & Décor', 'Event Planning', 'Venue', 'Videography', 'Lighting'];
        $cats = [];
        foreach ($catNames as $i => $name) {
            $cats[$name] = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $i + 1]
            );
        }

        // A client to own the gigs (fall back to any client).
        $client = User::where('email', 'client@example.com')->first()
            ?? User::role('client')->first();
        if (! $client) {
            return;
        }

        $gigs = [
            ['Luxury Garden Wedding Photography', 'Seeking a photographer for a 150-guest garden wedding — ceremony, reception, family portraits and candids.', 'Los Angeles, CA', 2500, 40, ['Photography']],
            ['Corporate Gala — Full Production', 'Annual corporate gala needs catering, AV, lighting and a planner. Black-tie, 300 guests.', 'Chicago, IL', 18000, 55, ['Catering', 'Event Planning', 'Lighting', 'Videography']],
            ['Beach Wedding — Photo + Video + DJ', 'Sunset beach wedding wants a photographer, videographer and DJ. Relaxed, boho vibe.', 'Miami, FL', 6500, 70, ['Photography', 'Videography', 'DJ & Music']],
            ['Birthday Party Décor & Balloons', 'Black, gold & white décor for a 30th birthday, 80 guests. Setup and teardown included.', 'Tampa, FL', 600, 12, ['Floral & Décor']],
            ['Wedding Planner — Full Service', 'Engaged couple needs a full-service planner to manage vendors, timeline and day-of coordination.', 'Austin, TX', 4000, 90, ['Event Planning']],
            ['Conference Catering — 200 Guests', 'Two-day tech conference needs breakfast + lunch catering for 200. Dietary options required.', 'Seattle, WA', 5200, 21, ['Catering']],
        ];

        foreach ($gigs as [$title, $desc, $loc, $budget, $daysOut, $catList]) {
            $event = Event::updateOrCreate(
                ['title' => $title, 'client_id' => $client->id],
                [
                    'description'  => $desc,
                    'location'     => $loc,
                    'budget'       => $budget,
                    'status'       => 'pending',
                    'is_published' => true,
                    'starts_at'    => Carbon::now()->addDays($daysOut),
                    'ends_at'      => Carbon::now()->addDays($daysOut)->addHours(6),
                    'created_by'   => $client->id,
                    'source'       => 'user',
                ]
            );
            $event->categories()->sync(collect($catList)->map(fn ($n) => $cats[$n]->id)->all());
        }
    }
}
