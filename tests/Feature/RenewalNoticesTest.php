<?php

namespace Tests\Feature;

use App\Mail\SubscriptionRenewalNotice as RenewalMail;
use App\Models\{MembershipPlan, SubscriptionRenewalNotice, User, UserSubscription};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Members are told before their membership renews.
 *
 * Washington DC, Automatic Renewal Protections Act of 2018: notice before the
 * first renewal and before every renewal after it. There was none of any kind.
 *
 * Sent to everybody rather than by state: where somebody lives is not reliably
 * known, addresses change, and a reminder that a payment is coming is the
 * right thing to send anyway. The strictest rule sets the behaviour.
 */
class RenewalNoticesTest extends TestCase
{
    use RefreshDatabase;

    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->plan = MembershipPlan::create([
            'name' => 'Professional', 'slug' => 'professional-test',
            'price' => 49.00, 'billing_cycle' => 'month', 'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    private function subscription(int $renewsInDays, array $over = []): UserSubscription
    {
        $user = User::factory()->create();

        return UserSubscription::create(array_merge([
            'user_id' => $user->id,
            'membership_plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays($renewsInDays),
            'amount_paid' => 49.00,
        ], $over));
    }

    public function test_a_member_is_told_thirty_days_out(): void
    {
        $sub = $this->subscription(30);

        $this->artisan('subscriptions:renewal-notices')->assertSuccessful();

        Mail::assertSent(RenewalMail::class, fn ($m) => $m->subscription->is($sub) && $m->daysBefore === 30);
    }

    public function test_and_again_a_week_out(): void
    {
        $this->subscription(7);

        $this->artisan('subscriptions:renewal-notices');

        Mail::assertSent(RenewalMail::class, fn ($m) => $m->daysBefore === 7);
    }

    /** Nothing on a day that is not one of the notice days. */
    public function test_nothing_is_sent_on_an_ordinary_day(): void
    {
        $this->subscription(19);

        $this->artisan('subscriptions:renewal-notices');

        Mail::assertNothingSent();
    }

    /**
     * The same notice cannot go twice, however often the job runs — a deploy
     * that replays it, or two servers, or somebody running it by hand.
     */
    public function test_running_it_again_sends_nothing(): void
    {
        $this->subscription(30);

        $this->artisan('subscriptions:renewal-notices');
        Mail::assertSentCount(1);

        $this->artisan('subscriptions:renewal-notices');
        Mail::assertSentCount(1);
    }

    /** Telling somebody who cancelled that they are about to be charged is worse than silence. */
    public function test_a_cancelled_membership_is_not_told_it_will_renew(): void
    {
        $this->subscription(30, ['cancelled_at' => now(), 'status' => 'cancelled']);

        $this->artisan('subscriptions:renewal-notices');

        Mail::assertNothingSent();
    }

    /** What was sent is written down, because a notice you cannot show is one you did not send. */
    public function test_every_notice_is_recorded(): void
    {
        $sub = $this->subscription(30);

        $this->artisan('subscriptions:renewal-notices');

        $this->assertDatabaseHas('subscription_renewal_notices', [
            'user_subscription_id' => $sub->id,
            'days_before' => 30,
            'sent_to' => $sub->user->email,
        ]);

        $this->assertNotNull(SubscriptionRenewalNotice::first()->sent_at);
    }

    /** A dry run says what it would do and sends nothing. */
    public function test_a_dry_run_sends_nothing(): void
    {
        $this->subscription(30);

        $this->artisan('subscriptions:renewal-notices', ['--dry-run' => true])->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('subscription_renewal_notices', 0);
    }

    /** How far ahead is a setting, so a state that names a window is a config change. */
    public function test_the_lead_times_are_configurable(): void
    {
        config(['subscriptions.renewal_notice_days' => [3]]);

        $this->subscription(3);
        $this->subscription(30);

        $this->artisan('subscriptions:renewal-notices');

        Mail::assertSentCount(1);
        Mail::assertSent(RenewalMail::class, fn ($m) => $m->daysBefore === 3);
    }

    /** It runs on a schedule, or it never runs at all. */
    public function test_it_is_scheduled(): void
    {
        $this->assertStringContainsString(
            "Schedule::command('subscriptions:renewal-notices')",
            file_get_contents(base_path('routes/console.php')),
            'The command exists but nothing runs it.',
        );
    }

    /**
     * The notice says the four things the law is about, and carries no
     * unsubscribe — offering to stop a required notice is offering to break
     * the law on request.
     */
    public function test_the_notice_says_when_how_much_and_how_to_stop_it(): void
    {
        $sub = $this->subscription(30);

        $body = (new RenewalMail($sub->fresh(), 30))->render();

        $this->assertStringContainsString($sub->expires_at->format('F j, Y'), $body);
        $this->assertStringContainsString('49.00', $body);
        $this->assertStringContainsString('cancel', strtolower($body));
        $this->assertStringNotContainsString('unsubscribe', strtolower($body));
    }

    /**
     * And the scheduler says whether it is alive.
     *
     * A timer that is not running fails silently: the code is right and the
     * emails simply never go. The heartbeat is the difference between knowing
     * the notices are being sent and assuming it.
     */
    public function test_the_scheduler_records_that_it_ran(): void
    {
        \Illuminate\Support\Facades\Cache::forget(\App\Console\Commands\SchedulerHeartbeat::KEY);

        $this->assertNull(\App\Console\Commands\SchedulerHeartbeat::lastRun(),
            'It claims to have run before it ever did.');

        $this->artisan('scheduler:heartbeat')->assertSuccessful();

        $this->assertNotNull(\App\Console\Commands\SchedulerHeartbeat::lastRun());
        $this->assertTrue(\App\Console\Commands\SchedulerHeartbeat::lastRun()->gt(now()->subMinute()));
    }

    public function test_the_heartbeat_is_scheduled_every_minute(): void
    {
        $this->assertStringContainsString(
            "Schedule::command('scheduler:heartbeat')->everyMinute()",
            file_get_contents(base_path('routes/console.php')),
        );
    }
}
