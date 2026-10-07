<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\DisplayTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Issue #115, logged as "the Activity tab's timestamp looks four hours out".
 *
 * It was not the Activity tab. The application stores UTC, which is right,
 * and converted nowhere — so every time on every page was UTC while the
 * client read it in Baltimore. A request created at 1:06 PM logged itself at
 * 5:06 PM on the same page that said "3 minutes ago".
 *
 * Two halves, and the second is why it survived so long: times typed into a
 * form were stored unconverted too, so the platform was wrong in both
 * directions at once and the two mistakes cancelled out on screen. Fixing
 * only the display would have made every existing event read four hours
 * early, which is why the migration re-reads what was typed.
 */
class TimesAreShownOnTheRightClockTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    /**
     * One clock, and that is a finding rather than a shortcut: all seven
     * jurisdictions the platform serves keep the same time.
     */
    public function test_the_platform_has_one_clock_because_its_states_do(): void
    {
        /*
         * The seven, and all of them Eastern. This is what makes a single
         * clock honest rather than convenient — so the day an eighth state
         * arrives on another clock, this fails and says why, instead of the
         * platform quietly showing that state's clients the wrong hour.
         */
        $this->assertSame(
            ['DC', 'DE', 'MD', 'NJ', 'PA', 'VA', 'WV'],
            collect(array_keys(config('geo.allowed_states')))->sort()->values()->all(),
            'The service area changed. If the new state is not on Eastern time, '
            . 'DisplayTimezone needs to become a setting instead of a constant.'
        );

        $this->assertSame(DisplayTimezone::FALLBACK, DisplayTimezone::forUser($this->client));
        $this->assertSame(DisplayTimezone::FALLBACK, DisplayTimezone::forUser(null));
    }

    /** A time typed into a form comes back as the time that was typed. */
    public function test_a_time_the_client_types_reads_back_the_same(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'Evening Reception', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21 18:00:00',
        ]);

        $this->assertSame('18:00', $e->fresh()->starts_at->format('H:i'), 'Six in the evening came back as something else.');
    }

    /** And it is stored as the instant that really is, not as the wall time. */
    public function test_it_is_stored_in_utc(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'Evening Reception', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21 18:00:00',
        ]);

        // November: Eastern is five hours behind UTC.
        $this->assertSame(
            '2026-11-21 23:00:00',
            DB::table('events')->where('id', $e->id)->value('starts_at'),
            'The wall time was written into a UTC column.'
        );
    }

    /**
     * A stamp the application wrote is a true instant and was always right;
     * it is the reading of it that moves.
     */
    public function test_a_timestamp_the_app_wrote_is_read_on_the_local_clock(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'Anything', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'draft', 'is_published' => false,
        ]);

        $stored = Carbon::parse(DB::table('events')->where('id', $e->id)->value('created_at'), 'UTC');

        $this->assertSame(DisplayTimezone::FALLBACK, $e->fresh()->created_at->timezone->getName());
        $this->assertTrue($stored->equalTo($e->fresh()->created_at), 'The instant changed, not just the clock.');
    }

    /** Saving a record again must not walk it four hours every time. */
    public function test_reading_and_saving_a_record_leaves_its_time_alone(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'Evening Reception', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21 18:00:00',
        ]);

        $before = DB::table('events')->where('id', $e->id)->value('starts_at');

        for ($i = 0; $i < 3; $i++) {
            $again = Event::find($e->id);
            $again->title = 'Evening Reception ' . $i;
            $again->save();
        }

        $this->assertSame($before, DB::table('events')->where('id', $e->id)->value('starts_at'));
    }

    /**
     * A calendar date has no clock attached. Shifting one moves the day,
     * which turned a proposal for the event's own date into one for a
     * different date.
     */
    public function test_a_date_is_not_moved_by_the_clock(): void
    {
        $this->actingAs($this->client);

        $svc = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-tz',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $e = Event::create([
            'title' => 'Spring Wedding', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21 18:00:00',
        ]);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $bid = \App\Models\Bid::create([
            'event_id' => $e->id, 'category_id' => $svc->id, 'supplier_id' => $pro->id,
            'amount' => 900, 'status' => 'submitted',
            'available_confirmed' => true, 'confirmed_date' => '2026-11-21',
        ]);

        $this->assertSame('2026-11-21', $bid->fresh()->confirmed_date->toDateString());
    }

    /** A date with no time means midnight here, not midnight in Greenwich. */
    public function test_a_bare_date_on_a_datetime_field_is_local_midnight(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'All Day', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21',
        ]);

        $this->assertSame('2026-11-21', $e->fresh()->starts_at->toDateString(), 'The event moved to the day before.');
        $this->assertSame('00:00', $e->fresh()->starts_at->format('H:i'));
    }

    /** The whole round trip, through the form a client actually uses. */
    public function test_the_time_survives_the_request_form(): void
    {
        $svc = Category::create([
            'name' => 'Banquet Halls', 'slug' => Str::slug('Banquet Halls') . '-tz',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        // Published: the policy only lets a client edit a request that is
        // out in the world, which is a rule of its own and not what this
        // test is about.
        $e = Event::create([
            'title' => 'Reception', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
        ]);

        $this->actingAs($this->client)->patch('/client/events/' . $e->id, [
            'title' => 'Reception',
            'starts_at' => '2026-11-21 19:30',
            'ends_at' => '2026-11-21 23:30',
        ])->assertSessionHasNoErrors();

        $e->refresh();

        $this->assertSame('7:30 PM', $e->starts_at->format('g:i A'));
        $this->assertStringContainsString('7:30 PM – 11:30 PM', (string) $e->timeLabel());
    }

    /** The time carries its clock, now that the clock is no longer always UTC. */
    public function test_a_time_says_which_clock_it_is_on(): void
    {
        $this->actingAs($this->client);

        $e = Event::create([
            'title' => 'Evening Reception', 'created_by' => $this->client->id,
            'client_id' => $this->client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => '2026-11-21 18:00:00', 'ends_at' => '2026-11-21 23:00:00',
        ]);

        // November is standard time; July is not. The label follows the date.
        $this->assertStringEndsWith('EST', (string) $e->timeLabel());
        $this->assertSame('EDT', DisplayTimezone::abbreviation(null, Carbon::parse('2026-07-04 12:00', 'UTC')));
    }

    /** Account Settings says which clock, and does not offer a choice there isn't. */
    public function test_account_settings_names_the_clock(): void
    {
        $html = $this->actingAs($this->client)->get('/client/profile')->assertSuccessful()->getContent();

        $this->assertStringContainsString('Time zone', $html);
        $this->assertStringContainsString('Eastern', $html);
        $this->assertStringNotContainsString('name="timezone"', $html, 'A chooser with one option is not a choice.');
    }
}
