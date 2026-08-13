<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 97, 98, 121 and 122.
 *
 * 97 and 98 are layout, measured in a real mobile viewport rather than argued
 * about: the trust-badge row overflowed the phone by 38px, and the hero cards'
 * circular photos sat 8px past the card edge. Both are fixed in CSS, so what
 * is asserted here is the CAUSE rather than the pixels — a bare `1fr` grid
 * track cannot shrink below its content, which is what pushed the fifth trust
 * tile off-screen.
 *
 * 121's mixed art styles are a sourcing decision for the Owner, but the audit
 * found something underneath it that is not: a category with no artwork
 * rendered asset('storage/') — the string "/storage" — as a broken image in a
 * row of photographs.
 *
 * 122 asked what "Contact Support" actually opens. It opened nothing:
 * href="#" in the influencer portal, the About page from the FAQ, and
 * user-to-user chat from the professional side. The client portal had no
 * support link at all.
 */
class HomepageMobileAndSupportTest extends TestCase
{
    use RefreshDatabase;

    /* ── Rows 97 and 98: the phone-width rules exist ────────── */

    /**
     * The blowout itself. A bare `1fr` track takes its content's min-width as
     * its floor, and .lp-vb-sub is 150px wide before padding — so three tiles
     * demanded ~560px inside a 375px phone and the row ran off the screen.
     */
    public function test_the_value_band_uses_shrinkable_grid_tracks(): void
    {
        $css = file_get_contents(resource_path('views/landing.blade.php'));

        $this->assertStringNotContainsString(
            '.lp-valueband { grid-template-columns: 1fr 1fr 1fr;',
            $css,
            'a bare 1fr track cannot shrink below its content — that is the overflow',
        );
        $this->assertStringContainsString('.lp-valueband { grid-template-columns: repeat(3, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('.lp-valueband { grid-template-columns: repeat(2, minmax(0, 1fr))', $css);
    }

    /** And the hero photo comes inside the card at phone width. */
    public function test_the_hero_photo_is_pulled_inside_the_card_on_a_phone(): void
    {
        $css = file_get_contents(resource_path('views/landing.blade.php'));

        // The desktop rule still bleeds it off the corner deliberately.
        $this->assertStringContainsString('.lp-role-img { position: absolute; right: -8px; top: -8px;', $css);

        // The phone rule brings it back in, and the heading padding follows it.
        $this->assertStringContainsString('.lp-role-img { right: 14px; top: 14px;', $css);
        $this->assertStringContainsString('.lp-role h3, .lp-role p { padding-right: 96px; }', $css);
    }

    /* ── Row 121: no broken tiles in the showcase ───────────── */

    private function category(string $name, ?string $thumb, string $kind = Category::SERVICE_CATEGORY): Category
    {
        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => $kind, 'is_active' => true, 'thumbnail' => $thumb,
        ]);
    }

    /**
     * The section is headed "Explore Popular Categories" and had no kind
     * filter, so event types — which sort first — took every slot. Eight event
     * types under a heading promising categories, duplicating the page that
     * already exists for them.
     */
    public function test_the_showcase_holds_service_categories_not_event_types(): void
    {
        $this->category('Catering & Food Services', 'categories/catering.jpg');
        $this->category('Wedding', 'categories/wedding.jpg', Category::EVENT_TYPE);

        $shown = collect($this->get(route('landing'))->assertOk()->viewData('showcaseCategories'));

        $this->assertSame(['Catering & Food Services'], $shown->pluck('name')->all());
    }

    public function test_a_category_with_no_artwork_is_not_shown_as_a_broken_image(): void
    {
        $this->category('Has A Picture', 'categories/wedding.jpg');
        $this->category('Has No Picture', null);

        $shown = collect($this->get(route('landing'))->assertOk()->viewData('showcaseCategories'));

        $this->assertSame(['Has A Picture'], $shown->pluck('name')->all());

        // The tell-tale: asset('storage/') with nothing after it.
        foreach ($shown as $tile) {
            $this->assertDoesNotMatchRegularExpression('#/storage/?$#', $tile['image']);
        }
    }

