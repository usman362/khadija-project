<?php

namespace App\Console\Commands;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One multi-service test request with bids arranged to show every case.
 *
 * Sir Peter, 2026-09-17: the live Proposals page had a single proposal on it,
 * so nothing could be checked. He asked for a Wedding Reception with Banquet
 * Halls, DJ Services and 360 Photo Booths, and proposals covering:
 *
 *   one professional on one service         Velvet Beats (DJ only)
 *   one professional on every service       Evermore Events (all three)
 *   two professionals on the same service   SnapSpin 360 and Glow Booth (booths)
 *   split coverage by three companies       Grand Oak (hall) + Velvet Beats
 *                                           (DJ) + SnapSpin 360 (booth)
 *
 * The professionals are sample accounts with unguessable passwords, listed in
 * InventoryDemoData so `demo:purge` knows them. The request is created on the
 * real client account named, since that is who reviews it, and is marked so
 * --remove takes exactly it and nothing else of theirs.
 *
 * Dry by default, like every data command here.
 */
class DemoMultiServiceScenario extends Command
{
    protected $signature = 'demo:msr-scenario
        {client : email of the client account to put the test request on}
        {--force : actually create it (otherwise this only says what it would do)}
        {--remove : delete the test request and its sample professionals instead}';

    protected $description = 'Create (or remove) a multi-service test request with bids covering every case';

    /** Written into the description, so the request can always be found again. */
    public const MARKER = '[Test request: multi-service proposals]';

    public const SERVICES = [
        'hall'  => 'Banquet Halls',
        'dj'    => 'Wedding DJs',
        'booth' => '360 Photo Booths',
    ];

    /**
     * Who bids on what. Amounts are per service; `date` is whether they ticked
     * that they are free on the day, so the page shows both answers.
     */
    public const PROFESSIONALS = [
        'grandoak.msr.demo@example.test' => [
            'name' => 'Grand Oak Banquet Hall', 'headline' => 'Banquet hall for up to 250 guests',
            'bids' => ['hall' => 6200], 'date' => true,
        ],
        'velvetbeats.msr.demo@example.test' => [
            'name' => 'Velvet Beats DJ Co.', 'headline' => 'Wedding and reception DJs',
            'bids' => ['dj' => 1400], 'date' => true,
        ],
        'evermore.msr.demo@example.test' => [
            'name' => 'Evermore Events', 'headline' => 'Full-service wedding production',
            'bids' => ['hall' => 5800, 'dj' => 1650, 'booth' => 900], 'date' => false,
        ],
        'snapspin.msr.demo@example.test' => [
            'name' => 'SnapSpin 360', 'headline' => '360 photo and video booths',
            'bids' => ['booth' => 750], 'date' => true,
        ],
        'glowbooth.msr.demo@example.test' => [
            'name' => 'Glow Booth Studio', 'headline' => 'Photo booths with live sharing',
            'bids' => ['booth' => 680], 'date' => false,
        ],
    ];

    public function handle(): int
    {
        $client = User::where('email', $this->argument('client'))->first();

        if (! $client) {
            $this->error('No account with that email.');

            return self::FAILURE;
        }

        return $this->option('remove') ? $this->remove($client) : $this->create($client);
    }

