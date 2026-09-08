<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GigResourceId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Idea 1, Sir Peter — a permanent public reference for every account.
 *
 * CL-482731, PRO-100482, INF-900117. Quoted in support, verification,
 * transactions, reports, disputes and messaging, and the way to tell two
 * people with the same name apart.
 *
 * The three properties he asked for are the three things worth testing, and
 * each has a way of quietly not being true:
 *
 *  - permanent: a name, email, membership or role change must not touch it;
 *  - unique: enforced by the index, not by a check-then-insert;
 *  - not the user's: never fillable, never in a form.
 */
class GigResourceIdTest extends TestCase
{
    use RefreshDatabase;

    /* ── Assigned automatically ─────────────────────────────── */

    public function test_every_new_account_gets_one(): void
    {
        foreach (['client' => 'CL', 'professional' => 'PRO', 'influencer' => 'INF'] as $role => $prefix) {
            $user = User::factory()->create(['primary_role' => $role]);

            $this->assertStringStartsWith($prefix.'-', $user->fresh()->public_id, $role);
            $this->assertTrue(GigResourceId::looksValid($user->fresh()->public_id));
        }
    }

    /** A role with no prefix of its own gets the fallback, never a guess. */
    public function test_an_unknown_role_gets_the_fallback_prefix(): void
    {
        $user = User::factory()->create(['primary_role' => null]);

        $this->assertStringStartsWith('GR-', $user->fresh()->public_id);
    }

    /* ── Permanent ──────────────────────────────────────────── */

    public function test_it_survives_a_name_and_email_change(): void
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $before = $user->fresh()->public_id;

        $user->update(['name' => 'Someone Else', 'email' => 'new@example.test']);

        $this->assertSame($before, $user->fresh()->public_id);
    }

    /**
     * And a role change. The prefix records what the account registered as —
     * a client who later works as a professional keeps CL-, because a
     * reference that changes is not a reference.
     */
    public function test_it_survives_a_role_change(): void
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $before = $user->fresh()->public_id;

        $user->update(['primary_role' => 'professional']);

        $this->assertSame($before, $user->fresh()->public_id);
        $this->assertStringStartsWith('CL-', $user->fresh()->public_id);
    }

    /** Assigning again is a no-op, not a second number. */
    public function test_assigning_twice_returns_the_same_one(): void
    {
        $user = User::factory()->create(['primary_role' => 'client']);

        $this->assertSame(
            $user->fresh()->public_id,
            GigResourceId::assign($user->fresh()),
        );
    }

    /* ── Not the user's to set ──────────────────────────────── */

    public function test_it_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $real = $user->fresh()->public_id;

        $user->fill(['public_id' => 'CL-000001'])->save();

        $this->assertSame($real, $user->fresh()->public_id);
    }

    /* ── Unique, and not the database id ────────────────────── */

    public function test_two_hundred_accounts_get_two_hundred_references(): void
    {
        $ids = collect(range(1, 200))
            ->map(fn () => User::factory()->create(['primary_role' => 'client'])->fresh()->public_id);

        $this->assertCount(200, $ids->unique(), 'a reference was issued twice');
    }

    /**
     * It must not be the row id dressed up. Exposing that says how many
     * accounts exist and lets somebody walk the range, which is the reason
     * for having a separate reference at all.
     */
    public function test_it_is_not_the_database_id(): void
    {
        $user = User::factory()->create(['primary_role' => 'client']);

        // The whole number, not a substring: a random six-digit reference
        // legitimately starts with the digits of a low row id — CL-119512 for
        // user 1 — and failing on that made the test fail about one run in ten.
        [, $digits] = explode('-', (string) $user->fresh()->public_id, 2);

        $this->assertNotSame((string) $user->id, $digits);
        $this->assertNotSame((string) $user->id, ltrim($digits, '0'));
    }

    /* ── Where the client can see it ────────────────────────── */

    public function test_the_client_can_read_it_on_their_profile(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->actingAs(User::findOrFail($user->id))
            ->get('/client/profile')
            ->assertOk()
            ->assertSee('GigResource ID')
            ->assertSee($user->fresh()->public_id);
    }

    /**
     * The format has to be wideable later without touching what exists —
     * Sir Peter asked for that explicitly. It holds only because nothing
     * parses the digits back out as a number.
     */
    public function test_a_longer_number_is_still_a_valid_reference(): void
    {
        $this->assertTrue(GigResourceId::looksValid('CL-1234567'));
        $this->assertTrue(GigResourceId::looksValid('PRO-482731'));
        $this->assertFalse(GigResourceId::looksValid('CL-12345'));
        $this->assertFalse(GigResourceId::looksValid('482731'));
    }
}
