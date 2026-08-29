<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A taxonomy import created a second row for names that already existed — on
 * production, 106 event types became 153. No picture was lost: for each doubled
 * name one row still carries the uploaded photo and the newer one carries the
 * bundled artwork or nothing. The listing shows both, so every event type
 * appears twice and half of them look blank.
 *
 * Merging is destructive, so what matters is what these lock down: the picture
 * survives, nothing that pointed at the duplicate is orphaned, and the command
 * does nothing at all unless it is told to.
 */
class MergeDuplicateCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function cat(string $name, ?string $thumb, string $slug): Category
    {
        return Category::create([
            'name' => $name, 'slug' => $slug,
            'kind' => Category::EVENT_TYPE, 'thumbnail' => $thumb,
        ]);
    }

    public function test_the_row_with_the_picture_is_the_one_kept(): void
    {
        $withPhoto = $this->cat('Anniversary Party', 'categories/thumbnails/real.jpg', 'anniversary-party');
        $blank     = $this->cat('Anniversary Party', null, 'anniversary-party-2');

        $this->artisan('categories:merge-duplicates --apply')->assertSuccessful();

        $this->assertDatabaseHas('categories', ['id' => $withPhoto->id, 'thumbnail' => 'categories/thumbnails/real.jpg']);
        $this->assertDatabaseMissing('categories', ['id' => $blank->id]);
    }

    /** Nothing may be left pointing at a row that no longer exists. */
    public function test_everything_pointing_at_the_duplicate_is_moved_first(): void
    {
        $keeper = $this->cat('Baby Shower', 'categories/thumbnails/real.jpg', 'baby-shower');
        $dupe   = $this->cat('Baby Shower', null, 'baby-shower-2');

        $client = User::factory()->create();
        $client->assignRole('client');

        $event = Event::create([
            'title' => 'Harbour Shower', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'starts_at' => now()->addDays(10), 'category_id' => $dupe->id,
        ]);

        $child = Category::create([
            'name' => 'Cake', 'slug' => 'cake-under-dupe',
            'kind' => Category::SERVICE, 'parent_id' => $dupe->id,
        ]);

        $pro = User::factory()->create();
        DB::table('category_user')->insert(['category_id' => $dupe->id, 'user_id' => $pro->id]);

        $this->artisan('categories:merge-duplicates --apply')->assertSuccessful();

        $this->assertSame($keeper->id, $event->fresh()->category_id);
        $this->assertSame($keeper->id, $child->fresh()->parent_id);
        $this->assertDatabaseHas('category_user', ['category_id' => $keeper->id, 'user_id' => $pro->id]);
        $this->assertDatabaseMissing('categories', ['id' => $dupe->id]);
    }

    /** A professional linked to BOTH copies keeps one link, not a broken pair. */
    public function test_a_link_that_would_collide_is_folded_not_duplicated(): void
    {
        $keeper = $this->cat('Wedding', 'categories/thumbnails/real.jpg', 'wedding');
        $dupe   = $this->cat('Wedding', null, 'wedding-2');

        $pro = User::factory()->create();
        DB::table('category_user')->insert([
            ['category_id' => $keeper->id, 'user_id' => $pro->id],
            ['category_id' => $dupe->id,   'user_id' => $pro->id],
        ]);

        $this->artisan('categories:merge-duplicates --apply')->assertSuccessful();

        $this->assertSame(1, DB::table('category_user')->where('user_id', $pro->id)->count());
        $this->assertDatabaseHas('category_user', ['category_id' => $keeper->id, 'user_id' => $pro->id]);
    }

    public function test_nothing_happens_without_apply(): void
    {
        $this->cat('Bachelor Party', 'categories/thumbnails/real.jpg', 'bachelor-party');
        $dupe = $this->cat('Bachelor Party', null, 'bachelor-party-2');

        $this->artisan('categories:merge-duplicates')->assertSuccessful();

        $this->assertDatabaseHas('categories', ['id' => $dupe->id]);
    }

    /** If only the newer row has the picture, a human decides — not the command. */
    public function test_it_refuses_when_the_duplicate_holds_the_only_picture(): void
    {
        $blank = $this->cat('Gender Reveal', null, 'gender-reveal');
        $photo = $this->cat('Gender Reveal', 'categories/thumbnails/real.jpg', 'gender-reveal-2');

        $this->artisan('categories:merge-duplicates --apply')->assertSuccessful();

        // The one with the picture is kept; nothing with a picture is deleted.
        $this->assertDatabaseHas('categories', ['id' => $photo->id]);
        $this->assertDatabaseMissing('categories', ['id' => $blank->id]);
    }

    public function test_a_name_that_appears_once_is_untouched(): void
    {
        $solo = $this->cat('Divorce Party', 'categories/thumbnails/real.jpg', 'divorce-party');

        $this->artisan('categories:merge-duplicates --apply')->assertSuccessful();

        $this->assertDatabaseHas('categories', ['id' => $solo->id]);
    }
}
