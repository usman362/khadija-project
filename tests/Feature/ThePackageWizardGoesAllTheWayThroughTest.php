<?php

namespace Tests\Feature;

use App\Models\{Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Create a Package, from the first field to a published row.
 *
 * It is one form across five steps, switched with script, so every field from
 * every step arrives in a single post. That is easy to break quietly: a
 * required field inside a step nobody is looking at stops the submit with
 * nothing on screen to say why, and a renamed input is simply dropped on the
 * way through.
 *
 * Walked in a browser first and it holds up end to end. This keeps it that
 * way.
 */
class ThePackageWizardGoesAllTheWayThroughTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'professional', 'name' => 'Hollis Tremaine']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->professional = User::findOrFail($u->id);

        /*
         * The step 2 palette is not a constant: it is the active service
         * categories, read from the database and cached for an hour. With
         * none of them a professional cannot publish at all, and the only
         * thing said is "the services field is required" beside an empty list
         * of checkboxes.
         */
        \Illuminate\Support\Facades\Cache::forget('packages.service-filter.level2');

        foreach (['Catering & Food Services', 'DJs, Live Bands & Musicians'] as $name) {
            \App\Models\Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'kind' => \App\Models\Category::SERVICE_CATEGORY, 'is_active' => true],
            );
        }
    }

    /** The two names step 2 will actually accept. */
    private function palette(): array
    {
        return array_slice(\App\Http\Controllers\Public\PackageController::services(), 0, 2);
    }

    /** Everything the five steps collect, as the one form posts it. */
    private function wholeForm(array $override = []): array
    {
        return array_merge([
            'title'        => 'Elegant Wedding Photo and Video',
            'purpose'      => 'Full day coverage with an edited album',
            'description'  => 'Photography and cinematic video for the whole day.',
            'event_types'  => ['Weddings', 'Engagements'],
            // From the palette, not typed: the controller keeps only names
            // that are in it, so a hand-written one is dropped and the package
            // saves with no services at all.
            'services'     => $this->palette(),
            'guest_min'    => 50,
            'guest_max'    => 150,
            'price'        => 3250,
            'price_unit'   => 'from',
            'duration'     => '8 hours',
            'coverage'     => 'Up to 8 hours',
            'availability' => 'Weekends, April to October',
        ], $override);
    }

    public function test_the_page_opens(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.packages.create'))
            ->assertSuccessful()
            ->assertSee('Package Details');
    }

    public function test_publishing_keeps_every_step_s_answers(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.packages.store'), $this->wholeForm(['is_active' => '1']))
            ->assertRedirect();

        $package = Package::where('user_id', $this->professional->id)->firstOrFail();

        $this->assertSame('Elegant Wedding Photo and Video', $package->title);
        $this->assertSame('Full day coverage with an edited album', $package->purpose);
        $this->assertEqualsCanonicalizing($this->palette(), (array) $package->services);
        $this->assertSame(3250.0, (float) $package->price);
        // The two guest boxes are not stored as two columns: they are turned
        // into the label the card shows, plus a ceiling for filtering.
        $this->assertSame('50–150', $package->guests);
        $this->assertSame(150, (int) $package->guests_max);
        $this->assertTrue((bool) $package->is_active, 'Publish did not publish it.');
    }

    public function test_a_draft_needs_only_a_title(): void
    {
        // Step 1 of "How Publishing Works": save a draft at any point.
        $this->actingAs($this->professional)
            ->post(route('professional.packages.store'), ['title' => 'Half an idea', 'is_active' => '0'])
            ->assertRedirect();

        $package = Package::where('user_id', $this->professional->id)->firstOrFail();

        $this->assertFalse((bool) $package->is_active, 'A draft went live.');
    }

    public function test_publishing_without_the_later_steps_is_refused(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.packages.store'), ['title' => 'Half an idea', 'is_active' => '1'])
            ->assertSessionHasErrors();

        $this->assertSame(0, Package::where('user_id', $this->professional->id)->count());
    }

    public function test_a_published_package_is_the_professionals_own(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.packages.store'), $this->wholeForm(['is_active' => '1']));

        $this->assertSame(
            $this->professional->id,
            Package::firstOrFail()->user_id,
            'A package was filed against somebody else.'
        );
    }

    /**
     * The sidebar sells the package with "Build trust with verified badge".
     * Every verification mark was switched off on 23 September, so unless one
     * is actually shown, that line is selling something invisible.
     */
    public function test_no_badge_is_promised_while_badges_are_off(): void
    {
        $html = $this->actingAs($this->professional)
            ->get(route('professional.packages.create'))->getContent();

        if (! \App\Support\VerifiedBadge::shown()) {
            $this->assertStringNotContainsString('verified badge', $html);
        } else {
            $this->assertStringContainsString('verified badge', $html);
        }
    }
}
