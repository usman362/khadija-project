<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Package;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The package page, rebuilt to the Owner's mockup (2026-08-20).
 *
 * What is worth guarding is not the layout — it is the claims. The mockup
 * states a lot about a professional, and this page may only state the parts
 * that are true of the one in front of the reader.
 */
class PackageDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create(['name' => 'Elena Rossi', 'primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        UserProfile::updateOrCreate(['user_id' => $this->pro->id], [
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'company_name' => 'Rossi Studio', 'headline' => 'Lead Photographer',
        ]);

        $parent = Category::create(['name' => 'Photography & Videography', 'slug' => 'photo-video-pd', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $child = Category::create(['name' => 'Wedding Photo & Video', 'slug' => 'wedding-pv-pd', 'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true]);

        $this->package = Package::create([
            'user_id' => $this->pro->id, 'category_id' => $child->id,
            'title' => 'Elegant Wedding Photo & Video Package', 'slug' => 'elegant-pd',
            'type' => 'solo', 'description' => 'Complete visual storytelling.',
            'services' => ['Photography', 'Videography'], 'price' => 3250, 'price_unit' => 'from',
            'coverage' => 'Up to 10 Hours', 'guests' => 'Up to 150',
            'includes' => ['Full-day photo coverage', 'Edited online gallery'],
            'status' => 'active', 'is_active' => true,
        ]);
    }

    private function page(array $query = [])
    {
        return $this->get(route('public.package', $this->package->slug) . ($query ? '?' . http_build_query($query) : ''));
    }

    // ── The claims ───────────────────────────────────────────────

    public function test_a_professional_with_no_history_is_not_given_zeroes(): void
    {
        // "0 bookings" and "responds in —" are worse than saying nothing.
        $html = $this->page()->assertOk()->getContent();

        $this->assertStringContainsString('New on GigResource', $html);
        $this->assertStringNotContainsString('0 bookings completed', $html);
        $this->assertStringNotContainsString('Responds in ~', $html);
    }

    public function test_bookings_are_counted_once_not_twice(): void
    {
        /*
         * The mockup shows "63 Bookings" AND "128 Events Completed". On this
         * platform those are the same count read twice, and two numbers for one
         * fact invite the reader to add them up.
         */
        $this->completedBooking();

        $html = $this->page()->assertOk()->getContent();

        $this->assertStringContainsString('1 booking completed', $html);
        $this->assertStringNotContainsString('Events Completed', $html);
    }

    public function test_only_verifications_with_documents_behind_them_are_claimed(): void
    {
        $html = $this->page()->assertOk()->getContent();

        // Nothing is verified on this account, and the page says so rather than
        // showing three green ticks.
        $this->assertStringContainsString('Trade License not verified', $html);
        $this->assertStringNotContainsString('Trade License verified', $html);
    }

    public function test_a_stand_in_photograph_is_labelled_as_one(): void
    {
        // A stock image that reads as this professional's own work is a claim
        // about them.
        $this->page()->assertOk()->assertSee('Stock image');
    }

    public function test_the_service_area_is_a_state_not_a_radius(): void
    {
        // R38: a package is bookable inside its professional's own state, so
        // "within 20 miles of Washington" would describe a different marketplace.
        $html = $this->page()->assertOk()->getContent();

        $this->assertStringContainsString('Baltimore, MD', $html);
        $this->assertStringNotContainsString('miles', $html);
    }

    // ── Availability ─────────────────────────────────────────────

    public function test_a_free_date_is_reported_as_nothing_booked_not_as_free(): void
    {
        /*
         * Nobody can know a professional is free — only that their GigResource
         * calendar is clear. The wording says exactly that.
         */
        $date = now()->addDays(20)->toDateString();

        $this->page(['date' => $date])
            ->assertOk()
            ->assertSee('Nothing booked on')
            ->assertSee('calendar is clear');
    }

    public function test_a_committed_date_is_reported_as_committed(): void
    {
        $date = now()->addDays(20)->toDateString();
        $this->completedBooking($date, 'confirmed');

        $this->page(['date' => $date])->assertOk()->assertSee('Already committed on');
    }

    public function test_a_date_in_the_past_is_ignored_rather_than_answered(): void
    {
        $this->page(['date' => now()->subYear()->toDateString()])
            ->assertOk()
            ->assertSee('Pick your date');
    }

    public function test_the_chosen_date_carries_into_the_request(): void
    {
        // Asking the client for the date twice is the fault the request forms
        // were just fixed for.
        $date = now()->addDays(20)->toDateString();
        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        UserProfile::updateOrCreate(['user_id' => $client->id], ['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $html = $this->actingAs($client)->get(route('public.package', $this->package->slug) . '?date=' . $date)
            ->assertOk()->getContent();

        $this->assertStringContainsString('date=' . $date, $html);
    }

    // ── The rest of the page ─────────────────────────────────────

    public function test_the_breadcrumb_climbs_the_category_path(): void
    {
        $this->page()->assertOk()
            ->assertSee('Photography &amp; Videography', false)
            ->assertSee('Wedding Photo &amp; Video', false);
    }

    public function test_what_is_included_is_listed(): void
    {
        $this->page()->assertOk()
            ->assertSee('Full-day photo coverage')
            ->assertSee('Edited online gallery');
    }

    public function test_the_page_states_no_price_the_package_does_not_have(): void
    {
        /*
         * The mockup offers three tiers — Essential $2,250 / Signature $3,250 /
         * Complete $4,500 — and priced add-ons. A package has ONE price here,
         * so the page shows one. Inventing two more would be inventing what the
         * professional charges.
         */
        $html = $this->page()->assertOk()->getContent();

        $this->assertStringContainsString('$3,250', $html);

        // Asserted on the PRICES rather than the words: "Complete" appears in
        // this package's own description, and a test that fires on an ordinary
        // English word teaches everyone to ignore it.
        foreach (['$2,250', '$4,500', 'Estimated Total', 'Most Popular'] as $invented) {
            $this->assertStringNotContainsString($invented, $html, "the page offers something that does not exist: {$invented}");
        }
    }

    private function completedBooking(?string $date = null, string $status = 'completed'): void
    {
        $client = User::factory()->create();
        $event = Event::create([
            'title' => 'Someone elses wedding', 'created_by' => $client->id, 'client_id' => $client->id,
            'status' => 'published',
            'starts_at' => ($date ?? now()->subMonth()->toDateString()) . ' 15:00:00',
            'ends_at' => ($date ?? now()->subMonth()->toDateString()) . ' 23:00:00',
        ]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $client->id, 'status' => $status,
        ]);
    }
}
