<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-08 — three things about the BR, ER and DR together.
 *
 *  1. All three say what posting costs. It was on the ER only, in that page's
 *     own words, and the BR carried three separate wordings of its own — which
 *     is how three pages end up quoting three different fees the first time
 *     one of them changes. One partial now, so the wording cannot drift.
 *
 *  2. The client actively agrees to the $2.99 before the request goes
 *     anywhere, and the agreement is checked by the controller rather than
 *     only by the browser.
 *
 *  3. The three collect the same mandatory facts. The DR accepted a request
 *     with no name and no service at all — both of which the BR and ER refuse.
 */
class RequestFeeTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->service = Category::create([
            'name' => 'Fee Terms Service', 'slug' => 'fee-terms-service',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
        $this->pro->serviceCategories()->sync([$this->service->id]);
        $this->pro = $this->pro->fresh();
    }

    /** @return array<int, string> the pages that must carry the terms */
    private function pages(): array
    {
        return [
            'ER' => route('client.esr.create'),
            'DR' => route('client.direct-offers.create'),
        ];
    }

    public function test_the_fee_is_stated_on_every_request_page(): void
    {
        foreach ($this->pages() as $label => $url) {
            $html = $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();

            $this->assertStringContainsString('$2.99', $html, "The {$label} page does not say what it costs.");
            $this->assertStringContainsString('to post', $html, "The {$label} page does not say posting is free.");
            $this->assertStringContainsString('name="fee_agreed"', $html, "The {$label} page has no agreement to it.");
        }
    }

    /** The BR states it too, on the step that publishes. */
    public function test_the_bidding_request_states_it_on_the_step_that_publishes(): void
    {
        $this->startBidding();

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'review'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('$2.99', $html);
        $this->assertStringContainsString('name="confirm"', $html);
    }

    /** Required by the controller, not only by the markup. */
    public function test_an_emergency_request_is_refused_without_the_agreement(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->esrPayload(['fee_agreed' => null]))
            ->assertSessionHasErrors('fee_agreed');

        $this->actingAs($this->client)
            ->post(route('client.esr.store'), $this->esrPayload())
            ->assertSessionHasNoErrors();
    }

    public function test_a_direct_request_is_refused_without_the_agreement(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->drPayload(['fee_agreed' => null]))
            ->assertSessionHasErrors('fee_agreed');

        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->drPayload())
            ->assertSessionHasNoErrors();
    }

    /** A request with no name is an untitled job on the professional's board. */
    public function test_a_direct_request_needs_a_name(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->drPayload(['event_name' => null]))
            ->assertSessionHasErrors('event_name');
    }

    /** And a request for nothing at all is not a request. */
    public function test_a_direct_request_needs_a_service(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), $this->drPayload([
                'services' => null, 'service_single' => null,
            ]))
            ->assertSessionHasErrors('services');
    }

    /**
     * The acronym is not a flex item.
     *
     * .do-ai-row is a flex container, so the inline <b> around the request type
     * became its own flex item — a 9px gap either side of "MSR" and a line
     * break allowed there. Sir Peter read it as the acronym being oddly
     * placed; it was the layout. The sentence is one element now.
     */
    public function test_the_msr_sentence_is_one_element(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(
            '<span class="ck">✓</span><span>For an <b id="doTypeLbl">',
            $html,
            'The sentence is loose text beside the tick, so the flex row spaces its bold parts apart.',
        );
    }

    private function esrPayload(array $over = []): array
    {
        return array_merge([
            'fee_agreed' => 1,
            'reason' => 'professional_cancelled',
            'organization_type' => 'individual',
            'needed_by' => now()->addDays(2)->toDateTimeString(),
            'services' => [$this->service->id],
            // The three facts every request carries now — see CoreFacts.
            'event_name' => 'Garden Reception',
            'description' => 'Cover for a two-hour reception, including setup and one group photo.',
        ], $over);
    }

    private function drPayload(array $over = []): array
    {
        return array_merge([
            'fee_agreed' => 1,
            'professional_id' => $this->pro->id,
            'event_name' => 'Garden Reception',
            'organization_type' => 'individual',
            'request_type' => 'MSR',
            'services' => [$this->service->id],
            'description' => 'Cover for a two-hour reception, including setup and one group photo.',
            'event_date' => now()->addDays(30)->format('Y-m-d'),
        ], $over);
    }

    private function startBidding(): void
    {
        $eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-fee-terms',
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [$this->service->id],
            'event_type' => $eventType->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'event'), [
            'title' => 'Garden Reception', 'location_kind' => 'area', 'location' => 'Baltimore, MD',
        ]);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'requirements'), [
            'description' => 'We need coverage for a garden reception in Baltimore this autumn.',
        ]);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'budget'), ['budget_min' => 500, 'budget_max' => 1500]);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'proposals'), []);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'files'), []);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date' => now()->addMonths(3)->toDateString(),
            'event_start_time' => '17:00',
        ]);
    }

    /**
     * The bidding request does not ask for a name twice.
     *
     * Sir Peter, 11 Sep: step 1 asks what the event is, and step 2 then asked
     * for its name, which is the same question again. The name is now built
     * for them, and step 2 has no name box and no "Request name" either.
     */
    public function test_step_two_no_longer_asks_for_a_name(): void
    {
        $this->startBidding();

        $bidding = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'event'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('name="title"', $bidding);
        $this->assertStringNotContainsString('Request name', $bidding);

        $direct = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Event Name', $direct);
    }

    /** Step 2 goes through without a name, because one is already there. */
    public function test_step_two_saves_without_a_name(): void
    {
        $this->startBidding();

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'event'), ['location_kind' => 'area'])
            ->assertSessionHasNoErrors();

        $this->assertNotEmpty(session('bsr_wizard')['title'] ?? null);
    }
}
