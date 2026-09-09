<?php

namespace App\Console\Commands;

use App\Domain\Requests\FoodDelivery;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Pickup or delivery — which one clients actually ask for.
 *
 * This began as a way to size a courier deal. Sir Peter dropped that on
 * 2026-09-10, so what is left is the plainer question: how often a catering
 * client wants the food brought to them rather than collecting it. Useful to
 * whoever writes the catering guidance, and to any professional deciding
 * whether to offer delivery at all.
 */
class FoodDeliveryDemand extends Command
{
    protected $signature = 'food:delivery-demand {--days=30 : Only requests raised in the last N days}';

    protected $description = 'The split between client pickup and professional delivery on catering requests';

    public function handle(): int
    {
        $days  = max(1, (int) $this->option('days'));
        $since = now()->subDays($days);

        $counts = Event::query()
            ->whereNotNull('delivery_mode')
            ->where('created_at', '>=', $since)
            ->selectRaw('delivery_mode, COUNT(*) as n')
            ->groupBy('delivery_mode')
            ->pluck('n', 'delivery_mode');

        $answered = (int) $counts->sum();

        /*
         * Requests that were asked and left the question blank are counted too.
         * A field with a low answer rate produces a split that looks decisive
         * and is not, so the denominator is stated rather than hidden.
         */
        $asked = Event::query()
            ->whereHas('categories', fn ($q) => $q->whereIn('parent_id', FoodDelivery::foodCategoryIds()))
            ->where('created_at', '>=', $since)
            ->count();

        $this->info("Catering requests in the last {$days} days: {$asked}");
        $this->line("Answered the delivery question: {$answered}");
        $this->newLine();

        if ($answered === 0) {
            $this->warn('Nothing to report yet — no catering request has answered the question.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach (FoodDelivery::CHOICES as $value => $label) {
            $n = (int) ($counts[$value] ?? 0);
            $rows[] = [$label, $n, round($n / $answered * 100) . '%'];
        }

        $this->table(['Choice', 'Requests', 'Share of answers'], $rows);

        return self::SUCCESS;
    }
}
