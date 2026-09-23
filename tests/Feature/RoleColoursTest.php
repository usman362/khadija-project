<?php

namespace Tests\Feature;

use App\Support\RoleColours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The role colours, set in stone on 23 September 2026 (OA-164).
 *
 * Each role has exactly one Light and one Dark value and no third shade. The
 * values they replace — #F97316, #2563EB, #7C3AED, #F3E8FF, #EDE9FE, #5B21B6
 * and the pink that used to mean Influencer — must not identify a role
 * anywhere, which is what the page checks below are for.
 *
 * Brand colour is a separate thing and is deliberately not checked: "Client
 * orange does not mean every Client-facing page must have an orange header",
 * and the same document says not to recolour the platform.
 */
class RoleColoursTest extends TestCase
{
    use RefreshDatabase;

    /** Light, Dark, straight out of the table. */
    private const TABLE = [
        'client'       => ['#FFF7ED', '#EA580C'],
        'professional' => ['#EFF6FF', '#1D4ED8'],
        'influencer'   => ['#F5F3FF', '#6D28D9'],
        'admin'        => ['#F0FDF4', '#15803D'],
        'affiliate'    => ['#FDF2F8', '#DB2777'],
    ];

    public function test_every_role_has_the_two_values_it_was_given(): void
    {
        foreach (self::TABLE as $role => [$light, $dark]) {
            $this->assertSame($light, RoleColours::tintFor($role), "Light value for {$role}");
            $this->assertSame($dark, RoleColours::strongFor($role), "Dark value for {$role}");
        }

        $this->assertSame(array_keys(self::TABLE), array_keys(RoleColours::ROLES));
    }

    public function test_the_registration_role_chooser_uses_them(): void
    {
        $page = $this->get(route('register'))->assertOk()->getContent();

        foreach (['client', 'professional', 'influencer'] as $role) {
            $this->assertStringContainsString('data-role="' . $role . '" data-c="' . RoleColours::strongFor($role) . '"', $page);
        }

        // Influencer was pink here, which now means Affiliate.
        foreach (['#ec4899', '#2563eb', '#f97316'] as $superseded) {
            $this->assertStringNotContainsString('data-c="' . $superseded . '"', $page);
        }
    }
}
