<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing page renders for a visitor who is not signed in.
 *
 * This is Laravel's scaffold test, and it had RefreshDatabase commented out,
 * so it ran against a database with no tables and failed on the first query
 * the homepage makes. It was reported as "pre-existing" for a long time.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_loads_for_a_visitor(): void
    {
        $this->get('/')->assertOk();
    }
}
