<?php

namespace Tests\Feature;

use App\Domain\Support\SupportTopics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's meeting notes, 25 September, item 6.
 *
 * The assistant opened with nine unrelated chips. It now narrows: main
 * category, then a subcategory, then the particular issue, then the answer —
 * his own example being Events → Bidding Request → a proposal problem, and
 * Account → Verification → Identity verification.
 *
 * The two doors matter as much as the tree: "Other / None of these" and
 * "Talk to Staff" are offered at every level, so the categories can never
 * close around somebody whose problem is not one of them.
 */
class SupportTopicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_narrows_through_three_levels(): void
    {
        // His first example.
        $events = SupportTopics::at(['events']);
        $this->assertSame('Events', $events['label']);
        $this->assertArrayHasKey('bidding', $events['children']);

        $bidding = SupportTopics::at(['events', 'bidding']);
        $this->assertSame('Bidding Request', $bidding['label']);

        // The third level is the issues themselves, each carrying its question.
        $issues = $bidding['children'];
        $this->assertGreaterThan(2, count($issues));
        foreach ($issues as $issue) {
            $this->assertNotEmpty($issue['label']);
            $this->assertNotEmpty($issue['ask'], "\"{$issue['label']}\" asks the assistant nothing.");
        }

        // And his second.
        $this->assertSame('Verification', SupportTopics::at(['account', 'verification'])['label']);
        $this->assertContains(
            'Identity verification',
            array_column(SupportTopics::at(['account', 'verification'])['children'], 'label'),
        );
    }

    public function test_every_question_is_a_whole_sentence(): void
    {
        $asks = SupportTopics::questions();

        $this->assertGreaterThan(20, count($asks));

        foreach ($asks as $ask) {
            // The assistant is handed a question, not a heading it has to guess at.
            $this->assertMatchesRegularExpression('/[?.]$/', $ask, "\"{$ask}\" is not a sentence.");
            $this->assertGreaterThan(20, strlen($ask));
        }
    }

    public function test_a_path_that_does_not_exist_is_not_invented(): void
    {
        $this->assertNull(SupportTopics::at(['nonsense']));
        $this->assertNull(SupportTopics::at(['events', 'nonsense']));
    }

    /** Both doors, rendered beside whatever level the client is on. */
    public function test_the_widget_offers_other_and_talk_to_staff(): void
    {
        $widget = file_get_contents(base_path('resources/views/partials/_ai_chatbot_widget.blade.php'));

        $this->assertStringContainsString("label: 'Other / None of these'", $widget);
        $this->assertStringContainsString("label: 'Talk to Staff'", $widget);

        // Added once, to every level, rather than written into the tree.
        $this->assertStringNotContainsString('Talk to Staff', json_encode(SupportTopics::TREE));

        // Talk to Staff goes somewhere real: the support request form.
        $this->assertStringContainsString('SupportTopics::STAFF_FORM', $widget);
        $this->assertNotEmpty(\App\Domain\Forms\FormRegistry::url(SupportTopics::STAFF_FORM));
    }

    public function test_the_old_wall_of_chips_is_gone(): void
    {
        $widget = file_get_contents(base_path('resources/views/partials/_ai_chatbot_widget.blade.php'));

        foreach (['Commission tiers', 'Photo limit', 'Switch mode'] as $chip) {
            $this->assertStringNotContainsString($chip, $widget, "The chip \"{$chip}\" is still offered.");
        }
    }
}
