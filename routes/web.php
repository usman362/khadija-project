<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Client\ClientBookingController;
use App\Http\Controllers\Client\ClientChatController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientEventController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Professional\ProfessionalChatController;
use App\Http\Controllers\Professional\ProfessionalDashboardController;
use App\Http\Controllers\Professional\ProfessionalEarningsController;
use App\Http\Controllers\Professional\ProfessionalGigController;
use App\Http\Controllers\Professional\ProfessionalPackageController;
use App\Http\Controllers\Professional\ProfessionalProposalController;
use App\Http\Controllers\Professional\ProfessionalProfileController;
use App\Http\Controllers\Professional\ProfessionalReviewController;
use App\Http\Controllers\Professional\ProfessionalTransactionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Dashboard\AdminMembershipPlanController;
use App\Http\Controllers\Dashboard\AgreementLogPageController;
use App\Http\Controllers\Dashboard\AgreementPageController;
use App\Http\Controllers\Dashboard\BookingPageController;
use App\Http\Controllers\Dashboard\ChatPageController;
use App\Http\Controllers\Dashboard\AdminCategoryController;
use App\Http\Controllers\Dashboard\AdminEventController;
use App\Http\Controllers\Dashboard\AdminProfileController;
use App\Http\Controllers\Dashboard\AdminFaqController;
use App\Http\Controllers\Dashboard\AdminPolicyController;
use App\Http\Controllers\Dashboard\AdminSettingsController;
use App\Http\Controllers\Dashboard\EventPageController;
use App\Http\Controllers\Dashboard\MembershipPlanPageController;
use App\Http\Controllers\Dashboard\MessagePageController;
use App\Http\Controllers\Dashboard\PaymentPageController;
use App\Http\Controllers\Dashboard\PermissionPageController;
use App\Http\Controllers\Dashboard\RolePageController;
use App\Http\Controllers\Dashboard\UserAccessPageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\AiBudgetAllocatorController;
use App\Http\Controllers\AiChatbotController;
use App\Http\Controllers\AiReviewWriterController;
use App\Http\Controllers\AiVendorMatchmakingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Dashboard\AdminBlogCategoryController;
use App\Http\Controllers\Dashboard\AdminBlogPostController;
use App\Http\Controllers\PolicySignatureController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');

// ── Public Professional Profile ───────────────────────────────────────
// The "store front" a visitor lands on when browsing a pro. No auth.
Route::get('/pro/{user}', [\App\Http\Controllers\Public\ProfessionalProfileShowController::class, 'show'])
    ->name('public.professional.show');

// ── Public Browse (filterable professional directory) ─────────────────
// Where the landing-page hero search, A-Z chips, and events-categories
// mega-panel all converge. Supports ?q= ?city= ?rating_min= ?verified=
// ?sort= query params.
Route::get('/browse', [\App\Http\Controllers\Public\BrowseProfessionalsController::class, 'index'])
    ->middleware('auth')
    ->name('public.browse');

// ── How It Works (standalone explainer page) ──────────────────────────
// Dual-audience explainer with client/pro flows, comparison table, and
// FAQ. The navbar "How It Works" link points here.
Route::view('/how-it-works', 'public.how-it-works')
    ->name('public.how-it-works');

// ── Reviews (authenticated participants of completed bookings) ────────
Route::middleware('auth')->group(function () {
    Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::patch('/reviews/{review}/respond', [\App\Http\Controllers\ReviewController::class, 'respond'])->name('reviews.respond');
});

// ── Influencer Module ─────────────────────────────────────────────────
Route::get('/join-as-influencer', [\App\Http\Controllers\Influencer\JoinAsInfluencerController::class, 'show'])->name('influencer.join');
Route::post('/join-as-influencer', [\App\Http\Controllers\Influencer\JoinAsInfluencerController::class, 'store'])->name('influencer.join.submit');
// Affiliate application status (logged-in, before/without portal access — NOT permission-gated)
Route::get('/affiliate/status', [\App\Http\Controllers\Influencer\JoinAsInfluencerController::class, 'status'])
    ->middleware('auth')->name('influencer.status');
Route::get('/ref/{code}', \App\Http\Controllers\Influencer\ReferralLandingController::class)->name('influencer.referral');

// Policy pages
Route::get('/privacy-policy', function () {
    $policy = \App\Models\PolicyPage::findBySlug('privacy-policy');
    $existingSignature = auth()->check()
        ? \App\Models\PolicySignature::where('user_id', auth()->id())->where('policy_type', 'privacy_policy')->latest()->first()
        : null;
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'Privacy Policy', 'policyType' => 'privacy_policy', 'existingSignature' => $existingSignature]);
})->name('privacy-policy');

Route::get('/ai-agreement', function () {
    $policy = \App\Models\PolicyPage::findBySlug('ai-usage-agreement');
    $existingSignature = auth()->check()
        ? \App\Models\PolicySignature::where('user_id', auth()->id())->where('policy_type', 'ai_usage_agreement')->latest()->first()
        : null;
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'AI Usage Agreement', 'policyType' => 'ai_usage_agreement', 'existingSignature' => $existingSignature]);
})->name('ai-agreement');

Route::get('/payment-policy', function () {
    $policy = \App\Models\PolicyPage::findBySlug('payment-policy');
    $existingSignature = auth()->check()
        ? \App\Models\PolicySignature::where('user_id', auth()->id())->where('policy_type', 'terms_of_service')->latest()->first()
        : null;
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'Payment Policy', 'policyType' => null, 'existingSignature' => null]);
})->name('payment-policy');

Route::get('/cancellation-policy', function () {
    $policy = \App\Models\PolicyPage::findBySlug('cancellation-policy');
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'Cancellation & Refund Policy', 'policyType' => null, 'existingSignature' => null]);
})->name('cancellation-policy');

Route::get('/dmca-policy', function () {
    $policy = \App\Models\PolicyPage::findBySlug('dmca-policy');
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'DMCA Takedown Policy', 'policyType' => null, 'existingSignature' => null]);
})->name('dmca-policy');

Route::post('/dmca-policy/report', [\App\Http\Controllers\DmcaReportController::class, 'store'])
    ->middleware('throttle:5,60')->name('dmca-policy.report');

// Platform Disclaimer — Peter's confirmed platform limitations (Developer Feedback v1.1 §1.2)
Route::get('/platform-disclaimer', function () {
    $policy = \App\Models\PolicyPage::findBySlug('platform-disclaimer');
    return view('policies.show', ['policy' => $policy, 'fallbackTitle' => 'Platform Disclaimer', 'policyType' => null, 'existingSignature' => null]);
})->name('platform-disclaimer');

// About Us
Route::view('/about-us', 'about')->name('about-us');

// Internal — designer page inventory (printable PDF spec).
// Not linked from navbar; share the URL only with the design team.
Route::view('/design-spec', 'design-spec')->name('design-spec');

// Internal — design scope breakdown (custom vs dev-handled split).
// Companion doc for clarifying budget with the client.
Route::view('/design-breakdown', 'design-breakdown')->name('design-breakdown');

// Internal — custom-design-only spec (28 pages the designer mocks up).
Route::view('/design-spec-custom', 'design-spec-custom')->name('design-spec-custom');

// Events & Categories — passes live DB categories so the "Explore by category"
// grid can link directly to /category/{slug} landing pages.
Route::get('/events-categories', function () {
    $allCategories = \App\Models\Category::active()
        ->whereNull('parent_id')
        ->with('allChildren')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    return view('events-categories', compact('allCategories'));
})->name('events-categories');

// Per-category landing page — SEO-friendly URL that highlights featured
// pros and links into /browse for full results.
Route::get('/category/{slug}', [\App\Http\Controllers\Public\CategoryLandingController::class, 'show'])
    ->name('public.category');

// Public package detail — the browsable, shareable page for a pro's package.
Route::get('/package/{package:slug}', [\App\Http\Controllers\Public\PackageController::class, 'show'])
    ->name('public.package');

