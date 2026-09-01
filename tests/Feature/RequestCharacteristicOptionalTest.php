<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Request characteristic" was required and did nothing.
 *
 * It is stored on the event and then never read: it reaches no professional,
 * and changes no matching, no deadline and no fee. Sir Peter, 2026-08-31: "a
 * required field that does nothing is a broken form. Remove the required
 * constraint immediately so clients aren't blocked by a field with no
 * function."
 *
 * It stays on the form while its purpose is decided. It can no longer stop
 * anybody posting a request.
 */
class RequestCharacteristicOptionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function payload(array $overrides = []): array
    {
        $service = Category::create([
            'name' => 'DJ Services', 'slug' => 'dj-services-' . uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-' . uniqid(),
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);

        return array_merge([
            'services'          => [$service->id],
            'event_type'        => $eventType->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ], $overrides);
    }

    public function test_a_request_can_be_posted_without_choosing_one(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.bsr.save', 'service'), $this->payload())
            ->assertSessionHasNoErrors();
    }

    /** Choosing one still works — the field is optional, not gone. */
    public function test_choosing_one_is_still_accepted(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.bsr.save', 'service'), $this->payload([
                'characteristic' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
            ]))
            ->assertSessionHasNoErrors();
    }

    /** An invented value is still refused. Optional is not unvalidated. */
    public function test_a_value_that_does_not_exist_is_still_refused(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.bsr.save', 'service'), $this->payload(['characteristic' => 'wizardry']))
            ->assertSessionHasErrors('characteristic');
    }

    public function test_the_form_no_longer_marks_it_required(): void
    {
        $html = $this->actingAs($this->client())
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Request characteristic', $html);
        $this->assertStringNotContainsString('Request characteristic <span class="req">*</span>', $html);
    }
}
