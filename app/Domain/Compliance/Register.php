<?php

namespace App\Domain\Compliance;

use Illuminate\Support\Collection;

/**
 * What the law asks of us, and where each one stands.
 *
 * The register lives in config so a requirement can be added the moment it is
 * known — Khadijah is writing the Terms of Service against several states, and
 * each item she sends becomes a row here rather than a message somebody has to
 * remember.
 *
 * Nothing here marks itself done. A status is a claim a person makes, so the
 * register makes the claim checkable instead: anything marked done has to name
 * what satisfies it and the month it was finished, and this class refuses to
 * call a row complete without both.
 */
final class Register
{
    public const STATUSES = ['done', 'building', 'todo', 'blocked', 'not_applicable'];

    /** @return Collection<int, array<string, mixed>> */
    public static function all(): Collection
    {
        return collect(config('compliance-register.requirements', []))
            ->map(fn (array $r) => $r + [
                'citation'    => null,
                'implemented' => null,
                'done_on'     => null,
                'note'        => null,
            ])
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    public static function byJurisdiction(): Collection
    {
        return self::all()->groupBy('jurisdiction');
    }

    /** Counts per status, so the page says how much is left rather than listing it. */
    public static function tally(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        foreach (self::all() as $row) {
            $status = $row['status'] ?? 'todo';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Rows whose own record does not hold up.
     *
     * A register nobody checks becomes a list of things somebody believes are
     * done. These are the ways a row can be lying: it says done with nothing
     * named as doing it, or done with no date, or it cites a law nobody has
     * given us the reference for.
     *
     * @return Collection<int, array{key:string, problem:string}>
     */
    public static function problems(): Collection
    {
        return self::all()->flatMap(function (array $r) {
            $out = [];

            if (($r['status'] ?? null) === 'done' && blank($r['implemented'] ?? null)) {
                $out[] = ['key' => $r['key'], 'problem' => 'Marked done, but nothing is named as satisfying it.'];
            }

            if (($r['status'] ?? null) === 'done' && blank($r['done_on'] ?? null)) {
                $out[] = ['key' => $r['key'], 'problem' => 'Marked done with no month recorded.'];
            }

            if (blank($r['citation'] ?? null)) {
                $out[] = ['key' => $r['key'], 'problem' => 'No citation on file. We cannot show which law this answers.'];
            }

            return $out;
        });
    }
}
