<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\InsuranceRequirement;
use Database\Seeders\InsuranceMatrixSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reconciled workbook fills draft cells on the V2 tree. It must not become
 * a live lock — 75 "Required" services would ask almost every trade overnight.
 */
class InsuranceMatrixSeederTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('professional');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_the_sheet_fills_every_v2_service_without_enforcing_required(): void
    {
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
        config(['taxonomy.version' => 'v2']);

        $this->assertSame(75, Category::ofKind(Category::SERVICE)->where('insurance_requirement', 'required')->count());
        $this->assertSame(139, Category::ofKind(Category::SERVICE)->where('insurance_requirement', 'conditional')->count());
        $this->assertSame(27, Category::ofKind(Category::SERVICE)->where('insurance_requirement', 'not_required')->count());
        $this->assertSame(27, Category::ofKind(Category::SERVICE_CATEGORY)->whereNotNull('insurance_requirement')->count());

        $drone = Category::where('name', 'Drone Photography')->firstOrFail();
        $this->assertSame('required', $drone->insurance_requirement);
        $this->assertSame('General Liability; Aviation/Drone Liability', $drone->insurance_type);

        $this->assertFalse((bool) config('compliance.insurance_matrix_signed_off'));

        $pro = $this->pro();
        $pro->serviceCategories()->attach($drone);
        $this->assertFalse(
            InsuranceRequirement::appliesTo($pro->fresh()),
            'a matrix Required cell must not lock anyone before sign-off',
        );
    }

    public function test_the_live_parent_list_still_catches_a_v2_caterer(): void
    {
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
        config(['taxonomy.version' => 'v2']);

        $service = Category::where('name', 'Full-Service Catering')->firstOrFail();

        $pro = $this->pro();
        $pro->serviceCategories()->attach($service);

        $this->assertTrue(InsuranceRequirement::appliesTo($pro->fresh()));
    }

    public function test_reapplying_the_matrix_does_not_need_a_fresh_import(): void
    {
        $this->artisan('taxonomy:import-v2')->assertSuccessful();

        $again = InsuranceMatrixSeeder::apply();

        $this->assertSame(0, $again['missing']);
        $this->assertSame(75, $again['required']);
    }
}
