<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Influencer;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Resources section of the affiliate portal was retired on 2026-11-12
 * (owner's call: no value in it). Five pages, a sidebar group, a controller,
 * a model, a seeder and a table.
 *
 * These guard the two halves that drift back separately. The pages are the
 * loud half: delete a route and the next request is a 404 somebody notices.
 * The table is the quiet half, and this project has already paid for leaving
 * one behind once, so it is asserted here rather than trusted.
 */
class AffiliateResourcesRemovedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public static function retiredRoutes(): array
    {
        return [
            ['influencer.resources.getting-started'],
            ['influencer.resources.library'],
            ['influencer.resources.academy'],
            ['influencer.resources.tutorials'],
            ['influencer.resources.articles'],
        ];
    }

    /** @dataProvider retiredRoutes */
    public function test_the_resource_pages_have_no_route(string $name): void
    {
        $this->assertFalse(
            Route::has($name),
            "{$name} belongs to a retired section of the affiliate portal",
        );
    }

    public function test_the_resource_urls_are_not_reachable(): void
    {
        $this->actingAs($this->approvedAffiliate());

        foreach (['library', 'academy', 'tutorials', 'articles', 'getting-started'] as $page) {
            $this->get("/influencer/resources/{$page}")
                ->assertNotFound();
        }
    }

    public function test_the_sidebar_offers_no_resources_group(): void
    {
        $page = $this->actingAs($this->approvedAffiliate())
            ->get(route('influencer.dashboard'))
            ->assertOk()
            ->getContent();

        // The group heading and each of its five children. "Resources" alone
        // is too common a word to assert on; the children are not.
        foreach (['Resource Library', 'Academy', 'Tutorials', 'Featured Articles'] as $label) {
            $this->assertStringNotContainsString($label, $page);
        }
    }

    public function test_the_content_table_is_gone(): void
    {
        $this->assertFalse(
            Schema::hasTable('influencer_resources'),
            'storage for a retired section is a query away from resurrecting it',
        );
    }

    private function approvedAffiliate(): User
    {
        $user = User::create([
            'name' => 'Affiliate ' . uniqid(),
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'primary_role' => RoleName::INFLUENCER->value,
        ]);
        $user->assignRole(RoleName::INFLUENCER->value);

        Influencer::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'referral_code' => strtoupper(substr(uniqid(), -8)),
            'commission_tier' => 'starter',
            'status' => 'approved',
        ]);

        return $user->fresh();
    }
}
