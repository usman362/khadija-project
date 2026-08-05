<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryRelevance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sir Peter's V2 tree is imported alongside the live one so it can be checked
 * before anything switches. These tests hold the two apart: v2 must be
 * invisible while v1 is live, and the switch must refuse while professionals
 * are still attached to old categories.
 */
class TaxonomyV2Test extends TestCase
{
    use RefreshDatabase;

    private function importV2(): void
    {
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
    }

    private function v1(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'taxonomy_version' => 'v1',
            'is_active' => true,
        ]);
    }

    public function test_the_sheet_imports_at_its_stated_size(): void
    {
        $this->importV2();

        $of = fn (string $kind) => Category::anyTaxonomy()
            ->where('taxonomy_version', 'v2')->where('kind', $kind)->count();

        $this->assertSame(106, $of(Category::EVENT_TYPE));
        $this->assertSame(27, $of(Category::SERVICE_CATEGORY));
        $this->assertSame(241, $of(Category::SERVICE));
        $this->assertSame(139, CategoryRelevance::count());
    }

    public function test_v2_is_invisible_while_v1_is_live(): void
    {
        config(['taxonomy.version' => 'v1']);
        $this->v1('Catering Services');
        $this->importV2();

        $this->assertSame(1, Category::count(), 'ordinary queries must not see the other tree');
        $this->assertSame(375, Category::anyTaxonomy()->count());
    }

    public function test_switching_the_config_swaps_which_tree_is_visible(): void
    {
        $this->v1('Catering Services');
        $this->importV2();

        config(['taxonomy.version' => 'v2']);

        $this->assertSame(374, Category::count());
        $this->assertNotNull(Category::where('name', 'Catering & Food Services')->first());
        $this->assertNull(Category::where('name', 'Catering Services')->first());
    }

    public function test_importing_twice_does_not_duplicate(): void
    {
        $this->importV2();
        $before = Category::anyTaxonomy()->where('taxonomy_version', 'v2')->count();

        $this->importV2();

        $this->assertSame($before, Category::anyTaxonomy()->where('taxonomy_version', 'v2')->count());
    }

    public function test_services_hang_under_their_service_category(): void
    {
        config(['taxonomy.version' => 'v2']);
        $this->importV2();

        $catering = Category::where('name', 'Catering & Food Services')->firstOrFail();
        $names = $catering->children()->pluck('name');

        $this->assertCount(16, $names, 'the sheet lists 16 services under catering');
        $this->assertContains('Buffet Catering', $names->all());
    }

    public function test_two_categories_can_hold_a_service_of_the_same_name(): void
    {
        config(['taxonomy.version' => 'v2']);
        $this->importV2();

        // "Bartenders (Staffing Only)" under staffing and "Professional
        // Bartenders" under the bar category are separate rows; slugs carry
        // the parent so neither overwrites the other.
        $this->assertSame(
            241,
            Category::ofKind(Category::SERVICE)->count(),
            'no service was lost to a slug collision',
        );
    }

    public function test_relevance_ranks_essential_above_common_above_occasional(): void
    {
        config(['taxonomy.version' => 'v2']);
        $this->importV2();

        $tiers = CategoryRelevance::forArchetype('Milestone & Personal Celebrations')
            ->ranked()->pluck('tier')->unique()->values()->all();

        $this->assertSame(['Essential', 'Common', 'Occasional'], $tiers);
    }

    public function test_the_switch_refuses_while_professionals_still_point_at_the_old_tree(): void
    {
        $old = $this->v1('Catering Services');
        $this->importV2();

        $pro = User::factory()->create();
        DB::table('category_user')->insert(['user_id' => $pro->id, 'category_id' => $old->id]);

        $this->artisan('taxonomy:switch')->assertFailed();
    }

    public function test_remap_moves_the_links_and_then_the_switch_is_allowed(): void
    {
        $old = $this->v1('Catering Services');       // maps to Catering & Food Services
        $this->importV2();

        $pro = User::factory()->create();
        DB::table('category_user')->insert(['user_id' => $pro->id, 'category_id' => $old->id]);

        $this->artisan('taxonomy:switch --remap')->assertSuccessful();

        $landedOn = DB::table('category_user')->where('user_id', $pro->id)->value('category_id');
        $target = Category::anyTaxonomy()->where('taxonomy_version', 'v2')
            ->where('name', 'Catering & Food Services')->firstOrFail();

        $this->assertSame($target->id, $landedOn, 'the professional followed their category across');
    }

    public function test_every_event_type_carries_an_archetype_the_matrix_knows(): void
    {
        config(['taxonomy.version' => 'v2']);
        $this->importV2();

        $used = Category::ofKind(Category::EVENT_TYPE)->pluck('archetype')->unique();
        $mapped = CategoryRelevance::pluck('archetype')->unique();

        $this->assertEmpty(
            $used->diff($mapped)->all(),
            'an event type points at an archetype with no service categories behind it',
        );
    }
    public function test_a_lowercase_spelling_of_the_same_category_is_remapped_too(): void
    {
        // The old tree carries "Food Services" and "Food services" as separate
        // rows, each with professionals attached. A case-sensitive match moved
        // one and stranded the other.
        $properCase = $this->v1('Catering Services');
        $lowerCase = Category::create([
            'name' => 'Catering services',
            'slug' => 'catering-services-lower',
            'taxonomy_version' => 'v1',
            'is_active' => true,
        ]);
        $this->importV2();

        $pro = User::factory()->create();
        DB::table('category_user')->insert([
            ['user_id' => $pro->id, 'category_id' => $properCase->id],
            ['user_id' => $pro->id, 'category_id' => $lowerCase->id],
        ]);

        $this->artisan('taxonomy:switch --remap')->assertSuccessful();

        $target = Category::anyTaxonomy()->where('taxonomy_version', 'v2')
            ->where('name', 'Catering & Food Services')->firstOrFail();

        $landed = DB::table('category_user')->where('user_id', $pro->id)->pluck('category_id')->unique();

        $this->assertSame([$target->id], $landed->values()->all(), 'both spellings must land on the same v2 category');
    }
}
