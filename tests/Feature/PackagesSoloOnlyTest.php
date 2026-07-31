<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Team / Co-Op was retired platform-wide on 2026-07-15, but the decision only
 * half-landed: the behaviour went and the column, the relation and four rows
 * naming a partner stayed for two weeks. These guard the parts that could drift
 * back without anyone noticing, since nothing on screen would look different.
 */
class PackagesSoloOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function package(array $attrs = []): Package
    {
        $pro = User::factory()->create();

        return Package::create(array_merge([
            'user_id'     => $pro->id,
            'title'       => 'Package ' . uniqid(),
            'slug'        => 'pkg-' . uniqid(),
            'type'        => 'solo',
            'price'       => 1000,
            'services'    => ['Photography'],
            'event_types' => ['Wedding'],
            'is_active'   => true,
            'status'      => 'active',
        ], $attrs));
    }

    public function test_the_coop_partner_column_is_gone(): void
    {
        $this->assertFalse(
            Schema::hasColumn('packages', 'coop_partner_id'),
            'the co-op partner column is part of a retired product',
        );
    }

    public function test_a_partner_cannot_be_mass_assigned_back_on(): void
    {
        $package = $this->package(['coop_partner_id' => User::factory()->create()->id]);

        $this->assertArrayNotHasKey('coop_partner_id', $package->getAttributes());
    }

    public function test_the_public_listing_hides_nothing(): void
    {
        // The controller carries a "solo-only" comment but no filter, which is
        // correct now that every package is solo — if someone adds the filter
        // back to match the comment, packages would silently vanish.
        $this->package();
        $this->package();
        $this->package(['status' => 'draft']);   // not listed — scopeActive

        $response = $this->get(route('public.packages'));

        $response->assertSuccessful();
        $this->assertCount(2, $response->viewData('packages'));
    }
}
