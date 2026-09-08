<?php

namespace Tests\Feature;

use App\Domain\Cancellations\CancellationPolicy;
use Tests\TestCase;

/**
 * OA-149 — the notice ladder was applied to a date that had passed.
 *
 * Booking #58 was dated 28 March and cancelled on 25 August. The refund panel
 * read "Refund $0.00 — Less than 14 days before the event." Five months after
 * the event is not short notice; it is no notice, about a date that has gone.
 *
 * The figure may well be right. The sentence is not, and the sentence is what
 * a client reads and quotes back. The share stays 0.00 on purpose — there is
 * no post-event cancellation policy yet, it is the PM's to write, and
 * inventing a refund rule here would be worse than the wrong label.
 */
class CancellationAfterTheEventTest extends TestCase
{
    public function test_a_passed_date_is_not_called_short_notice(): void
    {
        $tier = CancellationPolicy::tierFor(-150);

        $this->assertSame('Event date has passed', $tier['label']);
        $this->assertNotSame('Less than 14 days before the event', $tier['label']);
    }

    /** No refund rule is invented while the policy is outstanding. */
    public function test_no_refund_share_is_invented_for_a_passed_event(): void
    {
        $this->assertSame(0.00, CancellationPolicy::tierFor(-1)['share']);
        $this->assertTrue(CancellationPolicy::tierFor(-1)['needs_policy']);
    }

    /** The day of the event is still the event, not after it. */
    public function test_the_day_of_the_event_still_uses_the_ladder(): void
    {
        $this->assertSame('Less than 14 days before the event', CancellationPolicy::tierFor(0)['label']);
    }

    /** And the bands before it are untouched. */
    public function test_the_notice_bands_are_unchanged(): void
    {
        $this->assertSame(1.00, CancellationPolicy::tierFor(45)['share']);
        $this->assertSame(0.50, CancellationPolicy::tierFor(20)['share']);
        $this->assertSame(0.00, CancellationPolicy::tierFor(3)['share']);
    }

    /**
     * An event with no date keeps the least generous band, as before —
     * unknown is not the same as passed, and guessing "plenty of notice"
     * would refund money against an assumption.
     */
    public function test_an_undated_event_is_not_treated_as_passed(): void
    {
        $tier = CancellationPolicy::tierFor(null);

        $this->assertSame('Less than 14 days before the event', $tier['label']);
        $this->assertArrayNotHasKey('needs_policy', $tier);
    }
}
