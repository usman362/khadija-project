<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Professionals is gone, name and address as well as page.
 *
 * Khadijah removed the page on 21 October: "its the same thing" as Hired
 * Talent, which it was, for the half that listed who you had worked with. Its
 * Saved tab was a live feature and moved there rather than being deleted with
 * it.
 *
 * What survived that was the naming. Saving somebody still posted to
 * /client/my-professionals under route names beginning
 * client.saved-professionals, so a module that no longer existed was still in
 * the address bar and in every reference to it. Asked again on 27 October to
 * take the old one out.
 *
 * The old address stays as a redirect, deliberately, so nobody's bookmark
 * breaks. It is unnamed, so nothing in the application can point at it again.
 */
class MyProfessionalsIsGoneTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    // ── The page and its name ────────────────────────────────

    public function test_the_page_the_controller_and_the_view_are_all_gone(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Client/ClientSavedProfessionalController.php'));
        $this->assertDirectoryDoesNotExist(resource_path('views/client/saved-professionals'));
    }

    /** Nothing can route to the old name, because there is no longer one. */
    public function test_the_old_route_names_do_not_exist(): void
    {
        $routes = app('router')->getRoutes();

        foreach (['index', 'store', 'destroy'] as $action) {
            $this->assertNull(
                $routes->getByName('client.saved-professionals.' . $action),
                'client.saved-professionals.' . $action . ' is still registered.',
            );
        }
    }

    /** And no screen mentions it. */
    public function test_no_view_refers_to_the_old_module(): void
    {
        $offenders = [];

        foreach (['views', 'views/client', 'views/public', 'views/layouts'] as $dir) {
            foreach (glob(resource_path($dir . '/*.blade.php')) as $file) {
                if (str_contains(file_get_contents($file), 'client.saved-professionals')) {
                    $offenders[] = basename($file);
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    // ── A bookmark still lands somewhere sensible ────────────

    public function test_the_old_address_sends_you_to_the_saved_tab(): void
    {
        $this->actingAs($this->client())
            ->get('/client/my-professionals')
            ->assertRedirect(route('client.hired-talent.index', ['who' => 'saved']));
    }

    // ── And the feature it held still works ──────────────────

    public function test_saving_somebody_still_works_under_its_new_name(): void
    {
        $client = $this->client();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $this->actingAs($client)
            ->post(route('client.hired-talent.save'), ['professional_id' => $pro->id])
            ->assertRedirect();

        $this->assertTrue($client->fresh()->savedProfessionals->contains($pro->id));

        $this->actingAs($client)
            ->delete(route('client.hired-talent.unsave', $pro))
            ->assertRedirect();

        $this->assertFalse($client->fresh()->savedProfessionals->contains($pro->id));
    }

    /**
     * The heart on Browse posts to the new address.
     *
     * Needs a professional in the client's own state, or the page renders no
     * cards and there is no form to check. The first version of this test
     * asserted against an empty list and failed on correct markup.
     */
    public function test_the_save_button_on_browse_points_at_the_new_route(): void
    {
        $client = $this->client();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $html = $this->actingAs($client)->get(route('public.browse'))
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString($pro->name, $html, 'No professional on the page to save.');
        $this->assertStringContainsString(route('client.hired-talent.save'), $html);
        $this->assertStringNotContainsString('/client/my-professionals', $html);
    }
}
