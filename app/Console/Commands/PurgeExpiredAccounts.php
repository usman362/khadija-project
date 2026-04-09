<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeExpiredAccounts extends Command
{
    protected $signature = 'users:purge-expired
                            {--dry-run : Show what would be purged without making changes}';

    protected $description = 'Anonymize and soft-delete user accounts whose 60-day grace period has expired';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expired = User::expiredDeletionRequests()->get();

        if ($expired->isEmpty()) {
            $this->info('No expired accounts to purge.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d expired account(s)%s', $expired->count(), $dryRun ? ' [DRY RUN]' : ''));

        $purged = 0;
        $failed = 0;

        foreach ($expired as $user) {
            $this->line(" → User #{$user->id}  {$user->email}  (scheduled: {$user->deletion_scheduled_at->format('Y-m-d')})");

            if ($dryRun) {
                $purged++;
                continue;
            }

            try {
                DB::transaction(function () use ($user) {
                    $this->anonymizeUser($user);
                });
                $purged++;
            } catch (Throwable $e) {
                $failed++;
                Log::error('Failed to purge user account', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
                $this->error("   ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->info(sprintf('Purge complete — %d processed, %d failed.', $purged, $failed));
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Anonymize the user and related data, then soft-delete the user.
     * Business records (bookings, payments) are preserved with the
     * identifying fields scrubbed, for audit/reporting purposes.
     */
    private function anonymizeUser(User $user): void
    {
        $anonEmail = 'deleted-user-' . $user->id . '-' . uniqid() . '@deleted.local';
        $anonName  = 'Deleted User';

        // 1) Avatar file removal
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // 2) User profile extended data — delete if exists
        $user->profile()->delete();

        // 3) Messages — keep content, null out sender
        DB::table('messages')->where('sender_id', $user->id)->update(['sender_id' => null]);
        DB::table('messages')->where('recipient_id', $user->id)->update(['recipient_id' => null]);

        // 4) Bookings — anonymize references (FK is set null on delete anyway, but belt + suspenders)
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'client_id')) {
            DB::table('bookings')->where('client_id', $user->id)->update(['client_id' => null]);
        }
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'supplier_id')) {
            DB::table('bookings')->where('supplier_id', $user->id)->update(['supplier_id' => null]);
        }
        if (DB::getSchemaBuilder()->hasColumn('bookings', 'created_by')) {
            DB::table('bookings')->where('created_by', $user->id)->update(['created_by' => null]);
        }

        // 5) Events — anonymize references
        if (DB::getSchemaBuilder()->hasColumn('events', 'client_id')) {
            DB::table('events')->where('client_id', $user->id)->update(['client_id' => null]);
        }
        if (DB::getSchemaBuilder()->hasColumn('events', 'supplier_id')) {
            DB::table('events')->where('supplier_id', $user->id)->update(['supplier_id' => null]);
        }
        if (DB::getSchemaBuilder()->hasColumn('events', 'created_by')) {
            DB::table('events')->where('created_by', $user->id)->update(['created_by' => null]);
        }

        // 6) Influencer record — if user is an influencer, anonymize it
        if (DB::getSchemaBuilder()->hasTable('influencers')) {
            DB::table('influencers')->where('user_id', $user->id)->update([
                'user_id'               => null,
                'full_name'             => $anonName,
                'email'                 => $anonEmail,
                'social_media_links'    => null,
                'audience_description'  => null,
            ]);
        }

        // 7) Referred by — keep the anonymized reference
        // (No action needed — InfluencerReferral records reference influencer_id, not user_id)

        // 8) Scrub the user record itself
        $user->forceFill([
            'name'                       => $anonName,
            'email'                      => $anonEmail,
            'avatar'                     => null,
            'phone'                      => null,
            'password'                   => bcrypt(str()->random(40)),
            'email_verified_at'          => null,
            'remember_token'             => null,
            'referred_by_influencer_id'  => null,
            'referral_attributed_at'     => null,
            'deletion_reason'            => null,
        ])->save();

        // 9) Soft-delete the user (keeps row for FK references but excluded from queries)
        $user->delete();

        Log::info('User account purged after grace period', [
            'original_id' => $user->id,
        ]);
    }
}
