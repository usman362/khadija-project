<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every portal and public page sets @section('title'), and for a long time the
 * layouts ignored it -- they title themselves through _seo_meta, which reads a
 * $seoTitle view variable the pages never set. So the browser tab, the bookmark
 * and the open-tab list all read as the marketing homepage on every page.
 *
 * The fix feeds the section into _seo_meta at the layout level, brand-suffixed
 * only when the page's own title does not already carry it. These guard both
 * halves: the section is used, and neither convention double-brands.
 */
class PortalPageTitleTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->givePermissionTo('dashboard.view');

        return $u->fresh();
    }

    private function titleOf(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/s', $html, $m);

        return trim($m[1] ?? '');
    }

    public function test_a_portal_page_uses_its_own_title_not_the_homepage_default(): void
    {
        $html = $this->actingAs($this->client())
            ->get(route('client.toolkit.plan'))->assertOk()->getContent();

        $title = $this->titleOf($html);

        $this->assertStringContainsString('Plan with Toolkit', $title);
        $this->assertStringNotContainsString('Hire trusted event professionals', $title,
            'The page fell back to the marketing homepage title.');
    }

    public function test_a_bare_title_is_brand_suffixed_once(): void
    {
        $title = $this->titleOf($this->actingAs($this->client())
            ->get(route('client.toolkit.plan'))->getContent());

        // "Plan with Toolkit | GigResource" — exactly one brand.
        $this->assertSame(1, substr_count($title, 'GigResource'),
            "Expected exactly one brand in [{$title}].");
    }

    public function test_a_title_that_already_carries_the_brand_is_not_doubled(): void
    {
        // Public page whose @section('title') already ends in "— GigResource".
        $title = $this->titleOf($this->get(route('public.event-types'))->assertOk()->getContent());

        $this->assertStringContainsString('Explore by Event Type', $title);
        $this->assertSame(1, substr_count($title, 'GigResource'),
            "A brand-carrying title was doubled: [{$title}].");
    }
}
