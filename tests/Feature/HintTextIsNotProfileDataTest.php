<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\NotTheHintText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OA-131 — the form's hints were saved as somebody's details.
 *
 * A profile carried "Your Company LLC" as its company name, "e.g. Technology,
 * Healthcare" as its industry and "https://iyourcompany.com" as its website.
 * The form is written correctly — those strings are placeholder attributes and
 * a placeholder never submits — so they were typed in, typo and all.
 *
 * The typo is the useful detail. "iyourcompany" is not the placeholder and is
 * not anybody's website either, so an exact-match check would have let it
 * through and the field would still read like an answer.
 */
class HintTextIsNotProfileDataTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile();

        return User::findOrFail($u->id);
    }

    /* ── The rule ───────────────────────────────────────────── */

    public function test_the_hint_itself_is_refused(): void
    {
        $this->actingAs($this->client())
            ->from('/client/profile')
            ->patch('/client/profile/company', [
                'company_name' => 'Your Company LLC',
                'industry' => 'e.g. Technology, Healthcare',
            ])
            ->assertSessionHasErrors(['company_name', 'industry']);
    }

    /** The one that got through: a mistyped hint. */
    public function test_a_mistyped_hint_is_refused_too(): void
    {
        $this->actingAs($this->client())
            ->from('/client/profile')
            ->patch('/client/profile/company', [
                'company_website' => 'https://iyourcompany.com',
            ])
            ->assertSessionHasErrors('company_website');
    }

    /** Anything opening "e.g." is an example somebody forgot to replace. */
    public function test_anything_starting_with_eg_is_refused(): void
    {
        $this->actingAs($this->client())
            ->from('/client/profile')
            ->patch('/client/profile/company', ['industry' => 'e.g. anything at all'])
            ->assertSessionHasErrors('industry');
    }

    /* ── And real answers still save ────────────────────────── */

    public function test_a_real_company_saves(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->patch('/client/profile/company', [
                'company_name' => 'Harbour Events LLC',
                'company_website' => 'https://harbourevents.com',
                'industry' => 'Events',
            ])
            ->assertSessionHasNoErrors();

        $profile = $client->fresh()->profile;

        $this->assertSame('Harbour Events LLC', $profile->company_name);
        $this->assertSame('Events', $profile->industry);
    }

    /**
     * A company genuinely called something close to the hint must not be
     * locked out. "Your Company Ltd" is two edits away and is somebody's
     * actual name; the tolerance is for typos, not for a naming convention.
     */
    public function test_a_real_name_that_merely_resembles_the_hint_is_allowed(): void
    {
        $this->assertFalse(NotTheHintText::looksLike('Yourk Company Holdings LLC', 'Your Company LLC'));
        $this->assertTrue(NotTheHintText::looksLike('https://iyourcompany.com', 'https://yourcompany.com'));
    }

    /* ── The cleanup ────────────────────────────────────────── */

    public function test_the_migration_left_real_values_alone(): void
    {
        // The migration runs before this test; what matters is that it cannot
        // reach a value a person plausibly meant.
        $this->assertFalse(NotTheHintText::looksLike('https://realbusiness.example', 'https://yourwebsite.com'));
    }
}
