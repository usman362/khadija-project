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
                'question' => 'How does GIGS work?',
                'answer' => 'GIGS connects event organizers (clients) with verified service professionals (suppliers). Simply create an account, browse available professionals by category, send booking requests, discuss details through our built-in chat, and confirm your booking. Payments are processed securely through Stripe or PayPal.',
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
                'answer' => 'We accept all major credit and debit cards through Stripe, as well as PayPal. All transactions are encrypted and processed through PCI-compliant payment gateways to ensure your financial data is secure.',
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
