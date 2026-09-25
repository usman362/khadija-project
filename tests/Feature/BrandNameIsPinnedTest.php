<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 25 September: "many times it's listed as gigs not gigresource."
 *
 * The professional profile read "How booking works on Gigs" and "Accepted on
 * Gigs". Every brand mention on the site took its name from APP_NAME, which
 * is a server setting, and on production that setting said something else.
 * One line in a server file had quietly renamed the product everywhere it is
 * named — including the emails, which go out under it.
 *
 * The product's name is not a deployment decision, so it no longer reads a
 * server setting. These hold that, with APP_NAME deliberately set wrong.
 */
class BrandNameIsPinnedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_name_does_not_come_from_a_server_setting(): void
    {
        config(['app.name' => 'Gigs']);

        $this->assertSame('GigResource', config('brand.name'));
    }

    public function test_a_profile_names_the_product_correctly(): void
    {
        config(['app.name' => 'Gigs']);

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Silver Spring']);

        $html = $this->actingAs(User::findOrFail($client->id))
            ->get(route('public.professional.show', $pro->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('How booking works on GigResource', $html);
        $this->assertStringContainsString('Accepted on GigResource', $html);
        $this->assertStringNotContainsString('on Gigs', $html);
    }

    /** Nothing user-facing reads the setting any more. */
    public function test_no_page_or_email_takes_its_brand_from_app_name(): void
    {
        $found = [];

        foreach ([base_path('resources/views'), base_path('app')] as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                if (str_contains((string) file_get_contents($file->getPathname()), "config('app.name'")) {
                    $found[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $found, "these still take the brand from APP_NAME:\n".implode("\n", $found));
    }
}
