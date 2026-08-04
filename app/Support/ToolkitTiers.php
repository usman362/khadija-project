<?php

namespace App\Support;

use App\Domain\AiFeatures\AiToolCatalog;
use Illuminate\Support\Collection;

/**
 * Which of the 12 Client Toolkit tools each paid tier unlocks (Rule R31).
 *
 * The tier is a tool SUBSET, not an automation depth — that second thing is
 * config/ai-levels.php and is a different question. Manual has no toolkit
 * access at all, so it is not a tab.
 */
class ToolkitTiers
{
    /** The 12 client-facing tools, in catalogue order. */
    public static function clientTools(): Collection
    {
        return collect(AiToolCatalog::all())
            ->filter(fn (array $tool) => in_array($tool['audience'] ?? null, ['client', 'both'], true))
            ->values();
    }

    /**
     * One row per client tool for the given tier: is it included, is that
     * settled, and why.
     *
     * @return Collection<int, array{title:string, route:?string, purpose:?string, included:bool, confirmed:bool, note:?string}>
     */
    public static function table(string $tier): Collection
    {
        $config = config('toolkit-tiers.tools', []);
        $superset = (bool) config('toolkit-tiers.maximum_includes_semi', true);

        return self::clientTools()->map(function (array $tool) use ($tier, $config, $superset) {
            $title = $tool['name'] ?? '';
            $row = $config[$title] ?? ['tier' => null, 'confirmed' => false, 'note' => null];

            return [
                'title'     => $title,
                'route'     => $tool['route'] ?? null,
                'purpose'   => $tool['purpose'] ?? null,
                'status'    => $tool['status'] ?? null,
                'included'  => self::includes($tier, $row['tier'] ?? null, $superset),
                'confirmed' => (bool) ($row['confirmed'] ?? false),
                'note'      => $row['note'] ?? null,
            ];
        });
    }

    /**
     * Maximum is expected to be a superset of Semi, so a Semi tool is included
     * at Maximum too. A tool with no tier yet is included in neither.
     */
    private static function includes(string $viewing, ?string $assigned, bool $superset): bool
    {
        if ($assigned === null) {
            return false;
        }

        if ($viewing === $assigned) {
            return true;
        }

        return $superset && $viewing === 'maximum' && $assigned === 'semi';
    }

    /** Tools still waiting on Peter's answer — the table says so rather than guessing. */
    public static function unconfirmed(): Collection
    {
        return self::clientTools()
            ->map(fn (array $t) => $t['name'] ?? '')
            ->reject(fn (string $title) => (bool) (config("toolkit-tiers.tools.{$title}.confirmed") ?? false))
            ->values();
    }

    public static function allConfirmed(): bool
    {
        return self::unconfirmed()->isEmpty();
    }
}
