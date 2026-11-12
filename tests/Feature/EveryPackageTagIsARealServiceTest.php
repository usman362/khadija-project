<?php

namespace Tests\Feature;

use App\Http\Controllers\Professional\ProfessionalPackageController;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A package is found by the names it is tagged with, so a name that is not in
 * the taxonomy is a package nobody can find.
 *
 * The demo data was written with a vocabulary of its own — "Catering / Food",
 * "Lighting & Tech", "DJ / Entertainment" — and eighteen of nineteen packages
 * carried at least one. Package Search returned nothing for them whatever a
 * client ticked, which is the page the owner demonstrates.
 */
class EveryPackageTagIsARealServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['taxonomy.version' => 'v2']);
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
    }

    public function test_the_old_labels_are_mapped_onto_real_services(): void
    {
        // A row as it existed before 2026-11-12, written past the model so
        // the casts do not tidy it on the way in.
        $owner = \App\Models\User::factory()->create();

        DB::table('packages')->insert([
            'user_id' => $owner->id,
            'title' => 'Legacy row',
            'slug' => 'legacy-' . uniqid(),
            'type' => 'solo',
            'price' => 2400,
            'services' => json_encode(['Catering / Food', 'Lighting & Tech', 'Add-On']),
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * The migration itself, called directly.
         *
         * Under RefreshDatabase it has already run and is recorded as such,
         * so `artisan migrate` would do nothing and the row would keep its
         * old names: the test would pass or fail for the wrong reason. This
         * runs the mapping over a row that exists, which is the only thing
         * worth proving, and it also shows the pass is safe to repeat.
         */
        $migration = require base_path(
            'database/migrations/2026_11_12_230000_a_package_tag_has_to_be_a_real_service.php'
        );
        $migration->up();

        $services = Package::where('title', 'Legacy row')->value('services');

        $this->assertNotContains('Catering / Food', $services);
        $this->assertContains('Full-Service Catering', $services);
        $this->assertNotContains('Add-On', $services, 'it was never a service');

        // And a name nobody recognises is left exactly as it is, rather than
        // quietly turned into a guess.
        DB::table('packages')->where('title', 'Legacy row')
            ->update(['services' => json_encode(['Something Nobody Has Heard Of'])]);

        $migration->up();

        $this->assertSame(
            ['Something Nobody Has Heard Of'],
            Package::where('title', 'Legacy row')->value('services'),
        );
    }

    public function test_the_demo_packages_can_all_be_found(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DemoProfessionalsSeeder::class);
        $this->seed(\Database\Seeders\DemoPackagesSeeder::class);

        $palette = collect(ProfessionalPackageController::palette());

        $unknown = Package::pluck('services')->flatten()->filter()->unique()
            ->reject(fn ($n) => $palette->contains($n))
            ->values();

        $this->assertSame(
            [],
            $unknown->all(),
            'a package tagged with a name no filter offers is a package nobody can find',
        );
    }
}