    /** With nothing to show, the section is withheld rather than left empty. */
    public function test_the_carousel_section_is_withheld_when_no_category_has_artwork(): void
    {
        $this->category('No Picture Either', null);

        $this->get(route('landing'))->assertOk()->assertDontSee('Explore Popular Categories', false);
    }

    /* ── Popular Cities (Khadijah, 2026-08-13) ──────────────── */

    private function pro(string $city, string $state = 'MD'): void
    {
        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => $city]);
    }

    /**
     * The threshold is the whole point of the feature. Bark's version works
     * because each city holds hundreds; ours would otherwise advertise
     * "Philadelphia — 1 professional" on the front page.
     */
    public function test_a_city_below_the_threshold_is_not_advertised(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro('Baltimore'); $this->pro('Baltimore');
        $this->pro('Philadelphia', 'PA');   // one only

        $cities = collect($this->get(route('landing'))->assertOk()->viewData('popularCities'));

        $this->assertSame(['Baltimore'], $cities->pluck('city')->all());
        $this->assertSame(2, $cities->first()['count']);
    }

    /** With nothing over the line, the section is withheld rather than left thin. */
    public function test_the_section_is_withheld_when_no_city_qualifies(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro('Baltimore');   // one only

        $page = $this->get(route('landing'))->assertOk();

        $this->assertCount(0, $page->viewData('popularCities'));
        $page->assertDontSee('Where our professionals are', false);
    }

    /** A city we cannot trade in has no business on the front page. */
    public function test_out_of_area_cities_are_excluded(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro('Los Angeles', 'CA'); $this->pro('Los Angeles', 'CA');

        $this->assertCount(0, $this->get(route('landing'))->assertOk()->viewData('popularCities'));
    }

    /* ── Row 122: Contact Support opens support ─────────────── */

    public function test_the_support_form_exists_and_asks_what_a_ticket_needs(): void
    {
        $form = \App\Domain\Forms\FormRegistry::all()['support_request'] ?? null;

        $this->assertNotNull($form, 'row 122 asked for a real submission form');

        $fields = collect($form['fields'])->pluck('name');

        $this->assertContains('topic', $fields);
        $this->assertContains('subject', $fields);
        $this->assertContains('detail', $fields);
    }

    public function test_a_user_can_submit_a_support_request(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');

        $this->actingAs($user)->get(route('forms.create', 'support_request'))->assertOk();

        $this->actingAs($user)->post(route('forms.store', 'support_request'), [
            'topic'   => 'payment',
            'subject' => 'Deposit taken twice',
            'detail'  => 'I was charged the deposit twice for the same booking on 3 August.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('form_submissions', [
            'form_key'     => 'support_request',
            'submitted_by' => $user->id,
        ]);
    }

    /**
     * The specific failure the row reported: a link that says support and
     * does nothing.
     */
    public function test_no_support_link_still_points_nowhere(): void
    {
        $layouts = [
            'views/layouts/influencer-portal.blade.php',
            'views/public/faq.blade.php',
            'views/professional/packages/create.blade.php',
        ];

        foreach ($layouts as $path) {
            $body = file_get_contents(resource_path($path));

            // Find the anchor carrying "Contact Support" and check where it goes.
            preg_match('/<a href="([^"]*)"[^>]*>(?:(?!<\/a>).)*Contact Support/s', $body, $m);

            $this->assertNotEmpty($m, "no Contact Support link found in {$path}");
            $this->assertStringContainsString('support_request', $m[1], "{$path} still points elsewhere");
        }
    }

    /** And both portals that had no support link at all now have one. */
    public function test_the_client_and_professional_sidebars_can_reach_support(): void
    {
        foreach (['views/layouts/client.blade.php', 'views/layouts/professional.blade.php'] as $path) {
            $this->assertStringContainsString(
                "route('forms.create', 'support_request')",
                file_get_contents(resource_path($path)),
                "{$path} has no way to reach the platform",
            );
        }
    }
}
