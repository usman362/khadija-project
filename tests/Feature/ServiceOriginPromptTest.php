<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nobody had set a service origin — not because the screen was missing, but
 * because nothing ever asked. The fields have been on the profile all along;
 * of 17 professionals, zero had filled them in, which is what stops distance
 * matching from being switched on at all.
 *
 * The wording matters here. Distance matching is still OFF, so telling a
 * professional "clients cannot find you without this" would be false today.
 * What is true is that this is the thing standing in the way of turning it on.
 */
class ServiceOriginPromptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(array $profile = []): User
    {
        $u = User::factory()->create();
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update(array_merge([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ], $profile));

        return $u->fresh();
    }

    public function test_a_professional_without_an_origin_is_asked_for_one(): void
    {
        $html = $this->actingAs($this->pro())
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Where do you travel from', $html);
        $this->assertStringContainsString('Set it now', $html);
    }

    /** It must not claim something that is not true while the switch is off. */
    public function test_it_does_not_claim_clients_cannot_find_them(): void
    {
        $html = $this->actingAs($this->pro())
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->getContent();

        // Read the prompt itself, not the page. "invisible" appears in the
        // datepicker's CSS, and asserting a bare word against a whole document
        // is how a guard ends up failing for reasons that have nothing to do
        // with what it guards.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $prompt = (new \DOMXPath($dom))->query("//*[contains(@class, 'so-prompt')]")->item(0);
        $this->assertNotNull($prompt, 'The prompt did not render.');

        $words = strtolower($prompt->textContent);

        foreach (['cannot find you', "can't find you", 'will not appear', 'invisible', 'hidden from clients'] as $overclaim) {
            $this->assertStringNotContainsString($overclaim, $words,
                'The prompt claims a consequence that does not happen while distance matching is off.');
        }
    }

    public function test_a_professional_who_has_set_one_is_not_nagged(): void
    {
        $pro = $this->pro([
            'service_origin_city'  => 'Baltimore',
            'service_origin_state' => 'MD',
            'service_origin_zip'   => '21201',
            'origin_lat'           => 39.2904,
            'origin_lng'           => -76.6122,
            'origin_precision'     => \App\Domain\Geolocation\LocationPrecision::EXACT,
            'travel_radius_miles'  => 50,
        ]);

        $html = $this->actingAs($pro)
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('Where do you travel from', $html);
    }

    /** An address that could not be placed gets told so, not asked again. */
    public function test_an_address_that_could_not_be_placed_says_so(): void
    {
        $pro = $this->pro([
            'service_origin_line' => 'Nowhere At All',
            'origin_precision'    => \App\Domain\Geolocation\LocationPrecision::UNRESOLVED,
            'travel_radius_miles' => 40,
        ]);

        $html = $this->actingAs($pro)
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('could not place your service origin', $html);
        $this->assertStringContainsString('Check the address', $html);
    }

    /** The button has somewhere to land. */
    public function test_the_profile_has_the_anchor_it_points_at(): void
    {
        $html = $this->actingAs($this->pro())
            ->get(route('professional.profile.index', ['tab' => 'general']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('id="service-origin"', $html);
    }
}
