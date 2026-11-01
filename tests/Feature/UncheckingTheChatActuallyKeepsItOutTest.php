<?php

namespace Tests\Feature;

use App\Models\{Agreement, Booking, Category, Conversation, Event, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A control over private data has to be the thing that decides.
 *
 * Generating an AI agreement offers the client a choice, in the form's own
 * words: "Uncheck to generate a clean, standard agreement from booking
 * details only." Unchecking it did nothing. An unchecked checkbox is not
 * submitted at all, the controller read `boolean('include_chat', true)`, and
 * so the whole client-professional conversation went to OpenAI anyway.
 *
 * The rest of the system was built to honour the answer. The prompt even
 * carries the sentence "The client chose NOT to include the chat transcript",
 * and the agreement page prints a "Booking Only" badge. One line threw the
 * choice away before any of that was reached.
 *
 * Two fixes, on purpose, rather than one covering for the other: the form now
 * submits an explicit 0, and the controller no longer supplies a default. A
 * private conversation leaving the platform to a third party is the wrong
 * thing to do on a guess.
 */
class UncheckingTheChatActuallyKeepsItOutTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // No network from a test, whatever the environment happens to hold.
        Http::preventStrayRequests();
        Http::fake();

        $this->client       = $this->person('client', 'Imogen Hartwell');
        $this->professional = $this->person('professional', 'Hollis Tremaine');

        $event = Event::create([
            'title'       => 'Hartwell Anniversary',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(30),
        ]);

        $this->booking = Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $this->service()->id,
            'status'      => 'confirmed',
            'price'       => 2600,
        ]);

        $conversation = Conversation::create([
            'type'       => 'booking',
            'booking_id' => $this->booking->id,
            'event_id'   => $event->id,
            'created_by' => $this->client->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'booking_id'      => $this->booking->id,
            'sender_id'       => $this->client->id,
            'recipient_id'    => $this->professional->id,
            'body'            => 'We settled on the earlier start time.',
        ]);
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

    private function service(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'event-photography'],
            ['name' => 'Event Photography', 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    private function generate(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)
            ->post(route('app.agreements.generate', $this->booking), $payload);
    }

    public function test_unchecking_the_box_keeps_the_chat_out(): void
    {
        // What the browser sends once the box is cleared: the hidden
        // companion, and no checkbox.
        $this->generate(['include_chat' => '0']);

        $agreement = Agreement::where('booking_id', $this->booking->id)->firstOrFail();

        $this->assertFalse(
            $agreement->include_chat,
            'The client unchecked the box and the conversation was used anyway.'
        );
    }

    public function test_a_request_that_says_nothing_does_not_send_the_conversation(): void
    {
        // The form carries the hidden field, so this should not happen. It is
        // pinned because the cost of guessing wrong is a private conversation
        // sent to a third party, and a guess must fall the quiet way.
        $this->generate([]);

        $agreement = Agreement::where('booking_id', $this->booking->id)->firstOrFail();

        $this->assertFalse($agreement->include_chat);
    }

    public function test_ticking_the_box_still_includes_the_chat(): void
    {
        $this->generate(['include_chat' => '1']);

        $agreement = Agreement::where('booking_id', $this->booking->id)->firstOrFail();

        $this->assertTrue($agreement->include_chat);
    }

    public function test_the_form_sends_an_answer_even_when_the_box_is_cleared(): void
    {
        // The hidden companion is the half of the fix that lives in the page.
        // Without it the server is back to telling "no" from "nothing said".
        $this->actingAs($this->client)
            ->get(route('app.bookings.index'))
            ->assertOk()
            ->assertSee('name="include_chat" value="0"', false);
    }

    public function test_no_conversation_is_needed_when_the_chat_is_not_wanted(): void
    {
        $this->booking->conversation?->messages()->delete();
        $this->booking->conversation?->delete();

        $this->generate(['include_chat' => '0']);

        $this->assertNotNull(Agreement::where('booking_id', $this->booking->id)->first());
    }

    public function test_a_chat_aware_agreement_still_asks_for_a_conversation_first(): void
    {
        $this->booking->conversation?->messages()->delete();
        $this->booking->conversation?->delete();

        $this->generate(['include_chat' => '1'])->assertSessionHas('error');

        $this->assertNull(Agreement::where('booking_id', $this->booking->id)->first());
    }
}
