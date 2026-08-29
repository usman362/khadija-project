<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Notifications\BookingCompleted;
use App\Notifications\ProposalCancelled;
use App\Notifications\ProposalReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The three things that happen to a client's event now reach them by email as
 * well as in the app: a proposal arrives, a professional withdraws, the work is
 * marked complete.
 *
 * The point of care here is the preferences. notify_email_bookings and friends
 * have been editable in profile settings since the beginning and NOTHING ever
 * read them, because nothing sent email. Switching email on without consulting
 * them would mail everybody who had already opted out.
 */
class LifecycleEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(array $prefs = []): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update($prefs);

        return $user->fresh();
    }

    private function booking(User $client): Booking
    {
        $pro = User::factory()->create(['name' => 'Thessaly Strings']);
        $event = Event::create([
            'title' => 'Rooftop Anniversary', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'published', 'starts_at' => now()->addDays(20),
        ]);

        return Booking::create([
            'client_id' => $client->id, 'created_by' => $client->id,
            'supplier_id' => $pro->id, 'event_id' => $event->id,
            'status' => 'pending', 'price' => 900,
        ]);
    }

    public function test_a_new_proposal_reaches_the_client_by_email(): void
    {
        Notification::fake();
        $client = $this->client();

        $client->notify(new ProposalReceived($this->booking($client)));

        Notification::assertSentTo($client, ProposalReceived::class, function ($n, $channels) {
            return in_array('mail', $channels, true) && in_array('database', $channels, true);
        });
    }

    public function test_a_client_who_turned_event_email_off_is_not_emailed(): void
    {
        Notification::fake();
        $client = $this->client(['notify_email_events' => false]);

        $client->notify(new ProposalReceived($this->booking($client)));

        // Still in the app — silencing email does not silence the app.
        Notification::assertSentTo($client, ProposalReceived::class, function ($n, $channels) {
            return ! in_array('mail', $channels, true) && in_array('database', $channels, true);
        });
    }

    public function test_a_client_who_turned_booking_email_off_is_not_emailed(): void
    {
        Notification::fake();
        $client = $this->client(['notify_email_bookings' => false]);
        $booking = $this->booking($client);

        $client->notify(new BookingCompleted($booking));
        $client->notify(new ProposalCancelled($booking, $booking->supplier));

        foreach ([BookingCompleted::class, ProposalCancelled::class] as $class) {
            Notification::assertSentTo($client, $class, function ($n, $channels) {
                return ! in_array('mail', $channels, true);
            });
        }
    }

    public function test_the_master_switch_stops_every_lifecycle_email(): void
    {
        config(['emails.lifecycle.enabled' => false]);
        Notification::fake();
        $client = $this->client();

        $client->notify(new ProposalReceived($this->booking($client)));

        Notification::assertSentTo($client, ProposalReceived::class, function ($n, $channels) {
            return ! in_array('mail', $channels, true);
        });
    }

    /** Marketing is the one nobody is opted into by never visiting settings. */
    public function test_marketing_is_off_unless_it_was_switched_on(): void
    {
        $client = $this->client();

        $this->assertFalse($client->acceptsEmail('marketing'));
        $this->assertTrue($client->acceptsEmail('bookings'));
    }

    /** Every lifecycle template must render — a mail that throws is a mail nobody gets. */
    public function test_the_lifecycle_templates_render(): void
    {
        $client = $this->client();
        $booking = $this->booking($client);

        $cases = [
            new ProposalReceived($booking),
            new BookingCompleted($booking),
            new ProposalCancelled($booking, $booking->supplier),
        ];

        foreach ($cases as $notification) {
            $html = $notification->toMail($client)->render();

            $this->assertStringContainsString($client->name, $html);
            $this->assertStringContainsString('Rooftop Anniversary', $html);
        }
    }
}
