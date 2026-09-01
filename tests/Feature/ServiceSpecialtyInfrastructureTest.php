<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Level 4 infrastructure. Sir Peter, 2026-08-31: build the table, the
 * relationship to Level 3, and the UI, using placeholder data — the structure
 * does not change based on the names, and the real list comes from Khadijah.
 *
 * The trap this file mostly exists for: services and specialties share one
 * pivot, and sync()'s delete ignores a relation's `where kind` constraint. So
 * serviceCategories()->sync() wiped every specialty on its way past, and
 * specialties()->sync() wiped every service. A professional would have saved
 * their services and silently lost their specialties.
 */
class ServiceSpecialtyInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(): User
    {
        $u = User::factory()->create();
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    /** @return array{0: Category, 1: Category} a level-3 service and a specialty under it */
    private function serviceWithSpecialty(string $service, string $specialty): array
    {
        $svc = Category::create([
            'name' => $service, 'slug' => \Illuminate\Support\Str::slug($service) . uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $spec = Category::create([
            'name' => $specialty, 'slug' => \Illuminate\Support\Str::slug($specialty) . uniqid(),
            'kind' => Category::SERVICE_SPECIALTY, 'parent_id' => $svc->id, 'is_active' => true,
        ]);

        return [$svc, $spec];
    }

    public function test_a_specialty_hangs_off_its_service(): void
    {
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');

        $this->assertSame($dj->id, $weddingDj->parent_id);
        $this->assertSame(Category::SERVICE_SPECIALTY, $weddingDj->kind);
    }

    public function test_a_professional_can_hold_both(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');

        $pro->syncServices([$dj->id]);
        $pro->syncSpecialties([$weddingDj->id]);

        $this->assertSame(1, $pro->serviceCategories()->count());
        $this->assertSame(1, $pro->specialties()->count());
    }

    /** The whole reason syncServices() exists. */
    public function test_saving_services_does_not_wipe_specialties(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        [$catering] = $this->serviceWithSpecialty('Catering', 'Buffet Catering');

        $pro->syncServices([$dj->id]);
        $pro->syncSpecialties([$weddingDj->id]);

        // The professional adds a second service on the profile page.
        $pro->syncServices([$dj->id, $catering->id]);

        $this->assertSame(2, $pro->serviceCategories()->count());
        $this->assertSame(1, $pro->specialties()->count(),
            'Saving services wiped the specialties — the sync() trap is back.');
    }

    public function test_saving_specialties_does_not_wipe_services(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        [, $karaoke] = $this->serviceWithSpecialty('DJ Services 2', 'Karaoke DJ');

        $pro->syncServices([$dj->id]);
        $pro->syncSpecialties([$weddingDj->id]);
        $pro->syncSpecialties([$weddingDj->id, $karaoke->id]);

        $this->assertSame(1, $pro->serviceCategories()->count(),
            'Saving specialties wiped the services.');
        $this->assertSame(2, $pro->specialties()->count());
    }

    /** A specialty cannot be smuggled into the services list. */
    public function test_the_kinds_cannot_be_mixed_up(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');

        $pro->syncServices([$weddingDj->id]);
        $pro->syncSpecialties([$dj->id]);

        $this->assertSame(0, $pro->serviceCategories()->count());
        $this->assertSame(0, $pro->specialties()->count());
    }

    /** Reading services must not start returning specialties. */
    public function test_the_services_relation_excludes_specialties(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');

        $pro->syncServices([$dj->id]);
        $pro->syncSpecialties([$weddingDj->id]);

        $names = $pro->serviceCategories()->pluck('name')->all();

        $this->assertSame(['DJ Services'], $names);
    }

    /** Placeholders are marked, and removable without touching approved rows. */
    public function test_placeholders_are_marked_and_removable(): void
    {
        [$dj] = $this->serviceWithSpecialty('DJ Services', 'Approved Wedding DJ');

        $this->artisan('taxonomy:placeholder-specialties --per=2')->assertSuccessful();

        $placeholders = Category::where('kind', Category::SERVICE_SPECIALTY)
            ->where('short_description', \App\Console\Commands\SeedPlaceholderSpecialties::MARKER)
            ->count();
        $this->assertGreaterThan(0, $placeholders);

        $this->artisan('taxonomy:placeholder-specialties --clear')->assertSuccessful();

        $this->assertSame(0, Category::where('short_description', \App\Console\Commands\SeedPlaceholderSpecialties::MARKER)->count());

        // The approved one is still there.
        $this->assertDatabaseHas('categories', ['name' => 'Approved Wedding DJ']);
    }

    /**
     * A category saved without a kind must still count as a service.
     *
     * `kind != 'service_specialty'` evaluates to NULL in SQL for a row with no
     * kind, which excludes it — so a category created without one would have
     * disappeared from every professional's services, silently, and only for
     * them. Two insurance tests caught it.
     */
    public function test_a_category_with_no_kind_is_still_a_service(): void
    {
        $pro = $this->pro();

        $kindless = Category::create([
            'name' => 'Catering', 'slug' => 'catering-' . uniqid(), 'is_active' => true,
        ]);

        \DB::table('category_user')->insert(['user_id' => $pro->id, 'category_id' => $kindless->id]);

        $this->assertSame(1, $pro->serviceCategories()->count(),
            'A category with no kind fell out of the services relation.');
    }

    // ── The screen ────────────────────────────────────────────────

    public function test_the_profile_offers_specialties_for_the_services_offered(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        [, $buffet] = $this->serviceWithSpecialty('Catering', 'Buffet Catering');

        $pro->syncServices([$dj->id]);

        $html = $this->actingAs($pro)
            ->get(route('professional.profile.index', ['tab' => 'professional']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Wedding DJ', $html);

        // A DJ has no business being offered catering specialties.
        $this->assertStringNotContainsString('Buffet Catering', $html);
    }

    public function test_a_professional_with_no_services_is_told_what_to_do_first(): void
    {
        $html = $this->actingAs($this->pro())
            ->get(route('professional.profile.index', ['tab' => 'professional']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Choose your services above and save first', $html);
    }

    public function test_specialties_can_be_saved(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        $pro->syncServices([$dj->id]);

        $this->actingAs($pro)
            ->patch(route('professional.profile.update.specialties'), ['specialties' => [$weddingDj->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame([$weddingDj->id], $pro->specialties()->pluck('categories.id')->all());
    }

    /** A stale form or a hand-edited request cannot attach somebody else's specialty. */
    public function test_a_specialty_under_a_service_they_do_not_offer_is_refused(): void
    {
        $pro = $this->pro();
        [$dj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        [, $buffet] = $this->serviceWithSpecialty('Catering', 'Buffet Catering');

        $pro->syncServices([$dj->id]);

        $this->actingAs($pro)
            ->patch(route('professional.profile.update.specialties'), ['specialties' => [$buffet->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $pro->specialties()->count(),
            'A caterer specialty was attached to a DJ.');
    }

    /** Saving specialties must leave the services alone — same pivot. */
    public function test_saving_from_the_form_does_not_disturb_the_services(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        $pro->syncServices([$dj->id]);

        $this->actingAs($pro)
            ->patch(route('professional.profile.update.specialties'), ['specialties' => [$weddingDj->id]]);

        $this->assertSame(1, $pro->serviceCategories()->count());
    }

    public function test_clearing_them_is_allowed(): void
    {
        $pro = $this->pro();
        [$dj, $weddingDj] = $this->serviceWithSpecialty('DJ Services', 'Wedding DJ');
        $pro->syncServices([$dj->id]);
        $pro->syncSpecialties([$weddingDj->id]);

        $this->actingAs($pro)
            ->patch(route('professional.profile.update.specialties'), [])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $pro->specialties()->count());
        $this->assertSame(1, $pro->serviceCategories()->count());
    }
}
