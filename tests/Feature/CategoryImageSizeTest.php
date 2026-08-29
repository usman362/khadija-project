<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\ImagePipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * One size for every category picture — Sir Peter, 29 Aug.
 *
 * The existing Level 1 pictures are 300x300, but nothing enforced it: any
 * image up to a couple of megabytes was stored exactly as uploaded, so the
 * event-type wall mixed 300x300 with whatever else had been put in.
 *
 * It crops rather than refuses. Rejecting anything that is not already square
 * would make the Owner resize every photograph by hand before he could use it;
 * a centre crop gives the same consistency and costs him nothing. What these
 * tests pin is that the STORED FILE is 300x300 whatever went in.
 */
class CategoryImageSizeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin->fresh();
    }

    /** @return array{0:int,1:int} the stored file's real dimensions */
    private function dimensions(string $path): array
    {
        $size = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$size[0], $size[1]];
    }

    public function test_a_wide_photo_is_stored_square(): void
    {
        $this->actingAs($this->admin())->post(route('app.admin.categories.store'), [
            'name'      => 'Beach Party',
            'thumbnail' => UploadedFile::fake()->image('wide.jpg', 1600, 500),
        ]);

        $category = Category::where('name', 'Beach Party')->firstOrFail();

        $this->assertNotNull($category->thumbnail);
        $this->assertSame([300, 300], $this->dimensions($category->thumbnail));
    }

    public function test_a_tall_photo_is_stored_square(): void
    {
        $this->actingAs($this->admin())->post(route('app.admin.categories.store'), [
            'name'      => 'Block Party',
            'thumbnail' => UploadedFile::fake()->image('tall.jpg', 400, 1200),
        ]);

        $this->assertSame(
            [300, 300],
            $this->dimensions(Category::where('name', 'Block Party')->firstOrFail()->thumbnail),
        );
    }

    /** A small image is scaled up rather than left inconsistent. */
    public function test_a_small_photo_is_stored_at_the_same_size(): void
    {
        $this->actingAs($this->admin())->post(route('app.admin.categories.store'), [
            'name'      => 'Club Event',
            'thumbnail' => UploadedFile::fake()->image('small.jpg', 120, 90),
        ]);

        $this->assertSame(
            [300, 300],
            $this->dimensions(Category::where('name', 'Club Event')->firstOrFail()->thumbnail),
        );
    }

    /**
     * Both columns, because `Category::imageUrl()` treats them
     * interchangeably — a 300x300 thumbnail beside a 1600x500 cover would put
     * the drift straight back on the wall.
     */
    public function test_the_cover_image_gets_the_same_treatment(): void
    {
        $this->actingAs($this->admin())->post(route('app.admin.categories.store'), [
            'name'        => 'Gala Dinner',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1600, 500),
        ]);

        $this->assertSame(
            [300, 300],
            $this->dimensions(Category::where('name', 'Gala Dinner')->firstOrFail()->cover_image),
        );
    }

    /** Editing an existing category goes through the same crop. */
    public function test_replacing_a_picture_is_cropped_too(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('app.admin.categories.store'), [
            'name'      => 'Bridal Shower',
            'thumbnail' => UploadedFile::fake()->image('first.jpg', 300, 300),
        ]);

        $category = Category::where('name', 'Bridal Shower')->firstOrFail();

        $this->actingAs($admin)->put(route('app.admin.categories.update', $category), [
            'name'      => 'Bridal Shower',
            'thumbnail' => UploadedFile::fake()->image('second.jpg', 2000, 400),
        ]);

        $this->assertSame([300, 300], $this->dimensions($category->fresh()->thumbnail));
    }

    /** The size lives in one place, and it is the one already in use. */
    public function test_the_size_is_declared_once(): void
    {
        $this->assertSame(300, ImagePipelineService::CATEGORY_SIZE);
        $this->assertSame([300, 300], ImagePipelineService::SIZES['square']);
    }
}
