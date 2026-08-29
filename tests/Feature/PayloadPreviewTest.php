<?php

namespace Tests\Feature;

use App\Support\PayloadPreview;
use Tests\TestCase;

/**
 * A saved tool result took an event page down with a 500.
 *
 * The card rendered each value with `implode(', ', array_map('strval', $v))`,
 * which assumes every element of an array is a scalar. A tool that saves a list
 * of ROWS — a checklist of tasks, a timeline of blocks — hands it an array of
 * arrays, and strval() on an array is a fatal "Array to string conversion".
 *
 * A preview is decoration. No shape of saved data is worth losing the page, so
 * nothing here may throw.
 */
class PayloadPreviewTest extends TestCase
{
    public function test_the_shape_that_broke_production(): void
    {
        $tasks = [
            ['name' => 'Book the venue', 'priority' => 'High', 'due' => 'May 20'],
            ['name' => 'Finalize catering', 'priority' => 'High', 'due' => 'May 28'],
        ];

        // Names, not every field of every row.
        $this->assertSame('Book the venue, Finalize catering', PayloadPreview::line($tasks));
    }

    public function test_a_plain_list_still_reads_as_a_list(): void
    {
        $this->assertSame('DJ, Catering, Florist', PayloadPreview::line(['DJ', 'Catering', 'Florist']));
    }

    public function test_scalars_are_unchanged(): void
    {
        $this->assertSame('Wedding', PayloadPreview::line('Wedding'));
        $this->assertSame('2400', PayloadPreview::line(2400));
        $this->assertSame('12.5', PayloadPreview::line(12.5));
    }

    public function test_the_values_a_person_cannot_read_are_translated(): void
    {
        $this->assertSame('Yes', PayloadPreview::line(true));
        $this->assertSame('No', PayloadPreview::line(false));
        $this->assertSame('—', PayloadPreview::line(null));
        $this->assertSame('—', PayloadPreview::line([]));
    }

    /** Rows with no name at all are counted rather than dumped. */
    public function test_a_nameless_nested_row_is_summarised(): void
    {
        $this->assertSame('3 items', PayloadPreview::line([
            [['a'], ['b']], [['c']], [['d']],
        ]));
    }

    public function test_deep_nesting_does_not_run_away(): void
    {
        $deep = [[[['x', 'y', 'z']]]];

        $line = PayloadPreview::line($deep);

        $this->assertNotSame('', $line);
        $this->assertStringNotContainsString('Array', $line);
    }

    public function test_an_object_is_handled(): void
    {
        $obj = new class () implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['name' => 'Halloway Sound'];
            }
        };

        $this->assertSame('Halloway Sound', PayloadPreview::line([$obj]));
    }

    /** A long list is trimmed rather than filling the card. */
    public function test_a_very_long_value_is_trimmed(): void
    {
        $line = PayloadPreview::line(array_fill(0, 80, 'Catering'));

        $this->assertLessThanOrEqual(160, mb_strlen($line));
        $this->assertStringEndsWith('…', $line);
    }

    /** The whole point: no input may throw. */
    public function test_nothing_throws(): void
    {
        $cases = [
            null, true, 0, '', [], [[]], [null], [[null]],
            ['a' => ['b' => ['c' => ['d' => 'deep']]]],
            [new \stdClass()],
            [fn () => 'x'],
        ];

        foreach ($cases as $case) {
            $this->assertIsString(PayloadPreview::line($case));
        }
    }
}
