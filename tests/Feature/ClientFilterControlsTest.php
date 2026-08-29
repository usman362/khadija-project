<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter controls that were decoration.
 *
 * Spending, Proposals and Reviews each carried a working search box beside
 * buttons labelled "Filters" and "Date Range" — <button> elements with no form
 * and no handler. One control working and the ones next to it doing nothing is
 * worse than none of them working: it reads as the page being broken.
 *
 * Events was already a real GET form and is left as it was.
 */
class ClientFilterControlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        return $user->fresh();
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function filterPages(): array
    {
        return [
            'spending'  => ['client.spending.index',  'ea'],
            'proposals' => ['client.proposals.index', 'pr'],
            'reviews'   => ['client.reviews.index',   'rv'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterPages')]
    public function test_the_date_range_is_a_real_form(string $route, string $prefix): void
    {
        $html = $this->actingAs($this->client())
            ->get(route($route))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('name="from"', $html);
        $this->assertStringContainsString('name="to"', $html);

        // And nothing is left that only looks like a control.
        $this->assertStringNotContainsString('<button class="' . $prefix . '-tool-btn"><svg', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterPages')]
    public function test_the_dates_are_echoed_back(string $route): void
    {
        $from = now()->subDays(30)->toDateString();
        $to   = now()->toDateString();

        $html = $this->actingAs($this->client())
            ->get(route($route, ['from' => $from, 'to' => $to]))
            ->assertSuccessful()
            ->getContent();

        // A page that forgets what was asked for reads as a filter that failed.
        $this->assertStringContainsString('value="' . $from . '"', $html);
        $this->assertStringContainsString('value="' . $to . '"', $html);
    }

    /** Backwards, nonsense and empty must not 500 — a filter is user input. */
    #[\PHPUnit\Framework\Attributes\DataProvider('filterPages')]
    public function test_awkward_input_does_not_break_the_page(string $route): void
    {
        $cases = [
            ['from' => now()->toDateString(), 'to' => now()->subDays(30)->toDateString()],
            ['from' => 'not-a-date', 'to' => ''],
            ['from' => '', 'to' => ''],
            ['q' => str_repeat('x', 300)],
        ];

        foreach ($cases as $query) {
            $this->actingAs($this->client())
                ->get(route($route, $query))
                ->assertSuccessful();
        }
    }
}
