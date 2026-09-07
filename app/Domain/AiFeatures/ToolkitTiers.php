<?php

namespace App\Domain\AiFeatures;

/**
 * Which toolkit tier unlocks a tool — one answer, both pages.
 *
 * OA-142: Contract Assistant was tagged "Semi" on /tools and marked
 * Maximum-only in the Toolkit Tiers table. Message Builder was tagged
 * "Maximum" on /tools and sits in the Semi bundle.
 *
 * The pages were not disagreeing about a fact. They were answering two
 * different questions with the same word:
 *
 *   /tools           "the highest level YOU can use on this tool"
 *   Toolkit Tiers    "the tier that unlocks this tool"
 *
 * Both render as "Semi" or "Maximum", so a client comparing them sees a
 * contradiction. A badge whose meaning depends on which page you are standing
 * on is worse than no badge.
 *
 * The tier assignment lives in config/toolkit-tiers.php — Rule R31, from the
 * Owner's 2026-08-05 correction. This reads it, and only it: where a tool has
 * no assignment, the answer is "not assigned", never a guess.
 */
class ToolkitTiers
{
    public const SEMI = 'semi';

    public const MAXIMUM = 'maximum';

    /**
     * The tier a tool needs, by its display name.
     *
     * Matched on the name because that is how R31 records the bundles — the
     * Owner's spreadsheet lists tools by what they are called, not by a key
     * this codebase invented.
     *
     * @return 'semi'|'maximum'|null  null when nothing has been assigned
     */
    public static function tierForName(string $toolName, string $audience = 'client'): ?string
    {
        $semi = (array) config("toolkit-tiers.semi_tools.{$audience}", []);

        if ($semi === []) {
            // No bundle recorded for this audience at all. Saying "maximum"
            // here would be a guess dressed as a rule.
            return null;
        }

        if (self::listContains($semi, $toolName)) {
            return self::SEMI;
        }

        // In the catalogue for this audience but not in the Semi bundle: R31
        // defines the rest as Maximum-only.
        return self::MAXIMUM;
    }

    /** The same question from a catalogue entry. */
    public static function tierFor(array $tool, string $audience = 'client'): ?string
    {
        return self::tierForName((string) ($tool['name'] ?? ''), $audience);
    }

    /** What to print beside a tool. */
    public static function label(?string $tier): ?string
    {
        return match ($tier) {
            self::SEMI => 'Semi',
            self::MAXIMUM => 'Maximum',
            default => null,
        };
    }

    /**
     * Tolerant of the small differences between a spreadsheet and a UI.
     *
     * "Guest Capacity Calculator" in R31 is "Guest Capacity" on the card, and
     * a straight string comparison would silently move it to Maximum-only —
     * a pricing change caused by a missing word.
     */
    private static function listContains(array $names, string $needle): bool
    {
        $norm = fn ($s) => preg_replace('/[^a-z]/', '', strtolower((string) $s));
        $target = $norm($needle);

        if ($target === '') {
            return false;
        }

        foreach ($names as $name) {
            $candidate = $norm($name);

            if ($candidate === $target
                || str_starts_with($candidate, $target)
                || str_starts_with($target, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
