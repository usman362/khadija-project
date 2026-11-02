<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a professional charges, per service.
 *
 * They could always say which services they do. What any of them cost had
 * nowhere to live, and three things were waiting on it: Create a Package
 * asks them to pick from "your existing services" and shows a price beside
 * each one, Quick Bid answers a request with a number, and Sir Peter's rough
 * budget estimator was parked with the note that a price per service is a
 * field on their profile and nobody has one.
 */
class AProfessionalCanPriceTheirOwnServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private Category $dj;
    private Category $photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = $this->person('professional', 'Hollis Tremaine');

        $this->dj    = $this->service('Wedding DJ', 'wedding-dj');
        $this->photo = $this->service('Event Photography', 'event-photography');

        $this->pro->syncServices([$this->dj->id, $this->photo->id]);
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function service(string $name, string $slug): Category
    {
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    private function priceOf(Category $service): ?string
    {
        return $this->pro->fresh()->serviceCategories()
            ->where('categories.id', $service->id)->first()?->pivot?->price;
    }

    public function test_the_page_lists_the_services_they_offer(): void
    {
        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSuccessful()
            ->assertSee('Wedding DJ')
            ->assertSee('Event Photography');
    }

    public function test_a_service_they_do_not_offer_is_not_listed(): void
    {
        $other = $this->service('Catering', 'catering');

        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSuccessful()
            ->assertDontSee($other->name);
    }

    public function test_a_price_is_saved_against_the_service(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [
                $this->dj->id => ['price' => '1200', 'price_unit' => 'from', 'duration_hours' => 4, 'extra_hour_price' => '150'],
            ],
        ])->assertRedirect();

        $pivot = $this->pro->fresh()->serviceCategories()
            ->where('categories.id', $this->dj->id)->firstOrFail()->pivot;

        // Compared as numbers: a pivot is not a cast model, so what comes
        // back is whatever the driver stored it as.
        $this->assertSame(1200.0, (float) $pivot->price);
        $this->assertSame('from', $pivot->price_unit);
        $this->assertSame(4, (int) $pivot->duration_hours);
        $this->assertSame(150.0, (float) $pivot->extra_hour_price);
    }

    /**
     * The ids come from the form, so a hand-edited one must not be able to
     * price a service this professional does not offer, or reach a row that
     * is not theirs.
     */
    public function test_a_price_cannot_be_written_against_a_service_they_do_not_offer(): void
    {
        $other = $this->service('Catering', 'catering');

        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$other->id => ['price' => '9999']],
        ])->assertRedirect();

        $this->assertNull($this->priceOf($other));
    }

    /**
     * Re-saving the service list must not clear the prices.
     *
     * syncWithinKind detaches only what was removed and uses
     * syncWithoutDetaching for the rest, which leaves existing rows alone.
     * It is the reason the price can live on the pivot at all, so it is
     * worth holding.
     */
    public function test_choosing_services_again_keeps_the_prices(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->pro->syncServices([$this->dj->id, $this->photo->id]);

        $this->assertSame(1200.0, (float) $this->priceOf($this->dj));
    }

    /** Dropping the service does drop its price, which is the right answer. */
    public function test_dropping_a_service_drops_its_price(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->pro->syncServices([$this->photo->id]);

        $this->assertNull($this->priceOf($this->dj));
    }

    /** A blank price is "not priced yet", never "free". */
    public function test_a_blank_price_stays_blank(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '', 'price_unit' => 'flat']],
        ]);

        $this->assertNull($this->priceOf($this->dj));
    }

    public function test_the_page_says_how_many_are_priced(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSee('1 of 2 services have a price');
    }
}
