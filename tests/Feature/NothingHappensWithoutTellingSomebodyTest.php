<?php

namespace Tests\Feature;

use App\Models\{Category, Conversation, Event, Message, Package, User};
use App\Notifications\{NewMessageReceived, YouBoughtAPackage, YourRequestIsLive};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sir Peter, 18 October: "Notifications needs to fully functional and getting
 * every notification from whatever the clients do themselves from BR, DR, ER,
 * buying a package, alerts, inbox messages to etc."
 *
 * Most of it was there. Publishing already told the professionals, a proposal
 * already told the client, accepting already told the professional, and the
 * five agreement steps were built on 19 October. What was missing was the three
 * this file covers, and one of them was the worst kind of missing: a message
 * only ever broadcast, so somebody who was not looking at the thread got
 * nothing at all.
 */
class NothingHappensWithoutTellingSomebodyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Mike T.']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update(['state' => 'MD']);
        $this->pro = User::findOrFail($this->pro->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── A message reaches somebody who is not looking ────────

    private function thread(): Conversation
    {
        $c = Conversation::create([
            'type'       => 'direct',
            'created_by' => $this->client->id,
        ]);

        $c->participants()->attach([$this->client->id, $this->pro->id], ['joined_at' => now()]);

        return $c;
    }

    public function test_a_message_notifies_the_other_participant(): void
    {
        Notification::fake();

        $thread = $this->thread();

        $this->actingAs($this->client)
            ->postJson(route('conversations.messages.store', $thread), ['body' => 'Are you free on the 14th?'])
            ->assertCreated();

        Notification::assertSentTo($this->pro, NewMessageReceived::class);
    }

    /** And never the person who wrote it. */
    public function test_the_sender_is_not_notified_of_their_own_message(): void
    {
        Notification::fake();

        $thread = $this->thread();

        $this->actingAs($this->client)
            ->postJson(route('conversations.messages.store', $thread), ['body' => 'Hello']);

        Notification::assertNotSentTo($this->client, NewMessageReceived::class);
    }

    /**
     * A system line is not a second bell.
     *
     * The agreement notices post one into the thread as the written record of
     * a step they have already announced through their own notification.
     */
    public function test_a_system_line_does_not_ring_again(): void
    {
        Notification::fake();

        $thread = $this->thread();

        Message::create([
            'conversation_id' => $thread->id,
            'sender_id'       => $this->client->id,
            'body'            => 'Dana Whitfield approved version 2.',
            'source'          => 'system',
        ]);

        event(new \App\Domain\Messaging\Events\MessageInserted(Message::latest('id')->first()));

        Notification::assertNothingSent();
    }

    /** An attachment with no words is still worth knowing about. */
    public function test_an_attachment_only_message_says_so(): void
    {
        $thread = $this->thread();

        $message = Message::create([
            'conversation_id' => $thread->id,
            'sender_id'       => $this->client->id,
            'body'            => '',
            'source'          => 'user',
        ]);

        $notice = new NewMessageReceived($message);

        $this->assertSame('Sent an attachment.', $notice->toArray($this->pro)['preview']);
    }

    /** Email rides the messages preference, which had nothing using it. */
    public function test_message_email_follows_the_messages_preference(): void
    {
        config(['emails.lifecycle.enabled' => true]);

        $thread = $this->thread();
        $message = Message::create([
            'conversation_id' => $thread->id,
            'sender_id'       => $this->client->id,
            'body'            => 'Hello',
            'source'          => 'user',
        ]);

        $notice = new NewMessageReceived($message);

        $this->pro->profile->update(['notify_email_messages' => true]);
        $this->assertContains('mail', $notice->via($this->pro->fresh()));

        $this->pro->profile->update(['notify_email_messages' => false]);
        $this->assertNotContains('mail', $notice->via($this->pro->fresh()));
    }

    // ── A client hears that their own request went out ───────

    /**
     * The one case where telling somebody what they just did is not noise.
     *
     * Publishing told the professionals and left the client a flash message,
     * which is gone the moment they navigate. Since the posting fee moved to
     * publish there is a charge at that exact moment, and this is the only
     * record of it they can come back to.
     */
    public function test_publishing_tells_the_client_their_request_is_live(): void
    {
        Notification::fake();

        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'pending',
            'starts_at' => now()->addDays(30),
        ]);

        $event->update(['is_published' => true, 'status' => 'published']);

        Notification::assertSentTo($this->client, YourRequestIsLive::class);
    }

    /** And it names the fee that was charged for it. */
    public function test_the_notice_names_the_posting_fee(): void
    {
        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'pending',
            'starts_at' => now()->addDays(30),
        ]);

        $event->update(['is_published' => true, 'status' => 'published']);

        $notice = new YourRequestIsLive($event->fresh(), 2.99);

        $this->assertStringContainsString('$2.99', $notice->sentence());
        $this->assertStringContainsString('posting fee', $notice->sentence());
    }

    /**
     * Once per publication, however many times it is saved afterwards.
     *
     * A bug this file found. The hook asked for "wasRecentlyCreated ||
     * wasChanged('published_at')", and wasRecentlyCreated stays true for the
     * whole life of the instance, so every later save of a freshly created
     * published request looked like a fresh publication. The posting fee
     * survived it, being idempotent; the notification did not, and an ordinary
     * edit sent a second "your request is live".
     */
    public function test_editing_a_published_request_does_not_announce_it_again(): void
    {
        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'is_published' => true, 'starts_at' => now()->addDays(30),
        ]);

        Notification::fake();

        $event->update(['guest_count' => 140]);
        $event->update(['title' => 'Rivera Wedding, renamed']);

        Notification::assertNotSentTo($this->client, YourRequestIsLive::class);
    }

    /** A draft tells nobody, because nothing has gone anywhere. */
    public function test_a_draft_tells_nobody(): void
    {
        Notification::fake();

        Event::create([
            'title' => 'Not ready', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'pending',
            'is_published' => false, 'starts_at' => now()->addDays(30),
        ]);

        Notification::assertNothingSent();
    }

    // ── A client hears what they bought ──────────────────────

    public function test_buying_a_package_tells_the_client_what_they_bought(): void
    {
        Notification::fake();

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $package = Package::create([
            'user_id' => $this->pro->id, 'category_id' => $category->id,
            'title' => 'Reception DJ package', 'slug' => 'dj-pkg-' . uniqid(),
            'price' => 1200, 'state' => 'MD', 'is_active' => true,
        ]);

        $this->actingAs($this->client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Anniversary Party',
            'date'        => now()->addDays(40)->toDateString(),
            'location'    => '18 Henry Street',
            'guests'      => 80,
            'agree'       => 1,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($this->client, YouBoughtAPackage::class);
    }

    /**
     * And it says requested, not booked.
     *
     * A package purchase opens a booking the professional still has to accept.
     * Telling the client it is confirmed would be the platform promising
     * something nobody agreed to.
     */
    public function test_the_package_notice_does_not_claim_it_is_confirmed(): void
    {
        $category = Category::create(['name' => 'Catering', 'slug' => 'cat-' . uniqid()]);

        $package = Package::create([
            'user_id' => $this->pro->id, 'category_id' => $category->id,
            'title' => 'Buffet package', 'slug' => 'buffet-' . uniqid(),
            'price' => 2000, 'state' => 'MD', 'is_active' => true,
        ]);

        $this->actingAs($this->client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Anniversary Party',
            'date'        => now()->addDays(40)->toDateString(),
            'guests'      => 80,
            'agree'       => 1,
        ]);

        $booking = \App\Models\Booking::latest('id')->first();
        $notice  = new YouBoughtAPackage($booking, $package, 4.99);

        $this->assertStringContainsString('not confirmed until they accept', $notice->sentence());
        $this->assertStringNotContainsString('booked', $notice->sentence());
    }
}