    private function create(User $client): int
    {
        $services = collect(self::SERVICES)->map(fn ($name) => Category::where('kind', Category::SERVICE)->where('name', $name)->first());

        if ($services->contains(null)) {
            $this->error('Missing service(s): ' . collect(self::SERVICES)->filter(fn ($n, $k) => ! $services[$k])->implode(', '));

            return self::FAILURE;
        }

        if (Event::where('client_id', $client->id)->where('description', 'like', '%' . self::MARKER . '%')->exists()) {
            $this->warn('This account already has the test request. Run with --remove first to recreate it.');

            return self::SUCCESS;
        }

        $this->info("Test request for {$client->name}: Wedding Reception, " . collect(self::SERVICES)->implode(', '));
        foreach (self::PROFESSIONALS as $p) {
            $bids = collect($p['bids'])->map(fn ($amt, $k) => self::SERVICES[$k] . ' $' . number_format($amt))->implode(', ');
            $this->line("  {$p['name']}: {$bids}" . ($p['date'] ? ' (date confirmed)' : ' (date not confirmed)'));
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->warn('Dry run. Nothing written. Add --force to create it.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($client, $services) {
            $state = $client->profile?->state ?: 'MD';
            $city  = $client->profile?->city ?: 'Baltimore';

            $event = Event::create([
                'title'             => 'Wedding Reception (test request)',
                'description'       => 'Sample request to review multi-service proposals. ' . self::MARKER,
                'event_type'        => 'Wedding',
                'status'            => 'published',
                'is_published'      => true,
                'published_at'      => now(),
                'starts_at'         => now()->addDays(45)->setTime(17, 0),
                'ends_at'           => now()->addDays(45)->setTime(23, 0),
                'location'          => "{$city}, {$state}",
                'guest_count'       => 150,
                'budget_min'        => 8000,
                'budget_max'        => 9500,
                'budget'            => 9500,
                'proposal_deadline' => now()->addDays(20),
                'organization_type' => 'individual',
                'created_by'        => $client->id,
                'client_id'         => $client->id,
                'source'            => 'user',
            ]);

            $event->categories()->sync($services->pluck('id')->all());

            foreach (['hall' => 6500, 'dj' => 1800, 'booth' => 1200] as $key => $amount) {
                $event->serviceBudgets()->create(['category_id' => $services[$key]->id, 'amount' => $amount]);
            }

            foreach (self::PROFESSIONALS as $email => $p) {
                $pro = User::firstOrCreate(
                    ['email' => $email],
                    // Never a known password: these can exist on the live site.
                    ['name' => $p['name'], 'password' => Str::random(40), 'primary_role' => RoleName::PROFESSIONAL->value],
                );
                $pro->syncRoles([RoleName::PROFESSIONAL->value]);

                UserProfile::updateOrCreate(['user_id' => $pro->id], [
                    'headline'     => $p['headline'],
                    'company_name' => $p['name'],
                    'city'         => $city,
                    'state'        => $state,
                    'country'      => 'US',
                ]);

                $pro->serviceCategories()->syncWithoutDetaching(
                    collect($p['bids'])->keys()->map(fn ($k) => $services[$k]->id)->all()
                );

                foreach ($p['bids'] as $key => $amount) {
                    Bid::create([
                        'event_id'            => $event->id,
                        'category_id'         => $services[$key]->id,
                        'supplier_id'         => $pro->id,
                        'amount'              => $amount,
                        'status'              => 'submitted',
                        'available_confirmed' => $p['date'],
                        'availability_note'   => $p['date'] ? null : 'Checking our calendar for that weekend.',
                        'note'                => "Proposal for " . self::SERVICES[$key] . '.',
                        'submitted_at'        => now()->subHours(rand(2, 48)),
                    ]);
                }
            }

            $this->newLine();
            $this->info("Created. Open My Events, then \"{$event->title}\", then the Proposals tab.");
        });

        return self::SUCCESS;
    }

    private function remove(User $client): int
    {
        $events = Event::where('client_id', $client->id)
            ->where('description', 'like', '%' . self::MARKER . '%')
            ->get();

        $pros = User::whereIn('email', array_keys(self::PROFESSIONALS))->get();

        $this->line("Test requests: {$events->count()}. Sample professionals: {$pros->count()}.");

        if (! $this->option('force')) {
            $this->warn('Dry run. Nothing removed. Add --force to remove them.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($events, $pros) {
            foreach ($events as $event) {
                $event->bookings()->delete();
                $event->bids()->delete();
                $event->categories()->detach();
                $event->delete();
            }

            foreach ($pros as $pro) {
                $pro->forceDelete();
            }
        });

        $this->info('Removed.');

        return self::SUCCESS;
    }
}
