<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Dashboard\AdminMembershipPlanController;
use App\Http\Controllers\Dashboard\AgreementLogPageController;
use App\Http\Controllers\Dashboard\BookingPageController;
use App\Http\Controllers\Dashboard\ChatPageController;
use App\Http\Controllers\Dashboard\EventPageController;
use App\Http\Controllers\Dashboard\MembershipPlanPageController;
use App\Http\Controllers\Dashboard\MessagePageController;
use App\Http\Controllers\Dashboard\PermissionPageController;
use App\Http\Controllers\Dashboard\RolePageController;
use App\Http\Controllers\Dashboard\UserAccessPageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->middleware('permission:dashboard.view')->name('dashboard');

    Route::redirect('/home', '/dashboard')->name('home');

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
