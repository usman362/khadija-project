<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The rules that hide source files must not hide the application.
 *
 * The .htaccess added on 22 October stopped the live site serving its own
 * error log and composer.lock, which was right. It matched on the URL alone,
 * which was not: the admin dashboard lives under /app/users and
 * /app/admin/..., the same first path segment as the app/ source directory, so
 * 135 routes began answering 403 from the web server and the application never
 * saw them. The whole admin side was down.
 *
 * The condition that fixes it is `RewriteCond %{REQUEST_FILENAME} -f`: refuse
 * a path only when a real file is sitting there. A source file is; a route is
 * not.
 *
 * This test reads the real .htaccess and checks it against the real route
 * list, because the mistake was invisible to every other kind of test. Nothing
 * in PHP was wrong.
 */
class TheHtaccessDoesNotBlockTheAppTest extends TestCase
{
    private function rules(): array
    {
        $body = file_get_contents(base_path('.htaccess'));

        $this->assertNotFalse($body, 'There is no .htaccess to check.');

        $rules = [];
        $pending = [];

        foreach (preg_split('/\R/', $body) as $line) {
            $line = trim($line);

            if (str_starts_with($line, '#') || $line === '') {
                continue;
            }

            if (preg_match('/^RewriteCond\s+(\S+)\s+(\S+)/i', $line, $m)) {
                $pending[] = [$m[1], $m[2]];

                continue;
            }

            if (preg_match('/^RewriteRule\s+(\S+)\s+\S+\s+\[([^\]]*)\]/i', $line, $m)) {
                // The flags are comma separated, and F means forbidden. A
                // substring search finds the F in [E=HTTP_X_XSRF_TOKEN:...]
                // and calls a header rule a refusal.
                $flags = array_map('strtoupper', array_map('trim', explode(',', $m[2])));

                if (in_array('F', $flags, true) || in_array('FORBIDDEN', $flags, true)) {
                    $rules[] = ['pattern' => $m[1], 'conds' => $pending];
                }

                $pending = [];

                continue;
            }

            $pending = [];
        }

        return $rules;
    }

    /** Every refusing rule asks whether a real file is there first. */
    public function test_nothing_is_refused_on_the_url_alone(): void
    {
        $rules = $this->rules();

        $this->assertNotEmpty($rules, 'No refusing rules found, so the source files are being served.');

        foreach ($rules as $rule) {
            $checksDisk = collect($rule['conds'])
                ->contains(fn ($c) => $c[0] === '%{REQUEST_FILENAME}' && $c[1] === '-f');

            $this->assertTrue(
                $checksDisk,
                'The rule "' . $rule['pattern'] . '" refuses a path without checking whether a file is there, '
                . 'so it will refuse routes that merely look like directories.',
            );
        }
    }

    /**
     * And no route in the application matches one of those patterns while
     * having no file behind it.
     *
     * Belt and braces: the condition above is what makes this safe, and this
     * is the check that would have caught it on the day.
     */
    public function test_no_route_is_refused_by_the_web_server(): void
    {
        $rules = $this->rules();
        $broken = [];

        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            // Only paths a person opens in a browser.
            if (! in_array('GET', $route->methods(), true) || str_contains($uri, '{')) {
                continue;
            }

            foreach ($rules as $rule) {
                if (! @preg_match('#' . $rule['pattern'] . '#', $uri)) {
                    continue;
                }

                // Matched the pattern. Safe only because no FILE sits there.
                // is_file, not file_exists: the root route is "" and
                // base_path("") is the project directory, which exists.
                if (is_file(base_path($uri))) {
                    $broken[] = $uri;
                }
            }
        }

        $this->assertSame([], $broken, 'These routes are shadowed by a real file and would never reach the app.');
    }

    /** The things it exists to refuse are still refused. */
    public function test_the_source_files_still_match_a_refusing_rule(): void
    {
        $rules = $this->rules();

        foreach (['app/Models/User.php', 'composer.lock', 'storage/logs/laravel.log', 'vendor/autoload.php', 'routes/web.php'] as $path) {
            $matched = collect($rules)->contains(fn ($r) => (bool) @preg_match('#' . $r['pattern'] . '#', $path));

            $this->assertTrue($matched, $path . ' is not refused by anything.');
            $this->assertFileExists(base_path($path), 'and it is a real file, so the -f condition holds.');
        }
    }

    /** Public assets are deliberately exempt or the site loses its images. */
    public function test_public_assets_are_left_alone(): void
    {
        $body = file_get_contents(base_path('.htaccess'));

        $this->assertStringContainsString(
            'RewriteCond %{REQUEST_URI} !^/public/',
            $body,
            'Without this, /public/storage and the logos stop being served.',
        );
    }
}
