<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-02, marked urgent:
 *
 *   "in the BR, DR and ER, they again should allow the users to break down the
 *    services (and budget) if there are more than one service needed."
 *
 * The Bidding Request wizard learned this on 2026-08-30. Direct Request and
 * Emergency Request did not, so the same client asking for the same five
 * services got a breakdown on one screen and a single figure on the other two.
 *
 * The rule is not cosmetic. Professionals quote on ONE service, so a $10,000
 * total across five services showed five different professionals $10,000 each
 * and had every one of them price against a number that was never theirs.
 *
 * All three now save through ServiceBudgetWriter, so what is asserted here is
 * that each route reaches it and that the rules it enforces hold whichever
 * door the request came through.
 */
class ServiceBudgetEverywhereTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    private function service(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => Category::SERVICE,
            'is_active' => true,
        ]);
    }

    private function event(): Event
    {
        return Event::create([
            'title' => 'Multi-service request',
            'client_id' => $this->client->id,
            'created_by' => $this->client->id,
            'status' => 'open',
        ]);
    }

    /* ── The rule itself ────────────────────────────────────── */

    public function test_a_multi_service_request_keeps_a_figure_per_service(): void
    {
        $event = $this->event();
        $photo = $this->service('Photography');
        $cater = $this->service('Catering');

        \App\Domain\Budget\ServiceBudgetWriter::save(
            $event,
            [$photo->id => 100, $cater->id => 325],
            [$photo->id, $cater->id],
        );

        $this->assertSame(
            [$photo->id => 100.0, $cater->id => 325.0],
            $event->serviceBudgets()->pluck('amount', 'category_id')->map(fn ($a) => (float) $a)->all(),
        );
    }

    /** One service has one budget. There is nothing to divide. */
    public function test_a_single_service_request_stores_no_breakdown(): void
    {
        $event = $this->event();
        $photo = $this->service('Photography');

        \App\Domain\Budget\ServiceBudgetWriter::save($event, [$photo->id => 100], [$photo->id]);

        $this->assertSame(0, $event->serviceBudgets()->count());
    }

    /**
     * A figure may only attach to a service actually being requested. Without
     * this, a field left behind in the posted form puts money against a service
     * the client removed two steps earlier.
     */
    public function test_money_cannot_attach_to_a_service_not_being_requested(): void
    {
        $event = $this->event();
        $photo = $this->service('Photography');
        $cater = $this->service('Catering');
        $other = $this->service('Lighting');

        \App\Domain\Budget\ServiceBudgetWriter::save(
            $event,
            [$photo->id => 100, $cater->id => 325, $other->id => 900],
            [$photo->id, $cater->id],
        );

        $this->assertSame(
            [$photo->id, $cater->id],
            $event->serviceBudgets()->pluck('category_id')->sort()->values()->all(),
        );
    }

    /** Saving again replaces the split rather than adding a second one. */
    public function test_saving_again_replaces_the_previous_split(): void
    {
        $event = $this->event();
        $photo = $this->service('Photography');
        $cater = $this->service('Catering');

        $ids = [$photo->id, $cater->id];

        \App\Domain\Budget\ServiceBudgetWriter::save($event, [$photo->id => 100, $cater->id => 325], $ids);
        \App\Domain\Budget\ServiceBudgetWriter::save($event, [$photo->id => 250, $cater->id => 250], $ids);

        $this->assertSame(2, $event->serviceBudgets()->count());
        $this->assertEqualsWithDelta(250.0, (float) $event->serviceBudgets()->first()->amount, 0.01);
    }

    /** A blank box means "I would rather not say", not zero. */
    public function test_a_blank_amount_is_not_stored_as_zero(): void
    {
        $event = $this->event();
        $photo = $this->service('Photography');
        $cater = $this->service('Catering');

        \App\Domain\Budget\ServiceBudgetWriter::save(
            $event,
            [$photo->id => 100, $cater->id => ''],
            [$photo->id, $cater->id],
        );

        $this->assertSame([$photo->id], $event->serviceBudgets()->pluck('category_id')->all());
    }

    /* ── Which services get a row ───────────────────────────── */

    public function test_the_breakdown_is_offered_only_when_there_is_something_to_divide(): void
    {
        $photo = $this->service('Photography');
        $cater = $this->service('Catering');

        $this->assertCount(0, \App\Domain\Budget\ServiceBudgetWriter::splittableServices([$photo->id]));
        $this->assertCount(2, \App\Domain\Budget\ServiceBudgetWriter::splittableServices([$photo->id, $cater->id]));

        // A service named twice is still one service.
        $this->assertCount(0, \App\Domain\Budget\ServiceBudgetWriter::splittableServices([$photo->id, $photo->id]));
    }

    /* ── All three doors reach it ───────────────────────────── */

    /**
     * The screens themselves. Sir Peter's report was that he could not see the
     * breakdown, so the check is that each page carries the field — not merely
     * that the writer works when called.
     */
    public function test_all_three_request_screens_carry_the_breakdown(): void
    {
        $this->service('Photography');
        $this->service('Catering');

        // BR is a wizard: /client/bsr is the service step, and the breakdown
        // belongs on the budget step, where the chosen services are known.
        $pages = [
            'ER' => '/client/esr/create',
            'DR' => '/client/direct-offers/create',
            'BR' => '/client/bsr/budget',
        ];

        // Two services chosen, in all three. The breakdown is conditional by
        // design — it appears when there is something to divide — so a check
        // run with none chosen would pass on a page that never offers it.
        $chosen = Category::pluck('id')->take(2)->all();

        // Enough wizard state to be allowed onto the budget step — it refuses
        // to jump ahead of what has been filled in, which is right, and means
        // a bare session lands on step 1 and proves nothing.
        $wizard = [
            'services' => $chosen,
            'organization_type' => 'individual',
            'title' => 'Multi-service request',
            'description' => 'Several services needed.',
        ];

        $missing = [];

        foreach ($pages as $label => $path) {
            $html = $this->actingAs($this->client)
                ->withSession(['bsr_wizard' => $wizard])
                ->get($path)
                ->getContent();

            if (! str_contains($html, 'service_budgets') && ! str_contains($html, 'data-sbs')) {
                $missing[] = "{$label} ({$path})";
            }
        }

        $this->assertSame([], $missing, 'no per-service budget on: '.implode(', ', $missing));
    }
}
