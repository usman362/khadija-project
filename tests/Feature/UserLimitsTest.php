<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * What one account may do, and how often — Khadijah's revised sheet, 29 Aug.
 *
 * Her framing is the thing to keep hold of: GigResource is a seasonal,
 * event-based marketplace, so these exist to stop spam, abuse, fraud and
 * technical overload, NOT to restrain somebody doing ordinary work. A caterer
 * bidding thirty times in June is not a spammer.
 *
 * Two rules the mechanism follows, and both are tested here:
 *
 *   It counts what SUCCEEDED — a rejected form must not cost an allowance.
 *   It says WHEN — "try again later" leaves a person guessing.
 */
class UserLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        RateLimiter::clear('');   // a clean slate between tests
    }

    private function user(string $role = 'client'): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u->fresh();
    }

    /** Every number on her sheet, as configured. */
    public function test_the_sheet_is_what_is_configured(): void
    {
        $expected = [
            'messages-day'       => [25, 60 * 24],
            'messages-hour'      => [10, 60],
            'password-reset'     => [3,  60 * 24],
            'email-resend'       => [3,  60 * 24],
            'reports'            => [5,  60 * 24],
            'client-postings'    => [10, 60 * 24],
            'pro-responses'      => [30, 60 * 24],
            'client-invitations' => [30, 0],
            'client-images'      => [25, 60 * 24],
            'pro-images'         => [50, 60 * 24],
        ];

        foreach ($expected as $rule => [$max, $minutes]) {
            $config = config("limits.rules.{$rule}");

            $this->assertNotNull($config, "Rule {$rule} is missing — a limit that is not configured silently does not exist.");
            $this->assertSame($max, $config['max'], "{$rule} max");
            $this->assertSame($minutes, $config['minutes'], "{$rule} window");
        }
    }

    /**
     * The trap this nearly shipped with: config() reads a dot as nesting, so
     * a rule named "client.postings" resolves to NULL and every request sails
     * through. A limit that silently is not there is worse than none.
     */
    public function test_no_rule_name_contains_a_dot(): void
    {
        foreach (array_keys(config('limits.rules')) as $rule) {
            $this->assertStringNotContainsString('.', $rule, "Rule {$rule} would resolve to null through config().");
        }
    }

    public function test_it_allows_up_to_the_limit_then_stops(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            UserLimit::hit('reports', $user);   // 5 a day
        }

        $this->expectException(ValidationException::class);
        UserLimit::hit('reports', $user);
    }

    /** A refusal tells you how long to wait, not just that you failed. */
    public function test_the_refusal_says_when_to_come_back(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 5; $i++) {
            UserLimit::hit('reports', $user);
        }

        try {
            UserLimit::hit('reports', $user);
            $this->fail('Expected the limit to stop it.');
        } catch (ValidationException $e) {
            $this->assertMatchesRegularExpression(
                '/Try again in about \d+ (minutes|hours)\./',
                implode(' ', $e->validator->errors()->all()),
            );
        }
    }

    /** One person's allowance is their own. */
    public function test_accounts_are_counted_separately(): void
    {
        $a = $this->user();
        $b = $this->user();

        for ($i = 0; $i < 5; $i++) {
            UserLimit::hit('reports', $a);
        }

        UserLimit::hit('reports', $b);   // must not throw
        $this->assertSame(4, UserLimit::remaining('reports', $b));
    }

    /** Support answering ten people in a row is the job. */
    public function test_admins_are_exempt(): void
    {
        $admin = $this->user('admin');

        for ($i = 0; $i < 40; $i++) {
            UserLimit::hit('reports', $admin);
        }

        $this->assertNull(UserLimit::remaining('reports', $admin));
    }

    /** A per-scope rule is a total, not a rate — and it says so. */
    public function test_a_scoped_rule_counts_per_scope(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 30; $i++) {
            UserLimit::hit('client-invitations', $user, 'event:1');
        }

        // A different event has its own allowance.
        UserLimit::hit('client-invitations', $user, 'event:2');
        $this->assertSame(29, UserLimit::remaining('client-invitations', $user, 'event:2'));

        try {
            UserLimit::hit('client-invitations', $user, 'event:1');
            $this->fail('Expected the per-event total to stop it.');
        } catch (ValidationException $e) {
            $errors = implode(' ', $e->validator->errors()->all());
            $this->assertStringContainsString('up to 30 people', $errors);
            // No countdown: it does not reset tomorrow.
            $this->assertStringNotContainsString('Try again in', $errors);
        }
    }

    /** The whole thing can be switched off without touching code. */
    public function test_it_can_be_disabled(): void
    {
        config(['limits.enabled' => false]);

        $user = $this->user();

        for ($i = 0; $i < 50; $i++) {
            UserLimit::hit('reports', $user);   // must not throw
        }

        $this->assertNull(UserLimit::remaining('reports', $user));
    }

    /** Undoing an action gives the allowance back. */
    public function test_an_allowance_can_be_returned(): void
    {
        $user = $this->user();

        UserLimit::hit('reports', $user);
        UserLimit::hit('reports', $user);
        $this->assertSame(3, UserLimit::remaining('reports', $user));

        UserLimit::release('reports', $user);
        $this->assertSame(4, UserLimit::remaining('reports', $user));
    }

    /** An unknown rule is not a silent free pass in the other direction. */
    public function test_an_unknown_rule_does_nothing(): void
    {
        $user = $this->user();

        UserLimit::hit('no-such-rule', $user);   // must not throw
        $this->assertNull(UserLimit::remaining('no-such-rule', $user));
    }
}
