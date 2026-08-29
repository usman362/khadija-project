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
}
