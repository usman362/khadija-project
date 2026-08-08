<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\Commission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R61, ratified 2026-08-07, in answer to "shouldn't Direct Offers only
 * be shown to Elite members?"
 *
 * No. A Direct Offer is addressed to ONE professional the client chose by
 * name — it is never broadcast, so there is nobody to hide it from except
 * its own recipient. Gating it by membership would mean a client picks a
 * professional and the platform quietly withholds the request.
 *
 * Elite is still worth buying; the offer just makes the argument with money
 * instead of a locked door. Every offer carries the payout ladder — this
 * offer's own amount at the three published commission rates — with the
 * viewer's own row marked.
 */
class DirectOfferHasNoTierGateTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('professional');
        $user->givePermissionTo(['dashboard.view', 'bookings.view_any', 'bookings.update']);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function offerTo(User $pro, float $budget = 10000): Event
    {
        $client = User::factory()->create();

        return Event::create([
            'title'        => 'Anniversary Dinner',
            'created_by'   => $client->id,
            'client_id'    => $client->id,
            'source'       => 'direct_offer',
            'supplier_id'  => $pro->id,
            'status'       => 'pending',
            'is_published' => false,
            'budget'       => $budget,
            'starts_at'    => now()->addMonth(),
        ]);
    }

    public function test_a_professional_on_no_paid_plan_still_receives_the_offer(): void
    {
        // The Starter case is the whole question: if any gate existed, this
        // is the professional it would have shut out.
        $pro = $this->pro();
        $offer = $this->offerTo($pro);

        $this->actingAs($pro)
            ->get(route('professional.direct-offers.show', $offer->id))
            ->assertSuccessful()
            ->assertSee($offer->title);
    }

    public function test_a_starter_professional_can_accept_it(): void
    {
        // Seeing it but being unable to act on it would be the same gate
        // wearing a different coat.
        $pro = $this->pro();
        $offer = $this->offerTo($pro);

        $this->actingAs($pro)
            ->post(route('professional.direct-offers.accept', $offer->id))
            ->assertRedirect();

        $this->assertSame('confirmed', $offer->fresh()->status);
    }

    public function test_an_offer_is_private_to_the_professional_it_was_sent_to(): void
    {
        // The real access rule, and the reason a membership gate is the wrong
        // tool: the audience for a Direct Offer is already exactly one person.
        $pro = $this->pro();
        $other = $this->pro();
        $offer = $this->offerTo($pro);

        $this->actingAs($other)
            ->post(route('professional.direct-offers.accept', $offer->id))
            ->assertForbidden();
    }

    public function test_the_ladder_shows_all_three_memberships_on_the_real_amount(): void
    {
        $ladder = Commission::ladderFor(10000);

        $this->assertSame(['Starter', 'Pro', 'Elite'], array_column($ladder, 'label'));
        // 5% / 3% / 1.5% of the published rate card, nothing estimated.
        $this->assertSame([9500.0, 9700.0, 9850.0], array_column($ladder, 'net'));
    }

    public function test_the_ladder_marks_the_row_the_viewer_is_on(): void
    {
        // A professional with no subscription is on Starter terms, and the
        // ladder should say which row is theirs rather than highlight none.
        $current = collect(Commission::ladderFor(10000, $this->pro()))
            ->where('current', true)->pluck('slug')->all();

        $this->assertSame(['starter'], $current);
    }

    public function test_the_offer_page_renders_the_ladder(): void
    {
        $pro = $this->pro();
        $offer = $this->offerTo($pro, 10000);

        $page = $this->actingAs($pro)->get(route('professional.direct-offers.show', $offer->id));

        $page->assertSuccessful();
        $page->assertSee('Your payout on this offer');
        $page->assertSee('$9,850');            // what Elite would keep
        $page->assertSee('after 1.5% commission');
    }
}
