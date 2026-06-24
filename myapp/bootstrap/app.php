<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: '',  // We manage versioning in routes/api.php ourselves
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Force JSON responses on all API routes
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // Sanctum stateful middleware for SPA cookie-based auth (optional)
        $middleware->statefulApi();

        // Middleware aliases
        $middleware->alias([
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Global exception handling is in app/Exceptions/Handler.php
        // which is auto-discovered by Laravel 12
    })
    ->create();
