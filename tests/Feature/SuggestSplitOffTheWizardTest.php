<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Suggest a split" works from the pages that show the button.
 *
 * Emergency Request and Direct Request both render the shared budget-split
 * partial and both point it at the BSR endpoint — but that endpoint read the
 * services out of the BSR wizard's own session. Off the wizard there is no
 * session, so it answered "a split needs at least two services" on a page
 * showing two, and the partial reported "Suggested" over the refusal and left
 * every box empty.
 */
class SuggestSplitOffTheWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    /** @var array<int, int> */
    private array $services = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $this->client = $this->client->fresh();

        foreach (['Awards & Trophy Design', 'BBQ & Grill Catering'] as $i => $name) {
            $this->services[] = Category::create([
                'name' => $name, 'slug' => 'svc-sp-'.$i,
                'kind' => Category::SERVICE, 'is_active' => true,
            ])->id;
        }
    }

    public function test_it_splits_what_the_page_sent_rather_than_a_wizard_session(): void
    {
        $res = $this->actingAs($this->client)
            ->postJson(route('client.bsr.suggest-split'), [
                'total' => 20000,
                'services' => $this->services,
            ])
            ->assertSuccessful();

        $res->assertJsonPath('ok', true);

        $split = $res->json('split');

        $this->assertCount(2, $split, 'Both services should get a figure.');
        $this->assertEqualsWithDelta(20000, array_sum($split), 1,
            'The split should account for the whole budget.');

        foreach ($this->services as $id) {
            $this->assertArrayHasKey($id, $split);
            $this->assertGreaterThan(0, $split[$id]);
        }
    }

    /** One service has nothing to divide, and the caller is told which it is. */
    public function test_one_service_is_refused_with_a_reason(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('client.bsr.suggest-split'), [
                'total' => 20000,
                'services' => [$this->services[0]],
            ])
            ->assertSuccessful()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'A split needs at least two services.');
    }

    /** No budget is a different problem, and says so. */
    public function test_no_budget_says_so(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('client.bsr.suggest-split'), [
                'total' => 0,
                'services' => $this->services,
            ])
            ->assertSuccessful()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Add a budget above first, then we can suggest a split.');
    }

    /** The partial acts on the refusal instead of reporting success over it. */
    public function test_the_page_reports_a_refusal(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.esr.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('data.ok === false', $html,
            'The page shows "Suggested" whatever the endpoint answered.');
    }
}
