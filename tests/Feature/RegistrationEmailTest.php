<?php

namespace Tests\Feature;

use App\Mail\WelcomeToGigResource;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordLink;
use App\Notifications\Auth\VerifyEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Signing up used to send nothing at all. Every notification in the app was
 * in-app only, so a new client got a dashboard and silence — no welcome, no
 * confirmation that the address they typed was even theirs.
 */
class RegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function signUp(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', array_merge([
            'name'                  => 'Marguerite Ashfield',
            'email'                 => 'marguerite@example.com',
            'password'              => 'Password!2345',
            'password_confirmation' => 'Password!2345',
            'role'                  => 'client',
            'state'                 => 'MD',
            'agree'                 => '1',
            'date_of_birth'         => '1990-04-11',
            // Peter's location/state disclosures, 26 Aug 2026.
            'disclosure_event_location' => '1',
            'disclosure_state_limit'    => '1',
            'disclosure_temporary'      => '1',
        ], $overrides));
    }

    public function test_a_new_client_is_welcomed_by_email(): void
    {
        Mail::fake();

        $this->signUp();

        $this->assertDatabaseHas('users', ['email' => 'marguerite@example.com']);

        Mail::assertSent(WelcomeToGigResource::class, function ($mail) {
            return $mail->hasTo('marguerite@example.com');
        });
    }

    public function test_a_new_account_is_asked_to_confirm_its_address(): void
    {
        Notification::fake();

        $this->signUp();

        $user = User::where('email', 'marguerite@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailAddress::class);
        $this->assertNull($user->email_verified_at, 'A brand-new account should not count as verified.');
    }

    /**
     * Exactly one welcome. Registration fires two events — Laravel's Registered
     * and the app's own UserRegistered — and listening to both would greet a
     * client twice. (Signup does send two emails in total: this one and the
     * confirm-your-address link, which are different jobs.)
     */
    public function test_the_welcome_is_sent_once(): void
    {
        Mail::fake();

        $this->signUp();

        Mail::assertSent(WelcomeToGigResource::class, 1);
    }

    public function test_the_welcome_can_be_switched_off_without_touching_code(): void
    {
        config(['emails.lifecycle.enabled' => false]);
        Mail::fake();

        $this->signUp();

        Mail::assertNothingSent();
    }

    public function test_a_password_reset_uses_the_apps_own_template(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'olwen@example.com']);
        Password::sendResetLink(['email' => 'olwen@example.com']);

        Notification::assertSentTo($user, ResetPasswordLink::class);
    }

    /** The templates must actually render — a mail that throws is a mail nobody gets. */
    public function test_every_registration_email_renders(): void
    {
        $user = User::factory()->create(['name' => 'Perrin Halloway']);

        $welcome = (new WelcomeToGigResource($user))->render();
        $this->assertStringContainsString('Perrin Halloway', $welcome);

        $verify = (new VerifyEmailAddress())->toMail($user)->render();
        $this->assertStringContainsString('Confirm my email', $verify);

        $reset = (new ResetPasswordLink('a-token'))->toMail($user)->render();
        $this->assertStringContainsString('Reset my password', $reset);
    }
}
