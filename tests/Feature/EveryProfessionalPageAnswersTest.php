<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Conversation, Event, Finalization, Message, Package, StaffMember, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Open every professional page, with real records behind it, and look.
 *
 * Not a style pass. Three things only, each of which has already been found
 * on this side by hand: a page that errors, a link that goes nowhere, and a
 * form with no action, which looks like it will save and does not.
 */
class EveryProfessionalPageAnswersTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Event $event;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Hollis Tremaine');
        $this->client = $this->person('client', 'Imogen Hartwell');

        $this->event = Event::create([
            'title' => 'Hartwell Anniversary', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addDays(20), 'location' => 'Baltimore, MD',
        ]);

        $this->booking = Booking::create([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'category_id' => $this->service()->id, 'status' => 'confirmed', 'price' => 2600,
        ]);

        Finalization::create([
            'event_id' => $this->event->id, 'category_id' => $this->service()->id,
            'supplier_id' => $this->pro->id, 'client_id' => $this->client->id,
            'booking_id' => $this->booking->id, 'terms_version' => 1,
            'agreed_price' => 2600, 'scope' => 'Six hours of coverage.',
            'client_signature' => $this->client->name, 'client_signed_at' => now(),
            'client_signed_version' => 1,
        ]);

        /*
         * A second event, still open for bids and belonging to nobody. The
         * first one is awarded to this professional, and the bid wizard
         * rightly answers 410 for a request that is no longer taking
         * proposals, so it needs one that is.
         */
        $this->openEvent = Event::create([
            'title' => 'Tremaine Fundraiser', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'is_published' => true, 'starts_at' => now()->addDays(45),
            'location' => 'Baltimore, MD', 'proposal_deadline' => now()->addDays(10),
        ]);

        StaffMember::create([
            'supplier_id' => $this->pro->id, 'name' => 'Marguerite Vaskov',
            'role' => 'Second shooter', 'phone' => '410-555-0188', 'status' => 'active',
        ]);

        Package::create([
            'user_id' => $this->pro->id, 'title' => 'Full Day Coverage', 'slug' => 'full-day-coverage',
            'type' => 'solo', 'price' => 3250, 'is_active' => true,
        ]);

        $c = Conversation::create([
            'type' => 'booking', 'booking_id' => $this->booking->id,
            'event_id' => $this->event->id, 'created_by' => $this->client->id,
        ]);
        // A conversation nobody is in cannot be opened by anybody, which is
        // the policy working, not a bug: participants are what make it theirs.
        $c->participants()->attach([$this->client->id, $this->pro->id], ['joined_at' => now()]);

        Message::create([
            'conversation_id' => $c->id, 'booking_id' => $this->booking->id,
            'sender_id' => $this->client->id, 'recipient_id' => $this->pro->id,
            'body' => 'Could you start an hour earlier?',
        ]);
        $this->conversation = $c;
    }

    private Conversation $conversation;
    private Event $openEvent;

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

    /** Every GET page in the professional group, with parameters filled in. */
    private function pages(): array
    {
        $fill = [
            'event'        => $this->openEvent->id,
            'finalization' => Finalization::firstOrFail()->id,
            'conversation' => $this->conversation->id,
            'package'      => Package::firstOrFail()->id,
            'booking'      => $this->booking->id,
            'id'           => $this->event->id,
            'gig'          => $this->event->id,
            'step'         => null,
            'shift'        => null,
        ];

        $out = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'professional.') || str_contains($name, 'login')) {
                continue;
            }
            // Downloads and exports are files, not pages.
            if (preg_match('/\.(csv|pdf|download|draft)$/', $name) || str_contains($name, 'export')) {
                continue;
            }

            $params = [];
            $usable = true;
            foreach ($route->parameterNames() as $p) {
                if (! array_key_exists($p, $fill)) { $usable = false; break; }
                if ($fill[$p] !== null) { $params[$p] = $fill[$p]; }
            }
            if ($usable) {
                $out[$name] = $params;
            }
        }

        return $out;
    }

    public function test_no_professional_page_errors(): void
    {
        $broken = [];

        foreach ($this->pages() as $name => $params) {
            $status = $this->actingAs($this->pro)->get(route($name, $params))
                ->baseResponse->getStatusCode();

            if ($status >= 400) {
                $broken[] = "{$name} -> {$status}";
            }
        }

        $this->assertSame([], $broken, "Pages that did not answer:\n  " . implode("\n  ", $broken));
    }

    public function test_no_professional_page_offers_a_link_to_nowhere(): void
    {
        $dead = [];

        foreach ($this->pages() as $name => $params) {
            $html = $this->actingAs($this->pro)->get(route($name, $params))->getContent();

            // A bare "#" href that is not a toggle or a tab.
            if (preg_match_all('/<a\b(?![^>]*\bdata-[a-z-]+)[^>]*href="#"[^>]*>/i', $html, $m)) {
                $dead[] = $name . ' (' . count($m[0]) . ')';
            }
        }

        $this->assertSame([], $dead, "Links that go nowhere:\n  " . implode("\n  ", $dead));
    }

    public function test_no_professional_page_shows_a_form_that_saves_nothing(): void
    {
        $bad = [];

        foreach ($this->pages() as $name => $params) {
            $html = $this->actingAs($this->pro)->get(route($name, $params))->getContent();

            foreach (['/<form\b(?![^>]*\baction=)[^>]*>/i' => 'no action'] as $re => $why) {
                if (preg_match_all($re, $html, $m)) {
                    // A GET form with no action posts to the current page, which
                    // is how a filter works, so only POST ones count.
                    $posts = array_filter($m[0], fn ($f) => stripos($f, 'method="post"') !== false);
                    if ($posts) {
                        $bad[] = "{$name}: " . count($posts) . " form(s) with {$why}";
                    }
                }
            }
        }

        $this->assertSame([], $bad, "Forms that save nothing:\n  " . implode("\n  ", $bad));
    }

    /**
     * A button drawn in a row is wired by what it is, not where it sits.
     *
     * The Messages composer carried five icons and one of them worked. The
     * script took "the second button in the compose row", so the paperclip
     * was the paperclip only while the four dead ones around it kept their
     * places. They are gone and it is found by name.
     */
    public function test_the_message_composer_offers_only_what_it_can_do(): void
    {
        $html = $this->actingAs($this->pro)
            ->get(route('professional.chat.show', ['conversation' => $this->conversation->id]))
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('id="pm-attach"', $html);
        $this->assertStringNotContainsString('title="Emoji"', $html);
        $this->assertStringNotContainsString('title="Schedule"', $html);
        $this->assertStringNotContainsString('icons[1]', $html, 'The composer is wired by position again.');
    }

    /** The Gig Hub's Export button had no destination for as long as it existed. */
    public function test_the_gig_list_can_actually_be_exported(): void
    {
        $response = $this->actingAs($this->pro)->get(route('professional.gigs.export'));

        $response->assertSuccessful();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Gig,Date,Start,Client', $csv);
        $this->assertStringContainsString($this->event->title, $csv, 'The export left out a gig that is theirs.');
    }

    /** Sir Peter's rule: the platform does not promise an outcome. */
    public function test_no_professional_page_promises_more_business(): void
    {
        $promises = [];

        foreach ($this->pages() as $name => $params) {
            $html = $this->actingAs($this->pro)->get(route($name, $params))->getContent();

            foreach (['win more', 'close more deals', 'convert more leads', 'stress-free',
                      'ahead of the competition', 'grow your business'] as $claim) {
                if (stripos($html, $claim) !== false) {
                    $promises[] = "{$name}: {$claim}";
                }
            }
        }

        $this->assertSame([], $promises, "Promises still on screen:\n  " . implode("\n  ", $promises));
    }
}
