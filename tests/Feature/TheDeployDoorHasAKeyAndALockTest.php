<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The deploy endpoints open with the key and are shut without it.
 *
 * The lock was built in October after these four routes were found wide open:
 * /deploy/git-pull, /deploy/migrate, /deploy/seed and /deploy/cache-clear had
 * no authentication, so anybody who guessed a path could pull code onto
 * production or run a seeder. Running CategorySeeder on this site once made a
 * second copy of all 360 categories and the client believed their pictures had
 * been wiped.
 *
 * What nobody noticed until 28 October is that the lock had no keyhole.
 * DeployKey reads config('app.deploy_key') and nothing in the repository ever
 * defined it, so the value was always the empty string, which the middleware
 * correctly refuses. Every deploy route answered 404 to everybody. The guard
 * failed closed, which is the right way to fail, and the feature behind it had
 * been dead since the day it was locked.
 *
 * Both halves are asserted here because each without the other is a trap: a
 * key that opens nothing, or a door that opens for nobody.
 */
class TheDeployDoorHasAKeyAndALockTest extends TestCase
{
    /** Every route the key guards. */
    public static function deployRoutes(): array
    {
        return [
            'cache clear' => ['/deploy/cache-clear'],
            'git pull'    => ['/deploy/git-pull'],
            'migrate'     => ['/deploy/migrate'],
            'seed'        => ['/deploy/seed'],
        ];
    }

    /**
     * The setting exists at all, which is the thing that was missing.
     *
     * Asserted against the config rather than the middleware, because the
     * middleware was always right and the config was always absent.
     */
    public function test_the_deploy_key_setting_exists(): void
    {
        $this->assertTrue(
            app('config')->has('app.deploy_key'),
            'DeployKey reads app.deploy_key and nothing defines it, so every deploy route is permanently 404.',
        );
    }

    /** Empty is the default, and empty means shut. */
    public function test_no_key_configured_means_the_door_is_shut(): void
    {
        config(['app.deploy_key' => '']);

        // Both the way a script passes it and the way a browser would.
        $this->get('/deploy/cache-clear')->assertNotFound();
        $this->get('/deploy/cache-clear?key=')->assertNotFound();
        $this->withHeader('X-Deploy-Key', '')->get('/deploy/cache-clear')->assertNotFound();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('deployRoutes')]
    public function test_a_wrong_key_is_refused(string $route): void
    {
        config(['app.deploy_key' => 'the-real-key']);

        $this->get($route . '?key=not-the-key')->assertNotFound();
    }

    /**
     * And refused with 404 rather than 403.
     *
     * A "wrong key" reply tells whoever is knocking that there is a door.
     */
    public function test_a_wrong_key_is_not_told_that_a_door_exists(): void
    {
        config(['app.deploy_key' => 'the-real-key']);

        $this->get('/deploy/cache-clear?key=wrong')->assertStatus(404);
    }

    /**
     * With the key, the door opens. Tested on cache-clear alone, because it is
     * the only one of the four that is safe to actually run in a test: the
     * others pull code, migrate and seed.
     */
    public function test_the_right_key_opens_it(): void
    {
        config(['app.deploy_key' => 'the-real-key']);

        $this->get('/deploy/cache-clear?key=the-real-key')
            ->assertSuccessful()
            ->assertJson(['success' => true]);
    }

    /** By header too, which is how a script should send it. */
    public function test_the_key_can_come_in_a_header(): void
    {
        config(['app.deploy_key' => 'the-real-key']);

        $this->withHeader('X-Deploy-Key', 'the-real-key')
            ->get('/deploy/cache-clear')
            ->assertSuccessful();
    }

    /**
     * The example environment file names it, so a new deployment does not have
     * to read the middleware to discover the setting exists.
     */
    public function test_the_setting_is_documented_where_somebody_will_find_it(): void
    {
        $this->assertStringContainsString(
            'DEPLOY_KEY=',
            (string) file_get_contents(base_path('.env.example')),
        );
    }
}
