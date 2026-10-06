<?php

namespace Tests\Feature;

use App\Domain\Requests\FoodDelivery;
use App\Models\Category;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 6 October, on an Emergency Request that refused to submit:
 * "this one created as step #2 error... Step 2: Say how the food gets to your
 * event, or that no delivery is needed. BUT there is no question there that
 * asked this question, its a missing step or something needs to be figured
 * out."
 *
 * He was right, and it was worse than a missing step. The question was on
 * the page, hidden, waiting to be revealed the moment a food service was
 * ticked. The reveal read a data-parent attribute on each service that
 * nothing has ever rendered, so it never fired, on any form that uses the
 * picker, while the server went on demanding an answer to a question nobody
 * could see.
 *
 * The picker does say which category a service sits under: data-group, on
 * the section around it. What this holds is that join, on both sides.
 */
class TheFoodQuestionIsOnTheFormTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

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
        $this->client = User::findOrFail($this->client->id);
    }

    /** The picker says which category each group of services sits under. */
    public function test_the_picker_names_the_category_each_service_is_under(): void
    {
        $parent = Category::create([
            'name' => 'Catering & Food Services',
            'slug' => 'catering-food-parent-test',
            'kind' => Category::SERVICE_CATEGORY,
        ]);

        Category::create([
            'name'      => 'Plated Dinner Service',
            'slug'      => 'plated-dinner-test',
            'parent_id' => $parent->id,
        ]);

        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-service-picker :categories="$categories" name="services" :selected="[]" />',
            // The picker takes the SERVICES, and groups them by the category
            // each one sits under.
            ['categories' => Category::whereNotNull('parent_id')->get()],
        );

        $this->assertStringContainsString('data-group="' . $parent->id . '"', $html,
            'The picker names no category, so the food question can never reveal itself.');
    }

    /**
     * And the attribute carries what the server reasons about, so the
     * question appears exactly when an answer will be demanded.
     */
    public function test_the_reveal_and_the_rule_agree_on_what_food_is(): void
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'catering-food-services-agree'],
            ['name' => FoodDelivery::FOOD_CATEGORIES[0], 'kind' => Category::SERVICE_CATEGORY],
        );

        $service = Category::create([
            'name'      => 'Plated Dinner Service',
            'slug'      => 'plated-dinner-agree',
            'parent_id' => $parent->id,
        ]);

        $this->assertContains($parent->id, FoodDelivery::foodCategoryIds(),
            'The reveal looks for this category id and would not find it.');

        $this->assertTrue(FoodDelivery::appliesTo([$service->id]),
            'The server would demand an answer the form never asked for.');
    }

    /** And the reveal reads that, rather than something nobody writes. */
    public function test_the_reveal_reads_what_the_picker_writes(): void
    {
        $script = file_get_contents(base_path('resources/views/partials/_food_delivery.blade.php'));

        $this->assertStringContainsString("closest('.svc-group')", $script,
            'The reveal is reading an attribute again instead of the one the picker renders.');

        $this->assertStringNotContainsString('dataset.parent', $script,
            'dataset.parent is what nothing renders; that was the fault.');
    }
}
