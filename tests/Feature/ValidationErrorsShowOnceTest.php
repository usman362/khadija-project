<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A failed submit says what is wrong once.
 *
 * layouts.client has rendered every validation message since March, at the top
 * of the page. Six client screens rendered their own copy as well, so a client
 * who mistyped an address was told about it twice, in two differently styled
 * boxes, one under the other. Sir Peter saw it on the Bidding Request wizard.
 *
 * The layout keeps the job because it covers every page without each one
 * remembering to. What is asserted here is the count, not the presence — a
 * message shown once is right, and this is the check that noticed it was two.
 */
class ValidationErrorsShowOnceTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /**
     * Counted on the rendered page rather than in the Blade source: a message
     * printed by the layout and a message printed by the page look nothing
     * alike in the files and identical to the person reading them.
     */
    private function timesShown(string $html, string $message): int
    {
        $text = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES);
        $text = preg_replace('/\s+/', ' ', $text);

        return substr_count($text, $message);
    }

    public function test_the_bidding_wizard_reports_a_problem_once(): void
    {
        $client = $this->client();

        // An address that is only an area, with "I know the address" chosen —
        // the exact mistake Sir Peter's screenshot was showing.
        // Step 1 answered, or the wizard bounces this back before it validates.
        $wizard = ['services' => [1, 2], 'organization_type' => 'individual'];

        $html = $this->actingAs($client)
            ->withSession(['bsr_wizard' => $wizard])
            ->followingRedirects()
            ->from('/client/bsr/event')
            ->post('/client/bsr/event', [
                'location_kind' => 'exact',
                'location' => 'Baltimore',
                'title' => 'A request',
                'guest_count' => 150,
            ])
            ->getContent();

        $shown = $this->timesShown($html, 'That looks like an area rather than an address');

        $this->assertSame(1, $shown, "the message was shown {$shown} times");
    }

    /** The other request screens follow the same rule. */
    public function test_no_client_page_doubles_up_on_its_own_error_block(): void
    {
        $doubling = [];

        foreach ([
            'resources/views/client/bsr/wizard.blade.php',
            'resources/views/client/esr/create.blade.php',
            'resources/views/client/direct-offers/create.blade.php',
            'resources/views/client/finalize/wizard.blade.php',
            'resources/views/client/bookings/index.blade.php',
        ] as $view) {
            $source = file_get_contents(base_path($view));

            // A page-level list of every error is the duplicate. @error on a
            // single field is not — that sits beside the field it belongs to
            // and says something the summary does not.
            if (preg_match('/@if\s*\(\s*\$errors->any\(\)\s*\)(?:(?!@endif).)*\$errors->all\(\)/s', $source)) {
                $doubling[] = $view;
            }
        }

        $this->assertSame([], $doubling, "these print the layout's list again:\n".implode("\n", $doubling));
    }
}
