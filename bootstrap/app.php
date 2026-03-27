<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\TrackUserSession::class,
        ]);

        $middleware->alias([
            // Middlewares propios
            'require.2fa'         => \App\Http\Middleware\RequiresTwoFactor::class,
            'require.consent'     => \App\Http\Middleware\RequiresConsent::class,
            // Spatie Laravel Permission — OBLIGATORIO en Laravel 12 (sin Kernel.php)
            'role'                => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'          => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'  => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
