<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\Dashboard\AgreementLogPageController;
use App\Http\Controllers\Dashboard\BookingPageController;
use App\Http\Controllers\Dashboard\EventPageController;
use App\Http\Controllers\Dashboard\MessagePageController;
use App\Http\Controllers\Dashboard\PermissionPageController;
use App\Http\Controllers\Dashboard\RolePageController;
use App\Http\Controllers\Dashboard\UserAccessPageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

    Route::get('/app/messages', [MessagePageController::class, 'index'])->middleware('permission:messages.view_any')->name('app.messages.index');
    Route::post('/app/messages', [MessagePageController::class, 'store'])->middleware('permission:messages.create')->name('app.messages.store');

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
