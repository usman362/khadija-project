<?php

namespace Tests\Feature;

use App\Domain\Requests\BudgetGuide;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 26 September: "can get a rough estimator for the the clients so
 * they are not over guessing... more in tune with the prices lists of the
 * current professionals in our databases."
 *
 * No price list exists and professionals have never been asked for one. What
 * the platform does hold is what they have bid, which is better evidence
 * anyway: a price list is what somebody hopes to charge, a bid is what they
 * offered for a real job.
 *
 * The care is in the three refusals. It will not speak from too few bids. It
 * prefers the client's own state and says which it used. And the middle
 * figure is the median, because one unusual job moves an average somewhere
 * nobody will ever pay.
 */
class BudgetGuideTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private int $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->service = Category::create(['name' => 'Drone Photography', 'slug' => 'drone-photography-guide'])->id;
    }

    private function bid(float $amount, string $state = 'MD', array $attributes = []): Bid
    {
        $event = Event::create([
            'title'      => 'Some event',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(20),
            'state'      => $state,
        ]);

        return Bid::create(array_merge([
            'event_id'    => $event->id,
            'category_id' => $this->service,
            'supplier_id' => User::factory()->create(['primary_role' => 'professional'])->id,
            'amount'      => $amount,
            'status'      => 'submitted',
        ], $attributes));
    }

    /** Two bids are an anecdote, not a market. */
    public function test_it_says_nothing_from_too_few_bids(): void
    {
        $this->bid(400);
        $this->bid(600);

        $this->assertNull(BudgetGuide::forService($this->service),
            'Two bids should not be quoted as what professionals charge.');
    }

    /** Three and it will speak, with the range and the count. */
    public function test_it_reports_the_range_once_there_is_enough(): void
    {
        $this->bid(400);
        $this->bid(600);
        $this->bid(900);

        $g = BudgetGuide::forService($this->service);

        $this->assertNotNull($g);
        $this->assertSame(400.0, $g['low']);
        $this->assertSame(900.0, $g['high']);
        $this->assertSame(600.0, $g['typical']);
        $this->assertSame(3, $g['count']);
    }

    /** One huge job does not drag the middle figure with it. */
    public function test_the_middle_figure_is_the_median_not_the_mean(): void
    {
        foreach ([400, 420, 440, 460] as $a) {
            $this->bid($a);
        }
        $this->bid(90000);

        $g = BudgetGuide::forService($this->service);

        $this->assertSame(440.0, $g['typical'],
            'An average would have reported something nobody will ever pay.');
    }

    /** The client's own state is preferred, and the guide says so. */
    public function test_it_prefers_the_clients_state(): void
    {
        foreach ([200, 220, 240] as $a) {
            $this->bid($a, 'MD');
        }
        foreach ([5000, 5200, 5400] as $a) {
            $this->bid($a, 'NY');
        }

        $g = BudgetGuide::forService($this->service, 'MD');

        $this->assertSame('state', $g['scope']);
        $this->assertSame(240.0, $g['high'], 'New York prices leaked into a Maryland answer.');
    }

    /** With too little locally it widens, and says it has. */
    public function test_it_falls_back_to_everywhere_and_admits_it(): void
    {
        $this->bid(200, 'MD');
        foreach ([800, 900, 1000] as $a) {
            $this->bid($a, 'NY');
        }

        $g = BudgetGuide::forService($this->service, 'MD');

        $this->assertSame('everywhere', $g['scope']);
        $this->assertStringContainsString('on GigResource', BudgetGuide::sentence($g));
    }

    /** A withdrawn bid is not a price anybody is offering. */
    public function test_withdrawn_bids_are_left_out(): void
    {
        $this->bid(400);
        $this->bid(600);
        $this->bid(900, 'MD', ['status' => 'withdrawn']);

        $this->assertNull(BudgetGuide::forService($this->service));
    }

    /** A service nobody has bid on has nothing to say about itself. */
    public function test_an_unbid_service_says_nothing(): void
    {
        $other = Category::create(['name' => 'Ice Sculpture', 'slug' => 'ice-sculpture-guide'])->id;

        $this->assertNull(BudgetGuide::forService($other));
    }
}
