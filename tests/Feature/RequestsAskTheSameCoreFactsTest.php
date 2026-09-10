<?php

namespace Tests\Feature;

use App\Domain\Requests\CoreFacts;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A bidding, emergency and direct request all end in the same agreement, so
 * all three ask for the same facts.
 *
 * Sir Peter, 2026-09-09: "if all of these workflows ultimately lead to an
 * agreement, I would make the core agreement-driving information consistent
 * across all of them."
 *
 * They did not. Before this:
 *   BR  — name, description and date all required.
 *   ER  — asked for none of the three. It built its own title out of the
 *         services picked ("Urgent: Buffet Catering"), so the professional
 *         read a label no person wrote and the client could not correct it.
 *   DR  — asked for a name, took no date, and had no description field at all,
 *         so a professional was sent a name and a service list and had to
 *         guess the rest.
 */
class RequestsAskTheSameCoreFactsTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->service = Category::create([
            'name' => 'Event Photography', 'slug' => 'core-facts-photography',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $this->client = $this->client->fresh();
    }

    private function pro(): User
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $pro->serviceCategories()->sync([$this->service->id]);

        return $pro->fresh();
    }

    /** Every form shows a field for each of the three. */
    public function test_each_form_asks_for_all_three(): void
    {
        $pages = [
            'ER' => route('client.esr.create'),
            'DR' => route('client.direct-offers.create'),
        ];

        foreach ($pages as $flow => $url) {
            $html = $this->actingAs($this->client)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('name="event_name"', $html, "The {$flow} form does not ask for the event's name.");
            $this->assertStringContainsString('name="description"', $html, "The {$flow} form has nowhere to say what is needed.");
        }

        // The BR asks across steps, so its own tests cover the fields; what
        // matters here is that it demands the same three.
        $this->assertSame(
            ['required', 'string', 'max:200'],
            CoreFacts::rules('event_name', 'description', 'event_date')['event_name'],
        );
    }

    public function test_an_emergency_request_without_the_three_is_refused(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), [
                'reason'            => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
                'organization_type' => 'individual',
                'services'          => [$this->service->id],
                'fee_agreed'        => 1,
            ])
            ->assertSessionHasErrors(['event_name', 'description', 'needed_by']);
    }

    public function test_a_direct_request_without_the_three_is_refused(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), [
                'professional_id'   => $this->pro()->id,
                'organization_type' => 'individual',
                'services'          => [$this->service->id],
                'fee_agreed'        => 1,
            ])
            ->assertSessionHasErrors(['event_name', 'description', 'event_date']);
    }

    /**
     * The name the client typed is the name that is stored — not one assembled
     * from the services, which is what an emergency request used to file.
     */
    public function test_the_emergency_request_keeps_the_name_the_client_gave(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), [
                'reason'            => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
                'organization_type' => 'individual',
                'services'          => [$this->service->id],
                'event_name'        => 'Graduation party — Saturday',
                'description'       => 'Our photographer cancelled this morning and we need cover for four hours.',
                'needed_by'         => now()->addDays(2)->format('Y-m-d H:i'),
                'fee_agreed'        => 1,
            ])
            ->assertSessionHasNoErrors();

        $event = \App\Models\Event::where('created_by', $this->client->id)->latest('id')->first();

        $this->assertSame('Graduation party — Saturday', $event->title);
        $this->assertStringNotContainsString('Urgent:', $event->title);
        $this->assertStringContainsString('cancelled this morning', $event->description);
    }

    /** The direct request had no description field, so nothing was stored. */
    public function test_the_direct_request_stores_what_the_client_wrote(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.direct-offers.store'), [
                'professional_id'   => $this->pro()->id,
                'organization_type' => 'individual',
                'services'          => [$this->service->id],
                'event_name'        => 'Anniversary dinner',
                'description'       => 'Two hours of coverage at the restaurant, plus a group photo at the end.',
                'event_date'        => now()->addDays(30)->format('Y-m-d'),
                'fee_agreed'        => 1,
            ])
            ->assertSessionHasNoErrors();

        $event = \App\Models\Event::where('created_by', $this->client->id)->latest('id')->first();

        $this->assertSame('Anniversary dinner', $event->title);
        $this->assertStringContainsString('group photo', $event->description);
        $this->assertNotNull($event->starts_at);
    }

    /** One definition. Three copies is how they came apart the first time. */
    public function test_the_rules_are_written_once(): void
    {
        foreach (['ClientEsrController', 'ClientDirectOfferController', 'ClientBsrController'] as $controller) {
            $this->assertStringContainsString(
                'CoreFacts',
                file_get_contents(app_path("Http/Controllers/Client/{$controller}.php")),
                "{$controller} writes its own version of the core rules.",
            );
        }
    }
}
