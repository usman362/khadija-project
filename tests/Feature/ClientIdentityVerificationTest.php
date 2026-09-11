<?php

namespace Tests\Feature;

use App\Domain\Badges\ClientBadges;
use App\Domain\Forms\FormRegistry;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Account verification for clients, like Freelancer's: upload an ID, the team
 * approves it, and the account gets the Verified Client badge (PM-14).
 *
 * The ID is a government document, so it is kept on the private disk and only
 * an administrator can open it.
 */
class ClientIdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Storage::fake('local');
        Storage::fake('public');

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();

        $this->admin = User::factory()->create(['primary_role' => 'admin']);
        $this->admin->assignRole('admin');
    }

    private function upload()
    {
        return $this->actingAs($this->client)->post(route('client.verification.store'), [
            'document_type' => 'passport',
            'document'      => UploadedFile::fake()->image('passport.jpg'),
            'certify'       => 1,
        ]);
    }

    public function test_account_and_verification_offers_it_to_clients(): void
    {
        $groups = FormRegistry::groupsForAudience(FormRegistry::CLIENT);

        $this->assertArrayHasKey('identity_verification', $groups['account']['forms']);
        $this->assertSame(route('client.verification.show'), FormRegistry::url('identity_verification'));
    }

    public function test_the_old_form_address_opens_the_page(): void
    {
        $this->actingAs($this->client)
            ->get(route('forms.create', FormRegistry::slugFor('identity_verification')))
            ->assertRedirect(route('client.verification.show'));
    }

    public function test_the_id_is_kept_privately(): void
    {
        $this->upload()->assertSessionHasNoErrors()->assertRedirect(route('client.verification.show'));

        $profile = $this->client->fresh()->profile ?? $this->client->getOrCreateProfile();
        $this->assertNotNull($profile->identity_doc);
        $this->assertSame('Passport', $profile->identity_number);
        Storage::disk('local')->assertExists($profile->identity_doc);
        $this->assertSame([], Storage::disk('public')->allFiles(), 'An ID landed on the public disk.');
    }

    public function test_approval_earns_the_verified_client_badge(): void
    {
        $this->upload();
        $this->assertNotContains('verified-client', ClientBadges::earnedBy($this->client->fresh())->pluck('key')->all());

        $profile = $this->client->getOrCreateProfile();
        $this->actingAs($this->admin)
            ->post(route('app.admin.verifications.approve', $profile), ['badge' => 'identity'])
            ->assertSessionHasNoErrors();

        $this->assertContains('verified-client', ClientBadges::earnedBy($this->client->fresh())->pluck('key')->all());

        $this->actingAs($this->client->fresh())->get(route('client.verification.show'))
            ->assertOk()->assertSee('Your account was verified on', false);
    }

    public function test_rejection_removes_the_id_and_says_why(): void
    {
        $this->upload();
        $profile = $this->client->getOrCreateProfile();
        $path = $profile->identity_doc;

        $this->actingAs($this->admin)
            ->post(route('app.admin.verifications.reject', $profile), ['badge' => 'identity']);

        Storage::disk('local')->assertMissing($path);
        $this->actingAs($this->client->fresh())->get(route('client.verification.show'))
            ->assertOk()->assertSee('We could not verify that ID', false);
    }

    public function test_only_an_administrator_can_open_the_id(): void
    {
        $this->upload();
        $profile = $this->client->getOrCreateProfile();

        $this->actingAs($this->admin)->get(route('app.admin.verifications.identity', $profile))->assertOk();
        $this->assertNotSame(200, $this->actingAs($this->client)->get(route('app.admin.verifications.identity', $profile))->status());
    }

    public function test_the_upload_must_be_an_image_or_pdf(): void
    {
        $this->actingAs($this->client)->post(route('client.verification.store'), [
            'document_type' => 'passport',
            'document'      => UploadedFile::fake()->create('id.exe', 20),
            'certify'       => 1,
        ])->assertSessionHasErrors('document');
    }
}
