<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, MessageAttachment, User};
use App\Support\{PlaceholderAssets, ServiceArea};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Round three from the Developer Handoff v4 of 12 September. */
class HandoffSep12RoundThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore', 'service_area_status' => ServiceArea::SUPPORTED]);

        return $u->fresh();
    }

    private function confirmed(string $source): array
    {
        $client = $this->user('client');
        $pro = $this->user('professional');
        $event = Event::create(['title' => 'Direct Offer Event', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'confirmed', 'is_published' => true, 'source' => $source, 'starts_at' => now()->addMonth()]);
        $b = Booking::create(['event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
            'created_by' => $client->id, 'status' => 'confirmed', 'price' => 500]);

        return [$event, $b];
    }

    /** OA-114: a withdrawn direct request no longer says Confirmed. */
    public function test_a_cancelled_direct_request_booking_cancels_its_event(): void
    {
        [$event, $b] = $this->confirmed('direct_offer');
        $b->update(['status' => 'cancelled']);

        $this->assertSame('cancelled', $event->fresh()->status);
        $this->assertSame('cancelled', $event->fresh()->stage());
    }

    public function test_a_cancelled_bidding_booking_reopens_its_event(): void
    {
        [$event, $b] = $this->confirmed('user');
        $b->update(['status' => 'cancelled']);

        $this->assertSame('published', $event->fresh()->status);
    }

    public function test_another_live_booking_keeps_it_confirmed(): void
    {
        [$event, $b] = $this->confirmed('user');
        Booking::create(['event_id' => $event->id, 'client_id' => $event->client_id, 'supplier_id' => $this->user('professional')->id,
            'created_by' => $event->client_id, 'status' => 'confirmed', 'price' => 300]);

        $b->update(['status' => 'cancelled']);

        $this->assertSame('confirmed', $event->fresh()->status);
    }

    /** OA-111 / OA-113: per-event counts, and chips that add to the total. */
    public function test_event_types_show_recommended_counts(): void
    {
        Category::create(['name' => 'Bachelor Party', 'slug' => 'bachelor-party-t', 'kind' => Category::EVENT_TYPE,
            'archetype' => 'Wedding & Related Ceremonies', 'is_active' => true]);

        $html = $this->get(route('public.event-types'))->assertOk()->getContent();

        $this->assertStringContainsString('recommended service', $html);
        $this->assertStringNotContainsString('27 service categories', $html);
        $this->assertStringContainsString('Numbers show how many event types are in each group', $html);
    }

    /** OA-121: sample files are not attachments. */
    public function test_placeholder_files_are_recognised(): void
    {
        $this->assertTrue(PlaceholderAssets::looksLikeOne('SampleVideo_1280x720_1mb.mp4'));
        $this->assertTrue(PlaceholderAssets::looksLikeOne('neutral-person.png'));
        $this->assertFalse(PlaceholderAssets::looksLikeOne('venue-floor-plan.pdf'));
    }

    public function test_the_cleanup_command_lists_without_changing(): void
    {
        [$event, $b] = $this->confirmed('direct_offer');
        Booking::withoutEvents(fn () => $b->update(['status' => 'cancelled']));

        $this->artisan('data:client-cleanup')->assertSuccessful();
        $this->assertSame('confirmed', $event->fresh()->status);

        $this->artisan('data:client-cleanup', ['--force' => true])->assertSuccessful();
        $this->assertSame('cancelled', $event->fresh()->status);
    }
}
