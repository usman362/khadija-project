<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration that takes the dash out of text the seeders wrote long ago —
 * and leaves alone anything a person wrote.
 */
class SeededRecordsLoseTheirDashTest extends TestCase
{
    use RefreshDatabase;

    private function migration()
    {
        return require database_path('migrations/2026_09_10_190000_drop_spaced_dash_from_seeded_records.php');
    }

    private function event(string $title, ?string $description = null): int
    {
        $u = User::factory()->create();

        return DB::table('events')->insertGetId([
            'title' => $title, 'description' => $description, 'status' => 'published', 'is_published' => true,
            'client_id' => $u->id, 'created_by' => $u->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_a_seeded_title_is_renamed_whole(): void
    {
        $id = $this->event('Corporate Gala — Full Production');

        $this->migration()->up();

        $this->assertSame('Corporate Gala: Full Production', DB::table('events')->where('id', $id)->value('title'));
    }

    /** A client's own title that merely starts the same way is theirs. */
    public function test_a_persons_own_title_is_left_alone(): void
    {
        $id = $this->event('Corporate Gala — Full Production 2027, our version');

        $this->migration()->up();

        $this->assertSame('Corporate Gala — Full Production 2027, our version', DB::table('events')->where('id', $id)->value('title'));
    }

    public function test_the_seeded_sentence_inside_a_description_is_changed_and_nothing_else(): void
    {
        $id = $this->event('Garden', 'Our note first. Seeking a photographer for a 150-guest garden wedding — ceremony, reception, family portraits and candids. Our note last — kept.');

        $this->migration()->up();

        $this->assertSame(
            'Our note first. Seeking a photographer for a 150-guest garden wedding, ceremony, reception, family portraits and candids. Our note last — kept.',
            DB::table('events')->where('id', $id)->value('description'),
        );
    }

    /** JSON stores the dash as —; a text search would never have found it. */
    public function test_a_seeded_value_inside_a_form_payload_is_changed(): void
    {
        $u = User::factory()->create();
        $id = DB::table('form_submissions')->insertGetId([
            'reference' => 'T-1', 'form_key' => 'x', 'submitted_by' => $u->id, 'submitted_role' => 'client',
            'payload' => json_encode(['details' => 'Reported in error — the message was from a colleague, not a stranger.', 'n' => 3]),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->migration()->up();

        $payload = json_decode(DB::table('form_submissions')->where('id', $id)->value('payload'), true);
        $this->assertSame('Reported in error: the message was from a colleague, not a stranger.', $payload['details']);
        $this->assertSame(3, $payload['n']);
    }

    public function test_the_disclaimer_changes_punctuation_only(): void
    {
        $id = DB::table('policy_pages')->insertGetId([
            'slug' => 'platform-disclaimer', 'title' => 'Platform Disclaimer', 'is_active' => true,
            // Exactly as the seeder stores it: a line break straight after the dash.
            'content' => "<p>GigResource makes <strong>no guarantees to any user regarding membership outcomes</strong> —\nincluding, but not limited to, the number of leads.</p>",
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertSame(
            "<p>GigResource makes <strong>no guarantees to any user regarding membership outcomes</strong>,\nincluding, but not limited to, the number of leads.</p>",
            DB::table('policy_pages')->where('id', $id)->value('content'),
        );
    }

    public function test_down_puts_it_back(): void
    {
        $id = $this->event('Wedding Planner — Full Service');

        $m = $this->migration();
        $m->up();
        $m->down();

        $this->assertSame('Wedding Planner — Full Service', DB::table('events')->where('id', $id)->value('title'));
    }
}
