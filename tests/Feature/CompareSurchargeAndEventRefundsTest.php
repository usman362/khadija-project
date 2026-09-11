<?php

namespace Tests\Feature;

use App\Domain\Requests\Award;
use App\Models\{Bid, Booking, CancellationRequest, Event, Package, User, UserProfile};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Three of Sir Peter's written answers of 5 September:
 *
 *  PM-10  Package compare highlights what differs and mutes what matches.
 *  PM-3   An emergency request carries a flat 25% on the professional's price.
 *  D-2    Cancelling a whole event refunds each booking under its own tier,
 *         tells each professional, and shows "Cancelled by Client on [date]".
 */
class CompareSurchargeAndEventRefundsTest extends TestCase
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
        UserProfile::updateOrCreate(['user_id' => $u->id], [
            'city' => 'Baltimore', 'state' => 'MD', 'country' => 'US',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $u->fresh();
    }

    /* ── PM-10 ─────────────────────────────────────────────── */

    private function package(User $pro, array $attrs): Package
    {
        static $n = 0;
        $n++;

        return Package::create(array_merge([
            'user_id' => $pro->id, 'title' => 'Package ' . $n, 'slug' => 'cmp-pkg-' . $n,
            'type' => 'solo', 'price' => 3000, 'services' => ['Photography & Videography'],
            'is_active' => true, 'status' => 'active', 'coverage' => '6 hours',
        ], $attrs));
    }

    private function rowClass(string $html, string $label): ?string
    {
        return preg_match('/<tr class="([^"]*)">\s*<th class="row" scope="row">' . preg_quote($label, '/') . '<\/th>/', $html, $m)
            ? $m[1] : null;
    }

    public function test_compare_highlights_differences_and_mutes_matches(): void
    {
        $pro = $this->user('professional');
        $a = $this->package($pro, ['price' => 2000]);
        $b = $this->package($pro, ['price' => 3500]);

        $html = $this->get(route('public.packages.compare', ['ids' => "{$a->id},{$b->id}"]))
            ->assertOk()->getContent();

        $this->assertSame('is-diff', $this->rowClass($html, 'Total price'));
        $this->assertSame('is-same', $this->rowClass($html, 'Coverage'));
        $this->assertStringContainsString('Show differences only', $html);
    }

    public function test_one_package_has_nothing_to_highlight(): void
    {
        $a = $this->package($this->user('professional'), []);

        $html = $this->get(route('public.packages.compare', ['ids' => (string) $a->id]))
            ->assertOk()->getContent();

        // The row classes, not the CSS that styles them.
        $this->assertStringNotContainsString('<tr class="is-', $html);
        $this->assertStringNotContainsString('Show differences only', $html);
    }

    /* ── PM-3 ──────────────────────────────────────────────── */

    private function bidOn(string $source, int $amount): Bid
    {
        $client = $this->user('client');
        $event = Event::create([
            'title' => 'Tonight', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'is_published' => true, 'source' => $source,
            'starts_at' => now()->addDays(2),
        ]);

        return Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->user('professional')->id,
            'amount' => $amount, 'status' => 'submitted',
        ]);
    }

    public function test_an_emergency_agreement_starts_25_percent_above_the_bid(): void
    {
        $f = Award::openFinalization($this->bidOn('esr', 800));

        $this->assertEqualsWithDelta(1000.0, (float) $f->agreed_price, 0.001);
    }

    public function test_other_requests_start_at_the_bid(): void
    {
        $f = Award::openFinalization($this->bidOn('bsr', 800));

        $this->assertEqualsWithDelta(800.0, (float) $f->agreed_price, 0.001);
    }

    public function test_the_emergency_form_says_so(): void
    {
        $this->actingAs($this->user('client'))
            ->get(route('client.esr.create'))
            ->assertOk()
            ->assertSee('Emergency requests add a flat', false);
    }

    /* ── D-2 ───────────────────────────────────────────────── */

    public function test_a_whole_event_is_refunded_booking_by_booking(): void
    {
        $client = $this->user('client');
        $admin = User::factory()->create(['primary_role' => 'admin']);
        $admin->assignRole('admin');

        $event = Event::create([
            'title' => 'Garden Reception', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addMonths(3),
        ]);

        $pros = [$this->user('professional'), $this->user('professional')];
        foreach ($pros as $i => $pro) {
            Booking::create([
                'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
                'created_by' => $client->id, 'status' => 'confirmed', 'price' => [1000, 600][$i],
            ]);
        }

        // The client sees each booking's refund before asking.
        $this->actingAs($client)->get(route('cancellations.create', ['kind' => CancellationRequest::CLIENT_CANCELS_EVENT]))
            ->assertOk()
            ->assertSee('each booking is refunded on its own', false);

        $this->actingAs($client)->post(route('cancellations.store'), [
            'kind' => CancellationRequest::CLIENT_CANCELS_EVENT, 'event_id' => $event->id,
            'reason' => 'The venue fell through and we are not going ahead this year.', 'certified' => 1,
        ])->assertSessionHasNoErrors();

        $cr = CancellationRequest::firstWhere('event_id', $event->id);
        $this->assertCount(2, $cr->quoted_breakdown);
        $this->assertEqualsWithDelta(1600.0, (float) $cr->quoted_refund, 0.001);

        $this->actingAs($admin)->post(route('app.admin.cancellations.approve', $cr), [])
            ->assertSessionHasNoErrors();

        // The bookings go with the event.
        $this->assertSame(['cancelled', 'cancelled'], Booking::where('event_id', $event->id)->pluck('status')->all());

        // Each professional gets the standard message, with their tier.
        foreach ($pros as $pro) {
            $n = $pro->fresh()->notifications()->first();
            $this->assertNotNull($n, 'A professional was not told.');
            $this->assertSame('event_cancelled_by_client', $n->data['type']);
            $this->assertStringContainsString('This was not caused by anything on your end.', $n->data['message']);
            $this->assertStringContainsString('More than 30 days', $n->data['message']);
        }

        // "Cancelled", with who and when.
        $this->actingAs($client)->get(route('client.events.show', $event))
            ->assertOk()
            ->assertSee('Cancelled by Client on', false);
    }
}
