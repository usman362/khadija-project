<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\MessageAttachment;
use App\Support\PlaceholderAssets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * The data side of the Sep 12 handoff: things a code fix cannot reach
 * because the rows already exist.
 *
 *   OA-114 / OA-115  events still Confirmed with no confirmed or completed booking
 *   OA-121           placeholder files attached to messages
 *   OA-116           event locations that are a web address or an email, not a place
 *
 * Lists everything by default. --force fixes the first two; locations are only
 * listed, because what the right place is has to come from a person.
 */
class ClientDataCleanup extends Command
{
    protected $signature = 'data:client-cleanup {--force : fix what can be fixed}';

    protected $description = 'List (or with --force, fix) leftover test data on the client side';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        // 1. Confirmed events with nothing confirmed behind them.
        $events = Event::where('status', 'confirmed')
            ->whereHas('bookings')
            ->whereDoesntHave('bookings', fn ($q) => $q->whereIn('status', ['confirmed', 'completed']))
            ->get(['id', 'title', 'source', 'status']);

        $this->line('<info>Events Confirmed with no confirmed booking:</info> ' . $events->count());
        foreach ($events as $e) {
            $to = $e->source === 'direct_offer' ? 'cancelled' : 'published';
            $this->line("  #{$e->id} {$e->title} -> {$to}");
            if ($force) {
                $e->forceFill(['status' => $to, 'supplier_id' => null])->saveQuietly();
            }
        }

        // 2. Placeholder attachments.
        $files = MessageAttachment::query()->get()->filter(fn ($a) => PlaceholderAssets::looksLikeOne($a->file_name));
        $this->line('<info>Placeholder attachments:</info> ' . $files->count());
        foreach ($files as $a) {
            $this->line("  #{$a->id} {$a->file_name}");
            if ($force) {
                if ($a->file_path) {
                    Storage::disk('private')->delete($a->file_path);
                }
                $a->delete();
            }
        }

        // 3. Locations that are an address on the web, not a place. Listed only.
        $bad = Event::query()->whereNotNull('location')->get(['id', 'title', 'location'])
            ->filter(fn ($e) => preg_match('/@|^\S+\.(com|net|org|io|co)\b|^https?:/i', trim((string) $e->location)));
        $this->line('<info>Event locations that are not a place (fix by hand):</info> ' . $bad->count());
        foreach ($bad as $e) {
            $this->line("  #{$e->id} {$e->title}: {$e->location}");
        }

        $this->line($force ? '<comment>Fixed what could be fixed.</comment>' : '<comment>Nothing changed. Run with --force to fix the first two.</comment>');

        return self::SUCCESS;
    }
}
