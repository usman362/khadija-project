<?php

namespace App\Support;

/**
 * One readable line for any value a tool put in its saved result.
 *
 * The toolkit card rendered these with
 * `implode(', ', array_map('strval', $v))`, which assumes every element of an
 * array is a scalar. A tool that saves a list of rows — a checklist of tasks, a
 * timeline of blocks — hands it an array of arrays, and strval() on an array is
 * a fatal "Array to string conversion". A whole event page went down on it.
 *
 * Nothing here can throw: a preview is decoration, and no shape of saved data
 * is worth a 500 on somebody's event.
 */
class PayloadPreview
{
    /** Longest line we will print before trimming. */
    private const MAX_LENGTH = 160;

    /** Keys worth showing when a row is itself a set of fields. */
    private const NAME_KEYS = ['name', 'title', 'label', 'service', 'task', 'item', 'value'];

    public static function line(mixed $value): string
    {
        return self::trim(self::render($value));
    }

    private static function render(mixed $value, int $depth = 0): string
    {
        return match (true) {
            $value === null   => '—',
            is_bool($value)   => $value ? 'Yes' : 'No',
            is_scalar($value) => (string) $value,
            is_array($value)  => self::fromArray($value, $depth),
            $value instanceof \BackedEnum => (string) $value->value,
            is_object($value) => self::fromObject($value, $depth),
            default           => '—',
        };
    }

    private static function fromArray(array $value, int $depth): string
    {
        if ($value === []) {
            return '—';
        }

        // A row of fields reads best as its name. "Book the venue" beats
        // "Book the venue, High, May 20, todo".
        if ($depth > 0 && ($name = self::nameOf($value)) !== null) {
            return $name;
        }

        // Deep enough. Say how much is there rather than unrolling it.
        if ($depth >= 2) {
            return self::countLabel($value);
        }

        $parts = [];
        foreach ($value as $item) {
            $parts[] = self::render($item, $depth + 1);
        }

        $parts = array_filter($parts, fn ($p) => $p !== '' && $p !== '—');

        if ($parts === []) {
            return self::countLabel($value);
        }

        // If every piece came back as a count, the list holds nothing a person
        // can read — "1 item, 1 item, 1 item" is noise. Say how big it is once.
        $allCounts = ! array_filter($parts, fn ($p) => ! preg_match('/^\d+ items?$/', $p));

        return $allCounts ? self::countLabel($value) : implode(', ', $parts);
    }

    private static function fromObject(object $value, int $depth): string
    {
        if (method_exists($value, '__toString')) {
            return (string) $value;
        }

        if ($value instanceof \JsonSerializable) {
            $data = $value->jsonSerialize();

            return is_array($data) ? self::fromArray($data, $depth) : self::render($data, $depth + 1);
        }

        return self::fromArray(get_object_vars($value), $depth);
    }

    /**
     * A row of fields reads best as its name, not as every value it holds.
     * "Book the venue" beats "Book the venue, High, May 20, todo".
     */
    private static function nameOf(array $row): ?string
    {
        foreach (self::NAME_KEYS as $key) {
            if (isset($row[$key]) && is_scalar($row[$key]) && (string) $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return null;
    }

    private static function countLabel(array $value): string
    {
        $n = count($value);

        return $n . ' ' . ($n === 1 ? 'item' : 'items');
    }

    private static function trim(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_strlen($text) > self::MAX_LENGTH
            ? mb_substr($text, 0, self::MAX_LENGTH - 1) . '…'
            : $text;
    }
}
