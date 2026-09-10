<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three request forms are numbered, and numbered the same way.
 *
 * Sir Peter, 2026-09-10, sending recreated versions of our screens: "Make all
 * of these changes so that we look more like Chatgpt suggestions… use the
 * colors/shades as they are presented."
 *
 * Every one of those mockups numbers the sections down the page. Ours numbered
 * nothing on the emergency and direct forms, and each of the three drew its
 * headings differently — small uppercase with an icon on one, a tinted bar with
 * a tag on another, a plain heading on the third. Three ways of saying "this is
 * a section" on three pages that are the same task.
 *
 * The wizard is left alone: its sections are steps, and the stepper across the
 * top already numbers them.
 */
class RequestFormsLookLikeOneFamilyTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** @return array<string, string> */
    private function onePageForms(): array
    {
        return [
            'ER' => route('client.esr.create'),
            'DR' => route('client.direct-offers.create'),
        ];
    }

    public function test_the_sections_are_numbered_from_one(): void
    {
        foreach ($this->onePageForms() as $flow => $url) {
            $html = $this->actingAs($this->client())->get($url)->assertOk()->getContent();

            preg_match_all('/<span class="fsec-n">(\d+)<\/span>/', $html, $m);
            $numbers = array_map('intval', $m[1]);

            $this->assertNotEmpty($numbers, "The {$flow} form numbers none of its sections.");
            $this->assertSame(
                range(1, count($numbers)),
                $numbers,
                "The {$flow} form's section numbers do not run 1, 2, 3 down the page.",
            );
        }
    }

    /** One heading style. Three was how they stopped looking like one product. */
    public function test_no_form_draws_its_own_section_heading(): void
    {
        $strays = [];

        foreach (['esr/create' => 'esr-sec-h', 'direct-offers/create' => 'do-sec-hd'] as $view => $old) {
            $html = file_get_contents(resource_path("views/client/{$view}.blade.php"));

            if (str_contains($html, 'class="' . $old . '"')) {
                $strays[] = $view;
            }
        }

        $this->assertSame([], $strays, 'These forms still draw their own section headings: ' . implode(', ', $strays));
    }
}
