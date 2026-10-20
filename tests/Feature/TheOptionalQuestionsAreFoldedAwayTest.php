<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Eleven questions, seven of which you have to answer.
 *
 * Sir Peter counted the boxes on both the emergency and the direct forms and
 * got the same number twice: "I found 11 plus a submission", then "As for the
 * DR image, I found the same amount of 11 questions plus the submission." He
 * asked whether each should become a step by step process like the bidding
 * request.
 *
 * It should not. Splitting a short form across pages adds clicks without
 * removing questions, and the emergency form in particular is the one a client
 * reaches when they are already late. What was actually wrong is that the
 * optional questions looked exactly like the required ones, so a form needing
 * seven answers presented itself as needing eleven.
 *
 * So the optional ones are folded into a disclosure. This test holds the split
 * by where the field sits in the page, not by whether a class name exists:
 * a required question must be in the open part of the form, and an optional one
 * must be inside the fold.
 */
class TheOptionalQuestionsAreFoldedAwayTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    /**
     * The markup inside the disclosure, and the markup outside it.
     *
     * The fold is found by its own class rather than by the first <details> on
     * the page, so a disclosure added to the layout one day cannot silently
     * become the region under test.
     */
    private function split(string $url, string $fold): array
    {
        $html = $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();

        $open = strpos($html, '<details class="' . $fold . '"');

        $this->assertNotFalse($open, "No fold on {$url}: every question is presented as required.");

        $close = strpos($html, '</details>', $open);

        $this->assertNotFalse($close, "The fold on {$url} is never closed.");

        $inside = substr($html, $open, $close - $open);

        return [$inside, str_replace($inside, '', $html)];
    }

    private function asks(string $html, string $field): bool
    {
        return str_contains($html, 'name="' . $field . '"');
    }

    // ── The emergency request ────────────────────────────────

    public function test_the_emergency_form_folds_away_its_optional_questions(): void
    {
        [$inside, $outside] = $this->split(route('client.esr.create'), 'esr-more');

        foreach (['event_type', 'location', 'guest_count'] as $optional) {
            $this->assertTrue($this->asks($inside, $optional), "{$optional} is still asked up front.");
            $this->assertFalse($this->asks($outside, $optional), "{$optional} is asked twice.");
        }
    }

    public function test_the_emergency_form_asks_what_it_needs_up_front(): void
    {
        [, $outside] = $this->split(route('client.esr.create'), 'esr-more');

        foreach (['event_name', 'needed_by', 'description'] as $required) {
            $this->assertTrue($this->asks($outside, $required), "{$required} is required and hidden in the fold.");
        }
    }

    // ── The direct request ───────────────────────────────────

    public function test_the_direct_form_folds_away_its_optional_questions(): void
    {
        [$inside, $outside] = $this->split(route('client.direct-offers.create'), 'do-more');

        foreach (['event_type', 'guests', 'venue'] as $optional) {
            $this->assertTrue($this->asks($inside, $optional), "{$optional} is still asked up front.");
            $this->assertFalse($this->asks($outside, $optional), "{$optional} is asked twice.");
        }
    }

    public function test_the_direct_form_asks_what_it_needs_up_front(): void
    {
        [, $outside] = $this->split(route('client.direct-offers.create'), 'do-more');

        foreach (['event_name', 'event_date', 'organization_type'] as $required) {
            $this->assertTrue($this->asks($outside, $required), "{$required} is required and hidden in the fold.");
        }
    }

    // ── What the fold must not cost ──────────────────────────

    /**
     * A rejected form does not swallow what the client typed.
     *
     * This is the way a disclosure usually goes wrong: validation fails on a
     * required field, the page comes back, and the optional answers are still
     * there in the HTML but behind a panel that is now shut. It reads as if
     * the form threw them away.
     */
    public function test_the_fold_opens_again_when_an_optional_answer_was_given(): void
    {
        $this->actingAs($this->client)
            ->from(route('client.direct-offers.create'))
            ->post(route('client.direct-offers.store'), ['venue' => 'The Grand Ballroom, Baltimore, MD'])
            ->assertRedirect(route('client.direct-offers.create'));

        [$inside] = $this->split(route('client.direct-offers.create'), 'do-more');

        $this->assertStringContainsString('open', substr($inside, 0, strpos($inside, '>')),
            'The panel came back shut on a form that had an answer in it.');
        $this->assertStringContainsString('The Grand Ballroom, Baltimore, MD', $inside,
            'The answer itself was dropped.');
    }

    /** And the optional answers still reach the server. */
    public function test_an_answer_given_in_the_fold_is_still_saved(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->actingAs($this->client)->post(route('client.direct-offers.store'), [
            'professional_id'   => $pro->id,
            'service_single'    => 'Wedding Photography',
            'event_name'        => 'Rivera Wedding',
            'event_date'        => now()->addDays(40)->format('Y-m-d'),
            'organization_type' => 'individual',
            'description'       => 'Full day coverage, two shooters, album included.',
            'budget_min'        => 1500,
            'venue'             => 'The Grand Ballroom, Baltimore, MD',
            'guests'            => 150,
            'fee_agreed'        => 1,
        ]);

        $event = \App\Models\Event::where('title', 'Rivera Wedding')->first();

        $this->assertNotNull($event, 'The direct offer was not created at all.');
        $this->assertSame(150, (int) $event->guest_count);
        $this->assertSame('The Grand Ballroom, Baltimore, MD', $event->location);
    }
}
