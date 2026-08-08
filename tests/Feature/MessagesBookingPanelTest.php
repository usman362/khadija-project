<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R52, locked 2026-08-05 — Messages and Threads were two front doors to
 * one inbox: the same conversation list and the same message history behind
 * different chrome.
 *
 * The redirect landed earlier; this is the other half of the merge. Threads'
 * booking-context panel — Conversation Info, the contract checkbox, Shared
 * Files, Quick Actions — now sits beside the conversation in Messages, and
 * the one page carries everything both pages used to.
 *
 * The panel is CONDITIONAL, which the old page got wrong. Threads drew it for
 * every conversation and filled each field with an em dash when there was no
 * booking; the game plan is explicit that a conversation with no booking
 * should show "no broken/empty panel".
 */
class MessagesBookingPanelTest extends TestCase
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
        $user->givePermissionTo(['dashboard.view', 'messages.view_any', 'messages.view']);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** A conversation between the two, optionally tied to a booking. */
    private function conversation(bool $withBooking): Conversation
    {
        $booking = null;

        if ($withBooking) {
            $event = Event::create([
                'title'      => 'Harbour Wedding',
                'created_by' => $this->client->id,
                'client_id'  => $this->client->id,
                'status'     => 'confirmed',
                'location'   => 'Baltimore, MD',
                'starts_at'  => now()->addMonth(),
            ]);

            $booking = Booking::create([
                'event_id'    => $event->id,
                'client_id'   => $this->client->id,
                'supplier_id' => $this->pro->id,
                'created_by'  => $this->client->id,
                'status'      => 'confirmed',
                'price'       => 4200,
                'currency'    => 'USD',
            ]);
        }

        $conversation = Conversation::create([
            'type'       => 'direct',
            'booking_id' => $booking?->id,
            'created_by' => $this->client->id,
        ]);
        $conversation->participants()->sync([$this->pro->id, $this->client->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $this->client->id,
            'recipient_id'    => $this->pro->id,
            'body'            => 'Looking forward to it.',
        ]);

        return $conversation->fresh();
    }

    private function open(Conversation $c): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->pro)->get(route('professional.chat.show', $c));
    }

    public function test_a_conversation_with_a_booking_shows_the_panel(): void
    {
        $page = $this->open($this->conversation(withBooking: true));

        $page->assertSuccessful();
        $page->assertSee('Conversation Info');
        $page->assertSee('Harbour Wedding');
        $page->assertSee('Baltimore, MD');
        $page->assertSee('$4,200');
        $page->assertSee('Shared Files');
        $page->assertSee('Quick Actions');
    }

    public function test_a_conversation_without_one_shows_no_panel_at_all(): void
    {
        // Not an empty panel — no panel. Threads drew the whole thing with an
        // em dash in every field, which reads as a page that failed to load.
        $page = $this->open($this->conversation(withBooking: false));

        $page->assertSuccessful();
        $page->assertDontSee('Conversation Info');
        $page->assertDontSee('Quick Actions');
        $this->assertNull($page->viewData('bookingPanel'));
    }

    public function test_the_page_keeps_its_tabs_and_stat_tiles(): void
    {
        // The merge takes Messages as the primary view, so nothing of its own
        // may be lost to make room for the panel.
        $page = $this->open($this->conversation(withBooking: true));

        foreach (['Inbox', 'Sent', 'Drafts', 'Archived'] as $tab) {
            $page->assertSee($tab);
        }
        $this->assertIsArray($page->viewData('stats'));
    }

    public function test_the_contract_checkbox_starts_unticked_and_persists(): void
    {
        // On Threads it was `<input type="checkbox" checked>` — no name, no
        // form, no column. It was ticked on load and recorded nothing, so a
        // professional had every reason to think their agreed points were
        // going into the contract.
        $conversation = $this->conversation(withBooking: true);

        $this->assertFalse($conversation->include_in_contract);

        $this->actingAs($this->pro)
            ->post(route('professional.chat.contract-toggle', $conversation), ['include' => '1'])
            ->assertRedirect();

        $this->assertTrue($conversation->fresh()->include_in_contract);
    }

    public function test_the_checkbox_can_be_turned_back_off(): void
    {
        $conversation = $this->conversation(withBooking: true);
        $conversation->update(['include_in_contract' => true]);

        $this->actingAs($this->pro)
            ->post(route('professional.chat.contract-toggle', $conversation), ['include' => '0']);

        $this->assertFalse($conversation->fresh()->include_in_contract);
    }

    public function test_a_stranger_cannot_toggle_someone_elses_conversation(): void
    {
        $conversation = $this->conversation(withBooking: true);
        $outsider = $this->account('professional');

        $this->actingAs($outsider)
            ->post(route('professional.chat.contract-toggle', $conversation), ['include' => '1'])
            ->assertForbidden();

        $this->assertFalse($conversation->fresh()->include_in_contract);
    }

    public function test_quick_actions_only_offers_what_exists(): void
    {
        // Threads drew four. "Send Payment Link" and "Create Invoice" were
        // buttons with no handler and no feature behind them — the platform
        // cannot issue either, so they did not come across.
        $page = $this->open($this->conversation(withBooking: true));

        $page->assertSee('Share Contract');
        $page->assertSee('Schedule Call');
        $page->assertDontSee('Send Payment Link');
        $page->assertDontSee('Create Invoice');
    }

    public function test_threads_is_gone_rather_than_left_running_alongside(): void
    {
        // One inbox, one page. Leaving the second controller and view in place
        // is how the two drift apart again, which is what R52 was written to
        // stop — and R42 before it, for My Gigs and Contracts.
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Professional/ProfessionalThreadController.php'));
        $this->assertFileDoesNotExist(resource_path('views/professional/threads/index.blade.php'));
    }

    public function test_an_old_threads_link_still_lands_on_the_conversation(): void
    {
        // Bookmarks and internal links must not break. The game plan's QA
        // table asks for the merged page with that conversation open.
        $conversation = $this->conversation(withBooking: true);

        $this->actingAs($this->pro)
            ->get('/professional/threads/' . $conversation->id)
            ->assertRedirect(route('professional.chat.show', $conversation->id));
    }

    public function test_no_conversation_or_message_is_lost_in_the_merge(): void
    {
        // A presentation-layer merge, not a data migration — both pages always
        // read the same records.
        $withBooking = $this->conversation(withBooking: true);
        $without     = $this->conversation(withBooking: false);

        $rows = collect($this->open($withBooking)->viewData('conversations'))->pluck('id');

        $this->assertContains($withBooking->id, $rows->all());
        $this->assertContains($without->id, $rows->all());
        $this->assertCount(1, $this->open($withBooking)->viewData('thread')['messages']);
    }
}
