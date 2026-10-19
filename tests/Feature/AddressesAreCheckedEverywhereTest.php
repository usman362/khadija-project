<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\PlausibleAddress;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Sir Peter, 20 October: "ALL addresses (in the website) needs be checked for
 * accuracy, I created a fake address and it went right by it, and accepted a
 * fake address, this is not good."
 *
 * He was right that nothing was checking. AddressFilter has existed since the
 * address-verification scaffold was written and was wired into exactly ONE
 * screen: a professional verifying their own business address. Event locations,
 * client profiles and everything else had no filter at all.
 *
 * AND THE HALF HE WILL NOT LIKE, which this file states rather than hides. The
 * address he used was "18 Henry Street, Canton, Maryland". It has a number, a
 * street and a real Maryland town, so the free layer passes it and always will.
 * Nothing free can tell that a well-formed address does not exist; that means
 * asking USPS or Google Address Validation. Layer 2 of the service is already
 * written for exactly that and is dormant behind the launch lock because the
 * API account has not been provisioned.
 *
 * So these tests pin what the free layer DOES catch, and one of them pins what
 * it does not, so nobody later mistakes the filter for a verification.
 */
class AddressesAreCheckedEverywhereTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @return array<int, string> the messages the rule produced */
    private function check(string $address, bool $requireNumber = true): array
    {
        $v = Validator::make(
            ['address' => $address],
            ['address' => [new PlausibleAddress($requireNumber)]],
        );

        return $v->errors()->get('address');
    }

    // ── What it catches ──────────────────────────────────────

    public function test_a_po_box_is_refused(): void
    {
        foreach (['PO Box 42', 'P.O. Box 1180', 'po box 7', 'PMB 220'] as $line) {
            $this->assertNotEmpty($this->check($line), "{$line} should be refused.");
        }
    }

    public function test_keyboard_mash_is_refused(): void
    {
        $this->assertNotEmpty($this->check('aaaaaaa'));
        $this->assertNotEmpty($this->check('11111111'), 'Digits only is not an address either.');
    }

    public function test_a_street_with_no_number_is_refused(): void
    {
        $this->assertNotEmpty($this->check('Henry Street'));
    }

    /** Unless a town or venue name is a legitimate answer on that field. */
    public function test_a_town_is_allowed_where_a_town_is_a_fair_answer(): void
    {
        $this->assertEmpty($this->check('Towson', requireNumber: false));
        $this->assertEmpty($this->check('Oregon Ridge Park', requireNumber: false));
    }

    /** A real-looking address passes, which is the point of the free layer. */
    public function test_an_ordinary_address_passes(): void
    {
        $this->assertEmpty($this->check('18 Henry Street'));
    }

    // ── What it cannot catch, said out loud ──────────────────

    /**
     * His own example still passes, and will until the provider is live.
     *
     * This test exists so that the limit is recorded rather than discovered
     * again. If somebody later believes the filter verifies addresses, this is
     * the test that tells them it does not, and why.
     */
    public function test_a_well_formed_address_that_does_not_exist_still_passes(): void
    {
        $this->assertEmpty(
            $this->check('18 Henry Street'),
            'A number, a street and a real town. Confirming the building is there needs USPS or Google, '
            . 'which is Layer 2 of AddressVerificationService and waits on an API account.',
        );
    }

    // ── Applied where addresses are actually typed ───────────

    /** The client's own profile, which had no check at all. */
    public function test_a_po_box_is_refused_on_the_client_profile(): void
    {
        $this->actingAs($this->client)
            ->patch(route('client.profile.update.general'), [
                'name'    => 'Dana Whitfield',
                'address' => 'PO Box 915',
            ])
            ->assertSessionHasErrors('address');
    }

    /** And an emergency request's location. */
    public function test_a_po_box_is_refused_on_an_emergency_request(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), [
                'event_name'  => 'Rush job',
                'description' => 'Something has fallen through and we need cover quickly today.',
                'needed_by'   => now()->addDays(2)->format('Y-m-d\TH:i'),
                'location'    => 'P.O. Box 88',
            ])
            ->assertSessionHasErrors('location');
    }

    /**
     * A town is still a fair answer on a request.
     *
     * A client who has not found a venue says where they are looking. Demanding
     * a house number of them would refuse the honest answer, which is why the
     * number is only asked for where the field is meant to be a street.
     */
    public function test_a_town_is_still_accepted_as_a_request_location(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.esr.store'), [
                'event_name'  => 'Rush job',
                'description' => 'Something has fallen through and we need cover quickly today.',
                'needed_by'   => now()->addDays(2)->format('Y-m-d\TH:i'),
                'location'    => 'Towson',
            ])
            // Only this field. A complete emergency request needs a reason, an
            // organisation type, a budget and services, and building all of
            // that here would be testing the form rather than the rule.
            ->assertSessionDoesntHaveErrors('location');
    }
}
