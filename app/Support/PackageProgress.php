<?php

namespace App\Support;

use App\Models\Package;

/**
 * How far through Create-a-Package a package actually is.
 *
 * The My Packages screen distinguishes a DRAFT (still being filled in) from one
 * that is READY TO PUBLISH (finished, just not live). Nothing in the database
 * says which is which, and adding a column would mean a second thing to keep in
 * step with the form — so it is read off the package itself: a package is ready
 * when every step of the wizard has what that step asks for.
 *
 * The four steps here are the four steps in create.blade.php, in order and by
 * the same names. If a step is added there it must be added here, or the
 * progress bar will report 100% on a package the form still considers unfinished.
 */
final class PackageProgress
{
    /**
     * @return array<int, array{n:int, label:string, done:bool, missing:?string}>
     */
    public static function steps(Package $package): array
    {
        $services = is_array($package->services) ? $package->services : [];

        return [
            [
                'n' => 1, 'label' => 'Package Details',
                'done' => filled($package->title) && filled($package->description),
                'missing' => filled($package->description) ? null : 'a description',
            ],
            [
                'n' => 2, 'label' => 'Services Included',
                // Two is the floor everywhere else — a package IS a bundle, and
                // the store validator says min:2.
                'done' => count($services) >= 2,
                'missing' => count($services) >= 2 ? null : 'at least two services',
            ],
            [
                'n' => 3, 'label' => 'Pricing & Options',
                'done' => (int) $package->price > 0,
                'missing' => (int) $package->price > 0 ? null : 'a price',
            ],
            [
                'n' => 4, 'label' => 'Availability & Coverage',
                'done' => filled($package->coverage) || filled($package->availability),
                'missing' => filled($package->coverage) || filled($package->availability)
                    ? null
                    : 'coverage or availability',
            ],
        ];
    }

    /** The step the professional would land on to carry on — null when finished. */
    public static function nextStep(Package $package): ?array
    {
        foreach (self::steps($package) as $step) {
            if (! $step['done']) {
                return $step;
            }
        }

        return null;
    }

    /** Whole percent complete. Counts steps, because that is what the bar shows. */
    public static function percent(Package $package): int
    {
        $steps = self::steps($package);
        $done = count(array_filter($steps, fn ($s) => $s['done']));

        return (int) round($done / max(1, count($steps)) * 100);
    }

    /** Nothing left to fill in. */
    public static function isComplete(Package $package): bool
    {
        return self::nextStep($package) === null;
    }

    /**
     * The state this package occupies on the shelf, which is what the tiles
     * count and the tabs filter by.
     *
     * Four of the five come straight off `status`. The fifth — "ready" — is the
     * distinction the mockup draws and the database does not: a draft with
     * every step finished is not still being written, it is waiting on a click.
     */
    public static function shelfState(Package $package): string
    {
        $status = $package->status ?: ($package->is_active ? 'active' : 'draft');

        return match ($status) {
            'active'   => 'published',
            'paused'   => 'unpublished',
            'archived' => 'archived',
            default    => self::isComplete($package) ? 'ready' : 'draft',
        };
    }
}
