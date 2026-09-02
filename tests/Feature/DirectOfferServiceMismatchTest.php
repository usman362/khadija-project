<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Choosing a service a professional does not offer is a mistake, not a crash.
 *
 * Seen live on 2026-09-03, mid-meeting: the Direct Offer form answered with the
 * framework's error page — "Symfony\Component\HttpKernel\Exception\
 * HttpException", a stack trace and a 422 — because the check used abort().
 * The client lost everything they had typed for making an ordinary mistake.
 *
 * The rule is right and stays: a florist must not receive a photography brief.
 * Only the reporting changes.
 */
class DirectOfferServiceMismatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_says_which_service_is_wrong_instead_of_crashing(): void
    {
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
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $offers = Category::create(['name' => 'Wedding Photography', 'slug' => 'wedding-photography',
            'kind' => Category::SERVICE, 'is_active' => true]);
        $doesNot = Category::create(['name' => 'Balloon Garlands', 'slug' => 'balloon-garlands',
            'kind' => Category::SERVICE, 'is_active' => true]);

        $pro->serviceCategories()->sync([$offers->id]);

        $response = $this->actingAs(User::findOrFail($client->id))
            ->from('/client/direct-offers/create')
            ->post('/client/direct-offers', [
                'request_type' => 'MSR',
                'professional_id' => $pro->id,
                'services' => [$offers->id, $doesNot->id],
                'title' => 'A party',
                'organization_type' => 'individual',
            ]);

        // Back to the form, not an error page.
        $response->assertRedirect('/client/direct-offers/create');
        $response->assertSessionHasErrors('services');

        // And it names the one to remove, rather than "one of the services".
        $this->assertStringContainsString(
            'Balloon Garlands',
            session('errors')->first('services'),
        );
    }
}
