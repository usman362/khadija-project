<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Which automated visitors may read GigResource, and which are asked not to.
 *
 * Sir Peter, 28 October, authorising the change and asking for one thing in
 * particular: "Test the proposed crawler rules and confirm which bots are
 * allowed and which are restricted."
 *
 * The confirming is the point. A robots file is a handful of lines that look
 * obvious and are easy to get backwards, and the expensive mistake is not
 * letting a training crawler through: it is blocking a SEARCH crawler by
 * accident and losing the traffic the file exists to attract. OpenAI and
 * Anthropic each run both kinds under different names, so the two sit one
 * line apart and must not be confused.
 *
 * This reads the real file and answers per robot, the way a crawler does:
 * the ONE group whose User-agent names it, falling back to * only when no
 * group does. Groups are not combined, which is the rule most often got
 * wrong when a file is read by eye.
 */
class WhichRobotsMayReadTheSiteTest extends TestCase
{
    private static function robots(): string
    {
        return (string) file_get_contents(public_path('robots.txt'));
    }

    /**
     * Whether one named robot may fetch one path.
     *
     * Not a full RFC parser: it does what these rules need, which is to pick
     * the group naming this agent, then take the longest matching rule, with
     * Allow winning a tie. That is Google's and Bing's own precedence.
     */
    private function mayFetch(string $agent, string $path): bool
    {
        $groups = $this->rulesByAgent(self::robots());

        $rules = $groups[strtolower($agent)] ?? $groups['*'] ?? [];

        $best = ['length' => -1, 'allow' => true];

        foreach ($rules as [$kind, $pattern]) {
            if ($pattern !== '' && ! str_starts_with($path, $pattern)) {
                continue;
            }

            $length = strlen($pattern);

            if ($length > $best['length'] || ($length === $best['length'] && $kind === 'allow')) {
                $best = ['length' => $length, 'allow' => $kind === 'allow'];
            }
        }

        return $best['allow'];
    }

    /**
     * The file as groups: agent name => list of [allow|disallow, path].
     *
     * Consecutive User-agent lines share the rules that follow them, which is
     * how one group can name several robots.
     *
     * @return array<string, list<array{0: string, 1: string}>>
     */
    private function rulesByAgent(string $text): array
    {
        $groups  = [];
        $current = [];
        $fresh   = false;

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if (! $fresh) {
                    $current = [];
                    $fresh   = true;
                }

                $current[] = strtolower($value);
                $groups[strtolower($value)] ??= [];

                continue;
            }

            if ($field !== 'allow' && $field !== 'disallow') {
                continue;
            }

            $fresh = false;

