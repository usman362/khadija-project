<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-137 / Issue #43: no Terms of Service link in the public footer.
 *
 * Raised at the start of September and not re-checked since the 6th. The link
 * is there; what was missing was anything holding it there, which is how it
 * went missing the first time. A visitor has to be able to read the terms
 * before signing up, from any public page.
 */
class PublicFooterHasTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_links_to_the_terms(): void
    {
        $missing = [];

        // Sign-up is on the list on purpose: it is where somebody agrees to them.
        foreach (['/', '/how-it-works', '/pricing', '/event-types', '/register'] as $path) {
            $response = $this->get($path);

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            if (! str_contains($response->getContent(), route('terms-of-service'))) {
                $missing[] = $path;
            }
        }

        $this->assertSame([], $missing, "no link to the terms on:\n".implode("\n", $missing));
    }

    public function test_the_terms_page_opens(): void
    {
        $this->get(route('terms-of-service'))->assertOk();
    }

    /**
     * Sign-up said "Terms of Service" and opened the platform disclaimer,
     * which is a different document. A box agreeing to something has to link
     * to that something.
     */
    public function test_the_sign_up_agreement_links_to_the_terms_it_names(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a href="'.preg_quote(route('terms-of-service'), '#').'"[^>]*>Terms of Service</a>#',
            $html,
        );
        $this->assertStringNotContainsString(
            '<a href="'.route('platform-disclaimer').'" target="_blank">Terms of Service</a>',
            $html,
        );
    }
}
