<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\PricingRule;
use App\Models\User;
use App\Support\DisplayTimezone;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Two things the professional dashboard was quietly getting wrong, and the
 * state it never drew.
 *
 * Both faults came from asking a question of the wrong column. "Is this gig
 * today" was asked of a UTC date, and "did we earn this in November" was
 * asked of the date somebody typed the booking in rather than the date the
 * work happened. Neither looks wrong on a page: a nine o'clock gig simply was
 * not listed, and a month's revenue was simply a different number.
 */
class TheDashboardReadsTheRightClockAndTheRightMonthTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Rosalind Venn');
        $this->client = $this->person('client', 'Theodore Marsh');
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

    private function job(string $title, Carbon $startsAt, float $price, string $status, ?Carbon $created = null): Booking
    {
        $event = Event::create([
            'title'        => $title,
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => $startsAt->isPast() ? 'completed' : 'published',
            'is_published' => true,
            'starts_at'    => $startsAt,
            'ends_at'      => $startsAt->copy()->addHours(3),
            'location'     => 'Baltimore, MD',
            'state'        => 'MD',
        ]);

        return Booking::create([
            'event_id'   => $event->id,
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'supplier_id' => $this->pro->id,
            'status'     => $status,
            'price'      => $price,
            'currency'   => 'USD',
            'created_at' => $created ?? $startsAt->copy()->subWeeks(4),
        ]);
    }

    /** The window is the local day, expressed as the UTC instants around it. */
    public function test_the_day_window_covers_the_local_day_not_the_utc_one(): void
    {
        [$from, $to] = DisplayTimezone::dayWindow('2026-11-13');

        // Eastern is five hours behind UTC in November.
        $this->assertSame('2026-11-13 05:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-14 05:00:00', $to->format('Y-m-d H:i:s'));

        // Nine in the evening Eastern is inside it, though UTC calls it the
        // next day. This is the whole bug in one assertion.
        $evening = Carbon::parse('2026-11-13 21:00', DisplayTimezone::FALLBACK)->utc();
        $this->assertSame('2026-11-14 02:00:00', $evening->format('Y-m-d H:i:s'));
        $this->assertTrue($evening->gte($from) && $evening->lt($to));
    }

    /** A job that starts at nine in the evening is today's job. */
    public function test_an_evening_gig_is_on_todays_schedule(): void
    {
        $zone  = DisplayTimezone::FALLBACK;
        $today = Carbon::today($zone);

        $this->job('Late reception coverage', $today->copy()->setTime(21, 0), 1400, 'confirmed');

        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Late reception coverage', $html);
        $this->assertStringNotContainsString('Nothing booked for today.', $html);
    }

    /** And it is not also tomorrow's. */
    public function test_a_gig_just_after_midnight_is_not_todays(): void
    {
        $zone     = DisplayTimezone::FALLBACK;
        $tomorrow = Carbon::today($zone)->addDay();

        $this->job('Breakfast setup', $tomorrow->copy()->setTime(1, 0), 600, 'confirmed');

        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Nothing booked for today.');
    }

    /**
     * Money belongs to the month the work was done, not the month the record
     * was created.
     */
    public function test_this_months_revenue_counts_work_done_this_month(): void
    {
        PricingRule::create(['user_id' => $this->pro->id, 'monthly_revenue_goal' => 10000]);

        // Booked two months ago, performed this month: this month's money.
        $this->job(
            'Autumn gala',
            Carbon::now(DisplayTimezone::FALLBACK)->startOfMonth()->addDays(2)->setTime(18, 0),
            4000,
            'completed',
            created: now()->subMonths(2),
        );

        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('This month against your goal', $html);

        // The figure itself, net of whatever commission this account pays, so
        // the test does not hardcode a rate the plans may change.
        $net = \App\Support\Commission::netOf(4000.0, $this->pro);

        $this->assertSame(
            '$'.number_format($net, 2),
            $this->goalEarned($html),
            'The goal card should count work performed this month, whenever it was booked.'
        );
    }

    /**
     * What the goal card says has been earned.
     *
     * Read out of the card itself rather than looked for anywhere on the page:
     * the Finance Hub prints money in the same markup, so a plain search for
     * "$0.00" finds its empty balance and proves nothing about the goal.
     */
    private function goalEarned(string $html): string
    {
        $this->assertSame(1, preg_match('#<div class="pd-goal">\s*<b>([^<]+)</b>#', $html, $m),
            'The goal card did not render.');

        return trim($m[1]);
    }

    /** Work booked this month but performed later is not this month's money. */
    public function test_work_still_ahead_is_not_counted_as_earned(): void
    {
        PricingRule::create(['user_id' => $this->pro->id, 'monthly_revenue_goal' => 10000]);

        $this->job(
            'Spring wedding',
            Carbon::now(DisplayTimezone::FALLBACK)->addMonths(4)->setTime(18, 0),
            9000,
            'confirmed',
            created: now(),
        );

        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        // The goal card is there, and it has nothing to put against the goal.
        $this->assertStringContainsString('This month against your goal', $html);
        $this->assertStringContainsString('of $10,000.00', $html);
        $this->assertSame('$0.00', $this->goalEarned($html));
    }

    /**
     * The finished setup bar is a state, not an absence.
     *
     * It used to be hidden the moment the last step was done, so somebody who
     * had completed everything watched the row vanish instead of being told
     * they were ready.
     */
    public function test_a_finished_setup_says_so_instead_of_disappearing(): void
    {
        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Finish setting up');

        $service = Category::create([
            'name' => 'Wedding DJs', 'slug' => 'wedding-djs-'.uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->syncWithoutDetaching([$service->id => ['price' => 800]]);

        PricingRule::create([
            'user_id' => $this->pro->id,
            'travel_free_miles' => 25,
            'travel_rate_per_mile' => 0.65,
        ]);

        \App\Models\Package::create([
            'user_id' => $this->pro->id, 'category_id' => $service->id,
            'title' => 'Evening package', 'slug' => 'evening-package-'.uniqid(),
            'price' => 1200, 'status' => 'active', 'is_active' => true,
        ]);

        $this->pro->getOrCreateProfile()->forceFill(['identity_verified_at' => now()])->save();

        $this->actingAs($this->pro->fresh())->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Business setup')
            ->assertSee('All set, your profile is complete.')
            ->assertDontSee('Finish setting up');
    }
}
