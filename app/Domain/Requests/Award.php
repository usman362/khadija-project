<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Finalization;

/**
 * How a proposal becomes a booking, in one place.
 *
 * There were two ways, and they disagreed. The Compare page and the chat went
 * through finalization: scope, price, schedule, contract and the $2.99 fee,
 * then a booking. The Proposals list's Accept went straight to a confirmed
 * booking and skipped all of it. Ali, 2026-09-10: "han finalization pe laga
 * do". Every award now opens a finalization, and only finishing one books.
 *
 * Both steps are keyed on the service as well as the event and professional
 * (B6): a professional awarded two services on one request gets two
 * finalizations and two bookings, each with its own price.
 */
final class Award
{
    /** Open the agreement for this proposal, or pick up the one already open. */
    public static function openFinalization(Bid $bid): Finalization
    {
        // OA-104: in regulated categories only a Verified professional's bid.
        RegulatedAcceptance::ensure($bid);

        self::ensureServiceIsFree($bid);
        self::ensureSameDate($bid);

        return Finalization::firstOrCreate(
            ['event_id' => $bid->event_id, 'supplier_id' => $bid->supplier_id, 'category_id' => $bid->category_id],
            [
                'bid_id'        => $bid->id,
                'client_id'     => $bid->event->client_id,
                // PM-3: an emergency request's agreement starts at the
                // professional's price plus the 25% surcharge.
                'agreed_price'  => EmergencySurcharge::priceFor($bid->event, (float) $bid->amount),
                'scope'         => $bid->plan,
                'payment_terms' => $bid->terms,
            ]
        );
    }

    /**
     * The finished agreement becomes its booking, and the proposal is won.
     *
     * Only this makes a booking from a proposal. Everything before it was an
     * agreement either side could walk away from.
     */
    public static function book(Finalization $f, string $notes): Booking
    {
        if ($f->bid) {
            RegulatedAcceptance::ensure($f->bid);
            self::ensureServiceIsFree($f->bid);
        }

        $booking = Booking::updateOrCreate(
            ['event_id' => $f->event_id, 'supplier_id' => $f->supplier_id, 'category_id' => $f->category_id],
            [
                'client_id'  => $f->client_id,
                'created_by' => $f->client_id,
                'status'     => 'confirmed',
                'price'      => $f->agreed_price,
                'currency'   => 'USD',
                'booked_at'  => now(),
                'source'     => 'finalization',
                'notes'      => $notes,
            ]
        );

        $f->bid?->update(['status' => 'won']);

        if ($f->bid) {
            self::moveToChosenDate($f->bid);
        }

        return $booking;
    }

    /**
     * Every service on one date. A proposal for a different day from one
     * already accepted on this request cannot be accepted.
     */
    private static function ensureSameDate(Bid $bid): void
    {
        if (ProposalDate::check($bid) === ProposalDate::MISMATCH) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bid' => ProposalDate::warning(ProposalDate::MISMATCH, $bid->event?->starts_at, $bid->supplier?->name, $bid),
            ]);
        }
    }

    /**
     * The first booking made on one of the client's backup dates moves the
     * request to that date: the start and end become that option's, and the
     * old preferred date becomes a backup, so nothing the client offered is
     * lost.
     */
    private static function moveToChosenDate(Bid $bid): void
    {
        $event = $bid->event;
        $o = EventDates::option($event, ProposalDate::dayOf($bid));

        if (! $o || $o['primary']) {
            return;
        }

        $old = EventDates::options($event)[0];
        $backups = collect(EventDates::options($event))
            ->where('primary', false)
            ->reject(fn ($b) => $b['date'] === $o['date'])
            ->map(fn ($b) => ['date' => $b['date'], 'start' => $b['start'], 'end' => $b['end']])
            ->push(['date' => $old['date'], 'start' => $old['start'], 'end' => $old['end']])
            ->sortBy('date')->values()->all();

        $start = \Illuminate\Support\Carbon::parse($o['date'] . ' ' . ($o['start'] ?: '00:00'));
        $end = $o['end'] ? \Illuminate\Support\Carbon::parse($o['date'] . ' ' . $o['end']) : null;
        if ($end && $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $event->forceFill(['starts_at' => $start, 'ends_at' => $end, 'backup_dates' => $backups])->save();
    }

    /**
     * One service, one professional.
     *
     * Every award is keyed on the professional as well as the service, which
     * is what lets two professionals split a request. It also meant nothing
     * stopped two of them being booked for the SAME service. Checked when the
     * agreement opens and again when it books, since another agreement for the
     * service can finish in between.
     */
    private static function ensureServiceIsFree(Bid $bid): void
    {
        $held = ServiceCoverage::awardFor($bid->event, $bid->category_id);

        if ($held && (int) $held->supplier_id !== (int) $bid->supplier_id) {
            $service = $bid->category?->name ?? 'This request';

            throw \Illuminate\Validation\ValidationException::withMessages([
                'bid' => "{$service} is already booked with " . ($held->supplier?->name ?? 'another professional')
                    . '. Cancel that booking first if you want to choose someone else.',
            ]);
        }
    }
}
