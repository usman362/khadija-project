<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Issue 4 of the Professional IA Consolidation Plan — the Rule R29 sweep the
 * plan says can run in parallel with everything else.
 *
 * Five labels claimed the platform did the work. In each case the behaviour
 * underneath was already rules-based and user-driven; only the words were
 * wrong, which is exactly what R29 forbids:
 *
 *   Smart Assistant             already gone before this pass
 *   Instant Proposal Generator  removed — it generated nothing at all
 *   Follow-Up Automation        → Follow-Up Reminder; the button opens
 *                                 Messages and the professional writes it
 *   Suggestion (Messages)       → Message Template; "Use" drops a fixed piece
 *                                 of text into the box
 *   Assist (Threads)            → Guided Workflow
 *
 * The check ignores Blade comments on purpose — the notes explaining each
 * change quote the old wording, and a developer reading the file is not a
 * client reading the page.
 */
class ProfessionalWordingIsRulesBasedTest extends TestCase
{
    private const RETIRED = [
        'Smart Assistant',
        'Instant Proposal Generator',
        'Follow-Up Automation',
    ];

    public function test_no_retired_label_is_visible_on_a_professional_page(): void
    {
        $offenders = [];

        foreach ($this->professionalViews() as $path) {
            $visible = $this->withoutComments(file_get_contents($path));

            foreach (self::RETIRED as $phrase) {
                if (str_contains($visible, $phrase)) {
                    $offenders[] = basename($path) . " — {$phrase}";
                }
            }
        }

        $this->assertEmpty($offenders, "wording that claims the platform does the work:\n" . implode("\n", $offenders));
    }

    public function test_the_follow_up_card_says_the_professional_writes_it(): void
    {
        $page = $this->withoutComments(
            file_get_contents(resource_path('views/professional/bid-intelligence/index.blade.php'))
        );

        $this->assertStringContainsString('Follow-Up Reminder', $page);
        $this->assertStringNotContainsString('Send Follow-Up Email', $page, 'nothing is sent for them');
    }

    public function test_the_message_helper_is_called_a_template(): void
    {
        // "Use" fills the compose box with a fixed piece of text. That is a
        // template; calling it a suggestion implied something worked it out.
        $page = $this->withoutComments(
            file_get_contents(resource_path('views/professional/chat/index.blade.php'))
        );

        $this->assertStringContainsString('Message Template', $page);
        $this->assertStringNotContainsString('<b>Suggestion</b>', $page);
    }

    /** Blade comments are for developers; they are never rendered. */
    private function withoutComments(string $blade): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $blade) ?? $blade;
    }

    /** @return list<string> */
    private function professionalViews(): array
    {
        $found = [];
        $dir = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views/professional'))
        );

        foreach ($dir as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }
}
