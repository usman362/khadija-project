<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\PackageProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Packages — the professional's shelf.
 *
 * The screen's whole job is telling four states apart: live, still being
 * written, finished but not live, and taken down. The one that bit hardest was
 * the first: "Save as Draft" published the package, because the mapping from
 * the button to `status` sat below an unconditional return and never ran.
 */
class MyPackagesShelfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(): User
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        UserProfile::updateOrCreate(['user_id' => $pro->id], [
            'city' => 'Baltimore', 'state' => 'MD', 'country' => 'US',
        ]);

        return $pro->fresh();
    }

    private function package(User $pro, array $attrs = []): Package
    {
        static $n = 0;
        $n++;

        return Package::create(array_merge([
            'user_id'     => $pro->id,
            'title'       => 'Package ' . $n,
            'slug'        => 'shelf-pkg-' . $n,
            'type'        => 'solo',
            'description' => 'A description.',
            'services'    => ['Photography', 'Videography'],
            'price'       => 2000,
            'coverage'    => 'Up to 8 Hours',
            'status'      => 'active',
            'is_active'   => true,
        ], $attrs));
    }

    // ── The bug that made drafts impossible ──────────────────────

    public function test_save_as_draft_does_not_publish_the_package(): void
    {
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title'      => 'Half finished',
            'is_active'  => 0,
            'services'   => ['Photography', 'Videography'],
            'price'      => 1200,
            'price_unit' => 'flat',
        ])->assertRedirect();

        $package = Package::where('title', 'Half finished')->firstOrFail();

        $this->assertSame('draft', $package->status, 'Save as Draft published it');
        $this->assertFalse((bool) $package->is_active);
    }

    public function test_a_draft_can_be_saved_before_the_price_and_services_exist(): void
    {
        // Step 2 of "How Publishing Works" says save a draft at any point. The
        // form used to demand two services and a price whichever button was
        // pressed, so stopping halfway was impossible.
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title'     => 'Just an idea',
            'is_active' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('packages', ['title' => 'Just an idea', 'status' => 'draft', 'price' => 0]);
    }

    public function test_publishing_still_demands_two_services_and_a_price(): void
    {
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title'     => 'Not ready',
            'is_active' => 1,
        ])->assertSessionHasErrors(['services', 'price']);

        $this->assertDatabaseMissing('packages', ['title' => 'Not ready']);
    }

    public function test_a_published_package_is_never_priced_at_zero(): void
    {
        // "Starting at $0" is worse than no package at all.
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title'      => 'Free somehow',
            'is_active'  => 1,
            'services'   => ['Photography', 'Videography'],
            'price'      => 0,
            'price_unit' => 'flat',
        ])->assertSessionHasErrors('price');
    }

    // ── Ready vs draft ───────────────────────────────────────────

    public function test_a_finished_draft_is_ready_to_publish_not_a_draft(): void
    {
        $pro = $this->pro();
        $done = $this->package($pro, ['status' => 'draft', 'is_active' => false]);
        $half = $this->package($pro, ['status' => 'draft', 'is_active' => false, 'price' => 0, 'coverage' => null, 'availability' => null]);

        $this->assertSame('ready', PackageProgress::shelfState($done));
        $this->assertSame('draft', PackageProgress::shelfState($half));
    }

    public function test_the_progress_bar_names_the_step_that_is_stopping_it(): void
    {
        $pro = $this->pro();
        $this->package($pro, [
            'title' => 'Stuck one', 'status' => 'draft', 'is_active' => false,
            'services' => ['Photography'], 'price' => 0, 'coverage' => null, 'availability' => null,
        ]);

        $this->actingAs($pro)->get(route('professional.packages.index'))
            ->assertOk()
            // "50% complete" alone tells nobody what to do next.
            ->assertSee('Step 2 of 4: Services &amp; Deliverables', false)
            ->assertSee('at least two services')
            ->assertSee('Pricing not set');
    }

    public function test_percent_counts_the_wizards_own_four_steps(): void
    {
        $pro = $this->pro();

        $none = $this->package($pro, ['title' => 'T', 'description' => null, 'services' => [], 'price' => 0, 'coverage' => null, 'availability' => null]);
        $half = $this->package($pro, ['services' => ['Photography', 'Videography'], 'price' => 0, 'coverage' => null, 'availability' => null]);
        $full = $this->package($pro);

        $this->assertSame(0, PackageProgress::percent($none));
        $this->assertSame(50, PackageProgress::percent($half));
        $this->assertSame(100, PackageProgress::percent($full));
    }

    // ── The tiles count what the tabs show (R1/R6) ───────────────

    public function test_every_tile_counts_the_list_its_tab_opens(): void
    {
        $pro = $this->pro();
        $this->package($pro);                                                        // published
        $this->package($pro, ['status' => 'draft', 'is_active' => false]);            // ready
        $this->package($pro, ['status' => 'draft', 'is_active' => false, 'price' => 0, 'coverage' => null, 'availability' => null]); // draft
        $this->package($pro, ['status' => 'paused', 'is_active' => false]);           // unpublished
        $this->package($pro, ['status' => 'archived', 'is_active' => false]);         // archived

        $page = $this->actingAs($pro)->get(route('professional.packages.index'))->assertOk();
        $counts = $page->viewData('counts');

        $this->assertSame(5, $counts['all']);

        foreach (['published', 'draft', 'ready', 'unpublished', 'archived'] as $state) {
            $this->assertSame(1, $counts[$state], "the {$state} tile counts one");

            $rows = $this->actingAs($pro)->get(route('professional.packages.index', ['tab' => $state]))
                ->assertOk()->viewData('packages');

            $this->assertCount($counts[$state], $rows, "the {$state} tab shows what its tile counted");
        }
    }

    public function test_another_professionals_packages_are_not_on_my_shelf(): void
    {
        $mine = $this->pro();
        $theirs = $this->pro();
        $this->package($mine, ['title' => 'Mine']);
        $this->package($theirs, ['title' => 'Theirs']);

        $this->actingAs($mine)->get(route('professional.packages.index'))
            ->assertOk()->assertSee('Mine')->assertDontSee('Theirs');
    }

    // ── Search and sort ──────────────────────────────────────────

    public function test_search_matches_the_title_the_description_and_the_services(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Drone add-on', 'description' => 'Aerial work.', 'services' => ['Videography', 'Photography']]);
        $this->package($pro, ['title' => 'Reception DJ', 'description' => 'Music all night.', 'services' => ['DJ / Entertainment', 'Lighting & Tech']]);

        // Asserted on the result list rather than the page: the rail's
        // duplicate picker names every package by design, so a whole-page
        // assertDontSee would be testing the picker, not the search.
        $titles = fn (string $q) => $this->actingAs($pro)
            ->get(route('professional.packages.index', ['q' => $q]))
            ->assertOk()->viewData('packages')
            ->getCollection()->pluck('title')->all();

        $this->assertSame(['Drone add-on'], $titles('drone'));
        $this->assertSame(['Reception DJ'], $titles('lighting'), 'a service name is searchable');
        $this->assertSame(['Reception DJ'], $titles('all night'), 'the description is searchable');
    }

    public function test_the_sort_actually_reorders(): void
    {
        $pro = $this->pro();
        $this->package($pro, ['title' => 'Cheap', 'price' => 500]);
        $this->package($pro, ['title' => 'Dear', 'price' => 9000]);

        $high = $this->actingAs($pro)->get(route('professional.packages.index', ['sort' => 'price_high']))
            ->assertOk()->viewData('packages');

        $this->assertSame('Dear', $high->first()->title);

        $low = $this->actingAs($pro)->get(route('professional.packages.index', ['sort' => 'price_low']))
            ->assertOk()->viewData('packages');

        $this->assertSame('Cheap', $low->first()->title);
    }

    // ── Duplicate ────────────────────────────────────────────────

    public function test_a_duplicate_is_always_a_draft(): void
    {
        // Publishing a copy the professional has not read yet would put two
        // near-identical offerings in front of the same client.
        $pro = $this->pro();
        $original = $this->package($pro, ['title' => 'Wedding Gold']);

        $this->actingAs($pro)->post(route('professional.packages.duplicate', $original))->assertRedirect();

        $copy = Package::where('title', 'Wedding Gold (Copy)')->firstOrFail();

        $this->assertSame('draft', $copy->status);
        $this->assertFalse((bool) $copy->is_active);
        $this->assertNotSame($original->slug, $copy->slug);
        $this->assertSame($original->services, $copy->services);
    }

    public function test_one_professional_cannot_duplicate_anothers_package(): void
    {
        $mine = $this->pro();
        $theirs = $this->package($this->pro());

        $this->actingAs($mine)->post(route('professional.packages.duplicate', $theirs))->assertForbidden();

        $this->assertSame(1, Package::count());
    }

    // ── Preview as Client ────────────────────────────────────────

    public function test_the_owner_can_preview_a_package_that_is_not_live(): void
    {
        // A preview that only works once the thing is published is not a preview.
        $pro = $this->pro();
        $draft = $this->package($pro, ['title' => 'Not live yet', 'status' => 'draft', 'is_active' => false]);

        $this->actingAs($pro)->get(route('public.package', $draft->slug))
            ->assertOk()
            ->assertSee('Not live yet')
            ->assertSee('this package is not live');
    }

    public function test_nobody_else_can_open_an_unpublished_package(): void
    {
        $draft = $this->package($this->pro(), ['status' => 'draft', 'is_active' => false]);

        $this->get(route('public.package', $draft->slug))->assertNotFound();
        $this->actingAs($this->pro())->get(route('public.package', $draft->slug))->assertNotFound();
    }

    public function test_previewing_does_not_put_the_package_in_recently_viewed(): void
    {
        // That rail is public; it would then offer a package nobody can open.
        $pro = $this->pro();
        $draft = $this->package($pro, ['status' => 'draft', 'is_active' => false]);

        $this->actingAs($pro)->get(route('public.package', $draft->slug))->assertOk();

        $this->assertSame([], session('recent_packages', []));
    }

    // ── Publish / unpublish round trip ───────────────────────────

    public function test_unpublish_hides_it_from_package_search_and_republish_brings_it_back(): void
    {
        $pro = $this->pro();
        $package = $this->package($pro, ['title' => 'On the shelf']);

        $this->actingAs($pro)->patch(route('professional.packages.status', $package), ['status' => 'paused']);
        $this->assertSame('unpublished', PackageProgress::shelfState($package->fresh()));
        $this->assertFalse((bool) $package->fresh()->is_active);
        $this->get(route('public.packages'))->assertOk()->assertDontSee('On the shelf');

        $this->actingAs($pro)->patch(route('professional.packages.status', $package), ['status' => 'active']);
        $this->assertSame('published', PackageProgress::shelfState($package->fresh()));
        $this->get(route('public.packages'))->assertOk()->assertSee('On the shelf');
    }

    // ── Deleting a copy must not take the original's pictures ────

    public function test_deleting_a_duplicate_leaves_the_originals_images_alone(): void
    {
        $pro = $this->pro();
        $images = [['hero' => 'packages/1/a-hero.jpg', 'square' => 'packages/1/a-sq.jpg']];
        $original = $this->package($pro, ['title' => 'Has photos', 'images' => $images]);

        $this->actingAs($pro)->post(route('professional.packages.duplicate', $original));
        $copy = Package::where('title', 'Has photos (Copy)')->firstOrFail();

        $this->assertSame($images, $copy->images, 'a copy points at the same files');

        $this->actingAs($pro)->delete(route('professional.packages.destroy', $copy))->assertRedirect();

        // The original still names the same file, so the file had to survive.
        $this->assertSame($images, $original->fresh()->images);
    }
}
