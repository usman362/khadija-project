<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Support\Earnings;
use App\Support\Reports\ProfessionalReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A professional's own report — the second of the three Peter asked for on
 * 2026-08-09. Professionals had two CSV exports of their transactions and no
 * view of whether their bidding is working, which is the only question a
 * professional on a marketplace actually has.
 *
 * Money and response figures are read from Earnings and ResponseStats rather
 * than recomputed, and a test below pins that: a report disagreeing with the
 * Earnings page about the same professional's money is worse than no report,
 * and that exact defect has already been found once on this platform.
 */
class ProfessionalReportTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->account('professional');
        $this->client = $this->account('client');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function gig(string $title = 'Gala'): Event
    {
        return Event::create([
            'title' => $title, 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'is_published' => true, 'status' => 'published', 'starts_at' => now()->addMonth(),
        ]);
    }

    private function bidOn(Event $event, string $status = 'submitted'): Bid
    {
        return Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id,
            'amount' => 1000, 'status' => $status,
        ]);
    }

    private function win(Event $event, float $price = 1000): Booking
    {
        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => $price, 'currency' => 'USD',
        ]);
    }

    private function report(): array
    {
        return (new ProfessionalReport($this->pro, now()->subDays(90), now()))->all();
    }

    public function test_an_open_bid_is_neither_won_nor_lost(): void
    {
        // The measure that matters. Counting open bids as losses would tell a
        // professional their bidding is failing when it is in progress.
        $won = $this->gig('Won');
        $this->bidOn($won);
        $this->win($won);

        $this->bidOn($this->gig('Lost'), 'not_selected');
        $this->bidOn($this->gig('Still open'));

        $bidding = $this->report()['bidding'];

        $this->assertSame(3, $bidding['placed']);
        $this->assertSame(1, $bidding['won']);
        $this->assertSame(1, $bidding['lost']);
        $this->assertSame(1, $bidding['open']);
        $this->assertSame(50, $bidding['win_rate'], 'one of two DECIDED bids');
    }

    public function test_a_professional_who_has_not_bid_has_no_win_rate(): void
    {
        // Not 0%. Nobody has lost anything yet.
        $this->assertNull($this->report()['bidding']['win_rate']);
        $this->assertNull($this->report()['bidding']['average_bid']);
    }

    public function test_the_report_agrees_with_the_earnings_page(): void
    {
        // The reason money is read from App\Support\Earnings rather than
        // recomputed here. Two places computing one professional's balance is
        // two chances to disagree.
        $this->win($this->gig(), 2000);

        $report = $this->report()['money'];
        $earnings = Earnings::forProfessional($this->pro);

        $this->assertSame($earnings['available'], $report['available']);
        $this->assertSame($earnings['earned'], $report['lifetime_earned']);
    }

    public function test_earnings_in_range_are_net_of_commission(): void
    {
        // Gross would flatter the number and is not what they are paid.
        $this->win($this->gig(), 1000);

        // Starter's 5%.
        $this->assertSame(950.0, $this->report()['money']['earned_in_range']);
    }

    public function test_a_booking_outside_the_range_is_not_in_the_range_figure(): void
    {
        $old = $this->win($this->gig(), 5000);
        $old->forceFill(['created_at' => now()->subYear()])->saveQuietly();

        $money = $this->report()['money'];

        $this->assertSame(0.0, $money['earned_in_range']);
        // But it is still theirs, and the lifetime figure says so.
        $this->assertGreaterThan(0, $money['lifetime_earned']);
    }

    public function test_reputation_comes_from_real_reviews(): void
    {
        $booking = $this->win($this->gig());
        Review::create([
            'reviewer_id' => $this->client->id, 'reviewee_id' => $this->pro->id,
            'booking_id' => $booking->id, 'rating' => 5, 'comment' => 'Excellent.',
        ]);

        $rep = $this->report()['reputation'];

        $this->assertSame(5.0, $rep['rating']);
        $this->assertSame(1, $rep['reviews']);
    }

    public function test_an_unrated_professional_has_no_rating_rather_than_zero(): void
    {
        $this->assertNull($this->report()['reputation']['rating']);
        $this->assertNull($this->report()['reputation']['response_rate']);
    }

    public function test_every_month_in_the_range_appears_even_at_zero(): void
    {
        // Skipping empty months makes a quiet spring look like continuous work.
        $months = collect($this->report()['over_time']);

        $this->assertGreaterThanOrEqual(3, $months->count());
        $this->assertTrue($months->every(fn ($m) => array_key_exists('earned', $m)));
    }

    public function test_opportunities_are_read_through_the_feed(): void
    {
        // So the report and the dashboard cannot disagree about how much work
        // is open to the same professional.
        $opps = $this->report()['opportunities'];

        $this->assertFalse($opps['has_services']);
        $this->assertArrayHasKey('in_your_services', $opps);
    }

    /* ── The page ──────────────────────────────────────────── */

    public function test_the_professional_can_open_their_report(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.reports.index'))
            ->assertSuccessful()
            ->assertSee('Your bidding')
            ->assertSee('Win rate');
    }

    public function test_the_range_falls_back_on_nonsense(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.reports.index', ['range' => 'nope']))
            ->assertSuccessful()->assertViewHas('range', '90');
    }

    public function test_the_dropdown_shows_the_range_the_page_is_actually_on(): void
    {
        // It did not. The array keys are numeric strings, which PHP casts to
        // int on the way into a foreach, so `$range === $value` compared
        // '90' to 90 and was never true — no option carried `selected` and
        // the browser fell back to showing the first one. The page said one
        // range and the figures were another.
        //
        // Asserted on the rendered option, not on the view variable: the old
        // test checked assertViewHas() and passed all the way through this.
        $page = $this->actingAs($this->pro)
            ->get(route('professional.reports.index', ['range' => '90']));

        $this->assertMatchesRegularExpression(
            '/<option value="' . trim('90', "'") . '"[^>]*\bselected\b/',
            $page->getContent(),
            'the Last 90 days option is not marked selected',
        );
    }

    public function test_the_csv_downloads(): void
    {
        $this->win($this->gig(), 1000);

        $response = $this->actingAs($this->pro)->get(route('professional.reports.csv'));

        $response->assertSuccessful();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Your GigResource report', $csv);
        $this->assertStringContainsString('Bidding', $csv);
        $this->assertStringContainsString('Month,Earned,Bookings', $csv);
    }

    public function test_the_sidebar_links_to_it(): void
    {
        $this->assertStringContainsString(
            "route('professional.reports.index')",
            file_get_contents(resource_path('views/layouts/professional.blade.php')),
        );
    }
}
