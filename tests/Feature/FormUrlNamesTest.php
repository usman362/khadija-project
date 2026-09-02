<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khadijah, 2026-08-30: the URLs and form names should use the real names.
 *
 * They did not. "Share Your Story" lived at /forms/new/testimonial, "Report
 * Content" at /forms/new/content_report, "Contact Support" at
 * /forms/new/support_request — a person reading the address bar saw an
 * internal identifier, not the thing they had clicked.
 *
 * The KEY is deliberately untouched: form_submissions.form_key stores it, and
 * four submissions already reference one. Renaming keys would orphan them. So
 * the name is what appears in the address, and the key stays what the database
 * knows.
 */
class FormUrlNamesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    /** @return array<string, array{0: string, 1: string}> key, expected address */
    public static function renamedForms(): array
    {
        return [
            'Contact Support'     => ['support_request', 'contact-support'],
            'Report Content'      => ['content_report', 'report-content'],
            'Share Your Story'    => ['testimonial', 'share-your-story'],
            'Request a Correction' => ['correction_request', 'request-a-correction'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('renamedForms')]
    public function test_the_address_reads_as_the_form_name(string $key, string $slug): void
    {
        $this->assertStringEndsWith("/requests-submissions/new/{$slug}", FormRegistry::url($key));
    }

    /** Every form, not just the four above. */
    public function test_no_address_carries_an_internal_key(): void
    {
        foreach (array_keys(FormRegistry::all()) as $key) {
            $this->assertStringNotContainsString('_', FormRegistry::slugFor($key),
                "The address for {$key} still reads as an internal identifier.");
        }
    }

    /** A link somebody was already sent must keep working. */
    public function test_the_old_address_still_opens_the_form(): void
    {
        $this->actingAs($this->client())
            ->get(route('forms.create', 'support_request'))
            ->assertSuccessful();
    }

    public function test_the_new_address_opens_the_form(): void
    {
        $this->actingAs($this->client())
            ->get(route('forms.create', 'contact-support'))
            ->assertSuccessful()
            ->assertSee('Contact Support');
    }

    /** Something that names no form is still a 404, not a blank page. */
    public function test_an_unknown_address_is_refused(): void
    {
        $this->actingAs($this->client())
            ->get(route('forms.create', 'no-such-form'))
            ->assertNotFound();
    }

    /**
     * The stored key must not move. Four submissions already point at one, and
     * changing it would leave them naming a form that does not exist.
     */
    public function test_the_stored_key_is_unchanged(): void
    {
        foreach (['support_request', 'content_report', 'testimonial'] as $key) {
            $this->assertNotNull(FormRegistry::get($key),
                "The key {$key} disappeared — submissions filed under it would be orphaned.");
        }
    }

    /**
     * The old address still answers. A renamed page is only safely renamed if
     * the link somebody already sent, bookmarked, or pasted into a message
     * still arrives — unlike the withdrawn Hub, this page has somewhere to
     * send them: itself, under its own name.
     */
    public function test_the_old_forms_address_redirects_rather_than_breaks(): void
    {
        // Signed in: the redirect sits inside the authenticated group, so a
        // guest is sent to the login screen and would prove nothing here.
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = \App\Models\User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');

        $this->actingAs($user)->get('/forms')
            ->assertRedirect('/requests-submissions');

        $this->actingAs($user)->get('/forms/new/contact-support')
            ->assertRedirect('/requests-submissions/new/contact-support');
    }

    /** And the page says the name the address now says. */
    public function test_the_page_is_headed_requests_and_submissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = \App\Models\User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');

        $this->actingAs($user)->get('/requests-submissions')
            ->assertOk()
            ->assertSee('Requests &amp; Submissions', false);
    }
}
