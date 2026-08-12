<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * V2 event types and service categories are both parent_id-null. The
 * Categories page used to dump every root into one left-hand list, so
 * Catering sat next to Wedding as if both were "main categories".
 */
class EventsCategoriesKindSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['taxonomy.version' => 'v2']);

        Category::create([
            'name' => 'Wedding',
            'slug' => 'wedding-kind-split',
            'kind' => Category::EVENT_TYPE,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        Category::create([
            'name' => 'Catering & Food Services',
            'slug' => 'catering-kind-split',
            'kind' => Category::SERVICE_CATEGORY,
            'is_active' => true,
            'sort_order' => 20,
        ]);
    }

    public function test_the_left_column_does_not_mix_services_into_event_types(): void
    {
        $html = $this->get('/events-categories')->assertOk()->getContent();

        $events   = $this->innerById($html, 'ecTree');
        $services = $this->innerById($html, 'ecServiceTree');

        $this->assertNotSame('', $events, 'Event Types list must render');
        $this->assertNotSame('', $services, 'Services list must render');

        $this->assertStringContainsString('Wedding', $events);
        $this->assertStringNotContainsString('Catering', $events);

        $this->assertStringContainsString('Catering', $services);
        $this->assertStringNotContainsString('Wedding', $services);
    }

    public function test_the_unfiltered_grid_is_event_types_only(): void
    {
        $html = $this->get('/events-categories')->assertOk()->getContent();

        $grid = $this->innerById($html, 'ecResults');

        $this->assertStringContainsString('Wedding', $grid);
        $this->assertStringNotContainsString('Catering', $grid);
        $this->assertStringContainsString('Event Type', $grid);
    }

    public function test_searching_still_finds_a_service_category(): void
    {
        $html = $this->get('/events-categories?q=Catering')->assertOk()->getContent();

        $grid = $this->innerById($html, 'ecResults');

        $this->assertStringContainsString('Catering', $grid);
        $this->assertStringContainsString('Service Category', $grid);
    }

    private function innerById(string $html, string $id): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $node = $dom->getElementById($id);

        return $node ? $dom->saveHTML($node) : '';
    }
}
