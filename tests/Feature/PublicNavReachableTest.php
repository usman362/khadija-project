<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public navbar has to lead somewhere a signed-out visitor can actually go.
 *
 * The first link said "Find Gigs" and pointed at /browse for anyone not signed
 * in. /browse was later put behind auth for its own good reasons, and nobody
 * went back to the navbar — so every guest's first click landed on a login
 * page. A fix to one screen quietly closed the door to another, which is the
 * kind of break only a test across the two notices.
 */
class PublicNavReachableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_the_primary_nav_link_opens_for_a_signed_out_visitor(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<a href="([^"]*)" class="lpn-link">([^<]*)<\/a>/', $html, $m);

        $this->assertNotEmpty($m, 'the primary nav link was not found');
        $this->assertSame('Shop Packages', trim($m[2]));

        // The point of the test: follow it, and it must not be a login wall.
        $this->get($m[1])->assertOk();
    }

    public function test_a_professional_still_gets_the_gig_board(): void
    {
        // The label is audience-specific on purpose — a professional looking
        // for work does not want the shop window.
        $pro = \App\Models\User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $this->actingAs($pro)->get('/')->assertOk()->assertSee('Find Gigs');
    }
}
