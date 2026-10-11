<?php

namespace Tests\Feature;

use App\Domain\CheckAvailability;
use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who is busy is a fact. Who is free is a claim nobody has made.
 *
 * Sir Peter asked for Check Availability as its own page: "Before I hire
 * somebody, are they available for my event?" Half of that is answerable.
 * Bookings tell us when a professional is taken, so a red mark is true. A
 * green one would be us saying somebody is free on a day they never told us
 * about, and a client reading it will go and book them.
 *
 * So the page tells the truth by default, and the sample view that fills in
 * the blanks is behind a flag and shouts about itself.
 */
class CheckAvailabilitySaysWhatItKnowsTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Mix Masters']);
        $this->pro->assignRole('professional');

        $this->event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(20)->setTime(18, 0),
            'city'       => 'Baltimore',
            'state'      => 'MD',
        ]);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.check-availability.index', $query))
            ->assertSuccessful()
            ->getContent();
    }

    /** Left alone, every unknown reads as unknown. */
    public function test_by_default_nobody_is_called_free(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('We can tell you who is busy, not who is free.', $html);
        $this->assertStringContainsString('Not Answered', $html);

        $states = CheckAvailability::suppliers(
            CheckAvailability::dates($this->event),
            'professional',
            false,
        )->flatMap(fn ($r) => array_values($r['states']));

        $this->assertNotContains(
            CheckAvailability::FREE,
            $states->all(),
            'Nobody has said they are free, so nothing may be marked Available.',
        );
    }

    /**
     * A booking on the day is a real answer, in either mode.
     *
     * This is the half we can stand behind, and it must not be overwritten by
     * the invented pattern when the sample view is on.
     */
    public function test_a_real_booking_shows_as_unavailable_even_in_sample_mode(): void
    {
        $category = Category::create([
            'name' => 'DJs', 'slug' => 'djs', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $busyEvent = Event::create([
            'title'      => 'Someone else’s party',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => $this->event->starts_at->copy(),
            'city'       => 'Baltimore',
            'state'      => 'MD',
        ]);

        Booking::create([
            'event_id'    => $busyEvent->id,
            'category_id' => $category->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->pro->id,
            'status'      => 'confirmed',
            'price'       => 500,
        ]);

        $dates = CheckAvailability::dates($this->event);

        foreach ([false, true] as $sample) {
            $row = CheckAvailability::suppliers($dates, 'professional', $sample)
                ->firstWhere('id', $this->pro->id);

            $this->assertSame(
                CheckAvailability::BUSY,
                $row['states']['primary'],
                $sample
                    ? 'Sample mode must not paint over a real booking.'
                    : 'A booking that day is an answer we have.',
            );
        }
    }

    /** The sample view is not something you arrive at by accident. */
    public function test_the_sample_view_has_to_be_asked_for(): void
    {
        $this->assertStringNotContainsString('Do not book from this screen', $this->page());
        $this->assertStringContainsString('Do not book from this screen', $this->page(['sample' => 1]));
    }

    /** And when it is on, it says so where nobody can miss it. */
    public function test_the_sample_view_shouts_about_itself(): void
    {
        $html = $this->page(['sample' => 1]);

        $this->assertStringContainsString('Sample answers. Do not book from this screen.', $html);
        $this->assertStringContainsString('the green and red marks are invented', $html);
        $this->assertStringContainsString('Show what we actually know', $html);
    }

    /**
     * The same person on the same date reads the same way twice.
     *
     * A pattern that shifted on every reload would be worse than useless for
     * reviewing a screen, and would look like live data changing.
     */
    public function test_the_invented_answers_hold_still(): void
    {
        $dates = CheckAvailability::dates($this->event);

        $first  = CheckAvailability::suppliers($dates, 'professional', true)->pluck('states', 'id');
        $second = CheckAvailability::suppliers($dates, 'professional', true)->pluck('states', 'id');

        $this->assertEquals($first, $second);
    }

    /** Backup dates are the client's own, never filled in for them. */
    public function test_backup_dates_are_not_invented(): void
    {
        $dates = CheckAvailability::dates($this->event);

        $this->assertCount(1, $dates, 'This event has no backups, so only its own date is checked.');
        $this->assertStringContainsString('No backup dates on this event', $this->page());
    }

    /** Where the client recorded them, they are used. */
    public function test_backup_dates_are_used_where_they_exist(): void
    {
        $this->event->update([
            'backup_dates' => [
                $this->event->starts_at->copy()->addDays(7)->toDateTimeString(),
                $this->event->starts_at->copy()->addDays(14)->toDateTimeString(),
            ],
        ]);

        $dates = CheckAvailability::dates($this->event->fresh());

        $this->assertCount(3, $dates);
        $this->assertSame('Backup Date 1', $dates[1]['label']);
        $this->assertSame('Backup Date 2', $dates[2]['label']);
    }

    /** The calendar tab opens it now instead of being greyed out. */
    public function test_the_calendar_tab_opens_it(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.calendar.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(
            '<a href="' . route('client.check-availability.index') . '" class="cal-tab">',
            $html,
        );
        $this->assertStringNotContainsString('Waiting on their side', $html);
    }
}
