<?php

namespace Database\Seeders;

use App\Models\PageSection;
use Illuminate\Database\Seeder;

/**
 * Seeds the landing page's editable content with exactly what the template
 * already displays, so switching the page over to the database changes nothing
 * on screen. Uses updateOrCreate on (page, key) — safe to re-run, and it will
 * not overwrite wording an admin has since changed unless that section is
 * deleted first.
 */
class PageSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $i => $s) {
            PageSection::updateOrCreate(
                ['page' => 'landing', 'key' => $s['key']],
                array_merge(['sort_order' => $i * 10, 'is_active' => true], $s)
            );
        }

        PageSection::forgetCache();
    }

    private function sections(): array
    {
        $img = fn (string $id, int $w) => "https://images.unsplash.com/photo-{$id}?w={$w}&q=80&auto=format&fit=crop";

        return [
            [
                'key'  => 'branding',
                'body' => 'The all-in-one marketplace connecting clients with verified event professionals to plan, manage, and deliver unforgettable events.',
            ],
            [
                'key'        => 'hero',
                // "|" is a line break and *stars*/_underscores_ colour a phrase.
                // A plain-text convention beats letting an admin paste HTML.
                'heading'    => 'Where Events|Come to Life.|*Your Vision.* _Our Network._',
                'subheading' => 'The all-in-one marketplace to connect with verified event professionals or find top talent to plan, manage, and deliver unforgettable events.',
                'image_path' => $img('1464366400600-7168b8af9bc3', 900),
                'payload'    => [
                    'badge_title' => 'Booking Confirmed! 🎉',
                    'badge_text'  => 'Your event is in great hands.',
                    'trust_text'  => 'Trusted by event professionals & clients',
                    'trust_sub'   => 'across the U.S.',
                    'roles' => [
                        ['title' => "I'm a Professional", 'text' => 'Grow your business and get discovered', 'cta' => 'Join as a Pro', 'image' => $img('1511578314322-379afb476865', 200)],
                        ['title' => "I'm a Client", 'text' => 'Find the perfect team for your event', 'cta' => 'Find Talent', 'image' => $img('1469371670807-013ccf25f16a', 200)],
                    ],
                ],
            ],
            [
                'key'     => 'trust_bar',
                'payload' => ['items' => [
                    ['title' => 'Verified Professionals', 'text' => 'Profile & business verification'],
                    ['title' => 'Secure Payments',        'text' => 'Secure, protected transactions'],
                    ['title' => 'Guided Support',         'text' => 'Smart help at every step'],
                    ['title' => 'Privacy Controls',       'text' => 'You choose what you share'],
                    ['title' => 'Built for Events',       'text' => 'Smart tools to plan with confidence'],
                ]],
            ],
            [
                'key'     => 'categories',
                'heading' => 'Explore Popular Categories',
                'body'    => 'View all categories',
            ],
            [
                'key'     => 'how_it_works',
                'heading' => 'How GigResource Works',
                'payload' => ['steps' => [
                    ['title' => 'Tell Us What You Need', 'text' => 'Share your event details, budget, and preferences.'],
                    ['title' => 'Get Matched',           'text' => 'We connect you with the best professionals.'],
                    ['title' => 'Compare & Choose',      'text' => 'Review profiles, portfolios, reviews, and quotes.'],
                    ['title' => 'Book with Confidence',  'text' => 'Secure payments, contracts, and clear communication.'],
                    ['title' => 'Deliver & Celebrate',   'text' => 'Enjoy a seamless event experience.'],
                ]],
            ],
            [
                'key'        => 'assistance',
                'heading'    => 'Choose Your Level of Assistance',
                'subheading' => "You're in control. Each level unlocks more capability — from fully manual to fully automated.",
                'payload'    => ['cards' => [
                    [
                        'title' => 'Manual', 'text' => 'You handle everything.',
                        'bullets' => "Search & compare professionals\nMessage & negotiate\nManage bookings & payments",
                        'footer' => 'Best for experienced planners',
                        'image' => $img('1486312338219-ce68d2c6f44d', 300),
                    ],
                    [
                        'title' => 'Semi-Assisted', 'text' => 'Guidance the whole way.',
                        'bullets' => "Smart suggestions\nRecommendations\nTemplates & best practices",
                        'footer' => 'Best for growing planners',
                        'image' => $img('1511795409834-ef04bbd61622', 300),
                    ],
                    [
                        'title' => 'Maximum Assistance', 'text' => 'We handle the details.',
                        'bullets' => "Matches & outreach\nNegotiation assistant\nSmart booking workflows",
                        'footer' => 'Best for busy professionals & clients',
                        'image' => $img('1519671482749-fd09be7ccebf', 300),
                    ],
                ]],
            ],
            [
                'key'     => 'why_choose',
                'heading' => 'Why Choose GigResource?',
                'payload' => ['items' => [
                    ['title' => 'Quality You Can Trust',    'text' => 'Every professional is verified & reviewed'],
                    ['title' => 'All-in-One Convenience',   'text' => 'Everything you need in one place'],
                    ['title' => 'Time & Cost Saving',       'text' => 'Smart tools to save you time & money'],
                    ['title' => 'Flexible for Every Event', 'text' => 'Any type, any size, in your area'],
                ]],
            ],
            [
                'key'        => 'video',
                'heading'    => 'GigResource',
                'body'       => '',
                'image_path' => $img('1492684223066-81342ee5ff30', 800),
            ],
            [
                'key'     => 'testimonials',
                'heading' => 'Loved by Our Community',
            ],
            [
                'key'     => 'value_band',
                'payload' => ['items' => [
                    ['title' => 'VERIFIED',   'text' => 'Professionals',        'note' => 'Trust badges & profile verification'],
                    ['title' => 'ALL EVENTS', 'text' => 'Event Solutions',      'note' => 'Solutions for events of any size'],
                    ['title' => '3 LEVELS',   'text' => 'Assistance',           'note' => 'Manual · Semi-Assisted · Maximum'],
                    ['title' => 'SECURE',     'text' => 'Payments & Contracts', 'note' => 'Payment protection & e-signatures'],
                    ['title' => 'SUPPORT',    'text' => 'Help Center',          'note' => 'Guides & help when you need it'],
                ]],
            ],
            [
                'key'        => 'pricing',
                'heading'    => 'Simple, Transparent Pricing',
                'subheading' => "Choose the plan that's right for you.",
                'body'       => 'All plans include secure payments and payment protection.',
            ],
            [
                'key'        => 'cta_banner',
                'heading'    => 'Ready to Create|Unforgettable Events?',
                'subheading' => 'Join thousands of professionals and clients who trust GigResource to bring their events to life.',
                'image_path' => $img('1464366400600-7168b8af9bc3', 1200),
                'payload'    => ['buttons' => [
                    ['title' => 'Join as a Professional'],
                    ['title' => 'Hire Top Talent'],
                ]],
            ],
        ];
    }
}
