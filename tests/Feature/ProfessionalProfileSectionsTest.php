<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Package;
use App\Models\Shift;
use App\Models\User;
use App\Support\Availability;
use App\Support\ProfessionalNumbers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Professional Profile page against the Owner's target mockup —
 * checklist rows 165, 166, 167, 207, 208, 209, 210, 234, 235, 236.
 *
 * Three of those rows are as much about what NOT to ship as what to build,
 * and those are the tests that matter most here:
 *
 *   Row 165 — "Live Event Upgrades" belongs to Rule R41's reserved stub,
 *   pulled on the Owner's explicit instruction. It must not appear.
 *
 *   Row 208 — the target's "99% Cancellation Rate" is almost certainly meant
 *   to be a completion rate. Printed literally beside a 5.0 rating it would
 *   destroy the trust the section exists to build.
 *
 *   Row 166 — no "Most Popular" tag until somebody defines the rule, and
 *   explicitly not defaulting to list order.
 */
class ProfessionalProfileSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = $this->account('professional');

        /*
         * A fixed name, not faker's.
         *
         * These tests assert on the RAW page body, and faker occasionally
         * produces a name with an apostrophe — "O'Brien" renders as
         * O&#039;Brien and the assertion fails perhaps one run in twenty. A
         * test that fails intermittently is one nobody trusts, and the
         * template is what is under test here, not faker's output.
         */
        $this->pro->forceFill(['name' => 'Elena Ruiz'])->save();
        $this->pro->refresh();

        $this->pro->getOrCreateProfile()->update([
            'company_name'     => 'ER Photography',
            'headline'         => 'Weddings and editorial',
            'experience_years' => 9,
            'skills'           => ['Photography', 'Editorial', 'Album Design', 'Engagement Shoots',
                                   'Wedding Photography', 'Portrait Photography', 'Event Photography', 'Photo Editing'],
            'portfolio'        => array_map(
                fn ($i) => ['type' => 'image', 'featured' => $i === 0, 'hero' => "https://example.test/p{$i}.jpg", 'square' => "https://example.test/p{$i}.jpg"],
                range(0, 6),
            ),
        ]);

        $this->client = $this->account('client');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function page()
    {
        return $this->get(route('public.professional.show', $this->pro));
    }

    private function booking(string $status, ?string $startsAt = null, ?User $client = null): Booking
    {
        $event = Event::create([
            'title'      => 'Event ' . fake()->word(),
            'client_id'  => ($client ?? $this->client)->id,
            'created_by' => ($client ?? $this->client)->id,
            'status'     => 'published',
            'starts_at'  => $startsAt ?? now()->addDays(10),
            'ends_at'    => $startsAt ? null : now()->addDays(10)->addHours(6),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => ($client ?? $this->client)->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => ($client ?? $this->client)->id,
            'status'      => $status,
            'price'       => 900,
        ]);
    }

    /* ── Row 209: who this page is about ────────────────────── */

    public function test_the_business_name_appears_in_the_header_and_the_breadcrumb(): void
    {
        $page = $this->page();

        $page->assertOk();
        $page->assertSee('ER Photography', false);
        $page->assertSee('Browse Professionals', false);

        // The breadcrumb no longer trails off after "Browse Professionals".
        // Sliced from the markup, not from the first mention of the class —
        // the stylesheet defines `.pp-breadcrumb` hundreds of lines earlier,
        // and the first version of this test measured the CSS.
        $this->assertStringContainsString('ER Photography', $this->markupSection($page->getContent(), 'pp-breadcrumb', '</nav>'));
    }

    /** The rendered element with this class, up to $until — never the CSS rule. */
    private function markupSection(string $html, string $class, string $until): string
    {
        $start = strpos($html, 'class="' . $class . '"');
        $this->assertNotFalse($start, "no element with class {$class} rendered");

        $end = strpos($html, $until, $start);

        return substr($html, $start, $end === false ? 4000 : $end - $start);
    }

    /** The person behind the trading name still shows — both matter on a contract. */
    public function test_the_account_name_is_shown_alongside_the_business_name(): void
    {
        $this->page()->assertSee('with ' . $this->pro->name, false);
    }

    public function test_a_professional_with_no_business_name_still_has_a_title(): void
    {
        $this->pro->profile->update(['company_name' => null]);

        $this->page()->assertOk()->assertSee($this->pro->name, false);
    }

    /* ── Row 210: cover photos ──────────────────────────────── */

    public function test_the_cover_tiles_portfolio_photos(): void
    {
        // The class attribute, not the bare name — the stylesheet mentions it
        // too, and asserting on the name alone can never fail.
        $this->page()->assertSee('class="pp-cover-tiles', false);
    }

    /** The fallback the row asked us to define: the gradient, as today. */
    public function test_a_professional_with_no_photos_keeps_the_plain_cover(): void
    {
        $this->pro->profile->update(['portfolio' => []]);

        $this->page()->assertOk()->assertDontSee('class="pp-cover-tiles', false);
    }

    /* ── Row 167: Portfolio Highlights ──────────────────────── */

    public function test_portfolio_highlights_shows_four_photos_and_the_full_count(): void
    {
        $page = $this->page();

        $page->assertSee('Portfolio Highlights', false);
        $page->assertSee('View full gallery (7)', false);
        $this->assertSame(4, substr_count($page->getContent(), 'class="pp-hl"'));
    }

    /** The gallery is on this page, so there is no second URL to keep in step. */
    public function test_the_full_gallery_opens_in_place(): void
    {
        $this->page()->assertSee('id="pp-gallery"', false);
    }

    /**
     * Photographs live in Highlights; write-ups live in Selected Projects.
     * Rendering the images in both would have read as two portfolios.
     */
    public function test_photos_are_not_repeated_in_a_second_portfolio_section(): void
    {
        $this->page()->assertDontSee('Selected Projects', false);
    }

    public function test_written_projects_still_have_a_home(): void
    {
        $this->pro->profile->update(['portfolio' => [
            ['title' => 'Baltimore Museum gala', 'description' => 'Two-day shoot.', 'url' => 'https://example.test/case-study'],
        ]]);

        $page = $this->page();

        $page->assertSee('Selected Projects', false);
        $page->assertSee('Baltimore Museum gala', false);

        // No highlights grid — the stylesheet names the section in a comment,
        // so the class attribute is what settles whether it rendered.
        $page->assertDontSee('class="pp-hl-grid"', false);
    }

    /* ── Row 166: Packages ──────────────────────────────────── */

    public function test_the_professionals_own_active_packages_are_listed(): void
    {
        Package::create([
            'user_id' => $this->pro->id, 'title' => 'Wedding Day Package', 'slug' => 'wedding-day',
            'description' => 'Full-day coverage.', 'price' => 1750, 'price_unit' => 'per event',
            'status' => 'active', 'is_active' => true,
        ]);
        Package::create([
            'user_id' => $this->pro->id, 'title' => 'Draft Package', 'slug' => 'draft-pkg',
            'price' => 500, 'status' => 'draft', 'is_active' => false,
        ]);

        $page = $this->page();

        $page->assertSee('Wedding Day Package', false);
        $page->assertSee('1,750', false);

        // Draft packages are not publicly browsable.
        $page->assertDontSee('Draft Package', false);
    }

    /**
     * Row 166: the tag needs a defined rule and must not fall back to list
     * order. Nothing records which package a booking came from, so it cannot
     * be counted — and an undefined tag is worse than no tag.
     */
    public function test_no_most_popular_tag_is_invented(): void
    {
        Package::create([
            'user_id' => $this->pro->id, 'title' => 'Engagement Session', 'slug' => 'engagement',
            'price' => 475, 'status' => 'active', 'is_active' => true,
        ]);

        $this->page()->assertDontSee('Most Popular', false);
    }

    /* ── Row 207: Availability ──────────────────────────────── */

    public function test_availability_reads_the_same_sources_as_the_calendar(): void
    {
        $this->booking('confirmed', now()->addDays(5)->toDateString());

        Shift::create([
            'supplier_id' => $this->pro->id,
            'role'        => 'Second shooter',
            'starts_at'   => now()->addDays(8),
            'ends_at'     => now()->addDays(8)->addHours(5),
            'status'      => 'assigned',
        ]);

        $busy = Availability::busyDates($this->pro);

        $this->assertContains(now()->addDays(5)->toDateString(), $busy);
        $this->assertContains(now()->addDays(8)->toDateString(), $busy);
    }

    /** A multi-day event blocks every day of itself, not only its first. */
    public function test_a_multi_day_commitment_blocks_every_day_it_covers(): void
    {
        $event = Event::create([
            'title' => 'Three-day festival', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(3)->startOfDay(),
            'ends_at'   => now()->addDays(5)->endOfDay(),
        ]);
        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 4000,
        ]);

        $busy = Availability::busyDates($this->pro);

        foreach ([3, 4, 5] as $offset) {
            $this->assertContains(now()->addDays($offset)->toDateString(), $busy, "day +{$offset}");
        }
    }

    /**
     * A proposal nobody has accepted is not a commitment. Showing the
     * professional as busy on the strength of one would cost them work they
     * have not won yet.
     */
    public function test_an_unaccepted_proposal_does_not_block_a_date(): void
    {
        $this->booking('requested', now()->addDays(6)->toDateString());

        $this->assertNotContains(now()->addDays(6)->toDateString(), Availability::busyDates($this->pro));
    }

    /**
     * The page never claims someone is free — only that nothing is booked
     * through GigResource. The professional may be working elsewhere, and a
     * profile that promised availability would be making their commitment.
     */
    public function test_the_page_does_not_promise_availability(): void
    {
        $body = $this->page()->getContent();
        $body = substr($body, (int) strpos($body, 'Availability'));

        $this->assertStringContainsString('booked through GigResource', $body);
        $this->assertDoesNotMatchRegularExpression('/\bis available on\b/i', $body);
    }

    /* ── Row 208: By The Numbers ────────────────────────────── */

    /**
     * The row's central instruction. A 99% cancellation rate beside a 5.0
     * rating is a contradiction, and it would be the first thing a client
     * read.
     */
    public function test_no_cancellation_rate_is_printed(): void
    {
        $this->page()
            ->assertSee('By The Numbers', false)
            ->assertDontSee('Cancellation Rate', false)
            ->assertSee('Completed as booked', false);
    }

    public function test_the_numbers_come_from_real_records(): void
    {
        $this->booking('completed');
        $this->booking('completed');
        $this->booking('cancelled');

        $numbers = ProfessionalNumbers::for($this->pro);

        $this->assertSame(2, $numbers['events_completed']);
        $this->assertSame(67, $numbers['completion_rate']);   // 2 of 3
        $this->assertSame(9, $numbers['years']);              // the professional's own figure
    }

    /** A figure the data cannot support prints a dash, never a plausible number. */
    public function test_missing_figures_print_a_dash(): void
    {
        $page = $this->page();

        $page->assertSee('Events completed', false);
        $this->assertStringContainsString('—', $page->getContent());
    }

    /**
     * One client who booked twice is a 100% repeat rate and a meaningless
     * one. Below two clients there is no rate.
     */
    public function test_repeat_rate_needs_more_than_one_client(): void
    {
        $this->booking('completed');
        $this->booking('completed');

        $this->assertNull(ProfessionalNumbers::repeatClientRate($this->pro));

        $second = $this->account('client');
        $this->booking('completed', null, $second);

        $this->assertSame(50, ProfessionalNumbers::repeatClientRate($this->pro));
    }

    /** Years on the platform is a different claim from years in business. */
    public function test_account_age_is_labelled_as_account_age(): void
    {
        $this->pro->profile->update(['experience_years' => null]);
        $this->pro->forceFill(['created_at' => now()->subYears(3)])->save();

        $this->page()->assertSee('Years on GigResource', false)->assertDontSee('Years in business', false);
    }

    /* ── Row 234: the six-step explainer ────────────────────── */

    public function test_the_booking_explainer_is_shared_not_per_professional(): void
    {
        $this->page()->assertSee('How booking works', false)->assertSee('Event day', false);

        // Same six steps for everyone — nothing per-professional to drift.
        $other = $this->account('professional');
        $this->get(route('public.professional.show', $other))->assertSee('Event day', false);
    }

    /* ── Row 235: Service Area ──────────────────────────────── */

    public function test_the_service_area_names_the_state_and_not_a_radius(): void
    {
        $page = $this->page();

        $page->assertSee('Service Area', false);
        $page->assertSee('Takes bookings from clients in this state', false);

        // R38 keeps a booking inside one jurisdiction; a mileage radius would
        // describe a marketplace this one is not.
        $this->assertDoesNotMatchRegularExpression('/within \d+\s*miles/i', $page->getContent());
    }

    /* ── Row 165: Accepted On GigResource ───────────────────── */

    public function test_the_accepted_box_shows_the_three_lines_that_carry_no_conflict(): void
    {
        $page = $this->page();

        $page->assertSee('Direct requests', false);
        $page->assertSee('Emergency requests', false);
        $page->assertSee('Payment protection', false);
    }

    /**
     * R41's reserved stub was pulled on the Owner's explicit instruction that
     * nothing from that discussion returns "unless and until the Owner gives
     * final approval". This is the test that keeps a tidy-up from adding it.
     */
    public function test_live_event_upgrades_is_not_on_the_page(): void
    {
        $this->page()->assertDontSee('Live Event Upgrade', false);
    }

    /** Never "escrow" — the processor holds the money, not GigResource. */
    public function test_the_payment_line_does_not_say_escrow(): void
    {
        $this->page()->assertDontSee('escrow', false)->assertDontSee('Escrow', false);
    }

    /* ── Row 236: skills ────────────────────────────────────── */

    /**
     * Row 236 asked whether four-of-eight is a display cap or a data gap. It
     * is a data gap: the template renders every skill on file and always has.
     */
    public function test_every_skill_on_file_renders(): void
    {
        $page = $this->page();

        foreach ((array) $this->pro->profile->skills as $skill) {
            $page->assertSee($skill, false);
        }
    }
}
