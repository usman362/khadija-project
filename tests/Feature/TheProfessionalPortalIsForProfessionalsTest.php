<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Who may open the professional portal.
 *
 * All eighty of its routes sat behind permission:dashboard.view, which the
 * client, professional, influencer and admin roles all hold. A signed-in
 * client could open any address in it.
 *
 * Nothing leaked while that was true: every page reads rows where the reader
 * is the supplier, and a client is the supplier on none of them, so they saw
 * empty screens. But "it happens to be empty" is not a boundary. It was
 * eighty routes each keeping the door shut by itself, and the first query
 * written without that in mind would have opened it.
 *
 * This walks the whole group rather than naming pages, so a route added
 * later is covered the day it is added.
 */
class TheProfessionalPortalIsForProfessionalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

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

    /**
     * Every GET in the portal that takes no parameters.
     *
     * The ones with a {booking} or an {event} in them are left out: those
     * need a record to exist before the route means anything, and what is
     * being tested here is the door rather than what is behind it.
     *
     * @return list<string>
     */
    private function plainPages(): array
    {
        $out = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! str_starts_with($name, 'professional.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
                continue;
            }

            $out[] = $name;
        }

        return $out;
    }

    public function test_there_are_pages_to_check(): void
    {
        // If this ever drops to nothing, the walk below is passing vacuously.
        $this->assertGreaterThan(15, count($this->plainPages()));
    }

    public function test_a_client_is_refused_everywhere_in_the_portal(): void
    {
        $client = $this->person('client');

        foreach ($this->plainPages() as $name) {
            $status = $this->actingAs($client)->get(route($name))->baseResponse->getStatusCode();

            $this->assertSame(403, $status, "A client reached {$name}.");
        }
    }

    public function test_an_influencer_is_refused_too(): void
    {
        $influencer = $this->person('influencer');

        // Their own portal is separate, no influencer is the supplier on any
        // booking, and the day one is, which portal they sign in is a
        // decision rather than an oversight.
        $this->actingAs($influencer)
            ->get(route('professional.dashboard'))
            ->assertForbidden();
    }

    public function test_a_stranger_is_sent_to_sign_in(): void
    {
        $this->get(route('professional.dashboard'))->assertRedirect(route('login'));
    }

    // ── And the people it is for still get in ────────────────

    public function test_a_professional_still_reaches_their_own_portal(): void
    {
        $pro = $this->person('professional');

        foreach ($this->plainPages() as $name) {
            $status = $this->actingAs($pro)->get(route($name))->baseResponse->getStatusCode();

            $this->assertNotSame(403, $status, "A professional was refused {$name}.");
        }
    }

    /**
     * Including one who is a client as well.
     *
     * The gate is the ROLE, not the active MODE. A dual-role account defaults
     * to client mode on purpose — User::activeRole prefers client so nobody
     * lands in the professional portal by accident — so gating on mode would
     * lock a real professional out of their own pages until they switched.
     */
    public function test_somebody_who_is_both_is_not_locked_out(): void
    {
        $both = $this->person('professional');
        $both->assignRole('client');
        $both = User::findOrFail($both->id);

        $this->assertTrue($both->isClientMode(), 'A dual-role account should start in client mode.');

        $this->actingAs($both)
            ->get(route('professional.dashboard'))
            ->assertSuccessful();
    }

    public function test_an_administrator_gets_in(): void
    {
        $this->actingAs($this->person('admin'))
            ->get(route('professional.dashboard'))
            ->assertSuccessful();
    }
}