            foreach ($current as $agent) {
                $groups[$agent][] = [$field, $value];
            }
        }

        return $groups;
    }

    // ── Search must not be touched ───────────────────────────

    public static function searchEngines(): array
    {
        return [
            'Google'     => ['Googlebot'],
            'Google images' => ['Googlebot-Image'],
            'Bing'       => ['Bingbot'],
            'DuckDuckGo' => ['DuckDuckBot'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('searchEngines')]
    public function test_a_search_engine_may_read_the_public_site(string $agent): void
    {
        $this->assertTrue($this->mayFetch($agent, '/'), "{$agent} is shut out of the home page.");
        $this->assertTrue($this->mayFetch($agent, '/find-professionals'), "{$agent} cannot read the professionals page.");
        $this->assertTrue($this->mayFetch($agent, '/packages'), "{$agent} cannot read the packages page.");
    }

    // ── Meta, which is the point of the change ───────────────

    public static function metaCrawlers(): array
    {
        return [
            "Meta's AI crawler"   => ['meta-externalagent'],
            'Meta on request'     => ['meta-externalfetcher'],
            'Meta web indexer'    => ['Meta-WebIndexer'],
            'Facebook previews'   => ['facebookexternalhit'],
            'Facebook bot'        => ['FacebookBot'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('metaCrawlers')]
    public function test_meta_may_read_the_public_site(string $agent): void
    {
        $this->assertTrue($this->mayFetch($agent, '/'), "{$agent} is shut out.");
        $this->assertTrue($this->mayFetch($agent, '/find-professionals'));
    }

    // ── The distinction worth getting right ──────────────────

    /**
     * An AI company's SEARCH crawler is welcome; its TRAINING crawler is not.
     *
     * These sit one line apart in the file and are the pair most likely to be
     * confused. Blocking the search half is the costly direction: it loses a
     * route by which a real person arrives.
     */
    public static function theTwoKinds(): array
    {
        return [
            'OpenAI search vs training'    => ['OAI-SearchBot', 'GPTBot'],
            'OpenAI on request vs training' => ['ChatGPT-User', 'GPTBot'],
            'Anthropic search vs training' => ['Claude-SearchBot', 'ClaudeBot'],
            'Anthropic on request vs training' => ['Claude-User', 'ClaudeBot'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('theTwoKinds')]
    public function test_the_search_crawler_is_let_in_and_the_training_one_is_not(string $search, string $training): void
    {
        $this->assertTrue(
            $this->mayFetch($search, '/'),
            "{$search} answers somebody's question and can send a person here. It must not be blocked.",
        );

        $this->assertFalse(
            $this->mayFetch($training, '/'),
            "{$training} collects pages to train on and is supposed to be asked to stay out.",
        );
    }

    public static function trainingCrawlers(): array
    {
        return [
            'OpenAI'       => ['GPTBot'],
            'Anthropic'    => ['ClaudeBot'],
            'Anthropic alt' => ['anthropic-ai'],
            'Common Crawl' => ['CCBot'],
            'ByteDance'    => ['Bytespider'],
            'Amazon'       => ['Amazonbot'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('trainingCrawlers')]
    public function test_a_training_crawler_is_asked_to_stay_out(string $agent): void
    {
        $this->assertFalse($this->mayFetch($agent, '/'), "{$agent} is still allowed in.");
        $this->assertFalse($this->mayFetch($agent, '/find-professionals'));
    }

    /**
     * Google's and Apple's AI halves are deliberately NOT blocked.
     *
     * Blocking them would take GigResource out of the AI answers this whole
     * change exists to appear in, which is the opposite of what was asked
     * for. Asserted so that adding them later is a decision somebody makes
     * on purpose rather than a line that drifts in.
     */
    public function test_googles_and_apples_ai_are_not_blocked(): void
    {
        $this->assertTrue($this->mayFetch('Google-Extended', '/'));
        $this->assertTrue($this->mayFetch('Applebot-Extended', '/'));
    }

    // ── Private areas ────────────────────────────────────────

    /**
     * The admin and account paths are kept out of search results.
     *
     * Not a defence. Anything that matters is behind authentication, and
     * this file is read only by robots that choose to obey it. It keeps
     * sign-in pages out of search listings, which is all it can do.
     */
    public function test_the_private_paths_are_kept_out_of_the_listings(): void
    {
        foreach (['Googlebot', 'Bingbot', 'meta-externalagent', '*'] as $agent) {
            foreach (['/admin/users', '/app/agreements', '/login', '/register'] as $path) {
                $this->assertFalse(
                    $this->mayFetch($agent, $path),
                    "{$agent} is invited to index {$path}.",
                );
            }
        }
    }

    /** An unnamed robot still gets the public site, and nothing more. */
    public function test_an_unknown_robot_falls_back_to_the_catch_all(): void
    {
        $this->assertTrue($this->mayFetch('SomeBotNobodyHasHeardOf', '/'));
        $this->assertFalse($this->mayFetch('SomeBotNobodyHasHeardOf', '/admin/users'));
    }

    // ── What must not have been lost ─────────────────────────

    public function test_the_sitemap_is_still_named(): void
    {
        $this->assertStringContainsString('Sitemap: https://gigresource.com/sitemap.xml', self::robots());
    }

    /**
     * It is actually served at /robots.txt, which is the only address a
     * crawler ever asks for.
     *
     * This is the half that had never been true. The document root is
     * public_html and its rewrite sends anything that is not a real file to
     * index.php, so /robots.txt reached the application and got a 404 page
     * while the file sat readable at /public/robots.txt, where nothing looks.
     * Every robots rule this site has ever had was invisible, including the
     * ones it had before this change.
     */
    public function test_it_is_served_at_the_address_crawlers_ask_for(): void
    {
        $response = $this->get('/robots.txt')->assertSuccessful();

        $this->assertStringContainsString('text/plain', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('meta-externalagent', $response->getContent());
    }

    /** And it is the one file, not a copy that can drift from it. */
    public function test_what_is_served_is_the_file_in_the_repository(): void
    {
        $served = $this->get('/robots.txt');

        $this->assertSame(self::robots(), $served->getContent());
    }
}
