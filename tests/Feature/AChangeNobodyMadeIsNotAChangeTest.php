<?php

namespace Tests\Feature;

use App\Domain\Agreements\{EditAgreement, Workspace};
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Editing an agreement without changing a time does not reset the signatures.
 *
 * Found on the professional's new agreement screen, 27 October, in a real row
 * in this database: a recorded "Service start" move from 2026-10-31T21:00:00Z
 * to "2026-10-31 17:00". Those are the same moment on two different clocks.
 *
 * EditAgreement::same() was written to stop exactly this, and says so: "1800"
 * typed into a form and 1800.00 out of a decimal column are the same
 * agreement, "and recording a change nobody made would reset signatures for
 * nothing". It covered numbers and not dates. The old value arrives as a
 * Carbon, the new one as the string somebody typed, and compared as text
 * "2026-10-31 17:00:00" and "2026-10-31 17:00" are different.
 *
 * The cost is not cosmetic. A recorded change raises the version, and raising
 * the version takes every approval and signature on the old one out of play.
 * So opening the edit panel and pressing send, having touched nothing, sent
 * both parties back to approving an agreement that had not changed.
 */
class AChangeNobodyMadeIsNotAChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Finalization $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(40),
        ]);

        $this->agreement = Finalization::create([
            'event_id'       => $event->id,
            'category_id'    => $service->id,
            'client_id'      => $this->client->id,
            'supplier_id'    => $this->professional->id,
            'terms_version'  => 1,
            'agreed_price'   => 4000,
            'scope'          => 'Six hours of music.',
            // Stored UTC. Displayed, and typed into the form, as 5pm Eastern.
            'service_start'  => '2026-10-31 21:00:00',
            'service_end'    => '2026-10-31 23:00:00',
            'balance_due_on' => '2026-11-15',
        ]);
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /** The form hands back the wall clock it was filled with. */
    private function asTheFormSendsIt(): array
    {
        $f = $this->agreement->fresh();

        return [
            'scope'           => $f->scope,
            'agreed_price'    => '4000',
            'service_start'   => $f->service_start->format('Y-m-d H:i'),
            'service_end'     => $f->service_end->format('Y-m-d H:i'),
            'schedule_notes'  => $f->schedule_notes,
            'deposit_percent' => $f->deposit_percent,
            'balance_due_on'  => $f->balance_due_on->format('Y-m-d'),
            'payment_terms'   => $f->payment_terms,
        ];
    }

    public function test_sending_the_same_terms_back_changes_nothing(): void
    {
        $changed = EditAgreement::diff(
            EditAgreement::current($this->agreement),
            $this->asTheFormSendsIt(),
        );

        $this->assertSame([], $changed, 'The diff reports a change in: ' . implode(', ', array_keys($changed)));
    }

    /**
     * And the signatures survive it, which is the harm this prevents.
     */
    public function test_the_approvals_survive_an_edit_that_changed_nothing(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        $before = Workspace::version($this->agreement->fresh());

        EditAgreement::submit($this->agreement->fresh(), $this->client, $this->asTheFormSendsIt(), 'No change.');

        $f = $this->agreement->fresh();

        $this->assertSame($before, Workspace::version($f), 'The version moved over an edit that changed nothing.');
        $this->assertTrue(Workspace::supplierApproved($f), "The professional's approval was thrown away.");
        $this->assertTrue(Workspace::clientApproved($f), "The client's approval was thrown away.");
    }

    /** A time that really moved is still a change. */
    public function test_a_time_that_really_moved_is_still_reported(): void
    {
        $terms = $this->asTheFormSendsIt();
        $terms['service_start'] = '2026-10-31 18:00';

        $changed = EditAgreement::diff(EditAgreement::current($this->agreement), $terms);

        $this->assertArrayHasKey('service_start', $changed);
    }

    /** As is a date that really moved. */
    public function test_a_date_that_really_moved_is_still_reported(): void
    {
        $terms = $this->asTheFormSendsIt();
        $terms['balance_due_on'] = '2026-11-20';

        $changed = EditAgreement::diff(EditAgreement::current($this->agreement), $terms);

        $this->assertArrayHasKey('balance_due_on', $changed);
    }

    /**
     * A due date is a calendar day and is never shifted onto a clock.
     *
     * The same mistake in the other direction: read the 15th as midnight
     * somewhere and it becomes the evening of the 14th somewhere else.
     */
    public function test_a_due_date_is_not_moved_by_a_timezone(): void
    {
        $this->assertSame('Nov 15, 2026', EditAgreement::readable('balance_due_on', '2026-11-15'));
        $this->assertSame(
            'Nov 15, 2026',
            EditAgreement::readable('balance_due_on', $this->agreement->fresh()->balance_due_on),
        );
    }

    // ── Reading what was recorded ────────────────────────────

    /**
     * Rows already written cannot be fixed by changing what is stored, so the
     * formatting happens at the point of reading and the old rows read
     * correctly too.
     */
    public function test_a_stored_utc_instant_reads_on_the_readers_clock(): void
    {
        $this->assertSame(
            'Oct 31, 2026 · 5:00 PM',
            EditAgreement::readable('service_start', '2026-10-31T21:00:00.000000Z'),
        );
    }

    /** And a wall clock string, which carries no zone, is not moved at all. */
    public function test_a_wall_clock_string_is_not_shifted(): void
    {
        $this->assertSame(
            'Oct 31, 2026 · 5:00 PM',
            EditAgreement::readable('service_start', '2026-10-31 17:00'),
        );
    }

    /**
     * The two notations the one real row holds now read as the same time.
     *
     * Which is the point: the history is only worth showing if a reader can
     * tell a change from a change of notation.
     */
    public function test_the_two_notations_read_alike(): void
    {
        $this->assertSame(
            EditAgreement::readable('service_start', '2026-10-31T21:00:00.000000Z'),
            EditAgreement::readable('service_start', '2026-10-31 17:00'),
        );
    }

    public function test_text_terms_are_printed_as_they_are(): void
    {
        $this->assertSame('Six hours of music.', EditAgreement::readable('scope', 'Six hours of music.'));
        $this->assertSame('not set', EditAgreement::readable('payment_terms', null));
        $this->assertSame('not set', EditAgreement::readable('scope', '  '));
    }

    /** Something unparseable is shown rather than swallowed. */
    public function test_a_value_that_will_not_parse_is_still_shown(): void
    {
        $this->assertSame('whenever', EditAgreement::readable('service_start', 'whenever'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
