<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An account can hold both roles. Without an explicit exclusion, someone who is
 * a client and a professional finds themselves in every list of professionals —
 * to browse, to save, to send an offer to.
 */
class NoSelfHireTest extends TestCase
{
    use RefreshDatabase;

    private User $both;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // The case that matters: one account, both roles.
        $u = User::factory()->create(['name' => 'Dual Role Person']);
        $u->assignRole('client');
        $u->assignRole('professional');
        $u->givePermissionTo('dashboard.view');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->both = $u->fresh();

        $o = User::factory()->create(['name' => 'Someone Else']);
        $o->assignRole('professional');
        $o->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->other = $o->fresh();
    }

    /** Every professional id offered on a page. */
    private function idsOn(string $route, string $key): array
    {
        $data = $this->actingAs($this->both)->get(route($route))
            ->assertSuccessful()
            ->viewData($key);

        return collect($data)->pluck('id')->filter()->all();
    }

    public function test_you_are_not_in_the_direct_offer_professional_list(): void
    {
        $ids = $this->idsOn('client.direct-offers.create', 'pros');

        $this->assertNotContains($this->both->id, $ids, 'you cannot hire yourself');
        $this->assertContains($this->other->id, $ids, 'other professionals still appear');
    }

    public function test_you_are_not_in_the_saved_professionals_list(): void
    {
        // Saving yourself is blocked, so force the row in to prove the list
        // filters it as well as the write path.
        $this->both->savedProfessionals()->syncWithoutDetaching([$this->both->id, $this->other->id]);

        $ids = $this->idsOn('client.saved-professionals.index', 'saved');

        $this->assertNotContains($this->both->id, $ids);
        $this->assertContains($this->other->id, $ids);
    }

    /*
     * Browse Professionals is not covered here: its category sidebar uses a
     * HAVING clause SQLite rejects, so the page 500s under test regardless of
     * this change. Verified by hand against the real database instead.
     */

    public function test_you_cannot_send_yourself_a_direct_offer(): void
    {
        // The list no longer offers it, but the id still arrives in the request.
        $this->actingAs($this->both)
            ->post(route('client.direct-offers.store'), [
                'professional_id' => $this->both->id,
                'event_name'      => 'Self dealing',
            ])
            ->assertStatus(422);
    }

    public function test_you_cannot_save_yourself_to_my_professionals(): void
    {
        $this->actingAs($this->both)
            ->post(route('client.saved-professionals.store'), [
                'professional_id' => $this->both->id,
            ])
            ->assertStatus(422);
    }

    public function test_the_exclusion_is_a_no_op_for_a_guest(): void
    {
        // Category pages and public profiles run the same queries with nobody
        // logged in. Tested on the scope directly because /browse itself is
        // behind auth, so it can never be reached as a guest.
        $this->assertNull(auth()->user());

        $ids = User::query()->excludingSelf()->pluck('id')->all();

        $this->assertContains($this->both->id, $ids);
        $this->assertContains($this->other->id, $ids);
    }
}
