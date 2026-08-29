<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A report that uploaded category pictures had "gone" after a seeder run.
 *
 * Nothing in this codebase deletes one. What DOES happen is
 * `categories:reclassify-imagery` moving a banner-shaped picture out of
 * `thumbnail` and into `cover_image`, leaving `thumbnail` empty — the card then
 * shows a coloured letter and reads as though the upload was lost. The file is
 * untouched and the path is in the other column.
 *
 * These lock the recovery, and the two rules that make it safe to run.
 */
class CategoryImageryRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function category(array $attrs): Category
    {
        return Category::create(array_merge([
            'name' => 'Anniversary Party',
            'slug' => 'anniversary-party-' . uniqid(),
            'kind' => Category::EVENT_TYPE,
        ], $attrs));
    }

    public function test_a_moved_picture_comes_back(): void
    {
        Storage::disk('public')->put('categories/covers/gala.jpg', 'x');

        $cat = $this->category(['thumbnail' => null, 'cover_image' => 'categories/covers/gala.jpg']);

        $this->artisan('categories:restore-thumbnails --apply')->assertSuccessful();

        $this->assertSame('categories/covers/gala.jpg', $cat->fresh()->thumbnail);
    }

    /** Dry by default: looking must never change anything. */
    public function test_nothing_is_written_without_apply(): void
    {
        Storage::disk('public')->put('categories/covers/gala.jpg', 'x');

        $cat = $this->category(['thumbnail' => null, 'cover_image' => 'categories/covers/gala.jpg']);

        $this->artisan('categories:restore-thumbnails')->assertSuccessful();

        $this->assertNull($cat->fresh()->thumbnail);
    }

    /** A picture somebody already has must not be replaced by this. */
    public function test_an_existing_thumbnail_is_left_alone(): void
    {
        Storage::disk('public')->put('categories/thumbnails/theirs.jpg', 'x');
        Storage::disk('public')->put('categories/covers/ours.jpg', 'x');

        $cat = $this->category([
            'thumbnail'   => 'categories/thumbnails/theirs.jpg',
            'cover_image' => 'categories/covers/ours.jpg',
        ]);

        $this->artisan('categories:restore-thumbnails --apply')->assertSuccessful();

        $this->assertSame('categories/thumbnails/theirs.jpg', $cat->fresh()->thumbnail);
    }

    /** Better a blank card than a broken image. */
    public function test_a_file_that_is_not_on_disk_is_not_restored(): void
    {
        $cat = $this->category(['thumbnail' => null, 'cover_image' => 'categories/covers/gone.jpg']);

        $this->artisan('categories:restore-thumbnails --apply')->assertSuccessful();

        $this->assertNull($cat->fresh()->thumbnail);
    }

    /** The report reads the database and writes nothing. */
    public function test_the_diagnosis_changes_nothing(): void
    {
        Storage::disk('public')->put('categories/covers/gala.jpg', 'x');
        $cat = $this->category(['thumbnail' => null, 'cover_image' => 'categories/covers/gala.jpg']);

        $before = $cat->fresh()->toArray();
        $this->artisan('categories:diagnose-imagery')->assertSuccessful();

        $this->assertSame($before['thumbnail'], $cat->fresh()->thumbnail);
        $this->assertSame($before['cover_image'], $cat->fresh()->cover_image);
    }

    /**
     * The guarantee that matters most here: seeding does not take a picture
     * away from a row that already has one.
     */
    public function test_seeding_does_not_replace_an_uploaded_picture(): void
    {
        Storage::disk('public')->put('categories/thumbnails/uploaded.jpg', 'x');

        $cat = Category::create([
            'name' => 'Wedding', 'slug' => 'wedding',
            'kind' => Category::EVENT_TYPE,
            'thumbnail' => 'categories/thumbnails/uploaded.jpg',
        ]);

        $this->seed(\Database\Seeders\CategoryArtworkSeeder::class);

        $this->assertSame('categories/thumbnails/uploaded.jpg', $cat->fresh()->thumbnail);
    }
}