// XML sitemap — generated on the fly so newly-added pros / blog posts show
// up the next time a crawler hits /sitemap.xml. Cached for an hour to keep
// the DB query out of every robot ping.
Route::get('/sitemap.xml', function () {
    $xml = \Illuminate\Support\Facades\Cache::remember('sitemap.xml', 3600, function () {
        $urls = [];
        // Static public pages
        foreach ([
            ['url' => route('landing'),             'priority' => '1.0', 'changefreq' => 'weekly'],
            // /browse is now login-gated — excluded from the public sitemap.
            ['url' => route('events-categories'),   'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => route('public.how-it-works'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => route('public.faq'),          'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => route('blog.index'),          'priority' => '0.7', 'changefreq' => 'weekly'],
        ] as $row) {
            $urls[] = $row;
        }
        // Active category landing pages
        \App\Models\Category::active()->select('slug', 'updated_at')->get()->each(function ($cat) use (&$urls) {
            $urls[] = [
                'url'        => route('public.category', $cat->slug),
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => $cat->updated_at?->toAtomString(),
            ];
        });

        // Published professional profiles
        \App\Models\User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', \App\Domain\Auth\Enums\RoleName::SUPPLIER->value))
            ->select('id', 'updated_at')
            ->chunk(500, function ($pros) use (&$urls) {
                foreach ($pros as $pro) {
                    $urls[] = [
                        'url'        => route('public.professional.show', $pro),
                        'priority'   => '0.6',
                        'changefreq' => 'weekly',
                        'lastmod'    => $pro->updated_at?->toAtomString(),
                    ];
                }
            });
        // Published blog posts
        if (class_exists(\App\Models\Post::class)) {
            \App\Models\Post::query()
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('posts', 'published_at'),
                       fn ($q) => $q->whereNotNull('published_at'))
                ->select('id', 'slug', 'updated_at')
                ->chunk(500, function ($posts) use (&$urls) {
                    foreach ($posts as $post) {
                        $urls[] = [
                            'url'        => route('blog.show', $post->slug ?? $post->id),
                            'priority'   => '0.5',
                            'changefreq' => 'monthly',
                            'lastmod'    => $post->updated_at?->toAtomString(),
                        ];
                    }
                });
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($u['url']) . '</loc>' . "\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $u['lastmod'] . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>' . ($u['changefreq'] ?? 'weekly') . '</changefreq>' . "\n";
            $xml .= '    <priority>' . ($u['priority'] ?? '0.5') . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';
        return $xml;
    });

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// Health check endpoint for external uptime monitoring (UptimeRobot,
// Better Stack, Pingdom). Returns 200 with a JSON body when the app is
// healthy; 503 if a critical dependency (database) is unreachable. The
// built-in /up still works for a lightweight ping; /health is the deep
// check we point monitors at.
Route::get('/health', function () {
    $checks = [];
    $status = 200;

    // DB ping
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'fail';
        $status = 503;
    }

    // Cache round-trip
    try {
        \Illuminate\Support\Facades\Cache::put('__health', '1', 5);
        $checks['cache'] = \Illuminate\Support\Facades\Cache::get('__health') === '1' ? 'ok' : 'fail';
        if ($checks['cache'] !== 'ok') $status = 503;
    } catch (\Throwable $e) {
        $checks['cache'] = 'fail';
        $status = 503;
    }

    return response()->json([
        'status'    => $status === 200 ? 'ok' : 'degraded',
        'checks'    => $checks,
        'version'   => config('app.version', '1.0'),
        'timestamp' => now()->toIso8601String(),
    ], $status);
})->name('health');

// Public Blog
Route::get('/blog',          [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}',   [BlogController::class, 'show'])->name('blog.show');

// Public FAQ — pulls active FAQs from the same store the admin manages.
Route::get('/faq', function () {
    $faqs = \App\Models\Faq::active()->ordered()->get();
    // Group by category so the page can render category sections.
    // Items without a category fall under "General".
    $grouped = $faqs->groupBy(fn ($f) => $f->category ?: 'General');
    return view('public.faq', compact('faqs', 'grouped'));
})->name('public.faq');

Auth::routes();

// Role-themed login pages — separate URL per audience, all posting to the
// same /login handler (auth is role-agnostic; only the look differs).
//   /login                 → Client  (orange, default — defined by Auth::routes)
//   /professional/login    → Professional (blue)
//   /affiliate/login       → Affiliate (orange)
//   /admin/login           → Admin (dark "Admin Portal")
Route::get('/professional/login', fn () => view('auth.login', ['loginRole' => 'supplier']))
    ->middleware('guest')->name('login.professional');
Route::get('/affiliate/login', fn () => view('auth.login', ['loginRole' => 'influencer']))
    ->middleware('guest')->name('login.affiliate');
Route::get('/admin/login', fn () => view('auth.admin-login'))
    ->middleware('guest')->name('admin.login');

// Policy E-Signature (auth required)
Route::post('/policy/sign', [PolicySignatureController::class, 'sign'])->middleware('auth')->name('policy.sign');

// Role Switching (auth required)
Route::middleware('auth')->group(function () {
    Route::post('/role/switch', [RoleSwitchController::class, 'switch'])->name('role.switch');
    Route::post('/role/enable', [RoleSwitchController::class, 'enable'])->name('role.enable');
});

// Account Deletion (auth required)
Route::middleware('auth')->group(function () {
    Route::post('/account/deletion/request', [AccountDeletionController::class, 'request'])->name('account.deletion.request');
    Route::get('/account/deletion/restore',  [AccountDeletionController::class, 'showRestore'])->name('account.deletion.restore.show');
    Route::post('/account/deletion/restore', [AccountDeletionController::class, 'restore'])->name('account.deletion.restore');

    // Account Reactivation Payment callbacks
    Route::get('/account/reactivation/success', [AccountDeletionController::class, 'reactivationSuccess'])->name('account.reactivation.success');
    Route::get('/account/reactivation/cancel',  [AccountDeletionController::class, 'reactivationCancel'])->name('account.reactivation.cancel');

    // AI Chatbot (user-facing)
    Route::prefix('ai-chatbot')->name('ai-chatbot.')->group(function () {
        Route::post('/chat',                        [AiChatbotController::class, 'chat'])->name('chat');
        Route::get('/conversations',                [AiChatbotController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{conversation}', [AiChatbotController::class, 'show'])->name('show');
        Route::delete('/conversations/{conversation}', [AiChatbotController::class, 'destroy'])->name('destroy');
    });

    // AI Budget Allocator (plan-gated)
    // AI Toolkit hub — every tool for this user (catalog-driven, live + planned).
    Route::get('/ai-tools', [\App\Http\Controllers\AiToolkitController::class, 'index'])->name('ai-tools.index');

    // AI Availability Optimizer (professional).
    Route::get('/ai-tools/availability-optimizer', [\App\Http\Controllers\AiAvailabilityOptimizerController::class, 'show'])->name('ai-tools.availability-optimizer');
    // AI Event Planner (client).
    Route::get('/ai-tools/event-planner', [\App\Http\Controllers\AiEventPlannerController::class, 'show'])->name('ai-tools.event-planner');
    // AI Theme & Style Advisor (client).
    Route::get('/ai-tools/theme-advisor', [\App\Http\Controllers\AiThemeAdvisorController::class, 'show'])->name('ai-tools.theme-advisor');
    // AI Timeline Builder (client).
    Route::get('/ai-tools/timeline-builder', [\App\Http\Controllers\AiTimelineBuilderController::class, 'show'])->name('ai-tools.timeline-builder');
    // AI Venue Analyzer (client).
    Route::get('/ai-tools/venue-analyzer', [\App\Http\Controllers\AiVenueAnalyzerController::class, 'show'])->name('ai-tools.venue-analyzer');
    // AI Guest Capacity Planner (client).
    Route::get('/ai-tools/guest-capacity', [\App\Http\Controllers\AiGuestCapacityController::class, 'show'])->name('ai-tools.guest-capacity');
    // AI Package Builder (professional).
    Route::get('/ai-tools/package-builder', [\App\Http\Controllers\AiPackageBuilderController::class, 'show'])->name('ai-tools.package-builder');
    // AI Portfolio Optimizer (professional).
    Route::get('/ai-tools/portfolio-optimizer', [\App\Http\Controllers\AiPortfolioOptimizerController::class, 'show'])->name('ai-tools.portfolio-optimizer');
    // AI membership / pricing page — the target for every AI tool's "Upgrade for
    // more AI" banner. Reuses the plans page controller (auth-only, no extra
    // permission gate) so any signed-in pro can view and pick a plan.
    Route::get('/membership', [MembershipPlanPageController::class, 'index'])->name('membership.plans');

    // AI Checklist Generator (client).
    Route::get('/ai-tools/checklist-generator', [\App\Http\Controllers\AiChecklistGeneratorController::class, 'show'])->name('ai-tools.checklist-generator');
    // AI Bid Optimizer (professional).
    Route::get('/ai-tools/bid-optimizer', [\App\Http\Controllers\AiBidOptimizerController::class, 'show'])->name('ai-tools.bid-optimizer');
    // AI Upsell Assistant (professional).
    Route::get('/ai-tools/upsell-assistant', [\App\Http\Controllers\AiUpsellAssistantController::class, 'show'])->name('ai-tools.upsell-assistant');
    // AI Contract Assistant (both).
    Route::get('/ai-tools/contract-assistant', [\App\Http\Controllers\AiContractAssistantController::class, 'show'])->name('ai-tools.contract-assistant');
    // AI Message Assistant (both).
    Route::get('/ai-tools/message-assistant', [\App\Http\Controllers\AiMessageAssistantController::class, 'show'])->name('ai-tools.message-assistant');
    // AI Translator (both).
    Route::get('/ai-tools/translator', [\App\Http\Controllers\AiTranslatorController::class, 'show'])->name('ai-tools.translator');

    // ── Dynamic compute endpoints for the 14 tools above (real local engines, JSON) ──
    Route::post('/ai-tools/availability-optimizer/compute', [\App\Http\Controllers\AiAvailabilityOptimizerController::class, 'compute'])->middleware('ai.level')->name('ai-tools.availability-optimizer.compute');
    Route::post('/ai-tools/event-planner/compute',          [\App\Http\Controllers\AiEventPlannerController::class, 'compute'])->middleware('ai.level')->name('ai-tools.event-planner.compute');
    Route::post('/ai-tools/theme-advisor/compute',          [\App\Http\Controllers\AiThemeAdvisorController::class, 'compute'])->middleware('ai.level')->name('ai-tools.theme-advisor.compute');
    Route::post('/ai-tools/timeline-builder/compute',       [\App\Http\Controllers\AiTimelineBuilderController::class, 'compute'])->middleware('ai.level')->name('ai-tools.timeline-builder.compute');
    Route::post('/ai-tools/venue-analyzer/compute',         [\App\Http\Controllers\AiVenueAnalyzerController::class, 'compute'])->middleware('ai.level')->name('ai-tools.venue-analyzer.compute');
    Route::post('/ai-tools/guest-capacity/compute',         [\App\Http\Controllers\AiGuestCapacityController::class, 'compute'])->middleware('ai.level')->name('ai-tools.guest-capacity.compute');
    Route::post('/ai-tools/package-builder/compute',        [\App\Http\Controllers\AiPackageBuilderController::class, 'compute'])->middleware('ai.level')->name('ai-tools.package-builder.compute');
    Route::post('/ai-tools/portfolio-optimizer/compute',    [\App\Http\Controllers\AiPortfolioOptimizerController::class, 'compute'])->middleware('ai.level')->name('ai-tools.portfolio-optimizer.compute');
    Route::post('/ai-tools/checklist-generator/compute',    [\App\Http\Controllers\AiChecklistGeneratorController::class, 'compute'])->middleware('ai.level')->name('ai-tools.checklist-generator.compute');
    Route::post('/ai-tools/bid-optimizer/compute',          [\App\Http\Controllers\AiBidOptimizerController::class, 'compute'])->middleware('ai.level')->name('ai-tools.bid-optimizer.compute');
    Route::post('/ai-tools/upsell-assistant/compute',       [\App\Http\Controllers\AiUpsellAssistantController::class, 'compute'])->middleware('ai.level')->name('ai-tools.upsell-assistant.compute');
    Route::post('/ai-tools/contract-assistant/compute',     [\App\Http\Controllers\AiContractAssistantController::class, 'compute'])->middleware('ai.level')->name('ai-tools.contract-assistant.compute');
    Route::post('/ai-tools/message-assistant/compute',      [\App\Http\Controllers\AiMessageAssistantController::class, 'compute'])->middleware('ai.level')->name('ai-tools.message-assistant.compute');
    Route::post('/ai-tools/translator/compute',             [\App\Http\Controllers\AiTranslatorController::class, 'compute'])->middleware('ai.level')->name('ai-tools.translator.compute');

    Route::get('/ai-tools/budget-allocator',  [AiBudgetAllocatorController::class, 'show'])->name('ai-tools.budget-allocator');
    Route::post('/ai-tools/budget-allocator', [AiBudgetAllocatorController::class, 'allocate'])->middleware('ai.level')->name('ai-tools.budget-allocator.allocate');

    // AI Vendor Matchmaking (plan-gated)
    Route::get('/ai-tools/vendor-matchmaking',  [AiVendorMatchmakingController::class, 'show'])->name('ai-tools.vendor-matchmaking');
    Route::post('/ai-tools/vendor-matchmaking', [AiVendorMatchmakingController::class, 'match'])->middleware('ai.level')->name('ai-tools.vendor-matchmaking.match');

    // AI Review Writer (plan-gated)
    Route::get('/ai-tools/review-writer',  [AiReviewWriterController::class, 'show'])->name('ai-tools.review-writer');
    Route::post('/ai-tools/review-writer', [AiReviewWriterController::class, 'compose'])->middleware('ai.level')->name('ai-tools.review-writer.compose');

    // AI Pricing Assistant (deterministic calculator — not plan-gated)
    Route::get('/ai-tools/pricing-assistant',  [\App\Http\Controllers\AiPricingAssistantController::class, 'show'])->name('ai-tools.pricing-assistant');
    Route::post('/ai-tools/pricing-assistant/calculate', [\App\Http\Controllers\AiPricingAssistantController::class, 'calculate'])->middleware('ai.level')->name('ai-tools.pricing-assistant.calculate');

    // AI Proposal Writer (deterministic template generator — not plan-gated)
    Route::get('/ai-tools/proposal-writer',  [\App\Http\Controllers\AiProposalWriterController::class, 'show'])->name('ai-tools.proposal-writer');
    Route::post('/ai-tools/proposal-writer/generate', [\App\Http\Controllers\AiProposalWriterController::class, 'generate'])->middleware('ai.level')->name('ai-tools.proposal-writer.generate');

    // AI Staffing Planner (deterministic staffing planner — not plan-gated)
    Route::get('/ai-tools/staffing-planner',  [\App\Http\Controllers\AiStaffingPlannerController::class, 'show'])->name('ai-tools.staffing-planner');
    Route::post('/ai-tools/staffing-planner/generate', [\App\Http\Controllers\AiStaffingPlannerController::class, 'generate'])->middleware('ai.level')->name('ai-tools.staffing-planner.generate');

    // AI Agreement Builder — guided 3-phase agreement lifecycle (client ↔ professional).
    // Phase 1: Discovery & AI Evidence Collection.
    Route::get('/ai-agreement/build/{booking?}', [\App\Http\Controllers\AiAgreementBuilderController::class, 'phase1'])->name('ai-agreement.build');
    // Draft Generation — AI-filled (green) + required (amber) sections.
    Route::get('/ai-agreement/draft/{booking?}', [\App\Http\Controllers\AiAgreementBuilderController::class, 'draft'])->name('ai-agreement.draft');
    // Phase 2: Collaboration & Negotiation.
    Route::get('/ai-agreement/negotiate/{booking?}', [\App\Http\Controllers\AiAgreementBuilderController::class, 'phase2'])->name('ai-agreement.negotiate');
    // Phase 3: Execution & Finalization.
    Route::get('/ai-agreement/finalize/{booking?}', [\App\Http\Controllers\AiAgreementBuilderController::class, 'phase3'])->name('ai-agreement.finalize');

    // Integrated Cancellation & Rejection Wizard (agreement reject/renegotiate flow)
    Route::get('/cancellation-wizard/{agreement?}',  [\App\Http\Controllers\CancellationWizardController::class, 'show'])->name('cancellation-wizard.show');
    Route::post('/cancellation-wizard/resolve/{agreement?}', [\App\Http\Controllers\CancellationWizardController::class, 'resolve'])->name('cancellation-wizard.resolve');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Redirect client/supplier users based on active mode (session) with fallback
        $active = $user->activeRole();
        if ($active === 'supplier') {
            return redirect()->route('professional.dashboard');
        }
        if ($active === 'client') {
            return redirect()->route('client.dashboard');
        }

        // Stats for admin
        $stats = [];
        if ($user->hasRole('admin')) {
            $stats = [
                'total_users' => \App\Models\User::count(),
                'total_events' => \App\Models\Event::count(),
                'total_bookings' => \App\Models\Booking::count(),
                'active_plans' => \App\Models\UserSubscription::where('status', 'active')->count(),
            ];
        }

        // Recent events (visible to all)
        $recentEvents = \App\Models\Event::latest()->take(5)->get();

        // User's own bookings
        $myBookings = \App\Models\Booking::where(function ($q) use ($user) {
            $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id);
        })->latest()->take(5)->get();

        // User's subscription
        $subscription = \App\Models\UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('membershipPlan')
            ->first();

        return view('dashboard.index', compact('stats', 'recentEvents', 'myBookings', 'subscription'));
    })->middleware('permission:dashboard.view')->name('dashboard');

    Route::redirect('/home', '/dashboard')->name('home');

    // ── Influencer Dashboard ──────────────────────────────────────
    Route::prefix('influencer')->name('influencer.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Influencer\InfluencerDashboardController::class, 'index'])
            ->middleware('permission:influencer.dashboard.view')->name('dashboard');
        Route::get('/referrals', [\App\Http\Controllers\Influencer\InfluencerDashboardController::class, 'referrals'])
            ->middleware('permission:influencer.referrals.view')->name('dashboard.referrals');
        Route::get('/payouts', [\App\Http\Controllers\Influencer\InfluencerDashboardController::class, 'payouts'])
            ->middleware('permission:influencer.payouts.view')->name('dashboard.payouts');
        Route::post('/payouts', [\App\Http\Controllers\Influencer\InfluencerDashboardController::class, 'requestPayout'])
            ->middleware('permission:influencer.payouts.request')->name('dashboard.payouts.request');

        // Badges & Tiers section
        Route::middleware('permission:influencer.dashboard.view')->group(function () {
            $b = \App\Http\Controllers\Influencer\InfluencerBadgesController::class;
            Route::get('/badges/tiers', [$b, 'tiers'])->name('badges.tiers');
            Route::get('/badges/current', [$b, 'current'])->name('badges.current');
            Route::get('/badges/progress', [$b, 'progress'])->name('badges.progress');
            Route::get('/badges/all', [$b, 'badges'])->name('badges.all');
            Route::get('/badges/benefits', [$b, 'benefits'])->name('badges.benefits');
        });

        // Invite & Earn More section
        Route::middleware('permission:influencer.dashboard.view')->group(function () {
            $i = \App\Http\Controllers\Influencer\InfluencerInviteController::class;
            Route::get('/invite/tools', [$i, 'tools'])->name('invite.tools');
            Route::get('/invite/earn', [$i, 'earn'])->name('invite.earn');
            Route::get('/invite/promote', [$i, 'promote'])->name('invite.promote');
            Route::get('/invite/become', [$i, 'become'])->name('invite.become');
            Route::get('/invite/onboarding', [$i, 'onboarding'])->name('invite.onboarding');
            Route::get('/invite/success-stories', [$i, 'stories'])->name('invite.stories');
            Route::get('/invite/faqs', [$i, 'faqs'])->name('invite.faqs');
            Route::get('/invite/apply', fn () => redirect()->route('influencer.join'))->name('invite.apply');
        });

        // Analytics section
        Route::middleware('permission:influencer.dashboard.view')->group(function () {
            $a = \App\Http\Controllers\Influencer\InfluencerAnalyticsController::class;
            Route::get('/analytics/performance', [$a, 'performance'])->name('analytics.performance');
            Route::get('/analytics/campaigns', [$a, 'campaigns'])->name('analytics.campaigns');
            Route::get('/analytics/audience', [$a, 'audience'])->name('analytics.audience');
            Route::get('/analytics/content', [$a, 'content'])->name('analytics.content');
            Route::get('/analytics/reports', [$a, 'reports'])->name('analytics.reports');
            Route::get('/analytics/getting-started', [$a, 'gettingStarted'])->name('analytics.getting-started');
            Route::get('/analytics/export', [$a, 'export'])->name('analytics.export');
        });

        // Resources section
        Route::middleware('permission:influencer.dashboard.view')->group(function () {
            $r = \App\Http\Controllers\Influencer\InfluencerResourceController::class;
            Route::get('/resources/library', [$r, 'library'])->name('resources.library');
            Route::get('/resources/academy', [$r, 'academy'])->name('resources.academy');
            Route::get('/resources/tutorials', [$r, 'tutorials'])->name('resources.tutorials');
            Route::get('/resources/articles', [$r, 'articles'])->name('resources.articles');
            Route::get('/resources/getting-started', [$r, 'gettingStarted'])->name('resources.getting-started');
        });

        // Program sections: Referral Center, Marketing, Leaderboards, Commissions
        Route::middleware('permission:influencer.dashboard.view')->group(function () {
            $p = \App\Http\Controllers\Influencer\InfluencerProgramController::class;
            Route::get('/referral-center', [$p, 'referralCenter'])->name('referral-center');
            Route::get('/marketing', [$p, 'marketing'])->name('marketing');
            Route::get('/leaderboards', [$p, 'leaderboards'])->name('leaderboards');
            Route::get('/commissions', [$p, 'commissions'])->name('commissions');
        });
    });

    // ── Admin Influencer Management ───────────────────────────────
    Route::prefix('app/influencers')->name('app.influencers.')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'index'])
            ->middleware('permission:influencers.view_any')->name('index');
        Route::get('/payouts', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'payouts'])
            ->middleware('permission:influencers.manage_payouts')->name('payouts');
        Route::post('/payouts/{payoutRequest}/paid', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'markPayoutPaid'])
            ->middleware('permission:influencers.manage_payouts')->name('payouts.paid');
        Route::post('/payouts/{payoutRequest}/reject', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'rejectPayout'])
            ->middleware('permission:influencers.manage_payouts')->name('payouts.reject');
        Route::get('/{influencer}', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'show'])
            ->middleware('permission:influencers.view')->name('show');
        Route::post('/{influencer}/approve', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'approve'])
            ->middleware('permission:influencers.approve')->name('approve');
        Route::post('/{influencer}/reject', [\App\Http\Controllers\Dashboard\AdminInfluencerController::class, 'reject'])
            ->middleware('permission:influencers.reject')->name('reject');
    });

    // ── Admin AI Tools Control ────────────────────────────────────
    Route::prefix('app/ai-tools')->name('app.ai-tools-admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboard\AdminAiToolController::class, 'index'])->name('index');
        Route::post('/{key}', [\App\Http\Controllers\Dashboard\AdminAiToolController::class, 'update'])->name('update');
    });

    // ── Client Panel ──────────────────────────────────────────────
    Route::prefix('client')->middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');

        // Client-context professional search — reuses /browse data but
        // frames it with the client's active project + budget rail.
        Route::get('/search', [\App\Http\Controllers\Client\ClientSearchController::class, 'index'])
            ->name('client.search.index');

        // Find Gigs — client-side mirror of the professional bidding board:
        // browse professional gig listings (service packages) to book / message.
        Route::get('/find-gigs', [\App\Http\Controllers\Client\FindGigsController::class, 'index'])
            ->name('client.find-gigs.index');

        // Proposals — supplier responses to the client's events.
        Route::get('/proposals', [\App\Http\Controllers\Client\ClientProposalController::class, 'index'])
            ->middleware('permission:bookings.view_any')
            ->name('client.proposals.index');

        // Multi-Service Request for Proposal (RFP wizard).
        Route::get('/multi-service', [\App\Http\Controllers\Client\ClientMultiServiceController::class, 'index'])
            ->name('client.multi-service.index');

        // Reviews the client has written about hired professionals.
        Route::get('/reviews', [\App\Http\Controllers\Client\ClientReviewController::class, 'index'])
            ->name('client.reviews.index');

        // Finance — Payments ledger + Earnings (project financial dashboard).
        Route::get('/payments', [\App\Http\Controllers\Client\ClientFinanceController::class, 'payments'])
            ->name('client.payments.index');
        Route::get('/earnings', [\App\Http\Controllers\Client\ClientFinanceController::class, 'earnings'])
            ->name('client.earnings.index');

        // Virtual & Hybrid Hub (new feature scaffold).
        Route::get('/virtual-hub', [\App\Http\Controllers\Client\ClientVirtualHubController::class, 'index'])
            ->name('client.virtual-hub.index');
        // Virtual & Hybrid Event Brief — dedicated posting form.
        Route::get('/virtual-hub/brief', [\App\Http\Controllers\Client\ClientVirtualHubController::class, 'brief'])
            ->name('client.virtual-hub.brief');

        Route::get('/events', [ClientEventController::class, 'index'])->middleware('permission:events.view_any')->name('client.events.index');
        // "Create a Gig" (bidding builder) retired in favour of the "Post an Event" flow — redirect any old links.
        Route::get('/events/create', fn () => redirect()->route('client.post-event.event-info'))->name('client.events.create');
        Route::post('/events', [ClientEventController::class, 'store'])->middleware('permission:events.create')->name('client.events.store');

        // ── Post an Event — guided 11-step booking journey ──
        Route::prefix('post-event')->name('client.post-event.')->group(function () {
            $pe = \App\Http\Controllers\Client\PostEventController::class;
            Route::get('/',                [$pe, 'eventInfo'])->name('event-info');
            Route::post('/',               [$pe, 'storeEventInfo'])->name('store-info');
            Route::get('/build',           [$pe, 'build'])->name('build');
            Route::get('/service-details', [$pe, 'serviceDetails'])->name('service-details');
            Route::get('/review-search',   [$pe, 'reviewSearch'])->name('review-search');
            Route::get('/results',         [$pe, 'results'])->name('results');
            Route::get('/compare',         [$pe, 'compare'])->name('compare');
            Route::get('/customize',       [$pe, 'customize'])->name('customize');
            Route::get('/combinations',    [$pe, 'combinations'])->name('combinations');
            Route::get('/checkout',        [$pe, 'checkout'])->name('checkout');
            Route::get('/confirmed',       [$pe, 'confirmed'])->name('confirmed');
            Route::get('/final-payment',   [$pe, 'finalPayment'])->name('final-payment');
        });

        // Direct Offer / Request builder (SSR / MSR / ESR) — client → professional.
        Route::get('/direct-offers/create', [\App\Http\Controllers\Client\ClientDirectOfferController::class, 'create'])->middleware('permission:events.create')->name('client.direct-offers.create');
        Route::get('/events/{event}', [ClientEventController::class, 'show'])->middleware('permission:events.view')->name('client.events.show');
        Route::patch('/events/{event}', [ClientEventController::class, 'update'])->middleware('permission:events.update')->name('client.events.update');
        Route::post('/events/{event}/publish', [ClientEventController::class, 'publish'])->middleware('permission:events.publish')->name('client.events.publish');

        // Client Bookings
        Route::get('/bookings', [ClientBookingController::class, 'index'])->middleware('permission:bookings.view_any')->name('client.bookings.index');
        Route::patch('/bookings/{booking}/status', [ClientBookingController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('client.bookings.update-status');

        // Client Messages (Chat)
        Route::get('/messages', [ClientChatController::class, 'index'])->middleware('permission:messages.view_any')->name('client.chat.index');
        Route::get('/messages/{conversation}', [ClientChatController::class, 'show'])->middleware('permission:messages.view')->name('client.chat.show');

        // Notification Preferences (dedicated page — Email / Push / SMS)
        Route::get('/notifications', [ClientProfileController::class, 'notifications'])->name('client.notifications.index');

        // Client Profile & Settings
        Route::get('/profile', [ClientProfileController::class, 'index'])->name('client.profile.index');
        Route::patch('/profile/general', [ClientProfileController::class, 'updateGeneral'])->name('client.profile.update.general');
        Route::patch('/profile/company', [ClientProfileController::class, 'updateCompany'])->name('client.profile.update.company');
        Route::patch('/profile/social', [ClientProfileController::class, 'updateSocial'])->name('client.profile.update.social');
        Route::patch('/profile/password', [ClientProfileController::class, 'updatePassword'])->name('client.profile.update.password');
        Route::patch('/profile/notifications', [ClientProfileController::class, 'updateNotifications'])->name('client.profile.update.notifications');
        Route::post('/profile/avatar', [ClientProfileController::class, 'updateAvatar'])->name('client.profile.avatar');
        Route::delete('/profile/avatar', [ClientProfileController::class, 'removeAvatar'])->name('client.profile.avatar.remove');
    });

    // ── Professional Panel ──────────────────────────────────────────
    Route::prefix('professional')->middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [ProfessionalDashboardController::class, 'index'])->name('professional.dashboard');

        // Contracts hub (contracts, proposals, earnings, gig opportunities, bids pipeline)
        Route::get('/contracts', [\App\Http\Controllers\Professional\ProfessionalContractController::class, 'index'])->name('professional.contracts.index');

        // Multi-Service Requests (browse & bid on multi-service event postings)
        Route::get('/multi-service', [\App\Http\Controllers\Professional\ProfessionalMultiServiceController::class, 'index'])->name('professional.multi-service.index');

        // Priority Actions (aggregated urgent items the pro must act on)
        Route::get('/priority-actions', [\App\Http\Controllers\Professional\ProfessionalPriorityController::class, 'index'])->name('professional.priority.index');

        // Gig Operations Hub (gigs overview hub — links to My Gigs for the full list)
        Route::get('/gig-hub', [\App\Http\Controllers\Professional\ProfessionalGigHubController::class, 'index'])->name('professional.gig-hub.index');

        // Lead Pipeline (Leads CRM — inquiry → proposal → negotiation → booked)
        Route::get('/leads', [\App\Http\Controllers\Professional\ProfessionalLeadController::class, 'index'])->name('professional.leads.index');

        // My Calendar (schedule home — agenda + month grid + availability)
        Route::get('/calendar', [\App\Http\Controllers\Professional\ProfessionalCalendarController::class, 'index'])->name('professional.calendar.index');

        // Bid Intelligence (bid pipeline performance — invited→submitted→viewed→won/lost)
        Route::get('/bid-intelligence', [\App\Http\Controllers\Professional\ProfessionalBidIntelligenceController::class, 'index'])->name('professional.bid-intelligence.index');

        // Main Bidding Board (browse all open client gigs + place bids)
        Route::get('/bidding-board', [\App\Http\Controllers\Professional\ProfessionalBiddingBoardController::class, 'index'])->name('professional.bidding-board.index');

        // Team & Staffing (crew + shifts subsystem)
        Route::get('/team', [\App\Http\Controllers\Professional\ProfessionalTeamController::class, 'index'])->name('professional.team.index');
        Route::post('/team/staff', [\App\Http\Controllers\Professional\ProfessionalTeamController::class, 'storeStaff'])->name('professional.team.staff.store');
        Route::post('/team/shifts', [\App\Http\Controllers\Professional\ProfessionalTeamController::class, 'storeShift'])->name('professional.team.shifts.store');
        Route::post('/team/shifts/{shift}/fill', [\App\Http\Controllers\Professional\ProfessionalTeamController::class, 'fillShift'])->name('professional.team.shifts.fill');

        // My Gigs (Events assigned to professional)
        // Professional Packages — bundle services into a fixed, browsable offering.
        Route::get('/packages', [ProfessionalPackageController::class, 'index'])->name('professional.packages.index');
        Route::get('/packages/create', [ProfessionalPackageController::class, 'create'])->name('professional.packages.create');
        Route::post('/packages', [ProfessionalPackageController::class, 'store'])->name('professional.packages.store');
        Route::get('/packages/{package}/edit', [ProfessionalPackageController::class, 'edit'])->name('professional.packages.edit');
        Route::patch('/packages/{package}', [ProfessionalPackageController::class, 'update'])->name('professional.packages.update');
        Route::delete('/packages/{package}', [ProfessionalPackageController::class, 'destroy'])->name('professional.packages.destroy');

        Route::get('/gigs', [ProfessionalGigController::class, 'index'])->middleware('permission:events.view_any')->name('professional.gigs.index');
        Route::get('/gigs/create', [ProfessionalGigController::class, 'create'])->name('professional.gigs.create');
        Route::post('/gigs', [ProfessionalGigController::class, 'store'])->name('professional.gigs.store');
        Route::get('/gigs/{event}', [ProfessionalGigController::class, 'show'])->middleware('permission:events.view')->name('professional.gigs.show');

        // Proposals (Bookings from professional's perspective)
        // Direct Offers (SSR / MSR / ESR) — client sends a direct request to this pro
        Route::get('/direct-offers/{id?}', [\App\Http\Controllers\Professional\ProfessionalDirectOfferController::class, 'show'])->middleware('permission:bookings.view_any')->name('professional.direct-offers.show');

        Route::get('/proposals', [ProfessionalProposalController::class, 'index'])->middleware('permission:bookings.view_any')->name('professional.proposals.index');
        Route::post('/proposals/send/{event}', [ProfessionalProposalController::class, 'sendProposal'])->middleware('permission:bookings.create')->name('professional.proposals.send');
        Route::patch('/proposals/{booking}/status', [ProfessionalProposalController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('professional.proposals.update-status');

        // Earnings
        Route::get('/earnings', [ProfessionalEarningsController::class, 'index'])->name('professional.earnings.index');

        // Messages (Chat)
        Route::get('/messages', [ProfessionalChatController::class, 'index'])->middleware('permission:messages.view_any')->name('professional.chat.index');
        Route::get('/messages/{conversation}', [ProfessionalChatController::class, 'show'])->middleware('permission:messages.view')->name('professional.chat.show');

        // Threads (per-conversation deep view + AI extracted commitments)
        Route::get('/threads', [\App\Http\Controllers\Professional\ProfessionalThreadController::class, 'index'])->middleware('permission:messages.view_any')->name('professional.threads.index');
        Route::get('/threads/{conversation}', [\App\Http\Controllers\Professional\ProfessionalThreadController::class, 'show'])->middleware('permission:messages.view')->name('professional.threads.show');

        // Reviews
        Route::get('/reviews', [ProfessionalReviewController::class, 'index'])->name('professional.reviews.index');
        Route::post('/reviews', [ProfessionalReviewController::class, 'store'])->name('professional.reviews.store');

        // Transactions
        Route::get('/transactions', [ProfessionalTransactionController::class, 'index'])->name('professional.transactions.index');
        Route::get('/transactions/export/csv', [ProfessionalTransactionController::class, 'exportCsv'])->name('professional.transactions.export.csv');
        Route::get('/transactions/export/pdf', [ProfessionalTransactionController::class, 'exportPdf'])->name('professional.transactions.export.pdf');

        // Notification Preferences (dedicated page — Email / Push / SMS)
        Route::get('/notifications', [ProfessionalProfileController::class, 'notifications'])->name('professional.notifications.index');

        // Address verification (§7.3 — risk-based; paid call launch-gated)
        Route::post('/profile/verify-address', [ProfessionalProfileController::class, 'verifyAddress'])->name('professional.profile.verify-address');

        // Professional Profile & Settings
        Route::get('/profile', [ProfessionalProfileController::class, 'index'])->name('professional.profile.index');
        Route::patch('/profile/general', [ProfessionalProfileController::class, 'updateGeneral'])->name('professional.profile.update.general');
        Route::patch('/profile/professional', [ProfessionalProfileController::class, 'updateProfessional'])->name('professional.profile.update.professional');
        Route::patch('/profile/portfolio', [ProfessionalProfileController::class, 'updatePortfolio'])->name('professional.profile.update.portfolio');
        Route::post('/profile/portfolio/image', [ProfessionalProfileController::class, 'uploadPortfolioImage'])->name('professional.profile.portfolio.image');
        Route::delete('/profile/portfolio/image', [ProfessionalProfileController::class, 'deletePortfolioImage'])->name('professional.profile.portfolio.image.delete');
        Route::post('/profile/portfolio/featured', [ProfessionalProfileController::class, 'setFeaturedPortfolio'])->name('professional.profile.portfolio.featured');
        Route::post('/profile/portfolio/crop', [ProfessionalProfileController::class, 'adjustPortfolioCrop'])->name('professional.profile.portfolio.crop');
        Route::patch('/profile/social', [ProfessionalProfileController::class, 'updateSocial'])->name('professional.profile.update.social');
        Route::patch('/profile/password', [ProfessionalProfileController::class, 'updatePassword'])->name('professional.profile.update.password');
        Route::patch('/profile/notifications', [ProfessionalProfileController::class, 'updateNotifications'])->name('professional.profile.update.notifications');
        Route::post('/profile/avatar', [ProfessionalProfileController::class, 'updateAvatar'])->name('professional.profile.avatar');
        Route::delete('/profile/avatar', [ProfessionalProfileController::class, 'removeAvatar'])->name('professional.profile.avatar.remove');
        Route::post('/profile/cover', [ProfessionalProfileController::class, 'updateCover'])->name('professional.profile.cover');
        Route::delete('/profile/cover', [ProfessionalProfileController::class, 'removeCover'])->name('professional.profile.cover.remove');

        // Verification badge uploads (trade license, insurance, workers' comp)
        Route::post('/profile/verification', [ProfessionalProfileController::class, 'submitVerification'])->name('professional.profile.verification.submit');
        Route::post('/profile/verification/remove', [ProfessionalProfileController::class, 'removeVerification'])->name('professional.profile.verification.remove');
    });

    // Dashboard UI pages
    Route::get('/app/events', [EventPageController::class, 'index'])->middleware('permission:events.view_any')->name('app.events.index');
    Route::post('/app/events', [EventPageController::class, 'store'])->middleware('permission:events.create')->name('app.events.store');
    Route::patch('/app/events/{event}', [EventPageController::class, 'update'])->middleware('permission:events.update')->name('app.events.update');
    Route::get('/app/events/{event}', [EventPageController::class, 'show'])->middleware('permission:events.view')->name('app.events.show');
    Route::post('/app/events/{event}/publish', [EventPageController::class, 'publish'])->middleware('permission:events.publish')->name('app.events.publish');
    // Admin moderation: pull a bad event from discovery without deleting it.
    Route::post('/app/events/{event}/unpublish', [EventPageController::class, 'unpublish'])->middleware('permission:events.publish')->name('app.events.unpublish');

    Route::get('/app/bookings', [BookingPageController::class, 'index'])->middleware('permission:bookings.view_any')->name('app.bookings.index');
    Route::post('/app/bookings', [BookingPageController::class, 'store'])->middleware('permission:bookings.create')->name('app.bookings.store');
    Route::patch('/app/bookings/{booking}/status', [BookingPageController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('app.bookings.update-status');
    // Admin moderation: force-cancel a booking regardless of status.
    Route::post('/app/bookings/{booking}/force-cancel', [BookingPageController::class, 'forceCancel'])->middleware('permission:bookings.update')->name('app.bookings.force-cancel');

    // Chat (messenger-style) — replaces old table-based messages
    Route::get('/app/chat', [ChatPageController::class, 'index'])->middleware('permission:messages.view_any')->name('app.chat.index');
    Route::get('/app/chat/{conversation}', [ChatPageController::class, 'show'])->middleware('permission:messages.view')->name('app.chat.show');

    // Conversation API
    Route::resource('conversations', ConversationController::class)->only(['index', 'store', 'show'])->middleware('permission:messages.view_any');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->middleware('permission:messages.create')->name('conversations.messages.store');
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markAsRead'])->middleware('permission:messages.view')->name('conversations.mark-read');
    Route::post('/conversations/{conversation}/typing', [ConversationController::class, 'typing'])->middleware('permission:messages.view')->name('conversations.typing');

    // Attachments
    Route::post('/attachments', [MessageAttachmentController::class, 'store'])->middleware('permission:messages.create')->name('attachments.store');
    Route::get('/attachments/{attachment}/download', [MessageAttachmentController::class, 'download'])->name('attachments.download');

    // Legacy messages redirect
    Route::redirect('/app/messages', '/app/chat');
    Route::post('/app/messages', [MessagePageController::class, 'store'])->middleware('permission:messages.create')->name('app.messages.store');

    // Membership Plans (all authenticated users with permission)
    Route::get('/app/membership-plans', [MembershipPlanPageController::class, 'index'])->middleware('permission:membership_plans.view_any')->name('app.membership-plans.index');
    Route::post('/app/membership-plans/{membership_plan}/subscribe', [MembershipPlanPageController::class, 'subscribe'])->middleware('permission:membership_plans.subscribe')->name('app.membership-plans.subscribe');
    Route::post('/app/membership-plans/cancel', [MembershipPlanPageController::class, 'cancel'])->middleware('permission:membership_plans.subscribe')->name('app.membership-plans.cancel');
    Route::get('/app/membership-plans/history', [MembershipPlanPageController::class, 'history'])->middleware('permission:membership_plans.view_any')->name('app.membership-plans.history');

    // ── Admin Panel (role:admin required) ──────────────────────────────
    Route::prefix('app/admin')->middleware('role:admin')->group(function () {
        // Admin Profile
        Route::get('/profile', [AdminProfileController::class, 'index'])->name('app.admin.profile.index');
        Route::patch('/profile/general', [AdminProfileController::class, 'updateGeneral'])->name('app.admin.profile.update.general');
        Route::patch('/profile/social', [AdminProfileController::class, 'updateSocial'])->name('app.admin.profile.update.social');
        Route::patch('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('app.admin.profile.update.password');
        Route::patch('/profile/notifications', [AdminProfileController::class, 'updateNotifications'])->name('app.admin.profile.update.notifications');
        Route::post('/profile/avatar', [AdminProfileController::class, 'updateAvatar'])->name('app.admin.profile.avatar');
        Route::delete('/profile/avatar', [AdminProfileController::class, 'removeAvatar'])->name('app.admin.profile.avatar.remove');

        // Membership Plan Management
        Route::get('/membership-plans', [AdminMembershipPlanController::class, 'index'])->middleware('permission:membership_plans.create')->name('app.admin.membership-plans.index');
        Route::post('/membership-plans', [AdminMembershipPlanController::class, 'store'])->middleware('permission:membership_plans.create')->name('app.admin.membership-plans.store');
        Route::patch('/membership-plans/{membership_plan}', [AdminMembershipPlanController::class, 'update'])->middleware('permission:membership_plans.update')->name('app.admin.membership-plans.update');
        Route::delete('/membership-plans/{membership_plan}', [AdminMembershipPlanController::class, 'destroy'])->middleware('permission:membership_plans.delete')->name('app.admin.membership-plans.destroy');

        // Settings
        Route::get('/settings/payments', [AdminSettingsController::class, 'paymentSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.payments');
        Route::post('/settings/payments', [AdminSettingsController::class, 'updatePaymentSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.payments.update');
        Route::get('/settings/openai', [AdminSettingsController::class, 'openaiSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.openai');
        Route::post('/settings/openai', [AdminSettingsController::class, 'updateOpenAISettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.openai.update');
        Route::get('/settings/recaptcha', [AdminSettingsController::class, 'recaptchaSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.recaptcha');
        Route::post('/settings/recaptcha', [AdminSettingsController::class, 'updateRecaptchaSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.recaptcha.update');
        Route::get('/settings/account-deletion', [AdminSettingsController::class, 'accountDeletionSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.account-deletion');
        Route::post('/settings/account-deletion', [AdminSettingsController::class, 'updateAccountDeletionSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.account-deletion.update');

        // AI Chatbot settings & logs (admin)
        Route::get('/settings/chatbot',       [\App\Http\Controllers\Dashboard\AdminAiChatbotController::class, 'settings'])->name('app.admin.settings.chatbot');
        Route::post('/settings/chatbot',      [\App\Http\Controllers\Dashboard\AdminAiChatbotController::class, 'updateSettings'])->name('app.admin.settings.chatbot.update');
        Route::get('/chatbot-logs',           [\App\Http\Controllers\Dashboard\AdminAiChatbotController::class, 'logs'])->name('app.admin.chatbot-logs.index');
        Route::get('/chatbot-logs/{conversation}', [\App\Http\Controllers\Dashboard\AdminAiChatbotController::class, 'showConversation'])->name('app.admin.chatbot-logs.show');

        // All Events
        Route::get('/events', [AdminEventController::class, 'index'])->middleware('permission:events.view_any')->name('app.admin.events.index');
        Route::post('/events', [AdminEventController::class, 'store'])->middleware('permission:events.create')->name('app.admin.events.store');
        Route::patch('/events/{event}', [AdminEventController::class, 'update'])->middleware('permission:events.update')->name('app.admin.events.update');
        Route::delete('/events/{event}', [AdminEventController::class, 'destroy'])->middleware('permission:events.delete')->name('app.admin.events.destroy');

        // Categories
        Route::get('/categories', [AdminCategoryController::class, 'index'])->middleware('permission:events.view_any')->name('app.admin.categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->middleware('permission:events.create')->name('app.admin.categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->middleware('permission:events.create')->name('app.admin.categories.store');
        Route::get('/categories/{category}', [AdminCategoryController::class, 'show'])->middleware('permission:events.view_any')->name('app.admin.categories.show');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->middleware('permission:events.update')->name('app.admin.categories.edit');
        Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:events.update')->name('app.admin.categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:events.delete')->name('app.admin.categories.destroy');

        // FAQ Management
        Route::get('/faqs', [AdminFaqController::class, 'index'])->name('app.admin.faqs.index');
        Route::post('/faqs', [AdminFaqController::class, 'store'])->name('app.admin.faqs.store');
        Route::patch('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('app.admin.faqs.update');
        Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('app.admin.faqs.destroy');
        Route::patch('/faqs/{faq}/toggle', [AdminFaqController::class, 'toggleStatus'])->name('app.admin.faqs.toggle');

        // Professional Verifications (trade license / insurance / workers' comp)
        Route::get('/verifications', [\App\Http\Controllers\Dashboard\AdminVerificationController::class, 'index'])->name('app.admin.verifications.index');
        Route::post('/verifications/{profile}/approve', [\App\Http\Controllers\Dashboard\AdminVerificationController::class, 'approve'])->name('app.admin.verifications.approve');
        Route::post('/verifications/{profile}/reject', [\App\Http\Controllers\Dashboard\AdminVerificationController::class, 'reject'])->name('app.admin.verifications.reject');

        // Policy Pages
        Route::get('/policies', [AdminPolicyController::class, 'index'])->name('app.admin.policies.index');
        Route::get('/policies/{policy}/edit', [AdminPolicyController::class, 'edit'])->name('app.admin.policies.edit');
        Route::patch('/policies/{policy}', [AdminPolicyController::class, 'update'])->name('app.admin.policies.update');
    });

    // Payment Flow
    Route::match(['get', 'post'], '/app/payments/initiate/{plan}', [PaymentPageController::class, 'initiate'])->middleware('permission:membership_plans.subscribe')->name('app.payments.initiate');
    Route::get('/app/payments/success', [PaymentPageController::class, 'success'])->name('app.payments.success');
    Route::get('/app/payments/cancel', [PaymentPageController::class, 'cancel'])->name('app.payments.cancel');
    Route::get('/app/payments/history', [PaymentPageController::class, 'history'])->middleware('permission:payments.view')->name('app.payments.history');

    // AI Agreements
    Route::get('/app/agreements', [AgreementPageController::class, 'index'])->middleware('permission:agreements.view_any')->name('app.agreements.index');
    Route::get('/app/agreements/{agreement}', [AgreementPageController::class, 'show'])->middleware('permission:agreements.view_any')->name('app.agreements.show');
    Route::get('/app/agreements/{agreement}/download', [AgreementPageController::class, 'download'])->middleware('permission:agreements.view_any')->name('app.agreements.download');
    Route::post('/app/agreements/generate/{booking}', [AgreementPageController::class, 'generate'])->middleware('permission:agreements.generate')->name('app.agreements.generate');
    Route::post('/app/agreements/{agreement}/accept', [AgreementPageController::class, 'accept'])->middleware('permission:agreements.accept')->name('app.agreements.accept');
    Route::post('/app/agreements/{agreement}/reject', [AgreementPageController::class, 'reject'])->middleware('permission:agreements.accept')->name('app.agreements.reject');
    Route::post('/app/agreements/regenerate/{booking}', [AgreementPageController::class, 'regenerate'])->middleware('permission:agreements.generate')->name('app.agreements.regenerate');

    Route::get('/app/agreement-log', [AgreementLogPageController::class, 'index'])->middleware('permission:agreement_log.view_any')->name('app.agreement-log.index');

    // ── Admin Deletion Requests ──────────────────────────────
    Route::prefix('app/admin/deletion-requests')->name('app.admin.deletion-requests.')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Dashboard\AdminUserDeletionController::class, 'index'])->name('index');
        Route::post('/{user}/cancel', [\App\Http\Controllers\Dashboard\AdminUserDeletionController::class, 'cancel'])->name('cancel');
    });

    // ── Admin Activity Log ──────────────────────────────────
    Route::get('/app/admin/activity-logs', [\App\Http\Controllers\Dashboard\AdminActivityLogController::class, 'index'])
        ->middleware('role:admin')
        ->name('app.admin.activity-logs.index');

    // ── Admin Blog Management ───────────────────────────────
    Route::prefix('app/admin/blog')->name('app.admin.blog.')->middleware('role:admin')->group(function () {
        // Categories
        Route::get('/categories',             [AdminBlogCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories',            [AdminBlogCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [AdminBlogCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}',[AdminBlogCategoryController::class, 'destroy'])->name('categories.destroy');

        // Posts
        Route::get('/posts',                  [AdminBlogPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create',           [AdminBlogPostController::class, 'create'])->name('posts.create');
        Route::post('/posts',                 [AdminBlogPostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit',      [AdminBlogPostController::class, 'edit'])->name('posts.edit');
        Route::patch('/posts/{post}',         [AdminBlogPostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}',        [AdminBlogPostController::class, 'destroy'])->name('posts.destroy');
    });

    Route::get('/app/users', [UserAccessPageController::class, 'index'])->middleware(['role:admin', 'permission:users.view_any'])->name('app.users.index');
    Route::post('/app/users', [UserAccessPageController::class, 'store'])->middleware(['role:admin', 'permission:users.create'])->name('app.users.store');
    Route::patch('/app/users/{user}', [UserAccessPageController::class, 'update'])->middleware(['role:admin', 'permission:users.update'])->name('app.users.update');
    Route::delete('/app/users/{user}', [UserAccessPageController::class, 'destroy'])->middleware(['role:admin', 'permission:users.delete'])->name('app.users.destroy');

    Route::get('/app/roles', [RolePageController::class, 'index'])->middleware(['role:admin', 'permission:roles.view_any'])->name('app.roles.index');
    Route::post('/app/roles', [RolePageController::class, 'store'])->middleware(['role:admin', 'permission:roles.create'])->name('app.roles.store');
    Route::patch('/app/roles/{role}', [RolePageController::class, 'update'])->middleware(['role:admin', 'permission:roles.update'])->name('app.roles.update');
    Route::delete('/app/roles/{role}', [RolePageController::class, 'destroy'])->middleware(['role:admin', 'permission:roles.delete'])->name('app.roles.destroy');

    Route::get('/app/permissions', [PermissionPageController::class, 'index'])->middleware(['role:admin', 'permission:permissions.view_any'])->name('app.permissions.index');
    Route::post('/app/permissions', [PermissionPageController::class, 'store'])->middleware(['role:admin', 'permission:permissions.create'])->name('app.permissions.store');
    Route::patch('/app/permissions/{permission}', [PermissionPageController::class, 'update'])->middleware(['role:admin', 'permission:permissions.update'])->name('app.permissions.update');
    Route::delete('/app/permissions/{permission}', [PermissionPageController::class, 'destroy'])->middleware(['role:admin', 'permission:permissions.delete'])->name('app.permissions.destroy');

    // Core APIs
    Route::resource('events', EventController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
    Route::get('/events/{event}/details', [EventController::class, 'details'])->name('events.details');

    Route::resource('bookings', BookingController::class)->only(['index', 'store', 'show', 'update']);
    Route::resource('messages', MessageController::class)->only(['index', 'store', 'show']);
});

// Payment Webhooks (no auth, no CSRF — verified via gateway signatures)
Route::post('/webhooks/stripe', [PaymentWebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('/webhooks/paypal', [PaymentWebhookController::class, 'paypal'])->name('webhooks.paypal');

// ── Deploy Helper Routes (public, no auth) ──────────────────────────────
Route::get('/deploy/git-pull', function () {
    $output = [];
    exec('cd ' . base_path() . ' && git pull 2>&1', $output, $returnCode);
    return response()->json([
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
    ]);
});

Route::get('/deploy/migrate', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'success' => true,
            'output' => $output,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/deploy/seed/{seeder?}', function (?string $seeder = null) {
    try {
        $params = ['--force' => true];
        if ($seeder) {
            $params['--class'] = $seeder;
        }
        \Illuminate\Support\Facades\Artisan::call('db:seed', $params);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'success' => true,
            'output' => $output,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/deploy/cache-clear', function () {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    return response()->json([
        'success' => true,
        'output' => 'All caches cleared.',
    ]);
});
