<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PM-2 / OA-25 — "No fake Client, Professional, booking, payment, event,
 * review, or dispute data should appear in public production views."
 *
 * This command answers the first half of that: what fake data is actually
 * there. It reads and counts. It changes nothing, so it is safe to run on
 * production, which is the only place the answer matters — the local database
 * has been out of step with production before, and a cleanup planned against
 * the wrong copy would delete the wrong rows.
 *
 * Demo data is recognised two ways, and the distinction is the whole point:
 *
 *  1. By owner. Every demo account the seeders create has an email at
 *     @example.test or @example.com. Anything those accounts own is demo data
 *     and can go with them.
 *
 *  2. By title, on a real account. DemoGigsSeeder picked its owner as
 *
 *         User::where('email', 'client@example.com')->first()
 *             ?? User::role('client')->first();
 *
 *     On production the demo client does not exist, so the fallback attached
 *     the gig to the first REAL client on the platform. That is why "Corporate
 *     Gala — Full Production" shows on an owner's own account. Deleting demo
 *     accounts would not touch it, because it is not owned by one.
 *
 * The second group is reported separately and never as a plain count, because
 * those rows sit next to genuine rows belonging to a real person. They are
 * listed one by one, with the account they landed on, so a human decides.
 */
class InventoryDemoData extends Command
{
    protected $signature = 'demo:inventory {--json : machine-readable output}';

    protected $description = 'Report which demo/sample rows exist (read-only; changes nothing)';

    /** The email shapes a fake account can take. */
    public const DEMO_EMAIL_PATTERNS = ['%@example.test', '%@example.com'];

    /**
     * The accounts the demo seeders actually create, listed by hand.
     *
     * The pattern above is how you FIND candidates; this list is what may be
     * removed without asking. The two are not the same, and conflating them
     * would have deleted the platform's administrator: AdminUserSeeder writes
     * admin@example.com, which matches the pattern and is not demo data.
     */
    public const SEEDED_DEMO_EMAILS = [
        'client@example.com',
        'supplier@example.com',
        'professional@example.com',
        'bloomvine.demo@example.test',
        'duy.demo@example.test',
        'elena.demo@example.test',
        'glowstudio.demo@example.test',
        'grandaffair.demo@example.test',
        'horizon.demo@example.test',
        'james.demo@example.test',
        'lumiere.demo@example.test',
        'marcus.demo@example.test',
        'mixmasters.demo@example.test',
        'olivia.demo@example.test',
        'priya.demo@example.test',
        'ridgeline.md.demo@example.test',
        'ridgeline.va.demo@example.test',
        'saffron.demo@example.test',
        'sofia.demo@example.test',
        'velvetnotes.demo@example.test',
    ];

    /**
     * Never demo data, whatever its address looks like. An administrator
     * locked out of their own platform is a worse outcome than a stray
     * sample row left on a page.
     */
    public const PROTECTED_EMAILS = ['admin@example.com'];

    /**
     * Titles DemoGigsSeeder writes. Matched exactly, never by prefix — a real
     * client is perfectly entitled to raise an event called "Corporate Gala".
     */
    public const DEMO_GIG_TITLES = [
        'Luxury Garden Wedding Photography',
        'Corporate Gala — Full Production',
        'Waterfront Wedding — Photo + Video + DJ',
        'Birthday Party Décor & Balloons',
        'Wedding Planner — Full Service',
        'Conference Catering — 200 Guests',
    ];

