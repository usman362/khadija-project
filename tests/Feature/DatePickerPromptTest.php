<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every date field on the site opens a picker, and says so.
 *
 * The shared datepicker runs flatpickr with altInput, which hides the real
 * input and shows a formatted one in its place. flatpickr copies the source
 * input's placeholder onto that visible field — and not one of the site's 27
 * date inputs had a placeholder, so every date on every screen rendered as an
 * empty bordered box with no hint that clicking it does anything. Spotted on
 * the Virtual & Hybrid brief; it was true everywhere.
 *
 * The prompt is set once, in the initialiser, rather than on each field, so a
 * date input added tomorrow cannot arrive without one.
 */
class DatePickerPromptTest extends TestCase
{
    public function test_the_shared_datepicker_supplies_a_prompt(): void
    {
        $js = file_get_contents(resource_path('views/partials/_datepicker.blade.php'));

        $this->assertStringContainsString("setAttribute('placeholder'", $js,
            'The datepicker no longer gives its fields a prompt — every date box will render empty.');
        $this->assertStringContainsString('Pick a date and time', $js);
        $this->assertStringContainsString('Pick a date', $js);
    }

    /** It must not overwrite a field that already says something better. */
    public function test_a_field_with_its_own_placeholder_keeps_it(): void
    {
        $js = file_get_contents(resource_path('views/partials/_datepicker.blade.php'));

        $this->assertMatchesRegularExpression(
            "/if \(!input\.getAttribute\('placeholder'\)\)/",
            $js,
            'The default prompt must only fill a gap, never replace a field\'s own wording.',
        );
    }
}
