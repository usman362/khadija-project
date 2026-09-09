<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four claim tiles at the top of /packages became one line of three.
 *
 * "One Contract — one point of contact" came out because the same page says it
 * again further down, in "Why Package Bundles?" as "One Contract — one payment,
 * one point of contact". A page that makes the same promise twice in different
 * words reads as padding, and the second one is where a reader who is actually
 * asking "why a bundle?" will look.
 *
 * The other three are a sentence now rather than four bordered tiles. Four
 * boxes for four short phrases was more furniture than the phrases needed.
 */
class PackagesHeaderClaimsTest extends TestCase
{
    use RefreshDatabase;

    private function topBand(): string
    {
        $html = $this->get('/packages')->assertOk()->getContent();

        $at = strpos($html, '<p class="pk-props">');
        $this->assertNotFalse($at, 'the claim line is not on the page');

        return substr($html, $at, 400);
    }

    public function test_the_three_claims_are_there(): void
    {
        $band = $this->topBand();

        foreach (['Professionally coordinated', 'Better value', 'Customizable'] as $claim) {
            $this->assertStringContainsString($claim, $band, $claim);
        }
    }

    /**
     * Each claim is its own element, which is what stops a line break falling
     * inside one.
     *
     * As a single sentence it wrapped to "Better value through bundle /
     * pricing" — a phrase split down the middle, which is harder to read than
     * the four tiles it replaced. Measured in the browser at 1440 and at
     * 375: three lines, right edges level, nothing broken mid-phrase.
     */
    public function test_each_claim_is_its_own_element(): void
    {
        $this->assertSame(3, substr_count($this->topBand(), '<span>'));
    }

    /** And the one that was duplicated is not. */
    public function test_one_contract_is_not_claimed_twice_on_the_page(): void
    {
        $this->assertStringNotContainsString('One point of contact', $this->topBand());
    }

    /**
     * The tiles are gone, not merely restyled — a leftover .pk-prop would mean
     * the old markup is still rendering somewhere on the page.
     */
    public function test_no_claim_tiles_remain(): void
    {
        $html = $this->get('/packages')->assertOk()->getContent();

        $this->assertSame(0, substr_count($html, 'class="pk-prop"'));
    }

    /**
     * "Why Package Bundles?" keeps its own One Contract line. That is the
     * place the claim belongs — this is not a rule against saying it, only
     * against saying it twice.
     */
    public function test_the_why_bundles_panel_still_explains_one_contract(): void
    {
        $this->get('/packages')->assertOk()->assertSee('One Contract');
    }

    /**
     * The hero is one centred column: heading, subtitle, then the claims.
     *
     * They used to sit opposite the heading in the hero's right-hand slot,
     * where a short column stranded against a wide gap read as a stray
     * element. Nothing is opposite anything now, so nothing can be stranded.
     */
    public function test_the_hero_is_one_centred_column(): void
    {
        $html = $this->get(route('public.packages'))->assertSuccessful()->getContent();

        $this->assertMatchesRegularExpression(
            '/\.pk-hero \{[^}]*flex-direction:\s*column/',
            $html,
            'The hero still puts the claims opposite the heading.',
        );

        $this->assertMatchesRegularExpression('/\.pk-hero \{[^}]*text-align:\s*center/', $html);

        // The subtitle is capped for readability, so it needs telling to sit
        // in the middle — a capped block does not centre itself.
        $this->assertMatchesRegularExpression('/\.pk-hero p \{[^}]*margin:\s*0 auto/', $html);
    }

    /** And the three claims run along one line, centred under it. */
    public function test_the_claims_read_as_one_centred_line(): void
    {
        $html = $this->get(route('public.packages'))->getContent();

        $this->assertMatchesRegularExpression(
            '/\.pk-hero p\.pk-props \{[^}]*justify-content:\s*center/',
            $html,
        );

        // Each claim whole — an earlier version broke one into
        // "Better value through bundle / pricing".
        $this->assertMatchesRegularExpression(
            '/\.pk-hero p\.pk-props span \{[^}]*white-space:\s*nowrap/',
            $html,
        );
    }
}
