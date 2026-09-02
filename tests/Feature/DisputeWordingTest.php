<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah's dispute-page wording, 2026-08-30.
 *
 * Her rules, and what each one is guarding against:
 *
 *   · GigResource makes a PLATFORM decision, not a legal judgment
 *   · the internal period is a direct-resolution window, not a loss of rights
 *   · outside legal and consumer remedies must not be restricted by the wording
 *   · money language must match the actual payment architecture
 *   · the same published rules apply to everyone, consistently
 *
 * The Platform Resolution paragraph is her exact text, supplied to be used
 * verbatim. It lives in one partial so the three pages that describe a decision
 * cannot drift into saying three different things.
 */
class DisputeWordingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $pro = User::factory()->create();
        $pro->assignRole('professional');

        $event = Event::create([
            'title' => 'Garden wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published', 'starts_at' => now()->addMonth(),
        ]);

        $this->booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $pro->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => 1500,
        ]);
    }

    /**
     * The page as words, not as markup. Sentences wrap across lines in the
     * source, so an exact phrase can straddle a newline and never be found —
     * which says nothing about whether it is on the page.
     */
    private function words(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    /** Her exact sentences, word for word. */
    public function test_the_platform_resolution_wording_is_verbatim(): void
    {
        $words = $this->words(
            $this->actingAs($this->client)->get(route('disputes.index'))->assertOk()->getContent()
        );

        $this->assertStringContainsString(
            'GigResource reviews the information available through the platform and communicates its '
            . 'decision regarding the booking and any funds controlled by GigResource, based on the '
            . 'applicable booking terms and platform policies.',
            $words
        );
        $this->assertStringContainsString(
            "GigResource's platform resolution does not prevent either party from pursuing rights or "
            . 'remedies available under applicable law.',
            $words
        );
    }

    /** It has to be on the page where somebody is about to file, too. */
    public function test_it_is_on_the_filing_page(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('disputes.create', ['booking' => $this->booking->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Platform Resolution', $this->words($html));
    }

    /**
     * Rights under law are not granted by our terms. The page used to read
     * "Outside escalation. If the Terms of Service allow it" — as though ours
     * decided whether somebody could use them.
     */
    public function test_outside_remedies_are_not_made_conditional_on_our_terms(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('disputes.create', ['booking' => $this->booking->id]))
            ->assertOk()
            ->getContent();

        $words = $this->words($html);

        $this->assertStringNotContainsString('If the Terms of Service allow it', $words);
        $this->assertStringContainsString('you keep whatever rights and remedies the law gives you', $words);
    }

    /** Say why the deposit is not refundable, and what is actually in question. */
    public function test_the_deposit_is_explained_rather_than_asserted(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('disputes.create', ['booking' => $this->booking->id]))
            ->assertOk()
            ->getContent();

        $words = $this->words($html);

        $this->assertStringContainsString(
            'It covers the administrative and reservation costs of holding the date for you',
            $words
        );
        $this->assertStringContainsString('Only the balance above the deposit is in question', $words);
    }

    /** One partial, so the pages cannot say three different things. */
    public function test_the_wording_lives_in_one_place(): void
    {
        $partial = file_get_contents(base_path('resources/views/disputes/_platform_resolution.blade.php'));
        $this->assertStringContainsString('Platform Resolution', $partial);

        foreach (['index', 'create', 'show'] as $page) {
            $this->assertStringContainsString(
                "@include('disputes._platform_resolution')",
                file_get_contents(base_path("resources/views/disputes/{$page}.blade.php")),
                "The {$page} page does not use the shared wording, so it can drift."
            );
        }
    }
}
