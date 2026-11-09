<?php

namespace App\Console\Commands;

use App\Domain\Auth\Enums\RoleName;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\Enums\InfluencerStatus;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Models\{Influencer, User};
use App\Support\ServiceArea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Hash};

/**
 * A working affiliate login, without going through apply-then-wait-for-admin.
 *
 * "Affiliate" is a name, not a role. /affiliate/login and /affiliate/status
 * both route into the Influencer controllers with loginRole => 'influencer',
 * the reference prefix is AFF- and the colour is its own, but the role on the
 * account is `influencer`. So this makes an influencer and the whole affiliate
 * side opens up.
 *
 * Three things have to line up or the portal is reachable but useless, and
 * each one has bitten this project already:
 *
 *   • the ROLE, which only approve() assigns. An Influencer row on its own
 *     leaves the account with no portal permissions at all.
 *   • the STATUS. attributeSignup() and attributeBookingCommission() both
 *     filter on approved, so a pending affiliate earns nothing however many
 *     people use their link.
 *   • the PROFILE's service area. An account with no state is `coming_soon`
 *     and fail-closed middleware stops it transacting.
 *
 * Idempotent: run it twice and the second run resets the password and repairs
 * whichever of the three is missing, rather than failing on the unique email.
 */
class MakeAffiliateAccount extends Command
{
    protected $signature = 'affiliate:make
                            {email : The address they sign in with}
                            {--name= : Display name (default: the part before the @)}
                            {--password= : Leave empty to have one generated and printed}
                            {--state=MD : Two-letter state; must be one we serve or they cannot transact}
                            {--city=Baltimore : Town on the profile}
                            {--pending : Leave the application unapproved, to test the waiting screen}';

    protected $description = 'Create (or repair) an affiliate login and print its credentials';

    public function handle(InfluencerServiceInterface $influencers): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: str((string) strstr($email, '@', true))->headline());
        $state = strtoupper((string) $this->option('state'));
        $password = (string) ($this->option('password') ?: str()->password(14, true, true, false));

        if (! array_key_exists($state, config('geo.allowed_states', []))) {
            $this->warn("{$state} is not one of the states we serve, so this account will register but "
                . 'will not be able to transact. Pass --state with one of: '
                . implode(', ', array_keys(config('geo.allowed_states', []))));
        }

        $user = DB::transaction(function () use ($email, $name, $password, $state) {
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user?->trashed()) {
                $user->restore();
                $this->line('That account was deleted. Restored it.');
            }

            if ($user) {
                $this->line('An account already uses that address. Resetting its password and repairing it.');
                $user->forceFill(['password' => Hash::make($password)])->saveQuietly();
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    // Set HERE and not a line later. The AFF-/INF- reference is
                    // stamped by a created() hook that reads primary_role, and
                    // an account created without one is USR- for life: the
                    // whole point of the prefix is that it never changes.
                    'primary_role' => RoleName::INFLUENCER->value,
                ]);
            }

            // Already-existing accounts only; a new one was born with it.
            $user->forceFill([
                'primary_role' => RoleName::INFLUENCER->value,
                // Nothing here can send mail on cPanel while MAIL_MAILER is
                // `log`, and an unverified account cannot finish anything.
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->saveQuietly();

            $user->getOrCreateProfile()->update([
                'city' => (string) $this->option('city'),
                'state' => $state,
                'country' => 'US',
                'service_area_status' => ServiceArea::statusFor('US', $state),
            ]);

            return $user->fresh();
        });

        $influencer = Influencer::where('user_id', $user->id)->first();

        if (! $influencer) {
            $influencer = $influencers->apply(new InfluencerApplicationData(
                fullName: $name,
                email: $email,
                socialMediaLinks: [],
                audienceDescription: 'Account created from the console for testing the affiliate portal.',
                monthlyReach: 0,
                userId: $user->id,
            ));
        }

        if ($this->option('pending')) {
            $influencer->update(['status' => InfluencerStatus::PENDING->value]);
            $this->warn('Left pending: the portal will show the waiting screen and no commission will be earned.');
        } else {
            // approve() is what assigns the role, so it runs even on a record
            // that already says approved.
            $influencers->approve($influencer->fresh(), null, 'Approved from the console.');
        }

        $user = $user->fresh();
        $influencer = $influencer->fresh();

        $this->newLine();
        $this->info('Affiliate account ready.');
        $this->table(['', ''], [
            ['Sign in at', rtrim(config('app.url'), '/') . '/affiliate/login'],
            ['Email', $user->email],
            ['Password', $password],
            ['Name', $user->name],
            ['Reference', \App\Support\GigResourceId::display($user->public_id) ?? '(none)'],
            ['Role', $user->getRoleNames()->implode(', ') ?: 'NONE — the portal will refuse them'],
            ['Status', $influencer->status instanceof InfluencerStatus
                ? $influencer->status->value
                : (string) $influencer->status],
            ['Referral code', $influencer->referral_code],
            ['Referral link', rtrim(config('app.url'), '/') . '/?ref=' . $influencer->referral_code],
            ['Service area', $user->profile?->service_area_status . ' (' . $state . ')'],
        ]);
        $this->newLine();
        $this->line('Write the password down now. It is hashed, so nothing can print it again.');

        return self::SUCCESS;
    }
}
