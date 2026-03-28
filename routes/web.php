<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Client\ClientBookingController;
use App\Http\Controllers\Client\ClientChatController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientEventController;
use App\Http\Controllers\Professional\ProfessionalChatController;
use App\Http\Controllers\Professional\ProfessionalDashboardController;
use App\Http\Controllers\Professional\ProfessionalEarningsController;
use App\Http\Controllers\Professional\ProfessionalGigController;
use App\Http\Controllers\Professional\ProfessionalProposalController;
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
use App\Http\Controllers\Dashboard\AdminFaqController;
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
use App\Http\Controllers\Webhook\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');

// Policy pages
Route::view('/privacy-policy', 'policies.privacy')->name('privacy-policy');
Route::view('/payment-policy', 'policies.payment')->name('payment-policy');
Route::view('/cancellation-policy', 'policies.cancellation')->name('cancellation-policy');

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Redirect client/supplier users to their own dashboard
        if ($user->hasRole('supplier')) {
            return redirect()->route('professional.dashboard');
        }
        if ($user->hasRole('client')) {
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

    // ── Client Panel ──────────────────────────────────────────────
    Route::prefix('client')->middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');

        Route::get('/events', [ClientEventController::class, 'index'])->middleware('permission:events.view_any')->name('client.events.index');
        Route::post('/events', [ClientEventController::class, 'store'])->middleware('permission:events.create')->name('client.events.store');
        Route::get('/events/{event}', [ClientEventController::class, 'show'])->middleware('permission:events.view')->name('client.events.show');
        Route::patch('/events/{event}', [ClientEventController::class, 'update'])->middleware('permission:events.update')->name('client.events.update');
        Route::post('/events/{event}/publish', [ClientEventController::class, 'publish'])->middleware('permission:events.publish')->name('client.events.publish');

        // Client Bookings
        Route::get('/bookings', [ClientBookingController::class, 'index'])->middleware('permission:bookings.view_any')->name('client.bookings.index');
        Route::patch('/bookings/{booking}/status', [ClientBookingController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('client.bookings.update-status');

        // Client Messages (Chat)
        Route::get('/messages', [ClientChatController::class, 'index'])->middleware('permission:messages.view_any')->name('client.chat.index');
        Route::get('/messages/{conversation}', [ClientChatController::class, 'show'])->middleware('permission:messages.view')->name('client.chat.show');
    });

    // ── Professional Panel ──────────────────────────────────────────
    Route::prefix('professional')->middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [ProfessionalDashboardController::class, 'index'])->name('professional.dashboard');

        // My Gigs (Events assigned to professional)
        Route::get('/gigs', [ProfessionalGigController::class, 'index'])->middleware('permission:events.view_any')->name('professional.gigs.index');
        Route::get('/gigs/{event}', [ProfessionalGigController::class, 'show'])->middleware('permission:events.view')->name('professional.gigs.show');

        // Proposals (Bookings from professional's perspective)
        Route::get('/proposals', [ProfessionalProposalController::class, 'index'])->middleware('permission:bookings.view_any')->name('professional.proposals.index');
        Route::post('/proposals/send/{event}', [ProfessionalProposalController::class, 'sendProposal'])->middleware('permission:bookings.create')->name('professional.proposals.send');
        Route::patch('/proposals/{booking}/status', [ProfessionalProposalController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('professional.proposals.update-status');

        // Earnings
        Route::get('/earnings', [ProfessionalEarningsController::class, 'index'])->name('professional.earnings.index');

        // Messages (Chat)
        Route::get('/messages', [ProfessionalChatController::class, 'index'])->middleware('permission:messages.view_any')->name('professional.chat.index');
        Route::get('/messages/{conversation}', [ProfessionalChatController::class, 'show'])->middleware('permission:messages.view')->name('professional.chat.show');

        // Reviews
        Route::get('/reviews', [ProfessionalReviewController::class, 'index'])->name('professional.reviews.index');

        // Transactions
        Route::get('/transactions', [ProfessionalTransactionController::class, 'index'])->name('professional.transactions.index');
    });

    // Dashboard UI pages
    Route::get('/app/events', [EventPageController::class, 'index'])->middleware('permission:events.view_any')->name('app.events.index');
    Route::post('/app/events', [EventPageController::class, 'store'])->middleware('permission:events.create')->name('app.events.store');
    Route::patch('/app/events/{event}', [EventPageController::class, 'update'])->middleware('permission:events.update')->name('app.events.update');
    Route::get('/app/events/{event}', [EventPageController::class, 'show'])->middleware('permission:events.view')->name('app.events.show');
    Route::post('/app/events/{event}/publish', [EventPageController::class, 'publish'])->middleware('permission:events.publish')->name('app.events.publish');

    Route::get('/app/bookings', [BookingPageController::class, 'index'])->middleware('permission:bookings.view_any')->name('app.bookings.index');
    Route::post('/app/bookings', [BookingPageController::class, 'store'])->middleware('permission:bookings.create')->name('app.bookings.store');
    Route::patch('/app/bookings/{booking}/status', [BookingPageController::class, 'updateStatus'])->middleware('permission:bookings.update')->name('app.bookings.update-status');

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

    // Admin Membership Plan Management
    Route::get('/app/admin/membership-plans', [AdminMembershipPlanController::class, 'index'])->middleware('permission:membership_plans.create')->name('app.admin.membership-plans.index');
    Route::post('/app/admin/membership-plans', [AdminMembershipPlanController::class, 'store'])->middleware('permission:membership_plans.create')->name('app.admin.membership-plans.store');
    Route::patch('/app/admin/membership-plans/{membership_plan}', [AdminMembershipPlanController::class, 'update'])->middleware('permission:membership_plans.update')->name('app.admin.membership-plans.update');
    Route::delete('/app/admin/membership-plans/{membership_plan}', [AdminMembershipPlanController::class, 'destroy'])->middleware('permission:membership_plans.delete')->name('app.admin.membership-plans.destroy');

    // Admin Settings
    Route::get('/app/admin/settings/payments', [AdminSettingsController::class, 'paymentSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.payments');
    Route::post('/app/admin/settings/payments', [AdminSettingsController::class, 'updatePaymentSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.payments.update');
    Route::get('/app/admin/settings/openai', [AdminSettingsController::class, 'openaiSettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.openai');
    Route::post('/app/admin/settings/openai', [AdminSettingsController::class, 'updateOpenAISettings'])->middleware('permission:payment_settings.manage')->name('app.admin.settings.openai.update');

    // Admin All Events
    Route::get('/app/admin/events', [AdminEventController::class, 'index'])->middleware('permission:events.view_any')->name('app.admin.events.index');
    Route::post('/app/admin/events', [AdminEventController::class, 'store'])->middleware('permission:events.create')->name('app.admin.events.store');
    Route::patch('/app/admin/events/{event}', [AdminEventController::class, 'update'])->middleware('permission:events.update')->name('app.admin.events.update');
    Route::delete('/app/admin/events/{event}', [AdminEventController::class, 'destroy'])->middleware('permission:events.delete')->name('app.admin.events.destroy');

    // Admin Categories
    Route::get('/app/admin/categories', [AdminCategoryController::class, 'index'])->middleware('permission:events.view_any')->name('app.admin.categories.index');
    Route::get('/app/admin/categories/create', [AdminCategoryController::class, 'create'])->middleware('permission:events.create')->name('app.admin.categories.create');
    Route::post('/app/admin/categories', [AdminCategoryController::class, 'store'])->middleware('permission:events.create')->name('app.admin.categories.store');
    Route::get('/app/admin/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->middleware('permission:events.update')->name('app.admin.categories.edit');
    Route::patch('/app/admin/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:events.update')->name('app.admin.categories.update');
    Route::delete('/app/admin/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:events.delete')->name('app.admin.categories.destroy');

    // Admin FAQ Management
    Route::get('/app/admin/faqs', [AdminFaqController::class, 'index'])->name('app.admin.faqs.index');
    Route::post('/app/admin/faqs', [AdminFaqController::class, 'store'])->name('app.admin.faqs.store');
    Route::patch('/app/admin/faqs/{faq}', [AdminFaqController::class, 'update'])->name('app.admin.faqs.update');
    Route::delete('/app/admin/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('app.admin.faqs.destroy');
    Route::patch('/app/admin/faqs/{faq}/toggle', [AdminFaqController::class, 'toggleStatus'])->name('app.admin.faqs.toggle');

    // Payment Flow
    Route::match(['get', 'post'], '/app/payments/initiate/{plan}', [PaymentPageController::class, 'initiate'])->middleware('permission:membership_plans.subscribe')->name('app.payments.initiate');
    Route::get('/app/payments/success', [PaymentPageController::class, 'success'])->name('app.payments.success');
    Route::get('/app/payments/cancel', [PaymentPageController::class, 'cancel'])->name('app.payments.cancel');
    Route::get('/app/payments/history', [PaymentPageController::class, 'history'])->middleware('permission:payments.view')->name('app.payments.history');

    // AI Agreements
    Route::get('/app/agreements', [AgreementPageController::class, 'index'])->middleware('permission:agreements.view_any')->name('app.agreements.index');
    Route::get('/app/agreements/{agreement}', [AgreementPageController::class, 'show'])->middleware('permission:agreements.view_any')->name('app.agreements.show');
    Route::post('/app/agreements/generate/{booking}', [AgreementPageController::class, 'generate'])->middleware('permission:agreements.generate')->name('app.agreements.generate');
    Route::post('/app/agreements/{agreement}/accept', [AgreementPageController::class, 'accept'])->middleware('permission:agreements.accept')->name('app.agreements.accept');
    Route::post('/app/agreements/{agreement}/reject', [AgreementPageController::class, 'reject'])->middleware('permission:agreements.accept')->name('app.agreements.reject');
    Route::post('/app/agreements/regenerate/{booking}', [AgreementPageController::class, 'regenerate'])->middleware('permission:agreements.generate')->name('app.agreements.regenerate');

    Route::get('/app/agreement-log', [AgreementLogPageController::class, 'index'])->middleware('permission:agreement_log.view_any')->name('app.agreement-log.index');

    Route::get('/app/users', [UserAccessPageController::class, 'index'])->middleware('permission:users.view_any')->name('app.users.index');
    Route::post('/app/users', [UserAccessPageController::class, 'store'])->middleware('permission:users.create')->name('app.users.store');
    Route::patch('/app/users/{user}', [UserAccessPageController::class, 'update'])->middleware('permission:users.update')->name('app.users.update');
    Route::delete('/app/users/{user}', [UserAccessPageController::class, 'destroy'])->middleware('permission:users.delete')->name('app.users.destroy');

    Route::get('/app/roles', [RolePageController::class, 'index'])->middleware('permission:roles.view_any')->name('app.roles.index');
    Route::post('/app/roles', [RolePageController::class, 'store'])->middleware('permission:roles.create')->name('app.roles.store');
    Route::patch('/app/roles/{role}', [RolePageController::class, 'update'])->middleware('permission:roles.update')->name('app.roles.update');
    Route::delete('/app/roles/{role}', [RolePageController::class, 'destroy'])->middleware('permission:roles.delete')->name('app.roles.destroy');

    Route::get('/app/permissions', [PermissionPageController::class, 'index'])->middleware('permission:permissions.view_any')->name('app.permissions.index');
    Route::post('/app/permissions', [PermissionPageController::class, 'store'])->middleware('permission:permissions.create')->name('app.permissions.store');
    Route::patch('/app/permissions/{permission}', [PermissionPageController::class, 'update'])->middleware('permission:permissions.update')->name('app.permissions.update');
    Route::delete('/app/permissions/{permission}', [PermissionPageController::class, 'destroy'])->middleware('permission:permissions.delete')->name('app.permissions.destroy');

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
