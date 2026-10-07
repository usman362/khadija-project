<?php

namespace Tests\Feature;

use App\Domain\Finance\ClientTotals;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The client-side issues in the 6 October developer package.
 *
 * One note worth keeping with the code: the package's other track — Designer
 * Session 1, issues #120–#133 — is a different build under a different test
 * account. Billy Kane, Rivera Wedding, Elena Marsh, DJ Kolt and the rest do
 * not exist in this application, so those issues could not be reproduced here
 * and are not what these tests cover. What they do cover is the fault class
 * that round named "highest priority" — a summary figure disagreeing with the
 * detail beneath it — checked against this build's own pages.
 */
class HandoffOct06ClientSideTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['name' => 'Dana Whitfield', 'primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    private function event(array $attrs = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Beach Party', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'source' => 'user',
            'starts_at' => now()->addDays(20)->setTime(17, 0),
        ], $attrs));
    }

    private function pro(string $name = 'Priya Raghavan'): User
    {
        $u = User::factory()->create(['name' => $name, 'primary_role' => 'professional']);
        $u->assignRole('professional');

        return $u->fresh();
    }

    // ── Issue #113 — a budget with no upper figure ────────────────────

    /**
     * A client typed one number and three pages gave three answers: "$425 –
     * $0" on the event page, "Not set" on My Events and on Proposals by
     * Service. None of them is what she entered.
     */
    public function test_an_open_ended_budget_reads_as_one_number_not_a_range_to_zero(): void
    {
        $e = $this->event(['budget_min' => 425, 'budget_max' => null]);

        $this->assertSame('From $425', $e->budgetLabel());

        $html = $this->actingAs($this->client)->get('/client/events/' . $e->id)
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('From $425', $html);
        $this->assertStringNotContainsString('$425 – $0', $html);
        $this->assertStringNotContainsString('– $0', $html);
    }

    /** The same figure, the same words, on the list as on the page. */
    public function test_every_page_says_the_same_thing_about_a_budget(): void
    {
        $e = $this->event(['budget_min' => 425, 'budget_max' => null]);

        $list = $this->actingAs($this->client)->get('/client/events')->assertSuccessful()->getContent();

        $this->assertStringContainsString('From $425', $list, 'My Events still calls an entered budget "Not set".');

        // And the ordinary cases are unchanged.
        $this->assertSame('$500 – $900', $this->event(['budget_min' => 500, 'budget_max' => 900])->budgetLabel());
        $this->assertSame('Up to $900', $this->event(['budget_min' => null, 'budget_max' => 900])->budgetLabel());
        $this->assertSame('$300', $this->event(['budget_min' => null, 'budget_max' => null, 'budget' => 300])->budgetLabel());
        $this->assertNull($this->event()->budgetLabel());
    }

    // ── Issue #114 — a tag that never changed ─────────────────────────

    /**
     * Two activity rows wore "New" from July to October. The word was a
     * constant in the array, and the pill's colour was a different constant,
     * so the row showed two states at once from the day it was written.
     */
    public function test_the_dashboard_activity_tag_follows_the_record(): void
    {
        $e = $this->event();
        Booking::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro()->id,
            'created_by' => $this->client->id, 'status' => 'completed', 'price' => 750,
        ]);

        $html = $this->actingAs($this->client)->get('/client/dashboard')
            ->assertSuccessful()->getContent();

        $activity = $this->slice($html, 'od-a-activity');

        $this->assertStringContainsString('Completed', $activity);
        $this->assertStringNotContainsString('>New<', $activity, 'A finished booking is still tagged New.');
    }

    // ── Issue #116 — a booking with no duration ───────────────────────

    /** One time is one time, not a range from a moment to itself. */
    public function test_an_event_that_ends_when_it_starts_shows_one_time(): void
    {
        // Wall-clock, the way a client types it: see the model's localWallTimes.
        $at = now()->addDays(10)->setTime(1, 4)->format('Y-m-d H:i:s');
        $e = $this->event(['starts_at' => $at, 'ends_at' => $at]);

        $this->assertStringStartsWith('1:04 AM', (string) $e->timeLabel());
        $this->assertStringNotContainsString('–', (string) $e->timeLabel());

        // A real range still reads as one, and names its clock.
        $r = $this->event([
            'starts_at' => $at,
            'ends_at' => now()->addDays(10)->setTime(4, 4)->format('Y-m-d H:i:s'),
        ]);
        $this->assertStringContainsString('1:04 AM – 4:04 AM', (string) $r->timeLabel());
        $this->assertStringContainsString(\App\Support\DisplayTimezone::abbreviation(), (string) $r->timeLabel());
    }

    /** And the form stops accepting one in the first place. */
    public function test_an_event_cannot_be_saved_ending_when_it_starts(): void
    {
        $e = $this->event();
        $at = now()->addDays(10)->setTime(1, 4);

        $this->actingAs($this->client)
            ->patch('/client/events/' . $e->id, [
                'title' => 'Beach Party',
                'starts_at' => $at->format('Y-m-d H:i:s'),
                'ends_at' => $at->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('ends_at');
    }

    // ── Issue #117 — the client as her own professional ───────────────

    /**
     * PaymentTracker had refused these rows since Issue #51, in its own
     * query. Spending and Payments build their lists straight off bookings
     * and never got the same guard, which is why the record vanished from
     * Bookings and stayed on the other two.
     */
    public function test_a_client_is_never_her_own_professional_on_any_finance_page(): void
    {
        $e = $this->event();

        /*
         * Straight into the table. The model has refused to save one of these
         * since OA-154 — which is why the record this issue is about is an old
         * row, from before that guard, that the finance pages went on showing.
         */
        \Illuminate\Support\Facades\DB::table('bookings')->insert([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'requested', 'price' => 7777,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Booking::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro()->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 750,
        ]);

        foreach (['/client/spending', '/client/payments', '/client/bookings'] as $path) {
            $html = $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();

            $this->assertStringContainsString('Priya Raghavan', $html, $path . ' lost the real booking.');
            // A distinctive figure, so this cannot pass or fail on a number
            // that happens to appear in a stylesheet or a script.
            $this->assertStringNotContainsString(
                '7,777',
                $html,
                $path . ' lists the client as her own professional.'
            );
        }

        // The money agrees with the lists: the bad row is not in the total either.
        $this->assertSame(750.0, ClientTotals::agreed($this->client));
    }

    /** A booking waiting for a professional is still the client's booking. */
    public function test_a_booking_with_nobody_on_it_yet_is_kept(): void
    {
        $e = $this->event();
        Booking::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => null,
            'created_by' => $this->client->id, 'status' => 'requested', 'price' => 400,
        ]);

        $this->assertSame(400.0, ClientTotals::agreed($this->client));

        $this->actingAs($this->client)->get('/client/bookings')->assertSuccessful()->assertSee('Beach Party');
    }

    // ── The pattern that round called highest priority ────────────────

    /**
     * Issue #123's shape, checked against this build: a money panel sitting
     * above a list of cards invites the reader to add the cards up. The list
     * is filtered, searched and cut to ten a page; the total is the whole
     * account. The page has to say so.
     */
    public function test_the_money_panel_says_how_many_bookings_it_covers(): void
    {
        $e = $this->event();
        foreach ([600, 900, 1200] as $i => $price) {
            Booking::create([
                'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro('Pro ' . $i)->id,
                'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => $price,
            ]);
        }

        $html = $this->actingAs($this->client)->get('/client/bookings')->assertSuccessful()->getContent();

        $this->assertStringContainsString('$2,700', $html);
        $this->assertStringContainsString('Across 3 bookings', $html);
    }

    /** On a narrowed list it says the cards are only part of the figure. */
    public function test_the_panel_admits_when_the_list_is_not_all_of_it(): void
    {
        $e = $this->event();
        foreach ([600, 900] as $i => $price) {
            Booking::create([
                'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro('Pro ' . $i)->id,
                'created_by' => $this->client->id, 'status' => $i === 0 ? 'confirmed' : 'completed', 'price' => $price,
            ]);
        }

        $all = $this->actingAs($this->client)->get('/client/bookings')->assertSuccessful()->getContent();
        $this->assertStringNotContainsString('showing part of them', $all);

        $one = $this->actingAs($this->client)->get('/client/bookings?tab=completed')->assertSuccessful()->getContent();
        $this->assertStringContainsString('showing part of them', $one);
        // The figure itself does not change: it is the account, not the tab.
        $this->assertStringContainsString('$1,500', $one);
    }

    // ── Issue #119 — a palette built on a colour nobody typed ─────────

    /** "brown" is a colour. It used to come back violet. */
    public function test_the_palette_is_built_on_the_colour_that_was_typed(): void
    {
        $res = $this->actingAs($this->client)->postJson('/tools/theme-advisor/compute', [
            'event_type' => 'Wedding', 'season' => 'spring',
            'primary_color' => 'brown', 'formality' => 'casual',
        ])->assertSuccessful();

        $primary = collect($res->json('result.palette'))->firstWhere('name', 'Primary')['hex'];

        $this->assertNotSame('#7C3AED', strtoupper($primary), 'Still the old hard-coded violet.');

        // Brown: red leading, blue trailing. Not a purple by any reading.
        [$r, $g, $b] = sscanf(strtoupper($primary), '#%02X%02X%02X');
        $this->assertGreaterThan($b, $r, 'The primary swatch is not a brown.');
        $this->assertGreaterThan($b, $g);

        $this->assertStringContainsString($primary, implode(' ', $res->json('result.tips') ?? []));
    }

    /** A colour it cannot read is said out loud, not quietly replaced. */
    public function test_an_unreadable_colour_is_admitted_rather_than_substituted(): void
    {
        $this->actingAs($this->client)->postJson('/tools/theme-advisor/compute', [
            'event_type' => 'Wedding', 'season' => 'spring',
            'primary_color' => 'the colour of a rainy tuesday', 'formality' => 'casual',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'I could not read "the colour of a rainy tuesday" as a colour. Try a colour name like brown or sage, or a hex code like #8B4513.']);
    }

    /** A card's markup, not the style block that names the same class. */
    private function slice(string $html, string $marker): string
    {
        $start = strpos($html, 'class="od-card ' . $marker . '"');
        $this->assertNotFalse($start, 'No "' . $marker . '" card on the page.');

        $end = strpos($html, '</div>\n    </div>', $start);

        return substr($html, $start, $end === false ? 4000 : $end - $start);
    }
}
