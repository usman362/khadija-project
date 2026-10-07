<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Presence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter asked for status dots on avatars.
 *
 * The chat carried a note for months saying this could not be done honestly:
 * "nothing records whether someone is online, and a green dot we cannot back
 * is a claim." TouchLastActive has stamped users.last_active_at on every
 * signed-in request since, so the dot has something behind it — and the note
 * was simply out of date, which is its own kind of fault.
 *
 * Three states, not two. The third is the one that matters: an account nobody
 * has ever measured is not offline, and a grey dot would say we checked.
 */
class ThePresenceDotIsBackedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function seenAt($when): User
    {
        return User::factory()->create(['last_active_at' => $when]);
    }

    public function test_someone_here_a_moment_ago_is_here(): void
    {
        $u = $this->seenAt(now()->subMinute());

        $this->assertSame(Presence::HERE, Presence::stateOf($u));
        $this->assertTrue(Presence::isHere($u));
        $this->assertSame('Online now', Presence::label($u));
        $this->assertSame(Presence::COLOURS[Presence::HERE], Presence::colour($u));
    }

    /**
     * The reason the window is not five minutes.
     *
     * TouchLastActive writes at most every five minutes, so someone sitting
     * on a page carries a stamp up to five minutes old. A five-minute window
     * blinks them out between writes — which is what the three hand-written
     * copies of this rule did before it moved here.
     */
    public function test_the_window_outlasts_the_interval_that_writes_it(): void
    {
        // TouchLastActive::EVERY_MINUTES is 5 and private, so it is named
        // here rather than read. If it ever rises, this has to rise with it.
        $this->assertGreaterThan(
            5,
            Presence::WINDOW_MINUTES,
            'Presence goes stale faster than it is written, so an active person flickers out.'
        );

        $this->assertTrue(Presence::isHere($this->seenAt(now()->subMinutes(5)->subSeconds(30))));
    }

    public function test_someone_gone_a_while_is_away_and_it_says_how_long(): void
    {
        $u = $this->seenAt(now()->subHours(3));

        $this->assertSame(Presence::AWAY, Presence::stateOf($u));
        $this->assertFalse(Presence::isHere($u));
        $this->assertStringContainsString('Last active', (string) Presence::label($u));
        $this->assertStringContainsString('hours ago', (string) Presence::label($u));
    }

    /** Never measured is not offline. No dot, no words, no claim. */
    public function test_someone_never_recorded_gets_no_dot(): void
    {
        $u = $this->seenAt(null);

        $this->assertSame(Presence::UNKNOWN, Presence::stateOf($u));
        $this->assertNull(Presence::colour($u));
        $this->assertNull(Presence::label($u));
    }

    /**
     * A narrowed eager load leaves the column out, and a model without it
     * answers null — which would read as "never here" for somebody sitting
     * on the page. Unknown is the honest answer to a question not asked.
     */
    public function test_a_column_that_was_not_loaded_is_unknown_not_offline(): void
    {
        $this->seenAt(now()->subMinute());

        $thin = User::query()->select('id', 'name')->first();

        $this->assertSame(Presence::UNKNOWN, Presence::stateOf($thin));
        $this->assertNull(Presence::colour($thin));
    }

    public function test_nobody_at_all_is_unknown(): void
    {
        $this->assertSame(Presence::UNKNOWN, Presence::stateOf(null));
        $this->assertNull(Presence::colour(null));
        $this->assertNull(Presence::label(null));
    }

    /** The component draws the colour and says what it means. */
    public function test_the_dot_carries_its_meaning_in_words(): void
    {
        $here = $this->seenAt(now()->subMinute());

        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-presence-dot :user="$user" />', ['user' => $here]
        );

        $this->assertStringContainsString(Presence::COLOURS[Presence::HERE], $html);
        $this->assertStringContainsString('aria-label="Online now"', $html);

        $none = \Illuminate\Support\Facades\Blade::render(
            '<x-presence-dot :user="$user" />', ['user' => $this->seenAt(null)]
        );

        $this->assertStringNotContainsString('pres-dot', $none, 'A dot was drawn for somebody nobody has measured.');
    }

    /** My Professionals shows it, which is where it was already half-built. */
    public function test_my_professionals_shows_the_dot(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $pro = User::factory()->create(['primary_role' => 'professional', 'last_active_at' => now()]);
        $pro->assignRole('professional');
        $client->savedProfessionals()->attach($pro->id);

        $html = $this->actingAs($client->fresh())->get('/client/my-professionals')
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('pres-dot', $html);
        $this->assertStringContainsString('Online now', $html);

        // And the hand-written rule it replaced is gone.
        $this->assertStringNotContainsString('mp-on', $html);
    }
}
