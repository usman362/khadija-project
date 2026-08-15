<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a professional is told a client's budget is.
 *
 * Found while answering the Multi-Service Budget Framework's Part 17 review
 * questions. The board built the figure as `budget * 0.85` to `budget`, over a
 * `budget` column that the wizard fills with the range's FLOOR. A client
 * offering $2,000–$3,000 was shown to every professional as $1,700–$2,000: the
 * top of what they saw was the bottom of what was offered, both numbers were
 * invented, and budget_min / budget_max sat unread in the same row.
 *
 * Professionals price against this figure, so it decides who bids and at what.
 */
class BoardBudgetTruthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(): User
    {
        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->givePermissionTo(['dashboard.view']);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function client(): User
    {
        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function request(array $money, string $source = 'bsr'): Event
    {
        $e = Event::create(array_merge([
            'title' => 'Anniversary Dinner', 'client_id' => $this->client()->id,
            'created_by' => $this->client()->id, 'is_published' => true,
            'status' => 'published', 'starts_at' => now()->addMonth(),
            'state' => 'MD', 'source' => $source,
            'proposal_deadline' => now()->addWeek(),
            // Multi-service requests are held back from non-Elite tiers for
            // their first hour. Backdated so this suite tests the budget
            // label rather than the early-access gate.
            'published_at' => now()->subDay(),
        ], $money));

        $e->categories()->attach(Category::firstOrCreate(
            ['slug' => 'djs'],
            ['name' => 'DJs', 'kind' => Category::SERVICE, 'is_active' => true],
        )->id);

        return $e;
    }

    private function addService(Event $e, string $name): void
    {
        $e->categories()->attach(Category::firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name, 'kind' => Category::SERVICE, 'is_active' => true],
        )->id);
    }

    private function labelSeenBy(User $pro): string
    {
        $gigs = collect($this->actingAs($pro)->get(route('professional.bidding-board.index'))
            ->assertOk()->viewData('gigs'));

        $this->assertNotEmpty($gigs, 'the request should be on the board');

        return $gigs->first()['budget'];
    }

    /** The range the client typed, both ends, unchanged. */
    public function test_the_professional_sees_the_range_the_client_entered(): void
    {
        $this->request(['budget' => 2000, 'budget_min' => 2000, 'budget_max' => 3000]);

        $this->assertSame('$2,000 – $3,000', $this->labelSeenBy($this->pro()));
    }

    /** Not the old manufactured one. */
    public function test_the_manufactured_range_is_gone(): void
    {
        $this->request(['budget' => 2000, 'budget_min' => 2000, 'budget_max' => 3000]);

        $this->assertStringNotContainsString('1,700', $this->labelSeenBy($this->pro()));
    }

    /**
     * One end only is stated as a ceiling. Padding it outwards would be the
     * same invention in a smaller coat.
     */
    public function test_a_single_figure_is_stated_as_a_ceiling(): void
    {
        $this->request(['budget' => 1500, 'budget_min' => null, 'budget_max' => null]);

        $this->assertSame('Up to $1,500', $this->labelSeenBy($this->pro()));
    }

    /** No figure at all says so, rather than guessing one. */
    public function test_no_budget_says_open(): void
    {
        $this->request(['budget' => null, 'budget_min' => null, 'budget_max' => null]);

        $this->assertSame('Open budget', $this->labelSeenBy($this->pro()));
    }

    /* ── Rule 4 and Rule 5: only your own service's money ───── */

    /**
     * The sum of three services is not the DJ's budget.
     *
     * One budget covers every service on a multi-service request, and it was
     * printed under the plain word "Budget". A DJ on a DJ + Photographer +
     * Band request read the total as theirs and priced against it. The
     * controller set a `budget_is_whole_request` flag for exactly this, and no
     * view ever read it — the qualifier it promised never reached a screen.
     */
    public function test_a_multi_service_request_does_not_show_its_combined_budget(): void
    {
        $e = $this->request(['budget' => 6000, 'budget_min' => 6000, 'budget_max' => 8000]);
        $this->addService($e, 'Photography');
        $this->addService($e, 'Live Music');

        $seen = $this->labelSeenBy($this->pro());

        $this->assertStringNotContainsString('6,000', $seen);
        $this->assertStringNotContainsString('8,000', $seen);
        $this->assertSame('Set per service', $seen);
    }

    /**
     * And it does not claim the client set none — that figure exists, it is
     * simply not this professional's to see.
     */
    public function test_it_does_not_pretend_the_client_gave_no_budget(): void
    {
        $e = $this->request(['budget' => 6000, 'budget_min' => 6000, 'budget_max' => 8000]);
        $this->addService($e, 'Photography');

        $this->assertNotSame('Open budget', $this->labelSeenBy($this->pro()));
    }

    /** A single-service request is unaffected: that budget IS theirs. */
    public function test_a_single_service_request_still_shows_its_range(): void
    {
        $this->request(['budget' => 2000, 'budget_min' => 2000, 'budget_max' => 3000]);

        $this->assertSame('$2,000 – $3,000', $this->labelSeenBy($this->pro()));
    }
}
