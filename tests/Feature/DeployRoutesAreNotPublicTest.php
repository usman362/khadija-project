<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The deploy endpoints are not open to the internet.
 *
 * They were. /deploy/git-pull, /deploy/migrate, /deploy/seed and
 * /deploy/cache-clear had no authentication at all, so anybody who guessed the
 * path could pull code onto production or run a seeder.
 *
 * The seeder is the one that matters. Running the category seeder on a v2 site
 * once created a second copy of all 360 categories — 106 event types became
 * 153 — and the client believed their uploaded pictures had been wiped. That
 * was us, deliberately. A stranger could have done it by loading a URL.
 */
class DeployRoutesAreNotPublicTest extends TestCase
{
    private const ENDPOINTS = [
        '/deploy/git-pull',
        '/deploy/migrate',
        '/deploy/seed',
        '/deploy/cache-clear',
    ];

    public function test_a_stranger_gets_nothing(): void
    {
        config(['app.deploy_key' => 'a-real-key']);

        foreach (self::ENDPOINTS as $url) {
            $this->get($url)->assertNotFound();
            $this->get($url . '?key=wrong')->assertNotFound();
        }
    }

    /**
     * No key configured closes them rather than opening them.
     *
     * An empty secret matching an empty parameter is how a guard like this
     * quietly lets everybody through.
     */
    public function test_an_unconfigured_key_locks_them_shut(): void
    {
        config(['app.deploy_key' => null]);

        foreach (self::ENDPOINTS as $url) {
            $this->get($url)->assertNotFound();
            $this->get($url . '?key=')->assertNotFound();
        }
    }

    /** With the key they work — this is still how the site is deployed. */
    public function test_the_key_opens_the_harmless_one(): void
    {
        config(['app.deploy_key' => 'a-real-key']);

        $this->get('/deploy/cache-clear?key=a-real-key')
            ->assertSuccessful()
            ->assertJson(['success' => true]);
    }

    /** The header works too, so a key need not sit in a server log. */
    public function test_the_key_can_be_sent_as_a_header(): void
    {
        config(['app.deploy_key' => 'a-real-key']);

        $this->withHeader('X-Deploy-Key', 'a-real-key')
            ->get('/deploy/cache-clear')
            ->assertSuccessful();
    }
}
