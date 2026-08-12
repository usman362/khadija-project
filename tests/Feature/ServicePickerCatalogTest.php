<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 91, 118 and 124 — what counts as a service.
 *
 * Row 91 was called the single biggest finding of the review, and it was
 * functional rather than cosmetic: "Baby Shower" could be submitted as the
 * service requested on a direct offer, and then sat on the booking card
 * beside Event Staffing as though someone had been hired to perform it.
 *
 * Underneath, three flows each had their own idea of what a service was. The
 * emergency form filtered nothing at all. The direct offer filtered on kind.
 * The broadcast form filtered on parent_id, a first-taxonomy idiom. Three
 * answers meant three catalogues, which is exactly what the reviewer saw when
 * the same alphabetical position held different entries on different screens.
 *
 * One scope now answers the question — Category::bookableServices() — and
 * these tests hold every picker to it. The server-side rule matters as much
 * as the pickers: narrowing a list changes what a client is OFFERED, not what
 * the endpoint will ACCEPT.
 */
class ServicePickerCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** The offending example from the report, and a real service to sit beside it. */
    private function babyShower(): Category
    {
        return $this->category('Baby Shower', Category::EVENT_TYPE);
    }

    private function staffing(): Category
    {
        return $this->category('Guest Services & Event Staffing', Category::SERVICE);
    }

    private function category(string $name, string $kind): Category
    {
        $slug = \Illuminate\Support\Str::slug($name);

        return Category::firstOrCreate(['slug' => $slug], [
            'name'      => $name,
            'kind'      => $kind,
            'is_active' => true,
            // Under the first taxonomy a service is a row with a parent, so the
            // fixture has to be honest about that too or the v1 branch of the
            // scope is never really exercised.
            'parent_id' => $kind === Category::SERVICE
                ? Category::firstOrCreate(
                    ['slug' => 'picker-parent'],
                    ['name' => 'Services', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
                )->id
                : null,
        ]);
    }

    private function names($categories): array
    {
        return collect($categories)->pluck('name')->all();
    }

    /* ── The pickers offer services only ────────────────────── */

    public function test_the_emergency_form_offers_no_event_types(): void
    {
        $this->babyShower();
        $this->staffing();

        $offered = $this->names(
            $this->actingAs($this->client)->get(route('client.esr.create'))->assertOk()->viewData('categories')
        );

        $this->assertContains('Guest Services & Event Staffing', $offered);
        $this->assertNotContains('Baby Shower', $offered);
    }

    public function test_the_direct_offer_form_offers_no_event_types(): void
    {
        $this->babyShower();
        $this->staffing();

        $offered = $this->names(
            $this->actingAs($this->client)->get(route('client.direct-offers.create'))->assertOk()->viewData('categories')
        );

        $this->assertContains('Guest Services & Event Staffing', $offered);
        $this->assertNotContains('Baby Shower', $offered);
    }

    public function test_the_broadcast_form_offers_no_event_types_among_its_services(): void
    {
        $this->babyShower();
        $this->staffing();

        $page = $this->actingAs($this->client)->get(route('client.bsr.step', ['step' => 'service']))->assertOk();

        $this->assertNotContains('Baby Shower', $this->names($page->viewData('categories')));

        // It still knows what an occasion is — in its own field, where one belongs.
        $this->assertContains('Baby Shower', $this->names($page->viewData('eventTypes')));
    }

    /**
     * The drift the reviewer spotted by reading the same alphabetical
     * position on three screens.
     */
    public function test_all_three_flows_offer_the_same_catalogue(): void
    {
        $this->babyShower();
        $this->staffing();
        $this->category('Beverage Catering Packages', Category::SERVICE);

        $esr = $this->names($this->actingAs($this->client)->get(route('client.esr.create'))->viewData('categories'));
        $dso = $this->names($this->actingAs($this->client)->get(route('client.direct-offers.create'))->viewData('categories'));
        $bsr = $this->names($this->actingAs($this->client)->get(route('client.bsr.step', ['step' => 'service']))->viewData('categories'));

        sort($esr);
        sort($dso);
        sort($bsr);

        $this->assertSame($esr, $dso, 'emergency and direct offer must share one catalogue');
        $this->assertSame($esr, $bsr, 'broadcast must share it too');
    }

    /* ── And the endpoints refuse one ───────────────────────── */

    /**
     * The half that matters most. Narrowing a picker changes what a client is
     * shown; this is what happens when the value arrives anyway.
     */
    public function test_an_event_type_cannot_be_submitted_as_the_service_requested(): void
    {
        $babyShower = $this->babyShower();

        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'event_name' => 'Emergency help',
            'reason'     => 'last_minute',
            'needed_by'  => now()->addDays(2)->format('Y-m-d H:i:s'),
            'services'   => [$babyShower->id],
        ])->assertSessionHasErrors('services.0');

        $this->assertDatabaseCount('events', 0);
    }

    /** And the refusal explains itself rather than saying "invalid". */
    public function test_the_refusal_says_what_to_choose_instead(): void
    {
        $babyShower = $this->babyShower();

        $errors = $this->actingAs($this->client)->post(route('client.esr.store'), [
            'event_name' => 'Emergency help',
            'reason'     => 'last_minute',
            'needed_by'  => now()->addDays(2)->format('Y-m-d H:i:s'),
            'services'   => [$babyShower->id],
        ])->assertSessionHasErrors()->getSession()->get('errors')->getBag('default');

        $this->assertStringContainsString('type of event', $errors->first('services.0'));
        $this->assertStringContainsString('Baby Shower', $errors->first('services.0'));
    }

    public function test_a_real_service_is_still_accepted(): void
    {
        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'event_name' => 'Emergency help',
            'reason'     => 'last_minute',
            'needed_by'  => now()->addDays(2)->format('Y-m-d H:i:s'),
            'services'   => [$this->staffing()->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('events', 1);
    }

    /* ── Row 124: the hub, and the breadcrumb ───────────────── */

    public function test_the_virtual_hub_lists_services_not_occasions(): void
    {
        $this->babyShower();
        $this->staffing();

        $offered = $this->names(
            $this->actingAs($this->client)->get(route('client.virtual-hub.index'))->assertOk()->viewData('categories')
        );

        $this->assertNotContains('Baby Shower', $offered);
    }

    /**
     * yieldContent hands back rendered html, so "&" arrived already escaped
     * and {{ }} escaped it again — the breadcrumb read "Virtual &amp; Hybrid
     * Hub" while the page heading beside it, printed once, read correctly.
     */
    public function test_an_ampersand_in_a_page_title_renders_once(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.virtual-hub.index'))->assertOk();

        $page->assertDontSee('&amp;amp;', false);
        $page->assertSee('Virtual &amp; Hybrid Hub', false);
    }
}
