<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-03: a signed-in user had no way back to the public site.
 *
 * He assumed the logo was the only route there. It isn't one at all — the
 * sidebar logo goes to the client dashboard, which is correct for a workspace
 * and means the homepage was unreachable from inside the portal by any means.
 *
 * The link sits at the foot of the sidebar beside Contact Support, not at the
 * top. It is a "leave the workspace" action rather than a place to work; above
 * Dashboard it would read as a second front door and compete with the one
 * people use every day.
 */
class HomeLinkFromPortalTest extends TestCase
{
    use RefreshDatabase;

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

        return User::findOrFail($u->id);
    }

    private function pro(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function influencer(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'influencer']);
        $u->assignRole('influencer');

        // The dashboard sends anyone without an Influencer record to the join
        // page — correctly — so a bare account never reaches the layout being
        // checked here.
        \App\Models\Influencer::create([
            'user_id' => $u->id,
            'full_name' => $u->name,
            'email' => $u->email,
            'referral_code' => 'TEST'.$u->id,
        ]);

        return User::findOrFail($u->id);
    }

    /**
     * All three portals, because Sir Peter asked for "logged-in users" — and a
     * way home that exists on one side only is the same complaint again from
     * whichever side missed out.
     */
    public function test_every_portal_offers_a_labelled_way_home(): void
    {
        $portals = [
            'client' => [$this->client(), '/client/dashboard'],
            'professional' => [$this->pro(), '/professional/dashboard'],
            'influencer' => [$this->influencer(), '/influencer/dashboard'],
        ];

        $missing = [];

        foreach ($portals as $name => [$user, $path]) {
            $html = $this->actingAs($user)->get($path)->getContent();

            // Labelled, not just an icon — that was the whole complaint.
            $hasLabel = str_contains($html, '>Home</span>');
            $hasLink = str_contains($html, 'href="'.route('landing').'"');

            if (! $hasLabel || ! $hasLink) {
                $missing[] = $name;
            }
        }

        $this->assertSame([], $missing, 'no way home from: '.implode(', ', $missing));
    }

    /** And it goes to the public homepage, which opens for them. */
    public function test_the_link_actually_reaches_the_homepage(): void
    {
        $this->actingAs($this->client())->get(route('landing'))->assertOk();
    }

    /**
     * The logo still goes to the dashboard. That is deliberate — inside a
     * workspace the brand mark should return you to your own front page, not
     * sign-posted marketing — and it is why a separate Home link was needed.
     */
    public function test_the_logo_still_returns_to_the_dashboard(): void
    {
        $html = $this->actingAs($this->client())->get('/client/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="'.route('client.dashboard').'" class="cl-sidebar-brand"',
            $html,
        );
    }
}
