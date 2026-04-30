<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'                    => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'              => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'      => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.deletion.status'   => \App\Http\Middleware\CheckAccountDeletionStatus::class,
        ]);

        // Run canonical-host redirect FIRST (before anything else) so a
        // dashboard.* hit doesn't waste a session lookup or auth check
        // before being sent to gigresource.com.
        $middleware->web(prepend: [
            \App\Http\Middleware\CanonicalDomainRedirect::class,
        ]);

        // Automatically gate every authenticated request through the deletion check
        $middleware->web(append: [
            \App\Http\Middleware\CheckAccountDeletionStatus::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
