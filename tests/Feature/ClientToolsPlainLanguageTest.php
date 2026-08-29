<?php

namespace Tests\Feature;

use App\Domain\AiFeatures\AiToolCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A client tool page must not read like it was written for a developer.
 *
 * Three tools carried a tile reading "No API" over the label "Built-in", and
 * the Language tool advertised "Fuzzy / Match". That is how the thing is built,
 * in our words, on a page a client reads. Ali called it unprofessional and he
 * was right.
 *
 * This checks the words a client can actually SEE — script bodies are stripped
 * first, because `payload`, `json` and the rest are variable names nobody reads.
 */
class ClientToolsPlainLanguageTest extends TestCase
{
    use RefreshDatabase;

    /** Words that mean nothing to a client booking a wedding. */
    private const JARGON = [
        'No API', 'Built-in', 'Fuzzy', 'endpoint', 'nullable',
        'localhost', 'schema', 'migration', 'boolean', 'null',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @return array<string, array{0: string}> */
    public static function clientTools(): array
    {
        $rows = [];
        foreach (AiToolCatalog::all() as $tool) {
            if (in_array($tool['audience'], ['client', 'both'], true) && $tool['status'] === 'live' && $tool['route']) {
                $rows[$tool['name']] = [$tool['route']];
            }
        }

        return $rows;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('clientTools')]
    public function test_a_client_tool_page_speaks_plain_english(string $route): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach (['manual', 'semi', 'maximum'] as $level) {
            $html = $this->actingAs($admin->fresh())
                ->get(route($route, ['preview' => $level]))
                ->assertSuccessful()
                ->getContent();

            // Only what a client can read: no scripts, no styles, no attributes.
            $visible = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
            $visible = strip_tags($visible);

            foreach (self::JARGON as $word) {
                $this->assertStringNotContainsString(
                    $word,
                    $visible,
                    "\"{$word}\" is visible on {$route} at the {$level} level. That is our word, not the client's."
                );
            }
        }
    }

    /**
     * Nothing should be left stranded when a heading is removed.
     *
     * The page header on Best Match was an icon beside a title. The heading
     * sweep moved title and subtitle into the page banner and took them out of
     * here — but left the icon, so the page opened with a purple blob floating
     * alone above it, next to nothing.
     *
     * A decorative icon container that renders with no text beside it is the
     * shape of that mistake, so this looks for one.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('clientTools')]
    public function test_no_icon_is_left_stranded_without_its_heading(string $route): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $html = $this->actingAs($admin->fresh())
            ->get(route($route, ['preview' => 'maximum']))
            ->assertSuccessful()
            ->getContent();

        // Regex cannot see nesting, so this walks the real DOM: find the icon
        // wrapper, step up to its container, and read the text in it.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $icons = $xpath->query("//*[contains(@class, 'head-ico')]");

        foreach ($icons as $icon) {
            $container = $icon->parentNode;
            $text = trim(preg_replace('/\s+/', ' ', $container->textContent ?? ''));

            $this->assertNotSame(
                '',
                $text,
                "{$route} renders a header icon with no heading beside it — the text was removed and the icon was left behind."
            );
        }
    }
}
