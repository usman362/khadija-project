<?php

namespace App\Models\Concerns;

use App\Support\DisplayTimezone;

/**
 * Read in the reader's clock, written in UTC.
 *
 * Issue #115. Every ->format() in the application printed whatever zone the
 * Carbon instance carried, and Eloquent builds those in config('app.timezone')
 * — UTC. There are about 420 of those calls. Converting at each one is four
 * hundred chances to miss one and no way to tell which were done; converting
 * where the instance is made is one place, and every call site is right
 * afterwards without being touched.
 *
 * The instant never changes. setTimezone moves the clock face, not the
 * moment, so comparisons against now(), isPast(), diffForHumans and every
 * query still behave exactly as before.
 *
 * fromDateTime is the half that is easy to forget and expensive to get wrong.
 * Eloquent writes a date by formatting whatever asDateTime returns — so
 * without forcing UTC back on, a model saved after being read would write
 * the local wall time into a UTC column, and the record would walk four
 * hours every time it was touched.
 *
 * Not applied globally, and not by a base class. It belongs on the models
 * whose dates a person reads; a job's run time or a cache row has no reader
 * and no business shifting.
 */
trait ShowsTimesWhereTheReaderIs
{
    /** Reading: the same instant, on the reader's clock. */
    protected function asDateTime($value)
    {
        return parent::asDateTime($value)->setTimezone(DisplayTimezone::current());
    }

    /**
     * A date is not a time, and must not be moved like one.
     *
     * Laravel builds a `date` cast by taking the datetime and calling
     * startOfDay on it. Run that through the shift above and a date stored as
     * the 23rd becomes seven in the evening on the 22nd here, and the start
     * of that day is the 22nd — so a proposal offered for the event's own
     * date read as a proposal for a different one. A calendar date has no
     * clock attached and belongs to nobody's timezone.
     */
    protected function asDate($value)
    {
        return parent::asDateTime($value)->startOfDay();
    }

    /** Writing: always UTC, whatever clock it arrived on. */
    public function fromDateTime($value)
    {
        if (empty($value)) {
            return parent::fromDateTime($value);
        }

        return $this->asDateTime($value)->copy()->setTimezone('UTC')->format($this->getDateFormat());
    }

    /**
     * Times a person typed, which are wall-clock in their own zone.
     *
     * The other half of the problem, and the half that bites back. A client
     * filling in "7:00 PM" means seven in the evening where they are; stored
     * unconverted it becomes seven in the evening UTC, which is the
     * afternoon in Baltimore — so the form reads back a time nobody entered.
     * While display was also UTC this was invisible, because the same
     * mistake was being made twice and cancelling itself out.
     *
     * A model names its own fields rather than every controller converting,
     * because the BSR wizard alone assembles starts_at down four different
     * paths and all of them end at an assignment here.
     *
     * Only bare strings are touched, and only on the fields a model names. A
     * Carbon already believes it knows its zone, so the place to be right
     * about one of those is where it is built.
     *
     * A date with no time counts, and has to: "the 12th" written into a
     * datetime column becomes midnight, and midnight UTC is eight in the
     * evening on the 11th here — so a one-day event quietly moved to the day
     * before. Midnight means midnight where the person is.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value)
            && in_array($key, $this->localWallTimes ?? [], true)
            && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', trim($value))) {
            $value = DisplayTimezone::toUtc(trim($value));
        }

        return parent::setAttribute($key, $value);
    }
}
