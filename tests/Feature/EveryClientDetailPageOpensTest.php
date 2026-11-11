<?php

namespace Tests\Feature;

use App\Models\{Bid, Booking, Category, Conversation, Event, Finalization, Package, RequestAttachment, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The pages ClientSideSmokeTest could not reach.
 *
 * That test walks every client route and checks none of them throws, and says
 * in its own header why it skips the ones taking parameters: "fabricating an
 * id per route tests my fixtures rather than the pages."
 *
 * Which left the detail pages untested as a group — the event, the agreement,
 * the transaction, the conversation, the comparison. Those are the pages that
 * load the most, join the most and are likeliest to throw, and they are where
 * this month's ledger work landed. The answer to the objection is one fixture
 * for all of them rather than an id per route: a single client with a real
 * event, bid, agreement, booking, conversation, package and attachment, and
 * every route handed the row it is actually about.
 *
 * Same deliberately weak assertion: not a 5xx. A redirect or a 403 can be the
 * honest answer; a thrown page never is.
 */
class EveryClientDetailPageOpensTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private array $rows = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $pro = User::findOrFail($pro->id);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        // Unpublished, because client.bsr.resume refuses a published one: it
        // reopens a draft, and a draft is what it has to be handed.
        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'pending',
            'is_published' => false,
            'starts_at' => now()->addDays(30), 'city' => 'Baltimore', 'state' => 'MD',
        ]);

        $bid = Bid::create([
            'event_id' => $event->id, 'supplier_id' => $pro->id,
            'category_id' => $category->id, 'amount' => 2000, 'status' => 'submitted',
        ]);

        $finalization = Finalization::create([
            'event_id' => $event->id, 'bid_id' => $bid->id,
            'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'agreed_price' => 2000, 'deposit_percent' => 25, 'deposit_amount' => 500,
            'scope' => 'DJ for the reception',
        ]);

        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $pro->id, 'created_by' => $this->client->id,
            'category_id' => $category->id, 'status' => 'confirmed',
            'price' => 2000, 'currency' => 'USD',
        ]);

        $conversation = Conversation::create([
            'type' => 'direct', 'event_id' => $event->id,
            'booking_id' => $booking->id, 'created_by' => $this->client->id,
        ]);
        $conversation->participants()->attach([$this->client->id, $pro->id], ['joined_at' => now()]);

        $package = Package::create([
            'user_id' => $pro->id, 'category_id' => $category->id,
            'title' => 'Full day coverage', 'slug' => 'full-day-' . uniqid(),
            'type' => 'solo', 'price' => 2400,
            'services' => ['Photography', 'Videography'],
            'event_types' => ['Wedding'], 'state' => 'MD',
            'is_active' => true, 'status' => 'active',
        ]);

        $attachment = RequestAttachment::create([
            'user_id' => $this->client->id, 'event_id' => $event->id,
            'file_path' => 'request-files/example.pdf', 'file_name' => 'example.pdf',
            'file_size' => 1024, 'mime_type' => 'application/pdf',
        ]);

        $this->rows = [
            'event'        => $event->id,
            'finalization' => $finalization->id,
            'booking'      => $booking->id,
            'conversation' => $conversation->id,
            'package'      => $package->id,
            'attachment'   => $attachment->id,
        ];
    }

    public static function detailRoutes(): array
    {
        return [
            ['client.bsr.resume'],
            ['client.events.show'],
            ['client.proposals.compare'],
            ['client.events.extension.cancel'],
            ['client.events.extension.success'],
            ['client.finalize.overview'],
            ['client.finalize.step'],
            ['client.chat.show'],
            ['client.packages.booked'],
            ['client.packages.book'],
            ['client.payments.show'],
            // A download rather than a page, and included for that reason:
            // the file behind this row does not exist on disk, which is a
            // missing file and should read as one, not as a thrown page.
            ['client.request-files.show'],
        ];
    }

    /** @dataProvider detailRoutes */
    public function test_the_page_does_not_throw(string $name): void
    {
        if (! Route::has($name)) {
            $this->markTestSkipped("{$name} is not routed");
        }

        $uri = Route::getRoutes()->getByName($name)->uri();
        $args = [];

        // Hand each route the row it is about, by the name it calls it.
        foreach (['event', 'finalization', 'booking', 'conversation', 'package', 'attachment'] as $param) {
            if (str_contains($uri, '{' . $param . '}')) {
                $args[$param] = $this->rows[$param];
            }
        }

        $response = $this->actingAs($this->client)->get(route($name, $args));

        $this->assertLessThan(
            500,
            $response->getStatusCode(),
            "{$name} threw: " . \Illuminate\Support\Str::limit(strip_tags($response->getContent()), 400),
        );
    }
}
