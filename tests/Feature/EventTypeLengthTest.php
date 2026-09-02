<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Validation must not accept more than the column can hold.
 *
 * events.event_type is varchar(80). Several forms validated it at max:120, so
 * a 108-character value passed validation and then killed the request with a
 * 500 — "Data too long for column 'event_type'". Ali hit it on the Virtual &
 * Hybrid brief with autofilled text.
 *
 * A rejected form is a form; a 500 is a dead end with a stack trace on it.
 */
class EventTypeLengthTest extends TestCase
{
    use RefreshDatabase;

    /** The limit the forms enforce has to be the limit the table has. */
    public function test_no_form_accepts_more_event_type_than_the_column_holds(): void
    {
        /*
         * Read the width from the migration that declares it. SQLite (the test
         * database) reports "varchar" with no length, so the live schema cannot
         * answer this portably — but the migration is the same source both
         * engines were built from.
         */
        $limit = 0;

        foreach (glob(database_path('migrations/*.php')) as $migration) {
            if (preg_match("/string\('event_type',\s*(\d+)\)/", file_get_contents($migration), $m)) {
                $limit = (int) $m[1];
                break;
            }
        }

        $this->assertGreaterThan(0, $limit, 'No migration declares the width of events.event_type.');

        foreach (glob(app_path('Http/Controllers/Client/*.php')) as $file) {
            if (! preg_match_all("/'event_type'\s*=>\s*\[([^\]]*)\]/", file_get_contents($file), $rules)) {
                continue;
            }

            foreach ($rules[1] as $rule) {
                if (preg_match("/'max:(\d+)'/", $rule, $max)) {
                    $this->assertLessThanOrEqual(
                        $limit,
                        (int) $max[1],
                        basename($file) . " validates event_type at {$max[1]}, but the column holds {$limit}.",
                    );
                }
            }
        }
    }

    /** And the form says so, rather than reaching the database and dying. */
    public function test_an_over_long_event_type_is_rejected_not_fatal(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create();
        $client->assignRole('client');
        $client->givePermissionTo('dashboard.view');

        $parent = Category::firstOrCreate(['slug' => 'etl-cat'],
            ['name' => 'Production', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $service = Category::create([
            'name' => 'Streaming', 'slug' => 'etl-streaming',
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);

        // Was posted at the Virtual Hub's plan step, which no longer exists —
        // the hub came off the site on 2026-08-31. The rule it checks is the
        // request wizard's, and that is where it is checked now.
        $this->actingAs($client->fresh())
            ->post(route('client.bsr.save', 'service'), [
                'services'          => [\App\Models\Category::where('kind', \App\Models\Category::SERVICE)->value('id')],
                'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
                'event_type'        => str_repeat('a', 120),
            ])
            ->assertSessionHasErrors('event_type');
    }
}
