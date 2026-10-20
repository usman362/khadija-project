<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sir Peter, 20 October, on Send a Direct Request:
 *
 * "Step 3 is confusing bc the service is not available in the state so this is a
 * weird step, is there anything we can do to make this steps actually show that
 * it works."
 *
 * He is right. Step 2 said "No professional in your state offers this yet", and
 * then steps 3 to 5 and the Send button carried on asking for a full brief as
 * though everything were fine. A page that tells you it cannot work and then
 * asks you to fill it in anyway is worse than one that simply refuses.
 *
 * The rest of the form is inert now rather than hidden. Hiding it would leave
 * the client on a short page with no sense of what the request involves;
 * dimming says "this is the form, and it is waiting on step 2".
 */
class AFormDoesNotAskForWhatItCannotUseTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $parent = Category::firstOrCreate(
            ['slug' => 'rentals-cat'],
            ['name' => 'Rentals', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        $this->service = Category::create([
            'name'      => 'Bounce House Rentals',
            'slug'      => 'bounce-house-' . uniqid(),
            'kind'      => Category::SERVICE,
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.direct-offers.create', $query))
            ->assertSuccessful()
            ->getContent();
    }

    /** A professional in this state who offers the service. */
    private function proOffering(): User
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['state' => 'MD', 'headline' => 'Party rentals']);
        $pro->serviceCategories()->syncWithoutDetaching([$this->service->id]);

        return User::findOrFail($pro->id);
    }

    // ── His case ─────────────────────────────────────────────

    /** Nobody offers it, so the rest of the form does not pretend otherwise. */
    public function test_the_rest_of_the_form_is_inert_when_nobody_offers_the_service(): void
    {
        $html = $this->page(['service' => $this->service->id]);

        $this->assertStringContainsString('No professional in your state offers this yet', $html);
        $this->assertStringContainsString('class="do-rest is-blocked"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    /** And it says why, rather than just going grey. */
    public function test_it_says_what_the_form_is_waiting_on(): void
    {
        $html = $this->page(['service' => $this->service->id]);

        $this->assertStringContainsString('waiting on step 2', $html);
    }

    /**
     * The steps are still there, dimmed rather than removed.
     *
     * Hiding them would leave the client with no sense of what a direct request
     * involves, and the two ways out of the dead end are in step 2 itself.
     */
    public function test_the_steps_are_dimmed_not_deleted(): void
    {
        $html = $this->page(['service' => $this->service->id]);

        $this->assertStringContainsString('Event Details', $html);
        $this->assertStringContainsString('Budget', $html);
        $this->assertStringContainsString('Choose another service', $html);
        $this->assertStringContainsString('Post it to the board instead', $html);
    }

    // ── Before a service is chosen ───────────────────────────

    /** Nothing can be sent without a recipient either, so the same applies. */
    public function test_the_form_is_inert_before_a_service_is_chosen(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Choose a service first', $html);
        $this->assertStringContainsString('class="do-rest is-blocked"', $html);
        $this->assertStringContainsString('these steps open up', $html);
    }

    // ── And when it can work ─────────────────────────────────

    /** With somebody to send to, the form is an ordinary form. */
    public function test_the_form_is_live_when_somebody_offers_the_service(): void
    {
        $pro = $this->proOffering();

        $html = $this->page(['service' => $this->service->id]);

        $this->assertStringContainsString($pro->name, $html);
        // The MARKUP, not the word: "is-blocked" is in the stylesheet at the
        // top of the page whether or not the form is blocked. Matching the
        // stylesheet instead of the element is a mistake this codebase has
        // now made seven times.
        $this->assertStringNotContainsString('class="do-rest is-blocked"', $html);
        $this->assertStringNotContainsString('waiting on step 2', $html);
        $this->assertStringContainsString('Send Direct Request', $html);
    }

    /** The empty state is gone once there is anybody at all. */
    public function test_the_empty_state_disappears_when_a_professional_exists(): void
    {
        $this->proOffering();

        $html = $this->page(['service' => $this->service->id]);

        $this->assertStringNotContainsString('No professional in your state offers this yet', $html);
        $this->assertStringContainsString('Choose who this goes to', $html);
    }
}
