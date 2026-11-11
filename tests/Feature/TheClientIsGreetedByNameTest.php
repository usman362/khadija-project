<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The greeting on the client topbar, and the row it has to live in.
 *
 * It read "Welcome back, " and then an ellipsis. The banner shares one line
 * with a search field, a Post an Event button and the account controls, and
 * the search was `flex: 0 0 360px` — it kept its width whatever else needed
 * it, so the greeting took the whole shortfall. At 1440px the h1 had 156px for
 * 263px of "Welcome back, Dana Whitfield!", which cut off the one word the
 * sentence exists for.
 *
 * The obvious fix, letting the search shrink, is the arrangement Sir Peter
 * complained about on 29 September, and TheBannerSearchIsOneWidthTest caught
 * it. So neither side yields differently: the greeting uses the first name,
 * which is what the influencer dashboard has always done, and the search is
 * 60px narrower. One width everywhere, and the words fit inside the rest.
 */
class TheClientIsGreetedByNameTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $name): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client', 'name' => $name]);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    public function test_the_greeting_uses_the_first_name(): void
    {
        $html = $this->actingAs($this->client('Dana Whitfield'))
            ->get(route('client.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Welcome back, Dana!', $html);
        $this->assertStringNotContainsString('Welcome back, Dana Whitfield!', $html);
    }

    public function test_a_one_word_name_still_reads_as_a_greeting(): void
    {
        // Str::before returns the whole string when the separator is absent,
        // which is the behaviour this depends on. An implementation that split
        // and took [0] would be fine too; one that assumed a space would not.
        $html = $this->actingAs($this->client('Dana'))
            ->get(route('client.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Welcome back, Dana!', $html);
    }

    /**
     * And the fix did not undo the one before it.
     *
     * My first attempt let the search shrink, which read as the obvious
     * answer and was the exact arrangement Sir Peter complained about on 29
     * September: the search was a different size on every page because the
     * title took what it wanted. TheBannerSearchIsOneWidthTest exists to stop
     * that coming back and it caught this. The search keeps a width of its
     * own; it is simply 60px smaller, which is a width somebody chose.
     */
    public function test_the_search_still_has_a_width_of_its_own(): void
    {
        $layout = file_get_contents(base_path('resources/views/layouts/client.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.cl-banner-search \{ flex: 0 0 \d+px/',
            $layout,
            'the search can be pushed about by the page title again',
        );
    }
}
