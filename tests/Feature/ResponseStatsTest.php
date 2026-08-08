<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ResponseStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Response rate and average response time, measured from the messages.
 *
 * The professional portfolio used to state both as facts it did not have: a
 * fully-verified pro was shown "Within 2 hours" and "98%", everyone else
 * "Within 24 hours". Verification says a licence was checked — it says nothing
 * about how fast anyone answers. The client portfolio needed the same two
 * figures, so both pages now read them from the same measured source.
 */
class ResponseStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $them;
    private Conversation $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me     = User::factory()->create();
        $this->them   = User::factory()->create();
        $this->thread = Conversation::create(['type' => 'direct', 'created_by' => $this->them->id]);
    }

    private function say(User $from, string $at): void
    {
        $message = Message::create([
            'conversation_id' => $this->thread->id,
            'sender_id'       => $from->id,
            'recipient_id'    => $from->is($this->me) ? $this->them->id : $this->me->id,
            'body'            => 'x',
        ]);

        // created_at is not fillable, so Eloquent stamps now() on insert and
        // every gap in these tests would be zero. Written straight to the row.
        \Illuminate\Support\Facades\DB::table('messages')
            ->where('id', $message->id)
            ->update(['created_at' => $at]);
    }

    public function test_nobody_has_a_rate_before_anyone_writes_to_them(): void
    {
        // Not 0% and not 100%. Both are claims, and a new account has earned
        // neither — this is exactly what the old hardcoded "98%" got wrong.
        $stats = ResponseStats::for($this->me);

        $this->assertNull($stats['rate']);
        $this->assertNull($stats['hours']);
    }

    public function test_a_reply_is_measured_from_the_message_it_answers(): void
    {
        $this->say($this->them, '2026-08-01 09:00');
        $this->say($this->me,   '2026-08-01 11:00');

        $stats = ResponseStats::for($this->me);

        $this->assertSame(100, $stats['rate']);
        $this->assertSame(2.0, $stats['hours']);
    }

    public function test_an_unanswered_message_pulls_the_rate_down(): void
    {
        $this->say($this->them, '2026-08-01 09:00');
        $this->say($this->me,   '2026-08-01 10:00');

        $second = Conversation::create(['type' => 'direct', 'created_by' => $this->them->id]);
        Message::create([
            'conversation_id' => $second->id,
            'sender_id'       => $this->them->id,
            'recipient_id'    => $this->me->id,
            'body'            => 'still waiting',
        ]);

        $this->assertSame(50, ResponseStats::for($this->me)['rate']);
    }

    public function test_a_run_of_messages_from_one_side_is_one_thing_to_answer(): void
    {
        // Three messages in a row from the other person is one prompt, not
        // three. Counting each would score someone at 33% for replying once
        // to what is plainly a single conversation.
        $this->say($this->them, '2026-08-01 09:00');
        $this->say($this->them, '2026-08-01 09:05');
        $this->say($this->them, '2026-08-01 09:10');
        $this->say($this->me,   '2026-08-01 10:00');

        $stats = ResponseStats::for($this->me);

        $this->assertSame(1, $stats['prompts']);
        $this->assertSame(100, $stats['rate']);
        // Measured from the FIRST of the run — that is when they were waiting.
        $this->assertSame(1.0, $stats['hours']);
    }

    public function test_the_users_own_message_is_never_a_prompt_to_themselves(): void
    {
        // Opening a conversation is not answering one. With nothing back,
        // there is still nothing to have a rate about.
        $this->say($this->me, '2026-08-01 09:00');

        $this->assertSame(0, ResponseStats::for($this->me)['prompts']);
        $this->assertNull(ResponseStats::for($this->me)['rate']);
    }

    public function test_a_reply_that_arrives_after_theirs_still_needs_answering(): void
    {
        // The opener does not exempt them from what came next: the other
        // side's 10:00 message is an open prompt, so the rate is 0%, not null.
        $this->say($this->me,   '2026-08-01 09:00');
        $this->say($this->them, '2026-08-01 10:00');

        $stats = ResponseStats::for($this->me);

        $this->assertSame(1, $stats['prompts']);
        $this->assertSame(0, $stats['rate']);
    }

    public function test_the_average_is_across_every_answered_message(): void
    {
        $this->say($this->them, '2026-08-01 09:00');
        $this->say($this->me,   '2026-08-01 10:00');   // 1h
        $this->say($this->them, '2026-08-01 12:00');
        $this->say($this->me,   '2026-08-01 15:00');   // 3h

        $this->assertSame(2.0, ResponseStats::for($this->me)['hours']);
    }

    public function test_the_wording_scales_with_the_gap(): void
    {
        $this->assertSame('—', ResponseStats::describe(null));
        $this->assertSame('30 minutes', ResponseStats::describe(0.5));
        $this->assertSame('1.5 hours', ResponseStats::describe(1.5));
        $this->assertSame('3 days', ResponseStats::describe(72));
    }

    public function test_the_professional_page_no_longer_invents_these(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/Public/ProfessionalProfileShowController.php')
        );

        foreach (['Within 2 hours', 'Within 24 hours', "'98%'"] as $invented) {
            $this->assertStringNotContainsString($invented, $controller);
        }
    }
}
