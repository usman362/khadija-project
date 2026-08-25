<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The date picker must be readable on the theme the site actually runs.
 *
 * `_datepicker.blade.php` shipped as a DARK theme with a short light
 * "override" bolted on: eleven dark rules, and a light block patching four
 * selectors. The site is light and stamps data-theme="light" by default, so
 * the patch was what ran — and it did not reach the two rules that set white
 * text with !important:
 *
 *     .flatpickr-day.flatpickr-disabled { color: rgba(255,255,255,.20) !important }
 *     .flatpickr-time input             { color: #fff !important }
 *
 * White on white. On the Emergency Request form, thirty days carry
 * `flatpickr-disabled` — every date before the earliest one allowed — and all
 * thirty rendered invisible. Opening the picker showed three empty rows above
 * the first pickable date and read as a broken calendar. The time field showed
 * as a stray floating fragment for the same reason.
 *
 * Asserted against the file because this is CSS: there is no request to make
 * that would prove a colour, and the defect was one literal in one rule.
 */
class DatepickerThemeTest extends TestCase
{
    /**
     * The stylesheet with its comments stripped.
     *
     * The file's own header quotes the two broken rules verbatim, to record
     * what went wrong. Searching the raw text finds the quotation before it
     * finds the rule, so the comments come out first — a note about a bug is
     * not the bug.
     */
    private function css(): string
    {
        $raw = file_get_contents(resource_path('views/partials/_datepicker.blade.php'));

        return preg_replace('#/\*.*?\*/#s', '', $raw);
    }

    /** The exact rules that made past dates and the clock invisible. */
    public function test_no_white_text_is_set_outside_a_dark_theme_block(): void
    {
        $css = $this->css();

        // Everything from the first dark-theme selector on is allowed to use
        // white; what matters is the base, which is what a light page gets.
        $base = substr($css, 0, strpos($css, '[data-theme="dark"]') ?: strlen($css));

        foreach (['.flatpickr-day.flatpickr-disabled', '.flatpickr-time input'] as $selector) {
            $this->assertStringNotContainsString(
                'rgba(255,255,255',
                $this->ruleFor($base, $selector),
                "{$selector} still sets a white-ish colour on the light base."
            );
        }

        $this->assertStringNotContainsString('color: #fff !important', $base);
        $this->assertStringNotContainsString('color: rgba(255, 255, 255, .20)', $base);
    }

    /** Light is the base, because light is what the site serves. */
    public function test_the_base_theme_is_light_and_dark_is_the_override(): void
    {
        $css = $this->css();

        $darkAt  = strpos($css, '[data-theme="dark"] .flatpickr-calendar');
        $lightAt = strpos($css, '.flatpickr-calendar {');

        $this->assertNotFalse($lightAt, 'No base .flatpickr-calendar rule.');
        $this->assertNotFalse($darkAt, 'No dark override — dark mode would be unstyled.');
        $this->assertLessThan($darkAt, $lightAt, 'Dark must come after light, or it cannot override it.');

        // The old light patch keyed off [data-theme="light"] and so did
        // nothing on any layout that does not stamp it.
        $this->assertStringNotContainsString('[data-bs-theme="light"]', $css);
    }

    /**
     * A date you cannot pick still has to be READABLE — the client is reading
     * the grid to find one they can.
     */
    public function test_disabled_days_are_greyed_rather_than_erased(): void
    {
        $rule = $this->ruleFor($this->css(), '.flatpickr-day.flatpickr-disabled');

        $this->assertStringNotContainsString('visibility: hidden', $rule);
        $this->assertStringNotContainsString('display: none', $rule);
        $this->assertMatchesRegularExpression('/color:\s*var\(--text-muted/', $rule);
    }

    /** Both themes have to style the clock, or one of them hides it. */
    public function test_the_time_picker_is_coloured_in_both_themes(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('.flatpickr-time input,', $css);
        $this->assertStringContainsString('[data-theme="dark"] .flatpickr-time input,', $css);
    }

    /** A picker must not need a CSS variable to be readable. */
    public function test_every_token_colour_carries_a_literal_fallback(): void
    {
        preg_match_all('/var\(--[a-z-]+([^)]*)\)/', $this->css(), $m);

        $this->assertNotEmpty($m[1]);

        foreach ($m[1] as $i => $fallback) {
            $this->assertNotSame('', trim($fallback), "Token #{$i} has no fallback value.");
        }
    }

    private function ruleFor(string $css, string $selector): string
    {
        $at = strpos($css, $selector);
        $this->assertNotFalse($at, "Selector {$selector} is missing.");

        $open = strpos($css, '{', $at);

        return substr($css, $at, strpos($css, '}', $open) - $at);
    }
}
