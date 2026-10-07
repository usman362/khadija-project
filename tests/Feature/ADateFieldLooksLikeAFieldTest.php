<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The box a person sees on a date field is not the one in the markup.
 *
 * Flatpickr's altInput hides the real input and puts a text input in front
 * of it, and its default class for that replacement is "form-control input"
 * — Bootstrap's names, which this site has never defined. So every date
 * field on every client screen rendered with the browser's own inset border,
 * no padding and no radius, while the CSS written for `input[type="date"]`
 * sat on a hidden element doing nothing at all. It is the same fault as the
 * undeclared CSS variables: a rule that is correct, and reaches nothing.
 *
 * A field that carries its own class keeps it, so a form styled for its page
 * still looks like its page. A field with none gets the house style.
 */
class ADateFieldLooksLikeAFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();
    }

    private function page(string $path): string
    {
        return $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();
    }

    /** The replacement is given a class this site actually styles. */
    public function test_the_visible_box_is_not_handed_bootstraps_class_names(): void
    {
        $html = $this->page('/client/calendar?tab=availability');

        $this->assertStringContainsString('altInputClass', $html, 'The picker is back on its default class.');

        $this->assertStringNotContainsString(
            "'form-control input'",
            $html,
            'Bootstrap class names are back, and this site does not define them.'
        );
    }

    /** And that class has a style, rather than being a name nothing answers. */
    public function test_the_house_style_for_a_bare_date_field_exists(): void
    {
        $html = $this->page('/client/calendar?tab=availability');

        $this->assertMatchesRegularExpression(
            '/\.dp-input\s*\{[^}]*border:[^}]*\}/s',
            $html,
            'dp-input is handed out but nothing defines it.'
        );
    }

    /**
     * A page that styles its own fields keeps them. The ESR form's date
     * carries esr-input, and that is what the client must still see.
     */
    public function test_a_field_with_its_own_class_keeps_it(): void
    {
        $html = $this->page('/client/esr/create');

        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="datetime-local"[^>]*class="[^"]*esr-input/s',
            $html,
            'The ESR date field lost the class its page styles.'
        );

        // The picker hands the field's own class on rather than replacing it.
        $this->assertStringContainsString("ownClass !== '' ? ownClass : 'dp-input'", $html);
    }

    /** The range form names its fields, instead of two anonymous boxes. */
    public function test_the_range_form_says_which_day_is_which(): void
    {
        $html = $this->page('/client/calendar?tab=availability');

        $this->assertMatchesRegularExpression('/<label for="av-from">\s*First day\s*<\/label>/', $html);
        $this->assertMatchesRegularExpression('/<label for="av-to">\s*Last day\s*<\/label>/', $html);

        // And its own class, which is what reaches the visible box.
        $this->assertMatchesRegularExpression('/id="av-from"[^>]*class="av-input"/', $html);
        $this->assertMatchesRegularExpression('/\.av-input[^{]*\{[^}]*border:/s', $html);
    }
}
