<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\ProfessionalProfileShowController as ProfileShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /browse 500'd for anyone who had opened a professional's profile.
 *
 * The Recently Viewed rail read `$profile->portfolio` raw and took the first
 * entry. That column holds two shapes — a structured entry from an upload
 * (`type`/`featured`/`hero`/`square`) and a bare URL string on older rows — so
 * the "first entry" came back as an ARRAY, and `<img src="{{ $rvImg }}">`
 * threw `htmlspecialchars(): Argument #1 must be of type string, array given`.
 *
 * Every seeded profile in the database carries the structured shape, so the
 * page died for any visitor who had opened one profile and then gone back to
 * browse. The rest of the same page already used `portfolioHeroUrls()`, which
 * is the one place that knows both shapes.
 */
class BrowseRecentlyViewedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /**
     * Names are pinned, not Faker's.
     *
     * These assertions use assertSee($name, false) — escaping off — and Faker
     * hands out names like "Dorothea D'Amore" perhaps one run in six. Blade
     * renders that apostrophe as &#039;, so the raw name is not in the HTML and
     * the test fails for a reason that has nothing to do with browse.
     */
    private int $proCount = 0;

    private function pro(array $portfolio): User
    {
        $names = ['Halloway Sound', 'Vestry Floral', 'Quillon Lighting', 'Marbeck Catering'];

        $user = User::factory()->create([
            'name' => $names[$this->proCount++ % count($names)],
        ]);
        $user->assignRole('professional');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'portfolio' => $portfolio,
        ]);

        return $user->fresh();
    }

    /** The exact crash: a structured upload entry in Recently Viewed. */
    public function test_browse_survives_a_recently_viewed_pro_with_an_uploaded_portfolio(): void
    {
        $pro = $this->pro([[
            'type' => 'image', 'featured' => true,
            'hero' => 'portfolio/hero.jpg', 'square' => 'portfolio/square.jpg',
        ]]);

        $this->actingAs($this->client())
            ->withSession([ProfileShow::RECENT_KEY => [$pro->id]])
            ->get(route('public.browse'))
            ->assertSuccessful();
    }

    /** The other shape on the same rail. */
    public function test_browse_survives_a_recently_viewed_pro_with_a_flat_url_portfolio(): void
    {
        $pro = $this->pro(['https://example.com/work.jpg']);

        $this->actingAs($this->client())
            ->withSession([ProfileShow::RECENT_KEY => [$pro->id]])
            ->get(route('public.browse'))
            ->assertSuccessful();
    }

    /** And no portfolio at all falls back to the avatar rather than blank. */
    public function test_browse_survives_a_recently_viewed_pro_with_no_portfolio(): void
    {
        $pro = $this->pro([]);

        $this->actingAs($this->client())
            ->withSession([ProfileShow::RECENT_KEY => [$pro->id]])
            ->get(route('public.browse'))
            ->assertSuccessful()
            ->assertSee($pro->name, false);
    }

    /**
     * The real path, end to end: open a profile, then browse. This is what a
     * visitor did, and it is what broke.
     */
    public function test_opening_a_profile_then_browsing_works(): void
    {
        $pro = $this->pro([[
            'type' => 'image', 'featured' => false,
            'hero' => 'portfolio/hero.jpg', 'square' => 'portfolio/square.jpg',
        ]]);

        $client = $this->client();

        $this->actingAs($client)->get(route('public.professional.show', $pro))->assertSuccessful();
        $this->actingAs($client)->get(route('public.browse'))->assertSuccessful();
    }

    /** No view may read the column raw again — the guard is the point. */
    public function test_browse_reads_the_portfolio_through_the_shared_accessor(): void
    {
        $view = file_get_contents(resource_path('views/public/browse.blade.php'));

        // Strip Blade comments; the note explaining the bug quotes the old code.
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', $view);

        // `portfolio` as a bare property, not `portfolioHeroUrls(` — a plain
        // substring check matches the accessor too and passes either way.
        $this->assertDoesNotMatchRegularExpression(
            '/->portfolio\b(?!\w)/',
            $code,
            'browse.blade.php reads the portfolio column directly again; it holds two shapes.',
        );
        $this->assertStringContainsString('portfolioHeroUrls', $code);
    }
}
