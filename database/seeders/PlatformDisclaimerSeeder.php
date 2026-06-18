<?php

namespace Database\Seeders;

use App\Models\PolicyPage;
use Illuminate\Database\Seeder;

/**
 * Platform Disclaimer page — surfaces Peter Zylstra's confirmed platform
 * limitations (Developer Feedback v1.1, §1.2). Idempotent: safe to re-run.
 */
class PlatformDisclaimerSeeder extends Seeder
{
    public function run(): void
    {
        $app = config('app.name', 'GigResource');

        $content = <<<HTML
<p>This page sets out important limitations about how {$app} works. By creating an
account or using the platform, you acknowledge and accept the points below. They also
apply to any marketing or promotional material you may see about {$app}.</p>

<div class="highlight-box">
    <strong>In short:</strong> {$app} is an AI-assisted marketplace that helps clients and
    professionals find and work with one another. We are not a party to the agreements you
    make, we do not provide professional advice, and we do not guarantee outcomes.
</div>

<h2>1. Nature of the Platform</h2>
<p>{$app} provides tools and AI-assisted features that help clients and professionals
discover each other, communicate, and arrange services. Our role is limited to providing
that technology and marketplace. The actual services are arranged and delivered directly
between clients and professionals.</p>

<h2>2. No Expert Advice, Recommendations, or Negotiation Management</h2>
<p>{$app} <strong>does not provide expert advice or recommendations</strong>, and
<strong>does not manage negotiations</strong> on behalf of any user. Our level of
assistance is focused on involving AI more helpfully at each step — for example, surfacing
information, drafting suggestions, and organising details. Any decision about who to hire,
what to pay, or what terms to accept is yours alone. You are responsible for your own due
diligence before entering into any agreement.</p>

<h2>3. Support Is Not Available 24/7</h2>
<p>Our support team operates during business hours and <strong>is not available 24 hours a
day, 7 days a week</strong>. We aim to respond as promptly as we reasonably can, but we do
not promise round-the-clock or immediate assistance.</p>

<h2>4. Background Checks Are Not Guaranteed</h2>
<p>{$app} <strong>does not guarantee background checks</strong> on any user. Where a
background or verification check is available, it may be offered as a separate paid option
and is performed by third-party providers. The absence of a check does not imply anything
about a user, and the presence of one does not constitute a guarantee or endorsement by
{$app}.</p>

<h2>5. Third-Party Data &amp; Privacy</h2>
<p>The privacy of your information depends in part on what you choose to share and on the
third-party services and integrations you use. {$app} <strong>cannot control how third
parties use data</strong> that you provide to them or that flows through their services.
Please review the privacy practices of any third party before sharing information, and see
our <a href="/privacy-policy">Privacy Policy</a> for how {$app} itself handles your data.</p>

<h2>6. No Guaranteed Outcomes</h2>
<p>{$app} makes <strong>no guarantees to any user regarding membership outcomes</strong> —
including, but not limited to, the number or quality of leads, bookings, proposals, hires,
earnings, or results of any kind. Membership gives you access to platform features; it does
not promise any particular commercial result.</p>

<h2>7. Questions</h2>
<p>If you have questions about this disclaimer, please contact our support team through your
account. This page may be updated from time to time; the version shown here is the current
one.</p>
HTML;

        PolicyPage::updateOrCreate(
            ['slug' => 'platform-disclaimer'],
            [
                'title'     => 'Platform Disclaimer',
                'content'   => $content,
                'is_active' => true,
            ]
        );
    }
}
