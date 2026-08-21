<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B4, resolved 2026-08-22: the platform is AI-assisted and must SAY so.
 *
 * R29's old blanket "no AI anywhere" reading was retired once it was shown that
 * five services genuinely call OpenAI. The exposure that remains is the mirror
 * image of the old one: a page that tells a user the product is AI-free while
 * AI runs behind it. These tests hold the disclosure in place so a future
 * "naming sweep" cannot quietly re-hide it.
 *
 * They assert the disclosure EXISTS and that the specific false claims do not
 * reappear -- not the exact marketing wording, which Peter and Khadijah own.
 */
class AiDisclosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_registration_disclaimer_discloses_ai(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertStringContainsString('AI-assisted', $html,
            'The registration disclaimer must disclose that the marketplace is AI-assisted.');
    }

    /**
     * The retired claims, in the exact shapes the old R29 sweep produced. If any
     * reappears in the signup copy, the disclosure has regressed to a falsehood.
     */
    public function test_the_signup_copy_does_not_claim_the_product_is_ai_free(): void
    {
        $html = strtolower($this->get(route('register'))->assertOk()->getContent());

        foreach (['no ai', 'not ai-powered', 'without ai', 'rules-based only', 'no artificial intelligence'] as $falseClaim) {
            $this->assertStringNotContainsString($falseClaim, $html,
                "Signup copy must not claim '{$falseClaim}' — five services call OpenAI (B4).");
        }
    }
}