    public function handle(): int
    {
        $demoIds = self::demoUserIds();

        $report = [
            'accounts' => self::demoAccounts(),
            'owned' => self::ownedByDemoAccounts($demoIds),
            'stranded' => self::strandedOnRealAccounts($demoIds),
            'unrecognised' => self::unrecognisedAccounts(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->renderAccounts($report['accounts']);
        $this->renderOwned($report['owned']);
        $this->renderStranded($report['stranded']);
        $this->renderUnrecognised($report['unrecognised']);

        return self::SUCCESS;
    }

    /* ── Group 1: the demo accounts and what they own ───────── */

    /**
     * The accounts a purge may remove: seeded by name, never protected.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function demoUserIds(): \Illuminate\Support\Collection
    {
        return User::whereIn('email', self::SEEDED_DEMO_EMAILS)
            ->whereNotIn('email', self::PROTECTED_EMAILS)
            ->pluck('id');
    }

    /**
     * Accounts that LOOK fake but no seeder wrote — a hand-made test login, a
     * factory row that reached the database outside the test suite. Reported
     * so a person can decide, never removed automatically: this command
     * cannot tell one of these from a real account that happens to use an
     * example.com address.
     *
     * @return array<int, array{id: int, name: string, email: string, role: ?string}>
     */
    public static function unrecognisedAccounts(): array
    {
        // The OR group is wrapped. Left flat, `... or email like B and email
        // not in (...)` binds the exclusions to the last pattern only, and
        // every seeded account reappears here as unrecognised.
        $q = User::where(function ($q) {
            foreach (self::DEMO_EMAIL_PATTERNS as $pattern) {
                $q->orWhere('email', 'like', $pattern);
            }
        });

        return $q->whereNotIn('email', self::SEEDED_DEMO_EMAILS)
            ->whereNotIn('email', self::PROTECTED_EMAILS)
            ->orderBy('email')
            ->get(['id', 'name', 'email', 'primary_role'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->primary_role,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, email: string, role: ?string}> */
    public static function demoAccounts(): array
    {
        return User::whereIn('id', self::demoUserIds())
            ->orderBy('email')
            ->get(['id', 'name', 'email', 'primary_role'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->primary_role,
            ])
            ->all();
    }

    /**
     * Rows belonging to those accounts, counted per table.
     *
     * Each entry is [table, columns that point at a user]. A row counts once
     * however many of its columns match, so a booking whose client and
     * supplier are both demo accounts is one booking, not two.
     *
     * @return array<string, int>
     */
    public static function ownedByDemoAccounts(\Illuminate\Support\Collection $demoIds): array
    {
        if ($demoIds->isEmpty()) {
            return [];
        }

        $counts = [];

        foreach (self::ownershipMap() as $table => $columns) {
            if (! \Schema::hasTable($table)) {
                continue;
            }

            $present = array_values(array_filter(
                $columns,
                fn ($c) => \Schema::hasColumn($table, $c),
            ));

            if ($present === []) {
                continue;
            }

            $count = DB::table($table)
                ->where(function ($q) use ($present, $demoIds) {
                    foreach ($present as $column) {
                        $q->orWhereIn($column, $demoIds);
                    }
                })
                ->count();

            if ($count > 0) {
                $counts[$table] = $count;
            }
        }

        return $counts;
    }

    /**
     * Where a user id can be recorded. Kept explicit rather than derived from
     * foreign keys: several of these columns carry no constraint, and a
     * cleanup that missed one would leave a review pointing at a deleted
     * account — a five-star rating with no reviewer.
     *
     * @return array<string, array<int, string>>
     */
    public static function ownershipMap(): array
    {
        return [
            'events' => ['client_id', 'created_by', 'supplier_id'],
            'bookings' => ['client_id', 'created_by', 'supplier_id'],
            'reviews' => ['reviewer_id', 'reviewee_id'],
            'packages' => ['user_id'],
            'bids' => ['user_id', 'supplier_id'],
            'payments' => ['user_id', 'payer_id', 'payee_id'],
            'payouts' => ['user_id'],
            'dispute_cases' => ['opened_by', 'against_user_id'],
            'form_submissions' => ['user_id'],
            'messages' => ['sender_id'],
            'conversations' => ['created_by'],
            'agreements' => ['client_id', 'supplier_id'],
            'influencers' => ['user_id'],
            'influencer_referrals' => ['influencer_id', 'referred_user_id'],
            'user_profiles' => ['user_id'],
            'user_subscriptions' => ['user_id'],
            'saved_searches' => ['user_id'],
            'ai_chat_conversations' => ['user_id'],
        ];
    }

    /* ── Group 2: demo rows sitting on real accounts ─────────── */

    /**
     * The fallback's leftovers. Reported one row at a time with the account
     * they landed on — these are the ones a person has to look at, because
     * they are mixed in with that person's genuine events.
     *
     * @return array<int, array{id: int, title: string, owner: string, email: string}>
     */
    public static function strandedOnRealAccounts(\Illuminate\Support\Collection $demoIds): array
    {
        if (! \Schema::hasTable('events')) {
            return [];
        }

        return DB::table('events')
            ->join('users', 'users.id', '=', 'events.client_id')
            ->whereIn('events.title', self::DEMO_GIG_TITLES)
            ->whereNotIn('events.client_id', $demoIds->all() ?: [0])
            ->orderBy('events.id')
            ->get(['events.id', 'events.title', 'users.name', 'users.email'])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => $r->title,
                'owner' => $r->name,
                'email' => $r->email,
            ])
            ->all();
    }

    /* ── Rendering ──────────────────────────────────────────── */

    private function renderAccounts(array $accounts): void
    {
        $this->newLine();
        $this->info('Demo accounts');

        if ($accounts === []) {
            $this->line('  none — no address matches @example.test or @example.com');

            return;
        }

        $this->table(['id', 'name', 'email', 'role'], array_map('array_values', $accounts));
        $this->line('  '.count($accounts).' account(s)');
    }

    private function renderOwned(array $counts): void
    {
        $this->newLine();
        $this->info('Rows owned by those accounts');

        if ($counts === []) {
            $this->line('  none');

            return;
        }

        foreach ($counts as $table => $count) {
            $this->line(sprintf('  %-24s %d', $table, $count));
        }
    }

    private function renderStranded(array $rows): void
    {
        $this->newLine();
        $this->info('Demo events sitting on REAL accounts');
        $this->line('  These came from the seeder\'s owner fallback. They are next to');
        $this->line('  genuine events belonging to a real person — read before removing.');
        $this->newLine();

        if ($rows === []) {
            $this->line('  none');

            return;
        }

        $this->table(
            ['event id', 'title', 'landed on', 'email'],
            array_map('array_values', $rows),
        );
    }

    private function renderUnrecognised(array $accounts): void
    {
        $this->newLine();
        $this->info('Accounts that look fake but no seeder wrote');
        $this->line('  Decide these by hand. This command cannot tell a leftover test');
        $this->line('  login from a real person using an example.com address.');
        $this->newLine();

        if ($accounts === []) {
            $this->line('  none');

            return;
        }

        $this->table(['id', 'name', 'email', 'role'], array_map('array_values', $accounts));
    }
}
