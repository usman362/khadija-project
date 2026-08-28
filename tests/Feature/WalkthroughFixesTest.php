<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fixes from the 26 Aug walkthrough.
 *
 * Each of these was reported as a broken feature. Two of them were not: the
 * feature worked and the page kept quiet about what it had done, which reads
 * exactly the same from the outside.
 */
class WalkthroughFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(string $state = 'MD'): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $u->fresh();
    }

    private function package(string $state, string $title): Package
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'X']);

        return Package::create([
            'user_id' => $pro->id, 'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title).'-'.uniqid(),
            'price' => 1000, 'status' => 'active', 'is_active' => true, 'state' => $state,
        ]);
    }

    /**
     * "Compare is not comparing."
     *
     * It was. The packages ticked were in another state, the same-state rule
     * dropped them, and the page said "Compare 1 Package" with no account of
     * the other two.
     */
    public function test_compare_says_how_many_were_dropped_and_why(): void
    {
        $mine   = $this->package('MD', 'Maryland DJ');
        $theirs = $this->package('PA', 'Philly Catering');

        $response = $this->actingAs($this->client('MD'))
            ->get(route('public.packages.compare', ['ids' => $mine->id.','.$theirs->id]));

        $response->assertSuccessful();
        $response->assertSee('Maryland DJ');
        $response->assertDontSee('Philly Catering');

        // The part that was missing.
        $response->assertSee('1 of the 2 you picked');
        $response->assertSee('offered in another state', false);
    }

    /** Nothing to explain when they all survive. */
    public function test_compare_stays_quiet_when_nothing_was_dropped(): void
    {
        $a = $this->package('MD', 'Maryland DJ');
        $b = $this->package('MD', 'Maryland Catering');

        $this->actingAs($this->client('MD'))
            ->get(route('public.packages.compare', ['ids' => $a->id.','.$b->id]))
            ->assertSuccessful()
            ->assertDontSee('not shown here');
    }

    /**
     * The CSV wrote rows of four different widths into one sheet, so nothing
     * lined up under anything.
     */
    public function test_the_report_csv_is_square_and_opens_in_excel(): void
    {
        $response = $this->actingAs($this->client())->get(route('client.reports.csv'));

        $response->assertSuccessful();

        $csv = $response->streamedContent();

        // A UTF-8 BOM, or Excel guesses the codepage and mangles the text.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr($csv, 3)))));
        $widths = array_unique(array_map('count', $rows));

        $this->assertSame([3], array_values($widths), 'Every row must have the same number of columns.');
        $this->assertSame(['Section', 'Item', 'Value'], $rows[0]);
    }

    /** The "Find Professionals" page finally lives at that address. */
    public function test_find_professionals_has_its_own_url_and_browse_still_redirects(): void
    {
        $client = $this->client();

        $this->assertStringEndsWith('/find-professionals', route('public.browse'));

        $this->actingAs($client)->get('/browse')->assertRedirect(route('public.browse'));
    }

    /** A client cancels; a professional reports. The button said neither. */
    public function test_the_cancellations_button_names_what_the_reader_does(): void
    {
        $this->actingAs($this->client())
            ->get(route('cancellations.index'))
            ->assertSuccessful()
            ->assertSee('Cancel a booking')
            ->assertDontSee('Report something');
    }

    /** Four labels, at most two destinations — three of them the same page. */
    public function test_the_proposals_rail_offers_only_what_it_can_do(): void
    {
        $response = $this->actingAs($this->client())->get(route('client.proposals.index'));

        $response->assertSuccessful();

        foreach (['Send Reminder', 'Schedule Call', 'Share Availability', 'Follow Up'] as $fake) {
            $response->assertDontSee('>'.$fake.'<', false);
        }

        $response->assertSee('Message a professional');
    }

    /** Invented money left over from the Payments clean-up on 2026-08-15. */
    public function test_the_spending_page_has_no_gateway_or_irs_figures(): void
    {
        $response = $this->actingAs($this->client())->get(route('client.spending.index'));

        $response->assertSuccessful();

        foreach ([
            'Stripe Outflow',
            'Secure Payment Vault',
            '1099 Compliance Hub',
            'W-9 Forms Collected',
            'Critical Alert',
            '$600 IRS',
            'Revenue Pipeline',   // a client has no revenue
        ] as $invented) {
            $response->assertDontSee($invented, false);
        }
    }
}
