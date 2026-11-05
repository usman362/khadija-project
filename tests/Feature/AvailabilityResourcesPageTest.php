<?php

namespace Tests\Feature;

use App\Domain\Calendar\Availability;
use App\Models\Category;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Khadijah's sidebar item that had no page. It opens, it answers, and the day
 * it writes is the same day the calendar reads.
 */
class AvailabilityResourcesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function professional(): User
    {
        $user = User::factory()->create(['primary_role' => 'professional']);
        $user->assignRole('professional');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($user->id);
    }

    public function test_the_page_opens_for_a_professional(): void
    {
        $this->actingAs($this->professional())
            ->get(route('professional.availability.index'))
            ->assertOk()
            ->assertSee('Availability &amp; Resources', false);
    }

    public function test_marking_a_day_is_stored_where_the_calendar_reads_it(): void
    {
        $me  = $this->professional();
        $day = Carbon::today(config('app.display_timezone'))->addDays(3)->format('Y-m-d');

        $this->actingAs($me)->post(route('professional.availability.day'), [
            'day'   => $day,
            'state' => 'available',
        ])->assertRedirect(route('professional.availability.index'));

        $this->assertSame(
            'available',
            Availability::between($me, Carbon::parse($day), Carbon::parse($day))->get($day)?->state
        );
    }

    public function test_a_range_can_be_set_and_taken_back(): void
    {
        $me   = $this->professional();
        $from = Carbon::today(config('app.display_timezone'))->addDay();
        $to   = $from->copy()->addDays(4);

        $this->actingAs($me)->post(route('professional.availability.range'), [
            'from'  => $from->format('Y-m-d'),
            'to'    => $to->format('Y-m-d'),
            'state' => 'unavailable',
        ])->assertRedirect(route('professional.availability.index'));

        $this->assertCount(5, Availability::between($me, $from, $to));

        $this->actingAs($me)->post(route('professional.availability.range'), [
            'from'  => $from->format('Y-m-d'),
            'to'    => $to->format('Y-m-d'),
            'state' => 'not_set',
        ]);

        $this->assertCount(0, Availability::between($me, $from, $to));
    }

    public function test_the_page_shows_the_crew_and_the_services(): void
    {
        $me = $this->professional();

        StaffMember::create([
            'supplier_id' => $me->id,
            'name'        => 'Rosa Mendel',
            'role'        => 'Second shooter',
            'status'      => 'active',
        ]);

        $service = Category::create([
            'name' => 'Wedding Photography',
            'slug' => 'wedding-photography-avail-test',
            'kind' => Category::SERVICE,
        ]);
        $me->serviceCategories()->attach($service->id, ['price' => 1200, 'price_unit' => 'flat rate']);

        $this->actingAs($me)
            ->get(route('professional.availability.index'))
            ->assertOk()
            ->assertSee('Rosa Mendel')
            ->assertSee('Wedding Photography');
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('professional.availability.index'))->assertRedirect();
    }
}
