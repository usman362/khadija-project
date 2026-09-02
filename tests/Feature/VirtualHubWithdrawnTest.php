<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah, 2026-08-31: remove the Virtual & Hybrid Hub pages and every web
 * link to them.
 *
 * The removal itself was done by deleting the routes and taking the entry out
 * of the client sidebar and the "Post an Event" chooser. What was missing is
 * anything holding it removed. The controller and its three views are still in
 * the repository against a possible rebuild, and a route file is one line away
 * from putting a withdrawn page back on the live site without anyone noticing.
 *
 * So this asserts the withdrawal rather than the feature: the addresses answer
 * 404, and the name appears in no client-facing navigation. If the Hub is ever
 * rebuilt, these tests are the thing that should be deliberately deleted —
 * which is the point.
 */
class VirtualHubWithdrawnTest extends TestCase
{
    use RefreshDatabase;

    /** Every address the Hub used to answer on. */
    public const WITHDRAWN_PATHS = [
        '/client/virtual-hub',
        '/client/virtual-hub/brief',
        '/client/virtual-hub/brief/plan',
        '/client/virtual-hub/brief/services',
    ];

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return $u;
    }

    /**
     * Signed in as the client who used to own the page — not as a guest, who
     * would be redirected to the login screen and produce a passing test that
     * proves only that the site has authentication.
     */
    public function test_the_hub_addresses_are_gone(): void
    {
        $client = $this->client();

        foreach (self::WITHDRAWN_PATHS as $path) {
            $this->actingAs($client)->get($path)->assertNotFound();
        }
    }

    /** No route may carry the name either — that is what links are built from. */
    public function test_no_route_is_named_after_the_hub(): void
    {
        $named = array_keys(app('router')->getRoutes()->getRoutesByName());

        $this->assertSame(
            [],
            array_values(array_filter($named, fn ($n) => str_contains($n, 'virtual-hub'))),
        );
    }

    /**
     * And the label is off the pages that used to offer it. Checked on the
     * rendered page, not in the Blade source, because a commented-out menu
     * item and a live one look much the same in a file.
     */
    public function test_the_client_is_not_offered_the_hub_anywhere(): void
    {
        $client = $this->client();

        foreach (['/client/dashboard', '/client/post-event'] as $path) {
            $body = $this->actingAs($client)->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString('virtual-hub', $body);

            // The visible name, in both the raw and HTML-escaped spellings.
            $this->assertStringNotContainsString('Virtual &amp; Hybrid', $body);
            $this->assertStringNotContainsString('Virtual & Hybrid', $body);
        }
    }
}
