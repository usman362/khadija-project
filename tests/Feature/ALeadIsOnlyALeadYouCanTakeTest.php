<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\BidAccess;
use App\Support\LeadPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ali, 13 November. Every "Open the request" link on the Leads CRM answered
 * 403 or 410 — all seven of them.
 *
 * The page offered the bid wizard on every row without asking whether the
 * wizard would open. Six were requests outside the services the professional
 * lists, which R61 bars them from bidding on, and one was closed. The deeper
 * fault was behind it: the pipeline already leaves out an open request in
 * another state, because R38 means the professional can never take it and
 * "a lead they are not allowed to take is not a lead", in the file's own
 * words. It never applied the same reasoning to R61. On the demonstration
 * account that was thirteen new leads worth $5,995 that could not be touched.
 *
 * The question now has one home, App\Support\BidAccess, and the wizard and
 * the list both ask it.
 */
class ALeadIsOnlyALeadYouCanTakeTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Category $mine;
    private Category $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->mine   = $this->category('Buffet Catering');
        $this->theirs = $this->category('Fire Breathing');

        $this->pro    = $this->account('professional');
        $this->client = $this->account('client');

        $this->pro->serviceCategories()->sync([$this->mine->id]);
        $this->pro = $this->pro->fresh();
    }

    private function category(string $name): Category
    {
        $slug = \Illuminate\Support\Str::slug($name) . '-' . uniqid();

        return Category::create([
            'name'      => $name . ' ' . $slug,
            'slug'      => $slug,
            'is_active' => true,
        ]);
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function openRequest(Category $for): Event
    {
        $event = Event::create([
            'title'             => 'Open request',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'status'            => 'published',
            'is_published'      => true,
            'state'             => 'MD',
            'starts_at'         => now()->addDays(30),
            'proposal_deadline' => now()->addDays(10),
            'budget'            => 4000,
            'location'          => 'Baltimore',
        ]);

        $event->categories()->sync([$for->id]);

        return $event->fresh();
    }

    public function test_an_open_request_for_a_service_you_do_not_offer_is_not_a_lead(): void
    {
        $this->openRequest($this->theirs);

        $leads = LeadPipeline::forProfessional($this->pro);

        $this->assertCount(0, $leads,
            'R61 bars bidding on an unlisted service, so the funnel must not count it.');
    }

    public function test_an_open_request_for_a_service_you_do_offer_is_a_lead_you_can_bid_on(): void
    {
        $event = $this->openRequest($this->mine);

        $leads = LeadPipeline::forProfessional($this->pro);

        $this->assertCount(1, $leads);
        $this->assertSame($event->id, $leads->first()['event_id']);
        $this->assertTrue($leads->first()['can_bid']);
    }

    public function test_the_list_and_the_wizard_cannot_disagree_about_what_is_takeable(): void
    {
        $open   = $this->openRequest($this->mine);
        $closed = $this->openRequest($this->theirs);

        $this->assertNull(BidAccess::denial($this->pro, $open->load('categories')));
        $this->assertNotNull(BidAccess::denial($this->pro, $closed->load('categories')));

        $this->actingAs($this->pro)->get(route('professional.bid.step', ['event' => $open->id]))->assertOk();
        $this->actingAs($this->pro)->get(route('professional.bid.step', ['event' => $closed->id]))->assertForbidden();
    }

    public function test_the_page_only_offers_the_wizard_where_the_wizard_opens(): void
    {
        $open = $this->openRequest($this->mine);

        $html = $this->actingAs($this->pro)
            ->get(route('professional.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('professional.bid.step', ['event' => $open->id]), $html);
    }

    public function test_a_request_already_bid_on_stays_in_the_list_whatever_became_of_it(): void
    {
        $event = $this->openRequest($this->theirs);

        \App\Models\Bid::create([
            'event_id'    => $event->id,
            'supplier_id' => $this->pro->id,
            'amount'      => 3000,
            'status'      => 'submitted',
        ]);

        $leads = LeadPipeline::forProfessional($this->pro);

        $this->assertCount(1, $leads, 'Work already done stays on the board whatever the rules now say.');
        $this->assertFalse($leads->first()['can_bid']);
    }

    public function test_a_closed_request_has_a_page_of_its_own_rather_than_a_bare_exception(): void
    {
        $this->assertFileExists(resource_path('views/errors/410.blade.php'),
            'The wizard answers 410 for a closed request; without this view it comes out as a stack trace.');
    }

    public function test_the_dashboard_does_not_offer_bid_on_a_request_that_is_closed(): void
    {
        $open = $this->openRequest($this->mine);

        $shut = $this->openRequest($this->mine);
        $shut->update(['proposal_deadline' => now()->subDay()]);

        $html = $this->actingAs($this->pro)
            ->get(route('professional.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('professional.bid.step', ['event' => $open->id]), $html);
        $this->assertStringNotContainsString(route('professional.bid.step', ['event' => $shut->id]), $html,
            'The feed matched on service alone and offered Bid on a request that answers 410.');
    }
}
