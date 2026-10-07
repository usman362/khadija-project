<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\SupplierProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sir Peter, 7 October: "small changes of text wording, change the red marked
 * area from 'View Event' to 'View Event Details' plus this other one...
 * 'View profile' to 'View Professional Profile' (unless its an 'Influencer')."
 *
 * The first half is wording. The second half is not: the word has to come from
 * the account, and the link has to agree with the word. /pro/{user} turns away
 * anyone without the professional role, so "View influencer profile" pointing
 * there is a 404 wearing a friendly label.
 */
class TheMenuNamesWhatItOpensTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Event $event;

    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->service = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-menu',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->event = Event::create([
            'title' => 'Wedding Reception', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(40)->setTime(17, 0),
        ]);
        $this->event->categories()->sync([$this->service->id]);
    }

    private function supplier(string $role): User
    {
        $u = User::factory()->create(['name' => ucfirst($role) . ' Person', 'primary_role' => $role]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function bidFrom(User $supplier): Bid
    {
        return Bid::create([
            'event_id' => $this->event->id, 'category_id' => $this->service->id,
            'supplier_id' => $supplier->id, 'amount' => 1400, 'status' => 'submitted',
            'available_confirmed' => true,
        ]);
    }

    private function proposalsPage(): string
    {
        return $this->actingAs($this->client)->get('/client/proposals')->assertSuccessful()->getContent();
    }

    /** The two strings Sir Peter marked. */
    public function test_the_proposals_menu_says_what_it_opens(): void
    {
        $this->bidFrom($this->supplier('professional'));

        $html = $this->proposalsPage();

        $this->assertStringContainsString('>View event details<', $html);
        $this->assertStringContainsString('>View professional profile<', $html);

        // And the old wording is gone, not merely joined.
        $this->assertStringNotContainsString('>View event<', $html);
        $this->assertStringNotContainsString('>View profile<', $html);
    }

    /** The "unless" half: an influencer is not called a professional. */
    public function test_an_influencer_is_named_as_one(): void
    {
        $influencer = $this->supplier('influencer');

        $this->assertSame('View influencer profile', SupplierProfile::viewLabel($influencer));
        $this->assertSame('Message influencer', SupplierProfile::messageLabel($influencer));

        $this->bidFrom($influencer);

        $html = $this->proposalsPage();

        $this->assertStringContainsString('Message influencer', $html);
        $this->assertStringNotContainsString('Message professional', $html);
    }

    /**
     * The professional profile page refuses anyone else, so an item offering
     * to open it for an influencer would be a dead end with a friendly name.
     */
    public function test_a_profile_is_only_offered_where_there_is_one(): void
    {
        $influencer = $this->supplier('influencer');

        $this->assertNull(SupplierProfile::url($influencer));

        // The page itself agrees, which is the reason for the rule.
        $this->actingAs($this->client)->get('/pro/' . $influencer->id)->assertNotFound();

        $this->bidFrom($influencer);

        $html = $this->proposalsPage();

        $this->assertStringNotContainsString('View influencer profile', $html);
        $this->assertStringNotContainsString('View professional profile', $html);
    }

    /** A professional's link is there, and it opens. */
    public function test_a_professionals_profile_opens(): void
    {
        $pro = $this->supplier('professional');

        $this->assertSame(route('public.professional.show', $pro->id), SupplierProfile::url($pro));

        $this->bidFrom($pro);

        $this->assertStringContainsString(
            'href="' . route('public.professional.show', $pro->id) . '"',
            $this->proposalsPage()
        );

        $this->actingAs($this->client)->get('/pro/' . $pro->id)->assertSuccessful();
    }

    /**
     * primary_role is read first because these labels are drawn once per row,
     * but an account that never had one set must still be named correctly.
     */
    public function test_an_account_with_no_primary_role_is_still_read_properly(): void
    {
        $pro = User::factory()->create(['primary_role' => null]);
        $pro->assignRole('professional');

        $inf = User::factory()->create(['primary_role' => null]);
        $inf->assignRole('influencer');

        $this->assertSame('View professional profile', SupplierProfile::viewLabel($pro->fresh()));
        $this->assertNotNull(SupplierProfile::url($pro->fresh()));

        $this->assertSame('View influencer profile', SupplierProfile::viewLabel($inf->fresh()));
        $this->assertNull(SupplierProfile::url($inf->fresh()));
    }

    /** Nobody on the other side at all: a label still reads, a link is not offered. */
    public function test_a_missing_supplier_is_not_a_broken_link(): void
    {
        $this->assertSame('View professional profile', SupplierProfile::viewLabel(null));
        $this->assertNull(SupplierProfile::url(null));
    }

    /** My Events and Bookings carry the same words as the proposals menu. */
    public function test_the_other_menus_were_brought_along(): void
    {
        $this->bidFrom($this->supplier('professional'));

        foreach (['/client/events', '/client/bookings'] as $path) {
            $html = $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();

            $this->assertStringNotContainsString('>View event<', $html, $path . ' still says "View event".');
        }
    }
}
