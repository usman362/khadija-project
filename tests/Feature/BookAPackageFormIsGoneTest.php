<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Book a Package" is not a form.
 *
 * It was one, and it booked nothing. "Which package" was a text box, so the
 * client typed a name from memory that pointed at no package record;
 * submitting filed a message; no booking was created and no professional was
 * told. It also asked them to certify that "a deposit is taken on booking and
 * is not refundable" when no deposit is taken anywhere.
 *
 * Sir Peter found it and asked what it was for. The honest answer was nothing,
 * and the real path already existed: browse /packages, open one, book it —
 * against that package, with its price and its professional.
 */
class BookAPackageFormIsGoneTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $user->fresh();
    }

    public function test_it_is_not_offered_any_more(): void
    {
        $this->assertArrayNotHasKey('package_purchase', FormRegistry::all());

        foreach ([FormRegistry::CLIENT, FormRegistry::ANYONE] as $audience) {
            foreach (FormRegistry::groupsForAudience($audience) as $group) {
                $this->assertArrayNotHasKey('package_purchase', $group['forms']);
            }
        }
    }

    /** And it is not listed on the page that offers the forms. */
    public function test_the_chooser_does_not_list_it(): void
    {
        $html = $this->actingAs($this->client())
            ->get(route('forms.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('Book a Package', $html);
    }

    /**
     * The old address still answers. Somebody following it wants to book a
     * package, and a 404 answers them with nothing at all.
     */
    public function test_the_old_link_goes_to_the_packages_page(): void
    {
        $this->actingAs($this->client())
            ->get('/requests-submissions/new/book-a-package')
            ->assertRedirect(route('public.packages'));
    }

    /** The way that actually books a package is still there. */
    public function test_the_real_path_still_works(): void
    {
        $this->assertTrue(\Route::has('public.packages'));
        $this->assertTrue(\Route::has('client.packages.book'));
    }
}
