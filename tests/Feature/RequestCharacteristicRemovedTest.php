<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * "Request characteristic" is gone.
 *
 * The picker — Standard / Urgent / Recurring / High-Value — came from the BSR
 * mockup and was built with the wizard. Nothing was ever written to read it:
 * it reached no professional, changed no matching, no deadline and no fee, and
 * all 76 events on the site had it empty. Sir Peter asked on 2026-08-31 why a
 * required field did nothing. It was made optional then and removed on
 * 2026-09-03 at Ali's instruction.
 *
 * The column went with it. Retiring Team Mode taught that lesson: its columns
 * were left behind as harmless, and later code read them and believed what it
 * found. A column nothing fills is a trap for the next query written against
 * it, so this checks the schema as well as the screen.
 */
class RequestCharacteristicRemovedTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    public function test_the_wizard_no_longer_asks_for_it(): void
    {
        $html = $this->actingAs($this->client())->get('/client/bsr/service')->assertOk()->getContent();

        $this->assertStringNotContainsString('Request characteristic', $html);
        $this->assertStringNotContainsString('name="characteristic"', $html);

        // The four labels, in case the heading is renamed rather than removed.
        foreach (['Typical timeline and scope', 'Shorter timeline than standard',
            'Occurs on a regular schedule', 'Large budget or complex request'] as $copy) {
            $this->assertStringNotContainsString($copy, $html);
        }
    }

    /**
     * The pointer to the Emergency Request stays. It sat under the picker and
     * is the useful half of that block — a client who needs something urgently
     * has a different request type, not a label on this one.
     */
    public function test_the_emergency_request_pointer_is_still_offered(): void
    {
        $this->actingAs($this->client())->get('/client/bsr/service')
            ->assertOk()
            ->assertSee('Post an emergency request');
    }

    public function test_the_column_is_gone_rather_than_left_dormant(): void
    {
        $this->assertFalse(Schema::hasColumn('events', 'characteristic'));
    }

    /** And nothing in the codebase still reaches for it. */
    public function test_no_code_still_refers_to_it(): void
    {
        $hits = [];

        foreach (['app', 'resources/views'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir)));

            foreach ($it as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                $body = file_get_contents($file->getPathname());

                // The migration that drops it, and this file's own history in
                // comments, are allowed to name it.
                if (str_contains($body, "'characteristic'") || str_contains($body, 'name="characteristic"')) {
                    $hits[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $hits, "still referring to the removed field:\n".implode("\n", $hits));
    }
}
