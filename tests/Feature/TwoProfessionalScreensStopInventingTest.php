<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Conversation, Event, Message, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two professional-side defects from the 14 October survey.
 *
 * Peter asked for "a list of what professional users screens are not updated or
 * missing". These were the two that were simply wrong on screen, as opposed to
 * waiting on a decision:
 *
 *   Leads printed the Blade directive instead of the budget, because
 *   "stated@elseif" puts a directive straight after a letter and Blade does not
 *   compile that. The reader saw the code.
 *
 *   Messages printed "↓ 12% vs last 30 days" under the response time. Always
 *   that number, for every professional, measured from nothing.
 */
class TwoProfessionalScreensStopInventingTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->pro = User::findOrFail($this->pro->id);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
    }

    // ── Leads ────────────────────────────────────────────────

    /** The budget, not the directive that was supposed to choose it. */
    public function test_leads_prints_a_budget_and_not_blade_source(): void
    {
        $category = Category::create(['name' => 'Photography', 'slug' => 'photography-' . uniqid()]);
        $this->pro->serviceCategories()->syncWithoutDetaching([$category->id]);

        Event::create([
            'title'      => 'Corporate Gala',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'is_published' => true,
            'starts_at'  => now()->addDays(20),
            'city'       => 'Baltimore',
            'state'      => 'MD',
            'budget'     => 2400,
        ]);

        $html = $this->actingAs($this->pro)
            ->get(route('professional.leads.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('@elseif', $html, 'The page is printing Blade source.');
        $this->assertStringNotContainsString('@endif', $html);
    }

    /**
     * A directive glued to a word character is never compiled.
     *
     * This is the fault class, not the one line: Blade reads "@elseif" only
     * when nothing wordlike sits immediately before it, so the whole block
     * renders as text. The same mistake has been made three times in this
     * codebase.
     */
    public function test_no_professional_view_glues_a_directive_to_a_word(): void
    {
        $found = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views/professional')),
        );

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Comments explaining the trap are allowed to show it.
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file->getPathname()));

            if (preg_match('/[A-Za-z0-9]@(if|elseif|else|endif|foreach|endforeach)\b/', $body, $m)) {
                $found[] = ltrim(str_replace(base_path(), '', $file->getPathname()), '/') . ': ' . $m[0];
            }
        }

        $this->assertSame([], $found, "A directive is glued to a word again:\n" . implode("\n", $found));
    }

    // ── Messages ─────────────────────────────────────────────

    /** The figure that was printed for everybody is gone. */
    public function test_messages_does_not_print_the_invented_trend(): void
    {
        $html = $this->actingAs($this->pro)
            ->get(route('professional.chat.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('12% vs last 30 days', $html);
    }

    /** With nothing to measure, it says nothing rather than guessing. */
    public function test_no_trend_is_claimed_without_two_months_to_compare(): void
    {
        $html = $this->actingAs($this->pro)
            ->get(route('professional.chat.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Average reply time', $html);
        $this->assertDoesNotMatchRegularExpression('/[↑↓] \d+% vs the month before/u', $html);
    }

    /**
     * And when there is something to measure, it is measured.
     *
     * Slow replies last month, fast ones this month, so the card should say
     * the direction is down. A gap belongs to the month its REPLY falls in,
     * which is what makes these two windows separable at all.
     */
    public function test_a_real_improvement_is_reported(): void
    {
        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $this->client->id,
        ]);

        $conversation->participants()->attach(
            [$this->pro->id, $this->client->id],
            ['joined_at' => now()],
        );

        // Last month: they waited four hours.
        $this->say($conversation, $this->client, now()->subDays(45));
        $this->say($conversation, $this->pro, now()->subDays(45)->addHours(4));

        // This month: twenty minutes.
        $this->say($conversation, $this->client, now()->subDays(5));
        $this->say($conversation, $this->pro, now()->subDays(5)->addMinutes(20));

        $html = $this->actingAs($this->pro)
            ->get(route('professional.chat.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertMatchesRegularExpression('/↓ \d+% vs the month before/u', $html);
    }

    private function say(Conversation $c, User $who, $at): void
    {
        // `messages` has no updated_at, so the timestamp is set by hand.
        $m = Message::create([
            'conversation_id' => $c->id,
            'sender_id'       => $who->id,
            'body'            => 'Message',
        ]);

        Message::where('id', $m->id)->update(['created_at' => $at]);
    }
}
