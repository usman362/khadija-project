<?php

namespace App\Console\Commands;

use App\Domain\Requests\FoodDelivery;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * How many clients want somebody else to bring the food.
 *
 * Sir Peter, 2026-09-09, on affiliating with UberEats or DoorDash: the deal is
 * only worth chasing if clients want a third option. This is that count, taken
 * from what clients actually answered rather than from what we expect.
 *
 * A commission deal negotiated on a guess is negotiated from the weaker side.
 */
class FoodDeliveryDemand extends Command
{
    protected $signature = 'food:delivery-demand {--days=30 : Only requests raised in the last N days}';

    protected $description = 'The split of how clients want catering delivered';

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

        $courier = (int) ($counts[FoodDelivery::COURIER_WANTED] ?? 0);
        $this->newLine();
        $this->line($courier > 0
            ? "{$courier} client(s) asked for a delivery service we do not offer."
            : 'No client has asked for a delivery service yet.');

        return self::SUCCESS;
    }
}
