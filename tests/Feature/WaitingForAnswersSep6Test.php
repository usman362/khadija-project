<?php

namespace Tests\Feature;

use App\Domain\Disputes\DisputeClassification;
use App\Models\Booking;
use App\Models\CancellationRequest;
use App\Models\DisputeCase;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Items from Sir Peter's Waiting for Answers list of 6 September, and his
 * written answers of 5 September.
 */
class WaitingForAnswersSep6Test extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** OA-137: the Terms have a page, and the footer links to it. */
    public function test_the_terms_of_service_page_exists_and_is_linked(): void
    {
        $this->get(route('terms-of-service'))->assertOk()->assertSee('Terms of Service');

        foreach (['layouts/landing.blade.php', 'partials/footer.blade.php'] as $file) {
            $this->assertStringContainsString(
                "route('terms-of-service')",
                file_get_contents(resource_path("views/{$file}")),
                "{$file} has no Terms of Service link",
            );
        }
    }

    /** OA-141: one account cannot be both sides of a booking. */
    public function test_a_client_cannot_be_their_own_professional(): void
    {
        $user = $this->account('client');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        (new Booking(['client_id' => $user->id, 'supplier_id' => $user->id, 'status' => 'requested']))->save();
    }

    /** OA-148: Sir Peter's PM-4 list, word for word, in his order. */
    public function test_dispute_types_are_the_pm4_list(): void
    {
        $this->assertSame(
            ['No-show', 'Service not as described', 'Property damage', 'Late arrival/departure', 'Payment discrepancy', 'Other'],
            array_values(DisputeClassification::FILING_TYPES),
        );

        foreach (array_keys(DisputeClassification::FILING_TYPES) as $key) {
            $this->assertArrayHasKey($key, DisputeClassification::TAXONOMY, "{$key} has no staff classification");
        }

        $tiles = array_column(\App\Http\Controllers\Disputes\DisputeController::COMMON_ISSUES, 1);
        $this->assertSame(array_values(DisputeClassification::FILING_TYPES), $tiles);
        $this->assertNotContains('Cancellation', $tiles, 'Cancellation is not a dispute type');
    }

    /** D-9: disputes open up to 14 days after the event, and not after. */
    public function test_a_dispute_cannot_be_opened_long_after_the_event(): void
    {
        $client = $this->account('client');
        $pro    = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $booking = function (int $daysAgo) use ($client, $pro) {
            $event = Event::create([
                'title' => "Party {$daysAgo}", 'client_id' => $client->id, 'created_by' => $client->id,
                'status' => 'published', 'starts_at' => now()->subDays($daysAgo),
            ]);

            return Booking::create([
                'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
                'created_by' => $client->id, 'status' => 'completed', 'price' => 900,
            ]);
        };

        $post = fn (Booking $b) => $this->actingAs($client)->post(route('disputes.store'), [
            'booking_id' => $b->id, 'taxonomy' => 'no_show',
            'summary' => 'The professional never arrived and did not answer any messages.',
            'attempted_direct' => 'yes', 'certify_truthful' => '1',
        ]);

        $post($booking(20))->assertSessionHasErrors('booking_id');
        $this->assertSame(0, DisputeCase::count());

        $post($booking(10))->assertSessionHasNoErrors();
        $this->assertSame(1, DisputeCase::count());
    }

    /** D-3: the $2.99 is non-refundable, and the fee terms say so. */
    public function test_the_fee_terms_say_the_fee_is_non_refundable(): void
    {
        $html = view('client.partials._request_fee_terms', ['action' => 'posting this request'])->render();

        $this->assertStringContainsString('non-refundable', $html);
    }

    /** DIR-15: the Cancellations page offers both kinds of cancellation. */
    public function test_cancellations_offers_a_booking_and_an_event(): void
    {
        $client = $this->account('client');

        $this->actingAs($client)->get(route('cancellations.index'))
            ->assertOk()
            ->assertSee('Cancel a booking')
            ->assertSee('Cancel an event')
            ->assertSee(route('cancellations.create', ['kind' => CancellationRequest::CLIENT_CANCELS_EVENT]), false);
    }

    /**
     * Sir Peter, Sep 11: "Service Needs, but what is the input?" With no
     * service or professional chosen yet, the section must say what to do,
     * not sit empty under "YOUR INPUT".
     */
    public function test_direct_request_service_needs_is_never_empty(): void
    {
        $client = $this->account('client');

        $this->actingAs($client)->get(route('client.direct-offers.create'))
            ->assertOk()
            ->assertSee('Choose the service you need at the top of this page.');
    }

    /** D-10: no emojis in the guided event planner. */
    public function test_the_event_planner_has_no_emojis(): void
    {
        $source = file_get_contents(resource_path('views/ai-tools/event-planner.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $source);
    }
}
