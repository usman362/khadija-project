<?php

namespace Tests\Feature;

use App\Console\Commands\InventoryDemoData;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PM-2 / OA-25 — no fake data in production views.
 *
 * Three things are worth guarding here, and none of them is "the command
 * deletes rows":
 *
 *  1. It must not delete the administrator. AdminUserSeeder writes
 *     admin@example.com, which matches every pattern you would reach for when
 *     looking for fake accounts.
 *  2. It must not delete an account no seeder created. A hand-made test login
 *     and a real person using an example.com address look identical from here.
 *  3. The seeder must never attach demo data to a real account, which is the
 *     bug that put "Corporate Gala — Full Production" on an owner's own page.
 */
class DemoDataCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $email, string $role = 'client'): User
    {
        $u = User::factory()->create(['email' => $email, 'primary_role' => $role]);
        $u->assignRole($role);

        return $u;
    }

    /* ── What counts as demo ────────────────────────────────── */

    public function test_the_administrator_is_never_treated_as_demo_data(): void
    {
        $admin = $this->user('admin@example.com', 'client');

        $this->assertNotContains($admin->id, InventoryDemoData::demoUserIds()->all());

        $this->assertNotContains(
            'admin@example.com',
            array_column(InventoryDemoData::unrecognisedAccounts(), 'email'),
            'the administrator must not be offered for removal at all',
        );
    }

    public function test_an_account_no_seeder_wrote_is_reported_but_not_removable(): void
    {
        $seeded = $this->user('client@example.com');
        $byHand = $this->user('bridgedemo@example.test');

        $this->assertContains($seeded->id, InventoryDemoData::demoUserIds()->all());
        $this->assertNotContains($byHand->id, InventoryDemoData::demoUserIds()->all());

        $this->assertSame(
            ['bridgedemo@example.test'],
            array_column(InventoryDemoData::unrecognisedAccounts(), 'email'),
        );
    }

    /**
     * The precedence bug this was written after. Built flat, the query reads
     *
     *     where email like '%@example.test'
     *        or email like '%@example.com' and email not in (...)
     *
     * and OR binds loosest, so every seeded account came back as
     * unrecognised — the report told you to review by hand the twenty rows it
     * was supposed to handle for you.
     */
    public function test_a_seeded_account_does_not_also_appear_as_unrecognised(): void
    {
        $this->user('bloomvine.demo@example.test');
        $this->user('client@example.com');

        $this->assertSame([], InventoryDemoData::unrecognisedAccounts());
    }

    /* ── Removal ────────────────────────────────────────────── */

    public function test_the_purge_writes_nothing_without_apply(): void
    {
        $demo = $this->user('client@example.com');

        $this->artisan('demo:purge')->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $demo->id]);
    }

    public function test_apply_removes_the_demo_account_and_what_it_owns(): void
    {
        $demo = $this->user('client@example.com');
        $event = Event::create([
            'title' => 'Demo event', 'client_id' => $demo->id,
            'created_by' => $demo->id, 'status' => 'open',
        ]);

        $real = $this->user('someone@gigresource.com');
        $keep = Event::create([
            'title' => 'A real event', 'client_id' => $real->id,
            'created_by' => $real->id, 'status' => 'open',
        ]);

        $this->artisan('demo:purge --apply')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $demo->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);

        $this->assertDatabaseHas('users', ['id' => $real->id]);
        $this->assertDatabaseHas('events', ['id' => $keep->id]);
    }

    /**
     * The reason --force-orphans exists. bookings.supplier_id is SET NULL, so
     * deleting a demo professional does not delete a real client's booking of
     * them — it leaves a booking with nobody booked, which is a worse page
     * than the sample data was.
     */
    public function test_it_stops_rather_than_leave_a_real_booking_with_no_professional(): void
    {
        $demoPro = $this->user('supplier@example.com', 'professional');
        $real = $this->user('someone@gigresource.com');

        $event = Event::create([
            'title' => 'Real event', 'client_id' => $real->id,
            'created_by' => $real->id, 'status' => 'open',
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $real->id,
            'created_by' => $real->id, 'supplier_id' => $demoPro->id,
            'status' => 'confirmed', 'price' => 100, 'currency' => 'USD',
        ]);

        $this->artisan('demo:purge --apply')->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $demoPro->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);

        // Acknowledged, it goes ahead.
        $this->artisan('demo:purge --apply --force-orphans')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $demoPro->id]);
    }

    /* ── Demo events stranded on real accounts ──────────────── */

    public function test_a_demo_event_on_a_real_account_is_reported_and_left_alone(): void
    {
        $real = $this->user('someone@gigresource.com');

        $stray = Event::create([
            'title' => 'Corporate Gala — Full Production',
            'client_id' => $real->id, 'created_by' => $real->id, 'status' => 'open',
        ]);

        $found = InventoryDemoData::strandedOnRealAccounts(InventoryDemoData::demoUserIds());

        $this->assertSame([$stray->id], array_column($found, 'id'));

        // Not touched by a plain purge — it belongs to a real person.
        $this->artisan('demo:purge --apply --force-orphans')->assertSuccessful();
        $this->assertDatabaseHas('events', ['id' => $stray->id]);

        $this->artisan('demo:purge --apply --stranded --force-orphans')->assertSuccessful();
        $this->assertDatabaseMissing('events', ['id' => $stray->id]);
    }

    /** A real client may raise an event whose title starts the same way. */
    public function test_a_real_event_with_a_similar_title_is_not_matched(): void
    {
        $real = $this->user('someone@gigresource.com');

        Event::create([
            'title' => 'Corporate Gala', 'client_id' => $real->id,
            'created_by' => $real->id, 'status' => 'open',
        ]);

        $this->assertSame([], InventoryDemoData::strandedOnRealAccounts(InventoryDemoData::demoUserIds()));
    }

    /* ── The cause, not just the symptom ────────────────────── */

    /**
     * DemoGigsSeeder used to fall back to `User::role('client')->first()` when
     * the demo client did not exist. On production it did not exist, so the
     * first real client on the platform was handed six sample gigs.
     */
    public function test_the_gig_seeder_attaches_nothing_when_the_demo_client_is_absent(): void
    {
        $real = $this->user('someone@gigresource.com');

        $this->assertSame(0, User::where('email', 'client@example.com')->count());

        (new \Database\Seeders\DemoGigsSeeder)->run();

        $this->assertSame(
            0,
            Event::where('client_id', $real->id)->count(),
            'demo gigs must never land on a real account',
        );
    }

    /** And on production the demo seeders decline to run at all. */
    public function test_demo_seeders_refuse_to_run_on_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new \Database\Seeders\DemoUsersSeeder)->run();

        $this->assertSame(0, User::where('email', 'client@example.com')->count());
    }
}
