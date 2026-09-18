<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The date, asked once, and the days the client could fall back on.
 *
 * Sir Peter, 2026-09-16: "In step 5 & 6 please make sure they don't repeat the
 * same questions", then: "If the client picks one date … can step 6 be used as
 * other dates that the clients are open minded for … bc the professionals
 * might be only available on certain times or dates."
 *
 * The wizard asked for the date on Event Details and again on Availability
 * Match. It is asked on Availability Match only now, beside who is free on it,
 * with up to five backup dates, each with its own times.
 */
class BsrBackupDatesTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $type = Category::create(['name' => 'Wedding', 'slug' => 'wedding-bd', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);
        $group = Category::create(['name' => 'Music', 'slug' => 'music-bd', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $service = Category::create(['name' => 'Wedding DJs', 'slug' => 'wedding-djs-bd', 'parent_id' => $group->id, 'kind' => Category::SERVICE, 'is_active' => true]);

        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [$service->id], 'event_type' => $type->name, 'organization_type' => 'individual',
        ])->assertSessionHasNoErrors();
    }

    /** Steps 2 and 3, so the later steps can be opened. */
    private function earlierSteps(): void
    {
        $this->actingAs($this->client)->post(route('client.bsr.save', 'event'), ['location_kind' => 'area', 'location' => 'Baltimore, MD']);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'requirements'), ['description' => 'A DJ for a wedding reception of about one hundred and fifty guests.']);
    }

    private function availability(array $payload): \Illuminate\Testing\TestResponse
    {
        $this->earlierSteps();

        return $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), $payload + [
            'event_date' => now()->addDays(30)->toDateString(), 'event_start_time' => '18:00',
        ]);
    }

    /** The date is no longer asked on Event Details. */
    public function test_event_details_does_not_ask_for_the_date(): void
    {
        $this->actingAs($this->client)->get(route('client.bsr.step', 'event'))
            ->assertOk()
            ->assertDontSee('name="starts_at"', false)
            ->assertSee("pick the date on the Availability step", false);
    }

    /** Sir Peter's Step 7: up to five backup dates, each with its own times. */
    public function test_the_availability_step_offers_backup_rows_with_their_own_times(): void
    {
        $this->earlierSteps();

        $this->actingAs($this->client)->get(route('client.bsr.step', 'availability'))
            ->assertOk()
            ->assertSee('0 of 5 backup dates added')
            ->assertSee('+ Add Another Backup Date')
            ->assertSee('One event, one date for all services')
            ->assertSee('data-f="start"', false);
    }

    /** Blanks dropped, repeats merged, the preferred day itself left out, in date order. */
    public function test_backup_dates_are_cleaned_up(): void
    {
        $preferred = now()->addDays(30)->toDateString();
        $later = now()->addDays(40)->toDateString();
        $sooner = now()->addDays(35)->toDateString();

        $this->availability(['backup_dates' => [
            ['date' => $later, 'start' => '12:00', 'end' => '16:00'],
            ['date' => $preferred],
            ['date' => $sooner],
        ]])->assertSessionHasNoErrors();

        $this->assertSame([
            // No times given: the preferred date's times are assumed.
            ['date' => $sooner, 'start' => '18:00', 'end' => null],
            ['date' => $later, 'start' => '12:00', 'end' => '16:00'],
        ], session('bsr_wizard')['backup_dates']);
    }

    public function test_more_than_five_are_refused(): void
    {
        $rows = collect(range(31, 36))->map(fn ($d) => ['date' => now()->addDays($d)->toDateString()])->all();

        $this->availability(['backup_dates' => $rows])->assertSessionHasErrors('backup_dates');
    }

    public function test_five_are_allowed(): void
    {
        $rows = collect(range(31, 35))->map(fn ($d) => ['date' => now()->addDays($d)->toDateString()])->all();

        $this->availability(['backup_dates' => $rows])->assertSessionHasNoErrors();
        $this->assertCount(5, session('bsr_wizard')['backup_dates']);
    }

    public function test_a_past_backup_date_is_refused(): void
    {
        $this->availability(['backup_dates' => [['date' => now()->subDay()->toDateString()]]])
            ->assertSessionHasErrors('backup_dates.0.date');
    }

    public function test_a_bad_backup_time_is_refused(): void
    {
        $this->availability(['backup_dates' => [['date' => now()->addDays(33)->toDateString(), 'start' => '25:99']]])
            ->assertSessionHasErrors('backup_dates.0.start');
    }

    public function test_blank_and_repeated_backup_dates_are_dropped(): void
    {
        $day = now()->addDays(36)->toDateString();

        $this->availability(['backup_dates' => [['date' => ''], ['date' => $day], ['date' => $day]]])->assertSessionHasNoErrors();

        $this->assertSame([$day], array_column(session('bsr_wizard')['backup_dates'], 'date'));
    }

    /** Saved on the request, and shown to the client on the review step. */
    public function test_backup_dates_are_saved_and_shown(): void
    {
        $backup = now()->addDays(37);

        $this->availability(['backup_dates' => [['date' => $backup->toDateString(), 'start' => '14:00', 'end' => '20:00']]]);

        $this->actingAs($this->client)->get(route('client.bsr.step', 'review'))
            ->assertOk()
            ->assertSee('Backup')
            ->assertSee($backup->format('M j, Y'));

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date' => now()->addDays(30)->toDateString(), 'event_start_time' => '18:00',
            'backup_dates' => [['date' => $backup->toDateString(), 'start' => '14:00', 'end' => '20:00']], 'action' => 'draft',
        ]);

        $this->assertSame(
            [['date' => $backup->toDateString(), 'start' => '14:00', 'end' => '20:00']],
            Event::where('client_id', $this->client->id)->firstOrFail()->backup_dates,
        );
    }
}
