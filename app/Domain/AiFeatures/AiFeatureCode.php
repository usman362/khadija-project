<?php

namespace App\Domain\AiFeatures;

/**
 * Canonical feature codes for plan-gated AI features.
 * Keep in sync with the PlanFeature records seeded in the database.
 */
final class AiFeatureCode
{
    public const BUDGET_ALLOCATOR    = 'ai.budget_allocator';
    public const VENDOR_MATCHMAKING  = 'ai.vendor_matchmaking';
    public const REVIEW_WRITER       = 'ai.review_writer';

    public static function all(): array
    {
        return [
            self::BUDGET_ALLOCATOR,
            self::VENDOR_MATCHMAKING,
            self::REVIEW_WRITER,
        ];
    }

    public static function label(string $code): string
    {
        return match ($code) {
            self::BUDGET_ALLOCATOR    => 'AI Budget Allocator',
            self::VENDOR_MATCHMAKING  => 'AI Vendor Matchmaking',
            self::REVIEW_WRITER       => 'AI Review Writer',
            default                   => ucwords(str_replace(['ai.', '_'], ['', ' '], $code)),
        };
    }
}
