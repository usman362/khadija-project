<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filling in a test professional's services.
 *
 * Ali, 28 October: the professional account left on production after the purge
 * offers nothing at all, so it cannot bid, which means it has no proposals, no
 * bookings and no agreements. Opening the new agreement screens on that account
 * shows an empty page and reads as "nothing was built".
 *
 * A command rather than a seeder, because the seeders run over whole tables and
 * CategorySeeder running once on this site made a second copy of all 360
 * categories, which the client read as their uploaded pictures having been
 * deleted. This touches one named account.
 */
class AProfessionalCanBeGivenEveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function services(int $how_many): void
    {
        for ($i = 1; $i <= $how_many; $i++) {
            Category::create([
                'name'      => "Service {$i}",
                'slug'      => "service-{$i}",
                'kind'      => Category::SERVICE,
                'is_active' => true,
            ]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_it_reports_without_writing_unless_told_to_apply(): void
    {
        $pro = $this->person('professional');
        $this->services(3);

        $this->artisan('professional:offer-everything', ['email' => $pro->email])
            ->expectsOutputToContain('Nothing was written')
            ->assertSuccessful();

        $this->assertSame(0, $pro->fresh()->serviceCategories()->count());
    }

    public function test_it_gives_the_professional_every_active_service(): void
    {
        $pro = $this->person('professional');
        $this->services(5);

        $this->artisan('professional:offer-everything', ['email' => $pro->email, '--apply' => true])
            ->assertSuccessful();

        $this->assertSame(5, $pro->fresh()->serviceCategories()->count());
    }

    /** An inactive service is not something anybody offers. */
    public function test_it_leaves_out_services_that_are_switched_off(): void
    {
        $pro = $this->person('professional');
        $this->services(3);

        Category::where('slug', 'service-2')->update(['is_active' => false]);

        $this->artisan('professional:offer-everything', ['email' => $pro->email, '--apply' => true])
            ->assertSuccessful();

        $this->assertSame(2, $pro->fresh()->serviceCategories()->count());
    }

    /**
     * The specialties survive, and this is the one worth testing.
     *
     * The services and the level-4 specialties share one pivot.
     * serviceCategories()->sync() would wipe every specialty on its way past,
     * because sync's delete ignores the kind filter the relation puts on its
     * reads. User::syncServices exists for that reason and this proves the
     * command went through it.
     */
    public function test_it_does_not_wipe_the_level_four_specialties(): void
    {
        $pro = $this->person('professional');
        $this->services(2);

        $specialty = Category::create([
            'name' => 'Fine Art Wedding Photography', 'slug' => 'fine-art-wedding-photography',
            'kind' => Category::SERVICE_SPECIALTY, 'is_active' => true,
        ]);

        $pro->syncSpecialties([$specialty->id]);

        $this->assertSame(1, $pro->fresh()->belongsToMany(Category::class)
            ->where('categories.kind', Category::SERVICE_SPECIALTY)->count());

        $this->artisan('professional:offer-everything', ['email' => $pro->email, '--apply' => true])
            ->assertSuccessful();

        $this->assertSame(
            1,
            $pro->fresh()->belongsToMany(Category::class)
                ->where('categories.kind', Category::SERVICE_SPECIALTY)->count(),
            'Adding the services wiped the specialties.',
        );
        $this->assertSame(2, $pro->fresh()->serviceCategories()->count());
    }

    // ── What it refuses ──────────────────────────────────────

    public function test_it_refuses_an_email_that_is_not_here(): void
    {
        $this->artisan('professional:offer-everything', ['email' => 'nobody@example.com'])
            ->expectsOutputToContain('No account with the email')
            ->assertFailed();
    }

    /** A client with services would be data nothing ever reads. */
    public function test_it_refuses_an_account_that_is_not_a_professional(): void
    {
        $client = $this->person('client');
        $this->services(2);

        $this->artisan('professional:offer-everything', ['email' => $client->email, '--apply' => true])
            ->expectsOutputToContain('is not a professional')
            ->assertFailed();

        $this->assertSame(0, $client->fresh()->serviceCategories()->count());
    }

    /**
     * Rows and distinct names are counted separately, and said separately.
     *
     * The legacy tree repeats one service name under many event types, so a
     * count of pivot rows read as a count of services is how category numbers
     * have been misquoted on this project before. Today's taxonomy happens to
     * have no repeats at level 3, which is exactly why the command prints both
     * rather than assuming.
     */
    public function test_it_reports_rows_and_distinct_names_separately(): void
    {
        $pro = $this->person('professional');

        foreach (['a', 'b'] as $slug) {
            Category::create([
                'name' => 'Event Photography', 'slug' => "event-photography-{$slug}",
                'kind' => Category::SERVICE, 'is_active' => true,
            ]);
        }

        $this->artisan('professional:offer-everything', ['email' => $pro->email])
            ->expectsOutputToContain('Active services on the platform: 2')
            ->expectsOutputToContain('Distinct service names: 1')
            ->assertSuccessful();
    }
}
