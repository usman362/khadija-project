<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\OnlyOutsideProduction;

/**
 * A few requests on one demo professional's Requests & Submissions screen.
 *
 * The screen sorts what you are part of into needs-your-action / under review /
 * completed / closed, and on an empty account all four tiles read zero and
 * nothing can be seen working. These are the four states, one each.
 *
 * One of them is a Change Order sent TO the professional, because "Needs Your
 * Action" only ever means a dual-approval form waiting on this person — and
 * with everything filed BY them, that tile could never be anything but zero.
 */
class DemoFormSubmissionsSeeder extends Seeder
{
    use OnlyOutsideProduction;

    public function run(): void
    {
        if ($this->refusedOnProduction()) {
            return;
        }

        $pro = User::where('email', 'elena.demo@example.test')->first();

        if (! $pro) {
            $this->command?->warn('DemoFormSubmissionsSeeder: elena.demo@example.test not found — skipped.');

            return;
        }

        $booking = Booking::where('supplier_id', $pro->id)->with('event')->latest('id')->first();
        $client = $booking?->client_id ?? User::role('client')->value('id');

        $rows = [
            // Waiting on Elena: the client proposed a change, she has to answer.
            [
                'form_key'        => 'change_order',
                'submitted_by'    => $client,
                'submitted_role'  => 'client',
                'counterparty_id' => $pro->id,
                'approval_status' => 'pending',
                'status'          => 'submitted',
                'payload'         => [
                    'what_changes' => 'date',
                    'detail'       => 'The ceremony has moved an hour later, so coverage would start at 3pm rather than 2pm.',
                ],
            ],
            // With the team: no approval step, so it waits on nobody here.
            [
                'form_key'       => 'support_request',
                'submitted_by'   => $pro->id,
                'submitted_role' => 'professional',
                'status'         => 'submitted',
                'payload'        => [
                    'topic'   => 'payments',
                    'details' => 'A payout from last month has not arrived and I would like someone to check it.',
                ],
            ],
            // Finished: a change order she accepted.
            [
                'form_key'        => 'change_order',
                'submitted_by'    => $client,
                'submitted_role'  => 'client',
                'counterparty_id' => $pro->id,
                'approval_status' => 'accepted',
                'approved_at'     => now()->subDays(4),
                'status'          => 'submitted',
                'payload'         => [
                    'what_changes' => 'scope',
                    'detail'       => 'Adding a second shooter for the reception.',
                ],
            ],
            // Closed: she withdrew it herself.
            [
                'form_key'       => 'content_report',
                'submitted_by'   => $pro->id,
                'submitted_role' => 'professional',
                'status'         => 'withdrawn',
                'payload'        => [
                    'what'    => 'message',
                    'details' => 'Reported in error — the message was from a colleague, not a stranger.',
                ],
            ],
        ];

        $made = 0;

        foreach ($rows as $row) {
            // Keyed on the form and who filed it, so re-running does not stack
            // four more onto the same account.
            $exists = FormSubmission::where('form_key', $row['form_key'])
                ->where('submitted_by', $row['submitted_by'])
                ->where('status', $row['status'])
                ->where('approval_status', $row['approval_status'] ?? null)
                ->exists();

            if ($exists) {
                continue;
            }

            if ($booking) {
                $row['subject_type'] = Booking::class;
                $row['subject_id'] = $booking->id;
            }

            FormSubmission::create($row);
            $made++;
        }

        $this->command?->info("Seeded {$made} demo request(s).");
    }
}
