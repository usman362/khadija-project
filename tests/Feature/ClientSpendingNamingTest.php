<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A client spends; a professional earns.
 *
 * The sidebar said "Spending" and the page titled itself "Spending", while the
 * URL, the route name and the controller method all said "earnings" — the
 * professional's word for the opposite side of the same transaction. The
 * inside and the outside of the screen disagreed about what it was.
 *
 * The old address still resolves, because renaming a URL people may have
 * bookmarked is not a rename, it is a removal.
 */
class ClientSpendingNamingTest extends TestCase
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

    public function test_the_page_lives_at_spending(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.spending.index'))
            ->assertOk()
            ->assertSee('Spending', false);
    }

    public function test_the_old_earnings_url_still_works(): void
    {
        $this->actingAs($this->client())
            ->get('/client/earnings')
            ->assertRedirect(route('client.spending.index'));
    }

    /** The professional's own earnings page is untouched — there the word is right. */
    public function test_the_professional_still_has_earnings(): void
    {
        $this->assertTrue(
            app('router')->getRoutes()->hasNamedRoute('professional.earnings.index'),
            'The professional earns; that page keeps its name.'
        );
    }
}
