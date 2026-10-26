<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Emptying the user table down to a named handful.
 *
 * Asked for on 26 October: keep client@example.com and
 * professional@example.com on production, remove every other account and its
 * data. `demo:purge` deletes only hand-listed seeded addresses on purpose, so
 * it could not do this; this command keeps a named list instead, which is a
 * far larger blast radius.
 *
 * The three traps these tests hold are the ones `demo:purge` already paid for
 * once: the admin must survive whatever is asked, soft deletes stop every
 * cascade so the data would quietly remain, and SET NULL leaves a kept account
 * holding a booking with nobody in it.
 */
class KeepingOnlyNamedAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'client'): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['email' => $email, 'primary_role' => $role]);
        $u->assignRole($role);

        return User::findOrFail($u->id);
    }

    private function eventFor(User $client, ?User $supplier = null): Event
    {
        return Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $supplier?->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(30),
        ]);
    }

    // ── What it keeps and what it removes ────────────────────

    public function test_it_keeps_the_named_accounts_and_removes_the_rest(): void
    {
        $keep  = $this->user('client@example.com');
        $also  = $this->user('professional@example.com', 'professional');
        $gone  = $this->user('someone@gmail.com');
        $gone2 = $this->user('another@gmail.com', 'professional');

        $this->artisan('users:keep-only', [
            'emails'  => ['client@example.com', 'professional@example.com'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertNotNull(User::find($keep->id));
        $this->assertNotNull(User::find($also->id));
        $this->assertNull(User::withTrashed()->find($gone->id), 'A removed account is still in the table.');
        $this->assertNull(User::withTrashed()->find($gone2->id));
    }

    /**
     * Their data goes with them, which soft deletes would have prevented.
     *
     * delete() only stamps deleted_at. The row stays, so no cascade fires and
     * every event, booking and review survives while the command reports
     * success. This is the assertion that catches that.
     */
    public function test_the_data_goes_with_the_account(): void
    {
        $this->user('client@example.com');
        $gone  = $this->user('someone@gmail.com');
        $event = $this->eventFor($gone);

        $this->artisan('users:keep-only', [
            'emails'  => ['client@example.com'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    // ── The admin is never reachable ─────────────────────────

    /** Deleting it locks everybody out of the admin side. */
    public function test_the_admin_survives_even_when_not_named(): void
    {
        $admin = $this->user('admin@example.com', 'admin');
        $this->user('client@example.com');

        $this->artisan('users:keep-only', [
            'emails'  => ['client@example.com'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertNotNull(User::find($admin->id), 'The admin account was deleted.');
    }

    // ── Orphans ──────────────────────────────────────────────

    /**
     * A kept account must not be left holding work with nobody in it.
     *
     * supplier_id is SET NULL, so the booking survives its professional and
     * becomes a booking for nobody. That is the one way this can damage an
     * account it was told to keep, so it stops rather than doing it quietly.
     */
    public function test_it_stops_when_a_kept_account_would_lose_its_professional(): void
    {
        $client = $this->user('client@example.com');
        $pro    = $this->user('pro@gmail.com', 'professional');
        $event  = $this->eventFor($client, $pro);

        $this->artisan('users:keep-only', [
            'emails'  => ['client@example.com'],
            '--apply' => true,
        ])->assertFailed();

        $this->assertNotNull(User::find($pro->id), 'It deleted anyway.');
        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    /** And goes ahead when that is said out loud. */
    public function test_it_proceeds_when_the_orphans_are_accepted(): void
    {
        $client = $this->user('client@example.com');
        $pro    = $this->user('pro@gmail.com', 'professional');
        $this->eventFor($client, $pro);

        $this->artisan('users:keep-only', [
            'emails'          => ['client@example.com'],
            '--apply'         => true,
            '--force-orphans' => true,
        ])->assertSuccessful();

        $this->assertNull(User::withTrashed()->find($pro->id));
    }

    // ── It does not fire by accident ─────────────────────────

    /** Without --apply it only prints. */
    public function test_it_changes_nothing_without_apply(): void
    {
        $this->user('client@example.com');
        $gone = $this->user('someone@gmail.com');

        $this->artisan('users:keep-only', ['emails' => ['client@example.com']])
            ->assertSuccessful();

        $this->assertNotNull(User::find($gone->id), 'A dry run deleted something.');
    }

    /**
     * A misspelled address stops everything.
     *
     * Otherwise "keep client@exmaple.com" keeps nothing and removes the lot,
     * and the typo is only discovered afterwards.
     */
    public function test_a_name_that_does_not_exist_stops_it(): void
    {
        $here = $this->user('client@example.com');

        $this->artisan('users:keep-only', [
            'emails'  => ['client@exmaple.com'],
            '--apply' => true,
        ])->assertFailed();

        $this->assertNotNull(User::find($here->id));
    }

    /** A soft-deleted account is removed for good rather than left behind. */
    public function test_an_already_soft_deleted_account_is_cleared_out(): void
    {
        $this->user('client@example.com');
        $gone = $this->user('someone@gmail.com');
        $gone->delete();

        $this->artisan('users:keep-only', [
            'emails'  => ['client@example.com'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertNull(User::withTrashed()->find($gone->id));
    }
}
