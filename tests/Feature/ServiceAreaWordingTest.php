<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R27 — checklist rows 81, 96 and 120.
 *
 * GigResource operates in seven jurisdictions: MD, PA, WV, VA, NJ, DE and DC.
 * Row 81 asks that no title, chart, placeholder or demo dataset anywhere name
 * a city outside them; row 96 that no wording claim a wider reach.
 *
 * The audit found them right through the tools: a pricing assistant with a
 * cost-of-market table of Los Angeles, Beverly Hills, San Francisco, New
 * York, Miami, Chicago and Austin; an event planner set in LA; an availability
 * optimiser with a Chicago wedding; a demo calendar of Miami, Orlando and
 * Tampa; and half a dozen "e.g. Austin, TX" placeholders.
 *
 * A scan rather than a spot check, because this is the sort of thing that
 * comes back one placeholder at a time.
 */
class ServiceAreaWordingTest extends TestCase
{
    use RefreshDatabase;

    /** The seven, and nowhere else. */
    private const OUT_OF_AREA = [
        'Chicago', 'Miami', 'Seattle', 'Los Angeles', 'Beverly Hills',
        'San Francisco', 'New York', 'Orlando', 'Tampa', 'Denver',
        'Atlanta', 'Boston', 'Dallas', 'Houston', 'Phoenix',
    ];

    /** Row 96 — nothing may imply a wider reach than the seven. */
    private const OVERREACH = [
        'nationwide', 'worldwide', 'across the country', 'all 50 states',
        'anywhere in the US', 'coast to coast',
    ];

    /** @return array<int, string> */
    private function sourceFiles(): array
    {
        $out = [];

        foreach ([resource_path('views'), app_path()] as $root) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($it as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                // The design-spec pages are internal reference documents.
                if (str_contains($file->getPathname(), '/design-')) {
                    continue;
                }

                $out[] = $file->getPathname();
            }
        }

        return $out;
    }

    /**
     * Strip comments before matching.
     *
     * A comment explaining that a placeholder USED to say Chicago is a record
     * of the fix, not a violation of it — and this test would otherwise force
     * the next person to delete the explanation to make it pass.
     */
    private function withoutComments(string $src): string
    {
        return preg_replace(
            ['/\{\{--.*?--\}\}/s', '#/\*.*?\*/#s', '#^\s*//.*$#m', '#^\s*\*.*$#m'],
            '',
            $src,
        );
    }

    public function test_no_screen_names_a_city_outside_the_seven_jurisdictions(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $path) {
            $src = $this->withoutComments(file_get_contents($path));

            foreach (self::OUT_OF_AREA as $city) {
                if (stripos($src, $city) !== false) {
                    $offenders[] = str_replace(base_path() . '/', '', $path) . ' — ' . $city;
                }
            }
        }

        $this->assertSame([], $offenders,
            "R27: these name a city GigResource does not operate in. Use one of the seven.\n"
            . implode("\n", array_unique($offenders)));
    }

    public function test_nothing_claims_a_wider_reach_than_the_seven(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $path) {
            $src = $this->withoutComments(file_get_contents($path));

            foreach (self::OVERREACH as $phrase) {
                if (stripos($src, $phrase) !== false) {
                    $offenders[] = str_replace(base_path() . '/', '', $path) . ' — "' . $phrase . '"';
                }
            }
        }

        $this->assertSame([], $offenders,
            "R27: these claim a reach the platform does not have.\n" . implode("\n", array_unique($offenders)));
    }

    /* ── Row 120: real input, not just copy ─────────────────── */

    /**
     * The seven are what the platform enforces, not merely what it prints.
     * A request raised outside them is invisible to everybody under R38, so
     * the constraint has to hold in the data as well as the wording.
     */
    public function test_the_service_area_is_enforced_and_not_only_written(): void
    {
        $supported = ['MD', 'PA', 'WV', 'VA', 'NJ', 'DE', 'DC'];

        foreach ($supported as $state) {
            $this->assertSame(
                \App\Support\ServiceArea::SUPPORTED,
                \App\Support\ServiceArea::statusFor('US', $state),
                "{$state} should be in area",
            );
        }

        foreach (['CA', 'NY', 'TX', 'FL', 'IL'] as $state) {
            $this->assertSame(
                \App\Support\ServiceArea::COMING_SOON,
                \App\Support\ServiceArea::statusFor('US', $state),
                "{$state} should not be in area",
            );
        }
    }

    /** And the demo data itself stays inside them. */
    public function test_no_seeded_record_sits_outside_the_seven(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DemoUsersSeeder::class);

        $supported = ['MD', 'PA', 'WV', 'VA', 'NJ', 'DE', 'DC'];

        $strayProfiles = UserProfile::whereNotNull('state')
            ->whereNotIn('state', $supported)->pluck('state')->all();

        $strayEvents = Event::whereNotNull('state')
            ->whereNotIn('state', $supported)->pluck('state')->all();

        $this->assertSame([], $strayProfiles, 'profiles outside the seven: ' . implode(', ', $strayProfiles));
        $this->assertSame([], $strayEvents, 'events outside the seven: ' . implode(', ', $strayEvents));
    }
}
