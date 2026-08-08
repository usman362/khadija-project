<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ProfessionalStateAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Rule R47, locked 2026-08-03 — a professional working in more than one of the
 * seven jurisdictions holds a separate account per state: its own email, its
 * own licence proving THAT state's licensing, its own registration and
 * payment. The phone number is explicitly allowed to be shared.
 *
 * Most of the rule was already satisfied by construction — emails are unique,
 * subscriptions are per account, and R38 scopes each account's work to its own
 * state. The half that needed building is the half nobody would notice: a
 * professional could simply EDIT their state in Profile & Settings, which is
 * the exact thing R47 exists to prevent. That would carry an account's
 * reviews, badges and history into a state it was never licensed in, and
 * silently move every package and gig it owns under R38.
 *
 * Deliberately untested because deliberately unbuilt: anything linking a
 * person's accounts. R47 records "open questions on reputation/profile
 * sharing across accounts", so building a link now would answer them by
 * accident.
 */
class PerStateProfessionalAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(?string $state, string $email = 'pro@example.com', ?string $phone = null): User
    {
        $user = User::factory()->create(['email' => $email, 'phone' => $phone, 'primary_role' => 'professional']);
        $user->assignRole('professional');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'city' => 'Baltimore', 'state' => $state,
        ]);

        return $user->fresh();
    }

    private function saveProfile(User $user, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->patch(route('professional.profile.update.general'), array_merge([
            'name'  => $user->name,
            'email' => $user->email,
            'city'  => 'Baltimore',
        ], $overrides));
    }

    public function test_a_professional_cannot_move_their_account_to_another_state(): void
    {
        // The rule in one test. Editing this field instead of opening a second
        // account is what R47 forbids.
        $pro = $this->pro('MD');

        $this->saveProfile($pro, ['state' => 'DE'])->assertSessionHasErrors('state');

        $this->assertSame('MD', $pro->fresh()->profile->state);
    }

    public function test_saving_the_rest_of_the_profile_keeps_the_state(): void
    {
        // The locked field is absent from the payload, so the stored value has
        // to survive the save rather than being nulled by a missing key.
        $pro = $this->pro('MD');

        $this->saveProfile($pro, ['bio' => 'Twelve years of weddings.'])->assertSessionHasNoErrors();

        $this->assertSame('MD', $pro->fresh()->profile->state);
        $this->assertSame('Twelve years of weddings.', $pro->fresh()->profile->bio);
    }

    public function test_an_account_with_no_state_yet_can_still_set_one(): void
    {
        // Older rows and admin-created accounts. Completing a blank field is
        // not the same act as moving an account between states.
        $pro = $this->pro(null);

        $this->assertTrue(ProfessionalStateAccount::ownerMaySetState($pro));

        $this->saveProfile($pro, ['state' => 'PA'])->assertSessionHasNoErrors();

        $this->assertSame('PA', $pro->fresh()->profile->state);
    }

    public function test_and_then_cannot_change_it_again(): void
    {
        $pro = $this->pro(null);
        $this->saveProfile($pro, ['state' => 'PA']);

        $this->saveProfile($pro->fresh(), ['state' => 'VA'])->assertSessionHasErrors('state');
        $this->assertSame('PA', $pro->fresh()->profile->state);
    }

    public function test_the_state_must_be_one_of_the_seven(): void
    {
        // R9 — locations come from the jurisdiction list, not free text. The
        // field used to accept any string up to 100 characters.
        $pro = $this->pro(null);

        $this->saveProfile($pro, ['state' => 'CA'])->assertSessionHasErrors('state');
        $this->saveProfile($pro, ['state' => 'Baltimore area'])->assertSessionHasErrors('state');
    }

    public function test_a_client_may_still_change_their_state(): void
    {
        // R47 governs professionals. A client relocating is R38 finding 15,
        // which is agreed-with-change and still needs outside review on the
        // grandfathering — not something to settle here by accident.
        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update(['state' => 'MD']);

        $this->assertTrue(ProfessionalStateAccount::ownerMaySetState($client->fresh()));
    }

    /* ── The three things that make a second account possible ── */

    public function test_two_accounts_may_share_one_phone_number(): void
    {
        // Confirmed 2026-08-03 and easy to break later by "tidying up" the
        // schema: it is the one detail a person's accounts are allowed to
        // have in common, and without it the rule is unworkable.
        $maryland = $this->pro('MD', 'me+md@example.com', '410 555 0134');
        $delaware = $this->pro('DE', 'me+de@example.com', '410 555 0134');

        $this->assertSame('410 555 0134', $maryland->phone);
        $this->assertSame('410 555 0134', $delaware->phone);
        $this->assertSame(2, User::where('phone', '410 555 0134')->count());
    }

    public function test_the_phone_column_carries_no_unique_index(): void
    {
        // Asserted against the schema, not just against two saved rows: a
        // unique index added later would break R47 silently on the next
        // person who tried to open their second account.
        $indexes = collect(Schema::getIndexes('users'))
            ->filter(fn ($i) => in_array('phone', $i['columns'], true))
            ->filter(fn ($i) => $i['unique']);

        $this->assertCount(0, $indexes);
    }

    public function test_each_account_still_needs_its_own_email(): void
    {
        $this->pro('MD', 'taken@example.com');
        $second = $this->pro('DE', 'other@example.com');

        $this->saveProfile($second, ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    }

    /* ── The licence belongs to the account's state ─────────── */

    public function test_a_licence_is_stamped_with_the_accounts_state(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $pro = $this->pro('MD');

        $this->actingAs($pro)->post(route('professional.profile.verification.submit'), [
            'badge'    => 'trade_license',
            'number'   => 'MD-99812',
            'document' => \Illuminate\Http\UploadedFile::fake()->create('licence.pdf', 40, 'application/pdf'),
        ]);

        $this->assertSame('MD', $pro->fresh()->profile->trade_license_state);
        $this->assertTrue(ProfessionalStateAccount::licenceCoversAccountState($pro->fresh()));
    }

    public function test_a_licence_from_before_the_column_existed_is_unknown_not_wrong(): void
    {
        // Same shape as R62's missing date of birth: absent is its own answer.
        // Reading it as a failure would un-verify every licence on file.
        $pro = $this->pro('MD');
        $pro->profile->update(['trade_license_number' => 'MD-1', 'trade_license_state' => null]);

        $this->assertNull(ProfessionalStateAccount::licenceCoversAccountState($pro->fresh()));
    }

    public function test_a_licence_for_another_state_does_not_cover_this_account(): void
    {
        $pro = $this->pro('MD');
        $pro->profile->update(['trade_license_state' => 'DE']);

        $this->assertFalse(ProfessionalStateAccount::licenceCoversAccountState($pro->fresh()));
    }

    /* ── What the professional is told ─────────────────────── */

    public function test_the_page_explains_the_second_account_instead_of_just_refusing(): void
    {
        // "No" on its own turns a workable rule into a dead end. The honest
        // answer is "not on this account", and the rest of that sentence.
        $pro = $this->pro('MD');

        $page = $this->actingAs($pro)->get(route('professional.profile.index'));

        $page->assertSuccessful();
        $page->assertSee('open a separate', false);
        $page->assertSee('phone number', false);
        $page->assertSee(route('register', ['role' => 'professional']), false);
    }

    public function test_the_explanation_names_the_state_the_account_works_in(): void
    {
        $this->assertStringContainsString(
            'This account works in MD',
            ProfessionalStateAccount::secondAccountExplanation($this->pro('MD')),
        );
    }
}
