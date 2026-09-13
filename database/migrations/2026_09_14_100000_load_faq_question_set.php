<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The FAQ question set from the PM's answers of 6 September (OA-138).
 *
 * Worded to what the platform actually does today: where the PM's answer
 * basis describes something not built yet (automatic release of the balance,
 * a separate Insured badge, an ID-based professional Verified badge), the
 * answer says what is true now rather than promising it.
 *
 * Added only if the question is not already there, so an administrator's
 * edits are never overwritten, and removed on rollback only if unchanged.
 */
return new class extends Migration
{
    private function rows(): array
    {
        return [
            // Bookings & Payments
            ['Bookings & Payments', 'How does payment work when I book a Professional?',
             "Posting a request is free. When you finalize an agreement with a Professional, you pay a single \$2.99 request fee and the deposit set in your agreement (usually 30%). The deposit secures the date and is non-refundable. The rest of the price, the balance, is due on the date shown in your agreement."],
            ['Bookings & Payments', 'What if I need to cancel?',
             "You can cancel a booking from the Cancellations page. The deposit is never refunded. What you get back from the balance depends on notice: more than 30 days before the event, the full balance; 14 to 30 days before, half of the balance; less than 14 days before, nothing from the balance. If you cancel a whole event, each booking is refunded on its own under these same rules."],
            ['Bookings & Payments', 'What is an Emergency Service Request?',
             "An Emergency Request is for a same-day or urgent booking. It adds a flat 25% to the Professional's price, to reflect last-minute availability. The total, including the 25%, is shown before you agree to anything."],
            // Trust & Safety
            ['Trust & Safety', 'How do I know a Professional is legitimate?',
             "Look for the Verified badge on their profile. It means our team has approved their license, liability insurance and workers' compensation. For alcohol, catering and security services, a bid can only be accepted from a Verified Professional. Every profile also has a link to research the business independently on BBB.org."],
            ['Trust & Safety', 'What happens if something goes wrong at my event?',
             "You can open a dispute with GigResource within 14 days of the event ending. We review evidence from both sides and issue a platform decision. This does not limit your other rights: you can also dispute the charge directly with your bank or card issuer (most allow up to 120 days), or pursue small claims court at your own expense if you are not satisfied with the outcome."],
            ['Trust & Safety', 'Does GigResource run background checks on Professionals?',
             "Not unless a category says so. Verification covers a Professional's license and insurance, not criminal history."],
            // Account & Membership
            ['Account & Membership', 'How do I verify my account?',
             "Go to your profile and choose Verify your identity, or open Requests & Submissions and choose Verify Your Identity. Upload a clear photo of your ID. When our team approves it, the Verified Client badge appears on your profile."],
            ['Account & Membership', 'What is Elite Membership for Professionals?',
             "Elite is a paid plan for Professionals with a lower commission rate and more visibility. The plans and what each includes are on our Pricing page."],
            ['Account & Membership', 'Where do I see my badges?',
             "Your badges are on your profile, and every badge, earned or not yet, is on the See all badges page with how to earn it."],
            // For Professionals
            ['For Professionals', 'How is my commission calculated?',
             "Your commission rate depends on your membership plan (Free, Standard or Elite) and is applied when the client's payment is taken."],
            ['For Professionals', 'Can I bid on any job?',
             "Yes, in every category. For alcohol, catering and security services, a client can only accept your bid once your license and insurance are verified."],
            ['For Professionals', 'How does the influencer referral program work?',
             "Influencers earn a one-time reward for each Elite Professional they refer. It is paid by GigResource, not taken from the Professional's fees, and is capped at 40 referrals in any 90 days."],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('faqs')) {
            return;
        }

        $order = (int) DB::table('faqs')->max('sort_order');

        foreach ($this->rows() as [$category, $question, $answer]) {
            if (DB::table('faqs')->where('question', $question)->exists()) {
                continue;
            }

            DB::table('faqs')->insert([
                'category' => $category, 'question' => $question, 'answer' => $answer,
                'sort_order' => ++$order, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('faqs')) {
            return;
        }

        foreach ($this->rows() as [, $question, $answer]) {
            DB::table('faqs')->where('question', $question)->where('answer', $answer)->delete();
        }
    }
};
