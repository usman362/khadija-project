<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\GigResourceId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Issue #76, which is not the bug it has been reported as four times.
 *
 * The report reads: "the GR- ID-prefix fix confirmed at Session 16 reverted
 * on both exact surfaces at Session 18 ... the same fix disappearing twice in
 * the same place suggests something structural, not an isolated miss."
 *
 * It is structural, and it is not the component. GigResourceId::display() is
 * correct and has not regressed: it strips a GR- that sits in FRONT of a real
 * prefix, and deliberately leaves a bare GR-###### alone on the grounds that
 * an old fallback reference is somebody's actual reference. What it cannot do
 * is invent a prefix for an account whose stored reference never had one.
 *
 * So the GR- on Account Settings is not a rendering fault. It is what that
 * account's public_id literally contains, written before Sir Peter took the
 * GR prefix off the platform on 22 September. No amount of fixing the display
 * will change it, which is exactly why fixing the display twice did not.
 *
 * A reference is permanent, so this does not run on its own and is not a
 * migration. It is a deliberate command with a dry run, because it rewrites
 * something people may have written down. The digits never change — they are
 * the identity and they are unique — only the retired prefix is replaced with
 * the one the account's role implies, or USR where it has none.
 */
class RetireGrPrefix extends Command
{
    protected $signature = 'ids:retire-gr-prefix {--apply : Write the changes. Without this it only reports.}';

    protected $description = 'Replace the retired GR- prefix on account references with the account\'s own (Issue #76)';

    public function handle(): int
    {
        $legacy = User::query()
            ->where('public_id', 'like', 'GR-%')
            ->get(['id', 'name', 'email', 'public_id', 'primary_role']);

        if ($legacy->isEmpty()) {
            $this->info('No account carries the retired GR- prefix.');

            return self::SUCCESS;
        }

        $rows = [];
        $taken = User::query()->whereNotNull('public_id')->pluck('public_id')->flip();

        foreach ($legacy as $user) {
            $digits = substr($user->public_id, 3);

            // A reference that is GR- in front of a real prefix is already
            // handled when it is displayed; it is not this command's business.
            if (! preg_match('/^\d+$/', $digits)) {
                continue;
            }

            $role = $user->primary_role ?: $this->roleOf($user);
            $prefix = GigResourceId::prefixFor($role);
            $new = $prefix . '-' . $digits;

            if ($taken->has($new)) {
                $this->warn($user->public_id . ' → ' . $new . ' is already in use. Left alone.');
                continue;
            }

            $rows[] = [$user->id, $user->name, $role ?: 'none', $user->public_id, $new];
            $taken->put($new, true);
        }

        if (empty($rows)) {
            $this->info('Nothing to change.');

            return self::SUCCESS;
        }

        $this->table(['id', 'name', 'role', 'was', 'becomes'], $rows);

        if (! $this->option('apply')) {
            $this->comment(count($rows) . ' account(s) would change. Run again with --apply to write them.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as [$id, , , , $new]) {
                DB::table('users')->where('id', $id)->update(['public_id' => $new]);
            }
        });

        $this->info(count($rows) . ' reference(s) rewritten. The digits are unchanged.');

        return self::SUCCESS;
    }

    /** What the account registered as, for one that never had primary_role set. */
    private function roleOf(User $user): ?string
    {
        foreach (['admin', 'professional', 'influencer', 'affiliate', 'client'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }
}
