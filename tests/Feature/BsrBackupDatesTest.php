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
 * with up to three backup dates.
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

    public function test_the_availability_step_offers_three_backup_dates(): void
    {
        $this->availability([]);

        $html = $this->actingAs($this->client)->get(route('client.bsr.step', 'availability'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'name="backup_dates[]"'));
    }

    /** Blanks dropped, repeats merged, the preferred day itself left out, in date order. */
    public function test_backup_dates_are_cleaned_up(): void
    {
        $preferred = now()->addDays(30)->toDateString();
        $later = now()->addDays(40)->toDateString();
        $sooner = now()->addDays(35)->toDateString();

        $this->availability(['backup_dates' => [$later, $preferred, $sooner]])
            ->assertSessionHasNoErrors();

        $this->assertSame([$sooner, $later], session('bsr_wizard')['backup_dates']);
    }

    public function test_more_than_three_are_refused(): void
    {
        $this->availability(['backup_dates' => [
            now()->addDays(31)->toDateString(), now()->addDays(32)->toDateString(),
            now()->addDays(33)->toDateString(), now()->addDays(34)->toDateString(),
        ]])->assertSessionHasErrors('backup_dates');
    }

    public function test_a_past_backup_date_is_refused(): void
    {
        $this->availability(['backup_dates' => [now()->subDay()->toDateString()]])
            ->assertSessionHasErrors('backup_dates.0');
    }

    public function test_blank_and_repeated_backup_dates_are_dropped(): void
    {
        $day = now()->addDays(36)->toDateString();

        $this->availability(['backup_dates' => ['', $day, $day]])->assertSessionHasNoErrors();

        $this->assertSame([$day], session('bsr_wizard')['backup_dates']);
    }

    /** Saved on the request, and shown to the client on the review step. */
    public function test_backup_dates_are_saved_and_shown(): void
    {
        $backup = now()->addDays(37);

        $this->availability(['backup_dates' => [$backup->toDateString()]]);

        $this->actingAs($this->client)->get(route('client.bsr.step', 'review'))
            ->assertOk()
            ->assertSee('Backup dates')
            ->assertSee($backup->format('M j, Y'));

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date' => now()->addDays(30)->toDateString(), 'event_start_time' => '18:00',
            'backup_dates' => [$backup->toDateString()], 'action' => 'draft',
        ]);

        $this->assertSame([$backup->toDateString()], Event::where('client_id', $this->client->id)->firstOrFail()->backup_dates);
    }
}
