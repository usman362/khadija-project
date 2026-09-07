<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How does GigResource work?',
                'answer' => 'GigResource connects event organizers (clients) with event service professionals (photographers, caterers, DJs, planners and more). Create an account, browse professionals by service, send a request, agree the details through the built-in messaging, and book. You can post one request and let professionals respond, or send a request straight to a professional you have chosen.',
                'category' => 'General',
                'sort_order' => 1,
            ],
            [
                'question' => 'How do I join as a professional?',
                'answer' => 'Click "Join as Professional" to create your account. Once registered, complete your profile with your services, portfolio, and availability. Clients will be able to discover and book you directly through the platform.',
                'category' => 'General',
                'sort_order' => 2,
            ],
            [
                'question' => 'What payment methods are accepted?',
                'answer' => 'Major credit and debit cards. Card details are entered on our payment provider\'s own secure page and are never stored on GigResource.',
                'category' => 'Billing',
                'sort_order' => 3,
            ],
            [
                'question' => 'Can I cancel a booking?',
                'answer' => 'Yes, bookings can be cancelled according to our cancellation policy. Full refunds are available for cancellations made 30+ days before the event. Partial refunds apply for shorter notice periods. See our <a href="/cancellation-policy" style="color: var(--primary);">Cancellation & Refund Policy</a> for full details.',
                'category' => 'Billing',
                'sort_order' => 4,
            ],
            [
                'question' => 'Is there a free plan available?',
                'answer' => 'Yes! We offer a free Starter plan that lets you explore the platform with limited event and booking slots. Upgrade to a paid plan anytime to unlock more features, higher limits, and priority support.',
                'category' => 'Billing',
                'sort_order' => 5,
            ],
            [
                'question' => 'How is my data protected?',
                'answer' => 'We take data privacy seriously. All sensitive information is encrypted, payment credentials are stored securely via industry-standard encryption, and we never share your personal data with third parties without consent. Read our <a href="/privacy-policy" style="color: var(--primary);">Privacy Policy</a> for more details.',
                'category' => 'General',
                'sort_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
