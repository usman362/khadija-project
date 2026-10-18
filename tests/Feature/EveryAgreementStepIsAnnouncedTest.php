<?php

namespace Tests\Feature;

use App\Domain\Agreements\{AgreementNotices, EditAgreement, Workspace};
use App\Models\{Bid, Category, Conversation, Event, Finalization, Message, User};
use App\Notifications\AgreementStepHappened;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sir Peter, 17 October: "Each step or update of this process we need to add it
 * to the notifications, message (inbox), both exterior email addresses,
 * transactions, and wherever else too."
 *
 * Three of those are built and tested here. Transactions are not: a signature is
 * not a money event, and what belongs in the money record is settled by the
 * ledger. That question is still with him, so nothing guesses at it.
 */
class EveryAgreementStepIsAnnouncedTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Finalization $fin;
    private Event $event;

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
        $this->pro->getOrCreateProfile();

        $this->event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30), 'city' => 'Baltimore', 'state' => 'MD',
        ]);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $bid = Bid::create([
            'event_id' => $this->event->id, 'supplier_id' => $this->pro->id,
            'category_id' => $category->id, 'amount' => 2000, 'status' => 'submitted',
        ]);

        $this->fin = Finalization::create([
            'event_id' => $this->event->id, 'bid_id' => $bid->id,
            'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'agreed_price' => 2000, 'scope' => 'DJ for the reception',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function thread(): Conversation
    {
        $c = Conversation::create([
            'type'       => 'direct',
            'event_id'   => $this->event->id,
            'created_by' => $this->client->id,
        ]);

        $c->participants()->attach([$this->client->id, $this->pro->id], ['joined_at' => now()]);

        return $c;
    }

    // ── The notification ─────────────────────────────────────

    public function test_sending_changes_notifies_the_other_side(): void
    {
        Notification::fake();

        $changed = EditAgreement::submit($this->fin, $this->client, ['agreed_price' => 2400]);
        AgreementNotices::changesSent($this->fin->fresh(), $this->client, $changed);

        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
    }

    /**
     * And never the person who did it.
     *
     * Telling somebody what they themselves just did is how a notification list
     * becomes something people learn to ignore.
     */
    public function test_the_actor_is_not_told_what_they_just_did(): void
    {
        Notification::fake();

        $changed = EditAgreement::submit($this->fin, $this->client, ['agreed_price' => 2400]);
        AgreementNotices::changesSent($this->fin->fresh(), $this->client, $changed);

        Notification::assertNotSentTo($this->client, AgreementStepHappened::class);
    }

    /** Funding is the exception: it ends the negotiation, so both hear it. */
    public function test_funding_is_announced_to_both(): void
    {
        Notification::fake();

        AgreementNotices::funded($this->fin);

        Notification::assertSentTo($this->client, AgreementStepHappened::class);
        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
    }

    // ── What it says ─────────────────────────────────────────

    /**
     * A step always names the version it happened on.
     *
     * A signature here has always been a signature OF A VERSION, so "they
     * signed" without saying which terms is the one thing that must never be
     * said.
     */
    public function test_a_step_names_the_version_it_happened_on(): void
    {
        $notice = new AgreementStepHappened($this->fin, AgreementStepHappened::SIGNED, 'Mike T.', 3);

        $this->assertStringContainsString('version 3', $notice->sentence());
        $this->assertSame(3, $notice->toArray($this->client)['version']);
    }

    /** Changed fields are named, not counted. */
    public function test_the_changed_fields_are_named(): void
    {
        Notification::fake();

        $changed = EditAgreement::submit($this->fin, $this->client, [
            'agreed_price' => 2400,
            'scope'        => 'DJ and lighting',
        ]);

        AgreementNotices::changesSent($this->fin->fresh(), $this->client, $changed);

        Notification::assertSentTo($this->pro, AgreementStepHappened::class, function ($notice) {
            $body = $notice->sentence();

            return str_contains($body, 'Agreed price') && str_contains($body, 'Scope and deliverables');
        });
    }

    /** And the one that matters most: a change unseats what was signed. */
    public function test_sending_changes_says_the_signatures_no_longer_stand(): void
    {
        $notice = new AgreementStepHappened(
            $this->fin,
            AgreementStepHappened::CHANGES_SENT,
            'Dana Whitfield',
            2,
        );

        $this->assertStringContainsString('no longer stand', $notice->sentence());
    }

    // ── The inbox copy ───────────────────────────────────────

    /** It belongs in the thread they already share, not a list of its own. */
    public function test_the_step_appears_in_the_thread_they_share(): void
    {
        $thread = $this->thread();

        AgreementNotices::approved($this->fin, $this->client);

        $posted = Message::where('conversation_id', $thread->id)->latest('id')->first();

        $this->assertNotNull($posted, 'Nothing was posted to the inbox.');
        $this->assertSame('system', $posted->source, 'It is an account of what happened, not something somebody typed.');
        $this->assertStringContainsString('approved version', $posted->body);
    }

    /**
     * With no thread, the step is still notified rather than lost.
     *
     * A conversation is deliberately not invented for it: an empty thread with
     * one system line in it reads as a message nobody sent.
     */
    public function test_a_missing_thread_does_not_lose_the_step(): void
    {
        Notification::fake();

        AgreementNotices::approved($this->fin, $this->client);

        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
        $this->assertSame(0, Message::count());
    }

    // ── The email ────────────────────────────────────────────

    /** Email rides the bookings preference: this is money, not event news. */
    public function test_email_goes_out_when_the_account_wants_bookings_mail(): void
    {
        config(['emails.lifecycle.enabled' => true]);
        $this->pro->profile->update(['notify_email_bookings' => true]);

        $notice = new AgreementStepHappened($this->fin, AgreementStepHappened::SIGNED, 'Dana Whitfield', 1);

        $this->assertContains('mail', $notice->via($this->pro->fresh()));
    }

    /** And is withheld when they have turned it off. */
    public function test_email_is_withheld_when_the_account_opted_out(): void
    {
        config(['emails.lifecycle.enabled' => true]);
        $this->pro->profile->update(['notify_email_bookings' => false]);

        $notice = new AgreementStepHappened($this->fin, AgreementStepHappened::SIGNED, 'Dana Whitfield', 1);

        $via = $notice->via($this->pro->fresh());

        $this->assertNotContains('mail', $via);
        $this->assertContains('database', $via, 'In-app always, whatever they chose about email.');
    }

    // ── Through the real actions ─────────────────────────────

    /** The controller announces it, not just the domain class. */
    public function test_the_approve_action_announces_itself(): void
    {
        Notification::fake();

        $this->fin->forceFill(['supplier_approved_version' => 1])->save();

        $this->actingAs($this->client)
            ->post(route('client.finalize.approve', $this->fin))
            ->assertRedirect();

        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
    }

    public function test_the_edit_action_announces_itself(): void
    {
        Notification::fake();

        $this->actingAs($this->client)
            ->post(route('client.finalize.edit', $this->fin), [
                'intent' => 'send',
                'terms'  => ['agreed_price' => 2400],
            ])
            ->assertRedirect();

        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
    }

    /**
     * Decline too, which was unreachable for the same reason.
     *
     * Both of these sat BELOW the /finalize/{finalization}/{step} wildcard, so
     * a POST to /approve matched {step} = "approve" and save() 404d on a step
     * name it did not recognise. Nothing had ever exercised them end to end,
     * so two of the Agreement Workspace's three negotiation controls had never
     * worked. Found by this file, fixed by moving them above the wildcard.
     */
    public function test_the_decline_action_announces_itself(): void
    {
        Notification::fake();

        $this->actingAs($this->client)
            ->post(route('client.finalize.decline', $this->fin))
            ->assertRedirect();

        Notification::assertSentTo($this->pro, AgreementStepHappened::class);
        $this->assertNotNull($this->fin->fresh()->declined_at, 'Declining must actually decline it.');
    }

    /** A draft tells nobody, because nothing has happened to them (rule 7). */
    public function test_saving_a_draft_announces_nothing(): void
    {
        Notification::fake();

        $this->actingAs($this->client)
            ->post(route('client.finalize.edit', $this->fin), [
                'intent' => 'draft',
                'terms'  => ['agreed_price' => 9999],
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }
}
