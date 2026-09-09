<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionRenewalNotice as RenewalMail;
use App\Models\SubscriptionRenewalNotice;
use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells members before their membership renews.
 *
 * Washington DC, Automatic Renewal Protections Act of 2018: notice before the
 * first renewal and before every renewal after it.
 *
 * Sent to every member, not only those in DC. Where somebody lives is not
 * reliably known, addresses change, and a reminder that a payment is coming is
 * the right thing to send anyway — so the rule with the strictest requirement
 * sets the behaviour for everybody rather than being applied by postcode.
 *
 * Runs daily. Each notice is written down before it is counted as sent, and
 * the row is unique on (subscription, renewal date, days before), so running
 * this twice in a day — or replaying it after a deploy — cannot send anybody
 * the same notice twice.
 */
class SendSubscriptionRenewalNotices extends Command
{
    protected $signature = 'subscriptions:renewal-notices {--dry-run : List what would be sent, send nothing}';

    protected $description = 'Email members before their membership renews (DC Automatic Renewal Protections Act)';

    public function handle(): int
    {
        $leads = collect(config('subscriptions.renewal_notice_days', [30, 7]))
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d > 0)
            ->unique()
            ->sortDesc();

        $dry = (bool) $this->option('dry-run');
        $sent = 0;
        $skipped = 0;

        foreach ($leads as $days) {
            $on = now()->addDays($days)->toDateString();

            /*
             * Renewing that day, still running, and not already cancelled — a
             * membership somebody has cancelled is not going to renew, and
             * telling them it will is worse than saying nothing.
             */
            $due = UserSubscription::query()
                ->with(['user', 'plan'])
                ->where('status', 'active')
                ->whereNull('cancelled_at')
                ->whereDate('expires_at', $on)
                ->get();

            foreach ($due as $subscription) {
                $email = $subscription->user?->email;

                if (! filter_var($email ?? '', FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                $already = SubscriptionRenewalNotice::where('user_subscription_id', $subscription->id)
                    ->whereDate('renews_on', $on)
                    ->where('days_before', $days)
                    ->exists();

                if ($already) {
                    $skipped++;
                    continue;
                }

                if ($dry) {
                    $this->line("would send: {$email} — renews {$on} ({$days} days)");
                    $sent++;
                    continue;
                }

                try {
                    /*
                     * Recorded first. If the record fails we have not sent, and
                     * if the send fails we delete the record — either way the
                     * next run tries again. Sending first and recording second
                     * is how one mail-host timeout turns into the same notice
                     * every day until the renewal.
                     */
                    $note = SubscriptionRenewalNotice::create([
                        'user_subscription_id' => $subscription->id,
                        'renews_on'            => $on,
                        'days_before'          => $days,
                        'sent_at'              => now(),
                        'sent_to'              => $email,
                    ]);

                    Mail::to($email)->send(new RenewalMail($subscription, $days));
                    $sent++;
                } catch (Throwable $e) {
                    if (isset($note)) {
                        $note->delete();
                    }

                    $skipped++;

                    Log::warning('Renewal notice failed', [
                        'subscription_id' => $subscription->id,
                        'days_before'     => $days,
                        'reason'          => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info(($dry ? 'Would send' : 'Sent') . " {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
