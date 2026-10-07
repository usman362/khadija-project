<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GigResourceId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Issue #76, reported four times as a fix that keeps reverting.
 *
 * It never reverted. GigResourceId::display() strips a GR- sitting in front
 * of a real prefix and leaves a bare GR-###### alone on purpose — an old
 * fallback reference is somebody's actual reference. What it cannot do is
 * invent a prefix for an account whose stored value never had one, which is
 * why fixing the display twice changed nothing on Account Settings: the GR-
 * there is what the column contains.
 *
 * So the remaining work is data, and data that people may have written down,
 * which is why it is a command with a dry run and not a migration that runs
 * itself on deploy.
 */
class TheRetiredPrefixCanBeRetiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function legacy(string $reference, ?string $role, ?string $primary = null): User
    {
        $u = User::factory()->create(['primary_role' => $primary]);
        if ($role) {
            $u->assignRole($role);
        }
        DB::table('users')->where('id', $u->id)->update(['public_id' => $reference]);

        return $u->fresh();
    }

    /** The display component was never the fault; this is what it does. */
    public function test_the_display_was_right_all_along(): void
    {
        // A doubled prefix is stripped — the thing that was actually fixed.
        $this->assertSame('CL-482731', GigResourceId::display('GR-CL-482731'));

        // A bare legacy reference is left alone, because nothing in the
        // string says what it should become.
        $this->assertSame('GR-482731', GigResourceId::display('GR-482731'));
    }

    /** Nothing writes a GR- reference any more. */
    public function test_a_new_account_cannot_be_given_the_retired_prefix(): void
    {
        foreach (['client', 'professional', 'influencer', 'admin', 'affiliate', null] as $role) {
            $this->assertStringStartsNotWith('GR', GigResourceId::prefixFor($role));
        }

        $this->assertSame('USR', GigResourceId::prefixFor(null));
    }

    /** Reporting only, until somebody says otherwise. */
    public function test_it_changes_nothing_without_apply(): void
    {
        $u = $this->legacy('GR-482731', 'client');

        $this->artisan('ids:retire-gr-prefix')
            ->expectsOutputToContain('CL-482731')
            ->assertSuccessful();

        $this->assertSame('GR-482731', $u->fresh()->public_id);
    }

    /** The digits never move: they are the identity, the prefix is a label. */
    public function test_apply_keeps_the_digits_and_changes_only_the_prefix(): void
    {
        $client = $this->legacy('GR-482731', 'client');
        $pro    = $this->legacy('GR-100482', 'professional');
        $noRole = $this->legacy('GR-769027', null);

        $this->artisan('ids:retire-gr-prefix --apply')->assertSuccessful();

        $this->assertSame('CL-482731', $client->fresh()->public_id);
        $this->assertSame('PRO-100482', $pro->fresh()->public_id);
        $this->assertSame('USR-769027', $noRole->fresh()->public_id);
    }

    /** It will not hand two accounts the same reference. */
    public function test_it_refuses_to_collide(): void
    {
        $legacy = $this->legacy('GR-482731', 'client');
        $holder = $this->legacy('CL-482731', 'client');

        $this->artisan('ids:retire-gr-prefix --apply')->assertSuccessful();

        $this->assertSame('GR-482731', $legacy->fresh()->public_id, 'It overwrote a reference somebody else holds.');
        $this->assertSame('CL-482731', $holder->fresh()->public_id);
    }

    /** A doubled prefix is the display's job, not this command's. */
    public function test_it_leaves_a_doubled_prefix_to_the_display(): void
    {
        $u = $this->legacy('GR-CL-482731', 'client');

        $this->artisan('ids:retire-gr-prefix --apply')->assertSuccessful();

        $this->assertSame('GR-CL-482731', $u->fresh()->public_id);
        $this->assertSame('CL-482731', GigResourceId::display($u->fresh()->public_id));
    }
}
