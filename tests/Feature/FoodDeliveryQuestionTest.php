<?php

namespace Tests\Feature;

use App\Domain\Requests\FoodDelivery;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-09: "if the users want the option of delivering not by the
 * clients or the professionals then maybe we can be affiliated with
 * ubereats.com or DoorDash.com so that we can earn a commission."
 *
 * Whether they want it is not knowable until somebody asks. So a catering
 * request asks, and only a catering request asks — a form that questions a
 * photographer about food delivery is a form people learn to click past.
 */
class FoodDeliveryQuestionTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $catering;
    private Category $photography;
    private Category $eventType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $foodCat = Category::create([
            'name' => 'Catering & Food Services', 'slug' => 'catering-food-test',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);
        $otherCat = Category::create([
            'name' => 'Photography & Video', 'slug' => 'photo-video-test',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $this->catering = Category::create([
            'name' => 'Buffet Catering', 'slug' => 'buffet-catering-test',
            'kind' => Category::SERVICE, 'parent_id' => $foodCat->id, 'is_active' => true,
        ]);
        $this->photography = Category::create([
            'name' => 'Event Photography', 'slug' => 'event-photography-test',
            'kind' => Category::SERVICE, 'parent_id' => $otherCat->id, 'is_active' => true,
        ]);

        $this->eventType = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding-food-test',
            'kind' => Category::EVENT_TYPE, 'is_active' => true,
        ]);

        \Illuminate\Support\Facades\Cache::forget('food.category_ids');

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $this->client = $this->client->fresh();
    }

    private function pick(Category $service): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$service->id],
            'event_type'        => $this->eventType->name,
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ])->assertRedirect();

        // The wizard will not open step 3 until step 2 is answered.
        $this->actingAs($this->client)->post(route('client.bsr.save', 'event'), [
            'title'         => 'Reception dinner',
            'location'      => 'Baltimore, MD',
            'location_kind' => 'area',
        ])->assertRedirect();
    }

    private function requirementsHtml(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'requirements'))
            ->assertSuccessful()
            ->getContent();
    }

    public function test_a_catering_request_is_asked_how_the_food_arrives(): void
    {
        $this->pick($this->catering);

        $html = $this->requirementsHtml();

        $this->assertStringContainsString('name="delivery_mode"', $html);
        $this->assertStringContainsString('How will this order be delivered?', $html);
        $this->assertStringContainsString(FoodDelivery::PROFESSIONAL_DELIVERS, $html);
        $this->assertStringContainsString(FoodDelivery::CLIENT_COLLECTS, $html);
    }

    /**
     * Sir Peter, 2026-09-10: "i rather now just have the client pick it up
     * themselves or set it up by the professionals do it, so that we are no
     * longer an option."
     *
     * So there are two choices, and a courier cannot be asked for -- not by
     * the form, and not by posting the old value at it either.
     */
    public function test_there_is_no_third_party_option(): void
    {
        $this->pick($this->catering);

        $this->assertCount(2, FoodDelivery::CHOICES);
        $this->assertStringNotContainsString('courier', $this->requirementsHtml());

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'requirements'), [
                'description'   => 'A sit-down dinner for eighty people, two courses.',
                'delivery_mode' => 'courier_wanted',
            ])
            ->assertSessionHasErrors('delivery_mode');
    }

    /** The point of asking only where it means something. */
    public function test_a_photographer_is_not_asked_about_food(): void
    {
        $this->pick($this->photography);

        $this->assertStringNotContainsString('name="delivery_mode"', $this->requirementsHtml());
    }

    public function test_the_answer_is_required_on_a_catering_request(): void
    {
        $this->pick($this->catering);

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'requirements'), [
                'description' => 'A sit-down dinner for eighty people, two courses.',
            ])
            ->assertSessionHasErrors('delivery_mode');
    }

    public function test_no_answer_is_required_when_the_question_was_never_asked(): void
    {
        $this->pick($this->photography);

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'requirements'), [
                'description' => 'Full day coverage of the ceremony and reception.',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_the_answer_is_kept_and_shown_back_on_the_review(): void
    {
        $this->pick($this->catering);

        $this->actingAs($this->client)
            ->post(route('client.bsr.save', 'requirements'), [
                'description'   => 'A sit-down dinner for eighty people, two courses.',
                'delivery_mode' => FoodDelivery::CLIENT_COLLECTS,
            ])
            ->assertSessionHasNoErrors();

        // Kept in the draft…
        $this->assertSame(
            FoodDelivery::CLIENT_COLLECTS,
            session('bsr_wizard.delivery_mode'),
        );

        // …and the radio comes back ticked, not blank, when they step back.
        $this->assertMatchesRegularExpression(
            '/value="' . FoodDelivery::CLIENT_COLLECTS . '"[^>]*checked/',
            $this->requirementsHtml(),
        );
    }

    /** A word check would have called this one not-food. */
    public function test_a_service_is_judged_by_its_category_not_its_name(): void
    {
        $truck = Category::create([
            'name' => 'Food Truck Booking', 'slug' => 'food-truck-test',
            'kind' => Category::SERVICE, 'parent_id' => $this->catering->parent_id, 'is_active' => true,
        ]);

        $this->assertTrue(FoodDelivery::appliesTo([$truck->id]));
        $this->assertFalse(FoodDelivery::appliesTo([$this->photography->id]));
        $this->assertFalse(FoodDelivery::appliesTo([]));
    }

    /**
     * Tick catering, answer, then swap the service out. The preference must go
     * with the service — the count Sir Peter is waiting on is made of these.
     */
    public function test_an_answer_is_dropped_when_the_food_service_is(): void
    {
        $this->assertNull(
            FoodDelivery::answerFor([$this->photography->id], FoodDelivery::CLIENT_COLLECTS),
        );

        $this->assertSame(
            FoodDelivery::CLIENT_COLLECTS,
            FoodDelivery::answerFor([$this->catering->id], FoodDelivery::CLIENT_COLLECTS),
        );
    }
}
