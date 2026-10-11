<?php

namespace Tests\Feature;

use App\Models\{Bid, Conversation, Event, Finalization, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The conversation folds away so the terms can be read.
 *
 * The agreement is the one screen a client goes through line by line before
 * signing, and the chat beside it was taking a third of the width. Sir Peter
 * asked for a way to put it aside: "would be a good idea or useful for the
 * agreement to have on it."
 *
 * Two things had to come with it, or folding would cost more than it saved.
 * The choice is remembered, so nobody folds it on every visit. And while it is
 * folded it still says when something new has been said, because the chat is
 * exactly where the changes to the agreement get argued out.
 */
class TheAgreementCanHaveTheWidthTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Finalization $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $pro->assignRole('professional');

        $event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
            'city'       => 'Baltimore',
            'state'      => 'MD',
        ]);

        $bid = Bid::create([
            'event_id'    => $event->id,
            'supplier_id' => $pro->id,
            'amount'      => 1800,
            'status'      => 'submitted',
        ]);

        $this->fin = Finalization::create([
            'event_id'    => $event->id,
            'bid_id'      => $bid->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $pro->id,
        ]);

        // A conversation the two of them share, so the panel has something in
        // it and the newest-message mark has an id to work from.
        $conversation = Conversation::create([
            'subject'    => 'About the wedding',
            'created_by' => $this->client->id,
        ]);
        $conversation->participants()->attach([$this->client->id, $pro->id]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $pro->id,
            'body'            => 'Happy to move the start time if that helps.',
        ]);
    }

    private function page(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.finalize.overview', $this->fin))
            ->assertSuccessful()
            ->getContent();
    }

    /** There is a control, and it says what it does. */
    public function test_the_conversation_can_be_folded_away(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('class="ws-fold" data-ws-fold', $html);
        $this->assertStringContainsString('aria-label="Hide the conversation"', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
    }

    /**
     * Folded, the agreement takes the width back.
     *
     * Measured in the browser on a 1600px screen: the terms went from 524px to
     * 798px. The rule that does it is the only thing a test can hold.
     */
    public function test_folding_gives_the_terms_the_room(): void
    {
        $html = $this->page();

        $this->assertStringContainsString(
            '.ws.is-folded { grid-template-columns: 210px minmax(0, 1fr) 210px 46px; }',
            $html,
            'Folded, the conversation column should shrink to a rail.',
        );
        $this->assertStringContainsString('.ws.is-folded .ws-msgs-b, .ws.is-folded .ws-msgs-f { display: none; }', $html);
    }

    /**
     * It is a rail, not a disappearance.
     *
     * Hiding the column outright would take the way back with it, and the
     * unread mark along with that.
     */
    public function test_folded_it_is_still_on_the_page(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('<span class="ws-rail">Messages</span>', $html);
        $this->assertStringContainsString('.ws.is-folded .ws-rail { display: inline-block; }', $html);
    }

    /** The choice survives the next visit. */
    public function test_the_choice_is_remembered(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('gr.agreement.msgs.folded', $html);
        $this->assertStringContainsString("apply(get(FOLD) === '1', false);", $html);
    }

    /**
     * Folded away, it still says when something has been said.
     *
     * Without this the client folds the chat, the professional proposes a
     * change in it, and nothing on the page ever mentions it again.
     */
    public function test_it_still_shows_that_something_new_arrived(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('class="ws-newdot" data-ws-newdot', $html);
        $this->assertStringContainsString('gr.agreement.msgs.seen', $html);
        $this->assertStringContainsString('dot.classList.toggle(\'is-on\', newest > seen);', $html);
    }

    /** Storage that throws must not take the panel down with it. */
    public function test_blocked_storage_is_survivable(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('try { return window.localStorage.getItem(k); } catch (e) { return null; }', $html);
        $this->assertStringContainsString('try { window.localStorage.setItem(k, v); } catch (e) {}', $html);
    }

    /**
     * Stacked, there is nothing to win, so the control is not offered.
     *
     * Below 1100px the conversation sits under the terms rather than beside
     * them, so folding it would buy no width at all and only hide the chat.
     */
    public function test_the_fold_is_not_offered_where_it_buys_nothing(): void
    {
        $html = $this->page();

        // Anchored on this page's own rule, not the first 1100px media query
        // on the page: the layout has its own, and slicing from that would
        // have measured somebody else's stylesheet.
        $at = strpos($html, '.ws, .ws.is-folded { grid-template-columns: 1fr; }');
        $this->assertNotFalse($at, 'The stacked layout rule has moved.');
        $narrow = substr($html, $at, 600);

        $this->assertStringContainsString('.ws-fold { display: none; }', $narrow);
        $this->assertStringContainsString('.ws.is-folded .ws-msgs-b, .ws.is-folded .ws-msgs-f { display: flex; }', $narrow);
    }
}
