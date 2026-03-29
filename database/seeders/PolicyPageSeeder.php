<?php

namespace Database\Seeders;

use App\Models\PolicyPage;
use Illuminate\Database\Seeder;

class PolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'slug'  => 'privacy-policy',
                'title' => 'Privacy Policy',
                'file'  => resource_path('views/policies/privacy.blade.php'),
            ],
            [
                'slug'  => 'payment-policy',
                'title' => 'Payment Policy',
                'file'  => resource_path('views/policies/payment.blade.php'),
            ],
            [
                'slug'  => 'cancellation-policy',
                'title' => 'Cancellation & Refund Policy',
                'file'  => resource_path('views/policies/cancellation.blade.php'),
            ],
        ];

        foreach ($policies as $data) {
            if (PolicyPage::where('slug', $data['slug'])->exists()) {
                continue;
            }

            $content = '<p>Content coming soon.</p>';

            // Try to extract content from old blade file
            if (file_exists($data['file'])) {
                $raw = file_get_contents($data['file']);

                // Extract content between <main> ... </main>
                if (preg_match('/<main>(.*?)<\/main>/s', $raw, $match)) {
                    $html = $match[1];

                    // Remove the outer <div class="container"> wrapper
                    $html = preg_replace('/^\s*<div class="container">\s*/s', '', $html);
                    $html = preg_replace('/\s*<\/div>\s*$/s', '', $html);

                    // Remove the <h1> and <p class="policy-date"> lines (we handle those in layout)
                    $html = preg_replace('/<h1>.*?<\/h1>\s*/s', '', $html);
                    $html = preg_replace('/<p class="policy-date">.*?<\/p>\s*/s', '', $html);

                    // Replace Blade config() calls with actual app name
                    $html = preg_replace('/\{\{\s*config\([\'"]app\.name[\'"],\s*[\'"].*?[\'"]\)\s*\}\}/', config('app.name', 'GigResource'), $html);

                    // Replace other Blade expressions
                    $html = preg_replace('/\{\{.*?parse_url.*?\}\}/', config('app.name', 'GigResource'), $html);

                    $content = trim($html);
                }
            }

            PolicyPage::create([
                'slug'      => $data['slug'],
                'title'     => $data['title'],
                'content'   => $content,
                'is_active' => true,
            ]);
        }
    }
}
