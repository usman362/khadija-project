<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bidding windows, approved 2026-07-31: a standard request accepts bids for
 * 5 days, an emergency one for 24 hours. R37 forbade inventing these values,
 * so until they were approved a request could stay open indefinitely.
 */
class BiddingWindowTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create();
        $u->assignRole('client');
        $u->givePermissionTo('dashboard.view');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $u->fresh();
    }

    public function test_the_approved_windows_are_configured(): void
    {
        $this->assertSame(5, (int) config('bsr.default_proposal_window_days'));
        $this->assertSame(24, (int) config('bsr.esr.default_window_hours'));
    }

    public function test_a_rush_request_closes_in_twenty_four_hours(): void
    {
        $category = Category::create(['name' => 'Photography', 'slug' => 'photography']);

        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'event_name'  => 'Replacement DJ needed',
            'reason'      => 'professional_cancelled',
            'description' => 'Our DJ cancelled and we need a replacement for Saturday evening.',
            'needed_by'   => now()->addDays(3)->format('Y-m-d H:i:s'),
            'services'    => [$category->id],
            'scope'       => 'single',
            'location'    => 'Baltimore, MD',
        ])->assertRedirect();

        $event = Event::where('source', 'esr')->latest('id')->first();

        $this->assertNotNull($event, 'the rush request was created');
        $this->assertNotNull($event->proposal_deadline, 'a rush request must have a closing time');
        $this->assertEqualsWithDelta(
            24,
            now()->diffInHours($event->proposal_deadline),
            1,
            'approved window is 24 hours',
        );
    }

    public function test_the_window_never_outlasts_the_event_itself(): void
    {
        $category = Category::create(['name' => 'Catering', 'slug' => 'catering']);

        // Needed in six hours — bidding cannot still be open tomorrow.
        $neededBy = now()->addHours(6);

        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'event_name'  => 'Emergency catering',
            'reason'      => 'no_show',
            'description' => 'Caterer pulled out this morning and we need cover for tonight.',
            'needed_by'   => $neededBy->format('Y-m-d H:i:s'),
            'services'    => [$category->id],
            'scope'       => 'single',
            'location'    => 'Baltimore, MD',
        ])->assertRedirect();

        $event = Event::where('source', 'esr')->latest('id')->first();

        $this->assertNotNull($event->proposal_deadline);
        $this->assertTrue(
            $event->proposal_deadline->lte($neededBy->addMinute()),
            'bidding must close by the time the service is needed',
        );
    }
}
