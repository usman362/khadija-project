<?php

namespace Tests\Feature;

use App\Domain\Budget\ServiceBudgetSuggester;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah asked whether the per-service split could be suggested rather than
 * typed from nothing.
 *
 * The Budget Planner tool could not be reused — it allocates across its own
 * fixed headings ("Venue & Rentals", "Catering & Bar"), not the services on the
 * request, and mapping one onto the other would be guesswork dressed as advice.
 *
 * So the weighting comes from the Category Masterlist's own ranking of each
 * service category for the occasion: Essential, Common, Occasional. It divides
 * the client's own number. It does not estimate what anything costs.
 */
class ServiceBudgetSuggesterTest extends TestCase
{
    use RefreshDatabase;

    private ServiceBudgetSuggester $suggester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suggester = app(ServiceBudgetSuggester::class);
    }

    /** @return array{0: Category, 1: Category} category, service beneath it */
    private function serviceUnder(string $categoryName, string $serviceName): array
    {
        $cat = Category::create([
            'name' => $categoryName, 'slug' => \Illuminate\Support\Str::slug($categoryName) . uniqid(),
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $svc = Category::create([
            'name' => $serviceName, 'slug' => \Illuminate\Support\Str::slug($serviceName) . uniqid(),
            'kind' => Category::SERVICE, 'parent_id' => $cat->id, 'is_active' => true,
        ]);

        return [$cat, $svc];
    }

    public function test_the_split_adds_up_to_the_clients_own_total(): void
    {
        [, $a] = $this->serviceUnder('Catering & Food', 'Buffet Catering');
        [, $b] = $this->serviceUnder('DJs & Musicians', 'Wedding DJ');
        [, $c] = $this->serviceUnder('Pet Services', 'Pet Sitting');

        $split = $this->suggester->suggest([$a->id, $b->id, $c->id], 10000, null);

        $this->assertSame(10000.0, array_sum($split),
            'A breakdown that does not add up to the number above it is the first thing a client notices.');
    }

    /** Rounding must not leave the split short. */
    public function test_an_awkward_total_still_adds_up(): void
    {
        [, $a] = $this->serviceUnder('Catering & Food', 'Buffet Catering');
        [, $b] = $this->serviceUnder('DJs & Musicians', 'Wedding DJ');
        [, $c] = $this->serviceUnder('Floral', 'Bouquets');

        foreach ([1, 7, 101, 9999, 33333] as $total) {
            $split = $this->suggester->suggest([$a->id, $b->id, $c->id], $total, null);
            $this->assertSame((float) $total, array_sum($split), "Total {$total} did not add up.");
        }
    }

    /** An essential service is weighted above an occasional one. */
    public function test_the_ranking_decides_the_shares(): void
    {
        [$essentialCat, $essential] = $this->serviceUnder('Catering & Food', 'Buffet Catering');
        [$occasionalCat, $occasional] = $this->serviceUnder('Pet Services', 'Pet Sitting');

        \App\Models\Category::whereIn('id', [$essentialCat->id, $occasionalCat->id])->get();

        \DB::table('category_relevance')->insert([
            ['archetype' => 'Wedding & Related Ceremonies', 'category_id' => $essentialCat->id,  'tier' => 'Essential',  'created_at' => now(), 'updated_at' => now()],
            ['archetype' => 'Wedding & Related Ceremonies', 'category_id' => $occasionalCat->id, 'tier' => 'Occasional', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \App\Domain\Taxonomy\ServiceRelevance::forget();

        $split = $this->suggester->suggest(
            [$essential->id, $occasional->id], 8000, 'Wedding & Related Ceremonies'
        );

        $this->assertGreaterThan($split[$occasional->id], $split[$essential->id],
            'An essential service should be given a larger share than an occasional one.');
        $this->assertSame(8000.0, array_sum($split));
    }

    /** Nothing to divide, or nothing to divide up. */
    public function test_it_declines_rather_than_guessing(): void
    {
        [, $only] = $this->serviceUnder('Catering & Food', 'Buffet Catering');
        [, $other] = $this->serviceUnder('Floral', 'Bouquets');

        $this->assertSame([], $this->suggester->suggest([$only->id], 10000, null),
            'One service has nothing to split.');
        $this->assertSame([], $this->suggester->suggest([$only->id, $other->id], 0, null),
            'No budget means no split.');
        $this->assertSame([], $this->suggester->suggest([], 10000, null));
    }

    /** An unranked occasion still gets an even, honest division. */
    public function test_an_unknown_occasion_divides_evenly(): void
    {
        [, $a] = $this->serviceUnder('Catering & Food', 'Buffet Catering');
        [, $b] = $this->serviceUnder('Floral', 'Bouquets');

        $split = $this->suggester->suggest([$a->id, $b->id], 1000, 'No Such Archetype');

        $this->assertSame(500.0, $split[$a->id]);
        $this->assertSame(500.0, $split[$b->id]);
    }
}
