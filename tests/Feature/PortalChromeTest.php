<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A page must draw the sidebar of whoever is looking at it, and it must always
 * offer a way out of the portal it drew.
 *
 * Reported from the live site: signed in as a professional, Toolkit Tiers
 * loaded the professional's tools inside the CLIENT sidebar, and the only
 * escape was the browser's Back button. Two separate faults met there —
 *
 *   1. the controller chose the audience while the view hardcoded the chrome,
 *      so the two could disagree; and
 *   2. the role switcher named the opposite of the user's ROLE rather than the
 *      opposite of the CHROME, so a stranded professional was offered "Switch
 *      to Client" — deeper into the portal they were trying to leave.
 *
 * These tests guard the rules, not the markup.
 */
class PortalChromeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** The sidebar that appears is the sidebar of the person signed in. */
    public function test_toolkit_tiers_draws_the_professional_sidebar_for_a_professional(): void
    {
        $html = $this->actingAs($this->user('professional'))
            ->get(route('client.toolkit.tiers'))
            ->assertOk()
            ->getContent();

        // Landmarks unique to each portal's navigation.
        $this->assertStringContainsString('Gig Operations Hub', $html);
        $this->assertStringNotContainsString('Post an Event', $html);
    }

    public function test_toolkit_tiers_draws_the_client_sidebar_for_a_client(): void
    {
        $html = $this->actingAs($this->user('client'))
            ->get(route('client.toolkit.tiers'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Gig Operations Hub', $html);
    }

    /**
     * The tools shown and the chrome drawn are one decision. If they were ever
     * split again, this catches it: a professional gets professional tools.
     */
    public function test_the_tools_and_the_chrome_agree(): void
    {
        $this->actingAs($this->user('professional'))
            ->get(route('client.toolkit.tiers'))
            ->assertOk()
            ->assertViewHas('layout', 'layouts.professional')
            ->assertViewHas('audience', \App\Support\ToolkitTiers::PROFESSIONAL);

        $this->actingAs($this->user('client'))
            ->get(route('client.toolkit.tiers'))
            ->assertOk()
            ->assertViewHas('layout', 'layouts.client')
            ->assertViewHas('audience', \App\Support\ToolkitTiers::CLIENT);
    }

    /**
     * The way out. Whatever put a professional in front of the client portal,
     * the header must offer the professional one — not the browser's Back
     * button.
     */
    public function test_client_chrome_always_offers_the_way_back_to_professional(): void
    {
        $user = $this->user('professional');
        $user->assignRole('client');   // both roles; active role stays professional
        $this->actingAs($user->fresh());

        // The exact stranded case: client chrome, professional signed in.
        $html = view('partials._role_switcher', ['portal' => 'client'])->render();

        $this->assertStringContainsString('Switch to Professional', $html);
        $this->assertStringNotContainsString('Switch to Client', $html);
    }

    /** And the mirror: inside the professional portal it offers the client one. */
    public function test_professional_chrome_offers_the_client_portal(): void
    {
        $user = $this->user('professional');
        $user->assignRole('client');
        $this->actingAs($user->fresh());

        $html = view('partials._role_switcher', ['portal' => 'professional'])->render();

        $this->assertStringContainsString('Switch to Client', $html);
        $this->assertStringNotContainsString('Switch to Professional', $html);
    }

    /** The switcher must never name the portal you are already inside. */
    public function test_the_switcher_reads_the_chrome_not_the_role(): void
    {
        $partial = file_get_contents(resource_path('views/partials/_role_switcher.blade.php'));

        $this->assertStringContainsString('$__portal', $partial);
        $this->assertStringNotContainsString(
            "\$__active === 'professional' ? 'client' : 'professional'",
            $partial,
            'The switch target is being read from the role again.'
        );
    }
}
