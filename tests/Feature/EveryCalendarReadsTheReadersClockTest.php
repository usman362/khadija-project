<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ClientCalendar;
use App\Support\DisplayTimezone;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A crash, and the quieter faults found while chasing it.
 *
 * The professional's Availability page was a 500: it selected `event_date`
 * from `events`, and `events` has never had that column. That is the easy
 * half, and the guard below generalises it, because no reader catches a bad
 * column inside a string and nothing fails until somebody opens the page.
 *
 * The hard half was deciding which of the surrounding date code was also
 * wrong, and the answer was: less than it looked. Models that read their
 * dates through ShowsTimesWhereTheReaderIs already hand back every stored
 * instant on the reader's clock, so `$event->starts_at->format('Y-m-d')`
 * was right all along. Five "fixes" written on the assumption that it was
 * not were reverted; a comment describing a bug that does not exist is
 * worse than no comment.
 *
 * What was genuinely wrong is everything that did NOT come off a model:
 *
 *   Carbon::now() and Carbon::today() are the application zone, which is
 *   UTC. All evening, every evening, the calendar opened on tomorrow's
 *   month, highlighted tomorrow's square as today, and put the "now" line
 *   five hours down the day grid.
 *
 *   whereBetween built its bounds from local days with startOfDay and
 *   endOfDay and compared them against a UTC column, so every range was
 *   five hours short at each end and the last day of a month lost its
 *   evening events outright.
 *
 * None of those raise an error either; the page loads and the answer is
 * simply a different answer. Each is pinned here by its own wrong value.
 */
class EveryCalendarReadsTheReadersClockTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Imogen Hartley');
        $this->client = $this->person('client', 'Bartholomew Nash');
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /** An event at a wall-clock time on the platform's clock. */
    private function event(string $title, Carbon $localStart, int $hours = 3): Event
    {
        return Event::create([
            'title'        => $title,
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => $localStart->isPast() ? 'completed' : 'published',
            'is_published' => true,
            'starts_at'    => $localStart->copy()->utc(),
            'ends_at'      => $localStart->copy()->addHours($hours)->utc(),
            'location'     => 'Baltimore, MD',
            'state'        => 'MD',
        ]);
    }

    private function booking(Event $event, string $status = 'confirmed'): Booking
    {
        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->pro->id,
            'status'      => $status,
            'price'       => 1200,
            'currency'    => 'USD',
        ]);
    }

    /** Nine in the evening Eastern, on a date far from any month edge. */
    private function evening(string $day = '2026-11-18'): Carbon
    {
        return Carbon::parse($day . ' 21:00', DisplayTimezone::FALLBACK);
    }

    // ---------------------------------------------------------------- the crash

    /**
     * The whole bug class in one assertion: no eager load may name a column
     * the table does not have.
     *
     * `with('event:id,title,event_date')` is not a typo a reader catches, and
     * nothing fails until somebody opens that page. The rest of the app's
     * column lists were checked by hand once; this keeps them checked.
     */
    public function test_no_eager_load_asks_for_a_column_that_does_not_exist(): void
    {
        $tables = [
            'event' => 'events', 'booking' => 'bookings', 'user' => 'users',
            'author' => 'users', 'sender' => 'users', 'supplier' => 'users',
            'changer' => 'users', 'uploader' => 'users', 'referredUser' => 'users',
            'participants' => 'users', 'client' => 'users',
            'profile' => 'user_profiles',
            'categories' => 'categories', 'category' => 'categories',
            'parent' => 'categories', 'serviceCategories' => 'categories',
            'services' => 'categories',
        ];

        $root  = base_path();
        $found = [];
        foreach ([$root . '/app', $root . '/resources'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($it as $file) {
                if ($file->isDir() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                preg_match_all(
                    "/with\('([a-zA-Z_.]+):([a-zA-Z_,]+)'\)/",
                    (string) file_get_contents($file->getPathname()),
                    $m,
                    PREG_SET_ORDER
                );
                foreach ($m as $hit) {
                    $leaf  = (string) last(explode('.', $hit[1]));
                    $table = $tables[$leaf] ?? null;
                    if (! $table || ! Schema::hasTable($table)) {
                        continue;
                    }
                    foreach (explode(',', $hit[2]) as $column) {
                        if (! Schema::hasColumn($table, $column)) {
                            $found[] = sprintf(
                                '%s asks for %s.%s',
                                str_replace($root . '/', '', $file->getPathname()),
                                $table,
                                $column
                            );
                        }
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($found)));
    }

    /** The page that was a 500. */
    public function test_the_availability_page_opens(): void
    {
        $this->booking($this->event('Harbourside dinner', $this->evening()));

        $this->actingAs($this->pro)
            ->get(route('professional.availability.index'))
            ->assertOk()
            ->assertSee('Harbourside dinner');
    }

    // ------------------------------------------------------- the five quiet ones

    /**
     * A nine o'clock gig blocks its own evening, not the morning after it.
     *
     * This passed before the fix as well, and is kept because it is the
     * thing a reader will assume is broken when they see the column name
     * that caused the crash. Event reads its dates on the reader's clock,
     * so the day was never wrong here — only the column was.
     */
    public function test_an_evening_booking_blocks_the_day_it_is_on(): void
    {
        $this->booking($this->event('Harbourside dinner', $this->evening('2026-11-18')));

        $html = $this->actingAs($this->pro)
            ->get(route('professional.availability.index'))->assertOk()->getContent();

        // Each cell names its own date and what is on it.
        $this->assertStringContainsString('title="2026-11-18: Booked: Harbourside dinner"', $html);
        $this->assertStringNotContainsString('title="2026-11-19: Booked', $html);
    }

    /**
     * A model hands back its dates already on the reader's clock.
     *
     * Written down because assuming otherwise is what produced five wrong
     * fixes. If this ever fails, every bare ->format('Y-m-d') on a stored
     * date in the application has quietly become wrong at once.
     */
    public function test_a_stored_date_arrives_on_the_readers_clock(): void
    {
        $event = $this->event('Rooftop anniversary', $this->evening('2026-11-18'));

        // The row itself holds the UTC instant, which is the next day.
        $this->assertSame(
            '2026-11-19 02:00:00',
            (string) \DB::table('events')->where('id', $event->id)->value('starts_at')
        );

        // The model hands it back as the evening it is.
        $this->assertSame('2026-11-18', $event->fresh()->starts_at->format('Y-m-d'));
        $this->assertSame('9:00 PM', $event->fresh()->starts_at->format('g:i A'));
    }

    /**
     * A range keeps the late events of its last day.
     *
     * whereBetween built its bounds from local intent and compared them to a
     * UTC column, so the final day of a month ended five hours early and its
     * evening simply was not in the calendar at all.
     */
    public function test_a_month_keeps_the_evening_of_its_last_day(): void
    {
        // The last day the November grid draws, 21:00 Eastern.
        $cal  = ClientCalendar::build($this->client, 'month', '2026-11-18');
        $last = $cal['last']->copy();

        $this->event('Closing night', Carbon::parse($last->format('Y-m-d') . ' 21:00', DisplayTimezone::FALLBACK));

        $again = ClientCalendar::build($this->client, 'month', '2026-11-18');

        $this->assertTrue(
            $again['byDate']->has($last->format('Y-m-d')),
            'The last day of the range lost its evening events.'
        );
    }

    /** Seven in the evening is drawn at seven, not at eleven. */
    public function test_the_day_grid_places_an_event_at_the_hour_it_happens(): void
    {
        $this->event('Seven o\'clock ceremony', Carbon::parse('2026-11-18 19:00', DisplayTimezone::FALLBACK), 2);

        $grid = ClientCalendar::timeGrid(ClientCalendar::build($this->client, 'day', '2026-11-18'));

        $items = collect($grid['cols'])->flatMap(fn ($c) => $c['items']);
        $this->assertCount(1, $items, 'The event was not on the day it belongs to.');

        // Minutes from midnight: 19:00 is 1140, the UTC reading would be 1440.
        $this->assertSame(19 * 60, $items->first()['start']);
    }

    /** The dashboard grid keeps the evening of its last row too. */
    public function test_the_dashboard_calendar_keeps_the_last_days_evening(): void
    {
        // The November grid runs to Saturday 5 December.
        $this->event('Closing night', Carbon::parse('2026-12-05 21:00', DisplayTimezone::FALLBACK));

        $html = $this->actingAs($this->client)
            ->get(route('client.dashboard', ['cal' => '2026-11-18']))
            ->assertOk()->getContent();

        // Scoped to the grid: the title also appears in the panels above it,
        // so looking for it anywhere on the page proves nothing.
        $this->assertMatchesRegularExpression(
            '/class="od-cal-event"[^>]*title="Closing night:/',
            $html,
            'The last row of the grid lost its evening events.'
        );
    }

    /**
     * Today is today all evening.
     *
     * Carbon::now() is UTC, so from 7pm Eastern the dashboard defaulted to
     * tomorrow's month and marked tomorrow's square as today.
     */
    public function test_the_dashboard_marks_todays_square_in_the_evening(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 02:00:00', 'UTC'));

        $html = $this->actingAs($this->client)
            ->get(route('client.dashboard'))->assertOk()->getContent();

        // Every square links to its own date, so the thing to look at is
        // which one wears the today class.
        preg_match('/class="od-cal-day[^"]*\btoday\b[^"]*".*?cal=(\d{4}-\d{2}-\d{2})/s', $html, $m);

        $this->assertSame('2026-11-18', $m[1] ?? null, 'The wrong square was marked as today.');

        Carbon::setTestNow();
    }

    /** And the calendar opens on the day the reader is having. */
    public function test_the_calendar_opens_on_the_readers_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 02:00:00', 'UTC'));

        $cal = ClientCalendar::build($this->client, 'day', null);
        $this->assertSame('2026-11-18', $cal['anchor']->format('Y-m-d'));
        $this->assertStringContainsString('Today', $cal['title']);

        Carbon::setTestNow();
    }

    // ------------------------------------------------------------- the exports

    /** My Events downloads the date and time the client was shown. */
    public function test_the_client_export_prints_the_local_date_and_time(): void
    {
        $this->event('Rooftop anniversary', $this->evening('2026-11-18'));

        $csv = $this->csv($this->actingAs($this->client)->get(route('client.events.export')));

        $this->assertStringContainsString('2026-11-18', $csv);
        $this->assertStringContainsString('9:00 PM', $csv);
        $this->assertStringNotContainsString('2026-11-19', $csv);
    }

    /**
     * A file downloaded in the evening is not stamped tomorrow.
     *
     * The filenames were built from now(), which is UTC.
     */
    public function test_an_export_is_named_for_the_readers_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 02:00:00', 'UTC'));

        $this->actingAs($this->pro)->get(route('professional.gigs.export'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=my-gigs-2026-11-18.csv');

        Carbon::setTestNow();
    }

    private function csv(\Illuminate\Testing\TestResponse $response): string
    {
        $response->assertOk();

        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }

    // --------------------------------------------------------- the helpers alone

    /** A range is half-open, and covers every local day it names. */
    public function test_a_range_covers_its_first_and_last_local_day(): void
    {
        [$from, $to] = DisplayTimezone::rangeWindow('2026-11-01', '2026-11-30');

        $this->assertSame('2026-11-01 04:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-01 05:00:00', $to->format('Y-m-d H:i:s'));

        // The last day's evening is inside it; the next day's midnight is not.
        $lastEvening = Carbon::parse('2026-11-30 23:00', DisplayTimezone::FALLBACK)->utc();
        $nextMidnight = Carbon::parse('2026-12-01 00:00', DisplayTimezone::FALLBACK)->utc();

        $this->assertTrue($lastEvening->gte($from) && $lastEvening->lt($to));
        $this->assertFalse($nextMidnight->lt($to));
    }

    /** today() is the reader's today, not the database's. */
    public function test_today_is_the_readers_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 02:00:00', 'UTC'));

        $this->assertSame('2026-11-19', Carbon::today()->format('Y-m-d'));
        $this->assertSame('2026-11-18', DisplayTimezone::today()->format('Y-m-d'));

        Carbon::setTestNow();
    }
}
