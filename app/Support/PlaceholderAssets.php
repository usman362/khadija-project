<?php

namespace App\Support;

/**
 * Sample and placeholder files (OA-121): demo video, stock avatars, dummy
 * images. They are not something a client or professional means to send.
 */
final class PlaceholderAssets
{
    private const PATTERNS = [
        '/^samplevideo[_-]/i',
        '/^sample[_-]?(video|image|audio|file|pdf)\b/i',
        '/^neutral-person\./i',
        '/^(placeholder|dummy|lorem)[_.-]/i',
    ];

    public static function looksLikeOne(?string $fileName): bool
    {
        $name = basename(trim((string) $fileName));

        foreach (self::PATTERNS as $p) {
            if (preg_match($p, $name)) {
                return true;
            }
        }

        return false;
    }
}
