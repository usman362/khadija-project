<?php

namespace Database\Seeders;

use App\Models\{AvailabilityDay, Booking, Conversation, DisputeCase, DisputeEvent, Finalization, Message, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The four professional screens that were built, working, and empty.
 *
 * Everything else on the professional side has demo data behind it: leads are
 * derived from real requests, the calendar reads real bookings, Transactions
 * reads real payments. Four screens had nothing to read, so they opened
 * correctly and said "nothing yet", which in a demonstration is
 * indistinguishable from unfinished.
 *
 *   Agreements      a contract on an existing booking, at each stage
 *   Messages        a thread with a real client about a real event
 *   Disputes        one case, filed, with its history
 *   Availability    a working pattern plus a few blocked days
 *
 * LOCAL AND DEMO ONLY. Never on production: this writes agreements, messages
 * and a dispute against real bookings, and a dispute in particular is a thing
 * two people argue about. The rule exists because a seeder was once run on
 * the live database and left 47 duplicate rows behind.
 *
 * Idempotent. Everything is matched before it is made, so a second run adds
 * nothing, and it writes only against bookings owned by the demo accounts.
 */
class DemoProfessionalActivitySeeder extends Seeder
{
    /**
     * Every demonstration professional, not a chosen two.
     *
     * Picking two meant the one somebody actually signed in as was the one
     * with the empty screens. Each piece below needs a booking of its own to
     * attach to, so an account with none is simply left as it is.
     */
    public function run(): void
    {
        $pros = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'professional'))
            ->get();

        $touched = 0;

        foreach ($pros as $pro) {
            if (! Booking::where('supplier_id', $pro->id)->exists()) {
                continue;
            }

            $this->agreements($pro);
            $this->threads($pro);
            $this->availability($pro);
            $this->dispute($pro);

            $touched++;
        }

        $this->command?->info("Demo activity seeded for {$touched} professionals.");
    }

    /**
     * Three agreements, at three different stages.
     *
     * The page reads FINALIZATION, not Agreement. They are two different
     * things with similar names: an Agreement is the written contract
     * document attached to a conversation, and a Finalization is the
     * step-by-step wizard a client and a professional walk from a chosen bid
     * to a funded booking. A first pass of this seeder filled the wrong one
     * and the page went on saying "no agreements yet" over three rows.
     *
     * One waiting on the professional, one waiting on the client, one signed
     * by both. Telling those apart is the page's whole job, so one of each is
     * the smallest set that shows it working.
     */
    private function agreements(User $pro): void
    {
        $bookings = Booking::where('supplier_id', $pro->id)
            ->whereNotNull('event_id')
            ->whereNotNull('client_id')
            ->with('event:id,title,starts_at')
            ->orderBy('id')
            ->take(3)
            ->get();

        foreach ($bookings as $i => $booking) {
            $exists = Finalization::where('supplier_id', $pro->id)
                ->where('event_id', $booking->event_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $price = max(500, (float) ($booking->price ?: 1800));

            $f = Finalization::create([
                'event_id'        => $booking->event_id,
                'category_id'     => $booking->category_id,
                'client_id'       => $booking->client_id,
                'supplier_id'     => $pro->id,
                'agreed_price'    => $price,
                'deposit_percent' => 25,
                'deposit_amount'  => round($price * 0.25, 2),
                'scope'           => 'As listed on the booking for ' . ($booking->event?->title ?? 'the event') . '.',
                'contract_body'   => $this->contractBody($booking, $pro),
            ]);

            // Everything up to the signatures is the same on all three: what
            // differs is who has approved and who has signed.
            $f->forceFill([
                'bid_reviewed_at'    => now()->subDays(7),
                'scope_agreed_at'    => now()->subDays(7),
                'price_agreed_at'    => now()->subDays(6),
                'service_start'      => $booking->event?->starts_at,
                'schedule_agreed_at' => now()->subDays(6),
                'terms_agreed_at'    => now()->subDays(5),
                'terms_version'      => 1,
            ])->save();

            match ($i) {
                // Waiting on the professional: the client has done their part.
                0 => $f->forceFill([
                    'client_approved_version' => 1,
                    'client_signature'        => optional($booking->client)->name,
                    'client_signed_at'        => now()->subDays(4),
                    'client_signed_version'   => 1,
                ])->save(),

                // Waiting on the client.
                1 => $f->forceFill([
                    'supplier_approved_version' => 1,
                    'supplier_signature'        => $pro->name,
                    'supplier_signed_at'        => now()->subDays(3),
                    'supplier_signed_version'   => 1,
                ])->save(),

                // Both sides done, ready to fund.
                default => $f->forceFill([
                    'client_approved_version'   => 1,
                    'supplier_approved_version' => 1,
                    'client_signature'          => optional($booking->client)->name,
                    'client_signed_at'          => now()->subDays(2),
                    'client_signed_version'     => 1,
                    'supplier_signature'        => $pro->name,
                    'supplier_signed_at'        => now()->subDay(),
                    'supplier_signed_version'   => 1,
                ])->save(),
            };
        }
    }

    /** Plain terms, not a legal document: this is what the screen shows. */
    private function contractBody(Booking $booking, User $pro): string
    {
        $when = $booking->event?->starts_at?->format('F j, Y') ?? 'the agreed date';

        return implode("\n\n", [
            'Scope of work',
            'The professional will provide the services listed on this booking for ' . ($booking->event?->title ?? 'the event') . ' on ' . $when . '.',
            'Price',
            'The agreed price is $' . number_format((float) $booking->price, 2) . '. A deposit secures the date and the balance is due as set out in the booking.',
            'Cancellation',
            'Either side may cancel in writing. What is owed on a cancellation follows the platform cancellation policy in force on the day the booking was made.',
            'Point of contact',
            $pro->name . ' is responsible for delivery and will confirm the on-site contact before the day.',
        ]);
    }

    /**
     * A thread with a client about a booking they already have.
     *
     * Both sides speak, and the client speaks last, because the Messages page
     * counts "awaiting your reply" and a thread the professional answered
     * last demonstrates nothing.
     */
    private function threads(User $pro): void
    {
        $bookings = Booking::where('supplier_id', $pro->id)
            ->whereNotNull('event_id')
            ->with(['event:id,title', 'client:id,name'])
            ->orderBy('id')
            ->take(2)
            ->get();

        foreach ($bookings as $booking) {
            $client = $booking->client;

            if (! $client) {
                continue;
            }

            /*
             * The thread, and then its messages, checked apart.
             *
             * A first run of this failed between the two and left a
             * conversation with nobody saying anything, which is worse than
             * no thread at all: the inbox listed it and opening it showed an
             * empty page. Asking "does the thread exist" would have skipped
             * it for ever.
             */
            $conversation = Conversation::firstOrCreate(
                ['booking_id' => $booking->id, 'type' => 'direct'],
                ['event_id' => $booking->event_id, 'created_by' => $client->id],
            );

            $conversation->participants()->syncWithoutDetaching([
                $client->id => ['joined_at' => now()->subDays(4)],
                $pro->id    => ['joined_at' => now()->subDays(4)],
            ]);

            if ($conversation->messages()->exists()) {
                continue;
            }

            $said = [
                [$client->id, 'Hi, looking forward to ' . ($booking->event?->title ?? 'the event') . '. Could you confirm what time you plan to arrive to set up?', 4],
                [$pro->id,    'Hello. We usually arrive two hours before guests, so that would be early afternoon. I will confirm the exact time once the venue gives us access.', 3],
                [$client->id, 'That works. One more thing: is there anything you need from the venue on the day, power or parking?', 1],
            ];

            foreach ($said as [$sender, $body, $daysAgo]) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'event_id'        => $booking->event_id,
                    'booking_id'      => $booking->id,
                    'sender_id'       => $sender,
                    'recipient_id'    => $sender === $pro->id ? $client->id : $pro->id,
                    'body'            => $body,
                    // No updated_at: the messages table has created_at alone,
                    // which is right for a thing that is written once.
                    'created_at'      => now()->subDays($daysAgo),
                ]);
            }
        }
    }

    /**
     * A working pattern: weekends open, two weekdays blocked.
     *
     * An empty availability page cannot show the difference between "free"
     * and "not answered", which is the one thing it is for.
     */
    private function availability(User $pro): void
    {
        $day = now()->startOfWeek();

        for ($i = 0; $i < 28; $i++) {
            $date = (clone $day)->addDays($i);

            // Weekends available, plus two blocked weekdays a month.
            $state = match (true) {
                in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true) => AvailabilityDay::AVAILABLE,
                in_array($i, [9, 17], true) => AvailabilityDay::UNAVAILABLE,
                default => null,
            };

            if ($state === null) {
                continue;
            }

            /*
             * A Carbon, not a date string.
             *
             * `day` is cast to a date and stored as midnight, so looking it
             * up by "2026-11-14" matched nothing and firstOrCreate inserted a
             * second row every run. SeederIdempotencyTest caught it, which is
             * exactly what that test is for.
             */
            AvailabilityDay::firstOrCreate(
                ['user_id' => $pro->id, 'day' => $date->copy()->startOfDay()],
                [
                    'state' => $state,
                    'note'  => $state === AvailabilityDay::UNAVAILABLE ? 'Already committed elsewhere' : null,
                ],
            );
        }
    }

    /**
     * One case, on a completed booking, with the history behind it.
     *
     * Severity 2 on purpose: it opens in Direct Resolution, which is the
     * first of the four steps, so the page shows a case moving rather than a
     * case already over.
     */
    private function dispute(User $pro): void
    {
        if (DisputeCase::where('professional_id', $pro->id)->exists()) {
            return;
        }

        $booking = Booking::where('supplier_id', $pro->id)
            ->where('status', 'completed')
            ->whereNotNull('client_id')
            ->with('event:id,title')
            ->first();

        if (! $booking) {
            return;
        }

        $case = DisputeCase::create([
            'booking_id'      => $booking->id,
            'category_id'     => $booking->category_id,
            'filed_by'        => $booking->client_id,
            'client_id'       => $booking->client_id,
            'professional_id' => $pro->id,
            'severity'        => 2,
            'priority'        => 'normal',
            'taxonomy'        => 'incomplete_service',
            'summary'         => 'Two of the agreed items were not delivered on the night, and the client is asking for part of the fee back.',
        ]);

        DisputeEvent::create([
            'dispute_case_id'    => $case->id,
            'actor_id'           => $booking->client_id,
            'actor_role'         => 'client',
            'action'             => 'filed',
            'reason'             => 'Filed by the client.',
            'visible_to_parties' => true,
            'created_at'         => now()->subDays(2),
        ]);

        DisputeEvent::create([
            'dispute_case_id'    => $case->id,
            'actor_id'           => $pro->id,
            'actor_role'         => 'professional',
            'action'             => 'responded',
            'reason'             => 'The professional has given their account of the evening.',
            'visible_to_parties' => true,
            'created_at'         => now()->subDay(),
        ]);
    }
}
