<?php

namespace Tests\Feature;

use App\Domain\Compliance\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The compliance register keeps itself honest.
 *
 * Sir Peter, 2026-09-09: record which state, which law and what date, so we
 * can look back and know. Khadijah's Terms-of-Service checklist lands here as
 * she writes it, and each item is ticked off as it is built.
 *
 * The failure mode of a register is that it becomes a list of things somebody
 * believes are done. So a row cannot say done without naming what satisfies it
 * and the month it was finished — and the page shows its own gaps rather than
 * waiting to be audited.
 */
class ComplianceRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_requirement_says_which_state_and_which_law(): void
    {
        $this->assertNotEmpty(Register::all());

        foreach (Register::all() as $row) {
            $this->assertNotEmpty($row['jurisdiction'] ?? null, "{$row['key']} has no state.");
            $this->assertNotEmpty($row['law'] ?? null, "{$row['key']} names no law.");
            $this->assertNotEmpty($row['effective'] ?? null, "{$row['key']} has no date it comes into force.");
            $this->assertNotEmpty($row['requires'] ?? null, "{$row['key']} does not say what it requires.");

            $this->assertContains($row['status'], Register::STATUSES, "{$row['key']} has a status nobody defined.");
        }
    }

    /** The month and year Sir Peter asked to be able to look back at. */
    public function test_a_finished_item_records_when_and_what_did_it(): void
    {
        foreach (Register::all()->where('status', 'done') as $row) {
            $this->assertNotEmpty($row['implemented'], "{$row['key']} says done but names nothing that does it.");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', (string) $row['done_on'],
                "{$row['key']} says done without a month.");
        }
    }

    /** Dates are month and year, which is the granularity that was asked for. */
    public function test_dates_are_recorded_as_month_and_year(): void
    {
        foreach (Register::all() as $row) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', (string) $row['effective'],
                "{$row['key']} does not record when the law comes into force as YYYY-MM.");
        }
    }

    /**
     * A missing citation is reported, not filled in.
     *
     * Nobody has given us the reference for the Maryland one yet. Inventing a
     * code section would be worse than leaving it blank, because a blank gets
     * asked about and an invented one gets quoted.
     */
    public function test_the_register_reports_its_own_gaps(): void
    {
        $problems = Register::problems();

        $this->assertNotEmpty($problems, 'Every citation is on file — then this test can go.');

        foreach ($problems as $p) {
            $this->assertNotEmpty($p['key']);
            $this->assertNotEmpty($p['problem']);
        }
    }

    /** A row that claims done with nothing behind it is caught. */
    public function test_a_hollow_claim_is_caught(): void
    {
        config(['compliance-register.requirements' => [[
            'key' => 'made-up', 'jurisdiction' => 'Nowhere', 'law' => 'An Act',
            'citation' => 'X-1', 'effective' => '2020-01', 'requires' => 'Something',
            'status' => 'done', 'implemented' => null, 'done_on' => null,
        ]]]);

        $problems = Register::problems()->pluck('problem')->implode(' ');

        $this->assertStringContainsString('nothing is named', strtolower($problems));
        $this->assertStringContainsString('no month', strtolower($problems));
    }

    /* ── The page ───────────────────────────────────────────── */

    public function test_staff_can_see_the_checklist(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create(['primary_role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin->fresh())
            ->get(route('app.admin.compliance.index'))
            ->assertSuccessful()
            ->assertSee('Washington DC')
            ->assertSee('Automatic Renewal Protections Act of 2018')
            ->assertSee('Maryland');
    }

    public function test_it_is_a_staff_screen(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');

        $this->actingAs($client->fresh())
            ->get(route('app.admin.compliance.index'))
            ->assertForbidden();
    }
}
